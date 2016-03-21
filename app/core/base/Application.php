<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
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
 * 核心应用类，框架的主心骨，初始化各个核心模块，由静态类 app\core\base\Wen创建实例，
 * 这样好处是，业务层通过静态类Wen就可以获得核心模块的实例对象，提供统一访问入口。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class Application
{

    /**
     * @var array app.config配置文件内容，返回的是数组
     */
    public $config;

    /**
     * @var string 当前请求的模块，根据路由规则获取
     */
    public $module;

    /**
     * @var string 当前请求的控制器，根据路由规则获取
     */
    public $controller;

    /**
     * @var string 当前请求的方法，根据路由规则获取
     */
    public $action;

    /**
     * @var \app\core\logger\xxx 实例，由app.config配置文件里的logger['class']指定
     */
    public $logger;

    /**
     * @var \app\core\i18n\xxx 实例，由app.config配置文件里的i18n['class']指定
     */
    public $i18n;

    /**
     * @var PDO 实例
     */
    public $db;

    /**
     * @var \app\core\caching\xxx 实例，由app.config配置文件里的cache['class']指定
     */
    public $cache;

    /**
     * @var bool 是否开启xhprof性能分析
     */
    private $enableXHProf = false;


	public function __construct($config='')
    {
        //加载配置文件，默认应用配置
        $this->loadConfig($config);

        $this->monitorProfilerStart();

        //获得当前语言，用于多语言本地化
        $this->loadLanguage();

        //初始化请求路由器
        $this->initRouter();

        //初始化日志
        $this->initLogger();

        //初始化数据库
        $this->initDB();

        //初始化缓存
        $this->initCache();

        //所有工作完成后，设置静态类属性
        Wen::setApp($this);
    }

    public function __destruct() {

       $this->monitorProfilerEnd();

    }

    /**
     * 准备就绪，开始执行
     *
     */
    public function run()
    {
        $this->runAction();
    }

    /**
     * 执行处理当前请求的控制器->方法
     *
     */
    private function runAction()
    {
        $controllerName = $this->controller;

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $this->action)) {
                $controller->{$this->action}();
            } else {
                throw new Exception( Wen::t('Action not exists',['action'=>$this->controller.'->'.$this->action]), 500);
            }
        } else {
            throw new Exception( Wen::t('Controller not exists',['controller'=>$this->controller]), 500);
        }
    }

    /**
     * 开始xhprof性能分析
     *
     */
    private function monitorProfilerStart()
    {
        $config = isset($this->config['xhprof']) ? $this->config['xhprof'] : '';
        if(isset($config['enable']) && $config['enable'] && extension_loaded('xhprof')) {
            if(mt_rand(1, $config['requestTimes']) === 1) {
                if(isset($config['noBuiltins']) && $config['noBuiltins']) {
                    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY); 
                } else {
                    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
                }
                $this->enableXHProf = true;
            }
        }
    }

    /**
     * 结束xhprof性能分析，并把分析结果保存到文件里，可以通过xhprof-viewer查看分析结果
     * 
     * xhprof-viewer：为开源项目，github: https://github.com/daviscai/xhprof-viewer
     */
    private function monitorProfilerEnd()
    {
        if($this->enableXHProf) {
            // stop profiler
            $xhprofData = xhprof_disable();

            $config = isset($this->config['xhprof']) ? $this->config['xhprof'] : '';

            $fileName = $config['fileDir'] . DS . date('YmdHis').mt_rand(100,10000).'.xhprof';
            $file = fopen($fileName, 'w');
            if($file) {
                fwrite($file, serialize($xhprofData));
                fclose($file);
            } else {
                $this->logger->error('save xhprof result faild');
            }
        }
    }

    /**
     * 加载配置文件
     *
     */
    private function loadConfig($configFile='')
    {   
        if($configFile) {
            $file = $configFile;
        }else{
            $file = APP_ROOT . DS . 'config' . DS . 'app.config';
        }
        
        if(file_exists($file)) {
            $this->config = require_once $file;
        }else{
            throw new Exception('App base config file not exists:'.$file, 500);
        }
        
    }

    /**
     * 初始化i18n实例，可参看 app\core\base\Wen::t()方法是如何使用该实例。
     *
     */
    private function loadLanguage()
    {
        $config = isset($this->config['i18n']) ? $this->config['i18n'] : '';
        $this->i18n = new I18NProvider($config);
    }

    /**
     * 初始化路由器实例，并返回当前请求的模块、控制器和方法
     *
     */
    private function initRouter()
    {
        $config = isset($this->config['router']) ? $this->config['router'] : '';
        $router = new RouterProvider($config);
        $this->module = $router->module;
        $controller = 'app/modules' . DS . $router->module . DS . $router->controller;
        $this->controller = str_replace('/', '\\', $controller);
        $this->action = $router->action;
    }

    /**
     * 初始化日志实例
     *
     */
    private function initLogger()
    {
        $config = isset($this->config['logger']) ? $this->config['logger'] : '';
        $this->logger = new LoggerProvider($config);
    }

    /**
     * 初始化DB操作实例
     *
     */
    private function initDB()
    {
        $config = [];
        $dbFile = APP_ROOT . DS . 'config' . DS . 'db.config';
        if(file_exists($dbFile)){
            $config = require_once $dbFile;
        }
        
        if(!empty($config)) {
            $this->db = new DB($config);
        }
    }

    /**
     * 初始化缓存实例
     *
     */
    private function initCache()
    {
        $config = isset($this->config['cache']) ? $this->config['cache'] : '';
        
        if(!empty($config)) {
            //通过Wen静态类的createObject方法创建对象，实现依赖注入，参考自Yii2的Di实现
            $this->cache = Wen::createObject($config);
        }
    }

    
    
    
}