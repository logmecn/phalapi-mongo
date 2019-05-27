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

    public function getRules() {
        return array(
            'index' => array(
                'username'  => array('name' => 'username', 'default' => 'PhalApi', 'desc' => '用户名'),
            ),
            'queryUser' => array(
                'userId' => array('name' => 'userId', 'desc'=> '用户的userid'),
            ),
            'syncUserByDept' => array(
                'dept' => array('name' => 'dept', 'type' => 'int', 'default' => 1, 'min' => 1, 'desc' => '同步该部门id下的所有人员详情'),
            ),
            'delUser' => array(
                'userId' => array('name' => 'userId', 'desc'=> '用户的userid'),
            ),
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
        $mong = DI()->mongo;
//        $arr_user = $this->object2array($user);
        $user = array (
            'userid' => 'bob',
            'name' => 'Bob',
            'english_name' => '',
            'mobile' => '',
            'department' =>
                array (
                    0 => 15,
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
            $ns = DI()->config->get('mongo.namespace');
            $msg = $mong->insert($user, $ns.".wkuser") != true;
            if ( $msg){
                $es = $msg;
            }
        } catch (Exception $e){
            DI()->logger->error("同步部门时，插入或更新用户失败：", json_encode($user) . $e->getMessage() );
            $es = $e->getMessage();
        }
        if ($es != ""){
            return "同步部门：$this->dept 时发生错误，请查看日志以分析原因。部分原因为：". $es ;
        }else {
            return "同步所属部门中的用户完全，部门id为：" . $this->dept;
        }
    }

    /**
     * @desc 查询单个用户信息，find的用法
     * @return mixed
     */
    public function queryUser(){
        // http://127.0.0.1/index.php?s=Site.queryUser&userid=bob
        $query = array('userid'=>$this->userid);
        $res = $this->mong->find($this->ns, $query, $field=array());
        return $res;
    }

    /**
     * @desc 删除用户
     * @return mixed
     */
    public function delUser(){
        $query = array('userid' => $this->userid);
        $res = $this->mong->delete($this->ns, $query);
        return $res;
    }



    /**
     * @param $object
     * @return mixed
     */
    private function object2array(&$object)
    {
        $object =  json_decode( json_encode($object),true);
        return  $object;
    }
}