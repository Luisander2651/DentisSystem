---
spec: 015-primer-despliegue-vps
status: approved   # draft | approved | blocked
created: 2026-09-29
---

# Plan · 015 Primer despliegue en el VPS

## Enfoque técnico
Separar por completo local y producción:
- `docker-compose.yml` vuelve a ser el entorno de desarrollo.
- Un `docker-compose.prod.yml` independiente arranca imágenes **inmutables**, construidas desde el
  tag con un `Dockerfile` multi-etapa ([ADR 0004](../../adr/0004-imagen-inmutable-y-compose-de-produccion.md)).

La aplicación asume lo que se puede probar con Pest: cabeceras de seguridad con CSP con nonce,
CORS restrictivo, `/up` que comprueba PostgreSQL y Redis, errores web genéricos, proveedores
leídos por `config()` y una plantilla `.env.example` segura. El servidor asume lo demás, con
scripts versionados en `docker/prod/`:
- despliegue, rollback, backup y restauración, que anotan cada operación en un registro;
- `verify.sh`, que comprueba desde fuera lo que Pest no alcanza: puertos, TLS/HSTS, backups y rutas
  no servidas.

Los cambios hechos en el droplet (`4b3aecf`) pasan al archivo de producción y el ensayo del
rollback (CA13) cierra la spec.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec 015 `approved` (2026-09-29). |
| P2 Test que falla antes y pasa después | ✅ | Todo comportamiento de la aplicación (cabeceras, CORS, `/up`, errores web, `config()`, plantilla) tiene tests Pest en `tests/Modules/Core/`, que hoy fallan. La configuración de servidor (puertos, TLS, backups, registro) no es código de la aplicación: se verifica con `docker/prod/verify.sh`, cuyas comprobaciones fallan hoy (Grafana publicado, sin HSTS, sin backups) y se ejecutan en el ensayo (CA13). |
| P3 Capas del módulo | ✅ | Twilio y Brevo cambian solo dentro de sus adaptadores de `Infrastructure/`. El middleware y el listener de salud viven en `app/Core/`, como los middlewares existentes. Sin dependencias nuevas entre módulos. |
| P4 Contrato de API primero | ✅ | No cambia ningún endpoint de `/api/v1`. El contrato de `/up` (200 / 500) y el de CORS se fijan en "Contratos y datos" y en tests. |
| P5 Autorización en el servidor | ➖ | No cambia la autorización de ningún endpoint. |
| P6 Validación con FormRequest | ➖ | No cambia la entrada de ningún endpoint. |
| P7 Errores sin detalles internos | ✅ | `APP_DEBUG=false` en la plantilla y en producción. Un test fija que el error web inesperado devuelve una página genérica. La API ya lo cumple (spec 014). |
| P8 Secretos fuera del repositorio | ✅ | Se elimina `env()` de `app/` (Twilio, Brevo) con un test de arquitectura que lo impide. Los archivos de Compose toman credenciales con `${VAR:?}` y un test lo comprueba. Las variables nuevas van por nombre a `.env.example`. |
| P9 Migraciones reversibles | ➖ | Sin migraciones. `deploy.sh` ejecuta `migrate --force` para versiones futuras, siempre después del backup. |
| P10 Dependencias nuevas con ADR | ✅ | No hay paquetes nuevos: `npm audit fix` actualiza dependencias existentes dentro de su rango. Si exigiera un paquete nuevo o un cambio de versión mayor, se detiene y se pregunta. La imagen `node:20-alpine` (solo en la etapa de build) y la estructura de imágenes van en el ADR 0004. |
| P11 Datos sensibles y modelo de amenazas | ✅ | Modelo de amenazas abajo. Los backups contienen toda la base de datos: carpeta 700 y archivos 600, fuera de la imagen y de lo que sirve nginx. El registro de despliegues no contiene datos de pacientes. |
| P12 Producción con aprobación, rollback y sin altas | ✅ | La spec construye y ensaya el rollback (CA13) y cierra EX1 (CA14). Toda operación en el droplet durante `/implement`, incluido el ensayo, se hace con aprobación explícita. |
| P13 Lógica de negocio en el backend | ➖ | Solo se añade `nonce` a scripts existentes; no cambia la lógica del frontend. |
| P14 Trazabilidad | ✅ | Nivel de log por defecto `info` y canal `stderr` hacia Loki. Las operaciones de producción quedan en `deploys.log` (CA15). La correlación con `X-Request-Id` y el registro de auditoría de la aplicación siguen en el objetivo 5: esta spec no emite eventos de auditoría de la aplicación (ver Observabilidad). |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `docker/Dockerfile` | Multi-etapa: `base` (extensiones PHP), `dev` (actual: Node + Composer, `USER www-data`), `assets` (`node:20-alpine`, `npm ci && npm run build`), `vendor` (`composer install --no-dev --optimize-autoloader --no-scripts`), `prod` (base + código + `vendor/` + `public/build`, sin Node, `USER www-data`, `ENTRYPOINT docker/prod/entrypoint.sh`) y `web` (`nginx:alpine` + `public/` + `docker/nginx/prod.conf`). | Medio: un fallo en el build rompe el despliegue (se detecta al construir, antes de parar nada). |
| `docker/prod/entrypoint.sh` (nuevo) | `php artisan optimize` (config, rutas, vistas y eventos en caché) y después `exec "$@"` (php-fpm o `queue:work`). | Bajo. |
| `docker-compose.yml` | Vuelve a local: `app` y `queue` con `target: dev` y los volúmenes anónimos de `vendor/` y `node_modules/`; nginx en `8000:80` con `docker/nginx/default.conf` sin TLS; vite en `127.0.0.1:5173:5173`; PostgreSQL y Redis en `127.0.0.1` (para clientes SQL locales); Grafana en `127.0.0.1:3000:3000`; credenciales `${DB_USERNAME:?}`, etc. | Medio: el `.env` local debe definir `DB_USERNAME`, `DB_PASSWORD` y `DB_DATABASE` iguales a los del volumen existente (ver Rollout). |
| `docker-compose.prod.yml` (nuevo) | Archivo completo: `app` y `queue` (`dentissa-app:${APP_VERSION:?}`, `env_file: .env`, volumen `storage`, `restart: unless-stopped`; `queue` con `queue:work --tries=3 --max-time=3600`); `nginx` (`dentissa-web:${APP_VERSION}`, `80:80` y `443:443`, `/etc/letsencrypt:ro` y `/var/www/certbot:ro`); `db` y `redis` sin puertos, con credenciales `${…:?}` y healthcheck; `loki` y `alloy` sin puertos; `grafana` en `127.0.0.1:3000:3000`. Mismos nombres de volumen (`db-data`, …) y proyecto que hoy, para conservar los datos. | Alto: es el entorno real; se valida con `docker compose -f docker-compose.prod.yml config` en CI local y en el ensayo. |
| `docker/nginx/default.conf` | Vuelve a la versión local (`listen 80`, `server_name localhost`). | Bajo. |
| `docker/nginx/prod.conf` (nuevo) | Lo de `4b3aecf` más: `location /.well-known/acme-challenge/` en el 80 (webroot `/var/www/certbot`) antes del 301; `listen 443 ssl` con `http2`; `add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always`; `server_tokens off`; `location ~ /\. { deny all; }`; `X-Content-Type-Options nosniff` en `/build/`. | Medio: HSTS no se puede deshacer en navegadores durante un año (el dominio ya usa TLS; aceptable). |
| `docker/prod/deploy.sh` (nuevo) | `deploy.sh vX.Y.Z`: comprueba árbol limpio y tag → construye `dentissa-app` y `dentissa-web` con la etiqueta → `backup.sh pre-vX.Y.Z` → `docker compose up -d` con `APP_VERSION=vX.Y.Z` → `migrate --force` → `verify.sh --local` → escribe la línea en `deploys.log` y guarda `APP_VERSION` en `.deploy/current` y la anterior en `.deploy/previous`. Si algo falla, anota `result=failed` y se detiene sin revertir solo. | Medio. |
| `docker/prod/rollback.sh` (nuevo) | `rollback.sh [vX.Y.Z]` (por defecto `.deploy/previous`): `up -d` con esas imágenes y, con `--restore <dump>`, llama a `restore.sh`; `verify.sh --local`; registra en `deploys.log`. | Medio. |
| `docker/prod/backup.sh` (nuevo) | `pg_dump -Fc` desde el contenedor `db` a `/srv/dentissa/backups/<etiqueta>-<fecha>.dump` (carpeta 700, archivo 600), borra con `find -mtime +7` los diarios y registra. Cron del host a diario (03:00) con `backup.sh daily`. | Medio: si el cron no corre, no hay backups (lo comprueba `verify.sh`). |
| `docker/prod/restore.sh` (nuevo) | `pg_restore -c` del dump indicado, con confirmación interactiva y registro. | Alto por naturaleza: solo se ejecuta a mano. |
| `docker/prod/verify.sh` (nuevo) | Dos modos. `--local`: comprobaciones desde el droplet (`/up`, `APP_DEBUG` apagado vía `php artisan about --json`, `queue` en marcha, backup de menos de 25 h, última línea de `deploys.log`). `--remote <dominio>`: desde fuera (redirección 301 y HSTS, cabeceras, CORS con origen ajeno, `/.env`, `/backups/` y `/.git/` → 404, y puertos 5432, 6379, 3000, 3100, 5173, 9000 y 12345 cerrados). Sale con código ≠ 0 si falla algo. | Bajo. |
| `.env.example` | `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`, `SESSION_ENCRYPT=true`, `DB_CONNECTION=pgsql` (`DB_HOST=db`, `DB_PORT=5432`, nombres vacíos), `REDIS_CLIENT=predis`, `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`; nombres nuevos: `TWILIO_*`, `BREVO_EMAIL_SENDER_API_KEY`, `BREVO_RESET_PASSWORD_TEMPLATE_ID`, `SANCTUM_*`, `APP_VERSION`, `COMPOSE_FILE` (comentado) y `CSP_REPORT_ONLY=false`. | Medio: CI copia `.env.example` y sobrescribe lo necesario; se corre la suite completa. |
| `config/services.php` | `twilio` (`sid`, `token`, `from`, `appointment_template_sid`) y `brevo.api_key`. | Bajo. |
| `config/cors.php` (nuevo) | `paths` `api/*` y `sanctum/csrf-cookie`, `allowed_origins` `[env('APP_URL')]`, métodos y cabeceras explícitos, `supports_credentials` `false`. | Bajo: el frontend es del mismo origen y no usa CORS. |
| `config/database.php` | `connect_timeout` de pgsql en 3 s y `timeout`/`read_timeout` de Redis en 2 s (CA11: fallo en menos de 5 s). Verificar al implementar que el conector de Laravel 12 pasa `connect_timeout` al DSN. | Bajo. |
| `config/security.php` (nuevo) | `csp.report_only` (`env('CSP_REPORT_ONLY', false)`) y orígenes extra de la CSP: host de `R2_URL`, `fonts.bunny.net`, `www.google.com` (iframe del mapa). | Bajo. |
| `app/Core/Middlewares/SecurityHeaders.php` (nuevo) | Middleware global: `Vite::useCspNonce()`, `Content-Security-Policy` (o `-Report-Only`) con `default-src 'self'`, `script-src 'self' 'nonce-…'`, `style-src 'self' 'unsafe-inline' fonts.bunny.net`, `font-src 'self' fonts.bunny.net`, `img-src 'self' data: <R2>`, `connect-src 'self'`, `frame-src www.google.com`, `frame-ancestors 'none'`, `base-uri 'self'`, `form-action 'self'`. Si `Vite::isRunningHot()`, añade el origen del servidor de desarrollo. Además `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`. | Medio: puede bloquear recursos no detectados; interruptor `CSP_REPORT_ONLY`. |
| `bootstrap/app.php` | Registra `SecurityHeaders` como middleware global (`append`). | Bajo. |
| `app/Core/Health/CheckDependenciesOnHealth.php` (nuevo) | Listener de `DiagnosingHealth`: consulta mínima a PostgreSQL y `ping` a Redis; ante un fallo lanza una excepción y `/up` responde 500. Se registra en `AppServiceProvider` (el descubrimiento de eventos solo recorre los módulos). | Bajo. |
| `app/Providers/AppServiceProvider.php` | `Event::listen(DiagnosingHealth::class, CheckDependenciesOnHealth::class)`. | Bajo. |
| `app/Modules/whatsApp/Infrastructure/ExternalApi/TwilioConection.php` | `env('TWILIO_*')` → `config('services.twilio.*')`; el log de diagnóstico sigue diciendo solo `set`/`NOT SET`. | Bajo. |
| `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php` | `env('BREVO_EMAIL_SENDER_API_KEY')` → `config('services.brevo.api_key')`. | Bajo. |
| Vistas con `<script>` inline (7): `components/landing/nav`, `components/ui/input`, `components/ui/table`, `pages/auth/login`, `pages/auth/logout`, `pages/auth/register`, `pages/usuarios/index` | atributo `nonce` con el valor de `Vite::cspNonce()` en cada `<script>`. | Bajo. |
| `package.json`, `package-lock.json` | `npm audit fix` (sin `--force`). | Medio: si el build de vite cambia, lo detecta `npm run build` en CI. |
| `tests/Modules/Core/` (nuevo) | `CoreIntegrationTestCase` y los tests de Trazabilidad. | — |
| Docs | `docs/deployment.md` (procedimiento, rollback medido, variables, certbot), `docs/security.md` (EX1 cerrada), `docs/observability.md`, `docs/architecture.md` (T090), `docs/roadmap.md`. | — |

