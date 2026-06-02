<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\LaptopConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaptopConfig>
 */
class LaptopConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'old_pc_nummer' => 'PC-'.$this->faker->numberBetween(100000, 999999),
            'old_serial_number' => mb_strtoupper($this->faker->bothify('??######')),
            'old_manufacturer' => $this->faker->randomElement(['Dell', 'Lenovo', 'HP', 'Fujitsu']),
            'old_model' => $this->faker->randomElement(['Latitude 5420', 'ThinkPad T14', 'EliteBook 840', 'Lifebook U7']),
            'old_cpu' => $this->faker->randomElement(['Intel Core i5', 'Intel Core i7', 'AMD Ryzen 5']),
            'old_ram_gb' => $this->faker->randomElement([8, 16, 32]),
            'old_storage_gb' => $this->faker->randomElement([256, 512, 1024]),
            'old_storage_type' => $this->faker->randomElement(['SSD', 'HDD']),
            'old_operating_system' => $this->faker->randomElement(['Windows 10', 'Windows 11']),
            'old_inventory_number' => 'INV-'.$this->faker->numberBetween(10000, 99999),
            'new_pc_nummer' => null,
            'new_serial_number' => null,
            'new_inventory_number' => null,
        ];
    }
}
