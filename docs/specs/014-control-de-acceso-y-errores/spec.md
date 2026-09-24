---
id: 014
slug: control-de-acceso-y-errores
status: approved
created: 2026-09-24
extends: [005, 006, 007, 009]
---

# 014 · Control de acceso a pacientes y citas, y errores sin detalles internos

## Problema
Hoy cualquier persona con sesión iniciada, incluido un paciente autorregistrado, puede leer y
modificar los datos personales y de salud de cualquier paciente, y crear, reprogramar o cancelar
citas ajenas. Además, cuando ocurre un error inesperado, la respuesta muestra el detalle interno del
fallo. Esto impide cargar datos reales de pacientes (restricción de la constitución y riesgos 1 y 3
de `docs/security.md`) y afecta a la clínica, a sus pacientes y al cumplimiento de la LFPDPPP.

## Historias de usuario
- Como administrador, quiero que solo el staff autorizado acceda a los datos de pacientes y a la agenda, para proteger datos de salud y evitar citas manipuladas.
- Como asistente, quiero seguir consultando expedientes y actualizando el contacto, la dirección y los datos médicos de los pacientes, para preparar su atención.
- Como doctor, quiero consultar los expedientes y el historial de citas de los pacientes, para conocer su situación antes de atenderlos.
- Como paciente, quiero que nadie fuera del staff autorizado vea ni cambie mis datos de salud, para que mi información esté protegida.
- Como administrador, quiero que un error inesperado no revele detalles internos del sistema, para no dar pistas a un atacante.

## Permisos esperados

| Operación | Administrador | Asistente | Doctor | Paciente |
|---|---|---|---|---|
| Listar y ver pacientes | sí | sí | sí | no |
| Ver el expediente consolidado de un paciente | sí | sí | sí | no |
| Ver el historial de citas de un paciente | sí | sí | sí | no |
| Ver el detalle de una cita | sí | sí | sí | no |
| Crear, modificar y eliminar contacto, dirección y datos médicos | sí | sí | no | no |
| Modificar los datos básicos de un paciente, incluida su contraseña | sí | no | no | no |
| Crear y eliminar pacientes | sí | no | no | no |
| Agenda: calendario, citas del día, crear, reprogramar, cambiar estado, eliminar y listas para los selectores | sí | no | no | no |

Un miembro del staff inactivo no tiene ningún permiso. Quien no tiene sesión no tiene ningún permiso.

## Criterios de aceptación
- [ ] CA1 · Dado un administrador activo, cuando realiza cualquier operación de la tabla de permisos, entonces se le permite con el mismo resultado que hoy.
- [ ] CA2 · Dado un asistente activo, cuando lista o ve pacientes, abre un expediente, consulta el historial de citas de un paciente, ve el detalle de una de esas citas o crea, modifica o elimina su contacto, dirección o datos médicos, entonces se le permite.
- [ ] CA3 · Dado un doctor activo, cuando lista o ve pacientes, abre un expediente, consulta el historial de citas de un paciente o ve el detalle de una de esas citas, entonces se le permite.
- [ ] CA4 · Dado un doctor activo, cuando entra a la pantalla de expedientes, entonces la ve sin acciones para crear, modificar ni eliminar datos; su menú lateral incluye "expedientes" y su panel de inicio lleva a esa pantalla.
- [ ] CA5 · (abuso) Como paciente con sesión iniciada, intento cualquier operación de la tabla de permisos sobre cualquier paciente, incluido yo mismo, o sobre cualquier cita → se rechaza como acceso denegado y no se lee ni cambia ningún dato.
- [ ] CA6 · (abuso) Como asistente, intento modificar los datos básicos o la contraseña de un paciente, crear o eliminar un paciente, o cualquier operación de la agenda (ver el detalle de una cita no es operación de la agenda) → se rechaza como acceso denegado.
- [ ] CA7 · (abuso) Como doctor, intento crear, modificar o eliminar contacto, dirección, datos médicos o datos básicos de un paciente, o cualquier operación de la agenda (ver el detalle de una cita no es operación de la agenda) → se rechaza como acceso denegado.
- [ ] CA8 · (abuso) Como miembro del staff inactivo con una sesión aún válida, intento cualquier operación de la tabla de permisos → se rechaza como acceso denegado.
- [ ] CA9 · (abuso) Como visitante sin sesión, intento cualquier operación de la tabla de permisos → se rechaza como no autenticado.
- [ ] CA10 · (abuso) Como asistente o doctor, pido las citas del día → se rechaza como acceso denegado, no como error interno (hoy responde error interno; spec 007, CA12).
- [ ] CA11 · Dado un administrador, cuando modifica los datos básicos de un paciente e incluye una contraseña nueva, entonces la contraseña se actualiza; asistente y doctor no pueden hacerlo (CA6, CA7).
- [ ] CA12 · (abuso) Como atacante, provoco un error inesperado en cualquier operación del sistema (pacientes, expediente, citas, seguimiento clínico, catálogo y contenido del sitio) → la respuesta solo dice que hubo un error interno, sin el mensaje técnico del fallo, trazas, consultas a la base de datos ni nombres internos, y el detalle queda registrado para el equipo.
- [ ] CA13 · Dado un error inesperado registrado para el equipo, cuando se revisa el registro, entonces no contiene datos de salud ni datos de contacto del paciente (P11).
- [ ] CA14 · Dado un error esperado de negocio (dato no encontrado, dato inválido, conflicto), cuando ocurre, entonces el mensaje de negocio que se muestra hoy se conserva.
- [ ] CA15 · Dado cualquier actor con sesión, cuando ve su menú lateral o su panel de inicio, entonces solo aparecen accesos a pantallas que puede abrir y acciones que puede ejecutar: el asistente no ve "agenda" ni "Registrar nuevo paciente", y el paciente no ve "agenda" ni "expedientes" (hoy aparecen y el servidor los rechaza; P13).

