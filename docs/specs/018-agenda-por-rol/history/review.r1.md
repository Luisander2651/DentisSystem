---
spec: 018-agenda-por-rol
verdict: changes_requested
round: 1
date: 2026-10-18
base: 2a6f200
head: afe1416
human_signoff: pending
---

# Review · 018 Agenda por rol

## Resumen
Sin bloqueantes: los trece criterios tienen su test y pasan, los controles TM1–TM7 están en el
código y sus tests prueban de verdad el rechazo y que nada cambia, y las herramientas de seguridad
salen limpias. Se piden cambios por dos huecos de prueba (R1, R2) que el usuario decidió corregir
ahora, junto con tres ajustes baratos de tests (R3, R4, R10); son solo tests, sin cambio de
comportamiento. El resto de los menores va al roadmap.
Conteo: bloqueante 0 · importante 2 · menor 10

Revisores independientes: spec (A), constitución y calidad (C+D), seguridad (S) y accesibilidad y
diseño, en subagentes de contexto limpio sobre `code.diff`. La sesión no ofrece el plugin `design`
(`design:accessibility-review`, `design:design-critique`): el revisor aplicó la lista de la skill
y lo contrastó con las capturas de `docs/design/capturas/`.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php -d memory_limit=1G ./vendor/bin/pest` (serie) | ✅ 1443 en verde, 0 fallos (línea base: 1 fallo ajeno, `DeployScriptsBehaviourTest`, que no se repitió) |
| `vendor/bin/pint --test` sobre los PHP de la spec | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` completo, dos veces (T033) | ✅ tras T034: solo las entradas sin línea base fallaban; `--only agenda,expedientes,error-403` (45 visitas) y los diálogos de la agenda (60 visitas) sin fallos. Intermitente ajeno a la 018: `contenido-promociones-eliminar` → roadmap (2026-10-18) |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `AgendaByRoleTest` (listados y citas de hoy del asistente) · `AgendaScreenByRoleTest` · ui-audit `agenda-asistente` | ✅ | |
| CA2 | `AgendaByRoleTest` (201, 409, 422) · `AgendaScreenByRoleTest` (selectores) · ui-audit `agenda-cita-crear-asistente` | ✅ | R10: el 409 del asistente no afirma el mensaje |
| CA3 | `AgendaByRoleTest` (reprogramar y cancelar) · ui-audit `agenda-cita-detalle-pendiente-asistente` | ✅ | |
| CA4 | `AppointmentsAccessControlTest` · `DoctorTrackingTest` · `CompleteAppointmentTest` · ui-audit `agenda-asistente` | ✅ | R1: el alcance `none` del asistente en `/agenda` no tiene test de Pest |
| CA5 | `AgendaByRoleTest` (solo sus citas) · `AgendaScreenByRoleTest` · ui-audit `agenda-doctor`, `agenda-doctor-hoy-sin-citas` | ✅ | |
| CA6 | ui-audit `agenda-cita-detalle-pendiente-doctor`, `agenda-doctor` · verificación manual (T029, T030) | ✅ | "Completar" va en la tarjeta y el detalle solo tiene "Cerrar", como fija `design.md` |
| CA7 | `AppointmentsAccessControlTest` (tabla y "whatever the body") | ✅ | R2: `POST` del doctor solo con cuerpo válido |
| CA8 | `AgendaByRoleTest` (`user_id`, `status`, `date` de otro doctor) | ✅ | |
| CA9 | `DoctorTrackingTest` (200 y 404) · `RecordsScreenTest` · ui-audit `agenda-cita-detalle-doctor*` | ✅ | R1: el alcance `own` en `/agenda` no tiene test de Pest |
| CA10 | `DoctorTrackingTest` (403 exacto, sin datos clínicos, sin cambios) | ✅ | R4 |
| CA11 | `AppointmentsAccessControlTest` · `DoctorTrackingTest` · `CompleteAppointmentTest` · `WebAccessDeniedPagesTest` | ✅ | |
| CA12 | `DoctorTrackingTest` (completa su cita con recetas) · ui-audit `agenda-cita-completar-doctor` | ✅ | |
| CA13 | `DoctorTrackingTest` (cita de otro doctor → 403, sin cambios) | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). La corrección del orden de
  los diálogos (nota de T029) está dentro de `resources/views/pages/agenda/index.blade.php`.
- Partes del plan sin implementar: ninguna. T001, T035 y T036 son solo documentación y T032 una
  comprobación, por eso no aparecen en `code.diff`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño, plan y tareas aprobados antes de `impl_base` (2a6f200) |
