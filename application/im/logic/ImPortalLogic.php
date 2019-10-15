<?php
/**
 * IM系统基类入口-逻辑层
 *
 * IM系统请求-逻辑层
 * @author  jnsong
 */
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;
use think\facade\Config;
use think\facade\Hook;

class ImPortalLogic
{
    /**
     * WebSocket握手成功,用户信息设置 缓存(有效期)
     * @access public static
     * @param mixed $param 对象信息
     * @return mixed  返回类型
     */
    public static function onOpenSetCache($param)
    {
        //设置用户哈希记录
        $redis = New Redis();
        $cache_resu = $redis->hSet(Constant::MAP_USER_INFO, $param['userId'], json_encode($param));
        return $cache_resu;
    }

    /**
     * 获取用户哈希数据记录
     * @access public static
     * @param mixed $userId 对象信息
     * @param mixed $tryCode 对象信息
     * @return mixed
     */
    public static function getMapUserInfo($userId, $tryCode = Constant::OBJECT_USER_LOSS)
    {
        $redis = New Redis();
        $cache_resu = $redis->hGet(Constant::MAP_USER_INFO, $userId);
        SLog::info($userId.'_获取用户哈希记录:'.$cache_resu);
        if(!$cache_resu)
        {
            BusinessException::throwException($tryCode);
        }
        return json_decode($cache_resu, true);
    }


    /**
     * 校验客户端Message结果
     * @access public
     * @param mixed $frame 消息对象
     * @param mixed $messageTypes 类型列表
     * @return mixed
     * @throws Exception
     */
    public static function checkMessageFormat($frame)
    {
        //校验客户端连接号和请求数据
        if(!$frame->fd || !$frame->data)
        {
            BusinessException::throwException(Constant::NECESSARY_PARAM_LOSE);
        }

        $result = [
            'fd' => $frame->fd,
            'data' => json_decode($frame->data, true),
        ];
        SLog::info('DATA数据:'.json_encode($result['data']));

        //临时屏蔽校验信息，正式上线需要注释掉
        return $result;

        //校验请求类型
        if(!isset($result['data']['messageTypes']) || !in_array($result['data']['messageTypes'],array_keys($messageTypes))){
            BusinessException::throwException(Constant::TYPES_OF_REJECTION);
        }

        //校验签名
        if(!isset($result['data']['type']) || empty(Constant::SECRET_KEYS[$result['data']['type']]))
        {
            BusinessException::throwException(Constant::SIGN_FAIL);
        }
        $local_sign = genSignApp($result['data'],Constant::SECRET_KEYS[$result['data']['type']]);
        SLog::info('本地签名:'.$local_sign);
        if($local_sign != $result['data']['data']['sign'])
        {
            BusinessException::throwException(Constant::SIGN_FAIL);
        }

        //验证token
        if(!isset($result['data']['token']) || $result['data']['token'] != getUserTokenKey($result['data']['token']))
        {
            BusinessException::throwException(Constant::TOKEN_UNUSUAL);
        }
        $redis = new Redis();
        $local_token = $redis->get(getUserTokenKey($result['data']['token']));
        SLog::info('本地Token:'.$local_token);
        if(!$local_token)
        {
            BusinessException::throwException(Constant::TOKEN_UNUSUAL);
        }

        //校验有效期
        if(!isset($result['data']['timestamp']) || (time()-$result['data']['timestamp'])>Constant::IM_REQUEST_VALIDITY)
        {
            BusinessException::throwException(Constant::SIGN_FAIL);
        }
        return $result;
    }

