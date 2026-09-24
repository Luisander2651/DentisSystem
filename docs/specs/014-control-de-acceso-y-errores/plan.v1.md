---
spec: 014-control-de-acceso-y-errores
status: approved
created: 2026-09-24
---

# Plan · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

## Enfoque técnico
La autorización se aplica en dos capas, como pide P5. La primera es un middleware nuevo de
`app/Core`, `EnsureActiveStaff` (alias `staff`), que deja pasar solo a staff activo y, opcionalmente,
de ciertos roles. Cubre los grupos de rutas de pacientes, agenda y citas en la API y las vistas de
expedientes en la web. La segunda es `assertCan()` en **cada** caso de uso de esas rutas, con un mapa
de permisos por rol en `CurrentActorAuthorizationService`; hoy ese servicio solo concede permisos al
administrador. Ese mapa es la única fuente de verdad de la tabla "Permisos esperados": el middleware
filtra por tipo de actor y estado, y el caso de uso decide por operación.

Los errores inesperados se corrigen controlador por controlador con un helper común de `app/Core`,
que registra el error sin datos sensibles y responde `{"error": "Internal server error"}`. Además,
`bootstrap/app.php` recibe una red de seguridad para lo que no se captura en los controladores: 500
genérico, `AuthorizationException` → 403 y 401 en JSON en `api/*`.

El frontend solo cambia para dejar de ofrecer lo que el servidor rechaza: menú lateral, panel de
inicio y expedientes en solo lectura para el doctor.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec 014 `approved` (2026-09-24). |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y TM tiene un test de integración (Trazabilidad). Los tests de acceso denegado fallan hoy porque las rutas aceptan cualquier token. No hay value objects nuevos, así que no hacen falta tests de propiedades. |
| P3 Capas del módulo | ✅ | El middleware y el helper viven en `app/Core`. Los permisos se comprueban en los casos de uso (`assertCan` al inicio de `execute`), no en los controladores. No se añaden dependencias entre módulos: `GetPatientsForAppointmentSelectController` ya usaba el caso de uso de Patients y no cambia. |
| P4 Contrato de API primero | ✅ | Los códigos por actor y el cuerpo de 401, 403 y 500 están en "Contratos y datos". No hay cambios incompatibles para el cliente autorizado: el administrador recibe las mismas respuestas (CA1), así que no hace falta `/api/v2`. |
| P5 Autorización en el servidor, por rol y por recurso | ✅ | Middleware `staff` más `assertCan` por rol. Los tests de acceso denegado cubren cada actor no autorizado y cada ruta (dataset). La comprobación de pertenencia no aplica: ningún paciente tiene acceso a estas operaciones (supuesto de la spec). Llegará con el portal del paciente. |
| P6 Validación de entrada con FormRequest | ➖ | La spec no cambia la entrada de ningún endpoint: solo su autorización y su respuesta ante errores. Los endpoints que hoy no usan FormRequest siguen como código previo (deuda). P6 se exige cuando una spec cambie su entrada. |
| P7 Errores sin detalles internos | ✅ | Los 48 controladores pasan al helper `UnexpectedErrorResponse`. La red global en `withExceptions` cubre los controladores sin try/catch. Hay tests que fuerzan el error en cada área (CA12) y una búsqueda de `getMessage()` en respuestas. |
| P8 Secretos fuera del repositorio | ➖ | No hay variables ni secretos nuevos. |
| P9 Migraciones reversibles y compatibles | ➖ | No hay migraciones. |
| P10 Dependencias nuevas con ADR | ➖ | No hay dependencias nuevas. |
| P11 Datos sensibles protegidos y modelo de amenazas | ✅ | Modelo de amenazas abajo. El registro de errores omite el mensaje de las excepciones de base de datos, que llevan los valores de la consulta, y nunca registra el cuerpo de la petición (CA13, TM6). |
| P12 Producción con aprobación humana, rollback y sin vulnerabilidades altas | ➖ | No hay entorno de producción. Rollback documentado en Rollout. Este plan **reduce** los riesgos 1 y 3 de security.md. |
| P13 Lógica de negocio en el backend | ✅ | Toda restricción se comprueba en el servidor. El frontend solo oculta lo que el servidor ya rechaza (CA4, CA15), y los tests llaman a la API directamente, sin pasar por la UI. |
| P14 Trazabilidad: correlación y auditoría | ❌ aceptado: el log de los 500 no lleva `request_id` hasta la spec de auditoría (objetivo 5) — aprobado por el usuario el 2026-09-24 | La spec no define eventos de auditoría (sección "Auditoría": diferidos a la spec del objetivo 5), así que no se emite ninguno. En cambio, el `Log::error` nuevo de los 500 **no lleva `request_id`**, porque la correlación aún no existe (brecha 3 de observability.md). Alternativa más cercana: añadir aquí el middleware de `X-Request-Id`, que es infraestructura del objetivo 5 y ampliaría la spec. Propuesta: aceptar la desviación. El helper `UnexpectedErrorResponse` deja un solo punto donde añadir el `request_id` cuando exista. |

