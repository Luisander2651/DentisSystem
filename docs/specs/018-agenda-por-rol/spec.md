---
id: 018
slug: agenda-por-rol
status: released
released: v0.4.0 (2026-10-18)
created: 2026-10-17
extends: [007, 008, 009, 014, 017]
---

# 018 · Agenda por rol

## Problema
Hoy la agenda es solo del administrador (007, 014, 017). El asistente, cuyo trabajo diario es
agendar y mover citas, no puede ni verla, y el doctor no tiene forma de consultar qué pacientes
atiende ni qué se les hizo en sus citas. Toda la gestión de citas recae en el administrador y los
otros dos roles del staff quedan casi sin uso. Además, el tablero de inicio que viene después
(spec por crear) necesita que cada rol ya tenga su parte de la agenda para mostrarle las citas
de hoy y las acciones rápidas.

## Historias de usuario
- Como asistente, quiero ver la agenda completa de la clínica y agendar, reprogramar y cancelar
  citas para gestionar la atención diaria sin depender del administrador.
- Como doctor, quiero ver en la agenda solo mis citas, sin poder moverlas ni cancelarlas, para
  saber a quién atiendo y cuándo.
- Como doctor, quiero completar mis citas registrando su seguimiento clínico y las recetas para
  dejar constancia de lo que hice a mis pacientes.
- Como doctor, quiero leer el seguimiento clínico y las recetas de mis citas completadas para
  conocer lo que se hizo a mis pacientes.
- Como administrador, quiero conservar todo lo que hoy hago en la agenda.

## Criterios de aceptación
- [x] CA1 · Dado un asistente activo, cuando abre la agenda desde su menú, entonces ve las citas de todos los doctores, con el calendario, las citas de hoy y los filtros por estado, igual que el administrador.
- [x] CA2 · Dado un asistente, cuando agenda una cita nueva eligiendo paciente, doctor, tratamiento, fecha y hora, entonces la cita se crea con las mismas reglas que para el administrador (sin choques de horario en la clínica y nunca en el pasado) y con los mismos mensajes de rechazo.
- [x] CA3 · Dada una cita asignada o reprogramada, cuando el asistente la reprograma o la cancela, entonces se aplica igual que para el administrador (017 CA1–CA9), incluidos los avisos "Cita reprogramada." y "Cita cancelada.".
- [x] CA4 · (abuso) Como asistente, intento completar una cita, leer o editar su seguimiento clínico o sus recetas, o eliminar una cita, por la pantalla o por otra vía → se rechaza con acceso denegado, la cita no cambia y la pantalla no le ofrece "Completar" ni el seguimiento.
- [x] CA5 · Dado un doctor activo, cuando abre la agenda desde su menú, entonces ve solo las citas que tiene asignadas, en el calendario, en las citas de hoy y en los filtros, y no ve el botón para agendar.
- [x] CA6 · Dada una cita asignada a un doctor, cuando la abre en la agenda, entonces ve fecha, hora, paciente, tratamiento y estado, sin "Reprogramar" ni "Cancelar cita"; si la cita está asignada o reprogramada, ve además "Completar".
- [x] CA7 · (abuso) Como doctor, intento agendar, reprogramar, cancelar o eliminar una cita por otra vía, con datos válidos o no → se rechaza con acceso denegado y la cita no cambia.
- [x] CA12 · Dada una cita asignada o reprogramada que tiene asignada, cuando el doctor la completa registrando el seguimiento clínico (motivo, síntomas, diagnóstico y procedimiento obligatorios; observaciones y recomendaciones opcionales) y cero o más recetas, entonces la cita queda completada con su seguimiento y sus recetas, con las mismas reglas que para el administrador (009 CA1–CA4 y CA13).
- [x] CA13 · (abuso) Como doctor, intento completar una cita asignada a otro doctor, por la pantalla o por otra vía → se rechaza con acceso denegado, la cita no cambia y no se guarda ningún seguimiento.
- [x] CA8 · (abuso) Como doctor, intento obtener en los listados de la agenda (las citas del calendario y las de hoy) las citas de otro doctor, por otra vía, cambiando el filtro o pidiendo un día concreto → solo recibo las mías; las demás no aparecen ni cuentan. Si no tengo citas, el listado viene vacío, no como error. Quedan fuera, a propósito, el detalle de una cita y el historial de citas de un paciente, que todo el staff ve desde el expediente (014), sin datos clínicos.
- [x] CA9 · Dada una cita completada que tiene asignada, cuando el doctor abre su detalle, entonces ve el seguimiento clínico y las recetas en solo lectura, sin opción de editarlos; si la cita no tiene seguimiento, se le indica "Esta cita no tiene seguimiento registrado.".
- [x] CA10 · (abuso) Como doctor, intento leer el seguimiento clínico o las recetas de una cita asignada a otro doctor, o editar el seguimiento o las recetas de una de mis citas → se rechaza con acceso denegado y no se devuelve ningún dato clínico.
- [x] CA11 · (abuso) Como paciente, staff inactivo o visitante sin sesión, intento abrir la agenda o usar cualquiera de sus operaciones → se rechaza igual que hoy (014); y el administrador conserva todas sus acciones de la agenda sin cambios.

