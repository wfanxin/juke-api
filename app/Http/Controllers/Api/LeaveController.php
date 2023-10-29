<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Config;
use App\Model\Api\Leave;
use App\Model\Api\Member;
use Illuminate\Http\Request;

/**
 * 留言管理
 */
class LeaveController extends Controller
{
    use FormatTrait;

    /**
     * 添加留言
     * @param Request $request
     */
    public function add(Request $request, Leave $mLeave)
    {
        $params = $request->all();

        $remark = $params['remark'] ?? '';
        $image_url = $params['image_url'] ?? '';

        if (empty($remark)) {
            return $this->jsonAdminResult([],10001,'留言内容不能为空');
        }

        if (empty($image_url)) {
            return $this->jsonAdminResult([],10001,'图片不能为空');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $image_url = str_replace($urlPre, '', $image_url);

        $time = date('Y-m-d H:i:s');
        $res = $mLeave->insert([
            'user_id' => $request->memId,
            'remark' => $remark,
            'image_url' => $image_url,
            'created_at' => $time,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 添加留言
     * @param Request $request
     */
    public function list(Request $request, Leave $mLeave)
    {
        $list = $mLeave->where('user_id', $request->memId)->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            $urlPre = config('filesystems.disks.tmp.url');
            foreach ($list as $key => $value) {
                if (!empty($value['image_url'])) {
                    $list[$key]['image_url'] = $urlPre . $value['image_url'];
                }
            }
        }

        return $this->jsonAdminResult(['list' => $list]);
    }
}
