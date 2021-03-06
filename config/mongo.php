<?php
return array(
    'mongo' => array(
        'host' => 'localhost',
        'port' => '27017',
        'db_name' => 'db',          // 数据库名
        'username' => 'user',
        'password' => 'password',
        'connect_timeout_ms' => '', // 连接超时时间
        'socket_timeout_ms' => '',  // socket超时时间
        'admin' => 'admin',     // 登录验证名，4.0以后增加
        'persist' => 'x',           // x 表示保持连接
    ),

    'wkuser' => 'wkuser',  // MongoDB 的库中的集合 collection，类似于数据库的一张表
    'coll' => 'msg',  // MongoDB 的库中的集合 collection，类似于数据库的一张表
);