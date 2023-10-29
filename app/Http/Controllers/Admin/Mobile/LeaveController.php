<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Api\Leave;
use Illuminate\Http\Request;

/**
 * @name 留言管理
 * Class LeaveController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("leaves")
 */
class LeaveController extends Controller
{
    use FormatTrait;

    /**
     * @name 留言列表
     * @Get("/lv/mobile/leave/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Leave $mLeave)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        if ($params['status'] != '') {
            $where[] = ['status', '=', $params['status']];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mLeave->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $urlPre = config('filesystems.disks.tmp.url');

            foreach ($data->items() as $k => $v){
                $data->items()[$k]['image_url'] = $urlPre . $v->image_url;
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 留言已处理
     * @Post("/lv/mobile/leave/handleStatus")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function handleStatus(Request $request, Leave $mLeave)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mLeave->where('id', $id)->update(['status' => 1]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
