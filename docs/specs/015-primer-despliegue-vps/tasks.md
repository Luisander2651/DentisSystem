---
spec: 015-primer-despliegue-vps
plan: plan.md
status: draft
---

# Tareas · 015 Despliegue de producción en el VPS

Versión 4, corregida con `--fix` tras el `/analyze` ronda 3 (A51–A59) y el plan v4. Ninguna tarea
estaba hecha. Se conservan los números. Tareas nuevas por versión:
- v2: T005–T007, T040 y T056.
- v3: T004, T008, T039, T041, T043, T044 y T057 (T007 pasa a cubrir el flujo completo).
- v4: T047 (controlador de Auth) y T058 (ensayo local del primer paso).

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).

Abreviaturas de rutas:
- `Core/` = `app/Core/`
- `TCore/` = `tests/Modules/Core/`
- `TWa/` = `tests/Modules/whatsApp/`
- `TEm/` = `tests/Modules/Email/`
- `Prod/` = `docker/prod/`
- `Wa/` = `app/Modules/whatsApp/`
- `Em/` = `app/Modules/Email/`
- `Twilio` = `app/Modules/whatsApp/Infrastructure/ExternalApi/TwilioConection.php`
- `Brevo` = `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php`

**Operaciones en el droplet** (T019, T050–T054): cada una se ejecuta solo con aprobación explícita
del usuario en ese momento, y ninguna sustituye la aprobación de `/release` (P12). El agente no lee
el `.env` del droplet ni escribe valores de secretos. Usuario `deploy`. La pila manual sigue en
`/home/deploy/DentisSystem`, que no se toca. Producción vive en un clon aparte,
`/home/deploy/dentissa` (D18).

## Constitution Check
Revisión del desglose contra el [plan](plan.md), sin repetir su análisis.

| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec (reaprobada tras la ronda 2) y plan v3. |
| P2 Test que falla antes y pasa después | ✅ | Los tests Pest (T005–T008, T010–T017) y `verify.sh` (T018, línea base en T019) van antes de su implementación, que depende de ellos. T007 y T008 prueban los flujos completos con los adaptadores reales. `X-Powered-By` se comprueba contra el servidor real (T046, `verify.sh`), no con Pest (A44). |
| P3 Capas del módulo | ✅ | T028, T029, T039 y T041 tocan logs, adaptadores y el caso de uso de correo sin cambiar firmas entre capas; T020, T021 y T027 viven en `Core/` y `bootstrap/`. |
| P4 Contrato de API primero | ✅ | T005, T011 y T012 fijan los contratos antes de T021 y T025–T027. |
| P5 Autorización en el servidor | ➖ | Ninguna tarea cambia permisos. |
| P6 Validación con FormRequest | ➖ | Ninguna tarea cambia la entrada de un endpoint. |
| P7 Errores sin detalles internos | ✅ | T013 antes de T030; T012 fija el fallo de `/up` sin datos internos; `APP_DEBUG=false` comprobado en T050. |
| P8 Secretos fuera del repositorio | ✅ | T015 y T017 antes de T028, T029, T033, T034 y T040; T045 ejecuta gitleaks. |
| P9 Migraciones reversibles | ➖ | Sin migraciones. |
| P10 Dependencias nuevas con ADR | ✅ | T003 solo actualiza dentro del rango; imágenes base fijadas (T032, T043). |
| P11 Datos sensibles y modelo de amenazas | ✅ | T007 y T008 antes de T028, T029, T039, T041 y T047 (CA18), incluidos los casos como el worker; `exception_ignore_args` en la imagen (T032); cada `TM#` con tarea de control y de test. |
| P12 Producción con aprobación, rollback y sin altas | ✅ | `rollback.sh --to-manual` documentado (T057), ensayado en local (T058) antes de tocar el droplet y repetido en T051; rollback de versión ensayado (T054); EX1 cerrada (T003, T042). |
| P13 Lógica de negocio en el backend | ➖ | T022–T024 solo añaden `nonce`. |
| P14 Trazabilidad | ✅ | Nivel `info` y canal `stderr` (T030); IP real en los logs de nginx (T031); registro de operaciones (T004, T035–T038, verificado en T054). |
| Definición de terminado | ✅ | T045 (Pint, `pest --parallel`, build, audits, gitleaks), T060 (`CHANGELOG.md`), T061 (`aidd.py validate`) y T090–T092. |

## Preparación
- [ ] T001 Registrar la línea base en este archivo: `./vendor/bin/pest --parallel` en Docker, `npm audit --audit-level=high` (se esperan 2 críticas y 5 altas) y `composer audit` — `docs/specs/015-primer-despliegue-vps/tasks.md` — hecho cuando: los tres resultados están anotados bajo el título — cubre: CA14
- [ ] T002 Crear la base `CoreIntegrationTestCase`, sin tocar `tests/Pest.php` (cada test declara `uses()`) — `TCore/CoreIntegrationTestCase.php` — hecho cuando: `./vendor/bin/pest tests/Modules/Core` se ejecuta sin errores de carga y `tests/Pest.php` no cambió
- [ ] T003 Actualizar las herramientas de build con `npm audit fix` (sin `--force`) — `package.json`, `package-lock.json` — hecho cuando: `npm audit --audit-level=high` sale sin hallazgos, `npm run build` funciona y no se añadió ningún paquete ni cambió una versión mayor (si ocurre, detenerse y preguntar) — cubre: CA14 — depende: T001
- [ ] T004 [P] Crear `lib.sh`: rutas `DENTISSA_DIR`, `MANUAL_DIR`, `BACKUP_DIR` y `DEPLOY_LOG` parametrizables (por defecto `/home/deploy/dentissa`, `/home/deploy/DentisSystem`, `/home/deploy/backups` y `/home/deploy/deploys.log`) y función de registro de una línea (fecha UTC, usuario del SSH, acción, versión, resultado), solo anexado — `Prod/lib.sh` — hecho cuando: `bash -n` y `shellcheck` limpios, y con `DEPLOY_LOG` temporal la función anexa una línea con ese formato — cubre: CA15

