<?php

namespace App\Http\Controllers\Api;


use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use function AlibabaCloud\Client\json;

/**
 * 验证码
 */
class CaptchaController extends Controller
{
    /**
     * 获取验证码
     */
    public function index(Request $request, CaptchaBuilder $captchaBuilder, Redis $redis)
    {
        $deviceId = $request->get('mobile_device_id');
        $key = md5($deviceId);

        $config = config('redisKey');
        $captcheKey = sprintf($config['mem_captcha']['key'], $key);

        $captchaBuilder->setPhrase(rand(1000, 9999));
        $captchaBuilder->build();
        $phrase = $captchaBuilder->getPhrase();
        $redis::set($captcheKey, $phrase);
        $redis::expire($captcheKey, $config['captcha']['ttl']);

        ob_clean();
        header('Content-type: image/jpeg');
        $captchaBuilder->output();
    }

    /**
     * 校验
     */
    public function check(Request $request, Redis $redis)
    {
        $vCode = strtolower(trim($request->post('vcode')));

        $key = md5($request->post('mobile_device_id'));
        $config = config('redisKey');
        $captcheKey = sprintf($config['mem_captcha']['key'], $key);
        $myVcode = redis::get($captcheKey);

        if ($vCode != $myVcode) {
            return $this->jsonAdminResultWithLog($request, $vCode, 30006);
        } else {
            return $this->jsonAdminResultWithLog($request);
        }
    }
}
