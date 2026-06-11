<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Kalender;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KalenderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_admin_root(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_calendar(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk();
    }

    public function test_viewer_can_open_the_calendar(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin')->assertOk();
    }

    public function test_admin_can_book_a_free_slot_from_the_calendar(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['kw' => $slot->calendar_week])
            ->callAction('createBooking', data: ['employee_id' => $employee->id], arguments: ['timeSlotId' => $slot->id])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('bookings', [
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_viewer_cannot_use_the_create_booking_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);

        Livewire::actingAs($viewer, 'admin')
            ->test(Kalender::class, ['kw' => $slot->calendar_week])
            ->assertActionHidden('createBooking', arguments: ['timeSlotId' => $slot->id]);
    }

    public function test_generate_slots_action_is_admin_only(): void
    {
        $slot = TimeSlot::factory()->create();
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['kw' => $slot->calendar_week])
            ->assertActionVisible('generateSlots');

        Livewire::actingAs($viewer, 'admin')
            ->test(Kalender::class, ['kw' => $slot->calendar_week])
            ->assertActionHidden('generateSlots');
    }

    public function test_week_switcher_is_rendered(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        // Zwei Slots in unterschiedlichen Kalenderwochen.
        $a = TimeSlot::factory()->create();
        TimeSlot::factory()->create(['calendar_week' => $a->calendar_week + 1]);

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['kw' => $a->calendar_week])
            ->assertSeeHtml('wire:model.live="kw"')
            ->assertSeeHtml('value="'.($a->calendar_week + 1).'"');
    }

    public function test_changing_the_week_updates_the_calendar(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        // Zwei Buchungen in fest gewählten, unterschiedlichen Kalenderwochen.
        $slot33 = TimeSlot::factory()->create(['calendar_week' => 33]);
        $slot34 = TimeSlot::factory()->create(['calendar_week' => 34]);

        $booking33 = Booking::factory()->create([
            'time_slot_id' => $slot33->id,
            'employee_id' => Employee::factory()->create(['nachname' => 'Aaltonen'])->id,
        ]);
        $booking34 = Booking::factory()->create([
            'time_slot_id' => $slot34->id,
            'employee_id' => Employee::factory()->create(['nachname' => 'Zillgens'])->id,
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['kw' => 33])
            ->assertSee('Aaltonen')
            ->assertDontSee('Zillgens')
            ->set('kw', 34)
            ->assertSee('Zillgens')
            ->assertDontSee('Aaltonen');
    }

    public function test_calendar_shows_only_the_selected_week(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $booking = Booking::factory()->create();
        $kw = $booking->timeSlot->calendar_week;
        $name = $booking->employee->nachname;

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['kw' => $kw])
            ->assertSee($name);
    }
}
