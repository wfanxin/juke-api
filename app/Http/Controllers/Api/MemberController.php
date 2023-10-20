<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

/**
 * 用户
 */
class MemberController extends Controller
{

    /**
     * 用户登录
     * @param Request $request
     */
    public function login(Request $request)
    {
        $params = $request->all();

        return $this->jsonAdminResult([],10001,'操作失败');
        return $this->jsonAdminResult();
    }
}
