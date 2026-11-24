<?php

namespace Database\Seeders;

use App\Enums\SectorClave;
use App\Fincas\AsignarProveedor;
use App\Fincas\SumarAfectado;
use App\Models\Edificio;
use App\Models\Proveedor;
use App\Models\Ticket;
use App\Tickets\CambiarEstado;
use App\Tickets\EnviarMensaje;

class FincasDemoSeeder extends DemoBase
{
    /** @var array<string, Edificio> */
    private array $edificios = [];

    /** @var array<string, Proveedor> */
    private array $proveedores = [];

    public function run(): void
    {
        $this->empresa('Fincas Levante', 'fincas-levante', SectorClave::Fincas, '#0d9488',
            'Avisa de cualquier avería en las zonas comunes de tu comunidad. Si ya la ha avisado un vecino, súmate y la priorizaremos.');
        $this->renombrarEquipo('Elena Sanz', 'Pablo Ferrer', 'Nuria Llopis');
        $this->clientes([
            ['Antonio García (1.º A)', 'antonio@vecinos.test', '611000001'],
            ['Rosa Martí (2.º B)', 'rosa@vecinos.test', '611000002'],
            ['Javier Pons (3.º C)', 'javier@vecinos.test', '611000003'],
            ['Laura Ibáñez (4.º A)', 'laura@vecinos.test', '611000004'],
            ['Miguel Roca (bajo)', 'miguel@vecinos.test', '611000005'],
            ['Pilar Soto (5.º B)', 'pilar@vecinos.test', '611000006'],
            ['David Ruiz (2.º A)', 'david@vecinos.test', '611000007'],
        ]);

        $zonas = ['Ascensor*', 'Portal', 'Garaje', 'Piscina', 'Tejado', 'Escalera'];
        foreach ([['mar', 'Residencial Mar', 'Av. del Mediterráneo 14'], ['sol', 'Torre Sol', 'C/ del Sol 3'], ['jardines', 'Jardines del Turia', 'C/ Valencia 120']] as [$k, $n, $d]) {
            $e = Edificio::create(['nombre' => $n, 'direccion' => $d, 'slug' => Edificio::slugLibre($this->empresa->id, $n)]);
            foreach ($zonas as $z) {
                $e->zonas()->create(['nombre' => rtrim($z, '*'), 'prioridad_alta' => str_ends_with($z, '*')]);
            }
            $this->edificios[$k] = $e;
        }

        foreach ([
            ['fontaneria', 'Fontanería Paco', 'Fontanero', 'paco@fontaneria.test', ['mar', 'sol', 'jardines']],
            ['ascensores', 'Ascensores Turia', 'Ascensorista', 'avisos@ascensoresturia.test', ['mar', 'sol', 'jardines']],
            ['limpieza', 'Limpiezas Brillo', 'Limpieza', 'brillo@limpiezas.test', ['mar', 'jardines']],
            ['electricidad', 'Electro Ramos', 'Electricista', 'ramos@electro.test', ['sol', 'jardines']],
        ] as [$k, $n, $o, $m, $eds]) {
            $p = Proveedor::create(['nombre' => $n, 'oficio' => $o, 'email' => $m, 'telefono' => '96'.random_int(1000000, 9999999)]);
            $p->edificios()->sync(array_map(fn ($e) => $this->edificios[$e]->id, $eds));
            $this->proveedores[$k] = $p;
        }

        $donde = fn (string $edificio, string $zona, string $vivienda) => [
            'edificio_id' => $this->edificios[$edificio]->id,
            'zona_id' => $this->edificios[$edificio]->zonas()->where('nombre', $zona)->value('id'),
            'datos' => ['vivienda' => $vivienda],
        ];
        $proveedor = function (Ticket $t, string $p, float $horas = 2) {
            $this->luego($horas);
            app(AsignarProveedor::class)->ejecutar($t->fresh(), $this->proveedores[$p], $this->equipo[1]);
        };
        $resuelveProveedor = function (Ticket $t, string $p, string $comentario, float $horas) {
            $this->luego($horas);
            app(EnviarMensaje::class)->ejecutar($t->fresh(), $this->proveedores[$p], $comentario);
            app(CambiarEstado::class)->ejecutar($t->fresh(), 'resuelta', null);
        };

        // 1. Ascensor parado con muchos vecinos afectados → urgente.
        $t = $this->crear(0, 6, ['asunto' => 'El ascensor no funciona', 'descripcion' => 'Se ha quedado parado en el 3.º con las puertas abiertas.', 'categoria' => 'averia', ...$donde('mar', 'Ascensor', '1.º A')]);
        foreach ([1, 2, 3, 4, 5] as $v) {
            $this->luego(0.4);
            app(SumarAfectado::class)->ejecutar($t->fresh(), $this->clientes[$v]);
        }
        $this->estado($t, 'en_revision', 1, 0.5);
        $proveedor($t, 'ascensores', 0.5);

        // 2. Reportada recientemente.
        $this->crear(6, 2, ['asunto' => 'Bombilla fundida en el portal', 'descripcion' => 'El portal está a oscuras por la noche.', 'categoria' => 'averia', ...$donde('sol', 'Portal', '2.º A')]);

        // 3. Reportada hace días: plazo vencido.
        $this->crear(2, 24 * 4, ['asunto' => 'Manchas de aceite en el garaje', 'descripcion' => 'Plaza 14, hay una mancha grande.', 'categoria' => 'limpieza', ...$donde('jardines', 'Garaje', '3.º C')]);

        // 4. En revisión con nota interna y dos afectados.
        $t = $this->crear(3, 30, ['asunto' => 'Ruidos de la piscina por la noche', 'descripcion' => 'La depuradora hace mucho ruido de madrugada.', 'categoria' => 'ruido', ...$donde('mar', 'Piscina', '4.º A')]);
        foreach ([1, 4] as $v) {
            app(SumarAfectado::class)->ejecutar($t->fresh(), $this->clientes[$v]);
        }
        $this->estado($t, 'en_revision', 2, 3);
        $this->mensaje($t, $this->equipo[2], 'Consultar con el presidente si se puede programar la depuradora de día.', interno: true);

        // 5. Asignada a proveedor.
        $t = $this->crear(4, 20, ['asunto' => 'Fuga de agua en el garaje', 'descripcion' => 'Gotea una tubería junto a la plaza 3.', 'categoria' => 'averia', ...$donde('sol', 'Garaje', 'bajo')]);
        $this->estado($t, 'en_revision', 1, 1);
        $proveedor($t, 'fontaneria');

        // 6. Visita programada.
        $t = $this->crear(5, 50, ['asunto' => 'Tejas sueltas', 'descripcion' => 'Tras el viento se ven tejas movidas.', 'categoria' => 'seguridad', ...$donde('jardines', 'Tejado', '5.º B')]);
        $this->estado($t, 'en_revision', 1, 1);
        $proveedor($t, 'electricidad');
        $this->luego(4);
        $t->fresh()->update(['visita_programada_en' => $this->ahora->copy()->addDay()->setTime(10, 0)]);
        app(CambiarEstado::class)->ejecutar($t->fresh(), 'visita_programada', null);

        // 7. Resuelta por el proveedor hace un día.
        $t = $this->crear(0, 24 * 3, ['asunto' => 'Atasco en la bajante', 'descripcion' => 'Huele mal en la escalera.', 'categoria' => 'averia', ...$donde('mar', 'Escalera', '1.º A')]);
        $this->estado($t, 'en_revision', 1, 1);
        $proveedor($t, 'fontaneria');
        $resuelveProveedor($t, 'fontaneria', 'Desatascada la bajante general y revisado el sifón.', 30);
        $this->valorar($t, 4, 'Bien, aunque tardaron un día en venir.');

        // 8. Cerrada.
        $t = $this->crear(1, 24 * 20, ['asunto' => 'Portal sucio tras la obra', 'descripcion' => 'Polvo y restos en la entrada.', 'categoria' => 'limpieza', ...$donde('mar', 'Portal', '2.º B')]);
        $this->estado($t, 'en_revision', 2, 2);
        $proveedor($t, 'limpieza');
        $resuelveProveedor($t, 'limpieza', 'Limpieza a fondo del portal realizada.', 20);
        $this->estado($t, 'cerrada', 0, 24 * 8);
        $this->valorar($t, 5);

        // 9. Descartada.
        $t = $this->crear(6, 24 * 6, ['asunto' => 'Vecino del 3.º hace ruido', 'descripcion' => 'Ponen música alta los fines de semana.', 'categoria' => 'ruido', ...$donde('sol', 'Escalera', '2.º A')]);
        $this->estado($t, 'descartada', 0, 5, 'Es un asunto privado entre vecinos; se ha informado al presidente.');

        // 10. Resuelta directamente por el administrador.
        $t = $this->crear(3, 24 * 2, ['asunto' => 'Mando del garaje no abre', 'descripcion' => 'Mi mando ha dejado de funcionar.', 'categoria' => 'averia', ...$donde('jardines', 'Garaje', '4.º A')]);
        $this->estado($t, 'en_revision', 1, 1);
        $this->mensaje($t, $this->equipo[1], 'Hemos dado de alta un mando nuevo. Pásate por la oficina a recogerlo.');
        $this->estado($t, 'resuelta', 1, 0.2);

        // 11. Seguridad: puerta del garaje, empieza en prioridad alta.
        $t = $this->crear(2, 10, ['asunto' => 'La puerta del garaje no cierra', 'descripcion' => 'Se queda abierta y entra cualquiera.', 'categoria' => 'seguridad', ...$donde('sol', 'Garaje', '3.º C')]);
        $this->estado($t, 'en_revision', 0, 0.5);

        // 12. Piscina en otro edificio, recién reportada.
        $this->crear(5, 4, ['asunto' => 'El agua de la piscina está verde', 'descripcion' => 'Parece que no funciona el clorador.', 'categoria' => 'averia', ...$donde('jardines', 'Piscina', '5.º B')]);

        $this->terminar();
    }
}
