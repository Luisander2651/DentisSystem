---
spec: 014-control-de-acceso-y-errores
status: approved
created: 2026-09-24
---

# Plan · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Versión 3, corregida con `--fix` tras la ronda 4 de `/analyze` (C1, C4, D1). La v2 (`/plan --redo` tras el primer `/analyze`: A1, A3, A8, A9, A11–A14, A20–A22, A26 y
A29) se ajustó con el segundo `/analyze` (B1, B3, B5, B6, B12, B13, B16, B17, B19 y B21) y la
constitución 1.1.2. Versiones anteriores: [plan.v1.md](plan.v1.md), [plan.v2.md](plan.v2.md).

## Enfoque técnico
La autorización se aplica en dos capas, como pide P5:
1. **Middleware nuevo `EnsureActiveStaff`** en `app/Core` (alias `staff`). Deja pasar solo a staff activo y, opcionalmente, de ciertos roles. Protege los grupos de rutas de pacientes, agenda y citas en la API, y las vistas de expedientes en la web.
2. **`assertCan()` en cada caso de uso** de esas rutas, con un mapa de permisos por rol en `CurrentActorAuthorizationService`. Hoy ese servicio solo concede permisos al administrador. El mapa es la única fuente de verdad de la tabla "Permisos esperados": el middleware filtra por tipo de actor y estado, y el caso de uso decide por operación.

Los errores inesperados se corrigen controlador por controlador con un helper común de `app/Core`,
que registra el error sin datos sensibles y responde `{"error": "Internal server error"}`. Además,
`bootstrap/app.php` recibe una red de seguridad para lo que no se captura en los controladores. Las
excepciones de dominio de Patients dejan de repetir en su mensaje el dato del paciente (CA16).

El frontend solo deja de ofrecer lo que el servidor rechaza. La decisión de qué botones de escritura
aparecen en expedientes se toma en Blade, para poder probarla con Pest (P2).

## Constitution Check
Evaluado contra la constitución **1.1.2**.

| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec 014 `approved`, con su Historial al día. |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y TM tiene un test Pest (Trazabilidad). La ocultación de los botones de escritura se decide en Blade (componentes con prop `$canEdit` y una plantilla `<template>` para "Eliminar"), así que `RecordsScreenTest` la afirma. No hay value objects nuevos ni cambia su comportamiento: solo el texto de sus excepciones. |
| P3 Capas del módulo | ✅ | Los permisos se comprueban en los casos de uso. `GetTreatmentsController` deja de consultar Eloquent directamente y usa `GetTreatmentsUseCase`, que ya existe. `AuthorizeAgendaSelectorsUseCase` evita lógica en el controlador del selector de pacientes. No hay dependencias nuevas entre módulos (los selectores ya usaban casos de uso de Patients y Users). |
| P4 Contrato de API primero | ✅ | Los códigos por actor y los cuerpos de 401, 403 y 500 están en "Contratos y datos". El administrador recibe las mismas respuestas (CA1). El único cambio visible en respuestas de negocio es el texto de los mensajes de Patients (CA16), que no forma parte del contrato de tipos. |
| P5 Autorización en el servidor | ✅ | Constitución 1.1.2: aplica a los endpoints cuya autorización cambia. Son las 25 rutas de pacientes, agenda y citas, que tienen middleware `staff` más `assertCan` por rol y test de acceso denegado por cada actor no autorizado (datasets). Los endpoints de contenido, catálogo y seguimiento clínico solo cambian su manejo de errores y ya tienen `only.admin`. La comprobación de pertenencia no aplica: ningún paciente accede a estas operaciones; llega con la 013 (CA24). |
| P6 Validación de entrada con FormRequest | ➖ | Constitución 1.1.1: P6 aplica cuando cambia la entrada del endpoint. Esta spec solo cambia su autorización y su manejo de errores. |
| P7 Errores sin detalles internos | ✅ | Constitución 1.1.1. Los 500 pasan a respuesta genérica (helper más red global). Los mensajes de negocio se conservan sin datos personales ni de salud (CA14, CA16). |
| P8 Secretos fuera del repositorio | ➖ | Sin variables ni secretos nuevos. |
| P9 Migraciones | ➖ | Sin migraciones. |
| P10 Dependencias con ADR | ➖ | Sin dependencias nuevas. |
| P11 Datos sensibles y modelo de amenazas | ✅ | Modelo de amenazas abajo. El log de errores registra solo clase, archivo, línea y origen, nunca el mensaje, los bindings ni el cuerpo de la petición. En cada controlador tocado se retira todo `Log::` con datos del paciente o con `getMessage()`, incluidos los `Log::info` con teléfono y nombre de `CreateAppointmentController` (l. 49-53) y los `Log::error` de sus catch de negocio. Los mensajes de negocio de Patients dejan de repetir el dato (TM7, TM11, TM12). |
| P12 Producción | ➖ | Sin entorno de producción. Rollback en Rollout. Reduce los riesgos 1 y 3 de security.md. |
| P13 Lógica en el backend | ✅ | Toda restricción se comprueba en el servidor; el frontend solo refleja. Los tests llaman a la API directamente. |
| P14 Trazabilidad | ❌ aceptado: el log de los 500 no lleva `request_id` y los accesos denegados no se auditan hasta la spec de auditoría (objetivo 5) — aprobado por el usuario el 2026-09-24 | La spec difiere los eventos de auditoría (sección "Auditoría") y la correlación no existe aún. El helper y los dos puntos únicos de 401/403 (`EnsureActiveStaff`, `AuthorizationException`) son donde se añadirán. |

