---
result: fail
round: 2
mode: full
constitution_version: 1.2.1
date: 2026-11-12
spec_sha: a13a0791891e
plan_sha: 703ebe2404a5
tasks_sha: b24670a453e5
---

# Análisis · 026 Nueva landing del sitio público

## Resumen
`fail`: de los 29 hallazgos de la ronda 1, 25 quedaron resueltos y 4 se resolvieron con efecto
nuevo. Hay un hallazgo crítico nuevo (colores escritos a mano en el JS del diente 3D y de la
galería, P15) y cuatro altos que dejarían la suite en rojo: la línea base de composición congelada
por 016 CA22, `ui-audit` con un solo conjunto de datos, `core.js` fuera del manifest y K4a sin
escena medible. Ronda completa (las tareas se regeneraron) con un subagente independiente; cada
hallazgo verificado en los archivos.
Conteo: CRÍTICA 1 · ALTA 4 · MEDIA 6 · BAJA 9

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 19 / 19 (con reservas en CA3, CA7, CA9, CA10) |
| Amenazas con control y test | 5 / 5 |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | `design.md` → Cambios (radios, altos, componentes); T001, T004 |
| A2 | ALTA | resuelto | T001: `DesignTokensTest` lee `@theme static` (resto → A42) |
| A3 | ALTA | resuelto con efecto nuevo (→ A31) | cada tarea de página actualiza `ui-audit`, pero `ADMITTED` no está en ninguna |
| A4 | ALTA | resuelto con efecto nuevo (→ A33) | entradas por página; `core.js` fuera del manifest |
| A5 | ALTA | resuelto | K1 solo en `criterios-inicio/nosotros.json` |
| A6 | ALTA | resuelto en parte (→ A37) | contacto cubierto; dirección, horario y mapa sin confirmar |
| A7 | ALTA | resuelto | T013 con `PromotionEntity` y `travelTo` (caché → A46) |
| A8 | ALTA | resuelto | SE8.a partida; texto de `seo.md` → A45 |
| A9 | MEDIA | resuelto | `extends` 022; T010 |
| A10 | MEDIA | resuelto | T008 con logo y favicon reales |
| A11 | MEDIA | resuelto | T004 (`@font-face`) |
| A12 | MEDIA | resuelto | T004 y T001 |
| A13 | MEDIA | resuelto | T007, T010 |
| A14 | MEDIA | resuelto | CA6, T022, T023 |
| A15 | MEDIA | resuelto | CA2, T014 (texto de `design.md` → A44) |
| A16 | MEDIA | resuelto | T015 |
| A17 | MEDIA | resuelto con efecto nuevo (→ A35) | sin `action`, el formulario se envía a `/contacto` |
| A18 | MEDIA | resuelto | RS4.a fuera; TM1 |
| A19 | MEDIA | resuelto | T008, T011, T026 |
| A20 | MEDIA | resuelto | T020, T004 |
| A21 | MEDIA | resuelto con efecto nuevo (→ A32) | escenarios del seeder sin forma de usarlos en `ui-audit` |
| A22 | MEDIA | resuelto | `design.md` → Pantallas (caso de 2 → A41) |
| A23 | MEDIA | resuelto | T006, T021 |
| A24 | BAJA | resuelto | T025 |
| A25 | BAJA | resuelto | plan TM5 |
| A26 | BAJA | resuelto | CA17 (restos → A44) |
| A27 | BAJA | resuelto | T019 (texto de TM1 → A44) |
| A28 | BAJA | resuelto | T011 |
| A29 | BAJA | resuelto | T028, T003 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A30 | Constitución (P15) | CRÍTICA | T016, T020, T004; `tooth3d.js` (shader `vec3`, partículas `rgb(`); `gallery.js` (`#e3cf9d`…) | Colores escritos a mano en el JS; `OnlyDesignTokensTest` dejaría T016/T020 en rojo y los `vec3` no los ve nadie | Todo color que pinta el JS del sitio sale de un token leído en ejecución; los tonos del esmalte se añaden como tokens decorativos (decisión del usuario); regla en `SiteStylesTokensTest` sobre `resources/js/site/**` con controles · `/design-spec --fix`, `/plan --fix`, `/tasks --fix` |
| A31 | Constitución (P2) / extends 016 | ALTA | T010, T014, T017, T019, T022; `checks-composition.mjs` (`ADMITTED`); 016 CA22 | La línea base solo se reescribe con una entrada `ADMITTED`; ningún archivo de tarea la toca y la spec no declara que sustituye 016 CA22 | La spec declara que 026 CA1 sustituye 016 CA22 en las seis pantallas públicas; cada tarea de página añade su entrada `ADMITTED`; `ui-audit --only <pantalla>` en verde · `/specify --edit`, `/tasks --fix` |
| A32 | Constitución (P2) | ALTA | T005, T024; `ui-audit.mjs`; `tests.yml` (`ui:audit-data` una vez) | `ui-audit` no puede tener varios conjuntos de datos en una ejecución | Estados vacíos y por cantidad con Pest y revisión manual; `ui-audit` en CI solo con contenido (decisión del usuario) · `/plan --fix`, `/tasks --fix` |
| A33 | Constitución (P2) | ALTA | T006, T009 | `core.js` cargado por el layout fuera del manifest; recoger `pages/` vacía o ausente puede romper el build | Toda entrada que carga una vista está en el manifest desde su tarea; la recogida funciona con la carpeta vacía; `npm run build` en T006 y `PublicSitePagesTest` 200 en T009 · `/tasks --fix` |
| A34 | Ambigüedad / P2 | ALTA | `criterios-inicio.json` K4a; T014, T016 | La propuesta no tiene `data-scene`; K4a siempre 0, y T014 lo exige antes de T016 | El recorrido se marca como escena sin cambiar la composición; K4a se mide desde T016 · `/design-spec --fix`, `/tasks --fix` |
| A35 | Seguridad (P11) | MEDIA | T022 | Sin `action`, el formulario se envía con `GET /contacto?…` y el mensaje acaba en los registros | El formulario no puede enviar nada al servidor (sin JS solo queda el enlace; con JS se impide el envío); `PublicSitePagesTest` y `SiteScriptsTest` · `/plan --fix`, `/tasks --fix` |
| A36 | Inconsistencia (016 CA12, CA13, CA26) | MEDIA | propuesta `inicio.blade.php` (visor de certificaciones `role="dialog"`); T014, T015 | El visor de certificaciones no está en plan, tareas ni `ui-audit` | El visor se documenta como componente del sitio y cumple 016 CA12, CA13 y CA26 (foco, Escape, retorno); entrada de diálogo en `ui-audit`; parte de CA10 · `/design-spec --fix`, `/tasks --fix` |
| A37 | Cobertura (CA7) | MEDIA | T003, T017; plan; `design.md` | Dirección, horario y enlaces de mapa vienen de ejemplos y nadie los confirma ni los vigila | Dirección, horario y mapa siguen la regla de A6 (confirmados o `null`, y sin ellos no aparecen ni el mapa ni "Abierto/Cerrado"); `PublicSitePagesTest` en las seis rutas · `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A38 | Pruebas que no prueban | MEDIA | `evaluate.mjs` (G-red solo en archivos, G-imagenes); T014, T019 | G-red no se evalúa con URL; las imágenes del staff no llevan `data-origen` | El RNF de terceros se apoya en `SiteStylesTokensTest` y en una comprobación de hosts en `PublicSitePagesTest`; las imágenes del staff declaran `data-origen="propia"` · `/plan --fix`, `/tasks --fix` |
| A39 | Constitución (P2) | MEDIA | T020 vs T021 | El comparador llega con el dibujo CSS (colores literales) antes que las fotos | Comparador y fotos en la misma tarea; `OnlyDesignTokensTest` y `ui-audit galeria` en verde · `/tasks --fix` |
| A40 | Inconsistencia (022, 016) | MEDIA | T008 vs T010 | T008 no exige lo que `LegalPagesTest`/`PageStructureTest` piden al layout (título, una meta description, enlaces legales con `route()`, sin `href="#"`) | El test de T008 comprueba esos puntos · `/tasks --fix` |
| A41 | Pruebas que no prueban | BAJA | T014, T019 | Faltan 3 testimonios (sin cinta) y 2 imágenes (fila estática) | Añadir los dos casos · nota de T014 y T019 |
| A42 | Pruebas que no prueban | BAJA | T001 | `DesignTokensTest` no compara `--radius-2xl`, `--space-*`, `--duration-*`, `--ease-*`, `--weight-*`, `--font-*` | El test cubre cada espacio nuevo con control · nota de T001 |
| A43 | Pruebas que no prueban | BAJA | T003 | Sin controles de frontera 69/70/160/161 | Añadir controles · nota de T003 |
| A44 | Inconsistencia | BAJA | spec K7 y Notas ("catálogo"); `design.md` Resultado 3; plan TM1 | Textos que contradicen A6, A15, A26, A27 | Alinear textos · `/specify --edit`, `/design-spec --fix`, `/plan --fix` |
| A45 | Cobertura de riesgos | BAJA | T028; `seo.md` SE8.a | T028 no reescribe el valor de SE8.a | T028 actualiza el texto · nota de T028 |
| A46 | Ambigüedad | BAJA | `ShowLandingPageController`; plan → Rollout | La caché vieja o una promoción que vence a medianoche siguen hasta 10 min | La caché del inicio usa una clave nueva y expira como tarde a la medianoche de la clínica · `/plan --fix`, nota de T014 |
| A47 | Pruebas que no prueban | BAJA | `criterios*.json` → `tokens.colors` | Incluye `#000000`, que no es token | La lista es exactamente la paleta del sistema · `/design-spec --fix` |
| A48 | Inconsistencia | BAJA | plan → Trazabilidad CA9 | `ui-audit` no emula movimiento reducido | Quitarlo; CA9 lo cubren G-reducido y `SiteScriptsTest` · `/plan --fix` |
| A49 | Seguridad (CSP) | BAJA | T007, T009 | Un script en línea para la marca de JS necesita el nonce | Todo script en línea lleva el nonce de Vite; `PublicSitePagesTest` · nota de T009 |

## Decisiones pendientes del usuario
Resueltas el 2026-11-12:
- A30 → los colores del JS se leen de los tokens al dibujar; los tonos del esmalte se añaden como tokens decorativos.
- A32 → estados vacíos y por cantidad con Pest y revisión manual; `ui-audit` en CI con contenido.

## Enmiendas propuestas a la constitución
- P15 (PATCH): que "Cómo se verifica" cubra las hojas CSS fuera de `@theme` y los colores que pinta el JS en lienzo o WebGL. No bloquea; la decide el usuario.

## Aceptados
Ninguno: todo se corrige antes de la ronda 3.
