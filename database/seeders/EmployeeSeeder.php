<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // 150 geleaste Laptops => 150 Mitarbeitende mit Testdaten.
        Employee::factory()->count(150)->create();
    }
}
