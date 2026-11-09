---
id: 024
slug: qr-de-cita-para-el-acceso
status: implemented
created: 2026-11-04
extends: [007, 023]
---

# 024 · QR de cita para el acceso

> Reemplaza a la spec [013](../013-codigos-qr-por-cita/spec.md), que se aprobó pero no se
> implementó. Su diseño (QR firmado, lector ESP32 con webhook, imagen en R2 y app del paciente) quedó
> superado: el QR ahora lo escanea el administrador con la app de asistencias, y el microservicio
> valida la cita con la consulta que ya existe (spec 023). Origen del requisito:
> `DentissaAsistence/SocketIO/docs/integraciones/core-qr-de-citas.md` (2026-11-04).

## Problema
El microservicio de asistencias registra la entrada y la salida de los pacientes y abre la puerta
cuando el administrador escanea, con la app móvil, el QR de una cita. Para validarlo ya consulta la
cita en Dentissa (spec 023). Lo que falta es el QR: Dentissa no lo genera ni lo muestra, así que
recepción no tiene nada que enseñarle o mandarle al paciente.

## Historias de usuario
- Como recepción, quiero ver el QR de una cita desde su detalle en la agenda para enseñárselo al
  paciente o mandárselo antes de que llegue.
- Como paciente, quiero que al mostrar el QR de mi cita de hoy el administrador me deje entrar sin
  buscar mi nombre en la agenda.
- Como responsable de la clínica, quiero que solo el personal pueda obtener el QR de una cita.

## Criterios de aceptación
- [x] CA1 · Dada una cita existente, cuando el personal pide su QR, entonces recibe una imagen SVG
  cuyo contenido decodificado es exactamente el identificador de la cita: sin URL, sin prefijo, sin
  JSON y sin espacios.
- [x] CA2 · Dada la misma cita, cuando se pide su QR varias veces, entonces el contenido decodificado
  es siempre el mismo (no se guarda ni caduca nada).
- [x] CA3 · Dado un identificador con forma válida que no corresponde a ninguna cita, cuando se pide
  su QR, entonces responde "no encontrado" (404).
- [x] CA4 · Dado un identificador con forma no válida, cuando se pide su QR, entonces responde
  petición incorrecta (400) con `{"error": "Invalid appointment id."}`, igual que la consulta
  interna de la cita (023).
- [x] CA5 · Dada una cita asignada o reprogramada, cuando el personal abre su detalle (desde la
  agenda, el inicio o el expediente), entonces el QR de la cita aparece dentro del mismo detalle,
  sin pulsar nada más, junto a los datos de la cita.
- [x] CA6 · Dado el QR a la vista en el detalle, cuando el personal pulsa "Descargar QR", entonces
  obtiene un archivo de imagen con el QR para mandarlo al paciente.
- [x] CA7 · Dada una cita completada o cancelada, cuando se abre su detalle, entonces no aparece el QR
  (el microservicio no deja entrar con ella).
- [x] CA8 · Dado un fallo al obtener el QR, cuando se abre el detalle, entonces el resto del detalle
  se muestra igual y en el lugar del QR aparece un aviso en español sin términos técnicos.
- [x] CA9 · (abuso) Sin sesión, pido el QR de una cita → 401.
- [x] CA10 · (abuso) Como paciente con sesión, pido el QR de una cita ajena (o de cualquier cita
  mientras no exista el portal del paciente) → 403, igual que en el resto de las rutas de citas del
  personal, y no recibo ningún QR.
- [x] CA11 · (abuso) Como atacante, provoco un fallo al generar el QR → la respuesta es el error
  genérico (500) sin detalles internos.
- [x] CA12 · (abuso) Como alguien con acceso al navegador o a un proxy, intento recuperar un QR
  descargado antes → la respuesta del QR indica que no debe guardarse en cachés.

## Fuera de alcance
- Enviar el QR en el recordatorio de WhatsApp: necesita una URL pública de la imagen. Queda para
  después de la maqueta.
- El portal del paciente y la app del paciente.
- Cambios en las consultas del microservicio (`/api/v1/internal/appointments`), en `/auth/me` o en
  las reglas de entrada y salida, que decide el microservicio (spec 023).
- La app Android de asistencias (escáner y evento del socket): otro repositorio.
- Firmar el QR, usarlo una sola vez o hacerlo caducar (ver "Decisiones").
- Un endpoint de validación del QR en Core: lo valida el microservicio con la consulta de la cita
  que ya existe (spec 023). Ver "Flujo de acceso".
- Comprobar en la puerta que quien muestra el QR es el paciente de la cita: lo hace el administrador
  que escanea, con los datos que le devuelve el microservicio (spec 005 de SocketIO).

