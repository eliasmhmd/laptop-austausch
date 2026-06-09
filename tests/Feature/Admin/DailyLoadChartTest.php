<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\DailyLoadChart;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DailyLoadChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_dashboard(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk();
    }

    public function test_viewer_can_open_the_dashboard(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin')->assertOk();
    }

    public function test_chart_counts_active_bookings_per_day_and_ignores_cancellations(): void
    {
        // Tag A: zwei aktive Termine (bestätigt + abgeschlossen) und eine Stornierung.
        $slotA1 = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '08:00:00']);
        $slotA2 = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '09:00:00']);
        $slotA3 = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '10:00:00']);
        // Tag B: ein aktiver Termin.
        $slotB1 = TimeSlot::factory()->create(['slot_date' => '2026-08-11', 'start_time' => '08:00:00']);

        Booking::factory()->create(['time_slot_id' => $slotA1->id, 'status' => 'confirmed']);
        Booking::factory()->create(['time_slot_id' => $slotA2->id, 'status' => 'completed']);
        Booking::factory()->cancelled()->create(['time_slot_id' => $slotA3->id]);
        Booking::factory()->create(['time_slot_id' => $slotB1->id, 'status' => 'confirmed']);

        $widget = new DailyLoadChart();
        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);
        /** @var array{datasets: array<int, array{data: array<int, int>}>, labels: array<int, string>} $payload */
        $payload = $method->invoke($widget);

        $data = $payload['datasets'][0]['data'];

        // Tage chronologisch: 10.08. zuerst, dann 11.08.
        $this->assertCount(2, $data);
        $this->assertSame(2, $data[0]); // Stornierung zählt nicht mit
        $this->assertSame(1, $data[1]);
    }
}
