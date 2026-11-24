<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verificado_en')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['empresa_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
