<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Payment;
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
    public function getLevelUpMember(Request $request, Member $mMember, Payment $mPayment)
    {
        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);

        // 获取邀请人
        $list = $mMember->where('id', $info['invite_uid'])->get();
        $list = $this->dbResult($list);

        $max_num = 2; // 下级人数
        while(1) {
            if (empty($list)) {
                return $this->jsonAdminResult([
                    'data' => []
                ]);
            }

            $ids = [];
            foreach ($list as $value) {
                $memberList = $mMember->where('p_uid', $value['id'])->get();
                $memberList = $this->dbResult($memberList);
                if ($value['status'] == 1 && $value['level'] >= 4 && count($memberList) < $max_num) { // 找到了
                    $paymentList = $mPayment->where('uid', $value['id'])->get();
                    $paymentList = $this->dbResult($paymentList);
                    if (!empty($paymentList)) {
                        foreach ($paymentList as $k => $v) {
                            $paymentList[$k]['content'] = json_decode($v['content'], true);
                        }
                        $value['paymentList'] = $paymentList;
                        unset($value['password']);
                        unset($value['salt']);
                        return $this->jsonAdminResult([
                            'data' => $value
                        ]);
                    }
                }

                $max_num *= 2;
                $ids = array_merge($ids, array_column($memberList, 'id'));
            }

            $list = $mMember->whereIn('id', $ids)->get();
            $list = $this->dbResult($list);
        }
    }
}
