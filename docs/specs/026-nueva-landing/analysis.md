---
result: pass
round: 3
mode: delta
constitution_version: 1.2.1
date: 2026-11-12
spec_sha: a2682204e0ad
plan_sha: 96de1029dc70
tasks_sha: d16beb945c10
---

# Análisis · 026 Nueva landing del sitio público

## Resumen
`pass`. Los 20 hallazgos de la ronda 2 quedaron resueltos (cuatro con efecto nuevo). La ronda 3
(delta, con subagente porque el diff pasaba de 30 KB) encontró cuatro hallazgos locales sin cambio
estructural. A50, A52 y A53 se corrigieron en `tasks.md` y se verificaron sin subagente releyendo
T014, T015, T019, T020 y la tabla de Cobertura (delta pequeño). A51 queda aceptado como nota de
tarea por decisión del usuario.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0 abiertos (1 BAJA aceptado)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 19 / 19 |
| Amenazas con control y test | 5 / 5 |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A30 | CRÍTICA | resuelto | T001 (tokens del esmalte), T004 (sin color literal en `resources/js/site/**`), T016 (uniformes desde tokens); `design.md` → Cambios |
| A31 | ALTA | resuelto | spec CA1 sustituye 016 CA22; `checks-composition.mjs` en T010, T014, T017, T019, T022 |
| A32 | ALTA | resuelto con efecto nuevo (→ A51) | T005, T019, T024 y plan alineados con la decisión del usuario |
| A33 | ALTA | resuelto | T006 declara `site.css` y `core.js` mínimo con `pages/` vacía; T009 lo completa |
| A34 | ALTA | resuelto | `data-scene` en `design.md`; T014 sin K4a; T016 lo mide |
| A35 | MEDIA | resuelto | plan, T022, T023: formulario oculto sin JS y envío cancelado |
| A36 | MEDIA | resuelto con efecto nuevo (→ A50) | visor documentado en plan y `design.md` |
| A37 | MEDIA | resuelto | CA7, plan, T003, T022 |
| A38 | MEDIA | resuelto | T014, T019 (`data-origen="propia"`); T008 y RNF de terceros con `PublicSitePagesTest` |
| A39 | MEDIA | resuelto con efecto nuevo (→ A52) | T021 fusionada en T020 con fotos |
| A40 | MEDIA | resuelto | T008 |
| A41 | BAJA | resuelto | T014 (1/2/3/4), T019 (0/1/2/3) |
| A42 | BAJA | resuelto | T001 |
| A43 | BAJA | resuelto | T003 |
| A44 | BAJA | resuelto | spec K7 y Notas, `design.md`, plan TM1 |
| A45 | BAJA | resuelto | T028 |
| A46 | BAJA | resuelto | T014 y plan → Rollout |
| A47 | BAJA | resuelto | `criterios*.json` sin `#000000` |
| A48 | BAJA | resuelto | plan → Trazabilidad CA9 |
| A49 | BAJA | resuelto | T009 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A50 | Constitución (P2) | ALTA | T014, T015; propuesta `home.js` (`initCerts`) | T014 exigía el comportamiento del visor y su entrada de diálogo en `ui-audit`, pero su JS llega en T015 | **Corregido:** T014 trae solo el marcado; T015 trae el comportamiento (foco atrapado, Escape, retorno del foco), su prueba y la entrada de diálogo en `ui-audit` |
| A51 | Inconsistencia | BAJA | spec K3; `design.md` | K3 sigue diciendo `ui-audit` para los estados vacíos | **Aceptado** (ver abajo) |
| A52 | Cobertura | MEDIA | T019, T020; propuesta `galeria.css` (`.ba-mouth`, `.ba-gum` con colores literales) | El CSS del dibujo iba en T019 y T020 no tocaba `galeria.css` | **Corregido:** T019 trae `galeria.css` sin el dibujo; T020 lista `galeria.css` y añade el CSS del comparador con fotos en tokens |
| A53 | Inconsistencia | BAJA | `tasks.md` → Cobertura CA10 | La tabla citaba T014 para CA10 | **Corregido:** fila CA10 sin T014 (el visor lo cubre T015) |

## Decisiones pendientes del usuario
Ninguna.

## Enmiendas propuestas a la constitución
- P15 (PATCH), de las rondas 1 y 2: que "Cómo se verifica" cubra las hojas CSS fuera de `@theme` y los colores que pinta el JS en lienzo o WebGL. No bloquea; la decide el usuario.

## Aceptados
Aceptados por el usuario el 2026-11-12:
- **A51** → nota de T014, T019: K3 (estados vacíos sin desbordes) se comprueba con `PublicSiteContentTest`/`PublicGalleryPageTest` y la verificación manual de la tarea, no con `ui-audit`.
