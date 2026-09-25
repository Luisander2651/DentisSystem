---
spec: 014-control-de-acceso-y-errores
plan: plan.md
status: approved
---

# Tareas · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Versión 3, corregida con `--fix` tras la ronda 4 de `/analyze` (C1, C4, D1, D2; los aceptados van
como notas de tarea, ver `analysis.md`). Base: plan v3 y segundo `/analyze`: B1, B3–B5, B7–B10, B12, B13, B16–B23). Versiones
anteriores: [tasks.v1.md](tasks.v1.md), [tasks.v2.md](tasks.v2.md). Ninguna tarea estaba hecha. Se
conservan los números de las tareas que siguen; las nuevas empiezan en T066.

> Línea base (2026-09-24, `/implement`): `./vendor/bin/pest --parallel` → 495 passed (9493 aserciones), sin fallos. Política de commits: uno por fase (decisión del usuario).

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).

Abreviaturas de rutas:
- `Core/` = `app/Core/`
- `PatUC/` = `app/Modules/Patients/Aplication/UseCases/`
- `PatCtl/` = `app/Modules/Patients/Infrastructure/Http/Controllers/`
- `PatEx/` = `app/Modules/Patients/Domain/Exceptions/`
- `ApUC/` = `app/Modules/Appointments/Aplication/UseCases/`
- `ApCtl/` = `app/Modules/Appointments/Infrastructure/Http/Controllers/`
- `TrCtl/` = `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/`
- `CM/<Sub>/` = `app/Modules/ContentManagement/Modules/<Sub>/Infrastructure/HTTP/Controllers/`
- `RecCmp/` = `resources/views/components/records/`

**Patrón de error** (plan, D2). "Aplicarlo" a un controlador significa:
1. `catch (AuthorizationException)` → 403 `{"error"}` antes del catch genérico, si no lo tiene;
2. el catch genérico queda **solo** con `return UnexpectedErrorResponse::from($e, self::class);`, retirando el `Log::error` previo (con `getMessage()` o `getTraceAsString()`);
3. se retira todo `Log::` del controlador que registre `getMessage()`, trazas o datos del paciente (también en catch de negocio y en `Log::info`);
4. las respuestas de los catch de negocio (400, 404, 409, 422) no se tocan.

## Constitution Check
Revisión del desglose frente a la constitución 1.1.2 y al Constitution Check de [plan.md](plan.md).

| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec y plan `approved`. |
| P2 Test que falla antes y pasa después | ✅ | Los tests nuevos (T010–T019, T066) y el ajuste de T074 van antes de su implementación, y cada tarea de implementación depende del test que fija su criterio de hecho. Los tests existentes (T020–T022) cambian de actor antes de implementar y pasan antes y después, salvo los dos casos de `GetAppointmentsTest` reescritos, que fallan hasta T047 y T052. La ocultación de botones se afirma en Blade (T018). |
| P3 Capas del módulo | ✅ | Permisos en casos de uso (T041–T045, T050, T051). `GetTreatmentsController` pasa por su caso de uso (T047). Los controladores solo traducen excepciones. Los mensajes cambian en las excepciones de dominio (T067–T069) sin tocar su firma. |
| P4 Contrato de API primero | ✅ | Códigos por actor fijados por T011 y T012 antes de implementar. |
| P5 Autorización en el servidor | ✅ | Constitución 1.1.2: middleware (T031) y `assertCan` con tests de acceso denegado por actor (T011, T012) en las 25 rutas cuya autorización cambia. Las demás solo cambian su manejo de errores. |
| P6 Validación con FormRequest | ➖ | Constitución 1.1.1: ninguna tarea cambia la entrada de un endpoint. |
| P7 Errores sin detalles internos | ✅ | Helper (T032), red global (T033), 48 controladores, mensajes de negocio sin datos (T067–T069) y comprobación estática (T064). |
| P8 Secretos | ➖ | Sin variables nuevas. |
| P9 Migraciones | ➖ | Sin migraciones. |
| P10 Dependencias con ADR | ➖ | Ninguna tarea añade paquetes. |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada `TM#` tiene tarea de control y de test (Cobertura). Se retiran los `Log::` con datos o `getMessage()` de los controladores tocados (patrón, pasos 2 y 3), incluidos los de `CreateAppointmentController` (T047). |
| P12 Producción | ➖ | Sin producción; T095–T098 las ejecuta `/release`. |
| P13 Lógica en el backend | ✅ | El frontend solo refleja lo que el servidor decide. Las tareas de frontend dependen del cableado (T034, T035), y T064 cierra la implementación solo cuando todos los rechazos del servidor (T041–T052) y el frontend están hechos. |
| P14 Trazabilidad | ❌ aceptado: el log de los 500 no lleva `request_id` y los accesos denegados no se auditan hasta la spec de auditoría (objetivo 5) — aprobado por el usuario el 2026-09-24 | Solo T032 introduce un log. |
| Restricciones (WCAG 2.1 AA) | ✅ | Cada tarea de frontend (T061, T062, T063, T070) incluye la revisión de foco con la skill `design` antes de darla por hecha. |
| Definición de terminado | ✅ | T064 (Pint y `pest --parallel` completo), T072 (`CHANGELOG.md`), T073 (`aidd.py validate`) y T090–T092. |

