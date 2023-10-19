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
        try {
            /// 验证token
            if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                return $next($request);
            }
            // token : 平台名称|时间戳|token
            $sign = $request->header('sign');
            if (empty($sign)) {
                echo json_encode([
                    'code' => 505,
                    'message' => 'sign信息有误'
                ]);
                exit;
            }
            list($appId, $time, $token) = explode('|', $sign);
            $redisKey = config('redisKey');
            $userInfoKey = sprintf($redisKey['mem_appSecret_status']['key'], $appId);
            $data = Redis::hgetall($userInfoKey);
            if (empty($data)){
              $data =  DB::table('members')->where('id',$appId)->first();
              if (empty($data)){
                  echo json_encode([
                      'code' => 505,
                      'message' => 'sign信息有误'
                  ]);
                  exit;
              }else{
                  $data = json_decode(json_encode($data),true);
                  $config = json_decode($data['config'],true);
                  if (empty($config)){
                      echo json_encode([
                          'code' => 505,
                          'message' => '暂时无法调用，请联系素材网管理员'
                      ]);
                  }else{
                      $data['appSecret'] = isset($config['appSecret'])?$config['appSecret']:'';
                  }
                  Redis::hmset($userInfoKey, [
                      'status' => $data['status'],
                      'appSecret' => $data['appSecret']
                  ]);
              }
            }
                if ($data['status'] != 0) {
                    echo json_encode([
                        'code' => 505,
                        'message' => '该账号已被锁定'
                    ]);
                    exit;
                }if (!isset($data['appSecret']) || empty($data['appSecret'])){
                    echo json_encode([
                        'code' => 505,
                        'message' => '暂时无法调用，请联系素材网管理员'
                    ]);
                    exit;
                }else{
                    $appSecret = $data['appSecret'];
                }

            if (time() - $time > 300) { //5分钟失效
                echo json_encode([
                    'code' => 505,
                    'message' => '已超时'
                ]);
                exit;
            }
            $sysToken = md5(md5(sprintf("%s%s%s%s", $appId, $time, $appSecret, $time)));
            if ($sysToken != $token) {
                echo json_encode([
                    'code' => 505,
                    'message' => 'sign信息有误'
                ]);
                exit;
            }
            return $next($request);
        } catch (\Exception $e) {
            echo json_encode([
                'code' => 505,
                'message' => 'sign信息有误',
            ]);
            exit;
        }
    }
}
