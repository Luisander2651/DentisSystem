---
updated: 2026-10-04
---

# Roadmap

**Etapa actual:** prototipo en producción (`dentissapp.com`, v0.1.0 desde el 2026-10-04). Sin usuarios ni datos reales de pacientes.

## Objetivos
| # | Objetivo | Criterio de éxito | Spec | Estado |
|---|---|---|---|---|
| 1 | Cerrar el control de acceso y la fuga de errores | Ninguna ruta de `/api/v1` con datos de pacientes o citas acepta tokens de pacientes ni de staff sin rol; test de acceso denegado por actor en cada ruta; 0 respuestas 500 con `$e->getMessage()` (P7 1.1.1). Riesgos: RS1.a, RS1.d, RS3.a, OB2.a, OB10.a; fuera: RS1.b, RS1.c → objetivo 3 (spec 013), OB2.b → objetivo 5, OB10.b → Pendientes y deuda (decisión del usuario, 2026-09-24) | [014](specs/014-control-de-acceso-y-errores/spec.md) (deriva de 005, 006, 007, 009) | cumplido (v0.1.0, 2026-10-04) |
| 2 | Terminar el endurecimiento AI-DLC de las Unidades 5–7 (ContentManagement, Email, whatsApp) | Unidades 5, 6 y 7 en `COMPLETE` en `aidlc-docs/aidlc-state.md`; `tests/Modules/ContentManagement` existe y la suite completa pasa | 010, 011, 012 | pendiente vía AI-DLC (en pausa, ver [AIDLC.md](../AIDLC.md)) |
| 3 | QR por cita: app Android del paciente y lector fijo de entrada (ESP32) | Spec 013 `released` | 013 | approved |
| 4 | Primer despliegue en el VPS | Imagen de producción, worker de colas, TLS, backups de PostgreSQL y rollback ensayado una vez; `/up` responde en el dominio y comprueba PostgreSQL y Redis; Grafana y Loki no expuestos públicamente; retención de Loki configurada; alertas mínimas de tasa de 5xx y logins fallidos. Riesgos: RD1.a, RD1.b, RD2.a, RD3.a, OB6.a, OB7.a, OB7.b, OB8.a, OB9.a, RS10.a, OB5.b; fuera: RS10.b → Pendientes y deuda, OB5.a → objetivo 5 (decisión del usuario, 2026-09-24). Actualizado: OB5.a, RS10.b, RS6.a, RS6.b, RD4.a, RD5.a, RD5.b, RD6.a, RD6.b y RS16.a entran en la spec 015; OB7.a, OB7.b y OB8.a van a la spec 016 (decisión del usuario, 2026-09-29) | [015](specs/015-primer-despliegue-vps/spec.md) (despliegue), 016 (monitoreo, por crear) | parcial (v0.1.0, 2026-10-04): cumplido salvo la retención de Loki y las alertas de 5xx y de logins fallidos (OB7.a, OB7.b, OB8.a), pendientes de la spec de monitoreo |
| 5 | Trazabilidad: auditoría de accesos, trazas técnicas y logs sin datos sensibles | Se registran con actor, acción, recurso y fecha los logins (incluidos los fallidos), los accesos denegados y las lecturas y cambios de expedientes y datos de salud; cada petición lleva un identificador que aparece en sus logs y en la respuesta; ningún log contiene teléfono, email, nombre del paciente ni datos de salud; los logs de la aplicación llegan a Loki (canal `stderr`) con su identificador; nivel por defecto `info` (obligación LFPDPPP de registro de accesos). Riesgos: RS9.a, RS9.b, RS11.a, OB1.a, OB2.b, OB3.a, OB4.a, OB5.a → objetivo 4 (spec 015, decisión del usuario, 2026-09-29); fuera: OB2.a → objetivo 1 (spec 014), OB5.b (`APP_DEBUG=false`) → objetivo 4 (decisión del usuario, 2026-09-24) | nueva (incluye OB2.b: logs con teléfono y nombre en el caso de uso de citas y en el módulo whatsApp; si antes se retoma la Unidad 7 de AI-DLC, coordinar para no limpiar los mismos archivos dos veces) | pendiente |

