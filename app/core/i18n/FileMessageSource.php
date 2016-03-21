<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\i18n;

use Exception;
use \app\core\i18n\I18NInterface;

/**
 * 多国语言文本配置实现类
 * 
 * 通过接口方式实现依赖注入
 *
 */
class FileMessageSource implements I18NInterface 
{
    private $configDir;

    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * 翻译
     *
     * @param string $message 配置文件里的key
     * @param array $params 动态替换的参数
     * @param string $language 目标语言
     * @return string 翻译后的文本信息
     */
    public function translate($message, $params = [], $language = null)
    {
        $messagesMap = $this->loadMessagesFromFile($this->configDir, $language);
        $msg = isset($messagesMap[$message]) ? $messagesMap[$message] : $message;
        if(!empty($params)){
            $p = [];
            foreach ((array) $params as $name => $value) {
                $p['{' . $name . '}'] = $value;
            }
            return ($p === []) ? $msg : strtr($msg, $p);
        }

        return $msg;
    }

    /**
     * 加载语言包文件，是一个数组
     *
     * @param string $dir 语言包目录
     * @param string $language 目标语言
     * @return array 文本配置数组
     */
    private function loadMessagesFromFile($dir, $language)
    {
        $messageFile = $dir . $language . '.config';
        if (is_file($messageFile)) {
            $messages = include($messageFile);
            if (!is_array($messages)) {
                $messages = [];
            }

            return $messages;
        } else {
            return null;
        }
    }
}
