<?php

namespace Database\Seeders;

use App\Models\SoftwareCatalog;
use Illuminate\Database\Seeder;

class SoftwareCatalogSeeder extends Seeder
{
    /**
     * Typischer Software-Katalog einer Behörde. is_standard = vorinstalliert.
     *
     * @var list<array{name: string, publisher: string, is_standard: bool}>
     */
    protected array $software = [
        ['name' => 'Microsoft Office', 'publisher' => 'Microsoft', 'is_standard' => true],
        ['name' => 'Microsoft Teams', 'publisher' => 'Microsoft', 'is_standard' => true],
        ['name' => 'Mozilla Firefox', 'publisher' => 'Mozilla', 'is_standard' => true],
        ['name' => 'Google Chrome', 'publisher' => 'Google', 'is_standard' => true],
        ['name' => 'Adobe Acrobat Reader', 'publisher' => 'Adobe', 'is_standard' => true],
        ['name' => '7-Zip', 'publisher' => 'Igor Pavlov', 'is_standard' => true],
        ['name' => 'VLC media player', 'publisher' => 'VideoLAN', 'is_standard' => true],
        ['name' => 'Notepad++', 'publisher' => 'Notepad++ Team', 'is_standard' => true],
        ['name' => 'SAP GUI', 'publisher' => 'SAP', 'is_standard' => false],
        ['name' => 'QGIS', 'publisher' => 'QGIS.org', 'is_standard' => false],
        ['name' => 'DATEV', 'publisher' => 'DATEV eG', 'is_standard' => false],
        ['name' => 'Adobe Photoshop', 'publisher' => 'Adobe', 'is_standard' => false],
    ];

    public function run(): void
    {
        foreach ($this->software as $entry) {
            SoftwareCatalog::updateOrCreate(
                ['name' => $entry['name']],
                [
                    'publisher' => $entry['publisher'],
                    'is_standard' => $entry['is_standard'],
                ],
            );
        }
    }
}
