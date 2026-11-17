---
spec: 027-testimonios-publicos
verdict: changes_requested
round: 1
date: 2026-11-17
base: 01704a6
head: 46523b4
human_signoff: pending
---

# Review · 027 Testimonios enviados desde el sitio público

## Resumen
Se piden cambios por un hallazgo importante de seguridad que el usuario decide corregir (R1): el límite de envíos se puede evadir. Además se corrigen los menores baratos, también por decisión del usuario.

Los 11 criterios tienen tests que pasan y los 7 controles del modelo de amenazas existen. Revisaron tres subagentes de contexto limpio (spec y calidad, seguridad, y accesibilidad con SEO) sobre un diff de 58,6 KB.

Conteo: bloqueante 0 · importante 1 · menor 12

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2541 |
| `pint` en el rango | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui -- --only opinion` e `--only inicio`, con los datos de `ui:audit-data` | ✅ dos veces seguidas |
| `aidd.py validate` | ✅ |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.30.1 (`01704a6..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.178.0 | no determinado en local: se cuelga al descargar las reglas (como en la 026); CI lo ejecuta en el push |
| composer audit / npm audit | sin altas ni críticas; el aviso bajo de Laravel ya está en el roadmap |

El modelo de amenazas se cumple:
- **TM2 a TM7:** existen sus controles y sus tests fallan si se rompe el control.
- **TM1:** se puede evadir (R1).
- **IP del visitante:** es fiable detrás de Cloudflare (`TrustCloudflareProxies`).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `SubmitPublicTestimonialController.php:34-55` | El límite se puede evadir (A06), por tres vías: <br>• va por IP completa, así que en IPv6 cada dirección de la misma red /64 tiene cupo propio; <br>• no hay un tope total que proteja la cola de moderación; <br>• comprobar y contar son pasos separados, y una ráfaga simultánea se cuela | Clave por red /64 en IPv6, conteo atómico antes de guardar y tope total de 30 opiniones nuevas por hora en todo el sitio |
| R2 | menor | `PublicTestimonialSubmissionTest.php:201, 211` | `withoutMiddleware(IlluminateRouting…)` perdió las barras invertidas y no hace nada | Importar `ThrottleRequests` |
| R3 | menor | `PublicTestimonialSubmissionTest.php` (CA4) | Ningún test publica una opinión y la busca en el inicio | Publicar con la API de staff, pasar la caché y buscarla en `/` |
| R4 | menor | `PublicTestimonialSubmissionTest.php:64` (CA2) | No se comprueba que la opinión enviada no esté en el inicio | Pedir `/` tras enviar |
| R5 | menor | `SubmitPublicTestimonialController.php:37` | Con el límite alcanzado, la página 429 no conserva lo escrito y recargar reenvía | Roadmap |
| R6 | menor | `GetPublicTestimonialsController.php:28` | `?id[]=1` da un 500 genérico (TypeError) | Leer `id` solo si es texto; test |
| R7 | menor | `PublicTestimonialSubmissionTest.php:148` | `assertStatus(429)` | `assertTooManyRequests()` |
| R8 | menor | `opinion.js:20` | El contador borra el mínimo de 20 caracteres | "N de 255 caracteres (mínimo 20)" |
| R9 | menor | `components/site/seo.blade.php:3` | Comentario de cabecera roto | Reescribirlo |
| R10 | menor | `opinion.blade.php:63` | `aria-describedby=""` vacío en la casilla | Escribirlo solo con error |
| R11 | menor | `opinion.blade.php:34` | El agradecimiento tiene `role="status"` y además recibe el foco: se lee dos veces | Dejar solo el foco |
| R12 | menor | `opinion.js` | Con errores del servidor, el foco no va al primer campo con error | Enfocarlo al cargar |
| R13 | menor | `opinion.js:28-37` | Al corregir un campo se quita `aria-invalid`, pero el mensaje sigue visible y enlazado | Ocultar el mensaje y quitarlo de `aria-describedby` |

## Preparación para release
- **Despliegue:** sin migraciones ni interruptores. Rollback con `docker/prod/rollback.sh` a v0.11.0, sin datos nuevos que deshacer.
- **Documentación:** `architecture.md`, `seo.md`, `security.md` y `observability.md` actualizados (T090, T091).

## Tareas añadidas
- T007 ← R1, R2, R3, R4, R6, R7, R8, R9, R10, R11, R12, R13

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17:
- **R5** → roadmap: la página del límite no conserva lo escrito.
