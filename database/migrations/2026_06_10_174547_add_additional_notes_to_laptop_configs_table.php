<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laptop_configs', function (Blueprint $table) {
            // Freitext der/des Mitarbeitenden für zusätzliche Hinweise zum Gerät.
            $table->text('additional_notes')->nullable()->after('old_inventory_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptop_configs', function (Blueprint $table) {
            $table->dropColumn('additional_notes');
        });
    }
};