## Preparación
Sin tareas: no hay dependencias, configuración, flags ni migraciones (plan, Rollout).

## Tests (antes de implementar)
- [x] T010 [P] Escribir la matriz de permisos: cada permiso de la tabla del plan × administrador, asistente y doctor activos × administrador inactivo × paciente, sobre `CurrentActorAuthorizationService` — `tests/Modules/Users/Integration/RolePermissionsTest.php` — hecho cuando: falla porque asistente y doctor no tienen los permisos nuevos — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA11
- [x] T011 [P] Escribir el dataset de acceso de las 15 rutas de pacientes, con `getJson`/`postJson` y el código esperado por actor según la tabla del plan. Detalle: actores: admin, asistente, doctor, staff inactivo, paciente y sin sesión; en los rechazos, la base no cambia; en `PUT /patients/{id}` con `new_password`, el hash solo cambia como admin (200); las rutas de `Route::getRoutes()` con prefijo `api/v1/patients` y middleware exactamente `staff`, más `POST` y `DELETE /patients`, coinciden con el dataset. — `tests/Modules/Patients/Integration/PatientsAccessControlTest.php` — hecho cuando: falla por los 2xx de paciente y de rol sin permiso — cubre: CA1, CA2, CA3, CA5, CA6, CA7, CA8, CA9, CA11
  - nota: la foto de la base se ordena por `id`, no por `created_at` (filas creadas en el mismo segundo daban una comparación inestable; corregido en T041).
- [x] T012 [P] Escribir el dataset de acceso de las 10 rutas de `agenda/*` y `appointments/*` bajo `staff` × 6 actores. Incluir: `GET /appointments/{id}/tracking` como asistente y doctor → 403; `agenda/today-appointments` y `agenda/doctors` como asistente y doctor → 403, no 500; `agenda/treatments` como asistente y doctor → 403, y como admin ordenado por nombre y con un tratamiento de `time` nulo devuelto como `time: 0`; la comparación con `Route::getRoutes()` filtrado por prefijo `api/v1/agenda` y `api/v1/appointments` y middleware exactamente `staff`. — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: falla por los 2xx de paciente y de rol sin permiso y por los 500 — cubre: CA1, CA2, CA3, CA5, CA6, CA7, CA8, CA9, CA10
  - nota: además del fallo esperado, el caso "admin" de `GET /agenda/doctors` falla por un bug previo (500 para todos); lo corrige T076.
  - nota: en 1 de 4 ejecuciones completas en serie falló un caso "guest" de este dataset (no reproducible aislado ni en ejecuciones posteriores; sin mensaje capturado). Vigilar en `/review`.
- [x] T013 [P] Escribir el test de error inesperado de Patients, con un repositorio enlazado en el contenedor: `RuntimeException('detalle interno')` en `GET /patients/{id}` y en `PUT /patients/{patientId}/contact-info` → cuerpo exacto `{"error": "Internal server error"}`; con `Log::spy()`, `QueryException` con un teléfono y una alergia de prueba en los bindings en `GET /patients/{id}`, y `RuntimeException` cuyo mensaje contiene un email de prueba en `GET /patients/{patientId}/record` → se registra `unexpected_error` con `exception` y `origin`, y ningún `Log::` contiene los valores de prueba. — `tests/Modules/Patients/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` y no se registra `unexpected_error` — cubre: CA12, CA13
  - nota: el log se comprueba con un listener de `MessageLogged` en lugar de `Log::spy()`: captura toda línea escrita (también la del reporte por defecto de Laravel) y permite afirmar que ninguna contiene el dato.
