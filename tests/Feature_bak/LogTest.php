<?php

namespace Tests\Feature;

use App\Log;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * 登录
     */
    public function testlogin()
    {
        $base = new Base();
        return $base::testlogin($this);
    }

    /**
     * 获取列表
     *
     * @depends testlogin
     */
    public function testIndex()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $log1 = \factory(Log::class)->create([
            "op_uid" => "1",
            "ip" => "192.168.2.1",
            "request" => serialize("123"),
            "response" => serialize("321"),
            'created_at' => $time,
            'updated_at' => $time
        ])->toArray();
        $log2 = \factory(Log::class)->create([
            "op_uid" => "1",
            "ip" => "192.168.2.1",
            "request" => serialize("2123"),
            "response" => serialize("2321"),
            'created_at' => $time,
            'updated_at' => $time
        ])->toArray();

        $day = date("Y-m-d");
        $response = $this->get("/lv/logs?user_name=admin&selectTime[]={$day}+00:00:00&selectTime[]={$day}+23:59:59&permission=&page=0", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
            "total" => 2,
            "logs" => [[
                "name" => "admin",
                "op_uid" => "1",
                "ip" => "192.168.2.1",
                "request" => "123",
                "response" => "321",
                'created_at' => $time,
                'updated_at' => $time
            ],[
                "name" => "admin",
                "op_uid" => "1",
                "ip" => "192.168.2.1",
                "request" => "2123",
                "response" => "2321",
                'created_at' => $time,
                'updated_at' => $time
            ]],
        ]);
    }
}
