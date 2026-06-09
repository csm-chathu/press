<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'city' => 'Colombo', 'country' => 'Sri Lanka']);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'     => 'admin@test.lk',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/login', [
            'email'    => 'admin@test.lk',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'     => 'admin@test.lk',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->postJson('/login', [
            'email'    => 'admin@test.lk',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/login', [
            'email'    => 'nobody@test.lk',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/login', [])->assertStatus(422)
             ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role'      => 'admin',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // Create a real personal access token so currentAccessToken() returns a deletable token.
        $token = $user->createToken('spa-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $token")
             ->postJson('/api/logout')
             ->assertStatus(200);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/job-cards')->assertStatus(401);
    }

    public function test_client_login_returns_client_role(): void
    {
        $customer = \App\Models\Customer::create([
            'name'      => 'Test Client',
            'phone'     => '0771111111',
            'branch_id' => $this->branch->id,
        ]);

        User::factory()->create([
            'email'       => 'client@test.lk',
            'password'    => bcrypt('password'),
            'role'        => 'client',
            'branch_id'   => $this->branch->id,
            'customer_id' => $customer->id,
            'is_active'   => true,
        ]);

        $this->postJson('/login', [
            'email'    => 'client@test.lk',
            'password' => 'password',
        ])->assertStatus(200)
          ->assertJsonPath('user.role', 'client');
    }
}
