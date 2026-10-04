---
spec: 015-primer-despliegue-vps
verdict: changes_requested
round: 1
date: 2026-10-03
base: 131ea14
head: e175328
human_signoff: pending
---

# Review · 015 Primer despliegue reproducible en el VPS

Ronda 1 sobre `131ea14..e175328` (58 archivos de código, `code.diff` de 136 KB). Revisores
independientes de solo lectura: A (spec), C+D (constitución y calidad), S (seguridad) y
accesibilidad. Plan y alcance (B) y preparación para release, revisados con `scope.md`, el Rollout
del plan y `docs/deployment.md`. Cada hallazgo se verificó abriendo el archivo.

La revisión se ejecutó con las skills del plugin 1.11.2 sobre un proyecto en 1.8.1 (validador y
plantillas); `review-pack` y `validate` del validador 1.8.1 bastaron.

## Resumen
**changes_requested.** La spec cumple sus criterios en el servidor (ensayos T050–T054) y la suite
pasa entera, pero hay dos bloqueantes pequeños —dos variables nuevas que faltan en `.env.example`
(P8) y un test de la spec que no pasa Pint— y varios fallos reales en los scripts de producción:
la comprobación de disco compara texto en vez de números, un despliegue que falla en la migración
deja el rollback apuntando a la versión equivocada, y redesplegar el mismo tag borra las imágenes
de la versión anterior.