Restricciones: las pantallas modificadas (menú lateral, panel de inicio y expedientes) mantienen
WCAG 2.1 AA. Solo se quitan u ocultan controles, sin dejar huecos de foco. Cada tarea de frontend se
revisa con la skill `design` (está en `skills.enabled`) antes de darla por hecha.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Core | **Nuevo** `app/Core/Middlewares/EnsureActiveStaff.php`. Exige `UserModel` con `status = active` y, si recibe parámetros, uno de esos roles (`staff:administrador,asistente,doctor`). En `api/*` responde 403 `{"error"}` con estos textos: no es staff → `"Only staff can access this resource."`; inactivo → `"Your account is inactive."` (el mismo de `OnlyAdmin` y `AuthorizationException`); rol no admitido → `"You are not allowed to access this resource."`. En web, `abort(403)`. | Medio: mal aplicado bloquea al administrador. Lo cubre CA1 (dataset como admin). |
| Core | `app/Core/Authorization/CurrentActorAuthorizationService.php`: la lista `$adminPermissions` pasa a un mapa `permiso → roles`. Los permisos nuevos están en "Contratos y datos"; los existentes siguen siendo solo del administrador. El actor inactivo o que no es staff sigue siendo rechazado primero. | Alto: es la fuente única. Tests de matriz completa. |
| Core | **Nuevo** `app/Core/Http/UnexpectedErrorResponse.php` con `::from(Throwable $e, string $origin): JsonResponse`. Registra `Log::error('unexpected_error', ['origin', 'exception', 'file', 'line'])` sin `message`: las excepciones de dominio y de base de datos pueden llevar datos del paciente. Responde 500 `{"error": "Internal server error"}`. | Bajo: se pierde el mensaje al depurar. Se compensa con clase, archivo y línea. |
| bootstrap | `bootstrap/app.php`: alias `staff`. En `withExceptions`: (1) `shouldRenderJsonWhen` para `api/*`; (2) `AuthorizationException` de Core → 403 `{"error"}` en `api/*`; (3) cualquier otra excepción en `api/*` que no sea `HttpExceptionInterface`, `HttpResponseException`, `ValidationException`, `AuthenticationException` ni `ThrottleRequestsException` → `UnexpectedErrorResponse`; (4) esas mismas excepciones no pasan por el reporte por defecto de Laravel (que registra `getMessage()` y la traza completa): se detiene su reporte en `api/*` para que solo quede el log del helper. | Medio: no debe alterar los 404, 405, 422 ni 429 del framework. Tests de regresión. |
| routes | `routes/api.php`: los grupos `agenda/*`, `patients/*` y `appointments/*` bajo solo `auth:sanctum` pasan a `['auth:sanctum', 'staff']`. Las rutas bajo `only.admin` no cambian (incluido `appointments/{id}/tracking`). | Medio: cobertura del dataset de rutas. |
| routes | `routes/web.php`: `/expedientes-clinicos` y `/expedientes-clinicos/{patientId}` pasan del closure con comprobación de rol a `middleware('staff:administrador,asistente,doctor')`. Esto añade el doctor y exige cuenta activa. | Bajo. |
| Patients | Casos de uso sin `assertCan` → añadir: `GetPatientsByStatusUseCase` y `GetPatientByIdUseCase` (`patients.view`); `GetPatientRecordByPatientIdUseCase` (`patients.record.view`); `UpdatePatientUseCase` (`patients.update`); `Save/Update/Delete{Address,ContactInfo,MedicalData}UseCase` (`patients.clinical-data.manage`). | Medio: `GetPatientsByStatusUseCase` también lo usa el selector de pacientes de la agenda (ver D5). |
| Patients | 15 controladores (`Infrastructure/Http/Controllers/**`) → patrón de error (ver D2). Los catch de negocio (400, 404, 409) no cambian. | Bajo. |
| Patients | Excepciones de dominio cuyo mensaje repite el dato (CA16): `Domain/Exceptions/PatientException.php` (`shouldBeUniqueEmail`), `Domain/Exceptions/ValueObjects/Patients/EmailException.php`, `…/Patients/PatientNameException.php` (2 métodos), `…/ContactInfo/ContactEmailException.php`, `…/ContactInfo/PhoneNumberException.php`, `…/Addresses/PostalCodeException.php` y `…/MedicalData/BloodTypeException.php`. El mensaje pasa a nombrar el campo y el formato esperado, sin el valor (p. ej. "The email is already in use by another patient."; "Invalid phone number format."). La firma de los métodos no cambia. | Bajo: ningún test afirma hoy esos textos. |
| Appointments | Casos de uso: `CreateAppointmentUseCase` (`appointments.create`), `UpdateAppointmentUseCase` (`appointments.update`), `GetAppointmentByIdUseCase` (`appointments.view-detail`), `GetAppointentByPatientIdUseCase` (`appointments.patient-history.view`). **Nuevo** `AuthorizeAgendaSelectorsUseCase` (`agenda.selectors.view`) solo para el selector de pacientes. | Medio. |
| Appointments | `GetTreatmentsController` deja de consultar `TreatmentModel` y usa `GetTreatmentsUseCase` sin filtros, que ya hace `assertCan('treatments.view')`, solo de administrador. Para conservar el orden actual (CA1), `EloquentTreatmentRepository::findAllByIdByName` ordena por `name`. Esto también ordena por nombre el catálogo de administración (`GET /treatments`), que hoy no tiene orden definido y ningún test fija. Un `time` nulo pasa de `null` a `0`, como ya ocurre en `GET /treatments` (declarado en Contratos; lo fija T012). `GetPatientsForAppointmentSelectController` llama a `AuthorizeAgendaSelectorsUseCase` antes de leer. `GetDoctorsForAppointmentSelectController` ya usa `GetUsersByRoleAndStatusUseCase` (`users.view`, solo administrador) y solo necesita el patrón de error (hoy responde 500 al asistente). | Medio: revierte U1 BR-5 (declarado en la spec). |
| Appointments | 11 controladores con el 500 filtrado, más `GetTodayAppointmentsController`, `GetTreatmentsController` y los dos selectores (hoy sin try/catch) → patrón de error. | Bajo. |
| AppointmentTracking | 6 controladores → patrón de error. Sin cambios de permisos. | Bajo. |
| ContentManagement | 16 controladores (`Modules/*/Infrastructure/HTTP/Controllers/**`) → patrón de error. Sin cambios de permisos. | Bajo. Módulo de la Unidad 5 de AI-DLC, en pausa: solo se toca el camino del 500 (decisión del usuario, 2026-09-24). |
| Frontend | `resources/views/components/ui/sidebar.blade.php`: `roleTabs` → asistente `['inicio', 'expedientes']`, doctor `['inicio', 'expedientes']` (nuevo), paciente `['inicio']`. | Bajo. |
| Frontend | `resources/views/pages/dashboard.blade.php`: `'doctor' => 'layouts.admin'` en `$layouts`. Rama nueva `@elseif doctor` con acceso a "Expedientes". Rama asistente sin "Ver Agenda" ni "Registrar Nuevo Paciente". El modal `create-patient-modal` y `create-patient.js` se cargan solo para el administrador. Rama paciente sin enlaces a `/expedientes-clinicos` ni `/agenda`. | Medio: hoy doctor y paciente comparten la rama `@else`. |
| Frontend | `resources/views/pages/records/index.blade.php`: calcula `$canEdit` (administrador o asistente), se lo pasa como prop a `x-records.contact-info-table`, `x-records.address-table` y `x-records.medical-data-table` (`resources/views/components/records/*.blade.php`), que solo renderizan `data-record-open-*-form` con `$canEdit`. Solo con `$canEdit` renderiza `<template data-record-delete-button-template>`. `resources/js/pages/records/index.js` genera "Eliminar" clonando esa plantilla y, si no existe, no genera ninguno (hoy son cadenas HTML en `index.js:385`, `:410`, `:435`). | Medio: 5 archivos de vista más 1 de JS. Se parte en tareas. |
| Tests | Los tests existentes que autentican con cualquier actor (`PatientCrudTest`, `AddressTest`, `ContactInfoTest`, `MedicalDataTest`, `PatientRecordTest`, `CreateAppointmentTest`, `UpdateAppointmentTest`, `GetAppointmentsTest`) pasan a un actor con el rol correcto **en tareas previas a la implementación**. En `GetAppointmentsTest` se reescriben los dos casos que afirman el comportamiento que esta spec corrige: citas del día → 500 y `agenda/treatments` → 200 para no admin. | Medio. |

