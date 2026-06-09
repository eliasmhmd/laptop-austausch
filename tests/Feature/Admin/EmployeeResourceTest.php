<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_employees_list(): void
    {
        $this->get('/admin/employees')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_employees_list(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/employees')
            ->assertOk();
    }

    public function test_viewer_can_open_the_employees_list(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/employees')
            ->assertOk();
    }

    public function test_admin_can_open_an_employee_detail_view(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get("/admin/employees/{$employee->id}")
            ->assertOk();
    }
}
