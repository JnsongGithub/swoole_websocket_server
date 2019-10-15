<?php
/**
 * 离开行为处理
 *
 * 退出直播间、关掉app进程、无网络连接脱离系统等逻辑处理
 * @author  jnsong
 * @version 1.0.0
 */

namespace app\im\logic;


use app\common\Constant;
use app\common\Redis;
use app\common\SLog;

class AwayActionLogic
{
    /**
     * Socket断开连接,执行数据移除
     * @access public static
     * @param mixed $server socket服务
     * @param mixed $fd 客户端连接号
     * @param mixed $reactorId 线程ID(主动关闭时为负数)
     * @return mixed  返回类型
     */
    public static function socketClose($server, $fd, $reactorId)
    {
        /*
         * 关闭APP、掉线、断网等状况，处理用户缓存信息
         * 1、查询fd对应的用户ID
         * 2、查找用户订阅的红娘频道-删除
         * 3、查找用户直播间有序集合-删除
         * 4、删除客户端userId和fd缓存记录、
         * */
        $redis = new Redis();
        $userId = $redis->get(PublicLogic::getFdMapping($fd, $server->port));
        if($userId)
        {
            //查找用户直播间频道
            $matchmakerId = $redis->get(PublicLogic::getUserBroadcast($userId));
            SLog::info($userId.'_RedisGet查询频道红娘ID：'.$matchmakerId);

            //执行离开事件
            $leave_resu = AwayActionLogic::leaveBroadcastPublic($userId, $matchmakerId, $fd, $server->port);
            SLog::info($userId.'_离开直播间处理:'.json_encode($leave_resu));
        }
        return true;
    }


    /**
     * 离开直播间公共方法
     * @access public static
     * @param int $userId 用户ID
     * @param int $matchmakerId 红娘ID
     * @param int $fd 用户链接客户端号
     * @return mixed  返回类型
     */
    private static function leaveBroadcastPublic($userId, $matchmakerId, $fd, $port)
    {
        /*
         * 1、移出直播间订阅队列
         * 2、删除直播间有序列表
         * 3、删除用户关联红娘记录
         * */

        $redis = new Redis();
        //移除直播间订阅信息
        $unsubscribe = $redis->unsubscribe([PublicLogic::getChannelsName($matchmakerId)]);
        SLog::info($userId.'_RedisSet退订频道:'.json_encode($unsubscribe));

        //删除直播间有序集合
        $zrem_broadcast = $redis->zRem(PublicLogic::getBroadcastList($matchmakerId), $userId);
        SLog::info($userId.'_RedisSet移除直播间:'.$zrem_broadcast);

        //删除用户关联红娘Redis
        $del_user_matchmaker = $redis->del(PublicLogic::getUserBroadcast($userId));
        SLog::info($userId.'_RedisSet删除用户和红娘关联记录:'.$del_user_matchmaker);

        //删除客户端关联用户ID映射关系
        $del_fd_user = $redis->del(PublicLogic::getFdMapping($fd, $port));
        SLog::info($userId.'_RedisSet删除客户端关联用户ID映射关系:'.$del_fd_user);

        //从排麦申请集合中移出
        $ApplyBroadcast = $redis->zRem(PublicLogic::getApplyAnchorListKey($matchmakerId), $userId);
        SLog::info($userId.'_RedisSet申请排麦集合移出:'.$ApplyBroadcast);

        return true;
    }







}
