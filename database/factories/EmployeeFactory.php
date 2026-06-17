<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Typische Abteilungen einer deutschen Kreisverwaltung.
     *
     * @var list<string>
     */
    protected array $abteilungen = [
        'Hauptamt',
        'Personalamt',
        'Kämmerei',
        'Bauamt',
        'Ordnungsamt',
        'Jugendamt',
        'Sozialamt',
        'Gesundheitsamt',
        'Umweltamt',
        'IT-Center',
        'Schulamt',
        'Straßenverkehrsamt',
    ];

    public function definition(): array
    {
        $vorname = $this->faker->firstName();
        $nachname = $this->faker->lastName();

        return [
            'kvgg_nummer' => 'KVGG-'.$this->faker->unique()->numberBetween(10000, 99999),
            'vorname' => $vorname,
            'nachname' => $nachname,
            'email' => Str::lower(Str::ascii($vorname).'.'.Str::ascii($nachname)).'@kreisgg.de',
            'abteilung' => $this->faker->randomElement($this->abteilungen),
            'pc_nummer' => 'PC-'.$this->faker->unique()->numberBetween(100000, 999999),
            'last_laptop_exchange' => $this->faker->optional(0.6)->dateTimeBetween('-4 years', '-2 years')?->format('Y-m-d'),
        ];
    }
}
