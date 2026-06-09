<?php

namespace Tests;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class PressTestCase extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name'    => 'Test Branch',
            'code'    => 'TB',
            'address' => '123 Test St',
            'city'    => 'Colombo',
            'country' => 'Sri Lanka',
        ]);

        $this->admin = User::factory()->create([
            'role'      => 'admin',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    /** Authenticate the given user (or admin) and return $this for chaining. */
    protected function asAdmin(): static
    {
        Sanctum::actingAs($this->admin);
        return $this;
    }

    protected function asRole(string $role): static
    {
        $user = User::factory()->create([
            'role'      => $role,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);
        return $this;
    }

    protected function asClient(\App\Models\Customer $customer): static
    {
        $user = User::factory()->create([
            'role'        => 'client',
            'branch_id'   => $this->branch->id,
            'customer_id' => $customer->id,
            'is_active'   => true,
        ]);
        Sanctum::actingAs($user);
        return $this;
    }

    /** Quick helper to create a machine. */
    protected function makeMachine(array $attrs = []): \App\Models\PressMachine
    {
        return \App\Models\PressMachine::create(array_merge([
            'branch_id'          => $this->branch->id,
            'name'               => 'Test Machine',
            'machine_type'       => 'printing',
            'status'             => 'active',
            'capacity_per_hour'  => 5000,
        ], $attrs));
    }

    /** Quick helper to create a customer. */
    protected function makeCustomer(array $attrs = []): \App\Models\Customer
    {
        return \App\Models\Customer::create(array_merge([
            'branch_id'   => $this->branch->id,
            'name'        => 'Test Customer',
            'phone'       => '0771234567',
            'credit_limit'=> 100000,
        ], $attrs));
    }

    /** Quick helper to create a job card with auto prepress/finishing tasks. */
    protected function makeJobCard(array $attrs = []): \App\Models\JobCard
    {
        $jobCard = \App\Models\JobCard::create(array_merge([
            'branch_id'  => $this->branch->id,
            'job_number' => \App\Models\JobCard::generateNumber(),
            'title'      => 'Test Job',
            'status'     => 'waiting',
            'order_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ], $attrs));

        \App\Models\PrepressTask::create([
            'branch_id'   => $this->branch->id,
            'job_card_id' => $jobCard->id,
            'status'      => 'pending',
        ]);

        \App\Models\FinishingTask::create([
            'branch_id'   => $this->branch->id,
            'job_card_id' => $jobCard->id,
            'status'      => 'pending',
        ]);

        return $jobCard;
    }
}