## Próxima etapa
Para pasar de **prototipo** a **MVP** con una clínica real:
- Objetivos 1, 4 y 5 cumplidos.
- Riesgos altos de [security.md](security.md) cerrados (incluido el cifrado en reposo de datos de salud).
- Obligaciones de la LFPDPPP implementadas: aviso de privacidad, consentimiento expreso para datos de salud y derechos ARCO (ver [security.md](security.md)).
- Specs 001–012 revisadas y pasadas de `inferred` a `approved` (cumplido el 2026-09-23).

## Pendientes y deuda
- Aceptados sin tarea en la review de la spec 015 (v0.1.0; decisión del usuario, 2026-10-04):
  - Backups: 015/R15 y 015/R46, retención de los dumps `pre-<tag>` y `pre-restore-*`, que no se rotan; 015/R23, cifrarlos y copiarlos fuera del droplet (antes de datos reales).
  - Endurecimiento de producción: 015/R20, la contraseña de Redis va en la línea de comandos del contenedor; 015/R21, Alloy monta `docker.sock`, sin `no-new-privileges`, y el provisioning de Grafana con escritura; 015/R22, imágenes de datos fijadas solo a versión mayor y test que no mira los Compose; 015/R25, sin `default_server` en nginx y 80/443 del origen abiertos a cualquier IP; 015/R26, `style-src 'unsafe-inline'` en la CSP.
  - Scripts de operación: 015/R16, `verify.sh` depende de `/storage/login.jpg`; 015/R19, `deploys.log` es de solo anexado por convención; 015/R44, `assert_logged` exige que la operación sea la última línea; 015/R47, `backups_are_not_mounted_in_nginx` no detecta un montaje en `/`; 015/R48, un segundo `rollback.sh` sin argumentos regresa a la versión de la que se volvió.
  - Aplicación y tests: 015/R24, `/up` nombra la dependencia caída, no tiene límite y `probePostgres` asume `host` escalar; 015/R27, `symfony/yaml` usada como dependencia transitiva en un test; 015/R28, la API key de Brevo queda como propiedad pública del adaptador; 015/R31, el 500 de `SendResetPasswordEmailController` no usa la respuesta genérica; 015/R32, falta `WhatsAppIntegrationTestCase`.
