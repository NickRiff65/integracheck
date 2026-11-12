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
        Schema::create('envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integracion_id')
                ->constrained('integraciones')
                ->cascadeOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();
            $table->enum('estado', ['exitoso', 'parcial', 'fallido', 'en_curso']);
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('registros_ok')->default(0);
            $table->unsignedInteger('registros_error')->default(0);
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->unsignedInteger('duracion_seg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
