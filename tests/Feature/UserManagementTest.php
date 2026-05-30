<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Program;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $studentRole;
    protected User $adminUser;
    protected User $studentUser;
    protected Program $testProgram;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed database
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ProgramSeeder::class);

        $this->adminRole = Role::where('name', 'Administrador')->first();
        $this->studentRole = Role::where('name', 'Estudiante')->first();
        $this->testProgram = Program::first();

        // Create Admin (has all permissions)
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        // Create Student (does not have user management permissions)
        $this->studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test authorized user can list users.
     */
    public function test_authorized_user_can_list_users(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    /**
     * Test unauthorized user cannot list users.
     */
    public function test_unauthorized_user_cannot_list_users(): void
    {
        $response = $this->actingAs($this->studentUser, 'sanctum')->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Unauthorized. Missing required permission: users.view'
            ]);
    }

    /**
     * Test user creation with validations.
     */
    public function test_authorized_user_can_create_user(): void
    {
        $userData = [
            'name' => 'Jane Student',
            'email' => 'jane.student@example.com',
            'password' => 'securePassword123',
            'role_id' => $this->studentRole->id,
            'program_id' => $this->testProgram->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'jane.student@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'jane.student@example.com'
        ]);
    }

    /**
     * Test user creation validation constraints.
     */
    public function test_user_creation_validation(): void
    {
        // Try creating with empty name and invalid role
        $userData = [
            'name' => '',
            'email' => 'notanemail',
            'password' => 'short',
            'role_id' => 999, // Non-existent
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role_id']);
    }

    /**
     * Test updating a user's details.
     */
    public function test_authorized_user_can_update_user(): void
    {
        $userToUpdate = User::create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');
    }

    /**
     * Test soft delete functionality.
     */
    public function test_authorized_user_can_soft_delete_user(): void
    {
        $userToDelete = User::create([
            'name' => 'To Delete',
            'email' => 'delete@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->studentRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User soft deleted successfully.'
            ]);

        // Assert it is soft deleted in the database (deleted_at is NOT null)
        $this->assertSoftDeleted('users', [
            'id' => $userToDelete->id
        ]);
    }
}
