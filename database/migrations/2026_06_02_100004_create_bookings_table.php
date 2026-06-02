<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eine Buchung verknüpft eine:n Mitarbeitende:n mit einem Zeitfenster.
     *
     * Hinweis: time_slot_id ist bewusst NICHT als unique-Spalte angelegt.
     * Beim Verschieben (Phase 4) wird die alte Buchung auf "cancelled" gesetzt
     * und bleibt als Historie erhalten – ein hartes UNIQUE würde dann das
     * erneute Buchen desselben Fensters blockieren. "Ein aktives Termin pro
     * Fenster" wird stattdessen über time_slots.status/booked_count und in der
     * Anwendungslogik sichergestellt.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->enum('status', ['confirmed', 'cancelled', 'completed', 'no_show', 'sick'])->default('confirmed');
            $table->text('cancellation_reason')->nullable();
            $table->text('unplanned_note')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
