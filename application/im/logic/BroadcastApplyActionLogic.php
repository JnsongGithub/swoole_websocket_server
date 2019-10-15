<?php
namespace app\im\logic;

use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;

class BroadcastApplyActionLogic
{
    /**
     * 红娘处理排麦
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $matchmakerId = $data['data']['matchmakerId'];
        $userIds = $data['data']['userIds'];
        SLog::info('上麦列表:'.json_encode($userIds));

        if(!$userIds || !is_array($userIds))
        {
            BusinessException::throwException(Constant::DATA_WRONG_FORMAT);
        }

        $forResult = [];

        //遍历上麦  todo 可以开启协程处理以下任务
        foreach($userIds as $k=>$userId)
        {
            //从排麦列表移出，且通知客户端推流
            $redis = new Redis();
            $zrem_list = $redis->zRem(PublicLogic::getApplyAnchorListKey($matchmakerId), $userId);
            SLog::info($userId.'_从排麦集合中移除:'.json_encode($zrem_list));
            if(!$zrem_list)
            {
                continue;
                //BusinessException::throwException(Constant::USER_INFO_LOST);
            }

            //提示男嘉宾，可以上麦
            $content = Constant::getReturn(Constant::SUCCESS);
            $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
            $content['data'] = [
                'matchmakerId' => $matchmakerId,
                'userId' => $userId,
            ];

            $userId_info = ImPortalLogic::getMapUserInfo($userId);
            if(empty($userId_info))
            {
                continue;
                //BusinessException::throwException(Constant::LIVE_BROADCAST_FINISH);
            }

            $pass_apply = $server->push($userId_info['fd'], json_encode($content));
            SLog::info($userId.'_同意上麦:'.$pass_apply);
            $forResult[] = $pass_apply;
        }
        return $forResult;
    }


}
