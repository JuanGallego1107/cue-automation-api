<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::create([
            'name'               => 'Planeador de clase',
            'slug'               => 'planeador',
            'allowed_extensions' => ['pdf', 'docx'],
            'max_size_mb'        => 10,
            'naming_pattern'     => null,
            'requires_signature' => true,
            'validation_rules'   => [
                'required_fields' => [
                    'nombre_docente',
                    'asignatura',
                    'grado_grupo',
                    'periodo',
                    'objetivos',
                    'actividades',
                    'criterios_evaluacion',
                    'firma',
                ],
                'check_date_consistency' => true,
                'check_signature'        => true,
            ],
        ]);

        DocumentType::create([
            'name'               => 'Registro de notas',
            'slug'               => 'registro_notas',
            'allowed_extensions' => ['pdf', 'xlsx', 'xls'],
            'max_size_mb'        => 15,
            'naming_pattern'     => null,
            'requires_signature' => true,
            'validation_rules'   => [
                'required_fields' => [
                    'nombre_docente',
                    'asignatura',
                    'grado_grupo',
                    'periodo_academico',
                    'lista_estudiantes',
                    'escala_valoracion',
                    'firma',
                ],
                'grade_scale_min'        => 1.0,
                'grade_scale_max'        => 5.0,
                'check_empty_cells'      => true,
                'check_signature'        => true,
                'check_date_consistency' => true,
            ],
        ]);
    }
}
