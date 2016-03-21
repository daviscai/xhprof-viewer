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
use app\core\base\Wen;
use app\core\db\DBConnection;


/**
 * 数据读写操作基类，实现读写分离，提供链式操作数据，方便，高效。
 * 
 * 通过预编译方式绑定数据，防止SQL注入。
 * 
 * 支持数据库分布式集群部署，对失效节点自动转移，提高系统可用性，具体实现参考 app\core\db\DBConnection
 * 
 * ```php
 * $db = Wen::app()->db;
 * 
 * $rs = $db->selectOne('u.id')->from('t_userinfo','u')->limit(0,10)->execute();
 * $rs = $db->select('u.id,u.name')->from('t_userinfo','u')->limit(0,2)->execute();
 * $rs = $db->selectCount('u.id')->from('t_userinfo','u')->where('u.id=:id',array(':id'=>101))->execute();
 * 
 * $rs = $db->selectOne('u.id,u.name')->from('t_userinfo','u')->where('u.id=:id',array(':id'=>10001))->groupby('u.id')->execute();
 * $rs = $db->select('u.id,u.name')->from('t_userinfo','u')->where('u.id=:id',array(':id'=>10001))->groupby('u.id')->execute();
 * 
 * $p=' and 0<>(select count(*) from admin)  '; //sql注入语句失效，预编译防止注入
 * $rs = $db->select('u.id,u.name')->from('t_userinfo','u')->where('u.name=:name',array(':name'=>$p))->execute();
 * 
 * $rs = $db->select()->from('pk_blog_post','u')->leftJoin('Phone','p','u.id = p.user_id')->where('u.id>:id',array(':id'=>1))->limit(0,10)->groupby('u.id')->execute();
 *
 * $rs = $db->insert('pk_blog_post',
 *          array('user_id'=>5,'title'=>'ssss','slug'=>10,'status'=>1,'modified'=>date('y-m-d'),'content'=>'rrr','excerpt'=>'pp','comment_status'=>1,'comment_count'=>2)
 *       )->execute();
 *
 * $rs = $db->update('pk_blog_post',array('title'=>'mmmm'))->where('id=:id',array(':id'=>5))->execute();
 * 
 * $rs = $db->delete('pk_blog_post')->where('id=:id',array(':id'=>3))->execute();
 *
 * ```
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class DB
{
    /**
     * @var DBConnection 数据库连接对象
     */
    private $connect;

    /**
     * @var string 查询sql语句
     */
    private $selectSql;

    /**
     * @var string 查询表
     */
    private $fromTable;

    /**
     * @var array 左连表，多表查询
     */
    private $leftJoin = [];

    /**
     * @var string where条件语句
     */
    private $where;

    /**
     * @var array where条件参数
     */
    private $whereParam = [];

    /**
     * @var string limit语句
     */
    private $limit;

    /**
     * @var string order by 语句
     */
    private $orderBy;

    /**
     * @var string group by 语句
     */
    private $groupBy;

    /**
     * @var boolean 是否只查询一条数据
     */
    private $isFetchOne = false;

    /**
     * @var boolean 是否为count操作, 当$db->selectCount()时会被设置为true
     */
    private $isCount = false;

    /**
     * @var boolean 是否为update操作, 当$db->update()时会被设置为true
     */
    private $isUpdate = false;

    /**
     * @var boolean 是否为insert操作, 当$db->insert()时会被设置为true
     */
    private $isInsert = false;

    /**
     * @var boolean 是否为delete操作, 当$db->delete()时会被设置为true
     */
    private $isDelete = false;

    /**
     * @var string update语句
     */
    private $updateSql = '';

    /**
     * @var string update字段数据
     */
    private $updateFieldData = [];

    /**
     * @var string insert语句
     */
    private $insertSql = '';

    /**
     * @var string insert字段数据
     */
    private $insertFieldData = [];

    /**
     * @var string delete语句
     */
    private $deleteSql = '';


    public function __construct($config)
    {   
        //创建数据库连接对象，构造函数仅仅是初始化数据库相关属性，并没有创建连接，只有在执行具体读写操作时，才会创建连接
        $this->connect = new DBConnection($config);

        //创建数据库连接
        //$this->connect->open();
    } 
 
    public function select($str='')
    {
        if(empty($str)){
            $str = '*';
        }
        $this->selectSql = 'SELECT '.$str.' ';
        return $this;
    }

    public function selectOne($str='')
    {
        $this->select($str);
        $this->isFetchOne = true;
        return $this;
    }

    public function selectCount($str='')
    {
        $this->select($str);
        $this->isCount = true;
        return $this;
    }

    public function from($tableName, $as='t')
    {
        if(empty($tableName)){
            $this->fromTable = '';
        }else{
            $this->fromTable = ' FROM '.$tableName. ' as ' .$as.' ';
        }
        return $this;
    }

    public function leftJoin($tableName, $as='lt', $JoinCondition)
    {
        if(empty($tableName) || empty($JoinCondition)){
            $this->leftJoin[] = '';
        }else{
            $this->leftJoin[] = ' LEFT JOIN '.$tableName.' as '.$as.' on '.$JoinCondition.' ';
        }
        
        return $this;
    }

    public function where($sql, $param=[])
    {
        if(empty($sql)) {
            $this->where = '';
        }else{
            $this->where = ' WHERE '.$sql.' ';
        }
        $this->whereParam = $param;
        
        return $this;
    }

    public function limit($start=0, $num)
    {
        if(empty($num)) {
            $this->limit = '';
        }else{
            $this->limit = ' LIMIT '.$start.','.$num.' ';
        }
        return $this;
    }

    public function orderBy($orderBy)
    {
        if(empty($orderBy)){
            $this->orderBy = '';
        }else{
            $this->orderBy = ' ORDER BY '.$orderBy.' ';
        }
        return $this;
    }

    public function groupBy($groupBy)
    {
        if(empty($groupBy)){
            $this->groupBy = '';
        }else{
            $this->groupBy = ' GROUP BY '.$groupBy.' ';
        }
        return $this;
    }

    public function execute()
    {   
        if($this->isUpdate === true) {
            return $this->executeUpdate();
        }elseif($this->isInsert === true) {
            return $this->executeInsert();
        }elseif($this->isDelete === true){
            return $this->executeDelete();
        }else{
            return $this->executeSelect();
        }
    }

    private function executeSelect()
    {
        $pdo = $this->connect->getSlavePdo();
        $leftJoin = implode(' ', $this->leftJoin);
        $sql = $this->selectSql . $this->fromTable . $leftJoin . $this->where . $this->limit . $this->groupBy .$this->orderBy;

        $sth = $pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute($this->whereParam);

        if($this->isFetchOne){
            $rs = $sth->fetch(PDO::FETCH_ASSOC);
        }else{
            $ret = $sth->fetchAll(PDO::FETCH_ASSOC);
            if($this->isCount){
                $rs = empty($ret) ? 0 : count($ret);
            }else{
                $rs = $ret;
            }
        }
        
        $this->clearParameters();

        return $rs;
    }

    private function executeUpdate()
    {
        if(empty($this->where)){
            return false;
        }
        $pdo = $this->connect->getMasterPdo();
        try {
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 0 );
            $pdo->beginTransaction();
            
            $holderData = array_merge($this->whereParam, $this->updateFieldData);

            $sql = $this->updateSql . $this->where;

            $st = $pdo->prepare($sql);
            $st->execute( $holderData );

            $pdo->commit();
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 1 );

            $this->clearParameters();
            return true;

        } catch(PDOException $e) {
            $pdo->rollBack();
            $this->clearParameters();
            return false;
        }
    }

    private function executeInsert()
    {
        $pdo = $this->connect->getMasterPdo();
        try {
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 0 );
            $pdo->beginTransaction();
            
            $st = $pdo->prepare($this->insertSql);
            $st->execute( $this->insertFieldData );

            $pdo->commit();
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 1 );

            $this->clearParameters();
            return true;

        } catch(PDOException $e) {
            $pdo->rollBack();
            $this->clearParameters();
            return false;
        }
    }

    private function executeDelete()
    {
        if(empty($this->where)){
            return false;
        }
        $pdo = $this->connect->getMasterPdo();
        try {
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 0 );
            $pdo->beginTransaction();
            
            $sql = $this->deleteSql . $this->where;
            $st = $pdo->prepare($sql);
            $st->execute( $this->whereParam );

            $pdo->commit();
            $pdo->setAttribute( PDO::ATTR_AUTOCOMMIT, 1 );

            $this->clearParameters();
            return true;

        } catch(PDOException $e) {
            $pdo->rollBack();
            $this->clearParameters();
            return false;
        }
    }

    public function insert($tableName, $data=[])
    {
        $this->isInsert = true;

        if(empty($data)){
            return $this;
        }
            
        $dataKeyArr = array_keys($data);
        $field = implode(',', $dataKeyArr);

        $holderArr = [];
        $holderData = [];
        foreach ($data as $_field => $val) {
            $holderArr[] = ':'.$_field;
            $holderData[':'.$_field] = $val;
        }
        $placeholder = implode(',', $holderArr);

        $sql = 'INSERT INTO '.$tableName.'( '.$field.' ) VALUES( '.$placeholder.' )' ;
        $this->insertSql = $sql;
        $this->insertFieldData = $holderData;
        return $this;
    }

    public function update($tableName, $data=[])
    {
        $this->isUpdate = true;

        $holderArr = [];
        $holderData = [];
        foreach ($data as $_field => $val) {
            $holderArr[] = $_field . '=:'.$_field;
            $holderData[':'.$_field] = $val;
        }
        $placeholder = implode(',', $holderArr);

        $sql = 'UPDATE '.$tableName.' SET '.$placeholder.' ' ;

        $this->updateSql = $sql;
        $this->updateFieldData = $holderData;
        return $this;
    }

    public function delete($tableName)
    {
        $this->isDelete = true;
        $sql = 'DELETE FROM '.$tableName.' ';
        $this->deleteSql =  $sql;
        return $this;
    }

    private function clearParameters()
    {
        $this->isFetchOne = false;
        $this->selectSql  = '';
        $this->fromTable  = '';
        $this->leftJoin   = [];
        $this->where      = '';
        $this->whereParam = [];
        $this->limit      = '';
        $this->orderBy    = '';
        $this->groupBy    = '';
        $this->isInsert   = false;
        $this->isUpdate   = false;
        $this->isDelete   = false;
        $this->isCount    = false;
        $this->updateSql  = '';
        $this->updateFieldData = [];
        $this->insertSql  = '';
        $this->insertFieldData = [];
        $this->deleteSql  = '';
    }

}