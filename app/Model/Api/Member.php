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

    public function delMember($data) {
        $child_list = $this->where('p_uid', $data['id'])->get();
        $child_list = $this->dbResult($child_list);

        if (empty($child_list)) { // 没有子节点，直接删除
            // 直接删除
            $this->where('id', $data['id'])->delete();
            return true;
        }

        if (count($child_list) == 1) { // 有1个子节点，删除后，子节点顶替他的位置
            // 直接删除
            $this->where('id', $data['id'])->delete();

            // 子节点顶替他的位置
            $this->where('id', $child_list[0]['id'])->update(['p_uid' => $data['p_uid']]);
            return true;
        }

        if (count($child_list) > 1) { // 有大于1个子节点，删除后，左子节点顶替他的位置，其他节点到左子节点下面
            // 直接删除
            $this->where('id', $data['id'])->delete();

            // 子节点顶替他的位置
            $this->where('id', $child_list[0]['id'])->update(['p_uid' => $data['p_uid']]);

            $left_child = $child_list[0]; // 左子节点
            unset($child_list[0]); // 其他节点

            // 其他节点到左子节点下面
            foreach ($child_list as $other) {
                $p_uid = $this->getPuid($left_child['id']);
                $this->where('id', $other['id'])->update(['p_uid' => $p_uid]);
            }
            return true;
        }
    }

    public function getChildren($p_uid, $invite_uid, $root, $level) {
        if ($level < 0) {
            return [];
        }

        $list = [];
        if ($root) { // 跟节点
            $list = $this->where('p_uid', $p_uid)->where('id', $invite_uid)->get();
        } else {
            $list = $this->where('p_uid', $p_uid)->get();
        }
        $list = $this->dbResult($list);

        $children = [];
        $level_list = $this->getLevelList();
        foreach ($list as $value) {
            $inviteNum = $this->where('invite_uid', $value['id'])->count();
            $childList = $this->getChildren($value['id'], $invite_uid, false, --$level);

            // 层级计算和宽度计算
            $deep = 0;
            $widthNum = 0;
            if (!empty($childList)) {
                foreach ($childList as $child) {
                    $deep = $child['deep'] > $deep ? $child['deep'] : $deep;
                    $widthNum += $child['widthNum'];
                }
            } else {
                $widthNum = 1;
            }
            $deep++;

            // 节点颜色
            $itemStyle = ['color' => '#1e9fff', 'borderColor' => '#1e9fff'];
            if ($value['invite_uid'] == $invite_uid) {
                $itemStyle = ['color' => '#e96161', 'borderColor' => '#e96161'];
            }

            $children[] = [
                'name' => $value['name'] . '|' . $value['mobile'] . '|直推人数:' . $inviteNum . '|收益:' . $value['money'] . '|等级:' . ($level_list[$value['level']] ?? ''),
                'deep' => $deep,
                'widthNum' => $widthNum,
                'itemStyle' => $itemStyle,
                'children' => $childList
            ];
        }
        return $children;
    }
}
