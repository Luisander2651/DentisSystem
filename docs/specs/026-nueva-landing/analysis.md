---
result: pass
round: 6
mode: delta
constitution_version: 1.2.1
date: 2026-11-16
spec_sha: 4d593b174036
plan_sha: e8f082f36448
tasks_sha: 8f10d7d3daed
---

# Análisis · 026 Nueva landing del sitio público

## Resumen
Delta sobre la ronda 5 ([history/analysis.r5.md](history/analysis.r5.md)). Se revisan:
- las enmiendas de CA7 (contacto provisional, ciudad) y CA18 (dentadura 3D con dibujo plano);
- la reescritura de T020;
- las tareas añadidas durante `/implement` (T033–T043);
- las notas de cierre de T092.

Lo revisó un subagente de contexto limpio (perfil `standard`) sobre el diff de `aidd.py changes` (135 KB, casi todo marcas `[x]` y notas). El agente verificó cada hallazgo abriendo los archivos. No hay cambio estructural ni criterios añadidos o eliminados. El cambio de CA18 quedó propagado sin restos de los pares de fotos. La deriva que queda es documental o de proceso, y el usuario la acepta en bloque.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 3 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 19 / 19 |
| Amenazas con control y test | 5 / 5 |
| Principios de la constitución evaluados | los tocados por el delta (P10, P15 y la verificación de pruebas); sin cambios en los demás |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A51 | MEDIA | aceptado, vigente | notas de T014 y T019 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A54 | Inconsistencia | MEDIA | `plan.md` → Contratos y datos (contacto provisional) frente a `spec.md` CA7 y la nota de T033 | El plan seguía diciendo "la dirección de la propuesta" como valor provisional, cuando la enmienda de CA7 la cambió a "Lázaro Cárdenas, Michoacán". Tampoco decía que la ciudad es un dato real ni cómo entra en el JSON-LD. | Corregido en el plan antes de esta ronda. La dirección provisional queda como la de CA7. La ciudad es un dato real y solo entra en el JSON-LD como `addressLocality` junto con una dirección real. Test: `PublicSiteSeoTest`, el caso con todo provisional da solo nombre y URL. |
| A55 | Cobertura | MEDIA | `tasks.md` T092 (notas de Lighthouse) frente a `spec.md` CA8 | Las notas solo daban accesibilidad, buenas prácticas y SEO del inicio. En las otras tres rutas la nota de aceptación no aclaraba si esas categorías se midieron. | Que conste que el usuario midió las 16 cifras: rendimiento 94 / 99 / 95 / 99 y 100 en las otras tres categorías de las cuatro rutas. Se pasan a `review.md`, y T095 repite la medición sobre producción. |
| A56 | Pruebas | MEDIA | `tasks.md` T092 (nota de `ui-audit inicio-testimonios`) y T095 | `ui-audit inicio-testimonios` a 1440 px falla de forma intermitente desde T024, y CI decide con `npm run test:ui`. El `/fix` aparte no tenía una puerta registrada antes de liberar. | No se va a staging hasta cerrar la ficha `/fix` y hasta que `npm run test:ui -- --only inicio` pase dos veces seguidas en los tres anchos. |
| A57 | Cobertura | BAJA | `plan.md` → Cambios por módulo (layout y componentes) frente a T040–T043 | El plan no mencionaba el menú de celular rediseñado (`<details>`, foco atrapado en `core.js`) ni el cupón de la promoción vigente dentro del menú del inicio. Sí estaban registrados en `design.md` → refinado, con sus tests. | Corregido en el plan antes de esta ronda, con una línea en la fila del layout. |

Descartados al verificar:
- **"T020 cita un A52 inexistente":** A52 existe en la ronda 3 ([history/analysis.r3.md](history/analysis.r3.md)), así que los nuevos siguen desde A54.
- **`tasks.md` en `approved` con la spec en `implemented`:** es la convención del flujo.

## Decisiones pendientes del usuario
Ninguna.

## Enmiendas propuestas a la constitución
- P15 (PATCH), de las rondas 1 y 2: que "Cómo se verifica" cubra las hojas CSS fuera de `@theme` y los colores que pinta el JS en lienzo o WebGL. No bloquea; la decide el usuario.

## Aceptados
Aceptados por el usuario el 2026-11-12:
- **A51** → nota de T014, T019: K3 (estados vacíos sin desbordes) se comprueba con `PublicSiteContentTest`/`PublicGalleryPageTest` y la verificación manual de la tarea, no con `ui-audit`.

Aceptados por el usuario el 2026-11-16:
- **A54** → plan: corregido con `/plan 026 --fix A54` antes de registrar esta ronda.
- **A55** → nota de T092: las 16 cifras de Lighthouse medidas por el usuario, que pasan a `review.md`.
- **A56** → nota de T095: no ir a staging hasta cerrar el `/fix` de `ui-audit inicio-testimonios` y que `ui-audit inicio` pase dos veces seguidas en los tres anchos.
- **A57** → plan: corregido con `/plan 026 --fix A57` antes de registrar esta ronda.
