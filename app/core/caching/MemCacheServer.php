<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */


namespace app\core\caching;

use app\core\base\Wen;

/**
 * MemCacheServer 封装了memcache 或者 memcached 客户端对象的配置
 * 
 * 参考 [PHP manual](http://www.php.net/manual/en/function.Memcache-addServer.php) 了解每一个配置属性
 *
 * 该实现参考自Yii2.0。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class MemCacheServer
{
    /**
     * @var string memcache server hostname or IP address
     */
    public $host;

    /**
     * @var integer memcache server port
     */
    public $port = 11211;

    /**
     * @var integer 权重，在分布式集群下，指定当前缓存节点的权重
     */
    public $weight = 1;

    /**
     * @var boolean 是否使用持久化链接. 仅当缓存客户端使用 memcache 有效.
     */
    public $persistent = true;

    /**
     * @var integer 连接缓存服务器的超时时间，毫秒 ，仅当缓存客户端使用 memcache 有效.
     */
    public $timeout = 1000;

    /**
     * @var integer 如果连接失败，重试间隔时间  仅当缓存客户端使用 memcache 有效.
     */
    public $retryInterval = 15;

    /**
     * @var boolean 是否要标识失效的节点. 仅当缓存客户端使用 memcache 有效.
     */
    public $status = true;

    /**
     * @var 在连接失败时将尝试执行该回调函数，该回调函数有2个参数，失效节点的host和port
     * 仅当缓存客户端使用 memcache 有效.
     */
    public $failureCallback;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            Wen::configure($this, $config);
        }
    }
}
