---
spec: 018-agenda-por-rol
status: approved   # reaprobado por el usuario, 2026-10-17, tras /plan --fix (analysis ronda 1)
constitution_version: 1.2.0
created: 2026-10-17
---

# Plan · 018 Agenda por rol

## Enfoque técnico
Todo el cambio de permisos vive en el mapa de `CurrentActorAuthorizationService` (la fuente única
de la spec 014). Se añaden dos tipos de permiso: los que ya existían, que ganan al asistente, y
permisos "propios" (`*-own`) del doctor, que solo valen sobre citas que tiene asignadas. Para
comprobar "propio", la interfaz de autorización gana dos métodos: `can()` (sin lanzar) y
`currentActorId()`. Los casos de uso deciden el alcance: con el permiso general no hay recorte; con
el propio, la consulta filtra por el doctor en el servidor o la operación compara el `user_id` de la
cita con el actor. Las rutas de completar y de leer el seguimiento salen de `only.admin` y pasan al
grupo `staff`, porque la decisión por recurso la toma el caso de uso. En la interfaz, la agenda y
el detalle de la cita reciben capacidades por rol (gestionar, completar, alcance del registro
clínico) en lugar de un `isAdmin`, y ocultan lo que el rol no puede usar. No hay migraciones ni
dependencias nuevas.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 018 `approved` y `design.md` `approved` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene un test de integración que hoy falla (403 donde la spec pide acceso, o acceso donde pide recorte). Lo visible por rol se prueba con `npm run test:ui` (entradas nuevas de asistente y doctor). Sin value objects nuevos o modificados: no hay tests de propiedades nuevos | test: Trazabilidad; `npm run test:ui` |
| P3 Capas del módulo | ✅ | Los casos de uso usan `AuthorizationServiceInterface` (Core) como hoy. `Domain/` solo cambia la firma de `AppointmentsRepositoryInterface::findAllByStatusAndDateOrPatientId()` con un value object propio. `GetAppointmentTrackingByAppointmentIdUseCase` pasa a usar `AppointmentsService`, dependencia AppointmentTracking → Appointments que ya existe en `CompleteAppointmentUseCase`. El selector de doctores de la agenda pasa de un caso de uso de Users a `UserService` de Users desde un caso de uso de Appointments: la dependencia Appointments → Users ya existe (`GetDoctorsForAppointmentSelectController` y el `UserId` de la cita). Ninguna es nueva entre módulos ni pide ADR | manual: `/review` busca `Infrastructure` e `Illuminate` en `Domain/` tocado |
| P4 Contrato de API primero | ✅ | Contrato por endpoint en "Contratos y datos". Compatible: el administrador recibe lo mismo que hoy; solo se admiten más actores y, para el doctor, la respuesta se recorta | test: `AgendaByRoleTest`, `DoctorTrackingTest`, `AppointmentsAccessControlTest` fijan código y cuerpo por actor |
| P5 Autorización en el servidor | ✅ | Middleware `staff` + `assertCan`/`can` en cada caso de uso + comprobación de pertenencia (`user_id` de la cita = actor) en completar y leer el seguimiento, y recorte de consultas para el doctor. Test de acceso denegado por cada actor no autorizado de cada fila de la tabla de permisos | test: `AppointmentsAccessControlTest`, `DoctorTrackingTest`, `RolePermissionsTest` |
| P6 Validación con FormRequest | ➖ | La entrada de ningún endpoint cambia: cambian los actores admitidos. `GET /appointments` ignora cualquier parámetro de doctor; no se añade uno | — |
| P7 Errores sin detalles internos | ✅ | Los 500 siguen en `UnexpectedErrorResponse`. Los 403 nuevos usan `AuthorizationException::forbidden()`, cuyo texto nombra el permiso y no lleva datos del paciente ni de la cita | test: `DoctorTrackingTest` afirma el cuerpo exacto del 403 sin datos clínicos |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración ni variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones: `appointments.user_id` ya existe como clave foránea. No tiene índice propio (`2026_08_24_120000` solo indexa `(date, status)` y `status`), pero el filtro del doctor siempre va junto a la fecha o al estado y el volumen es de prototipo; un índice queda para cuando haya datos reales | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna dependencia nueva | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | El doctor pasa a leer y escribir datos de salud de sus citas y el asistente a ver citas: modelo de amenazas TM1–TM7. Ningún log nuevo | test: TM1–TM7; manual: `/review` de `Log::` tocados |
| P12 Producción con aprobación humana | ✅ | Release con `/release` y aprobación del dueño. Línea base de dependencias el 2026-10-17: `composer audit` y `npm audit --audit-level=high` sin hallazgos | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Permisos, pertenencia y recorte en el servidor. La interfaz oculta botones y secciones solo como presentación | test: integración de CA4, CA7, CA8, CA10, CA13 sin pasar por la interfaz |
| P14 Trazabilidad y auditoría | ❌ aceptado: la spec define eventos de auditoría (lectura y escritura de datos de salud por el doctor, cambios de cita del asistente, accesos denegados) que no se emiten porque el registro de auditoría aún no existe (objetivo 5 del roadmap) — aprobado por el usuario el 2026-10-17 | Misma desviación aceptada en las specs 014, 016 y 017. Los puntos de emisión quedan identificados en "Observabilidad" | — |
| P15 Solo el sistema de diseño | ✅ | Solo componentes y tokens existentes; el diseño no trae valores nuevos. T001 actualiza el inventario de `system.md` | test: `OnlyDesignTokensTest`, `DesignTokensTest`, `npm run test:ui` |

