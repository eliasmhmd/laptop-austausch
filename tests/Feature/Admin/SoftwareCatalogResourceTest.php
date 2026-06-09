<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SoftwareCatalogs\Pages\CreateSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\Pages\EditSoftwareCatalog;
use App\Models\AdminUser;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SoftwareCatalogResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/software-catalogs')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_list(): void
    {
        SoftwareCatalog::factory()->count(3)->create();
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin/software-catalogs')->assertOk();
    }

    public function test_viewer_can_open_the_list(): void
    {
        SoftwareCatalog::factory()->count(3)->create();
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/software-catalogs')->assertOk();
    }

    public function test_viewer_cannot_open_the_create_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/software-catalogs/create')
            ->assertForbidden();
    }

    public function test_admin_can_create_a_catalog_entry(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin, 'admin')
            ->test(CreateSoftwareCatalog::class)
            ->fillForm([
                'name' => 'GIMP',
                'is_standard' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('software_catalog', ['name' => 'GIMP', 'is_standard' => false]);
    }

    public function test_name_must_be_unique(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        SoftwareCatalog::factory()->create(['name' => 'GIMP']);

        Livewire::actingAs($admin, 'admin')
            ->test(CreateSoftwareCatalog::class)
            ->fillForm(['name' => 'GIMP'])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_admin_can_edit_an_entry(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $software = SoftwareCatalog::factory()->create(['name' => 'Alt', 'is_standard' => false]);

        Livewire::actingAs($admin, 'admin')
            ->test(EditSoftwareCatalog::class, ['record' => $software->getRouteKey()])
            ->fillForm(['name' => 'Neu', 'is_standard' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $software->refresh();
        $this->assertSame('Neu', $software->name);
        $this->assertTrue($software->is_standard);
    }
}
