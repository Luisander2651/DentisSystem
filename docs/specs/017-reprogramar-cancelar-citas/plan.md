---
spec: 017-reprogramar-cancelar-citas
status: approved   # reaprobado por el usuario, 2026-10-15, tras /plan --fix (analysis ronda 1)
constitution_version: 1.2.0
created: 2026-10-15
---

# Plan · 017 Reprogramar y cancelar citas

## Enfoque técnico
La regla "no en el pasado" y las transiciones válidas viven en `AppointmentEntity`. La entidad
recibe el "ahora" desde el caso de uso (`now()`, en `America/Mexico_City`) en lugar de leer el
reloj, y lanza excepciones de dominio propias. `PUT /api/v1/appointments/{id}` hace una sola cosa
por petición, y sus reglas de entrada pasan a un `FormRequest` nuevo: reprogramar (fecha y hora
juntas), cambiar el estado o cambiar el recordatorio. Los rechazos de entrada responden 422 con el
formato de validación de Laravel, que es el único que la interfaz muestra tal cual
(`status.js → validationMessage`). En la interfaz, "Editar cita" pasa a ser "Reprogramar" y deja
de enviar el estado. "Cancelar cita" abre una confirmación propia y envía solo `status: cancelada`
por la misma ruta.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 017 `approved`, `design.md` `approved`; la ficha del bug está `escalated` a 017 | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene su test. El primero reproduce el 409 de la ficha (reprogramar con `status: asignada`). Los diálogos se prueban en navegador. La regla del pasado tiene test de propiedades (Eris) | test: Trazabilidad; `npm run test:ui` |
| P3 Capas del módulo | ✅ | `Domain/` recibe `DateTimeImmutable $now` y no usa facades; `now()` solo en `Aplication/UseCases`. Sin dependencias nuevas entre módulos | manual: `/review` busca `use Illuminate\|Carbon\|now(` en `Domain/` tocado |
| P4 Contrato de API primero | ❌ aceptado: cambio incompatible de códigos (409 → 422) dentro de `/api/v1` sin `/api/v2`; el único cliente es el JS de la agenda, que se despliega en la misma imagen, y no hay clientes externos ni datos reales — aprobado por el usuario el 2026-10-15 (/analyze A1, D10) | Contrato de `PUT` y `POST /appointments` en "Contratos y datos", con la lista de cambios frente a 007 | test: `UpdateAppointmentTest`, `CreateAppointmentTest` fijan el código y el texto de cada rechazo |
| P5 Autorización en el servidor | ✅ | El permiso de `PUT` no cambia (`appointments.update` solo administrador, middleware `staff`). `UpdateAppointmentRequest::authorize()` llama a `assertCan('appointments.update')` antes de validar, para que quien no es administrador reciba 403 con cualquier cuerpo (CA10, D11); el caso de uso lo repite | test: `AppointmentsAccessControlTest` (actor × cancelar, reprogramar y cuerpos inválidos) |
| P6 Validación con FormRequest | ✅ | La entrada de `PUT` cambia → `UpdateAppointmentRequest` nuevo (reglas en array, mensajes en español). La entrada de `POST` no cambia: la fecha pasada es invariante de dominio, no regla de entrada → sin FormRequest (Decisión D6) | test: datasets de payload inválido → 422 en `UpdateAppointmentTest` |
| P7 Errores sin detalles internos | ✅ | Los 500 siguen en `UnexpectedErrorResponse`. Los mensajes 422 y 409 nuevos no llevan datos personales | test: `UnexpectedErrorTest` existente; `grep getMessage()` en los controladores tocados solo para excepciones de negocio |
| P8 Secretos fuera del repositorio | ✅ | `APP_TIMEZONE` se añade por nombre a `.env.example`; no es secreto. Sin `env()` fuera de `config/` | lint: gitleaks en CI; `grep -rn "env(" app/` en tocado |
| P9 Migraciones reversibles | ➖ | Sin migraciones: los estados ya existen en la columna `status` | — |
| P10 Dependencias nuevas con ADR | ➖ | Ninguna dependencia nueva | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | La cita tiene paciente y tratamiento: hay modelo de amenazas. Ningún log nuevo con nombre ni teléfono | test: TM1–TM4; manual: `/review` de `Log::` tocados |
| P12 Producción con aprobación humana | ✅ | Release con `/release`, tag y `deploy.sh`, con aprobación del dueño. `composer audit` pendiente de la línea base (ver Riesgos) | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Pasado, transiciones y no-mezcla en el servidor. La interfaz solo oculta botones y limita el selector de fecha como ayuda | test: integración de CA3, CA4, CA6, CA8 sin pasar por la interfaz |
| P14 Trazabilidad y auditoría | ➖ | La spec no tiene eventos de auditoría (el registro aún no existe, roadmap objetivo 5). Sin logs nuevos; los existentes ya llevan `request_id` | — |
| P15 Solo el sistema de diseño | ✅ | Solo `x-ui.dialog` y `x-ui.button` (`secondary`, `primary`, `danger`). La regla del rojo se amplía en `system.md` antes del código (T001) | test: `OnlyDesignTokensTest`, `DesignTokensTest`, `npm run test:ui` |

