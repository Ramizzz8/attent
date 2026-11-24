# Atiende — Plataforma de incidencias por sector (diseño)

> Documento de diseño previo a la implementación (noviembre de 2026).

## 1. Intención y porqué

Una plataforma web multiempresa donde **los clientes de un negocio abren incidencias** y **los empleados de ese negocio las gestionan**. Lo que la distingue de un gestor de tickets genérico es que **cada empresa pertenece a un sector y el sector cambia el comportamiento**: qué pide el formulario, qué estados existen, qué transiciones se permiten, cómo se prioriza y qué módulos extra aparecen.

Tiene que estar completo, desplegado y con lógica real (no solo CRUD). Cada particularidad de sector debe ser creíble y explicable.

## 2. Actores

| Actor | Qué hace | Cómo accede |
|---|---|---|
| **Cliente** (conductor, vecino, usuario de un equipo) | Abre tickets, los sigue, responde, aprueba presupuestos, valora el servicio | Portal público de la empresa. Sin contraseña: email + enlace mágico. Cuenta opcional con contraseña |
| **Empleado** | Ve la bandeja, se asigna tickets, cambia estados, conversa con el cliente, deja notas internas | Panel interno con usuario y contraseña |
| **Administrador de empresa** | Todo lo del empleado + gestiona empleados, ajustes del portal (logo, colores, textos), catálogos del sector, SLA y estadísticas | Panel interno |
| **Proveedor externo** (solo comunidades de vecinos) | Recibe la incidencia asignada y actualiza su estado | Enlace firmado sin cuenta |

Las empresas se crean con seeders o comando de consola (no hay registro autoservicio ni superadmin en la primera versión).

## 3. Objetivos

### Núcleo (imprescindible)
- Portal público por empresa en `/{slug-empresa}` con su logo, colores y formulario adaptado al sector.
- Alta de ticket por el cliente con verificación del email (enlace mágico) antes de que el ticket entre en la bandeja, para evitar spam.
- Seguimiento del ticket por el cliente vía enlace mágico (URL firmada que caduca) y cuenta opcional.
- Conversación cliente ↔ empleado en el ticket; notas internas invisibles para el cliente.
- Adjuntos (imágenes y PDF, con límite de tamaño).
- Motor de sectores: cada sector define campos propios, estados, transiciones válidas, prioridades, SLA y módulos.
- Tres sectores completos (sección 6).
- Asignación manual de tickets a empleados.
- SLA: fecha límite calculada por prioridad; aviso visual al acercarse y marca de incumplido.
- Emails al cliente en cada cambio relevante (enviados en cola).
- Historial de cambios (auditoría) por ticket.
- Búsqueda y filtros en la bandeja.
- Datos de demostración: una empresa por sector con tickets en todos los estados, listos para enseñar.
- Tests de lo crítico (sección 10).

### Importante (si el núcleo está terminado, en este orden)
1. Panel interno en tiempo real con Laravel Reverb (nuevo ticket, cambio de estado, nuevo mensaje).
2. Estadísticas por empresa: abiertos/cerrados, tiempo medio de resolución, % SLA cumplido, carga por empleado, satisfacción media, por categoría.
3. Valoración de satisfacción (1–5 + comentario) al cerrar.
4. PDF del presupuesto del taller.
5. Despliegue en un VPS bajo un subdominio propio.

### Recortable (solo si sobra tiempo)
- SLA en horario laboral con festivos (la versión base usa horas naturales con pausa).
- Asignación automática (reparto equitativo).
- QR imprimible de los equipos de soporte informático.
- Registro de empresa autoservicio y panel de superadmin.

## 4. No-objetivos

- Pagos o facturación real (el presupuesto del taller se aprueba, no se cobra).
- App móvil nativa (sí diseño responsive; el portal del cliente debe ir bien en móvil).
- Chat en directo con el cliente (la conversación es asíncrona; el tiempo real es para el panel interno).
- Sector clínica (descartado: los datos de salud son categoría especial en el RGPD y no aportan al objetivo).
- Multidioma: solo español.
- Subdominios por empresa (se usa ruta `/{slug}`).

