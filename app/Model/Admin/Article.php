<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class Article extends Model
{
    use FormatTrait;
    public $table = 'articles';
}