Restricciones: las pantallas modificadas (menú lateral, panel de inicio y expedientes) mantienen
WCAG 2.1 AA. No se añaden controles nuevos: solo se quitan o se ocultan. Ocultar un elemento no deja
huecos de foco. Revisar con la skill `design` antes de `/implement` (está en `skills.enabled`).

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Core | **Nuevo** `app/Core/Middlewares/EnsureActiveStaff.php`: exige `UserModel` con `status = active` y, si recibe parámetros, uno de esos roles (`staff:administrador,asistente,doctor`). En `api/*` responde 403 `{"error"}`; en web, `abort(403)`. | Medio: si se aplica a una ruta que no toca, bloquea al administrador. Mitigado por CA1 (dataset de todas las rutas como admin). |
| Core | `app/Core/Authorization/CurrentActorAuthorizationService.php`: pasa de una lista `$adminPermissions` a un mapa `permiso → roles`. Los permisos nuevos están en "Contratos y datos"; los que ya existían siguen siendo solo del administrador. El actor inactivo o no staff sigue siendo rechazado primero. | Alto: es la fuente única de permisos. Tests de matriz completa (CA1–CA8). |
| Core | **Nuevo** `app/Core/Http/UnexpectedErrorResponse.php`: `::from(Throwable $e, string $origin): JsonResponse`. Registra `Log::error('unexpected_error', ['origin', 'exception', 'file', 'line'])` y añade `message` solo si la excepción **no** es `QueryException` ni `PDOException`. Responde 500 `{"error": "Internal server error"}`. | Bajo. |
| bootstrap | `bootstrap/app.php`: alias `staff`. En `withExceptions`: (1) `shouldRenderJsonWhen` para `api/*`, de modo que el 401 sea JSON y no una redirección a `/login`; (2) `AuthorizationException` de Core → 403 `{"error"}` en `api/*`; (3) cualquier otra excepción que no sea HTTP ni de validación en `api/*` → `UnexpectedErrorResponse`. | Medio: no debe alterar 404, 405, 422 ni 429 del framework. Test de regresión con un 422 de FormRequest y un 429. |
| routes | `routes/api.php`: los grupos `agenda/*`, `patients/*` y `appointments/*` bajo `auth:sanctum` pasan a `['auth:sanctum', 'staff']`. Las rutas bajo `only.admin` no cambian. | Medio: cobertura del dataset de rutas. |
| routes | `routes/web.php`: `/expedientes-clinicos` y `/expedientes-clinicos/{patientId}` pasan del closure con comprobación de rol a `middleware('staff:administrador,asistente,doctor')`. Añade el doctor y exige cuenta activa. | Bajo. |
| Patients | Casos de uso sin `assertCan` → añadir: `GetPatientsByStatusUseCase` y `GetPatientByIdUseCase` (`patients.view`); `GetPatientRecordByPatientIdUseCase` (`patients.record.view`); `UpdatePatientUseCase` (`patients.update`); `Save/Update/Delete{Address,ContactInfo,MedicalData}UseCase` (`patients.clinical-data.manage`). Inyectan `AuthorizationServiceInterface`, como `SavePatientUseCase`. | Medio: `GetPatientsByStatusUseCase` también lo usa el selector de la agenda (`GetPatientsForAppointmentSelectController`), que queda en `patients.view`. El selector se protege aparte, en su controlador, con `agenda.selectors.view` (ver Decisiones). |
| Patients | 15 controladores (`app/Modules/Patients/Infrastructure/Http/Controllers/**`): `catch (AuthorizationException)` → 403 antes del catch genérico; el catch genérico pasa a `UnexpectedErrorResponse::from($e, self::class)`. Los catch de negocio (400, 404, 409) no cambian (CA14). | Bajo. |
| Appointments | Casos de uso: `CreateAppointmentUseCase` (`appointments.create`), `UpdateAppointmentUseCase` (`appointments.update`), `GetAppointmentByIdUseCase` (`appointments.view-detail`), `GetAppointentByPatientIdUseCase` (`appointments.patient-history.view`). Controladores de los selectores (`GetPatientsForAppointmentSelectController`, `GetDoctorsForAppointmentSelectController`): `assertCan('agenda.selectors.view')` a través de un caso de uso nuevo y fino, `AuthorizeAgendaSelectorsUseCase`, para no poner lógica en el controlador. | Medio: `GetDoctorsForAppointmentSelectController` no tiene hoy caso de uso. Ver Decisiones D5. |
| Appointments | 11 controladores con el 500 filtrado → helper. `GetTodayAppointmentsController`, `GetTreatmentsController` y los selectores (sin try/catch) → `catch (AuthorizationException)` → 403, más el helper (CA10). | Bajo. |
| AppointmentTracking | 6 controladores → helper y `catch (AuthorizationException)` → 403 donde falte. Sin cambios de permisos: todo sigue solo para el administrador. | Bajo. |
| ContentManagement | 16 controladores (`app/Modules/ContentManagement/Modules/*/Infrastructure/HTTP/Controllers/**`) → helper. Sin cambios de permisos. | Bajo. Módulo de la Unidad 5 de AI-DLC, en pausa: solo se toca el camino del 500 (decisión del usuario, 2026-09-24). |
| Frontend | `resources/views/components/ui/sidebar.blade.php`: `roleTabs` → asistente `['inicio', 'expedientes']`, doctor `['inicio', 'expedientes']` (nuevo), paciente `['inicio']`. | Bajo. |
| Frontend | `resources/views/pages/dashboard.blade.php`: `'doctor' => 'layouts.admin'` en `$layouts`. Rama nueva `@elseif doctor` con acceso a "Expedientes". Rama asistente: se quitan "Ver Agenda" y "Registrar Nuevo Paciente". El modal `create-patient-modal` y `create-patient.js` pasan a cargarse solo para el administrador. Rama paciente: se quitan los enlaces a `/expedientes-clinicos` y `/agenda`. | Medio: la rama `@else` la comparten hoy doctor y paciente. |
| Frontend | `resources/views/pages/records/index.blade.php`: `$canEdit = in_array($sidebarRole, ['administrador', 'admin', 'asistente'])` → atributo `data-records-can-edit` en el contenedor; los botones de apertura de formularios se renderizan solo con `$canEdit`. `resources/js/pages/records/index.js`: lee `data-records-can-edit` y no genera los botones "Eliminar" ni los de edición si es `false`. | Medio: los botones "Eliminar" se generan en JS (`index.js:385`, `:410`, `:435`) y no hay runner de tests de JS. Ver Estrategia de pruebas. |
| Tests | Actualizar los tests que hoy autentican con cualquier actor: `PatientCrudTest`, `AddressTest`, `ContactInfoTest`, `MedicalDataTest`, `PatientRecordTest`, `CreateAppointmentTest`, `UpdateAppointmentTest` y `GetAppointmentsTest` pasan a un actor con el rol correcto. | Medio: pueden esconder otras dependencias del actor genérico. |

