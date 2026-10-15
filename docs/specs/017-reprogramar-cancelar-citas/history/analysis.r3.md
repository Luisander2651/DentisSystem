---
result: fail
round: 3
mode: delta
constitution_version: 1.2.0
date: 2026-10-15
spec_sha: 2563023a1a12
plan_sha: ed0a7322b8d7
tasks_sha: d5b35d3e42da
---

# Análisis · 017 Reprogramar y cancelar citas

## Resumen
`fail`. Los once hallazgos abiertos de la ronda 2 quedaron resueltos (dos con efecto nuevo), pero el
`ui-audit` de la convención (`--only agenda,detalle-cita`) no selecciona los diálogos de reprogramar,
agendar y cancelar, así que las pruebas de navegador de T015 y T018 no correrían (A39). Los tres
bajos se aceptan como notas. Modo delta con subagente (diff de ~39 KB); cada hallazgo se verificó
contra los archivos. Sin cambios estructurales.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 0 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 5 / 5 |
| Principios de la constitución evaluados | 15 / 15 (P4 ❌ aceptado, D10) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A23 | ALTA | resuelto | plan.md → contrato (fila Reprogramar), Trazabilidad CA1; T012, T024 |
| A24 | MEDIA | resuelto | plan.md → fila "Vacía" (409 `noInfoProvided`); T012, T025; `UpdateAppointmentDTO.php:47`, `UpdateAppointmentController.php:52-53` |
| A25 | MEDIA | resuelto | T021 depende de T012 y exige en verde los casos sin `skip` |
| A26 | MEDIA | resuelto | plan.md → TM5, P7; T017; T022 y T025 dependen de T017. T017 pasa hoy: `UpdateAppointmentController.php:58-59`; en `POST` el repositorio se llama dentro de `ensureAvailable` (`ScheduleAvailabilityChecker.php:39`) |
| A27 | MEDIA | resuelto con efecto nuevo (→ A39) | T015 comprueba `expediente-*-cita-detalle`, pero el `--only` nuevo deja fuera los otros diálogos |
| A28 | MEDIA | resuelto | T015 (textos "Reprogramar" y "Reprogramar cita") |
| A29 | MEDIA | resuelto | T018 (409 o 500 al confirmar) |
| A30 | MEDIA | resuelto con efecto nuevo (→ A41) | T037 genera las capturas, también a 768 px |
| A32 | BAJA | resuelto | plan.md → Estrategia de pruebas (Navegador), Trazabilidad CA8 |
| A33 | BAJA | resuelto | `docs/specs/015-primer-despliegue-vps/plan.md:286` |
| A37 | BAJA | resuelto | plan.md → Riesgos; T034 |

Los aceptados de la ronda 2 tienen su nota: A31 en T001, A35 en T018, A36 en T012, A38 en T035.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A39 | Inconsistencia / Constitución (P2) | ALTA | tasks.md → convención `ui-audit`; T015, T018, T030–T035; `tests/Browser/ui-audit.mjs:507-515`; `screens.json` | `--only agenda` selecciona solo las entradas de `agenda` sin diálogo y `detalle-cita` solo ese diálogo: quedan fuera `agenda-cita-reprogramar` (antes `editar-cita`), `agenda-cita-crear` (`nueva-cita`) y `agenda-cita-cancelar`. Las comprobaciones de T015 y T018 no correrían y su "falla antes / pasa después" sería vacío | `/tasks --fix A39`: el `ui-audit` local recorre todas las entradas que verifican T015 y T018 (pantallas de la agenda, reprogramar, agendar, cancelar y todos los `detalle-cita`); la nota de T015 muestra esas entradas en la salida con los fallos esperados antes de T030 |
| A40 | Ambigüedad | BAJA | T015; `ui-audit.mjs:513-514, 708, 836` | `agenda-cita-cancelar` solo lleva `dialogSelector`; sin `dialog` cambia su selección, su línea base y su captura | Aceptado → nota de T015 |
| A41 | Mecánica | BAJA | T037; `ui-audit.mjs:12, 708`; `docs/design/system.md` (inventario) | Sin `--width`, `--screenshots` escribe también `-768.png`, que el inventario no enlaza | Aceptado → nota de T037 |
| A42 | Mecánica | BAJA | T001 | La nota de A31 cambia `design.md`, que no está entre los archivos de T001 | Aceptado → nota de T001 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-15 (rondas anteriores en `history/`):
- **A17** → nota de T004: añadir `APP_TIMEZONE` a `EnvExampleDefaultsTest`
- **A18** → nota de T015, T018: CA8 también con la cita cancelada; CA7 también al cerrar con la X y con el fondo
- **A19** → spec: el límite `<=` ya está en el texto aprobado de CA3 y CA4
- **A20** → nota de T018: ante un 409, afirmar el texto genérico del sistema en la región del diálogo
- **A31** → nota de T001: alinear el anticliché de `design.md` con la regla (botón y advertencia)
- **A34** → spec: 013 no tiene plan; su `/plan` deberá contemplar reprogramar y cancelar de 017
- **A35** → nota de T018: el 422 de "Agendar cita" es caracterización
- **A36** → nota de T012: reprogramar una cita cancelada a una fecha pasada responde 409 con el texto de cita cerrada
- **A38** → nota de T035: el diff no deja entradas obsoletas de la agenda
- **A40** → nota de T015: `agenda-cita-cancelar` lleva `dialog` (p. ej. `cancelar-cita`) como las demás entradas de diálogo
- **A41** → nota de T037: en `docs/design/capturas/` solo quedan las capturas a 390 y 1440 px que enlaza el inventario
- **A42** → nota de T001: añadir `docs/specs/017-reprogramar-cancelar-citas/design.md` a sus archivos; al cerrar, `grep "Rojo solo en el botón que confirma"` en `design.md` no da resultados
