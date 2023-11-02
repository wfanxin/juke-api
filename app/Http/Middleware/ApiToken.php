<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ApiToken
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
        $params = $request->all();

        $token = $params['token'];
        if (empty($token) || count(explode('|', $token)) != 3) {
            return response()->json([
                'code' => 20001,
                'message' => '令牌错误'
            ]);
        }

        list($auth, $time, $memId) = explode('|', $token);

        $redisKey = config('redisKey');
        $mTokenKey = sprintf($redisKey['m_token']['key'], $memId);
        $mineToken = Redis::get($mTokenKey);
        if (empty($mineToken) || $mineToken != $token) {
            return response()->json([
                'code' => 20001,
                'message' => '请重新登录'
            ]);
        }

        $request->memId = $memId;
        $info =  DB::table('members')->where('id', $memId)->first();
        $info = json_decode(json_encode($info), true);
        if (empty($info)) {
            return response()->json([
                'code' => 20001,
                'message' => '请重新登录'
            ]);
        }

        if ($info['status'] == 3) { // 拉黑
            return response()->json([
                'code' => 20001,
                'message' => '请重新登录'
            ]);
        }

        return $next($request);
    }
}
