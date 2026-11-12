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
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integracion_id')->constrained('integraciones')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('motivo');
            $table->enum('gravedad', ['amarilla', 'roja']);
            $table->enum('estado', ['abierto', 'en_revision', 'resuelto']);
            $table->string('responsable')->nullable();
            $table->text('comentario_resolucion')->nullable();
            $table->timestamp('fecha_apertura');
            $table->timestamp('fecha_resolucion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidentes');
    }
};
