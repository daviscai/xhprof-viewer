<?php
namespace app\test;

use \app\core\base\Wen;

/**
 * 所有单元测试用例的基类
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * 执行单元测试前
     * 
     * 默认创建一个模拟的app实例
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }

    /**
     * 创建一个模拟的app实例
     */
    protected function mockApplication($config = [])
    {
        
    }

    /**
     * 测试完毕，执行某些清除操作
     * 
     * 默认会把模拟的app实例设置为null
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * 把模拟的app实例设置为null
     */
    protected function destroyApplication()
    {
        Wen::$app = null;
    }
}