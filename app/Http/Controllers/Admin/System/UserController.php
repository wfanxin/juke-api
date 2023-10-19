<?php

namespace App\Http\Controllers\Admin\System;

use App\Model\Admin\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Controller;
use App\Model\Admin\User;

/**
 * 用户管理
 * @name 用户管理
 * Class UserController
 * @package App\Http\Controllers\System
 *
 * @Resource("users")
 */
class UserController extends Controller
{
    /**
     * 用户列表
     * @name 用户列表
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     *
     *
     * @Get("/lv/users")
     * @Versions({"v1"})
     *
     @Request("status={status}&name={name}&page={page}", contentType="application/x-www-form-urlencoded", attributes={
        @Attribute("status", type="int", required=true, description="状态：-1：全部；1：正常；2：锁定", sample=1),
        @Attribute("name", type="string", required=false, description="姓名", sample="张三"),
        @Attribute("page", type="int", required=true, description="页码", sample=1),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
        "total": 10,
        "users":{
            {
            "id": 2,
            "name": "test",
            "user_name": "test",
            "password": "85316397bfcb170c8590dc724b3919da",
            "last_ip": "172.17.0.1",
            "status": 1,
            "error_amount": 0,
            "roles": "['2']",
            "avatar": "",
            "created_at": "2019-02-25 09:47:45",
            "updated_at": "2019-07-22 14:02:49",
            }
        }
    },
    attributes={
        @Attribute("total", type="int", description="查询到的用户总数", sample=10,required=true),
        @Attribute("users", type="string", description="用户数据集合", sample="[]",required=true),
    })
     */
    public function index(Request $request, User $user)
    {
        $params = $request->all();
        $where = [];

        if ($params['status'] > -1) {
            $where[] = ['status', '=', $params['status']];
        }

        if (! empty($params['name']) ) {
            $where[] = ['name', 'like', "%{$params['name']}%"];
        }

        $userInfo = $user->getCurUser($request->userId);
        if (!in_array('admin', $userInfo['roles'])) { // 不是超级管理员
            $mRole = new Role();
            $role_list = $mRole->whereIn('name', ['admin', '管理组'])->get();
            $role_ids = [];
            foreach ($role_list as $role) {
                $role_ids[] = '["' . $role['id'] . '"]';
            }
            $where[] = [function ($query) use ($role_ids, $request) {
                $query->whereNotIn('roles', $role_ids)->orWhere('id', $request->userId);
            }];
        }

        // order by
        $orderField = 'id';
        $sort = 'desc';
        if (!empty($params['order'])) {
            $order = explode('|', $params['order']);
            $orderField = $order[0];
            $sort = str_replace('ending', '', $order[1]);
        }

        $data = $user->where($where)->orderBy($orderField, $sort)->paginate(15, ["*"], "page", $params["page"]);

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'users' => $data->items()
        ]);
    }

    /**
     * 添加用户
     * @name 添加用户
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     *
     * @Post("/lv/users")
     * @Versions({"v1"})
     *
     @Request("name={name}&user_name={user_name}&password={password}&user_roles={user_roles}", contentType="application/x-www-form-urlencoded", attributes={
        @Attribute("name", type="string", required=true, description="姓名", sample="张三"),
        @Attribute("user_name", type="string", required=true, description="用户名", sample="zhangsan"),
        @Attribute("password", type="string", required=true, description="密码", sample="123123112"),
        @Attribute("user_roles", type="int", required=true, description="角色id", sample=5),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
     })
     */
    public function store(Request $request,User $user)
    {
        $params = $request->all();
        $data = $user->where('name', $params['name'])->orWhere('user_name', $params['user_name'])->get();
        if (!empty($data)){
            $data = json_decode(json_encode($data),true);
            foreach ($data as $val){
                if ($val['name'] == $params['name']){
                    return $this->jsonAdminResult([],10001,'系统已存在该姓名');
                }if ($val['user_name'] == $params['user_name']){
                    return $this->jsonAdminResult([],10001,'系统已存在该用户名');
                }
            }
        }
        if (empty($params['user_roles'])) {
            return $this->jsonAdminResult([],10001,'请选择角色');
        }
        $result = $user->insert([
            'name' => $params['name'],
            'user_name' => $params['user_name'],
            'password' => $this->_encodePwd(trim($params['password'])),
            'salt' => $this->_salt,
            'status' => 1,
            'avatar' => '',
            'roles' => json_encode([strval($params['user_roles'])]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [],10001);
        }
    }

    /**
     * 获取当前登录用户信息
     * @name 获取当前登录用户信息
     *
     * @return \Illuminate\Http\Response
     *
     * @PermissionWhiteList
     * @Get("/lv/users/{?id}")
     * @Versions({"v1"})
     *
     @Response(200, body={
        "code":0,
        "message":"success",
        "name":"管理员的名字",
        "roles":"[]",
        "permissions":"[]"
     }, attributes={
            @Attribute("name", type="string", description="用户姓名", sample="张三",required=true),
            @Attribute("roles", type="string", description="角色集合", sample="[]",required=true),
            @Attribute("permissions", type="string", description="权限集合", sample="[]",required=true),
     })
     */
    public function show(Request $request, $id, User $user)
    {
        if ($request->userId <= 0 || $id != $request->userId) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }

        $userInfo = $user->getCurUser($request->userId);

        return $this->jsonAdminResultWithLog($request, [
            'name' => $userInfo['user']['name'],
            'roles' => $userInfo['roles'],
            'permissions' => $userInfo['permissions'],
            'nav' => $userInfo['nav']
        ]);
    }

    /**
     * 编辑用户
     * @name 编辑用户
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     *
     * @Put("/lv/users/{?id}")
     * @Versions({"v1"})
     *
     @Request("id={id}&name={name}&user_name={user_name}&status={status}&password={password}&user_roles={user_roles}", contentType="application/x-www-form-urlencoded", attributes={
        @Attribute("id", type="int", required=true, description="用户ID", sample=1),
        @Attribute("name", type="string", required=true, description="姓名", sample="test"),
        @Attribute("user_name", type="string", required=true, description="用户名", sample="test"),
        @Attribute("status", type="int", required=true, description="状态", sample=1),
        @Attribute("password", type="string", required=false, description="密码", sample="123123112"),
        @Attribute("roles", type="int", required=true, description="角色集合", sample=5),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
     })
     */
    public function update(Request $request, $id, User $user)
    {
        $params = $request->all();

        if ($id <= 0) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }

        $data = [
            'name' => $params['name'],
            'user_name' => $params['user_name'],
            'status' => (int)$params['status'],
            'roles' => json_encode([strval($params['user_roles'])]),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $serData = $user->where('id', '!=', $id)
            ->where(function ($query) use ($params) {
                $query->where('name', '=', $params['name'])
                    ->orWhere('user_name', '=', $params['user_name']);
            })->get();
        $serData = json_decode(json_encode($serData),true);

        if (!empty($serData)){
            foreach ($serData as $val){
                if ($val['name'] == $params['name']){
                    return $this->jsonAdminResult([],10001,'系统已存在该姓名');
                } if ($val['user_name'] == $params['user_name']){
                    return $this->jsonAdminResult([],10001,'系统已存在该用户名');
                }
            }
        }

        if (!empty($params['password']) && $params['password'] == $params['re_password']) {
            $userInfo = $user->where(['id' => $id])->first();
            $data['password'] = $this->_encodePwd(trim($params['password']), $userInfo['salt']);
        }

        $result = $user->where(['id' => $id])->update($data);

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 修改密码
     * @name 修改密码
     * @Put("/lv/users/pwd")
     * @Versions("v1")
     * @PermissionWhiteList
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     *
     @Request("old_password={old_password}&password={password}", contentType="application/x-www-form-urlencoded", attributes={
     @Attribute("old_password", type="string", required=true, description="原密码", sample="123222222"),
     @Attribute("password", type="string", required=true, description="新密码", sample="123123112"),
     })
     @Response(200, body={
         "code":0,
         "message":"success",
     })
     */
    public function changePwd(Request $request, User $user)
    {
        $params = $request->all();

        $userInfo = $user->where('id', $request->userId)->first()->toArray();

        // 验证原密码
        if (empty($params['old_password']) || $userInfo['password'] != $this->_encodePwd($params['old_password'], $userInfo['salt'])) {
            return $this->jsonAdminResultWithLog($request, [], 10011);
        }

        $result = $user->where('id', $request->userId)->update([
            'password' => $this->_encodePwd($params['password'], $userInfo['salt'])
        ]);

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 删除用户
     * @name 删除用户
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     *
     * @Delete("/lv/users/{?id}")
     * @Versions("v1")
     *
     @Request("id={id}", contentType="application/x-www-form-urlencoded", attributes={
     @Attribute("id", type="int", required=true, description="用户id", sample=1),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
     })
     */
    public function destroy(Request $request, $id, User $user)
    {
        $params = $request->all();

        if ($id <= 0) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }

        $result = $user->where(['id' => $id])->delete();

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 批量删除用户
     * @name 批量删除用户
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     *
     * @Delete("/lv/users/batch")
     * @Versions("v1")
     *
     @Request("ids={ids}", contentType="application/x-www-form-urlencoded", attributes={
        @Attribute("ids", type="string", required=true, description="需要删除的用户id集合", sample="1,2,3"),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
     })
     */
    public function batchDestroy(Request $request, User $user)
    {
        $params = $request->all();

        if (empty($params['ids'])) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }

        $ids = explode(',', $params['ids']);
        $result = $user->whereIn('id', $ids)->delete();

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 校验用户名或者姓名是否存在
     * @name 校验用户名或者姓名是否存在
     * @Post("/lv/users/checkName")
     * @Versions("v1")
     * @PermissionWhiteList
     *
     * @param Request $request
     * @param User $mUser
     * @return \Illuminate\Http\JsonResponse
     *
     @Request("id={id}&name={name}&user_name={user_name}", contentType="application/x-www-form-urlencoded", attributes={
     @Attribute("id", type="int", required=true, description="用戶id 新增传0,编辑传用户id", sample=1),
     @Attribute("name", type="string", required=false, description="姓名", sample="test"),
     @Attribute("user_name", type="string", required=false, description="用户名", sample="test"),
     })
     @Response(200, body={
         "code":0,
         "message":"success",
     })
     *
     */
    public function checkName(Request $request, User $mUser)
    {
        $params = $request->all();
        $where = [];
        if ($params['id'] > 0){
            $where[] = ['id', '!=', $params['id']];
        }
        $name = '';
        if (!empty($params['user_name'])) {
            $exist = $mUser
                ->where($where)
                ->where('user_name', trim($params['user_name']))
                ->first();
            $name = '用户名';
        } else if (!empty($params['name'])) {
            $exist = $mUser
                ->where($where)
                ->where('name', trim($params['name']))
                ->first();
            $name = '姓名';
        }

        unset($request->userId); // 没这个参数不会记录操作log
        if (!empty($exist)) {
            return $this->jsonAdminResultWithLog($request, [], 10001, '系统已存在该' . $name);
        } else {
            return $this->jsonAdminResultWithLog($request);
        }
    }
}
