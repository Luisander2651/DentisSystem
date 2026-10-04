---
spec: 015-primer-despliegue-vps
verdict: approved
round: 3
date: 2026-10-04
base: eda2743
head: 0543154
human_signoff: Luisander2651 (2026-10-04)
---

# Review · 015 Primer despliegue reproducible en el VPS

Ronda 3 (`--rerun`) sobre la ronda 2 ([review.r2.md](history/review.r2.md); la ronda 1 está en
[review.r1.md](history/review.r1.md)). Revisa los hallazgos que se decidió corregir (R36–R43 y
R45), el diff `eda2743..0543154` (8 archivos de código, 18,5 KB) y la verificación automática. Con
un diff de ese tamaño la revisión se hizo sin subagentes, releyendo los archivos desde el disco y
no desde la memoria de la sesión. No reabre los menores aceptados en las rondas anteriores.

## Resumen
**approved.** Confirmación humana: Luisander2651, 2026-10-04. Los cuatro
importantes de la ronda 2 están corregidos y probados: la migración previa corre sin el arranque
de la imagen, el destino del rollback solo cambia cuando una versión pasa la verificación, la
comprobación de la rotación tiene un día de margen y el borrado se ejecuta en un test, y hay un
caso que documenta el riesgo que cierra la línea de nginx. El ensayo en el droplet (T088) pasó con
30/30 en la verificación remota. Quedan dos menores nuevos, sin impacto en el camino normal.