## Contratos y datos
Sin cambios de modelo de datos ni migraciones. No cambia ningún request ni la forma de las respuestas 2xx, salvo un caso declarado: en
`GET /agenda/treatments`, un `time` nulo (tratamientos anteriores a 2026-06-12, spec 008) se devuelve
como `0`, igual que ya hace `GET /treatments`, porque ahora pasa por el value object `TreatmentTime`.

**Respuestas comunes (todas las rutas de la tabla):**

| Situación | Respuesta |
|---|---|
| Sin sesión | `401 {"message": "Unauthenticated."}` (cuerpo por defecto de Laravel), también sin `Accept: application/json` |
| Paciente, staff inactivo o rol sin permiso | `403 {"error": "<motivo>"}`; el motivo no revela datos del recurso |
| Error inesperado | `500 {"error": "Internal server error"}`, sin `message`, traza ni SQL |
| Errores de negocio | 400, 404, 409 y 422 con su mensaje actual; en Patients, sin el valor del dato (CA16) |

**Permisos (mapa de `CurrentActorAuthorizationService`):**

| Permiso | Rutas | Admin | Asistente | Doctor |
|---|---|---|---|---|
| `patients.view` (nuevo) | `GET /patients`, `GET /patients/{id}` | ✓ | ✓ | ✓ |
| `patients.record.view` (nuevo) | `GET /patients/{patientId}/record` | ✓ | ✓ | ✓ |
| `appointments.patient-history.view` (nuevo) | `GET /appointments/patient/{patientId}` | ✓ | ✓ | ✓ |
| `appointments.view-detail` (nuevo) | `GET /appointments/{id}` | ✓ | ✓ | ✓ |
| `patients.clinical-data.manage` (nuevo) | `POST/PUT/DELETE /patients/{patientId}/{address,contact-info,medical-data}` | ✓ | ✓ | — |
| `patients.update` (nuevo) | `PUT /patients/{id}` (incluye `new_password`) | ✓ | — | — |
| `patients.create`, `patients.delete` | `POST /patients`, `DELETE /patients/{id}` (ya bajo `only.admin`) | ✓ | — | — |
| `appointments.view` | `GET /appointments`, `GET /agenda/today-appointments` | ✓ | — | — |
| `appointments.create` (nuevo), `appointments.update` (nuevo), `appointments.delete` | `POST /appointments`, `PUT /appointments/{id}`, `DELETE /appointments/{id}` | ✓ | — | — |
| `agenda.selectors.view` (nuevo) | `GET /agenda/patients` | ✓ | — | — |
| `users.view` | `GET /agenda/doctors` (a través de `GetUsersByRoleAndStatusUseCase`) | ✓ | — | — |
| `treatments.view` | `GET /agenda/treatments` (a través de `GetTreatmentsUseCase`) | ✓ | — | — |

