<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\logger;

use Exception;
use \app\core\base\Wen;
use \app\core\logger\LoggerInterface;

/**
 * 文件日志类, 实现了日志接口
 *
 * 定义了多种日志类型，可设置日志文件目录，在配置文件里定义：
 * 
 * ```php
 *   'logger' => array(
 *       'class' => 'app\core\logger\FileLogger',       //文件日志类名
 *       'dir' => ROOT . '/tmp/logs',  //日志目录
 *       'fileNameFormat'=>'app.%name.%date.log'  //日志文件名称的格式
 *   )
 * ```
 * Wen::app()->logger->error('msg','test');
 */
class FileLogger implements LoggerInterface 
{
    /**
     * 日志类型  notice
     */
    const LOG_LEVEL_NOTICE="notice";
    
    /**
     * 日志类型 debug
     */
    const LOG_LEVEL_DEBUG="debug";

    /**
     * 日志类型 error
     */
    const LOG_LEVEL_ERROR="error";

    /**
     * 日志类型 info
     */
    const LOG_LEVEL_INFO="info";
    
    /**
     * @var string 日志文件名
     */
    private $_logName;
    
    /**
     * @var string 日志目录
     */
    private $_logDir;
    
    /**
     * @var string 日志全路径
     */
    private $_logFullPath;
    
    /**
     * @var array 日志句柄数组
     */
    private $_logHandle;


    private $_loggerFormat;


    public function __construct($logDir = "")
    {
        $this->_logDir = $logDir;
    }

    public function error($msg, $fileName='')
    {
        $this->log(self::LOG_LEVEL_ERROR, $msg, $fileName);
    }

    public function notice($msg, $fileName='')
    {
        $this->log(self::LOG_LEVEL_NOTICE, $msg, $fileName);
    }

    public function info($msg, $fileName='')
    {
        $this->log(self::LOG_LEVEL_INFO, $msg, $fileName);
    }

    public function debug($msg, $fileName='')
    {
        $this->log(self::LOG_LEVEL_DEBUG, $msg, $fileName);
    }

    public function setLoggerFormat($format)
    {
        $this->loggerFormat = $format;
    }
    
    /**
     * 设置日志文件名
     * @param string $level    日志等级
     * @param string $fileName 日志文件名
     */
    public function setFileName($level, $fileName = '')
    {
        if (empty( $fileName )) {
            $fileName = $level;
        }
        
        $name = str_replace('%name', $fileName, $this->loggerFormat);
        $this->_logName = str_replace('%date', date("Ymd"), $name);
    }

    /**
     * 写日志
     * 
     * @param string $level 日志等级标识
     * @param string $msg 日志内容
     * @param string $fileName 日志文件名
     * @return boolean 是否写日志成功
     */
    private function log($level, $msg, $fileName='')
    {
        
        $this->setFileName($level, $fileName);

        //日志目录不存在，创建
        if (!file_exists($this->_logDir)) {
            if (false ===  mkdir($this->_logDir)) {
                return false;
            }
        }

        $this->_logFullPath = $this->_logDir . DS . $this->_logName;
        $logKey = 'log_' . md5($this->_logFullPath);
        if (!is_writable($this->_logDir)) {
            throw new Exception( Wen::t('failed to open stream: Permission denied',['file'=>$this->_logFullPath]), 500);
        }

        $this->_logHandle[$logKey] = fopen($this->_logFullPath, 'a');
        if (!$this->_logHandle[$logKey]) {
            throw new Exception( Wen::t('failed to open stream: Permission denied',['file'=>$this->_logFullPath]), 500);
        }
                
        $ip = $this->getIP();
        $log = '';
        $log .= '[requestUrl]http://'. $_SERVER['SERVER_NAME'] . ':' .$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"] . "\n";
        $log = sprintf("[%s][%s][%sms][%s] : %s\n", $level, date("Y-m-d H:i:s"), round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000), $ip, print_r($msg, 1));
        
        fwrite($this->_logHandle[$logKey], $log);
        @chown($this->_logFullPath, 'nobody');
        return true;
    }
    
    /**
     * 获取客户端IP地址
     * 
     * @return string ip地址
     */
    private function getIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } elseif( isset($_SERVER["REMOTE_ADDR"]) ) {
                $realip = $_SERVER["REMOTE_ADDR"];
            }else{
                $realip = '';
            }

            if($realip == '127.0.0.1' && isset($_SERVER["HTTP_X_REAL_IP"])) {
                $realip = $_SERVER["HTTP_X_REAL_IP"];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }

            if($realip == '127.0.0.1' && getenv('HTTP_X_REAL_IP')) {
                $realip = getenv('HTTP_X_REAL_IP');
            }
        }
        return $realip;
    }

   
    public function __destruct()
    {
        if (!empty($this->_logHandle)) {
            foreach ($this->_logHandle as $logHandle) {
                fclose($logHandle);
            }
        }
    }
}
