---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-10-23
spec_sha: 487172d42ea9
plan_sha: 70efdd9d2c61
tasks_sha: 8e33ca0ac3ed
---

# Análisis · 020 Tablero de inicio

## Resumen
**Fail.** No hay hallazgos CRÍTICOS, pero sí 7 ALTOS que el usuario decidió corregir. Dos tocan
el comportamiento: "Reprogramar" sin diálogo en el detalle abierto desde el inicio (A1) y datos de
prueba que no producen lo que miden los criterios de diseño (A2). Uno es la línea base de 1440 px
que la tarea no puede reescribir y que depende de la hora (A3). Uno es una desviación de P3 sin
excepción (A6). Tres son pruebas que faltan (A4, A5, A7). Las decisiones de la spec (A1, A9, A10,
A16, A17) van por `/specify --edit` antes de corregir el plan.

Análisis de un subagente independiente (perfil `critical`). Los hallazgos ALTOS se verificaron
en el código: `view-appointment.js:226-232`, `create-appointment.js:639-645`,
`ui-audit.mjs:778-795`, `baseline-1440.json:82-84`, `UiAuditSeederTest.php` y
`dashboard.blade.php:33,52,56`.

Conteo: CRÍTICA 0 · ALTA 7 · MEDIA 12 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (siete en parte: CA1, CA2, CA4, CA7, CA9, CA10, CA12) |
| Amenazas con control y test | 8 / 8 (con los matices de A8 y A24) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | `plan.md` → Cambios por módulo (Interfaz · pantalla); `spec.md` CA2 y "Fuera de alcance" | El detalle reutilizado muestra "Reprogramar" en cuanto existe `agendaAppointmentModal.openEdit`, que el diálogo de agendar define siempre. Sin `edit-appointment-modal` en el inicio, el botón no hace nada. Además, CA2 ("las acciones que el rol tiene en la agenda") choca con "Reprogramar desde el inicio" en "Fuera de alcance" | `/specify --edit`: el detalle del inicio ofrece Reprogramar (decisión del usuario). Después, `/plan --fix` y `/tasks --fix`: ningún rol ve un botón que no funciona. Test: entrada de ui-audit `dashboard-detalle` (administrador y asistente) que reprograma desde el detalle |
| A2 | Cobertura | ALTA | T002, T046, T050; `design/criterios.json` K3 | K3 exige "y 5 más", es decir, 15 citas sin cerrar. `UiAuditSeeder` siembra 3 o 4, y T002 no siembra las 15. `evaluate` sobre `/dashboard` local no puede pasar | Los datos de `ui:audit-data` producen lo que suponen los criterios: 15 sin cerrar del administrador, siguiente cita, completadas sin seguimiento por doctor. Test: `evaluate` con `criterios.json` y `criterios-doctor.json` en T046 |
| A3 | Cobertura / riesgo | ALTA | T051; `plan.md` D14 | `baseline-1440.json` ya congela `dashboard-administrador`, `-asistente` y `-doctor`, y `writeBaseline` solo reescribe entradas nuevas o de una diferencia admitida, que T051 no registra. Con citas relativas a la hora del sembrado, la composición a 1440 px cambia según la hora | Diferencia admitida registrada con su criterio (CA11, c5) y la aprobación del usuario. La composición de `dashboard-*` no depende de la hora. Test: `npm run test:ui -- --only dashboard` con datos sembrados a horas distintas da el mismo resultado |
| A4 | Constitución (P2) | ALTA | T048; `plan.md` → Estrategia de pruebas | `?buscar=1` (foco en el buscador, CA9) no tiene ninguna prueba que falle antes; T048 solo pide "mismo resultado" y una verificación manual | Entrada de ui-audit de `/expedientes-clinicos?buscar=1` que compruebe el foco en el buscador: falla antes de T048 y pasa después |
| A5 | Constitución (P2) / Cobertura | ALTA | `spec.md` → Criterios de diseño (estados a `npm run test:ui`); T026 | Ninguna tarea prueba en navegador los textos de vacío de CA1, CA2, CA4 y CA7, el estado de carga de CA12 ni que "Reintentar" restablezca la sección | Flujos de ui-audit con respuestas vacías por sección (texto exacto de la spec), estado de carga visible y "Reintentar" que restablece el contenido; fallan antes de T046 |
| A6 | Constitución (P3) | ALTA | `plan.md` P3 y "Rutas"; T042 | `ShowDashboardPageController` entrega rol y capacidades a la vista sin caso de uso, y el plan no registra la excepción de P3. La 019 sí la registró para el mismo patrón (A35) | Excepción de P3 registrada con la aprobación del usuario (decisión del usuario): la vista no lleva datos protegidos. Test: `DashboardScreenTest` por rol, incluido el staff inactivo |
| A7 | Constitución (P11) | ALTA | `plan.md` P11 y Trazabilidad (RNF de logs) | La prohibición "logs sin datos de pacientes ni clínicos" solo se verifica a mano | Test de integración que captura el log durante el registro y los errores forzados y afirma que no contiene los valores sembrados de paciente ni del seguimiento |
| A8 | Inconsistencia | MEDIA | `plan.md` TM1; T038 | TM1 nombra `DoctorScope` como control de `/agenda/without-tracking`, pero `DoctorScope` se basa en `appointments.view`, que el asistente tiene. El plan usa `appointment-tracking.create/-own` para esa lectura | TM1 nombra el control real de cada lectura. Test: `CompletedWithoutTrackingTest` (asistente 403, doctor con `user_id` ajeno) |
| A9 | Inconsistencia / Cobertura (CA15) | MEDIA | `spec.md` CA15; `plan.md` → Rollout (`POST /complete` igual) | `POST /complete` también registra el seguimiento de una cita completada sin él, y en una cita con seguimiento o cancelada responde en inglés y con el id | `/specify --edit`: CA15 incluye `/complete` (decisión del usuario). Mensajes en español y sin id. Test: casos en `CompleteAppointmentTest` |
| A10 | Ambigüedad | MEDIA | `spec.md` CA10; `plan.md` → `GET /reports/monthly` | "Hasta hoy" contradice "los mismos números que Reportes", que cuenta todo el mes. El plan usa `previous_label` donde la 019 da `previous_short_label`, y el formato del texto difiere entre spec y plan | `/specify --edit`: todo el mes, como Reportes, con el texto "42 citas · +6 respecto a septiembre (36)" (decisión del usuario). Test: `DashboardMatchesReportsTest` y ui-audit sobre el texto pintado |
| A11 | Cobertura (CA10) | MEDIA | `plan.md` → Estrategia de pruebas; T022, T026 | El plan dice que ui-audit compara las cifras de la pantalla con `reports/monthly`, pero T026 no tiene ese paso | Paso en `dashboard-administrador` que compara las cifras pintadas con las de `/reportes` del mismo mes |
| A12 | Ambigüedad / RNF | MEDIA | `plan.md` D4, D7; T021 | Que la 019 mida menos de 3 s no implica que quepa en 2 s. T021 suma tiempos de servidor, no "el inicio se muestra". La aserción de reloj real repite 019/R8 | Definir qué se mide y con qué margen. Test: `DashboardPerformanceTest` (o una medida de ui-audit si es la carga) |
| A13 | Seguridad / riesgos | MEDIA | T041; `plan.md` → Contratos; `observability.md` (punto único de 403) | El contrato no fija el cuerpo del 403. El patrón actual captura `AuthorizationException` en el controlador, se salta el punto único donde se engancharán los accesos denegados y nombra el permiso interno (018/R8) | Los 403 nuevos salen por el punto único con un cuerpo fijo; 018/R8 se declara dentro o fuera con su destino. Test: un caso de `DashboardAccessControlTest` que afirma el cuerpo |
| A14 | Cobertura de riesgos | MEDIA | `plan.md` → Riesgos; `security.md` RS5.a | `POST /appointments/{id}/tracking` es una entrada nueva que cambia estado y depende de `SameSite=lax`, y el plan no menciona RS5 | Riesgos del plan: RS5.a fuera, con motivo y destino; ninguna tarea lo declara mitigado |
| A15 | Cobertura (P2) | MEDIA | T002; `tests/Modules/Core/Integration/UiAuditSeederTest.php` | Ese test afirma que todas las citas sembradas son de julio de 2026 y cuántas completó el doctor. T002 lo rompe y ninguna tarea lo actualiza | T002 actualiza `UiAuditSeederTest` (citas de hoy, completadas sin seguimiento por doctor, idempotencia), primero en rojo y después en verde |
| A16 | Inconsistencia | MEDIA | `plan.md` → Interfaz · pantalla; T052 (cierra 016/R43) | La vista tiene una sola sección de título y el paciente también ve la tarjeta con la inicial recortada: o su título cambia (contra CA16) o R43 sigue abierto | `/specify --edit`: el paciente ve "Inicio - Dentissa" y su inicial completa; el resto de su inicio no cambia (decisión del usuario). Test: caso del paciente en `DashboardScreenTest` |
| A17 | Ambigüedad | MEDIA | `plan.md` → Pantalla `/dashboard`; T042 | Con el staff inactivo, `/dashboard` mostraría la rama del staff con todas las secciones en error | `/specify --edit`: el staff inactivo recibe la vista "Sin permiso" (403), como en `/agenda` (decisión del usuario). Test: caso en `DashboardScreenTest` |
| A18 | Constitución (P3) | MEDIA | T027 | El test de arquitectura no cubre todos los archivos de `Domain/` que tocan otras tareas: `AppointmentsService` (T031), `AppointmentCompletionRepositoryInterface` y `AppointmentCompletionService` (T039) | Los tests de arquitectura cubren todo archivo de `Domain/` que la spec crea o modifica |
| A19 | Ambigüedad (P4/P6) | MEDIA | `plan.md` → `POST /tracking`; T017 | "Mensajes en español (los de los campos de completar)", pero completar responde con mensajes en inglés de los value objects; los textos en español solo existen en el cliente | El contrato lista el mensaje exacto de cada campo; T017 lo afirma campo por campo |
| A20 | Inconsistencia | BAJA | `plan.md` → diálogos reutilizados; T047 | En modo registro, la confirmación seguiría diciendo "¿Completar esta cita?" y "Sí, completar cita" | Textos de confirmación del modo registro en el plan; `dashboard-registrar-seguimiento` los comprueba |
| A21 | Ambigüedad | BAJA | `spec.md` CA8, CA9 | "Registrar seguimiento" nombra dos operaciones: completar la siguiente cita (acción rápida) y registrar en una completada (lista) | Etiqueta y título del diálogo coherentes con la operación de cada botón; ui-audit comprueba el título del diálogo que abre cada uno |
| A22 | Ambigüedad | BAJA | T026 ("tras 13 s") | Una espera sin origen en el plan ni en la spec | Quitarla o explicar su origen; el flujo comprueba el bloque de error sin un tiempo arbitrario |
| A23 | Redacción | BAJA | `plan.md` P3 y AppointmentTracking · Infrastructure | El precedente citado para leer `appointments` desde Infrastructure es inexacto, y `doctor_name` del contrato nuevo frente a `user_name` del recurso existente nombran el mismo dato | Corregir el precedente y declarar la diferencia de nombre |
| A24 | Cobertura | BAJA | Cobertura de TM4; T012 | La ruta de T038 falta en el control de TM4, y T012 prueba casos de uso de AppointmentTracking desde la carpeta de tests de Appointments | T038 en TM4; el test repartido por módulo |

