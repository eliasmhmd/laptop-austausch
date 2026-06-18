<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\Employee;
use App\Models\LaptopConfig;
use App\Models\SoftwareCatalog;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaptopConfigTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFor(Employee $employee): Booking
    {
        return Booking::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_owner_can_open_the_config_form(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('config.edit', $booking))
            ->assertOk()
            ->assertSee('Benötigte Software')
            ->assertSee('Standard-Software – Auf jedem Laptop enthalten')
            ->assertSee('ProCall')
            ->assertSee('Softwarecenter')
            ->assertSee('KeePass XC')
            ->assertSee('Ihre Arbeitsumgebung');
    }

    public function test_config_form_shows_custom_software_texts_from_settings(): void
    {
        \App\Models\Setting::set(\App\Models\Setting::SOFTWARE_INTRO_KEY, 'Eigener Einleitungstext.');
        \App\Models\Setting::set(\App\Models\Setting::SOFTWARE_CENTER_TEXT_KEY, 'Eigener Softwarecenter-Hinweis.');
        \App\Models\Setting::set(\App\Models\Setting::SOFTWARE_CENTER_PROGRAMS_KEY, "FooTool\nBarTool");
        \App\Models\Setting::set(\App\Models\Setting::SOFTWARE_WARNING_KEY, 'Eigener Warnhinweis.');

        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('config.edit', $booking))
            ->assertOk()
            ->assertSee('Eigener Einleitungstext.')
            ->assertSee('Eigener Softwarecenter-Hinweis.')
            ->assertSee('FooTool')
            ->assertSee('BarTool')
            ->assertSee('Eigener Warnhinweis.')
            ->assertDontSee('KeePass XC'); // Standardliste wurde ersetzt
    }

    public function test_non_owner_cannot_open_the_config_form(): void
    {
        $booking = $this->bookingFor(Employee::factory()->create());

        $this->actingAs(Employee::factory()->create(), 'employee')
            ->get(route('config.edit', $booking))
            ->assertForbidden();
    }

    public function test_suggestions_hide_other_users_pending_software(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        SoftwareCatalog::factory()->create(['name' => 'FreigegebenTool', 'is_standard' => false]);
        SoftwareCatalog::factory()->pending()->create(['name' => 'EigenTool', 'submitted_by' => $employee->id]);
        SoftwareCatalog::factory()->pending()->create(['name' => 'FremdTool', 'submitted_by' => Employee::factory()->create()->id]);

        $this->actingAs($employee, 'employee')
            ->get(route('config.edit', $booking))
            ->assertOk()
            ->assertSee('FreigegebenTool')   // freigegeben → für alle sichtbar
            ->assertSee('EigenTool')          // eigener pending-Eintrag → sichtbar
            ->assertDontSee('FremdTool');     // fremder pending-Eintrag → verborgen
    }

    public function test_saving_config_links_known_software_and_creates_new_entries(): void
    {
        $employee = Employee::factory()->create(['pc_nummer' => 'PC7438']);
        $booking = $this->bookingFor($employee);
        SoftwareCatalog::factory()->create(['name' => 'Microsoft Office']);
        SoftwareCatalog::factory()->create(['name' => 'Microsoft Teams']);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), [
                'manufacturer' => 'Lenovo',
                'software_names' => ['Microsoft Office', 'Microsoft Teams', 'Fachverfahren XY'],
                'additional_notes' => 'Zweiter Monitor wird benötigt.',
            ])
            ->assertRedirect(route('booking.show', $booking));

        $config = LaptopConfig::firstWhere('booking_id', $booking->id);
        $this->assertSame('Lenovo', $config->old_manufacturer);
        // PC-Nummer wird automatisch aus dem Mitarbeiterdatensatz übernommen.
        $this->assertSame('PC7438', $config->old_pc_nummer);
        $this->assertSame('Zweiter Monitor wird benötigt.', $config->additional_notes);

        // Alle drei werden als Katalog-Verknüpfung gespeichert (keine is_custom-Zeilen mehr).
        $this->assertSame(3, BookingSoftware::where('booking_id', $booking->id)->count());
        $this->assertSame(0, BookingSoftware::where('booking_id', $booking->id)->where('is_custom', true)->count());

        // Der unbekannte Name wird selbsttätig als Zusatzsoftware angelegt.
        $this->assertDatabaseHas('software_catalog', [
            'name' => 'Fachverfahren XY',
            'is_standard' => false,
        ]);
    }

    public function test_existing_software_name_is_reused_case_insensitively(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);
        $office = SoftwareCatalog::factory()->create(['name' => 'Microsoft Office']);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software_names' => ['microsoft office']]);

        // Kein neuer Katalogeintrag, sondern Verknüpfung auf den bestehenden;
        // die ursprüngliche Schreibweise bleibt erhalten.
        $this->assertSame(1, SoftwareCatalog::count());
        $this->assertSame('Microsoft Office', $office->fresh()->name);
        $this->assertDatabaseHas('booking_software', [
            'booking_id' => $booking->id,
            'software_catalog_id' => $office->id,
        ]);
    }

    public function test_resaving_replaces_previous_selection_without_duplicates(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);
        $a = SoftwareCatalog::factory()->create(['name' => 'Programm A']);
        SoftwareCatalog::factory()->create(['name' => 'Programm B']);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software_names' => ['Programm A', 'Programm B']]);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software_names' => ['Programm A']]);

        $this->assertSame(1, BookingSoftware::where('booking_id', $booking->id)->count());
        $this->assertDatabaseHas('booking_software', ['booking_id' => $booking->id, 'software_catalog_id' => $a->id]);
    }

    public function test_config_and_software_move_to_new_booking_on_reschedule(): void
    {
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->booked()->create(['capacity' => 1]);
        $oldBooking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $oldSlot->id,
            'status' => 'confirmed',
        ]);
        SoftwareCatalog::factory()->create(['name' => 'Programm A']);

        // Software/Hersteller für die alte Buchung erfassen.
        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $oldBooking), [
                'manufacturer' => 'Dell',
                'software_names' => ['Programm A', 'Spezialtool'],
            ]);

        // Auf ein neues Fenster verschieben.
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $this->actingAs($employee, 'employee')->post(route('reschedule.update', $newSlot));

        $newBooking = Booking::where('employee_id', $employee->id)->where('status', 'confirmed')->firstOrFail();

        // Angaben hängen jetzt an der neuen Buchung, nicht mehr an der alten.
        $this->assertDatabaseHas('laptop_configs', ['booking_id' => $newBooking->id, 'old_manufacturer' => 'Dell']);
        $this->assertSame(2, BookingSoftware::where('booking_id', $newBooking->id)->count());
        $this->assertSame(0, BookingSoftware::where('booking_id', $oldBooking->id)->count());
    }
}
