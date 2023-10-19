<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('op_uid', 255)->comment("操作人员id");
            $table->ipAddress('ip', 255)->comment("操作人员ip")->default('');
            $table->string('name', 255)->comment("操作权限")->default('');
            $table->text('request')->comment("操作信息");
            $table->text('response')->comment("操作结果");
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
        Schema::dropIfExists('logs');
    }
}
