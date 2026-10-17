---
spec: 018-agenda-por-rol
plan: plan.md
impl_base: 2a6f200
status: approved   # reaprobado por el usuario, 2026-10-17, tras /tasks --fix (analysis ronda 1)
---

# Tareas · 018 Agenda por rol

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

Línea base (2026-10-17, `2a6f200`, `php -d memory_limit=1G ./vendor/bin/pest` en serie: con el límite por defecto de 128 MB la suite se queda sin memoria): 1344 en verde, 15 omitidos, 1 fallo ajeno a la spec, `DeployScriptsBehaviourTest` ("records the version as verified…"), porque `git tag` agota su tiempo de 60 s en el contenedor local. No debe aumentar.

Convenciones de esta spec:
- **Suite en verde por tarea:** cada test Pest nuevo o cambiado se escribe, se comprueba que falla
  por la razón esperada y se deja con `->skip('018: pendiente de T0xx')`. La tarea que lo
  implementa quita esa marca. Al cerrar cada tarea la suite Pest queda en verde salvo esas marcas;
  T033 falla si queda alguna.
- **Tests que cambian de expectativa** (hoy afirman el 403 del asistente o del doctor): se edita
  la expectativa en su tarea de test, nunca se borra el caso.
- **WhatsApp en tests:** todo test que cree citas para un paciente con teléfono llama a
  `fakeTwilio()`.
- `ui-audit` = `npm run test:ui -- --only agenda,expedientes,error-403` a 390, 768 y 1440 px, **en
  local** (Chrome del equipo), con los datos de `php artisan ui:audit-data`. La prueba de navegador
  (T019) no admite `skip`: en local se comprueba que falla por la razón esperada antes de T029–T031 y T040
  y que pasa después; cada tarea de interfaz anota el resultado (`- nota: ui-audit local
  AAAA-MM-DD: …`). CI lo repite al hacer push de la rama.
- **Pest de regresión en serie** (`./vendor/bin/pest`, sin `--parallel`), por los timeouts de
  PostgreSQL en paralelo (roadmap, 017/R12).
- "diseño" = [design.md](design.md) → "Composición y jerarquía"; "contrato" = [plan.md](plan.md) →
  "Contratos y datos"; "qué ve cada rol" = la tabla del mismo nombre en el plan.

## Constitution Check
Las tareas no añaden nada al análisis de [plan.md](plan.md); se revisa lo que el desglose pudo romper.

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec, `design.md` y plan `approved` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T019) van antes y cada tarea de implementación depende del suyo; la prueba de navegador se corre en local antes y después, con el resultado anotado | test: suite Pest (T033); `ui-audit` local anotado en T019, T029–T031 y T040 |
| P3 Capas del módulo | ✅ | T022 solo cambia en `Domain/` una firma con un value object propio; la sesión solo entra por `AuthorizationServiceInterface` (T020) | manual: `/review`; `grep` de `Illuminate` e `Infrastructure` en `Domain/` tocado (T033) |
| P4 Contrato de API primero | ✅ | T012–T015 fijan código y cuerpo por actor del contrato antes de T020–T027 | test: T012–T015 |
| P5 Autorización en el servidor | ✅ | Control de cada amenaza junto al caso de uso que protege (T021, T023–T027, T037–T038); un test de acceso denegado por actor no autorizado (T011–T016) | test: T011–T016 |
| P6 Validación con FormRequest | ➖ | Ninguna tarea cambia la entrada de un endpoint | — |
| P7 Errores sin detalles internos | ✅ | Los 403 nuevos usan `AuthorizationException::forbidden()`; T014 afirma el cuerpo exacto | test: T014 |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración ni variables | — |
| P9 Migraciones reversibles | ➖ | Ninguna tarea crea migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna tarea añade paquetes | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada TM tiene tarea de test y de control; ninguna tarea añade `Log::` | test: tabla de amenazas; manual: `/review` |
| P12 Producción con aprobación humana | ✅ | Línea base de dependencias limpia (plan); T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | T029–T031 y T040 solo presentan y llaman a la API; las reglas están en T021–T027 | manual: `/review` |
| P14 Trazabilidad y auditoría | ❌ aceptado: sin registro de auditoría hasta el objetivo 5 — aprobado por el usuario el 2026-10-17 (plan) | T091 anota en `docs/observability.md` los puntos de emisión del plan | manual: T091 |
| P15 Solo el sistema de diseño | ✅ | T001 actualiza el inventario antes de las tareas de interfaz, que dependen de ella; sin valores nuevos | test: `DesignTokensTest`, `OnlyDesignTokensTest` y `ui-audit` (T033) |
| Definición de terminado | ✅ | T033: Pint, Pest completa en serie, build, `ui-audit` y `aidd.py validate`; T034–T036 y T090–T092 | test: T033 |

