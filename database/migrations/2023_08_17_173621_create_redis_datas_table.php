<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedisDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redis_datas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 500)->comment('键');
            $table->longText('value')->comment('值');
            $table->dateTime('expire')->comment('有效时间');
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::statement("alter table `redis_datas` comment '模拟redis表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('redis_datas');
    }
}
