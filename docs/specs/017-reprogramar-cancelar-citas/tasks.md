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
  implementa quita esa marca. T034 falla si queda alguna.
- **Reloj fijo:** los tests de integración tocados usan `$this->travelTo()` a un instante fijo de
  `America/Mexico_City`, en `beforeEach`. Las fechas de los payloads se escriben relativas a ese
  instante.
- `ui-audit` = `npm run test:ui -- --only agenda`, a 390, 768 y 1440 px. Las pruebas de navegador
  (T015) no admiten `skip`: fallan hasta T030–T033 y solo corren en CI, que no se lanza hasta el
  push de la rama.
- "diseño" = [design.md](design.md) → "Composición y jerarquía".

## Constitution Check
Las tareas no añaden nada al análisis de [plan.md](plan.md); se revisa lo que el desglose pudo romper.

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Toda tarea traza a un criterio o a un cambio del plan (tablas de Cobertura) | manual: `/analyze 017` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T015) van antes y cada tarea de implementación depende del suyo. T012 empieza por la reproducción del 409 de la ficha. T011 es el test de propiedades | test: suite Pest (T034); `npm run test:ui` en CI |
| P3 Capas del módulo | ✅ | T021 solo usa `DateTimeImmutable` en `Domain/`. `now()` solo entra en T022 y T024 (`Aplication/`) | manual: `/review`; `grep` de `now(` y `Illuminate` en `Domain/` (T034) |
| P4 Contrato de API primero | ✅ | T012 y T013 fijan cada código HTTP del contrato del plan antes de T022–T025 | test: T012, T013 |
| P5 Autorización en el servidor | ✅ | La autorización no cambia; T014 amplía la prueba por actor antes de tocar el controlador | test: T014 |
| P6 Validación con FormRequest | ✅ | T023 crea `UpdateAppointmentRequest` después de su dataset (T012) | test: T012 |
| P7 Errores sin detalles internos | ✅ | T012 y T013 afirman los textos exactos de los 422; los 500 no cambian | test: T012, T013; `UnexpectedErrorTest` en T034 |
| P8 Secretos fuera del repositorio | ✅ | T002 solo añade `APP_TIMEZONE` por nombre a `.env.example`; `env()` solo en `config/` | lint: gitleaks en CI; `grep -rn "env(" app/` en T034 |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna tarea añade paquetes | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada `TM#` tiene tarea de control y de test; ningún `Log::` nuevo | manual: `/review`; `aidd.py validate` |
| P12 Producción con aprobación y rollback | ✅ | T003 cierra la línea base de `composer audit`; T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | T030–T033 solo presentan y llaman a la API; las reglas están en T021–T025 | manual: `/review` |
| P14 Trazabilidad | ➖ | Sin eventos de auditoría ni logs nuevos (plan → Observabilidad) | — |
| P15 Solo el sistema de diseño | ✅ | T001 amplía la regla del rojo antes de las tareas de interfaz, que dependen de ella | test: `DesignTokensTest`, `OnlyDesignTokensTest` y `ui-audit` (T034) |
| Definición de terminado | ✅ | T034: Pint, Pest completa, build, `ui-audit` y `aidd.py validate`; T090–T092 | test: T034 |

