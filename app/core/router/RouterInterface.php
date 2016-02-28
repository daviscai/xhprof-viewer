<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */


namespace app\core\router;


/**
 * 路由器接口，根据依赖倒置原则IoC，让应用层(高层模块)定义接口，由具体实现者(底层模块）实现接口
 * 
 * 这样做的好处：
 * 1. 提高灵活性，可灵活切换不同的路由方案
 * 2. 降低模块耦合度，路由方案的更换，不会影响应用业务逻辑
 *
 * Wen框架会默认提供一种路由方案。
 * 
 * nginx的配置通常为：location / { try_files $uri $uri/ /index.php?$uri&$args; }
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
interface RouterInterface
{
	public function getModule();

	public function getController();

	public function getAction();

	public function secureUrl();

}