Conteo de la ronda: 2 bloqueantes · 8 importantes · 25 menores.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` (12 procesos) | ✅ 868 pasan, 0 fallos (línea base de `/implement`: 799/800 en serie y fallos intermitentes en paralelo; esta pasada, ninguno) |
| Tests nuevos de la spec (`tests/Modules/Core`, `Email`, `whatsApp`), dos veces | ✅ 68/68 y 68/68 |
| `vendor/bin/pint --test` sobre los 40 archivos PHP del rango | ❌ 1 archivo: `tests/Modules/Core/Integration/HealthCheckTest.php` (`fully_qualified_strict_types`, `ordered_imports`) → R2. El resto del repositorio tiene 263 archivos sin formatear anteriores a la spec (línea base) |
| `npm run build` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/015-primer-despliegue-vps` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ComposeFilesTest` (8000, vite en loopback, sin TLS); T046 en local | ✅ | |
| CA2 | `ComposeFilesTest`; `verify.sh --local` `image_is_clean` | ✅ | |
| CA3 | `WhatsAppFlowLogsTest`, `PasswordResetEmailTest`, `ProvidersReadConfigTest`; T052 | ✅ | WhatsApp solo con tests (decisión del usuario, 2026-10-02) |
| CA4 | `verify.sh --remote` (301, HSTS); T051 | ⚠ | Se comprueba a través de Cloudflare y solo para el dominio sin `www` (R13) |
| CA5 | `SecurityHeadersTest`; `verify.sh --remote`; T046, T052 | ⚠ | Los archivos que nginx sirve directamente (`/storage`) salen sin `nosniff` ni `X-Frame-Options` (R8) |
| CA6 | `CorsTest`; `verify.sh --remote` | ✅ | |
| CA7 | `WebUnexpectedErrorTest`; `verify.sh --local` (`APP_DEBUG`) | ✅ | |
| CA8 | `EnvExampleDefaultsTest` | ✅ | |
| CA9 | `ComposeFilesTest`; `verify.sh --remote` (puertos) | ✅ | Un fallo del propio comando cuenta como "cerrado" (R12) |
| CA10 | `ComposeFilesTest`, `NoEnvOutsideConfigTest`; gitleaks | ✅ | |
| CA11 | `HealthCheckTest`; T052 (1,7 s y 1,3 s) | ✅ | |
| CA12 | `verify.sh --local` (backup reciente, permisos, rotación); T053 | ⚠ | La rotación conserva 8 días, no 7 (R15); dentro de un deploy el check de backup reciente siempre pasa (R9) |
| CA13 | T051, T054, T058; `ProdScriptsTest` | ⚠ | El rollback sin argumentos elige mal la versión si el deploy falló en la migración (R4) o tras redesplegar el mismo tag (R5) |
| CA14 | `composer audit`, `npm audit` | ✅ | 0 avisos en esta ronda |
| CA15 | `verify.sh --local` (formato de la última línea); T051, T054 | ⚠ | El check valida la línea del backup, no la de la operación (R9) |
| CA16 | `TrustedProxiesTest`, `CloudflareRangesTest`; T052 | ⚠ | El test prueba una topología que en producción no ocurre (R7) |
| CA17 | `TrustedProxiesTest`; `verify.sh --remote` (429 saltándose el proxy) | ✅ | |
| CA18 | `WhatsAppFlowLogsTest`, `PasswordResetEmailTest` | ✅ | La lista de datos buscados no incluye email, fecha ni hora (R17) |
| CA19 | `PasswordResetEmailTest`; T052 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin justificar. `scope.md` lista seis archivos sin tarea que los cite por ruta: `.dockerignore`, `.env.example`, `BrevoApi.php` y `TwilioConection.php` sí tienen tarea (T033, T040, T029, T028), citados con ruta abreviada; `.gitignore` y `tests/Modules/Core/Unit/ProdScriptsTest.php` son correcciones de los ensayos, anotadas en T058, T051 y T054.
- Partes del plan sin implementar: ninguna. T001, T042, T056 y T057 no tienen diff de código porque son línea base y documentación.
- Añadido durante los ensayos y anotado en las tareas: `dns_opt` en `app`, `dentissa_storage-public` como volumen externo, reintento de `/up` en `verify.sh` y restauración atómica en `restore.sh`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes de código | ✅ | commits `feat(015)`, `fix(015)` con tarea |
| P2 Test antes de implementación | ✅ | tests de Core, Email y whatsApp; los de scripts son solo de texto (R10) |
| P3 Arquitectura modular | ✅ | `app/Core/{Health,Middlewares}`; adaptadores en `Infrastructure` |
| P4 Contrato de API | ➖ | sin endpoints nuevos |
| P5 Autorización | ➖ | sin cambios de permisos |
| P6 Validación | ➖ | sin endpoints nuevos |
| P7 Errores sin detalles internos | ✅ | `WebUnexpectedErrorTest`; `/up` nombra la dependencia caída (R24, menor) |
| P8 Secretos fuera del repositorio | ❌ | `REDIS_TIMEOUT` y `REDIS_READ_WRITE_TIMEOUT` no están en `.env.example` (R1) |
| P9 Migraciones reversibles y compatibles | ⚠ | la spec no añade migraciones; `deploy.sh` migra después de servir el código nuevo (R6) |
| P10 Dependencias nuevas con ADR | ✅ | sin paquetes nuevos; `symfony/yaml` se usa como transitiva (R27, menor) |
| P11 Datos sensibles y modelo de amenazas | ✅ | logs sin datos personales; TM1–TM15 con control |
| P12 Producción con aprobación y rollback | ✅ | ensayos T051 y T054 aprobados por el usuario |
| P13 Estilo | ❌ | Pint falla en `HealthCheckTest.php` (R2) |
| P14 Trazabilidad | ✅ | `deploys.log`; sin `request_id`, desviación ya aceptada hasta el objetivo 5 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`131ea14..HEAD`, 25 commits) | ✅ sin fugas |
| semgrep `p/php` (`app`, `config`, `bootstrap`, `routes`; `--metrics=off`) | ✅ 499 archivos, 23 reglas, 0 hallazgos |
| `composer audit` / `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config `docker/` (HIGH, CRITICAL) | ✅ 0 |
| shellcheck `docker/prod/*.sh` (en `/implement`) | ✅ |

