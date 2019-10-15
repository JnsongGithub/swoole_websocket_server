<?php

/**
 * 用户登录APP、链接websocket服务端
 * 环境配置
 *      CentOS 7.6~
 *      thinkphp5.1.38 LTS
 *      swoole4.4.4
 *
 * @date        2019-09-16
 * @author      jnsong
 * @version     1.0 版本号
 */

namespace app\boss\controller;

use app\boss\service\ChatWebSocketService;
use app\boss\service\LoginWebSocketService;
use app\boss\service\RoomWebSocketService;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Controller;
use think\facade\Cache;
use think\Request;
use think\swoole\Server;
use think\facade\Config;

class Socket extends Server
{
    const MAP_FD_UID_PREFIX = 'map_fd:'; //关联记录信息
	const MAX_REDIS_TIME = 86400;

	private $fd; //用户连接ID
	private $userId; //用户ID
	private $matchmakerId; //红娘/月老ID
	private $role; //角色类型

	//websocket配置信息
//    protected $host = 'devtabossapi.budingvip.com';
    protected $host = 'devtabossapi.budingvip.com';
    protected $port = 9508;
    protected $serverType = 'socket';
    protected $sockType = SWOOLE_SOCK_TCP | SWOOLE_SSL;  //支持https
    protected $option = [
        'worker_num'=> 4, //进程数量
        'max_coroutine'=> 3000,//每个进程支持最大协成数
        'daemonize'	=> false,
        'backlog'	=> 128,
        'app_path'	=> '/home/devwww/boss_api/application/',


        'ssl' => true,
        'ssl_cert_file' => '/home/ssl/budingvip.com.pem',
        'ssl_key_file' => '/home/ssl/budingvip.com.key',

        //PID文件
        'pid_file' => '/productlog/login_socket.pid',

//        'heartbeat_check_interval' => 5, //每5秒侦测一次心跳
//        'heartbeat_idle_time' => 15, //超出15秒切断链接



        'open_tcp_keepalive' => 1, //TCP-Keepalive死连接检测 开启
        'tcp_keepidle' => 5, //连接在n秒内没有数据请求，将开始对此连接进行探测。
        'tcp_keepinterval' => 2, //探测的间隔时间，单位秒
        'tcp_keepcount' => 2,//探测的次数，超过次数后将close此连接。

        /*
         * task底层使用Unix Socket管道通信，是全内存的，没有IO消耗。单进程读写性能可达100万/s
         * 最大值不得超过SWOOLE_CPU_NUM * 1000
         * Task进程内不能使用swoole_mysql、swoole_redis、swoole_event等异步IO函数
         * 功用：用于将慢速的任务异步地去执行
         *
         * */
        'task_worker_num' => 200, //最大并发数/10的设置比例


        'task_enable_coroutine' => true, //task_enable_coroutine，开启后自动在onTask回调中创建协程，调用特性见：https://wiki.swoole.com/wiki/page/54.html



    ];

    protected $request_url = [
        'login' => [
            'open' => 'loginSocket',
            'message' => 'loginMessage',
            'close' => 'loginColse',
        ],
        'room' => [
            'open' => 'roomSocket',
            'message' => 'roomMessage',
            'close' => 'roomColse',
        ],
        'chat' => [
            'open' => 'chatSocket',
            'message' => 'chatMessage',
            'close' => 'chatColse',
        ],
    ];



	//握手成功
	public function onConnect($server, $fd)
	{
        SLog::info('onConnect链接信息:'.$fd);
	}

	//执行回调
	public function onOpen($server, $request)
	{
		//做用户ID校验
		$this->fd = $request->fd;

        SLog::info($request->fd.'_SOCKET链接信息:'.json_encode($request));

		parse_str($request->server['query_string'],$param);
		SLog::info($request->fd.'_SOCKET入参:'.json_encode($param));
		$this->userId = $param['userId'];
		$this->matchmakerId = $param['matchmakerId'];
		$this->role = $param['role'];
        $param['fd'] = $request->fd;

		$request_uri = trim($request->server['request_uri'],'/');
        $request_method = $this->request_url[$request_uri]['open'];
        SLog::info('Open执行方法:'.$request_method);
        switch($request_uri)
        {
            case "login":
                $service = new LoginWebSocketService();
                break;
            case "room":
                $service = new RoomWebSocketService();
                break;
            case "chat":
                $service = new ChatWebSocketService();
                break;
        }

        $resu = $service->$request_method($server,$request,$param);
        SLog::info('链接检查结果:'.json_encode($resu));
	}

