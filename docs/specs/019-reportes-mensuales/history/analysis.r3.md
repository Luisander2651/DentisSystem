---
result: pass
round: 3
mode: delta
constitution_version: 1.2.0
date: 2026-10-20
spec_sha: 8b6e01c2b1aa
plan_sha: 010f1175ebd6
tasks_sha: 1042c86b593a
---

# Análisis · 019 Reportes mensuales

## Resumen
`pass`. Los 13 hallazgos abiertos de la ronda 2 quedaron resueltos; seis traen un efecto nuevo.
Los 9 hallazgos nuevos (A44–A52) son MEDIOS o BAJOS y ninguno cambia el diseño: el usuario los
aceptó como notas de sus tareas para resolverlos en `/implement`. A48 lo decidió el usuario y ya
está en `design.md`. Hizo el análisis un subagente independiente en modo delta (perfil `standard`,
Sonnet), con un diff de más de 30 KB porque la base es el último commit (`d5ad717`): los
artefactos aún no están commiteados. A46 y A47 se comprobaron en el código y en `tasks.md`.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 5 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 |
| Amenazas con control y test | 7 / 7 |
| Principios de la constitución evaluados | 15 / 15 (sin cambios desde la ronda 2) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A23 | BAJA | resuelto (residuo → A51) | T016 sin la vista "Sin permiso"; `WebAccessDeniedPagesTest` recorre `DENIED_ADMIN_SCREENS` |
| A31 | ALTA | resuelto con efecto nuevo (→ A44, A45) | T004 en `checks-dialogs.mjs` y `ui-audit.mjs`; plan → Tests de navegador, D19 |
| A32 | ALTA | resuelto | `reportes-descarga`; T045, T049, T046 comprobables y sin ciclos |
| A33 | MEDIA | resuelto con efecto nuevo (→ A47, A48) | design.md, plan D20, T045, T024; contrastes recalculados |
| A34 | MEDIA | resuelto (residuo → A50) | plan → exportador, hojas, D21; T017, T040 |
| A35 | MEDIA | resuelto | plan → fila P3 con la excepción aceptada |
| A36 | BAJA | resuelto con efecto nuevo (→ A49) | contrato `short_label`, `previous_short_label`; T014, T036, T043 |
| A38 | BAJA | resuelto | plan → TM2 y Riesgos con RS1.c fuera de alcance, no mitigado |
| A39 | BAJA | resuelto con efecto nuevo (→ A46) | T053 con las pantallas de `x-ui.stat` |
| A40 | BAJA | resuelto | T056 |
| A41 | BAJA | resuelto | T017 con `sendContent()` |
| A42 | BAJA | resuelto | T023 lee `@vite` y `vite.config.js` |
| A43 | BAJA | resuelto (residuo → A52) | T004 `selectIndex`; T024 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A44 | Cobertura (P2) / Tareas | MEDIA | T004; `ui-audit.mjs:208-221` | Las capacidades nuevas de la herramienta no tienen prueba propia: T024 las usa contra una página que aún no existe. Además, una respuesta retenida deja la petición en curso, y `settle()` lanza a los 15 s | Al cerrar T004, cada capacidad nueva (`hold`, 429 servido sin "limitada por la API", `selectIndex`, `tabbable`, `absent`, `globalUndefined`, `visibleAfter`) se demuestra contra una pantalla existente, y se anota; una acción con una respuesta retenida no hace fallar la espera |
| A45 | Ambigüedad | MEDIA | T004, T024; `checks-dialogs.mjs:301-350` | `runFlows` carga la página antes de interceptar, así que "error de carga" y "nombre con HTML" necesitan una acción que vuelva a pedir el reporte. `selectIndex` solo existe como paso de la entrada, no como acción de un flujo | `selectIndex` también como acción `do` de un flujo, y en T024 los dos flujos cambian de mes con ella; los dos fallan por el motivo esperado antes de T045 |
| A46 | Inconsistencia (código) | MEDIA | T053 | `--only agenda,contenido,…`: no existe la entrada `contenido` (son `contenido-galeria`, `-promociones`, `-certificaciones` y `-testimonios`), y `--only` falla | El criterio nombra entradas que existen, incluidas las cuatro de contenido, y corre sin error con el mismo resultado que antes |
| A47 | Inconsistencia / P15 | MEDIA | T001; plan → fila "Sistema de diseño"; `system.md` → Estados de pantalla | T001 no aplica la regla nueva "Estados de pantalla · error" (bloque persistente) que añadió `design.md` | T001 actualiza "Estados de pantalla" en `system.md` y `system.html` (bloque, colores, medidas, persistencia, sin `role="alert"`) antes de T045 |
| A48 | Accesibilidad | MEDIA | design.md; T024; `status.js` | El bloque `role="alert"` y la región de estado anunciarían el mismo texto, y el flujo pasaría aunque `status.js` no escribiera nada | Resuelto en `design.md` (decisión del usuario): solo la región anuncia; el bloque no es región viva. El flujo afirma un único anuncio, en la región de estado |
| A49 | Inconsistencia | BAJA | plan → nota de `short_label`, D22; T043; `opcion-c.html:367` | "julio: 28.6 %" es el mes **anterior** con su valor; `short_label` no tiene uso. A `x-ui.trend` le falta el valor anterior para el sexto caso | `short_label` se usa en el título "<Mes> en cifras" o se quita. `x-ui.trend` recibe `previous` para el sexto caso; lo afirman T052 y T014 |
| A50 | Ambigüedad | BAJA | plan → exportador; T017, T040 | El formato `+0;−0;0` con U+2212 sin comillas puede ser inválido en Excel (aviso de reparación, CA8) | El formato escrito es `+0;"−"0;0` y `+0.0;"−"0.0;0.0` (signo entre comillas); `DownloadMonthlyReportTest` lo lee en `xl/styles.xml` y la verificación manual de T041 lo comprueba en Excel y LibreOffice |
| A51 | Documentación | BAJA | plan → Estrategia y Trazabilidad (CA10, TM2) | El plan atribuye a `ReportsAccessControlTest` la vista `/reportes` por actor, que ahora prueba `WebAccessDeniedPagesTest` | Al cerrar T016 y T023 se anota qué test cubre cada actor de `/reportes`; `/review` comprueba que la trazabilidad apunta al test real |
| A52 | Ambigüedad / Cobertura | BAJA | T024; `ui-audit.mjs:552-553` | `waitFor` espera un selector, no un texto, y ningún flujo prueba que "Intentar de nuevo" vuelva a pedir el reporte y quite el bloque | `reportes-mes-en-curso` espera el selector del aviso de mes en curso; el flujo "error de carga" responde 500 y luego 200 y afirma que el bloque desaparece y aparece el reporte |

