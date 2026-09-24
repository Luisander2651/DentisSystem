---
status: approved
updated: 2026-09-24
---

# Observabilidad y auditoría de Dentissa

Estado de cada capacidad: **presente** (instalada o declarada) · **configurada** (configurada para
este proyecto) · **en uso** (el código o la operación la ejercitan). Solo "en uso" cuenta como
cubierta.

Inferido del código el 2026-09-24 (`/init --upgrade`); requiere revisión humana. Los valores de
`.env` no se leyeron: lo que depende de ellos se indica como no determinado o lo confirmó el
usuario (indicado en cada caso).

## Resumen
| Capacidad | Herramienta | Estado | Evidencia |
|---|---|---|---|
| Logs de aplicación | `Log::` de Laravel; canal real en local `stderr` (confirmado por el usuario, 2026-09-24); declarado en `.env.example`: `stack` → `single` | en uso | `config/logging.php:21`, `.env.example:18-19` |
| Agregación de logs | Alloy → Loki → Grafana | en uso en local según la configuración real (`LOG_CHANNEL=stderr` y Alloy recoge stdout/stderr de los contenedores); por confirmar en Grafana. No garantizado en entornos nuevos: `.env.example` declara `single` | `docker-compose.yml:124-170`, `docker/grafana/provisioning/datasources/loki.yml` |
| Correlación por petición | — | ausente | sin `X-Request-Id`, `Log::withContext` ni `Context::add` en `app/`, `bootstrap/`, `routes/` |
| Registro de auditoría | — | ausente | sin tabla, listeners de `Illuminate\Auth\Events` ni paquete; `app/Providers/EventServiceProvider.php:18-26` |
| Métricas | — | ausente | nada en `composer.json`, `config/`, `docker/` |
| Trazas distribuidas | — | ausente | |
| Alertas | Grafana | presente; sin reglas ni dashboards aprovisionados | `docker/grafana/provisioning/` (solo datasource) |
| Health check | `/up` de Laravel | en uso; no comprueba PostgreSQL, Redis ni la cola; sin `healthcheck` en Compose | `bootstrap/app.php:20` |

## Logs
- Formato: texto de línea del canal `single`; casi todas las llamadas pasan un array de contexto
  (`Log::info('…', [...])`), sin formateador JSON. Excepción: `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php:49`
  concatena el mensaje de la excepción.
- Campos obligatorios (objetivo, P14): `timestamp`, `level`, `request_id`, `actor_id` (si hay
  sesión), `message`, `context`. Hoy no hay `request_id` ni `actor_id`.
- Emisión por módulo (aprox.): Appointments 16 · whatsApp 18 · Auth 5 (solo `error` en el catch
  genérico) · Users 4 · Email 4 · ContentManagement 1 · Patients, AppointmentTracking,
  Estadisticas y `app/Core` 0.
- Errores: `withExceptions` vacío (`bootstrap/app.php:36-38`); los controladores registran
  `Log::error` solo en el `catch (\Exception)` genérico, sin ruta, actor ni `request_id`; las
  excepciones de dominio (400/401/403/409) no se registran, salvo en `CreateAppointmentController`.
- Nivel por entorno: `.env.example` trae `LOG_LEVEL=debug` y `APP_DEBUG=true`; producción
  objetivo `info` (`.ai/project.yaml → observability.log_level_prod`). Valor real en el VPS: no
  determinado (no existe aún).
- Destino y retención: en local, stderr del contenedor → Alloy → Loki (confirmado por el usuario);
  la configuración declarada en `.env.example` escribiría en `storage/logs/laravel.log` sin
  rotación explícita. Logs de contenedores `json-file` 10m×3 (`docker-compose.yml:21-25`, `:36-40`); Loki con la configuración por defecto
  de la imagen, sin retención configurada.
- Qué **nunca** se registra (P11): teléfono, email, dirección, nombre del paciente, datos de salud,
  contraseñas, tokens, secretos, cuerpos completos de petición o de respuestas de terceros.
  Enmascarado: TODO(init): no existe mecanismo; hoy se incumple (ver Brechas).

