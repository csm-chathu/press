<?php

namespace Tests\Feature;

use App\Models\JobCard;
use Tests\PressTestCase;

class JobCardTest extends PressTestCase
{
    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title'            => 'Test Job Card',
            'quantity_ordered' => 500,
            'paper_type'       => 'Art Paper',
            'gsm'              => 170,
            'color_count'      => '4+0',
            'printing_method'  => 'offset',
            'due_date'         => now()->addDays(7)->toDateString(),
        ], $overrides);
    }

    // ── CRUD ────────────────────────────────────────────────────────

    public function test_admin_can_create_job_card(): void
    {
        $this->asAdmin();

        $response = $this->postJson('/api/job-cards', $this->jobPayload());

        $response->assertStatus(201)
                 ->assertJsonPath('title', 'Test Job Card')
                 ->assertJsonPath('status', 'waiting');

        $this->assertDatabaseHas('job_cards', ['title' => 'Test Job Card']);
    }

    public function test_job_number_is_auto_generated(): void
    {
        $this->asAdmin();

        $job = $this->postJson('/api/job-cards', $this->jobPayload())->json();

        $this->assertNotNull($job['job_number']);
        $this->assertStringStartsWith('JC-', $job['job_number']);
    }

    public function test_job_card_auto_creates_prepress_and_finishing_tasks(): void
    {
        $this->asAdmin();

        $job = $this->postJson('/api/job-cards', $this->jobPayload())->json();

        $this->assertDatabaseHas('prepress_tasks',  ['job_card_id' => $job['id']]);
        $this->assertDatabaseHas('finishing_tasks', ['job_card_id' => $job['id']]);
    }

    public function test_admin_can_list_job_cards(): void
    {
        $this->asAdmin();

        $this->postJson('/api/job-cards', $this->jobPayload(['title' => 'Job A']));
        $this->postJson('/api/job-cards', $this->jobPayload(['title' => 'Job B']));

        $this->getJson('/api/job-cards')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_view_job_card(): void
    {
        $this->asAdmin();

        $job = $this->postJson('/api/job-cards', $this->jobPayload())->json();

        $this->getJson("/api/job-cards/{$job['id']}")
             ->assertStatus(200)
             ->assertJsonPath('id', $job['id']);
    }

    public function test_admin_can_update_job_card(): void
    {
        $this->asAdmin();

        $job = $this->postJson('/api/job-cards', $this->jobPayload())->json();

        $this->putJson("/api/job-cards/{$job['id']}", $this->jobPayload(['title' => 'Updated Title']))
             ->assertStatus(200)
             ->assertJsonPath('title', 'Updated Title');
    }

    public function test_admin_can_delete_job_card(): void
    {
        $this->asAdmin();

        $job = $this->postJson('/api/job-cards', $this->jobPayload())->json();

        $this->deleteJson("/api/job-cards/{$job['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('job_cards', ['id' => $job['id'], 'deleted_at' => null]);
    }

    public function test_cannot_delete_job_in_production(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard(['status' => 'printing']);

        $this->deleteJson("/api/job-cards/{$job->id}")
             ->assertStatus(422);
    }

    public function test_title_is_required(): void
    {
        $this->asAdmin();

        $this->postJson('/api/job-cards', $this->jobPayload(['title' => '']))
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }

    // ── Status ──────────────────────────────────────────────────────

    public function test_status_can_be_advanced(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $this->patchJson("/api/job-cards/{$job->id}/status", ['status' => 'designing'])
             ->assertStatus(200)
             ->assertJsonPath('status', 'designing');

        $this->assertDatabaseHas('job_cards', ['id' => $job->id, 'status' => 'designing']);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $this->patchJson("/api/job-cards/{$job->id}/status", ['status' => 'invalid_status'])
             ->assertStatus(422);
    }

    public function test_all_status_transitions_are_valid(): void
    {
        $this->asAdmin();

        $statuses = ['designing', 'proof_approval', 'plate_making', 'printing', 'finishing', 'quality_check', 'ready', 'delivered'];

        $job = $this->makeJobCard();

        foreach ($statuses as $status) {
            $this->patchJson("/api/job-cards/{$job->id}/status", ['status' => $status])
                 ->assertStatus(200)
                 ->assertJsonPath('status', $status);
        }
    }

    // ── Clone ───────────────────────────────────────────────────────

    public function test_job_card_can_be_cloned(): void
    {
        $this->asAdmin();

        $original = $this->makeJobCard(['title' => 'Original Job', 'status' => 'printing']);

        $clone = $this->postJson("/api/job-cards/{$original->id}/clone")
                      ->assertStatus(201)
                      ->json();

        $this->assertEquals('Original Job (Copy)', $clone['title']);
        $this->assertEquals('waiting', $clone['status']);
        $this->assertNotEquals($original->id, $clone['id']);
        $this->assertNotEquals($original->job_number, $clone['job_number']);

        // Clone also gets prepress + finishing tasks
        $this->assertDatabaseHas('prepress_tasks',  ['job_card_id' => $clone['id']]);
        $this->assertDatabaseHas('finishing_tasks', ['job_card_id' => $clone['id']]);
    }

    // ── Priority ────────────────────────────────────────────────────

    public function test_job_priority_can_be_toggled(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard(['is_priority' => false]);

        $this->patchJson("/api/job-cards/{$job->id}/priority")
             ->assertStatus(200)
             ->assertJsonPath('is_priority', true);

        $this->patchJson("/api/job-cards/{$job->id}/priority")
             ->assertStatus(200)
             ->assertJsonPath('is_priority', false);
    }

    // ── Consumables ─────────────────────────────────────────────────

    public function test_consumable_can_be_added_to_job_card(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $response = $this->postJson("/api/job-cards/{$job->id}/consumables", [
            'type'        => 'plate',
            'description' => 'CTP Plate A3',
            'quantity'    => 4,
            'unit'        => 'pcs',
            'unit_cost'   => 350,
        ])->assertStatus(201);

        $this->assertEquals(1400.0, (float) $response->json('total_cost'));
        $this->assertDatabaseHas('job_consumables', ['job_card_id' => $job->id, 'description' => 'CTP Plate A3']);
    }

    public function test_consumable_can_be_deleted(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $consumable = $this->postJson("/api/job-cards/{$job->id}/consumables", [
            'type' => 'ink', 'description' => 'Black Ink', 'quantity' => 2, 'unit' => 'kg', 'unit_cost' => 900,
        ])->json();

        $this->deleteJson("/api/job-consumables/{$consumable['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('job_consumables', ['id' => $consumable['id']]);
    }

    // ── Production Costing ──────────────────────────────────────────

    public function test_job_costing_can_be_saved(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $response = $this->postJson("/api/job-cards/{$job->id}/costing", [
            'paper_sheets'         => 600,
            'paper_rate_per_sheet' => 4.5,
            'ink_colours'          => 4,
            'ink_cost_per_colour'  => 800,
            'plate_count'          => 4,
            'plate_cost_each'      => 350,
            'machine_hours'        => 2,
            'machine_rate_per_hour'=> 1500,
            'labour_hours'         => 3,
            'labour_rate_per_hour' => 400,
            'electricity_cost'     => 200,
            'outsource_cost'       => 0,
            'waste_percentage'     => 5,
        ])->assertStatus(200);

        // paper=2700, ink=3200, plate=1400, machine=3000, labour=1200 → sub=11500
        // waste=575, total=11500+575+200=12275
        $this->assertEquals(2700.0,  (float) $response->json('paper_cost'));
        $this->assertEquals(3200.0,  (float) $response->json('ink_cost'));
        $this->assertEquals(1400.0,  (float) $response->json('plate_cost'));
        $this->assertEquals(3000.0,  (float) $response->json('machine_cost'));
        $this->assertEquals(1200.0,  (float) $response->json('labour_cost'));
        $this->assertEquals(575.0,   (float) $response->json('waste_cost'));
        $this->assertEquals(12275.0, (float) $response->json('total_actual_cost'));

        $this->assertDatabaseHas('job_costing', ['job_card_id' => $job->id]);
    }

    public function test_job_costing_upserts_not_duplicates(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();
        $payload = ['paper_sheets' => 100, 'paper_rate_per_sheet' => 5, 'ink_colours' => 0,
                    'ink_cost_per_colour' => 0, 'plate_count' => 0, 'plate_cost_each' => 0,
                    'machine_hours' => 0, 'machine_rate_per_hour' => 0, 'labour_hours' => 0,
                    'labour_rate_per_hour' => 0, 'electricity_cost' => 0, 'outsource_cost' => 0,
                    'waste_percentage' => 0];

        $this->postJson("/api/job-cards/{$job->id}/costing", $payload);
        $this->postJson("/api/job-cards/{$job->id}/costing", array_merge($payload, ['paper_sheets' => 200]));

        $this->assertDatabaseCount('job_costing', 1);
        $this->assertDatabaseHas('job_costing', ['job_card_id' => $job->id, 'paper_sheets' => 200]);
    }

    public function test_get_costing_returns_estimated_and_actual(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $this->getJson("/api/job-cards/{$job->id}/costing")
             ->assertStatus(200)
             ->assertJsonStructure(['costing', 'estimated', 'revenue_source', 'revenue']);
    }

    // ── Artwork Files ────────────────────────────────────────────────

    public function test_artwork_files_can_be_listed(): void
    {
        $this->asAdmin();

        $job = $this->makeJobCard();

        $this->getJson("/api/job-cards/{$job->id}/artwork")
             ->assertStatus(200)
             ->assertJsonIsArray();
    }
}
