---
id: 014
slug: control-de-acceso-y-errores
status: implemented
created: 2026-09-24
extends: [001, 005, 006, 007, 008, 009, 011, 012]
---

# 014 · Control de acceso a pacientes y citas, y errores sin detalles internos

## Problema
Hoy cualquier persona con sesión iniciada, incluido un paciente autorregistrado, puede leer y
modificar los datos personales y de salud de cualquier paciente, y crear, reprogramar o cancelar
citas ajenas. Además, cuando ocurre un error inesperado, la respuesta muestra el detalle interno del
fallo. Esto impide cargar datos reales de pacientes (restricción de la constitución; atiende **parcialmente** RS1 —RS1.b y RS1.c pasan a la spec 013— y por completo RS3 de `docs/security.md`) y afecta a la clínica, a sus pacientes y al cumplimiento de la LFPDPPP.

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
- [x] CA1 · Dado un administrador activo, cuando realiza cualquier operación de la tabla de permisos, entonces se le permite con el mismo resultado que hoy.
- [x] CA2 · Dado un asistente activo, cuando lista o ve pacientes, abre un expediente, consulta el historial de citas de un paciente, ve el detalle de una de esas citas o crea, modifica o elimina su contacto, dirección o datos médicos, entonces se le permite.
- [x] CA3 · Dado un doctor activo, cuando lista o ve pacientes, abre un expediente, consulta el historial de citas de un paciente o ve el detalle de una de esas citas, entonces se le permite.
- [x] CA4 · Dado un doctor activo, cuando entra a la pantalla de expedientes, entonces la ve sin acciones para crear, modificar ni eliminar datos; su menú lateral incluye "expedientes" y su panel de inicio lleva a esa pantalla.
- [x] CA5 · (abuso) Como paciente con sesión iniciada, intento cualquier operación de la tabla de permisos sobre cualquier paciente, incluido yo mismo, o sobre cualquier cita → se rechaza como acceso denegado y no se lee ni cambia ningún dato.
- [x] CA6 · (abuso) Como asistente, intento modificar los datos básicos o la contraseña de un paciente, crear o eliminar un paciente, o cualquier operación de la agenda (ver el detalle de una cita no es operación de la agenda) → se rechaza como acceso denegado.
- [x] CA7 · (abuso) Como doctor, intento crear, modificar o eliminar contacto, dirección, datos médicos o datos básicos de un paciente, o cualquier operación de la agenda (ver el detalle de una cita no es operación de la agenda) → se rechaza como acceso denegado.
- [x] CA8 · (abuso) Como miembro del staff inactivo con una sesión aún válida, intento cualquier operación de la tabla de permisos → se rechaza como acceso denegado.
- [x] CA9 · (abuso) Como visitante sin sesión, intento cualquier operación de la tabla de permisos → se rechaza como no autenticado.
- [x] CA10 · (abuso) Como asistente o doctor, pido las citas del día → se rechaza como acceso denegado, no como error interno (hoy responde error interno; spec 007, CA12).
- [x] CA11 · Dado un administrador, cuando modifica los datos básicos de un paciente e incluye una contraseña nueva, entonces la contraseña se actualiza; asistente y doctor no pueden hacerlo (CA6, CA7).
- [x] CA12 · (abuso) Como atacante, provoco un error inesperado en cualquier operación de la API (`/api/v1`: pacientes, expediente, citas, seguimiento clínico, catálogo y contenido del sitio) → la respuesta solo dice que hubo un error interno, sin el mensaje técnico del fallo, trazas, consultas a la base de datos ni nombres internos, y el detalle queda registrado para el equipo.
- [x] CA13 · Dado un error inesperado registrado para el equipo, cuando se revisa el registro, entonces no contiene datos de salud ni datos de contacto del paciente (P11).
- [x] CA14 · Dado un error esperado de negocio (dato no encontrado, dato inválido, conflicto), cuando ocurre, entonces se conserva el mensaje de negocio que se muestra hoy, salvo los datos personales o de salud que contenga, que se retiran del mensaje (CA16).
- [x] CA15 · Dado cualquier actor con sesión, cuando ve su menú lateral o su panel de inicio, entonces solo aparecen accesos a pantallas que puede abrir y acciones que puede ejecutar: el asistente no ve "agenda" ni "Registrar nuevo paciente", y el paciente no ve "agenda" ni "expedientes" (hoy aparecen y el servidor los rechaza; P13).
- [x] CA16 · (abuso) Como actor con permiso de escritura, envío un email ya usado por otro paciente, o un email, teléfono, nombre, código postal o tipo de sangre inválidos → el error de negocio (400 o 409) explica qué campo falla sin repetir el valor enviado ni revelar datos de otro paciente (P7, P11).

