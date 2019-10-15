<?php

/*
 * websocket负载通信脚本
 * */
define('DEBUG', 'on');
//define('WEBPATH', dirname(__DIR__));
//require_once __DIR__ . '/extend/libs/lib_config.php';

//引入TP自带加载文件
require_once __DIR__ . '/thinkphp/base.php';

//$client = new \Swoole\Client\TCP();
//$client->connect('127.0.0.1',9508);
//$resu = $client->send('testCommunication');

\app\common\SLog::info('负载调用');


$client = new swoole_client(SWOOLE_SOCK_TCP);

//$con = $client->connect('127.0.0.1', 9508, -1);
//\app\common\SLog::info('链接有效请:'.$con);

if($client->connect('127.0.0.1', 9508))
{

    $isCon = $client->isConnected();
    \app\common\SLog::info('链接状态:'.$isCon);

    $loadData = [
        'type' => 'loadCommunication',
        'time' =>time(),
        'param' => 'list',
    ];

    $resu = $client->send(json_encode($loadData));
    \app\common\SLog::info('发送结果:'.$resu);

    $isCon = $client->isConnected();
    \app\common\SLog::info('链接状态______:'.$isCon);
}


//$loadData = [
//    'type' => 'loadCommunication',
//    'time' =>time(),
//    'param' => 'list',
//];
//
//$resu = $client->send(json_encode($loadData));



    $recv = $client->recv();
    \app\common\SLog::info('接收消息:'.$recv);



\app\common\SLog::info('recv输出:'.$resu);



$client->close();






\app\common\SLog::info('testCommunication Result:'.$resu);