## Contratos y datos
Sin cambios de modelo de datos ni migraciones. No cambia ningún request ni la forma de las
respuestas 2xx.

**Respuestas comunes (todas las rutas de la tabla):**
- Sin sesión → `401 {"message": "Unauthenticated."}`, cuerpo por defecto de Laravel, ahora también cuando la petición no manda `Accept: application/json`.
- Paciente, staff inactivo o rol sin permiso → `403 {"error": "<motivo>"}`. El motivo no revela datos del recurso.
- Error inesperado → `500 {"error": "Internal server error"}`, sin `message`, traza ni SQL.
- Errores de negocio sin cambios (CA14): 400, 404, 409 y 422 con su mensaje actual.

**Permisos (mapa de `CurrentActorAuthorizationService`):**

| Permiso | Rutas | Admin | Asistente | Doctor |
|---|---|---|---|---|
| `patients.view` (nuevo) | `GET /patients`, `GET /patients/{id}` | ✓ | ✓ | ✓ |
| `patients.record.view` (nuevo) | `GET /patients/{patientId}/record` | ✓ | ✓ | ✓ |
| `appointments.patient-history.view` (nuevo) | `GET /appointments/patient/{patientId}` | ✓ | ✓ | ✓ |
| `appointments.view-detail` (nuevo) | `GET /appointments/{id}` | ✓ | ✓ | ✓ |
| `patients.clinical-data.manage` (nuevo) | `POST/PUT/DELETE /patients/{patientId}/{address,contact-info,medical-data}` | ✓ | ✓ | — |
| `patients.update` (nuevo) | `PUT /patients/{id}` (incluye contraseña) | ✓ | — | — |
| `patients.create`, `patients.delete` | `POST /patients`, `DELETE /patients/{id}` (ya bajo `only.admin`) | ✓ | — | — |
| `appointments.view` | `GET /appointments`, `GET /agenda/today-appointments` | ✓ | — | — |
| `appointments.create` (nuevo), `appointments.update` (nuevo), `appointments.delete` | `POST /appointments`, `PUT /appointments/{id}`, `DELETE /appointments/{id}` | ✓ | — | — |
| `agenda.selectors.view` (nuevo) | `GET /agenda/patients`, `GET /agenda/doctors` | ✓ | — | — |
| `treatments.view` | `GET /agenda/treatments` | ✓ | — | — |

