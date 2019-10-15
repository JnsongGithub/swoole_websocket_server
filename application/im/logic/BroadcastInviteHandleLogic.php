<?php
namespace app\im\logic;


use app\common\Constant;
use app\common\Redis;
use app\common\SLog;

class BroadcastInviteHandleLogic
{
    /**
     * 被邀请者处理
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed  返回类型
     */
    public function run($data, $server)
    {
        $matchmakerId = $data['data']['matchmakerId'];
        $userId = $data['data']['userId'];
        $launchUserId = $data['data']['launchUserId'];
        $inviteResult = $data['data']['inviteResult'];

        //参数校验必要的操作 TODO

        if($inviteResult == 1)
        {
            /*
             *TODO 同意邀请
             * 1、校验直播间是否存在
             * 2、校验直播间是否有空位
             * 3、同意进入直播间，触发直播间信息(订阅、直播间集合、用户对应红娘记录)
             * 4、通知订阅分发，切流
             *
             * */
            $redis = new Redis();
            $zadd_resu = $redis->zRank(PublicLogic::getBroadcastList($matchmakerId), time(), $matchmakerId);
            if(!$zadd_resu)
            {

            }




        }else{
            SLog::info($userId.'_拒绝邀请');
        }

    }


}
