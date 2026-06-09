<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_url_shows_the_german_404_page(): void
    {
        $this->get('/diese-seite-gibt-es-nicht')
            ->assertNotFound()
            ->assertSee('Seite nicht gefunden')
            ->assertSee('Kreis Groß-Gerau');
    }

    public function test_forbidden_access_shows_the_german_403_page(): void
    {
        // Mitarbeiter:in ruft die Buchung einer anderen Person auf -> 403.
        $booking = Booking::factory()->create(['employee_id' => Employee::factory()->create()->id]);
        $other = Employee::factory()->create();

        $this->actingAs($other, 'employee')
            ->get(route('booking.show', $booking))
            ->assertForbidden()
            ->assertSee('Kein Zugriff');
    }
}