## Preparación
- [x] T001 Añadir al inventario del sistema de diseño las vistas con rol Asistente y Doctor de la tabla "Pantallas" de `design.md` (agenda, detalle pendiente, detalle completado con y sin registro clínico, agenda del doctor sin citas hoy) y mover "Acceso denegado (asistente en ruta de administrador)" de `/agenda` a `/usuarios`, con su fila en "Decisiones" — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` y `OnlyDesignTokensTest` pasan y ninguna fila del inventario dice que `/agenda` rechaza al asistente
  - nota: hecha 2026-10-17: 10 filas nuevas en el inventario (asistente y doctor, con los ids de T019) y sus figuras en `system.html`; "Acceso denegado (asistente en ruta de administrador)" pasa a `/usuarios`; fila de Decisiones en `system.md`. Las capturas enlazadas se generan en T035. `DesignTokensTest` y `OnlyDesignTokensTest` en verde (Core/Unit/Design 103/103)
  - nota: A16 — en `design.md` → "Dirección elegida", la cita a `references/spec-mode.md` §1 pasa a la regla de exploración de `/design-spec`
  - nota: A25 — los ids del inventario son los de T019 (`agenda-asistente`, `agenda-cita-crear-asistente`, `agenda-doctor`, `agenda-doctor-hoy-sin-citas`, `agenda-cita-detalle-pendiente-asistente`, `agenda-cita-detalle-pendiente-doctor`, `agenda-cita-detalle-doctor`, `agenda-cita-detalle-doctor-sin-seguimiento`, `agenda-cita-detalle-asistente`, `agenda-cita-completar-doctor`)

## Tests (antes de implementar)
- [x] T010 [P] Ampliar el test del servicio de autorización con `can()` y `currentActorId()`: sin actor, paciente, staff inactivo y cada rol con un permiso que tiene y otro que no; `currentActorId()` sin actor de staff lanza `unauthenticated()` — `tests/Modules/Users/Unit/Domain/CurrentActorAuthorizationServiceTest.php` — hecho cuando: los casos nuevos fallan porque los métodos no existen y quedan con `skip` — cubre: CA11
  - nota: hecha 2026-10-17: 15 casos nuevos (`can()` por actor y permiso, `currentActorId()` por rol y sin actor de staff); fallan con "Call to undefined method" y quedan con `skip('018: pendiente de T020')`
- [x] T011 [P] Actualizar el dataset de permisos con los roles exactos del plan: `appointments.view`, `.create`, `.update` y `agenda.selectors.view` → Administrador y Asistente; `appointments.view-own`, `appointment-tracking.create-own` y `.view-own` → Doctor; `appointments.mark-completed`, `appointments.delete`, `appointment-tracking.create`, `.view`, `.update` y `.prescriptions.*` → Administrador — `tests/Modules/Users/Integration/RolePermissionsTest.php` — hecho cuando: falla en los permisos que cambian o son nuevos y queda con `skip` — cubre: CA4, CA7, CA11
  - nota: hecha 2026-10-17: matriz con los roles finales del plan; fallan los 8 permisos que cambian o son nuevos y el test de cobertura del mapa, y quedan con `skip('018: pendiente de T021')`. Los de staff inactivo y paciente siguen en verde (se rechazan antes de mirar el mapa)
  - nota: A21 — `users.view` y `treatments.view` siguen solo para el administrador en la matriz (comentario D11); es el test que cubre el riesgo de los selectores
- [ ] T012 [P] Escribir el test de la agenda por rol en la API: el asistente lista todas las citas y las de hoy, agenda (201, 409 por choque, 422 en el pasado), reprograma y cancela (CA1–CA3); el doctor recibe solo sus citas en `GET /appointments` y `GET /agenda/today-appointments`, también enviando `user_id` de otro doctor, `status` y `date`, y 200 con lista vacía si no tiene citas (CA5, CA8, TM2); el administrador sigue viendo todas y recibe 200 con lista vacía en un filtro sin resultados, antes 409 (CA11, D10); con `fakeTwilio()` — `tests/Modules/Appointments/Integration/AgendaByRoleTest.php` (nuevo) — hecho cuando: falla con 403 para asistente y doctor y queda con `skip` — cubre: CA1, CA2, CA3, CA5, CA8, CA11
- [ ] T013 [P] Ampliar el test de acceso con la tabla del contrato por actor: asistente → 403 en `PUT status: completada` y `DELETE` (CA4, TM5); doctor → 403 en `POST`, `PUT` (reprogramar, cancelar, completar) y `DELETE`, con cuerpos válidos e inválidos, y en los tres selectores (CA7, TM1); asistente → 200 en los tres selectores y 403 en `GET /users` y `GET /treatments` de gestión (CA2, D11); paciente y staff inactivo → 403 en todas (CA11, TM7); la cita no cambia tras cada 403. Cambian de expectativa: los casos de asistente en `POST` y en `PUT` de reprogramar o cancelar (403 → 200/201), el caso "anyone but the administrator" y el de citas del día y selector de doctores para asistente y doctor; la tabla de rutas del test de cobertura incorpora `POST /appointments/{id}/complete` y `GET /appointments/{id}/tracking` (y `buildRequest` sus casos) — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: los casos que cambian fallan por la razón esperada y quedan con `skip` — cubre: CA2, CA4, CA7, CA11
- [ ] T014 [P] Escribir el test del seguimiento del doctor: completa su cita con seguimiento y recetas, queda completada con su seguimiento (CA12); lee el de su cita (200) y recibe 404 sin seguimiento (CA9); recibe 403 con cuerpo exacto y sin datos clínicos al leer o completar la cita de otro doctor, y la cita y el seguimiento no cambian (CA10, CA13, TM3, TM4); recibe 403 en `PUT /appointment-tracking/{id}` y en las recetas (CA10, TM6); el asistente recibe 403 al completar, leer y editar (CA4, TM5, TM6); en `GET /appointments/{id}/tracking`, paciente y staff inactivo reciben 403 y el visitante sin sesión 401, con el cuerpo exacto (CA11, TM7); completar una cita inexistente como doctor da 409 (D12) — `tests/Modules/AppointmentTracking/Integration/DoctorTrackingTest.php` (nuevo) — hecho cuando: falla con 403 en los casos permitidos del doctor y queda con `skip` — cubre: CA4, CA9, CA10, CA11, CA12, CA13
- [ ] T015 [P] Cambiar las expectativas de los tests que afirman la agenda solo de administrador: selectores abiertos al asistente y cerrados al doctor; completar con 403 solo para el asistente (el doctor pasa a T014) — `tests/Modules/Appointments/Integration/GetAppointmentsTest.php`, `tests/Modules/AppointmentTracking/Integration/CompleteAppointmentTest.php` — hecho cuando: fallan por la razón esperada y quedan con `skip` — cubre: CA2, CA4
- [ ] T016 [P] Escribir el test de la pantalla y el menú por rol: `GET /agenda` responde 200 a los tres roles y la vista "Sin permiso" al paciente y al staff inactivo; en el HTML, el botón "Agendar nueva cita", `create-appointment-modal` con `data-create-appointment-with-selectors="true"` y los selectores de paciente y doctor, `create-appointment.js` y `cancel-appointment.js` solo para administrador y asistente, `complete-appointment.js` y su diálogo solo para administrador y doctor, la descripción "Tus citas asignadas." y `data-agenda-empty-today="No tienes citas para hoy."` solo para el doctor; el menú muestra "Agenda" a los tres roles y no al paciente; `/agenda` sale de `DENIED_ADMIN_SCREENS` — `tests/Modules/Appointments/Integration/AgendaScreenByRoleTest.php` (nuevo), `tests/Modules/Users/Integration/StaffNavigationTest.php`, `tests/Modules/Core/Integration/WebAccessDeniedPagesTest.php` — hecho cuando: falla con 403 para asistente y doctor y queda con `skip` — cubre: CA1, CA2, CA5, CA11
- [ ] T017 [P] Escribir el test del detalle de cita en el expediente por rol: el diálogo lleva `data-view-appointment-tracking-scope` `all` (administrador), `own` (doctor) y `none` (asistente) y `data-view-appointment-actor-id` con el id del actor; comprobar que `OnlyAdminApiContractTest` sigue afirmando el 403 del paciente y del staff inactivo en `/agenda/today-appointments` sin cambios — `tests/Modules/Patients/Integration/RecordsScreenTest.php`, `tests/Modules/Core/Integration/OnlyAdminApiContractTest.php` — hecho cuando: el caso nuevo de `RecordsScreenTest` falla y queda con `skip`; `OnlyAdminApiContractTest` pasa sin cambios — cubre: CA9, CA10, CA11
- [ ] T018 Preparar los datos de la prueba de navegador por rol: en `UiAuditSeeder`, un segundo doctor de prueba con citas en el mismo mes y el mismo día (22) que el doctor de prueba, y para el doctor de prueba una cita completada con seguimiento y recetas y otra completada sin seguimiento; `UiAuditSeederTest` comprueba los datos nuevos — `database/seeders/UiAuditSeeder.php`, `tests/Modules/Core/Integration/UiAuditSeederTest.php` — hecho cuando: `UiAuditSeederTest` pasa y `php artisan ui:audit-data` termina sin errores — cubre: CA5, CA8, CA9
- [ ] T019 Añadir las entradas de la prueba de navegador por rol de la tabla "Pantallas" de `design.md`: `agenda-asistente`, `agenda-cita-crear-asistente` (diálogo de agendar con selectores), `agenda-doctor` (`now` del día 22 de julio, con citas de los dos doctores), `agenda-doctor-hoy-sin-citas` (`now` propio en un día sin citas del doctor, como `agenda-hoy-sin-citas`), `agenda-cita-detalle-pendiente-asistente`, `agenda-cita-detalle-pendiente-doctor`, `agenda-cita-detalle-doctor`, `agenda-cita-detalle-doctor-sin-seguimiento`, `agenda-cita-detalle-asistente` y `agenda-cita-completar-doctor`; `error-403-admin` con `path` `/usuarios` — `tests/Browser/screens.json` — hecho cuando: `ui-audit` local falla en las entradas nuevas de asistente y doctor (403 en `/agenda`) y pasa en `error-403-admin`, con nota — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA9, CA12 — depende: T018

## Implementación

### Autorización y datos
- [ ] T020 Añadir `can(string $permission): bool` y `currentActorId(): string` a la interfaz y al servicio, con `assertCan()` apoyado en la misma comprobación — `app/Core/Authorization/AuthorizationServiceInterface.php`, `app/Core/Authorization/CurrentActorAuthorizationService.php` — hecho cuando: T010 pasa sin `skip` y el resto de la suite sigue en verde — cubre: CA11 — depende: T010
- [ ] T021 Cambiar el mapa de permisos según el plan (D1, D6): asistente en `appointments.view`, `.create`, `.update` y `agenda.selectors.view`; permisos nuevos `appointments.view-own`, `appointments.mark-completed`, `appointment-tracking.create-own` y `.view-own` — `app/Core/Authorization/CurrentActorAuthorizationService.php` — hecho cuando: T011 pasa sin `skip` — cubre: CA2, CA3, CA4, CA7, CA11 — depende: T011, T020
- [ ] T022 [P] Añadir el filtro por doctor (`?AppointmentUserId $userId = null`) al repositorio y al servicio de citas — `app/Modules/Appointments/Domain/Repositories/AppointmentsRepositoryInterface.php`, `app/Modules/Appointments/Domain/Service/AppointmentsService.php`, `app/Modules/Appointments/Infrastructure/Persistence/Eloquent/EloquentAppointmentRepository.php` — hecho cuando: la suite de Appointments pasa y una llamada con `$userId` devuelve solo las citas de ese doctor (caso en T012)

### Citas
- [ ] T023 Recortar los listados al doctor: `can('appointments.view')` sin recorte, `can('appointments.view-own')` con `currentActorId()`, si no `assertCan('appointments.view')`; el alcance nunca sale de la petición (control de TM2) — `app/Modules/Appointments/Aplication/UseCases/GetAllApointmentsByStatusAndDateUseCase.php`, `app/Modules/Appointments/Aplication/UseCases/GetTodayAppointmentsUseCase.php` — hecho cuando: T012 y T015 (`GetAppointmentsTest`) pasan sin `skip` — cubre: CA1, CA2, CA3, CA5, CA8 — depende: T012, T015, T021, T022
- [ ] T024 Exigir `appointments.mark-completed` antes de `complete()` en `updateStatus()` (D1; control de TM5 por la edición) — `app/Modules/Appointments/Aplication/UseCases/UpdateAppointmentUseCase.php` — hecho cuando: T013 pasa sin `skip` (control de TM1 y TM7 en T021) — cubre: CA4, CA7, CA11 — depende: T013, T021
- [ ] T037 Crear los casos de uso de los selectores de la agenda (D11): `GetAgendaDoctorsUseCase` (`assertCan('agenda.selectors.view')`, usuarios activos con rol doctor o administrador vía `UserService::findByRoleAndStatus()`, sin duplicados) y `GetAgendaTreatmentsUseCase` (`assertCan('agenda.selectors.view')`, catálogo vía `TreatmentsService`) — `app/Modules/Appointments/Aplication/UseCases/GetAgendaDoctorsUseCase.php` (nuevo), `app/Modules/Appointments/Aplication/UseCases/GetAgendaTreatmentsUseCase.php` (nuevo) — hecho cuando: la suite sigue en verde — cubre: CA2 — depende: T021
- [ ] T038 Usar los casos de uso nuevos en los selectores de doctores y tratamientos, sin cambiar la respuesta — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetDoctorsForAppointmentSelectController.php`, `app/Modules/Appointments/Infrastructure/Http/Controllers/GetTreatmentsController.php` — hecho cuando: los casos de selectores de T013 y T015 pasan sin `skip` y el asistente sigue con 403 en `GET /users` y `GET /treatments` — cubre: CA2, CA7 — depende: T013, T015, T037
- [ ] T039 Devolver lista vacía en lugar de lanzar `notFoundByFilters()` cuando el listado no tiene resultados (D10), y retirar esa fábrica si queda sin uso — `app/Modules/Appointments/Aplication/UseCases/GetAllApointmentsByStatusAndDateUseCase.php`, `app/Modules/Appointments/Domain/Exceptions/AppointmentException.php` — hecho cuando: los casos de lista vacía de T012 pasan — cubre: CA5, CA8 — depende: T012, T023

