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
        Schema::create('integraciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('sistema_origen');
            $table->string('sistema_destino');
            $table->string('url_base')->nullable();
            $table->unsignedInteger('frecuencia_minutos');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integraciones');
    }
};
