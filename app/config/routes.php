<?php
defined('ROOT') OR exit('No direct script access allowed');

$route['default_module'] = 'def';
$route['default_controller'] = 'app';
$route['default_action'] = 'index';
$route['404_override'] = 'def/app/page404';
$route['translate_uri_dashes'] = FALSE;

// 模式（URI） = 模块/控制器/action/参数 

// $route['product'] = 'def/product/index';
// $route['product/(:num)'] = 'def/product/detail/id=$1';
// $route['product/(:any)'] = 'def/product/detail/id=$1';
//$route['([a-z]+)/(\w+)'] = 'def/$1/$2';

//$route['(201\d)/([\w\d-_]*)/([\w\d-_]*)'] = 'y_$1/$2/$3';
//$route['login/(.+)'] = 'auth/login/login/$1';

// $route['product/([a-zA-Z]+)/edit/(\d+)'] = function ($product_type, $id)
// {
//     return 'def/product/edit/type=' . strtolower($product_type) . '/id=' . $id;
// };

// $route['goods']['put'] = 'def/product/insert';
// $route['goods/(:num)']['DELETE'] = 'def/product/delete/id=$1';

