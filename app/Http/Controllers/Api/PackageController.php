<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Package;
use Illuminate\Http\Request;

/**
 * 丢包记录
 */
class PackageController extends Controller
{
    use FormatTrait;

    /**
     * 感恩奖审核记录
     * @param Request $request
     */
    public function getPackageList(Request $request, Member $mMember, Package $mPackage)
    {
        $list = $mPackage->where('pay_uid', $request->memId)->get();
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
}
