---
id: 020
slug: tablero-de-inicio
status: approved
created: 2026-10-22
extends: [009, 018]
---

# 020 · Tablero de inicio

## Problema
Al entrar al panel, el staff ve una pantalla de inicio sin datos: los contadores muestran "-",
hay una tarjeta de "Estado: En línea" y una "Actividad reciente" siempre vacía, y las acciones
rápidas no dependen de lo que cada rol puede hacer. Para saber qué toca hoy, quién es el
siguiente paciente o qué citas pasadas siguen sin cerrar, cada persona tiene que abrir la agenda
y revisar día por día. Las citas que se quedan sin cerrar o sin seguimiento clínico no las ve
nadie hasta que alguien las busca. El administrador, además, tiene que ir a "Reportes" para ver
cómo va el mes.

## Historias de usuario
- Como miembro del staff, quiero ver al entrar las citas de hoy y cuál es la siguiente, para
  organizar el día sin abrir la agenda.
- Como miembro del staff, quiero ver cuántas citas de hoy van completadas, por atender y
  canceladas, para saber cómo va el día de un vistazo.
- Como administrador, asistente o doctor, quiero ver las citas de días pasados que siguen sin
  cerrar y cerrarlas desde ahí según lo que mi rol puede hacer, para que la agenda refleje lo que
  pasó.
- Como administrador o doctor, quiero ver las citas completadas que no tienen seguimiento clínico
  y registrarlo, para que ninguna atención quede sin constancia.
- Como miembro del staff, quiero tener a mano las acciones que más uso según mi rol, para
  llegar a ellas en un paso.
- Como administrador, quiero ver en el inicio cómo va el mes frente al anterior, para no tener
  que abrir los reportes a diario.

## Criterios de aceptación
- [ ] CA1 · Dado un miembro activo del staff, cuando entra al inicio del panel, entonces ve la
  agenda de hoy (en la zona horaria de la clínica) ordenada por hora de inicio, con hora,
  paciente, tratamiento, doctor y estado de cada cita. El administrador y el asistente ven todas
  las citas de la clínica; el doctor, solo las que tiene asignadas. Si no hay citas hoy, ve "No
  hay citas para hoy.".
- [ ] CA2 · Dada la agenda de hoy, cuando se muestra, entonces la siguiente cita aparece
  destacada: la primera de hoy en estado asignada o reprogramada cuya hora de inicio es igual o
  posterior a la hora actual de la clínica. Desde ella se abre su detalle con las mismas acciones
  que el rol tiene en la agenda (018), incluido "Reprogramar" con el mismo diálogo y las mismas
  reglas (017); ningún rol ve una acción que no funcione. Si no queda ninguna, se indica "No
  quedan citas por atender hoy.".
- [ ] CA3 · Dada la agenda de hoy, cuando se muestra, entonces aparecen cuatro contadores de hoy:
  citas (todas, también las canceladas), completadas, por atender (asignadas o reprogramadas, sea
  cual sea su hora) y canceladas. Completadas, por atender y canceladas suman el total de citas,
  y cuentan las mismas citas que la lista del rol (las del doctor, solo las suyas).
- [ ] CA4 · Dadas citas de días anteriores a hoy que siguen asignadas o reprogramadas, cuando se
  muestra el inicio, entonces aparecen como "Citas sin cerrar", de la más reciente a la más
  antigua, con fecha, hora, paciente, tratamiento y doctor. Se ven hasta 10, con el total
  pendiente (p. ej. "y 5 más"). El administrador y el asistente ven todas; el doctor, solo las
  suyas. Si no hay ninguna, se indica "No hay citas pendientes de cerrar.".
- [ ] CA5 · Dada una cita sin cerrar, cuando el administrador (cualquier cita) o el doctor (las
  suyas) pulsa "Completar", entonces se abre el mismo diálogo de completar con seguimiento
  clínico y recetas de la agenda, con sus mismas reglas (009 CA1–CA4 y CA13, 018 CA12). Al
  guardar, la cita queda completada con su seguimiento y sale de la lista. El asistente no ve
  "Completar".
