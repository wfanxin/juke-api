<?php

use App\User;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'user_name' => $faker->name,
        'password' => $faker->name,
        'salt' => $faker->name,
        'last_ip' => $faker->ipv4,
        'status' => 1,
        'error_amount' => 0,
        'roles' => $faker->name,
        'avatar' => $faker->name,
        'created_at' => $faker->dateTime(),
        'updated_at' => $faker->dateTime()
    ];
});
