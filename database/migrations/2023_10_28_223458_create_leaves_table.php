<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('会员id');
            $table->text('remark')->default('')->comment('留言内容');
            $table->string('image_url', 500)->default(0)->comment('图片');
            $table->tinyInteger('status')->default(0)->comment('状态：0、未处理，1、已处理');
            $table->bigInteger('handle_user_id')->default(0)->comment('处理人id');
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
        Schema::dropIfExists('leaves');
    }
}