## Correlación
- Identificador por petición: cabecera `X-Request-Id` (propuesta); se acepta del cliente si es
  válido o se genera; se devuelve en la respuesta y se añade al contexto de todos los logs de la
  petición y de los trabajos en cola que dispare (`ConfirmationAppointmentMessage`, listeners de
  WhatsApp y Email). **No implementado.**

## Registro de auditoría
Separado de los logs técnicos: responde **quién hizo qué, sobre qué, cuándo y con qué resultado**.
Hoy no se registra ningún evento de esta tabla.

| Evento | Obligatorio | Campos |
|---|---|---|
| Login exitoso y fallido (staff y pacientes) | sí | actor (o identificador intentado), IP, resultado, fecha |
| Acceso denegado (401/403: `OnlyAdmin`, `CurrentActorAuthorizationService`) | sí | actor, acción, recurso, fecha |
| Lectura de datos sensibles (pacientes, expediente, seguimiento clínico, recetas) | sí | actor, recurso, fecha |
| Cambio de datos sensibles | sí | actor, recurso, campos cambiados (sin valores sensibles), fecha |
| Cambio de permisos o roles (`UpdateUserUseCase`) | sí | actor, sujeto, antes → después, fecha |
| Lectura del propio registro de auditoría | sí | actor, filtro consultado, fecha |

- Almacenamiento: tabla propia **solo anexado** (sin `UPDATE` ni `DELETE` desde la aplicación,
  tampoco para administradores). Por implementar.
- Retención: **5 años**, alineada con la conservación del expediente clínico (decisión del
  usuario, 2026-09-24). Base legal: LFPDPPP (datos de salud = datos personales sensibles);
  plazo por confirmar con asesoría legal.
- Quién puede leerlo: solo el rol administrador; cada lectura del registro también se audita.

## Métricas y alertas
| Métrica / alerta | Umbral | Canal |
|---|---|---|
| Tasa de errores 5xx | TODO(init) | TODO(init) |
| Logins fallidos por IP o cuenta | TODO(init) | TODO(init) |
| Latencia p95 | TODO(init) | TODO(init) |
| Cola de trabajos atascada | TODO(init) | TODO(init) |

Ninguna existe hoy; las dos primeras forman parte del criterio del objetivo 4 del roadmap.

## Brechas
Formato 1.5.6 (numeración añadida por `/init --upgrade` a 1.6.0, 2026-09-24; contenido sin
cambios). Estados: pendiente · en curso (spec NNN) · mitigada (vX.Y.Z) · aceptada (excepción EX<n>).
"(derivada)" marca una corrección que el riesgo no escribía de forma explícita y se deduce de su
descripción o del principio que la exige. Un riesgo solo está mitigado cuando todas sus
correcciones están mitigadas o aceptadas.

### OB1 · Alta — Sin registro de auditoría
de logins, accesos denegados, lecturas o cambios de datos de salud y cambios de rol (RS11 de [security.md](security.md)). → [roadmap objetivo 5](roadmap.md)

Correcciones:
- OB1.a Registro de auditoría de solo anexado para logins (también fallidos), accesos denegados, lecturas y cambios de datos de salud y cambios de rol (derivada) — estado: pendiente (roadmap objetivo 5)

### OB2 · Alta — Datos personales en logs
(teléfono, nombre, email, variables de la plantilla de WhatsApp): `RetriveDataForScheduledAppointmenEventUseCase.php:55`, `CreateAppointmentController.php:51-52`, `CreatedAppointmentListener.php:38-39,61`, `SendAppointmentConfirmationUseCase.php:20-21,34-35,45`, `ConfirmationAppointmentMessage.php:21,31`, `TwilioConection.php:31,43,69,74,90`, `SendPasswordResetListener.php:44-45,50`, `BrevoApi.php:43` (RS9 de [security.md](security.md)). → [roadmap objetivo 5](roadmap.md)

Correcciones:
- OB2.a Retirar teléfono y nombre de los `Log::` de `CreateAppointmentController` (derivada) — estado: en curso (spec 014)
- OB2.b Retirar datos personales del resto de logs citados (whatsApp, Email, `RetriveDataForScheduledAppointmenEventUseCase`) (derivada) — estado: pendiente (roadmap objetivo 5)