Resultado: 11 ✅ · 3 ➖ · 1 ❌ aceptado (P4).

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Appointments · Domain | `Domain/Entities/AppointmentEntity.php`: `create(..., DateTimeImmutable $now)` y `reschedule(AppointmentDate, AppointmentTime, DateTimeImmutable $now)` (fecha y hora obligatorias) rechazan un momento `<= $now` comparado en la zona de `$now`. `reschedule()` y `cancel()` rechazan citas completadas o canceladas; `complete()` rechaza citas canceladas (CA11; completar una cita ya completada no cambia, D12) | medio: firma usada por los casos de uso y por tests existentes |
| Appointments · Domain | `Domain/Exceptions/AppointmentInThePastException.php` (nueva): `forReschedule()` y `forCreation()` con los textos de CA3 y CA4. `AppointmentException::cannotChangeClosedAppointment()` (nueva fábrica, 409, texto "La cita ya está completada o cancelada y no se puede cambiar."). Se retira `rescheduleRequiresDateOrTime()` | bajo |
| Appointments · Aplication | `UseCases/UpdateAppointmentUseCase.php`: rama por tipo de petición. Reprogramar: entidad, luego `ensureAvailable()`. Estado: solo `completada` o `cancelada`. Recordatorio: como hoy. Si se mezclan reprogramación y estado → `AppointmentAplicationExceptions::rescheduleAndStatusTogether()` (defensa además del FormRequest). `validateReschedule()` siempre corre si llega fecha u hora | medio: es el arreglo del bug |
| Appointments · Aplication | `UseCases/CreateAppointmentUseCase.php`: pasa `now()` a `AppointmentEntity::create()` antes de `ensureAvailable()` | bajo |
| Appointments · Infrastructure | `Http/Requests/UpdateAppointmentRequest.php` (nuevo): `authorize()` llama a `AuthorizationServiceInterface::assertCan('appointments.update')` (su `AuthorizationException` ya se traduce a 403 en `bootstrap/app.php`), así la autorización corre antes que las reglas; `date` `required_with:time` y `date_format:Y-m-d`; `time` `required_with:date` y `date_format:H:i` o `H:i:s`; `status` `in:completada,cancelada` y `prohibits:date,time`; `whatsapp_reminder` `boolean`; mensajes en español | bajo |
| Appointments · Infrastructure | `Http/Controllers/UpdateAppointmentController.php`: usa el FormRequest. `AppointmentInThePastException` y `rescheduleAndStatusTogether` → 422 `{"message", "errors": {campo: [mensaje]}}`. `CreateAppointmentController.php`: `AppointmentInThePastException` → 422 con el mismo formato | bajo |
| Appointments · Aplication | `Exceptions/AppointmentAplicationExceptions.php`: `rescheduleAndStatusTogether()` | bajo |
| Configuración | `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'America/Mexico_City')`. `.env.example`: `APP_TIMEZONE=America/Mexico_City`. Es global (D13): también rige "citas de hoy" (`GetTodayAppointmentsUseCase`, `now()->toDateString()`) y la vigencia de las promociones (`PromotionEntity::isExpired()`, `new DateTime()`, que toma la zona por defecto de PHP que Laravel fija desde `app.timezone`), sin cambiar el código de esos módulos | bajo: los `created_at` de datos de prueba y la hora de los logs cambian de zona (sin datos reales) |
| Interfaz · agenda | `resources/views/components/calendar/edit-appointment-modal.blade.php`: título "Reprogramar cita", texto de entrada, sin campo Estado, pie "Cerrar" / "Reprogramar" | bajo |
| Interfaz · agenda | `resources/views/components/calendar/view-appointment-modal.blade.php`: pie "Cancelar cita" (`data-view-appointment-cancel-appointment`, oculto) · "Reprogramar" (antes "Editar cita") · "Cerrar" | bajo |
| Interfaz · agenda | `resources/views/components/calendar/cancel-appointment-confirm.blade.php` (nuevo): `x-ui.dialog size="sm"` con título, mensaje con el paciente en negrita, advertencia en `danger`, pie "No, mantenerla" / "Sí, cancelar la cita" (`danger`); se monta en `pages/agenda/index.blade.php` | bajo |
| Interfaz · agenda | `resources/js/pages/agenda/create-appointment.js`: sin `editStatusSelect`. El cuerpo de reprogramar lleva `date` y `time`, y `whatsapp_reminder` solo si la casilla cambió. `min` del selector de fecha = hoy. Aviso "Cita reprogramada." | medio: archivo grande compartido con agendar |
| Interfaz · agenda | `resources/js/pages/agenda/cancel-appointment.js` (nuevo, registrado en `vite.config.js` y cargado en `pages/agenda/index.blade.php`): expone `window.agendaCancelAppointment.open(appointment)`, envía `PUT {status: "cancelada"}`, avisa "Cita cancelada." y recarga la agenda | bajo |
| Interfaz · agenda | `resources/js/pages/agenda/view-appointment.js`: muestra "Cancelar cita" y "Reprogramar" solo en asignada o reprogramada y solo si su módulo está cargado (en el expediente no lo está) | bajo |
| Sistema de diseño | `docs/design/system.md` y `system.html`: regla del rojo, uso de `--color-danger`, inventario ("reprogramar cita", "confirmar cancelación") | bajo |
| Tests de navegador | `tests/Browser/screens.json`: `agenda-cita-editar` → `agenda-cita-reprogramar` (`dialog: reprogramar-cita`); entrada nueva `agenda-cita-cancelar` (`dialogSelector: [data-cancel-appointment-confirm]`, sin enviar) | bajo |

