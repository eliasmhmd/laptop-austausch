<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buchbare Zeitfenster. Pro Werktag 8 Stunden-Slots (08:00–16:00), je
     * Kapazität 1. status steuert die Farbe im Kalender (available = grün,
     * booked = rot, blocked = gesperrt).
     */
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('calendar_week');
            $table->enum('status', ['available', 'booked', 'blocked'])->default('available');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            // Pro Datum + Startzeit darf es nur ein Zeitfenster geben.
            $table->unique(['slot_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
