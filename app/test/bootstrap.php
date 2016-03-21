<?php

//目录分隔符
define('DS', DIRECTORY_SEPARATOR);

//根路径
define('ROOT', dirname(__FILE__).'/../..');

//框架内核路径
define('APP_ROOT', ROOT . DS . 'app');

//根日志目录路径
define('LOG_ROOT', ROOT . DS . 'tmp'. DS .'logs');

//是否开启debug模式，开发环境设置为1，正式环境设置为0
define('DEBUG', 1);


if (DEBUG) {
    error_reporting(E_ALL^E_NOTICE);
    ini_set('display_errors', true);
} else {
    ini_set('display_errors', false); 
}

// 设置时区，合法时区设置参看 http://php.net/manual/en/timezones.php
date_default_timezone_set('Asia/Chongqing');


// require composer autoloader if available
$composerAutoload = __DIR__ . '/../vendor/autoload.php';

if (is_file($composerAutoload)) {
    require_once($composerAutoload);
}

//自动加载wen框架类文件
spl_autoload_register(function($className){
    $fileName = ucwords(substr($className, strrpos($className, '\\') + 1)) . '.php';
    $filePath = ROOT . DS . str_replace('\\', DS, substr($className, 0, strrpos($className, '\\'))) . DS . $fileName;

    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

require_once(__DIR__ . '/TestCase.php');

