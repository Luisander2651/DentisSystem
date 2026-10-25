---
result: pass
round: 5
mode: delta
constitution_version: 1.2.0
date: 2026-10-25
spec_sha: 9e67e23b4bcd
plan_sha: 4428cfcb86f3
tasks_sha: c6ed4fd0d367
---

# Análisis · 020 Tablero de inicio

## Resumen
Pasa. La ronda revisa D26 (expedientes no tenía buscador y CA9 lo promete: se añade el campo
"Buscar", que filtra en el navegador la lista ya cargada; decisión del usuario del 2026-10-25, vista
en `/implement` T026), la ampliación de T048 (vista y JS) y de `expedientes-buscar` en T026, y las
tareas añadidas durante `/implement` (T060–T062). Delta pequeño, hecho sin subagente releyendo las
secciones afectadas. Sin cambio estructural: ni contratos de API, ni módulos, ni criterios nuevos; la
spec no cambia.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 2

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (CA9: T024, T026 → T045, T048) |
| Amenazas con control y test | 9 / 9 (sin cambios: el filtro no pide datos nuevos a la API) |
| Principios de la constitución evaluados | 3 / 15 en esta delta (P2: `expedientes-buscar` falla antes de T048; P15: el campo copia el marcado y los tokens del buscador de `/pacientes`, sin valores nuevos; P13: filtrar una lista ya autorizada no es lógica de negocio) |

## Seguimiento de rondas anteriores
Sin hallazgos abiertos. A34–A41 siguen aceptados como notas de tarea (abajo).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A42 | Ambigüedad | BAJA | `plan.md` → Interfaz · expedientes (D26); T048 | Expedientes ya muestra "No hay pacientes registrados para mostrar." con la lista vacía; con el buscador, una búsqueda sin coincidencias debe decir "No se encontraron pacientes" y no el aviso de lista vacía | Cada caso muestra solo su aviso. Test: el flujo sin coincidencias de `expedientes-buscar` comprueba el texto y que el aviso de lista vacía no está a la vista |
| A43 | Ambigüedad | BAJA | `plan.md` → Interfaz · expedientes (D26); T048 | Cambiar el filtro de estado recarga la lista desde la API; no está dicho si el texto escrito en "Buscar" se vuelve a aplicar | El texto del campo se vuelve a aplicar a la lista recargada. Test: un flujo de `expedientes-buscar` escribe, cambia el filtro y comprueba que siguen solo las tarjetas que coinciden |

T060–T062 (añadidas durante `/implement` con decisión del usuario) cumplen las reglas de `/tasks`:
una o dos rutas cada una, test antes del cambio (T060 → T061) y criterio de hecho; T026 depende de
T062.

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
- **A42** → nota de T048: la búsqueda sin coincidencias muestra "No se encontraron pacientes" y no el aviso de lista vacía; lo comprueba un flujo de `expedientes-buscar`.
- **A43** → nota de T048: el texto de "Buscar" se vuelve a aplicar cuando el filtro de estado recarga la lista; lo comprueba un flujo de `expedientes-buscar`.

## Siguiente paso
Seguir con `/implement 020` (T026).
