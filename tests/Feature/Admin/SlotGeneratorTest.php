<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\TimeSlot;
use App\Services\SlotGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_eight_slots_per_weekday(): void
    {
        // 10.08.2026 = Montag, 11.08.2026 = Dienstag -> 2 Werktage.
        $result = app(SlotGenerator::class)->generate('2026-08-10', '2026-08-11');

        $this->assertSame(16, $result['created']);
        $this->assertSame(2, $result['days']);
        $this->assertSame(16, TimeSlot::count());
    }

    public function test_it_skips_weekends(): void
    {
        // 15.08.2026 = Samstag, 16.08.2026 = Sonntag.
        $result = app(SlotGenerator::class)->generate('2026-08-15', '2026-08-16');

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['days']);
        $this->assertSame(0, TimeSlot::count());
    }

    public function test_it_does_not_duplicate_existing_slots(): void
    {
        $generator = app(SlotGenerator::class);
        $generator->generate('2026-08-10', '2026-08-10');

        $second = $generator->generate('2026-08-10', '2026-08-10');

        $this->assertSame(0, $second['created']);
        $this->assertSame(8, $second['existing']);
        $this->assertSame(8, TimeSlot::count());
    }

    public function test_it_records_the_creating_admin(): void
    {
        $admin = AdminUser::factory()->create();

        app(SlotGenerator::class)->generate('2026-08-10', '2026-08-10', $admin->id);

        $this->assertSame($admin->id, TimeSlot::first()->created_by);
    }
}