| Tema OWASP (Top 10 2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ➖ | sin cambios |
| A02 Configuración de seguridad | ⚠ | estáticos sin cabeceras (R8); sin `default_server` (R25); contraseña de Redis en la línea de comandos (R20); `docker.sock` en Alloy (R21) |
| A03 Cadena de suministro | ✅ | lockfiles: actualizaciones sin paquetes nuevos; imágenes de datos fijadas solo a versión mayor (R22) |
| A04 Fallos criptográficos | ✅ | TLS, HSTS, Full (strict); backups sin cifrar y solo en el droplet (R23) |
| A05 Inyección | ✅ | CSP con nonce; `--origin` sin validar en `verify.sh` (R12) |
| A07 Autenticación | ⚠ | la IP del límite de peticiones se resuelve dos veces (R7) |
| A09 Registro y alertas | ✅ | logs sin datos personales; `deploys.log` editable por `deploy` (R19) |
| A10 Manejo de errores | ✅ | 500 genérico; `restore.sh` sin copia previa (R18) |

Sin texto dirigido a agentes en el diff. Controles TM1, TM2, TM5, TM7, TM9, TM11, TM12, TM13 y TM15
presentes y probados; TM3, TM4, TM6, TM8, TM10 y TM14, con las observaciones de los hallazgos.

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: la spec no añade eventos de auditoría; `health.dependency_failed` se emite y `HealthCheckTest` lo prueba.
- Datos sensibles en logs del código tocado: ninguno (`WhatsAppFlowLogsTest`, `PasswordResetEmailTest`; comprobado en producción en T052).
- `request_id` presente en los logs nuevos: no; desviación de P14 ya aceptada hasta la spec del objetivo 5.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `config/database.php:164-165,180-181`; `.env.example:48-51` | P8: `REDIS_TIMEOUT` y `REDIS_READ_WRITE_TIMEOUT` son variables nuevas leídas con `env()` y no están en `.env.example`. `EnvExampleDefaultsTest` no lo detecta | Añadirlas a `.env.example` y al test |
| R2 | bloqueante | `tests/Modules/Core/Integration/HealthCheckTest.php` | Pint falla en un archivo de la spec (`fully_qualified_strict_types`, `ordered_imports`) | `vendor/bin/pint` sobre el archivo |
| R3 | importante | `docker/prod/verify.sh:104` | `disk_has_room` compara cadenas: tras `gsub`, `$5 < 80` es lexicográfico. Reproducido: 9 % → FAIL, 100 % → ok. Con el disco lleno pasa, y por debajo del 10 % hace fallar `deploy.sh` y `rollback.sh` | `exit (($5 + 0) < 80) ? 0 : 1` |
| R4 | importante | `docker/prod/deploy.sh:78-92`; `docker/prod/rollback.sh:55-59` | `.deploy/current` y `.deploy/previous` se escriben después de `migrate`. Si la migración falla, corre el tag nuevo con el estado antiguo, y `rollback.sh` sin argumentos vuelve a la versión anterior a la que estaba en servicio | Escribir el estado justo después del `up`, antes de migrar |
| R5 | importante | `docker/prod/deploy.sh:74,97-104` | Al redesplegar el mismo tag, `PREVIOUS == TAG` y la limpieza borra las imágenes de la versión que figura en `.deploy/previous`; `rollback.sh` falla después con "the images of X are not on this machine" | Conservar también el tag de `.deploy/previous` |
| R6 | importante | `docker/prod/deploy.sh:78,86` | El código nuevo empieza a servir antes de migrar. P9 garantiza esquema nuevo con código anterior, no al revés: ventana de errores en cada deploy con migración | Migrar con la imagen nueva (`compose.sh run --rm app php artisan migrate --force`) antes del `up -d` |
| R7 | importante | `docker/nginx/prod.conf:29`; `app/Core/Middlewares/TrustCloudflareProxies.php`; `tests/Modules/Core/Integration/TrustedProxiesTest.php` | La IP se resuelve dos veces con la misma lista: nginx ya pone la IP del visitante en `REMOTE_ADDR` y Laravel vuelve a compararla con los rangos de Cloudflare. Si la IP declarada cae en esos rangos (subpeticiones de Workers), Laravel confía en `X-Forwarded-For`, que el cliente controla. `TrustedProxiesTest` prueba `REMOTE_ADDR` = nodo de Cloudflare, que en producción no ocurre. Impacto no reproducido | Resolver en una sola capa: `fastcgi_param HTTP_X_FORWARDED_FOR $remote_addr;` en `prod.conf`, y un test con la topología real |
| R8 | importante | `docker/nginx/prod.conf:81-83` | Los archivos que nginx sirve directamente desde `location /` (entre ellos `/storage/*`, archivos subidos) salen sin `X-Content-Type-Options` ni `X-Frame-Options`; solo `/build/` y PHP las llevan. Ni `SecurityHeadersTest` ni `verify.sh` miran una respuesta estática | Añadir `nosniff` y `X-Frame-Options DENY` a nivel de `server` y repetirlas en cada `location` con `add_header`; comprobar `/storage/login.jpg` en `verify.sh --remote` |
| R9 | importante | `docker/prod/verify.sh:97-101,117,120,140,191` | Comprobaciones que no pueden fallar: (a) "nginx version is not sent" va por Cloudflare, que siempre responde `server: cloudflare`; (b) `lacks_header` pasa si `curl` falla; (c) el check de `deploys.log` valida la línea del backup previo, porque `verify.sh` corre antes del `log_operation` final; (d) dentro de un deploy siempre existe el dump `pre-<tag>` | (a) y (b): consultar el origen con `--resolve` y exigir que haya cabeceras; (c): registrar antes de verificar o comprobar acción, versión y resultado; (d): exigir un `daily-*` reciente cuando se ejecuta fuera de un deploy |
| R10 | importante | `tests/Modules/Core/Unit/ProdScriptsTest.php:35-36,46-50`; `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php:120`; `tests/Modules/Email/Integration/PasswordResetEmailTest.php:130` | Tests que no prueban lo que dicen: los de scripts buscan texto en el archivo completo (pasan con la cadena en un comentario; `health_answers` pasa con `seq 1 1`), y los casos "rechazado por el proveedor" solo afirman que el log no está vacío. R3 es la consecuencia: ningún umbral de `verify.sh` se ejecuta en CI | Ejecutar en CI las funciones puras de `verify.sh` con rutas y `df` simulados, excluir comentarios en las búsquedas, y afirmar la línea concreta del rechazo |
| R11 | menor | `docker/prod/verify.sh:166` | Sin `timeout` instalado (macOS), el check de puertos pasa sin probar nada | Comprobar `command -v timeout` |
| R12 | menor | `docker/prod/verify.sh:166,213` | `--origin` se interpola en `bash -c` sin validar, y cualquier fallo (sin `timeout`, IP mal escrita) cuenta como "puerto cerrado" | Validar la IP y pasar host y puerto como parámetros |
| R13 | menor | `docker/prod/verify.sh:130-134,184` | El 301 y HSTS se comprueban a través de Cloudflare y solo para el dominio sin `www`; el `return 301` del origen queda sin verificar | Repetir con `--resolve` al origen y para `www` |
| R14 | menor | `docker/prod/verify.sh:195` | `/backups/` → 404 no demuestra nada (cualquier ruta inexistente da 404); el control real es estructural | Comprobar en `--local` que `BACKUP_DIR` no está montado en nginx |
| R15 | menor | `docker/prod/backup.sh:55`; `docker/prod/verify.sh:94` | `-mtime +7` conserva hasta 8 dumps diarios; los `pre-<tag>` no se rotan nunca | `-mtime +6` y una retención para los `pre-*` |
| R16 | menor | `docker/prod/verify.sh:196` | La verificación depende de `/storage/login.jpg`, un archivo de datos que se puede cambiar desde el CMS | Archivo centinela o ruta configurable |
| R17 | menor | `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php:58` | La lista de datos buscados en los logs no incluye el email de contacto ni la fecha y hora de la cita (variables de la plantilla) | Añadirlos |
| R18 | menor | `docker/prod/restore.sh:54`; `docker/prod/rollback.sh:31,64` | `restore.sh` no hace copia del estado actual antes del `DROP SCHEMA`; `rollback.sh` hace `cd` antes de validar una ruta relativa de `--restore` | `backup.sh pre-restore` antes de borrar; resolver la ruta antes del `cd` |
| R19 | menor | `docker/prod/lib.sh:21-34`; `docker/prod/restore.sh:32` | `deploys.log` es de solo anexado por convención, y `DUMP_NAME` se escribe en el registro sin validar | `chattr +a` o enviarlo a Loki; validar el nombre como en `backup.sh` |
| R20 | menor | `docker-compose.prod.yml:100` | La contraseña de Redis va en la línea de comandos (`--requirepass`), visible con `docker inspect` | Leerla del entorno con `sh -c` o de un archivo |
| R21 | menor | `docker-compose.prod.yml:135,146` | Alloy monta `docker.sock` (equivale a root en el host); sin `no-new-privileges`; provisioning de Grafana con escritura | Proxy del socket o logs en solo lectura; `security_opt`; `:ro` |
| R22 | menor | `docker-compose.prod.yml:78,97`; `tests/Modules/Core/Unit/ComposeFilesTest.php` | `postgres:16` y `redis:7-alpine` fijadas solo a versión mayor; el test de imágenes fijadas solo mira el Dockerfile | Fijar a menor (igual en los dos Compose) y extender el test |
| R23 | menor | `docker/prod/backup.sh:43-47` | Los dumps quedan sin cifrar y solo en el droplet | Cifrar y copiar fuera antes de datos reales |
| R24 | menor | `app/Core/Health/CheckDependenciesOnHealth.php:40,46-48` | `/up` es público y sin límite, y el 500 nombra la dependencia caída; `probePostgres` asume `host` y `port` escalares | Mensaje único genérico; `getConfig()` resuelto |
| R25 | menor | `docker/nginx/prod.conf:50-52` | Sin `default_server` que rechace `Host` desconocidos; 80 y 443 del origen aceptan cualquier IP | `default_server` con `return 444`; limitar a rangos de Cloudflare |
| R26 | menor | `app/Core/Middlewares/SecurityHeaders.php:48` | `style-src 'unsafe-inline'` | Planificar su retirada |
| R27 | menor | `tests/Modules/Core/Unit/ComposeFilesTest.php:5` | Usa `symfony/yaml`, que llega solo como transitiva de `laravel/sail` | Declararla en `require-dev` |
| R28 | menor | `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php:26`; `tests/Modules/Core/Unit/ProvidersReadConfigTest.php:44` | La API key queda como propiedad pública solo para el test, que no comprueba la clave del cliente | No guardarla y verificar el cliente |
| R29 | menor | `tests/Modules/Core/Unit/ComposeFilesTest.php:53,141` | El filtro de credenciales no cubre `*_AUTH` ni `*_KEY`; el test de `.dockerignore` no exige `.env.*` | Ampliar ambos |
| R30 | menor | `docker/prod/deploy.sh:99-101` | La limpieza de imágenes corre con el trap `ERR`: un `docker image rm` fallido registra como `failed` un deploy ya verificado | Tolerar el fallo o moverla después del registro |
| R31 | menor | `app/Modules/Auth/Infrastructure/Http/Controllers/SendResetPasswordEmailController.php:35` | El 500 de un archivo tocado devuelve un texto propio en vez del genérico (línea anterior a la spec) | Unificar con `UnexpectedErrorResponse` |
| R32 | menor | `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php:17` | Usa la base de Appointments; no hay `WhatsAppIntegrationTestCase` | Crearla |
| R33 | menor | `tests/Modules/Core/Integration/WebUnexpectedErrorTest.php:27` | `assertStatus(500)` en lugar del aserto específico | `assertInternalServerError()` |
| R34 | menor | `docker/prod/deploy.sh:99` | Al redesplegar el mismo tag, el mensaje dice "ensayo-1 and ensayo-1" | Mostrar `.deploy/previous` |
| R35 | menor | `docker/prod/restore.sh:54` | `DROP SCHEMA` escribe un NOTICE por tabla en la consola | `client_min_messages=warning` |

Sin hallazgos de accesibilidad: el diff de las siete vistas solo añade el nonce a los `<script>`.

## Preparación para release
- Rollback factible: sí, ensayado (T051, T054, T058), con las salvedades de R4 y R5, que afectan a `rollback.sh` sin argumentos tras un deploy fallido o repetido.
- Migraciones compatibles con la versión anterior: la spec no añade migraciones; R6 afecta a las de specs futuras.
- Feature flags: `CSP_REPORT_ONLY`, por defecto `false` (seguro), probado en T052.
- Docs actualizadas (architecture, deployment): sí (T055, T090, T091). `deployment.md` describe la restauración atómica y los tiempos medidos.
- SCA: sin vulnerabilidades críticas ni altas; EX1 cerrada.
- Cobertura de riesgos: los estados de `security.md`, `observability.md` y `deployment.md` van por corrección; ningún riesgo figura como mitigado con correcciones pendientes (OB10.b sigue pendiente; OB7 y OB8, fuera de la spec).

## Tareas añadidas
Decisión del usuario (2026-10-04): corregir los 2 bloqueantes, los 8 importantes y los menores que
tocan los mismos archivos.
- T059 ← R2
- T062, T063 ← R1
- T064, T065 ← R3, R9, R10, R11, R12, R15
- T066, T067, T068 ← R4, R5, R6, R9, R10, R18, R30, R34, R35
- T069, T070 ← R7, R8
- T071 ← R7, R8, R9, R13, R14
- T072 ← R10, R17
- T073 ← R10, R29, R33
- T074 ← R4, R6, R18 (documentación)
- T075, T076 ← verificación y ensayo en el droplet (R1–R10)

## Aceptados sin tarea
Decisión del usuario (2026-10-04): estos menores no se corrigen en esta spec y pasan al roadmap.
- **R15** → roadmap: retención de los dumps `pre-<tag>`, que hoy no se rotan (la rotación de los diarios sí se corrige en T065).
- **R16** → roadmap: `verify.sh` depende de `/storage/login.jpg`, un archivo de datos editable desde el CMS.
- **R19** → roadmap: `deploys.log` es de solo anexado por convención; `chattr +a` o envío a Loki.
- **R20** → roadmap: la contraseña de Redis va en la línea de comandos del contenedor.
- **R21** → roadmap: Alloy monta `docker.sock`; sin `no-new-privileges`; provisioning de Grafana con escritura.
- **R22** → roadmap: imágenes de datos fijadas solo a versión mayor, y el test de imágenes fijadas no mira los Compose.
- **R23** → roadmap: backups sin cifrar y solo en el droplet (antes de datos reales).
- **R24** → roadmap: `/up` nombra la dependencia caída y no tiene límite; `probePostgres` asume `host` escalar.
- **R25** → roadmap: sin `default_server` en nginx; 80 y 443 del origen abiertos a cualquier IP.
- **R26** → roadmap: `style-src 'unsafe-inline'` en la CSP.
- **R27** → roadmap: `symfony/yaml` usada como dependencia transitiva en un test.
- **R28** → roadmap: la API key de Brevo queda como propiedad pública del adaptador.
- **R31** → roadmap: el 500 de `SendResetPasswordEmailController` no usa la respuesta genérica.
- **R32** → roadmap: falta `WhatsAppIntegrationTestCase` (el flujo de WhatsApp va a cambiar).