## Contratos y datos

### `PUT /api/v1/appointments/{id}` (modificado)
Autorización sin cambios: `auth:sanctum` + `staff` + `appointments.update` (solo administrador).
Una petición hace **una** de estas cosas:

| Petición | Cuerpo | Éxito | Rechazos |
|---|---|---|---|
| Reprogramar | `{"date": "2026-10-20", "time": "10:00", "whatsapp_reminder"?: bool}` | 200 `{"message", "data"}`; la cita queda `reprogramada` | 422 falta fecha u hora · 422 fecha u hora pasada · 409 choque de horario · 409 cita completada o cancelada · 409 no existe (como hoy, `AppointmentException::notFound`; sin cambio) |
| Cambiar estado | `{"status": "completada" \| "cancelada", "whatsapp_reminder"?: bool}` | 200; si llega `whatsapp_reminder`, se aplica también, como hoy | 422 valor fuera de la lista · 409 cancelar una cita completada o cancelada · 409 completar una cita cancelada (CA11) · 409 no existe |
| Recordatorio | `{"whatsapp_reminder": bool}` | 200 (sin cambio de comportamiento, 007 CA6) | — |
| Mezcla | `status` junto con `date` o `time` | — | 422 |
| Cualquiera, sin ser administrador | cualquier cuerpo, válido o no | — | 403 antes de validar (CA10); 401 sin sesión |

Formato del 422 (el de Laravel, que la interfaz muestra tal cual):
```json
{ "message": "No puedes reprogramar la cita a una fecha u hora que ya pasó.",
  "errors": { "date": ["No puedes reprogramar la cita a una fecha u hora que ya pasó."] } }
```
Texto del 409 de cita cerrada (`{"error"}`): "La cita ya está completada o cancelada y no se puede cambiar."
En los 409 la interfaz muestra el texto genérico del sistema (`status.js`), como hoy.

Textos del FormRequest:
- `date.required_with` y `time.required_with`: "Necesitas proporcionar fecha y hora para reprogramar la cita."
- `status.prohibits`: "No puedes reprogramar y cambiar el estado de la cita a la vez."
- `status.in`: "El estado solo puede cambiar a completada o cancelada."
- `date.date_format`: "La fecha no tiene un formato válido."
- `time.date_format`: "La hora no tiene un formato válido."

