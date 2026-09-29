---
spec: 015-primer-despliegue-vps
status: approved   # draft | approved | blocked
created: 2026-09-29
---

# Plan · 015 Despliegue de producción en el VPS

Versión 4, corregida con `--fix` tras el `/analyze` ronda 3 (A51–A59) y la tercera edición de la
spec (CA18 con el controlador de Auth y los fallos en segundo plano). La v3 se corrigió tras la
ronda 2 (A32–A50). Versiones anteriores: v1 en el commit `1a943f6`; v2 sin commit (ver
`history/analysis.r1.md`).

## Enfoque técnico
El punto de partida es el despliegue manual que ya está en servicio en `dentissapp.com`:
- Cloudflare en modo Full (strict), certbot standalone y `ufw` con 22, 80 y 443;
- el repositorio en `/home/deploy/DentisSystem`, con el usuario `deploy`;
- el commit `4b3aecf` y el runbook del usuario.

El plan lo vuelve repetible y reversible separando local y producción, y separando la pila
manual de la nueva: producción vive en un **clon aparte** (`/home/deploy/dentissa`) con su propio
proyecto de Compose, que usa como externos los volúmenes de datos de la pila manual. La pila manual
(`/home/deploy/DentisSystem`) no se toca y solo se detiene, así que se puede volver a ella tal cual
(decisión del usuario, 2026-09-29, A51, A52).
- `docker-compose.yml` vuelve a ser el entorno de desarrollo.
- Un `docker-compose.prod.yml` independiente arranca imágenes **inmutables**, construidas desde el
  tag con un `Dockerfile` multi-etapa y un `.dockerignore` que deja fuera `.env`, `.git` y los
  artefactos locales ([ADR 0004](../../adr/0004-imagen-inmutable-y-compose-de-produccion.md)).

La aplicación asume lo que se puede probar con Pest:
- cabeceras de seguridad con CSP con nonce y CORS restrictivo;
- IP real del visitante solo desde los rangos de Cloudflare;
- `/up` que comprueba PostgreSQL y Redis;
- errores web genéricos;
- proveedores leídos por `config()`, y flujos de WhatsApp y de correo que no registran datos
  personales, con el enlace de restablecimiento en el dominio real;
- una plantilla `.env.example` segura.

El servidor asume lo demás con scripts versionados en `docker/prod/`:
- despliegue, rollback, backup y restauración, que anotan cada operación en un registro;
- `verify.sh`, que comprueba desde fuera lo que Pest no alcanza, contra el dominio **y contra la
  IP directa del droplet** (Docker publica puertos por encima de `ufw`).

