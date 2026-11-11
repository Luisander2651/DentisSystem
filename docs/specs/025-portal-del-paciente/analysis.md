---
result: pass
round: 2
mode: delta
constitution_version: 1.2.1
date: 2026-11-10
spec_sha: c7efbe149370
plan_sha: 36c177fa9848
tasks_sha: e3ca499062a4
---

# Análisis · 025 Portal del paciente

## Resumen
`pass`. Los trece hallazgos de la ronda 1 que pedían corrección quedaron resueltos con las
decisiones del usuario del 2026-11-10, y los siete aceptados tienen su nota en la tarea indicada.
Los cambios solo añaden dos hallazgos bajos, aceptados como notas.

Modo delta **sin subagente**, a petición del usuario: la ronda con revisor independiente se detuvo
antes de terminar y el usuario pidió hacerla en delta. Se revisaron, releídas desde cero, las líneas
cambiadas entre `0b30d39` y `b0f6966` (constitución 1.2.1, spec, `design.md`, plan y tareas) y sus
referencias. El cambio de la constitución es la aclaración de P6 que pidió A1, así que no hizo falta
un análisis completo.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 2

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 20 / 20 |
| Amenazas con control y test | 11 / 12 (TM12 es riesgo aceptado, plan R4) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | `docs/constitution.md` 1.2.1 (P6); filas P6 de `plan.md` y `tasks.md` |
| A2 | ALTA | resuelto | T002 incluye `layouts/error.blade.php` y exige `NotFoundPageTest` y `WebAccessDeniedPagesTest` en verde |
| A3 | ALTA | resuelto | `spec.md` `extends: [001, 009, 016, 020, 024]` y Decisiones del 2026-11-10; T030 ajusta `RoleCopyTest` y `DashboardScreenTest` |
| A4 | ALTA | resuelto | T020 añade `/mis-citas` a `GuestRedirectTest` |
| A5 | ALTA | resuelto | T027 (comprobaciones de primera pantalla, tamaño de imagen y CLS); T030 depende de ella |
| A6 | ALTA | resuelto | `design.md` → Decisiones: fuente `usuario` (confirmado el 2026-11-10) |
| A7 | MEDIA | resuelto | `plan.md` D7 y "Estrategia de pruebas"; T011 con peticiones reales en 401 y 403 |
| A8 | MEDIA | resuelto | T030 y T031: estados vacíos con respuestas simuladas |
| A9 | MEDIA | resuelto | `spec.md` CA11, CA12 y Decisiones del 2026-11-10; `plan.md` D4 |
| A10 | MEDIA | aceptado | Notas en T015 y T017 |
| A11 | MEDIA | aceptado | Notas en T002 y T018 |
| A12 | MEDIA | aceptado | Notas en T019 y T050 |
| A13 | MEDIA | resuelto | T022 y T024 sin `[P]`; T024 depende de T025 |
| A14 | BAJA | aceptado | Nota en T020 |
| A15 | BAJA | resuelto | `plan.md` "Vistas que leen el guard": `layouts/admin` no cambia |
| A16 | BAJA | aceptado | Nota en T020 |
| A17 | BAJA | resuelto | `spec.md` "Cobertura de riesgos": RS1.d `fuera` (mitigada en v0.1.0) |
| A18 | BAJA | resuelto | Nota del plugin `design` eliminada de `plan.md` |
| A19 | BAJA | aceptado | Notas en T017 y T032 |
| A20 | BAJA | aceptado | Notas en T010, T015 y T016 |

Ronda anterior: [history/analysis.r1.md](history/analysis.r1.md).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Inconsistencia | BAJA | `plan.md` "Cambios por módulo → Pruebas de navegador"; T030, T031 | El plan aún presenta `paciente.vacio` como cuenta de los estados vacíos, y las tareas usan respuestas simuladas (A8) | Nota en T030: los estados vacíos usan respuestas simuladas |
| A22 | Pruebas que no prueban | BAJA | T027 | "Una entrada de prueba falla" no puede quedarse en `screens.json`: dejaría `ui-audit` en rojo | Nota en T027: el fallo se demuestra con una entrada temporal y se anota el resultado |

## Decisiones pendientes del usuario
Ninguna.

## Enmiendas propuestas a la constitución
- **P3:** incluir en "Cómo se verifica" los `*ArchitectureTest` de cada módulo, además de la
  revisión (de la ronda 1; sin decidir, no bloquea).

## Aceptados
Aceptados por el usuario el 2026-11-10:
- **A10** → nota de T015, T017: cita ajena cerrada → 404 con el mismo cuerpo que un UUID inexistente
- **A11** → nota de T002, T018: sin guards en caché entre peticiones con tokens distintos; el test falla sin el provider
- **A12** → nota de T019, T050: TM11 con el test de `throttle:api`; medida a mano con 100 citas
- **A14** → nota de T020: `noindex` en lugar de la comprobación del sitemap mientras no exista
- **A16** → nota de T020: la vista hereda de `layouts.patient`
- **A19** → nota de T017, T032: cita con recomendaciones y sin recetas
- **A20** → nota de T010, T015, T016: cada caso de uso nombra su DTO
- **A21** → nota de T030: los estados vacíos usan respuestas simuladas
- **A22** → nota de T027: el fallo se demuestra con una entrada temporal
