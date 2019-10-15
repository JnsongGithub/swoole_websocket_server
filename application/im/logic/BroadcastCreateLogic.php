<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;

class BroadcastCreateLogic extends BaseLogic
{

    /**
     * 初始化进入房间
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $userId = $data['data']['userId'];
        $matchmakerId = $data['data']['matchmakerId'];
        $content = '';

        $channelName = PublicLogic::getChannelsName($matchmakerId); //直播间订阅频道Key
        $orderly_key = PublicLogic::getBroadcastList($matchmakerId);//直播间有序列表Key
        SLog::info($userId.'_初始化直播间Keys:'.json_encode([$channelName, $orderly_key]));

        //2、检查红娘连接直播间是否正常存在
        $this->checkMatchmakerBroadcast($matchmakerId);

        //3、检查用户是否已经在订阅频道对应的有序集合内
        $redis = new Redis();
        $check = $redis->zRank($orderly_key, $userId);
        SLog::info($userId.'_RedisGet检查初始化状态:'.$check);
        if($check === false)
        {
            //3-1、设置用户对应的直播间缓存(退出直播间需要用户ID删除有序集合等信息)
            $user_broadcast = $redis->setex(PublicLogic::getUserBroadcast($userId), Constant::BROADCAST_LIST_TIMER, $matchmakerId);
            SLog::info($userId.'_RedisSet设置用户对应直播间号:'.$user_broadcast);
            if(empty($user_broadcast))
            {
                BusinessException::throwException(Constant::SUBSCRIBER_FAIL);
            }

            //获取用户信息
            $user_info = ImPortalLogic::getMapUserInfo($userId);
            if(!$user_info)
            {
                BusinessException::throwException(Constant::USER_INFO_LOST);
            }

            //3-2、Task订阅
            $task_data = [
                'taskType' => 'createBroadcast',
                'userId' => $userId,
                'channelName' => $channelName,
                'fd' => $data['fd'],
            ];
            $task_id  = $server->task($task_data);
            SLog::info($userId.'_TaskSet投放Redis订阅任务:'.$task_id);

            //3-3、生成json消息字符串
            $content = Constant::getReturn(Constant::SUCCESS);
            $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
            $content['data'] = [
                'nickName' => $user_info['nickName'],
                'icon' => $user_info['icon'],
                'defaultPrompt' => Constant::ROOM_DEFULT_CONTENT,
            ];
        }

        //4、广播消息，设置集合
        go(function() use ($channelName, $userId, $content, $orderly_key, $matchmakerId){
            SLog::info('任务投放入参:'.json_encode([$channelName, $userId, $content, $orderly_key]));

            //4-1、延迟发送消息0.2秒
            $sleeps = \Swoole\Coroutine::sleep(0.1);
            SLog::info($userId.'_延迟发送消息:'.$sleeps);

            //4-2、发布信息给该频道
            $redis = new Redis();
            $publish_resu = $redis->publish($channelName, json_encode($content));
            SLog::info($userId.'_RedisSet发布消息:'.$publish_resu);

            //4-3、设置直播间有序集合
            $zadd_resu = $redis->zAdd($orderly_key, time(), $userId);
            $expire_resu = $redis->expire($orderly_key, Constant::BROADCAST_LIST_TIMER);
            SLog::info($userId.'_RedisSet关注频道:'.$zadd_resu.' Timer:'.$expire_resu);

            //4-4、检查是否红娘，红娘设置在麦集合
            if($userId === $matchmakerId)
            {
                //红娘处理操作 todo
                $zadd_resu = $redis->zAdd(PublicLogic::getExistBroadcastListKey($matchmakerId), time(), $userId);
                $expire_resu = $redis->expire(PublicLogic::getExistBroadcastListKey($matchmakerId), Constant::BROADCAST_UP_TIMEOUT);
                SLog::info($userId.'_RedisSet设置红娘上麦操作:'.$zadd_resu.' Timer:'.$expire_resu);
            }
        });

        return Constant::getReturn(Constant::SUCCESS);
    }



}
