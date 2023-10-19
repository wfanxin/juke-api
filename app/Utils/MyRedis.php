<?php
namespace App\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MyRedis
{
    protected $options;

    public function __construct()
    {

    }

    public static function set($key, $value)
    {
        $info = DB::table('redis_datas')->where('key', $key)->first();
        $info = json_decode(json_encode($info), true);
        $time = date('Y-m-d H:i:s');
        if (empty($info)) { // 新增
            DB::table('redis_datas')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $time,
                'updated_at' => $time
            ]);
        } else { // 更新
            DB::table('redis_datas')->where('key', $key)->update([
                'value' => $value,
                'updated_at' => $time
            ]);
        }
    }
    
    public static function get($key)
    {
        $info = DB::table('redis_datas')->where('key', $key)->first();
        $info = json_decode(json_encode($info), true);
        return $info['value'] ?? null;
    }
    
    
    public static function hmset($key, $value)
    {
        $info = DB::table('redis_datas')->where('key', $key)->first();
        $info = json_decode(json_encode($info), true);
        $time = date('Y-m-d H:i:s');
        if (empty($info)) { // 新增
            DB::table('redis_datas')->insert([
                'key' => $key,
                'value' => json_encode($value),
                'created_at' => $time,
                'updated_at' => $time
            ]);
        } else { // 更新
            DB::table('redis_datas')->where('key', $key)->update([
                'value' => json_encode($value),
                'updated_at' => $time
            ]);
        }
    }
    
    public static function hgetall($key)
    {
        $info = DB::table('redis_datas')->where('key', $key)->first();
        $info = json_decode(json_encode($info), true);
        if (empty($info)) {
            return null;
        } else {
            return json_decode($info['value'], true);
        }
    }
    
    
    
    public static function expire($key, $seconds)
    {
        $expire = date('Y-m-d H:i:s', time() + $seconds);
        $time = date('Y-m-d H:i:s');
        DB::table('redis_datas')->where('key', $key)->update([
            'expire' => $expire,
            'updated_at' => $time
        ]);
    }
    
    public static function del($key)
    {
        DB::table('redis_datas')->where('key', $key)->delete();
    }
}