## Tests (antes de implementar)
- [ ] T005 Escribir `TrustedProxiesTest`: con `REMOTE_ADDR` de un rango de Cloudflare y `X-Forwarded-For: 203.0.113.7`, `request()->ip()` es `203.0.113.7`; dos visitantes distintos detrás de la misma IP de Cloudflare no comparten el límite `api`; con `REMOTE_ADDR` ajeno a Cloudflare y un `X-Forwarded-For` falso, `ip()` es `REMOTE_ADDR` y el límite cuenta por ella — `TCore/Integration/TrustedProxiesTest.php` — hecho cuando: fallan los casos de Cloudflare — cubre: CA16, CA17 — depende: T002
- [ ] T006 [P] Escribir `CloudflareRangesTest`: los rangos de `config('security.trusted_proxies')` y los `set_real_ip_from` de `docker/nginx/prod.conf` son los mismos y no están vacíos — `TCore/Unit/CloudflareRangesTest.php` — hecho cuando: falla porque no existen — cubre: CA16
- [ ] T007 [P] Escribir `WhatsAppFlowLogsTest` con `uses(AppointmentsIntegrationTestCase::class)` y `Log::spy()`. Se crea una cita para un paciente con teléfono y el flujo recorre listener, caso de uso, job y `TwilioConection` real, con un `Client` de Twilio simulado: una vez con éxito y otra lanzando un error cuyo mensaje incluye el número. Además, el caso de `RetriveDataForScheduledAppointmenEventUseCase` y un caso que ejecuta el listener fuera de una petición HTTP (como el worker), con `zend.exception_ignore_args=1`, y pasa la excepción por `report()`. Ningún log ni la excepción que llega al worker contienen el teléfono, el nombre, las variables, el mensaje del proveedor ni trazas — `TWa/Integration/WhatsAppFlowLogsTest.php` — hecho cuando: falla por los logs actuales (o porque `TwilioConection` aún no acepta el cliente) — cubre: CA18
- [ ] T008 [P] Crear la base `EmailIntegrationTestCase` (sin eventos fingidos) y escribir `PasswordResetEmailTest` con ella y `Log::spy()`. La solicitud llega por HTTP al controlador y recorre listener, caso de uso y `BrevoApi` real. El cliente Brevo simulado se inyecta sustituyendo solo en el test el binding contextual (`when(SendResetPasswordEmailUseCase::class)->needs(BrevoApi::class)`), sin tocar `AppServiceProvider`. Casos: con éxito; con un error del SDK cuyo cuerpo incluye el email; y el listener ejecutado como el worker, con `report()`. Ningún log (tampoco el del controlador) ni la excepción relanzada contienen el email, el nombre, el cuerpo ni trazas. Con `app.url=https://dentissapp.com`, el enlace enviado empieza por `https://dentissapp.com/reset-password?token=` — `TEm/Integration/EmailIntegrationTestCase.php`, `TEm/Integration/PasswordResetEmailTest.php` — hecho cuando: el flujo se ejecuta de verdad (el listener corre) y falla por el enlace con `localhost` y por los logs actuales — cubre: CA18, CA19
- [ ] T010 Escribir `SecurityHeadersTest`: CSP, `X-Frame-Options: DENY`, `nosniff` y `Referrer-Policy` en una vista web, en `/api/v1` y en `/up`; `frame-ancestors 'none'`; el `nonce` coincide con el de los scripts de `login`; modo report-only con `security.csp.report_only=true`; con `Vite::useHotFile()` apuntando a un archivo temporal propio del proceso (nunca `public/hot`) que se borra al terminar, la CSP incluye el origen de vite y `ws://` — `TCore/Integration/SecurityHeadersTest.php` — hecho cuando: falla porque no hay cabeceras, y tras la suite no existe `public/hot` — cubre: CA5, CA1 — depende: T002
- [ ] T011 Escribir `CorsTest`: preflight `OPTIONS` desde `https://evil.example` sin `Access-Control-Allow-Origin`; desde `APP_URL` con él; nunca `Access-Control-Allow-Credentials: true` — `TCore/Integration/CorsTest.php` — hecho cuando: falla porque hoy se permite cualquier origen — cubre: CA6 — depende: T002
- [ ] T012 Escribir `HealthCheckTest` con `app.debug=false` y `Log::spy()`: `/up` 200 con dependencias; 500 en menos de 5 s si PostgreSQL no responde y si Redis no responde (host no enrutable y puerto cerrado, por `config()`); ni el cuerpo ni los logs contienen el host ni el texto de la excepción original — `TCore/Integration/HealthCheckTest.php` — hecho cuando: fallan los casos de dependencia caída — cubre: CA11 — depende: T002
- [ ] T013 Escribir `WebUnexpectedErrorTest`: con `app.debug=false`, una ruta web de test que lanza responde 500 sin el mensaje, sin rutas de archivos ni trazas — `TCore/Integration/WebUnexpectedErrorTest.php` — hecho cuando: si pasa desde el principio, se comprueba que falla con `app.debug=true` y se anota — cubre: CA7 — depende: T002
- [ ] T014 [P] Escribir `EnvExampleDefaultsTest`: `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`, `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `DB_CONNECTION=pgsql`, `REDIS_CLIENT=predis`, `REDIS_PASSWORD` vacío (no `null`), y existen por nombre `TWILIO_*`, `BREVO_*`, `APP_VERSION` y `CSP_REPORT_ONLY` — `TCore/Unit/EnvExampleDefaultsTest.php` — hecho cuando: falla por los valores actuales — cubre: CA8, CA7
- [ ] T015 [P] Escribir `NoEnvOutsideConfigTest` con `arch()->expect('App')->not->toUse('env')` — `TCore/Unit/NoEnvOutsideConfigTest.php` — hecho cuando: falla señalando `TwilioConection` y `BrevoApi` — cubre: CA3, CA10
- [ ] T016 Escribir `ProvidersReadConfigTest` con los adaptadores reales y sin red: `new TwilioConection()` con valores solo en `config('services.twilio.*')` crea un cliente con esos `getUsername()` y `getPassword()`; `SendResetPasswordEmailUseCase` resuelto por el contenedor tiene un `BrevoApi` cuyo `apiKey` es `config('services.brevo.api_key')` — `TCore/Unit/ProvidersReadConfigTest.php` — hecho cuando: falla porque hoy se lee `env()` — cubre: CA3 — depende: T002
- [ ] T017 [P] Escribir `ComposeFilesTest`: en `docker-compose*.yml` no hay contraseñas, `POSTGRES_*` ni `requirepass` literales; `docker-compose.prod.yml` declara `name: dentissa`, solo publica `80`, `443` y `127.0.0.1:3000`, usa `container_name` `dentissa-*` y declara `external` los volúmenes de datos con `${DATA_VOLUME_PREFIX:?}`; `docker-compose.yml` publica `8000` y `127.0.0.1:5173`, y `app` y `queue` comparten los volúmenes de `vendor/` y `node_modules/`; el `Dockerfile` fija cada imagen base con etiqueta `mayor.menor` como mínimo, nunca `:latest` ni solo la mayor, y la mayor de Node coincide con `node-version` de `.github/workflows/tests.yml`; `.dockerignore` excluye `.env`, `.git` y `public/hot` — `TCore/Unit/ComposeFilesTest.php` — hecho cuando: falla (no existen el archivo de producción ni `.dockerignore`) — cubre: CA1, CA2, CA9, CA10
- [ ] T018 Escribir `verify.sh` según el plan, usando `lib.sh`. Modo `--local`: `/up`, `APP_DEBUG` apagado, `queue` en marcha, `id -u` ≠ 0 en `app` y `queue`, la imagen sin `node`, `composer`, `vendor/pestphp`, `.env`, `.git` ni `public/hot`, con `public/build/manifest.json` y `zend.exception_ignore_args=On`, la pila manual sin `db` en marcha, backup de menos de 25 h con permisos 700/600 y rotación, última línea de `deploys.log` válida y disco < 80 %. Modo `--remote <dominio> --origin <IP>`: contra el dominio, 301 a https con HSTS, cabeceras, sin `X-Powered-By` ni versión de nginx, CORS con origen ajeno, 404 en `/.env`, `/.git/` y `/backups/`, y 200 en `/storage/login.jpg` y en un asset del manifest; contra la IP directa, puertos 5432, 6379, 3000, 3100, 5173, 9000 y 12345 cerrados, y 11 peticiones a una ruta con `throttle:api`, con `CF-Connecting-IP` y `X-Forwarded-For` falsos y distintos en cada una, terminan en 429. Código ≠ 0 si algo falla — `Prod/verify.sh` — hecho cuando: `bash -n` sin errores y `shellcheck` limpio (vía imagen `koalaman/shellcheck` si no está instalado) — cubre: CA2, CA3, CA4, CA9, CA11, CA12, CA15, CA17 — depende: T004
- [ ] T019 Ejecutar la línea base de `verify.sh --remote dentissapp.com --origin <IP>` desde el equipo del usuario, **con su aprobación** (el usuario da la IP) — sin archivos (resultado en este `tasks.md`, sin la IP) — hecho cuando: el resultado queda anotado y falla al menos por la falta de HSTS y de cabeceras — cubre: CA4, CA9 — depende: T018

## Implementación

### Aplicación: cabeceras, CSP e IP real
- [ ] T020 Crear `config/security.php` (`csp.report_only`, orígenes extra) y el middleware `SecurityHeaders` (CSP con `Vite::useCspNonce()`, modo hot con el origen de vite y `ws://`, report-only y las otras tres cabeceras) — `config/security.php`, `Core/Middlewares/SecurityHeaders.php` — hecho cuando: `php -l` limpio en ambos y T010 sigue fallando solo porque el middleware no está registrado — cubre: CA5, CA1 — depende: T010
- [ ] T021 Crear `TrustCloudflareProxies` (extiende `TrustProxies` y lee `config('security.trusted_proxies')` en cada petición), añadir `trusted_proxies` (rangos IPv4 e IPv6 de Cloudflare) a `config/security.php`, registrar `SecurityHeaders` (`append`) y sustituir `TrustProxies` por `TrustCloudflareProxies` (`replace`) en `bootstrap/app.php`, sin llamar a `config()` ahí — `Core/Middlewares/TrustCloudflareProxies.php`, `bootstrap/app.php`, `config/security.php` — hecho cuando: T005 pasa, T010 pasa salvo el caso del nonce en `login`, y `bootstrap/app.php` no contiene `config(` — cubre: CA5, CA16, CA17 — depende: T005, T020
- [ ] T022 Añadir `nonce` con `Vite::cspNonce()` a los `<script>` inline de los componentes — `resources/views/components/landing/nav.blade.php`, `resources/views/components/ui/input.blade.php`, `resources/views/components/ui/table.blade.php` — hecho cuando: cada `<script>` inline lleva el nonce — cubre: CA5 — depende: T021
- [ ] T023 Añadir `nonce` a los `<script>` inline de auth — `resources/views/pages/auth/login.blade.php`, `resources/views/pages/auth/logout.blade.php`, `resources/views/pages/auth/register.blade.php` — hecho cuando: el caso de nonce de T010 pasa — cubre: CA5 — depende: T021
- [ ] T024 Añadir `nonce` al `<script>` inline de usuarios y comprobar que ningún `<script` de `resources/views` queda sin `nonce` ni `src` — `resources/views/pages/usuarios/index.blade.php` — hecho cuando: T010 pasa completo y la búsqueda no devuelve resultados — cubre: CA5 — depende: T022, T023

