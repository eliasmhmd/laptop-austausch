<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freigabe-Workflow für selbst eingetragene Software: Mitarbeitende können
     * unbekannte Programme erfassen; diese landen als „pending" (nur für die
     * Person selbst + Admins sichtbar) und werden von Admins freigegeben oder
     * gelöscht. Bestehende Einträge gelten als bereits freigegeben.
     */
    public function up(): void
    {
        Schema::table('software_catalog', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved'])
                ->default('approved')
                ->after('is_standard');
            $table->foreignId('submitted_by')
                ->nullable()
                ->after('status')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('software_catalog', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('status');
        });
    }
};
