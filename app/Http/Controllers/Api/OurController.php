<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\PayRecord;
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
    public function getOurLevelNum(Request $request, Member $mMember, PayRecord $mPayRecord)
    {
        $total_num = 0;
        $data = [];
        $level = 1;
        $p_uids = [$request->memId];
        $all_users = [];
        while ($level <= 20) {
            $list = $mMember->whereIn('p_uid', $p_uids)->get(['id','level']);
            $list = $this->dbResult($list);
            $data[] = [
                'level' => $level,
                'num' => count($list)
            ];
            $all_users = array_merge($all_users, $list);

            $p_uids = array_column($list, 'id');
            $level++;
        }

        foreach ($data as $value) {
            $total_num += $value['num'];
        }

        $active_num = 0;
        $all_ids = [];
        foreach ($all_users as $value) {
            if ($value['level'] >= 4) {
                $all_ids[] = $value['id'];
                $active_num++;
            }
        }

        $today_active_num = $mPayRecord->where('status', 1)
            ->where('up_level', 4)
            ->whereIn('user_id', $all_ids)
            ->where('updated_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        $invite_num = $mMember->where('invite_uid', $request->memId)->count();

        return $this->jsonAdminResult([
            'data' => $data,
            'total_num' => $total_num,
            'active_num' => $active_num,
            'today_active_num' => $today_active_num,
            'invite_num' => $invite_num
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
