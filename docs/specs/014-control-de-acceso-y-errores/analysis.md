---
result: pass
round: 7
mode: delta
constitution_version: 1.1.2
date: 2026-09-25
spec_sha: faaba2e87951
plan_sha: 6c6ff66851f0
tasks_sha: 772fe0c0b01a
---

# Análisis · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Ronda 7, **delta** sobre la ronda 6 ([analysis.r6.md](analysis.r6.md)). Solo cambió `tasks.md`: se añaden
T085 y T086, se reescriben T077 y T080–T084 y se amplían las tablas de cobertura. No hay cambio
estructural.

## Resumen
**pass.** E1–E7 quedan resueltos. La ronda deja 3 hallazgos BAJOS (F1–F3), aceptados por el usuario como
notas de T085 y T077 para `/implement`. Los controladores que ya llaman a `UnexpectedErrorResponse::from`
no relanzan la excepción, así que la solución de T077 no registra dos veces ni deja errores sin registrar.

Conteo: 0 CRÍTICOS · 0 ALTOS · 0 MEDIOS · 3 BAJOS (aceptados).

## Cobertura
| Métrica | Valor |
|---|---|
| Hallazgos de `/review` (R1–R7) con tarea | 7 / 7 (T077–T086) |
| Tareas de código con test antes o en la misma tarea (P2) | 6 / 6 (T085 antes de T080–T082; T077 con su test; T084 con verificación manual, como T070) |
| Tareas con ≤ 3 archivos y dependencias válidas | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| E1 | ALTA | resuelto | T085 (`StaffNavigationTest`, `RecordsScreenTest`) antes de T080–T082, que dependen de ella |
| E2 | MEDIA | resuelto | T083 actualiza axios; la excepción cubre solo dependencias de build |
| E3 | MEDIA | resuelto | T086: RS16 · A03:2025 "aceptada (EX1)", EX1 con aprobador y vencimiento, roadmap enlazado |
| E4 | BAJA | resuelto | T077 fija una solución; el test usa `GLOBAL_FALLBACK_TEST_PHONE` y comprueba que no aparece |
| E5 | BAJA | resuelto | T082 incluye el `colspan` de la fila vacía |
| E6 | BAJA | resuelto | T077–T086 en las tablas de cobertura |
| E7 | BAJA | resuelto | T080–T082 y T084 piden la revisión con la skill `design` |
| E8 | BAJA | aceptado (ronda 6) | Explicado en el Historial de la spec |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| F1 | Ambigüedad | BAJA | T085, T082 | La tabla del historial de citas (`appointments-history-table.blade.php:14`) también tiene cabecera "Acciones" y se muestra al doctor: una comprobación sobre toda la página fallaría aunque T082 esté bien. | Aceptado: nota de T085 |
| F2 | Inconsistencia | BAJA | T085 | "Los casos nuevos fallan" no aplica a los del asistente y el administrador, que ya pasan hoy. | Aceptado: nota de T085 |
| F3 | Ambigüedad | BAJA | T077 | No dice que `from()` debe seguir registrando para los controladores, ni que el caso nuevo deja un único log en total. | Aceptado: nota de T077 |

## Decisiones pendientes del usuario
- Ninguna.

## Aceptados
Aceptados por el usuario el 2026-09-25; se resuelven en `/implement` como nota de la tarea indicada:
- **F1:** T085 comprueba la cabecera "Acciones" solo en las tablas de contacto, dirección y datos médicos (acotando a cada `<section>`); la tabla del historial de citas conserva la suya.
- **F2:** en T085 fallan hoy los casos del doctor; los del asistente y el administrador pasan desde el principio.
- **F3:** en T077, `UnexpectedErrorResponse::from()` sigue registrando para los errores capturados por los controladores; el render de la red usa un constructor que no registra. El criterio incluye "`UnexpectedErrorTest` sigue en verde" y "el caso nuevo deja un único log en total".
- Siguen vigentes los aceptados de las rondas anteriores.
