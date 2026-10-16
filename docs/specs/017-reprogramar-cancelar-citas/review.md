---
spec: 017-reprogramar-cancelar-citas
verdict: changes_requested
round: 1
date: 2026-10-16
base: ea38cd5
head: 02448ff
human_signoff: pending
---

# Review · 017 Reprogramar y cancelar citas

## Resumen
Lo implementado cumple el plan y no hay bloqueantes. Hay cuatro hallazgos importantes que el usuario decidió corregir antes de liberar:
- **R1:** CA2 da otro texto cuando llegan vacías la fecha y la hora a la vez.
- **R2:** el controlador elige el código HTTP comparando el texto de la excepción.
- **R3:** la defensa del caso de uso contra mezclar reprogramación y estado no la ejecuta ningún test.
- **R11:** la prueba de navegador de la agenda falla de forma intermitente.

Los menores se aceptan y pasan al roadmap.

El plugin `design` de Anthropic (`design:accessibility-review`, `design:design-critique`) no está disponible en esta sesión, así que el revisor de accesibilidad aplicó la lista de la skill.

Conteo: bloqueante 0 · importante 4 · menor 7

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 1338 pasan (13 284 aserciones); línea base: 0 |
| `vendor/bin/pint --test` sobre los archivos PHP de la spec | ✅ (`pint --test` sobre todo el repositorio marca archivos antiguos con CRLF que la spec no toca; el comando del proyecto es `--dirty`) |
| `npm run build` | ✅ |
| `npm run test:ui -- --only agenda,citas-del-dia,detalle-cita,reprogramar-cita,nueva-cita,cancelar-cita` | ⚠ tres corridas: 41 visitas con 0, 2 y 0 fallos. Los fallos de la 2.ª no se guardaron en detalle (R11) |
| `aidd.py validate` | ✅ sin errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `UpdateAppointmentTest` › reschedules to a future slot; `agenda-cita-reprogramar` (flujos) | ✅ | |
| CA2 | `UpdateAppointmentTest` › rejects date without time / time without date | ⚠ | Con fecha y hora vacías a la vez el texto es otro (R1) |
| CA3 | `AppointmentEntityTest`, `AppointmentSchedulePropertiesTest`, `UpdateAppointmentTest` (ayer, hoy antes, ahora exacto) | ✅ | |
| CA4 | `AppointmentEntityTest`, `CreateAppointmentTest` | ✅ | |
| CA5 | `checks-dialogs.mjs` flujos de 422 en reprogramar y agendar; «reabrir tras un 422» | ✅ | El 409 muestra el texto genérico del sistema, como fija el plan (R4) |
| CA6 | `UpdateAppointmentTest` › rejects a request that reschedules and changes the status at once | ✅ | Solo prueba el FormRequest; la defensa del caso de uso queda sin test (R3) |
| CA7 | `UpdateAppointmentTest` › cancels…; `agenda-cita-cancelar` (7 flujos) | ✅ | |
| CA8 | `AppointmentEntityTest`; `UpdateAppointmentTest` (409); `agenda-cita-detalle-cancelada`, `expediente-administrador-cita-detalle` | ✅ | Falta la entrada de la cita cancelada en el expediente (R8) |
| CA9 | `agenda-cita-reprogramar` (título, sin estado) | ✅ | |
| CA10 | `AppointmentsAccessControlTest` (actor × acción y cuerpos inválidos → 403) | ✅ | |
| CA11 | `AppointmentEntityTest`; `UpdateAppointmentTest`; `CompleteAppointmentTest` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los 33 archivos de código tienen tarea (`scope.md`). `resources/js/ui/status.js` entró con T039, añadida durante /implement con aprobación del usuario.
- Partes del plan sin implementar: ninguna. T001, T003, T036 y T037 no tienen diff de código porque son documentación, auditoría de dependencias y capturas; sus cambios aparecen en `docs.stat`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño, plan y tareas aprobados; commits `(017): T0xx` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene su test (tabla anterior). La defensa del caso de uso de CA6 no tiene test (R3) |
| P3 Capas del módulo | ✅ | El dominio recibe `$now` y no hay `now(`, `Carbon` ni `Illuminate` en `Domain/`. `updatedAt` usa otro reloj (R7) |
| P4 Contrato de API primero | ❌ aceptado | 409 → 422 dentro de `/api/v1` (plan D10, aprobado por el usuario el 2026-10-15) |
| P5 Autorización en el servidor | ✅ | `UpdateAppointmentRequest::authorize()` hace `assertCan` antes de validar; `AppointmentsAccessControlTest` |
| P6 Validación con FormRequest | ✅ | `UpdateAppointmentRequest` con reglas en array y mensajes en español (con R1 pendiente) |
| P7 Errores sin detalles internos | ✅ | `UnexpectedErrorTest` (`PUT` y `POST` → `{"error":"Internal server error"}`) |
| P8 Secretos fuera del repositorio | ✅ | gitleaks sin hallazgos; `APP_TIMEZONE` no es secreto |
| P9 Migraciones reversibles | ➖ | Sin migraciones |
| P10 Dependencias nuevas con ADR | ➖ | Sin dependencias nuevas (`deps.stat` vacío) |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM5 con control y test |
| P12 Producción con aprobación humana | ✅ | Pendiente de `/release` |
| P13 Lógica de negocio en el backend | ✅ | Pasado, transiciones y no-mezcla en dominio, caso de uso y FormRequest |
| P14 Trazabilidad y auditoría | ➖ | Sin eventos de auditoría en la spec |
| P15 Solo el sistema de diseño | ✅ | `x-ui.dialog` y `x-ui.button`; `OnlyDesignTokensTest` y `ui-audit` en verde |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`ea38cd5..HEAD`, 32 commits) | ✅ 0 hallazgos |
| semgrep `p/php` (app, config, bootstrap, routes) | ✅ 0 hallazgos |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy config (`docker/`) | ✅ 0 |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `authorize()` antes de las reglas; 403 con cuerpo inválido (`AppointmentsAccessControlTest`) |
| A05:2025 Inyección / XSS en el DOM | ✅ | `cancel-appointment.js:49-51` usa `textContent` con los datos del paciente |
| A06:2025 Diseño inseguro | ✅ | TM2–TM4: transiciones cerradas en la entidad, no-mezcla y pasado en el servidor. El plan las clasifica como A04 (R10) |
| A08:2025 Integridad de datos | ⚠ | `whatsapp_reminder` invierte el valor en lugar de aplicarlo; ya estaba antes y queda fuera de alcance (R6) |
| A10:2025 Manejo de condiciones excepcionales | ✅ | 500 genérico; 422 y 409 con textos fijos |
| CSRF | ✅ | `X-XSRF-TOKEN` en el `PUT` de cancelar; Sanctum por cookie |
| Proceso (rutas protegidas, dependencias, instrucciones a agentes) | ✅ | El diff no toca rutas protegidas ni lockfiles y no contiene instrucciones dirigidas a agentes |

