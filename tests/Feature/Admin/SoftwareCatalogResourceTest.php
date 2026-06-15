<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SoftwareCatalogs\Pages\CreateSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\Pages\EditSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\Pages\ListSoftwareCatalogs;
use App\Filament\Resources\SoftwareCatalogs\Pages\ViewSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\RelationManagers\UsageRelationManager;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\Employee;
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

    public function test_viewer_can_open_the_view_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $software = SoftwareCatalog::factory()->create();

        $this->actingAs($viewer, 'admin')
            ->get("/admin/software-catalogs/{$software->getRouteKey()}")
            ->assertOk();
    }

    public function test_usage_relation_manager_lists_employees_using_the_software(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $software = SoftwareCatalog::factory()->create(['name' => 'Adobe Acrobat']);

        $user = Employee::factory()->create(['vorname' => 'Petra', 'nachname' => 'Zillgens']);
        $booking = Booking::factory()->create(['employee_id' => $user->id, 'status' => 'confirmed']);
        BookingSoftware::create([
            'booking_id' => $booking->id,
            'software_catalog_id' => $software->id,
            'is_custom' => false,
        ]);

        // Eine andere Mitarbeiterin, die diese Software NICHT angefordert hat.
        $other = Employee::factory()->create(['vorname' => 'Hans', 'nachname' => 'Aaltonen']);
        $otherBooking = Booking::factory()->create(['employee_id' => $other->id, 'status' => 'confirmed']);
        BookingSoftware::create([
            'booking_id' => $otherBooking->id,
            'software_catalog_id' => SoftwareCatalog::factory()->create()->id,
            'is_custom' => false,
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(UsageRelationManager::class, [
                'ownerRecord' => $software,
                'pageClass' => ViewSoftwareCatalog::class,
            ])
            ->assertCanSeeTableRecords($software->bookingSoftware)
            ->assertSee('Zillgens')
            ->assertDontSee('Aaltonen');
    }

    public function test_admin_can_approve_a_pending_entry(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $pending = SoftwareCatalog::factory()->pending()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->callTableAction('approve', $pending);

        $this->assertSame(SoftwareCatalog::STATUS_APPROVED, $pending->fresh()->status);
    }

    public function test_viewer_cannot_see_the_approve_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $pending = SoftwareCatalog::factory()->pending()->create();

        Livewire::actingAs($viewer, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->assertTableActionHidden('approve', $pending);
    }

    public function test_admin_can_merge_two_entries_via_the_table_action(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $winner = SoftwareCatalog::factory()->create(['name' => 'Excel']);
        $loser = SoftwareCatalog::factory()->pending()->create(['name' => 'excel']);

        $booking = Booking::factory()->create();
        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $loser->id, 'is_custom' => false]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->callTableAction('merge', $loser, data: ['target_id' => $winner->id]);

        $this->assertDatabaseMissing('software_catalog', ['id' => $loser->id]);
        $this->assertDatabaseHas('booking_software', [
            'booking_id' => $booking->id,
            'software_catalog_id' => $winner->id,
        ]);
    }

    public function test_admin_can_bulk_delete_catalog_entries(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $entries = SoftwareCatalog::factory()->count(3)->create();

        Livewire::actingAs($admin, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->callTableBulkAction('delete', $entries);

        foreach ($entries as $entry) {
            $this->assertDatabaseMissing('software_catalog', ['id' => $entry->id]);
        }
    }

    public function test_bulk_delete_nulls_the_link_on_bookings(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $software = SoftwareCatalog::factory()->create();
        $booking = Booking::factory()->create();
        $link = BookingSoftware::create([
            'booking_id' => $booking->id,
            'software_catalog_id' => $software->id,
            'is_custom' => false,
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->callTableBulkAction('delete', collect([$software]));

        $this->assertDatabaseMissing('software_catalog', ['id' => $software->id]);
        // Die Verknüpfung bleibt bestehen, zeigt aber auf NULL (nullOnDelete).
        $this->assertDatabaseHas('booking_software', [
            'id' => $link->id,
            'software_catalog_id' => null,
        ]);
    }

    public function test_viewer_cannot_use_the_bulk_delete_action(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        SoftwareCatalog::factory()->create();

        Livewire::actingAs($viewer, 'admin')
            ->test(ListSoftwareCatalogs::class)
            ->assertTableBulkActionHidden('delete');
    }
}
