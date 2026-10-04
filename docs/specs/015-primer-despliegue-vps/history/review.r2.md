---
spec: 015-primer-despliegue-vps
verdict: changes_requested
round: 2
date: 2026-10-04
base: e175328
head: eda2743
human_signoff: pending
---

# Review · 015 Primer despliegue reproducible en el VPS

Ronda 2 (`--rerun`) sobre la ronda 1 ([review.r1.md](../history/review.r1.md)). Revisa los hallazgos
que se decidió corregir (R1–R15, R17, R18, R29, R30, R33–R35), el diff `e175328..eda2743` (21
archivos de código, 64 KB) y la verificación automática completa. Un revisor independiente de solo
lectura con los encargos A, C+D y S; cada hallazgo se verificó abriendo el archivo. No reabre los
menores aceptados en la ronda 1.

## Resumen
**changes_requested.** Las correcciones de la ronda 1 están hechas y verificadas, y el ensayo en
el droplet (T076) pasó con el orden nuevo. Pero dos de esas correcciones introdujeron fallos
propios en `deploy.sh`: la migración previa pasa por el entrypoint de la imagen, que recompila las
vistas en el volumen que comparte con la versión en servicio (R36), y el estado se escribe antes
de verificar, de modo que una versión no verificada puede acabar como destino del rollback (R38).
Hay además una carrera en el límite de la rotación de backups (R37) y dos comprobaciones de R7 que
no fallarían sin la corrección (R39).

