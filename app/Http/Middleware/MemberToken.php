<?php

namespace App\Http\Middleware;

use App\Model\Member\Member;
use Closure;
use Illuminate\Support\Facades\Redis;

class MemberToken
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
            // 验证token
            if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                return $next($request);
            }

            $redisKey = config('redisKey');
            $token = $request->header('M-Token');
            if (empty($token)) {
                return response()->json([
                    'code' => 20000,
                    'message' => '请带入token信息'
                ]);
            }

            list($auth, $time, $userId) = explode('|', $token);

            $mTokenKey = sprintf($redisKey['m_token']['key'], $userId, $auth);
            $mineToken = Redis::get($mTokenKey);
            if (empty($mineToken) || $mineToken != $token) {
                return response()->json([
                    'code' => 20000,
                    'message' => '请重新登录'
                ]);
            }

            $request->userId = $userId;
            $mUser = new Member();
            $userInfo = $mUser->getCurUser($userId);
            if ($userInfo['user']['status'] == 3) {
                return response()->json([
                    'code' => 20505,
                    'message' => '账号已被禁用，请联系客服'
                ]);
            }
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 20000,
                'message' => $e->getMessage()
            ]);
        }
    }
}
