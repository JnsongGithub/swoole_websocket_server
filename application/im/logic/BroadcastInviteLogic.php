<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\SLog;

class BroadcastInviteLogic
{
    /**
     * 邀请上麦|围观
     * @access public
     * @param mixed $data 对象信息
     * @param mixed $server 对象信息
     * @return mixed  返回类型
     */
    public function run($data, $server)
    {
        $matchmakerId = $data['data']['matchmakerId'];
        $launchUserId = $data['data']['launchUserId'];
        $inviteUserId = $data['data']['inviteUserId'];
        $inviteType = $data['data']['inviteType'];
        $roleId = $data['data']['roleId'];

        //被邀请人信息
        $inviteUserInfo = ImPortalLogic::getMapUserInfo($inviteUserId, Constant::OBJECT_USER_LOSS);

        //发起人的信息
        $launchUserInfo = ImPortalLogic::getMapUserInfo($launchUserId, Constant::OBJECT_USER_LOSS);

        $message = Constant::INVITE_WATCH_LANGUAGE;
        if($inviteType == 2)
        {
            $message = Constant::INVITE_UPPER_WHEAT;
        }

        $content = Constant::getReturn(Constant::SUCCESS);
        $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
        $content['data'] = [
            'launchUserId' => $launchUserId,
            'launchNickName' => $launchUserInfo['nickName'],
            'launchIcon' => $launchUserInfo['icon'],
            'matchmakerId' => $matchmakerId,
            'roleId' => $roleId,
            'inviteType' => $inviteType,
            'message' => $message,
        ];

        $invite_resu = $server->push($inviteUserInfo['fd'], json_encode($content));
        SLog::info($inviteUserId.'_邀请信息：'.$invite_resu);

    }


}
