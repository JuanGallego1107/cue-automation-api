<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $program = Program::first();

        if (!$program) {
            $this->command->warn('SubjectSeeder: No program found. Skipping subject seeding.');
            return;
        }

        $subjects = [
            [
                'name'       => 'Matemáticas',
                'code'       => 'MAT101',
                'credits'    => 4,
                'semester'   => 1,
                'is_active'  => true,
            ],
            [
                'name'       => 'Lenguaje y Comunicación',
                'code'       => 'LEN101',
                'credits'    => 3,
                'semester'   => 1,
                'is_active'  => true,
            ],
            [
                'name'       => 'Ciencias Naturales',
                'code'       => 'CNA201',
                'credits'    => 3,
                'semester'   => 2,
                'is_active'  => true,
            ],
        ];

        foreach ($subjects as $subjectData) {
            Subject::create(array_merge($subjectData, ['program_id' => $program->id]));
        }
    }
}
