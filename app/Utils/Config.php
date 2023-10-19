<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

/**
 * 系统配置相关操作函数
 * Class Config
 * @package App\Utils
 */
class Config
{
    public function update($name, $value)
    {
        try {
            if ( empty($name) ) {
                return false;
            }

            $file = app_path("../").".env";
            $env = file_get_contents($file);

            $newEnv = preg_replace("/{$name}.*?\n/i", "{$name}={$value}\n", $env);
            return file_put_contents($file, $newEnv);
        } catch (\Exception $exception) {
            if (config('app.debug')) {
                echo "[ERROR] => ";
                echo $exception->getFile()." line ".$exception->getLine()."<br/>";
                echo $exception->getMessage()."<br/>";
            } else {
                Log::error(null, [
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getMessage()
                ]);
            }

            return false;
        }
    }
}