### Aplicación: CORS, salud, errores, proveedores y configuración
- [ ] T025 Publicar `config/cors.php` restrictivo (`api/*` y `sanctum/csrf-cookie`, origen `APP_URL`, métodos y cabeceras explícitos, sin credenciales) — `config/cors.php` — hecho cuando: T011 pasa — cubre: CA6 — depende: T011
- [ ] T026 Configurar predis con `timeout` 2 s, `read_write_timeout` 2 s y sin reintentos en la conexión por defecto (PostgreSQL no cambia) — `config/database.php` — hecho cuando: el caso de Redis caído de T012 responde en menos de 5 s — cubre: CA11 — depende: T012
- [ ] T027 Crear el listener `CheckDependenciesOnHealth` (comprobación de conexión TCP al host y puerto de PostgreSQL con límite de 2 s, consulta mínima, `ping` a Redis, `Log::error('health.dependency_failed', ['dependency' => …])` y excepción propia sin previa con el mensaje `"<dependencia> unavailable"`) y registrarlo en `AppServiceProvider` para `DiagnosingHealth` — `Core/Health/CheckDependenciesOnHealth.php`, `app/Providers/AppServiceProvider.php` — hecho cuando: T012 pasa completo — cubre: CA11 — depende: T012, T026
- [ ] T028 Añadir `services.twilio` (`sid`, `token`, `from`, `appointment_template_sid`) y `services.brevo.api_key`; en `Twilio`: constructor con `?Client $client = null`, `from` y `contentSid` desde `config()`, logs solo con `templateName`, `variableCount`, `messageId`, `status` y `errorCode`, y relanzar una excepción propia con solo el código ante un error de Twilio — `config/services.php`, `Twilio` — hecho cuando: la parte de Twilio de T016 pasa y, en T007, ningún log ni excepción que salga de `TwilioConection` contiene el número — cubre: CA3, CA10, CA18 — depende: T007, T015, T016
- [ ] T029 `Brevo`: `public string $apiKey` y `?Brevo $client = null` en el constructor, sin `env()`; logs y excepción relanzada solo con código de estado e id de plantilla (sin `body`, `getMessage()` ni email). `AppServiceProvider` lo construye con `apiKey: config('services.brevo.api_key')` — `Brevo`, `app/Providers/AppServiceProvider.php` — hecho cuando: T015 y T016 pasan completos y, en T008, nada de `BrevoApi` contiene el email — cubre: CA3, CA10, CA18 — depende: T008, T027, T028
- [ ] T039 Retirar teléfono, nombre, `to`, variables, `getMessage()` y trazas de los logs del flujo de WhatsApp, conservando el evento, el id de la cita, `errorCode` y la clase de la excepción — `Wa/Infrastructure/Listeners/CreatedAppointmentListener.php`, `Wa/Aplication/UseCases/SendAppointmentConfirmationUseCase.php`, `Wa/Aplication/Jobs/ConfirmationAppointmentMessage.php` — hecho cuando: los casos de listener, caso de uso y job de T007 pasan — cubre: CA18 — depende: T007, T028
- [ ] T041 Retirar el teléfono del log de `RetriveDataForScheduledAppointmenEventUseCase`, el email, el nombre, el mensaje y las trazas de `SendPasswordResetListener`, y construir el enlace de `SendResetPasswordEmailUseCase` con `config('app.url')` — `app/Modules/Appointments/Aplication/UseCases/RetriveDataForScheduledAppointmenEventUseCase.php`, `Em/Infrastructure/Listeners/SendPasswordResetListener.php`, `Em/Aplication/UseCases/SendResetPasswordEmailUseCase.php` — hecho cuando: T007 y T008 pasan completos — cubre: CA18, CA19 — depende: T007, T008, T029, T039
- [ ] T047 Retirar el mensaje y la traza del log de `SendResetPasswordEmailController`, sin cambiar su respuesta — `app/Modules/Auth/Infrastructure/Http/Controllers/SendResetPasswordEmailController.php` — hecho cuando: T008 pasa completo y los tests existentes de Auth siguen pasando — cubre: CA18 — depende: T008, T041
- [ ] T030 Actualizar `.env.example` según el plan (valores seguros, stack real con Redis para colas, caché y sesiones, `REDIS_PASSWORD` vacío, nombres nuevos, `COMPOSE_FILE` comentado) — `.env.example` — hecho cuando: T014 pasa y T013 sigue pasando — cubre: CA7, CA8 — depende: T013, T014

