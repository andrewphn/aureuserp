<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\ApiTestHelpers;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Calendar;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiAuth();
    }

    // ============ EMPLOYEES ============

    /** @test */
    public function can_list_employees(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        Employee::factory()->count(5)->create();

        $response = $this->apiGet('/employees');

        $this->assertApiSuccess($response);
        $this->assertPaginatedResponse($response);
    }

    /** @test */
    public function can_create_employee(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        $response = $this->apiPost('/employees', [
            'name' => 'John Doe',
            'work_email' => 'john.doe@example.com',
            'job_title' => 'Cabinet Maker',
        ]);

        $this->assertApiSuccess($response, 201);
        $response->assertJsonPath('data.name', 'John Doe');
    }

    /** @test */
    public function can_show_employee(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        $employee = Employee::factory()->create();

        $response = $this->apiGet("/employees/{$employee->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.id', $employee->id);
    }

    /** @test */
    public function can_update_employee(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        $employee = Employee::factory()->create(['job_title' => 'Junior Maker']);

        $response = $this->apiPut("/employees/{$employee->id}", [
            'job_title' => 'Senior Maker',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.job_title', 'Senior Maker');
    }

    /** @test */
    public function can_delete_employee(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        $employee = Employee::factory()->create();

        $response = $this->apiDelete("/employees/{$employee->id}");

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_search_employees(): void
    {
        if (!class_exists(Employee::class)) {
            $this->markTestSkipped('Employee model not available');
        }

        Employee::factory()->create(['name' => 'John Smith']);
        Employee::factory()->create(['name' => 'Jane Doe']);
        Employee::factory()->create(['name' => 'John Doe']);

        $response = $this->apiGet('/employees?search=John');

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_employees_by_department(): void
    {
        if (!class_exists(Employee::class) || !class_exists(Department::class)) {
            $this->markTestSkipped('Employee or Department model not available');
        }

        $department = Department::factory()->create();
        Employee::factory()->count(2)->create(['department_id' => $department->id]);
        Employee::factory()->count(3)->create();

        $response = $this->apiGet("/employees?filter[department_id]={$department->id}");

        $this->assertApiSuccess($response);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_include_department_relation(): void
    {
        if (!class_exists(Employee::class) || !class_exists(Department::class)) {
            $this->markTestSkipped('Employee or Department model not available');
        }

        $department = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id]);

        $response = $this->apiGet("/employees/{$employee->id}?include=department");

        $this->assertApiSuccess($response);
    }

    // ============ DEPARTMENTS ============

    /** @test */
    public function can_list_departments(): void
    {
        if (!class_exists(Department::class)) {
            $this->markTestSkipped('Department model not available');
        }

        Department::factory()->count(3)->create();

        $response = $this->apiGet('/departments');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_department(): void
    {
        if (!class_exists(Department::class)) {
            $this->markTestSkipped('Department model not available');
        }

        $response = $this->apiPost('/departments', [
            'name' => 'Cabinet Shop',
        ]);

        $this->assertApiSuccess($response, 201);
    }

    /** @test */
    public function can_update_department(): void
    {
        if (!class_exists(Department::class)) {
            $this->markTestSkipped('Department model not available');
        }

        $department = Department::factory()->create(['name' => 'Old Name']);

        $response = $this->apiPut("/departments/{$department->id}", [
            'name' => 'New Name',
        ]);

        $this->assertApiSuccess($response);
    }

    // ============ CALENDARS ============

    /** @test */
    public function can_list_calendars(): void
    {
        if (!class_exists(Calendar::class)) {
            $this->markTestSkipped('Calendar model not available');
        }

        Calendar::factory()->count(3)->create();

        $response = $this->apiGet('/calendars');

        $this->assertApiSuccess($response);
    }

    /** @test */
    public function can_create_calendar(): void
    {
        if (!class_exists(Calendar::class)) {
            $this->markTestSkipped('Calendar model not available');
        }

        $response = $this->apiPost('/calendars', [
            'name' => 'Shop Calendar',
        ]);

        $this->assertApiSuccess($response, 201);
    }
}
