---
id: 017
slug: reprogramar-cancelar-citas
status: released
released: v0.3.0 (2026-10-16)
created: 2026-10-15
extends: [007, 011, 012]
---

# 017 · Reprogramar y cancelar citas

## Problema
Desde la agenda no se puede reprogramar una cita: el diálogo de edición pide además un estado y,
con "Asignada" o "Reprogramada", el cambio se rechaza. Tampoco hay una forma directa de cancelar
una cita, y nada impide agendar o reprogramar una cita en una fecha u hora que ya pasó (007 CA14).
Afecta al administrador, que es quien gestiona la agenda: hoy no puede mover citas y podría dejar
citas en el pasado que nunca se atenderán. Escalada desde la ficha
[2026-10-15-fix-reprogramar-cita](../../changes/2026-10-15-fix-reprogramar-cita.md).

## Historias de usuario
- Como administrador, quiero reprogramar una cita eligiendo solo la nueva fecha y hora para
  moverla sin preocuparme por su estado.
- Como administrador, quiero cancelar una cita con una acción propia y confirmada para no tener
  que editarla.
- Como administrador, quiero que no se pueda agendar ni reprogramar una cita en el pasado para
  que la agenda solo tenga citas que se pueden atender.

## Criterios de aceptación
- [x] CA1 · Dada una cita asignada o reprogramada, cuando el administrador la reprograma a una fecha y hora futuras y libres, entonces la cita queda con la nueva fecha y hora y en estado "reprogramada" sin que tenga que elegir el estado, el diálogo se cierra, se avisa "Cita reprogramada." y la agenda muestra el cambio.
- [x] CA2 · Dada una reprogramación, cuando falta la fecha o la hora, entonces se rechaza con el mensaje "Necesitas proporcionar fecha y hora para reprogramar la cita." y la cita no cambia.
- [x] CA3 · Dada una fecha anterior a hoy, o la fecha de hoy con una hora igual o anterior a la hora actual de la clínica, cuando el administrador reprograma una cita a ese momento, entonces se rechaza con el mensaje "No puedes reprogramar la cita a una fecha u hora que ya pasó." y la cita no cambia. Cumple 007 CA14 al reprogramar.
- [x] CA4 · Dada una fecha anterior a hoy, o la fecha de hoy con una hora igual o anterior a la hora actual de la clínica, cuando se intenta agendar una cita nueva en ese momento, entonces se rechaza con el mensaje "No puedes agendar una cita en una fecha u hora que ya pasó." y no se crea la cita. Cumple 007 CA14 al crear.
- [x] CA5 · Dado un rechazo por fecha u hora pasada, choque de horario o dato faltante, cuando el administrador está en el diálogo de agendar o de reprogramar, entonces el diálogo sigue abierto con lo que escribió y muestra el mensaje junto al formulario.
- [x] CA6 · Dada una misma solicitud que pide reprogramar y cambiar el estado a la vez, entonces se rechaza y la cita no cambia: reprogramar y cambiar el estado son acciones separadas.
- [x] CA7 · Dada una cita asignada o reprogramada, cuando el administrador pulsa "Cancelar cita" en su detalle y lo confirma, entonces la cita queda cancelada, se avisa "Cita cancelada." y la agenda muestra el cambio; si en la confirmación elige no cancelar o la cierra, la cita no cambia.
- [x] CA8 · Dada una cita completada o cancelada, cuando el administrador abre su detalle, entonces no ve "Reprogramar" ni "Cancelar cita"; y si intenta reprogramarla o cancelarla por otra vía, se rechaza y la cita no cambia.
- [x] CA9 · Dado el detalle de una cita asignada o reprogramada, entonces el botón que antes decía "Editar cita" dice "Reprogramar" y abre el diálogo "Reprogramar cita", que pide fecha, hora y el recordatorio de WhatsApp, sin selector de estado.
- [x] CA10 · (abuso) Como miembro del staff que no es administrador, intento reprogramar o cancelar una cita, con datos válidos o no → se rechaza con acceso denegado y la cita no cambia.
- [x] CA11 · (abuso) Dada una cita cancelada, cuando se intenta marcarla como completada por cualquier vía, entonces se rechaza y la cita sigue cancelada.

