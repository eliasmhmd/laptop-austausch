<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Services\EmployeeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeImporterTest extends TestCase
{
    use RefreshDatabase;

    private function importer(): EmployeeImporter
    {
        return app(EmployeeImporter::class);
    }

    public function test_it_imports_tab_delimited_rows(): void
    {
        // Genau das vom Anwender gelieferte Beispiel (Tab-getrennt, wie aus Excel kopiert).
        $csv = "PC-Nummer\tLogin\tVorname\tNachname\teMail-Adresse\tFachabteilung\n"
            ."PC6228\tkvgg0807\tAndreas\tHartmann\tA.Hartmann@kreisgg.de\tRevision\n"
            ."PC6230\tkvgg0810\tTanja\tFriedmann\tt.friedmann@kreisgg.de\tRevision\n";

        $result = $this->importer()->import($csv);

        $this->assertSame(2, $result['created']);
        $this->assertSame([], $result['errors']);

        $emp = Employee::where('kvgg_nummer', 'kvgg0807')->first();
        $this->assertNotNull($emp);
        $this->assertSame('PC6228', $emp->pc_nummer);
        $this->assertSame('Andreas', $emp->vorname);
        $this->assertSame('Hartmann', $emp->nachname);
        $this->assertSame('A.Hartmann@kreisgg.de', $emp->email);
        $this->assertSame('Revision', $emp->abteilung);
    }

    public function test_it_imports_semicolon_delimited_rows(): void
    {
        $csv = "PC-Nummer;Login;Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            ."PC6228;kvgg0807;Andreas;Hartmann;A.Hartmann@kreisgg.de;Revision\n";

        $result = $this->importer()->import($csv);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('employees', ['kvgg_nummer' => 'kvgg0807', 'pc_nummer' => 'PC6228']);
    }

    public function test_reimport_updates_instead_of_duplicating(): void
    {
        $first = "PC-Nummer;Login;Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            ."PC6228;kvgg0807;Andreas;Hartmann;A.Hartmann@kreisgg.de;Revision\n";
        $this->importer()->import($first);

        // Gleiche KVGG-Nummer, neue PC-Nummer + Abteilung.
        $second = "PC-Nummer;Login;Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            ."PC9999;kvgg0807;Andreas;Hartmann;A.Hartmann@kreisgg.de;IT\n";
        $result = $this->importer()->import($second);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, Employee::count());
        $this->assertSame('PC9999', Employee::first()->pc_nummer);
        $this->assertSame('IT', Employee::first()->abteilung);
    }

    public function test_it_converts_windows_1252_umlauts(): void
    {
        // "Bürgerbüro" in Windows-1252 kodiert.
        $row = "PC-Nummer;Login;Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            ."PC1;kvgg0001;Jörg;Müller;j.mueller@kreisgg.de;Bürgerbüro\n";
        $win1252 = mb_convert_encoding($row, 'Windows-1252', 'UTF-8');

        $this->importer()->import($win1252);

        $emp = Employee::first();
        $this->assertSame('Jörg', $emp->vorname);
        $this->assertSame('Müller', $emp->nachname);
        $this->assertSame('Bürgerbüro', $emp->abteilung);
    }

    public function test_rows_missing_required_fields_are_skipped_with_errors(): void
    {
        $csv = "PC-Nummer;Login;Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            .";kvgg0001;Ohne;PC;x@kreisgg.de;IT\n"          // PC-Nummer fehlt
            ."PC2;;Ohne;Login;y@kreisgg.de;IT\n"            // Login fehlt
            ."PC3;kvgg0003;Gut;Eintrag;z@kreisgg.de;IT\n";  // ok

        $result = $this->importer()->import($csv);

        $this->assertSame(1, $result['created']);
        $this->assertCount(2, $result['errors']);
        $this->assertSame(1, Employee::count());
    }

    public function test_missing_required_header_is_reported(): void
    {
        $csv = "Vorname;Nachname;eMail-Adresse;Fachabteilung\n"
            ."Andreas;Hartmann;a@kreisgg.de;Revision\n";

        $result = $this->importer()->import($csv);

        $this->assertSame(0, $result['created']);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame(0, Employee::count());
    }
}
