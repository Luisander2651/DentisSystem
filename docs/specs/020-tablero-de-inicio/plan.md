---
spec: 020-tablero-de-inicio
status: approved
constitution_version: 1.2.0
created: 2026-10-23
---

# Plan · 020 Tablero de inicio

## Enfoque técnico
El inicio del staff sigue en `/dashboard`, pero cada sección carga sus datos de un endpoint propio.
Así una sección puede fallar sin tumbar a las demás (CA12) y cada una lleva su permiso. No hay
módulo nuevo, migración ni dependencia nueva. **Appointments** suma dos lecturas: el día de hoy ya
calculado (lista, contadores, siguiente cita y las que vienen después) y las citas sin cerrar
(hasta 10 más el total). El recorte del doctor es el mismo de la 018. **AppointmentTracking** suma
la lista de completadas sin seguimiento y una operación nueva, "registrar seguimiento", sobre una
cita ya completada. Esa operación tiene su propio FormRequest y sus mensajes en español, y nunca
completa una cita abierta (CA15). Los indicadores del mes del administrador reutilizan tal cual
`GET /api/v1/reports/monthly` con el mes en curso, así que los números son los de "Reportes"
(CA10). La pantalla sigue el candidato c5 de `design.md` y reutiliza los diálogos de la agenda:
completar, cancelar, agendar, reprogramar y ver detalle. El de completar suma un modo "Registrar
seguimiento". Para que la prueba de navegador vea siempre el mismo día, en local y en testing el
servidor acepta una hora fija que envía la prueba (D16). En producción esa cabecera se ignora.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 020 `approved` (reaprobada el 2026-10-23) y `design.md` `approved` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene su test de integración o de unidad, escrito antes que el código (Trazabilidad). Los value objects nuevos (`TodayAgenda`, `AppointmentPage`) tienen tests de unidad y de propiedades con Eris. Lo que solo existe en el navegador tiene entradas y flujos de `npm run test:ui` que fallan antes y pasan después: estados de carga, vacío y error por sección con "Reintentar" (A5), diálogos desde el inicio incluido reprogramar (A1), foco del buscador con `?buscar=1` (A4), orden en móvil y nombres con HTML. `UiAuditSeederTest` se actualiza con los datos nuevos (A15) | test: Trazabilidad; `TodayAgendaPropertiesTest`, `AppointmentPagePropertiesTest`; `npm run test:ui` (entradas `dashboard-*`) |
| P3 Capas del módulo | ✅ | Cada endpoint sigue Controller invocable → UseCase (`assertCan` primero) → Domain Service → `*RepositoryInterface`. Sin dependencias nuevas entre módulos: AppointmentTracking ya usa el dominio de Appointments (`CompleteAppointmentUseCase`) y ya lee `appointments` desde Infrastructure (la relación `appointment()` de `AppointmentTrackingModel`); su repositorio nuevo hace lo mismo (A23). `Domain/` sin Eloquent, facades ni `Infrastructure`. Excepción justificada (A6, decisión del usuario, 2026-10-23): `ShowDashboardPageController` entrega a la vista el rol, la fecha, la hora y el mes sin caso de uso, como `ShowReportsPageController` en la 019, porque la vista no lleva datos protegidos; los datos llegan por la API, que sí pasa por casos de uso con `assertCan` | test: `AppointmentsArchitectureTest` y `AppointmentTrackingArchitectureTest` sobre **todos** los archivos de `Domain/` que la spec crea o modifica (A18): ningún `use …\Infrastructure`, `Illuminate\Support\Facades` ni `Eloquent`; `DashboardScreenTest` por rol; manual: `/review` |
| P4 Contrato de API primero | ✅ | Cuatro endpoints nuevos con su contrato en "Contratos y datos"; `GET /reports/monthly` no cambia | test: `TodaySummaryTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest`, `RegisterAppointmentTrackingTest` fijan la forma del JSON y cada código |
| P5 Autorización en el servidor | ✅ | Rutas en el grupo `auth:sanctum` + `staff`. Cada caso de uso exige su permiso. El recorte del doctor y la comprobación de que la cita es suya van en el servidor e ignoran cualquier filtro que llegue. Test de acceso denegado por cada actor no autorizado y por endpoint, y otro de que `assertCan` va antes del repositorio | test: `DashboardAccessControlTest`, `DashboardUseCasesAuthorizationTest`, `RolePermissionsTest` |
| P6 Validación con FormRequest | ✅ | `RegisterAppointmentTrackingRequest`: reglas en array y mensajes en español, con las mismas reglas que completar (009 CA1, CA13). Las tres lecturas no reciben entrada: un parámetro que llegue se ignora. `POST /complete` no cambia su entrada y queda fuera de P6 (1.1.1) | test: `RegisterAppointmentTrackingValidationTest` (dataset) |
| P7 Errores sin detalles internos | ✅ | 500 con `UnexpectedErrorResponse`. Los 409 y 404 del registro llevan textos fijos en español, sin datos del paciente. Un doble envío que choca con la restricción única de `appointment_tracking.appointment_id` se convierte en el mismo 409, no en 500 | test: `UnexpectedDashboardErrorTest`, `RegisterAppointmentTrackingTest` (doble envío) |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración ni variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones. Las consultas usan el índice `appointments(date, status)` y la restricción única de `appointment_tracking.appointment_id` | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna. `playwright-core` solo se instaló fuera del repositorio para medir el diseño | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Las listas devuelven hora, fecha, nombre del paciente, tratamiento, doctor y estado, nunca datos clínicos. Modelo de amenazas TM1–TM9. Sin logs nuevos con datos de pacientes, y un test lo comprueba (A7) | test: TM1–TM9; `DashboardNoClinicalDataTest`; `DashboardLogsTest` (captura el log durante el registro, la cancelación y los errores forzados y afirma que no contiene los valores sembrados del paciente ni del seguimiento) |
| P12 Producción con aprobación humana | ✅ | Release con `/release` y aprobación del dueño. Línea base de dependencias del 2026-10-23: `composer audit` y `npm audit --audit-level=high` sin hallazgos | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | La siguiente cita, los contadores, las que vienen después, "sin cerrar", "sin seguimiento", el total pendiente, la etiqueta del día de cada grupo y las acciones permitidas por cita se calculan en el servidor. La pantalla solo pinta y oculta lo que la API ya rechaza | test: `TodayAgendaTest`, `TodaySummaryTest`, `UnclosedAppointmentsTest`; manual: `/review` del JS |
| P14 Trazabilidad y auditoría | ❌ aceptado: la spec pide registrar el seguimiento que se registra desde el inicio (CA5, CA8), las cancelaciones (CA6) y los accesos denegados (CA13, CA14, CA16), pero el registro de auditoría aún no existe (OB1.a) y tampoco la correlación por petición (OB3.a), ambos del objetivo 5 — aprobado por el usuario el 2026-10-23, con el objetivo 5 como la próxima spec | Quinta vez, después de 014, 017, 018 y 019. Los puntos de emisión quedan anotados en "Observabilidad". OB1.a y OB3.a siguen pendientes y esta spec no los mitiga | — |
| P15 Solo el sistema de diseño | ✅ | Componentes `x-ui.*` existentes y las piezas de `design.md` → "Cambios a incorporar al sistema" (siguiente cita, línea de ahora, regla de color de la barra del día). La primera tarea las añade a `system.md` y `system.html`. Sin tokens nuevos | test: `OnlyDesignTokensTest`, `DesignTokensTest`, `npm run test:ui`; `evaluate.mjs` con `design/criterios.json` y `criterios-doctor.json` sobre `/dashboard` servido en local |