### Seguimiento clínico
- [ ] T025 Completar con permiso propio: sin `create` ni `create-own` → `assertCan('appointment-tracking.create')`; tras cargar la cita (404 si no existe), con solo `create-own` exigir que su `UserId()` sea `currentActorId()`, si no `AuthorizationException::forbidden('appointment-tracking.create-own')` antes de tocar la cita (control de TM4 y TM5 por completar) — `app/Modules/AppointmentTracking/Application/UseCases/CompleteAppointmentUseCase.php` — hecho cuando: la suite sigue en verde (los casos de completar de T014 y T015 se desbloquean en T027, cuando la ruta admite al doctor) — cubre: CA4, CA12, CA13 — depende: T014, T015, T021
- [ ] T026 [P] Leer el seguimiento con permiso propio: misma regla con `view`/`view-own`, inyectando `AppointmentsService` para cargar la cita (si no existe, `null` → 404 como hoy) (control de TM3) — `app/Modules/AppointmentTracking/Application/UseCases/GetAppointmentTrackingByAppointmentIdUseCase.php` — hecho cuando: la suite sigue en verde (los casos de lectura de T014 se desbloquean en T027) — cubre: CA4, CA9, CA10 — depende: T014, T021
- [ ] T027 Mover `POST /appointments/{id}/complete` y `GET /appointments/{id}/tracking` del grupo `only.admin` al grupo `auth:sanctum` + `staff`, dejando `appointment-tracking/*` en `only.admin` (control de TM6) — `routes/api.php` — hecho cuando: T014 y T015 (`CompleteAppointmentTest`) pasan sin `skip` — cubre: CA4, CA9, CA10, CA11, CA12, CA13 — depende: T013, T025, T026