`appointments.update` cubre también el cambio de estado, que va por `PUT /appointments/{id}`. Los
permisos de usuarios, catálogo, contenido y seguimiento clínico no cambian.

**Web:** `GET /expedientes-clinicos[/{patientId}]` → 200 para administrador, asistente y doctor
activos; 403 para el resto con sesión; redirección a `/login` sin sesión (comportamiento actual).

**Contrato entre capas:** no hay tipos compartidos. El contrato vive en esta sección y en los tests
de acceso (dataset), que fijan cada código por actor.

## Estrategia de pruebas
- **Integración (Pest, `tests/Modules/<Módulo>/Integration`):**
  - `Patients/Integration/PatientsAccessControlTest.php` (nuevo): dataset de las 15 rutas de pacientes × 6 actores (admin, asistente, doctor, staff inactivo, paciente, sin sesión), con el código esperado según la tabla de permisos. Afirma que en los rechazos no cambió la base de datos (`assertDatabaseHas` o `assertDatabaseMissing` sobre el recurso).
  - `Appointments/Integration/AppointmentsAccessControlTest.php` (nuevo): el mismo patrón para las 10 rutas de `agenda/*` y `appointments/*` bajo `staff`, más `GET /appointments/{id}/tracking` como doctor y asistente (TM9).
  - `Patients/Integration/UnexpectedErrorTest.php`, `Appointments/Integration/UnexpectedErrorTest.php` (citas y catálogo), `AppointmentTracking/Integration/UnexpectedErrorTest.php` y `ContentManagement/Integration/UnexpectedErrorTest.php` (nuevos): enlazan en el contenedor un repositorio que lanza `RuntimeException('detalle interno')` o `QueryException` y afirman el cuerpo exacto `{"error": "Internal server error"}` sin `message`. `ContentManagement` necesita una base nueva, `ContentManagementIntegrationTestCase`.
  - Log sin datos sensibles (CA13): `Log::spy()` y una `QueryException` cuyos bindings llevan un teléfono y un dato médico de prueba. Se afirma que ninguna llamada a `Log::error` los contiene y que sí registra `exception` y `origin`.
  - Red global de `withExceptions`: excepción no capturada en `GetTodayAppointmentsController` → 500 genérico; `AuthorizationException` no capturada → 403; un 422 de FormRequest y un 429 conservan su forma.
  - `Patients/Integration/RecordsScreenTest.php` (nuevo, web): `/expedientes-clinicos` como doctor → 200 con `data-records-can-edit="false"` y sin botones de formulario; como asistente → `true`; paciente e inactivo → 403.
  - `Users/Integration/StaffNavigationTest.php` (nuevo, web): `/dashboard` y menú lateral por rol, con `assertSee` y `assertDontSee` de `href="/agenda"`, `href="/expedientes-clinicos"` y `data-create-patient-open`.
