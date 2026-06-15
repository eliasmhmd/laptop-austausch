<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Erinnerungen;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErinnerungenPageTest extends TestCase
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

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/erinnerungen')->assertRedirect('/admin/login');
    }

    public function test_page_lists_only_employees_without_a_termin(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $withConfirmed = Employee::factory()->create();
        $this->bookFor($withConfirmed, 'confirmed');

        $cancelled = Employee::factory()->create();
        $this->bookFor($cancelled, 'cancelled');

        $never = Employee::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(Erinnerungen::class)
            ->assertCanSeeTableRecords([$never, $cancelled])
            ->assertCanNotSeeTableRecords([$withConfirmed]);
    }

    public function test_viewer_can_open_the_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/erinnerungen')->assertOk();
    }

    public function test_reminder_action_links_to_a_mailto(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create(['email' => 'max.muster@kreisgg.de']);

        Livewire::actingAs($admin, 'admin')
            ->test(Erinnerungen::class)
            ->assertTableActionVisible('remind', $employee);
    }

    public function test_reminder_action_is_hidden_without_an_email(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create(['email' => '']);

        Livewire::actingAs($admin, 'admin')
            ->test(Erinnerungen::class)
            ->assertTableActionHidden('remind', $employee);
    }

    public function test_mailto_contains_address_subject_and_german_body(): void
    {
        $employee = Employee::factory()->create([
            'vorname' => 'Max',
            'nachname' => 'Muster',
            'email' => 'max.muster@kreisgg.de',
        ]);

        $url = Erinnerungen::reminderMailto($employee);

        $this->assertStringStartsWith('mailto:max.muster@kreisgg.de?', $url);
        $this->assertStringContainsString('subject=', $url);

        $decoded = rawurldecode($url);
        $this->assertStringContainsString('Termin für den Laptop-Austausch', $decoded);
        $this->assertStringContainsString('Max Muster', $decoded);
    }
}
