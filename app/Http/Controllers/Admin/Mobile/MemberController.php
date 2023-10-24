<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
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
            $level_list = config('global.level_list');
            $level_list = array_column($level_list, 'label', 'value');
            foreach ($data->items() as $k => $v) {
                if (!empty($v->avatar)) {
                    $data->items()[$k]['avatar'] = $urlPre . $v->avatar;
                }
                $data->items()[$k]['level_name'] = $level_list[$v->level] ?? '';
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }
}
