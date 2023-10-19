<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public $error = [
        10002 => 'code_error',
        10003 => 'phone_format_error',
        10004 => 'password_format_error',
        10005 => 'vcode_error',
        10006 => '%s_seconds_can_resend',
        10008 => 'phone_exist',
        10012 => 'params_error',
        10101 => 'nick_name_error',
        10102 => 'description_error',
        10103 => 'cate_mine_error'
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception->getMessage() == 'Too Many Attempts.') {
            return response()->json([
                'code' => 10001,
                'message' => '请求过于频繁，请稍后再试',
                'data' => []
            ], 201);
        }


//        if ($exception instanceof ModelNotFoundException) {
//            return response()->json([
//                'error' => 'Resource not found.'
//            ],404);
//        }

        return parent::render($request, $exception);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function invalidJson($request, ValidationException $exception)
    {
        $errors = $exception->errors();
        $error = array_pop($errors);
        $code = $error[0];

        $codeList = explode("|", $code);
        if (! empty($codeList[0]) && ! empty($codeList[1])) {
            $code = $codeList[0];
            $message = sprintf($this->error[$code], $codeList[1]);
        } else {
            $message = $this->error[$code];
        }

        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => []
        ], 200);
    }
}
