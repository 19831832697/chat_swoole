<?php
/**
 * Created by PhpStorm.
 * User: fengdan
 * Date: 2019/6/20
 * Time: 14:23
 */
$server = new swoole_websocket_server("0.0.0.0", 9502);

$server->on('open', function($server, $req) {
    echo "connection open: {$req->fd}\n";
    //连接数据库
    $swoole_mysql = new Swoole\Coroutine\MySQL();
    $swoole_mysql->connect([
        'host' => '192.168.3.132',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456abc',
        'database' => 'test',
    ]);
});

$server->on('message', function($server, $frame) {
    $data=json_decode($frame->data,true);
    $user_name=$data['text']['user_name'];
    if(empty($user_name)){
        $arr=[
            'code'=>1,
            'msg'=>'用户名不能为空'
        ];
        $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
        return;
    }else{
        $arr=[
            'code'=>2,
        ];
        $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
    }
    //连接数据库
    $swoole_mysql = new Swoole\Coroutine\MySQL();
    $swoole_mysql->connect([
        'host' => '192.168.3.132',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456abc',
        'database' => 'test',
    ]);
    $sql="insert into chat_user(user_name) values('$user_name')";
    $res=$swoole_mysql->query($sql);

    $user_sql="select * from chat_user where user_name='$user_name'";
    $user_res=$swoole_mysql->query($user_sql);
    $user_id=$user_res[0]['user_id'];

    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1',6379);
    $key="chat_user";
    $arr=[
        'user_id'=>$user_id,
        'user_name'=>$user_name
    ];
    $redis->set($key,json_encode($arr,JSON_UNESCAPED_UNICODE));
});

$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();