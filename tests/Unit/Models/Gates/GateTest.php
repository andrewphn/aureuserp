<?php

namespace Tests\Unit\Models\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\ProjectStage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $gate = Gate::factory()->create();

        $this->assertDatabaseHas('projects_gates', [
            'id' => $gate->id,
            'name' => $gate->name,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_stage()
    {
        $stage = ProjectStage::factory()->create();
        $gate = Gate::factory()->create(['stage_id' => $stage->id]);

        $this->assertInstanceOf(ProjectStage::class, $gate->stage);
        $this->assertEquals($stage->id, $gate->stage->id);
    }

    /** @test */
    public function it_has_many_requirements()
    {
        $gate = Gate::factory()->create();
        GateRequirement::factory()->count(3)->create([
            'gate_id' => $gate->id,
            'is_active' => true,
        ]);

        $this->assertCount(3, $gate->requirements);
    }

    /** @test */
    public function requirements_relation_only_returns_active_requirements()
    {
        $gate = Gate::factory()->create();
        GateRequirement::factory()->count(2)->create([
            'gate_id' => $gate->id,
            'is_active' => true,
        ]);
        GateRequirement::factory()->count(1)->create([
            'gate_id' => $gate->id,
            'is_active' => false,
        ]);

        $this->assertCount(2, $gate->requirements);
        $this->assertCount(3, $gate->allRequirements);
    }

    /** @test */
    public function requirements_are_ordered_by_sequence()
    {
        $gate = Gate::factory()->create();
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 30]);
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 10]);
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 20]);

        $sequences = $gate->requirements->pluck('sequence')->toArray();

        $this->assertEquals([10, 20, 30], $sequences);
    }

    /** @test */
    public function it_has_many_evaluations()
    {
        $gate = Gate::factory()->create();
        GateEvaluation::factory()->count(3)->create(['gate_id' => $gate->id]);

        $this->assertCount(3, $gate->evaluations);
    }

    /** @test */
    public function scope_active_filters_active_gates()
    {
        Gate::factory()->create(['is_active' => true]);
        Gate::factory()->create(['is_active' => true]);
        Gate::factory()->create(['is_active' => false]);

        $this->assertCount(2, Gate::active()->get());
    }

    /** @test */
    public function scope_blocking_filters_blocking_gates()
    {
        Gate::factory()->create(['is_blocking' => true]);
        Gate::factory()->create(['is_blocking' => true]);
        Gate::factory()->create(['is_blocking' => false]);

        $this->assertCount(2, Gate::blocking()->get());
    }

    /** @test */
    public function scope_ordered_orders_by_sequence()
    {
        Gate::factory()->create(['sequence' => 30]);
        Gate::factory()->create(['sequence' => 10]);
        Gate::factory()->create(['sequence' => 20]);

        $gates = Gate::ordered()->get();

        $this->assertEquals(10, $gates[0]->sequence);
        $this->assertEquals(20, $gates[1]->sequence);
        $this->assertEquals(30, $gates[2]->sequence);
    }

    /** @test */
    public function scope_for_stage_filters_by_stage_id()
    {
        $stage1 = ProjectStage::factory()->create();
        $stage2 = ProjectStage::factory()->create();

        Gate::factory()->count(2)->create(['stage_id' => $stage1->id]);
        Gate::factory()->count(1)->create(['stage_id' => $stage2->id]);

        $this->assertCount(2, Gate::forStage($stage1->id)->get());
        $this->assertCount(1, Gate::forStage($stage2->id)->get());
    }

    /** @test */
    public function scope_for_stage_key_filters_by_stage_key()
    {
        $stage = ProjectStage::factory()->create(['stage_key' => 'discovery']);
        Gate::factory()->count(2)->create(['stage_id' => $stage->id]);

        $this->assertCount(2, Gate::forStageKey('discovery')->get());
    }

    /** @test */
    public function find_by_key_returns_gate_by_gate_key()
    {
        $gate = Gate::factory()->create(['gate_key' => 'design-lock']);

        $found = Gate::findByKey('design-lock');

        $this->assertNotNull($found);
        $this->assertEquals($gate->id, $found->id);
    }

    /** @test */
    public function find_by_key_returns_null_for_nonexistent_key()
    {
        $found = Gate::findByKey('nonexistent-key');

        $this->assertNull($found);
    }

    /** @test */
    public function applies_any_lock_returns_true_when_any_lock_is_set()
    {
        $gateWithDesignLock = Gate::factory()->create(['applies_design_lock' => true]);
        $gateWithProcurementLock = Gate::factory()->create(['applies_procurement_lock' => true]);
        $gateWithProductionLock = Gate::factory()->create(['applies_production_lock' => true]);
        $gateWithNoLock = Gate::factory()->create([
            'applies_design_lock' => false,
            'applies_procurement_lock' => false,
            'applies_production_lock' => false,
        ]);

        $this->assertTrue($gateWithDesignLock->appliesAnyLock());
        $this->assertTrue($gateWithProcurementLock->appliesAnyLock());
        $this->assertTrue($gateWithProductionLock->appliesAnyLock());
        $this->assertFalse($gateWithNoLock->appliesAnyLock());
    }

    /** @test */
    public function get_lock_types_returns_array_of_applied_locks()
    {
        $gate = Gate::factory()->create([
            'applies_design_lock' => true,
            'applies_procurement_lock' => false,
            'applies_production_lock' => true,
        ]);

        $lockTypes = $gate->getLockTypes();

        $this->assertContains('design', $lockTypes);
        $this->assertNotContains('procurement', $lockTypes);
        $this->assertContains('production', $lockTypes);
    }

    /** @test */
    public function get_lock_types_returns_empty_array_when_no_locks()
    {
        $gate = Gate::factory()->create([
            'applies_design_lock' => false,
            'applies_procurement_lock' => false,
            'applies_production_lock' => false,
        ]);

        $this->assertEmpty($gate->getLockTypes());
    }

    /** @test */
    public function task_templates_json_is_cast_to_array()
    {
        $templates = [
            ['name' => 'Task 1', 'type' => 'follow_up'],
            ['name' => 'Task 2', 'type' => 'review'],
        ];

        $gate = Gate::factory()->create([
            'creates_tasks_on_pass' => true,
            'task_templates_json' => $templates,
        ]);

        $gate->refresh();

        $this->assertIsArray($gate->task_templates_json);
        $this->assertCount(2, $gate->task_templates_json);
        $this->assertEquals('Task 1', $gate->task_templates_json[0]['name']);
    }

    /** @test */
    public function boolean_attributes_are_cast_correctly()
    {
        $gate = Gate::factory()->create([
            'is_blocking' => 1,
            'is_active' => 1,
            'applies_design_lock' => 0,
        ]);

        $gate->refresh();

        $this->assertTrue($gate->is_blocking);
        $this->assertTrue($gate->is_active);
        $this->assertFalse($gate->applies_design_lock);
    }
}
