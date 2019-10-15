<?php
/*
 * 心跳回应器
 * @auth jnsong
 * @date 2019-09-27
 * */

namespace app\im\logic;

use app\common\SLog;

class HeartbeatReply
{
    /**
     * 心跳检测回复
     * @access public
     * @param mixed $data 对象信息
     * @return mixed
     */
    public function run($data)
    {
        SLog::info($data['data']['userId'].'心跳回应');
        return 'pong';
    }


}
