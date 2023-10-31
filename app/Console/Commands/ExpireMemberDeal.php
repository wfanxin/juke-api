<?php

namespace App\Console\Commands;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use Illuminate\Console\Command;


class ExpireMemberDeal extends Command
{
    use FormatTrait;

    protected $signature = 'ExpireMemberDeal';

    protected $description = '会员24小时未升级到4级处理';

    protected $expire_time = 12 * 60 * 60; // 24小时限制
    protected $mMember = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
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
    }

    public function doHandle($data) {
        $child_list = $this->mMember->where('p_uid', $data['id']);
        $child_list = $this->dbResult($child_list);
        if (empty($child_list)) { // 没有子节点，直接删除
            var_dump(0);
            return true;
        }

        if (count($child_list) == 1) { // 有1个子节点，删除后，子节点顶替他的位置
            var_dump(1);
            return true;
        }

        if (count($child_list) > 1) { // 有大于1个子节点，删除后，左子节点顶替他的位置，其他节点到左子节点下面
            var_dump(2);
            return true;
        }
    }

    public function _log($data) {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(storage_path('logs/') . 'expireMemberDeal-' . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . ':' . $data . "\n", FILE_APPEND);
    }
}
