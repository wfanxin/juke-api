<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\Controller;
use App\Model\Admin\Log;
use Illuminate\Http\Request;

/**
 * 操作日志
 * @name 操作日志
 * Class LogController
 * @package App\Http\Controllers\Admin\System
 *
 * @Resource("logs")
 */
class LogController extends Controller
{
    /**
     * 日志列表
     * @name 日志列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @Get("/lv/logs")
     * @Version("v1")
     *
     * @Request("user_name={user_name}&select_time={select_time}&permission={permission}&page={page}", contentType="application/x-www-form-urlencoded", attributes={
            @Attribute("user_name", type="string", required=true, description="用户名", sample="zhangsan"),
            @Attribute("select_time", type="array", required=true, description="查询日期", sample="['2018-05-20 12:00:00', '2018-05-20 14:00:00']"),
            @Attribute("permission", type="string", required=true, description="查询的权限", sample="2|32|35"),
            @Attribute("page", type="int", required=true, description="页码", sample="1"),
        })
     *
     @Response(200, body={
        "code":0,
        "message":"success",
        "total": 10,
        "logs":{
             {
                "created_at": "2021-08-30 14:31:22",
                "id": 2,
                "ip": "192.168.2.204",
                "name": "chenjj",
                "op_uid": "2",
                "request": {"name": "刷新权限", "url": "@Put:lv_member_permissions", "param": "[]"},
                "response": {"code": 200, "message": "success"},
                "updated_at": "2021-08-30 14:31:22",
                "user_name": "chenjj",
            }
        }
    },
    attributes={
        @Attribute("total", type="int", description="总条数", sample=10,required=true),
        @Attribute("logs", type="string", description="操作记录集合", sample="[]",required=true),
    })
     */
    public function index(Request $request, Log $log)
    {
        $params = $request->all();
        list($list, $data) = $log->getList($params);

        return $this->jsonAdminResultWithLog($request,[
            'total' => $list->total(),
            'logs' => $data
        ]);
    }
}
