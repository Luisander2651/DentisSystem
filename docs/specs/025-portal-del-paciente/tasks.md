---
spec: 025-portal-del-paciente
plan: plan.md
status: approved
impl_base: a72087d
---

# Tareas · 025 Portal del paciente

> Línea base 2026-11-10 (`a72087d`, rama `feat/025-portal-del-paciente`):
> - Pest en serie (`memory_limit=1G`): 2 018 pasan y 2 fallan de forma intermitente (`MonthlyReportPerformanceTest`, timeout de conexión a PostgreSQL, y `DeployScriptsBehaviourTest`); los dos pasan al repetirlos solos (1 130 s).
> - `npm run test:ui` (dos vueltas, mismo resultado): 306 visitas, 3 con fallo, todas en la agenda y ajenas a esta spec: `agenda` @ 1440 (espera de `#next-month`), `agenda-cita-detalle-pendiente` @ 390 (espera de un día del calendario) y `agenda-cita-crear` @ 390 ("no llega a enviar"). No deben aumentar.
> - `npm audit --audit-level=high`: 0. `composer audit`: solo los 8 avisos de Symfony ignorados por EX2 (vence el 2026-11-12; `/chore` previsto el 2026-11-11).

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
- **Rebanadas verticales:** cada tarea escribe primero su test, comprueba que falla por la razón
  esperada, implementa y termina con la suite en verde. Ninguna tarea deja tests en rojo ni con
  `skip`.
- "contrato" = [plan.md](plan.md) → "Contratos y datos"; "c3" = las páginas de
  `design/candidatos/c3*.html` de [design.md](design.md).
- **Tests de integración** con su base declarada con `uses()`: `AppointmentsIntegrationTestCase`,
  `AppointmentTrackingIntegrationTestCase`, `AuthIntegrationTestCase` o `CoreIntegrationTestCase`.
  Reloj fijo de los tests de fechas: 2026-09-17 11:40 (America/Mexico_City).
- **Token real** = la petición lleva `Authorization: Bearer <token>` de `createToken()`, sin
  `actingAs`. Lo exigen las pruebas de separación de sesiones.
- `ui-audit` = `npm run test:ui -- --only <entradas>` a 390, 768 y 1440 px, **en local** (Chrome
  del equipo), tras `php artisan ui:audit-data`. Se comprueba que la entrada nueva falla antes de
  implementar y que pasa después, y se anota el resultado bajo la tarea.
- Los criterios de diseño K1–K6 se comprueban con `ui-audit` (plan → Trazabilidad); la comparación
  con c3 es `verificación manual:` en la tarea de cada pantalla.
- **Pest de regresión en serie** (`./vendor/bin/pest`, sin `--parallel`), por los fallos conocidos
  de PostgreSQL en paralelo en local. CI sigue corriendo `--parallel`.
- Comandos dentro del contenedor: `docker compose exec app …`.

## Constitution Check
Las tareas no cambian el Constitution Check de [plan.md](plan.md):

| Principio | Resultado | Cómo lo cumplen las tareas | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño y plan `approved` | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada rebanada incluye su test, visto fallar antes de implementar; T013 añade el test de propiedades; las pantallas llevan su entrada de `ui-audit` | test: `./vendor/bin/pest --parallel` y `npm run test:ui` en CI; T050 |
| P3 Capas del módulo | ✅ | T050 extiende `AppointmentsArchitectureTest` a los archivos nuevos de `Aplication/`; los casos de uso piden el paciente a `currentPatientId()` (T005) | manual: `/review` y búsqueda de `Infrastructure` dentro de `Domain/`; test: T050 |
| P4 Contrato de API primero | ✅ | T011, T014, T015 y T017 fijan cada código y cabecera del contrato | test: T011, T014, T015, T017 |
| P5 Autorización en el servidor | ✅ | Cada ruta nueva añade sus filas a `PortalAccessControlTest` (T011, T014, T015, T017); T018 cubre las rutas del personal y los ids iguales | test: T011, T014, T015, T017, T018; manual: `/review` con `shared/security-checklist.md` |
| P6 Validación con FormRequest | ➖ | Sin cuerpo ni consulta; id de ruta con `AppointmentId` → 400, como permite P6 desde la 1.2.1 (plan) | test: T015, T017 |
| P7 Errores sin detalles internos | ✅ | T019 fuerza los fallos en las cuatro rutas | test: T019; `grep -rn "getMessage()"` en los controladores nuevos (T050) |
| P8 Secretos fuera del repositorio | ➖ | Sin variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna tarea toca `composer.json` ni `package.json` | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM11 con su tarea (Cobertura); T019 comprueba que no salen datos clínicos; sin `Log::` nuevos | test: `aidd.py validate`, T019; manual: `/review` de las llamadas a `Log::` |
| P12 Producción con aprobación humana | ✅ | T001 fija la línea base de auditorías; T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | La próxima cita, el orden, el QR y las recetas se deciden en los casos de uso (T010, T013, T015, T016) | test: T011, T014, T015, T017; manual: `/review` del JS y Blade tocados |
| P14 Trazabilidad y auditoría | ❌ aceptado: sin registro de auditoría ni correlación hasta el objetivo 5 — aprobado por el usuario el 2026-11-10 (plan) | T091 anota los puntos de emisión de la 025 en `docs/observability.md` | — |
| P15 Solo el sistema de diseño | ✅ | Las pantallas usan solo componentes existentes (c3); T050 corre los tests de tokens y `ui-audit` completo | test: `OnlyDesignTokensTest`, `DesignTokensTest` y `npm run test:ui` (T050) |

