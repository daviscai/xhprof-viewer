<?php
namespace app\test\core\base;

use \app\test\TestCase;

use \app\core\base\Application;

/**
 * @group base
 */
class ApplicationTest extends TestCase
{
    public function testBaseConfig()
    {
    	$app = new Application();

    	$config = $app->config;
    	$this->assertArrayHasKey('router', $config);
    	$this->assertArrayHasKey('i18n', $config);
    	$this->assertArrayHasKey('logger', $config);

    	$this->assertInstanceOf('app\core\i18n\I18NProvider',$app->i18n);
    	$this->assertInstanceOf('app\core\logger\LoggerProvider',$app->logger);
    	$this->assertInstanceOf('app\core\db\DB',$app->db);
    	if(isset($config['cache']['class'])){
    		$this->assertInstanceOf($config['cache']['class'], $app->cache);
    	}else{
    		$this->assertNull($app->cache);
    	}

    }

    public function testCacheConfig()
    {
    	$configFile = ROOT . DS . 'app/test/config' . DS . 'app_cache.config';
    	$app = new Application($configFile);

    	$config = $app->config;
    	$this->assertArrayHasKey('router', $config);
    	$this->assertArrayHasKey('i18n', $config);
    	$this->assertArrayHasKey('logger', $config);
    	$this->assertArrayHasKey('cache', $config);
    	$this->assertInstanceOf($config['cache']['class'], $app->cache);

    }
}