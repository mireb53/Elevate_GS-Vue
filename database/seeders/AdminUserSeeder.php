<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Only create if not exists
        $email = 'admin@elevategs.com';
        $exists = DB::table('users')->where('email', $email)->exists();
        if (!$exists) {
            DB::table('users')->insert([
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $email,
                'password' => Hash::make('admin123'), // Change password after first login
                'password_hash' => Hash::make('admin123'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