Cambios frente a 007:
- CA3: 409 → 422.
- CA4: un valor fuera de la lista pasa de 409 a 422. La transición inválida sobre una cita cerrada sigue en 409, y se añade completar una cita cancelada (antes la reabría).
- CA14: queda cumplido con CA3 y CA4 de esta spec.
- "Citas de hoy": el día se calcula en la zona de la clínica.

### `POST /api/v1/appointments` (modificado solo en un rechazo)
Mismo cuerpo. Nuevo: 422 con `errors.date` = "No puedes agendar una cita en una fecha u hora que
ya pasó." cuando la fecha y hora son `<=` ahora en la zona de la clínica.

### Datos
Sin migraciones. "Ahora" = `now()` con `config('app.timezone')` = `America/Mexico_City`. `date` y
`time` se guardan como hora local de la clínica (sin cambio).

### Pantallas (según `design.md`)
| Pantalla | Estados | Accesibilidad |
|---|---|---|
| Detalle de cita | asignada o reprogramada (3 botones) · completada o cancelada (solo "Cerrar") · en el expediente (sin acciones de agenda) | botones nativos de 44 px; al cerrar la confirmación sin cancelar, el foco vuelve a "Cancelar cita" (el detalle sigue abierto). "Reprogramar" cierra el detalle antes de abrir su diálogo, así que al cerrar "Reprogramar cita" el foco vuelve a la cita del calendario o de la lista del día que abrió el detalle (comportamiento actual de `dialog.js`) |
| Reprogramar cita | reposo · enviando (botón deshabilitado) · error 422 o 409 en la región de estado del diálogo con lo escrito intacto · éxito (cierra y avisa en el marco) | etiqueta visible por campo; error con `role="alert"` del diálogo |
| Agendar cita | error 422 de fecha pasada en la región de estado del diálogo | igual que hoy |
| Confirmar cancelación | reposo (foco en "No, mantenerla") · enviando · error en su región · éxito (cierra la confirmación y el detalle, avisa "Cita cancelada.") | Escape = no cancelar; título enlazado |

## Estrategia de pruebas
- **Unit (dominio), `tests/Modules/Appointments/Unit/Domain/`:**
  - `AppointmentEntityTest.php` (nuevo):
    - reprogramar a ayer, a hoy a una hora pasada y exactamente a "ahora" → excepción;
    - reprogramar a hoy a una hora futura y a mañana → `reprogramada`;
    - reprogramar o cancelar una cita completada o cancelada → excepción;
    - crear en el pasado → excepción;
    - la comparación usa la zona de `$now`: con un "ahora" de 23:30 en Ciudad de México, que en UTC ya es el día siguiente, se aceptan las 23:45 del mismo día.
  - `AppointmentSchedulePropertiesTest.php` (nuevo, Eris): para todo desplazamiento en minutos respecto a `$now`, `<= 0` se rechaza y `> 0` se acepta.
- **Integración, `tests/Modules/Appointments/Integration/`.** Todos con `$this->travelTo()` a un instante fijo en `America/Mexico_City`, en `beforeEach` de los archivos tocados. Así se arreglan también las fechas fijas que ya pasaron (`2026-09-02`).
  - `UpdateAppointmentTest.php`: reproducción del bug (fecha, hora y `status: asignada` → hoy 409; esperado 422 sin cambios), CA1–CA3, CA6, CA8, cancelar (CA7), `status: completada` sobre una cita cancelada → 409 y sigue cancelada (CA11), `status` con `whatsapp_reminder`, cita inexistente → 409, y el texto exacto del 409 de cita cerrada. Los tests de "solo fecha" y "solo hora" pasan de 409 a 422, y el de estado inválido también pasa a 422. Dataset de payloads inválidos del FormRequest.
  - `CreateAppointmentTest.php`: CA4 (ayer, hoy a una hora pasada, exactamente ahora) → 422 y nada en la base.
  - `AppointmentsAccessControlTest.php`: la tabla por actor añade cancelar y reprogramar para asistente, doctor y paciente → 403 o 401, sin cambios en la base (CA10); y un dataset de cuerpos inválidos (fecha sin hora, estado fuera de la lista, mezcla) por actor no administrador → 403, nunca 422. Sus fechas fijas (líneas 101 y 104) pasan a `travelTo()`.
  - `tests/Modules/AppointmentTracking/Integration/CompleteAppointmentTest.php`: ya cubre que completar por el seguimiento una cita cancelada da 409 (línea 221); debe seguir en verde (CA11 por la otra vía).
  - `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php`: su fecha fija (línea 56) pasa a `travelTo()` para que la regla nueva no lo rompa.
  - `GetTodayAppointmentsTest.php` (nuevo): con `travelTo()` a las 23:30 de Ciudad de México (ya el día siguiente en UTC), devuelve las citas de ese día y no las del siguiente.
