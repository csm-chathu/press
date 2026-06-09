<?php

namespace Tests\Feature;

use App\Models\JobCard;
use Tests\PressTestCase;

class PortalTest extends PressTestCase
{
    public function test_client_can_view_their_own_jobs(): void
    {
        $customer = $this->makeCustomer();
        $job      = $this->makeJobCard(['customer_id' => $customer->id]);

        $this->asClient($customer);

        $response = $this->getJson('/api/portal/jobs')
                         ->assertStatus(200)
                         ->assertJsonStructure(['data']);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($job->id, $ids->toArray());
    }

    public function test_client_cannot_see_other_customers_jobs(): void
    {
        $customerA = $this->makeCustomer(['phone' => '0771111111']);
        $customerB = $this->makeCustomer(['phone' => '0772222222']);

        $this->makeJobCard(['customer_id' => $customerB->id]);

        $this->asClient($customerA);

        $response = $this->getJson('/api/portal/jobs')->json();
        $this->assertEmpty($response['data']);
    }

    public function test_client_can_view_their_quotations(): void
    {
        $customer = $this->makeCustomer();
        $this->asAdmin();
        $this->postJson('/api/quotations', [
            'title'                 => 'Client Quote',
            'customer_id'           => $customer->id,
            'product_type'          => 'brochures',
            'quantity'              => 1000,
            'color_count'           => 4,
            'plate_cost'            => 1000, 'paper_cost' => 2000,
            'ink_cost'              => 500,  'finishing_cost' => 500,
            'labour_cost'           => 1000, 'wastage_percent' => 5,
            'profit_margin_percent' => 20,   'tax_rate' => 0,
        ]);

        $this->asClient($customer);

        $this->getJson('/api/portal/quotations')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_staff_cannot_access_portal_endpoints(): void
    {
        $this->asRole('sales');

        $this->getJson('/api/portal/jobs')->assertStatus(403);
    }

    // ── Proof Approval ──────────────────────────────────────────────

    public function test_client_can_approve_proof(): void
    {
        $customer = $this->makeCustomer();
        $job      = $this->makeJobCard([
            'customer_id' => $customer->id,
            'status'      => 'proof_approval',
        ]);

        $this->asClient($customer);

        $this->postJson("/api/portal/jobs/{$job->id}/proof-decision", [
            'decision' => 'approved',
            'notes'    => 'Looks great!',
        ])->assertStatus(200)
          ->assertJsonPath('decision', 'approved');

        $this->assertDatabaseHas('prepress_tasks', [
            'job_card_id'     => $job->id,
            'client_decision' => 'approved',
            'client_notes'    => 'Looks great!',
        ]);
    }

    public function test_client_can_reject_proof(): void
    {
        $customer = $this->makeCustomer();
        $job      = $this->makeJobCard([
            'customer_id' => $customer->id,
            'status'      => 'proof_approval',
        ]);

        $this->asClient($customer);

        $this->postJson("/api/portal/jobs/{$job->id}/proof-decision", [
            'decision' => 'rejected',
            'notes'    => 'Change the logo colour.',
        ])->assertStatus(200)
          ->assertJsonPath('decision', 'rejected');

        $this->assertDatabaseHas('prepress_tasks', [
            'job_card_id'     => $job->id,
            'client_decision' => 'rejected',
        ]);
    }

    public function test_client_cannot_approve_proof_for_another_customers_job(): void
    {
        $customerA = $this->makeCustomer(['phone' => '0771111111']);
        $customerB = $this->makeCustomer(['phone' => '0772222222']);

        $job = $this->makeJobCard([
            'customer_id' => $customerB->id,
            'status'      => 'proof_approval',
        ]);

        $this->asClient($customerA);

        $this->postJson("/api/portal/jobs/{$job->id}/proof-decision", [
            'decision' => 'approved',
        ])->assertStatus(404);
    }

    public function test_client_cannot_approve_proof_when_job_not_in_proof_approval_status(): void
    {
        $customer = $this->makeCustomer();
        $job      = $this->makeJobCard([
            'customer_id' => $customer->id,
            'status'      => 'printing',     // not proof_approval
        ]);

        $this->asClient($customer);

        $this->postJson("/api/portal/jobs/{$job->id}/proof-decision", [
            'decision' => 'approved',
        ])->assertStatus(404);
    }

    public function test_proof_decision_validates_allowed_values(): void
    {
        $customer = $this->makeCustomer();
        $job      = $this->makeJobCard([
            'customer_id' => $customer->id,
            'status'      => 'proof_approval',
        ]);

        $this->asClient($customer);

        $this->postJson("/api/portal/jobs/{$job->id}/proof-decision", [
            'decision' => 'maybe',   // invalid
        ])->assertStatus(422);
    }
}
