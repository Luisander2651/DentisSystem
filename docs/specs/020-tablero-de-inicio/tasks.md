---
spec: 020-tablero-de-inicio
plan: plan.md
status: draft
---

# Tareas · 020 Tablero de inicio

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

Convenciones de esta spec:
- **Suite en verde por tarea:** cada test Pest nuevo o cambiado se escribe, se comprueba que falla
  por la razón esperada y se deja con `->skip('020: pendiente de T0xx')`. La tarea que lo
  implementa quita esa marca. Al cerrar cada tarea, la suite Pest queda en verde salvo esas marcas.
  T050 falla si queda alguna.
- **Piezas antes del cableado:** las clases que solo se ejercitan a través de la API se cierran con
  un criterio comprobable en ese momento: sintaxis, Pint, tests de arquitectura y la suite en verde
  con las marcas `skip`. Los tests de integración que las cubren se activan en la tarea que
  registra la ruta.
- **Tests de integración:** en `tests/Modules/Appointments/Integration` (base
  `AppointmentsIntegrationTestCase`), `tests/Modules/AppointmentTracking/Integration` (base
  `AppointmentTrackingIntegrationTestCase`) y `tests/Modules/Core/Integration` (base
  `CoreIntegrationTestCase`), declaradas con `uses()`. Todo test que cree citas para un paciente
  con teléfono llama a `fakeTwilio()`. `now()` se fija con `travelTo()` en `America/Mexico_City`:
  el día de los tests es el jueves 22 de octubre de 2026 a las 11:40, con los datos de
  `design.md` (8 citas hoy, de las cuales 3 son completadas, 4 por atender y 1 cancelada; la
  siguiente a las 12:30; 15 sin cerrar; 6 completadas sin seguimiento).
- "contrato" = [plan.md](plan.md) → "Contratos y datos"; "Qué ve cada rol" = la tabla del plan;
  "c5" = [design/candidatos/c5.html](design/candidatos/c5.html) y `c5-doctor.html`.
- `ui-audit` = `npm run test:ui -- --only dashboard` a 390, 768 y 1440 px, **en local** (Chrome
  del equipo), tras `php artisan ui:audit-data`. La prueba de navegador no admite `skip`: en local
  se comprueba que falla por la razón esperada antes de las tareas de interfaz y que pasa después.
  Cada tarea de interfaz anota el resultado (`- nota: ui-audit local AAAA-MM-DD: …`). A 1440 px,
  hasta T051 la única falla admitida en las entradas `dashboard-*` es "sin línea base" o la
  diferencia de composición del inicio; desde T051, ninguna.
- `evaluate` = `node E:/AI-DD-Setup/skills/design-spec/scripts/evaluate.mjs http://localhost:8000/dashboard
  --criteria docs/specs/020-tablero-de-inicio/design/criterios.json` (y `criterios-doctor.json`),
  con una sesión del rol, sobre la build servida en local. Usa `playwright-core` 1.64.1 fuera del
  repositorio (`PLAYWRIGHT_PATH`) y el Chrome del equipo (`CHROMIUM_PATH`), como en `/design-spec`.
- **Pest de regresión en serie** (`./vendor/bin/pest`, sin `--parallel`), por los timeouts de
  PostgreSQL en paralelo (017/R12). CI sigue corriendo `--parallel`.

