<?php

namespace app\boss\service;


use app\common\Constant;
use app\common\SLog;

class RoomWebSocketService
{

    public function loginSocket($server,$request,$param)
    {
        SLog::info('直播间链接初始化');
        go(function() use ($server, $request, $param){
            try{
                //初始化mysql
                $swoole_mysql = initMysql();
                //查询数据
                $user_info = $swoole_mysql->query('select * from test_table;');
                SLog::info('核实用户信息:'.json_encode($user_info));
                $user_info = 1;
                if(!$user_info)
                {
                    $server->push($request->fd,"用户不存在!");
                    $close = $server->disconnect($request->fd, 1000, '用户不存在');
                    SLog::info('closeRoom'.$close);
                    exit();
                }

                //初始化Redis
                $swoole_redis = initRedis();
                //绑定用户ID和直播间
                $redis_bind = $swoole_redis->setex(Constant::MAP_FD_USER_PREFIX.$param['fd'], Constant::MAP_FD_USER_CACHE_TIME, $param['userId']);
                SLog::info("绑定直播间编号:".json_encode($redis_bind));
                if(!$redis_bind)
                {
                    $server->push($request->fd,"进入直播间失败!");
                    $close = $server->disconnect($request->fd, 1000, '进入直播间失败!');
                    SLog::info('closeRoom'.$close);
                    exit();
                }

            }catch(\Swoole\ExitException $e){
                $ret = [
                    'code' => $e->getCode(),
                    'msg' => $e->getMessage()
                ];
                SLog::info('协程结束:'.json_encode($ret));
            }
        });

        //获取连接信息
        $connections = $server->connection_info($request->fd);
        SLog::info($request->fd.'_链接成功:'.json_encode($connections));
        $server->push($request->fd,"连接成功");
    }

    public function loginMessage()
    {

    }

    public function loginColse()
    {

    }
}