Resultado: 11 ✅ · 3 ➖ · 1 ❌ aceptado (P14).

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Core · Autorización | `app/Core/Authorization/CurrentActorAuthorizationService.php`: sin permisos nuevos. Las lecturas usan `appointments.view` / `appointments.view-own`; "sin seguimiento" y registrar usan `appointment-tracking.create` / `appointment-tracking.create-own` | bajo: lo fija `RolePermissionsTest` |
| Appointments · Domain | `Domain/ValueObjects/TodayAgenda.php` (nuevo): `fromAppointments(AppointmentEntity[] $appointments, DateTimeImmutable $now)`. Ordena por hora, cuenta total, completadas, por atender (asignada o reprogramada, sea cual sea la hora) y canceladas. La siguiente es la primera asignada o reprogramada con hora ≥ ahora; "después", las dos siguientes por atender y cuántas más quedan. `Domain/ValueObjects/AppointmentPage.php` (nuevo): `items` (≤ 10) y `total`, con `total ≥ count(items)` | medio: aquí viven CA2 y CA3 |
| Appointments · Domain | `Domain/Repositories/AppointmentsRepositoryInterface.php`: `findUnclosedBefore(AppointmentDate $today, ?AppointmentUserId $userId, int $limit): AppointmentPage` (fecha < hoy, estado asignada o reprogramada, de la más reciente a la más antigua por fecha y hora). `Domain/Service/AppointmentsService.php`: `findUnclosed(...)` | bajo |
| Appointments · Application | `Aplication/UseCases/GetTodaySummaryUseCase.php` (recorte del doctor como `GetTodayAppointmentsUseCase`, ahora en `now()` de la clínica → `TodayAgenda`) y `Aplication/UseCases/GetUnclosedAppointmentsUseCase.php` (mismo recorte, límite 10). El recorte se extrae a `Aplication/Support/DoctorScope.php` (`final readonly`, `forActor(AuthorizationServiceInterface)`), que también usan los dos casos de uso de la 018 (cierra 018/R5 sin cambiar su comportamiento) | bajo: `AgendaByRoleTest` fija el comportamiento de la 018 |
| Appointments · Infrastructure | `EloquentAppointmentRepository::findUnclosedBefore`: una consulta con `with(user, patient, treatment)` limitada a 10 y un `count()` con los mismos filtros. Controladores invocables `GetTodaySummaryController` y `GetUnclosedAppointmentsController`. Recursos `TodaySummaryResource` y `DashboardAppointmentResource` (id, fecha, hora, estado, `day_label` como "Ayer · miércoles 21" o "Martes 20", paciente, tratamiento, doctor y `can`: acciones que el actor puede hacer sobre esa cita) | bajo |
| AppointmentTracking · Domain | `Domain/Repositories/PendingTrackingRepositoryInterface.php` (nuevo): `findCompletedWithoutTracking(?string $userId, int $limit): array{items, total}` con tipos del dominio. `Domain/Exceptions/TrackingRegistrationException.php` (nueva): `alreadyRegistered()` "Esta cita ya tiene seguimiento registrado.", `notCompleted()` "Solo se registra el seguimiento de una cita completada.", `cancelledAppointment()` "No se puede completar una cita cancelada." y `appointmentNotFound()` "No se encontró la cita.", sin datos de la cita (A9) | bajo |
| AppointmentTracking · Application | `Application/UseCases/GetCompletedWithoutTrackingUseCase.php`: `create` sin recorte, `create-own` con el recorte del doctor; el asistente recibe 403. `Application/UseCases/RegisterAppointmentTrackingUseCase.php`: el mismo control que `CompleteAppointmentUseCase` (permiso y que la cita sea del doctor), y además exige estado `completada` y sin seguimiento. Guarda el seguimiento y las recetas con `AppointmentTrackingService` y `PrescriptionService` en una transacción, sin tocar la cita. La construcción del seguimiento y de las recetas, que hoy vive en `CompleteAppointmentUseCase`, se extrae a `Application/Support/TrackingFactory.php` para no duplicarla. `CompleteAppointmentUseCase` cambia sus rechazos de cita cancelada y de cita con seguimiento a `TrackingRegistrationException::cancelledAppointment()` y `::alreadyRegistered()`, en español y sin id (CA15, A9); el resto de completar no cambia | medio: comparte la construcción con completar; `CompleteAppointmentTest` fija que completar no cambia salvo esos dos textos |
| AppointmentTracking · Infrastructure | `Persistence/Eloquent/EloquentPendingTrackingRepository.php`: `AppointmentModel` con `status = completada` y `whereNotExists` sobre `appointment_tracking`, recorte opcional por `user_id`, orden por fecha y hora descendente, límite 10 y total. `Http/Controllers/GetCompletedWithoutTrackingController.php` y `RegisterAppointmentTrackingController.php`; ninguno captura `AuthorizationException`: el 403 sale por el render de `bootstrap/app.php`, el punto único donde se engancharán los accesos denegados (A13). `CompleteAppointmentController` responde los dos rechazos nuevos con 409 y su texto. `Http/Requests/RegisterAppointmentTrackingRequest.php`. El repositorio que guarda el registro convierte `UniqueConstraintViolationException` en `TrackingRegistrationException::alreadyRegistered()` | medio |
| Providers | `app/Providers/AppServiceProvider.php`: enlace de `PendingTrackingRepositoryInterface` | bajo |
| Rutas | `routes/api.php`, grupo `auth:sanctum` + `staff`: `GET /agenda/today-summary`, `GET /agenda/unclosed` y `GET /agenda/without-tracking` dentro del prefijo `agenda` (no chocan con `/appointments/{id}`), y `POST /appointments/{id}/tracking`. `routes/web.php`: `/dashboard` pasa a `ShowDashboardPageController` (invocable, en `app/Core/Http/`), que entrega a la vista el rol, la fecha y la hora de la clínica, el mes en curso (`AAAA-MM`) y las capacidades. Al staff inactivo le responde la vista "Sin permiso" (403), como `EnsureActiveStaff` en `/agenda` (CA16, A17). Excepción de P3 registrada (A6) | bajo |
| Interfaz · pantalla | `resources/views/pages/dashboard.blade.php`: la rama del staff se reescribe según c5. Encabezado con `h1` "¡Hola, Nombre!" y la fecha; "Nueva cita" (`x-ui.button primary`) solo para el administrador. Secciones "Siguiente cita", "Cómo va el día", "Acciones rápidas", "Agenda de hoy", "<Mes en curso>" (p. ej. "Septiembre", solo administrador; c5 decía "Octubre hasta hoy", pero CA10 cuenta todo el mes; A33), "Citas sin cerrar" y "Completadas sin seguimiento" (administrador y doctor). Cada una con su región de carga, vacío y error, y `<template>` que el JS clona. Se van "Estado: En línea", "Actividad reciente", "Nuevo usuario", la tarjeta con inicial, correo y rol, y `x-ui.create-user-modal`. `@section('title', 'Inicio - Dentissa')` para todos los roles. Incluye `x-calendar.view-appointment-modal`, `complete-appointment-modal`, `cancel-appointment-confirm`, `create-appointment-modal` y `edit-appointment-modal` según el rol, como la agenda, para que el detalle ofrezca Reprogramar y ningún botón quede sin diálogo (CA2, A1). En la rama del paciente solo cambia la inicial, que pasa a `mb_substr` (CA11, A16) | medio: pantalla de entrada de todos los roles |
| Interfaz · componentes | `resources/views/components/dashboard/next-appointment.blade.php`, `day-progress.blade.php`, `now-line.blade.php` y `pending-group.blade.php` (nuevos, solo tokens), con las medidas de `design.md` | bajo |
| Interfaz · JS | `resources/js/pages/dashboard.js` (registrado en `vite.config.js`), reescrito: carga en paralelo con `fetch('/api/v1/...', { credentials: 'include' })` el resumen de hoy, las sin cerrar, las sin seguimiento (si el rol las ve) y `reports/monthly?month=<mes en curso>` (solo administrador). Pinta cada sección por separado: carga, vacío con el texto de la spec y error con "Reintentar" (bloque de error de carga de `system.md`), sin tocar las demás. Inserta todo dato con `textContent` o clonando plantillas, nunca con `innerHTML`. Define `window.agendaPage.reload` para que los diálogos reutilizados recarguen las secciones afectadas tras completar, cancelar, agendar o registrar. Los avisos de pendientes son enlaces a su sección | medio |
| Interfaz · diálogos reutilizados | `resources/js/pages/agenda/complete-appointment.js` y `components/calendar/complete-appointment-modal.blade.php`: `open(appointmentId, opener, { mode: 'register' })` cambia el título a "Registrar seguimiento", la confirmación a "¿Registrar el seguimiento de esta cita?" con "Sí, registrar seguimiento" (A20), envía a `POST /appointments/{id}/tracking` y anuncia "Seguimiento registrado.". Sin `mode`, todo igual: la acción rápida "Registrar seguimiento" completa la siguiente cita y abre el diálogo "Completar cita" (CA9, A21). El 422 marca el campo bajo su control (CA8). Cancelar, agendar y ver detalle se reutilizan sin cambios | medio: lo usa la agenda; `agenda-cita-completar*` de ui-audit lo vigila |
| Interfaz · expedientes | `resources/js/pages/records/index.js`: con `?buscar=1`, el foco va al campo de búsqueda al cargar (acción "Buscar paciente", CA9) | bajo |
| Datos de prueba de la interfaz | `database/seeders/UiAuditSeeder.php`: el día del inicio en la prueba es el **jueves 17 de septiembre de 2026 a las 11:40** (D16), un mes que ninguna otra entrada mira. Ese día lleva las 8 citas de `design.md` de los dos doctores (3 completadas, 4 por atender, 1 cancelada; la siguiente a las 12:30). Con las asignadas de julio suman 15 sin cerrar anteriores al 17, y hay 6 completadas sin seguimiento (al menos 2 de cada doctor), para que `evaluate` mida lo que suponen K1–K4 (A2). `tests/Modules/Core/Integration/UiAuditSeederTest.php` se actualiza: citas de septiembre, sin seguimiento por doctor e idempotencia (A15), y fija las cifras que pinta el mes del administrador con la hora de D16 (citas de septiembre y de agosto, pacientes nuevos de cada mes y activos), las mismas que da `reports/monthly?month=2026-09` (A31) | medio: `UiAuditSeederTest` y las entradas que cuentan citas del paciente sembrado |
| Core · reloj de la prueba de navegador (nuevo) | `app/Core/Middlewares/UiAuditClock.php`: si la petición trae `X-Ui-Audit-Now` (fecha ISO 8601 válida) y el entorno es `local` o `testing`, fija `now()` a ese instante para esa petición; en otro entorno, o con un valor inválido, no hace nada. El entorno se comprueba **en cada petición** (la misma lista que `UiAuditSeeder::ALLOWED_ENVIRONMENTS`). Se registra en `bootstrap/app.php` en los grupos `web` y `api` **sin condición**: en la web la pila se arma antes de cargar el entorno, así que un registro condicionado al entorno pasaría los tests y fallaría en el servidor (A27). `tests/Browser/ui-audit.mjs` envía la cabecera en todas las peticiones de una entrada que declara `serverNow` (D16, A3) y, en las que declaran `snapshot`, guarda una copia de la página pintada para `evaluate` (D22, A32) | medio: código que solo existe para la prueba; TM9 |
| Core · sesión de la prueba de navegador | `app/Core/Console/UiAuditSessionCommand.php`: acepta el rol `inactivo` y entrega la sesión del staff inactivo sembrado (`UiAuditSeeder::STAFF_EMAILS['inactivo']`, estado `inactive`); el resto de cuentas sigue fuera (016 TM5). `UiAuditSessionCommandTest` pasa `inactivo` del dataset de rechazos al de roles con sesión, y el comando solo corre en `local` y `testing`, como hoy (A26) | bajo |
| Runner de la prueba de navegador | `tests/Browser/checks-dialogs.mjs` (y la cabecera de `ui-audit.mjs`): un flujo puede declarar `load: true` para que sus respuestas se apliquen desde la carga de la página, una lista `answers` con una respuesta por ruta y por petición sucesiva (`to`, `status`, `body`, `times`), `hold: true` para retener una respuesta, y la acción `wait` (milisegundos). Las entradas existentes no cambian (D21, A25) | medio: lo usan todas las entradas con flujos |
| Tests de navegador | `tests/Browser/screens.json`: `dashboard-administrador`, `-asistente` y `-doctor` siguen, con `serverNow` y `now` en el 17 de septiembre de 2026 a las 11:40, y con la línea base de 1440 px rehecha mediante una diferencia admitida (CA11 y c5, aprobada por el usuario el 2026-10-23) registrada en `tests/Browser/checks-composition.mjs` (A3). `dashboard-administrador` y `dashboard-doctor` declaran `snapshot` (D22). Nuevas: `dashboard-detalle-administrador` y `dashboard-detalle-asistente` (el detalle de la siguiente cita ofrece Reprogramar y abre su diálogo; A1, A33), `dashboard-completar` (diálogo de completar desde "Citas sin cerrar", doctor), `dashboard-cancelar` (confirmación, asistente), `dashboard-registrar-seguimiento` (diálogo en modo registro con su título y su confirmación, administrador), `dashboard-nueva-cita` (administrador), `dashboard-inactivo` (rol `inactivo` de la sesión de prueba: "Sin permiso"; A26) y `expedientes-buscar` (`/expedientes-clinicos?buscar=1`: el foco queda en el buscador; A4). Flujos en `dashboard-administrador` y `dashboard-doctor`, con `load: true` (D21): "vacío de cada sección" (respuesta vacía de cada lectura: su texto exacto de la spec), "carga" (respuesta retenida: la sección indica que carga), "error de una sección" (500 en `/agenda/unclosed`: su bloque con "Reintentar" sigue a la vista pasada la caducidad de 12 s de la región de estado, porque es persistente como el de `system.md`, se alcanza con Tab y las demás secciones tienen contenido; A22), "Reintentar" (primera respuesta 500 y la siguiente real: la sección muestra sus citas; A5), "mes del administrador" (los textos pintados son los exactos que dan los datos sembrados de septiembre frente a agosto de 2026, fijados en `UiAuditSeederTest`; A11, A31) y "nombre con HTML" (paciente `<img src=x onerror=…>` como texto). Sale `dashboard-crear-usuario`. `dashboard-crear-paciente` sigue | medio |
| Sistema de diseño | `docs/design/system.md` y `system.html` (primera tarea): piezas y regla de `design.md` → "Cambios a incorporar al sistema"; inventario del inicio de los tres roles; fuera la vista "Inicio del panel, nuevo usuario" | bajo |
| Documentación | `docs/architecture.md` (T090): endpoints nuevos de Appointments y AppointmentTracking. `docs/security.md`: puntos de entrada nuevos. `docs/observability.md`: puntos de emisión de la 020 | bajo |