- **Configuración, `tests/Modules/Core/Unit/`:** `ClinicTimezoneTest.php` (nuevo): `config('app.timezone')` y `date_default_timezone_get()` valen `America/Mexico_City` (la segunda rige `new DateTime()` de las promociones); `EnvExampleDefaultsTest` exige `APP_TIMEZONE`.
- **Navegador (`npm run test:ui`, CI):**
  - `screens.json`: el diálogo de reprogramar (renombrado) y la confirmación nueva, a 390, 768 y 1440 px. Comprobaciones de desborde, tamaño, contraste, foco y teclado del verificador.
  - `checks-dialogs.mjs`: foco inicial de la confirmación en "No, mantenerla" y, al cerrarla con Escape, vuelta a "Cancelar cita"; al cerrar "Reprogramar cita", el foco vuelve a la cita que abrió el detalle.
  - Un 422 simulado en reprogramar muestra el mensaje del servidor en el diálogo, que sigue abierto con lo escrito (CA5).
  - El detalle de una cita completada no tiene "Reprogramar" ni "Cancelar cita" (CA8).
- **Diseño:** `DesignTokensTest` y `OnlyDesignTokensTest` siguen en verde tras actualizar `system.md`. `UnusedUiTest` sin cambios: la clave `data-edit-appointment-trigger` sigue sin usarse.
- Cada test nuevo de navegador se ejecuta dos veces (estabilidad).

## Modelo de amenazas
Flujo: navegador del administrador → `PUT`/`POST /api/v1/appointments` (Sanctum por cookie) →
caso de uso → base de datos. Datos: paciente y tratamiento (personal y salud).

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: staff no administrador o paciente reprograma o cancela, o sondea las reglas de validación con cuerpos inválidos | A01:2025 Broken Access Control · API5 BFLA | `PUT /appointments/{id}` | middleware `staff` + `assertCan('appointments.update')` en `UpdateAppointmentRequest::authorize()` (antes de validar) y en el caso de uso | `AppointmentsAccessControlTest` (actor × reprogramar, cancelar y cuerpos inválidos → 403) |
| TM2 | Tampering: colar un cambio de estado dentro de una reprogramación para saltar la regla (p. ej. volver a `asignada`) | A04:2025 Insecure Design · API3 BOPLA | `UpdateAppointmentRequest`, `UpdateAppointmentUseCase` | `prohibits` en el FormRequest y guardia en el caso de uso; estados admitidos solo `completada` y `cancelada` | `UpdateAppointmentTest`: mezcla → 422; `status: asignada` o `reprogramada` → 422 |
| TM3 | Tampering: reabrir o mover una cita cerrada por la API, incluido marcar como completada una cita cancelada | A04:2025 Insecure Design | `AppointmentEntity` | `reschedule()` y `cancel()` rechazan completada y cancelada; `complete()` rechaza cancelada (protege `PUT` y el seguimiento) | `AppointmentEntityTest`, `UpdateAppointmentTest` (CA8, CA11), `CompleteAppointmentTest` existente |
| TM4 | Tampering: agendar o mover al pasado saltándose la interfaz | A04:2025 Insecure Design | `AppointmentEntity` | regla en el dominio con "ahora" del servidor en la zona de la clínica; el `min` del selector solo ayuda | `CreateAppointmentTest`, `UpdateAppointmentTest` (CA3, CA4) |
| TM5 | Information disclosure: mensajes de error con datos internos | A10:2025 Mishandling of Exceptional Conditions | controladores tocados | 422 y 409 con textos fijos; 500 genérico | `UnexpectedErrorTest` existente; aserción de texto exacto en los 422 nuevos |

