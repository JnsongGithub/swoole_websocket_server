<?php
namespace app\im\logic;

use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;

class BroadcastLowerLogic
{
    /**
     * 红娘把嘉宾下麦
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $userId = $data['data']['userId'];
        $roleId = $data['data']['roleId'];
        $matchmakerId = $data['data']['matchmakerId'];

        if(!$userId || !$matchmakerId || !$roleId)
        {
            BusinessException::throwException(Constant::NECESSARY_PARAM_LOSE);
        }

        $down_userId_info = ImPortalLogic::getMapUserInfo($userId);
        if(empty($down_userId_info))
        {
            BusinessException::throwException(Constant::OBJECT_USER_LOSS);
        }

        //移除在麦有序集合
        $redis = new Redis();
        $zrem_resu = $redis->zRem(PublicLogic::getExistBroadcastListKey($matchmakerId), $userId);
        SLog::info('移除在麦有序集合:'.$zrem_resu);


        //发布消息到直播间用户，下掉对应嘉宾视频流
        $content = Constant::getReturn(Constant::SUCCESS);
        $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
        $content['data'] = [
                'userId' => $userId,
        ];

        //广播通知客户端推流(该同意下线)
        $channelName = PublicLogic::getChannelsName($matchmakerId);
        SLog::info($matchmakerId.'_订阅的频道:'.$channelName);
        go(function() use ($channelName, $userId, $content){
            $redis = new Redis();
            $publish_resu = $redis->publish($channelName, json_encode($content));
            SLog::info($userId.'_RedisSet发布消息:'.json_encode([$publish_resu, $content]));
        });
    }


}