### Docker: imágenes, nginx, Compose y CI
- [ ] T040 Crear `.dockerignore` (`.env*` salvo `.env.example`, `.git`, `node_modules`, `vendor`, `public/hot`, `public/build`, `public/storage`, contenido de `storage/`, `bootstrap/cache/*.php`, `tests`, `docs`, `.ai`, `aidlc-docs`, `docker-compose*.yml`) — `.dockerignore` — hecho cuando: el caso `.dockerignore` de T017 pasa — cubre: CA2, CA10 — depende: T017
- [ ] T043 Subir `node-version` a `'22'` en CI — `.github/workflows/tests.yml` — hecho cuando: coincide con la mayor de Node del `Dockerfile` (caso de T017) y CI pasa en la rama — cubre: CA2 — depende: T017, T032
- [ ] T031 Separar nginx: `default.conf` vuelve a la versión local y `prod.conf` nuevo con lo de `4b3aecf` más `location ^~ /.well-known/acme-challenge/` en el 80 **y en el 443**, `http2`, HSTS `always`, `server_tokens off`, dotfiles (salvo `.well-known`) → 404, `nosniff` en `/build/` y `set_real_ip_from` con los rangos de Cloudflare y `real_ip_header CF-Connecting-IP` — `docker/nginx/default.conf`, `docker/nginx/prod.conf` — hecho cuando: `nginx -t` pasa para ambos en la imagen `nginx` fijada (el de producción con certificados autofirmados de prueba) y T006 pasa — cubre: CA1, CA4, CA12, CA16 — depende: T006, T021
- [ ] T032 Convertir el `Dockerfile` en multi-etapa con imágenes base fijadas (`base`, `dev`, `assets` con Node 22, `vendor` con Composer 2, `prod` con `storage/` y `bootstrap/cache/` de `www-data`, `web` con `public/build` de `assets`), crear `entrypoint.sh` (`php artisan optimize` y `exec "$@"`) y `php.ini` de producción (`expose_php=Off`, `zend.exception_ignore_args=On`) — `docker/Dockerfile`, `Prod/entrypoint.sh`, `Prod/php.ini` — hecho cuando: `docker build --target dev`, `--target prod` y `--target web` terminan; en `prod` no existen `node`, `composer`, `vendor/pestphp`, `.env`, `.git` ni `public/hot`, sí `public/build/manifest.json`, y `id -u` ≠ 0; `docker run` de `prod` con volúmenes vacíos termina `optimize` y `/up` responde 200 (con dependencias locales); `php -i` en `prod` muestra `zend.exception_ignore_args => On`; ninguna etapa (tampoco `dev`) usa `:latest`; el manifest de `web` es igual al de `prod`; los casos del `Dockerfile` de T017 pasan — cubre: CA1, CA2, CA3 — depende: T003, T031, T040
- [ ] T033 Devolver `docker-compose.yml` a local: `target: dev` en `app` y `queue` con volúmenes con nombre compartidos para `vendor/` y `node_modules/`, nginx `8000:80` con `default.conf`, vite `127.0.0.1:5173:5173`, PostgreSQL y Redis (sin contraseña) en `127.0.0.1`, Grafana `127.0.0.1:3000:3000`, credenciales de PostgreSQL `${VAR:?}` — `docker-compose.yml` — hecho cuando: los casos locales de T017 pasan — cubre: CA1, CA10 — depende: T017, T032
- [ ] T034 Crear `docker-compose.prod.yml` completo según el plan (`name: dentissa`, `container_name` `dentissa-*`, volúmenes de datos `external` con `${DATA_VOLUME_PREFIX:?}`, imágenes `dentissa-app`/`dentissa-web:${APP_VERSION:?}`, `env_file: .env`, volúmenes `storage` y `storage-public` con este último de solo lectura en nginx, `queue` con `--tries=3 --max-time=3600` y `restart: unless-stopped`, healthchecks, sin puertos salvo 80, 443 y `127.0.0.1:3000`) — `docker-compose.prod.yml` — hecho cuando: T017 pasa completo y `docker compose -f docker-compose.prod.yml config` valida con un `.env` ficticio — cubre: CA3, CA9, CA10 — depende: T017, T032