    /**
     * 接收消息，返回对应信息
     * @access public static
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed  返回类型
     * @throws Exception
     */
    public static function onMessageAction($data, $server)
    {
        $server_stats = $server->stats();
        SLog::info('Switch前:'.json_encode([$data,$server_stats]));

        /*$temp_config = [
            'class' => 'HeartbeatReply',
        ];

        $class = "HeartbeatReply";

        $obj = new $class();
        SLog::info('OBJ:'.$obj);
        exit();*/

        //钩子配置
        switch($data['requestTypes'])
        {
            //链接心跳
            case 'heartbeat':
                $actionObj = new HeartbeatReply();
                break;

            //订阅直播间
            case 'createBroadcast':
                $actionObj = new BroadcastCreateLogic();
                break;

            //直播间发送消息
            case 'BroadcastSendMessage':
                $actionObj = new BroadcastMessageLogic();
                break;

            //直播间送礼物
            case 'SendGiftBroadcast':
                $actionObj = new BroadcastGiftLogic();
                break;

            //申请排麦
            case 'ApplyBroadcast':
                //嘉宾取消排麦
            case 'CancelBroadcast':
                $actionObj = new BroadcastApplyAnchorLogic();
                break;

            //红娘处理排麦
            case 'AgreeApplyBroadcast':
                $actionObj = new BroadcastApplyActionLogic();
                break;

            //嘉宾确认
            case 'ViewerBroadcastConfirm':
                $actionObj = new BroadcastApplyConfirmLogic();
                break;

            //红娘把嘉宾下麦
            case 'LowerBroadcast':
                $actionObj = new BroadcastLowerLogic();
                break;

            //一对一聊天
            case 'ChatAloneSend':
                $actionObj = new ChatAloneLogic();
                break;

            /*//直播间下麦
            case 'down_broadcast':
                $actionObj = new BroadcastKickLogic();
                break;*/

            //邀请(邀请围观、邀请上麦)
            case 'InviteGuests':
                $actionObj = new BroadcastInviteLogic();
                break;

            //被邀请人处理
            case 'InviteHandle':
                $actionObj = new BroadcastInviteHandleLogic();
                break;

            //默认...
            default:
                $actionObj = new PublicLogic();
                break;
        }
        $messageResu = $actionObj->run($data, $server);

        $server_stats = $server->stats();
        SLog::info('Switch后:'.json_encode($server_stats));
        return $messageResu;
    }


    /**
     * 服务器端定时心跳
     * @access public static
     * @param mixed $server
     * @param mixed $request
     * @return mixed
     */
    public static function heartbeatReply($server, $request)
    {
        //设置心跳
        \Swoole\Timer::tick(50000, function($timer_id) use($server, $request){
            SLog::info($timer_id.'_定时器开始:'.$request->fd, 'heartbeat');

            $checkLink = $server->isEstablished($request->fd);
            SLog::info($timer_id.'_检查客户端链接状态:'.$checkLink, 'heartbeat');
            if(!$checkLink)
            {
                $clear_timer = \Swoole\Timer::clear($timer_id);
                SLog::info($timer_id.'_链接失效，清除定时器:'.$clear_timer, 'heartbeat');
            }else{
                $server->push($request->fd, 'ping', 9);
                SLog::info($timer_id.'_定时器结尾:'.$request->fd, 'heartbeat');
            }
            SLog::info($timer_id.'_定时器退出日志:'.$request->fd, 'heartbeat');
        });
        return true;
    }



    /**
     * 检查用户是否在当下红娘直播间里
     * @access public static
     * @param mixed $userId 用户ID
     * @return mixed
     */
    public static function checkUserExistRoom($userId)
    {
        /*
         * 1、检查当下对应红娘ID
         * 2、检查是否在当下红娘的有序集合中
         * */
        $redis = new Redis();
        $matchmakerId = $redis->get(PublicLogic::getUserBroadcast($userId));
        SLog::info('检查直播间红娘状态:'.$matchmakerId);
        if(!$matchmakerId)
        {
            return false;
        }


        $orderly_key = PublicLogic::getBroadcastList($matchmakerId);//直播间有序列表Key
        $check = $redis->zRank($orderly_key, $userId);
        SLog::info('检查直播间有序列表:'.$check);
        if($check === false)
        {
            return $check;
        }
        return true;
    }



}