- **Matriz de permisos:** `Users/Integration/RolePermissionsTest.php` (nuevo) sobre `CurrentActorAuthorizationService`: cada permiso de la tabla × cada rol × activo o inactivo. Es de integración porque lee `UserModel` con su rol de la base.
- **Regresión:** los tests existentes de Patients y Appointments, actualizados a actores con rol, siguen verdes, y con ellos los mensajes de negocio (CA14). `UsersAuthorizationTest` y `RateLimitAndErrorLeakTest` no cambian.
- **JS sin runner:** la ocultación de los botones "Eliminar" y de edición en `records/index.js` se comprueba a mano como doctor (paso de verificación en `tasks.md`). El servidor ya los rechaza (CA7), así que un fallo aquí es cosmético, no de seguridad.
- **Estáticas:** `grep -rn "'message' => \$e->getMessage()" app/Modules/*/Infrastructure/*/Controllers` sin resultados en respuestas JSON, y `vendor/bin/pint --dirty`.

## Modelo de amenazas
Flujo: navegador (cookie `auth_token`) → `InjectSanctumTokenFromCookie` → `auth:sanctum`, que acepta
tokens de `UserModel` **y** de `PatientModel` → `staff` → controlador → caso de uso (`assertCan`) →
repositorio → PostgreSQL. Salida: respuesta JSON y `storage/logs/laravel.log`.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un paciente autorregistrado usa su token para leer o cambiar datos de salud de otros pacientes o crear citas ajenas | A01:2025 | `routes/api.php` (grupos bajo solo `auth:sanctum`) | Middleware `staff` rechaza cualquier actor que no sea `UserModel`; `assertCan` también lo rechaza (defensa en profundidad) | `PatientsAccessControlTest` y `AppointmentsAccessControlTest`, actor paciente → 403 sin cambios en la BD |
| TM2 | Elevation: asistente o doctor ejecutan operaciones fuera de su rol (agenda, crear o eliminar pacientes, editar datos clínicos siendo doctor) | A01:2025 | Casos de uso de Patients y Appointments | Mapa permiso → roles en `CurrentActorAuthorizationService`, denegado por defecto | Datasets de acceso (asistente y doctor → 403) y `RolePermissionsTest` |
| TM3 | Tampering / Spoofing: un asistente cambia la contraseña de un paciente para suplantarlo | A01:2025, A07:2025 | `PUT /patients/{id}` | `patients.update` solo para el administrador | `PatientsAccessControlTest`: `PUT` con `password` como asistente o doctor → 403 y el hash no cambia |
| TM4 | Spoofing / Elevation: un exempleado desactivado sigue usando un token vigente | A07:2025 | `EnsureActiveStaff`, `CurrentActorAuthorizationService` | Ambos comprueban `status = active` en cada petición | Datasets, actor inactivo → 403 en todas las rutas |
| TM5 | Spoofing: acceso sin sesión | A07:2025 | `auth:sanctum` | 401 JSON forzado en `api/*` | Datasets, actor sin sesión → 401 JSON |
| TM6 | Information disclosure: el 500 revela el mensaje técnico, la traza, el SQL o nombres internos | A10:2025 | 48 controladores; controladores sin try/catch; `APP_DEBUG=true` por defecto | `UnexpectedErrorResponse` más la red global en `withExceptions` para `api/*`, que no depende de `APP_DEBUG` | `UnexpectedErrorTest` de cada área y el test de la red global |
| TM7 | Information disclosure: el log del error guarda datos de contacto o de salud (bindings del SQL) | A09:2025 | `UnexpectedErrorResponse` | No registra `message` de `QueryException` ni `PDOException`; nunca registra el request | `UnexpectedErrorTest` (Patients) con `Log::spy` |
| TM8 | Elevation por UI: confiar en que el menú oculto impide la acción | A06:2025 | Menú lateral, panel de inicio y expedientes | Toda restricción se aplica en el servidor; la UI solo refleja | Los datasets llaman a la API sin pasar por la UI; `StaffNavigationTest` y `RecordsScreenTest` para lo visible |
| TM9 | Information disclosure: asistente o doctor obtienen el seguimiento clínico y las recetas desde el detalle de una cita, que ahora pueden ver | A01:2025 | `GET /appointments/{id}/tracking` | Sigue bajo `only.admin` y `appointment-tracking.view` solo de administrador; el modal no lo pide si no es admin (`view-appointment.js:213`) | `AppointmentsAccessControlTest`: tracking como asistente o doctor → 403 |
| TM10 | DoS lógico / fallo abierto: un error de autorización se convierte en 500 (hoy `GetTodayAppointmentsController`) y confunde al cliente o enmascara ataques | A10:2025 | Controladores sin try/catch | `AuthorizationException` → 403 en el controlador y en la red global | Test de CA10 |

