---
spec: 017-reprogramar-cancelar-citas
plan: plan.md
status: draft
---

# Tareas · 017 Reprogramar y cancelar citas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

Convenciones de esta spec:
- **Suite en verde por tarea:** cada test Pest nuevo o cambiado se escribe, se comprueba que falla
  por la razón esperada y se deja con `->skip('017: pendiente de T0xx')`. La tarea que lo
  implementa quita esa marca. Al cerrar cada tarea la suite Pest queda en verde salvo esas marcas;
  T034 falla si queda alguna.
- **Reloj fijo:** los tests de integración tocados usan `$this->travelTo()` a un instante fijo de
  `America/Mexico_City`, en `beforeEach`. Las fechas de los payloads se escriben relativas a ese
  instante.
- `ui-audit` = `npm run test:ui -- --only agenda,citas-del-dia,detalle-cita,reprogramar-cita,nueva-cita,cancelar-cita`
  (`--only` toma de una pantalla solo sus entradas sin diálogo, así que cada diálogo se nombra: las
  pantallas de la agenda, la lista del día, el detalle en la agenda y en el expediente, reprogramar,
  agendar y la confirmación de cancelar; A39), a 390, 768 y 1440 px, **en local** (Chrome del
  equipo). Las pruebas de navegador (T015, T018) no admiten `skip`: en local se comprueba que
  fallan por la razón esperada antes de T030–T033 y que pasan después; cada tarea de navegador o de
  interfaz anota el resultado en una nota (`- nota: ui-audit local AAAA-MM-DD: …`). CI lo repite al
  hacer push de la rama.
- "diseño" = [design.md](design.md) → "Composición y jerarquía".

## Constitution Check
Las tareas no añaden nada al análisis de [plan.md](plan.md); se revisa lo que el desglose pudo romper.

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Toda tarea traza a un criterio o a un cambio del plan (tablas de Cobertura) | manual: `/analyze 017` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T018) van antes y cada tarea de implementación depende del suyo. T012 empieza por la reproducción del 409 de la ficha. T011 es el test de propiedades. Las pruebas de navegador se corren en local antes y después, con el resultado anotado | test: suite Pest (T034); `ui-audit` local anotado en T015, T018, T030–T033 |
| P3 Capas del módulo | ✅ | T021 solo usa `DateTimeImmutable` en `Domain/`. `now()` solo entra en `Aplication/` (T021, T024) | manual: `/review`; `grep` de `now(` y `Illuminate` en `Domain/` (T034) |
| P4 Contrato de API primero | ❌ aceptado (plan D10) | Excepción aprobada por el usuario el 2026-10-15: códigos 409 → 422 dentro de `/api/v1`. T012 y T013 fijan cada código y texto del contrato antes de T022–T025 | test: T012, T013 |
| P5 Autorización en el servidor | ✅ | T014 amplía la prueba por actor, también con cuerpos inválidos, antes de que T023 añada `authorize()` y T025 cablee el FormRequest | test: T014 |
| P6 Validación con FormRequest | ✅ | T023 crea `UpdateAppointmentRequest` después de su dataset (T012). `POST` sin FormRequest (plan D6) | test: T012 |
| P7 Errores sin detalles internos | ✅ | T012 y T013 afirman los textos exactos de los 422 y del 409 de cita cerrada; T017 fija el 500 genérico de `PUT` y `POST /appointments` antes de que T022 y T025 toquen sus `catch` | test: T012, T013, T017 |
| P8 Secretos fuera del repositorio | ✅ | T004 solo añade `APP_TIMEZONE` por nombre a `.env.example`; `env()` solo en `config/` | lint: gitleaks en CI; `NoEnvOutsideConfigTest` y `grep -rn "env(" app/` en T034 |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna tarea añade paquetes | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada `TM#` tiene tarea de control y de test; ningún `Log::` nuevo | manual: `/review`; `aidd.py validate` |
| P12 Producción con aprobación y rollback | ✅ | T003 cierra la línea base de `composer audit`; T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | T030–T033 solo presentan y llaman a la API; las reglas están en T021–T025 | manual: `/review` |
| P14 Trazabilidad | ➖ | Sin eventos de auditoría ni logs nuevos (plan → Observabilidad) | — |
| P15 Solo el sistema de diseño | ✅ | T001 amplía la regla del rojo antes de las tareas de interfaz, que dependen de ella; T035 regenera la línea base de composición revisando el diff y T037 las capturas del inventario | test: `DesignTokensTest`, `OnlyDesignTokensTest` y `ui-audit` (T034) |
| Definición de terminado | ✅ | T034: Pint, Pest completa, build, `ui-audit` y `aidd.py validate`; T036 y T090–T092 | test: T034 |

