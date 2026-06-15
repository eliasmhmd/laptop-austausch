<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_bookings(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $bookings = collect(range(1, 3))->map(function () {
            $slot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1]);

            return Booking::factory()->create([
                'time_slot_id' => $slot->id,
                'status' => 'confirmed',
            ]);
        });

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->callTableBulkAction('delete', $bookings);

        foreach ($bookings as $booking) {
            $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        }
    }

    public function test_deleting_a_booking_frees_its_slot(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $slot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1]);
        $booking = Booking::factory()->create([
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->callTableBulkAction('delete', collect([$booking]));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $slot->refresh();
        $this->assertSame('available', $slot->status);
        $this->assertSame(0, $slot->booked_count);
    }

    public function test_viewer_cannot_use_the_bulk_delete_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $booking = Booking::factory()->create();

        Livewire::actingAs($viewer, 'admin')
            ->test(ListBookings::class)
            ->assertTableBulkActionHidden('delete');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }
}