## Contratos y datos
- **`GET /up`**: `200` (vista de salud de Laravel) si la aplicación arranca y PostgreSQL y Redis
  responden; `500` con la página genérica si alguno falla, en menos de 5 s. No expone qué
  dependencia falló. El motivo va a `Log::error('health.dependency_failed', ['dependency' => 'pgsql|redis'])`.
- **CORS**: una petición desde un origen distinto de `APP_URL` no recibe
  `Access-Control-Allow-Origin`. Un preflight `OPTIONS` desde `APP_URL` lo recibe. Nunca
  `Access-Control-Allow-Credentials: true`.
- **Cabeceras** en toda respuesta de la aplicación (web y API): `Content-Security-Policy`,
  `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`. En nginx de producción:
  `Strict-Transport-Security` (también en el 301) y `nosniff` en los estáticos.
- **Datos**: sin migraciones ni cambios de esquema.
- **Entornos**: en el `.env` de producción, `APP_ENV=production`, `APP_DEBUG=false`,
  `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`,
  `QUEUE_CONNECTION=redis`, `APP_VERSION` y `COMPOSE_FILE=docker-compose.prod.yml`. Solo nombres
  en `deployment.md`; los valores los pone el dueño del repositorio.
- **Frontend**: sin pantallas nuevas ni cambios visuales. La skill `design` no aplica, porque no
  hay cambio de interfaz.