`appointments.update` cubre también el cambio de estado (`PUT /appointments/{id}`). Los permisos de
usuarios, catálogo, contenido y seguimiento clínico no cambian.

**Web:** `GET /expedientes-clinicos[/{patientId}]` responde:
- 200 para administrador, asistente y doctor activos;
- 403 para el resto con sesión;
- redirección a `/login` sin sesión (comportamiento actual).

**Contrato entre capas:** no hay tipos compartidos. El contrato vive en esta sección y en los datasets
de acceso, que fijan cada código por actor.

## Estrategia de pruebas
- **Integración (Pest, `tests/Modules/<Módulo>/Integration`):**
  - `Patients/Integration/PatientsAccessControlTest.php` (nuevo):
    - dataset de las 15 rutas de pacientes × 6 actores (admin, asistente, doctor, staff inactivo, paciente, sin sesión);
    - en los rechazos, afirma que la base no cambió;
    - `PUT /patients/{id}` con `new_password`: el hash cambia solo como admin;
    - las rutas `api/v1/patients*` con middleware exactamente `staff`, más `POST` y `DELETE /patients`, coinciden con el dataset (`Route::getRoutes()` filtrado por prefijo y middleware).
  - `Appointments/Integration/AppointmentsAccessControlTest.php` (nuevo):
    - las 10 rutas de `agenda/*` y `appointments/*` bajo `staff`;
    - `GET /appointments/{id}/tracking` como asistente y doctor → 403;
    - `agenda/today-appointments` y `agenda/doctors` como asistente y doctor → 403, no 500;
    - comparación con `Route::getRoutes()` filtrado por prefijo `api/v1/agenda*` y `api/v1/appointments*` y middleware exactamente `staff`;
    - el administrador recibe `agenda/treatments` ordenado por nombre.
  - `UnexpectedErrorTest.php` (nuevos) en Patients, Appointments (citas y catálogo), AppointmentTracking y ContentManagement:
    - un repositorio enlazado lanza `RuntimeException` → cuerpo exacto `{"error": "Internal server error"}`;
    - `ContentManagement` necesita una base nueva, `ContentManagementIntegrationTestCase`.
  - Log sin datos sensibles (CA13), en el test de Patients, con `Log::spy()` y un repositorio enlazado en una ruta sin catch de negocio para la excepción lanzada:
    - una `QueryException` con un teléfono y una alergia de prueba en los bindings;
    - una `RuntimeException` cuyo mensaje contiene un email de prueba (simula una excepción de dominio con datos que llega al catch genérico: TM11);
    - se registra `unexpected_error` con `exception` y `origin`, y ningún `Log::` contiene los valores de prueba.
  - `Appointments/Integration/GlobalErrorFallbackTest.php` (nuevo), red global, sobre rutas bajo `api/v1` registradas solo dentro del test (sin try/catch), para que siga probando la red aunque los controladores capturen:
    - excepción no capturada → 500 genérico;
    - `AuthorizationException` no capturada → 403;
    - un 422 de FormRequest y un 429 conservan su forma;
    - 401 JSON sin `Accept`.
  - `Patients/Integration/PatientBusinessMessagesTest.php` (nuevo, CA16): alta con email duplicado → 409, y alta con email del paciente inválido, y contacto, dirección, datos médicos y paciente con teléfono, email de contacto, nombre, código postal y tipo de sangre inválidos → 400. Ningún cuerpo contiene el valor enviado.
  - `Patients/Integration/RecordsScreenTest.php` (nuevo, web):
    - doctor → 200, `data-records-can-edit="false"`, sin `data-record-open-*-form` ni `data-record-delete-button-template`;
    - asistente → con todos ellos;
    - paciente e inactivo → 403;
    - sin sesión → redirección a `/login`.
  - `Users/Integration/StaffNavigationTest.php` (nuevo, web): `/dashboard` y menú lateral por rol, con `assertSee` y `assertDontSee` de `href="/agenda"`, `href="/expedientes-clinicos"` y `data-create-patient-open`.
