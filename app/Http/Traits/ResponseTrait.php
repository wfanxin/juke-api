<?php

namespace App\Http\Traits;

use App\Model\Admin\Log;
use Illuminate\Http\Request;

/**
 * 接口响应格式化
 * Class ResponseTrait
 * @package App\Http\Traits
 */
trait ResponseTrait {
    private $_admin_error = [
        10001 => '操作失败，请联系管理员', // controller错误10000开始
        10002 => '参数错误',
        10003 => '用户名或密码错误',
        10004 => '请求超时，请稍后再试',
        10005 => '账号被锁定',
        10011 => '原密码错误',

        20001 => '读取权限失败', // model错误20000开始
        20002 => '未配置一级栏目',

        30006 => '验证码错误', // 30000起不经过前端拦截器
    ];

    /**
     * 结果格式化并记录日志
     * @param Request $request
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonAdminResultWithLog(Request $request, $data = [], $code = 0, $message = '')
    {
        if ($code != 0) { // 失败
            $result = [
                'code' => $code,
                'message' => empty($message) ? $this->_admin_error[$code] : $message,
                'data' => $data
            ];

            $httpCode = 201;
        } else { // 成功
            $result = array_merge([
                'code' => 0,
                'message' => 'success',
            ], $data);

            $httpCode = 200;
        }

        $log = new Log();
        $log->add($request, [
            'code' => $httpCode,
            'message' => $result['message']
        ]);

        return response()->json($result, $httpCode);
    }

    /**
     * 结果格式化
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonAdminResult($data = [], $code = 0, $message = '')
    {
        if ($code != 0) { // 失败
            $result = [
                'code' => $code,
                'message' => empty($message) ? $this->_admin_error[$code] : $message,
                'data' => $data
            ];

            $httpCode = 201;
        } else { // 成功
            $result = array_merge([
                'code' => 0,
                'message' => empty($message) ? '操作成功' : $message,
            ], $data);

            $httpCode = 200;
        }

        return response()->json($result, $httpCode);
    }
}
