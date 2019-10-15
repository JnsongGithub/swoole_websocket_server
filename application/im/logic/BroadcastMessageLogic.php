<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;

class BroadcastMessageLogic
{
    /**
     * 房间发送消息
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed  返回类型
     */
    public function run($data, $server)
    {
        $userId = $data['data']['userId'];
        $matchmakerId = $data['data']['matchmakerId'];

        //$content消息脱敏处理
        //TODO

        //发布消息广播
        $channelName = PublicLogic::getChannelsName($matchmakerId);
        SLog::info($userId.'_订阅的频道:'.$channelName);

        //获取用户信息
        $user_info = ImPortalLogic::getMapUserInfo($userId);
        if(!$user_info)
        {
            BusinessException::throwException(Constant::USER_INFO_LOST);
        }

        $content = Constant::getReturn(Constant::SUCCESS);
        $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
        $content['data'] = [
            'nickName' => $user_info['nickName'],
            'icon' => $user_info['icon'],
            'message' => $data['data']['content'],
        ];

        //发送消息
        go(function() use ($channelName, $userId, $content){
            $redis = new Redis();
            $publish_resu = $redis->publish($channelName, json_encode($content));
            SLog::info($userId.'_RedisSet发布消息:'.json_encode([$publish_resu, $content]));
        });
    }


}
