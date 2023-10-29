<?php

namespace App\Model\Api;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use FormatTrait;
    public $table = 'members';

    public function isThank($user_id) {
        $info = $this->where('id', $user_id)->first();
        $info = $this->dbResult($info);

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
}
