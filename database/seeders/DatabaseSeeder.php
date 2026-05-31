<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ProgramSeeder::class,
            AdminUserSeeder::class,
            // Document review system seeders
            PeriodSeeder::class,
            DocumentTypeSeeder::class,
            SubjectSeeder::class,
        ]);
    }
}