- **Matriz de permisos:** `Users/Integration/RolePermissionsTest.php` (nuevo): cada permiso × rol × activo o inactivo.
- **Regresión:** los tests existentes, actualizados a actores con rol **antes** de implementar, siguen verdes, y con ellos los mensajes de negocio (CA14). `UsersAuthorizationTest` y `RateLimitAndErrorLeakTest` no cambian.
- **JS:** `index.js` solo clona la plantilla. Que exista o no lo decide Blade y lo afirma `RecordsScreenTest`. Aun así, se verifica a mano como doctor que no aparezcan botones de escritura y que "Ver" abra el detalle.
- **Estáticas:** `grep -rn "getMessage()" app/Modules --include=*Controller.php` solo en contextos de log o en catch de negocio; `vendor/bin/pint --dirty`; `./vendor/bin/pest --parallel` completo; `aidd.py validate`.

## Modelo de amenazas
Flujo:
1. El navegador envía la cookie `auth_token`.
2. `InjectSanctumTokenFromCookie` la pasa como token.
3. `auth:sanctum` acepta tokens de `UserModel` **y** de `PatientModel`.
4. `staff` → controlador → caso de uso (`assertCan`) → repositorio → PostgreSQL.

Salidas: respuesta JSON o Blade, y `storage/logs/laravel.log`.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un paciente autorregistrado usa su token para leer o cambiar datos de salud de otros o crear citas ajenas | A01:2025 | `routes/api.php` (grupos bajo solo `auth:sanctum`) | Middleware `staff` rechaza a quien no es `UserModel`; `assertCan` también (defensa en profundidad) | Datasets de acceso, actor paciente → 403 sin cambios en la BD |
| TM2 | Elevation: asistente o doctor ejecutan operaciones fuera de su rol (agenda y sus selectores, crear o eliminar pacientes, editar datos clínicos siendo doctor) | A01:2025 | Casos de uso de Patients y Appointments; `GetTreatmentsController` | Mapa permiso → roles, denegado por defecto; `GetTreatmentsController` pasa por su caso de uso | Datasets (asistente y doctor → 403, incluido `agenda/treatments`) y `RolePermissionsTest` |
| TM3 | Tampering / Spoofing: un asistente cambia la contraseña de un paciente para suplantarlo | A01:2025, A07:2025 | `PUT /patients/{id}` (`new_password`) | `patients.update` solo para el administrador | `PatientsAccessControlTest`: `new_password` como asistente o doctor → 403 y hash intacto; como admin → 200 y hash cambiado |
| TM4 | Spoofing / Elevation: un exempleado desactivado sigue usando un token vigente | A07:2025 | `EnsureActiveStaff`, `CurrentActorAuthorizationService` | Ambos comprueban `status = active` | Datasets, actor inactivo → 403 |
| TM5 | Spoofing: acceso sin sesión | A07:2025 | `auth:sanctum` | 401 JSON forzado en `api/*` | Datasets, sin sesión → 401; `GlobalErrorFallbackTest` sin `Accept` |
| TM6 | Information disclosure: el 500 revela el mensaje técnico, la traza o el SQL | A10:2025 | 48 controladores; controladores sin try/catch; `APP_DEBUG=true` | `UnexpectedErrorResponse` más la red global en `api/*`, que no depende de `APP_DEBUG` | `UnexpectedErrorTest` de cada área y `GlobalErrorFallbackTest` |
| TM7 | Information disclosure: el log del error guarda datos del SQL (bindings) | A09:2025 | `UnexpectedErrorResponse`; red global y reporte por defecto de Laravel; `Log::` de los controladores tocados, incluidos los `Log::info` con teléfono y nombre de `CreateAppointmentController` | El helper no registra `message`; el reporte por defecto se detiene en `api/*`; se retira todo `Log::` con `getMessage()`, traza o datos del paciente en los controladores tocados | `Patients/UnexpectedErrorTest` y `GlobalErrorFallbackTest` con `QueryException` y `Log::spy`; `CreateAppointmentTest` con `Log::spy` |
| TM8 | Elevation por UI: confiar en que ocultar el menú impide la acción | A06:2025 | Menú lateral, panel de inicio y expedientes | Toda restricción se aplica en el servidor | Datasets directos a la API; `StaffNavigationTest` y `RecordsScreenTest` |
| TM9 | Information disclosure: asistente o doctor obtienen el seguimiento clínico desde el detalle de una cita, que ahora pueden ver | A01:2025 | `GET /appointments/{id}/tracking` | Sigue bajo `only.admin` y `appointment-tracking.view` solo de administrador | `AppointmentsAccessControlTest`: tracking → 403 |
| TM10 | Fallo abierto: un error de autorización se convierte en 500 (citas del día y selector de doctores) | A10:2025 | Controladores sin try/catch | `AuthorizationException` → 403 en el controlador y en la red global | `AppointmentsAccessControlTest` (CA10) |
| TM11 | Information disclosure: una excepción de dominio con el dato del paciente en su mensaje (email, teléfono, nombre, tipo de sangre) llega al log de un error inesperado | A09:2025 | `UnexpectedErrorResponse`, red global y reporte por defecto de Laravel | El helper registra solo clase, archivo, línea y origen; el reporte por defecto se detiene en `api/*`; y las excepciones de Patients dejan de llevar el valor | `Patients/UnexpectedErrorTest`: `RuntimeException` con un email de prueba + `Log::spy`; `GlobalErrorFallbackTest` sin captura + `Log::spy` |
| TM12 | Information disclosure / enumeración: el mensaje de conflicto o de validación repite el email, teléfono o tipo de sangre de un paciente | A01:2025, A04:2025 | Excepciones de dominio de Patients | Mensajes que nombran el campo sin el valor | `PatientBusinessMessagesTest` |

