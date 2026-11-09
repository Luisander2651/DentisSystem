---
result: pass
round: 2
mode: delta
constitution_version: 1.2.0
date: 2026-11-04
spec_sha: d5b05532a959
plan_sha: 8a2e9358116e
tasks_sha: 5868a2a95122
---

# Análisis · 024 QR de cita para el acceso

## Resumen
**Pass.** Ronda delta, sin subagente. Se revisaron las correcciones de A1–A8 de la ronda 1
([history/analysis.r1.md](history/analysis.r1.md)) en la spec, el plan y las tareas, y se buscaron
restos de lo corregido: no queda una CSP propia, ni el `canvas` a 8×, ni la aserción de tiempo
sobre la petición HTTP, ni "igual que la consulta de la cita". Las menciones de "300 ms" que
quedan son el RNF de la spec y su trazabilidad hacia T010, y la de "modo oscuro" en P15 es la
aclaración de A8. Las correcciones no crean dependencias nuevas entre tareas, no añaden archivos
fuera del plan (T016 suma `checks-dialogs.mjs`, que está en "Cambios por módulo" como prueba de
navegador) y no cambian el alcance.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0 (nuevos en esta ronda)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 7 / 8 (TM2 fuera de Core, riesgo aceptado R1) |
| Principios de la constitución evaluados | 15 / 15 (sin cambios desde la ronda 1) |

## Seguimiento de rondas anteriores
### Ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | spec CA4 y "Decisiones"; plan → Enfoque, P6, contrato (400 `Invalid appointment id.`), Trazabilidad CA4, D9; T013 y T022 con `InvalidArgumentException` → 400. El 500 del detalle queda para `/fix` (D9) |
| A2 | ALTA | resuelto | plan → contrato (cabeceras globales de `SecurityHeaders`), nota del contrato, TM6, Estrategia; T013 afirma la CSP global y `nosniff`; T022 sin CSP propia |
| A3 | MEDIA | resuelto | T016 añade la expectativa `image` a `checks-dialogs.mjs` y la demuestra antes de usarla; el nombre del archivo queda en `data-view-appointment-qr-filename` (D3, T023) y el flujo de error usa `answers` |
| A4 | MEDIA | resuelto | plan → D3 (`canvas` de 1024 px, fondo blanco, `drawImage` con tamaño); T023 y su verificación manual sobre fondo oscuro; spec K3 incluye el PNG |
| A5 | BAJA | resuelto | T015 generaliza el ayudante y suma `chillerlan` al patrón |
| A6 | BAJA | resuelto | T010 mide < 150 ms por QR; T013 sin aserción de tiempo; plan → TM8 y RNF |
| A7 | BAJA | resuelto | T011 comprueba que `AppointmentId::random()` es UUID versión 4; plan → TM3 |
| A8 | BAJA | resuelto | spec K3 sin "modo oscuro"; plan → P15 lo aclara |

## Hallazgos nuevos
Ninguno.

## Siguiente paso
`/implement`, empezando por T001.
