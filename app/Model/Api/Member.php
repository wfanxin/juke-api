<?php

namespace App\Model\Api;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use FormatTrait;
    public $table = 'members';

    /**
     * 是否满足感恩奖条件
     * @param $user_id
     * @return bool
     */
    public function isThank($user_id) {
        $info = $this->where('id', $user_id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return false;
        }

        if ($info['system'] == 1) { // 系统会员直接通过
            return true;
        }

        $mConfig = new Config();
        $award = $mConfig->where('name', 'award')->first();
        $award = $this->dbResult($award);
        if (empty($award)) {
            return true;
        }

        $award = json_decode($award['content'], true) ?? [];
        if (empty($award)) {
            return true;
        }

        if (empty($award['value'])) {
            return true;
        }

        $money = floatval($award['value']);
        if ($money <= 0) {
            return true;
        }

        return intval($info['money'] / $money) <= $info['thank_num'];
    }

    /**
     * 获取等级配置
     * @return array
     */
    public function getLevelList() {
        $level_list = config('global.level_list');

        $count = count($level_list);
        $level_item = $level_list[$count - 1]; // 获取最后一项

        while ($count <= 20) {
            $level_item['value'] = $count;
            $level_list[$count] = $level_item;
            $count++;
        }

        $level_list = array_column($level_list, 'label', 'value');

        return $level_list;
    }

    /**
     * 注册获取上级id
     * @param $invite_uid
     * @return mixed
     */
    public function getPuid($invite_uid) {
        $list = $this->where('id', $invite_uid)->get();
        $list = $this->dbResult($list);
        while (1) {
            $temp_list = [];
            foreach ($list as $value) {
                $child_list = $this->where('p_uid', $value['id'])->get();
                $child_list = $this->dbResult($child_list);
                if (count($child_list) < 2) { // 找到了
                    return $value['id'];
                }

                $temp_list = array_merge($temp_list, $child_list);
            }
            $list = $temp_list;
        }
    }

    /**
     * 获取理论上升级需要打款的上级
     * @param $user_id
     * @param $level
     * @return mixed
     */
    public function getUpLevelPUid($user_id, $level) {
        $p_uid = $user_id;
        while ($level > 0) {
            $p_uid = $this->where('id', $p_uid)->value('p_uid');
            $level--;
        }

        return $p_uid;
    }

    /**
     * 获取理论上感恩人
     * @param $user_id
     * @return mixed
     */
    public function getThankInviteUid($user_id) {
        return $this->where('id', $user_id)->value('invite_uid');
    }
}
