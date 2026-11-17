---
spec: 027-testimonios-publicos
verdict: approved
round: 2
date: 2026-11-17
base: 75cdd60
head: 7786b62
human_signoff: Luisander2651 2026-11-17
---

# Review · 027 Testimonios enviados desde el sitio público

## Resumen
Ronda 2 (`--rerun`) sobre T007. La ronda anterior está en [history/review.r1.md](history/review.r1.md).

R1 y los menores asignados (R2–R4 y R6–R13) quedan corregidos, sin hallazgos nuevos. R5 está aceptado al roadmap.

El diff de esta ronda es pequeño (12 archivos), así que la revisé sin subagente, releyendo desde cero el controlador, la página, su JS y los tests.

Conteo de esta ronda: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2547 |
| `pint` en el rango | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui -- --only opinion`, con los datos de `ui:audit-data` | ✅ 3 de 3 |
| `aidd.py validate` | ✅ |
| Chrome (390 px) | Foco en el primer campo con error; al corregirlo se quita la marca y se oculta el mensaje; el contador dice "N de 255 caracteres (mínimo 20)" |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `SubmitPublicTestimonialController`: <br>• clave por red /64 en IPv6; <br>• `RateLimiter::hit()` antes de guardar, atómico; <br>• tope de 30 por hora para toda la web. <br>Los tests de IPv6 y del tope total fallan con el control quitado |
| R2 | corregido | `use ThrottleRequests` en el test |
| R3, R4 | corregido | El caso que publica por la API de staff y comprueba el inicio, antes y después de que caduque la caché |
| R5 | aceptado → roadmap | — |
| R6 | corregido | `?id` solo como texto; el caso `?id[]=1` responde 200 y `?id=<oculto>` no devuelve nada |
| R7 | corregido | `assertTooManyRequests()` |
| R8 | corregido | El contador incluye el mínimo; caso en `SiteScriptsTest` |
| R9 | corregido | Comentario de `seo.blade.php` reescrito |
| R10 | corregido | Sin `aria-describedby` vacío; caso nuevo |
| R11 | corregido | El agradecimiento sin región; el título toma el foco |
| R12, R13 | corregido | `opinion.js`; verificado en Chrome |

## Seguridad
- **Herramientas:** como en la ronda 1; gitleaks sin hallazgos en el rango.
- **Límite de envíos:** el tope de la web lleva también su propio mensaje de 429.
- **Efecto menor del límite:** si se supera el tope total, el contador de la conexión ya ha sumado ese intento. Juega del lado seguro, así que no es un hallazgo.

## Hallazgos
Ninguno nuevo.

## Preparación para release
- **Despliegue:** sin migraciones ni interruptores. El rollback es `docker/prod/rollback.sh` a v0.11.0.
- **Rama:** sale de `docs/release-v0.11.0`, así que el PR a `main` incluye el cierre de la v0.11.0 si el PR #55 aún no está fusionado.

## Tareas añadidas
Ninguna en esta ronda (T007 viene de la ronda 1).

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17 (ronda 1):
- **R5** → roadmap: la página del límite no conserva lo escrito.