Resultado: 9 ✅ · 5 ➖ · 1 ❌ aceptado (P14).

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Core · Autorización | `app/Core/Authorization/AuthorizationServiceInterface.php`: añade `can(string $permission): bool` (falso para no autenticado, paciente, inactivo o rol sin el permiso; nunca lanza) y `currentActorId(): string` (lanza `unauthenticated()` sin actor de staff) | medio: interfaz usada por todos los casos de uso |
| Core · Autorización | `CurrentActorAuthorizationService.php`: implementa ambos métodos (`assertCan()` pasa a apoyarse en la misma comprobación). Mapa: `appointments.view`, `appointments.create`, `appointments.update` y `agenda.selectors.view` (los tres selectores de agendar) → administrador y asistente; `users.view` y `treatments.view` no cambian (solo administrador); nuevos `appointments.view-own` → doctor, `appointments.mark-completed` → administrador, `appointment-tracking.create-own` y `appointment-tracking.view-own` → doctor. Sin cambios: `appointments.delete`, `appointment-tracking.create`, `.view`, `.update`, `.prescriptions.*` (administrador) | medio: un error aquí abre o cierra de más; lo cubre `RolePermissionsTest` |
| Appointments · Domain | `Domain/Repositories/AppointmentsRepositoryInterface.php`: `findAllByStatusAndDateOrPatientId(?AppointmentStatus, ?AppointmentDate, ?PatientId = null, ?AppointmentUserId $userId = null)`. `Domain/Service/AppointmentsService.php::findByStatusAndDate()` pasa el parámetro | bajo |
| Appointments · Infrastructure | `Persistence/Eloquent/EloquentAppointmentRepository.php`: `where('user_id', …)` cuando llega `$userId` | bajo |
| Appointments · Aplication | `UseCases/GetAllApointmentsByStatusAndDateUseCase.php` y `GetTodayAppointmentsUseCase.php`: `can('appointments.view')` → sin recorte; si no, `can('appointments.view-own')` → recorte por `currentActorId()`; si no, `assertCan('appointments.view')` (lanza). El alcance sale siempre del actor, nunca de la petición | medio: el recorte es el control de CA8 |
| Appointments · Aplication | `UseCases/GetAllApointmentsByStatusAndDateUseCase.php`: un listado vacío devuelve lista vacía en lugar de lanzar `notFoundByFilters()` (D10); se retira esa fábrica de `Domain/Exceptions/AppointmentException.php` si queda sin uso | bajo: cambia la respuesta vacía para todos los roles |
| Appointments · Aplication | `UseCases/GetAgendaDoctorsUseCase.php` (nuevo): `assertCan('agenda.selectors.view')` y devuelve los usuarios activos con rol doctor o administrador vía `UserService::findByRoleAndStatus()`, sin duplicados (la misma lista de hoy). `UseCases/GetAgendaTreatmentsUseCase.php` (nuevo): `assertCan('agenda.selectors.view')` y devuelve el catálogo vía `TreatmentsService` (D11) | medio: el asistente no debe ganar `users.view` ni `treatments.view` |
| Appointments · Infrastructure | `Http/Controllers/GetDoctorsForAppointmentSelectController.php` y `GetTreatmentsController.php` usan los casos de uso nuevos; la respuesta (`UserResource`, `TreatmentResource`) no cambia | bajo |
| Appointments · Aplication | `UseCases/UpdateAppointmentUseCase.php::updateStatus()`: si el estado pedido es `completada`, `assertCan('appointments.mark-completed')` antes de `complete()` (D1). Cancelar sigue con `appointments.update` | bajo |
| AppointmentTracking · Application | `UseCases/CompleteAppointmentUseCase.php`: sin `create` ni `create-own` → `assertCan('appointment-tracking.create')` (lanza). Tras cargar la cita (409 si no existe, como hoy, D12), con solo `create-own` exige `UserId()` = `currentActorId()`; si no, `AuthorizationException::forbidden('appointment-tracking.create-own')`. El resto del flujo no cambia | medio: datos de salud |
| AppointmentTracking · Application | `UseCases/GetAppointmentTrackingByAppointmentIdUseCase.php`: misma regla con `view`/`view-own`; inyecta `AppointmentsService` para cargar la cita (si no existe, `null` → 404 como hoy) | medio: datos de salud |
| Rutas | `routes/api.php`: `POST /appointments/{id}/complete` y `GET /appointments/{id}/tracking` pasan del grupo `only.admin` al grupo `auth:sanctum` + `staff`. `appointment-tracking/*` se queda en `only.admin` | medio: cambia el middleware de rutas con datos de salud |
| Rutas web | `routes/web.php`: `/agenda` pasa del grupo `only.admin` al de `staff:administrador,asistente,doctor` | bajo |
| Interfaz · menú | `resources/views/components/ui/sidebar.blade.php`: `'agenda'` en las pestañas de asistente y doctor, tras `'inicio'` | bajo |
| Interfaz · agenda | `resources/views/pages/agenda/index.blade.php`: `$isAdmin` → `$canManageAgenda` (administrador, asistente), `$canCompleteAppointments` (administrador, doctor), `$isDoctor`. Botón "Agendar nueva cita", `create-appointment-modal`, `create-appointment.js` y `cancel-appointment.js` solo con `$canManageAgenda`; `complete-appointment-modal` y `complete-appointment.js` con `$canCompleteAppointments`. Descripción del encabezado del doctor "Tus citas asignadas."; atributos `data-agenda-can-complete` y `data-agenda-empty-today` ("No tienes citas para hoy." para el doctor) | medio: la vista hoy asume administrador |
| Interfaz · agenda | `resources/views/components/calendar/create-appointment-modal.blade.php` y `resources/js/pages/agenda/create-appointment.js`: el prop `isAdmin` y `data-create-appointment-is-admin` pasan a `withSelectors` / `data-create-appointment-with-selectors`, que la agenda pone a `true` para administrador y asistente: los selectores de paciente y doctor se muestran y se cargan para los dos | medio: archivo grande compartido con reprogramar |
| Interfaz · agenda | `resources/js/pages/agenda/index.js`: `isAdminUser` → `data-agenda-can-complete` decide el botón "Completar" de la tarjeta; el vacío de "Citas para hoy" toma el texto de `data-agenda-empty-today` | bajo |
| Interfaz · detalle | `resources/views/components/calendar/view-appointment-modal.blade.php`: prop `isAdmin` → `trackingScope` (`all`, `own`, `none`) y `actorId`. `resources/js/pages/agenda/view-appointment.js`: muestra "Registro clínico" si la cita está completada y el alcance es `all`, o `own` con `user_id` de la cita = `actorId`; texto vacío "Esta cita no tiene seguimiento registrado." (antes "Esta cita no tiene registro clínico."). Los botones del pie ya dependen de que su módulo esté cargado (017) | medio: componente compartido con el expediente |
| Interfaz · expediente | `resources/views/pages/records/index.blade.php`: pasa `trackingScope` (`all` administrador, `own` doctor, `none` asistente) y `actorId` al detalle de la cita (D5) | bajo |
| Datos de prueba de la interfaz | `database/seeders/UiAuditSeeder.php` (lo ejecuta `php artisan ui:audit-data`): un segundo doctor de prueba con citas en el mismo mes y el mismo día que el doctor de prueba, y para el doctor de prueba una cita completada con seguimiento y recetas y otra completada sin seguimiento; el día sin citas del doctor sale del `now` por entrada de `screens.json` (como `agenda-hoy-sin-citas`) | bajo |
| Tests de navegador | `tests/Browser/screens.json`: entradas de agenda y detalle como asistente y como doctor (tabla "Pantallas" de `design.md`); `error-403-admin` pasa de `/agenda` a `/usuarios` | bajo |
| Sistema de diseño | `docs/design/system.md` (inventario de vistas) y `system.html`: filas nuevas con rol Asistente y Doctor; "Acceso denegado (asistente en ruta de administrador)" pasa a `/usuarios` | bajo |

