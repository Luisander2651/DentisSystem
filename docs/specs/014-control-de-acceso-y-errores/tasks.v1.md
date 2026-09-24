---
spec: 014-control-de-acceso-y-errores
plan: plan.md
status: draft
---

# Tareas · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).

Abreviaturas de rutas:
- `Core/` = `app/Core/`
- `PatUC/` = `app/Modules/Patients/Aplication/UseCases/`
- `PatCtl/` = `app/Modules/Patients/Infrastructure/Http/Controllers/`
- `ApUC/` = `app/Modules/Appointments/Aplication/UseCases/`
- `ApCtl/` = `app/Modules/Appointments/Infrastructure/Http/Controllers/`
- `TrCtl/` = `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/`
- `CM/<Sub>/` = `app/Modules/ContentManagement/Modules/<Sub>/Infrastructure/HTTP/Controllers/`

"Aplicar el patrón de error" significa: `catch (AuthorizationException)` → 403 `{"error"}` antes del
catch genérico (si el controlador no lo tiene), y catch genérico → `UnexpectedErrorResponse::from($e, self::class)`.
Los catch de negocio (400, 404, 409, 422) no se tocan (CA14).

## Constitution Check
Revisión del desglose frente al Constitution Check de [plan.md](plan.md). No se repite el análisis del plan.

| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec y plan `approved`. |
| P2 Test que falla antes y pasa después | ✅ | Los tests nuevos (T010–T018) van antes de cualquier implementación, y cada tarea de implementación depende del test que la cubre. Los tests existentes (T019–T021) solo cambian de actor y pasan antes y después. |
| P3 Capas del módulo | ✅ | Los permisos se comprueban en casos de uso (T041–T045, T050, T051). Los controladores solo traducen excepciones (T036–T040, T046–T049, T052–T060). `AuthorizeAgendaSelectorsUseCase` (T051) evita poner lógica en los controladores de los selectores. |
| P4 Contrato de API primero | ✅ | Los códigos por actor están en el plan y fijados por los datasets T011 y T012 antes de implementar. |
| P5 Autorización en el servidor | ✅ | Middleware (T031) y `assertCan` (T030, T041–T045, T050, T051) con test de acceso denegado por actor (T011, T012). |
| P6 Validación con FormRequest | ➖ | Ninguna tarea cambia la entrada de un endpoint (ver plan). |
| P7 Errores sin detalles internos | ✅ | Helper (T032), red global (T033), los 48 controladores (T036–T040, T046–T049, T052–T060) y comprobación estática (T064). |
| P8 Secretos fuera del repositorio | ➖ | Sin variables nuevas. |
| P9 Migraciones | ➖ | Sin migraciones. |
| P10 Dependencias con ADR | ➖ | Ninguna tarea añade paquetes. |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada `TM#` tiene tarea de control y de test (tabla de Cobertura). T013 prueba que el log no contiene datos del paciente. |
| P12 Producción | ➖ | Sin producción. T095–T098 las ejecuta `/release`. |
| P13 Lógica en el backend | ✅ | Las tareas de frontend (T061–T063) dependen de que el servidor ya rechace (T034, T035). |
| P14 Trazabilidad | ❌ aceptado: el log de los 500 no lleva `request_id` hasta la spec de auditoría (objetivo 5) — aprobado por el usuario el 2026-09-24 | Solo T032 introduce el log; no se añade otro log sin correlación. |

## Preparación
Sin tareas: no hay dependencias, configuración, flags ni migraciones (plan, Rollout).

