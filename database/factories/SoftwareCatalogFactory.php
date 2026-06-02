<?php

namespace Database\Factories;

use App\Models\SoftwareCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareCatalog>
 */
class SoftwareCatalogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'version' => (string) $this->faker->numberBetween(1, 20).'.'.$this->faker->numberBetween(0, 9),
            'publisher' => $this->faker->company(),
            'is_standard' => $this->faker->boolean(70),
        ];
    }
}
