<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;

class UserTest extends TestCase
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
     * 获取
     * @depends testlogin
     */
    public function testIndex()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $user1 = \factory(User::class)->create([
            'name' => "单元测试用户1",
            'user_name' => "user1",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();
        $user2 = \factory(User::class)->create([
            'name' => "单元测试用户2",
            'user_name' => "user2",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $_SERVER['REQUEST_URI'] = 'lv/users';
        $_SERVER['REQUEST_METHOD'] = 'get';
        $response = $this->get("/lv/users?status=1&page=0&name=单元测试用户", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
            'total' => 2,
            'users' => [[
                'name' => "单元测试用户2",
                'user_name' => "user2",
                'password' => "12312312",
                'salt' => "123213123",
                'last_ip' => "192.168.2.1",
                'status' => 1,
                'error_amount' => 0,
                'roles' => "[]",
                'avatar' => "",
                "created_at" => $time,
                "updated_at" => $time
            ],[
                'name' => "单元测试用户1",
                'user_name' => "user1",
                'password' => "12312312",
                'salt' => "123213123",
                'last_ip' => "192.168.2.1",
                'status' => 1,
                'error_amount' => 0,
                'roles' => "[]",
                'avatar' => "",
                "created_at" => $time,
                "updated_at" => $time
            ]]
        ]);
    }

    /**
     * 添加用户
     * @depends testlogin
     */
    public function testStore()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $_SERVER['REQUEST_URI'] = 'lv/users';
        $_SERVER['REQUEST_METHOD'] = 'post';
        $response = $this->post("/lv/users", [
            "name" => "name",
            "user_name" => "user_name",
            "password" => "password",
            "user_roles" => "[]"
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success"
        ]);
    }

    /**
     * 获取当前登录用户信息
     *
     * @depends testlogin
     */
    public function testShow()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $_SERVER['REQUEST_URI'] = 'lv/users/1';
        $_SERVER['REQUEST_METHOD'] = 'get';
        $response = $this->get("/lv/users/1", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
            "name" => "admin",
            "roles" => [
                "admin"
            ],
            "permissions" => []
        ]);

        $_SERVER['REQUEST_URI'] = 'lv/users/0';
        $_SERVER['REQUEST_METHOD'] = 'get';
        $response = $this->get("/lv/users/0", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
        ]);
    }

    /**
     * 更新用户信息
     *
     * @depends testlogin
     */
    public function testUpdate()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $user = \factory(User::class)->create([
            'name' => "单元测试编辑",
            'user_name' => "user1",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $_SERVER['REQUEST_URI'] = "lv/users/{$user['id']}";
        $_SERVER['REQUEST_METHOD'] = 'put';
        $response = $this->put("/lv/users/{$user['id']}", [
            "name" => "单元测试编辑2",
            "user_name" => "user1editor",
            "status" => "0",
            "user_roles" => "[]",
            "password" => "12312213",
            "re_password" => "12312213",
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success"
        ]);

        $_SERVER['REQUEST_URI'] = "lv/users/0";
        $_SERVER['REQUEST_METHOD'] = 'put';
        $response = $this->put("/lv/users/0", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
        ]);
    }

    /**
     * 删除指定用户信息
     *
     * @depends testlogin
     */
    public function testDestroy()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $user = \factory(User::class)->create([
            'name' => "单元测试删除",
            'user_name' => "user1",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $_SERVER['REQUEST_URI'] = "lv/users/{$user['id']}";
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $response = $this->delete("/lv/users/{$user['id']}", [], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success"
        ]);

        $_SERVER['REQUEST_URI'] = "lv/users/0";
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $response = $this->delete("/lv/users/0", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
        ]);
    }

    /**
     * 批量删除用户信息
     *
     * @depends testlogin
     */
    public function testBatchDestroy()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $user1 = \factory(User::class)->create([
            'name' => "单元测试删除1",
            'user_name' => "user1",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();
        $user2 = \factory(User::class)->create([
            'name' => "单元测试删除2",
            'user_name' => "user1",
            'password' => "12312312",
            'salt' => "123213123",
            'last_ip' => "192.168.2.1",
            'status' => 1,
            'error_amount' => 0,
            'roles' => "[]",
            'avatar' => "",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $ids = implode(",", [
            $user1['id'],
            $user2['id']
        ]);

        $_SERVER['REQUEST_URI'] = "lv/users/batch";
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $response = $this->delete("/lv/users/batch", [
            "ids" => $ids
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success"
        ]);

        $response = $this->delete("/lv/users/batch", [
            "ids" => ""
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
        ]);
    }

    /**
     * 退出登录
     *
     * @depends testlogin
     */
    public function testLogout()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $_SERVER['REQUEST_URI'] = "lv/tokens/1";
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $response = $this->delete("/lv/tokens/1", [],["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success"
        ]);

        $_SERVER['REQUEST_URI'] = "lv/tokens/0";
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $response = $this->delete("/lv/tokens/0", [],["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
        ]);
    }
}