## Preparación
- [x] T001 Fijar la línea base: Pest en serie, `npm run test:ui`, `composer audit` y `npm audit --audit-level=high`; anotar resultados y `impl_base` en este archivo — `docs/specs/025-portal-del-paciente/tasks.md` — hecho cuando: la nota de línea base está escrita, sin vulnerabilidades críticas o altas fuera de la excepción EX2 (o EX2 ya cerrada por el `/chore` de Symfony)
- [x] T002 Separar las sesiones por tipo de cuenta: guard `sanctum` con provider `users`, guard `patient` con provider `patients`, `auth:sanctum,patient` en todos los grupos de rutas y `layouts/error` reconociendo también al paciente; `ActingAsPatient` pasa al guard `patient` y gana un ayudante de token real — `config/auth.php`, `routes/api.php`, `routes/web.php`, `resources/views/layouts/error.blade.php`, `tests/Support/ActingAsPatient.php`, `tests/Modules/Auth/Integration/GuardSeparationTest.php`, `tests/Modules/Core/Integration/GuestRedirectTest.php` — hecho cuando: `GuardSeparationTest` pasa (token real de paciente: el guard `sanctum` no lo reconoce y `patient` sí; token real de personal: al revés; token de paciente en `/api/v1/appointments` → 403, no 401; token de personal → 200), `NotFoundPageTest` y `WebAccessDeniedPagesTest` siguen en verde con el paciente (camino de vuelta a su inicio) y la suite completa está en verde — cubre: CA10, CA14, CA16 — depende: T001
  - nota: GuardSeparationTest falló antes del cambio (el guard `sanctum` aceptaba el token del paciente y `patient` no existía). Comprobado rompiendo: sin provider en `sanctum` falla "reads a patient token only through the patient guard"; con el grupo del personal solo en `auth:sanctum` falla "answers a patient token on a staff route with 403, not 401"; `layouts/error` sin el guard `patient` hace fallar el caso `paciente` de `NotFoundPageTest` y los de `WebAccessDeniedPagesTest`. Restaurado.
  - nota: Auth, Core/Integration y todos los `*AccessControl*Test`: 733 pasan. `ui-audit` de dashboard, error-403, error-404 y logout: 37 visitas sin fallos. Semgrep sobre los archivos tocados: 0 hallazgos. Secretos: el diff solo crea tokens de prueba en tiempo de ejecución; gitleaks corre sobre el historial en CI.
  - nota: A11 — cada petición con otro token empieza sin guards en caché (como `forgetAuthState()` de `AuthIntegrationTestCase`); `GuardSeparationTest` debe fallar si se quita el provider del guard `sanctum`.
  - nota: cuatro archivos de código a propósito: configuración, rutas y `layouts/error` tienen que cambiar juntos para que ninguna sesión quede rechazada a medias (`/analyze 025` A2, A15).