## Fuera de alcance
- Que el asistente complete citas o registre seguimiento clínico y recetas (decisión del usuario, 2026-10-17).
- Que el doctor corrija un seguimiento o unas recetas ya registrados: hoy ninguna pantalla
  permite editarlos (009 CA11) y siguen siendo del administrador; 009 CA9 queda pendiente solo en
  esa parte.
- Eliminar citas: sigue siendo solo del administrador.
- Limitar el expediente clínico del doctor a sus pacientes: hoy el doctor lee cualquier
  expediente (014) y esta spec no lo cambia; solo la agenda y el seguimiento quedan acotados a sus citas.
- El tablero de inicio (siguiente spec) y los reportes mensuales con exportación a Excel (spec
  posterior).
- Que el doctor reciba avisos de citas nuevas o cambios en las suyas.
- Que completar una cita deje de poder hacerse desde la edición de la cita (009 CA12).
- Cambios a la gestión de pacientes, tratamientos, usuarios o contenido para el asistente o el doctor.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales del paciente en la cita (nombre) y el
  tratamiento (personal y salud, según `docs/security.md`), que pasan a verse por el asistente
  (todas las citas) y el doctor (las suyas); y el seguimiento clínico y las recetas (salud), que
  el doctor pasa a registrar y leer solo en sus citas.
- Quién puede hacer qué:

  | Acción | Administrador | Asistente | Doctor | Paciente / inactivo / sin sesión |
  |---|---|---|---|---|
  | Ver la agenda | todas las citas | todas las citas | solo las suyas | denegado |
  | Agendar, reprogramar, cancelar | sí | sí | denegado | denegado |
  | Completar con seguimiento | sí | denegado | solo sus citas | denegado |
  | Leer seguimiento y recetas | sí | denegado | solo de sus citas | denegado |
  | Editar seguimiento y recetas | sí | denegado | denegado | denegado |
  | Eliminar una cita | sí | denegado | denegado | denegado |

- Casos de abuso:
  - Como asistente, intento completar una cita o tocar el seguimiento clínico → se rechaza (CA4).
  - Como doctor, intento modificar una cita por otra vía → se rechaza (CA7).
  - Como doctor, intento completar una cita de otro doctor → se rechaza (CA13).
  - Como doctor, intento ver las citas de otro doctor → solo recibo las mías (CA8).
  - Como doctor, intento leer el seguimiento de otro doctor o editar el mío → se rechaza (CA10).
  - Como paciente, staff inactivo o sin sesión, intento usar la agenda → se rechaza (CA11).

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe y se construye en
una spec aparte (objetivo 5 del roadmap, [observability.md](../../observability.md)). Cuando
exista, deberán quedar registrados, con actor, acción, recurso, resultado y fecha: las lecturas
del seguimiento clínico por el doctor (CA9, lectura de datos de salud), el seguimiento y las
recetas que registra el doctor al completar (CA12, escritura de datos de salud), los cambios de
cita hechos por el asistente (CA2, CA3) y los accesos denegados de CA4, CA7, CA8, CA10, CA11 y CA13. Desviación de
P14 que se justifica en el Constitution Check de `/plan`, como en las specs 014 y 017.

## Diseño
Debe sentirse como la misma agenda para todos, adaptada a lo que cada rol puede hacer: el
asistente trabaja igual que el administrador salvo "Completar"; el doctor ve sus citas sin botones
que no puede usar, las completa con el mismo diálogo que el administrador y lee el seguimiento de
sus citas sin confundirlo con un formulario editable.
Pantallas que toca: la agenda (calendario, citas de hoy, filtros y botón de agendar), el detalle
de la cita (acciones por rol y seguimiento en solo lectura) y el menú lateral del asistente y del
doctor. Solo con componentes del sistema de diseño ([docs/design/system.md](../../design/system.md));
no es un rediseño.
Diseño de la spec: [design.md](design.md) (sin composiciones; cada rol ve solo sus acciones).

