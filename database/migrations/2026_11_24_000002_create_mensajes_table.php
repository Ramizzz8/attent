<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->morphs('autor');
            $table->text('cuerpo');
            $table->boolean('interno')->default(false);
            $table->timestamps();
        });

        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mensaje_id')->nullable()->constrained('mensajes')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('nombre');
            $table->string('mime');
            $table->unsignedInteger('tamano');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
        Schema::dropIfExists('mensajes');
    }
};
