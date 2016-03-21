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
use ReflectionClass;

/**
 * 一个实现了依赖注入[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection)的容器。
 *
 * 一个实现了依赖注入的容器，它知道如何实例化和初始化对象，以及必要的属性值，更多信息可以看
 * [Martin Fowler's article](http://martinfowler.com/articles/injection.html)，依赖反转(倒置)原则
 *
 * 容器目前只支持构造函数注入
 *  
 * ```php
 * $DiContainer = new Di();
 * $DiContainer->get($class, $params, $config);
 * ```
 * 该DI实现参考自Yii2.0，为了代码的简单易于维护，去掉了set参数方式。
 *
 */
class Di
{
    /**
     * @var array 单例模式，存放已经创建的对象
     */
    private $_singletons = [];

    /**
     * @var array 构造函数的形参
     */
    private $_params = [];

    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $_reflections = [];

    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $_dependencies = [];


    /**
     * 返回需要创建的对象实例
     *
     * 或许需要提供构造函数的参数(`$params`) ，或者是对象的属性配置(`$config`)，这样，创建对象时候，就会初始化相关属性值
     * 
     * 只有第一次创建实例时候才会初始化属性值。
     *
     * @param string $class 类名
     * @param array $params 构造函数的参数
     * @param array $config 对象的属性，会在创建实例后进行属性赋值
     * @return 需要创建的对象实例
     * @throws Exception 
     */
    public function get($class, $params = [], $config = [])
    {
        //已存在单例数组里，直接返回该对象实例
        if (isset($this->_singletons[$class])) {
            // singleton
            return $this->_singletons[$class];
        } else {
            //创建对象实例
            $object = $this->build($class, $params, $config);
        }

        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

    
    /**
     * 创建类对象实例
     *
     * @param string $class 类名
     * @param array $params 构造函数参数
     * @param array $config 对象的属性，会在创建实例后进行属性赋值
     * @return object 对象实例
     */
    protected function build($class, $params, $config)
    {
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class, $config);

        if(!empty($params)){
            foreach ($params as $index => $param) {
                $dependencies[$index] = $param;
            }
        }
        
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        $object = $reflection->newInstanceArgs($dependencies);

        //初始化对象属性值
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }



    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class, $config)
    {

        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if (!empty($config)) {
                    $dependencies[] = $config;
                } else {
                    $dependencies[] = $param->getDefaultValue();
                }
            }
        }
        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }
}