| P2 Test que falla antes y pasa después | ✅ | Tests de T010–T019 con `skip` retirados por cada tarea; comprobaciones rompiendo el control anotadas (TM2–TM6, D11, orden de diálogos) |
| P3 Capas del módulo | ✅ | `Domain/` tocado sin `Illuminate` ni `Infrastructure`; sesión solo vía `AuthorizationServiceInterface` |
| P4 Contrato de API primero | ✅ | Tests de contrato por actor (T012–T015) antes de T020–T027 |
| P5 Autorización en el servidor | ✅ | `staff` + `assertCan`/`can` en cada caso de uso; pertenencia comparada con `currentActorId()` |
| P6 Validación con FormRequest | ➖ | Sin entradas nuevas |
| P7 Errores sin detalles internos | ✅ | 500 genérico; los 403 no llevan datos clínicos (R8: nombran el permiso, patrón de 014) |
| P8 Secretos fuera del repositorio | ✅ | gitleaks sin hallazgos en 38 commits |
| P9 Migraciones reversibles | ➖ | Sin migraciones |
| P10 Dependencias nuevas con ADR | ➖ | Sin dependencias nuevas (`deps.stat` vacío) |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM7 con control y test |
| P12 Producción con aprobación humana | ✅ | Pendiente de `/release` |
| P13 Lógica de negocio en el backend | ✅ | La vista solo decide qué ofrecer; la API aplica las reglas (R6: capacidades por nombre de rol en Blade) |
| P14 Trazabilidad y auditoría | ❌ aceptado | Hasta el objetivo 5 (usuario, 2026-10-17); puntos de emisión en `observability.md` |
| P15 Solo el sistema de diseño | ✅ | `DesignTokensTest`, `OnlyDesignTokensTest` y ui-audit en verde; sin valores sueltos |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.30.1 (`2a6f200..HEAD`) | ✅ 0 hallazgos (38 commits) |
| semgrep 1.178.0 `p/php` (`app config bootstrap routes`) | ✅ 0 hallazgos (salida 0) |
| `composer audit` · `npm audit --audit-level=high` | crít: 0 · alta: 0 · media: 0 |
| trivy 0.74.0 `config docker/` | ✅ 0 HIGH/CRITICAL (la spec no toca `docker/` ni `docker-compose.yml`) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso · escritura de citas (TM1) | ✅ | `CurrentActorAuthorizationService` (create/update: administrador y asistente; delete y mark-completed: administrador); `assertCan` antes de validar; `AppointmentsAccessControlTest` |
| A01 IDOR en listados (TM2) | ✅ | El alcance sale de `currentActorId()`, no de la petición; `AgendaByRoleTest` con `user_id` de otro doctor |
| A01 IDOR al leer seguimiento (TM3) | ✅ | `GetAppointmentTrackingByAppointmentIdUseCase` compara el dueño; `DoctorTrackingTest` (403 exacto) |
| A01 IDOR al completar (TM4) | ✅ | `CompleteAppointmentUseCase` compara el dueño antes de escribir; `DoctorTrackingTest` con instantánea |
| A01 Escalada del asistente (TM5) | ✅ | `UpdateAppointmentUseCase` exige `appointments.mark-completed` |
| A01 Edición de seguimiento (TM6) | ✅ | `appointment-tracking/*` sigue en `only.admin` + `assertCan` |
| A01/A07 Actores no staff (TM7) | ✅ | `auth:sanctum` + `staff`; `EnsureActiveStaff` |
| A05 Inyección (XSS) | ✅ | Seguimiento con `textContent`; tarjetas con `escapeHtml` |
| A10 Errores | ✅ | Cuerpos de 403/404/409 sin datos de pacientes (R8 menor) |
| A03 Cadena de suministro | ✅ | Sin cambios en lockfiles |
| Minimización de datos | ➖ | R9: el selector de doctores devuelve email, rol y estado del staff |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no; P14 aceptado hasta el objetivo 5, con
  los puntos de emisión en `docs/observability.md` (T091).
