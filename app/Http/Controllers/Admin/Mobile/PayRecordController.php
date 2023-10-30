<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\PayRecord;
use Illuminate\Http\Request;

/**
 * @name 打款记录
 * Class PayRecordController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("pay_records")
 */
class PayRecordController extends Controller
{
    use FormatTrait;

    /**
     * @name 打款记录列表
     * @Get("/lv/mobile/payRecord/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 状态
        if ($params['status'] != '') {
            $where[] = ['status', '=', $params['status']];
        }

        // 打款类型
        if ($params['level'] != '') {
            if ($params['level'] == 0) { // 感恩奖
                $where[] = ['up_level', '=', 0];
            } else { // 升级
                $where[] = ['up_level', '!=', 0];
            }
        }

        // 打款人姓名
        if (!empty($params['user_name'])){
            $uid = $mMember->where('name', 'like', '%' . $params['user_name'] . '%')->value('id') ?? -1;
            $where[] = ['user_id', '=', $uid];
        }

        // 收款人姓名
        if (!empty($params['pay_name'])){
            $uid = $mMember->where('name', 'like', '%' . $params['pay_name'] . '%')->value('id') ?? -1;
            $where[] = ['pay_uid', '=', $uid];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mPayRecord->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $pay_method_list = config('global.pay_method_list');
            $pay_method_list = array_column($pay_method_list, 'label', 'value');
            $urlPre = config('filesystems.disks.tmp.url');
            $uids = [];
            foreach ($data->items() as $k => $v){
                $uids[] = $v->user_id;
                $uids[] = $v->pay_uid;
            }
            $member_list = $mMember->whereIn('id', $uids)->get();
            $member_list = $this->dbResult($member_list);
            $member_list = array_column($member_list, 'name', 'id');

            foreach ($data->items() as $k => $v){
                $data->items()[$k]['pay_url'] = $urlPre . $v->pay_url;
                $data->items()[$k]['pay_method_name'] = $pay_method_list[$v->pay_method] ?? '';
                $data->items()[$k]['pay_name'] = $member_list[$v->pay_uid] ?? '';
                $data->items()[$k]['user_name'] = $member_list[$v->user_id] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 打款记录审核
     * @Post("/lv/mobile/leave/handleStatus")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function handleStatus(Request $request, Member $mMember, PayRecord $mPayRecord)
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

        if ($info['status'] == 0) {
            return $this->jsonAdminResult([],10001,'不是待审核状态不能操作');
        }

        if ($info['up_level'] == 0) { // 感恩奖
            if ($status == 1) { // 审核通过
                $pay_member_info = $mMember->where('id', $info['pay_uid'])->first();
                $pay_member_info = $this->dbResult($pay_member_info);

                $mPayRecord->where('id', $id)->update(['status' => $status]); // 修改状态
                $mMember->where('id', $pay_member_info['id'])->update(['money' => $pay_member_info['money'] + $info['money']]); // 添加金额
                $mMember->where('id', $info['user_id'])->increment('thank_num', 1); // 添加感恩奖支付次数
                return $this->jsonAdminResultWithLog($request);
            } else {
                $res = $mPayRecord->where('id', $id)->update(['status' => $status]);
                if ($res) {
                    return $this->jsonAdminResultWithLog($request);
                } else {
                    return $this->jsonAdminResult([],10001,'操作失败');
                }
            }
        } else { // 升级
            if ($status == 1) { // 审核通过
                $pay_member_info = $mMember->where('id', $info['pay_uid'])->first();
                $pay_member_info = $this->dbResult($pay_member_info);

                $mPayRecord->where('id', $id)->update(['status' => $status]); // 修改状态
                $mMember->where('id', $pay_member_info['id'])->update(['money' => $pay_member_info['money'] + $info['money']]); // 添加金额
                $mMember->where('id', $info['user_id'])->update(['level' => $info['up_level']]); // 会员升级
                return $this->jsonAdminResultWithLog($request);
            } else { // 审核失败
                $res = $mPayRecord->where('id', $id)->update(['status' => $status]);
                if ($res) {
                    return $this->jsonAdminResultWithLog($request);
                } else {
                    return $this->jsonAdminResult([],10001,'操作失败');
                }
            }
        }
    }
}
