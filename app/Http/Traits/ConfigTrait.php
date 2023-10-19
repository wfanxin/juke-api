<?php

namespace App\Http\Traits;


use App\Model\Member\MemberOnlineDetail;
use App\Model\Member\MemberRechargeRecord;
use App\Model\Member\SettingInviteReward;
use Illuminate\Support\Facades\DB;

/**
 * 格式化
 * Class FormatTrait
 * @package App\Http\Traits
 */
trait ConfigTrait {
    /**
     * 允许多登的数量
     * @param $memId
     * @return int
     */
    public function loginNum($memId){
      $mMemberOnlineDetail = new MemberOnlineDetail();
      $time = date('Y-m-d H:i:s');
      $num = 1;
     $res = $mMemberOnlineDetail
          ->where('mem_id',$memId)
          ->where('start_date','<=',$time)
          ->where('end_date','>=',$time)
          ->get(['num']);
        $res = json_decode(json_encode($res),true);
        if (count($res)){
            foreach ($res as $v){
                $num =  $v['num'] + $num;
            }
        }
        return $num;
    }

    /**
     * 多人使用信息
     * @param $memId
     * @return int
     */
    public function loginDetails($memId){
        $mMemberOnlineDetail = DB::table('member_online_details')
            ->select('start_date', 'end_date', 'num')
            ->where('end_date',">=", date("Y-m-d H:i:s"))
            ->where('mem_id', $memId)
            ->get();
        $mMemberOnlineDetail = json_decode(json_encode($mMemberOnlineDetail),true);
        return $mMemberOnlineDetail;
    }


    /**
     * 邀新奖励
     * @return int
     */
    public function inviteReward(){
      $mSettingInviteReward = new SettingInviteReward();
       $data = $mSettingInviteReward->first();
        return [
            'vip_days' => $data['vip_days'],
            'pixel_nums' => $data['pixel_nums']
        ];
    }



}
