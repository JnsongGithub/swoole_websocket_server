<?php


return [
    // redis缓存
    'redis'   =>  [
        // 驱动方式
        'type'   => 'redis',
        // 服务器地址
        'host'       => '127.0.0.1',
        // redis 端口号
        'port'      => '6379',
        // redis配置的密码
        'password'  => 'ZY7Fx3u0cxjR52kymnxJGczP',
        // 缓存时间
        'timeout'   => 3600,
        //选择缓存库
        'select' => '2',
        // 缓存前缀
        'prefix' => '',
        //是否长连接
        'persistent' => false,

        //是否序列化
        'serialize' => false,
    ],

];
