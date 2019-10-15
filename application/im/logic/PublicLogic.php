<?php


namespace app\im\logic;

use app\common\Constant;
use app\common\RpcClient;
use app\common\SLog;

class PublicLogic
{

    /**
     * 默认消息类型
     * @access public
     * @return mixed
     */
    public function run()
    {

    }

    /**
     * 直播间订阅频道Key
     * @access public
     * @param mixed $matchmakerId 对象信息
     * @return mixed
     */
    public static function getChannelsName($matchmakerId)
    {
       return Constant::BROADCAST_PREFIX.$matchmakerId;
    }

    /**
     * 直播间有序列表Key
     * @access public
     * @param mixed $matchmakerId 对象信息
     * @return mixed
     */
    public static function getBroadcastList($matchmakerId)
    {
        return Constant::BROADCAST_LIST_KEY.$matchmakerId;
    }

    /**
     * 获取用户对应的直播间有序列表
     * @access public
     * @param mixed $userId 对象信息
     * @return mixed
     */
    public static function getUserBroadcast($userId)
    {
        return Constant::USER_BROADCAST_PREFIX.$userId;
    }

    /**
     * RPC服务端请求实例
     * @access public
     * @param string $request_type 请求类型
     * @param array $param rpc入参
     * @return mixed
     */
    public static function rpcServerRequest($request_type, $param)
    {
        SLog::info('RpcType:'.$request_type.'_参数：'.json_encode($param));
        //Rpc执行礼物消耗
        $params = [
            'data' => $param,
        ];
        $rpcObj = new RpcClient();
        $rpc_resu = $rpcObj->rpcClientSend($request_type, $params);
        SLog::info('rpc_resu结果：'.json_encode($rpc_resu));
        return $rpc_resu;
    }


    /**
     * 设置fd连接id到用户ID的映射关系
     * @access public
     * @param mixed $fd 连接ID
     * @param mixed $server_port 服务端端口
     * @return mixed
     */
    public static function getFdMapping($fd, $server_port)
    {
        return Constant::MAP_FD_PREFIX.'_'.$fd.'_'.$server_port;
    }


    /**
     * 获取用户排麦申请队列key
     * @access public
     * @param mixed $matchmakerId 红娘ID
     * @return mixed
     */
    public static function getApplyAnchorListKey($matchmakerId)
    {
        return Constant::APPLY_ANCHOR_LIST_KEY.$matchmakerId;
    }

    /**
     * 上麦后进行在麦集合key
     * @access public
     * @param mixed $matchmakerId 红娘ID
     * @return mixed
     */
    public static function getExistBroadcastListKey($matchmakerId)
    {
        return Constant::EXIST_BROADCAST.$matchmakerId;
    }

    /**
     * 一对一消息未读缓存前缀
     * @access public
     * @param mixed $userId 用户ID
     * @return mixed
     */
    public static function getChatAloneKey($userId)
    {
        return Constant::CHAT_ALONE_PREFIX.$userId;
    }




}