- [x] T014 [P] Escribir el test de error inesperado de Appointments con el patrón de T013, en `GET /appointments` y `PUT /treatments/{id}` — `tests/Modules/Appointments/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
  - nota: `TreatmentsService` depende del `EloquentTreatmentRepository` final (fuga P3 conocida) y no se puede simular; el caso de `PUT /treatments/{id}` renombra la tabla dentro de la transacción del test para provocar un `QueryException` real.
- [x] T015 [P] Escribir el test de la red global de `withExceptions` sobre rutas bajo `api/v1` registradas solo dentro del test, sin try/catch: excepción no capturada → 500 genérico; `AuthorizationException` no capturada → 403 `{"error"}`; una que lanza un 422 de validación y otra con `throttle:api` excedido conservan su forma; `GET /api/v1/patients` sin sesión y sin `Accept: application/json` → 401 JSON; y, con `Log::spy()`, una `QueryException` con un teléfono de prueba no capturada → solo se registra `unexpected_error` una vez y ningún `Log::` contiene el teléfono. — `tests/Modules/Appointments/Integration/GlobalErrorFallbackTest.php` — hecho cuando: falla en el 500 (expone la excepción), el 403, el 401 (redirige) y el log (el reporte por defecto registra el mensaje) — cubre: CA9, CA10, CA12, CA13
  - nota: `MessageLogged` en lugar de `Log::spy()` (ver T013); las rutas de prueba llevan `throttle:api` explícito, como `routes/api.php`.
- [x] T016 [P] Escribir el test de error inesperado de AppointmentTracking con el patrón de T013, en `GET /appointments/{id}/tracking` y `DELETE /appointment-tracking/prescriptions/{id}` — `tests/Modules/AppointmentTracking/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
- [x] T017 [P] Crear la base mínima de ContentManagement (`RefreshDatabase`, `ActingAsStaff`) y su test de error inesperado, como admin, en el `GET` de administración de cada submódulo (certificaciones, galería, promociones, testimonios) — `tests/Modules/ContentManagement/Integration/ContentManagementIntegrationTestCase.php`, `tests/Modules/ContentManagement/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
- [x] T018 [P] Escribir el test de la pantalla de expedientes (`/expedientes-clinicos` y `/expedientes-clinicos/{id}`). Casos: doctor → 200 con `data-records-can-edit="false"` y sin `data-record-open-contact-form`, `data-record-open-address-form`, `data-record-open-medical-form` ni `data-record-delete-button-template`; asistente → con todos ellos; paciente y staff inactivo → 403; sin sesión → redirección a `/login`. — `tests/Modules/Patients/Integration/RecordsScreenTest.php` — hecho cuando: falla por el 403 del doctor y la ausencia de la plantilla — cubre: CA4
- [x] T019 [P] Escribir el test de navegación por rol en `/dashboard` (menú lateral y panel de inicio). Casos: admin ve todo; asistente sin `href="/agenda"` ni `data-create-patient-open`; doctor con `href="/expedientes-clinicos"` y sin `href="/agenda"`; paciente sin `href="/agenda"` ni `href="/expedientes-clinicos"`. — `tests/Modules/Users/Integration/StaffNavigationTest.php` — hecho cuando: falla en asistente, doctor y paciente — cubre: CA4, CA15
- [x] T066 [P] Escribir el test de mensajes de negocio sin datos del paciente: `POST /patients` con un email ya usado → 409; `POST /patients` con email inválido y con nombre inválido → 400; y teléfono y email de contacto, código postal y tipo de sangre inválidos en sus endpoints → 400. Cada cuerpo nombra el campo y no contiene el valor enviado. — `tests/Modules/Patients/Integration/PatientBusinessMessagesTest.php` — hecho cuando: falla porque los mensajes repiten el valor — cubre: CA14, CA16
  - nota: la comparación del valor es sin distinguir mayúsculas: los value objects normalizan lo que repiten (el nombre vuelve capitalizado). Incluye las aserciones de texto de C5 (404 de paciente, 409 de relación 1:1).
- [x] T020 [P] Cambiar el actor de los tests existentes al rol que conserva el permiso (admin para crear, eliminar y modificar datos básicos; asistente para datos clínicos), sin cambiar sus aserciones, y renombrar en `PatientCrudTest` el caso "allows any authenticated staff (not just admin) to partially update" para que no contradiga la spec — `tests/Modules/Patients/Integration/PatientCrudTest.php`, `tests/Modules/Patients/Integration/AddressTest.php`, `tests/Modules/Patients/Integration/ContactInfoTest.php` — hecho cuando: pasan hoy y siguen afirmando sus 400, 404 y 409 — cubre: CA14
- [x] T021 [P] Cambiar el actor de los tests existentes de datos médicos y expediente al asistente — `tests/Modules/Patients/Integration/MedicalDataTest.php`, `tests/Modules/Patients/Integration/PatientRecordTest.php` — hecho cuando: pasan hoy con el actor nuevo — cubre: CA14
- [x] T022 [P] Cambiar el actor de los tests existentes de citas a administrador y reescribir en `GetAppointmentsTest` los dos casos que afirman el comportamiento que esta spec corrige: citas del día como no admin → 403 (antes 500) y `agenda/treatments` como no admin → 403 (antes 200). Se renombran y se quita la referencia a U1 BR-5. Además, añadir en `CreateAppointmentTest` un caso con `Log::spy()` que afirme que ningún `Log::` contiene el teléfono ni el nombre del paciente de prueba (OB2.a) — `tests/Modules/Appointments/Integration/CreateAppointmentTest.php`, `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php`, `tests/Modules/Appointments/Integration/GetAppointmentsTest.php` — hecho cuando: todo pasa hoy salvo los dos casos reescritos y el caso de `Log::spy`, que fallan por la razón esperada — cubre: CA6, CA10, CA13, CA14
  - nota: el caso de `Log::spy` usa `fakeTwilio()`: sin él, el listener de WhatsApp llamaba a la API real de Twilio (deuda anotada en el roadmap). Casos renombrados según C8; actor explícito `Asistente` según C10.
- [x] T074 [P] Cambiar en `DeleteAppointmentTest` el texto esperado del 403 para el paciente a `'Only staff can access this resource.'` (el de `EnsureActiveStaff`). El del administrador inactivo (`'Your account is inactive.'`) no cambia — `tests/Modules/Appointments/Integration/DeleteAppointmentTest.php` — hecho cuando: falla solo en el caso del paciente, por el texto — cubre: CA5

## Implementación

### Core y cableado
- [x] T030 Sustituir la lista `$adminPermissions` por un mapa `permiso → roles` con los permisos nuevos del plan (`patients.view`, `patients.record.view`, `patients.update`, `patients.clinical-data.manage`, `appointments.patient-history.view`, `appointments.view-detail`, `appointments.create`, `appointments.update`, `agenda.selectors.view`). Los existentes quedan solo para administrador — `Core/Authorization/CurrentActorAuthorizationService.php` — hecho cuando: T010 pasa y `UsersAuthorizationTest` y `tests/Modules/Users/Unit/Domain/CurrentActorAuthorizationServiceTest.php` siguen verdes — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA11 — depende: T010
- [x] T031 Crear el middleware `EnsureActiveStaff` y registrar su alias `staff`. Exige `UserModel` activo y, con parámetros, uno de esos roles. En `api/*` responde 403 `{"error"}` con los textos del plan (no staff → `"Only staff can access this resource."`; inactivo → `"Your account is inactive."`; rol no admitido → `"You are not allowed to access this resource."`); en web, `abort(403)` — `Core/Middlewares/EnsureActiveStaff.php`, `bootstrap/app.php` — hecho cuando: el alias resuelve y `php artisan route:list` no falla — cubre: CA5, CA8 — depende: T011, T012, T074
- [x] T032 Crear el helper `UnexpectedErrorResponse::from(Throwable $e, string $origin)`. Hace `Log::error('unexpected_error', ['origin', 'exception', 'file', 'line'])`, **sin** `message`, y responde 500 `{"error": "Internal server error"}` — `Core/Http/UnexpectedErrorResponse.php` — hecho cuando: la clase tiene esa firma y no llama a `getMessage()` ni a `getTrace*()`. Su comportamiento lo verifica T013 en T036 y T037 — cubre: CA12, CA13 — depende: T013
- [x] T033 Configurar `withExceptions`: `shouldRenderJsonWhen` para `api/*`; `AuthorizationException` de Core → 403 `{"error"}` en `api/*`; toda excepción en `api/*` que no sea `HttpExceptionInterface`, `HttpResponseException`, `ValidationException`, `AuthenticationException` ni `ThrottleRequestsException` → `UnexpectedErrorResponse`; y detener el reporte por defecto de Laravel de esas mismas excepciones en `api/*`, para que solo quede el log del helper. — `bootstrap/app.php` — hecho cuando: T015 pasa — cubre: CA9, CA10, CA12, CA13 — depende: T015, T031, T032
  - nota: el reporte por defecto se detiene con un callback de `report()` que devuelve `false` para las excepciones inesperadas en `api/*`; el `origin` del log es la acción de la ruta.
- [x] T034 Poner los grupos `agenda/*`, `patients/*` y `appointments/*` que hoy solo tienen `auth:sanctum` bajo `['auth:sanctum', 'staff']`. `appointments/{id}/tracking` sigue bajo `only.admin` — `routes/api.php` — hecho cuando: los casos de paciente y de staff inactivo de T011 y T012 dan 403, el de tracking de T012 también, y T074 pasa — cubre: CA5, CA8 — depende: T031, T074
- [x] T035 Sustituir los closures de `/expedientes-clinicos` y `/expedientes-clinicos/{patientId}` por `middleware('staff:administrador,asistente,doctor')` — `routes/web.php` — hecho cuando: los casos de acceso de T018 pasan (doctor 200; paciente e inactivo 403; sin sesión → `/login`) — cubre: CA4 — depende: T018, T031

### Patients
Los controladores van antes que los casos de uso: capturan `AuthorizationException` → 403 antes de que los casos de uso empiecen a lanzarla.
- [x] T036 Aplicar el patrón de error — `PatCtl/GetPatientsByStatusController.php`, `PatCtl/GetPatientByIdController.php`, `PatCtl/UpdatePatientController.php` — hecho cuando: los casos `GET /patients/{id}` de T013 (respuesta y `QueryException`) pasan y `PatientCrudTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T013, T032, T020
- [x] T037 Aplicar el patrón de error — `PatCtl/CreatePatientController.php`, `PatCtl/DeletePatientByIdController.php`, `PatCtl/PatientRecord/GetPatientRecordByPatientIdController.php` — hecho cuando: el caso de log de `GET /patients/{patientId}/record` de T013 pasa y `PatientCrudTest` y `PatientRecordTest` siguen verdes — cubre: CA12, CA13, CA14 — depende: T013, T032, T020, T021
- [x] T038 Aplicar el patrón de error — `PatCtl/Addresses/CreateAddressController.php`, `PatCtl/Addresses/UpdateAddressController.php`, `PatCtl/Addresses/DeleteAddressByPatientIdController.php` — hecho cuando: no queda `getMessage()` en sus respuestas 500 ni en `Log::` y `AddressTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T032, T020
- [x] T039 Aplicar el patrón de error — `PatCtl/ContactInfo/CreateContactInfoController.php`, `PatCtl/ContactInfo/UpdateContactInfoController.php`, `PatCtl/ContactInfo/DeleteContactInfoByPatientIdController.php` — hecho cuando: el caso `PUT contact-info` de T013 pasa y `ContactInfoTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T013, T032, T020
- [x] T040 Aplicar el patrón de error — `PatCtl/MedicalData/CreateMedicalDataController.php`, `PatCtl/MedicalData/UpdateMedicalDataController.php`, `PatCtl/MedicalData/DeleteMedicalDataByPatientIdController.php` — hecho cuando: T013 completo en verde y `MedicalDataTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T032, T021, T036, T037, T038, T039
- [x] T041 Añadir `assertCan('patients.view')` a los casos de uso de listado y detalle, y `assertCan('patients.record.view')` al del expediente, inyectando `AuthorizationServiceInterface` — `PatUC/GetPatientsByStatusUseCase.php`, `PatUC/GetPatientByIdUseCase.php`, `PatUC/PatientRecord/GetPatientRecordByPatientIdUseCase.php` — hecho cuando: los casos de lectura de T011 pasan para los 6 actores y `PatientRecordTest` sigue verde — cubre: CA2, CA3 — depende: T030, T034, T036, T037, T020, T021
- [x] T042 Añadir `assertCan('patients.update')` al caso de uso de actualización de paciente — `PatUC/UpdatePatientUseCase.php` — hecho cuando: los casos de `PUT /patients/{id}` de T011 pasan, incluido `new_password`, y `PatientCrudTest` sigue verde — cubre: CA6, CA7, CA11 — depende: T030, T034, T036, T020
- [x] T043 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de dirección — `PatUC/Addresses/SaveAddressUseCase.php`, `PatUC/Addresses/UpdateAddressUseCase.php`, `PatUC/Addresses/DeleteAddressByPatientIdUseCase.php` — hecho cuando: los casos de dirección de T011 pasan y `AddressTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T038, T020
- [x] T044 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de contacto — `PatUC/ContactInfo/SaveContactInfoUseCase.php`, `PatUC/ContactInfo/UpdateContactInfoUseCase.php`, `PatUC/ContactInfo/DeleteContactInfoByPatientIdUseCase.php` — hecho cuando: los casos de contacto de T011 pasan y `ContactInfoTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T039, T020
- [x] T045 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de datos médicos — `PatUC/MedicalData/SaveMedicalDataUseCase.php`, `PatUC/MedicalData/UpdateMedicalDataUseCase.php`, `PatUC/MedicalData/DeleteMedicalDataByPatientIdUseCase.php` — hecho cuando: T011 completo en verde y `MedicalDataTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T040, T041, T042, T043, T044, T021
- [x] T067 Quitar el valor del mensaje (nombrar el campo y el formato esperado) en `PatientException::shouldBeUniqueEmail`, `EmailException` y los dos métodos de `PatientNameException`, sin cambiar su firma — `PatEx/PatientException.php`, `PatEx/ValueObjects/Patients/EmailException.php`, `PatEx/ValueObjects/Patients/PatientNameException.php` — hecho cuando: los casos de email duplicado, email y nombre de T066 pasan y los tests unitarios de Patients siguen verdes — cubre: CA14, CA16 — depende: T066
- [x] T068 Quitar el valor del mensaje en `ContactEmailException`, `PhoneNumberException` y `PostalCodeException` — `PatEx/ValueObjects/ContactInfo/ContactEmailException.php`, `PatEx/ValueObjects/ContactInfo/PhoneNumberException.php`, `PatEx/ValueObjects/Addresses/PostalCodeException.php` — hecho cuando: los casos de email de contacto, teléfono y código postal de T066 pasan — cubre: CA14, CA16 — depende: T066
- [x] T069 Quitar el valor del mensaje en `BloodTypeException` — `PatEx/ValueObjects/MedicalData/BloodTypeException.php` — hecho cuando: T066 completo en verde — cubre: CA14, CA16 — depende: T066, T067, T068

### Appointments
Mismo orden que en Patients: primero los controladores y después los casos de uso.
- [x] T046 Aplicar el patrón de error — `ApCtl/DeleteAppointmentController.php`, `ApCtl/GetAppointmentByIdController.php`, `ApCtl/GetAppointmentsByPatientIdController.php` — hecho cuando: no queda `getMessage()` en sus respuestas 500 ni en `Log::`, y `DeleteAppointmentTest` (con T074) y `GetAppointmentsTest` siguen verdes (salvo los dos casos reescritos en T022) — cubre: CA12, CA13, CA14 — depende: T032, T022, T034, T074
- [x] T047 Hacer que `GetTreatmentsController` use `GetTreatmentsUseCase` sin filtros en lugar de consultar `TreatmentModel`; aplicar el patrón de error a los tres controladores; en `CreateAppointmentController`, retirar los `Log::info` con `customerPhone` y `customerName` (l. 49-53) y los `Log::error` con `getMessage()` de sus catch de negocio — `ApCtl/GetTreatmentsController.php`, `ApCtl/CreateAppointmentController.php`, `ApCtl/UpdateAppointmentController.php` — hecho cuando: los casos de acceso de `agenda/treatments` de T012 pasan (admin 200; asistente y doctor 403), el caso de `time` nulo de T012 pasa, el caso reescrito de `GetAppointmentsTest` y el de `Log::spy` de `CreateAppointmentTest` pasan, `CreateAppointmentTest` y `UpdateAppointmentTest` siguen verdes, y `CreateAppointmentController` no contiene `customerPhone`, `customerName` ni `getMessage()` en `Log::` — cubre: CA6, CA7, CA12, CA13, CA14 — depende: T032, T034, T022
  - nota: en `CreateAppointmentController` solo se retiran las claves `customerPhone` y `customerName`; los demás `Log::info` del controlador no llevan datos del paciente y se conservan.
- [x] T075 Ordenar por `name` el resultado de `findAllByIdByName` para conservar el orden de `agenda/treatments` (también ordena `GET /treatments`) — `app/Modules/Appointments/Infrastructure/Persistence/Eloquent/EloquentTreatmentRepository.php` — hecho cuando: el caso de orden de T012 pasa y `TreatmentTest` sigue verde — cubre: CA1 — depende: T012, T047
- [x] T048 Aplicar el patrón de error — `ApCtl/GetAllApointmentsByStatusAndDateController.php`, `ApCtl/GetTreatmentsAdminController.php`, `ApCtl/GetTreatmentByIdController.php` — hecho cuando: el caso `GET /appointments` de T014 pasa y `TreatmentTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T014, T032
- [x] T049 Aplicar el patrón de error — `ApCtl/CreateTreatmentController.php`, `ApCtl/UpdateTreatmentController.php`, `ApCtl/DeleteTreatmentController.php` — hecho cuando: T014 completo en verde y `TreatmentTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T014, T032, T048
- [x] T050 Añadir `assertCan` a los casos de uso de citas: `appointments.create`, `appointments.update` y `appointments.view-detail` — `ApUC/CreateAppointmentUseCase.php`, `ApUC/UpdateAppointmentUseCase.php`, `ApUC/GetAppointmentByIdUseCase.php` — hecho cuando: los casos de `POST /appointments`, `PUT /appointments/{id}` y `GET /appointments/{id}` de T012 pasan (detalle 200 para todo el staff) — cubre: CA2, CA3, CA6, CA7 — depende: T030, T034, T046, T047, T022
- [x] T051 Añadir `assertCan('appointments.patient-history.view')` al historial por paciente y crear `AuthorizeAgendaSelectorsUseCase` (`final readonly`; `execute()` con `assertCan('agenda.selectors.view')`) — `ApUC/GetAppointentByPatientIdUseCase.php`, `ApUC/AuthorizeAgendaSelectorsUseCase.php` — hecho cuando: los casos de historial de T012 pasan para los 6 actores — cubre: CA2, CA3, CA6, CA7 — depende: T030, T034, T046
- [x] T052 Hacer que el selector de pacientes llame a `AuthorizeAgendaSelectorsUseCase` antes de leer, y aplicar el patrón de error a los dos selectores y a citas del día — `ApCtl/GetPatientsForAppointmentSelectController.php`, `ApCtl/GetDoctorsForAppointmentSelectController.php`, `ApCtl/GetTodayAppointmentsController.php` — hecho cuando: T012 completo en verde (`agenda/patients`, `agenda/doctors` y `agenda/today-appointments` → 403 para asistente y doctor) y el caso reescrito de citas del día de `GetAppointmentsTest` pasa — cubre: CA6, CA7, CA10, CA12 — depende: T032, T033, T034, T046, T047, T050, T051
- [x] T076 Pedir los administradores con el rol `administrador` (hoy `'admin'`, que `UserRoleId` rechaza y convierte el selector de doctores en un 500 para todos) — `ApCtl/GetDoctorsForAppointmentSelectController.php` — hecho cuando: el caso "admin" de `GET /agenda/doctors` de T012 pasa (200) — cubre: CA1 — depende: T012, T052
  - nota: añadida durante /implement (2026-09-24): bug previo, ajeno a la 014, detectado al escribir T012; decisión del usuario.

### AppointmentTracking
- [x] T053 Aplicar el patrón de error — `TrCtl/CompleteAppointmentController.php`, `TrCtl/GetAppointmentTrackingByAppointmentIdController.php`, `TrCtl/UpdateAppointmentTrackingController.php` — hecho cuando: el caso `tracking` de T016 pasa y `CompleteAppointmentTest` sigue verde — cubre: CA12, CA13, CA14 — depende: T016, T032
- [x] T054 Aplicar el patrón de error — `TrCtl/CreatePrescriptionController.php`, `TrCtl/UpdatePrescriptionController.php`, `TrCtl/DeletePrescriptionController.php` — hecho cuando: T016 completo en verde — cubre: CA12, CA13, CA14 — depende: T016, T032, T053

### ContentManagement
- [x] T055 Aplicar el patrón de error — `CM/Certificaciones/SaveCertificationController.php`, `CM/Certificaciones/UpdateCertificationController.php`, `CM/Certificaciones/DeleteCertificationController.php` — hecho cuando: no queda `getMessage()` en sus respuestas 500 ni en `Log::` — cubre: CA12 — depende: T017, T032
- [x] T056 Aplicar el patrón de error — `CM/Certificaciones/GetCertificationsController.php`, `CM/Galeria/SaveGalleryImageController.php`, `CM/Galeria/UpdateGalleryImageController.php` — hecho cuando: el caso de certificaciones de T017 pasa — cubre: CA12 — depende: T017, T032
- [x] T057 Aplicar el patrón de error — `CM/Galeria/DeleteGalleryImageController.php`, `CM/Galeria/GetGalleryImagesController.php`, `CM/Promociones/SavePromotionController.php` — hecho cuando: el caso de galería de T017 pasa — cubre: CA12 — depende: T017, T032
- [x] T058 Aplicar el patrón de error — `CM/Promociones/UpdatePromotionController.php`, `CM/Promociones/DeletePromotionController.php`, `CM/Promociones/GetPromotionsController.php` — hecho cuando: el caso de promociones de T017 pasa — cubre: CA12 — depende: T017, T032
- [x] T059 Aplicar el patrón de error — `CM/Testimonios/SaveTestimonialController.php`, `CM/Testimonios/UpdateTestimonialController.php`, `CM/Testimonios/DeleteTestimonialController.php` — hecho cuando: no queda `getMessage()` en sus respuestas 500 ni en `Log::` — cubre: CA12 — depende: T017, T032
- [x] T060 Aplicar el patrón de error — `CM/Testimonios/GetTestimonialsController.php` — hecho cuando: T017 completo en verde — cubre: CA12 — depende: T017, T032, T055, T056, T057, T058, T059

### Frontend
- [ ] T061 Ajustar `roleTabs`: asistente `['inicio', 'expedientes']`, doctor `['inicio', 'expedientes']` (nuevo), paciente `['inicio']`, y revisar el foco del menú con la skill `design` — `resources/views/components/ui/sidebar.blade.php` — hecho cuando: el caso admin de T019 pasa (asistente, doctor y paciente dependen también del panel, T062) y la revisión de foco queda anotada en esta tarea — cubre: CA4, CA15 — depende: T019, T034, T035
- [ ] T062 Ajustar el panel de inicio: `'doctor' => 'layouts.admin'` en `$layouts`; rama `@elseif doctor` con acceso a "Expedientes"; rama asistente sin "Ver Agenda" ni "Registrar Nuevo Paciente"; `create-patient-modal` y `create-patient.js` solo para administrador; rama paciente sin enlaces a `/expedientes-clinicos` ni `/agenda`. Revisar el foco y el orden de tabulación con la skill `design` (WCAG 2.1 AA). — `resources/views/pages/dashboard.blade.php` — hecho cuando: T019 completo en verde y la revisión de foco queda anotada en esta tarea — cubre: CA4, CA15 — depende: T019, T061
- [ ] T063 Calcular `$canEdit` (administrador o asistente) en la vista de expedientes; renderizar **siempre** `data-records-can-edit` con su valor (`true`/`false`) y `<template data-record-delete-button-template>` solo con `$canEdit`; pasar `$canEdit` como prop a los componentes de contacto y dirección, que solo renderizan `data-record-open-*-form` con él; revisar el foco con la skill `design` — `resources/views/pages/records/index.blade.php`, `RecCmp/contact-info-table.blade.php`, `RecCmp/address-table.blade.php` — hecho cuando: los casos de atributo, plantilla, contacto y dirección de T018 pasan y la revisión de foco queda anotada — cubre: CA4 — depende: T018, T035
- [ ] T070 Pasar `$canEdit` al componente de datos médicos y hacer que `index.js` genere "Eliminar" clonando `data-record-delete-button-template` (sin plantilla, no genera ninguno), en lugar de las cadenas de `index.js:385`, `:410` y `:435`. Después, `npm run build`, verificación manual como doctor y asistente (sin y con botones de escritura; "Ver" abre el detalle de la cita) y revisión de foco con la skill `design` — `RecCmp/medical-data-table.blade.php`, `resources/js/pages/records/index.js` — hecho cuando: T018 completo en verde y la verificación manual y de foco queda anotada en esta tarea — cubre: CA4 — depende: T063

### Cierre de la implementación
- [ ] T064 Comprobar con `grep -rn "getMessage()\|getTraceAsString()\|customerPhone\|customerName"` sobre los controladores de Patients, Appointments, AppointmentTracking y ContentManagement que solo quedan `getMessage()` en respuestas de catch de negocio (400, 404, 409, 422) y ninguno en `Log::`; ejecutar `vendor/bin/pint --dirty --format agent` y `./vendor/bin/pest --parallel` completo en Docker — sin archivos nuevos — hecho cuando: el grep cumple lo anterior, Pint no deja cambios y la suite completa pasa — cubre: CA12, CA13, CA14 — depende: T036, T037, T038, T039, T040, T041, T042, T043, T044, T045, T046, T047, T048, T049, T050, T051, T052, T053, T054, T055, T056, T057, T058, T059, T060, T061, T062, T063, T067, T068, T069, T070, T075

## Integración y documentación
- [ ] T065 Actualizar `docs/security.md`, con estado por corrección: RS1.a y RS1.d → mitigada; RS1.b (spec 013, CA24) y RS1.c (spec 013, CA23) siguen pendientes, así que RS1 queda parcialmente mitigado hasta la 013; RS3.a → mitigada; nota de RS9.a (la 014 retiró los logs de `CreateAppointmentController`); secciones "Autenticación y autorización" (permisos por rol, middleware `staff`, fin de los closures) y "Superficie de ataque" (filas "cualquier actor autenticado"); controles verificados con sus tests. — `docs/security.md` — hecho cuando: cada corrección de RS1 y RS3 tiene su estado, RS1 figura como parcialmente mitigado, RS1.b y RS1.c enlazan la 013 y ninguna sección afirma que las rutas de pacientes y citas aceptan cualquier actor — depende: T064
- [ ] T071 Añadir a "Pendientes y deuda" los mensajes y logs de Users y Auth que repiten el email o registran `getMessage()` y trazas (`UserException`, `EmailException` y `UserNameException` de Users; `AuthException`; catch genéricos de Auth). Proponer al usuario, y aplicar solo con su confirmación, retirar la deuda "Desalineación interfaz/backend" y ajustar el criterio "0 respuestas con `$e->getMessage()`" del objetivo 1 a P7 1.1.1 (las entradas de RS5.a, RS8.a, RS10.b y de las trazas de whatsApp de OB10.b ya se añadieron el 2026-09-24) — `docs/roadmap.md` — hecho cuando: la entrada de deuda existe y cita CA16 y la sección "Fuera de alcance" de la 014, y las dos propuestas quedan aplicadas o rechazadas por el usuario — depende: T064
- [ ] T072 Añadir la entrada de la 014 en `## [Unreleased]` (Security: control de acceso por rol, errores sin detalles internos y mensajes sin datos del paciente) — `CHANGELOG.md` — hecho cuando: la entrada existe — depende: T064
- [ ] T073 Ejecutar `python .ai/bin/aidd.py validate` — sin archivos — hecho cuando: 0 errores — depende: T065, T071, T072, T090, T091
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: sección Backend, "Autenticación y autorización" (middleware `staff`, mapa de permisos por rol, roles con acceso), manejo de errores (`UnexpectedErrorResponse`, `withExceptions`) y fila Core de la tabla de módulos
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas. En `observability.md`: OB2.a y OB10.a → mitigada; el evento `unexpected_error` (campos, sin `message` ni `request_id`) y los puntos únicos de 401/403 donde se enganchará la auditoría
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T012 | T030, T034, T075, T076 |
| CA2 | T010, T011, T012 | T030, T041, T043, T044, T045, T050, T051 |
| CA3 | T010, T011, T012 | T030, T041, T050, T051 |
| CA4 | T018, T019 | T035, T061, T062, T063, T070 |
| CA5 | T011, T012, T074 | T031, T034 |
| CA6 | T010, T011, T012, T022 | T030, T042, T047, T050, T051, T052 |
| CA7 | T010, T011, T012 | T030, T042, T043, T044, T045, T047, T050, T051, T052 |
| CA8 | T010, T011, T012 | T030, T031, T034 |
| CA9 | T011, T012, T015 | T033 |
| CA10 | T012, T015, T022 | T033, T052 |
| CA11 | T010, T011 | T030, T036, T042 |
| CA12 | T013, T014, T015, T016, T017 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T052, T053, T054, T055, T056, T057, T058, T059, T060, T064 |
| CA13 | T013, T015, T022 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T053, T054 |
| CA14 | T020, T021, T022, T066 | T036, T037, T038, T039, T040, T046, T047, T048, T049, T053, T054, T067, T068, T069, T064 |
| CA15 | T019 | T061, T062 |
| CA16 | T066 | T067, T068, T069 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T031, T034 | T011, T012 |
| TM2 | T030, T041, T043, T044, T045, T047, T050, T051, T052 | T010, T011, T012 |
| TM3 | T042 | T011 |
| TM4 | T030, T031 | T010, T011, T012 |
| TM5 | T033 | T011, T012, T015 |
| TM6 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T052, T053, T054, T055, T056, T057, T058, T059, T060 | T013, T014, T015, T016, T017 |
| TM7 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T053, T054 | T013, T015, T022 |
| TM8 | T061, T062, T063, T070 | T011, T012, T018, T019 |
| TM9 | T034 | T012 |
| TM10 | T033, T052 | T012, T015 |
| TM11 | T032, T033, T067 | T013, T015 |
| TM12 | T067, T068, T069 | T066 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Core (`EnsureActiveStaff`, `CurrentActorAuthorizationService`, `UnexpectedErrorResponse`) | T030, T031, T032 |
| bootstrap (`bootstrap/app.php`) | T031, T033 |
| routes (`routes/api.php`, `routes/web.php`) | T034, T035 |
| Patients (15 controladores, casos de uso y excepciones de dominio) | T036, T037, T038, T039, T040, T041, T042, T043, T044, T045, T067, T068, T069 |
| Appointments (controladores, selectores, casos de uso y repositorio de tratamientos) | T046, T047, T048, T049, T050, T051, T052, T075, T076 |
| AppointmentTracking (6 controladores) | T053, T054 |
| ContentManagement (16 controladores) | T055, T056, T057, T058, T059, T060 |
| Frontend (sidebar, dashboard, expedientes y componentes) | T061, T062, T063, T070 |
| Tests existentes (cambio de actor, casos reescritos y textos del 403) | T020, T021, T022, T074 |
| Documentación (security, roadmap, CHANGELOG) | T065, T071, T072, T073 |
