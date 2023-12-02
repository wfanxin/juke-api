<?php

namespace App\Console\Commands;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class ExpireMemberDeal extends Command
{
    use FormatTrait;

    protected $signature = 'ExpireMemberDeal';

    protected $description = '会员24小时未升级到4级处理';

    protected $expire_time = 24 * 60 * 60; // 24小时限制
    protected $mMember = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->_log('会员24小时未升级到4级处理开始');
        $this->mMember = new Member();
        while (1) {
            $expire_date = date('Y-m-d H:i:s', time() - $this->expire_time);
            $list = $this->mMember->where('level', '<', 4)->where('created_at', '<', $expire_date)->orderBy('id', 'desc')->limit(10)->get();
            $list = $this->dbResult($list);

            if (empty($list)) {
                break; // 循环结束
            }

            foreach ($list as $value) {
                $this->doHandle($value);
            }
        }
        $this->_log('会员24小时未升级到4级处理结束');
    }

    public function doHandle($data) {
        $child_list = $this->mMember->where('p_uid', $data['id'])->get();
        $child_list = $this->dbResult($child_list);
        if (empty($child_list)) { // 没有子节点，直接删除
            // 直接删除
            $this->mMember->where('id', $data['id'])->delete();
            $this->_log('删除用户' . $data['id']);
            $this->_log($data);
            $this->add_log($data);
            return true;
        }

        if (count($child_list) == 1) { // 有1个子节点，删除后，子节点顶替他的位置
            // 直接删除
            $this->mMember->where('id', $data['id'])->delete();
            $this->_log('删除用户' . $data['id']);
            $this->_log($data);
            $this->add_log($data);

            // 子节点顶替他的位置
            $this->mMember->where('id', $child_list[0]['id'])->update(['p_uid' => $data['p_uid']]);
            return true;
        }

        if (count($child_list) > 1) { // 有大于1个子节点，删除后，左子节点顶替他的位置，其他节点到左子节点下面
            // 直接删除
            $this->mMember->where('id', $data['id'])->delete();
            $this->_log('删除用户' . $data['id']);
            $this->_log($data);
            $this->add_log($data);

            // 子节点顶替他的位置
            $this->mMember->where('id', $child_list[0]['id'])->update(['p_uid' => $data['p_uid']]);

            $left_child = $child_list[0]; // 左子节点
            unset($child_list[0]); // 其他节点

            // 其他节点到左子节点下面
            foreach ($child_list as $other) {
                $p_uid = $this->mMember->getPuid($left_child['id']);
                $this->mMember->where('id', $other['id'])->update(['p_uid' => $p_uid]);
            }
            return true;
        }
    }

    public function _log($data) {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(storage_path('logs/') . 'expireMemberDeal-' . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . ':' . $data . "\n", FILE_APPEND);
    }

    public function add_log($data) {
        $time = date('Y-m-d H:i:s');
        DB::table('redis_datas')->insert([
            'key' => $data['mobile'],
            'value' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => $time,
            'updated_at' => $time
        ]);
    }
}
