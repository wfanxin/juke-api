<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadTest extends TestCase
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
     * 上传文件
     *
     * @depends testlogin
     * @return void
     */
    public function testStore()
    {
        $arguments = func_get_args();
        $xToken = $arguments[0]["token"];
        Storage::fake('upload');

        $response = $this->post('/lv/uploads', [
            "upload_file" => UploadedFile::fake()->image('avatar.jpg')
        ], ["X-Token" => $xToken, "Accept" => "application/vnd..v1+json"]);
        $result = json_decode($response->getContent(), true);
        $file = str_replace("/u/", "/", $result["message"]);

        // 断言文件保存...
        Storage::disk('upload')->assertExists($file);

        // 断言文件不存在...
        Storage::disk('upload')->assertMissing('missing.jpg');
    }
}
