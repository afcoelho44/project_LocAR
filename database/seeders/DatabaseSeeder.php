<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        User::create(['name' => 'Professor','email' => 'professor@example.com', 'username' => 'professor', 'password' => Hash::make('123456'), 'type' => 'teacher']);
        User::create(['name' => 'Aluno', 'email' => 'aluno@example.com', 'username' => 'aluno', 'password' => Hash::make('123456'), 'type' => 'student']);
    }
}
