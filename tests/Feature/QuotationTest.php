<?php

namespace Tests\Feature;

use Tests\PressTestCase;

class QuotationTest extends PressTestCase
{
    private function quotationPayload(array $overrides = []): array
    {
        return array_merge([
            'title'                 => 'Business Cards — 500 pcs',
            'product_type'          => 'business_cards',
            'paper_type'            => 'Art Card',
            'gsm'                   => 350,
            'size'                  => '3.5x2 inch',
            'quantity'              => 500,
            'color_count'           => 4,
            'printing_method'       => 'offset',
            'plate_cost'            => 2000,
            'paper_cost'            => 5000,
            'ink_cost'              => 1500,
            'finishing_cost'        => 1000,
            'labour_cost'           => 2000,
            'wastage_percent'       => 5,
            'profit_margin_percent' => 20,
            'tax_rate'              => 0,
        ], $overrides);
    }

    public function test_admin_can_create_quotation(): void
    {
        $this->asAdmin();

        $response = $this->postJson('/api/quotations', $this->quotationPayload());

        $response->assertStatus(201)
                 ->assertJsonPath('title', 'Business Cards — 500 pcs')
                 ->assertJsonStructure(['id', 'quotation_number', 'subtotal', 'total']);

        $this->assertDatabaseHas('quotations', ['title' => 'Business Cards — 500 pcs']);
    }

    public function test_quotation_totals_are_computed_correctly(): void
    {
        $this->asAdmin();

        // base = 2000+5000+1500+1000+2000 = 11500
        // with wastage 5% = 11500 * 1.05 = 12075
        // with profit 20% = 12075 * 1.20 = 14490
        // tax 0% => total = 14490
        $response = $this->postJson('/api/quotations', $this->quotationPayload());

        $response->assertStatus(201);
        $this->assertEquals(14490.0, (float) $response->json('total'));
    }

    public function test_quotation_number_is_auto_generated(): void
    {
        $this->asAdmin();

        $response = $this->postJson('/api/quotations', $this->quotationPayload());

        $this->assertNotNull($response->json('quotation_number'));
        $this->assertStringStartsWith('QT-', $response->json('quotation_number'));
    }

    public function test_admin_can_list_quotations(): void
    {
        $this->asAdmin();

        $this->postJson('/api/quotations', $this->quotationPayload());
        $this->postJson('/api/quotations', $this->quotationPayload(['title' => 'Brochures']));

        $this->getJson('/api/quotations')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_update_quotation(): void
    {
        $this->asAdmin();

        $qt = $this->postJson('/api/quotations', $this->quotationPayload())->json();

        $this->putJson("/api/quotations/{$qt['id']}", $this->quotationPayload(['title' => 'Updated Title']))
             ->assertStatus(200)
             ->assertJsonPath('title', 'Updated Title');
    }

    public function test_admin_can_delete_quotation(): void
    {
        $this->asAdmin();

        $qt = $this->postJson('/api/quotations', $this->quotationPayload())->json();

        $this->deleteJson("/api/quotations/{$qt['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('quotations', ['id' => $qt['id']]);
    }

    public function test_quotation_can_be_converted_to_sale(): void
    {
        $this->asAdmin();

        $customer = $this->makeCustomer();
        $qt = $this->postJson('/api/quotations', $this->quotationPayload([
            'customer_id' => $customer->id,
        ]))->json();

        $this->postJson("/api/quotations/{$qt['id']}/convert")
             ->assertStatus(200)
             ->assertJsonStructure(['order' => ['id', 'invoice_number', 'total']]);

        $this->assertDatabaseHas('sales', ['quotation_id' => $qt['id']]);
    }

    public function test_quotation_pdf_download_returns_pdf(): void
    {
        $this->asAdmin();

        $qt = $this->postJson('/api/quotations', $this->quotationPayload())->json();

        $response = $this->get("/api/quotations/{$qt['id']}/pdf");

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/pdf');
    }

    // ── Quotation Templates ─────────────────────────────────────────

    public function test_can_save_quotation_template(): void
    {
        $this->asAdmin();

        $this->postJson('/api/quotation-templates', [
            'name'                  => 'Business Cards — Standard',
            'plate_cost'            => 2000,
            'paper_cost'            => 5000,
            'ink_cost'              => 1500,
            'finishing_cost'        => 1000,
            'labour_cost'           => 2000,
            'wastage_percent'       => 5,
            'profit_margin_percent' => 20,
            'tax_rate'              => 0,
        ])->assertStatus(201)->assertJsonPath('name', 'Business Cards — Standard');

        $this->assertDatabaseHas('quotation_templates', ['name' => 'Business Cards — Standard']);
    }

    public function test_can_list_quotation_templates(): void
    {
        $this->asAdmin();

        $this->postJson('/api/quotation-templates', [
            'name' => 'Template A', 'plate_cost' => 1000, 'paper_cost' => 2000,
            'ink_cost' => 500, 'finishing_cost' => 500, 'labour_cost' => 1000,
            'wastage_percent' => 5, 'profit_margin_percent' => 20, 'tax_rate' => 0,
        ]);

        $this->getJson('/api/quotation-templates')
             ->assertStatus(200)
             ->assertJsonCount(1);
    }

    public function test_can_delete_quotation_template(): void
    {
        $this->asAdmin();

        $tmpl = $this->postJson('/api/quotation-templates', [
            'name' => 'Template A', 'plate_cost' => 1000, 'paper_cost' => 2000,
            'ink_cost' => 500, 'finishing_cost' => 500, 'labour_cost' => 1000,
            'wastage_percent' => 5, 'profit_margin_percent' => 20, 'tax_rate' => 0,
        ])->json();

        $this->deleteJson("/api/quotation-templates/{$tmpl['id']}")->assertStatus(200);
        $this->assertDatabaseMissing('quotation_templates', ['id' => $tmpl['id']]);
    }

    public function test_title_is_required_to_create_quotation(): void
    {
        $this->asAdmin();

        $this->postJson('/api/quotations', $this->quotationPayload(['title' => '']))
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }
}
