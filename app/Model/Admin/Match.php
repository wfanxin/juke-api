<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class Match extends Model
{
    use FormatTrait;
    public $table = 'matchs';
}
