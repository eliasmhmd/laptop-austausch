<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verknüpft eine Buchung mit der ausgewählten Software. Entweder ein Eintrag
     * aus dem Katalog (software_catalog_id) oder eine frei eingegebene
     * Spezialsoftware (custom_software_name, is_custom = true).
     */
    public function up(): void
    {
        Schema::create('booking_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('software_catalog_id')->nullable()->constrained('software_catalog')->nullOnDelete();
            $table->string('custom_software_name')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_software');
    }
};
