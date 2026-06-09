<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\AdminUser;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingStatusActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_a_booking_as_no_show_with_a_note(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('markNoShow', data: ['unplanned_note' => 'Nicht erschienen, nicht erreichbar.']);

        $booking->refresh();
        $this->assertSame('no_show', $booking->status);
        $this->assertSame('Nicht erschienen, nicht erreichbar.', $booking->unplanned_note);
    }

    public function test_admin_can_mark_a_booking_as_sick(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('markSick', data: ['unplanned_note' => '']);

        $booking->refresh();
        $this->assertSame('sick', $booking->status);
        $this->assertNull($booking->unplanned_note); // leeres Feld -> NULL
    }

    public function test_admin_can_reset_status_to_confirmed(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create([
            'status' => 'no_show',
            'unplanned_note' => 'War krank',
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('resetToConfirmed');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertNull($booking->unplanned_note);
    }

    public function test_viewer_cannot_use_the_status_actions(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        Livewire::actingAs($viewer, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('markNoShow')
            ->assertActionHidden('markSick');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
    }

    public function test_reset_action_is_hidden_for_a_confirmed_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertActionHidden('resetToConfirmed')
            ->assertActionVisible('markNoShow');
    }
}
