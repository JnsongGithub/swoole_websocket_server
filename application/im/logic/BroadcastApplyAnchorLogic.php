<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;
use think\Exception;

class BroadcastApplyAnchorLogic
{
    /**
     * 申请上麦
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

        //申请排麦 & 取消排麦
        switch($data['requestTypes'])
        {
            case 'ApplyBroadcast':
                //记录排麦申请(有序集合)
                $redis = new Redis();
                $apply_resu = $redis->zAdd(PublicLogic::getApplyAnchorListKey($matchmakerId), time(), $userId);
                SLog::info($matchmakerId.'_申请排麦:'.$apply_resu);
                break;

            case 'CancelBroadcast':
                //从排麦列表移出，且通知客户端推流
                $redis = new Redis();
                $zrem_list = $redis->zRem(PublicLogic::getApplyAnchorListKey($matchmakerId), $userId);
                SLog::info($matchmakerId.'_从排麦集合中移除:'.json_encode($zrem_list));
                break;
        }

        //通知红娘更新排麦结果
        $apply_zcard = $redis->zCard(PublicLogic::getApplyAnchorListKey($matchmakerId));
        SLog::info($matchmakerId.'_申请排麦的数量:'.$apply_zcard);

        //获取排麦集合的人员列表
        $apply_list = $redis->zRange(PublicLogic::getApplyAnchorListKey($matchmakerId), 0 , -1);
        SLog::info($matchmakerId.'_排队列表:'.json_encode($apply_list));
        $apply_users = [];
        foreach($apply_list as $k=>$v)
        {
            $apply_users[$v] = ImPortalLogic::getMapUserInfo($v);
        }

        $content = Constant::getReturn(Constant::SUCCESS);
        $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
        $content['data'] = [
            'applyNumber' => $apply_zcard,
            'applyList' => array_values($apply_users),
        ];

        //查询红娘的fd、推送排麦数据给红娘
        $matchmaker_info = ImPortalLogic::getMapUserInfo($matchmakerId);
        SLog::info($matchmakerId.'_查询用户信息:'.json_encode($matchmaker_info));
        if(empty($matchmaker_info))
        {
            BusinessException::throwException(Constant::LIVE_BROADCAST_FINISH);
        }
        $push_matchmaker = $server->push($matchmaker_info['fd'], json_encode($content));
        SLog::info($matchmakerId.'_推送消息数据到红娘状态栏:'.json_encode($content).' push:'.$push_matchmaker);
    }


}