## Observabilidad
- Eventos de auditoría de la spec: no aplica, porque la spec no los define (plan → Observabilidad).
- Datos sensibles en logs del código tocado: ninguno. No se añaden logs; los 500 pasan por `UnexpectedErrorResponse`.
- `request_id`: sin logs nuevos. Los 422 y 409 quedan en el log de acceso de nginx con su `request_id`.
- La hora de los logs pasa a la zona de la clínica, documentado en `docs/observability.md` (T091).

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rutas públicas | ➖ | El diff solo toca la agenda privada (`/agenda`, detrás de sesión); no hay rutas públicas, layouts públicos, metadatos, `robots.txt` ni sitemap |
- Sección "Descubribilidad" del plan: no aplica (sin rutas públicas).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `app/Modules/Appointments/Infrastructure/Http/Requests/UpdateAppointmentRequest.php:34-35` | Si llegan la fecha y la hora a la vez vacías o en `null` (lo que envía «Reprogramar cita» al vaciar los dos campos, tras `ConvertEmptyStringsToNull`), `required_with` no salta y `date_format` responde "La fecha no tiene un formato válido." en lugar del texto de CA2. Comprobado con `Validator::make(['date'=>null,'time'=>null], …)`. Ningún test cubre el caso. | Pedir fecha y hora, con el texto de CA2, siempre que la petición traiga cualquiera de las dos claves, y añadir los casos `''` y `null` al test de CA2 |
| R2 | importante | `app/Modules/Appointments/Infrastructure/Http/Controllers/UpdateAppointmentController.php:58` | El controlador elige entre 422 y 409 comparando `getMessage()` con el de `rescheduleAndStatusTogether()`. Si alguien edita o reutiliza ese texto, el código de respuesta cambia sin que nada avise. | Una excepción propia para «reprogramar y estado a la vez», capturada en su propio `catch` |
| R3 | importante | `app/Modules/Appointments/Aplication/UseCases/UpdateAppointmentUseCase.php:70`; `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php:159` | El test de CA6 se resuelve entero en el FormRequest (`prohibits`). Ni la guardia del caso de uso ni su conversión a 422 en el controlador se ejecutan en ningún test: si se borraran, la suite seguiría en verde. | Test del caso de uso con un DTO que mezcla fecha, hora y estado, y test del controlador con esa excepción → 422 `errors.status` |
| R4 | menor | `resources/js/pages/agenda/cancel-appointment.js:106`; `tests/Browser/screens.json:443,627` | En los 409, como la cita ya cerrada o el choque de horario, se muestra el texto genérico "No se pudo guardar porque choca con otro registro…". En la confirmación de cancelar ese texto no explica qué pasó. El plan lo fija así (`plan.md:85`). | Mostrar el texto de dominio del 409 en la agenda; es una decisión de diseño para otra spec |
| R5 | menor | `resources/js/pages/agenda/cancel-appointment.js:56-62,100-111` | Mientras se envía, «No, mantenerla» parece activo pero no hace nada. Escape, la X y el fondo sí cierran la confirmación, y si el envío falla después, el error cae en la región del detalle. | Deshabilitar «No, mantenerla» durante el envío y decidir qué hacen Escape, la X y el fondo mientras se envía |
| R6 | menor | `app/Modules/Appointments/Domain/Entities/AppointmentEntity.php:182-186`; `UpdateAppointmentTest.php:65,232` | `whatsapp_reminder` invierte el valor guardado en lugar de aplicar el que llega, así que la operación no es idempotente. Los tests «applies the reminder…» pasan igual invirtiendo. Ya ocurría antes; el plan lo deja fuera de alcance. | Spec o `/fix` aparte: guardar el booleano recibido, con un test de `false` sobre `false` |
| R7 | menor | `app/Modules/Appointments/Domain/Entities/AppointmentEntity.php:139` | `reschedule()` recibe `$now` pero guarda `updatedAt` con `new DateTimeImmutable`: son dos relojes en el mismo método. | `$this->updatedAt = $now;` |
| R8 | menor | `tests/Browser/screens.json:973` | La trazabilidad de CA8 promete el detalle de una cita cancelada en el expediente, pero solo hay entradas para la completada y la asignada. Queda cubierto de forma indirecta, porque el expediente no carga los diálogos. | Añadir `expediente-administrador-cita-detalle-cancelada` con `absent` |
| R9 | menor | `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php:32` | El test que reproduce la ficha solo comprueba el 422. Pasaría aunque el rechazo viniera de `status.in` y no de `prohibits`. | Comprobar también `errors.status.0` con el texto de CA6 |
| R10 | menor | `docs/specs/017-reprogramar-cancelar-citas/plan.md:151-153` | TM2–TM4 dicen "A04:2025 Insecure Design"; en el Top 10 2025 Insecure Design es A06 (A04 es Cryptographic Failures). | Corregir la categoría al tocar el plan o en la próxima spec |
| R11 | importante | `tests/Browser/` (convención de la agenda) | La 2.ª de tres corridas de la convención dio 2 fallos sobre 41 visitas; la 1.ª y la 3.ª, 0. No se guardó el detalle. Durante /implement ya hubo un tiempo de espera de carga en `agenda-cita-cancelar @ 390`. | Correr hasta reproducirlo con la salida completa y sustituir la espera que falla por una espera por condición |

