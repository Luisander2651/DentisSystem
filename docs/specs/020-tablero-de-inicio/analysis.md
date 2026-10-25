---
result: pass
round: 4
mode: delta
constitution_version: 1.2.0
date: 2026-10-25
spec_sha: 9e67e23b4bcd
plan_sha: 4b0e1980bf50
tasks_sha: bbbb0165211b
---

# Análisis · 020 Tablero de inicio

## Resumen
Pasa. La ronda revisa solo la edición de CA2 (decisión del usuario del 2026-10-25: la siguiente cita
es la primera de hoy por atender, aunque su hora haya pasado) y sus efectos en el plan (D25,
`TodayAgenda`, estrategia de pruebas, Trazabilidad) y en T010 y T013. Delta pequeño, hecho sin
subagente releyendo las secciones afectadas. Sin cambio estructural: ni módulos, ni contratos, ni
criterios nuevos; los campos del contrato (`next`, `after`, `is_next`) no cambian de forma.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (CA2: T010, T013 → T030, T033) |
| Amenazas con control y test | 9 / 9 (sin cambios) |
| Principios de la constitución evaluados | 2 / 15 en esta delta (P2: T010 y T013 fallan antes de T030; P13: la regla vive en `TodayAgenda`, en el servidor); el resto no cambia |

## Seguimiento de rondas anteriores
Sin hallazgos abiertos. A34–A40 siguen aceptados como notas de tarea (abajo).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A41 | Ambigüedad | BAJA | `plan.md` → contrato de `today-summary` (`is_past`: hora < ahora); T046 | Con D25, una misma cita puede llegar con `is_past: true` e `is_next: true`, y la línea de ahora queda después de ella. T046 no dice cómo se pinta esa fila | La fila vencida que es la siguiente lleva la marca de siguiente igual que las demás y la línea de ahora va después. Test: un flujo o caso de ui-audit en T046 (o su verificación manual) con una cita vencida por atender |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-24:
- **A34** → nota de T002: `UiAuditSeederTest` siembra sin reloj fijo, como `ui:audit-data`, y fija las cifras de `reports/monthly?month=2026-09` leídas con el reloj en D16; si hace falta, el seeder fija las fechas de alta de los pacientes de agosto y septiembre.
- **A35** → nota de T056: el "hecho cuando" prueba cada clave nueva con un flujo temporal que falla por la razón esperada (dos rutas en la carga, 500 y después la respuesta real, respuesta retenida desde la carga).
- **A36** → nota de T056: reutilizar `hold`, `times` y `expect.visibleAfter` existentes; solo `load` y `answers` son nuevos; las entradas que ya los usan dan el mismo resultado.
- **A37** → nota de T057: un archivo por entrada pintado a 1440 px; el "hecho cuando" nombra el archivo que mide `evaluate`.
- **A38** → nota de T006, T007: la caducidad de `XSRF-TOKEN` y de la cookie de sesión no depende del reloj fijo; `UiAuditClockTest` lo comprueba.
- **A39** → nota de T050: suite completa con `--parallel` dos veces, con el resultado de `DashboardPerformanceTest` anotado.
- **A40** → nota de T017, T047: "Escribe el síntoma o quítalo." es texto nuevo de la API; el diálogo marca `symptoms.N` bajo el campo de síntomas.

Aceptado por el usuario el 2026-10-25:
- **A41** → nota de T046: la fila vencida que es la siguiente (`is_past` e `is_next`) lleva la marca de siguiente y la línea de ahora va después; T046 lo comprueba con una cita vencida por atender.

## Siguiente paso
Seguir con `/implement 020` (T002).
