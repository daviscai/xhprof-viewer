<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\core\router;

use Exception;

/**
 * 路由器服务提供者，统一接口，通过setRouter方法实例化具体的路由器，实现依赖注入
 * 
 * 这样做的好处：解耦，高层业务不依赖底层实现
 *
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class RouterProvider
{

	private $router;

    public $module; 

    public $controller; 

    public $action;

	
    public function __construct($config)
    {
        $this->setRouter($config);

        $this->getModule();

        $this->getController();

        $this->getAction();
    }

    private function setRouter($router)
    {
        if(empty($router) || empty($router['class'])  ) {
            throw new Exception('找不到路由器，请在配置文件里设置', 500);
        }

        $routerClass = $router['class'];
        
        if(!class_exists($routerClass)) {
            throw new Exception('实例化路由器失败，'.$routerClass.' 类不存在', 500);
        }

        $configFile = isset($router['configFile']) ? ROOT . DS . $router['configFile'] : '';

        $this->router = new $routerClass($configFile);

        //防止xss，过滤url参数
        $this->router->secureUrl();
    }

    private function getModule()
    {
        $this->module = $this->router->getModule();
    }

    private function getController()
    {
        $this->controller = $this->router->getController();
    }

    private function getAction()
    {
        $this->action = $this->router->getAction();
    }
}