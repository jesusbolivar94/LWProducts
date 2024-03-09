<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         \App\Models\User::factory()->create([
             'name' => 'Superadmin',
             'email' => 'j5rojo94@gmail.com',
             "password" => bcrypt('password'),
             "remember_token" => Str::random(10),
         ]);
    }
}
