<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\base;

use app\core\base\Wen;
use Exception;
use app\core\router\RouterProvider;
use app\core\logger\LoggerProvider;
use app\core\i18n\I18NProvider;
use app\core\db\DB;

/**
 * 核心应用类
 *
 */
class Application
{

    public $config;

    public $module;

    public $controller;

    public $action;

    public $logger;

    public $i18n;

    public $db;

    public $cache;

    private $enableXHProf = false;

	public function __construct()
    {
        //加载配置文件，默认应用配置
        $this->loadConfig();

        //获得当前语言，用于多语言本地化
        $this->loadLanguage();

        //初始化请求路由器
        $this->initRouter();

        //初始化日志
        $this->initLogger();

        //所有工作完成后，设置静态类属性
        Wen::setApp($this);
    }

    public function __destruct() {

    }

    public function run()
    {
        $this->runAction();
    }

    private function loadConfig()
    {
        $this->config = require_once APP_ROOT . DS . 'config' . DS . 'app.config';
    }

    private function loadLanguage()
    {
        $config = isset($this->config['i18n']) ? $this->config['i18n'] : '';
        $this->i18n = new I18NProvider($config);
    }

    private function initRouter()
    {
        $config = isset($this->config['router']) ? $this->config['router'] : '';
        $router = new RouterProvider($config);
        $this->module = $router->module;
        $controller = 'app/modules' . DS . $router->module . DS . $router->controller;
        $this->controller = str_replace('/', '\\', $controller);
        $this->action = $router->action;
    }

    private function initLogger()
    {
        $config = isset($this->config['logger']) ? $this->config['logger'] : '';
        $this->logger = new LoggerProvider($config);
    }

    private function runAction()
    {
        $controllerName = $this->controller;

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $this->action)) {
                $controller->{$this->action}();
            } else {
                throw new Exception('请求方法：' . $this->controller.'->'.$this->action . '不存在！', 500);
                exit;
            }
        } else {
            throw new Exception('控制器' . $this->controller . '不存在，检查namespace是否正确！', 500);
            exit;
        }
    }

    
    
}