<?php
/**
 * Created by PhpStorm.
 * User: fengdan
 * Date: 2019/6/20
 * Time: 15:00
 */
$server = new swoole_websocket_server("0.0.0.0", 9503);

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
    $chat_msg=$data['text']['chat_msg'];
    if(empty($chat_msg)){
        $arr=[
            'code'=>1,
            'msg'=>'发送消息不能为空'
        ];
        $server->push($frame->fd, json_encode($arr,JSON_UNESCAPED_UNICODE));
        return;
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
    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1',6379);
    $chat_user=$redis->get('chat_user');

    $data=json_decode($chat_user,true);
    $user_id=$data['user_id'];

    $timestamp=mt_rand(946656000,1546272000);
    $timestamp2=mt_rand(946656000,1546272000);
    $date=date('Y-m-d',$timestamp);
    $date2=date('Y-m-d',$timestamp2);
    $id=$redis->incr('id');
    $user_id=$user_id;
    $time=time();
    $sql="insert into chat_msg(id,chat_msg,chat_time,hired,separated,user_id) values('$id','$chat_msg','$time','$date','$date2','$user_id')";
    $res=$swoole_mysql->query($sql);

    foreach ($server->connections as $fd) {
        // 需要先判断是否是正确的websocket连接，否则有可能会push失败
        if ($server->isEstablished($fd)) {
            $server->push($fd, $frame->data);
        }
    }

});

$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();