<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_returns_access_and_refresh_tokens(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrador']);
        
        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'email' => 'admin_test@aura.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'login' => 'admin_test@aura.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'email',
                    'role'
                ]
            ]);

        $this->assertEquals('bearer', $response->json('token_type'));
        $this->assertEquals(10800, $response->json('expires_in'));
        $this->assertNotEmpty($response->json('access_token'));
        $this->assertNotEmpty($response->json('refresh_token'));
    }

    public function test_admin_can_refresh_token_using_refresh_token(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrador']);
        
        $admin = User::create([
            'name' => 'Admin Refresh Test',
            'username' => 'admin_refresh_test',
            'email' => 'admin_refresh@aura.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $refreshTokenResult = $admin->createToken('admin-refresh', ['issue-access-token'], now()->addDays(30));

        $response = $this->withHeader('Authorization', 'Bearer ' . $refreshTokenResult->plainTextToken)
            ->postJson('/api/admin/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);

        $this->assertEquals('bearer', $response->json('token_type'));
        $this->assertEquals(10800, $response->json('expires_in'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_admin_cannot_refresh_token_using_access_token(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrador']);
        
        $admin = User::create([
            'name' => 'Admin Access Test',
            'username' => 'admin_access_test',
            'email' => 'admin_access@aura.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $accessTokenResult = $admin->createToken('admin-session', ['access-api'], now()->addHours(3));

        $response = $this->withHeader('Authorization', 'Bearer ' . $accessTokenResult->plainTextToken)
            ->postJson('/api/admin/auth/refresh');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'El token provisto no es válido para renovar sesión'
            ]);
    }
}