## 5. Anti-objetivos (sería un fracaso aunque funcione)

- Que el sector sea solo una etiqueta y todos los tickets se comporten igual.
- Que un empleado de una empresa pueda ver datos de otra (el aislamiento entre empresas debe estar probado con tests).
- Obligar al cliente a crear contraseña para abrir o seguir un ticket.
- Publicar tres sectores a medias: si falta tiempo, se recorta de la lista "Recortable" y luego "Importante", nunca del núcleo ni del comportamiento propio de cada sector.

## 6. Sectores

Cada sector define: campos del formulario del portal, estados y transiciones, categorías, regla de prioridad, SLA por defecto y módulos extra. El administrador puede ajustar SLA y catálogos, pero **no** los estados/transiciones (son la identidad del sector).

### 6.1 Taller mecánico
- **Formulario:** matrícula, marca, modelo, kilómetros, descripción del problema, fotos. Si el cliente ya tiene vehículos registrados, puede elegir uno.
- **Módulo vehículos:** el vehículo es una entidad propia ligada al cliente; se ve su historial de reparaciones.
- **Módulo presupuestos:** el empleado crea un presupuesto con líneas (pieza / mano de obra, cantidad, precio), IVA y total. El cliente lo **aprueba o rechaza desde el portal**. Un presupuesto rechazado puede revisarse y reenviarse como nueva versión mientras el vehículo no se haya entregado.
- **Estados:** Recibido → En diagnóstico → Presupuesto enviado → (Aprobado → En reparación ⇄ Esperando piezas → Listo para recoger → Entregado) | (Rechazado → [Presupuesto enviado (nueva versión)] | Listo para recoger → Entregado). No se puede pasar a "En reparación" sin presupuesto aprobado.
- **SLA:** de diagnóstico (p. ej. 48 h desde Recibido).

### 6.2 Administrador de fincas (comunidades de vecinos)
- **La empresa es un administrador de fincas que gestiona varios edificios.** Entidad **Edificio** (nombre, dirección) con sus **zonas comunes** (ascensor, portal, garaje, piscina, tejado…) y sus vecinos.
- **Portal:** el vecino elige su edificio (o entra por un enlace o QR propio del edificio, que lo deja preseleccionado), indica piso/puerta, zona común, categoría (avería, limpieza, ruido, seguridad), descripción y fotos.
- **Detección de duplicados por edificio:** al elegir zona + categoría, si hay una incidencia abierta igual en ese edificio se muestra y el vecino puede pulsar "A mí también me afecta" en lugar de crear otra. Se suma a la lista de afectados y recibe los avisos.
- **Prioridad dinámica:** sube automáticamente al superar un umbral de afectados; ciertas zonas/categorías (ascensor, seguridad) empiezan en alta.
- **Módulo proveedores:** catálogo de proveedores del administrador (fontanero, ascensorista…), con los edificios que atiende cada uno. Al asignar, el proveedor recibe un enlace firmado para marcar "Visita programada" con fecha y "Resuelta" con comentario/foto.
- **Estados:** Reportada → En revisión → Asignada a proveedor → Visita programada → Resuelta → Cerrada. También "Descartada" con motivo.
- **Filtro por edificio** en la bandeja y en las estadísticas.

### 6.3 Soporte informático (empresa o colegio)
- **Módulo inventario:** equipos con código de inventario, tipo y ubicación.
- **Formulario:** equipo (por código o lista), categoría (hardware, software, red, accesos/cuentas), impacto (solo yo / mi departamento / todos), descripción, capturas.
- **Prioridad:** la inicial sale del impacto y la categoría; el empleado fija la urgencia en el triaje y la prioridad se recalcula con la matriz impacto × urgencia.
- **SLA:** el más estricto de los tres, con tiempo de primera respuesta (desde la creación) y de resolución por prioridad (se recalcula si cambia la prioridad).
- **Estados:** Nuevo → Asignado → En curso ⇄ Pendiente del usuario → Resuelto → Cerrado (se cierra solo si el usuario no reabre en 7 días). Historial de incidencias por equipo.

