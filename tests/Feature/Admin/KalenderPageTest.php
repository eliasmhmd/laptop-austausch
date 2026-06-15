<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Kalender;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\SoftwareCatalog;
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

    public function test_clicking_a_slot_in_move_mode_moves_the_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);

        $booking = app(\App\Services\BookingManager::class)->create($employee->id, $oldSlot->id);

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['verschieben' => $booking->id])
            ->callAction('verschieben', arguments: ['timeSlotId' => $newSlot->id])
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertSame($newSlot->id, $booking->time_slot_id);
        $this->assertSame('available', $oldSlot->refresh()->status);
        $this->assertSame('booked', $newSlot->refresh()->status);
    }

    public function test_move_mode_remembers_the_booking_and_can_be_cancelled(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $component = Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class, ['verschieben' => $booking->id]);

        $this->assertSame($booking->id, $component->get('verschieben'));

        $component->call('abbrechenVerschieben');

        $this->assertNull($component->get('verschieben'));
    }

    public function test_admin_can_purge_all_bookings_and_employees(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $slot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1]);
        Booking::factory()->create(['time_slot_id' => $slot->id, 'status' => 'confirmed']);
        Employee::factory()->count(2)->create();
        $software = SoftwareCatalog::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(Kalender::class)
            ->callAction('purgeData')
            ->assertHasNoActionErrors();

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Employee::count());
        // Software-Katalog bleibt erhalten …
        $this->assertDatabaseHas('software_catalog', ['id' => $software->id]);
        // … und das Zeitfenster ist wieder frei.
        $slot->refresh();
        $this->assertSame('available', $slot->status);
        $this->assertSame(0, $slot->booked_count);
    }

    public function test_viewer_cannot_use_the_purge_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($viewer, 'admin')
            ->test(Kalender::class)
            ->assertActionHidden('purgeData');
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