## Fuera de alcance
- Portal del paciente: que un paciente consulte o edite sus propios datos (hoy no tiene pantallas).
- Que el doctor registre o edite el seguimiento clínico y las recetas (spec 009, CA9; spec aparte). El seguimiento clínico sigue siendo solo del administrador, sin cambios de permisos.
- Registro de auditoría de accesos y de accesos denegados, e identificador de traza por petición (riesgo 11 de `docs/security.md`; objetivo 5 del roadmap, spec aparte).
- Cifrado en reposo de los datos de salud (riesgo 2), CSRF (riesgo 5) y revocar sesiones al cambiar la contraseña (riesgo 8).
- Que el doctor solo vea a los pacientes que atiende: por ahora ve a todos.
- Rechazar fechas pasadas en citas (spec 007, CA14) y demás deuda funcional del roadmap.
- Permisos del catálogo de tratamientos, usuarios y contenido del sitio: ya son solo del administrador; aquí solo se corrige su respuesta ante errores inesperados (CA12).

## Seguridad y privacidad
- Datos sensibles involucrados: **datos de salud** (datos médicos, historial de citas) y **datos personales** (nombre, teléfono, contacto de emergencia, email, dirección), además de la contraseña del paciente. Clasificación restringida y confidencial según `docs/security.md`.
- Quién puede hacer qué: ver la tabla "Permisos esperados".
- Casos de abuso:
  - Como paciente autorregistrado, intento leer o modificar datos de salud de otros pacientes → se rechaza (CA5).
  - Como paciente, intento crear o reprogramar citas a nombre de otro paciente o doctor → se rechaza (CA5).
  - Como asistente o doctor, intento acciones fuera de mi rol, como cambiar la contraseña de un paciente para suplantarlo → se rechaza (CA6, CA7).
  - Como exempleado desactivado con una sesión abierta, intento seguir consultando expedientes → se rechaza (CA8).
  - Como visitante sin sesión, intento acceder a datos de pacientes → se rechaza (CA9).
  - Como atacante, provoco errores para obtener detalles internos del sistema → respuesta genérica (CA12).

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe y se construye en
una spec aparte (objetivo 5 del roadmap, [observability.md](../../observability.md)). Cuando exista,
los accesos denegados (401/403) de CA5–CA9 quedarán registrados con actor, acción, recurso y fecha
de forma centralizada, sin cambios en esta spec. Desviación de P14 que se justifica en el
Constitution Check de `/plan`.

## Requisitos no funcionales
- Cada operación de la tabla de permisos tiene una prueba automatizada de acceso denegado por cada tipo de actor no autorizado: paciente, staff sin permiso para esa operación, staff inactivo y visitante sin sesión (P5, criterio de éxito del objetivo 1 del roadmap).
- Ninguna respuesta del sistema ante un error inesperado incluye el mensaje técnico del fallo. Se verifica con pruebas que fuerzan el error en al menos una operación de cada área (pacientes, expediente, citas, seguimiento clínico, catálogo y contenido del sitio) y con una revisión que no deje ningún caso (P7).
- La comprobación de permisos se hace en el servidor; ocultar opciones del menú no sustituye a esa comprobación (P13).