## Fuera de alcance
- El recordatorio de WhatsApp que se invierte cada vez que se guarda el diálogo (007 CA6): el
  recordatorio aún no funciona al 100 % y se corregirá aparte (decisión del usuario, 2026-10-15).
- Avisos por WhatsApp al paciente cuando su cita se reprograma o se cancela (010 CA9, CA10).
- Motivo de cancelación, deshacer una cancelación o reactivar una cita cancelada.
- Reprogramar, cancelar o reabrir citas completadas o canceladas.
- Margen de anticipación (p. ej. no agendar con menos de 1 hora): no se pide.
- Cambios al calendario, a los filtros o al diálogo de completar la cita.
- Abrir la agenda a roles que no son administrador.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales del paciente y el tratamiento de la cita
  (personal y salud, según `docs/security.md`); esta spec no añade datos nuevos ni los expone a
  más roles.
- Quién puede hacer qué: administrador → reprogramar y cancelar; resto del staff y pacientes →
  denegado (como dejó la spec 014 sobre 007 CA3–CA6).
- Casos de abuso:
  - Como staff no administrador, intento reprogramar o cancelar una cita → se rechaza (CA10).
  - Como administrador, intento reprogramar o cancelar una cita completada o cancelada por fuera
    de la pantalla → se rechaza (CA8).
  - Como administrador, intento colar un cambio de estado dentro de una reprogramación → se
    rechaza (CA6).
  - Como administrador, intento reabrir una cita cancelada marcándola como completada → se
    rechaza (CA11).

## Auditoría
No aplica en esta spec: el registro de auditoría aún no existe (roadmap objetivo 5) y esta
spec no cambia expedientes ni datos de salud; cuando exista, los cambios de cita se registrarán
según `docs/observability.md`.

