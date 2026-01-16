<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Product\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    // ============ PRODUCTS ============

    /** @test */
    public function can_list_products(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        Product::factory()->count(5)->create();

        $response = $this->apiGet('/products');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
    }

    /** @test */
    public function can_create_product(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        $response = $this->apiPost('/products', [
            'name' => 'Plywood 3/4"',
            'sku' => 'PLY-34-001',
            'type' => 'sheet_goods',
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_show_product(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        $product = Product::factory()->create();

        $response = $this->apiGet("/products/{$product->id}");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_update_product(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->apiPut("/products/{$product->id}", [
            'name' => 'New Name',
        ]);

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_delete_product(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        $product = Product::factory()->create();

        $response = $this->apiDelete("/products/{$product->id}");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_search_products(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        Product::factory()->create(['name' => 'Plywood 3/4']);
        Product::factory()->create(['name' => 'Plywood 1/2']);
        Product::factory()->create(['name' => 'MDF']);

        $response = $this->apiGet('/products?search=Plywood');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_products_by_type(): void
    {
        if (!class_exists(Product::class)) {
            $this->markTestSkipped('Product model not available');
        }

        Product::factory()->count(2)->create(['type' => 'sheet_goods']);
        Product::factory()->count(3)->create(['type' => 'hardware']);

        $response = $this->apiGet('/products?filter[type]=sheet_goods');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    // ============ WAREHOUSES ============

    /** @test */
    public function can_list_warehouses(): void
    {
        if (!class_exists(Warehouse::class)) {
            $this->markTestSkipped('Warehouse model not available');
        }

        Warehouse::factory()->count(3)->create();

        $response = $this->apiGet('/warehouses');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_warehouse(): void
    {
        if (!class_exists(Warehouse::class)) {
            $this->markTestSkipped('Warehouse model not available');
        }

        $response = $this->apiPost('/warehouses', [
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_show_warehouse(): void
    {
        if (!class_exists(Warehouse::class)) {
            $this->markTestSkipped('Warehouse model not available');
        }

        $warehouse = Warehouse::factory()->create();

        $response = $this->apiGet("/warehouses/{$warehouse->id}");

        $this->assertApiSuccess($response);
    }

    // ============ LOCATIONS ============

    /** @test */
    public function can_list_locations(): void
    {
        if (!class_exists(Location::class)) {
            $this->markTestSkipped('Location model not available');
        }

        Location::factory()->count(5)->create();

        $response = $this->apiGet('/locations');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_location(): void
    {
        if (!class_exists(Location::class)) {
            $this->markTestSkipped('Location model not available');
        }

        $response = $this->apiPost('/locations', [
            'name' => 'Shelf A-1',
            'barcode' => 'LOC-A1-001',
        ]);

        $this->assertApiSuccess($response, 201);
    }

    // ============ MOVES ============

    /** @test */
    public function can_list_moves(): void
    {
        if (!class_exists(Move::class)) {
            $this->markTestSkipped('Move model not available');
        }

        Move::factory()->count(5)->create();

        $response = $this->apiGet('/moves');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_move(): void
    {
        if (!class_exists(Move::class) || !class_exists(Product::class) || !class_exists(Location::class)) {
            $this->markTestSkipped('Move, Product, or Location model not available');
        }

        $product = Product::factory()->create();
        $sourceLocation = Location::factory()->create();
        $destLocation = Location::factory()->create();

        $response = $this->apiPost('/moves', [
            'product_id' => $product->id,
            'source_location_id' => $sourceLocation->id,
            'destination_location_id' => $destLocation->id,
            'quantity' => 10,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_filter_moves_by_product(): void
    {
        if (!class_exists(Move::class) || !class_exists(Product::class)) {
            $this->markTestSkipped('Move or Product model not available');
        }

        $product = Product::factory()->create();
        Move::factory()->count(2)->create(['product_id' => $product->id]);
        Move::factory()->count(3)->create();

        $response = $this->apiGet("/moves?filter[product_id]={$product->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }
}
