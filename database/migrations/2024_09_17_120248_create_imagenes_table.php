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
        Schema::create('imagenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');  // Clave foránea a productos
            $table->unsignedBigInteger('inventario_id')->nullable();  // Clave foránea a inventario
            $table->string('url');  // URL de la imagen
            $table->string('alt_text')->nullable(); // Opcional, para texto alternativo de la imagen
            $table->timestamps();
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('inventario_id')->references('id')->on('inventario')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes');
    }
};