## Fuera de alcance
- Portal del paciente: que un paciente consulte o edite sus propios datos (hoy no tiene pantallas).
- Que el doctor registre o edite el seguimiento clínico y las recetas (spec 009, CA9; spec aparte). El seguimiento clínico sigue siendo solo del administrador, sin cambios de permisos.
- Dos correcciones de RS1 de `docs/security.md` (RS1.b y RS1.c), que pasan a la spec 013 (decisión del usuario, 2026-09-24):
  - Separar la autenticación de pacientes y de staff: hoy el guard `sanctum` no fija provider y acepta tokens de pacientes. Aquí lo compensa el middleware de staff, que los rechaza.
  - Comprobar que cada paciente solo accede a sus propios recursos: hoy ningún paciente tiene acceso a estas operaciones, y las rutas de paciente llegan con la 013.
- Errores inesperados en las vistas web (Blade): con `APP_DEBUG=true` pueden mostrar trazas; se corrige con la configuración de producción (RS10 de `docs/security.md`).
- Los logs de los módulos Auth y Users (login, registro de pacientes, usuarios), que registran `getMessage()` y trazas en sus catch genéricos: pasan a la deuda del roadmap junto con sus mensajes que repiten el email.
- Registro de auditoría de accesos y de accesos denegados, e identificador de traza por petición (RS11 de `docs/security.md`; objetivo 5 del roadmap, spec aparte).
- Cifrado en reposo de los datos de salud (RS2), CSRF (RS5) y revocar sesiones al cambiar la contraseña (RS8).
- Que el doctor solo vea a los pacientes que atiende: por ahora ve a todos.
- Rechazar fechas pasadas en citas (spec 007, CA14) y demás deuda funcional del roadmap.
- Permisos del catálogo de tratamientos, usuarios y contenido del sitio: ya son solo del administrador; aquí solo se corrige su respuesta ante errores inesperados (CA12).

## Cobertura de riesgos
Cada corrección de cada riesgo o brecha citado (IDs de `docs/security.md` y `docs/observability.md`),
dentro o fuera de alcance. Esta spec atiende RS1 **parcialmente**.

| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS1.a Restringir a staff | dentro | CA5, CA6, CA7, CA8, CA9 |
| RS1.b Propiedad del recurso | fuera | Ningún paciente accede a estas rutas → spec 013, CA24 (decisión del usuario, 2026-09-24) |
| RS1.c Provider del guard `sanctum` | fuera | Lo compensa el middleware de staff → spec 013, CA23 (decisión del usuario, 2026-09-24) |
| RS1.d Tests de acceso denegado | dentro | CA5–CA9 y requisito no funcional de pruebas por actor |
| RS2.a Casts `encrypted` | fuera | Cifrado en reposo → roadmap, Próxima etapa |
| RS2.b Cifrado de volumen y backups | fuera | → roadmap, Próxima etapa |
| RS3.a Respuestas 500 genéricas | dentro | CA12, CA13 |
| RS5.a Protección CSRF | fuera | Otro riesgo → roadmap, Pendientes y deuda (decisión del usuario, 2026-09-24) |
| RS8.a Revocar tokens al restablecer contraseña | fuera | Otro flujo (spec 003) → roadmap, Pendientes y deuda (decisión del usuario, 2026-09-24) |
| RS10.a `APP_DEBUG=false` en `.env.example` | fuera | Configuración, no código → roadmap objetivo 4 (decisión del usuario, 2026-09-24) |
| RS10.b `SESSION_ENCRYPT=true` en `.env.example` | fuera | Configuración → roadmap, Pendientes y deuda (decisión del usuario, 2026-09-24) |
| RS11.a Eventos de auditoría | fuera | → spec de auditoría, roadmap objetivo 5 (sección "Auditoría") |
| OB2.a Sin teléfono ni nombre en los logs de `CreateAppointmentController` | dentro | P11 en código tocado (plan, TM7); test con `Log::spy` en `CreateAppointmentTest` |
| OB2.b Sin datos personales en el resto de logs | fuera | whatsApp, Email y eventos de cita → roadmap objetivo 5 |
| OB10.a Respuestas 500 sin el mensaje de la excepción | dentro | CA12 |
| OB10.b Sin trazas ni `getMessage()` en logs de Auth y whatsApp | fuera | → roadmap, Pendientes y deuda: Auth y Users (ver "Fuera de alcance") y whatsApp, en entradas propias (decisión del usuario, 2026-09-24) |

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
  - Como staff con permiso de escritura, pruebo emails en el alta o edición de pacientes para saber cuáles existen y ver sus datos en el mensaje de conflicto → el mensaje no repite el email (CA16).

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe y se construye en
una spec aparte (objetivo 5 del roadmap, [observability.md](../../observability.md)). Cuando exista,
los accesos denegados (401/403) de CA5–CA9 quedarán registrados con actor, acción, recurso y fecha
de forma centralizada, sin cambios en esta spec. Desviación de P14 que se justifica en el
Constitution Check de `/plan`, junto con la falta de `request_id`.

