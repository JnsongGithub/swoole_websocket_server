<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\RpcClient;
use app\common\SLog;
use think\Exception;

class BroadcastGiftLogic
{
    /**
     * 发送礼物
     * @access public
     * @param mixed $data
     * @param mixed $server
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $userId = $data['data']['userId'];
        $matchmakerId = $data['data']['matchmakerId'];

        $type = $data['data']['type'];
        $toUserId = $data['data']['toUserId'];
        $toRoleId = $data['data']['toRoleId'];
        $giftsId = $data['data']['giftsId'];
        $roseNum = $data['data']['roseNum'];

        //礼物校验
        /*
         * TODO  礼物送出记录流水
         * 1、RPC调用API减玫瑰（送礼物的人）
         * 2、RPC调用API加余额(收礼物的人)
         * 3、RPC调用API增加收支流水记录
         * 4-1、发送消息，给房间内用户PUSH通知  |  4-2、特殊礼物，全系统PUSH通知
         * */

        //广播频道
        $channelName = PublicLogic::getChannelsName($matchmakerId);
        SLog::info($userId.'_订阅的频道:'.$channelName);

        //RPC数据拼接
        $content = [
            'userId' => $userId,
            'type' => $type,
            'toUserId' => $toUserId,
            'toRoleId' => $toRoleId,
            'roseNum' => $roseNum,
            'gift_id' => $giftsId,
        ];

        //Rpc执行礼物消耗
        $rpc_resu = PublicLogic::rpcServerRequest('consume_settle', $content);
        if($rpc_resu['code'] !== Constant::SUCCESS || !isset($rpc_resu['data'])  || empty($rpc_resu['data']))
        {
            BusinessException::throwException($rpc_resu->code);
        }

        //查询用户信息&被送礼物用户信息
        $user_info = ImPortalLogic::getMapUserInfo($userId);
        if(!$user_info)
        {
            BusinessException::throwException(Constant::USER_INFO_LOST);
        }

        //被刷礼物的用户
        $to_user_info = ImPortalLogic::getMapUserInfo($toUserId);
        if(!$to_user_info)
        {
            BusinessException::throwException(Constant::OBJECT_USER_LOSS);
        }


        $content = Constant::getReturn(Constant::SUCCESS);
        $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
        $content['data'] = [
            'SendNickName' => $user_info['nickName'],
            'SendIcon' => $user_info['icon'],
            'ToNickName' => $to_user_info['nickName'],
            'ToIcon' => $to_user_info['icon'],
            'verb' => '送出',
            'gift_id' => $giftsId,
        ];

        //发送广播消息
        go(function() use ($channelName, $userId, $content){
            $redis = new Redis();
            $publish_resu = $redis->publish($channelName, json_encode($content));
            SLog::info($userId.'_RedisSet发布消息:'.json_encode([$publish_resu, $content]));
        });

    }


}
