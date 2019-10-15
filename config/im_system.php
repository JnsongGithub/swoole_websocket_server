<?php

/**
 *Im系统配置信息
 *
 * 架构-钩子配置
 * @author  jnsong
 * @version 1.0
 */

return [
    'message_list' => [
        'createBroadcast' => 'BroadcastCreateLogic',  //初始化进入直播间

        'BroadcastSendMessage' => 'BroadcastMessageLogic', //直播间发讯息

        'SendGiftBroadcast' => '', //直播间发礼物

        'kick_broadcast' => '', //直播间踢人

        'apply_anchor' => '', //申请上麦

        'invite_broadcast' => '', //邀请他人直播间

        'quit_broadcast' => '', //离开直播间

        'heartbeat' => '', //心跳检测

        'close_broadcast' => '', //关闭直播间

        'chat_tidings' => '', //聊天消息(单对单聊天)
    ],

    'rpc_server' => [
        //获取用户信息
        'init_user_info' => [
            'class' => 'RpcServer',
            'method' => 'getUserInfo',
        ],

        //消费结算(送礼物，支出，收入等)
        'consume_settle' => [
            'class' => 'RpcServer',
            'method' => 'consumeSettle',
        ],
    ],
];