## Preguntas abiertas
- Ninguna.

## Supuestos
- El asistente no modifica los datos básicos del paciente (nombre, email, estado): su pantalla de expedientes hoy solo edita contacto, dirección y datos médicos. Solo el administrador lo hace desde la pantalla de pacientes.
- El asistente sí puede eliminar contacto, dirección y datos médicos, porque su pantalla lo ofrece hoy.
- Las listas para los selectores de la agenda quedan solo para el administrador, porque solo la pantalla de agenda las usa y esa pantalla es solo del administrador (spec 007, U1 BR-5).
- "Ver el detalle de una cita" es de todo el staff activo: la pantalla de expedientes lo usa desde el historial de citas (botón "Ver"). En ese detalle, quien no es administrador no ve el seguimiento clínico ni la opción de editar (decisión del usuario, 2026-09-24).
- El doctor accede a la pantalla de expedientes en modo solo lectura (CA4); sin pantalla, el permiso de lectura no tendría uso.
- Como ningún paciente tiene acceso a estas operaciones, no hace falta comprobar si un recurso pertenece al paciente. Esa comprobación llega con el portal del paciente.
- Los mensajes de negocio (no encontrado, inválido, conflicto) no son detalles internos y se mantienen (CA14).
- Incluye la gestión del contenido del sitio (Unidad 5 de AI-DLC, en pausa), pero solo su respuesta ante errores inesperados. Decisión del usuario, 2026-09-24.

## Notas para /plan
- Hoy: 24 rutas de `/api/v1` bajo solo `auth:sanctum` (`routes/api.php`: `agenda/*`, `patients/*` salvo POST/DELETE, `appointments/*` salvo complete/tracking). El guard `sanctum` no fija provider en `config/auth.php` y acepta tokens de `PatientModel`.
- Propuestas del roadmap y las specs 005 y 006: un middleware de staff por rol (p. ej. `only.staff` o `role:administrador,asistente`), `assertCan` con permisos por rol en `CurrentActorAuthorizationService` (hoy solo el administrador activo tiene permisos) y la comprobación de rol de los closures de `routes/web.php` movida a middleware.
- Códigos esperados: sin sesión → 401; sin permiso → 403; error inesperado → 500 con `{"error": "Internal server error"}` (P7). `GetTodayAppointmentsController` hoy da 500 ante `assertCan` fallido.
- 48 controladores con `'message' => $e->getMessage()` en el 500: Patients 15, Appointments 11, AppointmentTracking 6, ContentManagement 16. Auth y Users ya están corregidos y sirven de referencia (SECURITY-15).
- Los tests existentes que hoy autentican como cualquier actor (`PatientCrudTest`, `AddressTest`, `ContactInfoTest`, `MedicalDataTest`, `PatientRecordTest`, `CreateAppointmentTest`, `UpdateAppointmentTest`, `GetAppointmentsTest`) deben pasar a actores con el rol correcto.
- Sidebar: `resources/views/components/ui/sidebar.blade.php:50-51` (quitar `agenda` al asistente; añadir `expedientes` al doctor). Dashboard: `resources/views/pages/dashboard.blade.php` enlaza a `/agenda` para el asistente (l. 211) y a `/agenda` y `/expedientes-clinicos` en la rama `@else` que comparten doctor y paciente (l. 238, 243); separar doctor de paciente. Vista de expedientes en solo lectura para el doctor en `resources/js/pages/records/index.js`.
- Actualizar al cerrar: specs 005 (CA11, CA12), 006 (CA3, CA5), 007 (CA9, CA12, CA13) y 009 (CA8) mediante specs de extensión o notas en su Historial, y los riesgos 1 y 3 de `docs/security.md`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-24 | Creación | /specify, objetivo 1 del roadmap |
| 2026-09-24 | CA4 y CA15 cubren también el panel de inicio (dashboard) | Revisión del impacto en el frontend |
| 2026-09-24 | Sección Auditoría (diferida a la spec del objetivo 5) y aprobación | Constitución 1.1.0 (P14); aprobada por el usuario |
| 2026-09-24 | "Ver el detalle de una cita" pasa a todo el staff (CA2, CA3, CA6, CA7, tabla, supuestos); CA15 cubre también acciones | `/plan` detectó que expedientes usa ese detalle y que el asistente ve "Registrar nuevo paciente"; decisión del usuario |
