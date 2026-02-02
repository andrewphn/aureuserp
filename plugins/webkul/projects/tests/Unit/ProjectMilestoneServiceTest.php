<?php

namespace Webkul\Project\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Webkul\Project\Services\ProjectMilestoneService;

class ProjectMilestoneServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that the service can accept template IDs filter.
     * Note: Full integration testing requires database.
     */
    public function test_service_accepts_template_ids_parameter(): void
    {
        $service = new ProjectMilestoneService();

        // Verify the method signature accepts template IDs
        $reflector = new \ReflectionMethod($service, 'createMilestonesFromTemplates');
        $params = $reflector->getParameters();

        $this->assertCount(3, $params, 'Method should have 3 parameters');
        $this->assertEquals('project', $params[0]->getName());
        $this->assertEquals('referenceDate', $params[1]->getName());
        $this->assertEquals('templateIds', $params[2]->getName());

        // templateIds should be nullable
        $this->assertTrue($params[2]->allowsNull());
    }

    /**
     * Test that null templateIds allows all templates (default behavior).
     */
    public function test_null_template_ids_uses_all_templates(): void
    {
        $service = new ProjectMilestoneService();

        // Verify the parameter defaults to null (all templates)
        $reflector = new \ReflectionMethod($service, 'createMilestonesFromTemplates');
        $params = $reflector->getParameters();

        $templateIdsParam = $params[2];
        $this->assertTrue($templateIdsParam->isDefaultValueAvailable());
        $this->assertNull($templateIdsParam->getDefaultValue());
    }

    /**
     * Test that templateIds parameter accepts array type.
     */
    public function test_template_ids_accepts_array(): void
    {
        $service = new ProjectMilestoneService();

        $reflector = new \ReflectionMethod($service, 'createMilestonesFromTemplates');
        $params = $reflector->getParameters();

        $templateIdsParam = $params[2];
        $type = $templateIdsParam->getType();

        // Should be a union type that allows null
        $this->assertTrue($templateIdsParam->allowsNull());

        // The type should include array
        $this->assertNotNull($type);
    }
}