## Requisitos no funcionales
- La comprobación de permisos y el recorte a las citas del doctor se hacen en el servidor;
  ocultar botones o citas en la pantalla nunca sustituye a esa comprobación (P5, P13).
- Cada operación de la tabla de permisos tiene una prueba automatizada de acceso denegado por
  cada actor no autorizado de su fila (P5).
- La agenda y el detalle de la cita, con la vista de asistente y la de doctor, pasan la prueba en
  navegador a 390, 768 y 1440 px (P15, `npm run test:ui`).
- Contraste, foco visible y uso con teclado en la agenda y el detalle para los tres roles (WCAG 2.1 AA).
- Los mensajes de error no revelan el contenido del seguimiento clínico de otro doctor ni incluyen
  datos personales o de salud (P7, P11). Que una cita exista no es secreto: su detalle ya lo ve
  todo el staff (014).

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-17 | contradicción | El usuario pidió abrir la agenda al asistente y al doctor como `/fix`, pero 007, 014 y 017 especifican "solo el administrador": no es un defecto, es un cambio de permisos | Spec nueva antes del tablero; el tablero va después y los reportes al final | usuario |
| 2026-10-17 | brecha | ¿Qué puede hacer el asistente en la agenda? | Acceso completo: ver todas las citas, agendar, reprogramar y cancelar | usuario |
| 2026-10-17 | brecha | ¿El asistente completa citas (registra seguimiento clínico)? | No; completar sigue siendo del administrador | usuario |
| 2026-10-17 | brecha | ¿Qué ve el doctor en la agenda? | Solo sus citas, sin agendar, reprogramar ni cancelar | usuario |
| 2026-10-17 | brecha | ¿El doctor completa citas con seguimiento clínico? | Sí, solo las que tiene asignadas (cambia la decisión inicial de solo lectura) | usuario |
| 2026-10-17 | supuesto | ¿El doctor corrige después un seguimiento o unas recetas ya registrados? | No en esta spec: no hay pantalla de edición (009 CA11) y la corrección sigue siendo del administrador | agente; aceptado por el usuario, 2026-10-17 |
| 2026-10-17 | brecha | ¿El doctor lee el seguimiento clínico de sus citas completadas? | Sí, en solo lectura y solo de sus citas | usuario |
| 2026-10-17 | supuesto | ¿El asistente puede eliminar citas? | No: "acceso completo" se entiende como agendar, reprogramar y cancelar, que es lo que ofrece la agenda; eliminar sigue siendo del administrador | agente; aceptado por el usuario, 2026-10-17 |
| 2026-10-17 | supuesto | ¿Qué es "una cita del doctor"? | La que tiene asignado a ese doctor como quien atiende | `docs/specs/007-agenda-de-citas/spec.md` CA1 |
| 2026-10-17 | supuesto | ¿El seguimiento que lee el doctor incluye las recetas? | Sí, el seguimiento se muestra con sus recetas (009 CA5) | `docs/specs/009-seguimiento-clinico-y-recetas/spec.md` |
| 2026-10-17 | brecha | El validador avisa que la spec es grande (13 criterios, extiende 4 specs) | Se mantiene junta: es una sola capacidad (permisos de la agenda por rol) | usuario |
| 2026-10-17 | diseño | ¿Cómo se ve la agenda para cada rol? | Ver [design.md](design.md): misma agenda, acciones que el rol no puede usar ocultas, "Completar" en la tarjeta de la cita | /design-spec 018 |
| 2026-10-17 | brecha | ¿Qué responde un listado de citas vacío? Hoy responde un error y la agenda lo muestra como fallo | Lista vacía, sin error, para todos los roles (cambia 007 para el administrador) | usuario (/analyze A7) |
| 2026-10-17 | brecha | ¿Que el doctor reciba 403 en la cita de otro y otro código en una inexistente revela que existe? | Sí, y se acepta: la existencia de la cita no es secreta (014); lo protegido es el seguimiento | usuario (/analyze A8, plan D2) |
| 2026-10-17 | implícita | ¿Cambia la regla de choques de horario o de citas en el pasado para el asistente? | No, son reglas de la clínica, iguales para todos los roles | `docs/specs/007-agenda-de-citas/spec.md` CA2, `docs/specs/017-reprogramar-cancelar-citas/spec.md` CA3–CA4 |
| 2026-10-17 | implícita | ¿El detalle de una cita sigue abierto a todo el staff desde el expediente? | Sí (014); lo nuevo es que el doctor ve el seguimiento solo si la cita es suya | `docs/specs/014-control-de-acceso-y-errores/spec.md` → Supuestos |