## Flujo de acceso
Core solo genera y muestra el QR. El escaneo y la decisión ocurren fuera:

1. Recepción abre el detalle de la cita en Core y enseña o descarga el QR (esta spec).
2. El administrador escanea el QR con la app de asistencias, que manda al microservicio
   `attendance:scan { appointment_id }` por Socket.IO.
3. El microservicio confirma que quien escanea es un administrador activo (`GET /api/v1/auth/me`,
   spec 023) y consulta la cita (`GET /api/v1/internal/appointments/{id}` con `X-Service-Key`,
   spec 023).
4. Con sus propias reglas y su base de datos de asistencias decide si es entrada, salida o rechazo,
   lo registra y responde a la app.
5. Si concede el acceso, manda `door_open` por el namespace `/devices` a la placa de acceso
   (`ACCESO-01`), que mueve el servo y reporta `command:result` y `door:status` (`trigger = qr`). Si la
   placa está fuera de línea, la asistencia se registra igual y el ack lo indica.

Core no recibe ninguna petición nueva por el escaneo.

## Seguridad y privacidad
- Datos sensibles involucrados: el QR no contiene datos personales ni clínicos, solo el identificador
  de la cita. Funciona como **credencial de acceso**: con él se entra a la clínica el día de la
  cita. El diálogo muestra el nombre del paciente y la fecha y la hora, que el personal ya ve en el
  detalle.
- Quién puede hacer qué:
  - Personal que puede ver el detalle de la cita (`appointments.view-detail`) → obtener su QR.
  - Paciente → denegado (403). Cuando exista el portal del paciente, solo podrá obtener el QR de
    **sus** citas: es la corrección de propiedad del recurso pendiente en `docs/security.md`, que
    tendrá su propia spec.
  - Sin sesión → denegado (401).
- Quién pasa por la puerta: el QR es una credencial al portador. Core no puede saber quién lo
  muestra; lo comprueba el administrador al escanear, comparando a la persona con el nombre del
  paciente que le devuelve el microservicio.
- Casos de abuso: CA9–CA12.

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe (objetivo 5 del
roadmap, [observability.md](../../observability.md)). Es una desviación de P14, como en 021 y 023.
Cuando exista, deberá quedar registrado con actor, acción, recurso, resultado y fecha:
- cada obtención del QR de una cita (credencial de acceso; CA1);
- los accesos denegados de CA9 y CA10.

## Diseño
Recepción tiene que enseñar o mandar el QR en segundos, sin pasos extra: el QR va dentro del
diálogo "Detalle de la cita" (`view-appointment-modal`), que se abre desde la agenda, el inicio y el
expediente. Sin diálogos nuevos; componentes `x-ui.*`. Sistema: [docs/design/system.md](../../design/system.md).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | QR completo dentro del detalle | Visible a 390, 768 y 1440 px, con al menos 240 × 240 px | CA5 |
| K2 | Literales | "Descargar QR" presente en citas asignadas o reprogramadas; ausente en completadas y canceladas | CA6, CA7 |
| K3 | Lectura del QR | Módulos oscuros sobre fondo blanco con margen, en pantalla y en el PNG descargado | CA1, CA5, CA6 |
| K4 | Estabilidad visual | El detalle no salta al cargar el QR (espacio reservado; CLS ≤ 0.1) | CA5, CA8 |

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: pesos por defecto.

## Requisitos no funcionales
- La respuesta del QR llega en menos de 300 ms en local (sin almacenamiento ni llamadas externas).
- Corrección de error del QR al menos M, para que se lea en pantallas de teléfono con brillo bajo.
- El SVG se pinta como imagen, nunca insertado en el DOM como marcado.
- Accesibilidad: la imagen tiene texto alternativo ("Código QR de la cita del <fecha>").

## Preguntas abiertas
- Ninguna.

