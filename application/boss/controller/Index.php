<?php
namespace app\boss\controller;

use app\common\Redis;
use think\Controller;
use think\facade\Cache;

class Index extends Controller
{
    public function index()
    {
        echo '<pre>';

        //设置php脚本执行时间
        set_time_limit(0);
        //设置socket连接超时时间
        ini_set('default_socket_timeout', -1);



        // $redis = new Redis();
        $redis = Cache::store('redis');


        // $resu = $redis->set('name','lao mao tiao');

        // var_dump($resu);
        // exit();


        $redis->pconnect('192.168.100.102', 6379);

        // $redis->option(['password'=>'ZY7Fx3u0cxjR52kymnxJGczP']);


        $channel_id_1= 'channel_room_111'; //频道名称
        $channel_id_2 = 'channel_room_222'; //频道名称
        $userId = 123;
        $man_id = 456;
        $woman_id = 789;


        //消费者
        // $resu = $redis->subscribe([$channel_id_1,$channel_id_2],function($redis, $chan, $msg){
        // SLog::info('订阅回调信息:'.json_encode([$redis, $chan, $msg]));
        // });





        //生产者
        for ($i=0;$i<5;$i++){
            $data = array('key' => 'key'.$i, 'data' => 'testdata');
            $ret = $redis->publish($channel_id_1, json_encode($data));
            SLog::info('生产者('.$i.'):'.$ret);
        }
    }


	public function test()
    {

    }
}