## Contratos y datos
Sin migraciones ni cambios de esquema. Todas las rutas van bajo `/api/v1`, en el grupo
`auth:sanctum` + `staff`. Para todas: staff inactivo, paciente → 403; sin sesión → 401; error
inesperado → 500 `{"error": "Internal server error"}`. Un 403 por permiso o por pertenencia sale
del render de `AuthorizationException` con su cuerpo de siempre,
`{"error": "You are not allowed to perform this action (<permiso>)."}`. Que nombre el permiso
interno es 018/R8, que sigue en el roadmap y queda fuera de esta spec (A13).

Nombres: el contrato nuevo usa `doctor_name` para quien atiende la cita. Es el mismo dato que
`user_name` de `AppointmentResource`, renombrado porque el inicio no muestra "usuarios" (A23).

### `GET /agenda/today-summary`
| Actor | Respuesta |
|---|---|
| Administrador, asistente | 200, todas las citas de hoy |
| Doctor | 200, solo las suyas (un filtro que llegue se ignora) |

```json
{"data": {
  "date": "2026-10-22", "now": "11:40",
  "appointments": [{"id": "…", "date": "2026-10-22", "time": "09:00", "status": "completada",
    "patient_name": "Laura Prueba", "treatment_name": "Limpieza dental de prueba", "doctor_name": "Diego Prueba",
    "is_past": true, "is_next": false, "can": {"complete": false, "cancel": false}}],
  "counts": {"total": 8, "completed": 3, "pending": 4, "cancelled": 1},
  "next": {"id": "…", "time": "12:30", "status": "asignada", "patient_name": "Paula Prueba",
    "treatment_name": "Limpieza dental de prueba", "doctor_name": "Diego Prueba", "can": {"complete": true, "cancel": true}},
  "after": [{"time": "13:15", "patient_name": "Javier Prueba"}, {"time": "16:00", "patient_name": "Carmen Prueba"}],
  "after_more": 1
}}
```
- Sin citas: `appointments: []`, todos los contadores en 0, `next: null`, `after: []`.
- `completed + pending + cancelled = total` (CA3). `is_past`: hora < ahora.
- `can`: `complete` (administrador, o doctor y la cita es suya, y estado asignada o reprogramada) y
  `cancel` (administrador o asistente, estado asignada o reprogramada). El servidor lo vuelve a
  comprobar en cada operación (P13).

