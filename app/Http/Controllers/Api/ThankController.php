<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Config;
use App\Model\Api\Member;
use App\Model\Api\Package;
use App\Model\Api\Payment;
use App\Model\Api\PayRecord;
use Illuminate\Http\Request;

/**
 * 感恩奖
 */
class ThankController extends Controller
{
    use FormatTrait;

    /**
     * 获取上级邀请用户
     * @param Request $request
     */
    public function getThankUpMember(Request $request, Member $mMember, Payment $mPayment, Config $mConfig)
    {
        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);

        // 获取邀请人
        $pinfo = $mMember->where('id', $info['invite_uid'])->first();
        $pinfo = $this->dbResult($pinfo);

        $paymentList = $mPayment->where('uid', $pinfo['id'])->get();
        $paymentList = $this->dbResult($paymentList);

        while (empty($paymentList) || $pinfo['level'] < 4 || $pinfo['status'] != 1 || !$mMember->isThank($pinfo['id'])) { // 邀请人不符合条件，则找上级邀请人
            $pinfo = $mMember->where('id', $pinfo['invite_uid'])->first();
            $pinfo = $this->dbResult($pinfo);

            $paymentList = $mPayment->where('uid', $pinfo['id'])->get();
            $paymentList = $this->dbResult($paymentList);
        }

        if (!empty($paymentList)) {
            $urlPre = config('filesystems.disks.tmp.url');
            foreach ($paymentList as $k => $v) {
                $content = json_decode($v['content'], true);
                if (in_array($v['pay_method'], [2, 3])) {
                    $content['pay_url'] = $urlPre . $content['pay_url'];
                }
                $paymentList[$k]['content'] = $content;
            }
        }

        $award = $mConfig->where('name', 'award')->first();
        $award = $this->dbResult($award);
        if (empty($award)) {
            return $this->jsonAdminResult([],10001,'感恩奖没有配置');
        }

        $award = json_decode($award['content'], true) ?? [];
        if (empty($award)) {
            return $this->jsonAdminResult([],10001,'感恩奖没有配置');
        }

        $pinfo['money'] = ($award['value'] ?? 0) * ($award['scale'] ?? 0) * 0.01;
        if ($pinfo['money'] <= 0) {
            return $this->jsonAdminResult([],10001,'感恩奖配置错误');
        }

        return $this->jsonAdminResult([
            'pmember' => $pinfo,
            'paymentList' => $paymentList
        ]);
    }

    /**
     * 添加感恩奖
     * @param Request $request
     */
    public function thankUp(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $params = $request->all();

        $pay_uid = $params['pay_uid'] ?? 0;
        $pay_method = $params['pay_method'] ?? 0;
        $pay_url = $params['pay_url'] ?? '';
        $money = $params['money'] ?? 0;

        if (empty($pay_uid) || empty($money)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        if (empty($pay_method)) {
            return $this->jsonAdminResult([],10001,'请选择收款方式');
        }

        if (empty($pay_url)) {
            return $this->jsonAdminResult([],10001,'请上传付款凭证');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $pay_url = str_replace($urlPre, '', $pay_url);

        $time = date('Y-m-d H:i:s');
        $data = [
            'user_id' => $request->memId,
            'up_level' => 0,
            'pay_uid' => $pay_uid,
            'pay_method' => $pay_method,
            'pay_url' => $pay_url,
            'money' => $money,
            'created_at' => $time,
            'updated_at' => $time
        ];
        $package_data = $data;
        $package_data['pay_uid'] = $mMember->getThankInviteUid($request->memId);
        if ($package_data['pay_uid'] != $data['pay_uid']) { // 不一致，才算丢包
            $mPackage = new Package();
            $mPackage->insert($package_data);
        }

        $res = $mPayRecord->insert($data);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 感恩奖记录
     * @param Request $request
     */
    public function getThankList(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $list = $mPayRecord->where('user_id', $request->memId)->where('up_level', 0)->orderBy('id', 'desc')->get(); // 0为感恩奖
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $pay_method_list = config('global.pay_method_list');
            $pay_method_list = array_column($pay_method_list, 'label', 'value');
            $urlPre = config('filesystems.disks.tmp.url');
            $pay_uids = array_column($list, 'pay_uid');
            $member_list = $mMember->whereIn('id', $pay_uids)->get();
            $member_list = $this->dbResult($member_list);
            foreach ($member_list as $key => $value) {
                if (!empty($value['avatar'])) {
                    $member_list[$key]['avatar'] = $urlPre . $value['avatar'];
                }
            }
            $member_list = array_column($member_list, null, 'id');

            foreach ($list as $key => $value) {
                $list[$key]['pay_name'] = $member_list[$value['pay_uid']]['name'] ?? '';
                $list[$key]['pay_avatar'] = $member_list[$value['pay_uid']]['avatar'] ?? '';
                $list[$key]['pay_method_name'] = $pay_method_list[$value['pay_method']] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'data' => $list
        ]);
    }

    /**
     * 感恩奖审核记录
     * @param Request $request
     */
    public function getApplyList(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $list = $mPayRecord->where('pay_uid', $request->memId)->where('up_level', '=', 0)->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $urlPre = config('filesystems.disks.tmp.url');
            $uids = array_column($list, 'user_id');
            $member_list = $mMember->whereIn('id', $uids)->get();
            $member_list = $this->dbResult($member_list);
            foreach ($member_list as $key => $value) {
                if (!empty($value['avatar'])) {
                    $member_list[$key]['avatar'] = $urlPre . $value['avatar'];
                }
            }
            $member_list = array_column($member_list, null, 'id');
            $level_list = $mMember->getLevelList();

            foreach ($list as $key => $value) {
                $list[$key]['apply_name'] = $member_list[$value['user_id']]['name'] ?? '';
                $list[$key]['apply_avatar'] = $member_list[$value['user_id']]['avatar'] ?? '';
                $list[$key]['up_level_name'] = $level_list[$value['up_level']] ?? '';
                if (!empty($value['pay_url'])) {
                    $list[$key]['pay_url'] = $urlPre . $value['pay_url'];
                }
            }
        }

        return $this->jsonAdminResult([
            'data' => $list
        ]);
    }

    /**
     * 感恩奖审核操作
     * @param Request $request
     */
    public function thankVerify(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $status = $params['status'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        if (!in_array($status, [1,2])) {
            return $this->jsonAdminResult([],10001,'状态错误');
        }

        $info = $mPayRecord->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'审核记录不存在');
        }

        if ($info['status'] != 0) {
            return $this->jsonAdminResult([],10001,'不是待审核状态不能操作');
        }

        if ($status == 1) { // 审核通过
            $pay_member_info = $mMember->where('id', $info['pay_uid'])->first();
            $pay_member_info = $this->dbResult($pay_member_info);

            $mPayRecord->where('id', $id)->update(['status' => $status]);
            $mMember->where('id', $pay_member_info['id'])->update(['money' => $pay_member_info['money'] + $info['money']]);
            $mMember->where('id', $info['user_id'])->increment('thank_num', 1);
            return $this->jsonAdminResult();

        } else { // 审核失败
            $res = $mPayRecord->where('id', $id)->update(['status' => $status]);
            if ($res) {
                return $this->jsonAdminResult();
            } else {
                return $this->jsonAdminResult([],10001,'操作失败');
            }
        }
    }
}
