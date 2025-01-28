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
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('color_id')->nullable()->constrained('colores')->onDelete('set null');
            $table->foreignId('longitud_id')->nullable()->constrained('longitudes')->onDelete('set null');
            $table->foreignId('tamano_id')->nullable()->constrained('tamanos')->onDelete('set null');
            $table->double('precio_unitario')->default(0);
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'color_id', 'longitud_id', 'tamano_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
