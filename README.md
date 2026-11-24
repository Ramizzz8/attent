# ATTENT

**Demo en vivo: [attent.andresrami.com](https://attent.andresrami.com)** (los datos se reinician cada noche; en la demo los enlaces mágicos aparecen en pantalla en lugar de enviarse por email).

Plataforma de incidencias **multiempresa y especializada por sector**. Los clientes de cada negocio abren incidencias desde un portal propio de la empresa (sin contraseña, con enlaces mágicos por email) y el equipo las gestiona desde un panel interno.

Lo que la diferencia de un gestor de tickets genérico es que **el sector cambia el comportamiento**:

| Sector | Qué aporta |
|---|---|
| **Taller mecánico** | Vehículos por matrícula con historial; presupuestos con líneas e IVA que el cliente **aprueba o rechaza desde el portal**; no se puede reparar sin presupuesto aprobado. |
| **Administrador de fincas** | Varios edificios con sus zonas comunes; **detección de incidencias repetidas** ("a mí también me afecta"); la prioridad sube con el número de vecinos afectados; proveedores externos que gestionan la incidencia con un enlace firmado, sin cuenta. |
| **Soporte informático** | Inventario de equipos con historial; prioridad por **matriz impacto × urgencia**; plazo de primera respuesta y de resolución; cierre automático. |

Común a todos: estados y transiciones fijados por sector, plazos de resolución (SLA) que se pausan mientras se espera al cliente, conversación con notas internas, adjuntos, historial de cambios, avisos por email, reapertura en 7 días y aislamiento total entre empresas.

## Tecnologías

Laravel 13 · Inertia 3 · React 19 + TypeScript · Tailwind + shadcn/ui · Laravel Reverb (WebSockets) · Pest · SQLite · Docker (FrankenPHP).

## Puesta en marcha (Windows con Laravel Herd)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # crea las 3 empresas de demostración
php artisan storage:link
npm run build                      # o "composer run dev" para desarrollo
php artisan serve --port=8010
```

Para subir fotos de hasta 5 MB, sube en el `php.ini` de Herd `upload_max_filesize = 6M` y `post_max_size = 30M` (por defecto PHP solo admite 2 MB por archivo; la aplicación avisa con un mensaje claro si se superan).

En `.env`, `APP_URL` debe coincidir con la URL desde la que se abre la aplicación (los enlaces mágicos van firmados con ella). Los emails se escriben en `storage/logs/laravel.log` (`MAIL_MAILER=log`) y se envían en cola: `php artisan queue:work`.

## Datos de demostración

Contraseña de todos los empleados: `password`.

| Empresa | Portal de clientes | Administrador | Empleados |
|---|---|---|---|
| Talleres Ruiz (taller) | `/talleres-ruiz` | `admin@talleres-ruiz.test` | `empleado1@talleres-ruiz.test`, `empleado2@…` |
| Fincas Levante (fincas) | `/fincas-levante` | `admin@fincas-levante.test` | `empleado1@fincas-levante.test`, `empleado2@…` |
| IES Ribera · Soporte TIC | `/soporte-tic` | `admin@soporte-tic.test` | `empleado1@soporte-tic.test`, `empleado2@…` |

El panel interno está en `/panel` (acceso en `/login`). Cada empresa tiene incidencias en **todos** sus estados, con plazos vencidos y en riesgo, mensajes, notas internas, presupuestos y vecinos afectados.

Para entrar como cliente en el portal: pide un enlace en `/<empresa>/acceder` con el email de un cliente de la demo y ábrelo desde `storage/logs/laravel.log` tras ejecutar `php artisan queue:work --once`.

## Tests

```bash
php artisan test
```

Cubren las reglas de cada sector, la máquina de estados (incluidos retrocesos y cambios simultáneos), los plazos, el aislamiento entre empresas, los enlaces mágicos caducados o manipulados, los adjuntos peligrosos y los permisos por rol.

## Documentación

- Diseño: [`docs/diseno.md`](docs/diseno.md)
- Decisiones tomadas durante la implementación: [`docs/decisiones-implementacion.md`](docs/decisiones-implementacion.md)
- Despliegue en un VPS: [`docs/despliegue.md`](docs/despliegue.md)