Riesgos residuales (fuera de alcance):
- **Repudio:** los accesos denegados no quedan registrados hasta la spec de auditoría.
- **Trazas en la web:** las vistas web con `APP_DEBUG=true` siguen mostrando trazas, porque la red global solo cubre `api/*` (RS10 de security.md).
- **Guard sin provider:** el guard `sanctum` sigue sin provider fijo; lo compensa el middleware `staff`.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el administrador conserva todas las operaciones | Core (mapa), routes | Datasets, actor admin |
| CA2: el asistente lista y ve pacientes, abre expedientes, ve historial y detalle de cita y gestiona datos clínicos | Core, Patients, Appointments | Datasets, actor asistente → 2xx |
| CA3: el doctor lista y ve pacientes, abre expedientes y ve historial y detalle de cita | Core, Patients, Appointments | Datasets, actor doctor → 2xx |
| CA4: el doctor usa expedientes en solo lectura, con acceso en el menú y en el inicio | routes/web, Frontend (records, componentes, sidebar, dashboard) | `RecordsScreenTest`, `StaffNavigationTest` |
| CA5 (abuso): el paciente es rechazado | Core (`EnsureActiveStaff`), routes | Datasets, actor paciente (TM1) |
| CA6 (abuso): el asistente es rechazado fuera de su rol | Core (mapa), Patients, Appointments (incluido `GetTreatmentsController`) | Datasets, actor asistente (TM2, TM3) |
| CA7 (abuso): el doctor es rechazado en escrituras y agenda | Core (mapa), Patients, Appointments | Datasets, actor doctor (TM2, TM3) |
| CA8 (abuso): el staff inactivo es rechazado | Core | Datasets, actor inactivo (TM4) |
| CA9 (abuso): sin sesión → 401 | bootstrap | Datasets, `GlobalErrorFallbackTest` (TM5) |
| CA10 (abuso): citas del día como asistente o doctor → 403, no 500 | Appointments (controladores), bootstrap | `AppointmentsAccessControlTest` (TM10) |
| CA11: el admin cambia la contraseña; asistente y doctor no | Core (`patients.update`), Patients | `PatientsAccessControlTest`, `new_password` (TM3) |
| CA12 (abuso): error inesperado genérico en todas las áreas | Core (helper), 48 controladores, bootstrap | `UnexpectedErrorTest` ×4, `GlobalErrorFallbackTest` (TM6) |
| CA13: el log del error no contiene datos de salud ni de contacto | Core (helper), catch genéricos | `Patients/UnexpectedErrorTest` con `Log::spy` (TM7, TM11) |
| CA14: se conservan los mensajes de negocio, sin datos personales | Controladores (catch de negocio intactos), excepciones de Patients | Tests existentes de 400, 404 y 409, y `PatientBusinessMessagesTest` |
| CA15: menú e inicio sin pantallas ni acciones no permitidas | Frontend (sidebar, dashboard) | `StaffNavigationTest` (TM8) |
| CA16 (abuso): los errores de negocio no repiten el dato del paciente | Patients (excepciones de dominio) | `PatientBusinessMessagesTest` (TM12) |

