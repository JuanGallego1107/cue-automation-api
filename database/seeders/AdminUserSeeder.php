<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@cue.edu.co'],
                [
                    'name' => 'Administrador Principal',
                    'password' => Hash::make('password123'),
                    'role_id' => $adminRole->id,
                    'program_id' => null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
