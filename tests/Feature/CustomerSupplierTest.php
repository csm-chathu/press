<?php

namespace Tests\Feature;

use Tests\PressTestCase;

class CustomerSupplierTest extends PressTestCase
{
    // ── Customers ────────────────────────────────────────────────────

    public function test_admin_can_create_customer(): void
    {
        $this->asAdmin();

        $this->postJson('/api/customers', [
            'name'  => 'Dialog Axiata',
            'phone' => '0116780000',
            'email' => 'print@dialog.lk',
        ])->assertStatus(201)
          ->assertJsonPath('name', 'Dialog Axiata');

        $this->assertDatabaseHas('customers', ['name' => 'Dialog Axiata']);
    }

    public function test_customer_name_is_required(): void
    {
        $this->asAdmin();

        $this->postJson('/api/customers', ['phone' => '0771234567'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_list_customers(): void
    {
        $this->asAdmin();

        $this->makeCustomer(['phone' => '0771111111']);
        $this->makeCustomer(['phone' => '0772222222']);

        $this->getJson('/api/customers')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_update_customer(): void
    {
        $this->asAdmin();

        $customer = $this->makeCustomer();

        $this->putJson("/api/customers/{$customer->id}", [
            'name'  => 'Updated Name',
            'phone' => $customer->phone,
        ])->assertStatus(200)
          ->assertJsonPath('name', 'Updated Name');
    }

    public function test_admin_can_delete_customer(): void
    {
        $this->asAdmin();

        $customer = $this->makeCustomer();

        $this->deleteJson("/api/customers/{$customer->id}")->assertStatus(200);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_customers_all_endpoint_returns_flat_list(): void
    {
        $this->asAdmin();

        $this->makeCustomer(['phone' => '0771111111']);
        $this->makeCustomer(['phone' => '0772222222']);

        $response = $this->getJson('/api/customers/all')->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    // ── Suppliers ────────────────────────────────────────────────────

    public function test_admin_can_create_supplier(): void
    {
        $this->asAdmin();

        $this->postJson('/api/suppliers', [
            'name'    => 'Ceylon Paper Mills',
            'phone'   => '0114567890',
            'company' => 'Ceylon Paper Mills Ltd',
            'city'    => 'Colombo',
            'country' => 'Sri Lanka',
        ])->assertStatus(201)
          ->assertJsonPath('name', 'Ceylon Paper Mills');

        $this->assertDatabaseHas('suppliers', ['name' => 'Ceylon Paper Mills']);
    }

    public function test_supplier_name_is_required(): void
    {
        $this->asAdmin();

        $this->postJson('/api/suppliers', ['phone' => '0114567890'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_list_suppliers(): void
    {
        $this->asAdmin();

        $this->postJson('/api/suppliers', ['name' => 'Supplier A', 'city' => 'Colombo', 'country' => 'Sri Lanka']);
        $this->postJson('/api/suppliers', ['name' => 'Supplier B', 'city' => 'Kandy', 'country' => 'Sri Lanka']);

        $this->getJson('/api/suppliers')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_delete_supplier(): void
    {
        $this->asAdmin();

        $supplier = $this->postJson('/api/suppliers', [
            'name' => 'Temp Supplier', 'city' => 'Colombo', 'country' => 'Sri Lanka',
        ])->json();

        $this->deleteJson("/api/suppliers/{$supplier['id']}")->assertStatus(200);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier['id']]);
    }
}
