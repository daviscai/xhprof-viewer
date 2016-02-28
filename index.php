<?php

if (version_compare($ver = PHP_VERSION, $req = '5.4.0', '<')) {
    exit(sprintf('You are running PHP %s, but system needs at least <strong>PHP %s</strong> to run.', $ver, $req));
}

//目录分隔符
define('DS', DIRECTORY_SEPARATOR);

//根路径
define('ROOT', dirname(__FILE__));

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

//date_default_timezone_set('Asia/Chongqing');
date_default_timezone_set('UTC');

//自动加载类文件，遵循PHP-FIG PSR-4规范
spl_autoload_register(function($className){
    $fileName = ucwords(substr($className, strrpos($className, '\\') + 1)) . '.php';
    $filePath = ROOT . DS . str_replace('\\', DS, substr($className, 0, strrpos($className, '\\'))) . DS . $fileName;
    
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

use app\core\base\Wen;

Wen::createApplication()->run();