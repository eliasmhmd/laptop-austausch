<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_employees(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employees = Employee::factory()->count(3)->create();

        Livewire::actingAs($admin, 'admin')
            ->test(ListEmployees::class)
            ->callTableBulkAction('delete', $employees);

        foreach ($employees as $employee) {
            $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        }
    }

    public function test_deleting_an_employee_frees_their_booked_slot(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1]);
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListEmployees::class)
            ->callTableBulkAction('delete', collect([$employee]));

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        // Buchung wurde per Cascade entfernt …
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        // … und das Zeitfenster ist wieder frei.
        $slot->refresh();
        $this->assertSame('available', $slot->status);
        $this->assertSame(0, $slot->booked_count);
    }

    public function test_viewer_cannot_use_the_bulk_delete_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $employee = Employee::factory()->create();

        Livewire::actingAs($viewer, 'admin')
            ->test(ListEmployees::class)
            ->assertTableBulkActionHidden('delete');

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }
}