## Constitution Check
Las tareas no añaden nada al análisis de [plan.md](plan.md); se revisa lo que el desglose pudo romper.

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec, `design.md` y plan `approved` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T027) van antes y cada tarea de implementación depende del suyo. Las extracciones de T004 y T005 no cambian comportamiento y las fijan los tests que ya existen (`AgendaByRoleTest`, `CompleteAppointmentTest`, `DoctorTrackingTest`) más `DoctorScopeTest` (T003). Lo del navegador va en T026 y se corre en local antes y después | test: suite Pest (T050); `ui-audit` local anotado en T026 y T045–T048 |
| P3 Capas del módulo | ✅ | Domain (T030, T031, T036) sin Eloquent ni facades; las consultas y la transacción solo en Infrastructure (T032, T037, T039) | test: T027 |
| P4 Contrato de API primero | ✅ | T013–T017 fijan la forma de cada respuesta antes de T033–T041 | test: T013–T017 |
| P5 Autorización en el servidor | ✅ | El control de cada amenaza va junto al módulo que protege (T004, T033, T038, T040). Acceso denegado por actor y por endpoint (T019) y `assertCan` primero (T012) | test: T012, T019, T025 |
| P6 Validación con FormRequest | ✅ | T040 crea `RegisterAppointmentTrackingRequest` después de su test (T017) | test: T017 |
| P7 Errores sin detalles internos | ✅ | Los controladores responden 500 genérico (T035, T038, T041); el doble envío da 409 (T039) | test: T016, T023 |
| P8 Secretos fuera del repositorio | ➖ | Ninguna tarea añade configuración ni variables | — |
| P9 Migraciones reversibles | ➖ | Ninguna tarea crea migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna tarea añade dependencias; `playwright-core` queda fuera del repositorio | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM8 con tarea de test y de control; el recurso de las listas no lleva datos clínicos (T034) | test: T020 y los de cada TM |
| P12 Producción con aprobación humana | ✅ | Línea base de dependencias limpia (plan); T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | T046 solo pinta lo que llega del servidor: siguiente cita, contadores, `can` y `day_label` (T030, T034) | test: T010, T013, T014; manual: `/review` del JS |
| P14 Trazabilidad y auditoría | ❌ aceptado: sin registro de auditoría (OB1.a) ni correlación (OB3.a) hasta el objetivo 5 — aprobado por el usuario el 2026-10-23 (plan) | T091 anota en `docs/observability.md` los puntos de emisión de la 020 | manual: T091 |
| P15 Solo el sistema de diseño | ✅ | T001 añade las piezas de `design.md` antes de las tareas de interfaz, que dependen de ella; sin tokens nuevos | test: `DesignTokensTest`, `OnlyDesignTokensTest`, `ui-audit` y `evaluate` (T050) |
| Definición de terminado | ✅ | T050: Pint, Pest completa en serie, build, `ui-audit`, `evaluate`, auditorías y `aidd.py validate`; T051–T053 y T090–T092 | test: T050 |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los cambios de `design.md` → "Cambios a incorporar al sistema": regla de color de la barra del día (completadas `success`, por atender `warning`, canceladas `danger`, con cifra y etiqueta al lado), componentes "Siguiente cita" y "Línea de ahora" con sus medidas, y en el inventario el inicio del administrador, del asistente y del doctor con datos (sin captura hasta T051), sin la vista "Inicio del panel, nuevo usuario"; fila en "Decisiones" — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` y `OnlyDesignTokensTest` pasan · verificación manual: `system.html` muestra la siguiente cita, la línea de ahora y la barra del día a 390 y 1440 px contra c5; revisión anotada
- [ ] T002 Sembrar los datos del inicio para la prueba de navegador: citas de hoy relativas a la hora del sembrado para los dos doctores (una completada y una cancelada antes de ahora, la siguiente a +1 h y una reprogramada a +3 h; si una ya no cabe en el día, se omite), al menos una completada sin seguimiento de cada doctor, y la comprobación de que las asignadas de julio de 2026 siguen siendo "sin cerrar"; `agenda-hoy-sin-citas` sigue mirando un día sin citas — `database/seeders/UiAuditSeeder.php` — hecho cuando: `php artisan ui:audit-data` corre dos veces seguidas sin duplicar filas, y `npm run test:ui -- --only agenda` da el mismo resultado que antes, con nota — cubre: CA1, CA2, CA4, CA7
- [ ] T003 [P] Escribir `DoctorScopeTest`: el administrador y el asistente (`appointments.view`) sin recorte, el doctor (`appointments.view-own`) con su id y el resto rechazado con `AuthorizationException`, con un servicio de autorización falso — `tests/Modules/Appointments/Unit/Application/DoctorScopeTest.php` (nuevo) — hecho cuando: falla porque la clase no existe y queda con `skip` — cubre: CA1, CA13
- [ ] T004 Extraer el recorte del doctor a `DoctorScope::forActor()` y usarlo en `GetTodayAppointmentsUseCase` y `GetAllApointmentsByStatusAndDateUseCase`, sin cambiar su comportamiento (D11, 018/R5) — `app/Modules/Appointments/Aplication/Support/DoctorScope.php` (nuevo), `app/Modules/Appointments/Aplication/UseCases/GetTodayAppointmentsUseCase.php`, `app/Modules/Appointments/Aplication/UseCases/GetAllApointmentsByStatusAndDateUseCase.php` — hecho cuando: T003 pasa sin `skip`, y `AgendaByRoleTest` y `GetTodayAppointmentsTest` siguen en verde — cubre: CA13 — depende: T003
- [ ] T005 Extraer de `CompleteAppointmentUseCase` la construcción del seguimiento y las recetas a `TrackingFactory` (D12), con el `Instructions::fromNullable` de v0.5.1 — `app/Modules/AppointmentTracking/Application/Support/TrackingFactory.php` (nuevo), `app/Modules/AppointmentTracking/Application/UseCases/CompleteAppointmentUseCase.php` — hecho cuando: `CompleteAppointmentTest` y `DoctorTrackingTest` pasan igual que antes — cubre: CA8

## Tests (antes de implementar)
- [ ] T010 [P] Escribir los tests de `TodayAgenda`. De unidad, con los datos de las convenciones: orden por hora; contadores 8/3/4/1; siguiente a las 12:30; `after` con 13:15 y 16:00 y `after_more` 1; una cita a la misma hora que ahora cuenta como siguiente; sin siguiente si todo pasó o está cerrado; día vacío. Propiedades con Eris: completadas + por atender + canceladas = total; la siguiente, si existe, es por atender, tiene hora ≥ ahora y no hay otra por atender antes ni después de ahora que sea anterior a ella; `after` ≤ 2 — `tests/Modules/Appointments/Unit/Domain/TodayAgendaTest.php` (nuevo), `tests/Modules/Appointments/Unit/Domain/TodayAgendaPropertiesTest.php` (nuevo) — hecho cuando: fallan porque la clase no existe y quedan con `skip` — cubre: CA1, CA2, CA3
- [ ] T011 [P] Escribir los tests de `AppointmentPage`: crea con hasta 10 citas y su total; rechaza más citas que el límite o un total menor que las citas. Propiedad con Eris: para toda lista y total generados válidos, `total ≥ count(items)` e `items ≤ 10` — `tests/Modules/Appointments/Unit/Domain/AppointmentPageTest.php` (nuevo), `tests/Modules/Appointments/Unit/Domain/AppointmentPagePropertiesTest.php` (nuevo) — hecho cuando: fallan porque la clase no existe y quedan con `skip` — cubre: CA4, CA7
- [ ] T012 [P] Escribir el test de autorización de los casos de uso nuevos: sin permiso, `GetTodaySummaryUseCase`, `GetUnclosedAppointmentsUseCase`, `GetCompletedWithoutTrackingUseCase` y `RegisterAppointmentTrackingUseCase` lanzan `AuthorizationException` sin llamar al repositorio; con permiso, lo primero es comprobarlo — `tests/Modules/Appointments/Unit/Application/DashboardUseCasesAuthorizationTest.php` (nuevo) — hecho cuando: falla porque los casos de uso no existen y queda con `skip` — cubre: CA13, CA14
- [ ] T013 [P] Escribir `TodaySummaryTest`: forma del contrato; citas de hoy por hora con paciente, tratamiento, doctor y estado; siguiente cita y `after`; contadores; día sin citas (`next: null`); el doctor solo recibe las suyas aunque pida `?user_id=<otro>` (TM1); `can` por rol (completar: administrador o doctor con su cita; cancelar: administrador o asistente) — `tests/Modules/Appointments/Integration/TodaySummaryTest.php` (nuevo) — hecho cuando: falla con 404 (la ruta no existe) y queda con `skip` — cubre: CA1, CA2, CA3, CA13
- [ ] T014 [P] Escribir `UnclosedAppointmentsTest`: solo días anteriores en asignada o reprogramada (las de hoy y las completadas o canceladas quedan fuera); 15 → 10 más `total` 15 (TM8); orden de la más reciente a la más antigua; `day_label` "Ayer · miércoles 21" y "Martes 20"; recorte del doctor con `user_id` ajeno (TM1); vacío con `total` 0; `can` por rol — `tests/Modules/Appointments/Integration/UnclosedAppointmentsTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA4, CA5, CA6, CA13
- [ ] T015 [P] Escribir `CompletedWithoutTrackingTest`: solo completadas sin seguimiento; hasta 10 más el total; recorte del doctor (TM1); el asistente recibe 403; vacío — `tests/Modules/AppointmentTracking/Integration/CompletedWithoutTrackingTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA7, CA13, CA14
- [ ] T016 [P] Escribir `RegisterAppointmentTrackingTest`: el administrador y el doctor en su cita registran el seguimiento con recetas (una sin instrucciones); la cita sigue completada y sale de la lista sin seguimiento. Doctor en una cita ajena → 403 sin guardar (TM2). Cita con seguimiento, cancelada, asignada o reprogramada → 409 con su texto; inexistente → 404 (CA15, TM5). Doble envío → el segundo 409, no 500. Asistente → 403 — `tests/Modules/AppointmentTracking/Integration/RegisterAppointmentTrackingTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA8, CA13, CA14, CA15
- [ ] T017 [P] Escribir `RegisterAppointmentTrackingValidationTest`: dataset de `reason`, `symptoms`, `diagnosis` y `procedure_performed` vacíos, `durationDays` 0 y 366, `dailyFrequency` 0 y 25 → 422 con el mensaje en español de cada campo en `errors` — `tests/Modules/AppointmentTracking/Integration/RegisterAppointmentTrackingValidationTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA8
- [ ] T018 [P] Escribir `DashboardFromInboxTest`: una cita sin cerrar completada con `POST /complete` por el administrador y por su doctor, y cancelada por el asistente, deja de salir en `/agenda/unclosed` — `tests/Modules/Appointments/Integration/DashboardFromInboxTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA5, CA6
- [ ] T019 [P] Escribir `DashboardAccessControlTest`: cada endpoint nuevo por actor (asistente en `/agenda/without-tracking` y `POST /tracking`, doctor con cita ajena, staff inactivo, paciente, sin sesión) con su código (TM3, TM4); el asistente recibe 403 en `GET /reports/monthly` y `POST /patients` (CA14) — `tests/Modules/Appointments/Integration/DashboardAccessControlTest.php` (nuevo) — hecho cuando: los casos de las rutas nuevas fallan y quedan con `skip`; los de rutas existentes pasan — cubre: CA13, CA14, CA16
- [ ] T020 [P] Escribir `DashboardNoClinicalDataTest`: con un seguimiento, recetas y datos médicos y de contacto conocidos, ninguna respuesta de las tres lecturas contiene esos valores ni claves fuera del contrato (TM6) — `tests/Modules/Appointments/Integration/DashboardNoClinicalDataTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip` — cubre: CA7
- [ ] T021 [P] Escribir `DashboardPerformanceTest`: 50 citas hoy, 200 sin cerrar y 2 000 en el mes; las tres lecturas y `reports/monthly` del mes en curso, pedidas una tras otra como administrador, suman menos de 2 s (RNF, D7) — `tests/Modules/Appointments/Integration/DashboardPerformanceTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip`
- [ ] T022 [P] Escribir `DashboardMatchesReportsTest`: el mes que la vista entrega al inicio (atributo de datos) es el mes en curso, y `reports/monthly` con ese mes da las cifras de citas, pacientes nuevos con su comparación y activos de los datos del test — `tests/Modules/Core/Integration/DashboardMatchesReportsTest.php` (nuevo) — hecho cuando: falla porque la vista no lleva el mes y queda con `skip` — cubre: CA10
- [ ] T023 [P] Escribir `UnexpectedDashboardErrorTest`: si el repositorio de cada lectura o el registro lanza una excepción, la respuesta es 500 `{"error": "Internal server error"}` sin detalle — `tests/Modules/Appointments/Integration/UnexpectedDashboardErrorTest.php` (nuevo) — hecho cuando: falla con 404 y queda con `skip`
- [ ] T024 Escribir `DashboardScreenTest` con el dataset de "Qué ve cada rol" (encabezado, acciones de la siguiente cita, acciones rápidas, secciones que existen o no en el HTML), título "Inicio - Dentissa", un `h1`, ningún contador con "-", sin "Estado: En línea", "Actividad reciente", "Nuevo usuario" ni la tarjeta con inicial y correo, y el inicio del paciente sin cambios; y ajustar `RoleCopyTest` (sin la insignia del encabezado ni `data-role-label="inicio"` en el inicio del staff; el rol sigue en el menú) y `PageStructureTest` (título del inicio) — `tests/Modules/Core/Integration/DashboardScreenTest.php` (nuevo), `tests/Modules/Core/Integration/RoleCopyTest.php`, `tests/Modules/Core/Integration/PageStructureTest.php` — hecho cuando: los casos nuevos y cambiados fallan contra la vista actual y quedan con `skip` — cubre: CA9, CA10, CA11, CA16
- [ ] T025 [P] Añadir al dataset de permisos los que usan las lecturas y el registro nuevos (`appointments.view`, `appointments.view-own`, `appointment-tracking.create`, `appointment-tracking.create-own`) con su resultado por actor, sin cambiar el mapa — `tests/Modules/Users/Integration/RolePermissionsTest.php` — hecho cuando: los casos nuevos pasan (el mapa no cambia) — cubre: CA13, CA14
- [ ] T026 Añadir a la prueba de navegador las entradas `dashboard-completar` (doctor, diálogo de completar desde "Citas sin cerrar"), `dashboard-cancelar` (asistente, confirmación), `dashboard-registrar-seguimiento` (administrador, diálogo en modo registro, con un intento sin motivo que deja el error junto al campo) y `dashboard-nueva-cita` (administrador), y los flujos en `dashboard-administrador` "error de una sección" (500 en `/agenda/unclosed`: su bloque con "Reintentar" a la vista tras 13 s, alcanzable con Tab, y las demás secciones con contenido) y "nombre con HTML" (paciente `<img src=x onerror="window.__xss=1">` como texto, sin `img` en la lista y `window.__xss` sin definir, TM7); quitar `dashboard-crear-usuario` — `tests/Browser/screens.json` — hecho cuando: `ui-audit` local falla en las entradas nuevas por la razón esperada (falta el selector o el texto) y las demás no cambian, con nota — cubre: CA5, CA6, CA8, CA9, CA12
- [ ] T027 [P] Escribir los tests de arquitectura de los archivos que crea o toca esta spec en `Appointments/Domain` y `AppointmentTracking/Domain` (`TodayAgenda`, `AppointmentPage`, la interfaz del repositorio, `PendingTrackingRepositoryInterface`, `TrackingRegistrationException`): ninguno usa `…\Infrastructure`, `Illuminate\Support\Facades` ni `Illuminate\Database\Eloquent`. Los archivos con deuda conocida (`TreatmentsService`) quedan fuera, nombrados en el test — `tests/Modules/Appointments/Unit/AppointmentsArchitectureTest.php` (nuevo), `tests/Modules/AppointmentTracking/Unit/AppointmentTrackingArchitectureTest.php` (nuevo) — hecho cuando: fallan porque los archivos nuevos no existen y quedan con `skip`