- [ ] CA6 · Dada una cita sin cerrar, cuando el administrador o el asistente pulsa "Cancelar
  cita" y lo confirma, entonces la cita queda cancelada, se avisa "Cita cancelada." y sale de la
  lista. Si elige no cancelar o cierra la confirmación, la cita no cambia (017 CA7). El doctor no
  ve "Cancelar cita".
- [ ] CA7 · Dadas citas completadas que no tienen seguimiento clínico, cuando el administrador
  (todas) o el doctor (las suyas) abre el inicio, entonces aparecen como "Completadas sin
  seguimiento", de la más reciente a la más antigua, con fecha, paciente, tratamiento y doctor,
  sin ningún dato clínico. Se ven hasta 10, con el total pendiente. Si no hay ninguna, se indica
  "Todas las citas completadas tienen seguimiento.". El asistente no ve esta sección.
- [ ] CA8 · Dada una cita completada sin seguimiento, cuando el administrador o el doctor al que
  está asignada pulsa "Registrar seguimiento", entonces se abre el formulario del seguimiento
  clínico con los mismos campos y reglas que al completar una cita (motivo, síntomas, diagnóstico
  y procedimiento obligatorios; observaciones, recomendaciones y recetas opcionales; 009 CA1 y
  CA13). Al guardar, la cita sigue completada, queda con su seguimiento y sus recetas, y sale de
  la lista. Si falta un dato obligatorio, el formulario sigue abierto con lo escrito y el mensaje
  junto al campo.
- [ ] CA9 · Dado un miembro activo del staff, cuando abre el inicio, entonces ve solo las
  acciones rápidas de su rol:

  | Acción | Administrador | Asistente | Doctor | Qué hace |
  |---|---|---|---|---|
  | Nueva cita | sí | sí | no | abre el diálogo de agendar de la agenda, con sus reglas (017 CA4, 018 CA2) |
  | Nuevo paciente | sí | no | no | abre el diálogo de alta de paciente (005) |
  | Buscar paciente | sí | sí | sí | lleva a los expedientes clínicos con el buscador listo para escribir |
  | Agenda de hoy | sí | sí | sí | lleva a la agenda en el día de hoy |
  | Registrar seguimiento | sí | no | sí | abre el diálogo de completar de la siguiente cita (CA2); si no hay siguiente cita, no aparece |

