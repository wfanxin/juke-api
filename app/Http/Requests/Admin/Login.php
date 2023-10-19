<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Base;
use App\Http\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Login extends Base
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
            'user_name' => 'required',
            'password' => 'required|min:5|alpha_num',
        ];
    }

    public function messages()
    {
        return [
            'user_name.required' => '用户名或密码错误',
            'password.required' => '用户名或密码错误',
            'password.min' => '用户名或密码错误',
            'password.alpha_num' => '用户名或密码错误'
        ];
    }
}
