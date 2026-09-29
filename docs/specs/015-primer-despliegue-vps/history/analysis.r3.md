---
result: fail
round: 3
mode: delta
constitution_version: 1.1.2
date: 2026-09-29
spec_sha: 46a718d3cce9
plan_sha: 278b043f49e3
tasks_sha: a17f53717133
---

# Análisis · 015 Despliegue de producción en el VPS

## Resumen
**fail**, por 4 hallazgos ALTOS:
- A51 y A52: las dos pilas comparten directorio y proyecto de Compose. El primer `up` de
  producción borraría la pila manual, y el `git checkout` cambiaría lo que esta monta, así que el
  rollback del primer paso no funcionaría.
- A53: el controlador de Auth del restablecimiento registra el mensaje y la traza, y la base de
  tests de Auth finge el evento.
- A54: el `report()` por defecto del worker registra el mensaje y la traza con sus argumentos.

Modo delta: seguimiento de A32–A50 y revisión solo de lo añadido en la v3. La copia de la ronda 2
no se guardó, así que el revisor recibió los hallazgos abiertos y la lista de cambios, no el diff.

De A32–A50 hay 16 resueltos y 2 resueltos con efecto nuevo (A33 → A53 y A54; A42 → A51 y A52).

Conteo nuevo: 0 CRÍTICOS · 4 ALTOS · 2 MEDIOS · 3 BAJOS.

Decisiones del usuario (2026-09-29):
- A51 y A52: producción en un clon nuevo aparte, con su propio proyecto de Compose y volúmenes de
  datos externos; la pila manual no se toca.
- A53: `SendResetPasswordEmailController` entra en CA18 (`/specify --edit`).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 19 / 19 (formal; CA18 sin el camino del worker, A54; CA13 con `--to-manual` inviable, A51 y A52) |
| Amenazas con control y test | 15 / 15 (TM15 sin el `report()` del worker, A54) |
| Principios de la constitución evaluados | los que toca el delta: P11 ⚠️ (A53, A54), P12 ⚠️ (A51, A52) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A32 | ALTA | resuelto | `TrustCloudflareProxies` lee la configuración por petición; `replace()` funciona sobre el `TrustProxies` global (`Middleware.php:218, 457-466`) |
| A33 | ALTA | resuelto con efecto nuevo (→ A53, A54) | los 7 archivos cubiertos; faltan el controlador de Auth y el `report()` del worker |
| A34 | MEDIA | resuelto | comprobación TCP de 2 s (D19, T027) |
| A35 | MEDIA | resuelto | `web` copia `public/build`; manifests comparados en T032 |
| A36 | MEDIA | resuelto | `chown` y `docker run` con volúmenes vacíos en T032 |
| A37 | MEDIA | resuelto (ver A56) | `compose.sh` (T044) |
| A38 | MEDIA | resuelto | 429 con cabeceras falsas contra el origen |
| A39 | MEDIA | resuelto | IP solo en los logs de nginx |
| A40 | MEDIA | resuelto | CA19, T008, T041 |
| A41 | MEDIA | resuelto | T029 |
| A42 | MEDIA | resuelto con efecto nuevo (→ A51, A52) | T057 antes de T050; sin ensayo local |
| A43 | MEDIA | resuelto | grafo corregido |
| A44–A48 | BAJA | resueltos | ver plan v3 y tareas v3 |
| A50 | BAJA | resuelto (ver A58) | CI con Node 22 y regla `mayor.menor` |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A51 | Viabilidad (P12) | ALTA | plan `docker-compose.prod.yml`, D18, T034, T037, T038, T051 | Mismo directorio y mismo proyecto de Compose, con los mismos nombres de servicio: el primer `up -d` de producción recrea (borra) los contenedores `laravel-*` detenidos, y `--to-manual` ya no tiene qué arrancar. | Decisión del usuario: clon aparte con proyecto propio y volúmenes de datos externos. Condición: tras `--first`, la pila manual sigue existiendo y `--to-manual` la arranca, con el mismo `db-data`. Test: ensayo **local** desde `4b3aecf` antes de T050. → `/plan --fix`, `/tasks --fix` |
| A52 | Viabilidad (P12) | ALTA | Rollout, T050, T051 | El `git checkout <tag>` en el directorio de la pila manual cambia el código, el `.env` y el nginx que esta monta; tras `--to-manual`, el sitio no vuelve por https. | Resuelto con la misma decisión (clon aparte). Condición: tras `--to-manual` el sitio sirve el código y el nginx de `4b3aecf` por https. Test: ensayo local y T051. → `/plan --fix`, `/tasks --fix` |
| A53 | Inconsistencia (P11) | ALTA | spec CA18, T008, T041 | `SendResetPasswordEmailController` (Auth) registra `getMessage()` y `getTraceAsString()`. `AuthIntegrationTestCase` finge `SendEmailForChangePasswordEvent`. | Decisión del usuario: el controlador entra en CA18. Condición: T008 ejecuta de verdad listener → caso de uso → `BrevoApi`, y ningún log del flujo contiene email ni trazas. → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A54 | Cobertura (P11, TM15) | ALTA | plan pruebas, T007, T008, `php.ini` | En el worker, el `report()` por defecto registra el mensaje y la traza. Con `zend.exception_ignore_args=Off`, los argumentos (número, email) viajan en la traza. | Condición: el fallo de un envío procesado fuera de una petición HTTP no deja mensaje del proveedor, trazas con argumentos ni datos personales. Test: caso de T007 y T008 que ejecuta el listener como el worker, con `Log::spy()`; `verify.sh --local` comprueba la opción en la imagen. → `/plan --fix`, `/tasks --fix` |
| A55 | Inconsistencia | MEDIA | T051 | La vuelta a producción tras `--to-manual` no usa el mismo procedimiento que el primer paso. | Condición: la vuelta usa el procedimiento del primer paso; `/storage/login.jpg` 200 después. → `/tasks --fix` |
| A56 | Viabilidad | MEDIA | `compose.sh`, `backup.sh`, T035, T044 | Sin `.deploy/current`, que no existe en el primer despliegue, `${APP_VERSION:?}` rompe la interpolación de todo el archivo, también para `pg_dump`. | Condición: `backup.sh` funciona sin `.deploy/current`. Test: T035 y T044 sin ese archivo. → `/plan --fix`, `/tasks --fix` |
| A57 | Ambigüedad | BAJA | T008, D15 | El binding contextual de `BrevoApi` usa `new`, así que atar `Brevo::class` no inyecta el cliente simulado. | Indicar en T008 cómo se sustituye el binding sin tocar el provider de producción. → `/tasks --fix` |
| A58 | Inconsistencia | BAJA | plan `Dockerfile` | Los ejemplos del plan (`node:22-alpine`, `composer:2` y la etapa `dev` con `composer:latest`) contradicen la regla de A50. | Corregir los ejemplos; T017 lo comprueba en todas las etapas. → `/plan --fix` |
| A59 | Redacción | BAJA | plan Observabilidad, D9, orden de Decisiones | Las etiquetas de Grafana serán `dentissa-*`; D9 cita un test que ya no existe; D16 está fuera de orden. | Corregir el texto. → `/plan --fix` |

## Decisiones pendientes del usuario
Ninguna: A51, A52 y A53 las decidió el usuario el 2026-09-29.

## Aceptados
Ninguno.