## Requisitos no funcionales
- Cada operación de la tabla de permisos tiene una prueba automatizada de acceso denegado por cada tipo de actor no autorizado: paciente, staff sin permiso para esa operación, staff inactivo y visitante sin sesión (P5, criterio de éxito del objetivo 1 del roadmap).
- Ninguna respuesta del sistema ante un error inesperado incluye el mensaje técnico del fallo. Se verifica con pruebas que fuerzan el error en al menos una operación de cada área (pacientes, expediente, citas, seguimiento clínico, catálogo y contenido del sitio) y con una revisión que no deje ningún caso (P7).
- La comprobación de permisos se hace en el servidor; ocultar opciones del menú no sustituye a esa comprobación (P13).

## Preguntas abiertas
- Ninguna.

## Supuestos
- El asistente no modifica los datos básicos del paciente (nombre, email, estado): su pantalla de expedientes hoy solo edita contacto, dirección y datos médicos. Solo el administrador lo hace desde la pantalla de pacientes.
- El asistente sí puede eliminar contacto, dirección y datos médicos, porque su pantalla lo ofrece hoy.
- Las listas para los selectores de la agenda quedan solo para el administrador, porque solo la pantalla de agenda las usa (`create-appointment.js`) y esa pantalla es solo del administrador. Esto **revierte** la corrección de U1 BR-5 que abrió `GET /agenda/treatments` a cualquier staff (hoy afirmada en `GetAppointmentsTest`): se hizo cuando la agenda aún admitía a otros roles, y ya no los admite (decisión del usuario, 2026-09-24).
- "Ver el detalle de una cita" es de todo el staff activo: la pantalla de expedientes lo usa desde el historial de citas (botón "Ver"). En ese detalle, quien no es administrador no ve el seguimiento clínico ni la opción de editar (decisión del usuario, 2026-09-24).
- El doctor accede a la pantalla de expedientes en modo solo lectura (CA4); sin pantalla, el permiso de lectura no tendría uso.
- Como ningún paciente tiene acceso a estas operaciones, no hace falta comprobar si un recurso pertenece al paciente. Esa comprobación llega con el portal del paciente.
- Los mensajes de negocio (no encontrado, inválido, conflicto) no son detalles internos y se mantienen, sin datos personales ni de salud (CA14, CA16; constitución 1.1.1, P7).
- Solo cambian aquí los mensajes de negocio de Patients. Los de Users y Auth que repiten el email (p. ej. `UserException`, `AuthException`) quedan fuera de alcance y pasan a deuda del roadmap.
- De las specs 001, 011 y 012 solo cambia la respuesta ante un error inesperado (500 genérico) y, en `api/*`, que el 401 sea siempre JSON; su comportamiento de negocio no cambia. De la 008 cambia además CA3: el catálogo para la agenda (`GET /agenda/treatments`) pasa a ser solo del administrador.
- Orden de implementación: esta spec va antes que la 013 (decisión del usuario, 2026-09-24). La 013 partirá de este código y, con su CA23, el token de un paciente en rutas de staff podrá pasar de 403 a 401; lo declara la 013 al extender esta spec.
- Incluye la gestión del contenido del sitio (Unidad 5 de AI-DLC, en pausa), pero solo su respuesta ante errores inesperados. Decisión del usuario, 2026-09-24.

