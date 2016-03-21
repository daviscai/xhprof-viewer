<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\db;

use PDO;
use Exception;
use \app\core\caching\Cache;
use \app\core\base\Wen;


/**
 *
 * 数据库连接类，根据数据库主从配置，实现读写分离
 * 
 * 
 * 支持数据库分布式集群部署，对失效节点自动转移，提高系统可用性。
 *
 * 该实现参考自Yii2.0，部分功能做了删减和重构。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class DBConnection
{
    /**
     * @var string 数据源，包含数据库连接信息，可参看[PHP manual](http://www.php.net/manual/en/function.PDO-construct.php) 了解DSN格式
     *
     * [SQLite](http://php.net/manual/en/ref.pdo-sqlite.connection.php) 可能会用到的路径别名
     * 指定数据库路径，比如： `sqlite:@app/data/db.sql`.
     *
     */
    public $dsn;

    /**
     * @var string 数据库用户名. 默认为`null`，表示无需用户名
     */
    public $username;

    /**
     * @var string 数据库密码. 默认为`null`，表示无需密码
     */
    public $password;

    /**
     * @var array PDO属性，调用open()方法时会被赋值 
     * 参看 [PHP manual](http://www.php.net/manual/en/function.PDO-setAttribute.php) 了解更多属性设置
     */
    public $attributes;

    /**
     * @var PDO the PHP PDO 数据连接对象.
     * 通过[[open()]] and [[close()]] 方法来创建和关闭连接.
     */
    public $pdo;

    /**
     * @var string the 数据库字符编码. 
     * 只适用于 MySQL, PostgreSQL and CUBRID databases. 
     * 默认为null, 采用数据源默认的字符编码
     *
     * 如果想使用GBK或者BIG5，强烈建议通过DSN来设置，比如：'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'.
     */
    public $charset;

    /**
     * @var boolean 是否使用PDO模拟的预编译，默认false，
     * 大多数数据库本身已支持预编译prepare特性，比如mysql，这样PDO就会使用数据库本身的预编译特性。
     * 对于某些没有预编译prepare特性的，PDO提供模拟的预编译特性。
     * 如果要使用PDO模拟的预编译特性，需要 ATTR_EMULATE_PREPARES 设置为true，默认为false.
     */
    public $emulatePrepare;

    /**
     * @var string the 表前缀
     */
    public $tablePrefix = '';

    /**
     * @var string 自定义的PDO类. 如果没设置，就使用 PDO, 或者是 driver\mssql\PDO .
     */
    public $pdoClass;

    /**
     * @var integer the 对主从服务器失效节点重试时间间隔，多少秒后再重试连接，也就是自动转移失效节点
     * 
     * 依赖缓存组件，只有当存在缓存服务器时，自动转移失效节点才会起作用
     */
    public $serverRetryInterval = 600;

    /**
     * @var boolean 是否开启读写分离，从slave库读取数据
     * 如果不存在从库，即从库配置参数slaves为空，该设置不起作用。
     */
    public $enableSlaves = true;

    /**
     * @var array 从库集群DSN配置，每个配置项都将会被创建一个从库连接
     *
     * @see enableSlaves
     * @see slaveConfig
     */
    public $slaves = [];

    /**
     * @var array 从库服务器信息配置，如数据库账号密码，基本信息将会跟slaves的每一个从库dsn合并
     * 
     * 多个从库的账号密码必须相同，从库可以部署在不同的服务器上
     */
    public $slaveConfig = [];

    /**
     * @var array 主数据库集群DSN配置，每个配置项都将会被创建一个主库连接
     * 当调用open()方法后，将会创建一个数据库连接对象
     *
     * @see masterConfig
     */
    public $masters = [];

    /**
     * @var 主库服务器信息配置，如数据库账号密码，基本信息将会跟masters的每一个主库dsn合并
     */
    public $masterConfig = [];

    /**
     * @var string 数据库驱动名
     */
    private $_driverName;

    /**
     * @var Connection 当前生效的从库连接对象
     */
    private $_slave = false;


    public function __construct($config=[])
    {
        //根据配置，初始化属性
        Wen::configure($this,$config);
    } 

    /**
     * 建立数据库连接
     *
     * @throws Exception 如果连接失败抛出异常
     */
    public function open()
    {   
        //如果数据库连接已经存在，直接返回，不做任何处理
        if ($this->pdo !== null) {
            return;
        }

        //如果没有配置主数据库，抛出异常
        if (empty($this->masters)) {
            throw new Exception( Wen::t('masters DB servers cannot be empty'));
        }
       
        //从连接池里取一个
        $db = $this->openFromPool($this->masters, $this->masterConfig);
        if ($db !== null) {
            $this->pdo = $db->pdo;
            return;
        } else {
            //如果主库连接池里没有可用的数据库节点，抛出异常
            throw new Exception(Wen::t('None of the master DB servers is available'));
        }
    
    } 

    /**
     * 连接数据库 
     *
     */
    protected function connect()
    {
        try {

            $this->pdo = $this->createPdoInstance();
            $this->initConnection();
            
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->errorInfo, (int) $e->getCode(), $e);
        }
    }

    /**
     * 从连接池里取出一个连接
     * This method implements the load balancing among the given list of the servers.
     * @param array $pool the list of connection configurations in the server pool
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     * @return Connection the opened DB connection, or null if no server is available
     * @throws InvalidConfigException if a configuration does not specify "dsn"
     */
    protected function openFromPool(array $pool, array $sharedConfig)
    {
        if (empty($pool)) {
            return null;
        }

        //如果没有指定数据库连接类，则用当前类 DBConnection
        if (!isset($sharedConfig['class'])) {
            $sharedConfig['class'] = get_class($this);
        }

        //缓存对象，用于存储失效的数据库节点
        $cache = Wen::app()->cache;
        
        //打乱排序，以第一个成功创建连接作为当前数据库连接
        shuffle($pool);

        foreach ($pool as $config) {
            //数据库基本信息配置跟集群里的每个dsn合并
            $config = array_merge($sharedConfig, $config);

            //如果有dsn配置为空，抛出异常
            if (empty($config['dsn'])) {
                throw new Exception(Wen::t('The dsn option must be specified'));
            }
            //把当前数据库配置信息初始化属性值
            Wen::configure($this,$config);

            $key = [__METHOD__, $config['dsn']];
            if ($cache instanceof Cache && $cache->get($key)) {
                //当前节点失效，找下一个
                continue;
            }

            //创建数据库对象
            $db = Wen::createObject($config);

            try {
                //创建数据库连接
                $db->connect();
                return $db;
            } catch (\Exception $e) {
                if ($cache instanceof Cache) {
                    // 在重试时间内，设置当前节点失效
                    $cache->set($key, 1, $this->serverRetryInterval);
                }
            }
        }

        return null;
    }

     /**
     * 创建PDO实例
     * 
     * 默认创建 PHP PDO 实例.
     * @return PDO the pdo instance
     */
    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'PDO';
            if ($this->_driverName !== null) {
                $driver = $this->_driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }
            if (isset($driver)) {
                if ($driver === 'mssql' || $driver === 'dblib') {
                    $pdoClass = 'app\core\db\driver\mssql\PDO';
                } elseif ($driver === 'sqlsrv') {
                    $pdoClass = 'app\core\db\driver\mssql\SqlsrvPDO';
                }
            }
        }

        $dsn = $this->dsn;
       
        try {
            return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
        } catch (PDOException $e) {
            die( Wen::t('cannot create pdo instance',['errorMsg'=>$e->getMessage()]) );
        }
    }

    /**
     * 设置数据库连接属性，数据库连接成功创建后，调用该方法进行初始化相关属性
     * 
     * PHP 5.3.6及以前版本，设置PDO::ATTR_EMULATE_PREPARES参数为false（即由MySQL等数据库进行变量处理）
     * 
     * 参考文章 [PDO防注入原理分析以及使用PDO的注意事项](http://zhangxugg-163-com.iteye.com/blog/1835721)
     */
    protected function initConnection()
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->emulatePrepare);
        }
        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli', 'cubrid'])) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }
    }

    /**
    * 返回数据库驱动名. 
    * @return string name of the DB driver
    */
    public function getDriverName()
    {
        if ($this->_driverName === null) {
            if (($pos = strpos($this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->_driverName = strtolower($this->getSlavePdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }
        return $this->_driverName;
    }

    /**
     * 设置数据库驱动名
     * @param string $driverName name of the DB driver
     */
    public function setDriverName($driverName)
    {
        $this->_driverName = strtolower($driverName);
    }


    /**
     * 返回当前有效的从库连接PDO实例
     * 当[[enableSlaves]] 设置为true时，会从slaves取一个从库连接，否则返回主库连接
     *
     * @param boolean $fallbackToMaster 是否改用主库连接
     * @return PDO the PDO instance
     */
    public function getSlavePdo($fallbackToMaster = true)
    {
        $db = $this->getSlave(false);
        if ($db === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        } else {
            return $db->pdo;
        }
    }

    /**
     * 返回当前有效的主库连接PDO实例
     *
     * @return PDO the PDO instance
     */
    public function getMasterPdo()
    {
        $this->open();
        return $this->pdo;
    }

    /**
     * 返回当前有效的从库连接对象
     * If this method is called the first time, it will try to open a slave connection when [[enableSlaves]] is true.
     * @param boolean $fallbackToMaster whether to return a master connection in case there is no slave connection available.
     * @return Connection the currently active slave connection. Null is returned if there is slave available and
     * `$fallbackToMaster` is false.
     */
    public function getSlave($fallbackToMaster = true)
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->_slave === false) {
            //从slaves从库连接池里取一个连接
            $this->_slave = $this->openFromPool($this->slaves, $this->slaveConfig);
        }

        return $this->_slave === null && $fallbackToMaster ? $this : $this->_slave;
    }
 
}