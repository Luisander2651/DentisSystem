---
id: 010
slug: confirmacion-de-cita-por-whatsapp
status: approved
confidence: media
created: 2026-09-22
---

# 010 · Confirmación de cita por WhatsApp

## Problema
El paciente necesita enterarse de la cita que se le agendó sin que el staff tenga que llamarle.

## Historias de usuario
- Como paciente, quiero recibir un WhatsApp con los datos de mi cita cuando me la agenden.
- Como paciente, quiero que me recuerden mi cita antes de que llegue, si así se configuró.
- Como paciente, quiero enterarme por WhatsApp si mi cita se reprograma o se cancela.
- Como miembro del staff, quiero saber si un WhatsApp no llegó a enviarse para avisar al paciente por otro medio.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [ ] CA1 · Dado el evento `ScheduledAppointment` tras crear una cita, cuando se procesa la cola, entonces `CreatedAppointmentListener` envía la plantilla de Twilio (`TWILIO_APPOINTMENT_TEMPLATE_SID`) con nombre, fecha y hora al teléfono de `ContactInfo`.
- [ ] CA2 · Dado un paciente sin teléfono, cuando se procesa el evento, entonces se omite el envío sin error.
- [ ] CA3 · Dado un fallo de Twilio, cuando se procesa el evento, entonces se reintenta hasta 3 veces con 15 s de espera.
- [ ] CA4 · Dada la creación de la cita, cuando se envía el WhatsApp, entonces la respuesta HTTP no espera al envío (listener `ShouldQueue`).
- [ ] CA5 · (abuso) Como operador con acceso a los logs, leo los registros del envío → **HOY NO SE CUMPLE**: se registran el teléfono de destino y las variables de la plantilla (nombre, fecha, hora) a nivel info.
- [ ] CA6 · Dada una cita creada, cuando se envía la confirmación, entonces se envía **siempre**, esté o no activo el recordatorio (comportamiento actual, confirmado como correcto).
- [ ] CA7 · Dada una cita con el recordatorio por WhatsApp activo, cuando se acerca su hora, entonces el paciente recibe un recordatorio previo; con el recordatorio desactivado no lo recibe → **HOY NO SE CUMPLE**: el indicador se guarda pero nada lo usa.
- [ ] CA8 · Dado el formulario de creación de cita, cuando el staff agenda, entonces puede activar el recordatorio → **HOY NO SE CUMPLE**: solo se puede activar al editar la cita.
- [ ] CA9 · Dada una cita reprogramada, cuando cambia su fecha u hora, entonces el paciente recibe un WhatsApp con la nueva fecha y hora → **HOY NO SE CUMPLE**.
- [ ] CA10 · Dada una cita cancelada, cuando se cancela, entonces el paciente recibe un WhatsApp de cancelación y no recibe su recordatorio → **HOY NO SE CUMPLE**.
- [ ] CA11 · Dado un WhatsApp que no se pudo enviar tras los reintentos, o que se omitió por falta de teléfono, cuando el staff abre la cita, entonces ve el estado del envío (enviado, fallido u omitido) → **HOY NO SE CUMPLE**: el fallo queda solo en los logs y en los trabajos fallidos de la cola.

## Fuera de alcance
- Mensajes entrantes / webhooks de Twilio (respuestas del paciente, confirmación de asistencia).
- Otros canales (SMS, email) para estos avisos.

## Seguridad y privacidad
- Datos sensibles involucrados: teléfono y nombre del paciente; credenciales de Twilio.
- Quién puede hacer qué: no hay acción de usuario; lo dispara el sistema.
- Casos de abuso: CA5.
- Mostrar el estado del envío al staff (CA11) no debe exponer el número completo ni el contenido del mensaje más allá de lo que el staff ya ve en la cita.

## Requisitos no funcionales
- Requiere un worker de colas (`php artisan queue:work`) en ejecución.

## Preguntas abiertas
- Ninguna.

## Supuestos
- Ninguno.

## Notas para /plan
- Era la Unidad 7 de AI-DLC (no iniciada). `tests/Support/FakesTwilio.php` existe pero no se usa.
- Spec nueva pendiente para CA7–CA11. Preguntas para esa spec: ¿con cuánta antelación se envía el recordatorio (p. ej. 24 h)? ¿qué pasa si la cita se crea con menos antelación que esa? Necesita un scheduler (`routes/console.php`) y un worker de colas, que hoy no existen en `docker-compose.yml`.
- Cada aviso nuevo requiere una plantilla aprobada por WhatsApp en Twilio.
- Relación con la spec 013: la cancelación también expira el QR.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/whatsApp/Infrastructure/Listeners/CreatedAppointmentListener.php`, `app/Modules/whatsApp/Aplication/Jobs/ConfirmationAppointmentMessage.php`, `app/Modules/whatsApp/Infrastructure/ExternalApi/TwilioConection.php`, `app/Modules/Appointments/Infrastructure/Http/Controllers/CreateAppointmentController.php:47-55`, `app/Providers/EventServiceProvider.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- `TwilioConection` lee `TWILIO_*` con `env()` fuera de `config/`; con `config:cache` recibe null. Las variables no están en `.env.example`.
- El listener está registrado en `$listen` y además en la carpeta autodescubierta: posible doble envío (sin verificar; `php artisan event:list`).
- La confirmación se envía siempre, sin mirar `whatsapp_reminder` (confirmado como correcto, CA6).
- `whatsapp_reminder` vale `false` por defecto (migración de `appointments`) y solo aparece en el modal de edición de la cita.
- Tras 3 intentos fallidos el listener relanza la excepción: el trabajo queda en la tabla de trabajos fallidos y no se avisa a nadie.
- `docker-compose.yml` no define un servicio de worker de colas.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aclaraciones: recordatorio previo (CA7, CA8), avisos de reprogramación y cancelación (CA9, CA10), estado del envío visible (CA11); pendientes de spec nueva | /clarify |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |

## Aclaraciones
### Sesión 2026-09-22
- P: ¿Qué debe significar la opción "recordatorio por WhatsApp" (`whatsapp_reminder`)? → R: Un recordatorio previo a la cita; la confirmación al crear sale siempre.
- P: ¿Se debe avisar al paciente por WhatsApp cuando su cita se reprograma o se cancela? → R: Sí, en ambos casos.
- P: Si el WhatsApp no se pudo enviar tras los reintentos, ¿qué debe pasar? → R: El estado del envío es visible en la cita para que el staff avise por otro medio.
