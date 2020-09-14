<?php
namespace App\SearchBuilders;

use App\Models\Category;

class ProductSearchBuilder {

    // 初始化查询
    protected $params = [
        'index' => 'products',
        'type' => '_doc',
        'body' => [
            'query' => [
                'bool' => [
                    'filter' => [],
                    'must' => []
                ]
            ]
        ]
    ];

    /**
     * 分页查询
     * @param $size
     * @param $page
     * @return $this
     */
    public function paginate($size, $page)
    {
        $this->params['body']['from'] = ($page - 1) * $size;
        $this->params['body']['size'] = $size;

        return $this;
    }

    /**
     * 筛选上架商品
     * @return $this
     */
    public function onSale()
    {
        $this->params['body']['query']['bool']['filter'][] = ['term'=>['on_sale'=>true]];

        return $this;
    }

    /**
     * 按分类筛选商品
     * @param Category $category
     * @return $this
     */
    public function category(Category $category)
    {
        if ( $category->is_directory ) {
            $this->params['body']['query']['bool']['filter'][] = [
//                    'prefix' => ['category_path' => $category->path.$category->id.'-'], // 因为我们的父级路径是是用 逗号 拼接, 所以这种方式不适合目前逻辑
                'terms' => ['category_id' => Category::query()->whereRaw('FIND_IN_SET("'.$category->id.'", `path`)')->pluck('id')->toArray()]
            ];
        } else {
            $this->params['body']['query']['bool']['filter'][] = ['term'=>['category_id'=>$category->id]];
        }

        return $this;
    }

    /**
     * 添加搜索词
     * @param $keywords
     * @return $this
     */
    public function keywords($keywords)
    {
        // 如果参数不是数组则转为数组
        $keywords = is_array($keywords) ? $keywords : [$keywords];
        foreach ( $keywords as $keyword ) {
            $this->params['body']['query']['bool']['must'] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                        'title^3',
                        'long_title^2',
                        'category^2',
                        'description',
                        'skus_title',
                        'skus_description',
                        'properties_value'
                    ]
                ]
            ];
        }

        return $this;
    }

    /**
     * 分面搜索聚合
     * @return $this
     */
    public function aggregateProperties()
    {
        $this->params['body']['aggs'] = [
            'properties' => [
                'nested' => [
                    'path' => 'properties'
                ],
                'aggs' => [
                    'properties' => [
                        'terms' => [
                            'field' => 'properties.name'
                        ],
                        'aggs' => [
                            'value' => [
                                'terms' => [
                                    'field' => 'properties.value'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this;
    }

    /**
     * 添加一个按商品属性筛选的条件
     * @param $name
     * @param $value
     * @return $this
     */
    public function propertyFilter($name, $value, $type='filter')
    {
        $this->params['body']['query']['bool'][$type][] = [
            'nested' => [
                'path' => 'properties',
                'query' => [
                    ['term'=>['properties.search_value'=>$name.':'.$value]]
                ]
            ]
        ];

        return $this;
    }

    /**
     * 添加排序
     * @param $field
     * @param $direction
     * @return $this
     */
    public function orderBy($field, $direction)
    {
        if ( !isset($this->params['body']['sort']) ) {
            $this->params['body']['sort'] = [];
        }
        $this->params['body']['sort'][] = [$field=>$direction];

        return $this;
    }

    /**
     * 设置 minimum_should_match 参数
     * @param $count
     * @return $this
     */
    public function minShouldMatch($count)
    {
        $this->params['body']['query']['bool']['minimum_should_match'] = (int)$count;

        return $this;
    }

    /**
     * 返回构造好的查询参数
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}