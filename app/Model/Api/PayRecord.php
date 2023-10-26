<?php

namespace App\Model\Api;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class PayRecord extends Model
{
    use FormatTrait;
    public $table = 'pay_records';
}
