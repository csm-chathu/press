<?php

namespace Tests\Feature;

use Tests\PressTestCase;

class ProductionTest extends PressTestCase
{
    public function test_production_job_can_be_logged(): void
    {
        $this->asAdmin();

        $machine  = $this->makeMachine();
        $operator = \App\Models\User::factory()->create([
            'role'      => 'machine_operator',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $job = $this->makeJobCard([
            'machine_id'          => $machine->id,
            'assigned_operator_id'=> $operator->id,
            'status'              => 'printing',
        ]);

        $this->postJson('/api/production-jobs', [
            'job_card_id'     => $job->id,
            'machine_id'      => $machine->id,
            'operator_id'     => $operator->id,
            'start_time'      => now()->subHours(2)->toDateTimeString(),
            'end_time'        => now()->toDateTimeString(),
            'output_quantity' => 480,
            'waste_quantity'  => 20,
        ])->assertStatus(201)
          ->assertJsonStructure(['id', 'job_card_id', 'output_quantity', 'waste_quantity']);

        $this->assertDatabaseHas('production_jobs', ['job_card_id' => $job->id, 'output_quantity' => 480]);
    }

    public function test_production_analytics_endpoint_returns_data(): void
    {
        $this->asAdmin();

        $this->getJson('/api/production/analytics')
             ->assertStatus(200)
             ->assertJsonStructure(['by_machine', 'daily_trend', 'jobs_by_status', 'summary']);
    }

    public function test_production_dashboard_returns_queue(): void
    {
        $this->asAdmin();

        $this->makeJobCard(['status' => 'printing']);
        $this->makeJobCard(['status' => 'designing']);

        $this->getJson('/api/production/dashboard')
             ->assertStatus(200);
    }

    public function test_production_jobs_list_is_returned(): void
    {
        $this->asAdmin();

        $this->getJson('/api/production-jobs')
             ->assertStatus(200);
    }

    public function test_job_card_queue_excludes_delivered_jobs(): void
    {
        $this->asAdmin();

        $this->makeJobCard(['status' => 'printing']);
        $this->makeJobCard(['status' => 'delivered']);

        $response = $this->getJson('/api/job-cards/queue')->assertStatus(200);

        $statuses = collect($response->json())->pluck('status');
        $this->assertNotContains('delivered', $statuses->toArray());
    }

    // ── Pre-Press ───────────────────────────────────────────────────

    public function test_prepress_task_can_be_updated(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();
        $prepress = \App\Models\PrepressTask::where('job_card_id', $job->id)->first();

        $this->putJson("/api/prepress-tasks/{$prepress->id}", [
            'status' => 'artwork_received',
        ])->assertStatus(200)
          ->assertJsonPath('status', 'artwork_received');
    }

    // ── Finishing Tasks ─────────────────────────────────────────────

    public function test_finishing_task_can_be_updated(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();
        $finishing = \App\Models\FinishingTask::where('job_card_id', $job->id)->first();

        $this->putJson("/api/finishing-tasks/{$finishing->id}", [
            'cutting'  => true,
            'folding'  => true,
            'status'   => 'in_progress',
        ])->assertStatus(200)
          ->assertJsonPath('cutting', true);
    }

    // ── Machines ────────────────────────────────────────────────────

    public function test_admin_can_create_machine(): void
    {
        $this->asAdmin();

        $this->postJson('/api/press-machines', [
            'name'               => 'Heidelberg SM 52',
            'machine_type'       => 'printing',
            'status'             => 'active',
            'capacity_per_hour'  => 8000,
            'manufacturer'       => 'Heidelberg',
        ])->assertStatus(201)
          ->assertJsonPath('name', 'Heidelberg SM 52');

        $this->assertDatabaseHas('press_machines', ['name' => 'Heidelberg SM 52']);
    }

    public function test_admin_can_list_machines(): void
    {
        $this->asAdmin();

        $this->makeMachine(['name' => 'Machine A']);
        $this->makeMachine(['name' => 'Machine B']);

        $this->getJson('/api/press-machines')
             ->assertStatus(200)
             ->assertJsonCount(2);
    }
}
