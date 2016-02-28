<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\base;

use Exception;
use \app\core\base\Application;
use \app\core\base\Di;

/**
 * 核心应用静态类，应用层业务逻辑可以通过该类获得需要的对象实例，比如Application实例，日志实例等等
 *  
 * 这样的好处是，对外提供统一的访问方式，简单易用，提高开发效率。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class Wen
{
	/**
     * @var \wen\core\base\Application $app
     */
    public static $app;

    /**
     * 创建\wen\core\base\Application实例
     *
     * @return \wen\core\base\Application $app
     */
    public static function createApplication() {
        return new Application();
    }

    /**
     * 设置应用实例
     * 
     * @param  $app application
     */
    public static function setApp($app) { 
        if( $app instanceof Application ) { 
            self::$app = $app;   
        } 
    }

    /**
     * 返回当前应用实例，如业务层通过 Wen::app() 即可获得app实例
     * 
     * @return Application
     */
    public static function app() { 
        return self::$app;
    }

    /**
     * 多国语言国际化翻译静态方法
     * 
     * @param  string $message 英文字符串，对应配置文件里数组中的key
     * @param  array $params 把该数组里的key=>value替换掉文本里的占位字符key
     * @return string 翻译后的文本信息
     */
    public static function t($message, $params = [])
    {
        if (static::$app !== null) {
            return static::$app->i18n->translate($message, $params);
        } else {
            $p = [];
            foreach ((array) $params as $name => $value) {
                $p['{' . $name . '}'] = $value;
            }

            return ($p === []) ? $message : strtr($message, $p);
        }
    }

    /**
     * Configures an object with the initial property values.
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        if(empty($properties) || !is_array($properties)){
            return $object;
        }

        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }


    public static function createObject($type, array $params = [])
    {
        $DiContainer = new Di();
        if (is_string($type)) {
            return $DiContainer->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return $DiContainer->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return call_user_func($type, $params);
        } elseif (is_array($type)) {
            //return false;
            throw new Exception('Object configuration must be an array containing a "class" element.');
        } else {
            //return false;
            throw new Exception('Unsupported configuration type: ' . gettype($type));
        }
    }

}