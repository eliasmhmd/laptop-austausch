<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingManagementActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_booking_button_links_to_the_calendar(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->assertActionHasUrl('createBooking', route('filament.admin.pages.kalender'));
    }

    public function test_viewer_cannot_see_the_create_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($viewer, 'admin')
            ->test(ListBookings::class)
            ->assertActionHidden('createBooking');
    }

    public function test_move_booking_button_links_to_the_calendar_with_the_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $booking = app(\App\Services\BookingManager::class)->create($employee->id, $slot->id);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHasUrl('moveBooking', \App\Filament\Pages\Kalender::getUrl(['verschieben' => $booking->id]));
    }

    public function test_admin_can_cancel_a_booking_and_free_the_slot(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $booking = app(\App\Services\BookingManager::class)->create($employee->id, $slot->id);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('cancelBooking', data: ['cancellation_reason' => 'Gerät bereits getauscht']);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('Gerät bereits getauscht', $booking->cancellation_reason);
        $this->assertSame('available', $slot->refresh()->status);
    }

    public function test_cancel_and_move_are_hidden_for_a_cancelled_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->cancelled()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('cancelBooking')
            ->assertActionHidden('moveBooking');
    }

    public function test_viewer_cannot_see_cancel_or_move_actions(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        Livewire::actingAs($viewer, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('cancelBooking')
            ->assertActionHidden('moveBooking');
    }
}
