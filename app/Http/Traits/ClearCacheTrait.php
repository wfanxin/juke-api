<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Redis;

trait ClearCacheTrait
{
    /**
     * 清除后台登录信息
     * @param int $userId
     * @return bool
     */
    public function clearXtoken($userId = 0) {
        if ( $userId <= 0 ) {
            return false;
        }

        $redisKey = config('redisKey');
        $rbacKey = sprintf($redisKey['rbac']['key'], $userId);
        $xTokenKey = sprintf($redisKey['x_token']['key'], $userId);
        $userInfoKey = sprintf($redisKey['user_info']['key'], $userId);

        $result1 = Redis::del($rbacKey);
        $result2 = Redis::del($xTokenKey);
        $result3 = Redis::del($userInfoKey);

        return $result1 && $result2 && $result3;
    }
}
