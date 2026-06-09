<?php

namespace Tests\Feature;

use Tests\PressTestCase;

class ScheduleTest extends PressTestCase
{
    public function test_schedule_index_returns_machines_and_jobs(): void
    {
        $this->asAdmin();

        $machine = $this->makeMachine(['name' => 'Heidelberg']);
        $this->makeJobCard(['machine_id' => $machine->id, 'scheduled_date' => now()->toDateString()]);

        $response = $this->getJson('/api/schedule?week=' . now()->toDateString())
                         ->assertStatus(200)
                         ->assertJsonStructure(['jobs', 'machines', 'week_start', 'week_end']);

        $this->assertCount(1, $response->json('machines'));
        $this->assertCount(1, $response->json('jobs'));
    }

    public function test_schedule_includes_unscheduled_jobs(): void
    {
        $this->asAdmin();

        $this->makeJobCard(['scheduled_date' => null]);

        $response = $this->getJson('/api/schedule')->assertStatus(200);

        $jobs = collect($response->json('jobs'));
        $unscheduled = $jobs->filter(fn($j) => $j['scheduled_date'] === null);

        $this->assertCount(1, $unscheduled);
    }

    public function test_reschedule_updates_machine_and_date(): void
    {
        $this->asAdmin();

        $machine = $this->makeMachine();
        $job     = $this->makeJobCard();
        $date    = now()->addDay()->toDateString();

        $this->patchJson("/api/job-cards/{$job->id}/reschedule", [
            'machine_id'     => $machine->id,
            'scheduled_date' => $date,
        ])->assertStatus(200);

        $job->refresh();
        $this->assertEquals($machine->id, $job->machine_id);
        $this->assertEquals($date, $job->scheduled_date->toDateString());
    }

    public function test_reschedule_can_clear_schedule(): void
    {
        $this->asAdmin();

        $machine = $this->makeMachine();
        $job     = $this->makeJobCard([
            'machine_id'     => $machine->id,
            'scheduled_date' => now()->toDateString(),
        ]);

        $this->patchJson("/api/job-cards/{$job->id}/reschedule", [
            'machine_id'     => null,
            'scheduled_date' => null,
        ])->assertStatus(200);

        $this->assertDatabaseHas('job_cards', [
            'id'             => $job->id,
            'machine_id'     => null,
            'scheduled_date' => null,
        ]);
    }

    public function test_workload_returns_operators(): void
    {
        $this->asAdmin();

        \App\Models\User::factory()->create([
            'role'      => 'machine_operator',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->getJson('/api/schedule/workload')
             ->assertStatus(200)
             ->assertJsonIsArray();
    }

    public function test_workload_job_count_is_correct(): void
    {
        $this->asAdmin();

        $operator = \App\Models\User::factory()->create([
            'role'      => 'machine_operator',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->makeJobCard(['assigned_operator_id' => $operator->id, 'status' => 'printing']);
        $this->makeJobCard(['assigned_operator_id' => $operator->id, 'status' => 'designing']);

        $response = $this->getJson('/api/schedule/workload')->json();

        $operatorData = collect($response)->firstWhere('id', $operator->id);
        $this->assertEquals(2, $operatorData['job_count']);
    }

    public function test_alerts_returns_overdue_jobs(): void
    {
        $this->asAdmin();

        // Overdue job
        $this->makeJobCard(['due_date' => now()->subDays(2)->toDateString(), 'status' => 'printing']);
        // Future job — not in alerts
        $this->makeJobCard(['due_date' => now()->addDays(10)->toDateString(), 'status' => 'waiting']);

        $response = $this->getJson('/api/schedule/alerts')
                         ->assertStatus(200)
                         ->assertJsonIsArray();

        $this->assertCount(1, $response->json());
        $this->assertTrue($response->json()[0]['overdue']);
    }

    public function test_alerts_returns_due_soon_jobs(): void
    {
        $this->asAdmin();

        $this->makeJobCard(['due_date' => now()->addDay()->toDateString(), 'status' => 'designing']);

        $response = $this->getJson('/api/schedule/alerts')->json();

        $this->assertCount(1, $response);
        $this->assertFalse($response[0]['overdue']);
    }

    public function test_delivered_jobs_not_in_alerts(): void
    {
        $this->asAdmin();

        $this->makeJobCard(['due_date' => now()->subDays(1)->toDateString(), 'status' => 'delivered']);

        $this->getJson('/api/schedule/alerts')
             ->assertStatus(200)
             ->assertJsonCount(0);
    }

    public function test_branch_user_sees_only_own_branch_jobs(): void
    {
        // Create a second branch + user
        $otherBranch = \App\Models\Branch::create(['name' => 'Other Branch', 'code' => 'OB', 'city' => 'Kandy', 'country' => 'Sri Lanka']);
        $otherMachine = \App\Models\PressMachine::create([
            'branch_id' => $otherBranch->id, 'name' => 'Other Machine',
            'machine_type' => 'printing', 'status' => 'active',
        ]);
        \App\Models\JobCard::create([
            'branch_id'  => $otherBranch->id,
            'job_number' => 'JC-OTHER-0001',
            'title'      => 'Other Branch Job',
            'status'     => 'waiting',
            'order_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        $this->asRole('production_manager'); // branch-scoped

        $response = $this->getJson('/api/schedule')->json();

        $jobTitles = collect($response['jobs'])->pluck('title');
        $this->assertNotContains('Other Branch Job', $jobTitles);
    }
}