Repudio (acceso denegado sin rastro): riesgo residual aceptado por la spec. Se resuelve en la spec
de auditoría (objetivo 5). Ver Riesgos.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el administrador conserva todas las operaciones | Core (mapa de permisos), routes | Datasets de acceso, actor admin → mismo código que hoy |
| CA2: el asistente lista y ve pacientes, abre expedientes, ve historial y detalle de cita y gestiona datos clínicos | Core, Patients, Appointments | Datasets, actor asistente → 2xx |
| CA3: el doctor lista y ve pacientes, abre expedientes y ve historial y detalle de cita | Core, Patients, Appointments | Datasets, actor doctor → 2xx |
| CA4: el doctor entra a expedientes en solo lectura, con acceso en el menú y en el inicio | routes/web, Frontend (records, sidebar, dashboard) | `RecordsScreenTest` (doctor), `StaffNavigationTest` (doctor) y verificación manual del JS |
| CA5 (abuso): el paciente es rechazado en todas las operaciones | Core (`EnsureActiveStaff`), routes | Datasets, actor paciente → 403 sin cambios en la BD (TM1) |
| CA6 (abuso): el asistente es rechazado fuera de su rol | Core (mapa), Patients, Appointments | Datasets, actor asistente → 403 (TM2, TM3) |
| CA7 (abuso): el doctor es rechazado en las escrituras y en la agenda | Core (mapa), Patients, Appointments | Datasets, actor doctor → 403 (TM2, TM3) |
| CA8 (abuso): el staff inactivo es rechazado | Core | Datasets, actor inactivo → 403 (TM4) |
| CA9 (abuso): el visitante sin sesión recibe 401 | bootstrap (`shouldRenderJsonWhen`) | Datasets, sin sesión → 401 JSON (TM5) |
| CA10 (abuso): citas del día como asistente o doctor → 403, no 500 | Appointments (`GetTodayAppointmentsController`), bootstrap | `AppointmentsAccessControlTest`: `today-appointments` → 403 (TM10) |
| CA11: el admin cambia la contraseña; asistente y doctor no | Core (`patients.update`), Patients | `PatientsAccessControlTest`: admin → 200 y hash cambiado; asistente y doctor → 403 y hash intacto (TM3) |
| CA12 (abuso): el error inesperado responde genérico en todas las áreas | Core (helper), 48 controladores, bootstrap | `UnexpectedErrorTest` de Patients, Appointments (citas y catálogo), AppointmentTracking y ContentManagement, y el test de la red global (TM6) |
| CA13: el log del error no contiene datos de salud ni de contacto | Core (helper) | `Patients/Integration/UnexpectedErrorTest`, `Log::spy` con `QueryException` (TM7) |
| CA14: se conservan los mensajes de negocio | Controladores (catch de negocio intactos) | Tests existentes de 400, 404 y 409 en `PatientCrudTest`, `AddressTest`, `CreateAppointmentTest` y `UpdateAppointmentTest`, verdes tras el cambio de actor |
| CA15: menú e inicio sin pantallas ni acciones no permitidas | Frontend (sidebar, dashboard) | `StaffNavigationTest` por rol (TM8) |
| TM1 | Core, routes | Datasets, actor paciente |
| TM2 | Core | Datasets asistente y doctor, `RolePermissionsTest` |
| TM3 | Core, Patients | `PatientsAccessControlTest`, contraseña |
| TM4 | Core | Datasets, actor inactivo |
| TM5 | bootstrap | Datasets, sin sesión |
| TM6 | Core, controladores, bootstrap | `UnexpectedErrorTest` ×4 y red global |
| TM7 | Core | `UnexpectedErrorTest` (Patients) con `Log::spy` |
| TM8 | Frontend | Datasets directos a la API, `StaffNavigationTest`, `RecordsScreenTest` |
| TM9 | routes (sin cambio) | `AppointmentsAccessControlTest`, tracking |
| TM10 | Appointments, bootstrap | Test de CA10 |