- [ ] T004 [P] Crear los middlewares `patient` (`EnsureActivePatient`) y `no-store` (`NoStoreResponse`) y sus alias — `app/Core/Middlewares/EnsureActivePatient.php`, `app/Core/Middlewares/NoStoreResponse.php`, `bootstrap/app.php`, `tests/Modules/Core/Unit/Middlewares/EnsureActivePatientTest.php`, `tests/Modules/Core/Unit/Middlewares/NoStoreResponseTest.php` — hecho cuando: los tests pasan: paciente activo → sigue; personal → 403 `{"error": "Only patients can access this resource."}`; paciente inactivo → 403 `{"error": "Your account is inactive."}`; en una ruta web → `abort(403)`; `no-store` pone `Cache-Control: private, no-store` en respuestas 200 y de error — cubre: CA15, CA16, CA20 — depende: T002
- [ ] T005 [P] Añadir `currentPatientId()` a `AuthorizationServiceInterface` y a `CurrentActorAuthorizationService`, y a los dobles de test que implementan la interfaz — `app/Core/Authorization/AuthorizationServiceInterface.php`, `app/Core/Authorization/CurrentActorAuthorizationService.php`, `tests/Modules/Core/Unit/Authorization/CurrentActorAuthorizationServiceTest.php` (y los dobles de `DashboardUseCasesAuthorizationTest`, `DoctorScopeTest`, `GetAppointmentQrUseCaseTest`, `TrackingUseCasesAuthorizationTest`, `ReportsUseCasesAuthorizationTest`) — hecho cuando: el test pasa: paciente activo → su id; paciente inactivo, personal y sin actor → `AuthorizationException`; la suite está en verde — cubre: CA13, CA15 — depende: T002

## Implementación

### Historia 1: mis citas y mi próxima cita (API)
- [ ] T010 Crear `GetPatientAppointmentsUseCase` y `PortalAppointmentResource` — `app/Modules/Appointments/Aplication/UseCases/GetPatientAppointmentsUseCase.php`, `app/Modules/Appointments/Infrastructure/Http/Resources/PortalAppointmentResource.php`, `tests/Modules/Appointments/Unit/Application/GetPatientAppointmentsUseCaseTest.php`, `tests/Modules/Appointments/Unit/Infrastructure/PortalAppointmentResourceTest.php` — hecho cuando: los tests pasan: `currentPatientId()` se llama antes que el repositorio y, si lanza, no se lee nada; las citas salen por fecha y hora descendentes (dos del mismo día, la de hora mayor primero); el recurso tiene exactamente `id`, `date`, `time`, `status`, `treatment_name` y `doctor_name` — cubre: CA3, CA13, CA17 — depende: T005
  - nota: A20 — el caso de uso recibe un DTO `final readonly` con `::create()` (sin datos del paciente: lo da la sesión).
- [ ] T011 Exponer `GET /api/v1/portal/appointments` en el grupo `portal` (`auth:sanctum,patient`, `patient`, `no-store`) — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetPortalAppointmentsController.php`, `routes/api.php`, `tests/Modules/Appointments/Integration/PortalAppointmentsTest.php`, `tests/Modules/Appointments/Integration/PortalAccessControlTest.php` — hecho cuando: los tests pasan según el contrato: orden 26/11, 12/11, 20/10, 14/09; solo las citas propias aunque llegue `patient_id` de otro en la consulta o en el cuerpo; sin citas → `{"data": []}`; `Cache-Control: private, no-store` en 200, en el 401 sin sesión y en los 403 de personal y de paciente inactivo, con peticiones reales a la ruta; sin sesión → 401; administrador, asistente, doctor, personal inactivo y paciente inactivo → 403 — cubre: CA3, CA4, CA11, CA13, CA15, CA16, CA20 — depende: T004, T010
- [ ] T013 Crear `GetPatientNextAppointmentUseCase` con su DTO (fecha de hoy) — `app/Modules/Appointments/Aplication/UseCases/GetPatientNextAppointmentUseCase.php`, `app/Modules/Appointments/Aplication/DTOs/GetPatientNextAppointmentDTO.php`, `tests/Modules/Appointments/Unit/Application/GetPatientNextAppointmentUseCaseTest.php`, `tests/Modules/Appointments/Unit/Application/PatientNextAppointmentPropertiesTest.php` — hecho cuando: los tests pasan: `currentPatientId()` primero; con hoy = 2026-09-17, asignada hoy a las 09:00 → es la próxima; asignada el 16/09 → no; completada o cancelada hoy → no; hoy 16:00 y mañana 09:00 → la de hoy; reprogramada mañana y asignada pasado mañana → la de mañana; ninguna → `null`; propiedad (Eris): la elegida es siempre abierta y de hoy o posterior, y ninguna otra abierta desde hoy es anterior — cubre: CA1, CA2 — depende: T005
- [ ] T014 Exponer `GET /api/v1/portal/appointments/next`, declarada antes que las rutas con `{id}` — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetPortalNextAppointmentController.php`, `routes/api.php`, `tests/Modules/Appointments/Integration/PortalAppointmentsTest.php`, `tests/Modules/Appointments/Integration/PortalAccessControlTest.php` — hecho cuando: los tests pasan con el reloj en 2026-09-17 11:40: `{"data": PortalAppointment}` con las fronteras de T013 y `{"data": null}` sin cita abierta; `patient_id` ajeno ignorado; cabecera `no-store`; filas de acceso por actor (401 y 403) — cubre: CA1, CA2, CA11, CA13, CA15, CA16, CA20 — depende: T011, T013