## Estrategia de pruebas
- **Pest, Integración** (`tests/Modules/Core/Integration/`, base `CoreIntegrationTestCase`):
  - `SecurityHeadersTest`: cabeceras en una vista web, en `/api/v1` y en `/up`; `nonce` de la CSP
    igual al de los scripts de `login`; modo report-only con `CSP_REPORT_ONLY`.
  - `CorsTest`: origen ajeno sin `Access-Control-Allow-Origin`; origen propio con él; nunca credenciales.
  - `HealthCheckTest`: 200 con dependencias; 500 si PostgreSQL falla (conexión con host inválido
    vía `config()`) y si Redis falla; cuerpo sin nombres de host ni mensajes; tiempo < 5 s.
  - `WebUnexpectedErrorTest`: con `app.debug=false`, una ruta de test que lanza responde 500 sin
    el mensaje, sin rutas de archivos ni trazas.
- **Pest, Unit** (`tests/Modules/Core/Unit/`):
  - `EnvExampleDefaultsTest`: `APP_DEBUG=false`, `LOG_LEVEL=info`, `SESSION_ENCRYPT=true`,
    `DB_CONNECTION=pgsql` y `REDIS_CLIENT=predis`, y que todas las variables que usa `config/` para
    servicios propios existen por nombre.
  - `NoEnvOutsideConfigTest`: `arch()->expect('App')->not->toUse('env')`.
  - `ProvidersReadConfigTest`: con `config(['services.twilio.sid' => …])` y la configuración
    cacheada simulada (sin variables de entorno), `TwilioConection` y `BrevoApi` usan esos valores
    (con `FakesTwilio` y `FakesBrevo`).
  - `ComposeFilesTest`: ningún `*_PASSWORD`, `POSTGRES_*` ni `requirepass` literal en
    `docker-compose*.yml`, solo `${VAR:?}`; `docker-compose.prod.yml` no publica más puertos que
    `80`, `443` y `127.0.0.1:3000`; `docker-compose.yml` publica `8000` y `127.0.0.1:5173`.