## Preparación para release
- Rollback factible: sí. `docker/prod/rollback.sh` a la versión anterior; no hay esquema ni datos que revertir, y `cancelada` y `reprogramada` ya son estados válidos.
- Migraciones compatibles con la versión anterior: sin migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `docs/architecture.md` (T090), `docs/deployment.md` (fila `APP_TIMEZONE` y grep 2c) y `docs/observability.md` (T091).
- SCA: sin vulnerabilidades críticas ni altas.
- Antes del deploy: confirmar que el `.env` de producción no fija `APP_TIMEZONE` en otra zona (plan → Rollout).

## Tareas añadidas
- T040, T041 ← R1
- T042, T043, T044 ← R2, R3
- T045 ← R11

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-16:
- **R4** → roadmap: mostrar en la agenda el texto de dominio de los 409 (cita cerrada, choque de horario) en lugar del genérico.
- **R5** → roadmap: «No, mantenerla», Escape, la X y el fondo durante el envío de la cancelación.
- **R6** → roadmap: `whatsapp_reminder` invierte en lugar de aplicar el valor (no idempotente).
- **R7** → roadmap: `reschedule()` guarda `updatedAt` con el reloj del sistema en lugar de `$now`.
- **R8** → roadmap: entrada de navegador del detalle de una cita cancelada en el expediente.
- **R9** → roadmap: el test de reproducción de la ficha debería comprobar el texto de CA6.
- **R10** → roadmap: categoría OWASP de TM2–TM4 (A06:2025, no A04).