### Historia 2: el QR de mi cita (API)
- [ ] T015 Crear `GetPatientAppointmentQrUseCase` y exponer `GET /api/v1/portal/appointments/{id}/qr` — `app/Modules/Appointments/Aplication/UseCases/GetPatientAppointmentQrUseCase.php`, `app/Modules/Appointments/Infrastructure/Http/Controllers/GetPortalAppointmentQrController.php`, `routes/api.php`, `tests/Modules/Appointments/Unit/Application/GetPatientAppointmentQrUseCaseTest.php`, `tests/Modules/Appointments/Integration/PortalAppointmentQrTest.php`, `tests/Modules/Appointments/Integration/PortalAccessControlTest.php` — hecho cuando: los tests pasan según el contrato: cita propia asignada y reprogramada → 200 `image/svg+xml` y el SVG decodificado con `DecodesQrSvg` es el id; completada y cancelada → 409 sin SVG; cita de otro paciente → 404 con el mismo cuerpo que un UUID sin cita; `no-es-uuid` → 400; cabecera `no-store`; `currentPatientId()` antes que el repositorio; filas de acceso por actor — cubre: CA7, CA11, CA12, CA15, CA16, CA18, CA20 — depende: T014
  - nota: A20 — el caso de uso recibe un DTO `final readonly` con `::create()` con el id de la cita.
  - nota: A10 — también cita ajena completada y cancelada → 404 con el mismo cuerpo que un UUID inexistente (la pertenencia se comprueba antes que el estado).

### Historia 3: recetas y recomendaciones (API)
- [ ] T016 Crear `GetPatientPrescriptionsUseCase` y los recursos `PortalPrescriptionsResource` y `PortalPrescriptionResource` — `app/Modules/AppointmentTracking/Application/UseCases/GetPatientPrescriptionsUseCase.php`, `app/Modules/AppointmentTracking/Infrastructure/Http/Resources/PortalPrescriptionsResource.php`, `app/Modules/AppointmentTracking/Infrastructure/Http/Resources/PortalPrescriptionResource.php`, `tests/Modules/AppointmentTracking/Unit/Application/GetPatientPrescriptionsUseCaseTest.php`, `tests/Modules/AppointmentTracking/Unit/Infrastructure/PortalPrescriptionsResourceTest.php` — hecho cuando: los tests pasan: `currentPatientId()` primero; cita ajena o inexistente → `null`; completada con seguimiento → recomendaciones y recetas; sin seguimiento, asignada o cancelada → vacío; los recursos tienen exactamente `recommendations` y `prescriptions` con `medication`, `dosage`, `daily_frequency`, `duration_days` e `instructions` — cubre: CA5, CA6, CA8, CA12, CA17 — depende: T005
  - nota: A20 — el caso de uso recibe un DTO `final readonly` con `::create()` con el id de la cita.
- [ ] T017 Exponer `GET /api/v1/portal/appointments/{id}/prescriptions` — `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/GetPortalPrescriptionsController.php`, `routes/api.php`, `tests/Modules/AppointmentTracking/Integration/PortalPrescriptionsTest.php`, `tests/Modules/Appointments/Integration/PortalAccessControlTest.php` — hecho cuando: los tests pasan según el contrato: completada con seguimiento y dos recetas → 200 con los cinco campos de cada una; completada sin seguimiento → 200 vacío; con seguimiento sin recomendaciones → `null`; asignada y cancelada → vacío; ajena → 404; id inválido → 400; cabecera `no-store`; filas de acceso por actor — cubre: CA5, CA6, CA8, CA11, CA12, CA15, CA16, CA20 — depende: T015, T016
  - nota: A19 — también cita completada con recomendaciones y sin recetas: `recommendations` con texto y `prescriptions` vacío.
  - nota: A10 — también cita ajena completada con seguimiento → 404, el mismo cuerpo que un UUID inexistente.

