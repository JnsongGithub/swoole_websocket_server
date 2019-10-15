<?php

namespace app\command;

use app\common\SLog;
use Swoole\Client\WebSocket;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class loadCommunication extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('loadcommunication');
        // 设置参数

    }

    protected function execute(Input $input, Output $output)
    {
        /*
         * websocket负载通信脚本
         * */
        define('DEBUG', 'on');
        define('WEBPATH', dirname(__DIR__));

        define('PROJECT_BASE_PATH', trim(dirname(__DIR__), 'application'));


//        SLog::info('引入文件:'.PROJECT_BASE_PATH . '/extend/libs/lib_config.php');

        require_once PROJECT_BASE_PATH . 'extend/libs/lib_config.php';



//引入TP自带加载文件
        require_once PROJECT_BASE_PATH . 'thinkphp/base.php';

//$client = new \Swoole\Client\TCP();
//$client->connect('127.0.0.1',9508);
//$resu = $client->send('testCommunication');

        \app\common\SLog::info('负载调用');

        $client = new WebSocket('127.0.0.1',9508, '/');
        $client->connect(0.5 );

        $loadData = [
            'type' => 'loadCommunication',
            'time' =>time(),
            'param' => 'list',
        ];

        $resu = $client->send(json_encode($loadData));

        \app\common\SLog::info('testCommunication Result:'.$resu);
    }
}