## Diseño
Debe sentirse directo: dos acciones claras en el detalle de la cita ("Reprogramar" y "Cancelar
cita"), cancelar se percibe como acción destructiva y siempre pide confirmación. Pantallas que
toca: el detalle de la cita, el diálogo "Reprogramar cita" (antes "Editar cita") y un diálogo
nuevo de confirmación de la cancelación. Solo con componentes del sistema de diseño
([docs/design/system.md](../../design/system.md)); no es un rediseño.
Diseño de la spec: [design.md](design.md) (sin composiciones; rojo solo al confirmar la cancelación).

## Requisitos no funcionales
- "Ahora" es la fecha y hora de la clínica, en la zona horaria de Ciudad de México.
- Esa misma zona rige todo lo que depende del día actual: las citas de hoy de la agenda (007) y
  la vigencia de las promociones del sitio público (011, 012, que ya la pedía en la zona de la
  clínica).
- La interfaz usa solo el sistema de diseño y pasa la prueba en navegador a 390, 768 y 1440 px,
  incluido el diálogo nuevo de confirmación (constitución P15).
- Contraste, foco visible y uso con teclado en los dos diálogos y en los botones nuevos (WCAG AA).
- Mensajes en español y sin términos técnicos; los errores inesperados siguen siendo genéricos (P7).

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-15 | brecha | ¿Arreglar en el carril `/fix` o en spec? | Spec nueva que extiende 007; la ficha queda `escalated` | usuario |
| 2026-10-15 | brecha | ¿Crear una cita en el pasado también se rechaza? | Sí (CA4), cierra 007 CA14 completo | usuario |
| 2026-10-15 | brecha | ¿En qué zona horaria se compara "ahora"? | Ciudad de México, también como valor por defecto de la aplicación | usuario |
| 2026-10-15 | supuesto | ¿Hay margen de anticipación? | Sin margen: se rechaza lo igual o anterior al momento actual | usuario (no pidió margen) |
| 2026-10-15 | brecha | ¿Se corrige aquí el recordatorio de WhatsApp que se invierte? | No, fuera de alcance | usuario |
| 2026-10-15 | brecha | ¿Se quita el selector de estado y se separa cancelar? | Sí: "Reprogramar" sin estado y botón "Cancelar cita" con confirmación | usuario |
| 2026-10-15 | implícita | ¿Quién puede reprogramar y cancelar? | Solo el administrador | `docs/specs/007-agenda-de-citas/spec.md` → Historial (014, v0.1.0) |
| 2026-10-15 | diseño | ¿Cómo se ven las acciones y la confirmación? | Ver [design.md](design.md): "Cancelar cita" normal en el detalle, rojo solo en "Sí, cancelar la cita" | /design-spec 017, usuario |
| 2026-10-15 | brecha | ¿Se bloquea también reabrir una cita cancelada marcándola como completada? | Sí (CA11) | usuario (/analyze A9) |
| 2026-10-15 | brecha | La zona de la clínica para toda la aplicación cambia también las citas de hoy y la vigencia de las promociones | Se mantiene para toda la aplicación y se declara; la spec extiende 011 y 012 | usuario (/analyze A10) |
| 2026-10-15 | brecha | Con datos inválidos, ¿quien no es administrador ve errores de validación o acceso denegado? | Acceso denegado con cualquier dato (CA10) | usuario (/analyze A8) |
| 2026-10-15 | implícita | ¿Qué estado deja una reprogramación? | `reprogramada` (007 CA5); el estado solo cambia a completada o cancelada (007 CA4) | `docs/specs/007-agenda-de-citas/spec.md` |

## Notas para /plan
- Criterios de 007 que cambia:
  - CA3: fecha sin hora o al revés pasa de 409 a 422, y además se rechaza la fecha pasada.
  - CA4: un estado fuera de `completada` o `cancelada` pasa de 409 a 422; reprogramar y `status`
    no se mezclan en el mismo `PUT`; una transición sobre una cita cerrada sigue en 409.
  - CA14: pasa a cumplirse con CA3 y CA4 de esta spec.
  - "Citas de hoy" (`GET /agenda/today-appointments`) pasa a calcular el día en la zona de la
    clínica.
- 011 y 012: la vigencia de las promociones pasa a evaluarse en la zona de la clínica, como ya
  pedía 012 en sus Notas.
- Regla en el dominio: `AppointmentEntity::reschedule()` (y la creación) reciben el "ahora" en vez
  de leer el reloj; el caso de uso pasa `now()` con `APP_TIMEZONE=America/Mexico_City`.
- `cancel()` y `reschedule()` deben rechazar citas completadas o canceladas (CA8); hoy no lo hacen.
- La interfaz deja de enviar `status` al reprogramar; cancelar envía solo `status: cancelada`
  (o una acción propia, a decidir en el plan). Diálogos con `resources/js/ui/dialog.js`, avisos con
  `resources/js/ui/status.js`, botón de cancelar con la variante `danger` de `x-ui.button`.
- Archivos implicados: `UpdateAppointmentUseCase`, `CreateAppointmentUseCase`,
  `AppointmentEntity`, `AppointmentException`, `UpdateAppointmentController`,
  `components/calendar/edit-appointment-modal.blade.php`, `view-appointment-modal.blade.php`,
  `resources/js/pages/agenda/create-appointment.js`, `view-appointment.js`,
  `tests/Browser/screens.json` y `php artisan ui:audit-data`.
- Tests: `UpdateAppointmentTest` y `CreateAppointmentTest` usan fechas que ya pasaron
  (`2026-09-02`); hay que fijar el reloj con `travelTo()`.
- El primer test de la implementación reproduce el 409 de la ficha (reprogramar con
  `status: asignada`).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-15 | Creación en `draft` | /specify, escalada desde la ficha 2026-10-15-fix-reprogramar-cita |
| 2026-10-15 | Aprobada | Aprobación del usuario |
| 2026-10-15 | CA10 con datos inválidos, CA11 nuevo, `extends` con 011 y 012, efectos de la zona horaria; vuelve a `draft` | /analyze 017 ronda 1 (A8, A9, A10, A11) |
| 2026-10-15 | Reaprobada tras la edición | Aprobación del usuario |
| 2026-10-16 | Implementada: CA1–CA11 con sus tests en verde (Pest y ui-audit) | /implement 017 |
| 2026-10-16 | Correcciones de /review ronda 1 (R1, R2, R3, R11): T040–T045; vuelve a `implemented` | /implement 017 |
| 2026-10-16 | Liberada en v0.3.0 | /release 017 |
