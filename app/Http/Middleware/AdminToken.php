<?php

namespace App\Http\Middleware;

use App\Facades\PermissionFacade;
use App\Model\Admin\Permission;
use App\Model\Admin\User;
use Closure;
use Illuminate\Support\Facades\Redis;


class AdminToken
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
        // 验证token
        if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return $next($request);
        }

        $redisKey = config('redisKey');
        $token = $request->header('X-Token');
        if (empty($token)) {
            return response()->json([
                'code' => 10001,
                'message' => '操作错误'
            ]);
        }

        list($auth, $time, $userId) = explode('|', $token);

        $xTokenKey = sprintf($redisKey['x_token']['key'], $userId);
        $rbacKey = sprintf($redisKey['rbac']['key'], $userId);
        $mineToken = Redis::get($xTokenKey);
        if (empty($mineToken) || $mineToken != $token) {
            return response()->json([
                'code' => 20000,
                'message' => '请重新登录'
            ]);
        }

        $mUser = new User();
        if (!$mUser->getControlAuth()) {
            return response()->json([
                'code' => 10001,
                'message' => '系统异常，请联系管理员'
            ]);
        }

        $request->userId = $userId;

        // 验证权限
        $isExistRbac = Redis::hgetall($rbacKey);
        if ($isExistRbac) {
            $rbac = Redis::hgetall($rbacKey);
            $role = json_decode( $rbac['role'] , true);
            $permission = json_decode( $rbac['permission'] , true);
        } else {
            $userInfo = $mUser->getCurUser($userId);
            $role = $userInfo['roles'];
            $permission = $userInfo['permissions'];
        }

        if (empty($role)) {
            return response()->json([
                'code' => 10001,
                'message' => '系统异常，请联系管理员'
            ]);
        }

        // 超级管理员
        if (in_array('admin', $role)) {
            return $next($request);
        }

        // 格式成数据库path字段格式
        $xPermission = PermissionFacade::getRequestPath();

        // 取得对应的permission id
        $mPermission = new Permission();
        $result = $mPermission->where(['path' => $xPermission])->first();
        if ($result && $result['is_white']) { // 权限白名单
            return $next($request);
        }
        $xPermissionId = $result['id'];

        if (empty($xPermissionId) || !in_array($xPermissionId, $permission)) {
            return response()->json([
                'code' => 10001,
                'message' => '对不起，您无该操作权限'
            ]);
        }

        return $next($request);
    }
}
