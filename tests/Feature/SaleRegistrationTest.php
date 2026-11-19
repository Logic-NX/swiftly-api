<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'Tienda de prueba']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);
    }

    public function test_catalog_item_name_is_used_when_registering_a_sale(): void
    {
        $product = Product::create([
            'company_id' => User::firstOrFail()->company_id,
            'name' => 'Galletas',
            'price' => 5,
            'is_manual' => true,
        ]);

        $response = $this->postJson('/api/sales', [
            'method' => 'cash',
            'received' => 10,
            'change' => 0,
            'items' => [[
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => 2,
            ]],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'product_name' => 'Galletas',
        ]);
    }

    public function test_manual_item_without_a_name_is_rejected_as_invalid_input(): void
    {
        $response = $this->postJson('/api/sales', [
            'method' => 'cash',
            'received' => 10,
            'change' => 0,
            'items' => [[
                'id' => null,
                'quantity' => 1,
                'price_usd' => 10,
            ]],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_name']);
        $this->assertDatabaseCount('sales', 0);
    }
}