## Contratos y datos
Sin migraciones ni cambios de esquema. Respuestas y formatos iguales a los de hoy; cambian los
actores admitidos y, para el doctor, el contenido de los listados.

| Endpoint | Administrador | Asistente | Doctor | Paciente / inactivo |
|---|---|---|---|---|
| `GET /api/v1/appointments?status&date` | 200 todas; 200 `{"data": []}` si no hay ninguna (antes 409, D10) | 200 todas; 200 vacía | 200 solo sus citas; 200 vacía si no tiene; cualquier otro parámetro se ignora | 403 |
| `GET /api/v1/agenda/today-appointments` | 200 todas | 200 todas | 200 solo sus citas | 403 |
| `GET /api/v1/agenda/patients`, `/doctors`, `/treatments` | 200 | 200 (todos con `agenda.selectors.view`, D11) | 403 | 403 |
| `GET /api/v1/users`, `GET /api/v1/treatments` (gestión) | sin cambios | 403 como hoy | 403 | 403 |
| `POST /api/v1/appointments` | 201 | 201 (mismas reglas: 409 choque, 422 pasado) | 403 | 403 |
| `PUT /api/v1/appointments/{id}` reprogramar o `status: cancelada` | 200 | 200 (mismas reglas de 017) | 403 | 403 |
| `PUT /api/v1/appointments/{id}` `status: completada` | 200 (como hoy) | 403 | 403 | 403 |
| `DELETE /api/v1/appointments/{id}` | 200 | 403 | 403 | 403 |
| `GET /api/v1/appointments/{id}` (detalle) | 200 | 200 | 200 (sin cambios, 014) | 403 |
| `POST /api/v1/appointments/{id}/complete` | 201 (como hoy) · 409 si no existe (como hoy) | 403 | 201 si es suya · 403 si es de otro doctor · 409 si no existe (D12) | 403 |
| `GET /api/v1/appointments/{id}/tracking` | 200 · 404 sin seguimiento | 403 | 200 · 404 sin seguimiento, si es suya · 403 si es de otro doctor | 403 (paciente, inactivo); 401 sin sesión |
| `PUT /appointment-tracking/{id}`, `/prescriptions*` | sin cambios | 403 | 403 | 403 |

