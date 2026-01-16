<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\Stretcher;
use Webkul\Project\Models\Faceframe;

class CabinetComponentsApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected Cabinet $cabinet;
    protected ?CabinetSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();

        $cabinetRun = CabinetRun::factory()->create();
        $this->cabinet = Cabinet::factory()->create(['cabinet_run_id' => $cabinetRun->id]);

        // Create section if model exists
        if (class_exists(CabinetSection::class)) {
            $this->section = CabinetSection::factory()->create(['cabinet_id' => $this->cabinet->id]);
        }
    }

    // ============ CABINET SECTIONS ============

    /** @test */
    public function can_list_sections_for_cabinet(): void
    {
        if (!class_exists(CabinetSection::class)) {
            $this->markTestSkipped('CabinetSection model not available');
        }

        CabinetSection::factory()->count(2)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet("/cabinets/{$this->cabinet->id}/sections");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_section(): void
    {
        if (!class_exists(CabinetSection::class)) {
            $this->markTestSkipped('CabinetSection model not available');
        }

        $response = $this->apiPost("/cabinets/{$this->cabinet->id}/sections", [
            'name' => 'Main Section',
            'width_inches' => 24.0,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    // ============ DRAWERS ============

    /** @test */
    public function can_list_drawers(): void
    {
        if (!class_exists(Drawer::class)) {
            $this->markTestSkipped('Drawer model not available');
        }

        Drawer::factory()->count(3)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/drawers');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_drawer_for_section(): void
    {
        if (!class_exists(Drawer::class) || !$this->section) {
            $this->markTestSkipped('Drawer or CabinetSection model not available');
        }

        $response = $this->apiPost("/sections/{$this->section->id}/drawers", [
            'drawer_number' => 1,
            'height_inches' => 6.0,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_show_drawer(): void
    {
        if (!class_exists(Drawer::class)) {
            $this->markTestSkipped('Drawer model not available');
        }

        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet("/drawers/{$drawer->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $drawer->id);
    }

    /** @test */
    public function can_update_drawer(): void
    {
        if (!class_exists(Drawer::class)) {
            $this->markTestSkipped('Drawer model not available');
        }

        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiPut("/drawers/{$drawer->id}", [
            'height_inches' => 8.0,
        ]);

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_delete_drawer(): void
    {
        if (!class_exists(Drawer::class)) {
            $this->markTestSkipped('Drawer model not available');
        }

        $drawer = Drawer::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiDelete("/drawers/{$drawer->id}");

        $this->assertApiSuccess($response);
    }

    // ============ DOORS ============

    /** @test */
    public function can_list_doors(): void
    {
        if (!class_exists(Door::class)) {
            $this->markTestSkipped('Door model not available');
        }

        Door::factory()->count(3)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/doors');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_door(): void
    {
        if (!class_exists(Door::class) || !$this->section) {
            $this->markTestSkipped('Door or CabinetSection model not available');
        }

        $response = $this->apiPost("/sections/{$this->section->id}/doors", [
            'door_number' => 1,
            'width_inches' => 12.0,
            'height_inches' => 30.0,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_update_door(): void
    {
        if (!class_exists(Door::class)) {
            $this->markTestSkipped('Door model not available');
        }

        $door = Door::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiPut("/doors/{$door->id}", [
            'width_inches' => 14.0,
        ]);

        $this->assertApiSuccess($response);
    }

    // ============ SHELVES ============

    /** @test */
    public function can_list_shelves(): void
    {
        if (!class_exists(Shelf::class)) {
            $this->markTestSkipped('Shelf model not available');
        }

        Shelf::factory()->count(2)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/shelves');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_shelf(): void
    {
        if (!class_exists(Shelf::class) || !$this->section) {
            $this->markTestSkipped('Shelf or CabinetSection model not available');
        }

        $response = $this->apiPost("/sections/{$this->section->id}/shelves", [
            'shelf_number' => 1,
            'is_adjustable' => true,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    // ============ PULLOUTS ============

    /** @test */
    public function can_list_pullouts(): void
    {
        if (!class_exists(Pullout::class)) {
            $this->markTestSkipped('Pullout model not available');
        }

        Pullout::factory()->count(2)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/pullouts');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_pullout(): void
    {
        if (!class_exists(Pullout::class) || !$this->section) {
            $this->markTestSkipped('Pullout or CabinetSection model not available');
        }

        $response = $this->apiPost("/sections/{$this->section->id}/pullouts", [
            'pullout_number' => 1,
            'type' => 'shelf',
        ]);

        $this->assertApiSuccess($response, 201);
    }

    // ============ STRETCHERS ============

    /** @test */
    public function can_list_stretchers(): void
    {
        if (!class_exists(Stretcher::class)) {
            $this->markTestSkipped('Stretcher model not available');
        }

        Stretcher::factory()->count(2)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/stretchers');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_stretcher_for_cabinet(): void
    {
        if (!class_exists(Stretcher::class)) {
            $this->markTestSkipped('Stretcher model not available');
        }

        $response = $this->apiPost("/cabinets/{$this->cabinet->id}/stretchers", [
            'position' => 'top',
            'width_inches' => 3.0,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_update_stretcher(): void
    {
        if (!class_exists(Stretcher::class)) {
            $this->markTestSkipped('Stretcher model not available');
        }

        $stretcher = Stretcher::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiPut("/stretchers/{$stretcher->id}", [
            'width_inches' => 4.0,
        ]);

        $this->assertApiSuccess($response);
    }

    // ============ FACEFRAMES ============

    /** @test */
    public function can_list_faceframes(): void
    {
        if (!class_exists(Faceframe::class)) {
            $this->markTestSkipped('Faceframe model not available');
        }

        Faceframe::factory()->count(2)->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiGet('/faceframes');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_faceframe_for_cabinet(): void
    {
        if (!class_exists(Faceframe::class)) {
            $this->markTestSkipped('Faceframe model not available');
        }

        $response = $this->apiPost("/cabinets/{$this->cabinet->id}/faceframes", [
            'rail_width_inches' => 2.0,
            'stile_width_inches' => 2.0,
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_update_faceframe(): void
    {
        if (!class_exists(Faceframe::class)) {
            $this->markTestSkipped('Faceframe model not available');
        }

        $faceframe = Faceframe::factory()->create(['cabinet_id' => $this->cabinet->id]);

        $response = $this->apiPut("/faceframes/{$faceframe->id}", [
            'rail_width_inches' => 2.5,
        ]);

        $this->assertApiSuccess($response);
    }
}