### Seguridad transversal
- [ ] T018 Completar las pruebas de separación y de acceso a las rutas del personal — `tests/Modules/Auth/Integration/GuardSeparationTest.php`, `tests/Modules/Appointments/Integration/PortalAccessControlTest.php` — hecho cuando: los tests pasan: un usuario del personal y un paciente creados con el mismo UUID, con tokens reales, reciben cada uno solo lo suyo (paciente: sus citas en `/portal/appointments` y 403 en `/appointments`; personal: 200 en `/appointments` y 403 en `/portal/appointments`); paciente activo e inactivo contra expediente, pacientes, agenda, detalle, QR y seguimiento del personal → 403. Si alguno falla, se corrige el control en la tarea que lo creó — cubre: CA14, CA15, CA16 — depende: T017
  - nota: A11 — misma regla que T002: sin guards en caché entre peticiones con tokens distintos.
- [ ] T019 [P] Probar que el portal no filtra datos, no revela errores y no degrada con 100 citas — `tests/Modules/Appointments/Integration/PortalNoClinicalDataTest.php`, `tests/Modules/Appointments/Integration/PortalUnexpectedErrorTest.php`, `tests/Modules/Appointments/Integration/PortalPerformanceTest.php` — hecho cuando: los tests pasan: con un seguimiento cuyos motivo, síntomas, diagnóstico, procedimiento y observaciones llevan marcas únicas, ninguna respuesta del portal las contiene, ni el teléfono, el correo o el nombre de otro paciente; repositorio de citas, repositorio de seguimiento y generador de QR que lanzan → `{"error": "Internal server error"}` en cada ruta; con 100 citas, la lista y la próxima cita usan un número de consultas constante — cubre: CA17, CA19 — depende: T017
  - nota: A12 — TM11 (carga) se apoya además en el test existente de `throttle:api`; esta tarea prueba que las consultas no crecen con las citas.

### Historia 4: pantallas del portal
- [ ] T020 Crear la pantalla `/mis-citas` (ruta `portal.appointments` con `auth:sanctum,patient` + `patient`), con su encabezado y `noindex` — `routes/web.php`, `resources/views/pages/portal/appointments.blade.php`, `resources/views/layouts/dashboard.blade.php`, `tests/Modules/Core/Integration/PortalPagesTest.php`, `tests/Modules/Core/Integration/GuestRedirectTest.php` — hecho cuando: los tests pasan (incluido "covers every web route that asks for a session" con `/mis-citas` en la lista): paciente activo → 200 con `x-ui.page-hero` "Mis citas", la insignia "Mi cuenta" y `<meta name="robots" content="noindex">`; paciente inactivo y personal → 403 con "Sin permiso"; sin sesión → `/login`; `sitemap.xml` no la lista; las demás pantallas del panel no llevan la etiqueta nueva — cubre: CA11, CA15, CA16 — depende: T004
  - nota: A14 — no existe `sitemap.xml`: la comprobación del sitemap se sustituye por la de `noindex` mientras no exista.
  - nota: A16 — la vista hereda de `layouts.patient`; `PortalPagesTest` comprueba el 200 y el menú del paciente.
