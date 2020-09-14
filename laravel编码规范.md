#LARAVEL开发规范

####版本选择
选择 Laravel 版本时，应该 优先考虑 LTS 版本，因为安全性和稳定性考虑，商业项目开发中 不应该 使用最新版本的 『Laravel 一般发行版』

####开发专用扩展包
#####加载
开发专用的 provider 绝不在 config/app.php 里面注册，必须 在 app/Providers/AppServiceProvider.php 文件中使用如以下方式：
```php
public function register()
{
    if ($this->app->environment() == 'local') {
        $this->app->register('Laracasts\Generators\GeneratorsServiceProvider');
    }
}
```

####配置信息与环境变量
系统环境变量必须使用config()函数获取,在.env文件中配置
.env 文件中设置：
CDN_DOMAIN=cdndomain.com
config/app.php 文件中设置：
```php
'cdn_domain' => env('CDN_DOMAIN', null),
$cdn_domain = config("cdn_domain");
```

####辅助函数
#####存放位置
创建自己的辅助函数,必须把所有的自定义辅助函数存放于bootstrap文件夹中.并在bootstrap/app.php文件的最顶部进行加载:
```php
require __DIR__.'/func.php';
```

####工具统一
编码工具与调试工具要统一

####代码风格
代码风格 必须 严格遵循 PSR-2 规范。(自行百度PSR-2规范)

####路由器
#####路由闭包
绝不 在路由配置文件里书写『闭包路由』或者其他业务逻辑代码，因为一旦使用将无法使用 路由缓存 。路由器要保持干净整洁，绝不 放置除路由配置以外的其他程序逻辑。必须 优先使用 Restful 路由，配合资源控制器使用。超出 Restful 路由的,应该按照 Restful 配置路由。使用 resource 方法时，如果仅使用到部分路由，必须 使用 only 列出所有可用路由：
```php
Route::resource('photos', 'PhotosController', ['only' => ['index', 'show']]);
```
绝不 使用 except，因为 only 相当于白名单，相对于 except 更加直观。  路由使用白名单有利于养成『安全习惯』。
资源路由路由 URI 必须 使用复数形式，如：
/photos/create
/photos/{photo}
错误的例子如：
/photo/create
/photo/{photo}

#####路由模型绑定
在允许使用路由 模型绑定 的地方 必须 使用。模型绑定代码 必须 放置于 app/Providers/RouteServiceProvider.php 文件的 boot 方法中：
```php
public function boot()
{
    Route::bind('user_name', function ($value) {
        return User::where('name', $value)->first();
    });

    Route::bind('photo', function ($value) {
        return Photo::find($value);
    });

    parent::boot();
}
```

#####全局路由器参数
出于安全考虑，应该 使用全局路由器参数限制。
必须 在 RouteServiceProvider 文件的 boot 方法里定义模式：
```php
/**
 * 定义你的路由模型绑定，模式过滤器等。
 *
 * @param  \Illuminate\Routing\Router  $router
 * @return void
 */
public function boot(Router $router)
{
    $router->pattern('id', '[0-9]+');

    parent::boot($router);
}
```
模式一旦被定义，便会自动应用到所有使用该参数名称的路由上：
```php
Route::get('users/{id}', 'UsersController@show');
Route::get('photos/{id}', 'PhotosController@show');
```
只有在 id 为数字时，才会路由到控制器方法中，否则 404 错误。

#####路由命名
除了 resource 资源路由以外，其他所有路由都 必须 使用 name 方法进行命名。
必须 使用『资源前缀』作为命名规范，如下的 users.follow，资源前缀的值是 users.：
```php
Route::post('users/{id}/follow', 'UsersController@follow')->name('users.follow');
```

#####获取 URL
获取 URL 必须 遵循以下优先级：
$model->link()
route 方法
url 方法
在 Model 中创建 link() 方法：
```php
public function link($params = [])
{
    $params = array_merge([$this->id], $params);
    return route('models.show', $params);
}
```
所有单个模型数据链接使用：
```php
$model->link();
```
// 或者添加参数
```php
$model->link($params = ['source' => 'list'])
```
『单个模型 URI』经常会发生变化，这样做将会让程序更加灵活。
除了『单个模型 URI』，其他路由 必须 使用 route 来获取 URL：
```php
$url = route('profile', ['id' => 1]);
```
无法使用 route 的情况下，可以 使用 url 方法来获取 URL：
```php
url('profile', [1]);
```

