<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger("is_white")->default(0)->comment("是否为白名单权限：0:否；1:是");
            $table->string("path", 150)->comment("访问路径");
            $table->string("name")->comment("权限名称");
            $table->integer("p_id")->comment("父级权限");
            $table->string("id_path")->default(0)->comment("级联关系");
            $table->integer("is_show")->default(0)->comment("是否为栏目0：否；1：是");
            $table->timestamps();
            $table->unique("path");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
    }
}
