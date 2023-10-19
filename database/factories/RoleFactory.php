<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Role;
use Faker\Generator as Faker;

$factory->define(Role::class, function (Faker $faker) {
    return [
        //
        'name' => $faker->text,
        'permission' => $faker->text,
        'created_at' => $faker->dateTime(),
        'updated_at' => $faker->dateTime()
    ];
});