## Preparación
- [ ] T001 Ampliar en el sistema de diseño la regla del rojo ("eliminar, cancelar una cita y errores; en cancelar, solo en el botón que la confirma"), el uso de `--color-danger` y el inventario ("Agenda, reprogramar cita" y "Agenda, confirmar cancelación"), con su fila en "Decisiones" — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` y `OnlyDesignTokensTest` pasan y `system.html` muestra la regla nueva
- [ ] T002 Fijar la zona horaria de la clínica por defecto y declararla, con un test que lo compruebe — `config/app.php`, `.env.example`, `tests/Modules/Appointments/Integration/ClinicTimezoneTest.php` (nuevo) — hecho cuando: el test afirma que `config('app.timezone')` es `America/Mexico_City` y pasa; `.env.example` tiene `APP_TIMEZONE=America/Mexico_City`
- [ ] T003 Correr la línea base de dependencias pendiente (plan D9) — `composer audit` en el contenedor; `docs/security.md` solo si hay excepción — hecho cuando: sin vulnerabilidades críticas ni altas, o cada una con su decisión (actualizar dentro de 017 o excepción aprobada por el usuario con vencimiento)
  - nota: requiere reiniciar Docker Desktop (montaje de E: perdido el 2026-10-15)

## Tests (antes de implementar)
- [ ] T010 [P] Escribir los tests de dominio de la cita: reprogramar a ayer, a hoy a una hora pasada y exactamente a "ahora" lanza `AppointmentInThePastException`; a hoy más tarde y a mañana queda `reprogramada`; reprogramar o cancelar una cita completada o cancelada lanza la excepción de cita cerrada; crear en el pasado lanza; con un "ahora" de 23:30 en Ciudad de México se aceptan las 23:45 del mismo día — `tests/Modules/Appointments/Unit/Domain/AppointmentEntityTest.php` (nuevo) — hecho cuando: falla por la razón esperada (firma o excepción inexistente) y queda con `skip` hasta T021 — cubre: CA1, CA3, CA4, CA8
- [ ] T011 [P] Escribir el test de propiedades del "ahora": para todo desplazamiento en minutos respecto a `$now`, `<= 0` se rechaza y `> 0` se acepta, al crear y al reprogramar (`UsesEris`) — `tests/Modules/Appointments/Unit/Domain/AppointmentSchedulePropertiesTest.php` (nuevo) — hecho cuando: falla por la razón esperada y queda con `skip` hasta T021 — cubre: CA3, CA4
- [ ] T012 Reescribir los tests de `PUT /appointments/{id}` con el reloj fijo: reproducción de la ficha: fecha, hora y `status: asignada` → hoy 409, esperado 422 sin cambios; reprogramar a futuro → 200 y `reprogramada` (CA1); solo fecha o solo hora → 422 con "Necesitas proporcionar fecha y hora para reprogramar la cita." (CA2; antes 409); ayer, hoy a una hora pasada y exactamente ahora → 422 con `errors.date` y el texto de CA3, sin cambios; mezcla de estado con fecha u hora → 422 (CA6); `status: asignada` o `reprogramada` sin fecha → 422 (antes 409); cancelar una cita asignada y una reprogramada → 200 y `cancelada` (CA7); reprogramar o cancelar una cita completada o cancelada → 409 sin cambios (CA8); dataset de payloads inválidos del FormRequest (formato de fecha y hora, `whatsapp_reminder` no booleano). — `tests/Modules/Appointments/Integration/UpdateAppointmentTest.php` — hecho cuando: cada caso nuevo o cambiado falla por la razón esperada y queda con `skip` hasta T025; los casos sin cambio siguen en verde con el reloj fijo — cubre: CA1, CA2, CA3, CA6, CA7, CA8
- [ ] T013 [P] Añadir con el reloj fijo los rechazos de agendar en el pasado (ayer, hoy a una hora pasada, exactamente ahora → 422 con `errors.date` "No puedes agendar una cita en una fecha u hora que ya pasó." y ninguna cita nueva) y pasar las fechas fijas actuales a fechas relativas al reloj — `tests/Modules/Appointments/Integration/CreateAppointmentTest.php` — hecho cuando: los casos nuevos fallan por la razón esperada y quedan con `skip` hasta T022; los existentes siguen en verde — cubre: CA4
- [ ] T014 [P] Ampliar la tabla de permisos por actor con reprogramar (`{date, time}`) y cancelar (`{status: cancelada}`) en `PUT /appointments/{id}`: asistente y doctor → 403, paciente → 403 o 401 según la tabla, sin cambios en la cita — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: pasa (la autorización no cambia) y falla si se quita `assertCan('appointments.update')` en una prueba local que se revierte — cubre: CA10
- [ ] T015 Describir en el navegador los diálogos nuevos: `screens.json`: `agenda-cita-editar` pasa a `agenda-cita-reprogramar` (`dialog: reprogramar-cita`) y se añade `agenda-cita-cancelar` (`dialogSelector: [data-cancel-appointment-confirm]`, abierto desde "Cancelar cita" del detalle de una cita asignada, sin enviar). `checks-dialogs.mjs`: el diálogo de reprogramar no tiene `[data-edit-appointment-status]`; el foco inicial de la confirmación está en "No, mantenerla" y, tras Escape, vuelve a "Cancelar cita"; el detalle de la cita completada del seeder no muestra `[data-view-appointment-edit]` ni `[data-view-appointment-cancel-appointment]`. — `tests/Browser/screens.json`, `tests/Browser/checks-dialogs.mjs` — hecho cuando: `ui-audit` falla en esas comprobaciones por la razón esperada (los diálogos aún no existen) — cubre: CA5, CA7, CA8, CA9

## Implementación

### Dominio y API
- [ ] T020 Crear `AppointmentInThePastException` con `forReschedule()` y `forCreation()` (textos de CA3 y CA4) y añadir `AppointmentException::cannotChangeClosedAppointment()`; retirar `rescheduleRequiresDateOrTime()` — `app/Modules/Appointments/Domain/Exceptions/AppointmentInThePastException.php` (nuevo), `app/Modules/Appointments/Domain/Exceptions/AppointmentException.php` — hecho cuando: las clases cargan y `grep rescheduleRequiresDateOrTime app/` no da resultados — cubre: CA3, CA4, CA8 — depende: T010
- [ ] T021 Recibir el "ahora" en la entidad: `create(..., DateTimeImmutable $now)` y `reschedule(AppointmentDate, AppointmentTime, DateTimeImmutable $now)` rechazan un momento `<= $now` comparado en la zona de `$now`; `reschedule()` y `cancel()` rechazan citas completadas o canceladas — `app/Modules/Appointments/Domain/Entities/AppointmentEntity.php` — hecho cuando: T010 y T011 pasan sin `skip` — cubre: CA1, CA3, CA4, CA8 — depende: T011, T020
- [ ] T022 Pasar `now()` a `AppointmentEntity::create()` antes de `ensureAvailable()` y traducir `AppointmentInThePastException` a 422 `{"message", "errors": {"date": [...]}}` al agendar — `app/Modules/Appointments/Aplication/UseCases/CreateAppointmentUseCase.php`, `app/Modules/Appointments/Infrastructure/Http/Controllers/CreateAppointmentController.php` — hecho cuando: T013 pasa sin `skip` — cubre: CA4 — depende: T013, T021
- [ ] T023 [P] Crear `UpdateAppointmentRequest` con las reglas y los mensajes del contrato del plan (`required_with`, `date_format`, `in:completada,cancelada`, `prohibits:date,time`, `boolean`) — `app/Modules/Appointments/Infrastructure/Http/Requests/UpdateAppointmentRequest.php` (nuevo) — hecho cuando: la clase existe con reglas en array y mensajes en español (sus casos de T012 pasan en T025) — cubre: CA2, CA6 — depende: T012
- [ ] T024 Reescribir el caso de uso por tipo de petición: reprogramar (entidad con `now()` y después `ensureAvailable()`), cambiar estado (solo `completada` o `cancelada`), recordatorio (sin cambio); si se mezclan reprogramación y estado, `AppointmentAplicationExceptions::rescheduleAndStatusTogether()`; `validateReschedule()` corre siempre que llegue fecha u hora — `app/Modules/Appointments/Aplication/UseCases/UpdateAppointmentUseCase.php`, `app/Modules/Appointments/Aplication/Exceptions/AppointmentAplicationExceptions.php` — hecho cuando: los casos de T012 del caso de uso pasan al cablear T025 — cubre: CA1, CA2, CA3, CA6, CA7, CA8 — depende: T012, T021
- [ ] T025 Usar `UpdateAppointmentRequest` en el controlador y traducir `AppointmentInThePastException` y `rescheduleAndStatusTogether` a 422 con el formato de validación — `app/Modules/Appointments/Infrastructure/Http/Controllers/UpdateAppointmentController.php` — hecho cuando: T012 y T014 pasan sin `skip` — cubre: CA1, CA2, CA3, CA6, CA7, CA8, CA10 — depende: T014, T023, T024

### Interfaz de la agenda
- [ ] T030 Convertir "Editar cita" en "Reprogramar cita": Diálogo: título, texto "Elige la nueva fecha y hora de la cita.", sin campo Estado, pie "Cerrar" / "Reprogramar". JS: el cuerpo lleva `date` y `time`, y `whatsapp_reminder` solo si la casilla cambió; el `min` del selector de fecha es hoy; avisa "Cita reprogramada."; el 422 se muestra en la región del diálogo con lo escrito intacto. — `resources/views/components/calendar/edit-appointment-modal.blade.php`, `resources/js/pages/agenda/create-appointment.js` — hecho cuando: las comprobaciones de reprogramar de T015 y el caso de 422 de `checks-a11y` pasan en `ui-audit` · verificación manual: diálogo a 390 y 1440 px contra el diseño; reprogramar una cita en local y ver el aviso y la agenda actualizada — cubre: CA1, CA5, CA9 — depende: T001, T015, T025
- [ ] T031 Poner el pie del detalle "Cancelar cita" · "Reprogramar" · "Cerrar" (los tres `secondary`) y mostrar los dos primeros solo en citas asignadas o reprogramadas y solo si su módulo está cargado (no en el expediente) — `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: la comprobación del detalle de cita completada de T015 pasa y el detalle del expediente no muestra los botones · verificación manual: pie a 390 px (dos filas, 44 px) y 1440 px contra el diseño — cubre: CA7, CA8, CA9 — depende: T001, T015, T030
- [ ] T032 Crear la confirmación de cancelar y su script: Componente: `x-ui.dialog size="sm"`, título "Cancelar cita", mensaje con el paciente en negrita, la fecha y la hora, advertencia en `danger`, pie "No, mantenerla" / "Sí, cancelar la cita" (`danger`). Script: `window.agendaCancelAppointment.open(appointment)`; envía `PUT {status: "cancelada"}`; el error va a su región de estado; con éxito cierra la confirmación y el detalle, avisa "Cita cancelada." y recarga la agenda. Registro del script en `vite.config.js`. — `resources/views/components/calendar/cancel-appointment-confirm.blade.php` (nuevo), `resources/js/pages/agenda/cancel-appointment.js` (nuevo), `vite.config.js` — hecho cuando: `npm run build` incluye el script y el componente renderiza con `OnlyDesignTokensTest` en verde — cubre: CA7 — depende: T001, T025
- [ ] T033 Montar la confirmación y su script en la agenda y abrirla desde "Cancelar cita" — `resources/views/pages/agenda/index.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: las comprobaciones de cancelar de T015 pasan en `ui-audit` (foco inicial, Escape y vuelta del foco) · verificación manual: confirmar en local, ver "Cita cancelada." y la cita como "Cancelada" a 390 y 1440 px contra el diseño — cubre: CA7 — depende: T031, T032
- [ ] T034 Cerrar la implementación: Pint (`--dirty`) y suite Pest completa sin ninguna marca `skip('017:`. `npm run build` y `ui-audit` dos veces seguidas en verde. `grep` de `now(`, `Illuminate` y `Carbon` en `app/Modules/Appointments/Domain/` sin resultados nuevos; `grep -rn "env(" app/` sin resultados nuevos. `python .ai/bin/aidd.py validate` sin errores. — ninguno (solo verificación) — hecho cuando: todo lo anterior en verde — depende: T002, T003, T022, T033

## Integración y documentación
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
| CA1 | T010, T012 | T021, T024, T025, T030 |
| CA2 | T012 | T023, T024, T025 |
| CA3 | T010, T011, T012 | T020, T021, T024, T025 |
| CA4 | T010, T011, T013 | T020, T021, T022 |
| CA5 | T015 | T030 |
| CA6 | T012 | T023, T024, T025 |
| CA7 | T012, T015 | T024, T025, T031, T032, T033 |
| CA8 | T010, T012, T015 | T020, T021, T024, T025, T031 |
| CA9 | T015 | T030, T031 |
| CA10 | T014 | T025 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T025 | T014 |
| TM2 | T023, T024 | T012 |
| TM3 | T021 | T010, T012 |
| TM4 | T021, T022 | T010, T011, T012, T013 |
| TM5 | T022, T025 | T012, T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Appointments · Domain (`AppointmentEntity`) | T021 |
| Appointments · Domain (excepciones) | T020 |
| Appointments · Aplication (`UpdateAppointmentUseCase`) | T024 |
| Appointments · Aplication (`CreateAppointmentUseCase`) | T022 |
| Appointments · Aplication (`AppointmentAplicationExceptions`) | T024 |
| Appointments · Infrastructure (`UpdateAppointmentRequest`) | T023 |
| Appointments · Infrastructure (controladores) | T022, T025 |
| Configuración (`config/app.php`, `.env.example`) | T002 |
| Interfaz · agenda (reprogramar) | T030 |
| Interfaz · agenda (detalle) | T031, T033 |
| Interfaz · agenda (confirmación y script) | T032, T033 |
| Sistema de diseño | T001 |
| Tests de navegador | T015 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| "Ahora" en la zona de Ciudad de México | T002, T010 |
| Solo sistema de diseño, 390/768/1440 px | T001, T034 (`ui-audit`, `OnlyDesignTokensTest`, `DesignTokensTest`) |
| Contraste, foco y teclado (WCAG AA) | T015, T034 |
| Mensajes en español y 500 genérico | T012, T013, T034 (`UnexpectedErrorTest`) |
