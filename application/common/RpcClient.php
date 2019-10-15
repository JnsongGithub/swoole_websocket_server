<?php

/**
 * RPC客户端封装类
 *
 * 类的详细介绍（可选。）。
 * @author      alvin 作者
 * @version     1.0 版本号
 */

namespace app\common;


define('DEBUG', 'on');
define('WEBPATH', dirname(__DIR__));

use app\common\SLog;
use Swoole\Client\RPC;
use Swoole\Protocol\RPCServer;
use think\Console;
use think\facade\Config;


class RpcClient
{

    public function __construct()
    {
        //引入TP自带加载文件
        require_once __DIR__ . '/../../thinkphp/base.php';

        require_once __DIR__ . '/../../extend/libs/lib_config.php';
    }

    /**
     * Rpc客户端发送请求
     * @access public
     * @param mixed $request_type 请求类型
     * @param mixed $param 请求入参
     * @return mixed
     */
    public function rpcClientSend($request_type, $param)
    {
        $rpc_config = Config::pull('im_system');
        SLog::info('初始化RPC服务配置:'.json_encode($rpc_config));
        $rpc_request_cinfo = $rpc_config['rpc_server'][$request_type];
        $class = $rpc_request_cinfo['class'];
        $method = $rpc_request_cinfo['method'];

        $client = \Swoole\Client\RPC::getInstance();

        $client->setEncodeType(\Swoole\Protocol\RPCServer::DECODE_JSON, false); //打包、解压方式
//        $client->setEncodeType(false, true);
//        $client->putEnv('app', 'test');
//        $client->putEnv('appKey', 'test1234');
        $client->auth(Constant::RPC_CLIENT_USER, Constant::RPC_CLIENT_PASS);

        $client->addServers(Constant::RPC_SERVER_INFO);

        $timer_start = microtime(true);
        $ret = $client->task(Constant::RPC_NAMESPACE.$class.'::'.$method, $param, function($retObj) {
            SLog::info('RpcCallBack:'.json_encode($retObj));
        });
        $timer_end = (microtime(true) - $timer_start) * 1000;

        //接收数据
        $n = $client->wait(0.5); //500ms超时

        SLog::info($timer_end.'_RPC响应结果:'.json_encode([$ret,$n]));
        unset($client);
        return $ret->data;

    }




}