Cuerpo de los 403 nuevos: `{"error": "You are not allowed to perform this action (<permiso>)."}`,
el formato de `AuthorizationException` que ya usan estos controladores. En rutas web, `/agenda`
con un rol no admitido o un paciente sigue mostrando la vista "Sin permiso" (016).

### Qué ve cada rol
| Pantalla · elemento | Administrador | Asistente | Doctor | Test |
|---|---|---|---|---|
| Menú · "Agenda" | sí | sí | sí | `StaffNavigationTest` |
| Agenda · descripción del encabezado | la de hoy | la de hoy | "Tus citas asignadas." | `AgendaScreenByRoleTest` |
| Agenda · "Agendar nueva cita" | sí | sí | no | `AgendaScreenByRoleTest` |
| Agendar cita · selectores de paciente y doctor | sí | sí | — (sin diálogo) | `AgendaScreenByRoleTest` · ui-audit de agendar como asistente |
| Agenda · calendario, filtros y "Citas para hoy" | todas | todas | solo las suyas | `AgendaByRoleTest` (API) · ui-audit `agenda-doctor` |
| Agenda · "Citas para hoy" vacío | texto de hoy | texto de hoy | "No tienes citas para hoy." | `AgendaScreenByRoleTest` · ui-audit `agenda-doctor-hoy-sin-citas` |
| Tarjeta de cita pendiente · "Completar" | sí | no | sí (solo recibe las suyas) | ui-audit `agenda-asistente`, `agenda-doctor` |
| Detalle pendiente · "Cancelar cita", "Reprogramar" | sí | sí | no | ui-audit `agenda-cita-detalle-pendiente-asistente`, `-doctor` |
| Detalle completada · "Registro clínico" | sí | no | solo en sus citas | ui-audit `agenda-cita-detalle-doctor`, `agenda-cita-detalle-asistente` |
| Expediente · detalle de cita completada · "Registro clínico" | sí | no | solo en sus citas | `RecordsScreenTest` (atributos) |

Revisar con el plugin `design` (`design:design-handoff`) antes de `/implement`.

