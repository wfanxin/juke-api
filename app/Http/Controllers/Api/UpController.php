<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Config;
use App\Model\Api\Member;
use App\Model\Api\Payment;
use App\Model\Api\PayRecord;
use Illuminate\Http\Request;

/**
 * 升级
 */
class UpController extends Controller
{
    use FormatTrait;

    /**
     * 获取上级用户
     * @param Request $request
     */
    public function getLevelUpMember(Request $request, Member $mMember, Payment $mPayment, Config $mConfig)
    {
        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);

        // 获取邀请人
        $pinfo = $mMember->where('id', $info['invite_uid'])->first();
        $pinfo = $this->dbResult($pinfo);

        $paymentList = [];
        if ($info['level'] == 0) { // 上级1级，付款给邀请人
            $paymentList = $mPayment->where('uid', $info['invite_uid'])->get();
            $paymentList = $this->dbResult($paymentList);
        }

        $level = 0;
        if (empty($paymentList) || $info['level'] > $level) {
            $pinfo = $mMember->where('id', $pinfo['p_uid'])->first();
            $pinfo = $this->dbResult($pinfo);
            $level++;
            if ($info['level'] > $level) {
                $paymentList = $mPayment->where('uid', $pinfo['id'])->get();
                $paymentList = $this->dbResult($paymentList);
            }
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

        $grade = $mConfig->where('name', 'grade')->first();
        $grade = $this->dbResult($grade);
        $grade_list = [];
        if (!empty($grade)) {
            $grade_list = json_decode($grade['content'], true);
        }

        if (!empty($pinfo)) {
            $pinfo['money'] = $grade_list[$info['level']]['money'] ?? '';
        }

        return $this->jsonAdminResult([
            'pmember' => $pinfo,
            'paymentList' => $paymentList
        ]);
    }

    /**
     * 立即升级
     * @param Request $request
     */
    public function levelUp(Request $request, Member $mMember, PayRecord $mPayRecord)
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

        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'用户信息不存在');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $pay_url = str_replace($urlPre, '', $pay_url);
        $data = [
            'user_id' => $request->memId,
            'up_level' => $info['level'] + 1,
            'pay_uid' => $pay_uid,
            'pay_method' => $pay_method,
            'pay_url' => $pay_url,
            'money' => $money
        ];

        $count = $mPayRecord->where('user_id', $data['user_id'])->where('up_level', $data['up_level'])->count();
        $time = date('Y-m-d H:i:s');
        $res = true;
        if ($count > 0) { // 更新
            $data['status'] = 0; // 审核中
            $res = $mPayRecord->where('user_id', $data['user_id'])->where('up_level', $data['up_level'])->update($data);
        } else { // 新增
            $data['created_at'] = $time;
            $data['updated_at'] = $time;
            $res = $mPayRecord->insert($data);
        }

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 升级记录
     * @param Request $request
     */
    public function getPayRecordList(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $list = $mPayRecord->where('user_id', $request->memId)->orderBy('id', 'desc')->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $level_list = config('global.level_list');
            $level_list = array_column($level_list, 'label', 'value');
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
                $list[$key]['up_level_name'] = $level_list[$value['up_level']] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'data' => $list
        ]);
    }

    /**
     * 获取申请记录
     * @param Request $request
     */
    public function getApplyList(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $list = $mPayRecord->where('pay_uid', $request->memId)->get();
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
            $level_list = config('global.level_list');
            $level_list = array_column($level_list, 'label', 'value');

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
     * 获取申请记录
     * @param Request $request
     */
    public function getPayRecord(Request $request, Member $mMember, PayRecord $mPayRecord, Payment $mPayment)
    {
        $params = $request->all();

        if (empty($params['id'])) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $urlPre = config('filesystems.disks.tmp.url');

        $payRecord = $mPayRecord->where('id', $params['id'])->first();
        $payRecord = $this->dbResult($payRecord);

        if (!empty($payRecord['pay_url'])) {
            $payRecord['pay_url'] = $urlPre . $payRecord['pay_url'];
        }

        $pmember = $mMember->where('id', $payRecord['pay_uid'])->first();
        $pmember = $this->dbResult($pmember);

        $paymentList = $mPayment->where('uid', $payRecord['pay_uid'])->get();
        $paymentList = $this->dbResult($paymentList);

        if (!empty($paymentList)) {
            foreach ($paymentList as $k => $v) {
                $content = json_decode($v['content'], true);
                if (in_array($v['pay_method'], [2, 3])) {
                    $content['pay_url'] = $urlPre . $content['pay_url'];
                }
                $paymentList[$k]['content'] = $content;
            }
        }

        return $this->jsonAdminResult([
            'pmember' => $pmember,
            'paymentList' => $paymentList,
            'payRecord' => $payRecord
        ]);
    }

    /**
     * 审核操作
     * @param Request $request
     */
    public function upVerify(Request $request, Member $mMember, PayRecord $mPayRecord)
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

        if ($status == 1) { // 审核成功
            $list = $mMember->where('id', $info['pay_uid'])->get();
            $list = $this->dbResult($list);
            $max_num = 2; // 下级人数
            while(1) {
                if (empty($list)) {
                    return $this->jsonAdminResult([],10001,'没有合适的位置');
                }

                $ids = [];
                foreach ($list as $value) {
                    $memberList = $mMember->where('p_uid', $value['id'])->get();
                    $memberList = $this->dbResult($memberList);
                    if ($value['status'] == 1 && $value['level'] >= 4 && count($memberList) < $max_num) { // 找到了
                        $mMember->where('id', $info['user_id'])->update(['p_uid' => $value['id'], 'level' => $info['up_level']]);
                        $mPayRecord->where('id', $id)->update(['status' => $status]);
                        return $this->jsonAdminResult();
                    }

                    $max_num *= 2;
                    $ids = array_merge($ids, array_column($memberList, 'id'));
                }

                $list = $mMember->whereIn('id', $ids)->get();
                $list = $this->dbResult($list);
            }
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
