<?php
namespace app\im\logic;

use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;

class BroadcastApplyConfirmLogic
{
    /**
     * 红娘同意上麦后，嘉宾的处理
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $matchmakerId = $data['data']['matchmakerId'];
        $userId = $data['data']['userId'];
        $isAgree = $data['data']['isAgree'];
        $roleId = $data['data']['roleId']; //男嘉宾 | 女嘉宾

        //嘉宾同意
        if($isAgree == 1)
        {
            //检查房间是否依然存在
            $redis = new Redis();
            $check_matchmaker = $redis->zScore(PublicLogic::getBroadcastList($matchmakerId), $matchmakerId);
            SLog::info($userId.'_检查房间是否依然存在:'.$check_matchmaker);
            if(!$check_matchmaker)
            {
                BusinessException::throwException(Constant::LIVE_BROADCAST_FINISH);
            }

            //检查房间是否有空位
            $check_broadcast = $redis->zCard(PublicLogic::getExistBroadcastListKey($matchmakerId));
            SLog::info($userId.'_检查房间空位置:'.$check_broadcast);
            if($check_broadcast > Constant::BROADCAST_UP_MXA_NUM)
            {
                BusinessException::throwException(Constant::SUBSCRIBER_FAIL);
            }

            //将用户加入上麦队列
            $zadd_broadcast = $redis->zAdd(PublicLogic::getExistBroadcastListKey($matchmakerId), time(), $userId);
            SLog::info($userId.'_用户加入上麦集合:'.$zadd_broadcast);

            //广播频道
            $channelName = PublicLogic::getChannelsName($matchmakerId);
            SLog::info($matchmakerId.'_订阅的频道:'.$channelName);

            //提示男嘉宾，可以上麦
            $content = Constant::getReturn(Constant::SUCCESS);
            $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
            $content['data'] = [
                'matchmakerId' => $matchmakerId,
                'userId' => $userId,
                'isAgree' => $isAgree
            ];


            //广播通知客户端推流(该同意上线)
            go(function() use ($channelName, $userId, $content){
                $redis = new Redis();
                $publish_resu = $redis->publish($channelName, json_encode($content));
                SLog::info($userId.'_RedisSet发布消息:'.json_encode([$publish_resu, $content]));
            });
        }else{
            SLog::info($userId.'_主动拒绝上麦');
        }





    }


}
