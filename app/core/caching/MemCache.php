<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */


namespace app\core\caching;

use Exception;
use app\core\base\Wen;

/**
 * 基于 [memcache](http://pecl.php.net/package/memcache) 和 [memcached](http://pecl.php.net/package/memcached).
 * 实现了一个缓存应用组件
 *
 * MemCache同时支持 memcache 和 memcached ，在配置里设置 [[useMemcached]] 为true或者false即可
 *
 * 具体的cache配置如下
 *
 * ```php
 * 'cache' => [
 *      'class' => 'app\core\caching\MemCache', //缓存实现类
 *      'useMemcached'=>true, //推荐, memcached客户端已实现一致性Hash
 *      'servers' => [
 *          ['host' => 'localhost', 'port' => 11211, 'weight' => 60 ],
 *          ['host' => 'localhost', 'port' => 11212, 'weight' => 60 ],
 *      ],
 * ],
 * ```
 *
 * 每个server还可以设置其他属性，比如超时`timeout`, `persistent`等，可以参看 [[MemCacheServer]]
 *
 * memcached php扩展已支持一致性哈希，而memcaceh支持一致性哈希需要修改php.ini配置：
 * 
 * Memcache.hash_strategy =consistent
 * Memcache.hash_function =crc32
 *
 * 该实现参考自Yii2.0。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 *
 */
class MemCache extends Cache
{
    /**
     * @var boolean whether to use memcached or memcache as the underlying caching extension.
     * If true, [memcached](http://pecl.php.net/package/memcached) will be used.
     * If false, [memcache](http://pecl.php.net/package/memcache) will be used.
     * Defaults to false.
     */
    public $useMemcached = false;

    /**
     * @var string an ID that identifies a Memcached instance. This property is used only when [[useMemcached]] is true.
     * By default the Memcached instances are destroyed at the end of the request. To create an instance that
     * persists between requests, you may specify a unique ID for the instance. All instances created with the
     * same ID will share the same connection.
     * @see http://ca2.php.net/manual/en/memcached.construct.php
     */
    public $persistentId;

    /**
     * @var array options for Memcached. This property is used only when [[useMemcached]] is true.
     * 
     * like this: array(Memcached::OPT_HASH => Memcached::HASH_MURMUR, Memcached::OPT_PREFIX_KEY => "widgets")
     * @see http://ca2.php.net/manual/en/memcached.setoptions.php
     */
    public $options;

    /**
     * @var string memcached sasl username. This property is used only when [[useMemcached]] is true.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public $username;

    /**
     * @var string memcached sasl password. This property is used only when [[useMemcached]] is true.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public $password;


    public $servers;

    /**
     * @var \Memcache|\Memcached the Memcache instance
     */
    private $_cache;

    /**
     * @var array list of memcache server configurations
     */
    private $_servers = [];


    public function __construct($config = [])
    {
        if (!empty($config)) {
            Wen::configure($this, $config);
        }
        $this->init();
    }

    /**
     * 初始化缓存组件
     * 
     */
    public function init()
    {
        //根据配置servers，创建MemCacheServer实例对象，并保存到$_servers
        $this->setServers($this->servers);

        //添加缓存节点到缓存池里
        $this->addServers($this->getMemcache(), $this->getServers());
    }

    /**
     * 添加缓存节点到缓存池里
     *
     * @param \Memcache|\Memcached $cache
     * @param MemCacheServer[] $servers
     * @throws Exception
     */
    protected function addServers($cache, $servers)
    {
        if (empty($servers)) {
            return false;
        } else {
            foreach ($servers as $server) {
                if ($server->host === null) {
                    throw new Exception(Wen::t('The host property must be specified for every memcache server'));
                }
            }
        }
        if ($this->useMemcached) {
            $this->addMemcachedServers($cache, $servers);
        } else {
            $this->addMemcacheServers($cache, $servers);
        }
    }

    /**
     * Add servers to the server pool of the cache specified
     * Used for memcached PECL extension.
     *
     * @param \Memcached $cache
     * @param MemCacheServer[] $servers
     */
    protected function addMemcachedServers($cache, $servers)
    {
        $existingServers = [];
        if ($this->persistentId !== null) {
            foreach ($cache->getServerList() as $s) {
                $existingServers[$s['host'] . ':' . $s['port']] = true;
            }
        }
        $serversArr = array();
        foreach ($servers as $server) {
            if (empty($existingServers) || !isset($existingServers[$server->host . ':' . $server->port])) {
                $serversArr[] = array($server->host, $server->port, $server->weight);
            }
        }

        if($serversArr){
            $cache->addServers($serversArr);
        }
    }

