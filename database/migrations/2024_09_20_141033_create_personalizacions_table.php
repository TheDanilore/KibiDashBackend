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
        Schema::create('personalizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('imagen_frontal')->nullable();
            $table->string('texto_frontal')->nullable();
            $table->string('imagen_trasera')->nullable();
            $table->string('texto_trasera')->nullable();
            $table->double('precio_adicional')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personalizaciones');
    }
};
