<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hardware-Details des alten (abzugebenden) und des neuen Laptops, pro
     * Buchung genau einmal. old_* füllt der/die Mitarbeitende aus, new_* trägt
     * später die IT ein.
     */
    public function up(): void
    {
        Schema::create('laptop_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();

            // Altes Gerät (vom Mitarbeitenden ausgefüllt)
            $table->string('old_pc_nummer')->nullable();
            $table->string('old_serial_number')->nullable();
            $table->string('old_manufacturer')->nullable();
            $table->string('old_model')->nullable();
            $table->string('old_cpu')->nullable();
            $table->unsignedSmallInteger('old_ram_gb')->nullable();
            $table->unsignedSmallInteger('old_storage_gb')->nullable();
            $table->enum('old_storage_type', ['SSD', 'HDD'])->nullable();
            $table->string('old_operating_system')->nullable();
            $table->string('old_inventory_number')->nullable();

            // Neues Gerät (von der IT eingetragen)
            $table->string('new_pc_nummer')->nullable();
            $table->string('new_serial_number')->nullable();
            $table->string('new_inventory_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laptop_configs');
    }
};
