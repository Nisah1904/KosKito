<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CampusSeeder::class);

        User::firstOrCreate(
            ['email' => 'admin@kostly'],
            [
                'name' => 'Admin Kostly',
                'password' => env('ADMIN_INITIAL_PASSWORD', 'kostlyadmin123'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        $this->call(WebKostSeeder::class);
    }
}
