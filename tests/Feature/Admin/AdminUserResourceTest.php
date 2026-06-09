<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/admin-users')->assertRedirect('/admin/login');
    }

    public function test_viewer_cannot_access_admin_users(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/admin-users')
            ->assertForbidden();
    }

    public function test_admin_can_open_the_admin_users_list(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admin-users')
            ->assertOk();
    }

    public function test_admin_can_create_an_account_and_the_password_works(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin, 'admin')
            ->test(CreateAdminUser::class)
            ->fillForm([
                'name' => 'Neue Kollegin',
                'email' => 'neu@kreisgg.de',
                'role' => 'viewer',
                'password' => 'geheim123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = AdminUser::where('email', 'neu@kreisgg.de')->firstOrFail();
        $this->assertSame('viewer', $created->role);
        // Passwort genau einmal gehasht -> Klartext muss verifizierbar sein (kein Doppel-Hash).
        $this->assertTrue(Hash::check('geheim123', $created->password));
    }

    public function test_editing_without_a_password_keeps_the_old_one(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $target = AdminUser::factory()->create([
            'role' => 'viewer',
            'password' => Hash::make('original-pw'),
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(EditAdminUser::class, ['record' => $target->getRouteKey()])
            ->fillForm([
                'name' => 'Geänderter Name',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $target->refresh();
        $this->assertSame('Geänderter Name', $target->name);
        $this->assertTrue(Hash::check('original-pw', $target->password));
    }
}
