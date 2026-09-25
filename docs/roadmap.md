---
updated: 2026-09-24
---

# Roadmap

**Etapa actual:** prototipo. Sin usuarios ni datos reales de pacientes; sin entorno de producción.

## Objetivos
| # | Objetivo | Criterio de éxito | Spec | Estado |
|---|---|---|---|---|
| 1 | Cerrar el control de acceso y la fuga de errores | Ninguna ruta de `/api/v1` con datos de pacientes o citas acepta tokens de pacientes ni de staff sin rol; test de acceso denegado por actor en cada ruta; 0 respuestas 500 con `$e->getMessage()` (P7 1.1.1). Riesgos: RS1.a, RS1.d, RS3.a, OB2.a, OB10.a; fuera: RS1.b, RS1.c → objetivo 3 (spec 013), OB2.b → objetivo 5, OB10.b → Pendientes y deuda (decisión del usuario, 2026-09-24) | [014](specs/014-control-de-acceso-y-errores/spec.md) (deriva de 005, 006, 007, 009) | approved |
| 2 | Terminar el endurecimiento AI-DLC de las Unidades 5–7 (ContentManagement, Email, whatsApp) | Unidades 5, 6 y 7 en `COMPLETE` en `aidlc-docs/aidlc-state.md`; `tests/Modules/ContentManagement` existe y la suite completa pasa | 010, 011, 012 | pendiente vía AI-DLC (en pausa, ver [AIDLC.md](../AIDLC.md)) |
| 3 | QR por cita: app Android del paciente y lector fijo de entrada (ESP32) | Spec 013 `released` | 013 | approved |
| 4 | Primer despliegue en el VPS | Imagen de producción, worker de colas, TLS, backups de PostgreSQL y rollback ensayado una vez; `/up` responde en el dominio y comprueba PostgreSQL y Redis; Grafana y Loki no expuestos públicamente; retención de Loki configurada; alertas mínimas de tasa de 5xx y logins fallidos. Riesgos: RD1.a, RD1.b, RD2.a, RD3.a, OB6.a, OB7.a, OB7.b, OB8.a, OB9.a, RS10.a, OB5.b; fuera: RS10.b → Pendientes y deuda, OB5.a → objetivo 5 (decisión del usuario, 2026-09-24) | nueva | pendiente |
| 5 | Trazabilidad: auditoría de accesos, trazas técnicas y logs sin datos sensibles | Se registran con actor, acción, recurso y fecha los logins (incluidos los fallidos), los accesos denegados y las lecturas y cambios de expedientes y datos de salud; cada petición lleva un identificador que aparece en sus logs y en la respuesta; ningún log contiene teléfono, email, nombre del paciente ni datos de salud; los logs de la aplicación llegan a Loki (canal `stderr`) con su identificador; nivel por defecto `info` (obligación LFPDPPP de registro de accesos). Riesgos: RS9.a, RS9.b, RS11.a, OB1.a, OB2.b, OB3.a, OB4.a, OB5.a; fuera: OB2.a → objetivo 1 (spec 014), OB5.b (`APP_DEBUG=false`) → objetivo 4 (decisión del usuario, 2026-09-24) | nueva (incluye OB2.b: logs con teléfono y nombre en el caso de uso de citas y en el módulo whatsApp; si antes se retoma la Unidad 7 de AI-DLC, coordinar para no limpiar los mismos archivos dos veces) | pendiente |

## Próxima etapa
Para pasar de **prototipo** a **MVP** con una clínica real:
- Objetivos 1, 4 y 5 cumplidos.
- Riesgos altos de [security.md](security.md) cerrados (incluido el cifrado en reposo de datos de salud).
- Obligaciones de la LFPDPPP implementadas: aviso de privacidad, consentimiento expreso para datos de salud y derechos ARCO (ver [security.md](security.md)).
- Specs 001–012 revisadas y pasadas de `inferred` a `approved` (cumplido el 2026-09-23).

## Pendientes y deuda
- RS16 / EX1: actualizar las herramientas de build con vulnerabilidades de npm (vite, rollup, postcss, nanoid, picomatch, concurrently, shell-quote) antes del primer despliegue (objetivo 4) y como tarde el 2026-12-31, cuando vence la excepción EX1 de [security.md](security.md).
- Tests: `./vendor/bin/pest --parallel` (12 procesos) falla de forma intermitente por timeouts de conexión a PostgreSQL (`SQLSTATE[08006] … timeout expired`); en serie la suite pasa entera. Ajustar `max_connections` del contenedor `db` o limitar `--processes` (detectado en `/implement 014`, 2026-09-24).
- Auth y Users (RS15, fuera de alcance de la spec 014, CA16): sus catch genéricos registran `getMessage()` y la traza, y `UserException`, `EmailException`, `UserNameException` (Users) y `AuthException` repiten el email en el mensaje. Pasar esos controladores a `UnexpectedErrorResponse` y quitar el email de sus mensajes.
- Tests: crear una cita para un paciente con teléfono dispara el listener de WhatsApp en la misma petición (cola `sync` en `phpunit.xml`) y llama a la **API real de Twilio** si el contenedor tiene credenciales. `tests/Support/FakesTwilio.php` existe pero ningún test lo usaba; todo test que cree citas con teléfono debe llamar a `fakeTwilio()` (detectado en `/implement 014`, 2026-09-24).
- Seguridad sin objetivo asignado (decisión del usuario, 2026-09-24; destinos de la spec 014, "Cobertura de riesgos"):
  - RS5.a: protección CSRF (`SameSite=strict` o cabecera o token anti-CSRF).
  - RS8.a: revocar los tokens de sesión al restablecer la contraseña (spec 003).
  - RS10.b: `SESSION_ENCRYPT=true` en `.env.example`.
  - OB10.b (parte whatsApp): retirar `getTraceAsString()` y `getMessage()` de los logs del módulo whatsApp.
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
