<?php

namespace App\Http\Controllers\Admin\System;

use App\Model\Admin\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Controller;
use Illuminate\Support\Facades\Redis;

/**
 * 后台权限管理
 * @name 权限管理
 * Class PermissionController
 * @package App\Http\Controllers\System
 */
class PermissionController extends Controller
{
    /**
     * 权限列表
     * @name 权限列表
     * @Get("/lv/permissions")
     * @Versions("v1")
     *
    @Request("is_show={is_show}&page={page}&id_path={id_path}", contentType="application/x-www-form-urlencoded", attributes={
    @Attribute("is_show", type="int", required=true, description="是否是栏目 -1：全部 0：否 1：是", sample=-1),
    @Attribute("page", type="int", required=true, description="页码", sample=1),
    @Attribute("id_path", type="string", required=false, description="权限名称", sample="2|67|68"),
    })
    @Response(200, body={
        "code":0,
        "message":"success",
        "total":9,
        "list":{
            {
                "created_at": "2021-06-24 11:19:31",
                "id": 68,
                "idPath": "0|2|67|68",
                "id_path": "0|2|67",
                "is_show": 0,
                "is_white": 0,
                "name": "用户列表",
                "p_id": 67,
                "path": "@Get:api_delivery_statListPage",
                "updated_at": "2021-06-24 11:19:31",
            }
        }
    },
    attributes={
    @Attribute("list", type="string", description="权限列表集合", sample="[]",required=true),
    @Attribute("total", type="int", description="查询到的权限列表总条数", sample=9,required=true),
    })
     */
    public function index(Request $request, Permission $permission)
    {
        $params = $request->all();
        $data = $permission->getList($params);

        return $this->jsonAdminResultWithLog($request,[
            'total' => $data['total'],
            'list' => $data['items']
        ]);
    }

    /**
     * 全部权限
     * @name 全部权限
     * @Get("/lv/permissions/total")
     * @Versions("v1")
     * @PermissionWhiteList
     *
    @Response(200, body={
        "code":0,
        "message":"success",
        "list":{
            {
                "id": 2,
                "id_path": "",
                "key": "Order",
                "name": "订单",
                "children": {
                    {
                        "id": 67,
                        "id_path": "2",
                        "key": "Member_Order_DeliveryController",
                        "name": "批量发货",
                        "children": {
                            {
                                "id": 68,
                                "id_path": "2|67",
                                "key": "@Get:api_delivery_statListPage",
                                "name": "发货记录",
                            }
                        }
                    }
                }
            }
        }
    },
    attributes={
    @Attribute("list", type="string", description="所有权限集合", sample="[]",required=true),
    })
     */
    public function total(Request $request, Permission $permission)
    {
        $list = $permission->getPermissions();

        return $this->jsonAdminResultWithLog($request, [
            'list' => $list
        ]);
    }

    /**
     * 刷新权限
     * @name 刷新权限
     * @Put("/lv/permissions")
     * @Versions("v1")
     *
     * @param Request $request
     * @param Permission $permission
     * @return \Illuminate\Http\JsonResponse
     *
    @Response(200, body={
        "code":0,
        "message":"success",
    })
     */
    public function update(Request $request, Permission $permission)
    {
        $code = $permission->refresh();

        unset($request->userId); // 没这个参数不会记录操作log
        if ($code == 10000) { // 成功
            $redisKey = config('redisKey');
            $rbacKey = sprintf($redisKey['rbac']['key'], $request->userId);
            Redis::del($rbacKey);

            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 20001);
        }
    }

    /**
     * 编辑权限
     * @name 编辑权限
     * @Patch("/lv/permissions/{?id}")
     * @Versions("v1")
     * @PermissionWhiteList
     *
    @Request("id={id}&is_show={is_show}", contentType="application/x-www-form-urlencoded", attributes={
    @Attribute("id", type="int", required=true, description="权限id", sample=1),
    @Attribute("is_show", type="int", required=true, description="是否是栏目 0：否 1：是", sample=1),
    })
    @Response(200, body={
        "code":0,
        "message":"success",
    })
     */
    public function edit(Request $request, $id, Permission $permission)
    {
        $params = $request->all();

        if ($id <= 0) {
            return $this->jsonAdminResultWithLog($request, [],10002);
        }

        $result = $permission->where('id', $id)->update(['is_show' => $params['is_show']]);

        unset($request->userId); // 没这个参数不会记录操作log
        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [],10001);
        }
    }
}