### Servidor: scripts de operación
- [ ] T044 Crear `compose.sh`: envoltorio de `docker compose -f docker-compose.prod.yml` en `DENTISSA_DIR` que exporta `APP_VERSION` desde `.deploy/current`, o un valor neutro si no existe — `Prod/compose.sh` — hecho cuando: `bash -n` y `shellcheck` limpios; con un `.deploy/current` de prueba, `compose.sh config` interpola esa etiqueta aunque `.env` tenga otra; sin `.deploy/current`, `compose.sh config` no falla — cubre: CA3, CA12 — depende: T004, T034
- [ ] T035 Crear `backup.sh` (`pg_dump -Fc` vía `compose.sh`, o de la pila manual con `--from-manual` en `MANUAL_DIR`, a `$BACKUP_DIR`, carpeta 700, archivo 600, rotación de los diarios de más de 7 días, registro con `lib.sh`) — `Prod/backup.sh` — hecho cuando: contra la pila local, con `BACKUP_DIR` y `DEPLOY_LOG` temporales y sin `.deploy/current`, genera un dump restaurable y anexa la línea (también con `--from-manual` apuntando a la pila local); la rotación se prueba con un archivo con fecha antigua; `shellcheck` limpio. Los permisos 700/600 se verifican en Linux en T053 — cubre: CA12, CA15 — depende: T004, T044
- [ ] T036 Crear `restore.sh` (`pg_restore -c` del dump indicado, confirmación interactiva y registro) — `Prod/restore.sh` — hecho cuando: en local restaura el dump de T035 sobre la base de desarrollo y registra la operación; `shellcheck` limpio — cubre: CA13, CA15 — depende: T035
- [ ] T037 Crear `deploy.sh [--first] <tag>`: árbol limpio y `HEAD` igual al commit del tag; build de las dos imágenes; comprobación de que no contienen `.env`, `.git` ni `public/hot`; `backup.sh pre-<tag>`. Con `--first` (primer paso y vuelta tras `--to-manual`), `backup.sh --from-manual`, `docker compose stop` en `MANUAL_DIR` (sin borrar contenedores ni volúmenes) y copia de `MANUAL_DIR/storage/app/public` al volumen `storage-public` si está vacío. Después `up -d` con `APP_VERSION`, `migrate --force`, `verify.sh --local`, `.deploy/current` y `.deploy/previous`, dos últimas etiquetas de imagen, registro, y `result=failed` con parada si algo falla — `Prod/deploy.sh` — hecho cuando: `bash -n` y `shellcheck` limpios, y con otro `HEAD` que el del tag sale con error antes de construir (probado en local); la ejecución real se verifica en T051 — cubre: CA2, CA12, CA15 — depende: T018, T034, T035, T044
- [ ] T038 Crear `rollback.sh [tag] [--restore <dump>]` (imágenes de `.deploy/previous` por defecto, `up -d`, `restore.sh` opcional, actualiza `.deploy/current`, `verify.sh --local` y registro) y `rollback.sh --to-manual` (detiene la pila de producción y hace `docker compose start` en `MANUAL_DIR`) — `Prod/rollback.sh` — hecho cuando: `bash -n` y `shellcheck` limpios; la ejecución real se verifica en T058 (local), T051 y T054 — cubre: CA13, CA15 — depende: T036, T037

