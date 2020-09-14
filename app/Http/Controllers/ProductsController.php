<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, ProductSearchBuilder $productSearchBuilder)
    {
        $page = $request->input('page', 1);
        $perPage = 16;
        // 新建查询构造器对象, 设置只搜索上架商品, 设置分页
        $builder = $productSearchBuilder->onSale()->paginate($perPage, $page);

        // 是否有提交 order 参数, 如果有就赋值给 $order 变量
        // order 参数用来控制商品的排序规则
        if ( $order = $request->input('order', '') ) {
            // 是否以 _asc 或 _desc 结尾
            if ( preg_match('/^(.+)_(asc|desc)$/', $order, $m) ) {
                // 如果字符串开头是这 3 个字符串之一, 说明是合法的排序值
                if ( in_array($m[1], ['price', 'sold_count', 'rating']) ) {
                    // 调用查询构造器的排序
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        // 按类目筛选功能
        if ( $request->input('category_id') && $category = Category::find($request->input('category_id')) ) {
            // 调用查询构造器的类目筛选
            $builder->category($category);
        }

        if ( $search = $request->input('search', '') ) {
            // 将搜索词根据空格拆分成数组, 并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            // 调用查询构造器的关键词筛选
            $builder->keywords($keywords);
        }

        if ( $search || isset($category) ) {
            // 调用查询构造器的分面搜索
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        // 从用户请求中获取参数 filters
        if ( $filterString = $request->input('filters') ) {
            // 将获取到的字符串用符号 | 拆分成数组
            $filterArray = explode('|', $filterString);
            foreach ( $filterArray as $filter ) {
                // 将字符串用符号 : 拆分成两部分并且分别赋值给 $name 和 $value 两个变量
                list($name, $value) = explode(':', $filter);
                // 将用户筛选的属性添加到数组中
                $propertyFilters[$name] = $value;
                // 调用查询构造器的属性筛选
                $builder->propertyFilter($name, $value);
            }
        }

        $result = app('es')->search($builder->getParams());
        // 通过 collect 函数将返回结果转为集合, 并通过集合的 pluck 方法取到返回的商品 ID 数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        // 通过 whereIn 方法从数据库中取出商品数据
        $products = Product::query()->byIds($productIds)->get();
        // 返回一个 LengthAwarePaginator 对象
        $pager = new LengthAwarePaginator($products, $result['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false) // 手动构建分页的 url
        ]);

        $properties = [];
        // 如果返回结果里有 aggregations 字段, 说明做了分页搜索
        if ( isset($result['aggregations']) ) {
            // 使用 collect 函数将返回值转为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket){
                    // 通过 map 方法 取出我们需要的字段
                    return [
                        'key' => $bucket['key'],
                        'value' => collect($bucket['value']['buckets'])->pluck('key')->all()
                    ];
                })
                ->filter(function ($property) use ($propertyFilters){
                    // 过滤掉只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['value']) > 1 && !isset($propertyFilters[$property['key']]);
                });
        }

        return view('products.index', [
            'products' => $pager,
            'filters' => [
                'search' => $search,
                'order' => $order
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters
        ]);
    }

    /**
     * @param Product $product
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws InvalidRequestException
     */
    public function show(Product $product, Request $request, ProductService $productService)
    {
        // 判断商品是否已经上架, 如果没有上架则抛出异常
        if ( !$product->on_sale ) {
            throw new InvalidRequestException('商品未上架');
        }
        $favored = false;
        if ( $user = $request->user() ) {
            // 搜索用户是否已经收藏商品
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku']) // 预先加载关联关系
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at') // 筛选出已评价的
            ->orderBy('reviewed_at', 'desc') // 按评价时间倒序
            ->limit(10)
            ->get();

        $similarProductIds = $productService->getSimilarProductIds($product, 4);
        // 根据 Elasticsearch 搜索出来的商品 ID 从数据库中读取商品数据
        $similarProducts = Product::query()->byIds($similarProductIds)->get();

        return view('products.show', [
            'product' => $product,
            'favored'=>$favored,
            'reviews' => $reviews,
            'similar' => $similarProducts
        ]);
    }

    /**
     * @param Product $product
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ( $user->favoriteProducts()->find($product->id) ) {
            return success();
        }
        $user->favoriteProducts()->attach($product);
        return success();
    }

    /**
     * @param Product $product
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach( $product );
        return success();
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', ['products' => $products]);
    }

}
