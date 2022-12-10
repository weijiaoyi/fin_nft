<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/3 0003
 * Time: 17:09
 */

namespace app\lib\exception;

use Exception;
use InvalidArgumentException;
use think\Config;
use think\exception\Handle;
use think\Request;

class HttpException extends Handle
{
    private $code;
    private $msg;

    /**
     * 自定义错误异常
     * @param Exception $e
     * @return \think\Response|\think\response\Json
     * @throws Exception
     */
    public function render(Exception $e)
    {
        $debug = Config::get('app_debug');
        if ($e instanceof LoginException) {
            $this->msg = $e->getMessage();
            $msg = $this->msg;
            $result = [
                'msg' => $msg,
                'code' => 401,
            ];
            return json($result, 200);
        } else if ($e instanceof BaseException) {
            $this->code = $e->getCode();
            $this->msg = $e->getMessage();
        } else if ($e instanceof InvalidArgumentException) {
            $this->code = 200;
            $this->msg = $e->getMessage();
        } else {
            $this->code = 200;
            if (!$debug) {
                //上线环境
                $this->msg = '系统错误';
                $this->errorLog($e);
            } else {
                //测试环境  正常抛出错误
                return parent::render($e);
            }
        }
        $msg = $this->msg;
        $result = [
            'msg' => $msg,
            'code' => 0,
        ];
        return json($result, 200);
    }

    /**
     * 日志记录错误信息
     * @param Exception $e
     * @throws Exception
     */
    private function errorLog(Exception $e)
    {
        $log_filename = '../runtime/service/' . date('Y-m-d') . ".log";
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new \DateTime (date('Y-m-d H:i:s.' . $micro, $t));
        file_put_contents($log_filename, '   ' . $d->format('Y-m-d H:i:s ') . 'url:' . Request::instance()->url() . '     错误信息： ' . $e->getMessage() . "\r\n------------------------ \r\n", FILE_APPEND);
    }

}