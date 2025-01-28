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
        Schema::create('item_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_producto_id')->constrained('salidas_productos')->onDelete('cascade');
            $table->foreignId('inventario_id')->constrained('inventario')->onDelete('cascade');
            $table->integer('cantidad');
            $table->double('precio_unitario');
            $table->double('igv');
            $table->double('costo_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_salidas');
    }
};