## Estrategia de pruebas
- **Integración (Pest, `tests/Modules/…/Integration`)**, con `ActingAsStaff` y `fakeTwilio()` en
  todo test que cree citas con teléfono:
  - `tests/Modules/Appointments/Integration/AgendaByRoleTest.php` (nuevo): asistente lista,
    agenda, reprograma y cancela (CA1–CA3); doctor recibe solo sus citas en ambos listados,
    también enviando `user_id` u otros filtros, y una lista vacía (200) si no tiene (CA5, CA8);
    administrador sin cambios salvo el listado vacío, que pasa de 409 a 200 (CA11, D10).
  - `AppointmentsAccessControlTest.php` (se actualiza): tabla de la sección "Contratos" completa
    por actor, incluido asistente con `status: completada` y `DELETE`, y doctor en cada escritura
    con cuerpos válidos e inválidos (CA4, CA7, CA11). Su tabla de rutas incorpora
    `POST /appointments/{id}/complete` y `GET /appointments/{id}/tracking`, que dejan `only.admin`;
    los casos "anyone but the administrator" y "citas del día y selector de doctores" cambian de
    expectativa; el asistente sigue con 403 en `GET /users` y `GET /treatments` (D11).
  - `tests/Modules/AppointmentTracking/Integration/DoctorTrackingTest.php` (nuevo): doctor completa
    su cita con seguimiento y recetas (CA12), lee el seguimiento de la suya y recibe 404 si no hay
    (CA9), recibe 403 al completar o leer la de otro doctor y al editar seguimiento o recetas
    (CA10, CA13), y el asistente recibe 403 en completar y leer (CA4). Paciente y staff inactivo
    reciben 403 y el visitante sin sesión 401 en `GET /tracking`, con el cuerpo exacto (TM7, P5).
    Comprueba que tras cada rechazo la cita y el seguimiento no cambian.
  - `tests/Modules/Users/Integration/RolePermissionsTest.php`: dataset con los permisos nuevos y
    los que cambian.
  - `tests/Modules/Appointments/Integration/AgendaScreenByRoleTest.php` (nuevo): `GET /agenda` por
    rol (200 para los tres, 403 vista para paciente) y presencia o ausencia en el HTML del botón de
    agendar, de los selectores de paciente y doctor (`data-create-appointment-with-selectors`), de
    los scripts y diálogos por capacidad, la descripción y el texto vacío del doctor.
  - Se actualizan: `StaffNavigationTest` (agenda para asistente y doctor),
    `WebAccessDeniedPagesTest` (`/agenda` fuera de `DENIED_ADMIN_SCREENS`; sigue denegada al
    paciente), `GetAppointmentsTest` (selectores abiertos al asistente), `CompleteAppointmentTest`
    (403 de staff no administrador → solo asistente), `RecordsScreenTest`.
- **Unidad:** `CurrentActorAuthorizationService::can()` y `currentActorId()` sin actor, con
  paciente, con staff inactivo y por rol, en el test que ya existe
  (`tests/Modules/Users/Unit/Domain/CurrentActorAuthorizationServiceTest.php`).
- **Navegador (`npm run test:ui`):** entradas nuevas de `screens.json` a 390, 768 y 1440 px con
  los datos de `ui:audit-data`; abren el detalle de cita como asistente y como doctor. Se ejecutan
  dos veces en `/implement` (contrato → tests inestables).
