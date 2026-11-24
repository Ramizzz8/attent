<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado');
            $table->string('prioridad');
            $table->string('categoria');
            $table->string('asunto');
            $table->text('descripcion');
            $table->json('datos')->nullable();
            // Relaciones propias de cada sector (las claves foráneas se añaden con sus tablas).
            $table->unsignedBigInteger('edificio_id')->nullable()->index();
            $table->unsignedBigInteger('zona_id')->nullable();
            $table->unsignedBigInteger('vehiculo_id')->nullable()->index();
            $table->unsignedBigInteger('equipo_id')->nullable()->index();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('impacto')->nullable();
            $table->string('urgencia')->nullable();
            $table->timestamp('verificado_en')->nullable();
            $table->timestamp('sla_vence_en')->nullable();
            $table->timestamp('sla_pausado_en')->nullable();
            $table->boolean('sla_cumplido')->nullable();
            $table->timestamp('primera_respuesta_vence_en')->nullable();
            $table->timestamp('primera_respuesta_en')->nullable();
            $table->timestamp('visita_programada_en')->nullable();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamp('cerrado_en')->nullable();
            $table->unsignedTinyInteger('valoracion')->nullable();
            $table->text('valoracion_comentario')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'numero']);
            $table->index(['empresa_id', 'estado']);
        });

        Schema::create('ticket_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('tipo');
            $table->string('de')->nullable();
            $table->string('a')->nullable();
            $table->text('motivo')->nullable();
            $table->json('datos')->nullable();
            $table->timestamps();
        });

        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('estado')->default('pendiente');
            $table->decimal('iva_pct', 5, 2)->default(21);
            $table->timestamp('respondido_en')->nullable();
            $table->text('comentario_cliente')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'version']);
        });

        Schema::create('presupuesto_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');
            $table->string('concepto');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_lineas');
        Schema::dropIfExists('presupuestos');
        Schema::dropIfExists('ticket_eventos');
        Schema::dropIfExists('tickets');
    }
};