### Interfaz
- [ ] T028 Abrir `/agenda` a `staff:administrador,asistente,doctor` y añadir "Agenda" tras "Inicio" en el menú de asistente y doctor — `routes/web.php`, `resources/views/components/ui/sidebar.blade.php` — hecho cuando: los casos de ruta y menú de T016 pasan sin `skip` — cubre: CA1, CA5, CA11 — depende: T001, T016
- [ ] T029 Cambiar la agenda a capacidades por rol (`$canManageAgenda`, `$canCompleteAppointments`, `$isDoctor`): botón de agendar, diálogos y scripts según "qué ve cada rol", descripción del doctor, `data-agenda-can-complete` / `data-agenda-empty-today`, `trackingScope` (`all` administrador, `own` doctor, `none` asistente) y `actorId` al diálogo de detalle, y `withSelectors` al diálogo de agendar — `resources/views/pages/agenda/index.blade.php` — hecho cuando: T016 pasa sin `skip` · verificación manual: agenda como asistente y como doctor a 390 y 1440 px contra la del administrador y el diseño — cubre: CA1, CA2, CA5, CA6 — depende: T019, T028
- [ ] T040 Renombrar el prop `isAdmin` del diálogo de agendar a `withSelectors` (`data-create-appointment-with-selectors`) en la vista y en su JS, sin cambiar el comportamiento del administrador — `resources/views/components/calendar/create-appointment-modal.blade.php`, `resources/js/pages/agenda/create-appointment.js` — hecho cuando: el caso de selectores del asistente de T016 pasa y `ui-audit` local pasa en `agenda-cita-crear` y `agenda-cita-crear-asistente`, con nota — cubre: CA2 — depende: T016, T019, T029
- [ ] T030 Hacer que el botón "Completar" de la tarjeta dependa de `data-agenda-can-complete` y el vacío de "Citas para hoy" tome `data-agenda-empty-today` — `resources/js/pages/agenda/index.js` — hecho cuando: `ui-audit` local pasa en `agenda-asistente`, `agenda-doctor` y `agenda-doctor-hoy-sin-citas`, con nota · verificación manual: tarjeta de cita pendiente del doctor a 390 px con "Completar" y sin "Reprogramar" ni "Cancelar cita", contra el diseño — cubre: CA4, CA5, CA6, CA12 — depende: T019, T029
- [ ] T031 Cambiar el detalle de la cita a alcance del registro clínico (`trackingScope` `all`/`own`/`none` y `actorId`), con el texto vacío "Esta cita no tiene seguimiento registrado." (D8), y pasarlo desde el expediente (D5) — `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js`, `resources/views/pages/records/index.blade.php` — hecho cuando: T017 pasa sin `skip` y `ui-audit` local pasa en `agenda-cita-detalle-*` (asistente y doctor), en completar como doctor y en `expedientes`, con nota · verificación manual: orden de foco del pie del detalle como doctor y registro clínico en lista, sin campos, a 390 y 1440 px contra el diseño — cubre: CA3, CA4, CA6, CA9, CA10, CA12 — depende: T017, T019, T029