## Observabilidad
- Logs nuevos: `unexpected_error` → `error` → `origin` (clase del controlador), `exception` (clase),
  `file`, `line` y `message` salvo en `QueryException` o `PDOException`. Sin `request_id`: la
  correlación no existe aún (desviación de P14 aceptada por el usuario el 2026-09-24). Nunca registra el cuerpo
  de la petición ni valores de la consulta.
- Eventos de auditoría: ninguno. La spec los difiere a la spec del objetivo 5 (sección
  "Auditoría"). Los 401 y 403 de este plan se generan en dos puntos únicos, `EnsureActiveStaff` y
  `AuthorizationException`, que es donde se enganchará la auditoría de accesos denegados sin tocar
  los controladores.
- Métricas y alertas: ninguna. No existen (brecha 7 de observability.md).
- Cómo se verifica tras el deploy: no hay deploy. En local, `grep unexpected_error storage/logs/laravel.log`
  tras forzar un error, comprobando que no hay datos de pacientes.

## Rollout
- Feature flag: no aplica. No hay producción ni datos reales, y un flag mantendría abierta la
  vulnerabilidad que la spec cierra (riesgo 1 de security.md).
- Orden de despliegue: un solo paso (backend y Blade/JS juntos); `npm run build` para el cambio de
  `records/index.js`. Sin migraciones.
- Compatibilidad: el administrador no nota cambios. Los clientes con token de asistente, doctor o
  paciente dejan de poder hacer lo que la tabla no permite; es el objetivo.
- Rollback: `git revert` del merge y redeploy. No hay datos ni esquema que revertir.
- Métricas a vigilar tras el deploy: tasa de 403 y 500 en `/api/v1/patients*` y
  `/api/v1/appointments*` en los logs de nginx (Loki) cuando exista producción.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Dónde se decide el permiso.** Opciones: (a) solo middleware por rol en cada ruta;
  (b) Gates o Policies de Laravel; (c) middleware de tipo de actor más `assertCan` con mapa por rol.
  **Elegida (c):** es el patrón existente (`assertCan`, SECURITY-08), cumple P5 (middleware más
  `assertCan`) y deja una fuente única de permisos. (b) introduciría un segundo mecanismo en
  paralelo. Sin ADR: no cambia la arquitectura, solo amplía un servicio existente.