Casos de abuso de la spec: CA10 → TM1; CA8 → TM3; CA6 → TM2; CA11 → TM3.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| Ficha: reprogramar con `status: asignada` devolvía 409 | UpdateAppointmentUseCase, UpdateAppointmentRequest | integración `UpdateAppointmentTest` › rejects mixing a reschedule with a status (reproducción) |
| CA1: reprogramar a futuro deja `reprogramada` y avisa | AppointmentEntity, UpdateAppointmentUseCase, create-appointment.js | unit `AppointmentEntityTest`; integración `UpdateAppointmentTest` › reschedules to a future slot; navegador `agenda-cita-reprogramar`; verificación manual: aviso "Cita reprogramada." y agenda actualizada a 390 y 1440 px |
| CA2: falta fecha u hora → mensaje y sin cambios | UpdateAppointmentRequest, UpdateAppointmentUseCase | integración `UpdateAppointmentTest` › rejects date without time / time without date (422, texto exacto) |
| CA3: reprogramar al pasado → mensaje y sin cambios | AppointmentEntity, AppointmentInThePastException, UpdateAppointmentController | unit `AppointmentEntityTest`, `AppointmentSchedulePropertiesTest`; integración `UpdateAppointmentTest` (ayer, hoy pasado, ahora exacto) |
| CA4: agendar en el pasado → mensaje y no se crea | AppointmentEntity, CreateAppointmentUseCase, CreateAppointmentController | unit `AppointmentEntityTest`; integración `CreateAppointmentTest` |
| CA5: el diálogo sigue abierto con el mensaje | create-appointment.js, `status.js` (sin cambios) | navegador: 422 simulado en reprogramar y en agendar → mensaje en la región del diálogo, campos intactos |
| CA6: reprogramar y estado a la vez → rechazo | UpdateAppointmentRequest, UpdateAppointmentUseCase | integración `UpdateAppointmentTest` › rejects mixing (422, sin cambios) |
| CA7: cancelar con confirmación; "No, mantenerla" no cambia nada | cancel-appointment.js, cancel-appointment-confirm.blade.php, view-appointment.js, AppointmentEntity | integración `UpdateAppointmentTest` › cancels an assigned / a rescheduled appointment; navegador `agenda-cita-cancelar` (foco inicial, Escape) y, con la API interceptada, confirmar envía un solo `PUT {status: cancelada}` y avisa "Cita cancelada.", y salir sin confirmar no envía nada; verificación manual: confirmar y ver "Cancelada" en la agenda a 390 y 1440 px |
| CA8: completada o cancelada sin acciones y rechazo por API | AppointmentEntity, view-appointment.js | unit `AppointmentEntityTest`; integración `UpdateAppointmentTest` › refuses to reschedule or cancel a closed appointment (409); navegador: detalle de cita completada sin los dos botones |
| CA9: "Reprogramar" abre "Reprogramar cita" sin estado | edit-appointment-modal.blade.php, view-appointment-modal.blade.php, create-appointment.js | navegador `agenda-cita-reprogramar` (título, sin `[data-edit-appointment-status]`); verificación manual: contra `design.md` → Composición a 390 y 1440 px |
| CA10 (abuso): no administrador → denegado con datos válidos o no | UpdateAppointmentRequest (`authorize()`) | integración `AppointmentsAccessControlTest` (TM1): actor × reprogramar y cancelar, y cuerpos inválidos → 403 |
| CA11 (abuso): una cita cancelada no puede completarse | AppointmentEntity (`complete()`), UpdateAppointmentController | unit `AppointmentEntityTest` › refuses to complete a cancelled appointment; integración `UpdateAppointmentTest` › refuses to complete a cancelled appointment (409, sigue cancelada); `CompleteAppointmentTest` existente |
| TM1 | UpdateAppointmentRequest | `AppointmentsAccessControlTest` |
| TM2 | UpdateAppointmentRequest, UpdateAppointmentUseCase | `UpdateAppointmentTest` › mixing; › rejects status asignada and reprogramada |
| TM3 | AppointmentEntity | `AppointmentEntityTest`; `UpdateAppointmentTest` (CA8, CA11); `CompleteAppointmentTest` |
| TM4 | AppointmentEntity | `CreateAppointmentTest`; `UpdateAppointmentTest` (CA3) |
| TM5 | controladores | `UnexpectedErrorTest`; textos exactos en los 422 |
| RNF: "ahora" en la zona de Ciudad de México | config/app.php, AppointmentEntity | unit `AppointmentEntityTest` › compares in the clinic timezone; integración con `travelTo()` en `America/Mexico_City` |
| RNF: la misma zona rige "citas de hoy" (007) y la vigencia de las promociones (011, 012) | config/app.php (sin cambios en esos módulos) | unit `ClinicTimezoneTest` (zona de config y de PHP); integración `GetTodayAppointmentsTest` en el límite del día |
| RNF: solo sistema de diseño, 390/768/1440 px | vistas y JS tocados, system.md | `OnlyDesignTokensTest`, `DesignTokensTest`, `npm run test:ui` |
| RNF: contraste, foco y teclado (WCAG AA) | diálogos y botones | `npm run test:ui` (contraste, foco, teclado); `checks-dialogs.mjs` (foco inicial y de vuelta) |
| RNF: mensajes en español, 500 genérico | FormRequest, excepciones | textos exactos en integración; `UnexpectedErrorTest` |

