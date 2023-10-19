<?php

namespace App\Http\Requests;

use App\Http\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Base extends FormRequest
{
    /**
     * 自定义错误
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        if ($validator->failed()) {
            $errors = $validator->errors();
            foreach ($errors->messages() as $val) {
                throw new HttpResponseException(response()->json(['code' => '10001', 'message' => $val[0]], 201));
                break;
            }
        }
    }
}
