<?php

/**
 * IM系统基类入口
 *
 * IM系统请求的入口类
 * @author  jnsong
 * @version 1.0.0
 */

namespace app\im\controller;

use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\RpcClient;
use app\common\SLog;
use app\im\logic\AwayActionLogic;
use app\im\logic\ImPortalLogic;
use app\im\logic\PublicLogic;
use think\Exception;
use think\facade\Config;
use think\swoole\Server;


class ImPortal extends Server
{
    private $requestTypes = 'default';
    private $config = [];

    public function __construct()
    {
        $this->config = Config::pull('swoole_im');
        //SLog::info('初始化信息:'.json_encode($this->config));

        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->mode = $this->config['mode'];
        $this->sockType = $this->config['sock_type'];
        $this->serverType = $this->config['type'];
        $this->option = array_merge($this->config['option'], $this->option);

        parent::__construct();
    }

    /**
     * WebSocket握手成功,回调触发
     * @param mixed $server 服务对象
     * @param mixed $request 对象信息
     * @return mixed
     * @throws Exception
     */
    public function onOpen($server, $request)
    {
        try{
            if(!isset($request->get['userId']) || !$request->get['heartBeatType'])
            {
                BusinessException::throwException(Constant::NECESSARY_PARAM_LOSE);
            }
            SLog::info($request->get['userId'].'_Open入参:'.json_encode($request));
            /*
             * 握手成功
             * 1、请求RPC服务器查询用户信息
             * 2、初始化缓存用户信息
             * 3、push消息到客户端
             * */
            //发送RPC请求(数据需要包一层data，rpc服务端会自动剔除外层data拿数据)
//            $param = [
//                'userId' => $request->get['userId']
//            ];
//            $rpc_resu = PublicLogic::rpcServerRequest('init_user_info', $param);
            $rpc_resu = '{"code":0,"msg":"\u6210\u529f","data":{"userId":"100","icon":"https:\/\/amoylove.oss-cn-beijing.aliyuncs.com\/static\/image\/taoai\/temp\/timg.gif","nickName":"\u5927\u6d77\u91cc\u7684\u8783\u87f9","role":1}}';
            $rpc_resu = json_decode($rpc_resu, true);

            SLog::info($request->get['userId'].'_RPC——DATA:'.json_encode($rpc_resu));
            if($rpc_resu['code'] !== Constant::SUCCESS || !isset($rpc_resu['data'])  || empty($rpc_resu['data']))
            {
                BusinessException::throwException(isset($rpc_resu->msg)?$rpc_resu->msg:Constant::FAILED);
            }
            $rpc_resu['data']['timestamp'] = time();
            $rpc_resu['data']['fd'] =$request->fd;
            $set_cache = ImPortalLogic::onOpenSetCache($rpc_resu['data']);
            SLog::info($request->get['userId'].'_记录用户登录哈希:'.$set_cache);

            //fd到userId做映射关系
            $redis = new Redis();
            $fd_mapping_userid = $redis->setex(PublicLogic::getFdMapping($rpc_resu['data']['fd'],$request->server['server_port']), Constant::MAP_FD_CACHE_TIME, $request->get['userId']);
            SLog::info($request->get['userId'].'_fd到userId做映射关系'. $fd_mapping_userid);

            if($request->get['heartBeatType'] == 2)
            {
                //设置心跳
                $heartbeat = ImPortalLogic::heartbeatReply($server, $request);
                SLog::info('心跳设置:'.$heartbeat);
            }

            //生成token
            $socketToken = tokenCreate($request->get['userId']);
            $set_token_timer = $redis->setex(Constant::TOKEN_PREFIX.$request->get['userId'], Constant::TOKEN_TIME_OUT, $socketToken);
            SLog::info($request->get['userId'].'_token令牌有效期'. $set_token_timer);

            $ret = Constant::getReturn(Constant::HANDSHAKE_SUCCESS);
            $ret['messageType'] = Constant::SOCKET_MAPPING['init'];
            $ret['socketToken'] = $socketToken;

            SLog::info('当前客户端号：'.$request->fd);
            //通知一号
            if($request->fd == 2)
            {
                $test_one = $server->push(1, '测试负载通信');
                SLog::info('通知一号测试：'.$test_one);
            }

        }catch(BusinessException $e){
            $ret = returnCatch($e,'default');
        }
        SLog::info($request->get['userId'].'_Open出参:'.json_encode($ret));
        $server->push($request->fd, json_encode($ret));
    }


    /**
     * 接收客户端发送消息
     * @access public
     * @param mixed $server 服务对象
     * @param mixed $frame 消息对象
     * @return mixed
     * @throws Exception
     */
    public function onMessage($server, $frame)
    {

        //负载通知校验
        if(json_decode($frame->data, true)['type'] == 'loadCommunication')
        {
            try{
                SLog::info('执行消息发布：停止接来下的执行:'.$frame->data);
                // todo

                $disconnect_fd = $server->disconnect($frame->fd);
                SLog::info($frame->fd.'_服务端负载断开连接:'.$disconnect_fd);
            }catch(\Swoole\ExitException $e){
                SLog::info('负载结束');
            }
        }else{
            try{
                //设置php脚本执行时间
                set_time_limit(0);
                SLog::info($frame->fd.'_消息对象:'.json_encode($frame));





                //校验数据
                $check_resu = ImPortalLogic::checkMessageFormat($frame);
                SLog::info('校验返回:'.json_encode($check_resu));
                $data = $check_resu['data'];
                $data['fd'] = $frame->fd;
                $this->requestTypes = $data['requestTypes'];

                //业务类型处理
                $ret = ImPortalLogic::onMessageAction($data, $server);
                SLog::INFO($frame->fd.'_Message结果:'.json_encode($ret));
            }catch(BusinessException $e) {
                $ret = returnCatch($e, $this->requestTypes);
                $resu = $server->push($frame->fd, json_encode($ret));
                SLog::info($frame->fd.'_Message结果响应:'.json_encode([$ret,$resu]));
            }
        }
    }


