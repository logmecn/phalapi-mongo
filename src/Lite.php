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
     * @return bool|string|array
     */
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
            $objectId=$bulk->insert($addArr);
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 6000);
            $result = $conn->executeBulkWrite($collection, $bulk, $writeConcern);
            if ($result->getInsertedCount()) {
                return (array)($objectId);
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
     * @param $collection
     * @param array $params
     * @return string|MongoDB\Driver\Cursor
     * @throws MongoDB\Driver\Exception\Exception
     */
    private function command($collection, $params) {
        $conn = $this->connect();
        if (empty($conn)) {
            return false;
        }
        try {
            $cmd = new MongoDB\Driver\Command($params);
            $result = $conn->executeCommand($collection, $cmd);
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @desc // 6.统计count 获取统计数
     * @param string $collection
     * @param array $query
     * @return bool
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function count($collection, $query) {
        try {
            $res = $this->command($collection, $query);
            $result = $res->toArray();
            return $result[0]->n;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }

    /**
     * @desc  7.聚合distinct
     * @param $collection
     * @param $key
     * @param $where
     * @return bool
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function distinct($collection, $key, $where) {
        try {
            $cmd = array(
                'distinct' => $collection,
                'key' => $key,
                'query' => $where,
            );
            $res = $this->command($collection, $cmd);
            $result = $res->toArray();
            return $result[0]->values;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }

    /**
     * @desc  8.aggregate操作：
     * @param $where
     * @param $group
     * @param $collection
     * @return bool
     * @throws MongoDB\Driver\Exception\Exception
     */
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
            $res = $this->command($collection, $cmd);
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