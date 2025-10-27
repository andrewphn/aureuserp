<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Inventory\Models\WoodworkingMaterialCategory;
use Webkul\Product\Models\Product;

class WoodworkingMaterialCategoryTest extends TestCase
{
    use DatabaseTransactions;

    protected WoodworkingMaterialCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = WoodworkingMaterialCategory::create([
            'name' => 'Sheet Goods - Plywood', 'code' => 'SG-PLYWOOD',
            'code' => 'SG-PLY',
            'description' => 'Plywood sheet goods for cabinet boxes',
            'sort_order' => 10,
        ]);
    }

    /** @test */
    public function it_can_be_created_with_valid_attributes(): void
    {
        $category = WoodworkingMaterialCategory::create([
            'name' => 'Hardware - Hinges', 'code' => 'HW-HINGES',
            'code' => 'HW-HINGE',
            'description' => 'Cabinet door hinges',
            'sort_order' => 5,
        ]);

        $this->assertInstanceOf(WoodworkingMaterialCategory::class, $category);
        $this->assertEquals('Hardware - Hinges', $category->name);
        $this->assertEquals('HW-HINGE', $category->code);
        $this->assertEquals(5, $category->sort_order);
    }

    /** @test */
    public function it_can_be_created_with_minimal_attributes(): void
    {
        $category = WoodworkingMaterialCategory::create([
            'name' => 'Minimal Test Category',
            'sort_order' => 0,
        ]);

        $this->assertInstanceOf(WoodworkingMaterialCategory::class, $category);
        $this->assertEquals('Minimal Test Category', $category->name);
        $this->assertNull($category->code);
        $this->assertNull($category->description);
        $this->assertEquals(0, $category->sort_order);
    }

    /** @test */
    public function it_casts_sort_order_to_integer(): void
    {
        $this->assertIsInt($this->category->sort_order);
        $this->assertEquals(10, $this->category->sort_order);
    }

    /** @test */
    public function it_has_many_products(): void
    {
        // This test would need Product factory, keeping as placeholder
        $this->assertTrue(method_exists($this->category, 'products'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $this->category->products()
        );
    }

    /** @test */
    public function scope_by_type_filters_categories_by_prefix(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Hardware - Slides', 'code' => 'HW-SLIDES',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Hardware - Hinges', 'code' => 'HW-HINGES',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Solid Wood - Oak', 'code' => 'SW-OAK',
            'sort_order' => 0,
        ]);

        $hardwareCategories = WoodworkingMaterialCategory::byType('Hardware')->get();

        $this->assertEquals(2, $hardwareCategories->count());
        $this->assertTrue($hardwareCategories->every(fn ($cat) => str_starts_with($cat->name, 'Hardware')));
    }

    /** @test */
    public function scope_sheet_goods_filters_categories(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Sheet Goods - MDF', 'code' => 'SG-MDF',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Hardware - Hinges', 'code' => 'HW-HINGES',
            'sort_order' => 0,
        ]);

        $sheetGoods = WoodworkingMaterialCategory::sheetGoods()->get();

        $this->assertEquals(2, $sheetGoods->count()); // Including the setUp() category
        $this->assertTrue($sheetGoods->every(fn ($cat) => str_starts_with($cat->name, 'Sheet Goods')));
    }

    /** @test */
    public function scope_solid_wood_filters_categories(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Solid Wood - Oak', 'code' => 'SW-OAK',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Solid Wood - Maple', 'code' => 'SW-MAPLE',
            'sort_order' => 0,
        ]);

        $solidWood = WoodworkingMaterialCategory::solidWood()->get();

        $this->assertEquals(2, $solidWood->count());
        $this->assertTrue($solidWood->every(fn ($cat) => str_starts_with($cat->name, 'Solid Wood')));
    }

    /** @test */
    public function scope_hardware_filters_categories(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Hardware - Hinges', 'code' => 'HW-HINGES',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Sheet Goods - MDF', 'code' => 'SG-MDF',
            'sort_order' => 0,
        ]);

        $hardware = WoodworkingMaterialCategory::hardware()->get();

        $this->assertEquals(1, $hardware->count());
        $this->assertTrue($hardware->every(fn ($cat) => str_starts_with($cat->name, 'Hardware')));
    }

    /** @test */
    public function scope_finishes_filters_categories(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Finishes - Stain', 'code' => 'FIN-STAIN',
            'sort_order' => 0,
        ]);
        WoodworkingMaterialCategory::create([
            'name' => 'Finishes - Lacquer', 'code' => 'FIN-LACQ',
            'sort_order' => 0,
        ]);

        $finishes = WoodworkingMaterialCategory::finishes()->get();

        $this->assertEquals(2, $finishes->count());
        $this->assertTrue($finishes->every(fn ($cat) => str_starts_with($cat->name, 'Finishes')));
    }

    /** @test */
    public function scope_accessories_filters_categories(): void
    {
        WoodworkingMaterialCategory::create([
            'name' => 'Accessories - Shelf Pins', 'code' => 'ACC-PINS',
            'sort_order' => 0,
        ]);

        $accessories = WoodworkingMaterialCategory::accessories()->get();

        $this->assertEquals(1, $accessories->count());
        $this->assertTrue($accessories->every(fn ($cat) => str_starts_with($cat->name, 'Accessories')));
    }

    /** @test */
    public function scope_ordered_sorts_by_sort_order(): void
    {
        WoodworkingMaterialCategory::create(['name' => 'Cat C', 'code' => 'CAT-C', 'sort_order' => 30]);
        WoodworkingMaterialCategory::create(['name' => 'Cat A', 'code' => 'CAT-A', 'sort_order' => 10]);
        WoodworkingMaterialCategory::create(['name' => 'Cat B', 'code' => 'CAT-B', 'sort_order' => 20]);

        $ordered = WoodworkingMaterialCategory::ordered()->get();

        $this->assertEquals('Cat A', $ordered->first()->name);
        $this->assertEquals('Cat C', $ordered->last()->name);
    }

    /** @test */
    public function it_can_update_sort_order(): void
    {
        $this->category->update(['sort_order' => 50]);

        $this->assertEquals(50, $this->category->fresh()->sort_order);
    }

    /** @test */
    public function it_can_update_code(): void
    {
        $this->category->update(['code' => 'SG-PLY-NEW']);

        $this->assertEquals('SG-PLY-NEW', $this->category->fresh()->code);
    }

    /** @test */
    public function it_can_update_description(): void
    {
        $newDescription = 'Updated plywood description';
        $this->category->update(['description' => $newDescription]);

        $this->assertEquals($newDescription, $this->category->fresh()->description);
    }

    /** @test */
    public function it_can_be_deleted(): void
    {
        $id = $this->category->id;
        $this->category->delete();

        $this->assertDatabaseMissing('woodworking_material_categories', ['id' => $id]);
    }

    /** @test */
    public function multiple_categories_can_have_same_prefix(): void
    {
        WoodworkingMaterialCategory::create(['name' => 'Hardware - Hinges', 'code' => 'HW-HNG', 'sort_order' => 0]);
        WoodworkingMaterialCategory::create(['name' => 'Hardware - Slides', 'code' => 'HW-SLD', 'sort_order' => 0]);
        WoodworkingMaterialCategory::create(['name' => 'Hardware - Pins', 'code' => 'HW-PIN', 'sort_order' => 0]);

        $count = WoodworkingMaterialCategory::hardware()->count();

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function code_can_be_null(): void
    {
        $category = WoodworkingMaterialCategory::create([
            'name' => 'Test Category For Null Code',
            'sort_order' => 0,
        ]);

        $this->assertNull($category->code);
    }

    /** @test */
    public function description_can_be_null(): void
    {
        $category = WoodworkingMaterialCategory::create([
            'name' => 'Test Category For Null Description',
            'code' => 'TEST-NULL-DESC',
            'sort_order' => 0,
        ]);

        $this->assertNull($category->description);
    }
}
