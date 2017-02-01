<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'username' => str_random(3) . '.' . str_random(5),
        'email' => str_random(5) . '@' . str_random(5) . '.com',
        'password' => '12345',
        'fullname' => $faker->firstName . ' ' . $faker->lastName,
        'user_group_id' => 5,
        'status_id' => 1,
        'expired_at' => Carbon::now(),
        'remember_token' => str_random(10),
    ];
});