- **Regresión:** suite completa de Appointments y AppointmentTracking en serie.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: el doctor agenda, reprograma, cancela o elimina citas llamando a la API | A01:2025 | `POST`/`PUT`/`DELETE /appointments` | `appointments.create`/`update`/`delete` sin el doctor; `UpdateAppointmentRequest::authorize()` antes de validar | `AppointmentsAccessControlTest` (doctor × escritura, cuerpos válidos e inválidos) |
| TM2 | Information disclosure: el doctor lista citas de otros doctores manipulando filtros | A01:2025 | `GET /appointments`, `GET /agenda/today-appointments` | Recorte por `currentActorId()` en el caso de uso; ningún parámetro de la petición decide el alcance | `AgendaByRoleTest` (doctor con `user_id` ajeno y sin filtros) |
| TM3 | Information disclosure: el doctor lee el seguimiento y las recetas de una cita de otro doctor | A01:2025 | `GET /appointments/{id}/tracking` | `view-own` + comparación del `user_id` de la cita con el actor | `DoctorTrackingTest` (403 sin datos clínicos en el cuerpo) |
| TM4 | Tampering: el doctor completa una cita de otro doctor y escribe su seguimiento | A01:2025 | `POST /appointments/{id}/complete` | `create-own` + pertenencia, comprobada antes de crear seguimiento o cambiar la cita | `DoctorTrackingTest` (403, cita sin cambios, sin seguimiento) |
| TM5 | Elevation: el asistente completa una cita sin seguimiento con `PUT status: completada` o con `POST complete` | A01:2025 | `PUT /appointments/{id}`, `POST /complete` | `appointments.mark-completed` solo administrador (D1); `appointment-tracking.create` sin asistente | `AppointmentsAccessControlTest`, `DoctorTrackingTest` |
| TM6 | Elevation: el doctor o el asistente editan seguimiento o recetas | A01:2025 | `appointment-tracking/*` | Rutas en `only.admin` y permisos solo de administrador | `DoctorTrackingTest` |
| TM7 | Spoofing: un paciente, un staff inactivo o un visitante sin sesión usa la agenda o el seguimiento | A01:2025 | Rutas web y API de la agenda, `POST /complete` y `GET /tracking` | `auth:sanctum` + `staff` (activo, no paciente) + `assertCan`/`can`, que niegan a inactivos | `AppointmentsAccessControlTest`, `AgendaScreenByRoleTest`, `DoctorTrackingTest` (`/tracking`), `CompleteAppointmentTest` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: asistente ve la agenda completa | Core · mapa; rutas web; menú; agenda Blade | integración `AgendaByRoleTest`, `AgendaScreenByRoleTest`, `StaffNavigationTest`; ui-audit `agenda-asistente` · verificación manual: agenda como asistente a 390 y 1440 px contra la del administrador (`design.md`) |
| CA2: asistente agenda con las mismas reglas | Core · mapa (`create`, `selectors`); casos de uso de selectores (D11); diálogo de agendar con selectores | integración `AgendaByRoleTest` (201, 409, 422), `GetAppointmentsTest`, `AppointmentsAccessControlTest` (selectores), `AgendaScreenByRoleTest`; ui-audit de agendar como asistente |
| CA3: asistente reprograma y cancela | Core · mapa (`update`) | integración `AgendaByRoleTest`; ui-audit `agenda-cita-detalle-pendiente-asistente` |
| CA4 (abuso): asistente no completa, ni lee/edita seguimiento, ni elimina | `UpdateAppointmentUseCase` (D1); casos de uso de seguimiento; vista | integración `AppointmentsAccessControlTest`, `DoctorTrackingTest`; ui-audit `agenda-asistente`, `agenda-cita-detalle-asistente` |
| CA5: doctor ve solo sus citas, sin agendar | Casos de uso de listado; agenda Blade | integración `AgendaByRoleTest`, `AgendaScreenByRoleTest`; ui-audit `agenda-doctor`, `agenda-doctor-hoy-sin-citas` |
| CA6: doctor ve su cita sin reprogramar ni cancelar, con "Completar" | Agenda Blade y JS | ui-audit `agenda-cita-detalle-pendiente-doctor`, `agenda-doctor` · verificación manual: tarjeta y detalle a 390 px contra `design.md` |
| CA7 (abuso): doctor no agenda, reprograma, cancela ni elimina por otra vía | Core · mapa | integración `AppointmentsAccessControlTest` |
| CA8 (abuso): doctor no obtiene citas de otro doctor en los listados; lista vacía sin error | Casos de uso de listado; repositorio; listado vacío (D10) | integración `AgendaByRoleTest` |
| CA9: doctor lee el seguimiento de su cita completada | `GetAppointmentTrackingByAppointmentIdUseCase`; ruta; detalle | integración `DoctorTrackingTest`; ui-audit `agenda-cita-detalle-doctor` |
| CA10 (abuso): doctor no lee el de otro ni edita el suyo | Caso de uso de lectura; `appointment-tracking/*` sin cambios | integración `DoctorTrackingTest` |
| CA11 (abuso): paciente, inactivo, sin sesión denegados; administrador intacto | Middleware `staff`; mapa | integración `AppointmentsAccessControlTest`, `AgendaScreenByRoleTest`, `DoctorTrackingTest`, `OnlyAdminApiContractTest`, `GuestRedirectTest` |
| CA12: doctor completa su cita con seguimiento | `CompleteAppointmentUseCase`; ruta; `complete-appointment.js` cargado para el doctor | integración `DoctorTrackingTest`; ui-audit de completar como doctor |
| CA13 (abuso): doctor no completa la cita de otro | `CompleteAppointmentUseCase` | integración `DoctorTrackingTest` |
| TM1: doctor escribe citas por la API | Core · mapa | `AppointmentsAccessControlTest` |
| TM2: doctor lista citas ajenas | Casos de uso de listado | `AgendaByRoleTest` |
| TM3: doctor lee seguimiento ajeno | Caso de uso de lectura | `DoctorTrackingTest` |
| TM4: doctor completa cita ajena | `CompleteAppointmentUseCase` | `DoctorTrackingTest` |
| TM5: asistente completa sin seguimiento | `UpdateAppointmentUseCase` (D1); mapa | `AppointmentsAccessControlTest`, `DoctorTrackingTest` |
| TM6: doctor o asistente editan seguimiento | `appointment-tracking/*` sin cambios | `DoctorTrackingTest` |
| TM7: paciente, inactivo o sin sesión en la agenda y el seguimiento | Middleware `staff`; mapa | `AppointmentsAccessControlTest`, `AgendaScreenByRoleTest`, `DoctorTrackingTest`, `CompleteAppointmentTest` |
| RNF: permisos y recorte en el servidor | casos de uso | integración de CA4, CA7, CA8, CA10, CA13 sin interfaz |
| RNF: denegado por cada actor no autorizado | tests de acceso | `AppointmentsAccessControlTest`, `DoctorTrackingTest` |
| RNF: prueba en navegador a 390, 768 y 1440 px | `screens.json` | `npm run test:ui` |
| RNF: contraste, foco y teclado | componentes del sistema | `npm run test:ui` (contraste, foco, teclado) · verificación manual: orden de foco del pie del detalle como doctor |
| RNF: errores sin el contenido del seguimiento de otro doctor ni datos personales | `AuthorizationException::forbidden()` | `DoctorTrackingTest` (cuerpo exacto del 403) |

