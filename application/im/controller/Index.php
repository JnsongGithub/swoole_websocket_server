<?php

namespace app\im\controller;


use app\common\SLog;
use think\Controller;
use think\Loader;

class Index extends Controller
{


    public function index()
    {
//        $output = [
//            'value' => 'test'
//        ];
//        $resu = exec('cd / && sudo php loadCommunication.php');
//        SLog::info('命令结果:'.json_encode($resu));
//        SLog::info('命令结果PUT:'.json_encode($output));

        define('PROJECT_BASE_PATH', trim($_SERVER['DOCUMENT_ROOT'], 'public'));



//        $resu = exec('cd '.PROJECT_BASE_PATH.' && sudo /usr/local/php/bin/php think loadcommunication',$output, $test);
        $resu = exec (`cd .PROJECT_BASE_PATH. && sudo /usr/local/php/bin/php think loadcommunication`,$output, $test);


        echo `cd .PROJECT_BASE_PATH. && sudo /usr/local/php/bin/php think loadcommunication`;
        //SLog::info('脚本命令:'.'cd '.PROJECT_BASE_PATH.' && sudo /usr/local/php/bin/php think loadcommunication');

//        $resu = include_once(PROJECT_BASE_PATH.'loadCommunication.php');

        SLog::info('命令结果:'.json_encode($resu));
        SLog::info('命令结果____:'.json_encode($output));
        SLog::info('命令结果=====:'.json_encode($test));
//
//        $resu = include_once(PROJECT_BASE_PATH.'loadCommunication.php');
//        SLog::info('命令结果:'.$resu);



//        $url = 'http://127.0.0.1:9508';
//        $json_data = [
//            'name' => 'xiaohua',
//        ];

//        http_post($url, $json_data);


        //通知负载服务器
//        $server = new \Swoole\Client\TCP();
       // $server = new \swoole_http_client('127.0.0.1', 9508);
//        $server->connect('127.0.0.1', 9508);
        //$resu = $server->send('通知负载服务器');
       // SLog::info('通知负载服务器'.$resu);
//        $resu = $server->recv();

    }

}
