<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 系统配置相关操作函数
 * Class ConfigFacade
 * @package App\Facades
 *
 * @see \App\Utils\Config
 */
class ConfigFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return "\App\Utils\Config";
    }
}