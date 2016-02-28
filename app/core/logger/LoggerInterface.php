<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */


namespace app\core\logger;


/**
 * 日志接口，根据依赖倒置原则IoC，让应用层(高层模块)定义接口，由具体实现者(底层模块）实现接口
 * 
 * 这样做的好处：
 * 1. 提高灵活性，可灵活切换不同的路由方案
 * 2. 降低模块耦合度，路由方案的更换，不会影响应用业务逻辑
 *
 * Wen框架默认提供文件日志。业务如果需要其他类型日志，比如redis分布式日志，可以实现此接口定义的方法来更换日志记录器。
 * 
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
interface LoggerInterface
{
	public function error($msg, $fileName);

	public function notice($msg, $fileName);

	public function info($msg, $fileName);

	public function debug($msg, $fileName);

	public function setLoggerFormat($format);
}