### Documentación de seguridad y local
- [ ] T042 En `security.md`: EX1 → cerrada (spec 015); RS16.a, RS6.b, RS10.a, RS10.b, RS9.a y RS9.b → `mitigada (spec 015; versión en /release)`, una por una; nota de D10. En `AGENTS.md`: el `.env` local debe definir `DB_USERNAME`, `DB_PASSWORD` y `DB_DATABASE` iguales a los del volumen — `docs/security.md`, `AGENTS.md` — hecho cuando: cada corrección tiene su estado y `aidd.py validate` no da errores — cubre: CA10, CA14, CA18 — depende: T003, T025, T030, T041
- [ ] T057 Documentar en `deployment.md`, **antes** de tocar el droplet, el modelo de clon aparte (`/home/deploy/dentissa`, `DATA_VOLUME_PREFIX`, volúmenes externos), el rollback del primer paso (`rollback.sh --to-manual`: qué detiene, qué arranca y cómo se comprueba) y el orden de Rollout del plan — `docs/deployment.md` — hecho cuando: la sección de rollback describe `--to-manual` sin `TODO` — cubre: CA13 — depende: T038
- [ ] T056 En `security.md`: RS6.a → `mitigada (spec 015; versión en /release)` solo después de verificar HSTS y las cabeceras en el dominio real — `docs/security.md` — hecho cuando: el cambio cita el resultado de `verify.sh --remote` de T051 — cubre: CA4, CA5 — depende: T042, T051

