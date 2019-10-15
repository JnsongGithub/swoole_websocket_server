<?php
namespace app\im\logic;


use app\common\Constant;
use app\common\Redis;
use app\common\SLog;

class BroadcastKickLogic
{
    /**
     * 直播间下麦
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed  返回类型
     */
    public function run($data, $server)
    {
        $down_userId = $data['data']['down_userId'];
        $matchmakerId = $data['data']['matchmakerId'];

        //广播频道
        $channelName = PublicLogic::getChannelsName($matchmakerId);
        SLog::info($down_userId.'_订阅的频道:'.$channelName);

        //移出直播间在麦集合
        $redis = new Redis();
        $remove_broadcast_list = $redis->zRem(PublicLogic::getExistBroadcastListKey($matchmakerId), $down_userId);
        SLog::info($down_userId.'_主播下麦:'.$remove_broadcast_list);

        //移除直播间有序列表
        $remove_broadcast_orderly_list = $redis->zRem(PublicLogic::getBroadcastList($matchmakerId), $down_userId);
        SLog::info($down_userId.'_移除直播间有序列表:'.$remove_broadcast_orderly_list);

        $content = [
            'down_userId' => $down_userId,
            'matchmakerId' => $matchmakerId,
        ];

        //发送广播消息
        go(function() use ($channelName, $down_userId, $content){
            $redis = new Redis();
            $publish_resu = $redis->publish($channelName, json_encode($content));
            SLog::info($down_userId.'_RedisSet发布消息:'.json_encode([$publish_resu, $content]));
        });
    }


}
