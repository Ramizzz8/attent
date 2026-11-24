<?php

namespace Database\Seeders;

use App\Enums\SectorClave;
use App\Models\Presupuesto;
use App\Taller\EnviarPresupuesto;
use App\Taller\ResponderPresupuesto;

class TallerDemoSeeder extends DemoBase
{
    public function run(): void
    {
        $this->empresa('Talleres Ruiz', 'talleres-ruiz', SectorClave::Taller, '#dc2626');
        $this->renombrarEquipo('Pepe Ruiz', 'Marta Gil', 'Iván Soler');
        $this->clientes([
            ['Lucía Pérez', 'lucia@ejemplo.test', '600111222'],
            ['Andrés Molina', 'andres@ejemplo.test', '600222333'],
            ['Carmen Vidal', 'carmen@ejemplo.test', '600333444'],
            ['Jorge Navarro', 'jorge@ejemplo.test', '600444555'],
            ['Sara Ortega', 'sara@ejemplo.test', '600555666'],
            ['Raúl Campos', 'raul@ejemplo.test', '600666777'],
        ]);

        $coche = fn (string $m, string $marca, string $modelo, int $km) => ['datos' => ['matricula' => $m, 'marca' => $marca, 'modelo' => $modelo, 'km' => $km]];
        $presupuestar = function ($t, array $lineas) {
            $this->luego(3);

            return app(EnviarPresupuesto::class)->ejecutar($t->fresh(), $lineas, 21, $this->equipo[1]);
        };
        $responder = function (Presupuesto $p, int $cliente, bool $aprueba, ?string $comentario = null) {
            $this->luego(5);
            app(ResponderPresupuesto::class)->ejecutar($p->fresh(), $this->clientes[$cliente], $aprueba, $comentario);
        };
        $frenos = [
            ['tipo' => 'pieza', 'concepto' => 'Pastillas de freno delanteras', 'cantidad' => 1, 'precio_unitario' => 54.90],
            ['tipo' => 'pieza', 'concepto' => 'Discos de freno (par)', 'cantidad' => 1, 'precio_unitario' => 89.00],
            ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 1.5, 'precio_unitario' => 48.00],
        ];
        $embrague = [
            ['tipo' => 'pieza', 'concepto' => 'Kit de embrague', 'cantidad' => 1, 'precio_unitario' => 320.00],
            ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 5, 'precio_unitario' => 48.00],
        ];

        // 1. Recibido hace un rato.
        $this->crear(0, 1, ['asunto' => 'Testigo de motor encendido', 'descripcion' => 'Se ha encendido el testigo naranja del motor esta mañana.', 'categoria' => 'electricidad', ...$coche('1234 KLM', 'Seat', 'León', 98000)]);

        // 2. Recibido hace 3 días: diagnóstico fuera de plazo.
        $this->crear(1, 72, ['asunto' => 'Ruido en la suspensión', 'descripcion' => 'Golpea al pasar por baches.', 'categoria' => 'mecanica', ...$coche('5678 BCD', 'Renault', 'Clio', 143000)]);

        // 3. En diagnóstico.
        $t = $this->crear(2, 20, ['asunto' => 'No arranca en frío', 'descripcion' => 'Por las mañanas tarda mucho en arrancar.', 'categoria' => 'mecanica', ...$coche('9012 FGH', 'Peugeot', '308', 76000)]);
        $this->asignar($t, 2);
        $this->estado($t, 'en_diagnostico', 2);
        $this->mensaje($t, $this->equipo[2], 'Probablemente sean los calentadores. Lo confirmo mañana.', interno: true);

        // 4. Presupuesto enviado, esperando al cliente.
        $t = $this->crear(3, 30, ['asunto' => 'Chirrían los frenos', 'descripcion' => 'Chirrido fuerte al frenar.', 'categoria' => 'mecanica', ...$coche('3456 JKL', 'Toyota', 'Corolla', 112000)]);
        $this->asignar($t, 1);
        $this->estado($t, 'en_diagnostico', 1, 2);
        $presupuestar($t, $frenos);

        // 5. Aprobado, pendiente de empezar.
        $t = $this->crear(4, 50, ['asunto' => 'Patina el embrague', 'descripcion' => 'Al acelerar en cuesta sube de vueltas.', 'categoria' => 'mecanica', ...$coche('7890 MNP', 'Volkswagen', 'Golf', 187000)]);
        $this->asignar($t, 1);
        $this->estado($t, 'en_diagnostico', 1, 3);
        $responder($presupuestar($t, $embrague), 4, true);

        // 6. Rechazado.
        $t = $this->crear(5, 80, ['asunto' => 'Revisión de los 100.000 km', 'descripcion' => 'Quiero la revisión completa.', 'categoria' => 'revision', ...$coche('2468 QRS', 'Ford', 'Focus', 101000)]);
        $this->estado($t, 'en_diagnostico', 1, 4);
        $responder($presupuestar($t, [
            ['tipo' => 'pieza', 'concepto' => 'Kit de distribución', 'cantidad' => 1, 'precio_unitario' => 280],
            ['tipo' => 'pieza', 'concepto' => 'Aceite y filtros', 'cantidad' => 1, 'precio_unitario' => 95],
            ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 4, 'precio_unitario' => 48],
        ]), 5, false, 'Solo quiero el cambio de aceite de momento.');

        // 7. En reparación.
        $t = $this->crear(0, 120, ['asunto' => 'Cambio de neumáticos', 'descripcion' => 'Los delanteros están gastados.', 'categoria' => 'neumaticos', ...$coche('1357 TVW', 'Seat', 'Ibiza', 65000)]);
        $this->asignar($t, 2);
        $this->estado($t, 'en_diagnostico', 2, 2);
        $responder($presupuestar($t, [['tipo' => 'pieza', 'concepto' => 'Neumático 205/55 R16', 'cantidad' => 2, 'precio_unitario' => 79], ['tipo' => 'mano_obra', 'concepto' => 'Montaje y equilibrado', 'cantidad' => 2, 'precio_unitario' => 12]]), 0, true);
        $this->estado($t, 'en_reparacion', 2, 18);

        // 8. Esperando piezas (el SLA ya se evaluó; el vehículo espera recambio).
        $t = $this->crear(1, 150, ['asunto' => 'Fallo del alternador', 'descripcion' => 'Se descarga la batería.', 'categoria' => 'electricidad', ...$coche('8642 XYZ', 'Opel', 'Astra', 156000)]);
        $this->asignar($t, 1);
        $this->estado($t, 'en_diagnostico', 1, 2);
        $responder($presupuestar($t, [['tipo' => 'pieza', 'concepto' => 'Alternador reconstruido', 'cantidad' => 1, 'precio_unitario' => 210], ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 2, 'precio_unitario' => 48]]), 1, true);
        $this->estado($t, 'en_reparacion', 1, 4);
        $this->estado($t, 'esperando_piezas', 1, 2);
        $this->mensaje($t, $this->equipo[1], 'El alternador llega el jueves. Te avisamos en cuanto esté montado.');

        // 9. Listo para recoger.
        $t = $this->crear(2, 200, ['asunto' => 'Aire acondicionado no enfría', 'descripcion' => 'Sale aire caliente.', 'categoria' => 'electricidad', ...$coche('9012 FGH', 'Peugeot', '308', 75500)]);
        $this->estado($t, 'en_diagnostico', 1, 3);
        $responder($presupuestar($t, [['tipo' => 'pieza', 'concepto' => 'Recarga de gas', 'cantidad' => 1, 'precio_unitario' => 65], ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 1, 'precio_unitario' => 48]]), 2, true);
        $this->estado($t, 'en_reparacion', 1, 3);
        $this->estado($t, 'listo_recoger', 1, 4);

        // 10. Entregado hace dos días, con valoración.
        $t = $this->crear(3, 260, ['asunto' => 'Cambio de aceite', 'descripcion' => 'Toca el cambio de aceite.', 'categoria' => 'revision', ...$coche('3456 JKL', 'Toyota', 'Corolla', 108000)]);
        $this->estado($t, 'en_diagnostico', 2, 2);
        $responder($presupuestar($t, [['tipo' => 'pieza', 'concepto' => 'Aceite 5W30 y filtro', 'cantidad' => 1, 'precio_unitario' => 75], ['tipo' => 'mano_obra', 'concepto' => 'Mano de obra', 'cantidad' => 0.5, 'precio_unitario' => 48]]), 3, true);
        $this->estado($t, 'en_reparacion', 2, 2);
        $this->estado($t, 'listo_recoger', 2, 2);
        $this->estado($t, 'entregado', 0, 24 * 7);
        $this->valorar($t, 5, 'Rápidos y muy claros con el presupuesto.');

        // 11. Entregado tras un rechazo (no se reparó).
        $t = $this->crear(5, 400, ['asunto' => 'Rayón en la puerta', 'descripcion' => 'Presupuesto para pintar la puerta.', 'categoria' => 'carroceria', ...$coche('2468 QRS', 'Ford', 'Focus', 98000)]);
        $this->estado($t, 'en_diagnostico', 1, 3);
        $responder($presupuestar($t, [['tipo' => 'mano_obra', 'concepto' => 'Pintura de puerta', 'cantidad' => 1, 'precio_unitario' => 320]]), 5, false, 'Muy caro, lo dejo así.');
        $this->estado($t, 'listo_recoger', 1, 2);
        $this->estado($t, 'entregado', 0, 24);
        $this->valorar($t, 3);

        // 12. En reparación con conversación.
        $t = $this->crear(4, 40, ['asunto' => 'Vibración al frenar', 'descripcion' => 'Vibra el volante al frenar en autovía.', 'categoria' => 'mecanica', ...$coche('7531 BBC', 'Kia', 'Ceed', 88000)]);
        $this->asignar($t, 2);
        $this->estado($t, 'en_diagnostico', 2, 2);
        $responder($presupuestar($t, $frenos), 4, true);
        $this->mensaje($t, $this->clientes[4], '¿Para cuándo estaría? Lo necesito el viernes.', horas: 1);
        $this->estado($t, 'en_reparacion', 2, 1);
        $this->mensaje($t, $this->equipo[2], 'Lo tendrás el jueves por la tarde sin problema.');

        $this->terminar();
    }
}
