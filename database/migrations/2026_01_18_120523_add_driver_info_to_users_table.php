<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregamos un bloque de columnas para el "Chofer Asignado"
            // Son nullable porque no todos los usuarios (ej: secretarias) tendrán chofer
            $table->string('driver_name')->nullable()->after('role');
            $table->string('driver_dni')->nullable()->after('driver_name');
            $table->string('driver_address')->nullable()->after('driver_dni');
            $table->string('driver_phone')->nullable()->after('driver_address');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'driver_dni', 'driver_address', 'driver_phone']);
        });
    }
};