- **Verificación de servidor** (`docker/prod/verify.sh`), ejecutada en el ensayo y en cada
  despliegue: CA3, CA4, CA9, CA11 (en vivo), CA12 y CA15. Se ejecuta **antes** de los cambios para
  dejar constancia de que falla (Grafana abierto, sin HSTS, sin backups).
- **Ensayo** (CA13) en el droplet, con aprobación explícita: pasos y tiempos en `deployment.md`.
- **Suite completa** `./vendor/bin/pest --parallel` en Docker local y en CI, `npm run build`,
  `npm audit --audit-level=high` y `composer audit`.
- Las credenciales `admin`/`example` de `phpunit.xml` y `tests.yml` son de la base de datos
  efímera de tests y quedan fuera de CA10 (ver Decisiones).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: acceso directo a PostgreSQL, Redis, Loki, Grafana, php-fpm o vite desde Internet | A02:2025 Security Misconfiguration | `docker-compose.prod.yml` | Sin `ports` salvo 80/443; Grafana solo en `127.0.0.1`; túnel SSH | `ComposeFilesTest`; `verify.sh --remote` (puertos cerrados) |
| TM2 | Information disclosure: páginas de depuración con trazas y configuración | A02:2025 | `.env`, manejo de errores | `APP_DEBUG=false` en la plantilla y en producción | `WebUnexpectedErrorTest`; `EnvExampleDefaultsTest`; `verify.sh --local` (`about`) |
| TM3 | Tampering / Information disclosure: intermediario que fuerza `http://` | A04:2025 Cryptographic Failures | nginx | 301 a https y HSTS de un año con `always` | `verify.sh --remote` (301 y HSTS) |
| TM4 | Tampering: XSS con scripts inyectados o de terceros; clickjacking | A05:2025 Injection | Respuestas web | CSP con nonce, `frame-ancestors 'none'`, `X-Frame-Options: DENY`, `nosniff` | `SecurityHeadersTest`; `verify.sh --remote` |
| TM5 | Information disclosure: un sitio de otro origen lee respuestas de la API | A01:2025 Broken Access Control | CORS | `allowed_origins` = `APP_URL`, sin credenciales | `CorsTest`; `verify.sh --remote` |
| TM6 | Information disclosure: descarga de `.env`, `.git` o backups por la web | A02:2025 | nginx, imágenes | La imagen `web` solo contiene `public/`; `.env` por `env_file`; backups en `/srv/dentissa/backups` fuera de imágenes y volúmenes servidos; `deny` a dotfiles | `verify.sh --remote` (404 en `/.env`, `/.git/`, `/backups/`) |
| TM7 | Information disclosure: credenciales en archivos versionados | A02:2025 / A07:2025 | Compose, `app/` | `${VAR:?}`, `config()` en lugar de `env()`, gitleaks | `ComposeFilesTest`; `NoEnvOutsideConfigTest`; `gitleaks detect` |
| TM8 | Repudiation: despliegue, rollback o restauración sin rastro | A09:2025 Logging & Alerting Failures | Scripts de `docker/prod/` | Línea en `deploys.log` (fecha UTC, usuario del SSH, acción, versión, resultado), solo anexado | `verify.sh --local` (última línea); ensayo CA13 |
| TM9 | Denial of service / pérdida de datos: fallo del disco o una migración destructiva | A10:2025 Mishandling of Exceptional Conditions | PostgreSQL | Backup diario (7 días) y antes de cada despliegue; rollback ensayado | `verify.sh --local` (backup < 25 h); ensayo CA13 |
| TM10 | Tampering en la cadena de suministro: herramientas de build con vulnerabilidades | A03:2025 Software Supply Chain Failures | npm | `npm audit fix`; puerta SCA en `/release` | `npm audit --audit-level=high` sin hallazgos (CA14) |
| TM11 | Information disclosure: otro usuario del droplet lee los backups | A01:2025 | Sistema de archivos del host | Carpeta 700 y archivos 600 del usuario que despliega | `verify.sh --local` (permisos) |
| TM12 | Elevation of privilege: proceso de la app o de la cola comprometido con root | A02:2025 | Imagen `prod` | `USER www-data`, sin Node ni Composer en la imagen de ejecución | `verify.sh --local` (`id -u` en `app` y `queue` ≠ 0) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: local en `localhost:8000` sin certificados, con recarga en caliente | `docker-compose.yml`, `docker/nginx/default.conf`, `Dockerfile` (`dev`) | `ComposeFilesTest` (puertos local); comprobación manual en local documentada en la tarea (`docker compose up`, `curl localhost:8000/up`, edición de una vista con vite) |
| CA2: build de producción con assets y sin dependencias de desarrollo | `Dockerfile` (`assets`, `vendor`, `prod`, `web`) | `verify.sh --local`: en la imagen no existen `node` ni `vendor/pestphp` y sí `public/build/manifest.json`; CI `npm run build` |
| CA3: WhatsApp y correo en < 1 min con configuración cacheada; la cola se reinicia sola | `queue` en `docker-compose.prod.yml`, `entrypoint.sh`, `config/services.php`, `TwilioConection`, `BrevoApi` | `ProvidersReadConfigTest`; `NoEnvOutsideConfigTest`; ensayo: cita de prueba procesada (log de `queue`) y `kill 1` en `queue` → vuelve en < 1 min |
| CA4: http → 301 a https y HSTS | `docker/nginx/prod.conf` | `verify.sh --remote` |
| CA5: protecciones de embebido y tipo, CSP; pantallas principales sin bloqueos | `SecurityHeaders`, `config/security.php`, 7 vistas con `nonce` | `SecurityHeadersTest`; `verify.sh --remote`; ensayo: login, agenda, pacientes, expediente y sitio público sin violaciones de CSP en la consola |
| CA6 (abuso): otro origen bloqueado; mismo dominio funciona | `config/cors.php` | `CorsTest`; `verify.sh --remote` |
| CA7 (abuso): error inesperado → respuesta genérica | `.env.example`, `.env` de producción | `WebUnexpectedErrorTest`; `GlobalErrorFallbackTest` (existente, API); `verify.sh --local` (`APP_DEBUG` apagado) |
| CA8: plantilla con valores seguros y stack real | `.env.example` | `EnvExampleDefaultsTest` |
| CA9 (abuso): solo 80, 443 y SSH; Grafana por túnel | `docker-compose.prod.yml` | `ComposeFilesTest`; `verify.sh --remote` (puertos) |
| CA10: sin credenciales en archivos versionados de los entornos | `docker-compose.yml`, `docker-compose.prod.yml` | `ComposeFilesTest`; `gitleaks detect --no-banner` |
| CA11: `/up` comprueba BD y caché; fallo en < 5 s | `CheckDependenciesOnHealth`, `config/database.php` | `HealthCheckTest`; ensayo: `docker compose stop redis` → `/up` 500 en < 5 s |
| CA12: backup diario (7 días) y antes de cada despliegue; no descargable | `backup.sh`, cron, `deploy.sh`, `prod.conf` | `verify.sh --local` (backup < 25 h, permisos, rotación); `verify.sh --remote` (404 en `/backups/`) |
| CA13: rollback ensayado con restauración; `deployment.md` con pasos y tiempo | `rollback.sh`, `restore.sh`, `docs/deployment.md` | Ensayo en el droplet (tarea propia) con tiempos anotados; validación de que `deployment.md` no tiene `TODO` en despliegue y rollback |
| CA14: auditoría de dependencias sin críticas ni altas; EX1 cerrada | `package.json`, `package-lock.json`, `docs/security.md` | `npm audit --audit-level=high` y `composer audit` sin hallazgos |
| CA15: registro de despliegue, rollback y restauración | Scripts de `docker/prod/` | `verify.sh --local` (formato de la última línea); ensayo CA13 (tres líneas: deploy, rollback, restore) |
| TM1 | `docker-compose.prod.yml` | `ComposeFilesTest`; `verify.sh --remote` |
| TM2 | `.env.example`, `.env` | `WebUnexpectedErrorTest`; `EnvExampleDefaultsTest` |
| TM3 | `prod.conf` | `verify.sh --remote` |
| TM4 | `SecurityHeaders` | `SecurityHeadersTest` |
| TM5 | `config/cors.php` | `CorsTest` |
| TM6 | Imagen `web`, `prod.conf` | `verify.sh --remote` |
| TM7 | Compose, `app/` | `ComposeFilesTest`; `NoEnvOutsideConfigTest` |
| TM8 | `docker/prod/*.sh` | `verify.sh --local`; ensayo |
| TM9 | `backup.sh`, `rollback.sh` | `verify.sh --local`; ensayo |
| TM10 | `package-lock.json` | `npm audit` |
| TM11 | `backup.sh` | `verify.sh --local` |
| TM12 | `Dockerfile` (`prod`) | `verify.sh --local` |

