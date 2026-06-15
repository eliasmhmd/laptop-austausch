<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingSoftwareRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_remove_a_software_from_a_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create();
        $keep = SoftwareCatalog::factory()->create(['name' => 'MS Office']);
        $drop = SoftwareCatalog::factory()->create(['name' => 'Adobe Acrobat']);

        $keepLink = BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $keep->id, 'is_custom' => false]);
        $dropLink = BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $drop->id, 'is_custom' => false]);

        Livewire::actingAs($admin, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->callInfolistAction('software', 'removeSoftware', data: ['remove' => [$dropLink->id]])
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('booking_software', ['id' => $dropLink->id]);
        $this->assertDatabaseHas('booking_software', ['id' => $keepLink->id]);
    }

    public function test_viewer_cannot_see_the_remove_software_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $booking = Booking::factory()->create();
        $software = SoftwareCatalog::factory()->create();
        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $software->id, 'is_custom' => false]);

        // Für Betrachter:innen ist die Aktion ausgeblendet (visible=false) und
        // damit gar nicht erst im Schema vorhanden.
        Livewire::actingAs($viewer, 'admin')
            ->test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertInfolistActionDoesNotExist('software', 'removeSoftware');
    }
}
