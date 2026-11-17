---
spec: 026-nueva-landing
verdict: approved
round: 3
date: 2026-11-17
base: 9bbea55
head: be15dc8
human_signoff: Luisander2651 2026-11-17
---

# Review · 026 Nueva landing del sitio público

## Resumen
Ronda 3 (`--rerun`) sobre T051, que corrige R31 y R33 de la ronda 2.

Los dos quedan corregidos y no aparecen hallazgos nuevos. No hay bloqueantes. Todos los importantes de las tres rondas están corregidos o tienen destino decidido por el usuario: R8 va a un `/fix` antes de staging. Los menores se aceptaron al roadmap.

Esta ronda la revisé sin subagente, releyendo `initMarquee` y las reglas de la cinta desde cero, porque el diff de código mide 9 líneas en 2 archivos.

Rondas anteriores:
- [history/review.r1.md](history/review.r1.md): la review completa, con la verificación de criterios, seguridad, SEO y preparación para liberar.
- [history/review.r2.md](history/review.r2.md).

Conteo de esta ronda: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2505 tests, 0 fallos |
| `pint` sobre los archivos del rango | ✅ |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores |
| `npm run test:ui -- --only inicio` | ✅, salvo `inicio-testimonios` a 1440 px, intermitente desde T024 (R8) |
| Chrome con toque emulado (390 px) | el primer toque en el ícono pausa y el segundo reanuda |

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R31 | corregido | `home.js` `initMarquee`: `pointerdown` ignora los toques dentro del botón; `inicio.css`: la pausa por `:hover` solo con `(hover: hover)`. Casos en `SiteScriptsTest` (toque sobre el ícono) y en `SiteStylesTokensTest`; los dos fallan sin el cambio. |
| R33 | corregido | El foco en el propio botón no retiene la cinta. Caso en `SiteScriptsTest`. |
| R8 | abierto, con destino | `/fix` aparte antes de staging (nota A56 de T095) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.30.1 | ✅ 0 hallazgos en `7fae5a3..HEAD` (ronda 1) y en `6cb178e..HEAD` (ronda 2) |
| semgrep 1.178.0 (`p/php`, `app config bootstrap routes`) | ✅ 0 hallazgos en la ronda 1, sobre todo el código. En las rondas 2 y 3 se colgó en local al descargar las reglas, y se detuvo a los 26 min y a los 400 s. Desde la ronda 1 solo cambió en su alcance `config/site.php` (R2, el horario provisional, sin entradas nuevas). CI lo ejecuta en cada push (`.github/workflows/ai-dd.yml`). |
| composer audit / npm audit | sin altas ni críticas. El aviso bajo de Laravel 12.69.3 queda aceptado como R28 → `/chore` |
| trivy 0.74.0 | ✅ sin hallazgos altos ni críticos; sin cambios en `docker/` desde la ronda 1 |

## Hallazgos
Ninguno nuevo en esta ronda.

## Preparación para release
- **Rollback:** `SITE_NEW_LANDING=false` más `config:cache`, o `docker/prod/rollback.sh`.
- **Migraciones:** ninguna.
- **Documentación:** `architecture.md`, `deployment.md`, `observability.md` y `seo.md` al día.
- **Antes de staging:** cerrar el `/fix` de `ui-audit inicio-testimonios` (R8). Después, `ui-audit inicio` dos veces seguidas en los tres anchos.
- **Pendientes de la clínica:** todos los datos de contacto siguen provisionales.
- **Fotos de la propuesta:** generadas, aprobadas por el usuario para producción hasta tener fotos reales.
- **Revisión humana:** aprobada por el dueño del repositorio (Luisander2651) el 2026-11-17; el merge se aprueba en `/release`.

## Tareas añadidas
Ninguna en esta ronda. Antes: T044–T050 (ronda 1) y T051 (ronda 2), todas hechas.

## Aceptados sin tarea
Siguen vigentes los aceptados de las rondas anteriores, que `/release` llevará al roadmap:
- R9–R29: [history/review.r1.md](history/review.r1.md).
- R32, R34, R35 y R36: [history/review.r2.md](history/review.r2.md).

- **R9** → roadmap: títulos de galería y acerca por debajo de 30 caracteres.
- **R10** → roadmap: certificaciones sin `width`/`height`.
- **R11** → roadmap: galería con `data-src` desde la 4.ª foto.
- **R12** → roadmap: `PublicSiteSchedule::days()` sin uso.
- **R13** → roadmap: tests unitarios de la agrupación del horario.
- **R14** → roadmap: resumen del horario repetido en tres vistas.
- **R15** → roadmap: `SiteScriptsTest` sin comprobación previa de `node`.
- **R16** → roadmap: test de API del filtro `status` de galería.
- **R17** → roadmap: `PublicSiteSeoTest` compara el inicio con el texto de contacto.
- **R18** → roadmap: etiquetas OG y `theme-color` probadas solo en las legales.
- **R19** → roadmap: horario visible del inicio sin comparar con el JSON-LD.
- **R20** → roadmap: `og:description` sin comprobar en las cuatro páginas.
- **R21** → roadmap: caso de abuso de CA15 sin certificación.
- **R22** → roadmap: certificación sin descripción muestra "Dentissa".
- **R23** → roadmap: `site.timezone` sin uso y zona repetida.
- **R24** → roadmap: botones con estado y etiqueta que cambian a la vez.
- **R25** → roadmap: `SITE_WHATSAPP` sin normalizar.
- **R26** → roadmap: `aria-expanded` estático en el `<summary>` del menú.
- **R27** → roadmap: preguntas frecuentes sin `aria-controls`.
- **R28** → roadmap: subir `laravel/framework` a ≥ 12.71.0 con un `/chore`.
- **R29** → roadmap: tarjetas del equipo que solo giran con el puntero.
- **R32** → roadmap: el aviso de copiado no cancela los temporizadores anteriores.
- **R34** → roadmap: test de integración del `inert` de la bienvenida.
- **R35** → roadmap: comentario de `initVisit` fuera de su lugar.
- **R36** → roadmap: la entrada de acerca enumera especialidades a mano.
