<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('KVGG-Nummer');
    }

    public function test_employee_can_login_with_valid_credentials(): void
    {
        $employee = Employee::factory()->create([
            'kvgg_nummer' => 'KVGG-12345',
            'pc_nummer' => 'PC-654321',
        ]);

        $response = $this->post('/login', [
            'kvgg_nummer' => 'KVGG-12345',
            'pc_nummer' => 'PC-654321',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($employee, 'employee');
    }

    public function test_employee_cannot_login_with_wrong_pc_nummer(): void
    {
        Employee::factory()->create([
            'kvgg_nummer' => 'KVGG-12345',
            'pc_nummer' => 'PC-654321',
        ]);

        $this->from('/login')
            ->post('/login', [
                'kvgg_nummer' => 'KVGG-12345',
                'pc_nummer' => 'PC-000000',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('kvgg_nummer');

        $this->assertGuest('employee');
    }

    public function test_unknown_kvgg_nummer_is_rejected(): void
    {
        $this->post('/login', [
            'kvgg_nummer' => 'KVGG-99999',
            'pc_nummer' => 'PC-123456',
        ])->assertSessionHasErrors('kvgg_nummer');

        $this->assertGuest('employee');
    }

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_employee_can_access_dashboard(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee, 'employee')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($employee->vorname, escape: false);
    }

    public function test_employee_can_logout(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee, 'employee')
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest('employee');
    }
}