- [ ] CA10 · Dado un administrador, cuando abre el inicio, entonces ve las citas del mes en curso
  (todas las del mes, también las ya agendadas para días que vienen, como en "Reportes"), los
  pacientes nuevos del mes y los pacientes activos (ficha en estado activo). Las citas y los
  pacientes nuevos se comparan con el mes anterior y su diferencia (p. ej. "42 citas · +6 respecto
  a septiembre (36)"), con el aviso "Mes en curso: datos hasta hoy" y un enlace a "Reportes". Los
  números son los mismos que muestra "Reportes" para el mes en curso (019 CA2, CA4 y CA9). El
  asistente y el doctor no ven esta sección.
- [ ] CA11 · Dado el inicio de cualquier rol del staff, cuando se muestra, entonces ya no hay
  contadores con "-", ni la tarjeta "Estado: En línea", ni "Actividad reciente", ni la acción
  "Nuevo usuario", ni la tarjeta del encabezado con la inicial, el correo y el rol (el rol sigue
  en el menú lateral). El título de la pestaña es "Inicio - Dentissa" (cierra 016/R42). En el
  inicio del paciente, el título es también "Inicio - Dentissa" y su inicial se muestra completa
  aunque lleve acento; así no queda ninguna inicial recortada (cierra 016/R43).
- [ ] CA12 · Dado que una sección del inicio no se puede cargar, cuando falla, entonces esa
  sección muestra "No se pudo cargar esta sección." con un botón "Reintentar" y las demás
  secciones se siguen viendo y usando. Mientras carga, cada sección indica que está cargando.
- [ ] CA13 · (abuso) Como doctor, intento obtener por otra vía las citas de hoy, las sin cerrar o
  las completadas sin seguimiento de otro doctor, o completar o registrar el seguimiento de una
  cita que no es mía, o ver los indicadores del mes → solo recibo mis citas (las demás no
  aparecen ni cuentan) y lo demás se rechaza con acceso denegado, sin cambiar ninguna cita ni
  guardar ningún seguimiento.
- [ ] CA14 · (abuso) Como asistente, intento por otra vía completar una cita, registrar un
  seguimiento, ver las completadas sin seguimiento, ver los indicadores del mes o dar de alta un
  paciente → se rechaza con acceso denegado y no cambia nada.
- [ ] CA15 · (abuso) Como staff, intento registrar un seguimiento en una cita que ya lo tiene, que
  está cancelada o que todavía no está completada, por otra vía → se rechaza con un mensaje en
  español, la cita no cambia y no se guarda ningún seguimiento. Lo mismo al completar una cita que
  ya tiene seguimiento o está cancelada: el rechazo va en español y sin el identificador de la
  cita (cambia 009 CA3 y CA4 solo en el texto del mensaje).
- [ ] CA16 · (abuso) Como paciente, staff inactivo o visitante sin sesión, intento obtener
  cualquier dato del tablero por otra vía → se rechaza igual que la agenda (014, 018 CA11). Si
  el staff inactivo entra a `/dashboard`, ve la página "Sin permiso", como en la agenda. El
  paciente sigue viendo su inicio, sin más cambios que los de CA11.

## Fuera de alcance
- El recordatorio de WhatsApp de las citas de mañana y la confirmación por WhatsApp.
- Tasas de asistencia y de cancelación, tratamientos más solicitados, carga por doctor, demanda
  por día y hora y tendencias de varios meses: están en "Reportes" (019) o fuera de él.
- Pacientes que no vuelven, ingresos, estado "no asistió", ocupación del sillón y origen del
  paciente.
- El inicio del paciente (portal del paciente sin pantallas): no cambia, salvo el título y la
  inicial (CA11).
- Corregir un seguimiento o unas recetas ya registrados (009 CA11): solo se registra el que falta.
- Que el asistente complete citas, registre seguimiento o dé de alta pacientes.
- Limitar el historial de citas del expediente a las del doctor (spec por crear del roadmap).
- Actualización automática del tablero mientras está abierto: se recarga al volver a entrar o
  tras una acción hecha desde él.
- Personalizar qué secciones se ven o en qué orden.
- La acción "Nuevo usuario": sigue en "Usuarios".

## Seguridad y privacidad
- Datos sensibles involucrados: nombre del paciente y tratamiento de cada cita (personal y salud,
  según `docs/security.md`), que ya ve cada rol en la agenda (018); y el seguimiento clínico y las
  recetas (salud), que el administrador y el doctor registran desde el inicio. Los indicadores
  del mes son totales, sin datos de pacientes (019). Las listas del inicio nunca muestran datos
  clínicos.
- Quién puede hacer qué:

  | Acción | Administrador | Asistente | Doctor | Paciente / inactivo / sin sesión |
  |---|---|---|---|---|
  | Ver agenda de hoy, siguiente cita y contadores | todas | todas | solo las suyas | denegado |
  | Ver citas sin cerrar | todas | todas | solo las suyas | denegado |
  | Completar una cita sin cerrar | sí | denegado | solo las suyas | denegado |
  | Cancelar una cita sin cerrar | sí | sí | denegado | denegado |
  | Ver completadas sin seguimiento | todas | denegado | solo las suyas | denegado |
  | Registrar seguimiento de una completada | sí | denegado | solo las suyas | denegado |
  | Ver indicadores del mes | sí | denegado | denegado | denegado |
  | Nuevo paciente | sí | denegado | denegado | denegado |

- Casos de abuso:
  - Como doctor, intento ver o tocar las citas de otro doctor o los indicadores del mes → solo
    recibo las mías y lo demás se rechaza (CA13).
  - Como asistente, intento completar, registrar seguimiento, ver los indicadores o dar de alta
    un paciente → se rechaza (CA14).
  - Como staff, intento registrar un seguimiento en una cita que no corresponde → se rechaza (CA15).
  - Como paciente, staff inactivo o sin sesión, intento obtener datos del tablero → se rechaza
    (CA16).

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe y se construye en
una spec aparte (objetivo 5 del roadmap, [observability.md](../../observability.md)). Cuando
exista, deberán quedar registrados con actor, acción, recurso, resultado y fecha:
- el seguimiento clínico y las recetas que se registran desde el inicio (CA5, CA8; escritura de
  datos de salud);
- las cancelaciones hechas desde el inicio (CA6);
- los accesos denegados de CA13, CA14 y CA16.

Ver la agenda de hoy o las listas no es lectura de datos de salud: no muestran datos clínicos. Es
una desviación de P14 que se justifica en el Constitution Check de `/plan`, como en las specs 014,
017, 018 y 019.

## Diseño
Debe sentirse como el punto de partida del día: al entrar, cada persona sabe en cinco segundos
quién es el siguiente paciente, cómo va el día y si hay algo pendiente de cerrar. Lo pendiente
llama la atención sin alarmar, y el rol nunca ve botones que no puede usar. No debe parecerse a
"Reportes" (019) ni repetir la agenda completa.

Pantallas que toca:
- el inicio del panel para el administrador, el asistente y el doctor: agenda de hoy con la
  siguiente cita, contadores, citas sin cerrar, completadas sin seguimiento, acciones rápidas e
  indicadores del mes (solo administrador);
- los estados de cada sección: carga, vacío y error con reintento;
- el formulario "Registrar seguimiento" de una cita ya completada (reutiliza los campos del
  diálogo de completar).

Qué debe lograr cada rol al llegar:
- administrador: atender lo pendiente y ver el mes;
- asistente: preparar el día y cerrar las citas canceladas;
- doctor: saber quién es su siguiente paciente y dejar constancia de lo que atendió.

Recursos de marca: logo y paleta del sistema de diseño
([docs/design/system.md](../../design/system.md)). Solo con componentes del sistema de diseño.
El diálogo de completar, la confirmación de cancelar y el diálogo de agendar se reutilizan de la
agenda (017, 018).
Diseño de la spec: [design.md](design.md) (candidato c5, 80/100: siguiente cita con franja rosa, barra del
día con sus cuatro cifras, agenda con la línea "Ahora" y pendientes agrupados por día).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos. Cada candidato son dos
páginas, administrador (todas las secciones) y doctor, medidas con
[design/criterios.json](design/criterios.json) y [design/criterios-doctor.json](design/criterios-doctor.json).

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | "Siguiente cita" en la primera pantalla | A la vista a 390 y 1440 px, administrador y doctor | CA2 |
| K2 | Contadores de hoy en la primera pantalla | "Citas", "Completadas", "Por atender" y "Canceladas" a la vista a 1440 px | CA3 |
| K3 | Literales del administrador | "Citas sin cerrar", "Completadas sin seguimiento", "Completar", "Cancelar cita", "Registrar seguimiento", "Nueva cita", "Nuevo paciente", "Buscar paciente", "Agenda de hoy", "Mes en curso: datos hasta hoy", "Ver reportes" y "y 5 más" presentes | CA4–CA10 |
| K4 | Literales del doctor | "Completar", "Registrar seguimiento", "Buscar paciente" y "Agenda de hoy" presentes | CA5, CA8, CA9 |
| K5 | Títulos | `h1`–`h3` en 2 líneas como máximo | modo operar |
| K6 | Estabilidad visual | CLS ≤ 0.1 | modo operar |

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: pesos por defecto.
No se miden en el diseño, porque dependen de los datos o del rol en el servidor: los estados de
carga, vacío y error (CA1, CA4, CA7, CA12), la vista del asistente y que ningún rol vea acciones de
otro (CA9, CA11). Los verifican las tareas que construyen el inicio, con `npm run test:ui`.

## Requisitos no funcionales
- El inicio completo se muestra en menos de 2 s con hasta 50 citas hoy, 200 sin cerrar y 2 000
  citas en el mes.
- Los cálculos de contadores, listas e indicadores se hacen en el servidor, y los indicadores del
  mes salen del mismo cálculo que "Reportes" (P13).
- La comprobación de permisos y el recorte a las citas del doctor se hacen en el servidor;
  ocultar una sección o un botón nunca la sustituye. Cada operación de la tabla de permisos tiene
  una prueba de acceso denegado por cada actor no autorizado de su fila (P5).
- El inicio de los tres roles pasa la prueba en navegador a 390, 768 y 1440 px (P15,
  `npm run test:ui`), con contraste, foco visible, uso con teclado y listas que se leen en orden
  con lector de pantalla (WCAG 2.1 AA).
- Los errores no revelan detalles internos (P7) y los logs no llevan datos de pacientes ni datos
  clínicos (P11).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-17 | brecha | ¿Qué entra en el tablero y qué no? | Alcance y "Fuera" de `docs/roadmap.md` → Pendientes y deuda (tablero de inicio) | usuario |
| 2026-10-18 | brecha | ¿Orden respecto a los reportes? | Después de los reportes mensuales (019), reutilizando sus cálculos | usuario (`docs/roadmap.md`) |
| 2026-10-22 | contradicción | El roadmap pone "Nuevo paciente" en las acciones por rol, pero dar de alta pacientes es solo del administrador (005, 014) | "Nuevo paciente" solo para el administrador; no cambian los permisos de pacientes | usuario |
| 2026-10-22 | brecha | ¿Quién ve las completadas sin seguimiento y registra el seguimiento? | El administrador todas y el doctor las suyas; el asistente no ve la sección (no toca datos clínicos, 018) | usuario |
| 2026-10-22 | brecha | La spec tiene 16 criterios y extiende 2 specs, ¿se divide? | Una sola spec: es una sola pantalla y reutiliza reglas de 017, 018 y 019 | usuario |
| 2026-10-22 | brecha | ¿Se conservan "Nuevo usuario", "Estado: En línea" y "Actividad reciente" del inicio actual? | Se quitan los tres | usuario |
| 2026-10-22 | implícita | ¿Qué significa "citas sin cerrar"? | Asignadas o reprogramadas con fecha pasada, igual que en "Reportes"; en el inicio, solo las de días anteriores a hoy (las de hoy están en la agenda del día) | `docs/specs/019-reportes-mensuales/spec.md` CA2 |
| 2026-10-22 | implícita | ¿En qué zona horaria se decide qué es "hoy" y "ahora"? | Ciudad de México, la de la clínica | `docs/specs/017-reprogramar-cancelar-citas/spec.md` |
| 2026-10-22 | implícita | ¿Qué es un paciente activo y un paciente nuevo del mes? | Ficha en estado activo; ficha dada de alta en el mes, como en "Reportes" | `docs/specs/019-reportes-mensuales/spec.md` CA4 |
| 2026-10-22 | implícita | ¿Quién completa y quién cancela? | Completar: administrador y doctor (sus citas); cancelar: administrador y asistente | `docs/specs/018-agenda-por-rol/spec.md` → Seguridad y privacidad |
| 2026-10-22 | implícita | ¿Las listas del inicio auditan una lectura de datos de salud? | No: no muestran datos clínicos; los registros de seguimiento y los accesos denegados sí, cuando exista el registro (objetivo 5) | `docs/observability.md` → Registro de auditoría |
| 2026-10-23 | contradicción | CA11 pedía la inicial completa, pero el diseño aprobado (c5) quitó la tarjeta con la inicial, el correo y el rol | CA11 dice que esa tarjeta ya no está; 016/R43 se cierra porque no queda ninguna inicial recortada | usuario (/plan 020) |
| 2026-10-23 | contradicción | CA2 ofrecía las acciones de la agenda, pero "Reprogramar desde el inicio" estaba fuera de alcance (/analyze A1) | El detalle del inicio ofrece Reprogramar con el diálogo y las reglas de la agenda | usuario |
| 2026-10-23 | brecha | ¿El mes del administrador cuenta hasta hoy o todo el mes? (/analyze A10) | Todo el mes, como Reportes; texto "42 citas · +6 respecto a septiembre (36)" | usuario |
| 2026-10-23 | brecha | ¿Completar también responde en español al rechazar una cita con seguimiento o cancelada? (/analyze A9) | Sí, en español y sin el identificador de la cita | usuario |
| 2026-10-23 | contradicción | El paciente comparte la vista y su inicial salía recortada, lo que dejaba 016/R43 abierto (/analyze A16) | El paciente ve "Inicio - Dentissa" y su inicial completa; el resto de su inicio no cambia | usuario |
| 2026-10-23 | brecha | ¿Qué ve el staff inactivo en `/dashboard`? (/analyze A17) | La página "Sin permiso", como en la agenda | usuario |
| 2026-10-22 | supuesto | ¿Cuántas citas se ven en las listas de pendientes? | Hasta 10, de la más reciente a la más antigua, con el total pendiente | agente; aceptado por el usuario, 2026-10-22 |
| 2026-10-22 | supuesto | ¿Qué hace "Registrar seguimiento" en las acciones rápidas? | Abre el diálogo de completar de la siguiente cita; si no hay, no aparece | agente; aceptado por el usuario, 2026-10-22 |
| 2026-10-22 | supuesto | ¿El tablero se actualiza solo? | No; se recarga al entrar y tras una acción hecha desde él | agente; aceptado por el usuario, 2026-10-22 |
| 2026-10-22 | supuesto | ¿Cuánto puede tardar? | Menos de 2 s con hasta 50 citas hoy, 200 sin cerrar y 2 000 en el mes | agente; aceptado por el usuario, 2026-10-22 |
| 2026-10-22 | diseño | ¿Con qué criterios se acepta el diseño? | Los de "Criterios de diseño" (K1–K6, puertas por defecto); dos páginas por candidato, administrador y doctor | usuario (/design-spec 020) |
| 2026-10-22 | diseño | ¿Cómo se ve el inicio? | Candidato c5 de [design.md](design.md), aceptado con 80/100 en la quinta vuelta | /design-spec 020 |
| 2026-10-22 | supuesto | ¿Cómo se ve un fallo de carga? | Por sección, con "Reintentar"; las demás siguen visibles | agente; aceptado por el usuario, 2026-10-22 |

## Notas para /plan
- Criterios de las specs extendidas que cambian (los anota `/release` en su Historial):
  - 018: CA12 y la fila "Completar con seguimiento" se amplían. El doctor también registra el
    seguimiento de sus citas que ya están completadas sin él (hoy solo completa las asignadas o
    reprogramadas).
  - 009: CA1 (seguimiento de una cita no cancelada sin seguimiento) queda accesible desde el
    inicio para el administrador y el doctor en sus citas; CA9 avanza en esa parte.
- Comprobar cómo se registra hoy el seguimiento de una cita ya completada (`POST
  /appointments/{id}/complete` sobre una completada sin seguimiento, o una operación nueva). El
  permiso `appointment-tracking.create-own` del doctor necesita comprobación de pertenencia.
- Reutilizar los cálculos de la 019 (citas por estado, sin cerrar, pacientes nuevos y activos)
  para el mes en curso. No duplicarlos.
- La ruta `/dashboard` también la ven los pacientes (`routes/web.php:42`): el inicio del paciente
  no cambia y el del staff necesita sus datos por API con el recorte por rol.
- Reutilizar los diálogos de agendar, completar y cancelar y el de alta de paciente
  (`x-ui.create-patient-modal`).
- Cierra el pendiente "Inicio del panel: los contadores muestran '-'" y 016/R42 y 016/R43 del
  roadmap.
- `tests/Browser/screens.json` y `php artisan ui:audit-data` necesitan el inicio de los tres roles
  con datos (citas de hoy, sin cerrar y completadas sin seguimiento).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-22 | Creación en `draft` | /specify, desde el pendiente del roadmap |
| 2026-10-22 | Aprobada | Aprobación del usuario |
| 2026-10-23 | CA11: el inicio ya no muestra la tarjeta con inicial, correo y rol; vuelve a `draft` | Contradicción con `design.md` (c5) detectada en /plan 020 |
| 2026-10-23 | Reaprobada tras la edición de CA11 | Aprobación del usuario |
| 2026-10-23 | CA2 (Reprogramar en el detalle), CA10 (todo el mes, texto), CA11 (paciente: título e inicial), CA15 (también completar), CA16 (staff inactivo), "Fuera de alcance"; vuelve a `draft` | /analyze 020 ronda 1 (A1, A9, A10, A16, A17) |
| 2026-10-23 | Reaprobada tras la edición de /analyze ronda 1 | Aprobación del usuario |