## Observabilidad
- Logs nuevos: ninguno. Los 403 y 201 nuevos quedan en el log de acceso de nginx con su
  `request_id`, como el resto.
- Eventos de auditoría: la spec los define, pero el registro aún no existe (objetivo 5; P14 ❌ aceptado).
  Puntos de emisión para esa spec: `GetAppointmentTrackingByAppointmentIdUseCase` (lectura de
  datos de salud por el doctor), `CompleteAppointmentUseCase` (escritura de datos de salud),
  `CreateAppointmentUseCase` y `UpdateAppointmentUseCase` (cambios de cita por actor) y
  `AuthorizationException` / `EnsureActiveStaff` (accesos denegados, ya identificados en 014).
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: con cuentas de prueba, el asistente abre `/agenda` y agenda una
  cita; el doctor ve solo las suyas y recibe 403 en `GET /api/v1/appointments/{id}/tracking` de
  una cita ajena; en Loki, sin 500 nuevos en `/api/v1/appointments*`.

## Descubribilidad
No aplica: la spec solo cambia `/agenda` y el detalle de la cita, rutas del panel no indexables, y
no crea ni cambia rutas públicas.

## Rollout
- Feature flag: no aplica (D3, decisión del usuario, 2026-10-17): prototipo sin datos reales.
- Orden de despliegue: una sola imagen (backend y vistas juntos) con `deploy.sh`; sin migraciones.
- Compatibilidad: sin cambios de esquema; durante el cambio de contenedor, la versión anterior
  sigue aplicando sus permisos (solo administrador), que es el estado seguro.
- Rollback: `docker/prod/rollback.sh` a la versión anterior; no hay datos que revertir (las citas
  que haya creado el asistente siguen siendo válidas para el administrador).
- Métricas a vigilar tras el deploy: 403 y 500 en `/api/v1/appointments*` y `/agenda` en Loki.

## Decisiones (→ ADR si son arquitectónicas)
- **D1** Completar desde la edición de la cita (`PUT status: completada`) sigue siendo solo del
  administrador, con el permiso nuevo `appointments.mark-completed`; 009 CA12 queda fuera de
  alcance (decisión del usuario, 2026-10-17).
- **D2** El doctor que pide el seguimiento o completa una cita de otro doctor recibe 403. La
  existencia de la cita no es secreta: su detalle ya es visible para todo el staff (014) (decisión
  del usuario, 2026-10-17).
- **D3** Sin feature flag (decisión del usuario, 2026-10-17).
- **D4** La 018 va antes que la 013 (QR por cita); la 013 partirá de este código cuando se retome
  (decisión del usuario, 2026-10-17).
