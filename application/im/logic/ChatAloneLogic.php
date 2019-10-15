<?php
namespace app\im\logic;


use app\common\BusinessException;
use app\common\Constant;
use app\common\Redis;
use app\common\RpcClient;
use app\common\SLog;
use think\Exception;

class ChatAloneLogic
{
    /**
     * 一对一聊天消息发送
     * @access public
     * @param mixed $data
     * @param mixed $server
     * @return mixed
     * @throws Exception
     */
    public function run($data, $server)
    {
        $userId = $data['data']['userId'];
        $receiveUserId = $data['data']['receiveUserId'];
        $contentText = $data['data']['content'];
        $contentType = $data['data']['contentType'];
        $emojiId = $data['data']['emojiId'];
        $data['data']['time'] = time();

        //查询发送者信息
        $sendUser = ImPortalLogic::getMapUserInfo($userId);
        $contentData = [
            'sendUserId' => $userId,
            'sendnickName' => $sendUser['nickName'],
            'sendIcon' => $sendUser['icon'],
            'sendTime' => time(),
            'contentType' => $contentType,
            'content' => $contentText,
            'emojiId' => $emojiId,
        ];

        //校验接收者是否在线
        $receiveUserStatus = ImPortalLogic::getMapUserInfo($receiveUserId);
        if(!$receiveUserStatus)
        {
            //不在线，临时缓存消息(有序列表)
            $redis = new Redis();
            $zadd_receive = $redis->zAdd(PublicLogic::getChatAloneKey($receiveUserId), time(), $contentData);
            $expire_resu = $redis->expire(PublicLogic::getChatAloneKey($receiveUserId), Constant::CHAT_ALONE_TIME_OUT);
            SLog::info($receiveUserId.'_一对一消息缓存:'.$zadd_receive.' 设置有效期:'.$expire_resu);

        }else{

            //直接将消息发送至接收方
            $content = Constant::getReturn(Constant::SUCCESS);
            $content['messageType'] = Constant::SOCKET_MAPPING[$data['requestTypes']];
            $content['data'] = $contentData;
            $push_receive_user = $server->push($receiveUserStatus['fd'], json_encode($content));
            SLog::info($receiveUserId.'_接收一对一消息:'.$push_receive_user);
        }

    }


}