- WhatsApp: probar el envío en producción (menos de 1 minuto, logs sin datos personales) cuando cambie su flujo; en la spec 015 quedó verificado solo con tests (CA3, decisión del usuario, 2026-10-02).
- Producción: retirar la pila manual (`/home/deploy/DentisSystem`) cuando v0.1.0 lleve al menos dos semanas estable, siguiendo "Retirar la pila manual" de [deployment.md](deployment.md).
- Producción: purgar la caché de Cloudflare desde `deploy.sh` cuando cambien las cabeceras de nginx (hoy es manual; necesita un token de la API de Cloudflare en el droplet; decisión del usuario, 2026-10-04: más adelante).
- Monitoreo (spec por crear): retención de Loki y alertas de tasa de 5xx y de logins fallidos (OB7.a, OB7.b, OB8.a; resto del objetivo 4).
- Galería pública: `GetGalleryImagesController`, `GetCertificationsController`, `GetPromotionsController` y `GetTestimonialsController` devuelven un objeto en vez de una lista cuando hay un solo registro; la landing (`resources/js/pages/landing/galeria.js`) falla con "No se pudo establecer conexión con el servidor." (las promociones ya devuelven un objeto en producción). Fix con su propia spec: listados siempre como lista (detectado en T052 de la spec 015, 2026-10-02).
- R2: las imágenes se sirven desde la URL pública del bucket (`pub-….r2.dev`) y se muestran en gestión de contenido. Spec propuesta por el usuario: bucket privado servido a través de la app o con URLs firmadas (afecta a la CSP `img-src`) (2026-10-02).
- Tests: `./vendor/bin/pest --parallel` (12 procesos) falla de forma intermitente por timeouts de conexión a PostgreSQL (`SQLSTATE[08006] … timeout expired`); en serie la suite pasa entera. Ajustar `max_connections` del contenedor `db` o limitar `--processes` (detectado en `/implement 014`, 2026-09-24).
- Auth y Users (RS15, fuera de alcance de la spec 014, CA16): sus catch genéricos registran `getMessage()` y la traza, y `UserException`, `EmailException`, `UserNameException` (Users) y `AuthException` repiten el email en el mensaje. Pasar esos controladores a `UnexpectedErrorResponse` y quitar el email de sus mensajes.
- Tests: crear una cita para un paciente con teléfono dispara el listener de WhatsApp en la misma petición (cola `sync` en `phpunit.xml`) y llama a la **API real de Twilio** si el contenedor tiene credenciales. `tests/Support/FakesTwilio.php` existe pero ningún test lo usaba; todo test que cree citas con teléfono debe llamar a `fakeTwilio()` (detectado en `/implement 014`, 2026-09-24).
- Seguridad sin objetivo asignado (decisión del usuario, 2026-09-24; destinos de la spec 014, "Cobertura de riesgos"):
  - RS5.a: protección CSRF (`SameSite=strict` o cabecera o token anti-CSRF).
  - RS8.a: revocar los tokens de sesión al restablecer la contraseña (spec 003).
- Flujo AI-DLC en pausa: Unidad 5 en diseño funcional (paso 1, 6 preguntas sin responder), Unidades 6 y 7 sin empezar, Build and Test final pendiente. `aidlc-docs/` no está versionado: hacer copia de seguridad.
- Seguimiento clínico (spec 009): abrirlo a doctores, pantalla de edición y completar citas solo con seguimiento (CA9, CA11, CA12; requiere spec nueva).
- WhatsApp (spec 010): recordatorio previo, avisos de reprogramación y cancelación, estado del envío visible en la cita (CA7–CA11; requiere spec nueva, scheduler y worker de colas).
- Contenido (spec 012): límite de imágenes de 5 MB y 2000 px, ocultar promociones fuera de vigencia (también cambia la spec 011), crear testimonios desde el panel y no perder imágenes ante fallos (CA7, CA9–CA11; requiere spec nueva).
- Agenda (spec 007): rechazar fechas y horas pasadas al crear o reprogramar citas (CA14, requiere spec nueva).
- Portal del paciente sin pantallas; módulo `Estadisticas` vacío.
- Arquitectura: fugas de capa (`TreatmentsService`, `LoginService`), imports cruzados sobrantes, grafías `Aplication`/`Application` y `Http`/`HTTP`, posible doble registro de listeners.
- Configuración: `env()` fuera de `config/` (Twilio, Brevo), variables `TWILIO_*`, `BREVO_*` y `SANCTUM_*` ausentes de `.env.example`, `.env.example` con SQLite y phpredis.
- CI de seguridad inactivo: activar `.ai/ci/ai-dd.yml` (moverlo a `.github/workflows/`) cuando gitleaks, semgrep, composer/npm audit y trivy pasen sobre el código actual.
- Calidad: Pint no se aplicaba (154 archivos sin salto de línea final, 57 con `!$x`); `assertStatus(N)` en lugar de asserts específicos; restos del skeleton en `tests/Pest.php` y `DatabaseSeeder`.
- Documentación: README y `ARCHITECTURE.md` declaran PHP 8.2 y PHPUnit; `Docker.md` usa un nombre de contenedor incorrecto.
- `features/QRModule/` quedó migrado a `docs/specs/013-codigos-qr-por-cita/`; se puede borrar el original.