## 7. Restricciones

- **Plazo muy corto:** alrededor de una semana. Prioridad estricta según la sección 3.
- Desarrollo en Windows. **No están instalados PHP, Composer, Docker ni PostgreSQL** (sí Node 24). Hay que instalar Laravel Herd (PHP + Composer).
- El proyecto vive en `C:\dev\atiende`, fuera de OneDrive (OneDrive rompe `node_modules`).
- Despliegue en un VPS que ya sirve otras webs con Caddy.
- El frontend es React; el backend PHP debe quedar legible y explicable.

## 8. Enfoques considerados

| Enfoque | Ventajas | Inconvenientes |
|---|---|---|
| **A. Laravel + Inertia + React + PostgreSQL** ✅ elegido | Autenticación, URLs firmadas (enlaces mágicos), colas, emails, permisos (policies) y tiempo real (Reverb) ya incluidos; el front sigue siendo React | Backend en PHP |
| B. Next.js + Prisma + PostgreSQL | Todo en TypeScript | Tiempo real, colas y permisos montados a mano con varias librerías; más riesgo con el plazo |
| C. Spring Boot + React separado | Muy robusto | Mucho código repetitivo; imposible con el plazo |

Se eligió A.

## 9. Decisiones tomadas

- Stack: Laravel (versión estable actual) + Inertia + React + PostgreSQL + Reverb.
- Sectores: taller mecánico, administrador de fincas con varios edificios y soporte informático. Clínica descartada.
- El cliente abre el ticket; los empleados lo gestionan.
- Acceso del cliente por enlace mágico con cuenta opcional.
- Multiempresa en una sola base de datos con `empresa_id` y filtro global obligatorio; portal en `/{slug}`.
- Estados y transiciones definidos en código por sector, no editables por la empresa.

### Decisiones de detalle
- **Identidad del cliente por empresa:** el cliente es una entidad de cada empresa (con `empresa_id`, dentro del filtro global). El mismo email en dos empresas son dos clientes. Guard de autenticación propio para clientes, separado del de empleados. Con sesión iniciada no se re-verifica el email al abrir tickets.
- **SLA base en horas naturales con pausa:** el reloj se para en estados de espera ajena ("Presupuesto enviado", "Esperando piezas", "Pendiente del usuario"). El horario laboral es mejora recortable.
- **Reaperturas y retrocesos:** el cliente puede reabrir un ticket resuelto durante 7 días; vuelve al estado de trabajo del sector ("En reparación", "Asignada a proveedor", "En curso"). Los empleados pueden retroceder un estado indicando motivo (queda en el historial), sin saltarse reglas como reparar sin presupuesto aprobado.

## 10. Lo que queda al constructor

- Estructura del motor de sectores (clases por sector, máquina de estados, cómo se guardan los campos propios). Lo que tenga relaciones (vehículos, presupuestos, edificios, zonas, proveedores, equipos) va en tablas propias.
- Esquema exacto de base de datos.
- Librería de componentes de UI (sugerencia: Tailwind + shadcn/ui, que trae el starter kit de React de Laravel) y diseño visual.
- PostgreSQL en local o SQLite en local y PostgreSQL en producción, mientras las consultas sean portables.
- Proveedor de email en producción; Mailpit o el log de correo en local.
- Tests (Pest recomendado). Mínimo: transiciones de estado por sector, aislamiento entre empresas, permisos por rol, enlaces mágicos y duplicados por edificio.
- Configuración de despliegue (Caddy, cola y Reverb como servicios).