Cobertura de amenazas:

| Amenaza | Cambio(s) | Test(s) |
|---|---|---|
| TM1 | Core, routes | Datasets, actor paciente |
| TM2 | Core, Patients, Appointments | Datasets, `RolePermissionsTest` |
| TM3 | Core, Patients | `PatientsAccessControlTest` (`new_password`) |
| TM4 | Core | Datasets, actor inactivo |
| TM5 | bootstrap | Datasets, `GlobalErrorFallbackTest` |
| TM6 | Core, controladores, bootstrap | `UnexpectedErrorTest` ×4, `GlobalErrorFallbackTest` |
| TM7 | Core, bootstrap, controladores | `Patients/UnexpectedErrorTest`, `GlobalErrorFallbackTest`, `CreateAppointmentTest` |
| TM8 | Frontend | Datasets, `StaffNavigationTest`, `RecordsScreenTest` |
| TM9 | routes (sin cambio) | `AppointmentsAccessControlTest` |
| TM10 | Appointments, bootstrap | `AppointmentsAccessControlTest` |
| TM11 | Core, bootstrap, Patients | `Patients/UnexpectedErrorTest`, `GlobalErrorFallbackTest` |
| TM12 | Patients | `PatientBusinessMessagesTest` |

## Observabilidad
- **Logs nuevos:** `unexpected_error` → nivel `error` → campos `origin`, `exception`, `file` y `line`. Sin `message` ni `request_id`: la correlación no existe aún (desviación de P14 aceptada por el usuario el 2026-09-24). Se eliminan los `Log::error` previos de los catch genéricos, que registraban `getMessage()` y trazas.
- **Eventos de auditoría:** ninguno (la spec los difiere). Los 401 y 403 salen de dos puntos únicos, `EnsureActiveStaff` y `AuthorizationException`. Ahí se engancharán.
- **Métricas y alertas:** ninguna (brecha 7 de observability.md).
- **Verificación tras el deploy:** no hay deploy. En local, tras forzar un error, `grep unexpected_error storage/logs/laravel.log` sin datos de pacientes.

## Rollout
- **Feature flag:** no aplica. No hay producción ni datos reales, y un flag mantendría abierta la vulnerabilidad que la spec cierra.
- **Orden de despliegue:** un solo paso, backend y Blade/JS juntos, con `npm run build`. Sin migraciones.
- **Compatibilidad:** el administrador no nota cambios; los demás actores pierden lo que la tabla no permite.
- **Rollback:** `git revert` del merge y redeploy. No hay datos ni esquema que revertir.
- **Métricas a vigilar tras el deploy:** tasa de 403 y 500 en `/api/v1/patients*` y `/api/v1/appointments*` en los logs de nginx (Loki), cuando exista producción.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Dónde se decide el permiso.** Opciones:
  - (a) solo middleware por rol;
  - (b) Gates o Policies;
  - (c) middleware de tipo de actor más `assertCan` con mapa por rol.

  **Elegida (c):** es el patrón existente, cumple P5 y deja una fuente única. Sin ADR.
- **D2 · Errores inesperados y patrón de controlador.** Helper común más red global. "Aplicar el patrón de error" a un controlador significa:
  - `catch (AuthorizationException)` → 403 `{"error"}` antes del catch genérico;
  - el catch genérico queda **solo** con `return UnexpectedErrorResponse::from($e, self::class);`, retirando el `Log::error` previo;
  - se retira todo `Log::` del controlador que registre datos del paciente o `getMessage()` (también en los catch de negocio y los `Log::info` de éxito);
  - las respuestas de los catch de negocio no se tocan.
