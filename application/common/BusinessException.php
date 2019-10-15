<?php
namespace app\common;

use think\Exception;

class BusinessException extends Exception {
    /**
     * throw exception|BusinessException
     * @param $code
     * @param string $msg
     * @throws BusinessException
     * @throws Exception
     */
    public static function throwException($code, $msg = '')
    {
        static $errorCode = 10001;
        if (is_numeric($code) && isset(Constant::$errMsg[$code])) {
            $msg = Constant::$errMsg[$code];
        }
        elseif (is_string($code)) {
            $msg = $code;
            $code = ++ $errorCode;
        }

        if (is_object($code) && $code instanceof Exception) {
            throw $code;
        } else {
            throw new self($msg, $code);
        }
    }
}
