<?php
/**
 * Created by PS_YWQ
 * User: Logmecn
 * Date: 2019/5/24-15:32
 */

namespace PhalApi\Mongo;

use Exception;
use MongoDB;
use MongoDB\Driver\Manager;
use stdClass;

/**
 *
 */
class Lite {
    private $confArr = array();

    /**
     * Lite constructor.
     * @param array $conf 连接 MongoDB 的配置加载
     */
    public function __construct($conf)
    {
        $this->confArr = $conf;
    }

    /**
     * @desc 连接到 MongoDB
     * @return bool|Manager
     */
    private function connect() {
        try{
            $connStr = "mongodb://" . $this->confArr['host'] . ":" . $this->confArr['port'] . "/" . $this->confArr['db_name'];
            $options = array(
                'username' => $this->confArr['username'],
                'password' => $this->confArr['password'],
//                'readPreference' => $this->confArr['read_preference'],  // 此参数已废弃
                'connectTimeoutMS' => intval($this->confArr['connect_timeout_ms']),
                'socketTimeoutMS' => intval($this->confArr['socket_timeout_ms']),
                'persist' => $this->confArr['persist'],
            );
            $mc = new Manager($connStr, $options);
            return $mc;
        }
        catch(Exception $e){
            return false;
        }
    }

    /**
     * @desc 查询find:
     * @param array $query
     * @param array $fields
     * @param $collection
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return array|bool
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function find($collection, $query = array(), $fields = array(), $sort = array(), $limit = 0, $skip = 0) {
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $data = array();
            $options = array();
            if (!empty($query)) {
                $options['projection'] = array_fill_keys($fields, 1);
            }
            if (!empty($sort)) {
                $options['sort'] = $sort;
            }
            if (!empty($limit)) {
                $options['skip'] = $skip;
                $options['limit'] = $limit;
            }
            $mongoQuery = new MongoDB\Driver\Query($query, $options);
            $readPreference = new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_SECONDARY);
            $cursor = $conn->executeQuery($collection, $mongoQuery, $readPreference);
            foreach($cursor as $value) {
                $data[] = (array)$value;
            }
            return $data;
        } catch (Exception $e) {
            //记录错误日志
            return $e->getMessage();
        }
    }

    /**
     * @desc 插入操作insert:
     * @param  array $addArr  要增加的数据
     * @param  string $collection
     * @return bool|string
     */
    public function insert($collection, $addArr) {
        if (empty($addArr) || !is_array($addArr)) {
            return false;
        }
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->insert($addArr);
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 6000);
            $result = $conn->executeBulkWrite($collection, $bulk, $writeConcern);
            if ($result->getInsertedCount()) {
                return true;
            }
        } catch (Exception $e) {
            return "insert失败：" . $e->getMessage();
        }
        return false;
    }

    /**
     * @desc 删除delete：
     * @param array $whereArr
     * @param array $options
     * @param string $collection
     * @return bool|string
     */
    public function delete($collection, $whereArr, $options = array()) {
        if (empty($whereArr)) {
            return false;
        }
        if (!isset($options['justOne'])) {
            $options = array(
                'justOne' => false,
            );
        }
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete($whereArr, $options);
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 30000);
            $conn->executeBulkWrite($collection, $bulk, $writeConcern);
            return true;
        } catch (Exception $e) {
            return "delete失败：" . $e->getMessage();
        }
    }

    /**
     * @desc 执行command操作:
     * @param $dbName
     * @param array $params
     * @return string|MongoDB\Driver\Cursor
     * @throws MongoDB\Driver\Exception\Exception
     */
    private function command($dbName, $params) {
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $cmd = new MongoDB\Driver\Command($params);
            $result = $conn->executeCommand($dbName, $cmd);
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @desc // 6.统计count 获取统计数
     * @param $dbName
     * @param string $collection
     * @param array $query
     * @return array
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function count($dbName, $collection, array $query) {
        $cmd = array(
            'count' => $collection,
            'query' => $query,
        );
        try {
            $res = $this->command($dbName, $cmd);
            $result = $res->toArray();
//            var_dump($result);exit;
            if (!empty($result)) {
                return array("msg"=>$result[0]->n, "ret"=>200);
            }else{
                return array("msg"=>"result is empty", "ret"=>200);
            }
        } catch (Exception $e) {
            return array("msg"=>$e->getMessage(), "ret"=>200);
        }
    }

    /**
     * @desc  distinct 聚合数据，对结果重复数据去除，类似于数据库中的 distinct
     * @param $dbName
     * @param $collection
     * @param $key
     * @param $where
     * @return array
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function distinct($dbName, $collection, $key, $where) {
        try {
            $cmd = array(
                'distinct' => $collection,
                'key' => $key,
                'query' => $this->array2object($where),
            );
            $res = $this->command($dbName, $cmd);
            if (!$res || is_string($res)) {
                return array('msg'=>$res, "ret"=>400);
            }
            $res = $this->object2array($res);
            return array("msg" => $res, "ret"=>200);
        } catch (Exception $e) {
            return array('msg'=>$e->getMessage(), "ret"=>400);
        }
    }

    /**
     * @desc aggregate操作：聚合(aggregate)主要用于处理数据(诸如统计平均值,求和等)，并返回计算后的数据结果。
     * 有点类似sql语句中的 count(*).
     * 聚合操作处理数据记录并返回计算结果。
     * 聚合操作将多个文档中的值组合在一起，并可对分组数据执行各种操作，以返回单个结果。
     * 在SQL中的 count(*)与group by组合相当于mongodb 中的聚合功能。
     * 示例命令：db.wkuser.aggregate([{$group: { _id:"$name", count:{$sum:1}}}])
     * @param $dbName
     * @param $coll
     * @param $where
     * @param $group
     * @param int $limit
     * @return array ret 返回代码
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function aggregate($dbName, $coll, $where, $group, $limit=10) {
        try {
            $cmd = array(
                'aggregate' => $coll,
                'pipeline' => array(
                    array(
                        '$match' => $this->array2object($where),
                    ),
                    array(
                        '$group' => $group,
                    ),
                    array(
                        '$limit' => $limit,
                    ),
                ),
                'cursor' => new stdClass,
                'explain' => false,
            );
            $res = $this->command($dbName, $cmd);
            if (!$res || is_string($res)) {
                return array('msg'=>$res, "ret"=>400);
            }
            $res = $this->object2array($res);
            return array("msg" => $res, "ret"=>200);
        } catch (Exception $e) {
            return array('msg'=>$e->getMessage(), "ret"=>400);
        }

    }

    private function array2object($array) {
        if (is_array($array)) {
            $obj = new StdClass();
            foreach ($array as $key => $val){
                $obj->$key = $val;
            }
        }
        else { $obj = $array; }
        return $obj;
    }

    private function object2array($object) {
        $array = [];
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
}