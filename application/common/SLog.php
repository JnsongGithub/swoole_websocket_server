<?php
/**
 * Created by PhpStorm.
 * User: biyajuan
 * Date: 2018/5/24
 * Time: 下午6:42
 */
namespace app\common;

use \SeasLog;

class SLog
{

    /**
     * All level
     */
    const ALL = -2147483647;

    /**
     * Detailed debug information
     */
    const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;

    /**
     * Uncommon events
     */
    const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;

    /**
     * Runtime errors
     */
    const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;

    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;

    /**
     * request Level limit
     */
    static $RequestLevel =  self::ALL;

    /**
     * Monolog API version
     *
     * This is only bumped when API breaks are done and should
     * follow the major version of the library
     *
     * @var int
     */
    const API = 2;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * This is a static variable and not a constant to serve as an extension point for custom levels
     *
     * @var string[] $levels Logging levels with the levels as key
     */

    protected static $levels = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    public static $def_msg = '';

    /**
     * set request level for seaslog
     * @param int $level
     */
    public function setRequestLevel( $level = self::ALL ) {
        self::$RequestLevel = $level;
    }

    /**
     * @param string $message
     * @param array $context
     */
    static public function emergency($message, array $context = array(),$module = '')
    {

        SeasLog::emergency($message, $context, $module);
    }

    /**
     * @param string $message
     * @param array $context
     */
    static public function alert($message, array $context = array(),$module = '')
    {

        SeasLog::alert($message, $context, $module);
    }

    /**
     * @param string $message
     * @param array $context
     */
    static public function critical($message, array $context = array(),$module = '')
    {

        SeasLog::critical($message, $context, $module);
    }

    /**
     * @param string $message
     * @param string $module
     * @param array $context
     */
    static public function error($message, $module = '', array $context = array())
    {
        self::setAppModule($module);
        SeasLog::error($message, $context, $module);
    }

    /**
     * @param string $message
     * @param string $module
     * @param array $context
     */
    static public function warning($message, $module = '', array $context = array())
    {
        self::setAppModule($module);
        SeasLog::warning($message, $context, $module);
    }

    /**
     * @param string $message
     * @param array $context
     */
    static public function notice($message, array $context = array(),$module = '')
    {

        SeasLog::notice($message, $context, $module);
    }

    /**
     * @param string $message
     * @param string $module
     * @param array $context
     */
    static public function info($message, $module = 'default', array $context = array())
    {
        self::setAppModule($module);
        SeasLog::info($message, $context, $module);
    }

    /**
     * @param string $message
     * @param array $context
     */
    static public function debug($message, array $context = array(),$module = '')
    {

        SeasLog::debug($message, $context, $module);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    static public function log($level, $message, array $context = array(),$module = '')
    {


        if ( ( int ) $level < self::$RequestLevel ) return ;

        switch($level){
            case self::EMERGENCY:
                SeasLog::emergency($message, $context);
                break;
            case self::ALERT:
                SeasLog::alert($message, $context);
                break;
            case self::CRITICAL:
                SeasLog::critical($message, $context);
                break;
            case self::ERROR:
                SeasLog::error($message, $context);
                break;
            case self::WARNING:
                SeasLog::warning($message, $context);
                break;
            case self::NOTICE:
                SeasLog::notice($message, $context);
                break;
            case self::INFO:
                SeasLog::info($message, $context);
                break;
            case self::DEBUG:
                SeasLog::debug($message, $context);
                break;
            default:
                break;
        }
    }

    /**
     * @param string $basePath
     * @return bool
     */
    static public function setBasePath($basePath)
    {
        return SeasLog::setBasePath($basePath);
    }

    /**
     * @return string
     */
    static public function getBasePath()
    {
        return SeasLog::getBasePath();
    }

    /**
     * 设置本次请求标识
     * @param string
     * @return bool
     */
    static public function setRequestID($request_id)
    {
        return SeasLog::setRequestID($request_id);
    }

    /**
     * 获取本次请求标识
     * @return string
     */
    static public function getRequestID()
    {
        return SeasLog::getRequestID();
    }

    /**
     * 设置模块目录
     * @param $module
     *
     * @return bool
     */
    static public function setLogger($module)
    {
        return SeasLog::setLogger($module);
    }

    /**
     * 获取最后一次设置的模块目录
     * @return string
     */
    static public function getLastLogger()
    {
        return SeasLog::getLastLogger();
    }

    /**
     * 设置DatetimeFormat配置
     * @param $format
     *
     * @return bool
     */
    static public function setDatetimeFormat($format)
    {
        return SeasLog::setDatetimeFormat($format);
    }

    /**
     * 返回当前DatetimeFormat配置格式
     * @return string
     */
    static public function getDatetimeFormat()
    {
        return SeasLog::getDatetimeFormat();
    }

    /**
     * 统计所有类型（或单个类型）行数
     * @param string $level
     * @param string $log_path
     * @param null $key_word
     *
     * @return array
     */
    static public function analyzerCount($level = 'all', $log_path = '*', $key_word = null)
    {
        return SeasLog::analyzerCount($level, $log_path, $key_word);
    }

    /**
     * 以数组形式，快速取出某类型log的各行详情
     *
     * @param        $level
     * @param string $log_path
     * @param null $key_word
     * @param int $start
     * @param int $limit
     * @param        $order 默认为正序 SEASLOG_DETAIL_ORDER_ASC，可选倒序 SEASLOG_DETAIL_ORDER_DESC
     *
     * @return array
     */
    static public function analyzerDetail(
        $level = SEASLOG_INFO,
        $log_path = '*',
        $key_word = null,
        $start = 1,
        $limit = 20,
        $order = SEASLOG_DETAIL_ORDER_ASC
    ) {
        return SeasLog::analyzerDetail(
            $level,
            $log_path,
            $key_word,
            $start,
            $limit,
            $order
        );
    }

    /**
     * 获得当前日志buffer中的内容
     *
     * @return array
     */
    static public function getBuffer()
    {
        return SeasLog::getBuffer();
    }

    /**
     * 将buffer中的日志立刻刷到硬盘
     *
     * @return bool
     */
    static public function flushBuffer()
    {
        return SeasLog::flushBuffer();
    }

    /**
     * Create a custom SeasLog instance.
     *
     * @param  array  $config
     * @return \\SeasLog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger();
        if (!empty($config['path'])) {
            $logger->setBasePath($config['path']);
        }

        return $logger;
    }

    static public function setAppModule($module = 'default', $module_path = 'boss_api', $pattern = 1)
    {
        $path = '/productlog/'.$module_path;
        self::setBasePath($path);

        self::setLogger($module);
    }
}