- [ ] T021 Cerrar el inicio a un paciente inactivo y ofrecer "Mis citas" en el menú del paciente — `app/Core/Http/ShowDashboardPageController.php`, `resources/views/components/ui/sidebar.blade.php`, `resources/views/pages/dashboard.blade.php`, `tests/Modules/Core/Integration/PortalPagesTest.php` — hecho cuando: los tests pasan: paciente inactivo en `/dashboard` → 403 con "Sin permiso"; el menú del paciente tiene "Inicio", "Mis citas" y "Cerrar sesión" y ningún enlace a agenda, pacientes, expedientes, usuarios, contenido, tratamientos ni reportes; el inicio del paciente lleva `noindex`; el menú del personal no tiene "Mis citas" — cubre: CA10, CA15 — depende: T020
- [ ] T022 Extraer el bloque del QR a `x-appointments.qr-block` sin cambiar el detalle del personal — `resources/views/components/appointments/qr-block.blade.php`, `resources/views/components/calendar/view-appointment-modal.blade.php`, `tests/Modules/Core/Integration/UiComponentsTest.php` — hecho cuando: el test del componente pasa (marco, imagen de 240 × 240 px, aviso de error y "Descargar QR", con título y texto configurables) y `ui-audit` de las entradas del detalle del personal (agenda, inicio y expediente) pasa sin tocar sus expectativas — cubre: CA7 — depende: T001
- [ ] T023 Extraer la lógica del QR y el dibujo de recetas a módulos compartidos — `resources/js/ui/appointment-qr.js`, `resources/js/ui/prescriptions.js`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: `npm run build` pasa y `ui-audit` de las entradas del detalle del personal (QR, descarga, fallo del QR y cita completada con recetas) pasa sin tocar sus expectativas — cubre: CA5, CA7 — depende: T022
- [ ] T024 Extraer el dibujo del historial a un módulo compartido y hacer configurables el título y el texto vacío de la tabla — `resources/js/ui/appointments-history.js`, `resources/js/pages/records/index.js`, `resources/views/components/records/appointments-history-table.blade.php`, `tests/Modules/Core/Integration/UiComponentsTest.php` — hecho cuando: `npm run build` pasa, `UiComponentsTest` comprueba que sin parámetros la tabla conserva "Historial de citas" y "Este paciente aún no tiene citas registradas." y `ui-audit` del expediente pasa sin tocar sus expectativas — cubre: CA3, CA4 — depende: T025
- [ ] T025 Dar a `x-dashboard.next-appointment` un slot `actions` cuyo valor por defecto son los botones actuales — `resources/views/components/dashboard/next-appointment.blade.php`, `tests/Modules/Core/Integration/UiComponentsTest.php` — hecho cuando: el test pasa (sin slot, el marcado es el de hoy; con slot, sus botones sustituyen a los del personal) y `ui-audit` del inicio del personal pasa sin tocar sus expectativas — cubre: CA1 — depende: T022
- [ ] T026 [P] Sembrar las citas de la cuenta `paciente` de `ui-audit`: una asignada futura, una reprogramada, una completada con recomendaciones y dos recetas, una completada sin seguimiento y una cancelada — `database/seeders/UiAuditSeeder.php`, `tests/Modules/Core/Integration/UiAuditSeederTest.php` — hecho cuando: el test pasa, la cuenta `paciente.vacio` sigue sin citas y `ui-audit` completo sigue en verde — cubre: CA1, CA3, CA5 — depende: T001
- [ ] T027 [P] Dar a `npm run test:ui` comprobaciones para entradas de pantalla: elementos dentro de la primera pantalla, tamaño mínimo de una imagen y CLS máximo, declaradas en `screens.json` — `tests/Browser/checks-screens.mjs`, `tests/Browser/audit-lib.mjs`, `tests/Browser/screens.json` — hecho cuando: una entrada de prueba falla con un elemento fuera de la primera pantalla, con una imagen menor que la pedida y con un salto de CLS > 0.1, y pasa sin ellos; `ui-audit` completo sigue en verde — cubre: CA1 — depende: T001
  - nota: A22 — el fallo de cada comprobación nueva se demuestra con una entrada temporal que no se queda en `screens.json`; el resultado se anota bajo esta tarea.
- [ ] T030 Construir el inicio del paciente según c3: saludo, "Próxima cita" con la tarjeta, el bloque del QR con "Descargar QR" y "Ver detalle", los accesos "Mis citas" y "¿Necesitas cambiar tu cita?", el estado vacío y los avisos de fallo — `resources/views/pages/dashboard.blade.php`, `resources/js/pages/portal/home.js`, `vite.config.js`, `tests/Browser/screens.json`, `tests/Modules/Core/Integration/RoleCopyTest.php`, `tests/Modules/Core/Integration/DashboardScreenTest.php` — hecho cuando: `RoleCopyTest` y `DashboardScreenTest` pasan con el inicio de c3 (sin insignia ni rol en el encabezado del paciente; el rol sigue en el menú) y `ui-audit` pasa en `portal-inicio` (fecha, hora, QR de al menos 240 × 240 px y "Descargar QR" dentro de la primera pantalla y CLS ≤ 0.1, con las comprobaciones de T027), `portal-inicio-vacio` (con la próxima cita simulada como `null`: "Aún no tienes una cita agendada" y "Contacta a la clínica" hacia `/contacto`, sin QR), `portal-inicio-fallo-qr` y `portal-inicio-fallo-cita` (aviso en su lugar, el resto igual) y `portal-menu` · verificación manual: comparar con `c3.html` y `c3-vacio.html` a 390, 768 y 1440 px — cubre: CA1, CA2, CA9, CA10 — depende: T014, T015, T021, T023, T025, T026, T027
  - nota: A21 — los estados vacíos usan respuestas simuladas, no la cuenta `paciente.vacio` que menciona el plan.
