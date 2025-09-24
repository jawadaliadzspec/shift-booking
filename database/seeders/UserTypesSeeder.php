<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'user_type' => 'admin',
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '+1-234-567-8901',
        ]);

        \App\Models\User::factory(3)->create([
            'user_type' => 'employee',
            'full_name' => fn() => fake()->name(),
            'phone' => fn() => fake()->phoneNumber(),
            'hourly_rate' => fn() => fake()->randomFloat(2, 10, 100),
        ]);

        \App\Models\User::factory(5)->create([
            'user_type' => 'customer',
            'full_name' => fn() => fake()->name(),
            'phone' => fn() => fake()->phoneNumber(),
        ]);
    }
}