## Integración y documentación
- [ ] T045 Verificar en local: `vendor/bin/pint --dirty --format agent`, `./vendor/bin/pest --parallel` en Docker, `npm run build`, `npm audit --audit-level=high`, `composer audit` y `gitleaks detect --no-banner` — sin archivos nuevos — hecho cuando: todo pasa sin hallazgos y queda anotado aquí — cubre: CA14, CA10, CA18 — depende: T024, T025, T027, T029, T030, T033, T034, T039, T040, T041, T043, T047
- [ ] T046 Comprobar en local CA1, CA5 y el arranque real del kernel: `docker compose up -d` con el `docker-compose.yml` nuevo, `http://localhost:8000/up` 200 por nginx (petición HTTP real, A32), `docker compose exec queue php artisan about` sin errores, recarga en caliente al editar una vista con `npm run dev`, y consola sin violaciones de CSP en login, agenda, pacientes, expediente y sitio público (con `npm run dev` y con `npm run build`) — sin archivos (resultado aquí) — hecho cuando: todo se cumple y queda anotado — cubre: CA1, CA5, CA16 — depende: T045
- [ ] T058 Ensayar en local el primer paso y su rollback: pila manual levantada desde `4b3aecf` en un worktree aparte (con certificados autofirmados por un override temporal no versionado) y clon de producción aparte con `DATA_VOLUME_PREFIX` de esa pila. Después `deploy.sh --first`, `docker ps -a` (pila manual detenida y no borrada, producción con el mismo volumen de datos), `rollback.sh --to-manual` (la pila manual sirve por https su código y su nginx) y `deploy.sh --first` de nuevo — sin archivos del repo (resultado aquí) — hecho cuando: cada paso se cumple, los datos de prueba sobreviven a los tres cambios y queda anotado — cubre: CA13, CA15 — depende: T037, T038, T044, T046, T057
- [ ] T050 Preparar el droplet, **con aprobación del usuario**. Comprobar, sin configurar: `ufw` con 22, 80 y 443; Cloudflare en Full (strict) y si "Always Use HTTPS" está activo (queda anotado); RAM libre para el build (swap de 2 GB si hace falta). Clonar el repositorio en `/home/deploy/dentissa`; el usuario copia ahí el `.env` de la pila manual y pone `DATA_VOLUME_PREFIX`, `COMPOSE_FILE`, `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=info`, `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=redis` y `QUEUE_CONNECTION=redis`. Crear `/var/www/certbot` y pasar la renovación de certbot a webroot con `deploy-hook` vía `compose.sh` y `DENTISSA_DIR` explícito — sin archivos del repo — hecho cuando: la lista está hecha y anotada (sin valores de secretos) y `/home/deploy/DentisSystem` no cambió (`git status` limpio) — cubre: CA7, CA9 — depende: T058
- [ ] T051 Ensayo, primer despliegue y vuelta a la pila manual, **con aprobación del usuario**: en el clon, la rama con tag local `ensayo-1`; `deploy.sh --first ensayo-1`; `verify.sh --local` y `verify.sh --remote dentissapp.com --origin <IP>`; después `rollback.sh --to-manual` (la pila manual vuelve a servir por https, comprobado con `curl -I https://dentissapp.com/up` y `/login`) y de nuevo `deploy.sh --first ensayo-1` — sin archivos — hecho cuando: ambas verificaciones pasan (incluidos `/storage/login.jpg`, el asset del manifest y el 429 con cabeceras falsas, también tras la vuelta), la vuelta a la pila manual funciona y los cortes quedan anotados — cubre: CA2, CA4, CA5, CA6, CA7, CA9, CA12, CA13, CA15, CA17 — depende: T037, T038, T044, T050
- [ ] T052 Ensayo, comprobaciones en vivo, **con aprobación del usuario**: una cita de prueba con teléfono y un restablecimiento de contraseña se procesan en `queue` en menos de 1 minuto (logs en Grafana por túnel, sin teléfono, nombre ni email; el enlace del correo apunta a `https://dentissapp.com`); `kill 1` en `queue` → vuelve en menos de 1 minuto; `compose.sh stop redis` y `compose.sh stop db` → `/up` 500 en menos de 5 s, y después `start`; la IP propia del usuario aparece en los logs de nginx; el interruptor `CSP_REPORT_ONLY=true` con `compose.sh up -d app` cambia la cabecera y se revierte; consola sin violaciones de CSP en las cinco pantallas — sin archivos — hecho cuando: todo se cumple y queda anotado — cubre: CA3, CA5, CA11, CA16, CA18, CA19 — depende: T051
- [ ] T053 Ensayo, certificado, cron y permisos, **con aprobación del usuario**: `certbot renew --dry-run` correcto con nginx en marcha y la configuración real de Cloudflare; el `deploy-hook` recarga nginx; cron diario de `backup.sh daily` en `crontab -l` del usuario `deploy`; ejecución del cron en su contexto real (o `backup.sh daily` con el mismo entorno) con carpeta 700 y archivo 600 — sin archivos — hecho cuando: el dry-run y el hook funcionan, el cron está instalado y `verify.sh --local` ve el backup con sus permisos — cubre: CA4, CA12 — depende: T051
- [ ] T054 Ensayo del rollback de versión, **con aprobación del usuario**: `deploy.sh ensayo-2` (mismo código, otro tag) midiendo el corte; cambio de prueba en datos ficticios; `rollback.sh ensayo-1 --restore <dump pre-ensayo-2>`, cronometrado; después, `compose.sh up -d app` mantiene la etiqueta `ensayo-1` — sin archivos — hecho cuando: el corte de `ensayo-2` queda anotado (objetivo < 1 min), la app vuelve a `/up` 200 con los datos del backup, `deploys.log` tiene las líneas de deploy, rollback y restore, `verify.sh --local` pasa y el tiempo total queda anotado (< 15 min) — cubre: CA13, CA15 — depende: T036, T038, T052, T053
- [ ] T055 Completar en `deployment.md` el procedimiento real: `deploy.sh`, `rollback.sh`, `restore.sh`, `compose.sh`, backups, certbot, cron, Cloudflare (Full strict, "Always Use HTTPS", rangos y su revisión periódica) e interruptor de la CSP. Incluir los tiempos medidos en T051 y T054 y quitar los `TODO(init)` de despliegue y rollback — `docs/deployment.md` — hecho cuando: esas secciones no tienen `TODO` y citan los tiempos medidos — cubre: CA13 — depende: T054, T057
- [ ] T060 Añadir a `CHANGELOG.md` (`[Unreleased]`) las entradas de la spec 015 en lenguaje de usuario — `CHANGELOG.md` — hecho cuando: están en Added/Changed/Fixed/Security con `(spec 015)` — depende: T055
- [ ] T061 Ejecutar `python .ai/bin/aidd.py validate` — sin archivos — hecho cuando: 0 errores — depende: T056, T060, T090, T091
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: secciones Despliegue (Cloudflare, imágenes `dev`/`prod`/`web`, dos compose, scripts, backups, IP real), Backend (canal `stderr`, `/up` con dependencias, `SecurityHeaders`, `TrustCloudflareProxies`) y Deuda técnica (retirar `.env.example` y worker)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas (depende de T054). `deployment.md`: variables nuevas por nombre, entorno prod y RD1.a, RD1.b, RD2.a, RD3.a, RD4.a, RD5.a, RD5.b, RD6.a y RD6.b → `mitigada (spec 015; versión en /release)`, una por una. `observability.md`: OB2.b, OB4.a, OB5.a, OB5.b, OB6.a y OB9.a → mitigada igual; en OB10.b, nota "parcial: whatsApp (spec 015); Auth pendiente" sin cambiar su estado; evento `health.dependency_failed`; `deploys.log`; IP real en los logs de nginx. Las correcciones que la spec deja fuera no cambian de estado
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T017, T046 | T020, T031, T032, T033 |
| CA2 | T017, T018 | T032, T037, T040, T043 |
| CA3 | T015, T016, T018, T052 | T028, T029, T032, T034, T044 |
| CA4 | T018, T019 | T031, T056 |
| CA5 | T010, T046, T052 | T020, T021, T022, T023, T024, T056 |
| CA6 | T011 | T025 |
| CA7 | T013, T014 | T030, T050 |
| CA8 | T014 | T030 |
| CA9 | T017, T018, T019 | T034, T050 |
| CA10 | T015, T017, T045 | T028, T029, T033, T034, T040 |
| CA11 | T012, T052 | T026, T027 |
| CA12 | T018, T053 | T031, T035, T037, T044 |
| CA13 | T051, T054, T058 | T036, T038, T055, T057 |
| CA14 | T001, T045 | T003, T042 |
| CA15 | T018, T054, T058 | T004, T035, T036, T037, T038 |
| CA16 | T005, T006, T046, T052 | T021, T031 |
| CA17 | T005, T018 | T021 |
| CA18 | T007, T008, T052 | T028, T029, T032, T039, T041, T047 |
| CA19 | T008, T052 | T041 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T034, T050 | T017, T018, T019 |
| TM2 | T030, T050 | T013, T014 |
| TM3 | T031 | T018, T019 |
| TM4 | T020, T021, T022, T023, T024 | T010 |
| TM5 | T025 | T011 |
| TM6 | T031, T032 | T018 |
| TM7 | T028, T029, T033, T034 | T015, T017 |
| TM8 | T004, T035, T036, T037, T038 | T018, T054 |
| TM9 | T035, T038 | T018, T054 |
| TM10 | T003, T032, T043 | T001, T017, T045 |
| TM11 | T035 | T018, T053 |
| TM12 | T032 | T018 |
| TM13 | T040, T037 | T017, T018 |
| TM14 | T021, T031 | T005, T006, T018 |
| TM15 | T028, T029, T032, T039, T041, T047 | T007, T008, T018 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `.dockerignore` | T040 |
| `docker/Dockerfile` | T032 |
| `docker/prod/php.ini` | T032 |
| `docker/prod/entrypoint.sh` | T032 |
| `docker-compose.yml` | T033 |
| `docker-compose.prod.yml` | T034 |
| `docker/nginx/default.conf` | T031 |
| `docker/nginx/prod.conf` | T031 |
| `docker/prod/compose.sh` | T044 |
| `docker/prod/lib.sh` | T004 |
| `docker/prod/deploy.sh` | T037 |
| `docker/prod/rollback.sh` | T038 |
| `docker/prod/backup.sh` | T035 |
| `docker/prod/restore.sh` | T036 |
| `docker/prod/verify.sh` | T018 |
| `.env.example` | T030 |
| `config/services.php` | T028 |
| `config/cors.php` | T025 |
| `config/database.php` | T026 |
| `config/security.php` | T020, T021 |
| `app/Core/Middlewares/SecurityHeaders.php` | T020 |
| `app/Core/Middlewares/TrustCloudflareProxies.php` | T021 |
| `bootstrap/app.php` | T021 |
| `app/Core/Health/CheckDependenciesOnHealth.php` | T027 |
| `app/Providers/AppServiceProvider.php` | T027, T029 |
| `TwilioConection.php` | T028 |
| `BrevoApi.php` | T029 |
| Flujo de WhatsApp | T039, T041 |
| Flujo de correo | T041, T047 |
| `SendResetPasswordEmailUseCase.php` | T041 |
| Vistas con `<script>` inline | T022, T023, T024 |
| `package.json`, `package-lock.json` | T003 |
| `.github/workflows/tests.yml` | T043 |
| `tests/Modules/Core/`, `tests/Modules/whatsApp/`, `tests/Modules/Email/` (con `EmailIntegrationTestCase`) | T002, T005–T008, T010–T017 |
| Docs | T042, T055, T056, T057, T060, T090, T091 |
