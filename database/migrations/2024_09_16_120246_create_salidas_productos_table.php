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
        Schema::create('salidas_productos', function (Blueprint $table) {
            $table->id();
            $table->string('guia_salida')->unique();
            $table->enum('tipo_salida', ['Ventas', 'Donacion', 'Otros']);
            $table->string('destino');
            $table->timestamp('fecha')->useCurrent();
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salidas_productos');
    }
};
