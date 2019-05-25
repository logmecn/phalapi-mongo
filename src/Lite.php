<?php
/**
 * Created by PS_YWQ
 * User: Yuan Wen Qiang
 * Date: 2019/5/24-15:32
 */

namespace PhalApi\Mongo;

//'mongo' => array(
//    'host' => 'localhost',
//    'port' => '27017',
//    'db_name' => '',
//    'username' => '',
//    'password' => '',
//    'read_preference' => '',
//    'connect_timeout_ms' => 'connect_timeout_ms',
//    'socket_timeout_ms' => 'socket_timeout_ms',
//
//    'persist' => 'x',
//),

use Exception;
use MongoDB;
use MongoDB\Driver\Manager;

/**
 * @property  confArr
 */
class Lite {
    public function __construct($conf)
    {
        $this->confArr = $conf;
    }

    private function connect() {
        try{
            $connStr = "mongodb://" . $this->confArr['host'] . ":" . $this->confArr['port'] . "/" . $this->confArr['db_name'];
            $options = array(
                'username' => $this->confArr['username'],
                'password' => $this->confArr['password'],
//                'readPreference' => $this->confArr['read_preference'],
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
    //2.查询find:
    public function find($query = array(), $fields = array(), $collection, $sort = array(), $limit = 0, $skip = 0) {
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
        }
        return false;
    }
    //3.插入操作insert:
    public function insert($addArr, $collection) {
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
    //4.删除delete：
    public function delete($whereArr, $options = array(), $collection) {
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
            $result = $conn->executeBulkWrite($collection, $bulk, $writeConcern);
            return true;
        } catch (Exception $e) {
            //记录错误日志
        }
        return false;
    }
    //5.执行command操作:
    private function command($params, $dbName) {
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $cmd = new MongoDB\Driver\Command($params);
            $result = $conn->executeCommand($dbName, $cmd);
            return $result;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }
    // 6.统计count:
    public function count($query, $collection) {
        try {
            $cmd = array(
                'count' => $collection,
                'query' => $query,
            );
            $res = $this->command($cmd);
            $result = $res->toArray();
            return $result[0]->n;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }
    // 7.聚合distinct:
    public function distinct($key, $where, $collection) {
        try {
            $cmd = array(
                'distinct' => $collection,
                'key' => $key,
                'query' => $where,
            );
            $res = $this->command($cmd);
            $result = $res->toArray();
            return $result[0]->values;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }
    // 8.aggregate操作：
    public function aggregate($where, $group, $collection) {
        try {
            $cmd = array(
                'aggregate' => $collection,
                'pipeline' => array(
                    array(
                        '$match' => $where,
                    ),
                    array(
                        '$group' => $group,
                    ),
                ),
                'explain' => false,
            );
            $res = $this->command($cmd);
            if (!$res) {
                return false;
            }
            $result = $res->toArray();
            return $result[0]->total;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }
}