## Observabilidad
- **Logs nuevos:**
  - `health.dependency_failed` → `error` → `{dependency}`, sin host ni mensaje de la excepción.
  - Sin `request_id`: la correlación no existe aún (objetivo 5, OB4.a).
  - En producción, `LOG_CHANNEL=stderr` hace que todos los logs de la aplicación lleguen a Loki
    por Alloy.
- **Eventos de auditoría:** la aplicación no emite eventos nuevos. CA15 se cumple con
  `/srv/dentissa/deploys.log` en el host: una línea por operación, solo anexado, con
  `fecha_utc usuario acción versión resultado`. Lo verifican `verify.sh --local` y el ensayo.
- **Métricas y alertas:** ninguna nueva (spec 016: OB7 y OB8). Hasta entonces, la retención de
  Loki no está configurada. Riesgo de disco, con mitigación abajo.
- **Cómo se verifica tras el deploy:**
  - `verify.sh --local` y `verify.sh --remote dentissapp.com`;
  - en Grafana (por túnel), `{container="laravel-app"}` muestra los logs de la petición de prueba
    y `{container="laravel-queue"}` el trabajo procesado.
  - `/release` no podrá usar un `request_id` hasta el objetivo 5: se usará la marca de tiempo y la
    ruta de la petición de prueba.

