<?php

namespace App\Console\Commands;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\PayRecord;
use Illuminate\Console\Command;


class PayRecordVerify extends Command
{
    use FormatTrait;

    protected $signature = 'PayRecordVerify';

    protected $description = '打款12小时自动审核';

    protected $expire_time = 12 * 60 * 60; // 12小时限制
    protected $mPayRecord = null;
    protected $mMember = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->_log('打款12小时自动审核处理开始');
        $this->mMember = new Member();
        $this->mPayRecord = new PayRecord();
        while (1) {
            $expire_date = date('Y-m-d H:i:s', time() - $this->expire_time);
            $list = $this->mPayRecord->where('status', '=', 0)->where('updated_at', '<', $expire_date)->orderBy('id', 'asc')->limit(10)->get();
            $list = $this->dbResult($list);

            if (empty($list)) {
                break; // 循环结束
            }

            foreach ($list as $value) {
                $this->doHandle($value);
            }
        }
        $this->_log('打款12小时自动审核处理结束');
    }

    public function doHandle($info) {
        if ($info['up_level'] == 0) { // 感恩奖，审核通过
            $pay_member_info = $this->mMember->where('id', $info['pay_uid'])->first();
            $pay_member_info = $this->dbResult($pay_member_info);

            $this->mPayRecord->where('id', $info['id'])->update(['status' => 1]); // 审核通过
            $this->mMember->where('id', $pay_member_info['id'])->update(['money' => $pay_member_info['money'] + $info['money']]); // 添加金额
            $this->mMember->where('id', $info['user_id'])->increment('thank_num', 1); // 添加感恩奖支付次数
            $this->_log('感恩奖' . $info['id']);
            $this->_log($info);
            return true;
        } else { // 升级，审核通过
            $pay_member_info = $this->mMember->where('id', $info['pay_uid'])->first();
            $pay_member_info = $this->dbResult($pay_member_info);

            $this->mPayRecord->where('id', $info['id'])->update(['status' => 1]); // 审核通过
            $this->mMember->where('id', $pay_member_info['id'])->update(['money' => $pay_member_info['money'] + $info['money']]); // 添加金额
            $this->mMember->where('id', $info['user_id'])->update(['level' => $info['up_level']]); // 会员升级
            $this->_log('升级' . $info['id']);
            $this->_log($info);
            return true;
        }
    }

    public function _log($data) {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(storage_path('logs/') . 'payRecordVerify-' . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . ':' . $data . "\n", FILE_APPEND);
    }
}