    /**
     * 任务投递
     * @access public
     * @param mixed $server 服务对象
     * @param mixed $task 任务对象
     * @return mixed
     */
    public function onTask($server, $task)
    {
        SLog::info($task->data['userId'].'_触发任务进程:'.json_encode($task));
        switch($task->data['taskType'])
        {
            //初始化进入直播间
            case 'createBroadcast':
                $redis = new Redis();
                $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1); //设置Redis订阅客户端超时限制 -1不超时
                $redis->subscribe([$task->data['channelName']], function ($redis, $chan, $msg) use ($task, $server){
                    SLog::info('消息接收:'.json_encode([$task, $chan, $msg]));

                    //检查是否在直播间里
                    $checkExistRoom = ImPortalLogic::checkUserExistRoom($task->data['userId']);
                    SLog::info($task->data['userId'].'_检查用户是否在直播间里为有效用户:'.$checkExistRoom);
                    if(!$checkExistRoom)
                    {
                        $quxiao = $redis->unsubscribe([$chan]);
                        SLog::info($task->data['userId'].'_尝试取消订阅:'.$quxiao);
                    }

                    //获取用户fd
                    $isEstablished = $server->isEstablished($task->data['fd']);
                    SLog::info($task->data['userId'].'_校验是否为有效的链接('.$task->data['fd'].'):'.$isEstablished);
                    if($isEstablished)
                    {


                        $push_resu = $server->push($task->data['fd'], $msg);
                        SLog::info($task->data['userId'].'_PUSH消息:'.json_encode([$msg, $push_resu]));
                    }
                });
                break;
        }
    }


    /*
     * 关闭链接回调触发
     * */
    public function onClose($server, $fd, $reactorId)
    {
        SLog::info($fd.':断线直播间：'.$reactorId.'_服务:'.json_encode($server));
        //断线处理
        AwayActionLogic::socketClose($server, $fd, $reactorId);

        //swoole断开客户端链接
        if($server->isEstablished($fd))
        {
            $disconnect_fd = $server->disconnect($fd);
            SLog::info($fd.'_服务端主动断开连接:'.$disconnect_fd);
        }
    }



    /*
     * 程序异常结束触发
     * 如：被强制kill、致命错误、core dump时无法执行onWorkerStop回调函数
     *
     * */
    public function onWorkerStop($server, $worker_id)
    {
         SLog::info('onWorkerStop请求信息---终止通知___:'.json_encode($worker_id));
    }





    /**
     * 初始化函数
     * @mixed
     */
    public function init()
    {
        $im_system = Config::pull('im_system');
        $this->messageTypes = $im_system['message_list'];
        SLog::info('配置初始化：' . json_encode($this->messageTypes));
    }


    /**
     * WebSocket服务启动触发
     * @access public
     * @param mixed $server 服务对象
     * @param mixed $worker_id 对象信息
     * @mixed array 返回类型
     */
    public function onWorkerStart($server, $worker_id)
    {
        SLog::info($worker_id.'启动Worker进程--------：'.json_encode($server));
        try{


        }catch(BusinessException $e){

        }
    }

    /**
     * WebSocket握手成功
     * @access public
     * @param mixed $server 服务对象
     * @param mixed $fd 对象信息
     * @mixed array 返回类型
     */
    public function onConnect($server, $fd)
    {
        SLog::info($fd.'：WebSocket握手成功('.$fd.')'.json_encode($server));
    }


    public function onFinish($server, $task_id, $data)
    {
        SLog::info($task_id.'_任务完成投递---:'.json_encode($data));
    }


    public function onReceive($server, $fd, $from_id, $data)
    {
        SLog::info('onReceive请求信息:'.json_encode($data));
    }

    public function onPacket($server, $fd, $from_id, $data)
    {
        SLog::info( __METHOD__.'请求信息:'.json_encode($data));
    }

    public function PipeMessage($server, $fd, $from_id, $data)
    {
        SLog::info( __METHOD__.'请求信息:'.json_encode($data));
    }

    public function ManagerStart($server, $fd, $from_id, $data)
    {
        SLog::info( __METHOD__.'请求信息:'.json_encode($data));
    }

    public function Request($server, $fd, $from_id, $data)
    {
        SLog::info( __METHOD__.'请求信息:'.json_encode($data));
    }


    public function onRequest($request, $response)
    {
        SLog::info(__METHOD__.'HTTP请求入参:'.json_encode($request));
        SLog::info(__METHOD__.'服务:'.json_encode($response));


        //通知一号
        if($request->fd == 2)
        {
            $test_one = $response->push(1, '测试负载通信');
            SLog::info('通知一号测试：'.$test_one);
        }

    }

    /*
     * 未测试回调信息

    public function onManagerStart($server)
    {
        SLog::info('请求信息---onManagerStart---:');
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
    */




}
