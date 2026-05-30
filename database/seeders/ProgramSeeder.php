<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Ingeniería de Software',
                'code' => 'ISW-01',
                'faculty' => 'Facultad de Ingeniería',
                'is_active' => true,
            ],
            [
                'name' => 'Administración de Empresas',
                'code' => 'ADM-02',
                'faculty' => 'Facultad de Ciencias Económicas y Administrativas',
                'is_active' => true,
            ],
            [
                'name' => 'Medicina',
                'code' => 'MED-03',
                'faculty' => 'Facultad de Ciencias de la Salud',
                'is_active' => true,
            ],
            [
                'name' => 'Derecho',
                'code' => 'DER-04',
                'faculty' => 'Facultad de Ciencias Jurídicas',
                'is_active' => true,
            ],
        ];

        foreach ($programs as $program) {
            Program::firstOrCreate(
                ['code' => $program['code']],
                [
                    'name' => $program['name'],
                    'faculty' => $program['faculty'],
                    'is_active' => $program['is_active'],
                ]
            );
        }
    }
}
