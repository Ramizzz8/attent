<?php

namespace Database\Seeders;

use App\Enums\SectorClave;
use App\Models\Equipo;
use App\SoporteIt\MatrizPrioridad;
use App\Tickets\Sla;

class SoporteItDemoSeeder extends DemoBase
{
    public function run(): void
    {
        $this->empresa('IES Atiende · Soporte TIC', 'soporte-tic', SectorClave::SoporteIt, '#4f46e5',
            'Avisa de cualquier problema con los ordenadores, proyectores, la red o tus cuentas del instituto.');
        $this->renombrarEquipo('Coordinación TIC', 'Sergio Blasco', 'Ana Vera');
        $this->clientes([
            ['Mercedes López (Matemáticas)', 'mlopez@ies.test'],
            ['Tomás Ferrer (Inglés)', 'tferrer@ies.test'],
            ['Isabel Climent (Secretaría)', 'iclimet@ies.test'],
            ['Óscar Gómez (Tecnología)', 'ogomez@ies.test'],
            ['Beatriz Sáez (Jefatura)', 'bsaez@ies.test'],
            ['Hugo Marín (Biología)', 'hmarin@ies.test'],
        ]);

        $equipos = [];
        $inventario = [
            ['PRY', 'proyector', ['Aula 1', 'Aula 2', 'Aula 3', 'Aula 4', 'Aula 5', 'Salón de actos']],
            ['PC', 'sobremesa', ['Secretaría', 'Jefatura', 'Sala de profesores', 'Aula de informática 1', 'Aula de informática 2', 'Biblioteca']],
            ['POR', 'portatil', ['Carro de portátiles A', 'Carro de portátiles B', 'Departamento de Ciencias', 'Orientación']],
            ['IMP', 'impresora', ['Conserjería', 'Sala de profesores']],
            ['RED', 'red', ['Rack planta baja', 'Punto de acceso planta 1']],
        ];
        foreach ($inventario as [$pref, $tipo, $ubicaciones]) {
            foreach ($ubicaciones as $i => $u) {
                $codigo = sprintf('%s-%03d', $pref, $i + 1);
                $equipos[$codigo] = Equipo::create(['codigo' => $codigo, 'tipo' => $tipo, 'ubicacion' => $u]);
            }
        }

        $ticket = fn (int $cliente, float $horas, string $asunto, string $desc, string $categoria, string $impacto, ?string $equipo = null) => $this->crear($cliente, $horas, [
            'asunto' => $asunto, 'descripcion' => $desc, 'categoria' => $categoria, 'impacto' => $impacto, 'equipo_codigo' => $equipo,
        ]);
        $triaje = function ($t, string $urgencia) {
            $t = $t->fresh();
            $anterior = $t->prioridad;
            $t->urgencia = $urgencia;
            $t->prioridad = MatrizPrioridad::calcular($t->impacto, $urgencia);
            if ($t->prioridad !== $anterior) {
                app(Sla::class)->recalcular($t);
                $t->eventos()->create(['tipo' => 'prioridad', 'user_id' => $this->equipo[0]->id, 'datos' => ['de' => $anterior->etiqueta(), 'a' => $t->prioridad->etiqueta(), 'motivo' => "Urgencia {$urgencia}"]]);
            }
            $t->save();
        };

        // 1. Caída de red general: urgente, sin asignar todavía.
        $ticket(4, 0.3, 'No hay internet en todo el centro', 'Ningún aula tiene conexión desde las 9:00.', 'red', 'todos', 'RED-001');

        // 2. Nuevo con primera respuesta vencida.
        $ticket(1, 10, 'No puedo entrar en el aula virtual', 'Me dice que la contraseña es incorrecta.', 'accesos', 'individual');

        // 3. Asignado.
        $t = $ticket(0, 5, 'El proyector del aula 3 no enciende', 'La luz parpadea en rojo.', 'hardware', 'departamento', 'PRY-003');
        $this->asignar($t, 1);

        // 4. En curso con conversación.
        $t = $ticket(2, 8, 'La impresora de conserjería atasca el papel', 'Se atasca cada pocas hojas.', 'hardware', 'departamento', 'IMP-001');
        $this->asignar($t, 2);
        $this->estado($t, 'en_curso', 2);
        $this->mensaje($t, $this->equipo[2], 'Voy a revisar el rodillo. ¿Qué papel estáis usando?');
        $this->mensaje($t, $this->clientes[2], 'El de siempre, de 80 g.', horas: 0.5);

        // 5. Pendiente del usuario (SLA pausado).
        $t = $ticket(3, 26, 'Instalar software en el aula de informática', 'Necesito FreeCAD en los equipos del aula 1.', 'software', 'departamento', 'PC-004');
        $this->asignar($t, 1);
        $this->estado($t, 'en_curso', 1);
        $this->mensaje($t, $this->equipo[1], '¿Qué versión necesitas? ¿Te vale la 1.0?', horas: 2);
        $this->estado($t, 'pendiente_usuario', 1, 0.1);

        // 6. Urgencia elevada en triaje: vencido.
        $t = $ticket(4, 30, 'El PC de jefatura se reinicia solo', 'Se reinicia cada poco y pierdo el trabajo.', 'hardware', 'individual', 'PC-002');
        $this->asignar($t, 1);
        $triaje($t, 'alta');
        $this->estado($t, 'en_curso', 1, 1);
        $this->mensaje($t, $this->equipo[1], 'Sospecho de la fuente de alimentación; pedida una de repuesto.', interno: true);

        // 7. Resuelto ayer (el usuario aún puede reabrir).
        $t = $ticket(5, 30, 'No funciona el sonido del proyector del aula 5', 'Se ve pero no se oye.', 'hardware', 'departamento', 'PRY-005');
        $this->asignar($t, 2);
        $this->estado($t, 'en_curso', 2, 1);
        $this->mensaje($t, $this->equipo[2], 'Era el cable de audio. Cambiado.', horas: 2);
        $this->estado($t, 'resuelto', 2, 0.1);
        $this->valorar($t, 5, '¡Gracias por la rapidez!');

        // 8. Cerrado hace tiempo.
        $t = $ticket(0, 24 * 15, 'Alta de cuenta para alumno nuevo', 'Ha llegado un alumno a 2.º B.', 'accesos', 'individual');
        $this->asignar($t, 1);
        $this->estado($t, 'en_curso', 1, 1);
        $this->estado($t, 'resuelto', 1, 1);
        $this->estado($t, 'cerrado', null, 24 * 8, sistema: true);
        $this->valorar($t, 4);

        // 9. Otra incidencia del mismo proyector (historial del equipo).
        $t = $ticket(0, 24 * 40, 'Proyector del aula 3 con imagen amarillenta', 'Los colores se ven raros.', 'hardware', 'departamento', 'PRY-003');
        $this->asignar($t, 2);
        $this->estado($t, 'en_curso', 2, 2);
        $this->estado($t, 'resuelto', 2, 5);
        $this->estado($t, 'cerrado', null, 24 * 8, sistema: true);

        // 10. Wifi lento en una planta.
        $t = $ticket(5, 3, 'Wifi muy lento en la primera planta', 'Las clases con portátiles se hacen imposibles.', 'red', 'departamento', 'RED-002');
        $this->asignar($t, 1);

        // 11. Portátil roto.
        $ticket(3, 1.5, 'Pantalla rota en un portátil del carro A', 'Un alumno lo dejó caer.', 'hardware', 'individual', 'POR-001');

        // 12. Retroceso con motivo en el historial.
        $t = $ticket(1, 12, 'Correo no sincroniza en el móvil', 'Desde ayer no recibo correos en el móvil.', 'accesos', 'individual');
        $this->asignar($t, 2);
        $this->estado($t, 'en_curso', 2, 1);
        $this->estado($t, 'asignado', 2, 0.5, 'Lo cojo después del recreo, no lo he empezado.');

        $this->terminar();
    }
}
