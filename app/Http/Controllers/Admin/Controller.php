<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

class Controller extends BaseController
{
    use DispatchesJobs, ValidatesRequests;
    // api结果格式化
    use \App\Http\Traits\ResponseTrait;
    use \App\Http\Traits\EncodeTrait;

    /**
     * 密码盐值
     * @var string
     */
    protected $_salt = '';

    public $userId = 0;

    protected $user = [];
}
