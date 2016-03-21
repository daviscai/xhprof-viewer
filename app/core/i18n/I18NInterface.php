<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */


namespace app\core\i18n;


/**
 * 多国语言国际化接口，根据依赖倒置原则IoC，让应用层(高层模块)定义接口，由具体实现者(底层模块）实现接口
 * 
 * 这样做的好处：
 * 1. 提高灵活性，可灵活切换不同的路由方案
 * 2. 降低模块耦合度，路由方案的更换，不会影响应用业务逻辑
 *
 * Wen框架默认提供文件配置方式支持多国语言。业务如果需要其他方式，比如gettext，可以实现此接口定义的方法来更换实现方式。
 * 
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
interface I18NInterface
{	
	//由实现类处理翻译逻辑
	public function translate($message, $params = [], $language = null);

}