	/*
	 * 消息接收回调触发
	 * */
    public function onMessage($server, $frame)
    {
        SLog::info('原参数:'.json_encode($frame));
        SLog::info($frame->fd.' 说：'.$frame->data.'|CODE:'.$frame->opcode.'数据帧完整性:'.$frame->finish);

        $data = json_decode($frame->data,true);
        switch($data['type'])
        {
            case "login":
                $service = new LoginWebSocketService();
                break;
            case "room":
                $service = new LoginWebSocketService();
                break;
            case "chat":
                $service = new LoginWebSocketService();
                break;
        }
        $request_method = $this->request_url[$data['type']]['message'];
        $service->$request_method($server,$frame);
    }

    /*
     * 投递任务
     * */
    public function onTask($server, $task)
    {
        $stats = $server->stats();
        SLog::info('连接数0000:'.json_encode($stats));
        SLog::info($task->worker_id.'_onTask回调:'.json_encode($task));

        //执行Redis发布者分发消息



        $swoole_redis = initRedis();
        $test_task = $swoole_redis->set('task_id',100);
        SLog::info('测试TASK实现Redis:'.$test_task);


        $stats = $server->stats();
        SLog::info('连接数1111:'.json_encode($stats));
        //触发完成回调
//        $server->finish($task->data);
    }

    /*
     * 任务投递回调
     * */
    public function onFinish($server, $task_id, $data)
    {
        SLog::info($task_id.':任务调度结果：'.json_encode($data));
        //执行退出直播间的分发
        $stats = $server->stats();
        SLog::info('连接数3333:'.json_encode($stats));
    }

    /*
     * 关闭链接回调触发
     * */
    public function onClose($server, $fd, $reactorId)
    {
        SLog::info($fd.':退出直播间_线程：'.$reactorId);
        //执行退出直播间的分发

    }


    /*
     * Start开启服务时回调，和start不分先后,该函数触发的次数为worker_num的数量
     * */
    public function onWorkerStart($server, $worker_id)
    {
//        SLog::info('onWorkerStart请求信息---:'.json_encode($server));
//        SLog::info('onWorkerStart请求信息___:'.json_encode($worker_id));

        //向指定频道发送消息
        try {
            $channelName = "testPubSub";

            //客户端链接指定频道,用于发言使用
            $options = [
                // 服务器地址
                'host' => '192.168.100.102',
                // redis 端口号
                'port'      => '6379',
                // redis配置的密码
                'password'  => 'ZY7Fx3u0cxjR52kymnxJGczP',
                // 缓存时间
                'timeout'   => 3600,
                //选择缓存库
                'select' => '0',
                // 缓存前缀
                'prefix' => '',
                //是否长连接
                'persistent' => false,
                //序列化
                'serialize' => '',
                'expire' => 0,
            ];

            $ret = [];

            //设置php脚本执行时间
            set_time_limit(0);
            //设置socket连接超时时间
            ini_set('default_socket_timeout', -1);

//            $redis = new \think\cache\driver\Redis($options);
            /*
            $redis = Cache::store('redis');
            $redis->pconnect($options['host'], $options['port']);

            $redis->subscribe(array($channelName), function ($redis, $chan, $msg){
                $ret[] = [$chan, $msg];
            });*/

        } catch (Exception $e){

            $ret = [
                $e->getCode(),
                $e->getMessage()
            ];
        }

        SLog::info('指定频道链接尝试___:'.json_encode($ret));



    }

    /*
     * 程序异常结束触发
     * 如：被强制kill、致命错误、core dump时无法执行onWorkerStop回调函数
     *
     * */
    public function onWorkerStop($server, $worker_id)
    {
       /* SLog::info('onWorkerStop请求信息---终止通知---:'.json_encode($server));
        SLog::info('onWorkerStop请求信息---终止通知___:'.json_encode($worker_id));*/
    }

    /*
     * 管理进程启动时调用
     * */
    public function onManagerStart($server)
    {
        SLog::info('请求信息---onManagerStart---:');
    }


    public function onPipeMessage($server, $src_worker_id, $message)
    {
        SLog::info('onPipeMessage请求信息---onPipeMessage---:'.json_encode([$src_worker_id, $message]));
    }

    public function onRequest($request, $response)
    {
        SLog::info('onRequest请求信息:'.json_encode($request));
    }

    public function onReceive($server, $fd, $from_id, $data)
    {
        SLog::info('onReceive请求信息:'.json_encode($data));
    }

    public function onManagerStop($server)
    {
        SLog::info('请求信息---onManagerStart---:');
    }







}
