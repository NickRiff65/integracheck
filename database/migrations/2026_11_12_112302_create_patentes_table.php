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
        Schema::create('patentes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_patente');
            $table->string('titular');
            $table->string('tipo');
            $table->enum('estado', ['registrada', 'enviada', 'rechazada', 'pendiente']);
            $table->foreignId('integracion_id')
                ->constrained('integraciones')
                ->cascadeOnDelete();
            $table->foreignId('ultimo_envio_id')
                ->nullable()
                ->constrained('envios')
                ->nullOnDelete();
            $table->date('fecha_registro');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patentes');
    }
};