## Tests (antes de implementar)
- [ ] T010 [P] Escribir la matriz de permisos por rol: cada permiso de la tabla del plan × administrador, asistente, doctor (activos) × administrador inactivo × paciente, sobre `CurrentActorAuthorizationService` — `tests/Modules/Users/Integration/RolePermissionsTest.php` — hecho cuando: falla porque asistente y doctor no tienen los permisos nuevos y `patients.view` no existe — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA11
- [ ] T011 [P] Escribir el dataset de acceso de las 15 rutas de pacientes × 6 actores (admin, asistente, doctor, staff inactivo, paciente, sin sesión), con el código esperado de la tabla del plan; en los rechazos, afirmar que la base no cambió; en `PUT /patients/{id}` con `password`, afirmar que el hash solo cambia como admin; añadir un test que compare los prefijos `patients` de `Route::getRoutes()` con el dataset — `tests/Modules/Patients/Integration/PatientsAccessControlTest.php` — hecho cuando: falla por los 2xx de paciente, doctor y rol sin permiso, y por el 302 sin sesión — cubre: CA1, CA2, CA3, CA5, CA6, CA7, CA8, CA9, CA11
- [ ] T012 [P] Escribir el dataset de acceso de las 10 rutas de `agenda/*` y `appointments/*` bajo `auth:sanctum` × 6 actores; incluir `GET /appointments/{id}/tracking` como asistente y doctor (403) y `GET /agenda/today-appointments` como asistente y doctor (403, no 500); añadir la comparación con `Route::getRoutes()` — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: falla por los 2xx de paciente y de rol sin permiso y por el 500 de citas del día — cubre: CA1, CA2, CA3, CA5, CA6, CA7, CA8, CA9, CA10
- [ ] T013 [P] Escribir el test de error inesperado de Patients: repositorio enlazado que lanza `RuntimeException('detalle interno')` en una ruta de lectura y una de escritura → 500 con cuerpo exacto `{"error": "Internal server error"}`; `Log::spy()` con una `QueryException` cuyos bindings llevan un teléfono y una alergia de prueba → ningún `Log::error` los contiene y sí registra `exception` y `origin` — `tests/Modules/Patients/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12, CA13
- [ ] T014 [P] Escribir el test de error inesperado de Appointments (una ruta de citas y una del catálogo de tratamientos) con el mismo patrón que T013 — `tests/Modules/Appointments/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
- [ ] T015 [P] Escribir el test de la red global de `withExceptions`: excepción no capturada en `GetTodayAppointmentsController` → 500 genérico; `AuthorizationException` no capturada → 403 `{"error"}`; un 422 de FormRequest y un 429 de `throttle:api` conservan su forma; `GET /api/v1/patients` sin sesión y sin `Accept: application/json` → 401 JSON — `tests/Modules/Appointments/Integration/GlobalErrorFallbackTest.php` — hecho cuando: falla en el 500 (hoy expone la excepción con `APP_DEBUG`), en el 403 y en el 401 (hoy redirige) — cubre: CA9, CA10, CA12
- [ ] T016 [P] Escribir el test de error inesperado de AppointmentTracking con el patrón de T013 — `tests/Modules/AppointmentTracking/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
- [ ] T017 [P] Crear la base mínima de ContentManagement (`RefreshDatabase`, `ActingAsStaff`) y su test de error inesperado en una ruta de cada submódulo (certificaciones, galería, promociones, testimonios) como admin — `tests/Modules/ContentManagement/Integration/ContentManagementIntegrationTestCase.php`, `tests/Modules/ContentManagement/Integration/UnexpectedErrorTest.php` — hecho cuando: falla porque la respuesta trae `message` — cubre: CA12
- [ ] T018 [P] Escribir el test de la pantalla de expedientes: `/expedientes-clinicos` y `/expedientes-clinicos/{id}` como doctor → 200 con `data-records-can-edit="false"` y sin `data-record-open-contact-form`; como asistente → `true`; como paciente y como staff inactivo → 403 — `tests/Modules/Patients/Integration/RecordsScreenTest.php` — hecho cuando: falla por el 403 del doctor — cubre: CA4
- [ ] T019 [P] Escribir el test de navegación por rol en `/dashboard` (menú lateral y panel de inicio): admin ve todo; asistente sin `href="/agenda"` ni `data-create-patient-open`; doctor con `href="/expedientes-clinicos"` y sin `href="/agenda"`; paciente sin `href="/agenda"` ni `href="/expedientes-clinicos"` — `tests/Modules/Users/Integration/StaffNavigationTest.php` — hecho cuando: falla en asistente, doctor y paciente — cubre: CA4, CA15
- [ ] T020 [P] Cambiar el actor de los tests existentes al rol que conserva el permiso (admin para crear, eliminar y modificar datos básicos; asistente para datos clínicos), sin cambiar sus aserciones — `tests/Modules/Patients/Integration/PatientCrudTest.php`, `tests/Modules/Patients/Integration/AddressTest.php`, `tests/Modules/Patients/Integration/ContactInfoTest.php` — hecho cuando: pasan hoy y siguen afirmando los 400, 404 y 409 de negocio — cubre: CA14
- [ ] T021 [P] Cambiar el actor de los tests existentes de datos médicos y expediente (asistente) — `tests/Modules/Patients/Integration/MedicalDataTest.php`, `tests/Modules/Patients/Integration/PatientRecordTest.php` — hecho cuando: pasan hoy con el actor nuevo — cubre: CA14
- [ ] T022 [P] Cambiar el actor de los tests existentes de citas a administrador — `tests/Modules/Appointments/Integration/CreateAppointmentTest.php`, `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php`, `tests/Modules/Appointments/Integration/GetAppointmentsTest.php` — hecho cuando: pasan hoy con el actor nuevo y conservan sus aserciones de 400, 404 y 409 — cubre: CA14

## Implementación

### Core y cableado
- [ ] T030 Sustituir la lista `$adminPermissions` por un mapa `permiso → roles` con los permisos nuevos del plan (`patients.view`, `patients.record.view`, `patients.update`, `patients.clinical-data.manage`, `appointments.patient-history.view`, `appointments.view-detail`, `appointments.create`, `appointments.update`, `agenda.selectors.view`); los permisos existentes quedan solo para administrador — `Core/Authorization/CurrentActorAuthorizationService.php` — hecho cuando: T010 pasa y `UsersAuthorizationTest` sigue verde — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA11 — depende: T010
- [ ] T031 Crear el middleware `EnsureActiveStaff` (exige `UserModel` activo y, con parámetros, uno de esos roles; 403 `{"error"}` en `api/*`, `abort(403)` en web) y registrar el alias `staff` — `Core/Middlewares/EnsureActiveStaff.php`, `bootstrap/app.php` — hecho cuando: el alias `staff` resuelve y `php artisan route:list` no falla — cubre: CA5, CA8 — depende: T011, T012
- [ ] T032 Crear el helper `UnexpectedErrorResponse::from(Throwable $e, string $origin)`: `Log::error('unexpected_error', [origin, exception, file, line])` más `message` salvo en `QueryException` o `PDOException`; responde 500 `{"error": "Internal server error"}` — `Core/Http/UnexpectedErrorResponse.php` — hecho cuando: el caso `Log::spy` de T013 pasa al usarlo desde un controlador de Patients (T041) — cubre: CA12, CA13 — depende: T013
- [ ] T033 Configurar `withExceptions`: `shouldRenderJsonWhen` para `api/*`; `AuthorizationException` de Core → 403 `{"error"}` en `api/*`; toda excepción que no sea `HttpExceptionInterface`, `ValidationException`, `AuthenticationException` ni `ThrottleRequestsException` en `api/*` → `UnexpectedErrorResponse` — `bootstrap/app.php` — hecho cuando: T015 pasa — cubre: CA9, CA10, CA12 — depende: T015, T031, T032
- [ ] T034 Poner los grupos `agenda/*`, `patients/*` y `appointments/*` que hoy solo tienen `auth:sanctum` bajo `['auth:sanctum', 'staff']`; dejar `appointments/{id}/tracking` bajo `only.admin` — `routes/api.php` — hecho cuando: los casos de paciente y staff inactivo de T011 y T012 pasan (403) y el caso tracking de T012 sigue en 403 — cubre: CA5, CA8 — depende: T031
- [ ] T035 Sustituir los closures con comprobación de rol de `/expedientes-clinicos` y `/expedientes-clinicos/{patientId}` por `middleware('staff:administrador,asistente,doctor')` — `routes/web.php` — hecho cuando: los casos de acceso de T018 pasan (el doctor recibe 200; paciente e inactivo, 403) — cubre: CA4 — depende: T018, T031

### Patients
Los controladores van primero: capturan `AuthorizationException` → 403 antes de que los casos de uso empiecen a lanzarla; si no, el catch genérico la convertiría en 500.
- [ ] T036 Aplicar el patrón de error — `PatCtl/GetPatientsByStatusController.php`, `PatCtl/GetPatientByIdController.php`, `PatCtl/UpdatePatientController.php` — hecho cuando: los casos de T013 para estas rutas pasan y `PatientCrudTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T037 Aplicar el patrón de error — `PatCtl/CreatePatientController.php`, `PatCtl/DeletePatientByIdController.php`, `PatCtl/PatientRecord/GetPatientRecordByPatientIdController.php` — hecho cuando: sus casos de T013 pasan y `PatientCrudTest` y `PatientRecordTest` siguen verdes — cubre: CA12, CA14 — depende: T032
- [ ] T038 Aplicar el patrón de error — `PatCtl/Addresses/CreateAddressController.php`, `PatCtl/Addresses/UpdateAddressController.php`, `PatCtl/Addresses/DeleteAddressByPatientIdController.php` — hecho cuando: no queda `'message' => $e->getMessage()` en sus respuestas y `AddressTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T039 Aplicar el patrón de error — `PatCtl/ContactInfo/CreateContactInfoController.php`, `PatCtl/ContactInfo/UpdateContactInfoController.php`, `PatCtl/ContactInfo/DeleteContactInfoByPatientIdController.php` — hecho cuando: no queda `'message' => $e->getMessage()` en sus respuestas y `ContactInfoTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T040 Aplicar el patrón de error — `PatCtl/MedicalData/CreateMedicalDataController.php`, `PatCtl/MedicalData/UpdateMedicalDataController.php`, `PatCtl/MedicalData/DeleteMedicalDataByPatientIdController.php` — hecho cuando: T013 completo en verde y `MedicalDataTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T041 Añadir `assertCan('patients.view')` a los casos de uso de listado y detalle, y `assertCan('patients.record.view')` al del expediente, inyectando `AuthorizationServiceInterface` — `PatUC/GetPatientsByStatusUseCase.php`, `PatUC/GetPatientByIdUseCase.php`, `PatUC/PatientRecord/GetPatientRecordByPatientIdUseCase.php` — hecho cuando: los casos de lectura de T011 pasan para los 6 actores — cubre: CA2, CA3 — depende: T030, T034, T036, T037
- [ ] T042 Añadir `assertCan('patients.update')` al caso de uso de actualización de paciente — `PatUC/UpdatePatientUseCase.php` — hecho cuando: los casos de `PUT /patients/{id}` de T011, incluida la contraseña, pasan — cubre: CA6, CA7, CA11 — depende: T030, T034, T036
- [ ] T043 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de dirección — `PatUC/Addresses/SaveAddressUseCase.php`, `PatUC/Addresses/UpdateAddressUseCase.php`, `PatUC/Addresses/DeleteAddressByPatientIdUseCase.php` — hecho cuando: los casos de dirección de T011 pasan y `AddressTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T038, T020
- [ ] T044 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de contacto — `PatUC/ContactInfo/SaveContactInfoUseCase.php`, `PatUC/ContactInfo/UpdateContactInfoUseCase.php`, `PatUC/ContactInfo/DeleteContactInfoByPatientIdUseCase.php` — hecho cuando: los casos de contacto de T011 pasan y `ContactInfoTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T039, T020
- [ ] T045 Añadir `assertCan('patients.clinical-data.manage')` a los casos de uso de datos médicos — `PatUC/MedicalData/SaveMedicalDataUseCase.php`, `PatUC/MedicalData/UpdateMedicalDataUseCase.php`, `PatUC/MedicalData/DeleteMedicalDataByPatientIdUseCase.php` — hecho cuando: T011 completo en verde y `MedicalDataTest` sigue verde — cubre: CA2, CA7 — depende: T030, T034, T040, T041, T042, T021

### Appointments
Mismo orden que en Patients: primero los controladores y después los casos de uso.
- [ ] T046 Aplicar el patrón de error — `ApCtl/DeleteAppointmentController.php`, `ApCtl/GetAppointmentByIdController.php`, `ApCtl/GetAppointmentsByPatientIdController.php` — hecho cuando: no queda `'message' => $e->getMessage()` en sus respuestas y `DeleteAppointmentTest` y `GetAppointmentsTest` siguen verdes — cubre: CA12, CA14 — depende: T032
- [ ] T047 Aplicar el patrón de error — `ApCtl/GetTreatmentsController.php`, `ApCtl/CreateAppointmentController.php`, `ApCtl/UpdateAppointmentController.php` — hecho cuando: `agenda/treatments` como asistente o doctor responde 403 en T012, y `CreateAppointmentTest` y `UpdateAppointmentTest` siguen verdes — cubre: CA6, CA7, CA12, CA14 — depende: T032
- [ ] T048 Aplicar el patrón de error — `ApCtl/GetAllApointmentsByStatusAndDateController.php`, `ApCtl/GetTreatmentsAdminController.php`, `ApCtl/GetTreatmentByIdController.php` — hecho cuando: el caso de citas de T014 pasa y `GetAppointmentsTest` y `TreatmentTest` siguen verdes — cubre: CA12, CA14 — depende: T032
- [ ] T049 Aplicar el patrón de error — `ApCtl/CreateTreatmentController.php`, `ApCtl/UpdateTreatmentController.php`, `ApCtl/DeleteTreatmentController.php` — hecho cuando: T014 completo en verde y `TreatmentTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T050 Añadir `assertCan` a los casos de uso de citas: `appointments.create`, `appointments.update` y `appointments.view-detail` — `ApUC/CreateAppointmentUseCase.php`, `ApUC/UpdateAppointmentUseCase.php`, `ApUC/GetAppointmentByIdUseCase.php` — hecho cuando: los casos de `POST /appointments`, `PUT /appointments/{id}` y `GET /appointments/{id}` de T012 pasan (detalle 200 para todo el staff) — cubre: CA2, CA3, CA6, CA7 — depende: T030, T034, T046, T047, T022
- [ ] T051 Añadir `assertCan('appointments.patient-history.view')` al historial por paciente y crear `AuthorizeAgendaSelectorsUseCase` (`final readonly`, `execute()` con `assertCan('agenda.selectors.view')`) — `ApUC/GetAppointentByPatientIdUseCase.php`, `ApUC/AuthorizeAgendaSelectorsUseCase.php` — hecho cuando: los casos de historial de T012 pasan para los 6 actores — cubre: CA2, CA3, CA6, CA7 — depende: T030, T034, T046
- [ ] T052 Hacer que los selectores llamen a `AuthorizeAgendaSelectorsUseCase` antes de leer y aplicar el patrón de error a ellos y a citas del día — `ApCtl/GetPatientsForAppointmentSelectController.php`, `ApCtl/GetDoctorsForAppointmentSelectController.php`, `ApCtl/GetTodayAppointmentsController.php` — hecho cuando: T012 completo en verde (`agenda/patients`, `agenda/doctors` y `agenda/today-appointments` → 403 para asistente y doctor, no 500) — cubre: CA6, CA7, CA10, CA12 — depende: T032, T051

### AppointmentTracking
- [ ] T053 Aplicar el patrón de error — `TrCtl/CompleteAppointmentController.php`, `TrCtl/GetAppointmentTrackingByAppointmentIdController.php`, `TrCtl/UpdateAppointmentTrackingController.php` — hecho cuando: los casos de estas rutas en T016 pasan y `CompleteAppointmentTest` sigue verde — cubre: CA12, CA14 — depende: T032
- [ ] T054 Aplicar el patrón de error — `TrCtl/CreatePrescriptionController.php`, `TrCtl/UpdatePrescriptionController.php`, `TrCtl/DeletePrescriptionController.php` — hecho cuando: T016 completo en verde — cubre: CA12, CA14 — depende: T032

### ContentManagement
- [ ] T055 Aplicar el patrón de error — `CM/Certificaciones/SaveCertificationController.php`, `CM/Certificaciones/UpdateCertificationController.php`, `CM/Certificaciones/DeleteCertificationController.php` — hecho cuando: el caso de certificaciones de T017 pasa — cubre: CA12 — depende: T032
- [ ] T056 Aplicar el patrón de error — `CM/Certificaciones/GetCertificationsController.php`, `CM/Galeria/SaveGalleryImageController.php`, `CM/Galeria/UpdateGalleryImageController.php` — hecho cuando: no queda `'message' => $e->getMessage()` en la respuesta de estos archivos — cubre: CA12 — depende: T032
- [ ] T057 Aplicar el patrón de error — `CM/Galeria/DeleteGalleryImageController.php`, `CM/Galeria/GetGalleryImagesController.php`, `CM/Promociones/SavePromotionController.php` — hecho cuando: el caso de galería de T017 pasa — cubre: CA12 — depende: T032
- [ ] T058 Aplicar el patrón de error — `CM/Promociones/UpdatePromotionController.php`, `CM/Promociones/DeletePromotionController.php`, `CM/Promociones/GetPromotionsController.php` — hecho cuando: el caso de promociones de T017 pasa — cubre: CA12 — depende: T032
- [ ] T059 Aplicar el patrón de error — `CM/Testimonios/SaveTestimonialController.php`, `CM/Testimonios/UpdateTestimonialController.php`, `CM/Testimonios/DeleteTestimonialController.php` — hecho cuando: no queda `'message' => $e->getMessage()` en la respuesta de estos archivos — cubre: CA12 — depende: T032
- [ ] T060 Aplicar el patrón de error — `CM/Testimonios/GetTestimonialsController.php` — hecho cuando: T017 completo en verde — cubre: CA12 — depende: T032

### Frontend
- [ ] T061 Ajustar `roleTabs`: asistente `['inicio', 'expedientes']`, doctor `['inicio', 'expedientes']` (nuevo), paciente `['inicio']` — `resources/views/components/ui/sidebar.blade.php` — hecho cuando: los casos de menú lateral de T019 pasan — cubre: CA4, CA15 — depende: T019, T034, T035
- [ ] T062 Ajustar el panel de inicio: `'doctor' => 'layouts.admin'`; rama `@elseif doctor` con acceso a "Expedientes"; rama asistente sin "Ver Agenda" ni "Registrar Nuevo Paciente"; modal `create-patient-modal` y `create-patient.js` solo para administrador; rama paciente sin enlaces a `/expedientes-clinicos` ni `/agenda` — `resources/views/pages/dashboard.blade.php` — hecho cuando: T019 completo en verde — cubre: CA4, CA15 — depende: T019, T061
- [ ] T063 Expedientes en solo lectura para el doctor: `$canEdit` y `data-records-can-edit` en la vista, botones de formulario solo con `$canEdit`; `index.js` no genera "Eliminar" ni edición si `data-records-can-edit` es `false`; `npm run build`; verificación manual como doctor (sin botones de crear, editar ni eliminar; "Ver" abre el detalle de la cita) — `resources/views/pages/records/index.blade.php`, `resources/js/pages/records/index.js` — hecho cuando: T018 completo en verde y la verificación manual queda anotada en esta tarea — cubre: CA4 — depende: T018, T035

### Cierre de la implementación
- [ ] T064 Comprobar que no queda `'message' => $e->getMessage()` en respuestas JSON de `app/Modules/*/Infrastructure/*/Controllers` (solo en contexto de `Log::`), ejecutar `vendor/bin/pint --dirty --format agent` y la suite de los módulos tocados (`tests/Modules/{Patients,Appointments,AppointmentTracking,ContentManagement,Users,Auth}`) — sin archivos nuevos — hecho cuando: el grep solo devuelve contextos de log, Pint no deja cambios y la suite pasa en Docker — cubre: CA12, CA14 — depende: T036, T037, T038, T039, T040, T041, T042, T043, T044, T045, T046, T047, T048, T049, T050, T051, T052, T053, T054, T055, T056, T057, T058, T059, T060, T061, T062, T063

## Integración y documentación
- [ ] T065 Marcar los riesgos 1 (control de acceso) y 3 (fuga de detalles internos) como mitigados y añadir los controles verificados — `docs/security.md` — hecho cuando: ambos riesgos citan la spec 014 y sus tests — depende: T064
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: sección Backend, "Autenticación y autorización" (middleware `staff`, mapa de permisos por rol, roles con acceso) y manejo de errores (`UnexpectedErrorResponse`, `withExceptions`)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en observability.md, el evento `unexpected_error` (campos, sin `request_id`) y los puntos únicos de 401/403 donde se enganchará la auditoría
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T012 | T030, T034 |
| CA2 | T010, T011, T012 | T030, T041, T043, T044, T045, T050, T051 |
| CA3 | T010, T011, T012 | T030, T041, T050, T051 |
| CA4 | T018, T019 | T035, T061, T062, T063 |
| CA5 | T011, T012 | T031, T034 |
| CA6 | T010, T011, T012 | T030, T042, T047, T050, T051, T052 |
| CA7 | T010, T011, T012 | T030, T042, T043, T044, T045, T047, T050, T051, T052 |
| CA8 | T010, T011, T012 | T030, T031, T034 |
| CA9 | T011, T012, T015 | T033 |
| CA10 | T012, T015 | T033, T052 |
| CA11 | T010, T011 | T030, T036, T042 |
| CA12 | T013, T014, T015, T016, T017 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T052, T053, T054, T055, T056, T057, T058, T059, T060, T064 |
| CA13 | T013 | T032 |
| CA14 | T020, T021, T022 | T036, T037, T038, T039, T040, T046, T047, T048, T049, T053, T054, T064 |
| CA15 | T019 | T061, T062 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T031, T034 | T011, T012 |
| TM2 | T030, T041, T043, T044, T045, T050, T051 | T010, T011, T012 |
| TM3 | T042 | T011 |
| TM4 | T030, T031 | T010, T011, T012 |
| TM5 | T033 | T011, T012, T015 |
| TM6 | T032, T033, T036, T037, T038, T039, T040, T046, T047, T048, T049, T052, T053, T054, T055, T056, T057, T058, T059, T060 | T013, T014, T015, T016, T017 |
| TM7 | T032 | T013 |
| TM8 | T061, T062, T063 | T011, T012, T018, T019 |
| TM9 | T034 | T012 |
| TM10 | T033, T052 | T012, T015 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Core (`EnsureActiveStaff`, `CurrentActorAuthorizationService`, `UnexpectedErrorResponse`) | T030, T031, T032 |
| bootstrap (`bootstrap/app.php`) | T031, T033 |
| routes (`routes/api.php`, `routes/web.php`) | T034, T035 |
| Patients (15 controladores y casos de uso) | T036, T037, T038, T039, T040, T041, T042, T043, T044, T045 |
| Appointments (controladores, selectores y casos de uso) | T046, T047, T048, T049, T050, T051, T052 |
| AppointmentTracking (6 controladores) | T053, T054 |
| ContentManagement (16 controladores) | T055, T056, T057, T058, T059, T060 |
| Frontend (sidebar, dashboard, expedientes) | T061, T062, T063 |
| Tests existentes (cambio de actor) | T020, T021, T022 |