Conteo de la ronda: 0 bloqueantes · 4 importantes · 7 menores.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` (12 procesos) | ⚠ 905/905 en dos pasadas y **27 fallos en otra** (`relation "roles" does not exist` y similares, en módulos que el diff no toca). Es la carrera de `--parallel` al preparar las bases de test, ya en la línea base de `/implement` y en el roadmap (Pendientes y deuda); no la introduce esta spec |
| `./vendor/bin/pest` en serie | ✅ 905/905 (línea base de `/implement`: 799/800 en serie) |
| Tests nuevos de la spec (`tests/Modules/Core`, `Email`, `whatsApp`), dos veces | ✅ 105/105 y 105/105 |
| `vendor/bin/pint --test` sobre los 44 PHP de `131ea14..HEAD` | ✅ |
| `npm run build` | ✅ |
| `shellcheck -x docker/prod/*.sh` (8 scripts) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/015-primer-despliegue-vps` | ✅ 0 errores |

## Hallazgos de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `.env.example:52-54`; `EnvExampleDefaultsTest` deriva las variables comparando con el archivo de fábrica |
| R2 | corregido | Pint pasa sobre los 44 PHP del rango |
| R3 | corregido | `verify.sh:129`; casos 9, 79, 80 y 100 % |
| R4 | corregido, con efecto nuevo (R38) | `deploy.sh:83` → `:86-92` → `:95` |
| R5 | corregido | `deploy.sh:107-117` |
| R6 | corregido, con problema nuevo (R36) | `deploy.sh:83` |
| R7 | corregido en nginx; tests débiles (R39) | `prod.conf:99`; comprobado en el droplet (T076) |
| R8 | corregido | `prod.conf:63-65,80-84,101-102`; en el origen, `/storage/login.jpg` y `/login` con cada cabecera una vez (T076) |
| R9 | corregido | `verify.sh:184-191,199-201,279-280`; `lib.sh:38-44` |
| R10 | corregido | `VerifyScriptBehaviourTest`, `DeployScriptsBehaviourTest`, `ProdScriptsTest`; solo el SQL de `restore.sh` se comprueba leyendo (necesita PostgreSQL) |
| R11–R14 | corregidos | `verify.sh:238,265-269,168-173,106-115` |
| R15 | parcial | Umbral corregido; queda la carrera del límite (R37). La retención de los `pre-*` ya estaba aceptada |
| R17, R18 | corregidos | `CreateAppointmentController.php:29-32`; `restore.sh:35,50-52`; `rollback.sh:32-35` |
| R29, R30, R33, R34, R35 | corregidos | `ComposeFilesTest.php:53`; `deploy.sh:112-114`; `WebUnexpectedErrorTest.php:27`; `restore.sh:64` |

## Criterios de aceptación
Sin cambios respecto a la ronda 1, salvo los que tenían observación:

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA4 | `verify.sh --remote` (origen y `www`) | ✅ | 30/30 contra el sitio (T079) |
| CA5 | `NginxProdConfTest`; `verify.sh --remote` | ✅ | estáticos con cabeceras en el origen y a través de Cloudflare |
| CA9 | `VerifyScriptBehaviourTest` | ✅ | `--origin` validado; sin `timeout`, falla |
| CA12 | `VerifyScriptBehaviourTest`; T076 | ⚠ | carrera en el límite de la rotación (R37) |
| CA13 | `DeployScriptsBehaviourTest`; T076 | ⚠ | vistas recompiladas antes de cambiar de versión (R36); versión no verificada como `previous` (R38) |
| CA15 | `DeployScriptsBehaviourTest` (`assert_logged`) | ✅ | |
| CA16 | `NginxProdConfTest`; T076 | ⚠ | dos comprobaciones no fallarían sin la corrección (R39) |
| CA18 | `WhatsAppFlowLogsTest`, `PasswordResetEmailTest` | ✅ | fecha y hora fuera del log (T077) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` señala `.env.example` sin tarea que lo cite por ruta: es T063.
- Tareas añadidas durante `/implement` con decisión del usuario: T077 (fecha y hora en el log de citas) y T078–T079 (comprobación de estáticos contra el origen y purga de la caché de Cloudflare).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test antes de implementación | ⚠ | los casos nuevos fallaron antes de implementar y se comprobaron rompiendo lo protegido; el `-delete` de `backup.sh` no lo ejecuta ningún test (R37) y dos casos de R7 no distinguen la corrección (R39) |
| P7 Errores sin detalles internos | ✅ | |
| P8 Secretos fuera del repositorio | ✅ | R1 corregido; sin `env()` fuera de `config/` |
| P9 Migraciones compatibles | ✅ | `deploy.sh` migra antes de cambiar de versión |
| P10 Dependencias nuevas con ADR | ✅ | `bash` solo en la etapa `dev` de la imagen; no es paquete de Composer ni npm |
| P11 Datos sensibles | ✅ | el log de citas ya no lleva fecha ni hora |
| P13 Estilo | ✅ | Pint y `shellcheck` limpios |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`131ea14..HEAD`) | ✅ sin fugas |
| semgrep `p/php` | ✅ 0 hallazgos en la ronda 1 (499 archivos); el único PHP de aplicación tocado después es `CreateAppointmentController.php`, donde se quitaron dos claves de un log |
| `composer audit` / `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config `docker/` | ✅ 0 |

| Tema OWASP (Top 10 2025) | Resultado | Evidencia |
|---|---|---|
| A02 Configuración de seguridad | ✅ | cabeceras en todo lo que sirve nginx; `X-Forwarded-Proto` aún confiado (R40, menor) |
| A05 Inyección | ✅ | `--origin` validado y pasado como parámetro posicional |
| A07 Autenticación | ✅ | IP resuelta una vez en nginx; 429 con IP falsificada por el origen y a través de Cloudflare (T076) |
| A09 Registro y alertas | ✅ | logs sin fecha ni hora de la cita; `assert_logged` |
| A10 Manejo de errores | ✅ | `restore.sh` con copia previa y transacción única |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: la spec no añade eventos de auditoría.
- Datos sensibles en logs del código tocado: ninguno; el test busca ahora también email de contacto, contacto de emergencia, fecha y hora.
- `request_id` presente en los logs nuevos: no; desviación ya aceptada hasta el objetivo 5.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R36 | importante | `docker/prod/deploy.sh:83`; `docker/prod/entrypoint.sh:7`; `docker-compose.prod.yml:31` | `compose.sh run --rm app …` pasa por el entrypoint, que ejecuta `php artisan optimize` (incluye `view:cache`) con la imagen nueva. Las vistas compiladas van a `storage/framework/views`, en el volumen `storage` que comparte con el `dentissa-app` en servicio, y `opcache.validate_timestamps=1`: la versión anterior sirve vistas compiladas de la nueva mientras dura la migración, y de forma indefinida si la migración falla. Contradice el propio comentario de `deploy.sh:9-10`. Por lectura; el doble de `compose.sh` no modela el entrypoint | `compose.sh run --rm --entrypoint php app artisan migrate --force`, con ese argumento fijado en el test |
| R37 | importante | `docker/prod/backup.sh:56`; `docker/prod/verify.sh:119` | La rotación y la comprobación usan el mismo umbral (`-mtime +6`). A las 03:00 el dump de hace 7 días tiene 7 días ± segundos: si queda justo por debajo sobrevive, y `verify.sh --local` falla horas después hasta la rotación siguiente; dentro de un deploy o rollback eso registra `result=failed` con la aplicación sana. Además ningún test ejecuta el `-delete` de `backup.sh` | Dar un día de margen a la comprobación (`-mtime +7`) y un test que ejecute `backup.sh daily` con dumps envejecidos |
| R38 | importante | `docker/prod/deploy.sh:86-92,107-117` | El estado se escribe antes de `up` y `verify`. Si el deploy de v3 falla ahí, queda `current=v3`, `previous=v2`; un deploy posterior de v4 sin rollback previo pone `previous=v3` (nunca verificada) y la limpieza borra las imágenes de v2, la última buena | Escribir `.deploy/verified` tras `verify.sh` y tomar de ahí la versión que pasa a `previous` |
| R39 | importante | `tests/Modules/Core/Integration/TrustedProxiesTest.php:67-76`; `docker/prod/verify.sh:254-261,298` | Ni el caso nuevo de `TrustedProxiesTest` ni `forged_forwarded_ip_is_ignored_through_proxy` fallarían si se quitara `fastcgi_param HTTP_X_FORWARDED_FOR`: el primero manda la cabecera igual a `REMOTE_ADDR`, y el segundo ya pasaba antes de R7. R7 queda protegido solo por `NginxProdConfTest` | Un caso con `REMOTE_ADDR` en rango de Cloudflare y `X-Forwarded-For` distinto, que documente por qué nginx debe sobrescribirla; y que la descripción del check de `verify.sh` diga lo que prueba (CA16) |
| R40 | menor | `docker/nginx/prod.conf:99`; `app/Core/Middlewares/TrustCloudflareProxies.php:28-31` | Solo se fija `X-Forwarded-For`; un cliente con IP en rangos de Cloudflare aún puede enviar `X-Forwarded-Proto: http` | `fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;` |
| R41 | menor | `docker/prod/deploy.sh:88-92,113-114`; `docker/prod/verify.sh:211`; `tests/Modules/Core/Unit/VerifyScriptBehaviourTest.php:271-276` | Saltos de línea literales donde había `\n` y `\r\n`, y una continuación de línea perdida. Funciona igual; es una sustitución accidental | Restaurar `printf '%s\n'` y los `\r\n` |
| R42 | menor | `tests/Modules/Core/Unit/DeployScriptsBehaviourTest.php:72-85` | El entorno de los procesos no neutraliza `SUDO_USER` ni `GIT_DIR`, `GIT_WORK_TREE`, `GIT_INDEX_FILE`: bajo `sudo` falla, y desde un hook de git operaría sobre el repositorio real | Poner esas variables a `false` en el entorno del `Process` |
| R43 | menor | `docker/prod/verify.sh:26,92-98`; `docker/prod/rollback.sh:95` | `rollback.sh` sin `--restore` no crea ningún dump, pero el comentario de `--in-operation` dice que sí | Corregir el comentario |
| R44 | menor | `docker/prod/lib.sh:38-44` | `assert_logged` es casi tautológico y podría fallar en falso si el cron de backups escribe su línea entre las dos llamadas | Buscar la línea propia entre las últimas en lugar de exigir que sea la última |
| R45 | menor | `docker/prod/verify.sh:112-113` | La comparación de rutas de `backups_are_not_mounted_in_nginx` no normaliza barras finales ni enlaces simbólicos | Quitar la barra final o `realpath -m` |
| R46 | menor | `docker/prod/backup.sh:54-57`; `docker/prod/restore.sh:50-52` | Los `pre-restore-*` nuevos tampoco se rotan, como los `pre-<tag>` (R15, ya aceptado) | Misma retención que se decida para los `pre-*` |

## Preparación para release
- Rollback factible: sí, ensayado en el droplet con el orden nuevo (T076: deploy 1 min 38 s, rollbacks 13,1 s y 11,9 s). R36 y R38 afectan a un deploy que falla a mitad, no al camino normal.
- Migraciones compatibles con la versión anterior: la spec no añade migraciones; `deploy.sh` migra antes de cambiar de versión.
- Feature flags: `CSP_REPORT_ONLY`, por defecto `false`.
- Docs actualizadas: sí (`deployment.md` con el orden nuevo, las comprobaciones y la purga de la caché de Cloudflare; `CHANGELOG.md`).
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: sin cambios de estado en esta ronda.

## Tareas añadidas
Decisión del usuario (2026-10-04): corregir los 4 importantes y los menores R40–R43 y R45.
- T080, T081 ← R36, R37, R38, R41, R42
- T082, T083 ← R37, R39, R41, R43, R45
- T084, T085 ← R39, R40
- T086 ← R36, R38 (documentación)
- T087, T088 ← verificación y ensayo en el droplet (R36–R43)

## Aceptados sin tarea
De la ronda 2 (decisión del usuario, 2026-10-04):
- **R44** → roadmap: `assert_logged` exige que la operación sea la última línea de `deploys.log`; el cron de backups podría escribir entre medias.
- **R46** → roadmap: los dumps `pre-restore-*` no se rotan (misma retención pendiente que los `pre-<tag>`, R15).

De la ronda 1 (decisión del usuario, 2026-10-04):
- **R15** → roadmap: retención de los dumps `pre-<tag>`, que hoy no se rotan.
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