- **D2 · Errores inesperados.** Opciones: (a) corregir los 48 catch uno a uno con el patrón de Users;
  (b) quitar los catch genéricos y dejarlo todo al manejador global; (c) helper común en cada catch
  más red global. **Elegida (c):** mantiene el patrón de controlador de Auth y Users, evita 48
  copias del mismo bloque y cubre los controladores sin try/catch y el caso `APP_DEBUG=true`.
- **D3 · Qué se registra de un error.** Registrar `getMessage()` tal cual filtra los bindings de
  `QueryException` (datos del paciente) y viola P11. Se omite el mensaje en `QueryException` y
  `PDOException` y se conserva en el resto (útil para depurar). Es un punto único para añadir
  `request_id` cuando exista.
- **D4 · 401 en JSON en `api/*`.** Hoy, sin `Accept: application/json`, Laravel redirige a `/login`.
  Se fuerza JSON con `shouldRenderJsonWhen`, para que la API responda igual a cualquier cliente
  (JS, app Android).
- **D5 · Selectores de la agenda.** `GetPatientsForAppointmentSelectController` reutiliza
  `GetPatientsByStatusUseCase` (ahora `patients.view`, abierto a todo el staff), y
  `GetDoctorsForAppointmentSelectController` no tiene caso de uso. Para dejarlos solo para el
  administrador (tabla de la spec) sin poner lógica en el controlador (P3), se añade el caso de uso
  `AuthorizeAgendaSelectorsUseCase` (`assertCan('agenda.selectors.view')`), que ambos llaman antes
  de leer. Alternativa descartada: un permiso distinto dentro de `GetPatientsByStatusUseCase` según
  quién lo llame, que acopla el caso de uso a la pantalla.

## Impacto en arquitectura
- `docs/architecture.md` → Backend, "Autenticación y autorización": middleware `staff`, mapa de
  permisos por rol en `CurrentActorAuthorizationService` y lista de roles con acceso.
- `docs/architecture.md` → Backend: manejo de errores centralizado (`UnexpectedErrorResponse` y
  `withExceptions`).
- `docs/security.md`: riesgos 1 y 3 pasan a mitigados; controles verificados.
- Specs 005, 006, 007 y 009: nota en su Historial en `/release` (extensión declarada).

## Riesgos y mitigaciones
- **Olvidar una ruta o un caso de uso.** Mitigación: los datasets recorren la lista completa de
  rutas de `routes/api.php` bajo `staff`; un test afirma que cada ruta de esos prefijos aparece en
  el dataset (comparando con `Route::getRoutes()`).
- **Romper al administrador.** Mitigación: CA1 con el mismo dataset y los tests existentes con
  actor admin.
- **La red global cambia respuestas del framework** (404 de ruta, 405, 422, 429). Mitigación: solo
  actúa sobre excepciones que no son `HttpExceptionInterface`, `ValidationException`,
  `AuthenticationException` ni `ThrottleRequestsException`; tests de regresión.
- **Tests existentes acoplados a `actingAsNonAdminUser()`.** Mitigación: se actualizan en la misma
  tarea que el caso de uso de su ruta, sin mezclar cambios de comportamiento.
- **Sin rastro de los accesos denegados** (repudio) hasta la spec de auditoría. Aceptado por la
  spec. Los dos puntos únicos de 401 y 403 facilitan añadirlo.
- **ContentManagement sin tests previos y con AI-DLC en pausa.** Mitigación: solo se cambia el
  catch genérico; `ContentManagementIntegrationTestCase` es mínimo y no anticipa decisiones de la
  Unidad 5.
- **P14 sin `request_id` en el log nuevo.** Aceptado por el usuario el 2026-09-24. Se añade en el helper en cuanto
  exista la correlación.