El ensayo del rollback (CA13) cierra la spec.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec 015 `approved` (reaprobada el 2026-09-29 tras la edición). |
| P2 Test que falla antes y pasa después | ✅ | Todo comportamiento de la aplicación tiene tests Pest que hoy fallan: cabeceras, CORS, IP real, `/up`, errores web, `config()`, logs de los flujos de WhatsApp y de correo, enlace de restablecimiento y plantilla. Viven en `tests/Modules/Core/`, `tests/Modules/whatsApp/` y `tests/Modules/Email/`. La ausencia de `X-Powered-By` la añade PHP fuera de la respuesta de Symfony, así que se comprueba contra el servidor real (A44). Los tests de proveedores usan los adaptadores reales con un cliente simulado, no el mock de la clase (A6). La configuración de servidor no es código de la aplicación: se verifica con `docker/prod/verify.sh`, cuya línea base falla hoy, y en el ensayo (CA13). |
| P3 Capas del módulo | ✅ | Twilio y Brevo cambian en sus adaptadores de `Infrastructure/` y en el binding de `AppServiceProvider`. La limpieza de logs no cambia firmas entre capas. El enlace de restablecimiento se construye en el caso de uso a partir de `config('app.url')`. El middleware, el proxy de confianza y el listener de salud viven en `app/Core/`. Sin dependencias nuevas entre módulos. |
| P4 Contrato de API primero | ✅ | No cambia ningún endpoint de `/api/v1`. Los contratos de `/up`, CORS y cabeceras se fijan abajo y en tests. |
| P5 Autorización en el servidor | ➖ | No cambia la autorización. El límite de peticiones pasa a contar por IP real (CA16), sin cambiar actores ni permisos. |
| P6 Validación con FormRequest | ➖ | No cambia la entrada de ningún endpoint. |
| P7 Errores sin detalles internos | ✅ | `APP_DEBUG=false` en la plantilla y en producción. Test del error web genérico. `/up` falla con un mensaje sin host (A8). |
| P8 Secretos fuera del repositorio | ✅ | Sin `env()` en `app/` (test de arquitectura). Compose con `${VAR:?}`. `.env` fuera de las imágenes por `.dockerignore` y `env_file` (A2, TM13), comprobado en el build y en `verify.sh`. Variables nuevas por nombre en `.env.example`. |
| P9 Migraciones reversibles | ➖ | Sin migraciones. `deploy.sh` ejecuta `migrate --force` para versiones futuras, siempre después del backup. |
| P10 Dependencias nuevas con ADR | ✅ | Sin paquetes nuevos: `npm audit fix` actualiza dentro del rango. Las imágenes base (`node`, `composer`, `nginx`, `php`) van con versión fija y en soporte, en el ADR 0004 (A19). |
| P11 Datos sensibles y modelo de amenazas | ✅ | Los 7 archivos que cita OB2 dejan de registrar teléfono, nombre, email, variables, mensajes del proveedor y trazas, y las excepciones que relanzan los adaptadores no llevan datos del destinatario (CA18, A33, A41). Lo mismo vale para el controlador que recibe la solicitud de restablecimiento (A53) y para los fallos que reporta el worker: `zend.exception_ignore_args=On` en la imagen, para que las trazas no lleven argumentos (A54). Backups en carpeta 700 y archivos 600, fuera de las imágenes y de lo que sirve nginx. El registro de despliegues no contiene datos de pacientes. Modelo de amenazas abajo (TM1–TM15). |
| P12 Producción con aprobación, rollback y sin altas | ✅ | La spec construye y ensaya el rollback (CA13) y cierra EX1 (CA14). El rollback del **primer** paso (volver a la pila manual, intacta en su propio directorio) se documenta y se **ensaya en local** antes de tocar el droplet, y se repite en el ensayo del droplet (A42, A51, A52). Toda operación en el droplet durante `/implement` se hace con aprobación explícita. |
| P13 Lógica de negocio en el backend | ➖ | Solo se añade `nonce` a scripts existentes. |
| P14 Trazabilidad | ✅ | Nivel `info` y canal `stderr` hacia Loki. IP real en los logs de nginx y en la aplicación (CA16). Registro de operaciones (CA15). La correlación `X-Request-Id` y la auditoría de la aplicación siguen en el objetivo 5. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `.dockerignore` (nuevo) | Excluye `.env*` (salvo `.env.example`), `.git`, `node_modules`, `vendor`, `public/hot`, `public/build`, `public/storage`, `storage/*` (salvo la estructura de carpetas), `bootstrap/cache/*.php`, `tests`, `docs`, `.ai`, `aidlc-docs` y `docker-compose*.yml` (A2). | Medio: una exclusión de más rompe el build (se detecta al construir). |
| `docker/Dockerfile` | Multi-etapa con imágenes base fijadas por etiqueta completa `mayor.menor[.parche]` y en soporte, nunca `:latest` ni solo la mayor (A19, A50):<br>• `base`: `php:8.4-fpm-alpine` con las extensiones.<br>• `dev`: la actual, con Node y Composer.<br>• `assets`: Node 22 con etiqueta completa (p. ej. `node:22.20-alpine3.22`), con `npm ci && npm run build`.<br>• `vendor`: Composer 2 con etiqueta completa (p. ej. `composer:2.8`), con `install --no-dev --optimize-autoloader --no-scripts`. La etapa `dev` también deja de usar `composer:latest` (A58).<br>• `prod`: `base` + código + `vendor/` + `public/build` + `docker/prod/php.ini` (`expose_php=Off`, `zend.exception_ignore_args=On`), sin Node ni Composer. `storage/` y `bootstrap/cache/` son de `www-data` antes de `USER www-data`, para que los volúmenes nuevos hereden ese dueño (A36). `ENTRYPOINT` `entrypoint.sh`.<br>• `web`: `nginx` fijado con `public/` y el mismo `public/build` de la etapa `assets` (A35), más `prod.conf`. | Medio: un fallo en el build rompe el despliegue (se detecta antes de parar nada). |
| `docker/prod/php.ini` (nuevo) | `expose_php=Off` (A31) y `zend.exception_ignore_args=On`, para que las trazas que reporta el worker no lleven argumentos con datos personales (A54). | Bajo. |
| `docker/prod/entrypoint.sh` (nuevo) | `php artisan optimize` y `exec "$@"` (php-fpm o `queue:work`). | Bajo. |
| `docker-compose.yml` | Vuelve a local:<br>• `app` y `queue` con `target: dev` y la misma carpeta montada.<br>• `vendor/` y `node_modules/` en **volúmenes con nombre compartidos** por `app` y `queue`, para que la cola encuentre `vendor/autoload.php` (A3).<br>• nginx `8000:80` con `default.conf`; vite en `127.0.0.1:5173`.<br>• PostgreSQL y Redis en `127.0.0.1`, Redis sin contraseña (solo local).<br>• Grafana en `127.0.0.1:3000`.<br>• Credenciales de PostgreSQL con `${VAR:?}`. | Medio: el `.env` local debe definir `DB_*` iguales a los del volumen existente (ver Rollout). |
| `docker-compose.prod.yml` (nuevo) | Archivo completo con `name: dentissa` (proyecto propio) y `container_name` `dentissa-*`, para no chocar con la pila manual (proyecto del directorio `DentisSystem`, contenedores `laravel-*`). Los volúmenes de datos (`db-data`, `redis-data`, `loki-data`, `grafana-data`) se declaran `external` con el nombre de los de la pila manual (`${DATA_VOLUME_PREFIX:?}_db-data`, etc.), así que los datos son los mismos y la pila manual se puede **detener sin borrar** (A51):<br>• `app` y `queue`: `dentissa-app:${APP_VERSION:?}`, `env_file: .env`, volúmenes `storage` (`storage/`) y `storage-public` (`storage/app/public`), `restart: unless-stopped`; `queue` con `queue:work --tries=3 --max-time=3600`.<br>• `nginx`: `dentissa-web:${APP_VERSION}`, `80:80` y `443:443`, `/etc/letsencrypt:ro`, `/var/www/certbot:ro` y `storage-public` de solo lectura en `public/storage` (A4).<br>• `db` y `redis`: sin puertos, credenciales `${…:?}` y healthcheck.<br>• `loki` y `alloy`: sin puertos. `grafana`: `127.0.0.1:3000`.<br>• Nunca corren a la vez dos pilas sobre el mismo volumen: `--first` detiene la manual antes de arrancar esta, y `--to-manual` al revés. | Alto: es el entorno real; se valida con `docker compose -f docker-compose.prod.yml config`, en el ensayo local y en el del droplet. |
| `docker/nginx/default.conf` | Vuelve a la versión local (`listen 80`, `server_name localhost`). | Bajo. |
| `docker/nginx/prod.conf` (nuevo) | Parte de `4b3aecf`:<br>• En el puerto 80, `location ^~ /.well-known/acme-challenge/` (webroot `/var/www/certbot`) antes del 301.<br>• En el 443: `listen 443 ssl` con `http2`, `Strict-Transport-Security "max-age=31536000; includeSubDomains" always` y `server_tokens off`.<br>• Dotfiles: `location ~ /\.(?!well-known)` → `return 404` (A5); `nosniff` en `/build/`.<br>• IP real: `set_real_ip_from` con los rangos de Cloudflare y `real_ip_header CF-Connecting-IP`, para que los logs de nginx y `REMOTE_ADDR` sean los del visitante (CA16). | Medio: HSTS de un año; rangos de Cloudflare que mantener (ver Riesgos). |
| `docker/prod/compose.sh` (nuevo) | Envoltorio de `docker compose -f docker-compose.prod.yml` en `DENTISSA_DIR` que exporta `APP_VERSION` desde `.deploy/current`, o un valor neutro si aún no existe, para que comandos que no usan la imagen (`exec db`, `config`) funcionen en el primer despliegue (A56). Todo comando de Compose fuera de `deploy.sh` y `rollback.sh` pasa por aquí: interruptor de la CSP, cron de backups y `deploy-hook` de certbot (A37). | Bajo. |
| `docker/prod/lib.sh` (nuevo) | Rutas parametrizadas: `DENTISSA_DIR` (clon de producción, por defecto `/home/deploy/dentissa`), `MANUAL_DIR` (pila manual, por defecto `/home/deploy/DentisSystem`), `BACKUP_DIR` (por defecto `/home/deploy/backups`) y `DEPLOY_LOG` (por defecto `/home/deploy/deploys.log`) (A21, A30, A51) y la función de registro: fecha UTC, usuario del SSH, acción, versión y resultado; solo anexado. | Bajo. |
| `docker/prod/deploy.sh` (nuevo) | `deploy.sh <tag>`:<br>1. Exige árbol limpio y **`HEAD` igual al commit del tag** (A20).<br>2. Construye `dentissa-app` y `dentissa-web` con la etiqueta.<br>3. Comprueba que las imágenes no contienen `.env`, `.git` ni `public/hot`.<br>4. `backup.sh pre-<tag>` y `up -d` con `APP_VERSION`.<br>5. `migrate --force` y `verify.sh --local`.<br>6. Guarda `.deploy/current` y `.deploy/previous` y conserva solo las dos últimas etiquetas.<br>7. Registra la operación; ante cualquier fallo anota `result=failed` y se detiene.<br>Opción `--first` para el paso desde la pila manual (también para volver a producción tras `--to-manual`, A55): backup desde la pila manual, `docker compose stop` en `MANUAL_DIR` (sin borrar contenedores ni volúmenes) y copia de `MANUAL_DIR/storage/app/public` al volumen `storage-public` si está vacío (A4, A51). | Medio. |
| `docker/prod/rollback.sh` (nuevo) | `rollback.sh [tag] [--restore <dump>]`: por defecto las imágenes de `.deploy/previous`; `up -d`, `restore.sh` opcional, `verify.sh --local` y registro. `rollback.sh --to-manual`: detiene la pila de producción y hace `docker compose start` en `MANUAL_DIR`, que arranca la pila manual con su código, su `.env` y su nginx intactos (A52). | Medio. |
| `docker/prod/backup.sh` (nuevo) | `pg_dump -Fc` desde el contenedor `db` de la pila en servicio (producción por `compose.sh`; la manual con `--from-manual`, que usa `--first`) a `$BACKUP_DIR/<etiqueta>-<fecha>.dump` (carpeta 700, archivo 600); borra los diarios de más de 7 días y registra. Cron del usuario `deploy` a las 03:00 con `backup.sh daily`. | Medio: si el cron no corre, no hay backups (lo comprueba `verify.sh`). |
| `docker/prod/restore.sh` (nuevo) | `pg_restore -c` del dump indicado, con confirmación interactiva y registro. | Alto por naturaleza: solo a mano. |
| `docker/prod/verify.sh` (nuevo) | `--local` (en el droplet):<br>• `/up`, `APP_DEBUG` apagado, `queue` en marcha y `id -u` ≠ 0 en `app` y `queue`;<br>• la imagen no contiene `node`, `composer`, `vendor/pestphp`, `.env`, `.git` ni `public/hot`, sí `public/build/manifest.json`, y tiene `zend.exception_ignore_args=On`;<br>• backup de menos de 25 h con permisos 700/600 y rotación;<br>• última línea de `deploys.log` con formato válido y disco < 80 %.<br>`--remote <dominio> --origin <IP>`:<br>• contra el dominio: 301 a https con HSTS, cabeceras, sin `X-Powered-By` ni versión de nginx, CORS con origen ajeno, 404 en `/.env`, `/.git/` y `/backups/`, 200 en `/storage/login.jpg` y en un asset del manifest (A35);<br>• contra la IP directa: 5432, 6379, 3000, 3100, 5173, 9000 y 12345 cerrados; y 11 peticiones a una ruta con `throttle:api`, con `CF-Connecting-IP` y `X-Forwarded-For` falsos y distintos en cada una, terminan en 429 (la IP falsificada no se acepta, A38).<br>Sale con código ≠ 0 si algo falla. | Bajo. |
| `.env.example` | `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`, `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `DB_CONNECTION=pgsql` (`DB_HOST=db`, `DB_PORT=5432`, nombres vacíos), `REDIS_CLIENT=predis`, `REDIS_HOST=redis`, `REDIS_PASSWORD=` vacío (no `null`) (A15). Nombres nuevos: `TWILIO_*`, `BREVO_EMAIL_SENDER_API_KEY`, `BREVO_RESET_PASSWORD_TEMPLATE_ID`, `SANCTUM_*`, `APP_VERSION`, `COMPOSE_FILE` (comentado) y `CSP_REPORT_ONLY=false`. | Medio: CI copia `.env.example` y sobrescribe lo necesario; se corre la suite completa. |
| `config/services.php` | `twilio` (`sid`, `token`, `from`, `appointment_template_sid`) y `brevo.api_key`. | Bajo. |
| `config/cors.php` (nuevo) | `paths` `api/*` y `sanctum/csrf-cookie`, `allowed_origins` `[env('APP_URL')]`, métodos y cabeceras explícitos, `supports_credentials` `false`. | Bajo: el frontend es del mismo origen. |
| `config/database.php` | Redis (predis) con `timeout` 2 s, `read_write_timeout` 2 s y sin reintentos en la conexión por defecto (A7). PostgreSQL no cambia: Laravel 12 no pasa `connect_timeout` al DSN (A34), así que el límite de tiempo lo pone el listener de salud. | Bajo. |
| `config/security.php` (nuevo) | `csp.report_only` (`env('CSP_REPORT_ONLY', false)`); orígenes extra de la CSP (host de `R2_URL`, `fonts.bunny.net`, `www.google.com`); `trusted_proxies`, con los rangos IPv4 e IPv6 publicados por Cloudflare. | Bajo. |
| `app/Core/Middlewares/SecurityHeaders.php` (nuevo) | Middleware global:<br>• `Vite::useCspNonce()`.<br>• `Content-Security-Policy` (o `-Report-Only`): `default-src 'self'`, `script-src 'self' 'nonce-…'`, `style-src 'self' 'unsafe-inline' fonts.bunny.net`, `font-src 'self' fonts.bunny.net`, `img-src 'self' data: <R2>`, `connect-src 'self'`, `frame-src www.google.com`, `frame-ancestors 'none'`, `base-uri 'self'`, `form-action 'self'`.<br>• Si `Vite::isRunningHot()`, añade el origen del servidor de vite a `script-src`, `style-src` y `connect-src`, incluido `ws://` (A9).<br>• `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`. `X-Powered-By` se retira con `expose_php=Off` en la imagen, no en el middleware (A44). | Medio: puede bloquear recursos no detectados; interruptor `CSP_REPORT_ONLY`. |
| `app/Core/Middlewares/TrustCloudflareProxies.php` (nuevo) | Extiende el `TrustProxies` de Laravel y lee `config('security.trusted_proxies')` **en cada petición**, no al construir el kernel. `withMiddleware` corre antes de cargar la configuración, así que `config()` fallaría ahí (A32). Cabeceras `X-Forwarded-For` y `X-Forwarded-Proto`. | Bajo. |
| `bootstrap/app.php` | Registra `SecurityHeaders` como middleware global (`append`) y sustituye el `TrustProxies` de Laravel por `TrustCloudflareProxies` (`replace`), sin llamar a `config()` (CA16, CA17). | Bajo; sin conflicto con la 014 (solo añade). |
| `app/Core/Health/CheckDependenciesOnHealth.php` (nuevo) | Listener de `DiagnosingHealth`. Primero comprueba con un tiempo límite de 2 s que el host y el puerto de PostgreSQL aceptan conexión (A34). Después hace una consulta mínima a PostgreSQL y un `ping` a Redis. Ante un fallo:<br>• registra `Log::error('health.dependency_failed', ['dependency' => …])`;<br>• lanza una excepción propia **sin previa**, con el mensaje no vacío `"<dependencia> unavailable"`, sin host ni texto de PDO o Redis. Así el `report()` de Laravel no registra datos del servidor (A8). | Bajo. |
| `app/Providers/AppServiceProvider.php` | Registra el listener para `DiagnosingHealth`. Construye `BrevoApi` con `apiKey: config('services.brevo.api_key')`, junto a `TemplateId`. | Bajo. |
| Flujo de WhatsApp: `app/Modules/whatsApp/Infrastructure/Listeners/CreatedAppointmentListener.php`, `app/Modules/whatsApp/Aplication/UseCases/SendAppointmentConfirmationUseCase.php`, `app/Modules/whatsApp/Aplication/Jobs/ConfirmationAppointmentMessage.php`, `app/Modules/Appointments/Aplication/UseCases/RetriveDataForScheduledAppointmenEventUseCase.php` | Sus `Log::` dejan de registrar `customerPhone`, `customerName`, `phone`, `to`, variables, `getMessage()` y `getTraceAsString()`. Se conservan el nombre del evento, el id de la cita, `errorCode` y la clase de la excepción (CA18, A33). | Bajo. |
| Flujo de correo: `app/Modules/Email/Infrastructure/Listeners/SendPasswordResetListener.php` y `app/Modules/Auth/Infrastructure/Http/Controllers/SendResetPasswordEmailController.php` | Sin email, nombre, mensaje ni trazas en sus logs (CA18, A53). La respuesta del controlador no cambia. | Bajo. |
| `app/Modules/Email/Aplication/UseCases/SendResetPasswordEmailUseCase.php` | El enlace se construye con `rtrim(config('app.url'), '/') . '/reset-password?token='`, en lugar de la constante con `localhost` (CA19, A40). | Bajo. |
| `app/Modules/whatsApp/Infrastructure/ExternalApi/TwilioConection.php` | Constructor con `?Client $client = null`: sin cliente, lo crea con `config('services.twilio.sid')` y `token`. `sendTemplate` lee `from` y `appointment_template_sid` de `config()`. Los logs conservan solo `templateName`, `variableCount`, `messageId`, `status` y `errorCode`, sin `to`, variables, `json`, `getMessage()` ni trazas. Ante un error de Twilio, relanza una excepción propia con solo el código, sin el mensaje del proveedor, que puede incluir el número (CA18). | Bajo. |
| `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php` | Recibe `public string $apiKey` y un `?Brevo $client = null` opcional (para probar sin red) en el constructor; sin `env()`. Sus logs y la excepción que relanza no llevan `body`, `getMessage()` del SDK ni el email: solo el código de estado y el id de plantilla (CA18, A41). | Bajo. |
| Vistas con `<script>` inline (7): `components/landing/nav`, `components/ui/input`, `components/ui/table`, `pages/auth/login`, `pages/auth/logout`, `pages/auth/register`, `pages/usuarios/index` | Atributo `nonce` con el valor de `Vite::cspNonce()` en cada `<script>`. | Bajo. |
| `package.json`, `package-lock.json` | `npm audit fix` (sin `--force`). | Medio: si cambia el build de vite, lo detecta `npm run build`. |
| `.github/workflows/tests.yml` | `node-version: '22'`, la misma versión mayor de la etapa `assets` (A50). No es una ruta protegida. | Bajo. |
| `tests/Modules/Core/` (nuevo), `tests/Modules/whatsApp/` (nuevo), `tests/Modules/Email/` (nuevo) | `CoreIntegrationTestCase`; los tests declaran su base con `uses()` en cada archivo, sin tocar `tests/Pest.php` (A11). El de WhatsApp usa `AppointmentsIntegrationTestCase`. El de correo usa una base nueva `EmailIntegrationTestCase`, porque `AuthIntegrationTestCase` finge `SendEmailForChangePasswordEvent` (A53). | — |
| Docs | `docs/deployment.md` (clon de producción, volúmenes externos y rollback del primer paso **antes** del ensayo; después, procedimiento real, rollback medido, variables, certbot y Cloudflare), `docs/security.md` (EX1 cerrada), `docs/observability.md`, `docs/architecture.md` (T090). El roadmap lo actualiza `/release` (A48). | — |

## Contratos y datos
- **`GET /up`:**
  - `200` con la vista de salud de Laravel si la aplicación arranca y PostgreSQL y Redis responden.
  - `500` con la misma vista y el mensaje `"<dependencia> unavailable"` si alguna falla, en menos
    de 5 s. Sin host ni texto de la excepción original.
  - La vista carga estilos de un CDN que la CSP bloquea: solo cambia su aspecto y no forma parte
    de los criterios (A26).
- **CORS:**
  - Un origen distinto de `APP_URL` no recibe `Access-Control-Allow-Origin`; un preflight desde
    `APP_URL` sí.
  - Nunca `Access-Control-Allow-Credentials: true`.
- **Cabeceras:**
  - En toda respuesta de la aplicación: CSP, `X-Frame-Options`, `X-Content-Type-Options` y
    `Referrer-Policy`; sin `X-Powered-By`.
  - En nginx de producción: HSTS (también en el 301), `nosniff` en los estáticos y sin versión en
    `Server`.
  - Detrás de Cloudflare, la cabecera `Server` la reescribe el proxy.
- **IP del cliente:**
  - `request()->ip()` es la IP de `X-Forwarded-For` solo si `REMOTE_ADDR` pertenece a los rangos
    de Cloudflare; en otro caso, `REMOTE_ADDR`.
  - El limitador `api` usa esa IP.
  - En producción, nginx ya entrega a PHP la IP del visitante, así que la capa de Laravel es una
    segunda defensa. La prueba de extremo a extremo contra el origen es la de `verify.sh` (A38).
  - La IP solo aparece en los logs de nginx; la aplicación no la registra (A39).
- **Correo de restablecimiento:** el enlace es `<APP_URL>/reset-password?token=<token>` (CA19).
- **Datos:** sin migraciones. El volumen `storage-public` nuevo recibe en el primer despliegue el
  contenido actual de `storage/app/public` del droplet.
- **Entornos:**
  - En el `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`,
    `LOG_LEVEL=info`, `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`,
    `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `APP_VERSION` y
    `COMPOSE_FILE=docker-compose.prod.yml`.
  - Solo nombres en `deployment.md`; los valores los pone el usuario.
- **Frontend:** sin pantallas nuevas ni cambios visuales. La skill `design` no aplica.

## Estrategia de pruebas
- **Pest, Integración** (`tests/Modules/Core/Integration/`, `uses(CoreIntegrationTestCase::class)` en cada archivo):
  - `SecurityHeadersTest`:
    - cabeceras en una vista web, en `/api/v1` y en `/up`;
    - `nonce` igual al de los scripts de `login`;
    - modo report-only;
    - con `Vite::useHotFile()` apuntando a un archivo temporal propio del proceso, que se borra al
      terminar (nunca `public/hot`, A45), el origen de vite y `ws://` en la CSP.
  - `CorsTest`: origen ajeno sin `Access-Control-Allow-Origin`; origen propio con él; nunca credenciales.
  - `TrustedProxiesTest` (además, T046 hace una petición HTTP real a `/up` en local, porque Pest
    no reproduce el orden de arranque del kernel, A32):
    - con `REMOTE_ADDR` de Cloudflare y `X-Forwarded-For`, `ip()` es la del visitante;
    - dos visitantes distintos detrás de la misma IP de Cloudflare no comparten el límite `api` (CA16);
    - con `REMOTE_ADDR` ajeno a Cloudflare y un `X-Forwarded-For` falso, `ip()` es `REMOTE_ADDR`
      y el límite cuenta por ella (CA17).
  - `HealthCheckTest`:
    - con `app.debug=false`: 200 con dependencias;
    - 500 en menos de 5 s si PostgreSQL no responde y si Redis no responde (host no enrutable y
      puerto cerrado), sin depender de `connect_timeout` (A34);
    - con `Log::spy()`: ni el cuerpo ni los logs contienen host ni el texto de la excepción original.
  - `WebUnexpectedErrorTest`: con `app.debug=false`, una ruta de test que lanza responde 500 sin
    el mensaje, sin rutas de archivos ni trazas.
- **Pest, Unit** (`tests/Modules/Core/Unit/`):
  - `EnvExampleDefaultsTest`: valores de "Cambios por módulo" y nombres nuevos.
  - `NoEnvOutsideConfigTest`: `arch()->expect('App')->not->toUse('env')`.
  - `ProvidersReadConfigTest`:
    - Twilio: `new TwilioConection()` sin cliente y con valores solo en `config()`; el cliente
      creado tiene esos `getUsername()` y `getPassword()`.
    - Brevo: con el contenedor de la app, `SendResetPasswordEmailUseCase` resuelve un `BrevoApi`
      cuyo `apiKey` es el de `config()`.
    - Sin red. Falla hoy (A6).
  - `ComposeFilesTest`:
    - ninguna contraseña, `POSTGRES_*` ni `requirepass` literal en `docker-compose*.yml`;
    - puertos publicados de cada archivo según el plan;
    - `app` y `queue` locales comparten los volúmenes de `vendor/` y `node_modules/`;
    - el `Dockerfile` fija cada imagen base con etiqueta `mayor.menor` como mínimo, nunca `:latest`
      ni solo la mayor, y la versión mayor de Node coincide con la de `tests.yml`;
    - `.dockerignore` excluye `.env`, `.git` y `public/hot`.
  - `CloudflareRangesTest`: los rangos de `config/security.php` y los `set_real_ip_from` de
    `docker/nginx/prod.conf` son los mismos.
- **Pest, flujos completos con `Log::spy()`** (CA18):
  - `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest` (base `AppointmentsIntegrationTestCase`):
    - se crea una cita para un paciente con teléfono;
    - el evento recorre listener, caso de uso, job y `TwilioConection` real, con un `Client` simulado
      (una vez con éxito y otra lanzando un error de Twilio cuyo mensaje incluye el número);
    - ningún log contiene el teléfono, el nombre, las variables, el mensaje del proveedor ni trazas;
    - la excepción que llega al worker tampoco los contiene;
    - un caso ejecuta el listener fuera de una petición HTTP, como el worker, con
      `zend.exception_ignore_args=1`, y pasa la excepción por `report()`: el log resultante no
      contiene datos personales (A54).
    - Incluye el caso de `RetriveDataForScheduledAppointmenEventUseCase`.
  - `tests/Modules/Email/Integration/PasswordResetEmailTest` (base nueva `EmailIntegrationTestCase`, sin eventos fingidos):
    - la solicitud llega por HTTP al controlador y recorre listener, caso de uso y `BrevoApi` real.
      El cliente Brevo simulado se inyecta sustituyendo, solo en el test, el binding contextual de
      `BrevoApi` (`when(SendResetPasswordEmailUseCase::class)->needs(BrevoApi::class)`), sin tocar el
      provider de producción (A57). Un caso con éxito y otro con error del SDK cuyo cuerpo incluye el
      email;
    - un caso ejecuta el listener como el worker y pasa la excepción por `report()` (A54);
    - ningún log (tampoco el del controlador) ni la excepción relanzada contienen el email, el
      nombre, el cuerpo ni trazas;
    - con `app.url=https://dentissapp.com`, el enlace enviado es `https://dentissapp.com/reset-password?token=…` (CA19).
- **Verificación de servidor** (`docker/prod/verify.sh`): línea base antes de los cambios (falla
  hoy) y en cada despliegue. Cubre `X-Powered-By` (A44), el asset del manifest (A35) y la IP
  falsificada contra el origen (A38).
- **Imagen** (T032): `docker run` de `prod` con volúmenes vacíos, que termina `optimize` y sirve
  `/up` 200 sin root (A36); el manifest de `web` es igual al de `prod` (A35).
- **Ensayo local del primer paso** (A51, A52), antes de tocar el droplet:
  1. Pila manual levantada desde `4b3aecf` en un worktree aparte, con certificados autofirmados
     montados por un override temporal no versionado.
  2. Clon de producción aparte y `deploy.sh --first`. `docker ps -a` muestra los contenedores de la
     pila manual detenidos, no borrados, y producción usa el mismo volumen de datos.
  3. `rollback.sh --to-manual`: la pila manual vuelve a servir por https su código y su nginx.
  4. `deploy.sh --first` de nuevo.
- **Ensayo** (CA13) en el droplet, con aprobación explícita: pasos y tiempos en `deployment.md`,
  incluido el corte de un despliegue sin migraciones (A13).
- **Suite completa:** `./vendor/bin/pest --parallel` en Docker local y en CI, `npm run build`,
  `npm audit --audit-level=high`, `composer audit` y `gitleaks detect`.
- Las credenciales `admin`/`example` de `phpunit.xml` y `tests.yml` son de la base de datos
  efímera de tests y quedan fuera de CA10 (D10).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: PostgreSQL, Redis, Loki, Grafana, php-fpm o vite accesibles desde Internet por la IP directa (Docker se salta `ufw`) | A02:2025 Security Misconfiguration | `docker-compose.prod.yml` | Sin `ports` salvo 80/443; Grafana solo en `127.0.0.1`; túnel SSH | `ComposeFilesTest`; `verify.sh --remote --origin` |
| TM2 | Information disclosure: páginas de depuración con trazas y configuración | A02:2025 | `.env`, manejo de errores | `APP_DEBUG=false` en la plantilla y en producción | `WebUnexpectedErrorTest`; `EnvExampleDefaultsTest`; `verify.sh --local` |
| TM3 | Tampering / Information disclosure: intermediario que fuerza `http://` | A04:2025 Cryptographic Failures | nginx, Cloudflare | 301 a https y HSTS de un año con `always`; Cloudflare Full (strict) | `verify.sh --remote` |
| TM4 | Tampering: XSS con scripts inyectados o de terceros; clickjacking | A05:2025 Injection | Respuestas web | CSP con nonce, `frame-ancestors 'none'`, `X-Frame-Options`, `nosniff` | `SecurityHeadersTest` |
| TM5 | Information disclosure: un sitio de otro origen lee respuestas de la API | A01:2025 Broken Access Control | CORS | `allowed_origins` = `APP_URL`, sin credenciales | `CorsTest`; `verify.sh --remote` |
| TM6 | Information disclosure: descarga de `.env`, `.git` o backups por la web | A02:2025 | nginx, imágenes | La imagen `web` solo contiene `public/`; dotfiles → 404; backups fuera de imágenes y volúmenes servidos | `verify.sh --remote` |
| TM7 | Information disclosure: credenciales en archivos versionados | A02:2025 / A07:2025 | Compose, `app/` | `${VAR:?}`, `config()` en lugar de `env()`, gitleaks | `ComposeFilesTest`; `NoEnvOutsideConfigTest` |
| TM8 | Repudiation: despliegue, rollback o restauración sin rastro | A09:2025 Logging & Alerting Failures | Scripts de `docker/prod/` | Línea en `deploys.log`, solo anexado | `verify.sh --local`; ensayo |
| TM9 | Denial of service / pérdida de datos | A10:2025 Mishandling of Exceptional Conditions | PostgreSQL | Backup diario (7 días) y antes de cada despliegue; rollback ensayado | `verify.sh --local`; ensayo |
| TM10 | Tampering en la cadena de suministro: herramientas de build o imágenes base vulnerables | A03:2025 Software Supply Chain Failures | npm, imágenes base | `npm audit fix`; imágenes base fijadas y en soporte | `npm audit`; `ComposeFilesTest` (sin `:latest`) |
| TM11 | Information disclosure: otro usuario del droplet lee los backups | A01:2025 | Host | Carpeta 700 y archivos 600 | `verify.sh --local` |
| TM12 | Elevation of privilege: proceso de la app o de la cola con root | A02:2025 | Imagen `prod` | `USER www-data`, sin Node ni Composer | `verify.sh --local`; T032 |
| TM13 | Information disclosure: `.env` real, `.git` o `public/hot` dentro de una imagen | A02:2025 | Build, `.dockerignore` | `.dockerignore` y `env_file`; comprobación tras el build | `ComposeFilesTest` (`.dockerignore`); `deploy.sh` y `verify.sh --local` |
| TM14 | Spoofing: un cliente falsifica `X-Forwarded-For` o `CF-Connecting-IP` para esquivar el límite o ensuciar los logs | A07:2025 Identification & Authentication Failures | `TrustCloudflareProxies`, nginx `real_ip` | Cabeceras de IP aceptadas solo desde los rangos de Cloudflare | `TrustedProxiesTest`; `CloudflareRangesTest`; `verify.sh --remote --origin` (429 con cabeceras falsas) |
| TM15 | Information disclosure: teléfono, nombre, email o variables del paciente en los logs o en las excepciones de los flujos de WhatsApp y de correo | A09:2025 | 7 archivos de OB2 | Logs y excepciones sin datos personales ni trazas | `WhatsAppFlowLogsTest`; `PasswordResetEmailTest` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: local en `localhost:8000`, con recarga en caliente | `docker-compose.yml`, `default.conf`, `Dockerfile` (`dev`), `SecurityHeaders` (modo hot) | `ComposeFilesTest`; `SecurityHeadersTest` (modo hot); comprobación en local (T046) |
| CA2: build de producción con assets y sin dependencias de desarrollo | `Dockerfile`, `.dockerignore` | Comprobación del build (T032); `verify.sh --local` |
| CA3: WhatsApp y correo en < 1 min con configuración cacheada; la cola se reinicia sola | `queue`, `entrypoint.sh`, `compose.sh`, `config/services.php`, `TwilioConection`, `BrevoApi`, `AppServiceProvider` | `ProvidersReadConfigTest`; `NoEnvOutsideConfigTest`; ensayo (cita y reset de contraseña, `kill 1`) |
| CA4: http → 301 a https y HSTS | `prod.conf` | `verify.sh --remote` |
| CA5: protecciones y CSP; pantallas principales sin bloqueos | `SecurityHeaders`, `config/security.php`, 7 vistas | `SecurityHeadersTest`; `verify.sh --remote`; consola en T046 y T052 |
| CA6 (abuso): otro origen bloqueado | `config/cors.php` | `CorsTest`; `verify.sh --remote` |
| CA7 (abuso): error inesperado → respuesta genérica | `.env.example`, `.env` de producción | `WebUnexpectedErrorTest`; `GlobalErrorFallbackTest` (existente); `verify.sh --local` |
| CA8: plantilla segura y stack real | `.env.example` | `EnvExampleDefaultsTest` |
| CA9 (abuso): solo 80, 443 y SSH en la IP directa; Grafana por túnel | `docker-compose.prod.yml` | `ComposeFilesTest`; `verify.sh --remote --origin` |
| CA10: sin credenciales en archivos versionados | Compose, `.dockerignore` | `ComposeFilesTest`; `gitleaks detect` |
| CA11: `/up` comprueba BD y caché; fallo en < 5 s | `CheckDependenciesOnHealth`, `config/database.php` | `HealthCheckTest`; ensayo (`stop redis` y `stop db`) |
| CA12: backups diarios y antes de desplegar; no descargables | `backup.sh`, cron, `deploy.sh`, `prod.conf` | `verify.sh --local` y `--remote` |
| CA13: rollback ensayado con restauración; `deployment.md` con pasos y tiempo | `rollback.sh`, `restore.sh`, `deployment.md`, clon aparte | Ensayo local del primer paso (T058); ensayo en el droplet (T051, T054) |
| CA14: auditoría de dependencias limpia; EX1 cerrada | `package.json`, `package-lock.json`, `security.md` | `npm audit`, `composer audit` |
| CA15: registro de operaciones | `lib.sh` y scripts | `verify.sh --local`; ensayo |
| CA16: IP real detrás de Cloudflare en el límite y los logs | `TrustCloudflareProxies`, `bootstrap/app.php`, `config/security.php`, `prod.conf` (`real_ip`) | `TrustedProxiesTest`; `CloudflareRangesTest`; petición HTTP real en T046; ensayo (IP propia en los logs de nginx) |
| CA17 (abuso): cabecera de IP falsificada sin pasar por el proxy → se ignora | Igual que CA16 | `TrustedProxiesTest`; `verify.sh --remote --origin` (429) |
| CA18: logs de los flujos de WhatsApp y de correo sin datos personales ni trazas | 7 archivos de OB2, `SendResetPasswordEmailController`, `php.ini` (`exception_ignore_args`) | `WhatsAppFlowLogsTest`; `PasswordResetEmailTest` (incluidos los casos como el worker); `verify.sh --local` |
| CA19: enlace de restablecimiento con el dominio del sitio | `SendResetPasswordEmailUseCase` | `PasswordResetEmailTest` |
| TM1 | `docker-compose.prod.yml` | `ComposeFilesTest`; `verify.sh --remote --origin` |
| TM2 | `.env.example`, `.env` | `WebUnexpectedErrorTest`; `EnvExampleDefaultsTest` |
| TM3 | `prod.conf` | `verify.sh --remote` |
| TM4 | `SecurityHeaders` | `SecurityHeadersTest` |
| TM5 | `config/cors.php` | `CorsTest` |
| TM6 | Imagen `web`, `prod.conf` | `verify.sh --remote` |
| TM7 | Compose, `app/` | `ComposeFilesTest`; `NoEnvOutsideConfigTest` |
| TM8 | Scripts | `verify.sh --local`; ensayo |
| TM9 | `backup.sh`, `rollback.sh` | `verify.sh --local`; ensayo |
| TM10 | `package-lock.json`, `Dockerfile` | `npm audit`; `ComposeFilesTest` |
| TM11 | `backup.sh` | `verify.sh --local` |
| TM12 | `Dockerfile` (`prod`) | `verify.sh --local` |
| TM13 | `.dockerignore`, `deploy.sh` | `ComposeFilesTest`; `verify.sh --local` |
| TM14 | `TrustCloudflareProxies`, `prod.conf` | `TrustedProxiesTest`; `CloudflareRangesTest`; `verify.sh --remote --origin` |
| TM15 | 7 archivos de OB2 | `WhatsAppFlowLogsTest`; `PasswordResetEmailTest` |

## Observabilidad
- **Logs nuevos o cambiados:**
  - `health.dependency_failed` → `error` → `{dependency}`.
  - Los logs de los 7 archivos de OB2 pierden teléfono, nombre, email, variables, cuerpos y
    mensajes del proveedor y trazas. Conservan el nombre del evento, el id de la cita,
    `templateName`, `variableCount`, `messageId`, `status`, `errorCode` y la clase de la excepción.
  - En producción, `LOG_CHANNEL=stderr` hace llegar a Loki todos los logs de la aplicación.
  - nginx registra la IP real del visitante (CA16); la aplicación no registra IPs.
  - Sin `request_id` (objetivo 5, OB4.a no lo incluye).
- **Eventos de auditoría:** la aplicación no emite eventos nuevos. CA15 se cumple con
  `deploys.log` en el host.
- **Métricas y alertas:** ninguna nueva (spec 016).
- **Cómo se verifica tras el deploy:**
  - `verify.sh --local` y `verify.sh --remote dentissapp.com --origin <IP>`;
  - en Grafana, por túnel SSH, los logs de `dentissa-app` y `dentissa-queue` de la petición y el
    trabajo de prueba (sin teléfono ni nombre), y la IP propia del usuario en los logs de nginx.

## Rollout
- **Feature flag:** no aplica a la infraestructura. Interruptor de emergencia de la CSP:
  `CSP_REPORT_ONLY=true` en `.env` y `docker/prod/compose.sh up -d app` (recrea el contenedor con
  la versión en servicio) (A10, A37).
- **Preparación en local:** el `.env` local define `DB_USERNAME`, `DB_PASSWORD` y `DB_DATABASE`
  con los valores del volumen `db-data` local.
- **Orden en el droplet** (ensayo y release, con aprobación explícita):
  1. **Comprobar** (no configurar):
     - `ufw` con 22, 80 y 443;
     - Cloudflare en Full (strict);
     - RAM libre para el build, con swap de 2 GB si hace falta.
  2. Clonar el repositorio en `/home/deploy/dentissa`, con `git checkout <tag>`. El usuario copia
     ahí el `.env` de la pila manual y ajusta `COMPOSE_FILE=docker-compose.prod.yml`,
     `DATA_VOLUME_PREFIX` (el proyecto de la pila manual) y las variables de "Contratos y datos".
     `/home/deploy/DentisSystem` y su `.env` no se tocan (A52).
  3. Certbot a webroot:
     - `mkdir -p /var/www/certbot`;
     - `certbot reconfigure` con `webroot_path` y un `deploy-hook` que llama a
       `docker/prod/compose.sh exec nginx nginx -s reload` con `DENTISSA_DIR` explícito (A37);
     - `prod.conf` sirve `/.well-known/acme-challenge/` también en el 443, porque con "Always Use
       HTTPS" de Cloudflare el desafío llega redirigido (A46);
     - `certbot renew --dry-run` tras el paso 4.
  4. Antes de tocar nada, `deployment.md` ya documenta `rollback.sh --to-manual` (A42).
  5. `deploy.sh --first <tag>` desde el clon: backup de la pila manual, `stop` de la pila manual
     (sin borrar), copia de su `storage/app/public` al volumen, build, `up`, `migrate` y verify.
  6. Cron de `backup.sh daily` (usa `compose.sh`).
  7. `verify.sh --remote dentissapp.com --origin <IP>` desde el equipo del usuario.
- **Compatibilidad:**
  - Sin cambios de esquema.
  - `SESSION_ENCRYPT` invalida las sesiones abiertas; no hay usuarios.
  - Corte estimado del primer paso: 1–3 min. El de un despliegue normal sin migraciones se mide en
    el ensayo; objetivo < 1 min.
- **Rollback:**
  - `rollback.sh`: imágenes anteriores, `up -d` y verify.
  - Con restauración: `rollback.sh --restore <dump>`.
  - Del primer despliegue: `rollback.sh --to-manual` detiene la pila de producción y hace
    `docker compose start` en `/home/deploy/DentisSystem`, con su código, su `.env` y su nginx
    intactos. Se ensaya en local (T058) y en el droplet (T051). La pila manual y su directorio se
    retiran solo después del release, en una tarea de limpieza posterior.
  - Objetivo: < 15 min con restauración (medido en CA13).
- **Métricas a vigilar tras el deploy (15 min):**
  - `/up` cada minuto;
  - 5xx en nginx y en la app (Loki) y trabajos fallidos de `queue`;
  - violaciones de CSP en las cinco pantallas;
  - disco del droplet;
  - `certbot renew --dry-run`.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 Imagen inmutable** multi-etapa construida en el droplet y etiquetada por versión, con
  `.dockerignore` (decisión del usuario, 2026-09-29) →
  [ADR 0004](../../adr/0004-imagen-inmutable-y-compose-de-produccion.md). Cubre RD2.a.
- **D2 Compose de producción completo e independiente**, seleccionado con `COMPOSE_FILE`
  (decisión del usuario, 2026-09-29) → ADR 0004.
- **D3 CSP aplicada con nonce** en los 7 scripts inline; `'unsafe-inline'` solo en `style-src`
  (decisión del usuario, 2026-09-29). Cubre RS6.a junto con HSTS en nginx.
- **D4 Certbot pasa de standalone a webroot** con recarga de nginx (confirmado por el usuario,
  2026-09-29: standalone; recogido en los Supuestos de la spec).
- **D5 Cabeceras en la aplicación, HSTS y ocultación de versiones en nginx.**
- **D6 Backups con `pg_dump -Fc` por cron del host**, en el droplet, 7 días (decisión del usuario,
  2026-09-29). Cubre RD1.b.
- **D7 Registro de operaciones en `deploys.log`** del host.
- **D8 Redis para colas, caché y sesiones** en producción y en la plantilla, como en el runbook
  del usuario. La cola cambia de `database` a `redis`. Cubre RD3.a junto con el servicio `queue`.
- **D9 Tests** de `app/Core` en `tests/Modules/Core/`, del flujo de WhatsApp en
  `tests/Modules/whatsApp/Integration/` (grafía del módulo) y del de correo en
  `tests/Modules/Email/Integration/`, con `uses()` en cada archivo.
- **D10 Credenciales de tests fuera de CA10:** `admin`/`example` de `phpunit.xml` y
  `.github/workflows/tests.yml` son de la base de datos efímera de tests.
- **D11 Local publica PostgreSQL y Redis solo en `127.0.0.1`**, Redis sin contraseña en local.
- **D12 La spec se mantiene junta con 19 criterios y cuatro módulos** (decisión del usuario,
  2026-09-29).
- **D13 IP real con doble capa:** nginx `real_ip` (logs de nginx y `REMOTE_ADDR`) y
  `TrustCloudflareProxies` en Laravel, que lee los rangos por petición (A32). Ambos usan los mismos
  rangos de Cloudflare, comprobados por `CloudflareRangesTest`. En producción actúa nginx, y
  `verify.sh --remote --origin` lo prueba de extremo a extremo (A38).
- **D14 Limpieza de logs en los 7 archivos de OB2 y en `SendResetPasswordEmailController`**
  (decisiones del usuario, 2026-09-29, `/analyze` rondas 2 y 3): cierra RS9.a y OB2.b y atiende
  OB10.b **parcialmente** (el resto de Auth → Pendientes y deuda).
  Las excepciones que relanzan los adaptadores tampoco llevan datos del destinatario.
- **D15 `TwilioConection` y `BrevoApi` aceptan un cliente opcional** para probar sin red. El
  contenedor los sigue construyendo sin él.
- **D16 El origen no se limita a los rangos de Cloudflare** (fuera de alcance en la spec); el
  cortafuegos ya limita a 22, 80 y 443.
- **D17 Envoltorio `compose.sh`** con la versión de `.deploy/current` para todo comando fuera de
  `deploy.sh` y `rollback.sh` (A37).
- **D18 Clon de producción aparte con proyecto propio y volúmenes de datos externos**
  (decisión del usuario, 2026-09-29, A51, A52). La pila manual se detiene sin borrar y su directorio
  no se toca, para un rollback del primer despliegue sin reinstalar nada.
- **D19 Tiempo límite de PostgreSQL en el listener de salud** (comprobación de conexión de 2 s),
  porque Laravel 12 no pasa `connect_timeout` al DSN (A34).

- **D20 `zend.exception_ignore_args=On` en la imagen de producción** (A54): las trazas de los
  errores que reporta el worker no llevan argumentos.

## Impacto en arquitectura
- `docs/architecture.md → Despliegue`: Cloudflare delante; imágenes `dev`/`prod`/`web`;
  compose de producción; backups y scripts; IP real.
- `docs/architecture.md → Backend`: canal `stderr` a Loki, `/up` con dependencias,
  `SecurityHeaders`, `trustProxies`.
- `docs/architecture.md → Deuda técnica y riesgos observados`: retirar las entradas de
  `.env.example` y del worker.
- `docs/deployment.md`: procedimiento real y rollback medido (CA13).

## Riesgos y mitigaciones
- **La CSP bloquea algo no detectado.** Mitigación: `SecurityHeadersTest`, revisión de la consola
  en local y en el ensayo, e interruptor `CSP_REPORT_ONLY`.
- **Rangos de Cloudflare desactualizados:** la IP real deja de verse bien para los nodos nuevos.
  Mitigación: una sola lista comprobada en dos sitios por `CloudflareRangesTest`; revisión de
  `https://www.cloudflare.com/ips/` en `deployment.md` como tarea de mantenimiento.
- **Memoria del droplet insuficiente para el build.** Mitigación: comprobar RAM y usar swap temporal.
- **Docker ignora `ufw`.** Mitigación: producción solo publica 80 y 443, y Grafana en
  `127.0.0.1`. CA9 se comprueba contra la IP directa.
- **Disco lleno** (imágenes, backups, Loki sin retención). Mitigación: dos etiquetas de imagen,
  backups de 7 días y aviso con disco > 80 %. La retención de Loki va en la 016.
- **La plantilla cambia el comportamiento de CI** (`APP_DEBUG`, Redis en sesiones y caché).
  Mitigación: CI sobrescribe los drivers; suite completa antes del merge.
- **Pérdida del contenido de `storage/app/public`** al pasar a volúmenes. Mitigación: copia en
  `deploy.sh --first`, comprobación de `/storage/login.jpg` y el original se queda en el disco.
- **HSTS de un año.** Sin `preload`; aceptado porque el sitio ya es solo https.
- **Primer paso desde la pila manual.** Mitigación: backup previo, clon aparte, pila manual
  detenida y no borrada, y `rollback.sh --to-manual` documentado y ensayado en local antes del
  droplet.
- **Dos pilas sobre el mismo volumen de datos.** Mitigación: los scripts detienen una antes de
  arrancar la otra, y `verify.sh --local` falla si ambas tienen `db` en marcha.
- **Limpiar logs deja menos contexto para depurar envíos fallidos.** Mitigación: se conservan
  el id de la cita, el código de error y la clase de la excepción.
