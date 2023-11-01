<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0)->comment('会员id');
            $table->tinyInteger('up_level')->default(0)->comment('会员等级');
            $table->bigInteger('pay_uid')->default(0)->comment('收款会员id');
            $table->tinyInteger('pay_method')->default(0)->comment('付款方式');
            $table->string('pay_url', 500)->default('')->comment('付款凭证');
            $table->decimal('money', 8, 2)->default(0)->comment('付款金额');
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
        Schema::dropIfExists('packages');
    }
}
