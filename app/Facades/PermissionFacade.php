<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 后台权限相关
 * Class PermissionFacade
 * @package App\Facades
 *
 * @see \App\Utils\Permission
 */
class PermissionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "\App\Utils\Permission";
    }
}