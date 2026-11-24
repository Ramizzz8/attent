<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edificios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('direccion');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['empresa_id', 'slug']);
        });

        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edificio_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('prioridad_alta')->default(false);
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('oficio');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->timestamps();
        });

        Schema::create('edificio_proveedor', function (Blueprint $table) {
            $table->foreignId('edificio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->primary(['edificio_id', 'proveedor_id']);
        });

        Schema::create('ticket_afectados', function (Blueprint $table) {
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['ticket_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_afectados');
        Schema::dropIfExists('edificio_proveedor');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('zonas');
        Schema::dropIfExists('edificios');
    }
};
