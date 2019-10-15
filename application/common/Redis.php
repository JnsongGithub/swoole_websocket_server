<?php

/*
 * 使用示例
 * use app\common\Redis;
 * $obj = new Redis();
   $resu = $obj->lpush('test','sdds');
 *
 * */

namespace app\common;

use think\facade\Config;

class Redis extends \think\cache\driver\Redis
{
    protected $options = [];

    public function __construct($options = [])
    {
        $this->options = Config::get('nosql_database.redis');

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        parent::__construct($this->options);
    }
}