## Observabilidad
No aplica más allá de lo que ya existe. La spec no define eventos de auditoría (el registro aún no
existe, roadmap objetivo 5) y no se añaden logs. Los 422 y 409 nuevos quedan en el log de acceso de
nginx con su `request_id`, como el resto de respuestas.
- Cómo se verifica tras el deploy: en Loki, sin 500 nuevos en `/api/v1/appointments*`, y el 422 de
  una reprogramación al pasado hecha a mano en producción.

## Descubribilidad
No aplica: la spec solo cambia diálogos de `/agenda`, una ruta del panel no indexable, y no crea ni
cambia rutas públicas.

## Rollout
- **Feature flag:** no aplica. Es la corrección de un flujo hoy roto, el prototipo no tiene datos
  reales, y backend e interfaz se despliegan juntos.
- **Orden de despliegue:** un solo paso, backend y Blade/JS juntos (`npm run build` en la imagen),
  con `docker/prod/deploy.sh <tag>`. Sin migraciones.
- **Compatibilidad:** el JS anterior en una pestaña abierta durante el deploy enviaría
  `status: asignada` con la reprogramación y recibiría 422. Basta con recargar. Aceptable: un solo
  administrador y sin datos reales.
- **Rollback:** `docker/prod/rollback.sh` a la versión anterior. No hay esquema ni datos que
  revertir; las citas canceladas o reprogramadas siguen siendo estados válidos en la versión
  anterior.
- **Antes del deploy:** confirmar que el `.env` de producción no fija `APP_TIMEZONE` en otra zona
  (si no la define, rige el nuevo valor por defecto).
- **Métricas a vigilar tras el deploy:** 500 en `/api/v1/appointments*` (Loki) y proporción de 422
  en `PUT /appointments/{id}`.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Código de los rechazos de entrada: 422** con formato de validación (decisión del usuario,
  2026-10-15). Alternativa: mantener 409, que la interfaz traduce a un texto genérico y que
  incumpliría CA5. Afecta a 007 CA3 y CA4 (los tests se actualizan).
- **D2 · Cancelar por `PUT` con `status: cancelada`** (decisión del usuario, 2026-10-15).
  Alternativa: `POST /appointments/{id}/cancel`, con endpoint, FormRequest y tests por actor nuevos
  sin ganancia funcional.
- **D3 · Zona horaria por defecto `America/Mexico_City`** en `config/app.php` y en `.env.example`
  (decisión del usuario, 2026-10-15). Alternativa: solo documentarla, con riesgo de desfase de 6 h si
  falta en producción.
- **D4 · `whatsapp_reminder` solo si la casilla cambió** al reprogramar (decisión del usuario,
  2026-10-15). Solo interfaz: evita que reprogramar invierta el recordatorio. El defecto del backend
  (invertir en lugar de guardar el valor) sigue fuera de alcance.
- **D5 · El reloj entra al dominio como parámetro** (`DateTimeImmutable $now`). Alternativas: un
  `ClockInterface` propio (más piezas para una sola regla) o `now()` dentro de la entidad (rompe P3).
  Los tests controlan el tiempo con `travelTo()`, que afecta a `now()` en el caso de uso.
- **D6 · Sin FormRequest en `POST /appointments`:** su entrada (campos y formatos) no cambia; la
  fecha pasada es una invariante del dominio que la entidad lanza y el controlador traduce a 422
  (P6 aplica solo si cambian parámetros, cuerpo o reglas de validación de entrada). Confirmada: sin FormRequest hasta que cambie su contrato de entrada (decisión del usuario, 2026-10-15, /analyze A15). `CreateAppointmentTest` fija el 422 y su texto.
