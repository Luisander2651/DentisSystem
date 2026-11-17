---
spec: 026-nueva-landing
verdict: changes_requested
round: 2
date: 2026-11-17
base: 6cb178e
head: 3a8f470
human_signoff: pending
---

# Review · 026 Nueva landing del sitio público

## Resumen
Ronda 2 (`--rerun`) sobre las correcciones T044–T050. La ronda anterior está en [history/review.r1.md](history/review.r1.md).

R1–R7 quedan corregidos y cada uno tiene un test que fallaría sin la corrección. Pero la corrección de la cinta de servicios (R1) trae un defecto nuevo: R31, importante. En el celular, tocar el botón de pausa no pausa la cinta. El usuario decide corregirlo junto con R33, del mismo código.

El diff de esta ronda pesa 46 KB. Lo revisó un único subagente de contexto limpio con los encargos de spec, constitución, calidad y seguridad juntos.

Conteo de esta ronda: bloqueante 0 · importante 1 · menor 5

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2503 tests, 0 fallos |
| `pint` sobre los PHP del rango | ✅ |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores |
| `npm run test:ui` (inicio, contacto, acerca) | ✅ dos veces, salvo el intermitente conocido `inicio-testimonios` a 1440 px (R8, `/fix` aparte) |
| `evaluate.mjs` | Contacto y acerca pasan enteros. El inicio solo falla en las excepciones aceptadas en la ronda 1 |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1, R30 | corregido, con un defecto nuevo (→ R31) | `inicio.blade.php:95-106`: grupo con nombre y botón fuera de la pista. `home.js` `initMarquee`. Tests de `PublicSiteContentTest` y `SiteScriptsTest`. |
| R2 | corregido | `config/site.php:72-77`; `SiteConfigTest`, tres casos inválidos más uno válido. |
| R3 | corregido | `home.js` `welcomeFade` e `initDeck`. El test cubre la función pura (menor R34). |
| R4 | corregido | `data-ts-live`; `polite` solo en los cambios pedidos. Tests en `PublicSiteContentTest` y `SiteScriptsTest`. |
| R5 | corregido | `contacto.blade.php:121-122`: `role="img"` propio, sin controles dentro. `PublicSitePagesTest`. |
| R6 | corregido | `contacto.js` `announce` en `[data-ui-status]`. `SiteScriptsTest`. |
| R7 | corregido | `nosotros.blade.php`: especialidades de `config('site.treatments')`. `PublicSitePagesTest`, también con otra configuración. |
| R8 | abierto, fuera de esta ronda | `/fix` aparte antes de staging (nota A56 de T095) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.30.1 (`6cb178e..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.178.0 | en curso al escribir esta ronda; se registra en la siguiente |
| SCA / trivy | sin cambios de dependencias ni de `docker/` en el rango; igual que en la ronda 1 |

No hay vías nuevas de XSS: los avisos son textos fijos escritos con `textContent`, la dirección va escapada en `aria-label` y los iconos están en el código.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R31 | importante | `resources/js/site/home.js:606-609` | Un toque sobre el ícono del botón de pausa llega con `event.target` en el `svg` o el `path`, no en el `button`. `pointerdown` alterna una vez y `click` vuelve a alternar, así que en el celular el botón no pausa la cinta (CA10, M-3). El test solo simula el toque sobre la cinta. | Ignorar en `pointerdown` todo toque que caiga dentro del botón, con un caso de toque sobre el ícono. |
| R32 | menor | `resources/js/site/pages/contacto.js:51-67` | `announce` no cancela los temporizadores anteriores: con dos copias seguidas, el borrado de la primera acorta el segundo aviso. | Cancelar los temporizadores al principio. |
| R33 | menor | `resources/js/site/home.js:593-603` | Con el foco en el botón, "reanudar" no mueve la cinta hasta que el foco sale: el foco la sigue reteniendo. | Que el foco retenga la cinta salvo cuando está en el propio botón. |
| R34 | menor | `SiteScriptsTest.php` (R3) | El test de R3 prueba `welcomeFade`, no que `initDeck` aplique `inert`. | Escenario de integración o comprobación en `ui-audit`. |
| R35 | menor | `resources/js/site/home.js:579` | El comentario de `initVisit` quedó encima del de `initMarquee`. | Moverlo. |
| R36 | menor | `resources/views/site/nosotros.blade.php:61` | La entrada de acerca enumera a mano cuatro especialidades y no sigue a `config('site.treatments')`. | Redactarla sin enumerar o generarla de la configuración. |

## Preparación para release
Sin cambios respecto a la ronda 1: rollback con `SITE_NEW_LANDING` y `rollback.sh`, sin migraciones y la documentación al día. R8 debe estar cerrado antes de staging.

## Tareas añadidas
- T051 ← R31, R33

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17:
- **R32** → roadmap: el aviso de copiado no cancela los temporizadores anteriores.
- **R34** → roadmap: test de integración del `inert` de la bienvenida.
- **R35** → roadmap: comentario de `initVisit` fuera de su lugar.
- **R36** → roadmap: la entrada de acerca enumera especialidades a mano.

Los aceptados de la ronda 1 (R9–R29) siguen vigentes; ver [history/review.r1.md](history/review.r1.md).