### Cierre
- [ ] T032 Comprobar que no quedan marcas `018: pendiente` ni usos de `isAdmin` en la agenda y el detalle — `tests/Modules/Core/Unit/Design/UnusedUiTest.php` — hecho cuando: `grep -rn "018: pendiente" tests` y `grep -rn "isAdmin\|is-admin" resources/views/pages/agenda resources/views/components/calendar resources/js/pages/agenda` no devuelven nada, y `UnusedUiTest` pasa (se ajusta solo si referencia los nombres retirados) — depende: T023, T024, T027, T030, T031, T038, T039, T040
- [ ] T033 Ejecutar la verificación completa: `vendor/bin/pint --dirty --format agent`, `./vendor/bin/pest` en serie, `npm run build`, `ui-audit` local (dos veces, contrato → tests inestables), `grep` de `Illuminate` e `Infrastructure` en `Domain/` tocado y `python .ai/bin/aidd.py validate` — sin archivos nuevos — hecho cuando: todo pasa sin marcas `skip` de la 018, con nota de resultados — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10, CA11, CA12, CA13 — depende: T032
- [ ] T034 Regenerar la línea base de composición con las entradas nuevas revisando el diff (solo cambian entradas de asistente, doctor y `error-403-admin`) — `tests/Browser/baseline-1440.json` — hecho cuando: `ui-audit` local pasa sin `--baseline` y el diff no toca entradas del administrador — depende: T033
- [ ] T035 Generar las capturas del inventario de vistas nuevas y de `error-403-admin` — `docs/design/capturas/ (generado por npm run test:ui -- --only agenda,expedientes,error-403 --width 390 y --width 1440)` — hecho cuando: cada fila nueva del inventario de `system.md` enlaza capturas que existen — depende: T001, T034
- [ ] T036 Actualizar el modelo de autorización y los puntos de entrada: asistente con la agenda salvo completar y eliminar; doctor con sus citas, completar y leer el seguimiento de las suyas; `/agenda`, `POST /complete` y `GET /tracking` en la tabla de puntos de entrada — `docs/security.md` — hecho cuando: la línea del modelo de autorización y las filas de la tabla coinciden con el contrato del plan — depende: T033

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: sección de autorización con los permisos "propios" del doctor y los métodos `can()` y `currentActorId()`
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `observability.md`, los puntos de emisión de auditoría de la 018 para el objetivo 5 (lectura y escritura de seguimiento por el doctor, cambios de cita por el asistente); sin cambios en `deployment.md`
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097, con cuentas de prueba. El asistente abre `/agenda` y agenda una cita (CA1, CA2); el doctor ve solo sus citas (CA5) y recibe 403 en `GET /api/v1/appointments/{id}/tracking` de una cita ajena (CA10); en Loki, sin 500 nuevos en `/api/v1/appointments*` ni en `/agenda`.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T012, T016, T019 | T021, T023, T028, T029 |
| CA2 | T012, T013, T015, T016, T019 | T021, T023, T029, T037, T038, T040 |
| CA3 | T012, T019 | T021, T023, T031 |
| CA4 | T011, T013, T014, T015, T019 | T021, T024, T025, T026, T027, T030, T031 |
| CA5 | T012, T016, T018, T019 | T023, T028, T029, T030, T039 |
| CA6 | T019 | T029, T030, T031 |
| CA7 | T011, T013 | T021, T024, T038 |
| CA8 | T012, T018 | T023, T039 |
| CA9 | T014, T017, T018, T019 | T026, T027, T031 |
| CA10 | T014, T017 | T026, T027, T031 |
| CA11 | T010, T011, T012, T013, T014, T016, T017 | T020, T021, T024, T027, T028 |
| CA12 | T014, T019 | T025, T027, T030, T031 |
| CA13 | T014 | T025, T027 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021, T038 | T013 |
| TM2 | T023 | T012 |
| TM3 | T026 | T014 |
| TM4 | T025 | T014 |
| TM5 | T024, T025 | T013, T014 |
| TM6 | T027 | T014 |
| TM7 | T021, T027, T028 | T013, T014, T016, T017 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Core · Autorización | T020, T021 |
| Appointments · Domain | T022, T039 |
| Appointments · Infrastructure | T022, T038 |
| Appointments · Aplication | T023, T024, T037, T039 |
| AppointmentTracking · Application | T025, T026 |
| Rutas | T027 |
| Rutas web | T028 |
| Interfaz · menú | T028 |
| Interfaz · agenda | T029, T030, T040 |
| Interfaz · detalle | T031 |
| Interfaz · expediente | T031 |
| Datos de prueba de la interfaz | T018 |
| Tests de navegador | T019, T034 |
| Sistema de diseño | T001, T035 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Permisos y recorte del doctor en el servidor; ocultar no sustituye (P5, P13) | T012, T013, T014 |
| Prueba de acceso denegado por cada actor no autorizado de cada fila (P5) | T011, T013, T014, T016 |
| Agenda y detalle, vista de asistente y de doctor, en navegador a 390, 768 y 1440 px (P15) | T019, T030, T031, T033, T040 |
| Contraste, foco visible y teclado para los tres roles (WCAG 2.1 AA) | T033 (`ui-audit`), T031 (verificación manual del orden de foco) |
| Errores sin el contenido del seguimiento de otro doctor ni datos personales o de salud (P7, P11) | T014 |

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-17 | T010 amplía el test existente; T012 lista vacía; T013 casos que cambian, rutas nuevas y selectores; T014 `/tracking` por actor y 409; T016 selectores; T018 dividida en T018 (seeder) y T019 (`screens.json`) con ids y `now`; nuevas T037–T040 (selectores de la agenda, lista vacía, diálogo de agendar); T027 depende de T013 | /tasks --fix A1, A2, A3, A5, A6, A7, A13, A14 |
