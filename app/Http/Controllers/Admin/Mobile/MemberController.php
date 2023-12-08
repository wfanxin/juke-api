<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Payment;
use Illuminate\Http\Request;

/**
 * @name 会员管理
 * Class MemberController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("slides")
 */
class MemberController extends Controller
{
    use FormatTrait;

    /**
     * @name 会员列表
     * @Get("/lv/mobile/member/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Member $mMember)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 手机号
        if (!empty($params['mobile'])){
            $where[] = ['mobile', 'like', '%' . $params['mobile'] . '%'];
        }

        // 姓名
        if (!empty($params['name'])){
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mMember->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $urlPre = config('filesystems.disks.tmp.url');
            $level_list = $mMember->getLevelList();
            $status_list = config('global.status_list');
            $status_list = array_column($status_list, 'label', 'value');
            foreach ($data->items() as $k => $v) {
                if (!empty($v->avatar)) {
                    $data->items()[$k]['avatar'] = $urlPre . $v->avatar;
                }
                $data->items()[$k]['level_name'] = $level_list[$v->level] ?? '';
                $data->items()[$k]['status_name'] = $status_list[$v->status] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'status_list' => config('global.status_list'),
            'system_num' => $mMember->where('system', 1)->count()
        ]);
    }

    /**
     * @name 修改会员
     * @Post("/lv/mobile/member/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Member $mMember)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $mobile = $params['mobile'] ?? '';
        $name = $params['name'] ?? '';
        $status = $params['status'] ?? 0;
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        $data = [];

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        $info = $mMember->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001, '用户信息不存在');
        }

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001, '手机号不能为空');
        }

        $pattern = '/^1[0-9]{10}$/';
        if (!preg_match($pattern, $mobile)) {
            return $this->jsonAdminResult([],10001,'手机号格式不正确');
        }

        $count = $mMember->where('id', '!=' , $id)->where('mobile', $mobile)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001, '手机号已存在');
        }

        if (empty($name)) {
            return $this->jsonAdminResult([],10001, '姓名不能为空');
        }

        if (empty($status)) {
            return $this->jsonAdminResult([],10001, '状态不能为空');
        }

        $data['mobile'] = $mobile;
        $data['name'] = $name;
        $data['status'] = $status;

        if (!empty($password)) {
            if (empty($cfpassword)) {
                return $this->jsonAdminResult([],10001, '确认新密码不能为空');
            }
            if ($password != $cfpassword) {
                return $this->jsonAdminResult([],10001, '确认新密码不一致');
            }

            $salt = $info['salt'];
            $data['password'] = $this->_encodePwd($password, $salt);
        }

        $res = $mMember->where('id', $id)->update($data);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除会员
     * @Post("/lv/mobile/member/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Member $mMember)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $info = $mMember->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'会员不存在');
        }

        if ($info['system'] == 1) {
            return $this->jsonAdminResult([],10001,'不能删除系统会员');
        }

        $res = $mMember->delMember($info);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 生成系统会员
     * @Post("/lv/mobile/member/createSystemMember")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function createSystemMember(Request $request, Member $mMember, Payment $mPayment)
    {
        $params = $request->all();

        $image = $params['image'] ?? '';
        $memberList = $params['memberList'] ?? [];

        if (empty($image)){
            return $this->jsonAdminResult([],10001, '微信收款码不能为空');
        }

        if (count($memberList) != 20) {
            return $this->jsonAdminResult([],10001, '账号不是20个');
        }

        $count = $mMember->where('system', 1)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001, '系统账号已存在，不用再创建');
        }

        $pattern = '/^1[0-9]{10}$/';
        $mobile_arr = [];
        foreach ($memberList as $value) {
            if (empty($value['mobile'])) {
                return $this->jsonAdminResult([],10001, '手机号不能为空');
            }
            if (empty($value['name'])) {
                return $this->jsonAdminResult([],10001, '姓名不能为空');
            }

            $pattern = '/^1[0-9]{10}$/';
            if (!preg_match($pattern, $value['mobile'])) {
                return $this->jsonAdminResult([],10001,'手机号格式不正确');
            }

            if (in_array($value['mobile'], $mobile_arr)) {
                return $this->jsonAdminResult([],10001,'手机号【' . $value['mobile'] . '】重复');
            }
            $mobile_arr[] = $value['mobile'];
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $image = str_replace($urlPre, '', $image);

        $invite_uid = 0;
        $time = date('Y-m-d H:i:s');
        $pay_method = 2;
        $content = [];
        $content['pay_url'] = $image;
        foreach ($memberList as $value) {
            $password = '123456';
            $salt = rand(1000, 9999);
            $password = $this->_encodePwd($password, $salt);
            $data = [
                'invite_uid' => $invite_uid,
                'p_uid' => $invite_uid,
                'mobile' => $value['mobile'],
                'name' => $value['name'],
                'password' => $password,
                'salt' => $salt,
                'level' => 20,
                'status' => 1,
                'system' => 1,
                'created_at' => $time,
                'updated_at' => $time
            ];

            $invite_uid = $mMember->insertGetId($data);

            $count = $mPayment->where('uid', $invite_uid)->where('pay_method', $pay_method)->count();
            if ($count > 0) { // 更新
                $mPayment->where('uid', $invite_uid)->where('pay_method', $pay_method)->update([
                    'content' => json_encode($content)
                ]);
            } else { // 新增
                $mPayment->insert([
                    'uid' => $invite_uid,
                    'pay_method' => $pay_method,
                    'content' => json_encode($content),
                    'created_at' => $time,
                    'updated_at' => $time
                ]);
            }
        }

        return $this->jsonAdminResult();
    }

    /**
     * @name 树形结构
     * @Get("/lv/mobile/member/getTree")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function getTree(Request $request, Member $mMember) {
        $params = $request->all();
        if (empty($params['id'])) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        $p_uid = $mMember->where('id', $params['id'])->value('p_uid');
        $data = $mMember->getChildren($p_uid, $params['id'], true, 20);

        return $this->jsonAdminResult([
            'data' => $data
        ]);
    }
}
