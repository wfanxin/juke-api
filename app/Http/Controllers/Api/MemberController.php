<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Payment;
use App\Model\Api\PayRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 用户
 */
class MemberController extends Controller
{
    use FormatTrait;

    /**
     * 用户注册
     * @param Request $request
     */
    public function register(Request $request, Member $mMember, Redis $redis)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $name = $params['name'] ?? '';
        $mobile_code = $params['mobile_code'] ?? '';
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        $pattern = '/^1[0-9]{10}$/';
//        $pattern = '/^1((3[0-9])|(4[57])|(5[012356789])|(8[02356789]))[0-9]{8}$/';
        if (!preg_match($pattern, $mobile)) {
            return $this->jsonAdminResult([],10001,'手机号格式不正确');
        }

        $count = $mMember->where('mobile', $mobile)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'该手机号已注册过');
        }

        if (empty($name)) {
            return $this->jsonAdminResult([],10001,'姓名不能为空');
        }

        if (empty($mobile_code)) {
            return $this->jsonAdminResult([],10001,'验证码不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'密码不能为空');
        }

        if (empty($cfpassword)) {
            return $this->jsonAdminResult([],10001,'确认密码不能为空');
        }

        if ($password != $cfpassword) {
            return $this->jsonAdminResult([],10001,'密码和确认密码不一致');
        }

        $config = config('redisKey');
        $mobileKey = sprintf($config['mem_code']['key'], $mobile);
        $verify_code = $redis::get($mobileKey);
        if ($verify_code != $mobile_code) {
            return $this->jsonAdminResult([],10001,'验证码错误');
        }

        $inviteUserId = $params['inviteUserId'] ?? 0;
        $count = $mMember->where('id', $inviteUserId)->where('level', '>=', 4)->where('status', 1)->count();
        $invite_uid = 0;
        if ($count > 0) { // 有邀请人
            $invite_uid = $inviteUserId;
        } else { // 无邀请人，则邀请人为系统用户最底层用户
            $info = $mMember->where('level', '>=', 4)->where('status', 1)->where('system', 1)->orderBy('id', 'desc')->first();
            $info = $this->dbResult($info);
            if (!empty($info)) {
                $invite_uid = $info['id'];
            }
        }

        if (empty($invite_uid)) {
            return $this->jsonAdminResult([],10001,'没有邀请人');
        }

        $p_uid = $mMember->getPuid($invite_uid);

        // 数据
        $time = date('Y-m-d H:i:s');
        $salt = rand(1000, 9999);
        $password = $this->_encodePwd($password, $salt);
        $data = [
            'invite_uid' => $invite_uid,
            'p_uid' => $p_uid,
            'mobile' => $mobile,
            'name' => $name,
            'password' => $password,
            'salt' => $salt,
            'status' => 1,
            'created_at' => $time,
            'updated_at' => $time
        ];

        $res = $mMember->insert($data);
        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 用户登录
     * @param Request $request
     */
    public function login(Request $request, Member $mMember)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $password = $params['password'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'密码不能为空');
        }

        $info = $mMember->where('mobile', $mobile)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'账号或密码错误');
        }

        if ($this->_encodePwd($password, $info['salt']) != $info['password']) {
            return $this->jsonAdminResult([],10001,'账号或密码错误');
        }

        if ($info['status'] == 3) { // 拉黑
            return $this->jsonAdminResult([],10001,'拉黑账号不能登录');
        }

        $redisKey = config('redisKey');
        $mTokenKey = sprintf($redisKey['m_token']['key'], $info['id']); // 登录授权令牌信息
        $memInfoKey = sprintf($redisKey['mem_info']['key'], $info['id']); // 用户信息

        // 发放校验令牌
        $time = time();
        $auth = md5(md5(sprintf("%s_%s_%s", $time, '34jkjf234KGDF3ORGI4j', $info['id'])));
        $token = sprintf("%s|%s|%s", $auth, $time, $info['id']);

        Redis::set($mTokenKey, $token);
        Redis::expire($mTokenKey, $redisKey['m_token']['ttl']);
        Redis::hmset($memInfoKey, $info);

        return $this->jsonAdminResult(['token' => $token]);
    }

    /**
     * 忘记密码
     * @param Request $request
     */
    public function forget(Request $request, Member $mMember, Redis $redis)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $mobile_code = $params['mobile_code'] ?? '';
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        if (empty($mobile_code)) {
            return $this->jsonAdminResult([],10001,'验证码不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'新密码不能为空');
        }

        if (empty($cfpassword)) {
            return $this->jsonAdminResult([],10001,'确认新密码不能为空');
        }

        if ($password != $cfpassword) {
            return $this->jsonAdminResult([],10001,'新密码不一致');
        }

        $config = config('redisKey');
        $mobileKey = sprintf($config['mem_code']['key'], $mobile);
        $verify_code = $redis::get($mobileKey);
        if ($verify_code != $mobile_code) {
            return $this->jsonAdminResult([],10001,'验证码错误');
        }

        $info = $mMember->where('mobile', $mobile)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'手机号还未注册');
        }

        $res = $mMember->where('id', $info['id'])->update(['password' => $this->_encodePwd($password, $info['salt'])]);
        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 退出登录
     * @param Request $request
     */
    public function logout(Request $request, Member $mMember)
    {
        $params = $request->all();

        $redisKey = config('redisKey');
        $mTokenKey = sprintf($redisKey['m_token']['key'], $request->memId); // 登录授权令牌信息
        $memInfoKey = sprintf($redisKey['mem_info']['key'], $request->memId); // 用户信息

        Redis::del($mTokenKey);
        Redis::del($memInfoKey);

        return $this->jsonAdminResult();
    }

    /**
     * 用户信息
     * @param Request $request
     */
    public function getMember(Request $request, Member $mMember, PayRecord $mPayRecord) {
        $params = $request->all();

        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);

        if (!empty($info)) {
            $level_list = $mMember->getLevelList();
            $info['level_name'] = $level_list[$info['level']] ?? '';
            $urlPre = config('filesystems.disks.tmp.url');
            if (!empty($info['avatar'])) {
                $info['avatar'] = $urlPre . $info['avatar'];
            }
        }

        return $this->jsonAdminResult(['data' => $info]);
    }

    /**
     * 编辑用户
     * @param Request $request
     */
    public function editMember(Request $request, Member $mMember) {
        $params = $request->all();

        $method = $params['method'] ?? '';

        $res = true;
        if ($method == 'avatar') {
            $avatar = $params['avatar'] ?? '';
            $urlPre = config('filesystems.disks.tmp.url');
            $avatar = str_replace($urlPre, '', $avatar);
            $avatar = str_replace('/static/logo.png', '', $avatar);
            if (empty($avatar)) {
                return $this->jsonAdminResult([],10001,'头像不能为空');
            }

            $res = $mMember->where('id', $request->memId)->update(['avatar' => $avatar]);
        } else if ($method == 'name') {
            $name = $params['name'] ?? '';
            if (empty($name)) {
                return $this->jsonAdminResult([],10001,'姓名不能为空');
            }

            $res = $mMember->where('id', $request->memId)->update(['name' => $name]);
        } else if ($method == 'password') {
            $oldPassword = $params['oldPassword'] ?? '';
            $password = $params['password'] ?? '';
            $cfpassword = $params['cfpassword'] ?? '';

            if (empty($oldPassword)) {
                return $this->jsonAdminResult([],10001,'原始密码不能为空');
            }

            if (empty($password)) {
                return $this->jsonAdminResult([],10001,'新密码不能为空');
            }

            if (empty($cfpassword)) {
                return $this->jsonAdminResult([],10001,'确认新密码不能为空');
            }

            if ($password != $cfpassword) {
                return $this->jsonAdminResult([],10001,'新密码不一致');
            }

            $info = $mMember->where('id', $request->memId)->first();
            $info = $this->dbResult($info);

            if ($this->_encodePwd($oldPassword, $info['salt']) != $info['password']) {
                return $this->jsonAdminResult([],10001,'原始密码错误');
            }

            $res = $mMember->where('id', $request->memId)->update(['password' => $this->_encodePwd($password, $info['salt'])]);
        }

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 收款方式
     * @param Request $request
     */
    public function payment(Request $request, Member $mMember, Payment $mPayment)
    {
        $params = $request->all();

        $pay_method = $params['pay_method'] ?? 0;

        if (empty($pay_method)) {
            return $this->jsonAdminResult([],10001,'请选择收款渠道');
        }

        if (!in_array($pay_method, [1,2,3])) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $count = $mPayment->where('uid', $request->memId)->where('pay_method', $pay_method)->count();
        $content = [];
        if ($pay_method == 1) { // 银行卡
            $bank_name = $params['bank_name'] ?? '';
            $branch_name = $params['branch_name'] ?? '';
            $account = $params['account'] ?? '';
            $account_name = $params['account_name'] ?? '';
            $contact = $params['contact'] ?? '';

            if (empty($bank_name)) {
                return $this->jsonAdminResult([],10001,'请输入银行名称');
            }

            if (empty($branch_name)) {
                return $this->jsonAdminResult([],10001,'请输入支行名称');
            }

            if (empty($account)) {
                return $this->jsonAdminResult([],10001,'请输入银行账号');
            }

            if (empty($account_name)) {
                return $this->jsonAdminResult([],10001,'请输入账号姓名');
            }

            if (empty($contact)) {
                return $this->jsonAdminResult([],10001,'请输入联系方式');
            }

            $content['bank_name'] = $bank_name;
            $content['branch_name'] = $branch_name;
            $content['account'] = $account;
            $content['account_name'] = $account_name;
            $content['contact'] = $contact;
        } else {
            $pay_url = $params['pay_url'] ?? '';
            if (empty($pay_url)) {
                return $this->jsonAdminResult([],10001,'请上传收款码');
            }

            $urlPre = config('filesystems.disks.tmp.url');
            $pay_url = str_replace($urlPre, '', $pay_url);

            $content['pay_url'] = $pay_url;
        }

        $res = true;
        $time = date('Y-m-d H:i:s');
        if ($count > 0) { // 更新
            $res = $mPayment->where('uid', $request->memId)->where('pay_method', $pay_method)->update([
                'content' => json_encode($content)
            ]);
        } else { // 新增
            $res = $mPayment->insert([
                'uid' => $request->memId,
                'pay_method' => $pay_method,
                'content' => json_encode($content),
                'created_at' => $time,
                'updated_at' => $time
            ]);
        }

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 获取收款方式
     * @param Request $request
     */
    public function getPayment(Request $request, Member $mMember, Payment $mPayment)
    {
        $params = $request->all();

        $list = $mPayment->where('uid', $request->memId)->orderBy('updated_at', 'asc')->get(['pay_method', 'content']);
        $list = $this->dbResult($list);

        $data = [];
        $pay_method = 0;
        $urlPre = config('filesystems.disks.tmp.url');
        foreach ($list as $key => $value) {
            $content = json_decode($value['content'], true) ?? [];
            if (!empty($content['pay_url'])) {
                $content['pay_url'] = $urlPre . $content['pay_url'];
            }

            $data[$value['pay_method']] = $content;
            $pay_method = $value['pay_method']; // 最后一次的收款方式优先
        }

        return $this->jsonAdminResult(['pay_method' => $pay_method, 'list' => $data]);
    }

    /**
     * 推荐人信息
     * @param Request $request
     */
    public function getInvite(Request $request, Member $mMember) {
        $params = $request->all();

        $userInfo = $mMember->where('id', $request->memId)->first(['id', 'invite_uid']);
        $userInfo = $this->dbResult($userInfo);

        $info = $mMember->where('id', $userInfo['invite_uid'])->first(['id', 'mobile', 'name', 'avatar', 'level']);
        $info = $this->dbResult($info);

        if (!empty($info)) {
            $level_list = $mMember->getLevelList();
            $info['level_name'] = $level_list[$info['level']] ?? '';
            $urlPre = config('filesystems.disks.tmp.url');
            if (!empty($info['avatar'])) {
                $info['avatar'] = $urlPre . $info['avatar'];
            }
        } else {
            $info = [
                'mobile' => '13800000000',
                'name' => 'admin',
                'avatar' => ''
            ];
        }

        return $this->jsonAdminResult(['data' => $info]);
    }

    /**
     * 我的收益
     * @param Request $request
     */
    public function getMoneyList(Request $request, Member $mMember, PayRecord $mPayRecord) {
        $params = $request->all();

        // 收益总额
        $money = $mMember->where('id', $request->memId)->value('money');

        // 收益明细
        $list = $mPayRecord->where('pay_uid', $request->memId)->where('status', 1)->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            // 付款人列表
            $member_list = $mMember->whereIn('id', array_column($list, 'user_id'))->get();
            $member_list = $this->dbResult($member_list);
            $urlPre = config('filesystems.disks.tmp.url');
            foreach ($member_list as $key => $value) {
                if (!empty($value['avatar'])) {
                    $member_list[$key]['avatar'] = $urlPre . $value['avatar'];
                }
            }
            $member_list = array_column($member_list, null, 'id');
            $level_list = $mMember->getLevelList();
            $pay_method_list = config('global.pay_method_list');
            $pay_method_list = array_column($pay_method_list, 'label', 'value');
            foreach ($list as $key => $value) {
                $list[$key]['user_name'] = $member_list[$value['user_id']]['name'] ?? '';
                $list[$key]['user_avatar'] = $member_list[$value['user_id']]['avatar'] ?? '';
                $list[$key]['up_level_name'] = $level_list[$value['up_level']] ?? '';
                $list[$key]['pay_method_name'] = $pay_method_list[$value['pay_method']] ?? '';
            }
        }

        return $this->jsonAdminResult(['data' => $list, 'money' => $money]);
    }

    /**
     * 直推列表
     * @param Request $request
     */
    public function getInviteMemberList(Request $request, Member $mMember) {
        $list = $mMember->where('invite_uid', $request->memId)->orderBy('id', 'desc')->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $urlPre = config('filesystems.disks.tmp.url');
            $level_list = $mMember->getLevelList();
            foreach ($list as $key => $value) {
                $value['level_name'] = $level_list[$value['level']] ?? '';
                if (!empty($value['avatar'])) {
                    $value['avatar'] = $urlPre . $value['avatar'];
                }
                $list[$key] = $value;
            }
        }

        return $this->jsonAdminResult([
            'data' => $list
        ]);
    }

    /**
     * 树形结构
     * @param Request $request
     */
    public function getTree(Request $request, Member $mMember) {
        $p_uid = $mMember->where('id', $request->memId)->value('p_uid');
        $data = $mMember->getChildren($p_uid, $request->memId, true,20);

        return $this->jsonAdminResult([
            'data' => $data
        ]);
    }
}