- **D3 · Qué se registra de un error.** Solo clase, archivo, línea y origen. Registrar `getMessage()` filtra los bindings de `QueryException` y los valores de las excepciones de dominio (P11). Es un punto único para añadir `request_id` más adelante. Para que sea el **único** registro, la red global también detiene el reporte por defecto de Laravel de las excepciones que atiende en `api/*`, que escribiría `getMessage()` y la traza por su cuenta (C1).
- **D4 · 401 en JSON en `api/*`.** Con `shouldRenderJsonWhen`, para que la API responda igual a cualquier cliente.
- **D5 · Selectores de la agenda.** Cada uno se resuelve distinto:
  - `agenda/doctors`: ya pasa por `GetUsersByRoleAndStatusUseCase` (`users.view`, solo administrador); solo le falta el patrón de error.
  - `agenda/treatments`: pasa a `GetTreatmentsUseCase` (`treatments.view`), en lugar de consultar Eloquent.
  - `agenda/patients`: reutiliza `GetPatientsByStatusUseCase`, que ahora es `patients.view` (todo el staff). Para dejarlo solo para el administrador sin lógica en el controlador, llama antes a `AuthorizeAgendaSelectorsUseCase` (`agenda.selectors.view`).
- **D6 · Botones de escritura en expedientes.** Descartado: decidirlo solo en JS, porque no se puede probar sin runner. Elegido: Blade decide con la prop `$canEdit` en los componentes y una `<template>` de "Eliminar" que el JS clona. Así `RecordsScreenTest` lo afirma (P2).
- **D7 · Mensajes de negocio de Patients.** Se cambian en las excepciones de dominio, no en los controladores: la excepción es la dueña del texto y así ningún consumidor (API, log, futura app) recibe el valor.

## Impacto en arquitectura
- `docs/architecture.md` → Backend, "Autenticación y autorización": middleware `staff`, mapa de permisos por rol y roles con acceso.
- `docs/architecture.md` → Backend: manejo de errores centralizado (`UnexpectedErrorResponse` y `withExceptions`).
- `docs/security.md`, estados por corrección: RS1.a y RS1.d → mitigada; RS1.b y RS1.c siguen pendientes (spec 013, CA24 y CA23), así que RS1 queda **parcialmente** mitigado hasta la 013; RS3.a → mitigada (RS3 mitigado); nota de RS9.a (la 014 retira los logs de `CreateAppointmentController`). También las secciones "Autenticación y autorización" (permisos por rol, middleware `staff`, fin de los closures) y "Superficie de ataque" (las filas que hoy dicen "cualquier actor autenticado").
- `docs/observability.md`: OB2.a y OB10.a → mitigada; evento `unexpected_error`.
- `docs/architecture.md`: fila Core de la tabla de módulos (`EnsureActiveStaff`, `UnexpectedErrorResponse`).
- `docs/roadmap.md` → "Pendientes y deuda": mensajes y logs de Users y Auth con el email o `getMessage()`. Propuestas para que el usuario confirme: retirar la deuda "Desalineación interfaz/backend", ya resuelta, y ajustar el criterio "0 respuestas con `$e->getMessage()`" del objetivo 1 a P7 1.1.1.
- Specs 001, 005, 006, 007, 008, 009, 011 y 012: nota en su Historial en `/release` (lista en la spec, Notas para /plan).

## Riesgos y mitigaciones
- **Olvidar una ruta o un caso de uso.** Los datasets se comparan con `Route::getRoutes()` filtrado por middleware `staff`.
- **Romper al administrador.** CA1 con el mismo dataset y los tests existentes.
- **La red global cambia respuestas del framework.** Solo actúa sobre excepciones que no son HTTP, de validación, de autenticación ni de throttle; hay tests de regresión.
- **Tests existentes acoplados al actor genérico.** Se actualizan en tareas propias antes de implementar, y pasan antes y después. Los dos casos de `GetAppointmentsTest` que afirman el comportamiento corregido se reescriben.
- **Conflicto con la spec 013.** Se implementa después de la 014 (decisión del usuario, 2026-09-24) y la extiende. Sus rutas de paciente irán fuera del grupo `staff` (la comparación de rutas filtra por middleware). Su CA23 cambiará de 403 a probablemente 401 el código de un paciente en rutas de staff, y actualizará los datasets de esta spec. Toca los mismos controladores de citas. Queda anotado en la 013.
- **ContentManagement sin tests previos y con AI-DLC en pausa.** Solo cambia el catch genérico. `ContentManagementIntegrationTestCase` es mínimo y no cumple por sí solo el criterio del objetivo 2 del roadmap.
- **Se pierde el mensaje de la excepción al depurar.** La clase, el archivo y la línea bastan en la mayoría de los casos, y `request_id` llegará con la spec de auditoría.
- **Riesgos residuales:** repudio, trazas en la web y guard sin provider (ver Modelo de amenazas).