## Rollout
- **Feature flag:** no aplica a la infraestructura. Para la CSP hay un interruptor de
  emergencia: `CSP_REPORT_ONLY=true` en `.env` y `php artisan optimize` (o reiniciar `app`).
- **Preparación en local** (antes del merge): el `.env` local define `DB_USERNAME`,
  `DB_PASSWORD` y `DB_DATABASE` con los valores con que se creó el volumen `db-data` local. Si no,
  PostgreSQL no arranca con `${…:?}`.
- **Orden en el droplet** (primer despliegue; ensayo y release con aprobación explícita):
  1. Cortafuegos del proveedor con 22, 80 y 443. Es un supuesto de la spec: se comprueba, no se
     configura aquí.
  2. `git fetch --tags && git checkout vX.Y.Z`; en `.env`, `COMPOSE_FILE=docker-compose.prod.yml`,
     `APP_VERSION` y las variables de "Contratos y datos".
  3. Certbot a webroot:
     - `mkdir -p /var/www/certbot`;
     - `certbot reconfigure` (o editar `renewal/dentissapp.com.conf`) con
       `authenticator = webroot`, `webroot_path = /var/www/certbot` y
       `deploy-hook = docker compose -f /srv/dentissa/docker-compose.prod.yml exec nginx nginx -s reload`;
     - se prueba con `certbot renew --dry-run` después del paso 5.
  4. `backup.sh pre-vX.Y.Z` con la pila actual y `docker compose -f docker-compose.yml down` (sin
     `-v`: los volúmenes se conservan).
  5. `deploy.sh vX.Y.Z` (build, backup, up, migrate, verify).
  6. Instalar el cron de `backup.sh daily`.
  7. `verify.sh --remote dentissapp.com` desde el equipo del dueño.
