<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\core\i18n;

use Exception;
use \app\core\base\Wen;

/**
 * 多国语言国际化服务提供者，统一接口，通过set方法实例化具体的实现类，实现依赖注入
 * 
 * 这样做的好处：解耦，高层业务不依赖底层实现
 *
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class I18NProvider
{
    /**
     * @var object 多国语言具体实现类对象
     */
	private $i18n;

    /**
     * @var string 当前语言
     */
    private $language;
	
    public function __construct($config)
    {
        $this->set($config);
        $this->setLanguage($config);
    }   

    /**
     * 翻译文本信息
     * 
     * @param  string $message 英文字符串，对应配置文件里数组中的key
     * @param  array $params 把该数组里的key=>value替换掉文本里的占位字符key
     * @return string 翻译后的文本信息
     */
    public function translate($message, $params = [])
    {
        return $this->i18n->translate($message, $params, $this->language);
    }

    /**
     * 根据 app/config/app.config 配置文件找到具体实现类以及语言包目录
     * 
     * @param  array $config i18n配置
     * @return void
     */
    private function set($config)
    {
        if(empty($config) || empty($config['class'])) {
            throw new Exception( Wen::t('cannot find i18n class'), 500);
        }
        
        $cls = $config['class'];
        
        if(!class_exists($cls)) {
            throw new Exception( Wen::t('cannot create i18n instance',['class'=>$cls]), 500);
        }

        $dir = isset($config['dir']) ?  ROOT . '/' .$config['dir'] : ROOT . '/app/config/lang/';
        $this->i18n = new $cls($dir);
    }

    /**
     * 根据用户客户端语言找到对应的语言包
     * 
     * @param  array $config i18n配置
     * @return void
     */
    private function setLanguage($config)
    {
        $defaultLanguage = isset($config['language']) ? $config['language'] : 'en-US';
        preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
        if(isset($matches[1]) && $matches[1]){
            $langArr = explode('-',$matches[1]);
            $clientLanguage = $langArr[0].'-'.strtoupper($langArr[1]);
            $this->language = $clientLanguage;
        }else{
            $this->language = $defaultLanguage;
        }
    }

   
   
}