## Notas para /plan
- Hoy: 23 rutas de `/api/v1` bajo solo `auth:sanctum` (`routes/api.php`: `agenda/*`, `patients/*` salvo POST/DELETE, `appointments/*` salvo complete/tracking). El guard `sanctum` no fija provider en `config/auth.php` y acepta tokens de `PatientModel`.
- Propuestas del roadmap y las specs 005 y 006: un middleware de staff por rol (p. ej. `only.staff` o `role:administrador,asistente`), `assertCan` con permisos por rol en `CurrentActorAuthorizationService` (hoy solo el administrador activo tiene permisos) y la comprobación de rol de los closures de `routes/web.php` movida a middleware.
- Códigos esperados: sin sesión → 401; sin permiso → 403; error inesperado → 500 con `{"error": "Internal server error"}` (P7). `GetTodayAppointmentsController` hoy da 500 ante `assertCan` fallido.
- 48 controladores con `'message' => $e->getMessage()` en el 500: Patients 15, Appointments 11, AppointmentTracking 6, ContentManagement 16. Auth y Users ya están corregidos y sirven de referencia (SECURITY-15).
- Los tests existentes que hoy autentican como cualquier actor (`PatientCrudTest`, `AddressTest`, `ContactInfoTest`, `MedicalDataTest`, `PatientRecordTest`, `CreateAppointmentTest`, `UpdateAppointmentTest`, `GetAppointmentsTest`) deben pasar a actores con el rol correcto.
- Sidebar: `resources/views/components/ui/sidebar.blade.php:50-51` (quitar `agenda` al asistente; añadir `expedientes` al doctor). Dashboard: `resources/views/pages/dashboard.blade.php` enlaza a `/agenda` para el asistente (l. 211) y a `/agenda` y `/expedientes-clinicos` en la rama `@else` que comparten doctor y paciente (l. 238, 243); separar doctor de paciente. Vista de expedientes en solo lectura para el doctor en `resources/js/pages/records/index.js`.
- Actualizar al cerrar (`/release`, Historial de cada spec extendida): 005 CA3 y CA4 ("usuario autenticado" pasa a staff con permiso), CA11 y CA12 (resueltos); 006 CA1 y CA2 (staff), CA3 (entra el doctor, en solo lectura) y CA5 (resuelto para pacientes; el doctor sí lee expedientes); 007 CA1, CA3–CA6 y CA10 (solo administrador), CA9 (todo el staff), CA12 y CA13 (resueltos); 009 CA8; 008 CA3 (catálogo de la agenda solo para administrador), 500 genérico y, en `agenda/treatments`, `time` nulo devuelto como `0`, igual que ya hace `GET /treatments`; 011 y 012 (500 genérico); 001 (401 JSON en logout sin sesión). RS1.a y RS1.d → mitigadas (RS1 queda parcialmente mitigado hasta la 013) y RS3.a → mitigada en `docs/security.md`; OB2.a y OB10.a en `docs/observability.md`.
- Mensajes de Patients con datos (CA16): `PatientException::shouldBeUniqueEmail`, `EmailException`, `ContactEmailException`, `PhoneNumberException`, `PatientNameException` (2), `PostalCodeException` y `BloodTypeException`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-24 | Creación | /specify, objetivo 1 del roadmap |
| 2026-09-24 | CA4 y CA15 cubren también el panel de inicio (dashboard) | Revisión del impacto en el frontend |
| 2026-09-24 | Sección Auditoría (diferida a la spec del objetivo 5) y aprobación | Constitución 1.1.0 (P14); aprobada por el usuario |
| 2026-09-24 | "Ver el detalle de una cita" pasa a todo el staff (CA2, CA3, CA6, CA7, tabla, supuestos); CA15 cubre también acciones | `/plan` detectó que expedientes usa ese detalle y que el asistente ve "Registrar nuevo paciente"; decisión del usuario |
| 2026-09-24 | Implementada: 16 criterios cubiertos por tests en verde; T076 añadida durante `/implement` (selector de doctores) | `/implement 014` |
| 2026-09-24 | Cobertura de riesgos: destinos de RS5.a, RS8.a, RS10.a, RS10.b y OB10.b, OB2.a con su test, RS1.a sin CA15; CA15 antes que CA16; `time` nulo en `agenda/treatments` | `/analyze 014` ronda 4 (C4, C14, D2–D4, D6); decisión del usuario |
| 2026-09-24 | Sección "Cobertura de riesgos" y citas por ID (RS/OB) | `/init --upgrade` a 1.6.0 (formato 1.5.6); decisión del usuario |
| 2026-09-24 | CA12 acotado a la API; web y logs de Auth y Users fuera de alcance; 008 CA3 declarado; orden 014 → 013 | Segundo `/analyze 014` (B6, B11, B14, B15); decisión del usuario |
| 2026-09-24 | Provider del guard y propiedad del recurso (riesgo 1) pasan a la spec 013 | Aclaración del alcance del riesgo 1; decisión del usuario |
| 2026-09-24 | CA14 sin datos personales y CA16 nuevo; `extends` añade 001, 008, 011 y 012; reversión de U1 BR-5 declarada; criterios de specs extendidas completos | `/analyze 014` (A13, A17–A19, A28) y constitución 1.1.1; decisión del usuario |