- [ ] T031 Construir "Mis citas" según c3: historial con tarjetas bajo 768 px y tabla desde 768 px, "Aún no tienes citas registradas" y aviso de fallo — `resources/views/pages/portal/appointments.blade.php`, `resources/js/pages/portal/appointments.js`, `vite.config.js`, `tests/Browser/screens.json` — hecho cuando: `ui-audit` pasa en `portal-mis-citas` (cuatro estados, orden de la más reciente a la más antigua, sin desborde) y `portal-mis-citas-vacio` (con la lista simulada como `[]`) · verificación manual: comparar con `c3-citas.html` a 390, 768 y 1440 px — cubre: CA3, CA4, CA9 — depende: T011, T020, T024, T026, T030
- [ ] T032 Construir el detalle de la cita del paciente (diálogo) y abrirlo desde el inicio y desde "Mis citas" — `resources/views/components/portal/appointment-detail-modal.blade.php`, `resources/js/pages/portal/appointment-detail.js`, `vite.config.js`, `tests/Browser/screens.json` (más su inclusión en `pages/dashboard.blade.php` y `pages/portal/appointments.blade.php`) — hecho cuando: `ui-audit` pasa en `portal-detalle-abierta` (QR y "Descargar QR"), `portal-detalle-completada` (recomendaciones y recetas, sin QR), `portal-detalle-sin-recetas` ("Esta cita no tiene recetas", sin apartado de recomendaciones ni aviso de error), `portal-detalle-cancelada` (sin QR ni recetas) y `portal-detalle-fallo` (aviso en el lugar de las recetas o del QR); el foco entra al diálogo y vuelve al botón que lo abrió · verificación manual: comparar con `c3-detalle.html` y `c3-detalle-qr.html` a 390, 768 y 1440 px; recorrer el detalle con teclado; escanear con un teléfono el QR de la pantalla y el PNG descargado — cubre: CA5, CA6, CA7, CA8, CA9 — depende: T017, T031
  - nota: A19 — entrada `portal-detalle-sin-recetas` con recomendaciones: muestra "Recomendaciones" y "Esta cita no tiene recetas".

## Cierre
- [ ] T050 Verificación completa: `AppointmentsArchitectureTest` extendido a los archivos nuevos de `Aplication/`, Pest en serie, `vendor/bin/pint --dirty --format agent`, `npm run build`, `npm run test:ui` completo, `grep -rn "getMessage()"` y `grep -rn "Log::"` en los archivos nuevos, `python .ai/bin/aidd.py validate` — `tests/Modules/Appointments/Unit/AppointmentsArchitectureTest.php` — hecho cuando: todo pasa, ningún controlador nuevo devuelve `getMessage()`, no hay `Log::` nuevos y los resultados quedan anotados bajo esta tarea · verificación manual: revisar que los textos del portal están en español, en tuteo y sin términos técnicos — depende: T018, T019, T032
  - nota: A12 — medir a mano, en local, la carga de la lista y de la próxima cita con 100 citas (objetivo < 2 s) y anotar el tiempo.
