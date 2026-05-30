<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initial Roles
        $roles = [
            'Administrador' => 'Acceso total al sistema y gestión de configuraciones.',
            'Coordinador' => 'Gestión académica de su programa asignado.',
            'Docente' => 'Gestión de sus cursos y calificaciones asignadas.',
            'Estudiante' => 'Consulta de notas, cursos e información académica.',
        ];

        $roleInstances = [];
        foreach ($roles as $name => $description) {
            $roleInstances[$name] = Role::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Initial Permissions
        $permissions = [
            'users.view' => 'Permite ver el listado y detalle de los usuarios del sistema.',
            'users.create' => 'Permite crear nuevos usuarios en el sistema.',
            'users.update' => 'Permite actualizar la información de los usuarios existentes.',
            'users.delete' => 'Permite desactivar o realizar soft delete a usuarios.',
            'roles.manage' => 'Permite ver y gestionar roles y la asignación de permisos.',
        ];

        $permissionInstances = [];
        foreach ($permissions as $name => $description) {
            $permissionInstances[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Assign all permissions to the Administrador role
        $adminRole = $roleInstances['Administrador'];
        $adminRole->permissions()->sync(
            collect($permissionInstances)->pluck('id')->toArray()
        );
    }
}