## Preparación
- [ ] T001 Ampliar en el sistema de diseño la regla del rojo ("eliminar, cancelar una cita y errores; en cancelar, solo dentro de la confirmación: el botón que confirma y la advertencia de que no se puede deshacer"), el uso de `--color-danger`, el componente propio `calendar/cancel-appointment-confirm` y el inventario ("Agenda, reprogramar cita" con capturas `agenda-cita-reprogramar-*`, "Agenda, detalle de cita asignada (con 'Reprogramar' y 'Cancelar cita')" y "Agenda, confirmar cancelación" con capturas `agenda-cita-cancelar-*`), con su fila en "Decisiones" — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` y `OnlyDesignTokensTest` pasan, `system.html` muestra la regla nueva y ninguna fila del inventario dice "Editar cita"
  - nota: A42 (aceptado) — añadir `docs/specs/017-reprogramar-cancelar-citas/design.md` a sus archivos; al cerrar, `grep "Rojo solo en el botón que confirma"` en `design.md` no da resultados
  - nota: A31 (aceptado) — alinear el anticliché de `design.md` ("Rojo solo en el botón que confirma") con la regla: botón y advertencia
- [ ] T002 Fijar la zona horaria de la clínica por defecto y probar sus efectos: `ClinicTimezoneTest` afirma que `config('app.timezone')` y `date_default_timezone_get()` valen `America/Mexico_City`; `GetTodayAppointmentsTest`, con `travelTo()` a las 23:30 de Ciudad de México (ya el día siguiente en UTC), devuelve las citas de ese día y no las del siguiente — `config/app.php`, `tests/Modules/Core/Unit/ClinicTimezoneTest.php` (nuevo), `tests/Modules/Appointments/Integration/GetTodayAppointmentsTest.php` (nuevo) — hecho cuando: los dos tests fallan antes de cambiar `config/app.php` y pasan después — cubre: CA3, CA4
- [ ] T003 Correr la línea base de dependencias pendiente (plan D9) — `composer audit` en el contenedor; `docs/security.md` solo si hay excepción — hecho cuando: sin vulnerabilidades críticas ni altas, o cada una con su decisión (actualizar dentro de 017 o excepción aprobada por el usuario con vencimiento)
  - nota: requiere reiniciar Docker Desktop (montaje de E: perdido el 2026-10-15)
- [ ] T004 [P] Declarar `APP_TIMEZONE=America/Mexico_City` en `.env.example` y exigirlo en su test — `.env.example`, `tests/Modules/Core/Unit/EnvExampleDefaultsTest.php` — hecho cuando: el test falla sin la variable y pasa con ella
  - nota: A17 (aceptado en /analyze ronda 1 como nota de T002; se hace aquí porque T002 ya toca tres archivos)

## Tests (antes de implementar)
- [ ] T010 [P] Escribir los tests de dominio de la cita: reprogramar a ayer, a hoy a una hora pasada y exactamente a "ahora" lanza `AppointmentInThePastException`; a hoy más tarde y a mañana queda `reprogramada`; reprogramar o cancelar una cita completada o cancelada lanza la excepción de cita cerrada; completar una cita cancelada lanza la excepción de cita cerrada (CA11); crear en el pasado lanza; con un "ahora" de 23:30 en Ciudad de México se aceptan las 23:45 del mismo día — `tests/Modules/Appointments/Unit/Domain/AppointmentEntityTest.php` (nuevo) — hecho cuando: falla por la razón esperada (firma o excepción inexistente) y queda con `skip` hasta T021 — cubre: CA1, CA3, CA4, CA8, CA11
- [ ] T011 [P] Escribir el test de propiedades del "ahora": para todo desplazamiento en minutos respecto a `$now`, `<= 0` se rechaza y `> 0` se acepta, al crear y al reprogramar (`UsesEris`) — `tests/Modules/Appointments/Unit/Domain/AppointmentSchedulePropertiesTest.php` (nuevo) — hecho cuando: falla por la razón esperada y queda con `skip` hasta T021 — cubre: CA3, CA4
- [ ] T012 Reescribir los tests de `PUT /appointments/{id}` con el reloj fijo: reproducción de la ficha: fecha, hora y `status: asignada` → hoy 409, esperado 422 sin cambios; reprogramar a futuro → 200 y `reprogramada` (CA1); solo fecha o solo hora → 422 con "Necesitas proporcionar fecha y hora para reprogramar la cita." (CA2; antes 409); ayer, hoy a una hora pasada y exactamente ahora → 422 con `errors.date` y el texto de CA3, sin cambios; mezcla de estado con fecha u hora → 422 (CA6); `status: asignada` o `reprogramada` sin fecha → 422 (antes 409); cancelar una cita asignada y una reprogramada → 200 y `cancelada` (CA7); reprogramar o cancelar una cita completada o cancelada → 409 con "La cita ya está completada o cancelada y no se puede cambiar." y sin cambios (CA8); `status: completada` sobre una cita cancelada → 409 y sigue cancelada (CA11); `status` con `whatsapp_reminder` → 200 y aplica los dos; reprogramar con `whatsapp_reminder` → 200, la cita `reprogramada` y el recordatorio aplicado (A23); cuerpo vacío `{}` → 409 con `noInfoProvided`, como hoy (A24); cita inexistente → 409; dataset de payloads inválidos del FormRequest (formato de fecha y hora, `whatsapp_reminder` no booleano). — `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php` — hecho cuando: cada caso nuevo o cambiado falla por la razón esperada y queda con `skip` hasta T025; los casos sin cambio siguen en verde con el reloj fijo — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA9, CA11
  - nota: A36 (aceptado) — reprogramar una cita cancelada a una fecha pasada responde 409 con el texto de cita cerrada (precedencia de D8)
- [ ] T013 [P] Añadir con el reloj fijo los rechazos de agendar en el pasado (ayer, hoy a una hora pasada, exactamente ahora → 422 con `errors.date` "No puedes agendar una cita en una fecha u hora que ya pasó." y ninguna cita nueva) y pasar las fechas fijas actuales a fechas relativas al reloj — `tests/Modules/Appointments/Integration/CreateAppointmentTest.php` — hecho cuando: los casos nuevos fallan por la razón esperada y quedan con `skip` hasta T022; los existentes siguen en verde — cubre: CA4
- [ ] T014 [P] Fijar el reloj en la tabla de permisos (las fechas de las líneas 101 y 104 pasan a relativas al instante de `travelTo()`) y ampliarla: reprogramar (`{date, time}`) y cancelar (`{status: cancelada}`) en `PUT /appointments/{id}` para asistente y doctor → 403, paciente → 403 o 401 según la tabla, sin cambios en la cita; y un dataset de cuerpos inválidos (fecha sin hora, estado fuera de la lista, mezcla de estado y fecha) por actor no administrador → 403, nunca 422 — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: pasa hoy (la autorización corre antes de validar en el caso de uso), falla si se quita `assertCan('appointments.update')` en una prueba local que se revierte, y sigue en verde tras T025 — cubre: CA10
- [ ] T015 Describir en el navegador los diálogos nuevos: `screens.json`: `agenda-cita-editar` pasa a `agenda-cita-reprogramar` (`dialog: reprogramar-cita`) y se añade `agenda-cita-cancelar` (`dialogSelector: [data-cancel-appointment-confirm]`, abierto desde "Cancelar cita" del detalle de una cita asignada, sin enviar). `checks-dialogs.mjs`: el botón del detalle dice "Reprogramar" y el diálogo se titula "Reprogramar cita" y no tiene `[data-edit-appointment-status]` (A28); el foco inicial de la confirmación está en "No, mantenerla" y, tras Escape, vuelve a "Cancelar cita"; al cerrar "Reprogramar cita", el foco vuelve a la cita que abrió el detalle; el detalle de la cita completada y el de la cancelada del seeder no muestran `[data-view-appointment-edit]` ni `[data-view-appointment-cancel-appointment]`, y en `expediente-*-cita-detalle` tampoco aparecen (A27). — `tests/Browser/screens.json`, `tests/Browser/checks-dialogs.mjs` — hecho cuando: `ui-audit` local falla en esas comprobaciones por la razón esperada (los diálogos aún no existen), con el resultado anotado: la salida lista `agenda-cita-reprogramar`, `agenda-cita-cancelar`, `agenda-cita-crear` y todos los `*-cita-detalle` (A39) — cubre: CA7, CA8, CA9
  - nota: A40 (aceptado) — `agenda-cita-cancelar` lleva `dialog: cancelar-cita`, como las demás entradas de diálogo
  - nota: A18 (aceptado) — CA8 también con la cita cancelada del seeder
- [ ] T016 [P] Fijar el reloj en el test de logs del flujo de WhatsApp para que su fecha fija no caiga en el pasado con la regla nueva — `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php` — hecho cuando: pasa hoy y sigue en verde tras T021 y T022
- [ ] T017 [P] Añadir dos casos de error inesperado: el repositorio lanza en `PUT /appointments/{id}` y en `POST /appointments` → 500 con `{"error":"Internal server error"}` exacto — `tests/Modules/Appointments/Integration/UnexpectedErrorTest.php` — hecho cuando: pasan hoy (caracterización: los `catch` actuales ya responden el 500 genérico) y siguen en verde tras T022 y T025
- [ ] T018 Describir con la API interceptada los flujos que envían: reprogramar con éxito envía un solo `PUT` con `date` y `time` (sin `status`) y avisa "Cita reprogramada." en el marco (CA1); un 422 en "Reprogramar cita" y otro en "Agendar cita" dejan el diálogo abierto con lo escrito y muestran el texto del servidor en su región (CA5); un 409 muestra el texto genérico del sistema en la región del diálogo; "Sí, cancelar la cita" envía exactamente un `PUT {status: "cancelada"}`, cierra los dos diálogos y avisa "Cita cancelada." (CA7); "No, mantenerla", Escape, la X y el fondo cierran la confirmación sin ninguna petición (CA7); ante un 409 o un 500 al confirmar, la confirmación sigue abierta y anuncia el error en su región (CA7, A29) — `tests/Browser/checks-dialogs.mjs` — hecho cuando: `ui-audit` local falla en esas comprobaciones por la razón esperada, con el resultado anotado — cubre: CA1, CA5, CA7 — depende: T015
  - nota: A18 (aceptado) — CA7 también al cerrar la confirmación con la X y con el fondo
  - nota: A20 (aceptado) — ante un 409, afirmar el texto genérico del sistema en la región del diálogo
  - nota: A35 (aceptado) — el 422 de "Agendar cita" probablemente ya se muestra: esa comprobación es caracterización; el "falla antes" se exige a las de reprogramar y cancelar

## Implementación

### Dominio y API
- [ ] T020 Crear `AppointmentInThePastException` con `forReschedule()` y `forCreation()` (textos de CA3 y CA4) y añadir `AppointmentException::cannotChangeClosedAppointment()` con el texto del plan; `rescheduleRequiresDateOrTime()` se mantiene hasta T024 — `app/Modules/Appointments/Domain/Exceptions/AppointmentInThePastException.php` (nuevo), `app/Modules/Appointments/Domain/Exceptions/AppointmentException.php` — hecho cuando: las clases cargan y la suite sigue en verde — cubre: CA3, CA4, CA8, CA11 — depende: T010
- [ ] T021 Recibir el "ahora" en la entidad: `create(..., DateTimeImmutable $now)` y `reschedule(AppointmentDate, AppointmentTime, DateTimeImmutable $now)` rechazan un momento `<= $now` comparado en la zona de `$now`; `reschedule()` y `cancel()` rechazan citas completadas o canceladas; `complete()` rechaza citas canceladas. Los dos casos de uso pasan `now()` a la firma nueva sin cambiar aún su comportamiento (si falta fecha u hora, el caso de uso sigue lanzando la excepción actual) — `app/Modules/Appointments/Domain/Entities/AppointmentEntity.php`, `app/Modules/Appointments/Aplication/UseCases/CreateAppointmentUseCase.php`, `app/Modules/Appointments/Aplication/UseCases/UpdateAppointmentUseCase.php` — hecho cuando: T010 y T011 pasan sin `skip` y la suite queda en verde: todos los casos sin `skip` de `UpdateAppointmentTest` y `CreateAppointmentTest` en verde (los de T012 y T013 siguen con `skip`), y `CompleteAppointmentTest` en verde — cubre: CA1, CA3, CA4, CA8, CA11 — depende: T011, T012, T013, T014, T016, T020
- [ ] T022 Traducir `AppointmentInThePastException` a 422 `{"message", "errors": {"date": [...]}}` al agendar — `app/Modules/Appointments/Infrastructure/Http/Controllers/CreateAppointmentController.php` — hecho cuando: T013 pasa sin `skip` y T017 sigue en verde — cubre: CA4 — depende: T013, T017, T021
- [ ] T023 [P] Crear `UpdateAppointmentRequest`: `authorize()` llama a `AuthorizationServiceInterface::assertCan('appointments.update')`; reglas y mensajes del contrato del plan (`required_with`, `date_format`, `in:completada,cancelada`, `prohibits:date,time`, `boolean`) — `app/Modules/Appointments/Infrastructure/Http/Requests/UpdateAppointmentRequest.php` (nuevo) — hecho cuando: la clase existe con reglas en array y mensajes en español (sus casos de T012 y T014 pasan en T025) — cubre: CA2, CA6, CA10 — depende: T012, T014
- [ ] T024 Reescribir el caso de uso por tipo de petición: reprogramar (entidad con `now()` y después `ensureAvailable()`; si llega `whatsapp_reminder`, se aplica también), cambiar estado (solo `completada` o `cancelada`, con `whatsapp_reminder` si llega), recordatorio (sin cambio); si se mezclan reprogramación y estado, `AppointmentAplicationExceptions::rescheduleAndStatusTogether()`; `validateReschedule()` corre siempre que llegue fecha u hora; retirar `rescheduleRequiresDateOrTime()` — `app/Modules/Appointments/Aplication/UseCases/UpdateAppointmentUseCase.php`, `app/Modules/Appointments/Aplication/Exceptions/AppointmentAplicationExceptions.php`, `app/Modules/Appointments/Domain/Exceptions/AppointmentException.php` — hecho cuando: `grep rescheduleRequiresDateOrTime app/` no da resultados y la suite queda en verde (los casos de T012 siguen con `skip` hasta T025) — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA11 — depende: T012, T021
- [ ] T025 Usar `UpdateAppointmentRequest` en el controlador y traducir `AppointmentInThePastException` y `rescheduleAndStatusTogether` a 422 con el formato de validación; el resto de `AppointmentAplicationExceptions` (cuerpo vacío) sigue en 409 — `app/Modules/Appointments/Infrastructure/Http/Controllers/UpdateAppointmentController.php` — hecho cuando: T012 pasa sin `skip` y T014 y T017 siguen en verde — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA10, CA11 — depende: T014, T017, T023, T024

### Interfaz de la agenda
- [ ] T030 Convertir "Editar cita" en "Reprogramar cita": Diálogo: título, texto "Elige la nueva fecha y hora de la cita.", sin campo Estado, pie "Cerrar" / "Reprogramar". JS: el cuerpo lleva `date` y `time`, y `whatsapp_reminder` solo si la casilla cambió; el `min` del selector de fecha es hoy; avisa "Cita reprogramada."; el 422 se muestra en la región del diálogo con lo escrito intacto. — `resources/views/components/calendar/edit-appointment-modal.blade.php`, `resources/js/pages/agenda/create-appointment.js` — hecho cuando: las comprobaciones de reprogramar y de agendar de T015 y T018 pasan en `ui-audit` local, con el resultado anotado · verificación manual: diálogo a 390 y 1440 px contra el diseño; reprogramar una cita en local y ver el aviso y la agenda actualizada — cubre: CA1, CA5, CA9 — depende: T001, T015, T018, T025
- [ ] T031 Poner el pie del detalle "Cancelar cita" · "Reprogramar" · "Cerrar" (los tres `secondary`) y mostrar los dos primeros solo en citas asignadas o reprogramadas y solo si su módulo está cargado (no en el expediente) — `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: las comprobaciones del detalle de cita completada y cancelada y de la vuelta del foco al cerrar "Reprogramar cita" de T015 pasan en `ui-audit` local, con el resultado anotado, y el detalle del expediente no muestra los botones · verificación manual: pie a 390 px (dos filas, 44 px) y 1440 px contra el diseño — cubre: CA7, CA8, CA9 — depende: T001, T015, T030
- [ ] T032 Crear la confirmación de cancelar y su script: Componente: `x-ui.dialog size="sm"`, título "Cancelar cita", mensaje con el paciente en negrita, la fecha y la hora, advertencia en `danger`, pie "No, mantenerla" / "Sí, cancelar la cita" (`danger`). Script: `window.agendaCancelAppointment.open(appointment)`; envía `PUT {status: "cancelada"}`; el error va a su región de estado; con éxito cierra la confirmación y el detalle, avisa "Cita cancelada." y recarga la agenda. Registro del script en `vite.config.js`. — `resources/views/components/calendar/cancel-appointment-confirm.blade.php` (nuevo), `resources/js/pages/agenda/cancel-appointment.js` (nuevo), `vite.config.js` — hecho cuando: `npm run build` incluye el script y el componente renderiza con `OnlyDesignTokensTest` en verde — cubre: CA7 — depende: T001, T025
- [ ] T033 Montar la confirmación y su script en la agenda y abrirla desde "Cancelar cita" — `resources/views/pages/agenda/index.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: las comprobaciones de cancelar de T015 y T018 pasan en `ui-audit` local (foco inicial, Escape, vuelta del foco, un solo `PUT`, salir sin petición), con el resultado anotado · verificación manual: confirmar en local, ver "Cita cancelada." y la cita como "Cancelada" a 390 y 1440 px contra el diseño — cubre: CA7 — depende: T018, T031, T032
- [ ] T035 Regenerar la línea base de composición de las entradas de la agenda (`agenda-cita-reprogramar`, `agenda-cita-cancelar`, `agenda-cita-detalle-pendiente`) con `npm run test:ui -- --baseline` y revisar el diff: solo cambian esas entradas — `tests/Browser/baseline-1440.json` — hecho cuando: el diff solo toca las entradas de la agenda y `ui-audit` local pasa la comprobación de composición, con el resultado anotado — cubre: CA7, CA9 — depende: T033
  - nota: A38 (aceptado) — el diff no deja entradas obsoletas de la agenda (`agenda-cita-editar`)
- [ ] T037 Generar las capturas del inventario que enlaza T001 con `npm run test:ui -- --only agenda-cita-reprogramar,agenda-cita-cancelar,agenda-cita-detalle-pendiente --screenshots docs/design/capturas` y retirar `agenda-cita-editar-*` — `docs/design/capturas/ (generado por npm run test:ui -- --screenshots)` — hecho cuando: existen `agenda-cita-reprogramar-*`, `agenda-cita-cancelar-*` y la `agenda-cita-detalle-pendiente-*` renovada, ya no existe `agenda-cita-editar-*`, y todo enlace del inventario de `system.md` y `system.html` apunta a un archivo que existe — cubre: CA7, CA9 — depende: T001, T035
  - nota: A41 (aceptado) — en `docs/design/capturas/` solo quedan, de esas entradas, las capturas a 390 y 1440 px que enlaza el inventario (las de 768 px se borran)
- [ ] T034 Cerrar la implementación: Pint (`--dirty`) y suite Pest completa sin ninguna marca `skip('017:` (incluidos `CompleteAppointmentTest`, `WhatsAppFlowLogsTest`, `AppointmentsAccessControlTest` y `UnexpectedErrorTest`). `npm run build` y `ui-audit` dos veces seguidas en verde. `grep` de `now(`, `Illuminate` y `Carbon` en `app/Modules/Appointments/Domain/` sin resultados nuevos; `grep -rn "env(" app/` sin resultados nuevos. `python .ai/bin/aidd.py validate` sin errores. — ninguno (solo verificación) — hecho cuando: todo lo anterior en verde — depende: T002, T003, T004, T022, T035, T037

## Integración y documentación
- [ ] T036 Registrar en el Historial de las specs extendidas el cambio de 017: 007 (CA3 y CA4 pasan de 409 a 422, CA14 cumplido, "citas de hoy" en la zona de la clínica, completar una cita cancelada se rechaza), 011 y 012 (vigencia de las promociones en la zona de la clínica) — `docs/specs/007-agenda-de-citas/spec.md`, `docs/specs/011-sitio-publico/spec.md`, `docs/specs/012-gestion-de-contenido/spec.md` — hecho cuando: cada Historial tiene una fila que cita 017 y `aidd.py validate` no da errores nuevos — depende: T034
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: "Contrato entre capas" (un `PUT` de cita hace una sola acción; rechazos de entrada en 422) y "Backend" (el dominio de citas recibe el "ahora"; zona `America/Mexico_City`)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: `APP_TIMEZONE` en las variables de producción y comprobar antes del deploy que el `.env` de producción no fija otra zona
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097. Reprogramar una cita de prueba a futuro (CA1), intentar una hora pasada (CA3, 422 con el mensaje), cancelar con confirmación (CA7), y comprobar en Loki que no hay 500 nuevos en `/api/v1/appointments*`.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T018 | T021, T024, T025, T030 |
| CA2 | T012 | T023, T024, T025 |
| CA3 | T002, T010, T011, T012 | T020, T021, T024, T025 |
| CA4 | T002, T010, T011, T013 | T020, T021, T022 |
| CA5 | T018 | T030 |
| CA6 | T012 | T023, T024, T025 |
| CA7 | T012, T015, T018 | T024, T025, T031, T032, T033, T035, T037 |
| CA8 | T010, T012, T015 | T020, T021, T024, T025, T031 |
| CA9 | T012, T015 | T024, T030, T031, T035, T037 |
| CA10 | T014 | T023, T025 |
| CA11 | T010, T012 | T020, T021, T024, T025 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T023, T025 | T014 |
| TM2 | T023, T024 | T012 |
| TM3 | T021 | T010, T012 |
| TM4 | T021, T022 | T010, T011, T012, T013 |
| TM5 | T022, T025 | T012, T013, T017 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Appointments · Domain (`AppointmentEntity`) | T021 |
| Appointments · Domain (excepciones) | T020, T024 |
| Appointments · Aplication (`UpdateAppointmentUseCase`) | T021, T024 |
| Appointments · Aplication (`CreateAppointmentUseCase`) | T021 |
| Appointments · Aplication (`AppointmentAplicationExceptions`) | T024 |
| Appointments · Infrastructure (`UpdateAppointmentRequest`) | T023 |
| Appointments · Infrastructure (controladores) | T022, T025 |
| Configuración (`config/app.php`, `.env.example`) | T002, T004 |
| Interfaz · agenda (reprogramar) | T030 |
| Interfaz · agenda (detalle) | T031, T033 |
| Interfaz · agenda (confirmación y script) | T032, T033 |
| Sistema de diseño | T001, T037 |
| Tests de navegador | T015, T018, T035 |
| Tests existentes con fechas fijas | T012, T013, T014, T016 |
| Historial de 007, 011 y 012 | T036 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| "Ahora" en la zona de Ciudad de México | T002, T010 |
| La misma zona rige "citas de hoy" (007) y la vigencia de las promociones (011, 012) | T002 (`ClinicTimezoneTest`, `GetTodayAppointmentsTest`), T036 |
| Solo sistema de diseño, 390/768/1440 px | T001, T035, T034 (`ui-audit`, `OnlyDesignTokensTest`, `DesignTokensTest`) |
| Contraste, foco y teclado (WCAG AA) | T015, T034 |
| Mensajes en español y 500 genérico | T012, T013, T017, T018, T034 |
