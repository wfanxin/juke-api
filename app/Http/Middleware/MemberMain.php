<?php

namespace App\Http\Middleware;

use Closure;

class MemberMain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $token = $request->header('M-Token');
            if (empty($token)) {
                return response()->json([
                    'code' => 10001,
                    'message' => '操作错误'
                ]);
            }

            list($auth, $time, $userId, $subUid) = explode("|", $token);
            if ($subUid > 0) {
                return response()->json([
                    'code' => 20000,
                    'message' => '操作错误'
                ]);
            }

            ///
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 20000,
                'message' => '服务异常，请联系客服'
            ]);
        }
    }
}