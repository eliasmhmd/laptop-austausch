<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Einstellungen;
use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EinstellungenPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_page(): void
    {
        $this->get('/admin/einstellungen')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_page(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin/einstellungen')->assertOk();
    }

    public function test_viewer_cannot_open_the_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/einstellungen')->assertForbidden();
    }

    public function test_admin_can_save_the_room(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin, 'admin')
            ->test(Einstellungen::class)
            ->callAction('editRoom', data: ['room' => 'Raum 345']);

        $this->assertSame('Raum 345', Setting::get(Setting::ROOM_KEY));
    }
}
