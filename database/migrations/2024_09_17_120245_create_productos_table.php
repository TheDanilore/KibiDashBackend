
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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion');
            $table->foreignId('categoria_producto_id')->constrained('categoria_productos');
            $table->foreignId('unidad_medida_id')->constrained('unidad_medidas');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->enum('estado', ['Activo', 'Desactivado']);
            $table->foreignId('ubicacion_id')->constrained('ubicaciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
