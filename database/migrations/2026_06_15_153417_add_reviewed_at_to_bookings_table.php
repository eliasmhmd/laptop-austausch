<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warteschlange: Zeitpunkt, an dem ein:e Admin die Buchung durchgegangen und
 * bestätigt hat. NULL = noch offen (in der Warteschlange), gesetzt = bereit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('booked_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
