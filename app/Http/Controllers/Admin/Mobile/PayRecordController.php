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

        $where[] = ['status', '=', 1];

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
}
