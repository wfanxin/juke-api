<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members;', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('pid')->default(0)->comment('上级id');
            $table->string('mobile', 20)->default('')->comment('手机号');
            $table->string('name', 255)->default('')->comment('姓名');
            $table->string('password', 255)->default('')->comment('密码');
            $table->string('salt', 255)->default('')->comment('密码盐值');
            $table->string('avatar', 500)->default('')->comment('头像');
            $table->tinyInteger('status')->default(0)->comment('状态：1：正常；2：锁定');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('members;');
    }
}
