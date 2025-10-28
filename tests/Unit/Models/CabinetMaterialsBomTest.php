<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\CabinetRun;
use Webkul\Product\Models\Product;

class CabinetMaterialsBomTest extends TestCase
{
    use DatabaseTransactions;

    protected CabinetMaterialsBom $bom;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test product using factory
        $this->product = Product::factory()->create([
            'name' => '3/4" Plywood - Birch',
        ]);

        // Create basic BOM entry
        $this->bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'box_sides',
            'quantity_required' => 10.00,
            'unit_of_measure' => 'SQFT',
            'waste_factor_percentage' => 10.00,
            'unit_cost' => 2.50,
        ]);
    }

    /** @test */
    public function it_can_be_created_with_minimal_attributes(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test_component',
            'quantity_required' => 5.00,
            'unit_of_measure' => 'EA',
        ]);

        $this->assertInstanceOf(CabinetMaterialsBom::class, $bom);
        $this->assertEquals('test_component', $bom->component_name);
        $this->assertEquals(5.00, $bom->quantity_required);
    }

    /** @test */
    public function it_auto_calculates_quantity_with_waste(): void
    {
        // quantity_with_waste = quantity_required * (1 + waste_factor_percentage / 100)
        // Expected: 10.00 * (1 + 10/100) = 10.00 * 1.10 = 11.00
        $this->assertEquals(11.00, $this->bom->quantity_with_waste);
    }

    /** @test */
    public function it_auto_calculates_total_material_cost(): void
    {
        // total_material_cost = quantity_with_waste * unit_cost
        // Expected: 11.00 * 2.50 = 27.50
        $this->assertEquals(27.50, $this->bom->total_material_cost);
    }

    /** @test */
    public function it_auto_calculates_square_footage_for_components(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'cabinet_door',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'waste_factor_percentage' => 10.00,
            'component_width_inches' => 24.000,
            'component_height_inches' => 36.000,
            'quantity_of_components' => 2,
        ]);

        // sqft_per_component = (width * height) / 144
        // Expected: (24 * 36) / 144 = 864 / 144 = 6.00 sqft
        $this->assertEquals(6.00, $bom->sqft_per_component);

        // total_sqft_required = sqft_per_component * quantity * (1 + waste_factor / 100)
        // Expected: 6.00 * 2 * 1.10 = 13.20 sqft
        $this->assertEquals(13.20, $bom->total_sqft_required);
    }

    /** @test */
    public function it_recalculates_on_update(): void
    {
        $this->bom->update([
            'quantity_required' => 20.00,
            'waste_factor_percentage' => 15.00,
        ]);

        // New quantity_with_waste: 20.00 * 1.15 = 23.00
        $this->assertEquals(23.00, $this->bom->fresh()->quantity_with_waste);

        // New total_material_cost: 23.00 * 2.50 = 57.50
        $this->assertEquals(57.50, $this->bom->fresh()->total_material_cost);
    }

    /** @test */
    public function it_handles_zero_waste_factor(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 10.00,
            'unit_of_measure' => 'EA',
            'waste_factor_percentage' => 0.00,
        ]);

        // With 0% waste: quantity_with_waste should equal quantity_required
        $this->assertEquals(10.00, $bom->quantity_with_waste);
    }

    /** @test */
    public function it_handles_high_waste_factor(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 10.00,
            'unit_of_measure' => 'EA',
            'waste_factor_percentage' => 100.00,
        ]);

        // With 100% waste: quantity_with_waste = 10.00 * 2.00 = 20.00
        $this->assertEquals(20.00, $bom->quantity_with_waste);
    }

    /** @test */
    public function it_casts_decimal_fields_correctly(): void
    {
        // Laravel's decimal cast returns strings for precision
        $this->assertIsString($this->bom->quantity_required);
        $this->assertIsString($this->bom->waste_factor_percentage);
        $this->assertIsString($this->bom->quantity_with_waste);
        $this->assertIsString($this->bom->unit_cost);
        $this->assertIsString($this->bom->total_material_cost);

        // But they should be numeric strings
        $this->assertIsNumeric($this->bom->quantity_required);
        $this->assertIsNumeric($this->bom->waste_factor_percentage);
    }

    /** @test */
    public function it_casts_integer_fields_correctly(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'quantity_of_components' => 5,
        ]);

        $this->assertIsInt($bom->quantity_of_components);
        $this->assertEquals(5, $bom->quantity_of_components);
    }

    /** @test */
    public function it_casts_boolean_fields_correctly(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'requires_edge_banding' => true,
            'material_allocated' => false,
            'material_issued' => false,
        ]);

        $this->assertIsBool($bom->requires_edge_banding);
        $this->assertIsBool($bom->material_allocated);
        $this->assertIsBool($bom->material_issued);
        $this->assertTrue($bom->requires_edge_banding);
        $this->assertFalse($bom->material_allocated);
    }

    /** @test */
    public function it_belongs_to_product(): void
    {
        $this->assertTrue(method_exists($this->bom, 'product'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->bom->product()
        );
        $this->assertEquals($this->product->id, $this->bom->product->id);
    }

    /** @test */
    public function it_belongs_to_substituted_product(): void
    {
        $substitute = Product::factory()->create(['name' => 'Substitute Material']);

        $this->bom->update(['substituted_product_id' => $substitute->id]);

        $this->assertTrue(method_exists($this->bom, 'substitutedProduct'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->bom->substitutedProduct()
        );
        $this->assertEquals($substitute->id, $this->bom->fresh()->substitutedProduct->id);
    }

    /** @test */
    public function it_belongs_to_cabinet_specification(): void
    {
        $this->assertTrue(method_exists($this->bom, 'cabinetSpecification'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->bom->cabinetSpecification()
        );
    }

    /** @test */
    public function it_belongs_to_cabinet_run(): void
    {
        $this->assertTrue(method_exists($this->bom, 'cabinetRun'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->bom->cabinetRun()
        );
    }

    /** @test */
    public function scope_allocated_filters_allocated_materials(): void
    {
        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'allocated_1',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_allocated' => true,
        ]);

        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'not_allocated',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_allocated' => false,
        ]);

        $allocated = CabinetMaterialsBom::allocated()->get();

        $this->assertEquals(1, $allocated->count());
        $this->assertEquals('allocated_1', $allocated->first()->component_name);
    }

    /** @test */
    public function scope_issued_filters_issued_materials(): void
    {
        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'issued_1',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_issued' => true,
        ]);

        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'not_issued',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_issued' => false,
        ]);

        $issued = CabinetMaterialsBom::issued()->get();

        $this->assertEquals(1, $issued->count());
        $this->assertEquals('issued_1', $issued->first()->component_name);
    }

    /** @test */
    public function scope_pending_filters_unallocated_materials(): void
    {
        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'pending_1',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_allocated' => false,
        ]);

        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'allocated',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_allocated' => true,
        ]);

        $pending = CabinetMaterialsBom::pending()->get();

        // Should include setup bom (default allocated = false) + pending_1
        $this->assertGreaterThanOrEqual(2, $pending->count());
        $this->assertTrue($pending->contains('component_name', 'pending_1'));
    }

    /** @test */
    public function scope_by_component_filters_by_component_name(): void
    {
        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'door_panel',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
        ]);

        CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'drawer_front',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
        ]);

        $doorPanels = CabinetMaterialsBom::byComponent('door_panel')->get();

        $this->assertEquals(1, $doorPanels->count());
        $this->assertEquals('door_panel', $doorPanels->first()->component_name);
    }

    /** @test */
    public function it_can_track_edge_banding(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'shelf',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'requires_edge_banding' => true,
            'edge_banding_sides' => 'front_only',
            'edge_banding_lf' => 4.50,
        ]);

        $this->assertTrue($bom->requires_edge_banding);
        $this->assertEquals('front_only', $bom->edge_banding_sides);
        $this->assertEquals(4.50, $bom->edge_banding_lf);
    }

    /** @test */
    public function it_can_track_grain_direction(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'panel',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'grain_direction' => 'vertical',
        ]);

        $this->assertEquals('vertical', $bom->grain_direction);
    }

    /** @test */
    public function it_can_store_cnc_and_machining_notes(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'door',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'cnc_notes' => 'Profile edges with 1/8" roundover',
            'machining_operations' => 'Dado, groove, mortise',
        ]);

        $this->assertEquals('Profile edges with 1/8" roundover', $bom->cnc_notes);
        $this->assertEquals('Dado, groove, mortise', $bom->machining_operations);
    }

    /** @test */
    public function it_can_track_linear_feet_and_board_feet(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'face_frame',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'LF',
            'linear_feet_per_component' => 12.50,
            'total_linear_feet' => 25.00,
            'board_feet_required' => 4.17,
        ]);

        $this->assertEquals(12.50, $bom->linear_feet_per_component);
        $this->assertEquals(25.00, $bom->total_linear_feet);
        $this->assertEquals(4.17, $bom->board_feet_required);
    }

    /** @test */
    public function it_handles_very_small_dimensions(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'small_part',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'waste_factor_percentage' => 10.00,
            'component_width_inches' => 0.125,
            'component_height_inches' => 0.125,
            'quantity_of_components' => 1,
        ]);

        // sqft = (0.125 * 0.125) / 144 = 0.000108... ≈ 0.00
        $this->assertEquals(0.00, $bom->sqft_per_component);
    }

    /** @test */
    public function it_handles_very_large_dimensions(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'large_panel',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'waste_factor_percentage' => 10.00,
            'component_width_inches' => 96.000,
            'component_height_inches' => 48.000,
            'quantity_of_components' => 1,
        ]);

        // sqft = (96 * 48) / 144 = 4608 / 144 = 32.00
        $this->assertEquals(32.00, $bom->sqft_per_component);
        // total_sqft with 10% waste: 32.00 * 1 * 1.10 = 35.20
        $this->assertEquals(35.20, $bom->total_sqft_required);
    }

    /** @test */
    public function it_can_be_updated(): void
    {
        $this->bom->update([
            'component_name' => 'updated_component',
            'quantity_required' => 15.00,
        ]);

        $this->assertEquals('updated_component', $this->bom->fresh()->component_name);
        $this->assertEquals(15.00, $this->bom->fresh()->quantity_required);
    }

    /** @test */
    public function it_can_be_soft_deleted(): void
    {
        $id = $this->bom->id;
        $this->bom->delete();

        $this->assertSoftDeleted('projects_bom', ['id' => $id]);
        $this->assertNotNull($this->bom->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete(): void
    {
        $this->bom->delete();
        $this->bom->restore();

        $this->assertNull($this->bom->fresh()->deleted_at);
        $this->assertDatabaseHas('projects_bom', [
            'id' => $this->bom->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_can_track_material_allocation_timestamp(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_allocated' => true,
            'material_allocated_at' => now(),
        ]);

        $this->assertTrue($bom->material_allocated);
        $this->assertNotNull($bom->material_allocated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $bom->material_allocated_at);
    }

    /** @test */
    public function it_can_track_material_issue_timestamp(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'material_issued' => true,
            'material_issued_at' => now(),
        ]);

        $this->assertTrue($bom->material_issued);
        $this->assertNotNull($bom->material_issued_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $bom->material_issued_at);
    }

    /** @test */
    public function it_can_store_substitution_details(): void
    {
        $substitute = Product::factory()->create(['name' => 'Alternate Plywood']);

        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'test',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
            'substituted_product_id' => $substitute->id,
            'substitution_notes' => 'Original material out of stock',
        ]);

        $this->assertEquals($substitute->id, $bom->substituted_product_id);
        $this->assertEquals('Original material out of stock', $bom->substitution_notes);
    }

    /** @test */
    public function nullable_fields_can_be_null(): void
    {
        $bom = CabinetMaterialsBom::create([
            'product_id' => $this->product->id,
            'component_name' => 'minimal',
            'quantity_required' => 1.00,
            'unit_of_measure' => 'EA',
        ]);

        $this->assertNull($bom->cabinet_specification_id);
        $this->assertNull($bom->cabinet_run_id);
        $this->assertNull($bom->component_width_inches);
        $this->assertNull($bom->component_height_inches);
        $this->assertNull($bom->cnc_notes);
        $this->assertNull($bom->machining_operations);
        $this->assertNull($bom->substituted_product_id);
        $this->assertNull($bom->substitution_notes);
    }
}
