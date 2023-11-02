<?php

namespace App\Http\Controllers\Api;

use App\Common\Upload;
use App\Http\Traits\FormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 服务
 */
class ServiceController extends Controller
{
    use FormatTrait;

    /**
     * 上传文件
     * @param Request $request
     */
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $tmpFile = '';
        if (!empty($file)) {
            $upload = new Upload();
            $tmpFile = $upload->uploadToTmp($file, 'api/' . $request->memId . '/');
        }

        if ($tmpFile) {
            return $this->jsonAdminResult([
                'file' => config('filesystems.disks.tmp.url') . $tmpFile
            ]);
        } else {
            return $this->jsonAdminResult([],10001,'上传失败');
        }
    }

    /**
     * 发送短信
     * @param Request $request
     */
    public function sendMobileMessage(Request $request, Redis $redis) {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        $pattern = '/^1[0-9]{10}$/';
        if (!preg_match($pattern, $mobile)) {
            return $this->jsonAdminResult([],10001,'手机号格式不正确');
        }

        $code = rand(100000, 999999);

        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $smsapi = "http://api.smsbao.com/";
        $user = env('SMS_USER', ''); //短信平台帐号
        $pass = md5(env('SMS_PASS', '')); //短信平台密码
        $content = '短信验证码：' . $code;//要发送的短信内容
        $phone = $mobile;//要发送短信的手机号码
        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
        $result = file_get_contents($sendurl);

        if ($result == 0) { // 短信发送成功
            $config = config('redisKey');
            $mobileKey = sprintf($config['mem_code']['key'], $mobile);

            $redis::set($mobileKey, $code);
            $redis::expire($mobileKey, $config['mem_code']['ttl']);

            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001, $statusStr[$result]);
        }
    }
}
