<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeReminderTest extends TestCase
{
    use RefreshDatabase;

    private function bookFor(Employee $employee, string $status): void
    {
        $slot = TimeSlot::factory()->create();
        Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => $status,
        ]);
    }

    public function test_scope_finds_only_employees_without_a_settled_booking(): void
    {
        $withConfirmed = Employee::factory()->create();
        $this->bookFor($withConfirmed, 'confirmed');

        $withCompleted = Employee::factory()->create();
        $this->bookFor($withCompleted, 'completed');

        $cancelled = Employee::factory()->create();
        $this->bookFor($cancelled, 'cancelled');

        $never = Employee::factory()->create();

        $ids = Employee::ohneTermin()->pluck('id');

        $this->assertContains($never->id, $ids);
        $this->assertContains($cancelled->id, $ids); // storniert → muss neu buchen
        $this->assertNotContains($withConfirmed->id, $ids);
        $this->assertNotContains($withCompleted->id, $ids);
    }

    public function test_reminder_action_is_visible_for_employees_without_termin(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create(['email' => 'max.muster@kreisgg.de']);

        Livewire::actingAs($admin, 'admin')
            ->test(ListEmployees::class)
            ->assertTableActionVisible('remind', $employee);
    }

    public function test_reminder_mailto_contains_address_subject_and_german_body(): void
    {
        $employee = Employee::factory()->create([
            'vorname' => 'Max',
            'nachname' => 'Muster',
            'email' => 'max.muster@kreisgg.de',
        ]);

        $url = EmployeesTable::reminderMailto($employee);

        $this->assertStringStartsWith('mailto:max.muster@kreisgg.de?', $url);
        $this->assertStringContainsString('subject=', $url);
        $this->assertStringContainsString('body=', $url);

        // Betreff + Text sind prozent-kodiert – nach dem Dekodieren lesbar.
        $decoded = rawurldecode($url);
        $this->assertStringContainsString('Termin für den Laptop-Austausch', $decoded);
        $this->assertStringContainsString('Max Muster', $decoded);
    }

    public function test_reminder_action_is_hidden_for_employees_with_a_termin(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create(['email' => 'hat.termin@kreisgg.de']);
        $this->bookFor($employee, 'confirmed');

        Livewire::actingAs($admin, 'admin')
            ->test(ListEmployees::class)
            ->assertTableActionHidden('remind', $employee);
    }

    public function test_reminder_action_is_hidden_without_an_email(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create(['email' => '']);

        Livewire::actingAs($admin, 'admin')
            ->test(ListEmployees::class)
            ->assertTableActionHidden('remind', $employee);
    }
}