## Notas para /plan
- Criterios de las specs extendidas que cambian (los anota `/release` en su Historial):
  - 007: CA1 y CA3–CA6 ("solo administrador" pasa a administrador y asistente; el doctor ve sus
    citas); el listado vacío deja de ser un error; "Fuera de alcance" sobre el asistente en el
    menú pasa a comportamiento deseado.
  - 008: CA3, que 014 dejó solo para el administrador, pasa a administrador y asistente (catálogo
    para agendar).
  - 009: CA7 (el doctor completa sus citas y lee su seguimiento) y CA9 (se cumple salvo la
    corrección posterior); CA10 sigue para el asistente.
  - 014: CA6 (el asistente sí hace operaciones de la agenda salvo completar y eliminar), CA7 (el
    doctor ve sus citas y completa las suyas), CA10 (asistente y doctor piden las citas del día) y
    CA15 (el asistente ve "agenda"); el supuesto de los selectores solo para el administrador.
  - 017: CA10 (el asistente reprograma y cancela) y "Fuera de alcance: abrir la agenda a roles que
    no son administrador".
- Mapa de permisos (`CurrentActorAuthorizationService`): `appointments.view`, `appointments.create`,
  `appointments.update` y `agenda.selectors.view` pasan de `$adminOnly` a administrador y
  asistente; el doctor necesita un permiso de lectura acotado a sus citas (p. ej.
  `appointments.view-own`) y `appointment-tracking.create` y `appointment-tracking.view` con
  comprobación de pertenencia (la cita es suya). `appointments.delete`,
  `appointment-tracking.update` y `appointment-tracking.prescriptions.*` siguen solo del
  administrador. Las recetas del doctor entran en línea al completar (009 CA1), no por el CRUD.
- Revierte el supuesto de 014 que dejó las listas de los selectores (`/agenda/patients`,
  `/agenda/doctors`, `/agenda/treatments`) solo para el administrador: el asistente las necesita
  para agendar.
- `POST /appointments/{id}/complete` y `GET /appointments/{id}/tracking` están hoy bajo
  `only.admin` (`routes/api.php:144-147`); hay que abrirlos al doctor con comprobación de
  pertenencia, sin abrir `appointment-tracking/*`.
- Relación con 009 CA12 (completar desde la edición de la cita): el doctor no tiene
  `appointments.update`, así que solo puede completar por el flujo con seguimiento.
- Recorte del doctor: `GET /appointments` y `GET /agenda/today-appointments` filtran por
  `user_id` del doctor en el servidor, ignorando cualquier filtro de doctor que llegue (CA8).
- Ruta web `/agenda` (`routes/web.php:75`) y el menú lateral: abrir a `staff:administrador,asistente,doctor`.
- 007 "Fuera de alcance" dice que mostrar la agenda al asistente era un defecto de la interfaz;
  pasa a ser comportamiento deseado.
- Tests: `AppointmentsAccessControlTest` y `RolePermissionsTest` afirman hoy el 403 del asistente
  y del doctor en la agenda; se actualizan. Crear citas con teléfono como asistente dispara
  WhatsApp: usar `fakeTwilio()`.
- `tests/Browser/screens.json` y `php artisan ui:audit-data` necesitan la agenda vista como
  asistente y como doctor.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-17 | Creación en `draft` | /specify, a partir de la investigación del tablero de inicio |
| 2026-10-17 | El doctor completa sus citas con seguimiento clínico (CA6 y CA7 ajustados, CA12 y CA13 nuevos) | Petición del usuario |
| 2026-10-17 | Aprobada | Aprobación del usuario |
| 2026-10-17 | CA8 acotado a los listados de la agenda y con lista vacía; RNF de errores sobre el seguimiento; 008 en `extends` y criterios que cambian de cada spec extendida; vuelve a `draft` | /analyze 018 ronda 1 (A7, A8, A9, A15) |
| 2026-10-17 | Reaprobada tras la edición | Aprobación del usuario |
| 2026-10-18 | Liberada en v0.4.0 | /release 018 |
| 2026-10-23 | Corregido por la ficha 2026-10-23-fix-completar-cita-sin-instrucciones en v0.5.1 (CA12) | /release |
