<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('matricula', 12);
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->unsignedInteger('km')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'matricula']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