- **Compatibilidad:** sin cambios de esquema; la versión anterior puede volver sobre la misma
  base. Al activar `SESSION_ENCRYPT` se invalidan las sesiones abiertas. No hay usuarios, así que
  no afecta. Corte estimado en el primer paso de la pila montada a la de imágenes: 1–3 minutos.
- **Rollback:**
  - Versiones con imagen: `rollback.sh` (imágenes de `.deploy/previous`, `up -d`, verify).
  - Con migraciones incompatibles: `rollback.sh --restore backups/pre-vX.Y.Z-*.dump`.
  - Del **primer** despliegue: `docker compose -f docker-compose.prod.yml down`,
    `git checkout 4b3aecf -- docker-compose.yml docker/nginx/default.conf` sobre el commit anterior
    y `docker compose -f docker-compose.yml up -d`.
  - Tiempo objetivo: < 15 min con restauración (se mide en CA13).
- **Métricas a vigilar tras el deploy (15 min):** `/up` cada minuto, 5xx en los logs de nginx y de
  la app en Loki, trabajos fallidos de `queue` (`failed_jobs`), violaciones de CSP en la consola
  en las cinco pantallas del smoke test y uso de disco del droplet.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 Imagen inmutable** multi-etapa construida en el droplet y etiquetada por versión
  (decisión del usuario, 2026-09-29) → [ADR 0004](../../adr/0004-imagen-inmutable-y-compose-de-produccion.md).
  Alternativa descartada: checkout montado. Cubre RD2.a.
- **D2 Compose de producción completo e independiente**, seleccionado con `COMPOSE_FILE`
  (decisión del usuario, 2026-09-29) → ADR 0004. Descartado el override con `!reset` porque
  Compose combina `ports` y podría reabrir puertos.