## Decisiones pendientes del usuario
Resueltas el 2026-10-23:
- **A1:** el detalle del inicio ofrece Reprogramar con el diálogo de la agenda; sale de "Fuera de
  alcance".
- **A3:** se admite la diferencia de composición del inicio a 1440 px (CA11, c5) y se rehace la
  línea base.
- **A6:** excepción de P3 para `ShowDashboardPageController`, como `/reportes` en la 019.
- **A9:** `/complete` entra en CA15: mensajes en español y sin id.
- **A10:** el mes cuenta como Reportes (todo el mes), con el texto "42 citas · +6 respecto a
  septiembre (36)".
- **A16:** el paciente ve "Inicio - Dentissa" y su inicial completa; el resto no cambia.
- **A17:** el staff inactivo recibe la vista "Sin permiso" (403) en `/dashboard`.
- **MEDIA y BAJA:** se corrigen en el mismo `/plan --fix` y `/tasks --fix`, no como notas.

## Aceptados
Ninguno.

## Siguiente paso
1. `/specify --edit 020`: A1, A9, A10, A16 y A17 (CA2, CA10, CA11, CA15, CA16 y "Fuera de
   alcance").
2. `/plan 020 --fix A1–A24`.
3. `/tasks 020 --fix A1–A24`.
4. `/analyze 020` (delta).