## Decisiones pendientes del usuario
Resuelta por el usuario el 2026-10-20:
- **A48:** solo la región de estado anuncia el error de carga; el bloque de la página no lleva
  `role="alert"` (registrado en `design.md` → Decisiones como `refinado`).

## Aceptados
Aceptados por el usuario el 2026-10-20 (rondas 1 y 2):
- **A24** → plan: "no se limita" significa sin límite propio; el JSON sigue con el límite general `api`.
- **A26** → nota de T048: CI sigue corriendo `pest --parallel`; la ejecución en serie es adicional, en local.
- **A28** → roadmap: alinear la definición de "sin cerrar" del tablero de inicio con CA2 (asignadas o reprogramadas con fecha pasada).
- **A37** → spec: el ejemplo de CA2 ("+6 respecto a septiembre") es ilustrativo; los textos del indicador los fija `design.md`.

Aceptados por el usuario el 2026-10-20 (ronda 3), para resolver en `/implement`:
- **A44** → nota de T004: demostrar cada capacidad nueva contra una pantalla existente, y que una acción con una respuesta retenida no hace fallar `settle()`.
- **A45** → nota de T004, T024: `selectIndex` también como acción de un flujo; los flujos "error de carga" y "nombre con HTML" cambian de mes con ella.
- **A46** → nota de T053: `--only agenda,contenido-galeria,contenido-promociones,contenido-certificaciones,contenido-testimonios,pacientes,expedientes,tratamientos,usuarios`, comprobando antes que cada nombre existe.
- **A47** → nota de T001: aplicar también "Estados de pantalla · error" (bloque persistente sin `role="alert"`) en `system.md` y `system.html`.
- **A48** → nota de T024, T045: el bloque no lleva `role="alert"`; el flujo "error de carga" afirma un único anuncio, en la región de estado.
- **A49** → nota de T043, T014: `x-ui.trend` recibe `previous` para "julio: 28.6 %" (mes anterior y su valor); `short_label` se usa en el título "<Mes> en cifras".
- **A50** → nota de T040, T017: formatos `+0;"−"0;0` y `+0.0;"−"0.0;0.0`, leídos en `xl/styles.xml` por el test.
- **A51** → nota de T016, T023: anotar qué test cubre cada actor de `/reportes`.
- **A52** → nota de T024: `reportes-mes-en-curso` espera el selector del aviso; el flujo "error de carga" responde 500 y después 200 y afirma que el bloque desaparece y aparece el reporte.
