<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\HardwareRequirement;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

class HardwareRequirementTest extends TestCase
{
    use DatabaseTransactions;

    protected HardwareRequirement $hardware;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test product manually
        $this->product = Product::factory()->create([
            'name' => 'Blum Hinge - Clip Top 110°',
            'type' => 'goods',
            'uom_id' => 1, // Units
            'uom_po_id' => 1, // Units for purchase orders
        ]);

        // Create basic hardware requirement
        $this->hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'manufacturer' => 'Blum',
            'model_number' => '71B3550',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'unit_cost' => 4.50,
            'applied_to' => 'door',
        ]);
    }

    /** @test */
    public function it_can_be_created_with_minimal_attributes(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
        ]);

        $this->assertInstanceOf(HardwareRequirement::class, $hardware);
        $this->assertEquals('slide', $hardware->hardware_type);
        $this->assertEquals(1, $hardware->quantity_required);
    }

    /** @test */
    public function it_auto_calculates_total_hardware_cost(): void
    {
        // total_hardware_cost = quantity_required * unit_cost
        // Expected: 2 * 4.50 = 9.00
        $this->assertEquals(9.00, $this->hardware->total_hardware_cost);
    }

    /** @test */
    public function it_recalculates_cost_on_update(): void
    {
        $this->hardware->update([
            'quantity_required' => 4,
            'unit_cost' => 5.00,
        ]);

        // New total: 4 * 5.00 = 20.00
        $this->assertEquals(20.00, $this->hardware->fresh()->total_hardware_cost);
    }

    /** @test */
    public function it_casts_integer_fields_correctly(): void
    {
        $this->assertIsInt($this->hardware->quantity_required);
        $this->assertEquals(2, $this->hardware->quantity_required);
    }

    /** @test */
    public function it_casts_decimal_fields_correctly(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
            'slide_length_inches' => 18.5,
            'unit_cost' => 12.99,
        ]);

        // Laravel's decimal cast returns strings for precision
        $this->assertIsString($hardware->slide_length_inches);
        $this->assertIsString($hardware->unit_cost);
        $this->assertIsNumeric($hardware->slide_length_inches);
        $this->assertEquals('18.5', $hardware->slide_length_inches);
    }

    /** @test */
    public function it_casts_boolean_fields_correctly(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'requires_jig' => true,
            'hardware_kitted' => false,
            'hardware_installed' => false,
            'has_defect' => false,
        ]);

        $this->assertIsBool($hardware->requires_jig);
        $this->assertIsBool($hardware->hardware_kitted);
        $this->assertIsBool($hardware->hardware_installed);
        $this->assertIsBool($hardware->has_defect);
        $this->assertTrue($hardware->requires_jig);
        $this->assertFalse($hardware->hardware_kitted);
    }

    /** @test */
    public function it_belongs_to_product(): void
    {
        $this->assertTrue(method_exists($this->hardware, 'product'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->hardware->product()
        );
        $this->assertEquals($this->product->id, $this->hardware->product->id);
    }

    /** @test */
    public function it_belongs_to_cabinet_specification(): void
    {
        $this->assertTrue(method_exists($this->hardware, 'cabinetSpecification'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->hardware->cabinetSpecification()
        );
    }

    /** @test */
    public function it_belongs_to_cabinet_run(): void
    {
        $this->assertTrue(method_exists($this->hardware, 'cabinetRun'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->hardware->cabinetRun()
        );
    }

    /** @test */
    public function it_belongs_to_installed_by_user(): void
    {
        $this->assertTrue(method_exists($this->hardware, 'installedBy'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->hardware->installedBy()
        );
    }

    /** @test */
    public function it_belongs_to_substituted_product(): void
    {
        $substitute = Product::factory()->create(['name' => 'Alternative Hinge']);
        $this->hardware->update(['substituted_product_id' => $substitute->id]);

        $this->assertTrue(method_exists($this->hardware, 'substitutedProduct'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->hardware->substitutedProduct()
        );
        $this->assertEquals($substitute->id, $this->hardware->fresh()->substitutedProduct->id);
    }

    /** @test */
    public function scope_hinges_filters_hinge_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
        ]);

        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
        ]);

        $hinges = HardwareRequirement::hinges()->get();

        $this->assertGreaterThanOrEqual(2, $hinges->count()); // Including setup hardware
        $this->assertTrue($hinges->every(fn ($hw) => $hw->hardware_type === 'hinge'));
    }

    /** @test */
    public function scope_slides_filters_slide_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
        ]);

        $slides = HardwareRequirement::slides()->get();

        $this->assertEquals(1, $slides->count());
        $this->assertTrue($slides->every(fn ($hw) => $hw->hardware_type === 'slide'));
    }

    /** @test */
    public function scope_shelf_pins_filters_shelf_pin_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'shelf_pin',
            'quantity_required' => 4,
            'unit_of_measure' => 'EA',
            'applied_to' => 'shelf',
        ]);

        $shelfPins = HardwareRequirement::shelfPins()->get();

        $this->assertEquals(1, $shelfPins->count());
        $this->assertTrue($shelfPins->every(fn ($hw) => $hw->hardware_type === 'shelf_pin'));
    }

    /** @test */
    public function scope_pullouts_filters_pullout_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'pullout',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'cabinet',
        ]);

        $pullouts = HardwareRequirement::pullouts()->get();

        $this->assertEquals(1, $pullouts->count());
        $this->assertTrue($pullouts->every(fn ($hw) => $hw->hardware_type === 'pullout'));
    }

    /** @test */
    public function scope_kitted_filters_kitted_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_kitted' => true,
        ]);

        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
            'hardware_kitted' => false,
        ]);

        $kitted = HardwareRequirement::kitted()->get();

        $this->assertEquals(1, $kitted->count());
        $this->assertTrue($kitted->first()->hardware_kitted);
    }

    /** @test */
    public function scope_installed_filters_installed_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_installed' => true,
        ]);

        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
            'hardware_installed' => false,
        ]);

        $installed = HardwareRequirement::installed()->get();

        $this->assertEquals(1, $installed->count());
        $this->assertTrue($installed->first()->hardware_installed);
    }

    /** @test */
    public function scope_pending_filters_unkitted_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_kitted' => false,
        ]);

        $pending = HardwareRequirement::pending()->get();

        $this->assertGreaterThanOrEqual(2, $pending->count()); // Including setup hardware
        $this->assertTrue($pending->every(fn ($hw) => !$hw->hardware_kitted));
    }

    /** @test */
    public function scope_defective_filters_defective_hardware(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'has_defect' => true,
            'defect_description' => 'Damaged finish',
        ]);

        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
            'has_defect' => false,
        ]);

        $defective = HardwareRequirement::defective()->get();

        $this->assertEquals(1, $defective->count());
        $this->assertTrue($defective->first()->has_defect);
    }

    /** @test */
    public function scope_by_manufacturer_filters_by_manufacturer_name(): void
    {
        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'manufacturer' => 'Blum',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
        ]);

        HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'manufacturer' => 'Rev-a-Shelf',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
        ]);

        $blum = HardwareRequirement::byManufacturer('Blum')->get();

        $this->assertGreaterThanOrEqual(2, $blum->count()); // Including setup hardware
        $this->assertTrue($blum->every(fn ($hw) => str_contains($hw->manufacturer, 'Blum')));
    }

    /** @test */
    public function it_can_store_hinge_specifications(): void
    {
        $hinge = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hinge_type' => 'clip_top_blumotion',
            'hinge_opening_angle' => 110,
            'overlay_dimension_mm' => 18.50,
        ]);

        $this->assertEquals('clip_top_blumotion', $hinge->hinge_type);
        $this->assertEquals(110, $hinge->hinge_opening_angle);
        $this->assertEquals(18.50, $hinge->overlay_dimension_mm);
    }

    /** @test */
    public function it_can_store_slide_specifications(): void
    {
        $slide = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'slide',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawer',
            'slide_type' => 'undermount',
            'slide_length_inches' => 21.0,
            'slide_weight_capacity_lbs' => 100,
        ]);

        $this->assertEquals('undermount', $slide->slide_type);
        $this->assertEquals(21.0, $slide->slide_length_inches);
        $this->assertEquals(100, $slide->slide_weight_capacity_lbs);
    }

    /** @test */
    public function it_can_store_shelf_pin_specifications(): void
    {
        $pin = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'shelf_pin',
            'quantity_required' => 4,
            'unit_of_measure' => 'EA',
            'applied_to' => 'shelf',
            'shelf_pin_type' => 'locking',
            'shelf_pin_diameter_mm' => 5.00,
        ]);

        $this->assertEquals('locking', $pin->shelf_pin_type);
        $this->assertEquals(5.00, $pin->shelf_pin_diameter_mm);
    }

    /** @test */
    public function it_can_store_accessory_dimensions(): void
    {
        $accessory = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'pullout',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'cabinet',
            'accessory_width_inches' => 14.500,
            'accessory_depth_inches' => 21.000,
            'accessory_height_inches' => 10.250,
            'accessory_configuration' => '2-tier',
        ]);

        $this->assertEquals(14.500, $accessory->accessory_width_inches);
        $this->assertEquals(21.000, $accessory->accessory_depth_inches);
        $this->assertEquals(10.250, $accessory->accessory_height_inches);
        $this->assertEquals('2-tier', $accessory->accessory_configuration);
    }

    /** @test */
    public function it_can_track_door_and_drawer_numbers(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'door_number' => 3,
        ]);

        $this->assertEquals(3, $hardware->door_number);
        $this->assertIsInt($hardware->door_number);
    }

    /** @test */
    public function it_can_track_mounting_location(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'mounting_location' => 'left',
        ]);

        $this->assertEquals('left', $hardware->mounting_location);
    }

    /** @test */
    public function it_can_track_installation_details(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'install_sequence' => 5,
            'requires_jig' => true,
            'jig_name' => 'Blum Drilling Jig',
            'installation_notes' => 'Install top hinge first',
        ]);

        $this->assertEquals(5, $hardware->install_sequence);
        $this->assertTrue($hardware->requires_jig);
        $this->assertEquals('Blum Drilling Jig', $hardware->jig_name);
        $this->assertEquals('Install top hinge first', $hardware->installation_notes);
    }

    /** @test */
    public function it_can_track_hardware_status_timestamps(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_kitted' => true,
            'hardware_kitted_at' => now(),
            'hardware_installed' => true,
            'hardware_installed_at' => now(),
        ]);

        $this->assertTrue($hardware->hardware_kitted);
        $this->assertNotNull($hardware->hardware_kitted_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $hardware->hardware_kitted_at);

        $this->assertTrue($hardware->hardware_installed);
        $this->assertNotNull($hardware->hardware_installed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $hardware->hardware_installed_at);
    }

    /** @test */
    public function it_can_track_installed_by_user(): void
    {
        $user = User::create([
            'name' => 'Test Installer',
            'email' => 'installer@test.com',
            'password' => bcrypt('password'),
        ]);

        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_installed' => true,
            'installed_by_user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $hardware->installed_by_user_id);
        $this->assertEquals($user->name, $hardware->installedBy->name);
    }

    /** @test */
    public function it_can_track_allocation_and_issue_status(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'hardware_allocated' => true,
            'hardware_allocated_at' => now(),
            'hardware_issued' => true,
            'hardware_issued_at' => now(),
        ]);

        $this->assertTrue($hardware->hardware_allocated);
        $this->assertNotNull($hardware->hardware_allocated_at);
        $this->assertTrue($hardware->hardware_issued);
        $this->assertNotNull($hardware->hardware_issued_at);
    }

    /** @test */
    public function it_can_track_defects_and_returns(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'has_defect' => true,
            'defect_description' => 'Scratched finish on left arm',
            'returned_to_supplier' => true,
        ]);

        $this->assertTrue($hardware->has_defect);
        $this->assertEquals('Scratched finish on left arm', $hardware->defect_description);
        $this->assertTrue($hardware->returned_to_supplier);
    }

    /** @test */
    public function it_can_track_substitutions(): void
    {
        $substitute = Product::factory()->create(['name' => 'Alternative Hinge']);

        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'substituted_product_id' => $substitute->id,
            'substitution_reason' => 'Original product out of stock',
        ]);

        $this->assertEquals($substitute->id, $hardware->substituted_product_id);
        $this->assertEquals('Original product out of stock', $hardware->substitution_reason);
        $this->assertEquals($substitute->name, $hardware->substitutedProduct->name);
    }

    /** @test */
    public function it_can_store_finish_and_color_match(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 2,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
            'finish' => 'Satin Nickel',
            'color_match' => 'Door hardware package',
        ]);

        $this->assertEquals('Satin Nickel', $hardware->finish);
        $this->assertEquals('Door hardware package', $hardware->color_match);
    }

    /** @test */
    public function it_can_be_updated(): void
    {
        $this->hardware->update([
            'quantity_required' => 4,
            'manufacturer' => 'Rev-a-Shelf',
        ]);

        $this->assertEquals(4, $this->hardware->fresh()->quantity_required);
        $this->assertEquals('Rev-a-Shelf', $this->hardware->fresh()->manufacturer);
    }

    /** @test */
    public function it_can_be_soft_deleted(): void
    {
        $id = $this->hardware->id;
        $this->hardware->delete();

        $this->assertSoftDeleted('hardware_requirements', ['id' => $id]);
        $this->assertNotNull($this->hardware->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete(): void
    {
        $this->hardware->delete();
        $this->hardware->restore();

        $this->assertNull($this->hardware->fresh()->deleted_at);
        $this->assertDatabaseHas('hardware_requirements', [
            'id' => $this->hardware->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function nullable_fields_can_be_null(): void
    {
        $hardware = HardwareRequirement::create([
            'product_id' => $this->product->id,
            'hardware_type' => 'hinge',
            'quantity_required' => 1,
            'unit_of_measure' => 'EA',
            'applied_to' => 'door',
        ]);

        $this->assertNull($hardware->cabinet_specification_id);
        $this->assertNull($hardware->cabinet_run_id);
        $this->assertNull($hardware->manufacturer);
        $this->assertNull($hardware->model_number);
        $this->assertNull($hardware->hinge_type);
        $this->assertNull($hardware->slide_type);
        $this->assertNull($hardware->installation_notes);
        $this->assertNull($hardware->defect_description);
        $this->assertNull($hardware->substituted_product_id);
    }
}
