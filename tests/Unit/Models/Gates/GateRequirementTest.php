<?php

namespace Tests\Unit\Models\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GateRequirementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $requirement = GateRequirement::factory()->create();

        $this->assertDatabaseHas('projects_gate_requirements', [
            'id' => $requirement->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_gate()
    {
        $gate = Gate::factory()->create();
        $requirement = GateRequirement::factory()->create(['gate_id' => $gate->id]);

        $this->assertInstanceOf(Gate::class, $requirement->gate);
        $this->assertEquals($gate->id, $requirement->gate->id);
    }

    /** @test */
    public function scope_active_filters_active_requirements()
    {
        $gate = Gate::factory()->create();
        GateRequirement::factory()->count(2)->create(['gate_id' => $gate->id, 'is_active' => true]);
        GateRequirement::factory()->count(1)->create(['gate_id' => $gate->id, 'is_active' => false]);

        $this->assertCount(2, GateRequirement::active()->get());
    }

    /** @test */
    public function scope_ordered_orders_by_sequence()
    {
        $gate = Gate::factory()->create();
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 30]);
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 10]);
        GateRequirement::factory()->create(['gate_id' => $gate->id, 'sequence' => 20]);

        $requirements = GateRequirement::ordered()->get();

        $this->assertEquals(10, $requirements[0]->sequence);
        $this->assertEquals(20, $requirements[1]->sequence);
        $this->assertEquals(30, $requirements[2]->sequence);
    }

    /** @test */
    public function is_custom_check_returns_true_for_custom_type()
    {
        $customRequirement = GateRequirement::factory()->customCheck('App\\Test', 'check')->create();
        $regularRequirement = GateRequirement::factory()->fieldNotNull('name')->create();

        $this->assertTrue($customRequirement->isCustomCheck());
        $this->assertFalse($regularRequirement->isCustomCheck());
    }

    /** @test */
    public function get_decoded_target_value_returns_null_for_empty_value()
    {
        $requirement = GateRequirement::factory()->create(['target_value' => null]);

        $this->assertNull($requirement->getDecodedTargetValue());
    }

    /** @test */
    public function get_decoded_target_value_returns_string_for_non_json()
    {
        $requirement = GateRequirement::factory()->create(['target_value' => 'simple_value']);

        $this->assertEquals('simple_value', $requirement->getDecodedTargetValue());
    }

    /** @test */
    public function get_decoded_target_value_decodes_json()
    {
        $requirement = GateRequirement::factory()->create([
            'target_value' => json_encode(['key' => 'value']),
        ]);

        $decoded = $requirement->getDecodedTargetValue();

        $this->assertIsArray($decoded);
        $this->assertEquals('value', $decoded['key']);
    }

    /** @test */
    public function has_action_returns_true_when_both_label_and_route_are_set()
    {
        $withAction = GateRequirement::factory()
            ->withAction('Fix Issue', 'projects.edit')
            ->create();
        $withoutAction = GateRequirement::factory()->create([
            'action_label' => null,
            'action_route' => null,
        ]);
        $partialAction = GateRequirement::factory()->create([
            'action_label' => 'Label Only',
            'action_route' => null,
        ]);

        $this->assertTrue($withAction->hasAction());
        $this->assertFalse($withoutAction->hasAction());
        $this->assertFalse($partialAction->hasAction());
    }

    /** @test */
    public function get_custom_check_identifier_returns_class_at_method_format()
    {
        $requirement = GateRequirement::factory()->customCheck('App\\Checks\\MyChecker', 'verifyData')->create();

        $identifier = $requirement->getCustomCheckIdentifier();

        $this->assertEquals('App\\Checks\\MyChecker@verifyData', $identifier);
    }

    /** @test */
    public function get_custom_check_identifier_returns_null_for_non_custom_types()
    {
        $requirement = GateRequirement::factory()->fieldNotNull('name')->create();

        $this->assertNull($requirement->getCustomCheckIdentifier());
    }

    /** @test */
    public function get_requirement_types_returns_all_types()
    {
        $types = GateRequirement::getRequirementTypes();

        $this->assertArrayHasKey(GateRequirement::TYPE_FIELD_NOT_NULL, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_FIELD_EQUALS, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_FIELD_GREATER_THAN, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_RELATION_EXISTS, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_RELATION_COUNT, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_ALL_CHILDREN_PASS, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_DOCUMENT_UPLOADED, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_PAYMENT_RECEIVED, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_TASK_COMPLETED, $types);
        $this->assertArrayHasKey(GateRequirement::TYPE_CUSTOM_CHECK, $types);
    }

    /** @test */
    public function factory_field_not_null_state_works()
    {
        $requirement = GateRequirement::factory()
            ->fieldNotNull('partner_id', 'Project')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_FIELD_NOT_NULL, $requirement->requirement_type);
        $this->assertEquals('partner_id', $requirement->target_field);
        $this->assertEquals('Project', $requirement->target_model);
    }

    /** @test */
    public function factory_field_equals_state_works()
    {
        $requirement = GateRequirement::factory()
            ->fieldEquals('status', 'approved')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_FIELD_EQUALS, $requirement->requirement_type);
        $this->assertEquals('status', $requirement->target_field);
        $this->assertEquals('approved', $requirement->target_value);
    }

    /** @test */
    public function factory_relation_exists_state_works()
    {
        $requirement = GateRequirement::factory()
            ->relationExists('orders')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_RELATION_EXISTS, $requirement->requirement_type);
        $this->assertEquals('orders', $requirement->target_relation);
    }

    /** @test */
    public function factory_relation_count_state_works()
    {
        $requirement = GateRequirement::factory()
            ->relationCount('cabinets', 5, '>=')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_RELATION_COUNT, $requirement->requirement_type);
        $this->assertEquals('cabinets', $requirement->target_relation);
        $this->assertEquals('5', $requirement->target_value);
        $this->assertEquals('>=', $requirement->comparison_operator);
    }

    /** @test */
    public function factory_all_children_pass_state_works()
    {
        $requirement = GateRequirement::factory()
            ->allChildrenPass('cabinets', 'qc_passed', true)
            ->create();

        $this->assertEquals(GateRequirement::TYPE_ALL_CHILDREN_PASS, $requirement->requirement_type);
        $this->assertEquals('cabinets', $requirement->target_relation);
        $this->assertEquals('qc_passed', $requirement->target_field);
    }

    /** @test */
    public function factory_document_uploaded_state_works()
    {
        $requirement = GateRequirement::factory()
            ->documentUploaded('design_drawings')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_DOCUMENT_UPLOADED, $requirement->requirement_type);
        $this->assertEquals('design_drawings', $requirement->target_value);
    }

    /** @test */
    public function factory_payment_received_state_works()
    {
        $requirement = GateRequirement::factory()
            ->paymentReceived('deposit')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_PAYMENT_RECEIVED, $requirement->requirement_type);
        $this->assertEquals('deposit', $requirement->target_value);
    }

    /** @test */
    public function factory_task_completed_state_works()
    {
        $requirement = GateRequirement::factory()
            ->taskCompleted('design_review')
            ->create();

        $this->assertEquals(GateRequirement::TYPE_TASK_COMPLETED, $requirement->requirement_type);
        $this->assertEquals('design_review', $requirement->target_value);
    }
}