## Implementación

### Appointments: hoy y sin cerrar
- [ ] T030 Crear `TodayAgenda::fromAppointments(AppointmentEntity[], DateTimeImmutable $now)` con orden, contadores, siguiente, `after` y `after_more` — `app/Modules/Appointments/Domain/ValueObjects/TodayAgenda.php` (nuevo) — hecho cuando: T010 pasa sin `skip` — cubre: CA1, CA2, CA3 — depende: T010
- [ ] T031 Crear `AppointmentPage` y declarar `findUnclosedBefore(AppointmentDate $today, ?AppointmentUserId $userId, int $limit): AppointmentPage` en la interfaz y `findUnclosed(...)` en el servicio — `app/Modules/Appointments/Domain/ValueObjects/AppointmentPage.php` (nuevo), `app/Modules/Appointments/Domain/Repositories/AppointmentsRepositoryInterface.php`, `app/Modules/Appointments/Domain/Service/AppointmentsService.php` — hecho cuando: T011 pasa sin `skip` y la suite sigue en verde — cubre: CA4 — depende: T011
- [ ] T032 Implementar `EloquentAppointmentRepository::findUnclosedBefore`: fecha < hoy, estado asignada o reprogramada, recorte opcional por `user_id`, `with(user, patient, treatment)`, orden por fecha y hora descendente, límite y `count()` con los mismos filtros (control de TM8) — `app/Modules/Appointments/Infrastructure/Persistence/Eloquent/EloquentAppointmentRepository.php` — hecho cuando: sintaxis y Pint en verde y la suite sigue en verde (lo ejercitan T014 y T021 en T035) — cubre: CA4 — depende: T031
- [ ] T033 Crear `GetTodaySummaryUseCase` (recorte con `DoctorScope`, `now()` de la clínica → `TodayAgenda`) y `GetUnclosedAppointmentsUseCase` (mismo recorte, límite 10), con `assertCan` antes que nada (control de TM1) — `app/Modules/Appointments/Aplication/UseCases/GetTodaySummaryUseCase.php` (nuevo), `app/Modules/Appointments/Aplication/UseCases/GetUnclosedAppointmentsUseCase.php` (nuevo) — hecho cuando: los casos de estos dos casos de uso en T012 pasan sin `skip` — cubre: CA1, CA4, CA13 — depende: T004, T012, T030, T031
- [ ] T034 Crear los recursos del contrato: `TodaySummaryResource` y `DashboardAppointmentResource` con campos fijos (id, fecha, hora, estado, paciente, tratamiento, doctor, `is_past`, `is_next`, `day_label`, `can`), sin datos clínicos ni de contacto (control de TM6); `can` y `day_label` se calculan con el actor y la fecha de la clínica — `app/Modules/Appointments/Infrastructure/Http/Resources/TodaySummaryResource.php` (nuevo), `app/Modules/Appointments/Infrastructure/Http/Resources/DashboardAppointmentResource.php` (nuevo) — hecho cuando: sintaxis y Pint en verde (los ejercitan T013, T014 y T020 en T035) — cubre: CA1, CA4, CA5, CA6, CA7 — depende: T030
- [ ] T035 Crear `GetTodaySummaryController` y `GetUnclosedAppointmentsController` (invocables, `final`, 500 con `UnexpectedErrorResponse`) y registrar `GET /agenda/today-summary` y `GET /agenda/unclosed` en el grupo `auth:sanctum` + `staff` (control de TM4) — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetTodaySummaryController.php` (nuevo), `app/Modules/Appointments/Infrastructure/Http/Controllers/GetUnclosedAppointmentsController.php` (nuevo), `routes/api.php` — hecho cuando: T013, T014, T018 y los casos de estas rutas en T019, T020, T021 y T023 pasan sin `skip` — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA13, CA16 — depende: T013, T014, T018, T032, T033, T034

### AppointmentTracking: sin seguimiento y registrar
- [ ] T036 Crear `PendingTrackingRepositoryInterface::findCompletedWithoutTracking(?string $userId, int $limit)` y `TrackingRegistrationException` con `alreadyRegistered()`, `notCompleted()` y `appointmentNotFound()` y los textos del contrato — `app/Modules/AppointmentTracking/Domain/Repositories/PendingTrackingRepositoryInterface.php` (nuevo), `app/Modules/AppointmentTracking/Domain/Exceptions/TrackingRegistrationException.php` (nuevo) — hecho cuando: T027 pasa sin `skip` para estos archivos y los de T030–T031 — cubre: CA7, CA15 — depende: T027, T030, T031
- [ ] T037 Implementar `EloquentPendingTrackingRepository` (estado `completada` y `whereNotExists` sobre `appointment_tracking`, recorte por `user_id`, orden descendente, límite y total) y enlazarlo — `app/Modules/AppointmentTracking/Infrastructure/Persistence/Eloquent/EloquentPendingTrackingRepository.php` (nuevo), `app/Providers/AppServiceProvider.php` — hecho cuando: el contenedor resuelve la interfaz y la suite sigue en verde — cubre: CA7 — depende: T036
- [ ] T038 Crear `GetCompletedWithoutTrackingUseCase` (`appointment-tracking.create` sin recorte, `create-own` con el recorte del doctor, asistente rechazado; control de TM1 y TM3) y su controlador, y registrar `GET /agenda/without-tracking` — `app/Modules/AppointmentTracking/Application/UseCases/GetCompletedWithoutTrackingUseCase.php` (nuevo), `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/GetCompletedWithoutTrackingController.php` (nuevo), `routes/api.php` — hecho cuando: T015, el caso de T012 y los de esta ruta en T019, T020 y T023 pasan sin `skip` — cubre: CA7, CA13, CA14 — depende: T012, T015, T034, T035, T037
- [ ] T039 Añadir al repositorio de completar la operación `register(AppointmentTrackingEntity, PrescriptionEntity[])`: guarda el seguimiento y las recetas en una transacción sin tocar la cita y convierte `UniqueConstraintViolationException` en `TrackingRegistrationException::alreadyRegistered()` (control de TM5) — `app/Modules/AppointmentTracking/Domain/Repositories/AppointmentCompletionRepositoryInterface.php`, `app/Modules/AppointmentTracking/Infrastructure/Persistence/Eloquent/EloquentAppointmentCompletionRepository.php`, `app/Modules/AppointmentTracking/Domain/Service/AppointmentCompletionService.php` — hecho cuando: sintaxis y Pint en verde y `CompleteAppointmentTest` sigue en verde — cubre: CA8, CA15 — depende: T036
- [ ] T040 Crear `RegisterAppointmentTrackingUseCase` (permiso y pertenencia como completar, estado `completada` y sin seguimiento, construcción con `TrackingFactory`, guardado con `register`; control de TM2, TM3 y TM5) y `RegisterAppointmentTrackingRequest` (reglas en array y mensajes en español de los campos de completar) — `app/Modules/AppointmentTracking/Application/UseCases/RegisterAppointmentTrackingUseCase.php` (nuevo), `app/Modules/AppointmentTracking/Infrastructure/Http/Requests/RegisterAppointmentTrackingRequest.php` (nuevo) — hecho cuando: el caso de T012 pasa sin `skip` — cubre: CA8, CA13, CA15 — depende: T005, T012, T039
- [ ] T041 Crear `RegisterAppointmentTrackingController` (201 con el registro; 404, 409 y 403 con los textos del contrato; 500 genérico) y registrar `POST /appointments/{id}/tracking` en el grupo `auth:sanctum` + `staff` — `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/RegisterAppointmentTrackingController.php` (nuevo), `routes/api.php` — hecho cuando: T016, T017 y los casos de esta ruta en T019 y T023 pasan sin `skip` — cubre: CA8, CA13, CA14, CA15 — depende: T016, T017, T038, T040

### Interfaz
- [ ] T042 Crear `ShowDashboardPageController` (invocable, `final`) que entrega a la vista el rol, la fecha y la hora de la clínica, el mes en curso (`AAAA-MM`) y las capacidades del rol, y apuntar `/dashboard` a él sin cambiar su grupo — `app/Core/Http/ShowDashboardPageController.php` (nuevo), `routes/web.php` — hecho cuando: `/dashboard` responde 200 para los cuatro roles y la suite sigue en verde — cubre: CA10, CA16 — depende: T024
- [ ] T043 [P] Crear los componentes `x-dashboard.next-appointment` y `x-dashboard.day-progress` con las medidas de `design.md` y solo tokens — `resources/views/components/dashboard/next-appointment.blade.php` (nuevo), `resources/views/components/dashboard/day-progress.blade.php` (nuevo) — hecho cuando: `OnlyDesignTokensTest` pasa y se renderizan con `Blade::render` sin errores — cubre: CA2, CA3 — depende: T001
- [ ] T044 [P] Crear los componentes `x-dashboard.now-line` y `x-dashboard.pending-group` con las medidas de `design.md` y solo tokens — `resources/views/components/dashboard/now-line.blade.php` (nuevo), `resources/views/components/dashboard/pending-group.blade.php` (nuevo) — hecho cuando: `OnlyDesignTokensTest` pasa y se renderizan con `Blade::render` sin errores — cubre: CA1, CA4, CA7 — depende: T001
- [ ] T045 Reescribir la rama del staff de `dashboard.blade.php` según c5: encabezado con `h1` y fecha, "Nueva cita" solo del administrador, secciones con sus regiones de carga, vacío y error y sus `<template>`, acciones rápidas por rol, el mes solo del administrador, diálogos de la agenda según el rol y título "Inicio - Dentissa"; fuera las piezas de CA11 y `x-ui.create-user-modal`; la rama del paciente no cambia. Corregir aquí los tres problemas abiertos de `design.md` → Resultado (D15) — `resources/views/pages/dashboard.blade.php` — hecho cuando: T022 y T024 pasan sin `skip` · verificación manual: el inicio de cada rol a 390 y 1440 px contra c5 y `c5-doctor.html`, sin datos (solo la estructura); revisión anotada — cubre: CA1, CA9, CA10, CA11, CA16 — depende: T001, T042, T043, T044
- [ ] T046 Reescribir `dashboard.js`: carga en paralelo de las secciones del rol, pintado de cada una (siguiente cita, día, agenda con línea de ahora, sin cerrar agrupadas por `day_label`, sin seguimiento y mes en texto "+6 respecto a septiembre (36)"), estados por sección con "Reintentar", datos solo con `textContent` o plantillas (control de TM7), acciones según `can`, enlaces de los avisos de pendientes y `window.agendaPage.reload` para los diálogos reutilizados — `resources/js/pages/dashboard.js` — hecho cuando: `ui-audit` local pasa `dashboard-administrador`, `-asistente` y `-doctor`, incluidos los flujos "error de una sección" y "nombre con HTML" (salvo la línea base de 1440 px), y `evaluate` pasa con `criterios.json` (administrador) y `criterios-doctor.json` (doctor) · verificación manual: inicio con datos de cada rol a 390, 768 y 1440 px contra c5; "Agenda de hoy" abre la agenda en el día de hoy; revisión anotada — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA9, CA10, CA12 — depende: T026, T035, T038, T045
- [ ] T047 Añadir al diálogo de completar el modo `register` (título "Registrar seguimiento", envío a `POST /appointments/{id}/tracking`, aviso "Seguimiento registrado.", 422 bajo su campo); sin `mode`, todo igual — `resources/js/pages/agenda/complete-appointment.js`, `resources/views/components/calendar/complete-appointment-modal.blade.php` — hecho cuando: `ui-audit` local pasa `dashboard-registrar-seguimiento`, `dashboard-completar`, `dashboard-cancelar` y `dashboard-nueva-cita`, y las entradas `agenda-cita-completar*` dan el mismo resultado que antes — cubre: CA5, CA6, CA8 — depende: T041, T046
- [ ] T048 [P] Dejar el foco en el campo de búsqueda de expedientes al cargar con `?buscar=1` ("Buscar paciente", CA9) — `resources/js/pages/records/index.js` — hecho cuando: `npm run test:ui -- --only expedientes` da el mismo resultado que antes · verificación manual: desde el inicio, "Buscar paciente" abre los expedientes con el foco en el buscador, a 390 y 1440 px — cubre: CA9 — depende: T045

### Cierre
- [ ] T050 Verificación completa: Pint (`--dirty`) sin cambios, `./vendor/bin/pest` completo en serie sin fallos nuevos respecto a la línea base y sin marcas `skip('020…')`, `npm run build`, `ui-audit` completo dos veces, `evaluate` con los dos criterios, `composer audit` y `npm audit --audit-level=high` sin vulnerabilidades altas, y `python .ai/bin/aidd.py validate docs/specs/020-tablero-de-inicio` sin errores — hecho cuando: todo en verde, con nota de resultados — depende: T047, T048
- [ ] T051 Rehacer la línea base de 1440 px de las entradas `dashboard-*` y las capturas del inicio de los tres roles en `docs/design/capturas/`, y marcarlas como capturadas en el inventario de `system.md` — `tests/Browser/baseline-1440.json`, `docs/design/capturas/` (generado por `npm run test:ui -- --only dashboard --baseline`), `docs/design/system.md` — hecho cuando: `ui-audit` local pasa `dashboard-*` sin fallos a ningún ancho — depende: T050
- [ ] T052 Actualizar el roadmap: cerrar "Inicio del panel: los contadores muestran '-'", el tablero de inicio de "Pendientes y deuda", 016/R42, 016/R43 y 018/R5; dejar el objetivo 5 como la próxima spec (D8) — `docs/roadmap.md` — hecho cuando: el roadmap ya no lista esos pendientes y `aidd.py validate` sigue sin errores — depende: T050
- [ ] T053 Añadir a `docs/security.md` los puntos de entrada nuevos con su permiso (`/agenda/today-summary`, `/agenda/unclosed`, `/agenda/without-tracking`, `POST /appointments/{id}/tracking`) — `docs/security.md` — hecho cuando: la sección de puntos de entrada los lista con su permiso; ningún riesgo cambia de estado — depende: T050

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: endpoints nuevos de Appointments y AppointmentTracking, `DoctorScope` y `TrackingFactory`, y el inicio que consume además `reports/monthly`
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `observability.md`, los puntos de emisión de la 020 para el objetivo 5 (escritura de seguimiento en `RegisterAppointmentTrackingUseCase` y `CompleteAppointmentUseCase`, cancelación en `UpdateAppointmentUseCase`, accesos denegados); OB1.a y OB3.a siguen pendientes. Sin cambios en `deployment.md`
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097, con cuentas de prueba. El administrador y el doctor entran al inicio: las secciones cargan, la siguiente cita es la correcta y el mes coincide con `/reportes` (CA1–CA4, CA10). El asistente no ve "Completadas sin seguimiento" ni el mes (CA14). En Loki, sin 500 en `/api/v1/agenda/*` ni en `/api/v1/appointments/*/tracking`.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T013, T024 | T030, T033, T035, T045, T046 |
| CA2 | T010, T013 | T030, T035, T043, T046 |
| CA3 | T010, T013 | T030, T035, T043, T046 |
| CA4 | T011, T014 | T031, T032, T033, T035, T044, T046 |
| CA5 | T014, T018, T026 | T034, T035, T046, T047 |
| CA6 | T014, T018, T026 | T034, T035, T046, T047 |
| CA7 | T011, T015, T020 | T036, T037, T038, T044, T046 |
| CA8 | T016, T017, T026 | T005, T039, T040, T041, T047 |
| CA9 | T024, T026 | T045, T046, T048 |
| CA10 | T022, T024 | T042, T045, T046 |
| CA11 | T024 | T045 |
| CA12 | T026 | T046 |
| CA13 | T003, T012, T013, T014, T015, T016, T019, T025 | T004, T033, T035, T038, T040, T041 |
| CA14 | T012, T015, T016, T019, T025 | T038, T041 |
| CA15 | T016 | T036, T039, T040, T041 |
| CA16 | T019, T024 | T035, T042, T045 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T004, T033, T038 | T013, T014, T015 |
| TM2 | T040 | T016 |
| TM3 | T038, T040 | T019 |
| TM4 | T035, T041 | T019 |
| TM5 | T039, T040 | T016 |
| TM6 | T034 | T020 |
| TM7 | T046 | T026 |
| TM8 | T032 | T014, T021 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Core · Autorización (sin permisos nuevos) | T025 |
| Appointments · Domain | T030, T031 |
| Appointments · Application (`DoctorScope`, casos de uso) | T004, T033 |
| Appointments · Infrastructure | T032, T034, T035 |
| AppointmentTracking · Domain | T036, T039 |
| AppointmentTracking · Application (`TrackingFactory`, casos de uso) | T005, T038, T040 |
| AppointmentTracking · Infrastructure | T037, T038, T039, T040, T041 |
| Providers | T037 |
| Rutas | T035, T038, T041, T042 |
| Interfaz · pantalla | T042, T045 |
| Interfaz · componentes | T043, T044 |
| Interfaz · JS | T046 |
| Interfaz · diálogos reutilizados | T047 |
| Interfaz · expedientes | T048 |
| Datos de prueba de la interfaz | T002 |
| Tests de navegador | T026, T051 |
| Sistema de diseño | T001, T051 |
| Documentación | T052, T053, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| < 2 s con 50 hoy, 200 sin cerrar y 2 000 en el mes | T021 |
| Cálculos en el servidor; el mes sale del cálculo de Reportes | T010, T013, T022 |
| Permisos en el servidor, denegado por actor | T012, T019 |
| Navegador a 390, 768 y 1440 px, contraste, foco, teclado y listas en orden | T026, T046 (`ui-audit`, `evaluate`), T050 · verificación manual en T046: recorrido con NVDA del inicio del doctor |
| Errores sin detalles internos y logs sin datos de pacientes | T023; manual: `/review` de `Log::` |