- **D3 CSP aplicada con nonce** en los 7 scripts inline; `'unsafe-inline'` solo en `style-src`
  por los atributos `style` (decisión del usuario, 2026-09-29). Sin nonce en `style-src`, porque
  anularía `'unsafe-inline'`. Cubre RS6.a junto con HSTS en nginx.
- **D4 Certbot pasa de standalone a webroot** con recarga de nginx: el certificado se obtuvo con
  `--standalone` (confirmado por el usuario, 2026-09-29) y la renovación fallaría con nginx en el
  80.
- **D5 Cabeceras en la aplicación y HSTS en nginx.** Las cabeceras en un middleware se pueden
  probar con Pest y cubren web y API. HSTS pertenece a quien termina TLS y así cubre también el 301.
- **D6 Backups con `pg_dump -Fc` por cron del host**, en el droplet, 7 días (decisión del usuario,
  2026-09-29). Descartado el scheduler de Laravel, porque dependería de que la app esté sana
  para hacer su propio backup. Cubre RD1.b.
- **D7 Registro de operaciones en un archivo del host** (`deploys.log`) escrito por los scripts.
  Descartado Loki, porque la retención aún no está configurada (spec 016) y el registro debe
  sobrevivir a la pila.
- **D8 `QUEUE_CONNECTION=redis`** en producción y en la plantilla: Redis ya está en la pila y
  evita la tabla `jobs`. Cubre RD3.a junto con el servicio `queue`.
- **D9 Tests de `app/Core` en `tests/Modules/Core/{Unit,Integration}`** con
  `CoreIntegrationTestCase`, como espejo de `app/Core` (AGENTS.md no admite `tests/Feature`).
- **D10 Credenciales de tests fuera de CA10.** `admin`/`example` de `phpunit.xml` y
  `.github/workflows/tests.yml` son de la base de datos efímera de tests, no de un entorno. Se
  documentan en `security.md` y no se tocan (rutas protegidas).
- **D11 Local publica PostgreSQL y Redis solo en `127.0.0.1`**, para clientes SQL. En
  producción no se publican. Cubre RD6.a en el host del droplet.
- **D12 La spec se mantiene junta con 15 criterios** (decisión del usuario, 2026-09-29, al
  dividir el objetivo 4 en 015 y 016).

## Impacto en arquitectura
- `docs/architecture.md → Despliegue`: dos entornos, imágenes `dev`/`prod`/`web`, compose de
  producción, backups y scripts.
- `docs/architecture.md → Backend`: observabilidad con canal `stderr` a Loki y `/up` con
  dependencias.
- `docs/architecture.md → Deuda técnica y riesgos observados`: retirar las entradas de
  `.env.example` y del worker de colas.
- `docs/deployment.md`: procedimiento real y rollback medido (parte de CA13).

## Riesgos y mitigaciones
- **La CSP bloquea algo no detectado** (scripts añadidos por JS, estilos de terceros).
  Mitigación: `SecurityHeadersTest`, revisión de la consola en las cinco pantallas en local y
  en el ensayo, e interruptor `CSP_REPORT_ONLY`.
- **Memoria del droplet insuficiente para `npm run build`.** Mitigación: comprobar RAM antes del
  ensayo y, si hace falta, swap temporal de 2 GB.
- **Docker ignora `ufw` en los puertos publicados.** Mitigación: producción no publica más que
  80 y 443, y Grafana en `127.0.0.1`. CA9 se comprueba desde fuera.
- **Disco lleno** por imágenes antiguas, backups y Loki sin retención. Mitigación: `deploy.sh`
  conserva solo las dos últimas etiquetas (`docker image prune` de las demás), backups de 7 días y
  `verify.sh --local` avisa con disco > 80 %. La retención de Loki va en la 016.
- **`APP_DEBUG=false` en la plantilla cambia el comportamiento de CI** (copia `.env.example`).
  Mitigación: suite completa en CI antes del merge; si algún test dependía del modo depuración,
  se corrige el test.
- **`.env` local desalineado con `${…:?}`** impide arrancar local. Mitigación: nota en AGENTS.md
  y en `deployment.md`, con el mensaje de error de Compose como pista.
- **HSTS de un año** obliga a mantener TLS en `dentissapp.com` y subdominios. Mitigación: no se
  añade `preload`; se acepta, porque el sitio ya es solo https.
- **Primer paso desde la pila montada.** Si `deploy.sh` falla a mitad, está el rollback del
  primer despliegue en Rollout y el backup previo del paso 4.
