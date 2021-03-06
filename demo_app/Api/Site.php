<?php
namespace App\Api;
// 说明：此文件 一般放在 src/app/Api/Site.php

use Exception;
use ParameterError;
use PhalApi\Api;
use function PhalApi\DI;

/**
 * 默认接口服务类
 * @author: dogstar <chanzonghuang@gmail.com> 2014-10-04
 */
class Site extends Api {

    private $mong;
    private $coll;
    private $mdb;
    private $mdbcoll;

    public function __construct()
    {
        $this->mong = DI()->mongo;
        $this->mdb = DI()->config->get("mongo.mongo.db_name");  // MongoDB数据库
        $this->coll = DI()->config->get("mongo.wkuser");    // MongoDB 要操作的集合，即类似于MySQL的表
        $this->mdbcoll = $this->mdb.".".$this->coll;
    }

    public function getRules() {
        return array(
            'index' => array(
                'username'  => array('name' => 'username', 'default' => 'PhalApi', 'desc' => '用户名'),
            ),
            'queryUser' => array(
                'userId' => array('name' => 'userId', 'desc'=> '用户的userid'),
            ),
            'createUser' => array(),
            'syncDept' => array(),
            'syncUser' => array(),
            'syncUserByDept' => array(
                'dept' => array('name' => 'dept', 'type' => 'int', 'default' => 1, 'min' => 1, 'desc' => '同步该部门id下的所有人员详情'),
            ),
            'delUser' => array('userid' => array('name' => 'userid', 'desc'=> '用户的userid'),),
            'aggregateUser' => array(),
            'countUser' => [],
            'findAndMUser' => [],
        );
    }

    /**
     * 默认接口服务
     * @desc 默认接口服务，当未指定接口服务时执行此接口服务
     * @return array.string title 标题
     * @return string content 内容
     * @return string version 版本，格式：X.X.X
     * @return int time 当前时间戳
     * @exception 400 非法请求，参数传递错误
     */
    public function index() {
        return array(
            'title' => 'Hello ' . $this->username,
            'version' => PHALAPI_VERSION,
            'time' => $_SERVER['REQUEST_TIME'],
        );
    }

    /**
     * @desc 插入 用户详细信息
     * @return string
     */
    public function syncUserByDept(){
        $user = array (
            'userid' => 'bob2',
            'name' => 'Bob2',
            'english_name' => '',
            'mobile' => '',
            'department' =>
                array (
                    0 => 15,
                    1 => 16,
                ),
            'order' =>
                array (
                    0 => 0,
                ),
            'position' => '',
            'gender' => '0',
            'email' => '',
            'telephone' => '',
            'isleader' => 0,
            'avatar_mediaid' => NULL,
            'enable' => 1,
            'extattr' =>
                array (
                    'attrs' => NULL,
                ),
            'status' => 4,
        );
        $es = "";

        try{
            $msg = $this->mong->insert($this->mdbcoll, $user);
            if ( $msg){
                $es = $msg;
            }
            DI()->logger->info("插入Mongo成功：$es",  $msg);
        } catch (Exception $e){
            DI()->logger->error("同步部门时，插入或更新用户失败：", json_encode($user) . $e->getMessage() );
            $es = $e->getMessage();
        }
        if ($es != ""){
            return "同步部门：$this->dept 时发生错误，请查看日志以分析原因。部分原因为：". $es ;
        }else {
            return "同步所属部门中的用户完成，部门id为：" . $this->dept;
        }
    }

    /**
     * @desc 使用 insertOrUpdate 方法，当 mongodb中的关键字段中判断无该文档时，则插入；
     *          否则更新该文档中 除查询的关键字段外其他的字段。
     * @return string
     */
    public function InsertOrUpdateUser(){
        $user = array (
            'userid' => 'bob3',
            'name' => 'Bob3',
            'english_name' => 'e_bob3',
            'mobile' => '',
            'department' =>
                array (
                    0 => 15,
                    1 => 16,
                ),
            'order' =>
                array (
                    0 => 0,
                ),
            'position' => '',
            'gender' => '0',
            'email' => '',
            'telephone' => '',
            'isleader' => 0,
            'avatar_mediaid' => NULL,
            'enable' => 1,
            'extattr' =>
                array (
                    'attrs' => NULL,
                ),
            'status' => 4,
        );
        $failed_count = 0;  // 插入失败次数
        try{
            $query = ['userid'=>$user['userid']];
            $options =  ['multi' => false, 'upsert' => true];
            $msg = $this->mong->update($this->mdbcoll, $query, $user, $options); //无该userid时插入，有时更新
            if( $msg == false ){
                $failed_count = $failed_count + 1;
            }
        } catch (Exception $e){
            DI()->logger->error("同步部门时，插入或更新用户失败：", json_encode($user) . $e->getMessage() );
        }
        if ($failed_count != 0){
            DI()->response->setMsg("同步部门：$this->dept 时发生错误，请查看日志以分析原因，产生错误次数：$failed_count");
            return "failed!";
        }else {
            return "同步所属部门中的用户完成，部门id为：" . $this->dept;
        }
    }