### OB3 · Media — Sin correlación por petición
→ [roadmap objetivo 5](roadmap.md)

Correcciones:
- OB3.a Identificador `X-Request-Id` por petición en logs y respuesta (derivada) — estado: pendiente (roadmap objetivo 5)

### OB4 · Baja — `.env.example` no refleja el canal real de logs
En local se usa `LOG_CHANNEL=stderr` (los logs llegan a Loki vía Alloy), pero `.env.example` declara `stack` → `single`: un entorno nuevo creado desde el ejemplo (p. ej. el VPS) escribiría en archivo y no enviaría logs a Loki. → [roadmap objetivo 5](roadmap.md)

Correcciones:
- OB4.a `LOG_CHANNEL=stderr` en `.env.example` — estado: pendiente (roadmap objetivo 5)

### OB5 · Media — `LOG_LEVEL=debug` y `APP_DEBUG=true` por defecto
en `.env.example`. → [roadmap objetivo 5](roadmap.md)

Correcciones:
- OB5.a `LOG_LEVEL=info` por defecto (derivada) — estado: pendiente (roadmap objetivo 5)
- OB5.b `APP_DEBUG=false` por defecto (derivada) — estado: pendiente (roadmap objetivo 5)

### OB6 · Media — Grafana (3000) y Loki (3100) publicados en el host
, aunque [deployment.md](deployment.md) prevé acceso solo por túnel SSH. → [roadmap objetivo 4](roadmap.md)

Correcciones:
- OB6.a No publicar Grafana ni Loki en el host; acceso por túnel SSH (derivada) — estado: pendiente (roadmap objetivo 4)

### OB7 · Media — Sin métricas ni alertas
→ [roadmap objetivo 4](roadmap.md) (5xx y logins fallidos)

Correcciones:
- OB7.a Alerta de tasa de errores 5xx (derivada) — estado: pendiente (roadmap objetivo 4)
- OB7.b Alerta de logins fallidos (derivada) — estado: pendiente (roadmap objetivo 4)

### OB8 · Baja — Loki sin retención configurada
→ [roadmap objetivo 4](roadmap.md)

Correcciones:
- OB8.a Configurar la retención de Loki (derivada) — estado: pendiente (roadmap objetivo 4)

### OB9 · Baja — `/up` no comprueba PostgreSQL ni Redis
→ [roadmap objetivo 4](roadmap.md)

Correcciones:
- OB9.a `/up` comprueba PostgreSQL y Redis (derivada) — estado: pendiente (roadmap objetivo 4)

### OB10 · Baja — Trazas completas (`getTraceAsString`) en logs de Auth y whatsApp
pueden incluir argumentos con datos; y las 500 devuelven el mensaje de la excepción. → [roadmap objetivo 1](roadmap.md)

Correcciones:
- OB10.a Respuestas 500 sin el mensaje de la excepción (derivada) — estado: en curso (spec 014)
- OB10.b Retirar `getTraceAsString()` y `getMessage()` de los logs de Auth y whatsApp (derivada) — estado: pendiente (roadmap, Pendientes y deuda)

### OB11 · Baja — Posible doble registro de listeners
(descubrimiento automático + `$listen`): envío y log duplicados; comprobar con `php artisan event:list`. → roadmap, "Pendientes y deuda"

Correcciones:
- OB11.a Comprobar con `php artisan event:list` y eliminar el registro duplicado (derivada) — estado: pendiente (roadmap, Pendientes y deuda)

## Brechas por confirmar
- Que los logs de la aplicación se vean en Loki en local — depende de que php-fpm reenvíe la
  salida de los workers (la imagen oficial `php:8.4-fpm-alpine` lo hace por defecto con
  `catch_workers_output`) — cómo confirmarlo: Grafana → Explore → Loki, filtrando por el
  contenedor de la app, o `docker compose logs app`.

## No determinado
- `LOG_CHANNEL`/`LOG_STACK` en el VPS (aún no existe). En local: `stderr` (confirmado por el
  usuario, 2026-09-24).
- Si php-fpm redirige algo a stdout (`catch_workers_output`); `docker/Dockerfile` no lo declara.
- Si un firewall impide el acceso externo a los puertos 3000 y 3100.