### `GET /agenda/unclosed`
Mismos actores y recorte que arriba. 200 `{"data": {"items": [<cita>], "total": 15}}`. `items`
tiene como mucho 10 citas, con fecha < hoy y estado asignada o reprogramada, de la más reciente a
la más antigua (fecha y hora). Cada cita lleva la forma de arriba más `day_label` ("Ayer ·
miércoles 21", "Martes 20"). `total` cuenta todas. Vacío: `items: []`, `total: 0`.

### `GET /agenda/without-tracking`
| Actor | Respuesta |
|---|---|
| Administrador | 200, todas |
| Doctor | 200, solo las suyas |
| Asistente | 403 |

200 `{"data": {"items": [<cita completada sin seguimiento>], "total": 6}}`, con la misma forma,
estado `completada` y `can: {"register": true}`. Ningún dato clínico.

### `POST /appointments/{id}/tracking`
Cuerpo igual que `POST /appointments/{id}/complete`: `reason`, `diagnosis` y
`procedure_performed` obligatorios; `symptoms[]` opcional; `observations` y `recommendations` opcionales;
`prescriptions[]` con `medication`, `dosage`, `durationDays` (1–365), `dailyFrequency` (1–24) e
`instructions` opcional.

| Caso | Respuesta |
|---|---|
| Administrador, o doctor con su cita, completada y sin seguimiento | 201 `{"message": "Seguimiento registrado.", "data": <AppointmentTrackingRecordResource>}`; la cita sigue `completada` |
| Dato obligatorio vacío o fuera de rango | 422 con `errors` por campo (A19): `reason` "El motivo de la consulta es obligatorio.", `symptoms` puede faltar o venir vacío, como al completar, donde es opcional (A30, decisión del usuario); cada elemento debe ser un texto no vacío: `symptoms.N` "Escribe el síntoma o quítalo.", `diagnosis` "El diagnóstico es obligatorio.", `procedure_performed` "El procedimiento realizado es obligatorio.", `prescriptions.N.medication` "Escribe el medicamento.", `prescriptions.N.dosage` "Escribe la dosis.", `prescriptions.N.durationDays` "Escribe una duración de 1 a 365 días.", `prescriptions.N.dailyFrequency` "Escribe de 1 a 24 veces al día." (los textos del diálogo de completar) |
| Cita inexistente | 404 `{"error": "No se encontró la cita."}` |
| Cita con seguimiento, también por doble envío | 409 `{"error": "Esta cita ya tiene seguimiento registrado."}` |
| Cita cancelada, asignada o reprogramada | 409 `{"error": "Solo se registra el seguimiento de una cita completada."}` |
| Doctor con una cita de otro doctor | 403, sin tocar nada |
| Asistente | 403 |

### `GET /reports/monthly?month=AAAA-MM` (sin cambios, 019)
El inicio lo pide solo como administrador, con el mes en curso que entrega la vista. Cuenta todo el
mes, como "Reportes" (CA10). Usa `appointments.total` y `patients.new` (con `previous`,
`difference` y `previous_short_label`), `patients.active` (con `as_of`) e `is_current`. Textos (A10):
"42 citas · +6 respecto a septiembre (36)", "5 pacientes nuevos · +2 respecto a septiembre (3)",
"128 pacientes activos · al 22 de octubre de 2026". La diferencia lleva `+` o `−` (U+2212), y "sin
cambio" cuando es 0.

### Pantalla `/dashboard`
| Actor | Resultado |
|---|---|
| Administrador, asistente, doctor | Inicio del staff (c5) |
| Paciente | Su inicio de siempre, con el título "Inicio - Dentissa" y la inicial completa (CA11, CA16) |
| Staff inactivo | Vista "Sin permiso" (403), como `/agenda` (CA16, A17); no pide datos |
| Sin sesión | Redirección a `/login` |

### Qué ve cada rol
| Pantalla · elemento | Administrador | Asistente | Doctor | Test |
|---|---|---|---|---|
| Título de la pestaña | "Inicio - Dentissa" | igual | igual | `DashboardScreenTest` |
| Encabezado | "¡Hola, Nombre!", fecha y "Nueva cita" | "¡Hola, Nombre!" y fecha; "Nueva cita" en acciones | "¡Hola, Nombre!" y fecha | `DashboardScreenTest` |
| Siguiente cita · acciones | "Completar", "Ver detalle" | "Ver detalle" | "Completar", "Ver detalle" | `DashboardScreenTest`, `TodaySummaryTest` (`can`) |
| Detalle de una cita abierta | "Reprogramar", "Cancelar cita", "Completar" | "Reprogramar", "Cancelar cita" | "Completar" (las suyas) | ui-audit `dashboard-detalle` (A1) |
| Agenda de hoy | todas | todas | las suyas | `TodaySummaryTest` |
| Citas sin cerrar · acciones | "Completar", "Cancelar cita" | "Cancelar cita" | "Completar" (las suyas) | `UnclosedAppointmentsTest` (`can`); ui-audit |
| Completadas sin seguimiento | sección con "Registrar seguimiento" | no existe en el HTML | sus citas | `DashboardScreenTest`, `CompletedWithoutTrackingTest` |
| Acciones rápidas | Nuevo paciente, Buscar paciente, Agenda de hoy, Registrar seguimiento | Nueva cita, Buscar paciente, Agenda de hoy | Buscar paciente, Agenda de hoy, Registrar seguimiento | `DashboardScreenTest` (dataset por rol) |
| Octubre hasta hoy | sí | no existe en el HTML | no existe en el HTML | `DashboardScreenTest` |
| "Estado: En línea", "Actividad reciente", "Nuevo usuario", tarjeta con inicial y correo | no | no | no | `DashboardScreenTest` |

"Registrar seguimiento" de las acciones rápidas abre el diálogo de completar de la siguiente cita.
Si no hay siguiente cita o el rol no puede completarla, no aparece (CA9).

Estados por sección: carga (región de estado "Cargando…"), vacío ("No hay citas para hoy." /
"No quedan citas por atender hoy." / "No hay citas pendientes de cerrar." / "Todas las citas
completadas tienen seguimiento.") y error ("No se pudo cargar esta sección." con "Reintentar",
bloque persistente; la región de estado lo anuncia una vez). Accesibilidad según `design.md` →
Accesibilidad. A menos de 1024 px, el orden es el de `design.md` sin cambiar el orden del DOM de
cada columna.

## Estrategia de pruebas
- **Unidad:**
  - `tests/Modules/Appointments/Unit/TodayAgendaTest.php` y `TodayAgendaPropertiesTest.php` (Eris):
    orden por hora; los contadores suman el total; la siguiente es la primera por atender con hora
    ≥ ahora; ninguna siguiente si todo pasó o está cerrado; `after` ≤ 2 y `after_more` correcto.
    Una cita a la misma hora que ahora cuenta como siguiente.
  - `AppointmentPageTest` y `AppointmentPagePropertiesTest`: `items` ≤ límite y `total` ≥ `items`.
  - `DoctorScopeTest`: administrador y asistente sin recorte, doctor con su id, el resto rechazado.
  - `DashboardUseCasesAuthorizationTest`: sin permiso, cada caso de uso lanza
    `AuthorizationException` sin llamar al repositorio.
  - `AppointmentsArchitectureTest`, `AppointmentTrackingArchitectureTest`.
- **Integración** (`tests/Modules/Appointments/Integration` y
  `tests/Modules/AppointmentTracking/Integration`, con sus `*IntegrationTestCase`, `ActingAsStaff`,
  `ActingAsPatient` y `fakeTwilio()` al crear citas con teléfono; `travelTo` fija la hora de la
  clínica):
  - `TodaySummaryTest`: forma del JSON; recorte del doctor con un `user_id` ajeno en la URL (CA1,
    CA13); la siguiente cita (CA2); los contadores (CA3); un día sin citas; `can` por rol.
  - `UnclosedAppointmentsTest`: solo días anteriores en asignada o reprogramada; las de hoy
    quedan fuera; 15 → 10 más el total; orden; `day_label`; recorte del doctor (CA4, CA13).
  - `CompletedWithoutTrackingTest`: solo completadas sin seguimiento; recorte del doctor;
    asistente 403 (CA7, CA14).
  - `RegisterAppointmentTrackingTest`: registro del administrador y del doctor en su cita, con
    recetas, y la cita sigue completada (CA8). Doctor en cita ajena → 403 sin guardar (CA13).
    Cita con seguimiento, cancelada, asignada o reprogramada → 409 con su texto; inexistente → 404
    (CA15). Doble envío → 409. Asistente → 403 (CA14).
  - `RegisterAppointmentTrackingValidationTest`: dataset de campos vacíos y rangos de recetas → 422
    con el mensaje de cada campo (CA8, CA13 de 009).
  - `DashboardFromInboxTest`: completar una cita sin cerrar con `POST /complete` (administrador y
    doctor) y cancelarla como asistente; después, la cita sale de `/agenda/unclosed` (CA5, CA6).
  - `DashboardAccessControlTest`: cada endpoint nuevo por actor (asistente donde aplica, doctor
    con cita ajena, inactivo, paciente y sin sesión) (CA13, CA14, CA16).
  - `DashboardNoClinicalDataTest`: con seguimiento y datos médicos conocidos, ninguna respuesta de
    las tres lecturas los contiene (TM6).
  - `DashboardPerformanceTest`: 50 citas hoy, 200 sin cerrar y 2 000 en el mes. Mide el tiempo de
    servidor de los cuatro endpoints que pide el inicio, uno tras otro y en caliente (se descarta una
    primera vuelta). La suma debe quedar por debajo de 2 s, y cada endpoint hace un número de consultas fijo que
    no crece con los datos (sin N+1). Debe ser estable en `pest --parallel` como lo corre CI, sin
    `skip`: T050 lo corre dos veces en paralelo (A28). Es el RNF "el inicio se muestra en menos
    de 2 s" sin el navegador (A12).
  - `DashboardMatchesReportsTest`: las cifras del mes que pinta el inicio salen de
    `reports/monthly` del mes en curso (CA10). Lo comprueba ui-audit sobre la pantalla; en
    integración, el mismo endpoint con el mes de la vista.
  - `UnexpectedDashboardErrorTest`: el repositorio lanza → 500 genérico en cada endpoint (P7).
  - `DashboardLogsTest` (A7): con `Log::spy()` o un canal de prueba, registrar un seguimiento,
    cancelar desde el inicio y forzar un 500 en cada lectura no deja en ningún log el nombre, el
    teléfono ni el email del paciente, ni el motivo, el diagnóstico o las recetas sembrados.
  - `UiAuditClockTest` (D16, TM9): en `testing`, la cabecera fija `now()` de la petición; un valor
    inválido se ignora; con el entorno cambiado a `production` en el test, la cabecera no tiene
    efecto; el middleware está en los grupos `web` y `api` y `/dashboard` responde sin error con
    él (A27).
  - `CompleteAppointmentTest` (A9): completar una cita cancelada o con seguimiento responde 409 con
    el texto en español y sin el id; las dos aserciones actuales en inglés se sustituyen (A29).
  - `tests/Modules/Core/Integration/DashboardScreenTest.php` (nuevo): HTML de `/dashboard` por rol
    (dataset de "Qué ve cada rol"), título "Inicio - Dentissa" para todos, un `h1`, sin "-" en
    contadores, sin las piezas retiradas, mes en curso en el atributo de datos, staff inactivo → 403
    con la vista "Sin permiso", y el paciente con su inicio y su inicial completa con acento
    ("Ángela" → "Á") (CA9, CA11, CA16). El foco de `?buscar=1` es de navegador: va a ui-audit.
  - Se actualizan: `RoleCopyTest` (la insignia "inicio" del encabezado desaparece; el rol sigue en
    el menú), `PageStructureTest` (título del inicio), `RolePermissionsTest` (sin cambios de
    permisos, se añade la fila de las lecturas nuevas), `CompleteAppointmentTest` (completar no
    cambia tras extraer `TrackingFactory`), `AgendaByRoleTest` (recorte tras extraer
    `DoctorScope`).
- **Navegador (`npm run test:ui`):** las entradas `dashboard-*` y `expedientes-buscar` a 390, 768 y
  1440 px con los datos de `ui:audit-data` y la hora fija de D16, la línea base de 1440 px rehecha
  por diferencia admitida y los flujos de "Tests de navegador" (vacío, carga, error, Reintentar,
  mes y nombre con HTML). Se comprueba que el resultado no depende de la hora real: dos corridas,
  a horas distintas, dan lo mismo (A3). `evaluate.mjs` con `design/criterios.json` y `criterios-doctor.json` sobre
  la copia de `dashboard-administrador` y `dashboard-doctor` que guarda ui-audit con la hora de D16
  (D22), en la tarea que construye la pantalla. Se ejecutan dos veces en
  `/implement`.
- **Regresión:** suites de Appointments, AppointmentTracking, Reports, Core y Users en serie.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation / Information disclosure: un doctor pide por la API las citas de hoy, las sin cerrar o las sin seguimiento de otro doctor (parámetros manipulados) | A01:2025 · API1:2023 (BOLA) | `GET /agenda/*` nuevos | Hoy y sin cerrar: `DoctorScope` (`appointments.view` / `view-own`). Sin seguimiento: `appointment-tracking.create` sin recorte y `create-own` con el id del doctor, de modo que el asistente queda fuera (A8). En los dos casos los parámetros de la petición se ignoran | `TodaySummaryTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest` (recorte con `user_id` ajeno) |
| TM2 | Elevation: un doctor completa o registra el seguimiento de una cita que no es suya | A01:2025 · API1:2023 | `POST /complete`, `POST /tracking` | Comprobación de pertenencia antes de leer o tocar el seguimiento | `RegisterAppointmentTrackingTest`, `DoctorTrackingTest` (018) |
| TM3 | Elevation: el asistente registra seguimiento, ve las sin seguimiento o los indicadores, o da de alta un paciente | A01:2025 · API5:2023 | `POST /tracking`, `GET /agenda/without-tracking`, `GET /reports/monthly`, `POST /patients` | `assertCan` con `appointment-tracking.create(-own)`, `only.admin` en reportes y pacientes | `DashboardAccessControlTest` |
| TM4 | Spoofing: paciente, staff inactivo o visitante sin sesión usan los endpoints del inicio | A01:2025 · A07:2025 | API | `auth:sanctum` + `staff` (activo). El guard aún acepta tokens de pacientes (RS1.c pendiente → spec 013); el control efectivo es `staff` | `DashboardAccessControlTest` |
| TM5 | Tampering: registrar un seguimiento en una cita cancelada, abierta o con seguimiento, o dos veces a la vez | A06:2025 (diseño inseguro) | `RegisterAppointmentTrackingUseCase` | Estado `completada` y sin seguimiento; restricción única convertida en 409 | `RegisterAppointmentTrackingTest` (doble envío incluido) |
| TM6 | Information disclosure: las listas del inicio sacan datos clínicos o de contacto | A01:2025 · LFPDPPP | Recursos de las tres lecturas | `DashboardAppointmentResource` con campos fijos, sin seguimiento ni datos del paciente salvo el nombre | `DashboardNoClinicalDataTest` |
| TM7 | Tampering / XSS almacenado: un nombre de paciente, tratamiento o doctor con HTML se ejecuta en el inicio | A05:2025 (inyección) | `dashboard.js` | Solo `textContent` y plantillas; nunca `innerHTML` | ui-audit flujo "nombre con HTML" |
| TM8 | Denial of service: listas sin límite con muchas citas vencidas | A06:2025 · API4:2023 | `GET /agenda/unclosed`, `/without-tracking` | Límite de 10 en el servidor y `count()` aparte; límite general `api` | `UnclosedAppointmentsTest` (15 → 10), `DashboardPerformanceTest` |
| TM9 | Tampering: alguien envía `X-Ui-Audit-Now` en producción para cambiar qué cuenta como "hoy" o "sin cerrar" | A02:2025 (configuración insegura) | `UiAuditClock` | El middleware comprueba el entorno en cada petición y solo actúa en `local` y `testing`; su registro no depende del entorno (A27) | `UiAuditClockTest` (entorno `production`: la cabecera no tiene efecto) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: agenda de hoy por hora con sus datos; todas o las del doctor; "No hay citas para hoy." | `TodayAgenda`; `GetTodaySummaryUseCase`; `DoctorScope`; pantalla y JS | `TodayAgendaTest`, `TodaySummaryTest`; ui-audit `dashboard-*` (flujo de vacío) · verificación manual: agenda de hoy a 390 y 1440 px contra `candidatos/c5.html` |
| CA2: siguiente cita (por atender, hora ≥ ahora), detalle con acciones del rol incluido Reprogramar; "No quedan citas por atender hoy." | `TodayAgenda`; `next.can`; `view-appointment-modal` reutilizado | `TodayAgendaPropertiesTest`, `TodaySummaryTest`; ui-audit `dashboard-administrador` (siguiente cita a la vista, K1; flujo de vacío) y `dashboard-detalle` (Reprogramar) |
| CA3: cuatro contadores que suman el total, recortados por rol | `TodayAgenda`; `day-progress` | `TodayAgendaPropertiesTest`, `TodaySummaryTest`; `evaluate.mjs` K2 |
| CA4: sin cerrar de días anteriores, hasta 10 más el total, por rol; vacío | `findUnclosedBefore`; `AppointmentPage`; `pending-group` | `UnclosedAppointmentsTest`, `AppointmentPagePropertiesTest`; ui-audit `dashboard-administrador` (flujo de vacío); `evaluate` K3 |
| CA5: "Completar" con el diálogo de la agenda; sale de la lista; asistente sin el botón | `can.complete`; `complete-appointment.js` reutilizado; `agendaPage.reload` | `DashboardFromInboxTest`, `UnclosedAppointmentsTest` (`can`); ui-audit `dashboard-completar` |
| CA6: "Cancelar cita" con confirmación; "Cita cancelada."; doctor sin el botón | `can.cancel`; `cancel-appointment.js` reutilizado | `DashboardFromInboxTest`; ui-audit `dashboard-cancelar` |
| CA7: completadas sin seguimiento, por rol, sin datos clínicos; vacío; asistente sin sección | `GetCompletedWithoutTrackingUseCase`; repositorio; pantalla | `CompletedWithoutTrackingTest`, `DashboardScreenTest`, `DashboardNoClinicalDataTest`; ui-audit (flujo de vacío) |
| CA8: "Registrar seguimiento" con los campos y reglas de completar; sigue completada; error junto al campo | `RegisterAppointmentTrackingUseCase`; `RegisterAppointmentTrackingRequest`; modo `register` del diálogo | `RegisterAppointmentTrackingTest`, `RegisterAppointmentTrackingValidationTest`; ui-audit `dashboard-registrar-seguimiento` (error de validación junto al campo) |
| CA9: acciones rápidas por rol y qué hace cada una | Pantalla; `?buscar=1` en expedientes | `DashboardScreenTest` (dataset); ui-audit `dashboard-nueva-cita`, `dashboard-crear-paciente`, `expedientes-buscar` · verificación manual: "Agenda de hoy" abre la agenda en hoy |
| CA10: mes en curso del administrador, todo el mes, igual que "Reportes", con aviso, texto y enlace | Pantalla y JS sobre `reports/monthly` | `DashboardMatchesReportsTest`, `DashboardScreenTest`; ui-audit flujo "mes del administrador" |
| CA11: sin "-", "Estado: En línea", "Actividad reciente", "Nuevo usuario" ni la tarjeta con inicial; título "Inicio - Dentissa"; inicial completa del paciente | Pantalla | `DashboardScreenTest`, `PageStructureTest`, `RoleCopyTest` |
| CA12: error por sección con "Reintentar"; las demás siguen; carga por sección | `dashboard.js` | ui-audit flujos "error de una sección", "Reintentar" y "carga" |
| CA13 (abuso): doctor solo recibe sus citas y no toca citas ajenas ni ve el mes | `DoctorScope`; pertenencia; `only.admin` | `TodaySummaryTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest`, `RegisterAppointmentTrackingTest`, `DashboardAccessControlTest` |
| CA14 (abuso): asistente rechazado al completar, registrar, ver sin seguimiento, ver el mes o crear pacientes | Permisos existentes | `DashboardAccessControlTest`, `CompletedWithoutTrackingTest` |
| CA15 (abuso): registrar en una cita con seguimiento, cancelada o abierta → mensaje en español, sin cambios; completar una cita con seguimiento o cancelada → mensaje en español sin id | `RegisterAppointmentTrackingUseCase`; `CompleteAppointmentUseCase`; `TrackingRegistrationException` | `RegisterAppointmentTrackingTest`, `CompleteAppointmentTest` |
| CA16 (abuso): paciente, inactivo o sin sesión rechazados; el staff inactivo ve "Sin permiso"; el paciente ve su inicio | Rutas `staff`; `ShowDashboardPageController`; vista | `DashboardAccessControlTest`, `DashboardScreenTest`; ui-audit `dashboard-inactivo` |
| TM1: doctor pide citas de otro doctor | `DoctorScope` | `TodaySummaryTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest` |
| TM2: doctor completa o registra en cita ajena | Pertenencia | `RegisterAppointmentTrackingTest`, `DoctorTrackingTest` |
| TM3: asistente fuera de sus permisos | `assertCan`, `only.admin` | `DashboardAccessControlTest` |
| TM4: paciente, inactivo o sin sesión | `auth:sanctum` + `staff` | `DashboardAccessControlTest` |
| TM5: registro en cita no completada, con seguimiento o doble envío | `RegisterAppointmentTrackingUseCase`; restricción única → 409 | `RegisterAppointmentTrackingTest` |
| TM6: datos clínicos en las listas | `DashboardAppointmentResource` | `DashboardNoClinicalDataTest` |
| TM7: HTML en nombres | `dashboard.js` | ui-audit flujo "nombre con HTML" |
| TM8: listas sin límite | Límite 10 + `count()` | `UnclosedAppointmentsTest`, `DashboardPerformanceTest` |
| TM9: hora falsa en producción | `UiAuditClock` comprueba el entorno en cada petición | `UiAuditClockTest` |
| RNF: < 2 s con 50 hoy, 200 sin cerrar y 2 000 en el mes | Consultas con límite; reutiliza el reporte | `DashboardPerformanceTest` |
| RNF: cálculos en el servidor; el mes sale del cálculo de Reportes | `TodayAgenda`; `reports/monthly` | `TodaySummaryTest`, `DashboardMatchesReportsTest` |
| RNF: permisos en el servidor, denegado por actor | Casos de uso y rutas | `DashboardAccessControlTest`, `DashboardUseCasesAuthorizationTest` |
| RNF: navegador a 390, 768 y 1440 px, contraste, foco, teclado y listas en orden | Pantalla | `npm run test:ui`; `evaluate.mjs` · verificación manual: recorrido con NVDA del inicio del doctor |
| RNF: errores sin detalles internos y logs sin datos de pacientes | Controladores | `UnexpectedDashboardErrorTest`, `DashboardLogsTest` |

## Observabilidad
- Logs nuevos: ninguno. Los errores inesperados quedan en el log saneado de
  `UnexpectedErrorResponse`, como en toda la API.
- Eventos de auditoría: P14 ❌ aceptado (sin registro ni `request_id`, objetivo 5). Puntos de
  emisión para cuando exista el registro: escritura de datos de salud en
  `RegisterAppointmentTrackingUseCase` y `CompleteAppointmentUseCase` (CA5, CA8); cancelación en
  `UpdateAppointmentUseCase` (CA6); accesos denegados en el render de `AuthorizationException` y
  en `EnsureActiveStaff` (CA13, CA14, CA16). Las lecturas del inicio no son lecturas de datos de
  salud.
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: entrar al inicio como administrador y como doctor. Las secciones
  cargan, la siguiente cita es la correcta y el mes coincide con `/reportes`. En Loki, sin 500 en
  `/api/v1/agenda/*` ni en `/api/v1/appointments/*/tracking`.

## Descubribilidad
No aplica: `/dashboard` es una ruta privada del panel; la spec no crea ni cambia rutas públicas.

## Rollout
- Feature flag: no aplica (D6). Cambia la pantalla de entrada, pero no cambia ningún flujo de
  datos existente: completar, cancelar y agendar son los de la agenda.
- Orden de despliegue: una sola imagen con `deploy.sh`; sin migraciones.
- Compatibilidad: los endpoints existentes no cambian (`POST /complete` igual); la versión anterior
  no usa las rutas nuevas.
- Rollback: `docker/prod/rollback.sh` a la versión anterior. Los seguimientos registrados desde el
  inicio quedan en `appointment_tracking` con la misma forma que los de completar; la versión
  anterior los lee sin cambios.
- Métricas a vigilar tras el deploy: 500 y 409 en `/api/v1/agenda/*` y `/api/v1/appointments/*/tracking` en Loki.

## Decisiones (→ ADR si son arquitectónicas)
- **D1** Un endpoint por sección en lugar de uno solo para todo el inicio: cada sección falla y se
  reintenta por separado (CA12) y cada una lleva su permiso.
- **D2** Sin módulo nuevo: las lecturas de citas van en Appointments, las de seguimiento en
  AppointmentTracking (que ya depende de Appointments) y el mes reutiliza Reports. Sin ADR porque
  no hay dependencia nueva entre módulos.
- **D3** "Registrar seguimiento" es una operación nueva (`POST /appointments/{id}/tracking`) y no
  reutiliza `POST /complete`. Completar acepta una cita abierta y la cierra. CA15 pide rechazar el
  registro en una cita que no está completada, y P6 pide un FormRequest para una entrada nueva.
- **D4** El mes del administrador reutiliza `GET /reports/monthly` con el mes en curso: mismos
  números que "Reportes" sin duplicar cálculos (CA10, Notas de la spec). Es más de lo que el inicio
  pinta. Que la 019 lo mida en menos de 3 s no asegura los 2 s: lo comprueba
  `DashboardPerformanceTest` (D7, A12).
- **D5** "Sin cerrar" cuenta por día, como en la 019 (D7 de su plan): una cita de hoy cuya hora ya
  pasó sigue en la agenda de hoy, no en "Citas sin cerrar".
- **D6** Sin feature flag (ver Rollout).
- **D7** RNF de 2 s medido sobre los cuatro endpoints juntos en `DashboardPerformanceTest`. Si
  `reports/monthly` lo rompe, se sustituye por una lectura acotada en Reports que use los mismos
  métodos del repositorio (sin cambiar el contrato de la pantalla).
- **D8** P14 ❌ aceptado; el objetivo 5 va como la próxima spec (decisión del usuario,
  2026-10-23).
- **D9** CA11 ajustado a `design.md` (c5): el inicio ya no muestra la tarjeta con inicial, correo y
  rol (decisión del usuario, 2026-10-23, `/specify --edit`).
- **D10** La spec es grande (16 criterios) y se mantiene junta (decisión del usuario, 2026-10-22).
- **D11** El recorte del doctor se extrae a `DoctorScope` y lo usan los dos casos de uso de la 018
  y los dos nuevos. Cierra 018/R5 sin cambiar su comportamiento: lo fija `AgendaByRoleTest`.
- **D12** La construcción del seguimiento y las recetas se extrae de `CompleteAppointmentUseCase`
  a `TrackingFactory` para registrar y completar con las mismas reglas. Incluye el fix
  `Instructions::fromNullable` de v0.5.1.
- **D13** Las acciones por cita (`can`) las calcula el servidor; la pantalla no deduce permisos del
  rol (P13, y evita repetir 018/R6).
- **D14** Sustituida por D16 (A3): los datos de `UiAuditSeeder` ya no son relativos a la hora del
  sembrado.
- **D16** Hora fija en la prueba de navegador (A2, A3): en `local` y `testing`, `UiAuditClock` toma
  `now()` de la cabecera `X-Ui-Audit-Now` que envía `ui-audit.mjs` en las entradas con
  `serverNow`. El navegador ya movía su reloj con `now`; así servidor y navegador ven el mismo
  jueves 17 de septiembre de 2026 a las 11:40, y el seeder siembra ese día fijo. Se descartan las
  citas relativas a la hora real, porque la composición cambiaría con la hora, y una fecha elegida
  por el cliente en producción (TM9). El middleware vive en `app/Core` porque tiene que correr
  dentro de la aplicación.
- **D17** El detalle abierto desde el inicio ofrece Reprogramar con el diálogo de la agenda (A1;
  decisión del usuario, 2026-10-23; CA2).
- **D18** Completar responde en español y sin id al rechazar una cita cancelada o con seguimiento
  (A9; decisión del usuario, 2026-10-23; CA15). El resto de 018/R8 (403 en inglés que nombra el
  permiso) sigue en el roadmap (A13).
- **D19** El staff inactivo recibe la vista "Sin permiso" en `/dashboard` (A17; decisión del
  usuario, 2026-10-23).
- **D20** Excepción de P3 para `ShowDashboardPageController` (A6; decisión del usuario,
  2026-10-23).
- **D21** Los flujos de ui-audit pueden responder desde la carga de la página, en secuencia y
  reteniendo una respuesta, y esperar (A25). Sin eso, los estados por sección del inicio, que
  cargan al abrir la página, no se pueden probar en el navegador.
- **D22** `evaluate.mjs` no inicia sesión ni envía cabeceras, así que no puede medir `/dashboard`
  con la hora de D16 (A32). ui-audit guarda, en las entradas con `snapshot`, la página pintada
  (DOM con los estilos de la build en línea, sin scripts) a cada ancho, y `evaluate` mide esa
  copia. Se descarta sembrar datos que den K1–K4 con cualquier fecha, porque deshace D16.
- **D23** Registrar usa las mismas reglas que completar: los síntomas son opcionales (A30;
  decisión del usuario, 2026-10-24).
- **D24** El umbral de 2 s se comprueba en `pest --parallel`, como en CI, junto con un número de
  consultas fijo por endpoint, que no depende de la carga de la máquina (A28).
- **D15** Los problemas abiertos de la evaluación del diseño (`design.md` → Resultado: "·" inicial a
  390 px, "Registrar seguimiento" en dos líneas y filas de sin cerrar a 390 px) se corrigen en la
  tarea que construye la pantalla, sin cambiar criterios.

## Impacto en arquitectura
- `docs/architecture.md`: endpoints nuevos de Appointments (`/agenda/today-summary`,
  `/agenda/unclosed`) y AppointmentTracking (`/agenda/without-tracking`,
  `/appointments/{id}/tracking`); el inicio consume además `reports/monthly`.
- `docs/security.md`: puntos de entrada nuevos con su permiso y el reloj de prueba (solo local y
  testing).
- `docs/observability.md`: puntos de emisión de la 020 para el objetivo 5.
- `docs/roadmap.md`: cierra "Inicio del panel: los contadores muestran '-'", 016/R42, 016/R43 y
  018/R5.

## Riesgos y mitigaciones
- **Completar se rompe al extraer `TrackingFactory`** → `CompleteAppointmentTest` y
  `DoctorTrackingTest` en verde antes y después, y el fix de v0.5.1 cubierto.
- **El diálogo de completar cambia para la agenda** → el modo `register` es opcional; las entradas
  `agenda-cita-completar*` de ui-audit siguen igual.
- **`reports/monthly` lento con meses grandes** → `DashboardPerformanceTest`; plan B en D7.
- **Datos de "hoy" inestables en ui-audit** (la hora de CI cambia) → hora fija del servidor y del
  navegador en las entradas del inicio (D16); dos corridas a horas distintas dan lo mismo.
- **La hora fija llega a producción** → TM9: el middleware comprueba el entorno en cada petición y
  solo actúa en `local` y `testing`; `UiAuditClockTest` con el entorno en `production`.
- **CSRF en la operación nueva que cambia estado (RS5.a, pendiente)** → `POST
  /appointments/{id}/tracking` depende, como el resto de la API, de `SameSite=lax`. RS5.a queda fuera
  de esta spec, con su destino en el roadmap ("Seguridad sin objetivo asignado"); ninguna tarea la
  da por mitigada (A14).
- **El recorte del doctor cambia de sitio** (`DoctorScope`) → `AgendaByRoleTest` y
  `TodaySummaryTest` con un `user_id` ajeno.
- **Guard `sanctum` sin provider fijado (RS1.c, pendiente → spec 013)** → `staff` exige un
  `UserModel` activo; esta spec no mitiga RS1.c.
- **Sin auditoría de la escritura de datos de salud** (OB1.a) → P14 aceptado y el objetivo 5 como
  la próxima spec.
- **Pest en paralelo con timeouts de PostgreSQL** (017/R12) → la regresión se corre en serie.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-23 | Creación en `draft` | /plan 020 |
| 2026-10-23 | Aprobado | Aprobación del usuario |
| 2026-10-23 | Reprogramar en el detalle, completar en español, staff inactivo, paciente, mes como Reportes con sus textos, excepción de P3, hora fija de la prueba de navegador (D16, TM9), seeder de septiembre, pruebas de navegador de estados, buscador y mes, test de logs, tests de arquitectura completos, 403 por el punto único, textos del 422, RS5.a fuera, rendimiento definido; vuelve a `draft` | /plan --fix A1–A24 |
