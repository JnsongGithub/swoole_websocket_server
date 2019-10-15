<?php

namespace app\common;

class Constant
{
    //签名配置
    const SECRET_KEYS = [
        1 => 'secret@xitang_android.com',
        2 => 'secret@xitang_ios.com',
        3 => 'secret@xitang_h5.com',
    ];

    const SUCCESS = 0; //成功
    const FAILED = 99; //失败

    /***************初始化错误**************/
    const USER_CACHE_FAIL = 1000;
    const LIVE_BROADCAST_FINISH = 1001;



    /***************相亲交互错误**************/
    const HANDSHAKE_SUCCESS = 2000; //握手成功
    const TYPES_OF_REJECTION = 2001; //被拒绝的请求类型
    const NECESSARY_PARAM_LOSE = 2002; //必要参数缺失
    const SUBSCRIBER_FAIL = 2003;//进入直播间失败
    const USER_INFO_LOST = 2004;//用户信息丢失
    const SIGN_FAIL = 2005;     //签名有误
    const TOKEN_UNUSUAL = 2006;//TOKEN异常
    const REQUEST_TIMEOUT = 2007;//请求超时
    const OBJECT_USER_LOSS = 2009;//对象用户丢失
    const DATA_WRONG_FORMAT = 2010;//数据格式错误




    public static $errMsg = [
        self::SUCCESS   => '成功',
        self::FAILED    => '失败',

        //初始化链接提示
        self::USER_CACHE_FAIL => '用户登录失败',
        self::LIVE_BROADCAST_FINISH => '直播已结束',


        //交互提示
        self::NECESSARY_PARAM_LOSE => '必要参数缺失',
        self::TYPES_OF_REJECTION => '被拒绝的请求类型',
        self::HANDSHAKE_SUCCESS => '握手成功',
        self::SUBSCRIBER_FAIL => '进入直播间失败',
        self::USER_INFO_LOST => '用户信息丢失',
        self::SIGN_FAIL => '签名有误',
        self::TOKEN_UNUSUAL => 'TOKEN异常',
        self::REQUEST_TIMEOUT => '请求超时',
        self::OBJECT_USER_LOSS => '对象用户丢失',
        self::DATA_WRONG_FORMAT => '数据格式错误',


    ];


    /**
     * get return data
     * @param $code
     * @param string $msg
     * @return array
     */
    public static function getReturn($code, $msg = '') {
        if (!$msg && isset(self::$errMsg[$code])) {
            $msg = self::$errMsg[$code];
        }

        return ['code' => $code, 'msg' => $msg];
    }


    // ------oss相关配置-------
    const ACCESS_KEY_ID = '';
    const ACCESS_KEY_SECRET = '';
    const ENDPOINT = '';
    const BUCKET = '';

    const IM_REQUEST_VALIDITY = 600; //请求超时限制，正式上线，需要修改更短时间，如：60秒


    //用户登录哈希Key
    const MAP_USER_INFO = 'map_user_info';

    const BROADCAST_PREFIX = 'broadcast_'; //直播前缀
    const BROADCAST_LIST_KEY = 'room_orderly_key_'; //直播间有序集合列表Key
    const BROADCAST_LIST_TIMER = 1200; //直播间有序列表有效时长

    //用户ID(用户DI关联对应的信息)
    const MAP_USER_PREFIX = 'map_user_'; //登录APPRedis前缀
    const MAP_USER_CACHE_TIME = 86400;//登录APP缓存时间

    //连接号对应用户ID映射key
    const MAP_FD_PREFIX = 'map_fd_'; //登录APPRedis前缀
    const MAP_FD_CACHE_TIME = 86400;//登录APP缓存时间


    const APPLY_ANCHOR_LIST_KEY = 'apply_anchor_'; //申请上麦前缀
    const USER_BROADCAST_PREFIX = 'user_broadcast_'; //用户对应直播间红娘的前缀Key

    const EXIST_BROADCAST = 'exist_broadcast_'; //在麦上直播的列表
    const BROADCAST_UP_TIMEOUT = 1200;//在麦缓存时长

    const BROADCAST_UP_MXA_NUM = 3; //直播间最大上麦人数

    const TOKEN_PREFIX = 'websocket_token_';//登录成功分配token
    const TOKEN_TIME_OUT = 86400;//登录成功TOKEN有效期


    const CHAT_ALONE_PREFIX = 'chat_alone_';//一对一聊天消息缓存key
    const CHAT_ALONE_TIME_OUT = 604800;//一对一聊天消息有效期(缓存一周,如消息读取，会立刻删除)



    //直播间默认警示语
    const ROOM_DEFULT_CONTENT = '平台公示:我们提倡积极阳光交友,严谨涉黄、涉恐、涉政、低俗、辱骂等行为。发现违规行为将被封禁。保护网络绿色环境,从你我做起!';
    const INVITE_WATCH_LANGUAGE = '邀请进入直播间';
    const INVITE_UPPER_WHEAT= '邀请你连麦';




    /*
     * RPC客户端配置项
     * */
    const RPC_CLIENT_USER = 'dating';
    const RPC_CLIENT_PASS = 'dating@123';
    const RPC_SERVER_INFO = [
        'host' => 'devtaapi.budingvip.com',
        'port' => 16666
    ];
    const RPC_NAMESPACE = '\\app\\rpc_server\\controller\\'; //RPC服务端控制器对应的命名空间



    /*
     * socket请求&回调映射关系
     * */
    const SOCKET_MAPPING = [
        'default' => '', //默认空结果
        'init' => 'SuccessfulLink', //链接socket
        'createBroadcast' => 'IntoBroadcastResult', //进入直播间
        'BroadcastSendMessage' => 'BroadcastMessageResults', //直播间发送消息结果
        'SendGiftBroadcast' => 'GiftsGiveResult', //礼物送出结果
        'ApplyBroadcast' => 'ApplyBroadcastResult', //申请上麦通知
        'AgreeApplyBroadcast' => 'AgreeApplySuccess', //红娘处理上麦申请
        'ViewerBroadcastConfirm' => 'ViewerConfirmResult', //观众处理红娘的同意通知
        'CancelBroadcast' => 'CancelBroadcastResult', //排麦中的嘉宾主动取消排麦
        'LowerBroadcast' => 'LowerBroadcastResult', //红娘把嘉宾放下麦
        'ChatAloneSend' => 'ChatAloneResult', //单独聊天(一对一聊天留言等)
        'InviteGuests' => 'InviteGuestsNotice', //邀请上麦|围观
        'InviteHandle' => 'InviteHandleResponse', //邀请上麦|围观
    ];




}
