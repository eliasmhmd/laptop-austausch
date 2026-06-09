<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlotResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_time_slots_list(): void
    {
        $this->get('/admin/time-slots')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_time_slots_list(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/time-slots')
            ->assertOk();
    }

    public function test_viewer_can_open_the_time_slots_list(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/time-slots')
            ->assertOk();
    }
}
