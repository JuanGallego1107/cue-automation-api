<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions for tests
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test a user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $role = Role::where('name', 'Administrador')->first();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'is_active',
                ],
            ]);
    }

    /**
     * Test a user cannot login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $role = Role::where('name', 'Administrador')->first();

        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test an inactive user cannot login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $role = Role::where('name', 'Administrador')->first();

        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Your account is inactive. Please contact the administrator.'
            ]);
    }

    /**
     * Test authenticated user can retrieve their profile.
     */
    public function test_authenticated_user_can_retrieve_their_profile(): void
    {
        $role = Role::where('name', 'Administrador')->first();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'john@example.com');
    }

    /**
     * Test user can logout and revoke their token.
     */
    public function test_user_can_logout(): void
    {
        $role = Role::where('name', 'Administrador')->first();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Logged out successfully.'
            ]);
    }
}