    /**
     * @desc 查询单个用户信息，find的用法
     * @return mixed
     */
    public function queryUser(){
        // http://127.0.0.1/index.php?s=Site.queryUser&userid=bob
        $query = array('userid'=>$this->userid);
        $res = $this->mong->find($this->mdbcoll, $query, $field=array());

        return $res;
    }

    /**
     * @desc 删除用户
     * @return mixed
     */
    public function delUser(){
        $query = array('userid' => $this->userid);
        $res = $this->mong->delete($this->mdbcoll, $query);
        return $res;
    }


    /**
     * @desc 查询文档中指定字段的总数
     * @return string
     */
    public function countUser(){
        $query = array('userid' => 'bob');
        $res = $this->mong->count($this->mdb, $this->coll, $query);
        if ($res['ret'] == 200) {
            DI()->response->setMsg("ok");
            return $res['msg'];
        }else {
            DI()->response->setRet($res['ret']);
            DI()->response->setMsg($res['msg']);
            return "failed";
        }
    }

    /**
     * @desc 聚合去除重复的数据，示例：db.getCollection('wkuser').distinct('department')
     * @return string
     */
    public function distinctUser(){
        $dbName = $this->mdb;
        $collection = $this->coll;
        $where = [];
        $key = 'department';
        $res = $this->mong->distinct($dbName, $collection, $key, $where);
        if ($res['ret'] == 200 ) {
            DI()->response->setMsg("ok");
            return $res['msg'];
        }else {
            DI()->response->setRet($res['ret']);
            DI()->response->setMsg($res['msg']);
            return "failed";
        }
    }


    /**
     * @desc 聚合查询。
     * 示例：db.wkuser.aggregate([{$group: { _id:"$name", count:{$sum:1}}}])
     * @return string
     */
    public function aggregateUser(){
        $dbName = $this->mdb;
        $collection = $this->coll;
        $where = array();
        $group = array(
            "_id" => '$name',
            "count" => ['$sum'=>1,],
        );
        $res = $this->mong->aggregate($dbName, $collection, $where, $group);
        if ($res['ret'] == 200 ) {
            DI()->response->setMsg("ok");
            return $res['msg'];
        }else {
            DI()->response->setRet($res['ret']);
            DI()->response->setMsg($res['msg']);
            return "failed";
        }
    }

    /**
     * @desc 查找并修改发生的数据，本命令是单个文档提供了原子操作，因此效率高。此命令不支持多文档原子事务
     * db.getCollection('wkuser').findAndModify(
     *      {query:{userid:"bob"},update:{$inc:{status:+1}}
     *      $push:{order:1}}
     * })
     * 注意：返回的值是查询的值，而不是修改之后的值。
     * 即修改了数据，但返回的是修改之前的数据。
     *
     * @return string
     */
    public function findAndMUser(){
        $dbName = $this->mdb;
        $collection = $this->coll;
        $query = ['userid'=>'bob'];
        $update = array(
            '$inc' => ['status'=> +1,],
//            '$push' => ['order'=>1] // push的字段如果不是数组则会报错
        );
        $res = $this->mong->findAndModify($dbName, $collection, $query, $update);
        if ($res['ret'] == 200 ) {
            DI()->response->setMsg("ok");
            return $res['msg'];
        }else {
            DI()->response->setRet($res['ret']);
            DI()->response->setMsg($res['msg']);
            return "failed";
        }
    }




    /**
     * @param $object
     * @return array
     */
    private function object2array(&$object)
    {
        $object =  json_decode( json_encode($object),true);
        return  $object;
    }
}