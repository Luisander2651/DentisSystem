---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-10-17
spec_sha: 1c0579ed8584
plan_sha: b628c615d7d8
tasks_sha: 2ceb687c8ef2
---

# Análisis · 018 Agenda por rol

## Resumen
Falla por un hallazgo CRÍTICO: el plan abre al asistente solo uno de los tres selectores que
necesita para agendar (A1), así que CA2 no se puede cumplir tal como está planeado. Hay además
cinco ALTOS: el diálogo de agendar depende de un `isAdmin` que nadie cambia (A2), el archivo de
datos de prueba es otro (A3), el contrato de completar da 404 donde el código da 409 (A4) y faltan
los tests de acceso denegado del seguimiento para paciente, inactivo y sin sesión (A5, P5).
Revisión hecha por un subagente independiente; cada hallazgo se comprobó contra el código.
Conteo: CRÍTICA 1 · ALTA 4 · MEDIA 4 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 13 / 13 sobre el papel; CA2 no se puede cumplir (A1, A2) |
| Amenazas con control y test | 6 / 7 (TM7 sin test en `/tracking`, A5) |
| Principios de la constitución evaluados | 15 / 15 (P5 incumplido en `/tracking`, A5; P14 ❌ aceptado) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | CRÍTICA | `plan.md` → Cambios por módulo (Core) y Contratos (fila de selectores); T015, T021, T023 | Solo `/agenda/patients` usa `agenda.selectors.view` (`AuthorizeAgendaSelectorsUseCase`). `/agenda/doctors` autoriza con `users.view` (`GetUsersByRoleAndStatusUseCase`) y `/agenda/treatments` con `treatments.view` (`GetTreatmentsUseCase`), ambos solo del administrador. Con el plan actual, el asistente sigue recibiendo 403 en esos dos selectores y no puede agendar (CA2) | Los tres selectores de la agenda responden 200 al asistente y 403 al doctor, sin dar al asistente `users.view` ni `treatments.view` (gestión de usuarios y catálogo, fuera de alcance). Test: `GetAppointmentsTest` y `AppointmentsAccessControlTest` por selector y por actor, más un caso que confirme que el asistente sigue con 403 en `GET /users` y en `/treatments/admin`. → `/plan 018 --fix A1` y `/tasks 018 --fix A1` |
| A2 | Inconsistencia | ALTA | `plan.md` → Interfaz · agenda; T029, T032 | `create-appointment-modal.blade.php` (prop `isAdmin`) y `create-appointment.js` (`data-create-appointment-is-admin`) solo muestran y cargan los selectores de paciente y doctor con `true`. Ninguna tarea los cambia, y el `grep` de T032 fallaría si se les pasa `true` | El asistente ve y usa los selectores de paciente y doctor en "Agendar cita", y no queda ningún `isAdmin` en la agenda. Test: `AgendaScreenByRoleTest` (selectores en el HTML para el asistente) y ui-audit de agendar como asistente. → `/plan --fix`, `/tasks --fix` |
| A3 | Inconsistencia | ALTA | `plan.md` → Datos de prueba de la interfaz; T018 | Los datos están en `database/seeders/UiAuditSeeder.php` (`seedAppointments()`), no en `UiAuditDataCommand.php`, que solo llama al seeder. Con el archivo correcto, T018 tocaría 4 archivos | Citar el seeder y dividir T018 (seeder + su test; `screens.json`). → `/plan --fix`, `/tasks --fix` |
| A4 | Inconsistencia | ALTA | `plan.md` → Contratos (fila `POST /complete`) y Cambios (AppointmentTracking); T014, T025 | El contrato dice "404 si no existe", pero `CompleteAppointmentController` responde 409 a `AppointmentException::notFound` y `CompleteAppointmentTest` afirma ese 409. Ninguna tarea cambia el controlador | El contrato fija el código que el test de T014 afirma para una cita inexistente. Decisión pendiente abajo. → `/plan --fix` |
| A5 | Constitución (P5) | ALTA | `plan.md` → TM7 y Trazabilidad; T013, T014, T017 | `GET /appointments/{id}/tracking` sale de `only.admin` y cambia su autorización, pero ninguna tarea prueba el acceso denegado del paciente, del staff inactivo y del visitante sin sesión en esa ruta. `POST /complete` sí los tiene | Cada actor no autorizado recibe el rechazo esperado en `/tracking`, con el cuerpo exacto. Test: casos nuevos en T014. → `/plan --fix`, `/tasks --fix` |
| A6 | Inconsistencia | MEDIA | `AppointmentsAccessControlTest.php` (cobertura de rutas, casos "anyone but the administrator" y today/selectores); T013, T027 | El test de cobertura de rutas excluye las rutas `only.admin` y exige que todas las demás de `appointments*` estén en su tabla. Tras T027 entran `/complete` y `/tracking`, y la tabla no las tiene (`UnhandledMatchError`). Otros dos casos también cambian de expectativa | T013 enumera cada caso que cambia, incluidas las dos rutas nuevas, y T027 depende de T013. → `/tasks --fix` |
| A7 | Ambigüedad | MEDIA | `plan.md` → Contratos (fila `GET /appointments`); CA5, CA8; T012 | Un listado vacío responde 409 (`notFoundByFilters` → `GetAllApointmentsByStatusAndDateController`) y `index.js` lo muestra como error. Un doctor sin citas, o con un filtro de estado vacío, vería un error en lugar de "No tienes citas para hoy." | El contrato fija la respuesta vacía, y T012 y ui-audit (`agenda-doctor-hoy-sin-citas`) la prueban. Decisión pendiente abajo. → `/plan --fix` |
| A8 | Inconsistencia | MEDIA | `spec.md` → Requisitos no funcionales (último); `plan.md` D2 | El RNF pide no revelar si existe una cita de otro doctor, pero D2 (decisión del usuario) responde 403 a la de otro doctor y 404/409 a la inexistente. La existencia de la cita no es secreta (el detalle es de todo el staff, 014); el seguimiento sí | El RNF dice que los errores no revelan el contenido del seguimiento ni datos personales, y anota en Decisiones que la existencia de la cita no es secreta. → `/specify --edit 018` |
| A9 | Inconsistencia (extends) | MEDIA | `spec.md` frontmatter y "Notas para /plan" | No lista los criterios que cambia de cada spec extendida: 014 CA6, CA7, CA10, CA15 y su supuesto de los selectores; 017 CA10 y su "Fuera de alcance"; 009 CA7 y CA10. Además cambia 008 CA3 (catálogo para la agenda), y 008 no está en `extends` | La spec enumera los criterios que cambia de cada spec extendida y añade 008 a `extends`. `/release` anota el Historial de cada una (contrato). → `/specify --edit 018` |
| A10 | Inconsistencia | BAJA | `plan.md` → Constitution Check, P9 | `appointments.user_id` no está indexado: la migración `2026_08_24_120000` solo indexa `(date, status)` y `status`. `user_id` es solo una clave foránea | Corregir la justificación; no hace falta migración (volumen de prototipo). → `/plan --fix` |
| A11 | Inconsistencia | BAJA | `plan.md` → P14; `spec.md` → Auditoría | El plan cita la desviación de las specs "014 y 016" y la spec, "014 y 017" | Unificar las referencias. → `/plan --fix` |
| A12 | Inconsistencia | BAJA | `plan.md` → Riesgos (tests que cambian); T017 | `OnlyAdminApiContractTest` no cambia (T017 lo confirma). Faltan en la lista `AppointmentsAccessControlTest` y `RolePermissionsTest` | Corregir la lista. → `/plan --fix` |
| A13 | Duplicación | BAJA | T010 | Ya existe `tests/Modules/Users/Unit/Domain/CurrentActorAuthorizationServiceTest.php`; T010 crea otro con el mismo nombre en `Core/Unit/Authorization` | Ampliar el existente. → `/tasks --fix` |
| A14 | Ambigüedad | BAJA | T018; `plan.md` → Estrategia de pruebas | "Completar como doctor" no tiene id en `screens.json`. El `now` de `screens.json` es 2026-07-22, y no se dice cómo se obtiene un "hoy sin citas" para el doctor | Dar id a la entrada y fijar la fuente del día sin citas (otro `now` por entrada, otro doctor u otra fecha). → `/tasks --fix` |
| A15 | Ambigüedad | BAJA | `spec.md` CA8; `plan.md` D2 | `GET /appointments/{id}` y `GET /appointments/patient/{id}` siguen dando al doctor citas de otros doctores, sin datos clínicos (014). El detalle está declarado; el historial del paciente no. "Por otra vía" de CA8 puede leerse como que los incluye | CA8 se limita a los listados de la agenda y nombra los dos endpoints como excepciones conscientes. → `/specify --edit 018` |
| A16 | Constitución (Decisiones) | BAJA | `design.md` → Decisiones (primera fila) | La fila `implícita` cita `references/spec-mode.md` §1, archivo del plugin que no está en el repo | Citar la skill (`/design-spec`, regla de exploración) o pasar la fila a tipo `diseño`. → `/design-spec 018 --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-10-17:
- **A4:** `POST /appointments/{id}/complete` sobre una cita inexistente sigue respondiendo 409,
  como hoy; se corrige el contrato del plan (decisión del usuario, 2026-10-17).
- **A7:** un listado de citas vacío responde 200 con lista vacía para todos los roles; la agenda
  muestra el texto vacío y se actualizan los tests que afirman el 409 (decisión del usuario,
  2026-10-17).

## Aceptados
Ninguno todavía.
