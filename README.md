# 基于 PhalApi2 框架的 MongoDB 扩展

## 前言

使用 MongoDB 存储数据时，用到此扩展。

### 注意：
由于MongoDB在 PHP5.6之前的驱动与 PHP7.0 以后的驱动完全不一致，所以此项目建议使用 PHP7.1以上，建议使用当前较新的 PHP7.2或7.3版本。
php的windows驱动下载地址：https://windows.php.net/downloads/pecl/releases/mongodb/，下载到 ext 目录，并加到php.ini文件中。
其他系统的可以使用 yum/apt等工具下载安装
## 安装及配置方法：
1. 在 config 目录下，新建一个文件：config/mongo.php，内容如下
    ```
    <?php
    return array(
        'mongo' => array(
            'host' => 'localhost',  // 连接主机
            'port' => '27017',      // 端口
            'db_name' => 'wxwork',  // 连接MongoDB的库
            'username' => 'wxwork', //登录用户名
            'password' => 'wxwork', // 密码
            'connect_timeout_ms' => '',  // tcp连接超时时间 
            'socket_timeout_ms' => '',    // socket超时时间 
    
            'persist' => 'x',       // 连接保持（MongoDB3.*新增）
        ),
    
        'wkuser' => 'wkuser',  // MongoDB 的库中的集合 collection，类似于数据库的一张表
    
    );
    ```

2. 在 config/di.php 靠后的位置加入一行：
    ```
    $di->mongo = new PhalApi\Mongo\Lite($di->config->get('mongo.mongo'));
    ```

3. 在 composer.json 中的 require 中加入一行：
    （说明：暂时未支持，后续争取支持使用 phalapi/mongo）
    ```
    "phalapi/mongo": "dev-master"
    ```
4. 执行命令：
    ```shell
    php composer.phar update --no-interaction --ansi

    ```
5. 可以开始测试了。
6. 问题：在使用较低版本的 mongodb 驱动连接较低MongoDB服务器时，会提示：Authentication failed。这时使用较新的驱动即可解决。

## 使用方法：
各种使用方法示例，请参考 demo_app 目录下的 Site.php 。

其他无示例的操作，可以调用 command 函数进行封装。

如果您有其他命令要封装，请发issue ，我会尽力增加到项目中。

1. 插入示例：
      ```php
      $mong = DI()->mongo;
      // $arr_user = $this->object2array($user);
      // 把接收到的 json 值转为数组
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
          $msg = $mong->insert($arr_user, $ns.".wkuser") != true;
          if ( $msg ){
              $es = $msg;
          } else {
              // 其他提示
          }
      } catch (Exception $e){
          DI()->logger->error("插入或更新用户失败：", json_encode($arr_user) . $e->getMessage() );
          $es = $e->getMessage();
      }
      ```
2. 读取：
    （其他操作，请参考demo）