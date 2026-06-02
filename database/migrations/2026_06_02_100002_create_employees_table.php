<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mitarbeitende des Kreises. Login = kvgg_nummer (Benutzername) + pc_nummer
     * (Passwort). pc_nummer wird im Klartext gespeichert, weil sie auf dem Gerät
     * steht (kein echtes Geheimnis) und im Laptop-Formular wieder angezeigt wird.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('kvgg_nummer')->unique();
            $table->string('vorname');
            $table->string('nachname');
            $table->string('email');
            $table->string('abteilung');
            $table->string('pc_nummer');
            $table->date('last_laptop_exchange')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
