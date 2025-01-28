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
        Schema::create('item_carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos');
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->double('precio_unitario');
            $table->foreignId('personalizacion_id')->nullable()->constrained('personalizaciones');
            $table->double('subtotal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_carritos');
    }
};
