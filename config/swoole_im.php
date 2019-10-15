<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Env;

// +----------------------------------------------------------------------
// | Swoole设置 php think swoole:im 命令行下有效
// +----------------------------------------------------------------------
return [
    // 扩展自身配置
    'host'         => 'devtabossapi.budingvip.com', // 监听地址
//    'host'         => '0.0.0.0', // 监听地址
    'port'         => 9508, // 监听端口
    'type'         => 'socket', // 服务类型 支持 socket http server
    'mode'         => SWOOLE_PROCESS, // 运行模式 默认为SWOOLE_PROCESS
//    'sock_type'    => SWOOLE_SOCK_TCP | SWOOLE_SSL, // sock type 默认为SWOOLE_SOCK_TCP
    'sock_type'    => SWOOLE_SOCK_TCP, // sock type 默认为SWOOLE_SOCK_TCP
    'swoole_class' => 'app\im\controller\ImPortal', // 自定义服务类名称

    // 可以支持swoole的所有配置参数
    //'pid_file'     => '/productlog/ImServer/im_socket.pid',
    //'log_file'     => '/productlog/ImServer/im_server.log',

    //配置列表项
    'option' => [
        'worker_num'=> 8, //进程数量
        'max_coroutine'=> 3000,//每个进程支持最大协成数
        'daemonize'	=> true,  //是否后台执行
        'backlog'	=> 128,  //Listen队列长度，如backlog => 128，此参数将决定最多同时有多少个等待accept的连接。
        'app_path'	=> '/home/devwww/boss_api/application/',

        //wss配置信息
//        'ssl' => true,
//        'ssl_cert_file' => '/home/ssl/budingvip.com.pem',
//        'ssl_key_file' => '/home/ssl/budingvip.com.key',

        //PID文件
        'pid_file' => '/productlog/ImServer/im_socket.pid',
        'log_file'     => '/productlog/ImServer/im_server.log',

        //心跳检测(默认1分钟)
        'heartbeat_check_interval' => 5, //每5秒侦测一次心跳
        'heartbeat_idle_time' => 600, //超出15秒切断链接

        'open_tcp_keepalive' => 1, //TCP-Keepalive死连接检测 开启
        'tcp_keepidle' => 5, //连接在n秒内没有数据请求，将开始对此连接进行探测。
        'tcp_keepinterval' => 2, //探测的间隔时间，单位秒
        'tcp_keepcount' => 2,//探测的次数，超过次数后将close此连接。

        /*
         * task底层使用Unix Socket管道通信，是全内存的，没有IO消耗。单进程读写性能可达100万/s
         * 最大值不得超过SWOOLE_CPU_NUM * 1000
         * Task进程内不能使用swoole_mysql、swoole_redis、swoole_event等异步IO函数
         * 功用：用于将慢速的任务异步地去执行
         *
         * */
        'task_worker_num' => 10, //最大并发数/10的设置比例
        'task_enable_coroutine' => true, //task_enable_coroutine，开启后自动在onTask回调中创建协程，调用特性见：https://wiki.swoole.com/wiki/page/54.html

    ],
];