## Decisiones
Cada brecha, contradicción o supuesto resuelto, con su origen (`shared/contract.md` → "Decisiones").

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-04 | contradicción | La spec 013 (aprobada, sin implementar) define un QR firmado, un lector ESP32 y la app del paciente; el microservicio pide solo el UUID de la cita. | Se reemplaza la 013 por esta spec. | usuario |
| 2026-11-04 | brecha | La 013 cerraba, con CA23 y CA24, dos correcciones del riesgo de control de acceso de `docs/security.md` (propiedad del recurso y provider del guard) que no dependen del QR. | Vuelven a quedar pendientes en `docs/security.md`, sin spec. No entran aquí. | usuario |
| 2026-11-04 | diseño | ¿Qué lleva el QR? | Solo el UUID de la cita, tal cual; si no es un UUID, el microservicio niega el acceso. | `core-qr-de-citas.md` (SocketIO) |
| 2026-11-04 | supuesto | Con el UUID basta para entrar el día de la cita: no hay firma, uso único ni caducidad. | Aceptado para la maqueta. Lo mitigan que el UUID es v4 (122 bits aleatorios, no se adivina), que el microservicio valida estado, fecha y entrada previa, y CA12. Se revisa antes de tener datos reales. | /specify |
| 2026-11-04 | supuesto | ¿Quién puede ver el QR? | El mismo personal que ve el detalle de la cita (`appointments.view-detail`) y con los mismos middlewares (`auth:sanctum` + `staff`). | `core-qr-de-citas.md` (SocketIO) |
| 2026-11-04 | diseño | ¿Dónde se ve el QR? | Dentro del detalle de la cita, sin botón "Ver QR", desde cualquier pantalla que abra el detalle. | usuario |
| 2026-11-04 | supuesto | ¿Para qué citas se muestra el QR? | Solo asignadas y reprogramadas, las únicas con las que el microservicio deja entrar. El endpoint responde para cualquier cita existente. | /specify |
| 2026-11-04 | contradicción | CA4 decía "400, igual que la consulta de la cita", pero `GET /appointments/{id}` responde 500 con un id que no es UUID (`InvalidArgumentException` sin capturar). | El QR responde 400 como la consulta interna (023). El 500 del detalle es un bug aparte, para `/fix`. | `/analyze` A1; usuario |
| 2026-11-04 | brecha | ¿Hace falta un endpoint de validación del QR en Core? | No. El microservicio valida con `GET /internal/appointments/{id}` (023) y aplica sus reglas de entrada y salida con su propia base de asistencias (spec 005 de SocketIO). | usuario; `SocketIO/docs/roadmap.md` |
| 2026-11-04 | brecha | ¿Cómo se comprueba que quien pasa es el dueño de la cita? | En Core, solo el personal obtiene el QR (CA9, CA10). En la puerta, el administrador compara a la persona con el nombre que le devuelve el microservicio; se propone a SocketIO 005 incluirlo en el ack. | usuario |
| 2026-11-04 | supuesto | ¿Se guarda la imagen? | No: se genera en cada petición. Sin R2 ni enlaces temporales. | /specify |

## Notas para /plan
- Endpoint propuesto: `GET /api/v1/appointments/{id}/qr` → `200 image/svg+xml`, con
  `Cache-Control: private, no-store`. Se añade al grupo `auth:sanctum` + `staff` de las citas en
  `routes/api.php` y al dataset de `AppointmentsAccessControlTest`.
- Librería: `chillerlan/php-qrcode` (v5). Genera SVG sin extensiones extra y **decodifica** con GD, que
  ya está en la imagen (`docker/Dockerfile`), así que los tests de CA1 no necesitan otra dependencia.
  `bacon/bacon-qr-code` no decodifica. Requiere ADR 0007 (P10) y una versión publicada hace más de 7
  días (`security.agent.min_package_age_days`).
- Puerto en el dominio (generador de QR) con adaptador en Infrastructure, enlazado en
  `AppServiceProvider`. El caso de uso reutiliza `assertCan('appointments.view-detail')`,
  `AppointmentId` (400) y `AppointmentsService::findById` (404).
- El módulo usa `Aplication` y `Http`: respetar esa grafía.
- Frontend: bloque del QR en `resources/views/components/calendar/view-appointment-modal.blade.php` y
  lógica en `resources/js/pages/agenda/view-appointment.js`, que ya usan la agenda, el inicio y el
  expediente (`dashboard.blade.php`, `records/index.blade.php`). Pintar el SVG con un `blob:` URL en
  un `<img>` y liberar la URL al cerrar el detalle.
- Prueba de CA1: decodificar el SVG directamente no es posible con GD. Opción: comparar el SVG con el
  del adaptador para el mismo id y probar en un test unitario el ida y vuelta del adaptador (mismo
  contenido renderizado a PNG y leído con el lector de chillerlan).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-04 | Creada como draft; reemplaza a la 013 | Requisito del microservicio de asistencias (SocketIO); decisión del usuario |
| 2026-11-04 | QR dentro del detalle, flujo de acceso, sin endpoint de validación en Core; aprobada | Decisiones del usuario; confirmación explícita |
| 2026-11-04 | CA4 (400 como la consulta interna) y K3 (sin modo oscuro; también el PNG) | `/analyze` A1 y A8; decisión del usuario |
| 2026-11-04 | Implementada en `feat/024-qr-de-cita-para-el-acceso` | /implement (T001–T091) |