- Datos sensibles en logs del código tocado: ninguno; el diff no añade logs.
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Modules/Appointments/Integration/AgendaScreenByRoleTest.php` · `resources/views/pages/agenda/index.blade.php:12,79` | Ningún test de Pest comprueba en `/agenda` el alcance del registro clínico (`all`/`own`/`none`) ni el `actorId` del detalle; solo `RecordsScreenTest` lo hace en el expediente. Pasar `all` al asistente no rompería ningún test de Pest | Caso por rol en `AgendaScreenByRoleTest`, como en `RecordsScreenTest` |
| R2 | importante | `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php:122,172-194` | CA7 y TM1 piden "con datos válidos o no", pero `POST /appointments` del doctor solo se prueba con un cuerpo válido; si se validara antes de autorizar, el doctor recibiría 422 y ningún test fallaría | Doctor × `POST` con cuerpos inválidos → 403 y tabla sin cambios |
| R3 | menor | `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php:~153` | Para actores permitidos en `/complete` y `/tracking` la tabla solo exige `not->toBeIn([401, 403])`: un 500 pasaría | Conjunto cerrado de estados esperados |
| R4 | menor | `tests/Modules/AppointmentTracking/Integration/DoctorTrackingTest.php:133,140` | "edit the follow-up" envía un cuerpo de receta y solo afirma `assertForbidden()` sin cuerpo | Cuerpo de seguimiento válido y cuerpo exacto del rechazo |
| R5 | menor | `app/Modules/Appointments/Aplication/UseCases/GetAllApointmentsByStatusAndDateUseCase.php:~45-61` · `GetTodayAppointmentsUseCase.php:~31-47` | `doctorScope()` duplicado letra por letra | Extraer a un colaborador compartido |
| R6 | menor | `resources/views/pages/agenda/index.blade.php:6-12` · `resources/views/pages/records/index.blade.php:7-12` | Las capacidades de la vista se deducen de nombres de rol y duplican el mapa de permisos; `trackingScope` está repetido con sintaxis distinta | Derivarlas de `can()` o de un view composer |
| R7 | menor | `app/Core/Authorization/AuthorizationServiceInterface.php:16-20` · `CurrentActorAuthorizationService.php:51-60` | El docblock de `currentActorId()` habla del staff activo, pero el método no comprueba estado ni rol (hoy siempre va detrás de un `can()`) | Ajustar el docblock o validar dentro |
| R8 | menor | `app/Core/Authorization/Exceptions/AuthorizationException.php:18` | Los 403 nuevos nombran el permiso interno (`appointment-tracking.view-own`…); patrón heredado de 014 | `/fix` transversal con mensaje genérico en español |
| R9 | menor | `app/Modules/Appointments/Aplication/UseCases/GetAgendaDoctorsUseCase.php:39` (vía `UserResource`) | El selector de doctores, ahora también del asistente, devuelve `email`, `role_id` y `status` del staff; el diálogo solo usa id y nombre | Resource propio del selector |
| R10 | menor | `tests/Modules/Appointments/Integration/AgendaByRoleTest.php:83` | El choque de horario del asistente solo afirma 409, sin el mensaje que CA2 pide igual | Afirmar el mensaje de error |
| R11 | menor | `resources/js/pages/agenda/view-appointment.js:265-269` | Si falla la carga del registro clínico (salvo 404) se crea un `role="alert"` aparte de la región del diálogo; ahora también llega el doctor | Texto sin `role` y anuncio con `announceError` |
| R12 | menor | `resources/js/pages/agenda/view-appointment.js:160` | "Prescripciones" es `h3` dentro de la sección "Registro clínico", también `h3` | `h4` |

Descartado: "Completar" no está en el pie del detalle del doctor (CA6). `design.md` lo coloca en la
tarjeta y deja solo "Cerrar" en el detalle; coincide con el código y con la verificación manual.

## Preparación para release
- Rollback factible: sí; `docker/prod/rollback.sh` a la imagen anterior, sin datos que revertir
  (las citas del asistente siguen siendo válidas para el administrador).
- Migraciones compatibles con la versión anterior: no hay migraciones ni cambios de configuración.
- Feature flags: no aplica (D3, usuario, 2026-10-17).
- Docs actualizadas: `architecture.md` (T090), `observability.md` (T091) y `security.md` (T036);
  `deployment.md` sin cambios necesarios.
- Vulnerabilidades críticas o altas de SCA: ninguna.

## Tareas añadidas
- T041 ← R1
- T042 ← R2, R3
- T043 ← R4, R10

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-18:
- **R5** → roadmap: `doctorScope()` duplicado en los dos casos de uso de listados.
- **R6** → roadmap: capacidades de la agenda y del expediente deducidas de nombres de rol en Blade.
- **R7** → roadmap: contrato de `currentActorId()` (docblock frente a lo que comprueba).
- **R8** → roadmap: los 403 nombran el permiso interno (transversal desde 014).
- **R9** → roadmap: el selector de doctores devuelve email, rol y estado del staff.
- **R11** → roadmap: `role="alert"` aparte al fallar la carga del registro clínico.
- **R12** → roadmap: "Prescripciones" como `h3` dentro de "Registro clínico".
