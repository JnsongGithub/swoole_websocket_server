<?php

namespace app\boss\service;


use app\common\Constant;
use app\common\SLog;


class LoginWebSocketService
{
    public function loginSocket($server,$request,$param)
    {
        SLog::info('登录链接初始化');

        /*go(function() use ($server, $request, $param){
            $list = $server->stats();
            $a = \Swoole\Coroutine::stats();

            $sleeps = \Swoole\Coroutine::sleep(3);

            $start_time = microtime();

            SLog::info($start_time.'_查看协程数据0004'.json_encode($a));
            SLog::info($sleeps.'_协程0004'.json_encode($list));
        });*/


        go(function() use ($server, $request, $param){
            try{
                //初始化mysql
                $swoole_mysql = initMysql();
                //查询数据
                $user_info = $swoole_mysql->query('select * from test_table;');
                SLog::info('核实用户信息:'.json_encode($user_info));
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

                //任务投放
                $server->task($param);

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

        return Constant::getReturn(Constant::SUCCESS);
    }



    public function loginMessage($server,$frame)
    {
        $server->push($frame->fd, $frame->fd.' 说：'.$frame->data);
    }

    public function loginColse()
    {

    }
}

