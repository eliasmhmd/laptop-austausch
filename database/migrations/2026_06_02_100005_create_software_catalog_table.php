<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog bekannter Software, aus dem Mitarbeitende beim Ausfüllen des
     * Laptop-Formulars auswählen. is_standard = vorinstallierte Standardsoftware.
     */
    public function up(): void
    {
        Schema::create('software_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('publisher')->nullable();
            $table->boolean('is_standard')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_catalog');
    }
};
