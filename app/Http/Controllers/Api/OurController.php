<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use Illuminate\Http\Request;

/**
 * 同修
 */
class OurController extends Controller
{
    use FormatTrait;

    /**
     * 同修层级用户数量
     * @param Request $request
     */
    public function getOurLevelNum(Request $request, Member $mMember)
    {
        $total_num = 0;
        $data = [];
        $level = 1;
        $p_uids = [$request->memId];
        while ($level <= 20) {
            $list = $mMember->whereIn('p_uid', $p_uids)->get(['id']);
            $list = $this->dbResult($list);
            $data[] = [
                'level' => $level,
                'num' => count($list)
            ];

            $p_uids = array_column($list, 'id');
            $level++;
        }

        foreach ($data as $value) {
            $total_num += $value['num'];
        }

        return $this->jsonAdminResult([
            'data' => $data,
            'total_num' => $total_num
        ]);
    }

    /**
     * 同修层级用户数量
     * @param Request $request
     */
    public function getGroupList(Request $request, Member $mMember)
    {
        $params = $request->all();

        $target_level = $params['level'] ?? 0;
        if (empty($target_level)) {
            return $this->jsonAdminResult([
                'data' => []
            ]);
        }

        $data = [];
        $level = 1;
        $p_uids = [$request->memId];
        while ($level <= 20) {
            $list = $mMember->whereIn('p_uid', $p_uids)->get();
            $list = $this->dbResult($list);

            if ($level == $target_level) { // 找到了
                $data = $list;
                break;
            }

            $p_uids = array_column($list, 'id');
            $level++;
        }

        if (!empty($data)) {
            $urlPre = config('filesystems.disks.tmp.url');
            $invite_uids = array_column($data, 'invite_uid');
            $list = $mMember->whereIn('id', $invite_uids)->get();
            $list = $this->dbResult($list);
            $list = array_column($list, 'name', 'id');
            $level_list = $mMember->getLevelList();
            foreach ($data as $key => $value) {
                $value['invite_name'] = $list[$value['invite_uid']] ?? '无';
                $value['level_name'] = $level_list[$value['level']] ?? '';
                if (!empty($value['avatar'])) {
                    $value['avatar'] = $urlPre . $value['avatar'];
                }
                unset($value['password']);
                unset($value['salt']);
                $data[$key] = $value;
            }
        }

        return $this->jsonAdminResult([
            'data' => $data
        ]);
    }
}
