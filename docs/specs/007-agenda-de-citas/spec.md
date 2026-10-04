---
id: 007
slug: agenda-de-citas
status: approved
confidence: alta
created: 2026-09-22
---

# 007 · Agenda de citas

## Problema
La clínica necesita agendar citas sin choques de horario, reprogramarlas, cancelarlas o marcarlas
como completadas, y verlas en un calendario.

## Historias de usuario
- Como miembro del staff, quiero agendar una cita para un paciente con un doctor y un tratamiento.
- Como miembro del staff, quiero reprogramar o cancelar una cita.
- Como administrador, quiero ver el calendario de citas filtrado por estado y fecha.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un usuario autenticado, cuando hace `POST /api/v1/appointments` con fecha, hora, tratamiento, usuario (doctor) y paciente, entonces se crea la cita en estado `asignada`, responde 201 y se dispara el evento `ScheduledAppointment`.
- [x] CA2 · Dada otra cita no cancelada **en la misma fecha, de cualquier doctor** (la validación es para toda la clínica) cuyo intervalo `[inicio, inicio + duración del tratamiento)` se solapa, cuando se crea o reprograma, entonces se rechaza con 409; al reprogramar, la propia cita se excluye (U1 BR-1).
- [x] CA3 · Dado `PUT /api/v1/appointments/{id}` con `date` sin `time` o viceversa, entonces responde 409 (U1 BR-2).
- [x] CA4 · Dado un cambio de `status`, cuando el valor no es `completada` ni `cancelada`, entonces responde 409 (U1 BR-3).
- [x] CA5 · Dada una reprogramación exitosa, entonces la cita queda en estado `reprogramada` (U1 BR-4).
- [x] CA6 · Dado `PUT` con `whatsapp_reminder`, entonces se activa o desactiva el indicador.
- [x] CA7 · Dado un administrador, cuando hace `GET /api/v1/appointments` con filtros de estado y fecha, entonces obtiene las citas; el nombre del staff se muestra desde sus columnas (U4 BR-29).
- [x] CA8 · Dado un administrador, cuando hace `DELETE /api/v1/appointments/{id}`, entonces se elimina la cita (U1 BR-5).
- [x] CA9 · Dado un usuario autenticado, cuando consulta `GET /api/v1/appointments/{id}` o `/appointments/patient/{patientId}`, entonces obtiene la cita o el historial (U1 BR-6).
- [ ] CA10 · Dado un usuario autenticado, cuando abre el formulario de cita, entonces `GET /api/v1/agenda/patients`, `/agenda/doctors` y `/agenda/treatments` alimentan los selectores (solo `/agenda/treatments` tiene test).
- [x] CA11 · (abuso) Como staff no administrador, intento listar citas o borrar una cita → 403 (U1 BR-5).
- [x] CA12 · (abuso) Como staff no administrador, pido `GET /api/v1/agenda/today-appointments` → resuelto por 014 (v0.1.0): responde acceso denegado (antes: responde 500 en vez de 403 (U1 BR-5b)).
- [x] CA13 · (abuso) Como paciente autenticado, intento crear o modificar citas con cualquier `patient_id` o `user_id` → resuelto por 014 (v0.1.0): los pacientes ya no acceden a estas rutas (antes: está permitido (U1, hallazgo documentado)).
- [ ] CA14 · Dada una fecha u hora ya pasada, cuando se intenta crear o reprogramar una cita, entonces se rechaza → **HOY NO SE CUMPLE**: se permite (decisión del 2026-09-22; corrección en una spec nueva).

## Fuera de alcance
- Autoagendamiento por el paciente.
- Recordatorios programados (el indicador `whatsapp_reminder` se guarda pero nada lo usa).
- Confirmación por WhatsApp (spec 010) y seguimiento clínico (spec 009).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales del paciente y motivo implícito (tratamiento).
- Quién puede hacer qué: cualquier autenticado → crear, reprogramar, cambiar estado, consultar por id o paciente; administrador → listar, citas de hoy, borrar.
- Casos de abuso: CA11, CA12, CA13.

## Requisitos no funcionales
- Índices `(date, status)` y `(status)` en `appointments`.

## Preguntas abiertas
- Ninguna.

## Supuestos
- El modelo de negocio actual es "el staff agenda en nombre del paciente".
- Solo el administrador usa la agenda y el listado de citas (confirmado en AI-DLC U1 BR-5); que el sidebar muestre "agenda" al asistente es un defecto de la interfaz, no un permiso pendiente de decidir.

## Notas para /plan
- Spec nueva pendiente: rechazar fechas y horas pasadas al crear y reprogramar (CA14). Definir si la hora se compara en la zona horaria de la clínica y si hay margen.
- El README dice que el solapamiento es "por doctor/día"; es incorrecto y debe corregirse a "toda la clínica".
- Quitar la pestaña "agenda" del sidebar para roles que no son administrador (P13).

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Appointments/**`, `app/Modules/Appointments/Domain/Service/ScheduleAvailabilityChecker.php`, `routes/api.php:155-160,182-190`, `routes/web.php:87`, `resources/js/pages/agenda/*.js`, `resources/views/components/calendar/*`
- Tests: `tests/Modules/Appointments/Integration/CreateAppointmentTest.php`, `UpdateAppointmentTest.php`, `DeleteAppointmentTest.php`, `GetAppointmentsTest.php`, `AppointmentStaffNameTest.php`, `tests/Modules/Appointments/Unit/*`

## Observaciones (solo si status=inferred)
- `CreateAppointmentController` no usa FormRequest; 11 controladores del módulo devuelven `$e->getMessage()` en el 500.
- Nombres con erratas consolidadas: `GetAllApointmentsByStatusAndDateController`, `RetriveDataForScheduledAppointmenEventUseCase`, `AppointmentPateintId`.
- `RetriveDataForScheduledAppointmenEventUseCase` usa directamente repositorios del módulo Patients.
- La interfaz del asistente y del paciente muestra acciones que el backend rechaza (incumple P13 como deuda).
- Las citas existentes cuyo tratamiento no tiene duración (`time` nulo o 0) se ignoran en la comprobación de choque: nunca bloquean a otras. Una cita nueva con un tratamiento así tiene duración 0 y solo choca si su hora cae estrictamente dentro de otra cita (`ScheduleAvailabilityChecker::ensureAvailable`).
- No hay validación de fechas u horas pasadas (CA14).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aclaraciones: solapamiento para toda la clínica; se deben rechazar fechas pasadas (CA14, pendiente) | /clarify |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: CA1, CA3–CA6 y CA10 solo para el administrador; CA9 para todo el staff; CA12 y CA13 resueltos | /release |

## Aclaraciones
### Sesión 2026-09-22
- P: ¿Cómo debería validarse el choque de horarios entre citas: por doctor, para toda la clínica o por consultorio? → R: Para toda la clínica (el comportamiento actual es el correcto).
- P: ¿Se debe impedir agendar o reprogramar citas en fechas u horas ya pasadas? → R: Sí, impedirlo (hoy se permite; se corregirá en una spec nueva).
