<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Log;
use App\Model;
use Faker\Generator as Faker;

$factory->define(Log::class, function (Faker $faker) {
    return [
        //
        "op_uid" => $faker->randomNumber(),
        "ip" => $faker->ipv4,
        "request" => $faker->name(),
        "response" => $faker->name(),
        'created_at' => $faker->dateTime(),
        'updated_at' => $faker->dateTime()
    ];
});
