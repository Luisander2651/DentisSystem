---
result: fail
round: 2
mode: full
constitution_version: 1.1.2
date: 2026-09-29
spec_sha: 588f136e102f
plan_sha: 3bc7524bfa70
tasks_sha: 4c1487290b98
---

# Análisis · 015 Despliegue de producción en el VPS

## Resumen
**fail**, por 2 hallazgos ALTOS:
- A32: `trustProxies(at: config(...))` en `withMiddleware` se ejecuta antes de cargar la
  configuración y rompería toda petición real, aunque Pest pase.
- A33: CA18 promete los logs de WhatsApp sin datos personales, pero hay otros cuatro archivos del
  flujo que los registran.

Modo completo, porque cambió más del 40 % del plan y las tareas. De la ronda 1 hay 26 hallazgos
resueltos y 5 resueltos con efecto nuevo (→ A32, A33, A37, A44, A50).

Conteo nuevo: 0 CRÍTICOS · 2 ALTOS · 11 MEDIOS · 6 BAJOS.

Decisiones del usuario (2026-09-29):
- A33: cerrar OB2.b y RS9.a completos (7 archivos, incluye A41).
- A40: el enlace de restablecimiento entra en la 015.

Ambas requieren `/specify --edit`.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 18 / 18 (formal; el test de CA18 no cubre el flujo, A33) |
| Amenazas con control y test | 15 / 15 (TM14 sin test contra el origen, A38) |
| Principios de la constitución evaluados | 14 / 14 (P11 ⚠️ A33 y A41; P12 ⚠️ A42) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A33) | `TwilioConection` limpio en el plan; el resto del flujo no |
| A2 | CRÍTICA | resuelto | `.dockerignore`, T040, TM13 y comprobaciones en T032, `deploy.sh` y `verify.sh` |
| A3 | ALTA | resuelto | volúmenes con nombre compartidos (T033) |
| A4 | ALTA | resuelto | `storage-public`, copia en `--first`, `/storage/login.jpg` en `verify.sh` |
| A5 | ALTA | resuelto (ver A46) | `^~ /.well-known/acme-challenge/`; dotfiles → 404 |
| A6 | ALTA | resuelto | adaptadores reales en T016 |
| A7 | MEDIA | resuelto (ver A34) | parámetros de predis |
| A8 | MEDIA | resuelto | excepción propia sin previa |
| A9 | MEDIA | resuelto | `ws://` en modo hot |
| A10 | MEDIA | resuelto con efecto nuevo (→ A37) | `up -d app` interpola `APP_VERSION` desde `.env` |
| A11–A18 | MEDIA | resueltos | ver plan v2 y tareas v2 |
| A19 | MEDIA | resuelto con efecto nuevo (→ A50) | etiquetas flotantes; CI con Node 20 |
| A20–A27 | MEDIA/BAJA | resueltos | ver plan v2 y tareas v2 |
| A28 | ALTA | resuelto con efecto nuevo (→ A32, A38) | CA16/CA17 y TM14 |
| A29, A30 | MEDIA | resueltos | `--origin` y rutas de `/home/deploy` |
| A31 | BAJA | resuelto con efecto nuevo (→ A44) | el caso Pest no puede fallar |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A32 | Viabilidad | ALTA | plan `bootstrap/app.php`, T021 | `withMiddleware` corre al resolver el kernel HTTP, antes de `LoadConfiguration`: `config()` falla en cada petición real (`ApplicationBuilder.php:276`). En Pest la configuración ya está cargada y T005 pasaría. | Condición: los rangos de confianza se leen en tiempo de petición. Test: T005 más una petición HTTP real a `/up` en local (T046) y `verify.sh --local`. → `/plan --fix`, `/tasks --fix` |
| A33 | Cobertura (P11) | ALTA | spec CA18, D14, T007, T052 | Registran teléfono, nombre, variables o trazas `CreatedAppointmentListener`, `SendAppointmentConfirmationUseCase`, `ConfirmationAppointmentMessage` y `RetriveDataForScheduledAppointmenEventUseCase`, además de `TwilioConection`. | Decisión del usuario: cerrar OB2.b y RS9.a completos (7 archivos). Condición: ningún log del flujo de WhatsApp ni del de restablecimiento de contraseña contiene teléfono, nombre, email, variables, mensaje de la excepción ni trazas. Test: `Log::spy()` sobre cada flujo completo, con éxito y con error. → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A34 | Viabilidad | MEDIA | plan `config/database.php`, T026 | `PostgresConnector` de Laravel 12 no pasa `connect_timeout` al DSN. | Condición: `/up` 500 en menos de 5 s con PostgreSQL inalcanzable. Test: T012. → `/plan --fix`, `/tasks --fix` |
| A35 | Cobertura | MEDIA | imagen `web`, T032, `verify.sh` | Nada exige que `web` tenga los assets compilados (el `.dockerignore` excluye `public/build`). | Condición: `web` sirve los assets del manifest de `prod`. Test: manifest igual en T032; un asset 200 en `verify.sh --remote`. → `/plan --fix`, `/tasks --fix` |
| A36 | Viabilidad | MEDIA | imagen `prod`, volúmenes, T032 | Con `USER www-data`, los volúmenes nuevos heredan dueños de la imagen; si son de root, `optimize` y los logs fallan. | Condición: `prod` arranca sin root con volúmenes vacíos y `optimize` termina. Test: `docker run` en T032 y `/up` 200. → `/plan --fix`, `/tasks --fix` |
| A37 | Inconsistencia | MEDIA | Rollout, cron, `deploy-hook`, T052, T053 | Los comandos de Compose fuera de `deploy.sh` interpolan `APP_VERSION` desde `.env`, que puede no coincidir con `.deploy/current`; el cron y el hook de certbot corren en otro contexto. | Condición: toda invocación de Compose usa la versión en servicio. Test: tras el rollback de T054, `up -d app` conserva la etiqueta; el cron y el hook corren en su contexto real (T053). → `/plan --fix`, `/tasks --fix` |
| A38 | Verificabilidad | MEDIA | D13, `verify.sh` | En producción actúa nginx `real_ip`, no `trustProxies`; ningún test envía cabeceras falsas al origen. | Condición: petición al origen con `CF-Connecting-IP`/`X-Forwarded-For` falsos cuenta por la IP de la conexión. Test: `verify.sh --remote --origin`. → `/plan --fix`, `/tasks --fix` |
| A39 | Ambigüedad | MEDIA | T052, plan | La app no registra la IP en ningún log. | Condición: definir qué logs llevan la IP (nginx). Test: T052 ajustado. → `/tasks --fix` |
| A40 | Cobertura (CA3) | MEDIA | `SendResetPasswordEmailUseCase.php:15` | El enlace del correo está fijo en `http://localhost:8000`. | Decisión del usuario: dentro. Condición: el enlace usa el dominio de `APP_URL`. Test: Pest del caso de uso. → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A41 | Constitución (P11) | MEDIA | `BrevoApi` | Registra `body` y `getMessage()` del SDK, que pueden incluir el email. | Incluido en la decisión de A33. |
| A42 | Constitución (P12) | MEDIA | T051, Rollout, T055 | T051 despliega en el droplet en servicio antes de documentar y ensayar el rollback del primer paso, que además está incompleto. | Condición: antes de T051, `deployment.md` tiene ese rollback y se ensayó en local. Test: ensayo local `4b3aecf` → `--first` → vuelta. → `/plan --fix`, `/tasks --fix` |
| A43 | Dependencias | MEDIA | T018, T019, T051, T054, T091 | T018 usa `lib.sh` (T035) sin depender de ella; T051 no depende de T037; T054 no depende de T036 ni de T038; T091 marca RD1.a sin depender de T054. | Grafo corregido. → `/tasks --fix` |
| A44 | Verificabilidad (P2) | BAJA | T010 | `X-Powered-By` lo añade PHP al enviar, no aparece en Pest. | Comprobarlo contra el servidor real; el caso Pest queda como regresión declarada. → `/tasks --fix` |
| A45 | Viabilidad | BAJA | T010 (modo hot) | Un `public/hot` real en `pest --parallel` afecta a otros procesos. | Condición: el test no crea ni deja `public/hot` compartido. → `/tasks --fix` |
| A46 | Viabilidad | BAJA | `prod.conf`, T050, T053 | Con "Always Use HTTPS" de Cloudflare, el desafío ACME se redirige al 443. | Condición: la renovación funciona con la configuración real de Cloudflare. Test: dry-run de T053. → `/plan --fix`, `/tasks --fix` |
| A47 | Redacción | BAJA | spec Supuestos e Historial; cabecera de `tasks.md` | "15 criterios"; orden del Historial; T004 y T039 citados sin existir. | Corregir. → `/specify --edit`, `/tasks --fix` |
| A48 | Cobertura | BAJA | plan Docs | Ninguna tarea actualiza `roadmap.md`. | Añadir tarea o quitarlo del plan. → `/tasks --fix` |
| A50 | Ambigüedad | BAJA | `Dockerfile`, T017, CI | "Versión fija" ambiguo; CI con Node 20. | Condición: nivel de fijación comprobable y misma versión de Node en CI y en la imagen. → `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Ninguna: A33 (con A41) y A40 las decidió el usuario el 2026-09-29.

## Aceptados
Ninguno.
