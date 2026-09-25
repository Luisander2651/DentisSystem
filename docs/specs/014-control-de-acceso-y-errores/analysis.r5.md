---
result: pass
round: 5
mode: delta
constitution_version: 1.1.2
date: 2026-09-24
spec_sha: 4983cab9c060
plan_sha: 6c6ff66851f0
tasks_sha: c9a933042c25
---

# Análisis · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Ronda 5, **delta** sobre la ronda 4 ([analysis.r4.md](analysis.r4.md)). Revisa las correcciones
aplicadas con `--fix` (C1, C4, C14, D1–D4 y D6), unas 43 líneas de spec, plan y tareas. No hay
cambio estructural: ningún módulo, contrato ni criterio nuevo (CA15 y CA16 solo cambian de orden).

## Resumen
**pass.** Los 8 hallazgos corregidos quedan resueltos, incluido el único ALTO previo (C1). La
corrección es coherente con Laravel 12: se detiene el reporte por defecto en `api/*` sin silenciar
el log del helper. La corrección de D2 dejó un efecto nuevo (D7, ALTA): el test pedía más de lo que
cubre la spec. Se acepta con una nota que acota el test, junto con D8 (BAJA). La comprobación
crítica de cobertura de riesgos pasa.

Abiertos: 0. Aceptados en esta ronda: 1 ALTO · 1 BAJO. Siguen vigentes los aceptados de la ronda 4.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 |
| Amenazas con control y test | 12 / 12 (TM7 y TM11 con la red global incluida) |
| Correcciones de riesgo "dentro" con cambio y test | 5 / 5 (OB2.a con el alcance de D7) |
| Correcciones de riesgo "fuera" con destino real | 11 / 11 |
| Principios de la constitución evaluados | 16 / 16 (ronda 3; sin cambios de constitución) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| C1 | ALTA | resuelto | Plan, fila bootstrap, punto (4), y D3; TM7 y TM11 con red global; T033 detiene el reporte por defecto en `api/*`; T015 con `Log::spy` y `QueryException` |
| C4 | MEDIA | resuelto | "Contratos y datos" declara `time` nulo → `0` en `agenda/treatments`, y también la spec (nota a 008); T012 lo fija y T047 lo verifica. El código se comporta así (`TreatmentTime::fromInt((int) "")` = 0, dentro de rango) |
| D1 | MEDIA | resuelto | Plan, T065 y T091 con estados por corrección; RS1 "parcialmente mitigado"; nota de RS9.a |
| D2 | MEDIA | resuelto con efecto nuevo (→ D7) | Fila OB2.a → P11 y caso `Log::spy` en T022, verificado en T047 |
| D3 | MEDIA | resuelto | RS10.a → objetivo 4; el objetivo 4 lista RS10.a y OB5.b |
| D4 | MEDIA | resuelto | Entradas de RS5.a, RS8.a, RS10.b y OB10.b (whatsApp) en "Pendientes y deuda"; la spec y T071 las citan |
| C14 | BAJA | resuelto | CA15 antes que CA16 |
| D6 | BAJA | resuelto | Fila RS1.a sin CA15 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| D7 | Cobertura / inconsistencia | ALTA | T022, T047; fila OB2.a de la spec | El caso `Log::spy` de T022 afirma que **ningún** log de `POST /appointments` contiene el teléfono ni el nombre. Pero la misma petición los registra fuera del controlador: `RetriveDataForScheduledAppointmenEventUseCase.php:53-56`, `CreatedAppointmentListener.php:37-40`, `SendAppointmentConfirmationUseCase.php:19-21,33-35` y el job y `TwilioConection` (cola `sync` en tests, `phpunit.xml:39`). Son OB2.b, fuera de alcance, así que el test nunca pasaría. | Aceptado con nota (abajo). |
| D8 | Cobertura de riesgos | BAJA | `docs/security.md` RS10; `docs/observability.md` OB5; T065, T091 | Los registros de riesgos no reflejan los destinos nuevos del roadmap: OB5.b sigue apuntando al objetivo 5, y RS10.a y RS10.b no tienen destino. | Aceptado con nota (abajo). |

Descartado: un hallazgo sobre el objetivo 1 del roadmap, que no lista OB2.a ni OB10.a. Duplica D5,
ya aceptado en la ronda 4 como nota de T071.

## Decisiones pendientes del usuario
- Ninguna.

## Aceptados
Aceptados por el usuario el 2026-09-24; se resuelven en `/implement` como nota de la tarea indicada:
- **D7:** el caso `Log::spy` de `CreateAppointmentTest` (T022) solo comprueba los logs cuyo mensaje empieza por `CreateAppointmentController`: ninguno contiene el teléfono ni el nombre del paciente de prueba. Los logs que la misma petición escribe desde `RetriveDataForScheduledAppointmenEventUseCase`, `CreatedAppointmentListener`, `SendAppointmentConfirmationUseCase`, `ConfirmationAppointmentMessage` y `TwilioConection` son OB2.b (fuera de alcance → roadmap objetivo 5) y el test no los afirma. El criterio de T047 ("el caso de `Log::spy` pasa") se lee con ese alcance. Nota de T022 y T047.
- **D8:** T065 y T091 también ponen en `security.md` y `observability.md` los destinos nuevos: RS10.a y OB5.b → objetivo 4; RS10.b → roadmap, Pendientes y deuda.
- Siguen vigentes los aceptados de la ronda 4 ([analysis.r4.md](analysis.r4.md), "Aceptados"): C2, C3, C5–C11, C13 y D5, como notas de T052, T064, T066, T013, T022, T061, T020, T021, T063, T065 y T071.
