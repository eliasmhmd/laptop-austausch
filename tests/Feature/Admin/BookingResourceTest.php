<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_bookings_list(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_bookings_list(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/bookings')
            ->assertOk();
    }

    public function test_viewer_can_open_the_bookings_list(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/bookings')
            ->assertOk();
    }

    public function test_admin_can_open_a_booking_detail_view(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $this->actingAs($admin, 'admin')
            ->get("/admin/bookings/{$booking->id}")
            ->assertOk();
    }
}
