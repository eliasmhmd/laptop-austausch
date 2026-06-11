<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SoftwareCatalogMergerTest extends TestCase
{
    use RefreshDatabase;

    private function merger(): SoftwareCatalogMerger
    {
        return app(SoftwareCatalogMerger::class);
    }

    public function test_merge_repoints_bookings_and_deletes_the_loser(): void
    {
        $winner = SoftwareCatalog::factory()->create(['name' => 'Excel']);
        $loser = SoftwareCatalog::factory()->pending()->create(['name' => 'excel']);

        $b1 = Booking::factory()->create();
        $b2 = Booking::factory()->create();
        BookingSoftware::create(['booking_id' => $b1->id, 'software_catalog_id' => $loser->id, 'is_custom' => false]);
        BookingSoftware::create(['booking_id' => $b2->id, 'software_catalog_id' => $loser->id, 'is_custom' => false]);

        $this->merger()->merge($loser, $winner);

        $this->assertDatabaseMissing('software_catalog', ['id' => $loser->id]);
        $this->assertSame(2, BookingSoftware::where('software_catalog_id', $winner->id)->count());
        $this->assertSame(0, BookingSoftware::where('software_catalog_id', $loser->id)->count());
    }

    public function test_merge_does_not_create_duplicate_links_when_a_booking_has_both(): void
    {
        $winner = SoftwareCatalog::factory()->create(['name' => 'Excel']);
        $loser = SoftwareCatalog::factory()->create(['name' => 'excel']);

        // Eine Buchung hat BEIDE Einträge.
        $booking = Booking::factory()->create();
        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $winner->id, 'is_custom' => false]);
        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $loser->id, 'is_custom' => false]);

        $this->merger()->merge($loser, $winner);

        // Nach dem Zusammenführen genau EINE Verknüpfung (keine Dublette).
        $this->assertSame(1, BookingSoftware::where('booking_id', $booking->id)->count());
        $this->assertSame(1, BookingSoftware::where('software_catalog_id', $winner->id)->count());
    }

    public function test_merge_rejects_merging_an_entry_into_itself(): void
    {
        $software = SoftwareCatalog::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->merger()->merge($software, $software);
    }
}
