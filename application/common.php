<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

use app\common\Constant;
use app\common\SLog;

//Try Catch错误返回
function returnCatch($e, $requestTypes)
{
    return [
        'code' => $e->getCode(),
        'msg' => $e->getMessage(),
        'messageType' => Constant::SOCKET_MAPPING[$requestTypes]
    ];
}

/**
 * 获取直播间订阅频道Key
 * @param array $matchmakerId  红娘ID
 * @return string
 */
function getBroadcastChannelKeys($matchmakerId)
{
    return Constant::BROADCAST_PREFIX.$matchmakerId;
}

/**
 * 获取直播间有序列表Key
 * @param array $matchmakerId  红娘ID
 * @return string
 */
function getMatchMakerKeys($matchmakerId)
{
    return Constant::BROADCAST_LIST_KEY.$matchmakerId;
}

/**
 * 生成用户TokenKeys
 * @param array $userId
 * @return string
 */
function getUserTokenKey($userId)
{
    return 'im_'.md5($userId).'_token';
}


/**
 * 生成请求参数的数字签名
 * @param array $param
 * @return string
 */
function genSignApp(array $param, $secret_key) {
    //0. 删除原数据中自带的sign值,防止干扰计算结果
    unset($param['sign']);

    //1. 按key由a到z排序
    ksort($param);

    foreach ($param as $key => $value) {
        if (is_array($value)) {
            $param[$key] = genSignApp($value, $secret_key);
        }
    }

    //2. 生成以&符链接的key=value形式的字符串
    $paramString = urldecode(http_build_query($param));

    //3. 拼接我们的服务秘钥，并md5加密
    return md5($paramString . $secret_key);
}




/**
 * get请求方法
 * @param $url
 * @param array $headers
 * @return mixed
 */
function http_get($url, $headers = [])
{
    SLog::info('httpGet-url:' . $url);
    //初始化
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    // 执行后不直接打印出来
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // 不从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    SLog::info('curl_getinfo------' . json_encode($info));
    //释放curl句柄
    curl_close($ch);
    return $output;
}


/**
 * post请求
 * @param $url
 * @param $json_data
 * @param array $header
 * @return mixed
 */
function http_post($url, $json_data, $header = array())
{
    SLog::info('httpPost-url:' . $url);
    //SLog::info("[请求数据]".$json_data."[header]".json_encode($header));
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 200);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 200);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);//二进制流

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    $response = curl_exec($ch);

    SLog::info('curlpost返回---------' . $response);
    if (curl_errno($ch)) {
        print curl_error($ch);
    }
    curl_close($ch);
    return $response;
}


/*
 * 生成token
 * */
function tokenCreate($userId)
{
    $token = str_replace('=', '', strtr(base64_encode(hash_hmac("sha1", $userId . mt_rand() . time(), mt_rand(), true)), '+/', '-_'));
    return $token;
}



