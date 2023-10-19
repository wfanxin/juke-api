<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Base;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redis;

class Register extends Base
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'user_name' => 'required|min:8|max:15|alpha_num',
            'password' => 'required|min:9|alpha_num',
            're_password' => 'required|same:password',
            'email' => 'required|email',
            'verify_code' => 'required|min:6|max:6'
        ];
    }

    public function messages()
    {
        return [
            'user_name.required' => '用户名不能为空',
            'user_name.min' => '用户名格式错误',
            'user_name.max' => '用户名格式错误',
            'user_name.alpha_num' => '用户名格式错误',
            'password.required' => '请输入正确的密码',
            'password.min' => '请输入正确的密码',
            'password.alpha_num' => '请输入正确的密码',
            're_password.same' => '俩次密码不一致',
            'email.required' => '邮箱格式错误',
            'email.email' => '邮箱格式错误',
            'verify_code.required' => '验证码错误或过期',
            'verify_code.min' => '验证码错误或过期',
            'verify_code.max' => '验证码错误或过期'
        ];
    }

    protected function validationData()
    {
        $sessionId = Session::getId();
        $verifyCodeMail = config('redisKey.web_verify_code_mail');
        $verifyCodeMailKey = sprintf($verifyCodeMail['key'], $sessionId);
        $verifyCode = Redis::get($verifyCodeMailKey);

        if ($this->input('verify_code') !== $verifyCode) {
            throw new HttpResponseException(response()->json(['code' => '10001', 'message' => '验证码错误或过期'], 201));
        }

        return $this->all();
    }
}