- **D5** El detalle de la cita es un componente compartido con el expediente: el doctor también
  verá ahí el registro clínico de sus citas completadas. Opciones: (a) alcance por rol en el
  componente para las dos pantallas, (b) solo en la agenda. Se elige (a): es la misma regla de
  CA9 y CA10 y evita que el mismo diálogo se comporte distinto según la pantalla.
- **D6** Permisos "propios" (`*-own`) junto a los generales, en lugar de un único permiso con
  alcance. Opciones: (a) permisos separados en el mapa de 014, (b) un mapa de alcances
  (`todas`/`propias`), (c) Policies de Laravel. Se elige (a): encaja en la fuente única que ya
  existe, sin introducir Policies (014 decidió no usarlas) ni cambiar el formato del mapa.
- **D7** `can()` y `currentActorId()` en `AuthorizationServiceInterface` en lugar de leer
  `Auth::user()` en los casos de uso, para que la capa Application no dependa de la sesión.
- **D8** El texto vacío del registro clínico cambia también para el administrador ("Esta cita no
  tiene seguimiento registrado.", CA9), para que el mismo diálogo diga lo mismo a los dos roles.
- **D9** La spec es grande (13 criterios, extiende 4 specs) y se mantiene junta (decisión del
  usuario, 2026-10-17).
- **D10** Un listado de citas vacío responde 200 con lista vacía para todos los roles, en lugar
  de 409 (decisión del usuario, 2026-10-17, /analyze A7). La agenda ya pinta una lista vacía.
- **D11** Los selectores de doctores y tratamientos de la agenda se autorizan con
  `agenda.selectors.view` mediante casos de uso propios de Appointments, en lugar de dar al
  asistente `users.view` y `treatments.view`, que abrirían la gestión de usuarios y del catálogo,
  fuera de alcance (/analyze A1).
- **D12** Completar una cita que no existe sigue respondiendo 409, como hoy (decisión del usuario,
  2026-10-17, /analyze A4).
- Sin ADR: no hay dependencias nuevas, ni dependencias nuevas entre módulos, ni cambio de
  arquitectura.

## Impacto en arquitectura
- `docs/architecture.md`, sección de autorización: permisos "propios" del doctor y los métodos
  `can()` y `currentActorId()` de la interfaz.
- `docs/security.md`, modelo de autorización (línea "todo el staff activo lee…"): asistente con la
  agenda, doctor con sus citas y su seguimiento; filas de `/agenda` y de completar/seguimiento en
  la tabla de puntos de entrada.

## Riesgos y mitigaciones
- **Abrir de más con los selectores** → el asistente usa casos de uso propios de la agenda; un
  test confirma que sigue con 403 en la gestión de usuarios y del catálogo (D11).
- **Abrir de más al cambiar el mapa de permisos** → `RolePermissionsTest` fija cada permiso con
  sus roles exactos y `AppointmentsAccessControlTest` cubre la tabla completa por actor.
- **Mover las rutas de completar y seguimiento fuera de `only.admin`** deja la protección solo en
  `staff` + caso de uso → los tests de asistente y doctor ajeno verifican el 403 sin pasar por la
  interfaz (TM3–TM6).
- **El componente del detalle es compartido con el expediente** → `RecordsScreenTest` y la entrada
  de ui-audit del expediente cubren los tres roles (D5).
- **Tests que hoy afirman el 403 del asistente o del doctor** (`StaffNavigationTest`,
  `WebAccessDeniedPagesTest`, `GetAppointmentsTest`, `CompleteAppointmentTest`,
  `AppointmentsAccessControlTest`, `RolePermissionsTest`, `error-403-admin` de ui-audit) cambian
  de expectativa: se actualizan en la misma tarea que el permiso, nunca se borran.
  `OnlyAdminApiContractTest` no cambia.
- **El asistente crea citas con teléfono y dispara WhatsApp** en tests (cola `sync`) → `fakeTwilio()`
  en `AgendaByRoleTest`.
- **Pest en paralelo con timeouts de PostgreSQL** (roadmap, 017/R12) → la regresión se corre en serie.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-17 | Selectores de la agenda con casos de uso propios (D11); diálogo de agendar con selectores para el asistente; seeder correcto; completar inexistente en 409 (D12); tests de `/tracking` por actor; listado vacío en 200 (D10); P9, P14 y lista de tests que cambian corregidos | /plan --fix A1, A2, A3, A4, A5, A7, A10, A11, A12 |
