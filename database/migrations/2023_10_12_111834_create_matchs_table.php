<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matchs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('ptag_id')->default(0)->comment('标签大类id');
            $table->string('tag_id',500)->default('')->comment('标签子类id');
            $table->string('match_play', 255)->default('')->comment('比赛场次');
            $table->string('match_score', 255)->default('')->comment('比分');
            $table->string('match_result', 255)->default('')->comment('比赛结果');
            $table->string('match_half_audience', 255)->default('')->comment('半全场');
            $table->string('match_type', 255)->default('')->comment('比赛类型');
            $table->text('match_data')->default('')->comment('比赛数据');
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
        Schema::dropIfExists('matchs;');
    }
}
