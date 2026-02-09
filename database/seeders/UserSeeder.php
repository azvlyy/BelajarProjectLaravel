<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    // membuat data user admin secara otomatis ke database
    public function run(): void
    {
        User::create([
            'name' => 'Admin - Arya',
            'email' => 'adminaryasepatu@gmail.com',
            'password' => Hash::make('test'),
            'email_verified_at' => now(),
        ]);
    }
}