- **D7 · Confirmación como componente propio** (`calendar/cancel-appointment-confirm`) y no
  `x-ui.confirm-delete-modal`: el de eliminar fija textos y atributos de borrado y no permite el
  mensaje de `design.md` (paciente en negrita, fecha y hora en texto normal).
- **D8 · Orden de comprobaciones al reprogramar:** entrada (FormRequest) → existe → estado
  cerrado (409) → pasado (422) → choque de horario (409). Si una petición incumple varias reglas,
  gana la primera.
- **D10 · Excepción a P4** (decisión del usuario, 2026-10-15, /analyze A1): los códigos cambian dentro de `/api/v1` sin `/api/v2`. Motivo: el único consumidor es el JS de la agenda, que viaja en la misma imagen; no hay clientes externos. Si aparece otro cliente de la API, la siguiente rotura de contrato abre `/api/v2`.
- **D11 · Autorización antes de validar** (decisión del usuario, 2026-10-15, /analyze A8): `UpdateAppointmentRequest::authorize()` llama a `assertCan`. Alternativa: validar en el caso de uso tras `assertCan`, sin FormRequest, que contradice P6.
- **D12 · Completar una cita cancelada se rechaza en la entidad** (decisión del usuario, 2026-10-15, /analyze A9): `complete()` lanza `cannotChangeClosedAppointment()`. Completar una cita ya completada no se toca: el seguimiento puede llegar después de un `PUT {status: completada}`.
- **D13 · Zona horaria global** (decisión del usuario, 2026-10-15, /analyze A10): rige toda la aplicación y se declara su efecto sobre 007, 011 y 012 (`extends` de la spec).
- **D9 · Línea base de dependencias:** npm sin vulnerabilidades (CI de `main`, 2026-10-15).
  `composer audit` no se pudo correr: el contenedor perdió el montaje de E:. Se corre al empezar
  `/implement`; si aparece algo crítico o alto, se decide entonces (actualizar o excepción).
- Ninguna decisión requiere ADR: sin dependencias nuevas ni cambios de arquitectura.

## Impacto en arquitectura
- `docs/architecture.md` → "Contrato entre capas": un `PUT` de cita hace una sola acción y los
  rechazos de entrada son 422.
- `docs/architecture.md` → "Backend": el dominio de citas recibe el "ahora" como parámetro; zona
  horaria de la aplicación `America/Mexico_City`.
- `docs/deployment.md`: `APP_TIMEZONE` en las variables de producción (valor por defecto ya
  correcto).
- Spec 007: al liberar, anotar en su Historial que 017 cambia CA3 y CA4 (409 → 422), cumple CA14 y calcula "citas de hoy" en la zona de la clínica.
- Specs 011 y 012: anotar en su Historial que la vigencia de las promociones se evalúa en la zona de la clínica.
- `docs/roadmap.md`, objetivo 4: las brechas de monitoreo que apuntaban a "017" pasan a "spec de monitoreo por crear", sin número, porque 017 es esta spec (decisión del usuario, 2026-10-15, /analyze A14). Corregido junto con este `--fix`.

## Riesgos y mitigaciones
- **Tests existentes con fechas ya pasadas** fallarían con la regla nueva (`UpdateAppointmentTest`,
  `CreateAppointmentTest`, `AppointmentsAccessControlTest:101,104`, `WhatsAppFlowLogsTest:56`) →
  `travelTo()` en `beforeEach` de esos archivos. Correr la suite completa antes de cerrar.
- **Datos de `ui:audit-data` con citas en fechas fijas** → cerrado, no aplica: `screens.json` fija
  `now` en 2026-07-22, el seeder usa julio de 2026 y las comprobaciones de los diálogos interceptan
  la API (/analyze A16).
- **`create-appointment.js` es grande y lo comparten agendar y reprogramar** → cambios acotados a
  las funciones de edición; el test de navegador de agendar sigue en verde.
- **Cambio de zona horaria** desplaza `created_at` y `updated_at` de datos de prueba y los horarios
  de los logs → aceptable sin datos reales; se anota en el CHANGELOG.
- **El recordatorio que se invierte** sigue existiendo para quien llame a la API con el campo
  (fuera de alcance; D4 lo evita desde la interfaz).
- **`composer audit` pendiente** (D9) → primera comprobación de `/implement`.
