<?php

namespace Tests\Feature;

use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testHome()
    {
        $response = $this->get("/");

        $response->assertStatus(200)
            ->assertJson([
                "code" => 0,
                "message" => "success",
            ]);
    }

    /**
     * 登录
     */
    public function testlogin()
    {
        $base = new Base();
        return $base::testlogin($this);
    }

    /**
     * 获取全部角色
     *
     * @depends testlogin
     */
    public function testTotal()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $role1 = \factory(Role::class)->create([
            "name" => "单元测试-total1",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();
        $role2 = \factory(Role::class)->create([
            "name" => "单元测试-total2",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $response = $this->get("/lv/roles/total", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
            "roles" => [[
                "id" => 1,
                "name" => "超级管理员"
            ],[
                "id" => $role1["id"],
                "name" => "单元测试-total1"
            ],[
                "id" => $role2["id"],
                "name" => "单元测试-total2"
            ]]
        ]);
    }

    /**
     * 角色列表
     * @depends testlogin
     */
    public function testRoleList()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $response = $this->get("/lv/roles?page=1&name=", ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);

        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);

//        @unlink(app_path("../public/RoleList.html"));
//        file_put_contents(app_path("../public/RoleList.html"),$response->getContent());
//        var_dump($xToken);
    }

    /**
     * @depends testlogin
     */
    public function testRoleAdd()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        $role = [
            "name" => "单元测试",
            "rolePermissions" => [],
            "created_at" => $time,
            "updated_at" => $time
        ];
        $response = $this->post("/lv/roles", $role, ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);

        $mysqlRole = [
            "name" => "单元测试"
        ];
        $this->assertDatabaseHas("roles", $mysqlRole);

//        @unlink(app_path("../public/RoleAdd.html"));
//        file_put_contents(app_path("../public/RoleAdd.html"),$response->getContent());
//        var_dump($xToken);
    }

    /**
     * 角色编辑
     * @depends testlogin
     */
    public function testRoleEdit()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        //创建fake数据
        $role = \factory(Role::class)->create([
            "name" => "单元测试-编辑",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $response = $this->put("/lv/roles/{$role["id"]}", [
            "name" => "单元测试-编辑2",
            "rolePermissions" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);

        $response = $this->put("/lv/roles/0", [
            "name" => "单元测试-编辑3",
            "rolePermissions" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
            "data" => []
        ]);
    }

    /**
     * 角色删除
     * @depends testlogin
     */
    public function testRoleRemove()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        //创建fake数据
        $role = \factory(Role::class)->create([
            "name" => "单元测试-删除",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $response = $this->delete("/lv/roles/{$role["id"]}", [], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);

        $response = $this->delete("/lv/roles/0", [
            "name" => "单元测试-删除2",
            "rolePermissions" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
            "data" => []
        ]);
    }

    /**
     * 角色批量删除
     * @depends testlogin
     */
    public function testRoleBatchRemove()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];

        $time = date("Y-m-d H:i:s");
        //创建fake数据
        $role1 = \factory(Role::class)->create([
            "name" => "单元测试-批量删除1",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();
        $role2 = \factory(Role::class)->create([
            "name" => "单元测试-批量删除2",
            "permission" => "[]",
            "created_at" => $time,
            "updated_at" => $time
        ])->toArray();

        $ids = implode(",", [
            $role1['id'],
            $role2['id']
        ]);

        $response = $this->delete("/lv/roles/batch", [
            "ids" => $ids
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 0,
            "message" => "success",
        ]);


        $response = $this->delete("/lv/roles/batch", [
            "ids" => ""
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $response->assertJson([
            "code" => 10002,
            "message" => "参数错误",
            "data" => []
        ]);
    }
}