    /**
     * Add servers to the server pool of the cache specified
     * Used for memcache PECL extension.
     *
     * @param \Memcache $cache
     * @param MemCacheServer[] $servers
     */
    protected function addMemcacheServers($cache, $servers)
    {
        $class = new \ReflectionClass($cache);
        $paramCount = $class->getMethod('addServer')->getNumberOfParameters();
        foreach ($servers as $server) {
            // $timeout is used for memcache versions that do not have $timeoutms parameter
            $timeout = (int) ($server->timeout / 1000) + (($server->timeout % 1000 > 0) ? 1 : 0);
            if ($paramCount === 9) {
                $cache->addServer(
                    $server->host,
                    $server->port,
                    $server->persistent,
                    $server->weight,
                    $timeout,
                    $server->retryInterval,
                    $server->status,
                    $server->failureCallback,
                    $server->timeout
                );
            } else {
                $cache->addServer(
                    $server->host,
                    $server->port,
                    $server->persistent,
                    $server->weight,
                    $timeout,
                    $server->retryInterval,
                    $server->status,
                    $server->failureCallback
                );
            }
        }
    }

    /**
     * Returns the underlying memcache (or memcached) object.
     * @return \Memcache|\Memcached the memcache (or memcached) object used by this cache component.
     * @throws InvalidConfigException if memcache or memcached extension is not loaded
     */
    public function getMemcache()
    {
        if ($this->_cache === null) {
            $extension = $this->useMemcached ? 'memcached' : 'memcache';
            if (!extension_loaded($extension)) {
                throw new Exception(Wen::t('MemCache requires PHP extension to be loaded',['extension'=>$extension]));
            }

            if ($this->useMemcached) {
                $this->_cache = $this->persistentId !== null ? new \Memcached($this->persistentId) : new \Memcached;
                if ($this->username !== null || $this->password !== null) {
                    $this->_cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                    $this->_cache->setSaslAuthData($this->username, $this->password);
                }
                if (!empty($this->options)) {
                    $this->_cache->setOptions($this->options);
                }

                //使用一致性hash算法
                $this->_cache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
                $this->_cache->setOption(\Memcached::OPT_HASH, \Memcached::HASH_CRC);
            } else {
                $this->_cache = new \Memcache;
            }
        }

        return $this->_cache;
    }

    /**
     * Returns the memcache or memcached server configurations.
     * @return MemCacheServer[] list of memcache server configurations.
     */
    public function getServers()
    {
        return $this->_servers;
    }

    /**
     * @param array $config list of memcache or memcached server configurations. Each element must be an array
     * with the following keys: host, port, persistent, weight, timeout, retryInterval, status.
     * @see http://php.net/manual/en/memcache.addserver.php
     * @see http://php.net/manual/en/memcached.addserver.php
     */
    public function setServers($config)
    {
        if(empty($config)){
            $this->_servers = [];
        }else{
            foreach ($config as $c) {
                $this->_servers[] = new MemCacheServer($c);
            }
        } 
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string|boolean the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        return $this->_cache->get($key);
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * @param array $keys a list of keys identifying the cached values
     * @return array a list of cached values indexed by the keys
     */
    protected function getValues($keys)
    {
        return $this->useMemcached ? $this->_cache->getMulti($keys) : $this->_cache->get($keys);
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration)
    {
        $duration = $this->trimDuration($duration);
        $expire = $duration > 0 ? $duration + time() : 0;

        return $this->useMemcached ? $this->_cache->set($key, $value, $expire) : $this->_cache->set($key, $value, 0, $expire);
    }

    /**
     * Stores multiple key-value pairs in cache.
     * @param array $data array where key corresponds to cache key while value is the value stored
     * @param integer $duration the number of seconds in which the cached values will expire. 0 means never expire.
     * @return array array of failed keys. Always empty in case of using memcached.
     */
    protected function setValues($data, $duration)
    {
        $duration = $this->trimDuration($duration);

        if ($this->useMemcached) {
            $this->_cache->setMulti($data, $duration > 0 ? $duration + time() : 0);

            return [];
        } else {
            return parent::setValues($data, $duration);
        }
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
        $duration = $this->trimDuration($duration);
        $expire = $duration > 0 ? $duration + time() : 0;

        return $this->useMemcached ? $this->_cache->add($key, $value, $expire) : $this->_cache->add($key, $value, 0, $expire);
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return boolean if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        return $this->_cache->delete($key, 0);
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return boolean whether the flush operation was successful.
     */
    protected function flushValues()
    {
        return $this->_cache->flush();
    }

    /**
     * Trims duration to 30 days (2592000 seconds).
     * @param integer $duration the number of seconds
     * @return int the duration
     */
    protected function trimDuration($duration)
    {
        if ($duration > 2592000) {
            //Yii::warning('Duration has been truncated to 30 days due to Memcache/Memcached limitation.', __METHOD__);
            return 2592000;
        }
        return $duration;
    }
}
