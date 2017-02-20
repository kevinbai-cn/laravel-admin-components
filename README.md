laravel-admin-components
=====

说明
------------

** 项目基于 [laravel-admin](https://github.com/z-song/laravel-admin) 修改 **
> `laravel-admin` 是一个可以快速帮你构建后台管理的工具，它提供的页面组件和表单元素等功能，能帮助你使用很少的代码就实现功能完善的后台管理功能。

本项目去掉了 `laravel-admin` 的权限系统，仅仅提供后台 `UI` 组件的使用，适用于对权限系统有特定需求的开发者。

安装
------------

首先确保安装好了 `laravel` ，然后安装包
```
Laravel 5.3
composer require encore/laravel-admin 1.3.*
```
注意：目前只有 `5.3` 的修改版本  
在 `config/app.php` 加入 `ServiceProvider`
```
Kevinbai\Admin\Providers\AdminServiceProvider::class
```
然后发布资源
```
php artisan vendor:publish --tag=laravel-admin-components
```
在 `routes/web.php` 中配置路由
```
Route::get('/admin', 'HomeController@index');
```
最后，访问 `http://hostname/admin` 便能看到一个简洁的后台页面了

文档
------------

关于各个组件的具体使用方法，可以参考 [laravel-admin](https://github.com/z-song/laravel-admin) 的官方文档


License
------------
`laravel-admin-components` is licensed under [The MIT License (MIT)](LICENSE).
