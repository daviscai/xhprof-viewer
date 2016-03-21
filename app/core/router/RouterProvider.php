<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\core\router;

use Exception;
use app\core\base\Wen;

/**
 * 路由器服务提供者，统一接口，通过setRouter方法实例化具体的路由器，实现依赖注入
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
            throw new Exception( Wen::t('cannot find router class'), 500);
        }

        $routerClass = $router['class'];
        
        if(!class_exists($routerClass)) {
            throw new Exception( Wen::t('cannot create router instance',['class'=>$routerClass]), 500);
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