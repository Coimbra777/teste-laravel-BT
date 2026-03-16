<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@admin.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::create([
            'email' => 'manager@test.com',
            'password' => 'password',
            'role' => 'manager',
        ]);

        User::create([
            'email' => 'finance@test.com',
            'password' => 'password',
            'role' => 'finance',
        ]);

        User::create([
            'email' => 'user@test.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        Gateway::create([
            'name' => 'gateway_one',
            'priority' => 1,
            'is_active' => true,
        ]);

        Gateway::create([
            'name' => 'gateway_two',
            'priority' => 2,
            'is_active' => true,
        ]);

        Product::create(['name' => 'Product A', 'amount' => 5000]);
        Product::create(['name' => 'Product B', 'amount' => 10000]);
        Product::create(['name' => 'Product C', 'amount' => 2500]);
    }
}
