<?php

namespace Tests\Feature;
use Tests\CreatesApplication;
use Tests\TestCase;

class Base extends TestCase{
    /**
     * 测试登录
     * @param $test
     * @return mixed
     */
    public static function testlogin($test)
    {
        $_SERVER['REQUEST_URI'] = 'lv/tokens';
        $_SERVER['REQUEST_METHOD'] = 'post';
        $response = $test->post("lv/tokens", [
            "user_name" => "admin",
            "password" => "123"
        ], ["Accept" => "application/vnd..v1+json"]);

        $response->assertJson([
            "code" => 10001,
            "message" => "用户名或密码错误",
        ]);

        $response = $test->post("lv/tokens", [
            "user_name" => "admin",
            "password" => "Aa12345678"
        ], ["Accept" => "application/vnd..v1+json"]);

        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);

        return json_decode($response->getContent(), true);
    }
}