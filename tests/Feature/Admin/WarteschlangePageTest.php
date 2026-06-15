<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Warteschlange;
use App\Models\AdminUser;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarteschlangePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/warteschlange')->assertRedirect('/admin/login');
    }

    public function test_viewer_can_open_the_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/warteschlange')->assertOk();
    }

    public function test_offen_tab_shows_only_unreviewed_bookings(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $offen = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => null]);
        $bereit = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => now()]);

        Livewire::actingAs($admin, 'admin')
            ->test(Warteschlange::class)
            ->assertCanSeeTableRecords([$offen])
            ->assertCanNotSeeTableRecords([$bereit]);
    }

    public function test_bereit_tab_shows_only_reviewed_bookings(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $offen = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => null]);
        $bereit = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => now()]);

        Livewire::actingAs($admin, 'admin')
            ->test(Warteschlange::class)
            ->call('setTab', 'bereit')
            ->assertCanSeeTableRecords([$bereit])
            ->assertCanNotSeeTableRecords([$offen]);
    }

    public function test_admin_can_confirm_a_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => null]);

        Livewire::actingAs($admin, 'admin')
            ->test(Warteschlange::class)
            ->callTableAction('confirm', $booking);

        $this->assertNotNull($booking->fresh()->reviewed_at);
    }

    public function test_admin_can_reopen_a_confirmed_booking(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => now()]);

        Livewire::actingAs($admin, 'admin')
            ->test(Warteschlange::class)
            ->call('setTab', 'bereit')
            ->callTableAction('reopen', $booking);

        $this->assertNull($booking->fresh()->reviewed_at);
    }

    public function test_viewer_cannot_see_the_confirm_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $booking = Booking::factory()->create(['status' => 'confirmed', 'reviewed_at' => null]);

        Livewire::actingAs($viewer, 'admin')
            ->test(Warteschlange::class)
            ->assertTableActionHidden('confirm', $booking);
    }
}