- [ ] T051 [P] Actualizar los documentos de seguridad, SEO, diseño y proyecto — `docs/security.md` (RS1.b → en curso (spec 025), RS1.c → en curso (spec 025); "Superficie de ataque" con las cuatro rutas del portal), `docs/seo.md` (`/mis-citas` en el inventario como no indexable), `docs/design/system.md` y `docs/design/system.html` (bloque del QR como componente; historial con título y texto vacío configurables), `AGENTS.md` (el portal ya tiene pantallas), `CHANGELOG.md` (`[Unreleased]`) — hecho cuando: los documentos reflejan lo implementado y `aidd.py validate` no da errores — depende: T050

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: autenticación con dos guards (`sanctum` y `patient`) y los middlewares `staff` / `patient`; grupo `/api/v1/portal`; piezas de interfaz compartidas (`x-appointments.qr-block`, `ui/appointment-qr.js`, `ui/prescriptions.js`, `ui/appointments-history.js`)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `observability.md`, los puntos de emisión de la 025 para el objetivo 5 (lectura de recetas, obtención del QR y accesos denegados). Sin cambios en `deployment.md`
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097, con cuentas de prueba. Como paciente: el inicio muestra la próxima cita con su QR, "Descargar QR" baja un PNG que un teléfono lee como el identificador de la cita, "Mis citas" lista el historial y el detalle de una completada muestra sus recetas (CA1, CA3, CA5, CA7). Como administrador: la agenda y el detalle con QR siguen igual (CA14, CA16). En Loki, sin 500 en `/api/v1/portal/*` y sin aumento de 401 en el resto de la API.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T013, T014, T027, T030 | T013, T014, T025, T026, T027, T030 |
| CA2 | T013, T014, T030 | T013, T014, T030 |
| CA3 | T010, T011, T031 | T010, T011, T024, T031 |
| CA4 | T011, T031 | T011, T024, T031 |
| CA5 | T016, T017, T032 | T016, T017, T023, T032 |
| CA6 | T016, T017, T032 | T016, T017, T032 |
| CA7 | T015, T032 | T015, T022, T023, T032 |
| CA8 | T016, T017, T032 | T016, T017, T032 |
| CA9 | T030, T031, T032 | T030, T031, T032 |
| CA10 | T002, T021, T030 | T002, T021, T030 |
| CA11 | T011, T014, T015, T017, T020 | T011, T014, T015, T017, T020 |
| CA12 | T015, T016, T017 | T015, T016, T017 |
| CA13 | T005, T010, T011, T014 | T005, T010, T011, T014 |
| CA14 | T002, T018 | T002 |
| CA15 | T004, T005, T011, T018, T020, T021 | T004, T005, T020, T021 |
| CA16 | T002, T004, T011, T018, T020 | T002, T004, T020 |
| CA17 | T010, T016, T019 | T010, T016 |
| CA18 | T015 | T015 |
| CA19 | T019 | T011, T014, T015, T017 |
| CA20 | T004, T011, T014, T015, T017 | T004, T011 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T011, T014, T015, T017 | T011, T014, T015, T017 |
| TM2 | T015, T016 | T015, T016, T017 |
| TM3 | T010, T013 | T011, T014 |
| TM4 | T002 | T002, T018 |
| TM5 | T002 | T018 |
| TM6 | T004, T005, T021 | T004, T005, T011, T020, T021 |
| TM7 | T010, T016 | T010, T016, T019 |
| TM8 | T015 | T015 |
| TM9 | T011, T014, T015, T017 | T019 |
| TM10 | T004, T011 | T004, T011 |
| TM11 | T010, T013 | T019 |
| TM12 | — (riesgo aceptado, plan R4) | — |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Configuración | T002 |
| Core · Middlewares | T004 |
| Core · Authorization | T005 |
| Core · Http | T021 |
| `bootstrap/app.php` | T004 |
| Rutas | T002, T011, T014, T015, T017, T020 |
| Vistas que leen el guard | T002 |
| Appointments · Aplication | T010, T013, T015 |
| Appointments · Infrastructure | T010, T011, T014, T015 |
| AppointmentTracking · Application | T016 |
| AppointmentTracking · Infrastructure | T016, T017 |
| Interfaz · piezas compartidas | T022, T023, T024, T025 |
| Interfaz · portal | T020, T021, T030, T031, T032 |
| Pruebas de navegador | T026, T027, T030, T031, T032 |
| Tests de apoyo | T002 |
| Docs | T051, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA y `npm run test:ui` a 390, 768 y 1440 px | T030, T031, T032 (ui-audit y recorrido con teclado), T050 |
| 100 citas en menos de 2 s | T019, T050 (medida a mano) |
| Textos en español, en tuteo y sin términos técnicos | T030, T031, T032 (ui-audit), T050 (verificación manual) |
| El portal no aparece en buscadores | T020, T021 |
| Sin datos de salud ni de contacto en logs o errores | T019, T050 |
| K1–K6 de "Criterios de diseño" | T027 (comprobaciones de K1 y K6), T030 (K1, K2, K5, K6), T031 (K2, K3), T032 (K4) |