####数据模型
#####放置位置
所有的数据模型文件，都 必须 存放在：app/Models/ 文件夹中。
命名空间：
```php
namespace App\Models;
```

#####User.php
Laravel 5.1 默认安装会把 User 模型存放在 app/User.php，必须 移动到 app/Models 文件夹中，并修改命名空间声明为 App/Models，同上。
为了不破坏原有的逻辑点，必须 全局搜索 App/User 并替换为 App/Models/User

#####使用基类
所有的 Eloquent 数据模型 都 必须 继承统一的基类 App/Models/Model，此基类存放位置为 /app/Models/Model.php，内容参考以下：
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model as EloquentModel;
class Model extends EloquentModel
{
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
```
以 Photo 数据模型作为例子继承 Model 基类：
```php
<?php
namespace App\Models;
class Photo extends Model
{
    protected $fillable = ['id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#####命名规范
数据模型相关的命名规范：  
数据模型类名 必须 为「单数」, 如：App\Models\Photo  
类文件名 必须 为「单数」，如：app/Models/Photo.php  
数据库表名字 必须 为「复数」，多个单词情况下使用「Snake Case」 如：photos, my_photos  
数据库表迁移名字 必须 为「复数」，如：2014_08_08_234417_create_photos_table.php  
数据填充文件名 必须 为「复数」，如：PhotosTableSeeder.php  
数据库字段名 必须 为「Snake Case」，如：view_count, is_vip  
数据库表主键 必须 为「id」  
数据库表外键 必须 为「resource_id」，如：user_id, post_id  
数据模型变量 必须 为「resource_id」，如：$user_id, $post_id  

#####利用 Trait 来扩展数据模型
有时候数据模型里的代码会变得很臃肿，应该 利用 Trait 来精简逻辑代码量，提高可读性，类似于 Ruby China 源码。
借鉴于 Rails 的设计理念：「Fat Models, Skinny Controllers」。
存放于文件夹：app/Models/Traits 文件夹中。

#####Repository
绝不 使用 Repository，因为我们不是在写 JAVA 代码，太多封装就成了「过度设计（Over Designed）」，极大降低了编码愉悦感，使用 MVC 够傻够简单。
代码的可读性，维护和开发的便捷性，直接关系到程序员开发时的愉悦感，直接影响到项目推进效率和程序 Debug 的速度。

#####关于 SQL 文件
绝不 使用命令行或者 PHPMyAdmin 直接创建索引或表。必须 使用 数据库迁移 去创建表结构，并提交版本控制器中；
绝不 为了共享对数据库更改就直接导出 SQL，所有修改都 必须 使用 数据库迁移 ，并提交版本控制器中；
绝不 直接向数据库手动写入伪造的测试数据。必须 使用 数据填充 来插入假数据，并提交版本控制器中。

#####全局作用域
Laravel 的 Model 全局作用域 允许我们为给定模型的所有查询添加默认的条件约束。
所有的全局作用域都 必须 统一使用 闭包定义全局作用域，如下：
```php
/**
 * 数据模型的启动方法
 *
 * @return void
 */
protected static function boot()
{
    parent::boot();

    static::addGlobalScope('age', function(Builder $builder) {
        $builder->where('age', '>', 200);
    });
}
```

####控制器
#####资源控制器
必须 优先使用 Restful 资源控制器 
必须 使用资源的复数形式，如：
类名：PhotosController
文件名：PhotosController.php
错误的例子：
类名：PhotoController
文件名：PhotoController.php

#####保持短小精炼
必须 保持控制器文件代码行数最小化，还有可读性。
不应该 为「方法」书写注释，这要求方法取名要足够合理，不需要过多注释；
应该 为一些复杂的逻辑代码块书写注释，主要介绍产品逻辑 - 为什么要这么做。；
不应该 在控制器中书写「私有方法」，控制器里 应该 只存放「路由动作方法」；
绝不 遗留「死方法」，就是没有用到的方法，控制器里的所有方法，都应该被使用到，否则应该删除；
绝不 在控制器里批量注释掉代码，无用的逻辑代码就必须清除掉。

####表单验证
#####表单请求验证类
必须 使用 表单请求 - FormRequest 类 来处理控制器里的表单验证。

#####验证类的 authorize
绝不 使用 authorize() 方法来做用户授权，用户授权我们会单独使用 Policy 授权策略 来实现。

#####使用基类
所有 FormRequest 表验证类 必须 继承 app/Http/Requests/Request.php 基类。基类文件如下：
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class Request extends FormRequest
{
    public function authorize()
    {
        // Using policy for Authorization
        return true;
    }
}
```

#####验证类命名
FormRequest 表验证类 必须 遵循 资源路由 方式进行命名，photos 对应 app/Http/Requests/PhotoRequest.php

#####类文件参考
FormRequest 表验证类文件请参考以下：
```php
<?php
namespace App\Http\Requests;
class PhotoRequest extends Request
{
    public function rules()
    {
        switch($this->method())
        {
            // CREATE
            case 'POST':
            {
                return [
                    // CREATE ROLES
                ];
            }
            // UPDATE
            case 'PUT':
            case 'PATCH':
            {
                return [
                    // UPDATE ROLES
                ];
            }
            case 'GET':
            case 'DELETE':
            default:
            {
                return [];
            };
        }
    }

    public function messages()
    {
        return [
            // Validation messages
        ];
    }
}
```

####授权策略
必须 使用 授权策略 类来做用户授权。

#####使用基类
所有 Policy 授权策略类 必须 继承 app/Policies/Policy.php 基类。基类文件如下：
```php
<?php
namespace App\Policies;
use Illuminate\Auth\Access\HandlesAuthorization;
class Policy
{
    use HandlesAuthorization;

    public function __construct()
    {
        //
    }

    public function before($user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }
}
```

#####授权策略命名
Policy 授权策略类 必须 遵循 资源路由 方式进行命名，photos 对应 /app/Policies/PhotoPolicy.php 。

#####类文件参考
Policy 授权策略类文件内容请参考以下：
```php
<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Photo;
class PhotoPolicy extends Policy
{
    public function update(User $user, Photo $photo)
    {
        return $user->isAuthorOf($photo);
    }

    public function destroy(User $user, Photo $photo)
    {
        return $user->isAuthorOf($photo);
    }
}
```

#####自动判断授权策略
应该 使用 自动判断授权策略方法，这样控制器和授权类的方法名就统一起来了。
```php
/**
 * 更新指定的文章。
 *
 * @param  int  $id
 * @return Response
 */
public function update($id)
{
    $post = Post::findOrFail($id);

    // 会自动调用 `PostPolicy` 类中的 `update` 方法。
    $this->authorize($post);

    // 更新文章...
}
```

####数据填充
#####factory 辅助函数
必须 使用 factory 方法来做数据填充，因为是框架提倡的，并且可以同时为测试代码服务。

#####运行效率
开发数据填充时，必须 特别注意 php artisan db:seed 的运行效率，否则随着项目的代码量越来越大，db:seed 的运行时间会变得越来越长，有些项目多达几分钟甚至几十分钟。
原则是：
Keep it lighting speed.
只有当 db:seed 运行起来很快的时候，才能完全利用数据填充工具带来的便利，而不是累赘。

#####批量入库
所有假数据入库操作，都 必须 是批量操作，配合 factory 使用以下方法：
```php
$users = factory(User::class)->times(1000)->make();
User::insert($users->toArray());
```
以上只执行一条数据库语句

#### Artisan 命令行
所有的自定义命令，都 必须 有项目的命名空间。
如：
```shell
php artisan phphub:clear-token
php artisan phphub:send-status-email
...
```
错误的例子为：
```shell
php artisan clear-token
php artisan send-status-email
...
```

####日期和时间
必须 使用 Carbon 来处理日期和时间相关的操作。
Laravel 5.1 中文的 diffForHumans 可以使用 jenssegers/date。
Laravel 5.3 及以上版本的 diffForHumans，只需要在 config/app.php 文件中配置 locale 选项即可 ：
```php
'locale' => 'zh-CN',
```

####前端开发
必须 使用 Laravel 官方前端工具做前端开发自动化；
必须 保证页面只加载一个 .css 文件；
必须 保证页面只加载一个 .js 文件；
必须 为 .css 和 .js 增加 版本控制；
必须 使用 SASS 来书写 CSS 代码；

####中间件
#####Auth 中间件
Auth 中间件 必须 书写在控制器的 __construct 方法中，并且 必须 使用 except 黑名单进行过滤，这样当你新增控制器方法时，默认是安全的。
```php
public function __construct()
{
    $this->middleware('auth', [            
        'except' => ['show', 'index']
    ]);
}
```

####Laravel 安全实践
#####关闭 DEBUG
Laravel Debug 开启时，会暴露很多能被黑客利用的服务器信息，所以，生产环境下请 必须 确保：
```php
APP_DEBUG=false
```

#####XSS
跨站脚本攻击（cross-site scripting，简称 XSS），具体危害体现在黑客能控制你网站页面，包括使用 JS 盗取 Cookie 等
默认情况下，在无法保证用户提交内容是 100% 安全的情况下，必须 使用 Blade 模板引擎的 {{ $content }} 语法会对用户内容进行转义。
Blade 的 {!! $content !!} 语法会直接对内容进行 非转义 输出，使用此语法时，必须 使用 HTMLPurifier for Laravel 5 来为用户输入内容进行过滤

#####SQL 注入
Laravel 的 查询构造器 和 Eloquent 是基于 PHP 的 PDO，PDO 使用 prepared 来准备查询语句，保障了安全性。
在使用 raw() 来编写复杂查询语句时，必须 使用数据绑定。
错误的做法：
```php
Route::get('sql-injection', function() {
    $name = "admin"; // 假设用户提交
    $password = "xx' OR 1='1"; // // 假设用户提交
    $result = DB::select(DB::raw("SELECT * FROM users WHERE name ='$name' and password = '$password'"));
    dd($result);
});
```
以下是正确的做法，利用 select 方法 的第二个参数做数据绑定：
```php
Route::get('sql-injection', function() {
    $name = "admin"; // 假设用户提交
    $password = "xx' OR 1='1"; // // 假设用户提交
    $result = DB::select(
        DB::raw("SELECT * FROM users WHERE name =:name and password = :password"),
        [
            'name' => $name,
            'password' => $password,
        ]
    );
    dd($result);
});
```
DB 类里的大部分执行 SQL 的函数都可传参第二个参数 $bindings

#####批量赋值
Laravel 提供白名单和黑名单过滤（$fillable 和 $guarded），开发者 应该 清楚认识批量赋值安全威胁的情况下合理灵活地运用。
批量赋值安全威胁，指的是用户可更新本来不应有权限更新的字段。举例，users 表里的 is_admin 字段是用来标识用户『是否是管理员』，某不怀好意的用户，更改了『修改个人资料』的表单，增加了一个字段：
```html
<input name="is_admin" value="1" />
```
这个时候如果你更新代码如下：
```php
Auth::user()->update(Request::all());
```
此用户将获取到管理员权限。可以有很多种方法来避免这种情况出现，最简单的方法是通过设置 User 模型里的 $guarded 字段来避免：
```php
protected $guarded = ['id', 'is_admin'];
```

#####CSRF
CSRF 跨站请求伪造是 Web 应用中最常见的安全威胁之一，具体请见 Wiki - 跨站请求伪造 或者 Web 应用程序常见漏洞 CSRF 的入侵检测与防范。
Laravel 默认对所有『非幂等的请求』强制使用 VerifyCsrfToken 中间件防护，需要开发者做的，是区分清楚什么时候该使用『非幂等的请求』。
幂等请求指的是：'HEAD', 'GET', 'OPTIONS'，既无论你执行多少次重复的操作都不会给资源造成变更。
所有删除的动作，必须 使用 DELETE 作为请求方法；
所有对数据更新的动作，必须 使用 POST、PUT 或者 PATCH 请求方法。

####Laravel 程序优化
#####配置信息缓存
生产环境中的 应该 使用『配置信息缓存』来加速 Laravel 配置信息的读取。
使用以下 Artisan 自带命令，把 config 文件夹里所有配置信息合并到一个文件里，减少运行时文件的载入数量：
```shell
php artisan config:cache
```
缓存文件存放在 bootstrap/cache/ 文件夹中。
可以使用以下命令来取消配置信息缓存：
```shell
php artisan config:clear
```
注意：配置信息缓存不会随着更新而自动重载，所以，开发时候建议关闭配置信息缓存，一般在生产环境中使用。可以配合 Envoy 任务运行器 使用，在每次上线代码时执行 config:clear 命令。

#####路由缓存
生产环境中的 应该 使用『路由缓存』来加速 Laravel 的路由注册。
路由缓存可以有效的提高路由器的注册效率，在大型应用程序中效果越加明显，可以使用以下命令：
```shell
php artisan route:cache
```
缓存文件存放在 bootstrap/cache/ 文件夹中。另外，路由缓存不支持路由匿名函数编写逻辑，详见：文档 - 路由缓存。
可以使用下面命令清除路由缓存：
```shell
php artisan route:clear
```
注意：路由缓存不会随着更新而自动重载，所以，开发时候建议关闭路由缓存，一般在生产环境中使用。可以配合 Envoy 任务运行器 使用，在每次上线代码时执行 route:clear 命令。

#####类映射加载优化
optimize 命令把常用加载的类合并到一个文件里，通过减少文件的加载，来提高运行效率。生产环境中的 应该 使用 optimize 命令来优化类的加载速度：
```shell
php artisan optimize --force
```
以上命令会在 bootstrap/cache/ 文件夹中生成缓存文件。你可以通过修改 config/compile.php 文件来添加要合并的类。在 production 环境中，参数 --force 不需要指定，文件就会自动生成。
要清除类映射加载优化，请运行以下命令：
```shell
php artisan clear-compiled
```
此命令会删除上面 optimize 生成的两个文件。
注意：此命令要运行在 php artisan config:cache 后，因为 optimize 命令是根据配置信息（如：config/app.php 文件的 providers 数组）来生成文件的。

#####自动加载优化
此命令不止针对于 Laravel 程序，适用于所有使用 composer 来构建的程序。此命令会把 PSR-0 和 PSR-4 转换为一个类映射表，来提高类的加载速度。
```shell
composer dumpautoload -o
```
注意：php artisan optimize --force 命令里已经做了这个操作。

#####使用 Memcached 来存储会话
每一个 Laravel 的请求，都会产生会话，修改会话的存储方式能有效提高程序效率。会话的配置文件是 config/session.php。生产环境中的 必须 使用 Memcached 或者 Redis 等专业的缓存软件来存储会话，应该 优先选择 Memcached：
```php
'driver' => 'memcached',
```

#####使用专业缓存驱动器
「缓存」是提高应用程序运行效率的法宝之一，Laravel 默认缓存驱动是 file 文件缓存，生产环境中的 必须 使用专业的缓存系统，如 Redis 或者 Memcached。应该 优先考虑 Redis。应该 避免使用数据库缓存。
```php
'default' => 'redis',
```

#####数据库请求优化
关联模型数据读取时 必须 使用 延迟预加载 和 预加载 。
临近上线时 必须 使用 Laravel Debugbar 或者 Clockwork 留意每一个页面的总 SQL 请求条数，进行数据库请求调优。

#####为数据集书写缓存逻辑
应该 合理的使用 Laravel 提供的缓存层操作，把从数据库里面拿出来的数据集合进行缓存，减少数据库的压力，运行在内存上的专业缓存软件对数据的读取也远远快于数据库。
```php
$hot_posts = Cache::remember('posts.hot_posts', $minutes = 30, function()
{
    return Post::getHotPosts();
});
```
remember 甚至连数据关联模型也都一并缓存了，多么方便呀。

#####使用即时编译器
可以 使用 OpCache 进行优化。OpCache 都能轻轻松松的让你的应用程序在不用做任何修改的情况下，直接提高 50% 或者更高的性能，PHPhub 之前做过一个实验，具体请见：使用 OpCache 提升 PHP 5.5+ 程序性能。

#####前端资源合并
作为优化的标准：
一个页面 应该 只加载一个 CSS 文件；
一个页面 应该 只加载一个 JS 文件。
另外，为了文件要能方便走 CDN，需要文件名 应该 随着修改而变化。
Laravel Elixir 提供了一套简便实用的方案，详细请见文档：Laravel Elixir 文档。