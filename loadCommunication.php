<?php

/*
 * websocket负载通信脚本
 * */
define('DEBUG', 'on');
define('WEBPATH', dirname(__DIR__));
require_once __DIR__ . '/extend/libs/lib_config.php';

//引入TP自带加载文件
require_once __DIR__ . '/thinkphp/base.php';

//$client = new \Swoole\Client\TCP();
//$client->connect('127.0.0.1',9508);
//$resu = $client->send('testCommunication');

\app\common\SLog::info('负载调用');

$client = new Swoole\Client\WebSocket('127.0.0.1',9508, '/');
$client->connect(0.5 );

$loadData = [
    'type' => 'loadCommunication',
    'time' =>time(),
    'param' => 'list',
];

$resu = $client->send(json_encode($loadData));

\app\common\SLog::info('testCommunication Result:'.$resu);