Conteo de la ronda: 0 bloqueantes · 0 importantes · 2 menores.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` en serie | ✅ 911/911 (T087, sobre el mismo código; después solo cambió documentación) |
| `./vendor/bin/pest --parallel` | no se usa como referencia: tiene la carrera de la línea base al preparar las bases de test (ronda 2; roadmap, Pendientes y deuda) |
| Tests nuevos de la spec (`tests/Modules/Core`, `Email`, `whatsApp`), dos veces | ✅ 111/111 y 111/111 |
| `vendor/bin/pint --test` sobre los PHP de `131ea14..HEAD` | ✅ |
| `npm run build` | ✅ |
| `shellcheck -x docker/prod/*.sh` (8 scripts) | ✅ |
| `nginx -t` sobre `docker/nginx/prod.conf` (`nginx:1.30-alpine`, certificado de prueba) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/015-primer-despliegue-vps` | ✅ 0 errores |

## Hallazgos de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R36 | corregido | `deploy.sh:92` (`compose.sh run --rm --entrypoint php app artisan migrate --force`); caso "migrates with the new image…" fija ese argumento; ejecutado en el droplet (T088) |
| R37 | corregido | `verify.sh` comprueba con `-mtime +7` y `backup.sh:56` borra con `-mtime +6`; casos "reports a daily backup the rotation should have deleted…" y "deletes the daily dumps of seven full days…" (este ejecuta `backup.sh daily`; comprobado rompiendo el borrado) |
| R38 | corregido | `deploy.sh:81-84,110`; `rollback.sh:97`; caso "only promotes a verified version…": tras un deploy que falla la verificación y otro posterior, `previous` sigue en la última versión verificada y sus imágenes no se borran |
| R39 | corregido | `TrustedProxiesTest` documenta que Laravel, solo, toma el `X-Forwarded-For` de una conexión en rango de Cloudflare; `NginxProdConfTest` protege la línea que lo impide; el check de `verify.sh` dice lo que prueba |
| R40 | corregido | `prod.conf` (`fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;`); caso en `NginxProdConfTest`; el sitio sigue en https (T088) |
| R41 | corregido | sin saltos de línea literales en `deploy.sh`, `verify.sh` ni en el test |
| R42 | corregido | `DeployScriptsBehaviourTest`: `SUDO_USER`, `GIT_DIR`, `GIT_WORK_TREE` y `GIT_INDEX_FILE` a `false` |
| R43 | corregido | comentario de `--in-operation` en `verify.sh` |
| R45 | corregido | `verify.sh`: `BACKUP_DIR` y cada montaje sin barra final; caso con un subdirectorio montado |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ComposeFilesTest`; T046 | ✅ | |
| CA2 | `ComposeFilesTest`; `verify.sh --local` | ✅ | |
| CA3 | `WhatsAppFlowLogsTest`, `PasswordResetEmailTest`, `ProvidersReadConfigTest`; T052 | ✅ | WhatsApp solo con tests (decisión del usuario, 2026-10-02) |
| CA4 | `verify.sh --remote` (origen y `www`); T088 | ✅ | |
| CA5 | `SecurityHeadersTest`, `NginxProdConfTest`; `verify.sh --remote`; T088 | ✅ | |
| CA6 | `CorsTest`; `verify.sh --remote` | ✅ | |
| CA7 | `WebUnexpectedErrorTest`; `verify.sh --local` | ✅ | |
| CA8 | `EnvExampleDefaultsTest` | ✅ | |
| CA9 | `ComposeFilesTest`, `VerifyScriptBehaviourTest`; `verify.sh --remote` | ✅ | |
| CA10 | `ComposeFilesTest`, `NoEnvOutsideConfigTest`; gitleaks | ✅ | |
| CA11 | `HealthCheckTest`; T052 | ✅ | |
| CA12 | `VerifyScriptBehaviourTest`, `DeployScriptsBehaviourTest`; T053, T088 | ✅ | |
| CA13 | `DeployScriptsBehaviourTest`, `ProdScriptsTest`; T054, T076, T088 | ✅ | |
| CA14 | `composer audit`, `npm audit` | ✅ | `/release` lo repite sobre el tag |
| CA15 | `DeployScriptsBehaviourTest` (`assert_logged`); T088 | ✅ | |
| CA16 | `TrustedProxiesTest`, `NginxProdConfTest`, `CloudflareRangesTest`; T052, T088 | ✅ | |
| CA17 | `TrustedProxiesTest`; `verify.sh --remote` | ✅ | |
| CA18 | `WhatsAppFlowLogsTest`, `PasswordResetEmailTest` | ✅ | |
| CA19 | `PasswordResetEmailTest`; T052 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; `scope.md` no señala archivos sin tarea.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes de código | ✅ | commits con tarea y hallazgo |
| P2 Test antes de implementación | ✅ | los casos nuevos fallaron antes de implementar; los que pasaban desde el principio se comprobaron rompiendo lo protegido o quedan anotados como caracterización |
| P3 Arquitectura modular | ✅ | sin cambios |
| P7 Errores sin detalles internos | ✅ | |
| P8 Secretos fuera del repositorio | ✅ | |
| P9 Migraciones compatibles | ✅ | migración antes de cambiar de versión y sin tocar la versión en servicio |
| P10 Dependencias nuevas con ADR | ✅ | sin dependencias nuevas |
| P11 Datos sensibles | ✅ | |
| P12 Producción con aprobación y rollback | ✅ | ensayos T076 y T088 aprobados por el usuario |
| P13 Estilo | ✅ | Pint y `shellcheck` limpios |
| P14 Trazabilidad | ✅ | `deploys.log`; `request_id` sigue pendiente (objetivo 5) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`131ea14..HEAD`) | ✅ sin fugas |
| semgrep `p/php` | ✅ 0 hallazgos (ronda 1); esta ronda no toca PHP de aplicación |
| `composer audit` / `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config `docker/` | ✅ 0 |

| Tema OWASP (Top 10 2025) | Resultado | Evidencia |
|---|---|---|
| A02 Configuración de seguridad | ✅ | nginx fija la IP y el esquema que pasa a PHP |
| A07 Autenticación | ✅ | 429 con IP falsificada por el origen y a través de Cloudflare (T088) |
| A10 Manejo de errores | ✅ | un deploy fallido no deja un destino de rollback sin verificar |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: la spec no añade eventos de auditoría.
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no; desviación ya aceptada hasta el objetivo 5.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R47 | menor | `docker/prod/verify.sh` (`backups_are_not_mounted_in_nginx`) | Un montaje cuya ruta de origen es `/` no se detecta: al quitar la barra final queda vacío y se salta. Reproducido con un `docker` simulado. Montar la raíz del host en nginx no está en `docker-compose.prod.yml` | Tratar `/` como "contiene los backups" antes de quitar la barra |
| R48 | menor | `docker/prod/rollback.sh:89-92` | Tras volver de una versión que falló, `.deploy/previous` apunta a esa versión: un segundo `rollback.sh` sin argumentos regresa a ella. Es el comportamiento de ida y vuelta de los ensayos y `deploy.sh` ya no lo hereda (usa `.deploy/verified`), pero no está escrito en `deployment.md` | Documentarlo, o hacer que `rollback.sh` sin argumentos use `.deploy/verified` cuando `previous` no esté verificada |

## Preparación para release
- Rollback factible: sí. Ensayado en el droplet con los scripts finales (T088): deploy 2 min 3 s, rollbacks 17,1 s y 24,9 s, `verify.sh --local` y `--remote` (30/30) en verde.
- Migraciones compatibles con la versión anterior: la spec no añade migraciones; `deploy.sh` migra antes de cambiar de versión y sin tocar el volumen de la versión en servicio.
- Feature flags: `CSP_REPORT_ONLY`, por defecto `false`.
- Docs actualizadas: sí (`deployment.md`: orden del deploy, `.deploy/verified`, rotación, purga de la caché de Cloudflare; `CHANGELOG.md`).
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: sin cambios de estado en esta ronda; ningún riesgo figura como mitigado con correcciones pendientes.

## Tareas añadidas
- Ninguna en esta ronda.

## Aceptados sin tarea
De la ronda 3 (decisión del usuario, 2026-10-04):
- **R47** → roadmap: `backups_are_not_mounted_in_nginx` no detecta un montaje cuya ruta de origen sea `/`.
- **R48** → roadmap: tras volver de una versión que falló, un segundo `rollback.sh` sin argumentos regresa a ella; documentarlo o usar `.deploy/verified`.

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
