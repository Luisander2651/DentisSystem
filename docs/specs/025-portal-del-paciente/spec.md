---
id: 025
slug: portal-del-paciente
status: approved
created: 2026-11-08
extends: [001, 009, 016, 020, 024]
---

# 025 · Portal del paciente

## Problema
Un paciente con cuenta puede iniciar sesión, pero su inicio solo muestra "Próxima cita: aún no
agendada": no ve sus citas, ni las recetas que le dio el doctor, ni el QR que necesita para entrar a
la clínica (spec 024), así que todo lo tiene que pedir a recepción. Es lo último que falta para un
MVP funcional. Además, el riesgo RS1 de [security.md](../../security.md) sigue parcialmente
mitigado: falta comprobar que cada dato pertenece a quien lo pide (RS1.b) y separar las sesiones de
paciente y de personal (RS1.c); abrir datos a los pacientes sin cerrarlo expondría citas y recetas
ajenas. Esta spec atiende RS1 por completo.

## Historias de usuario
- Como paciente, quiero ver mi próxima cita al entrar para saber cuándo, a qué hora, con quién y
  para qué tratamiento tengo que ir.
- Como paciente, quiero ver el historial de mis citas para recordar qué tratamientos me hice y
  cuándo.
- Como paciente, quiero ver las recetas y las recomendaciones de una cita completada para seguir
  las indicaciones del doctor sin llamar a la clínica.
- Como paciente, quiero consultar y descargar el QR de mi cita para enseñarlo en la puerta el día
  de la cita.
- Como responsable de la clínica, quiero que cada paciente vea solo sus propios datos, que nadie
  sin sesión vea nada y que una cuenta desactivada pierda el acceso, para proteger datos de salud.

## Criterios de aceptación
Portal (paciente activo con sesión):
- [x] CA1 · Dado un paciente con al menos una cita asignada o reprogramada para hoy o una fecha
  posterior, cuando entra a su portal, entonces ve como "Próxima cita" la más cercana de ellas, con
  fecha, hora, tratamiento y doctor, y su QR a la vista sin pulsar nada más.
- [x] CA2 · Dado un paciente sin citas asignadas ni reprogramadas desde hoy, cuando entra a su
  portal, entonces ve "Aún no tienes una cita agendada" y un enlace a la página de contacto de la
  clínica, sin QR.
- [x] CA3 · Dado un paciente con citas, cuando abre su historial, entonces ve todas sus citas, de la
  más reciente a la más antigua, cada una con fecha, hora, tratamiento, doctor y estado (asignada,
  reprogramada, completada o cancelada).
- [x] CA4 · Dado un paciente sin ninguna cita, cuando abre su historial, entonces ve "Aún no tienes
  citas registradas" en lugar de la lista.
- [x] CA5 · Dada una cita completada del paciente con seguimiento, cuando abre su detalle, entonces
  ve las recomendaciones del doctor y cada receta con medicamento, dosis, frecuencia, duración e
  instrucciones.
- [x] CA6 · Dada una cita completada del paciente sin recetas o sin seguimiento registrado, cuando
  abre su detalle, entonces ve "Esta cita no tiene recetas" (y, si no hay recomendaciones, no
  aparece ese apartado), sin aviso de error.
- [x] CA7 · Dada una cita asignada o reprogramada del paciente, cuando abre su detalle, entonces ve
  el QR de la cita (mismo contenido que el de la spec 024: solo el identificador de la cita) y un
  botón "Descargar QR" que le da un archivo de imagen con el QR.
- [x] CA8 · Dada una cita completada o cancelada del paciente, cuando abre su detalle, entonces no
  aparece el QR ni el botón para descargarlo; una cita cancelada tampoco muestra recetas.
- [x] CA9 · Dado un fallo al obtener el QR o las recetas, cuando el paciente abre el portal o el
  detalle, entonces el resto se muestra igual y en el lugar de lo que falló aparece un aviso en
  español sin términos técnicos.
- [x] CA10 · Dado un paciente que inicia sesión, cuando llega al panel, entonces aterriza en su
  portal y el menú solo le ofrece sus pantallas y cerrar sesión: ninguna opción del personal
  (agenda, pacientes, expedientes, usuarios, contenido, reportes).

Seguridad:
- [x] CA11 · (abuso) Sin sesión, pido las citas, la próxima cita, las recetas o el QR de un
  paciente (con su identificador o el de la cita) → se rechaza como no autenticado (401) y no
  recibo ningún dato; si abro el portal en el navegador, me lleva al inicio de sesión.
- [x] CA12 · (abuso) Como paciente con sesión, pido las recetas o el QR de una cita de
  otro paciente indicando su identificador → recibo la misma respuesta que para una cita que no
  existe (no encontrada, 404), sin ningún dato de la cita ni indicio de que existe.
- [x] CA13 · (abuso) Como paciente con sesión, envío el identificador de otro paciente en la
  petición de mis citas o de mi próxima cita → el identificador se ignora y solo recibo mis propias
  citas: el portal identifica al paciente por su sesión, nunca por un dato que manda el cliente.
- [x] CA14 · (abuso) Como paciente con sesión, intento las rutas del personal (expedientes,
  pacientes, agenda, detalle o QR de citas del personal, seguimiento clínico) para mí o para otro
  paciente → siguen rechazándose (403), como fija la spec 014, y en el navegador veo "Sin permiso".
- [x] CA15 · (abuso) Como paciente cuya cuenta desactivó la clínica mientras tenía la sesión
  abierta, pido mis citas, mis recetas, mi QR, las de otro paciente o mi expediente → se rechaza
  (403) sin ningún dato y el portal en el navegador muestra "Sin permiso"; al volver a iniciar
  sesión se rechaza como hoy (spec 001).
- [x] CA16 · (abuso) Como miembro del personal con sesión, pido las pantallas o los datos del portal
  del paciente → se rechaza (403); y una sesión de paciente nunca se acepta como sesión de personal
  ni al revés, aunque los identificadores de ambas cuentas coincidieran (RS1.c).
- [x] CA17 · (abuso) Como paciente, inspecciono las respuestas del portal → nunca contienen motivo,
  síntomas, diagnóstico, procedimiento ni observaciones del seguimiento clínico, ni datos de
  contacto de otras personas, ni datos de otros pacientes.
- [x] CA18 · (abuso) Como paciente, pido el QR de una de mis citas completadas o canceladas → se
  rechaza y no recibo ningún QR.
- [x] CA19 · (abuso) Como atacante, provoco un fallo interno en cualquier petición del portal → la
  respuesta es el error genérico (500) sin detalles internos (P7).
- [x] CA20 · (abuso) Como alguien con acceso al navegador o a un proxy compartido, intento recuperar
  respuestas del portal (citas, recetas o QR) guardadas antes → las respuestas indican que no deben
  guardarse en cachés.

## Fuera de alcance
- Agendar, cancelar o reprogramar citas desde el portal: sigue siendo trabajo de recepción.
- Ver el seguimiento clínico completo (motivo, síntomas, diagnóstico, procedimiento,
  observaciones) o el expediente propio (datos médicos, contacto, dirección); acceder a sus datos
  es parte de los derechos ARCO, pendientes de su propia spec.
- Editar el perfil, cambiar la contraseña desde el portal (el restablecimiento de la spec 003 sigue
  igual) o subir documentos.
- Imprimir o enviar recetas por correo o WhatsApp.
- Enviar el QR por WhatsApp o correo (fuera de alcance de la 024 por el mismo motivo).
- Notificaciones o recordatorios dentro del portal.
- La app móvil del paciente.
- Cambiar quién del personal ve qué (p. ej. el historial por doctor del roadmap).
- Un diseño nuevo: se reutilizan las pantallas y componentes existentes ([design.md](design.md)).

## Seguridad y privacidad
- Datos sensibles involucrados (según [security.md](../../security.md)): datos de salud (recetas y
  recomendaciones del seguimiento clínico), citas del paciente (tratamiento, doctor, fechas) y el QR,
  que funciona como credencial al portador en la puerta (riesgo aceptado en la 024, plan R1).
- Quién puede hacer qué:
  - Paciente activo con sesión → ve solo sus citas, su próxima cita, las recetas y recomendaciones
    de sus citas completadas y el QR de sus citas asignadas o reprogramadas. No modifica nada.
  - Paciente desactivado (con o sin sesión abierta) → nada.
  - Sin sesión → nada.
  - Personal (cualquier rol, activo o no) → no usa el portal del paciente; sus permisos en las
    pantallas del personal no cambian.
- Casos de abuso:
  - Como visitante sin sesión, pido citas de un paciente con su identificador → se rechaza (CA11).
  - Como paciente, pido una cita ajena por su identificador → no encontrada, sin revelar que existe
    (CA12).
  - Como paciente, mando el identificador de otro paciente → se ignora (CA13).
  - Como paciente, uso las rutas del personal → se rechaza (CA14).
  - Como paciente desactivado con sesión abierta, sigo consultando → se rechaza (CA15).
  - Como personal, uso el portal, o una sesión se hace pasar por la de otro tipo de cuenta → se
    rechaza (CA16).
  - Como paciente, busco datos clínicos internos o de otros en las respuestas → no están (CA17).
  - Como paciente, pido el QR de una cita cerrada para volver a entrar → se rechaza (CA18).
  - Como atacante, provoco un error → 500 genérico (CA19).
  - Como alguien con acceso a cachés, recupero respuestas antiguas → no se guardaron (CA20).
  - El volumen de peticiones lo limita el límite general de la API, sin cambios.

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS1.a — Restringir las rutas de pacientes, subrecursos, expediente y citas a staff | fuera | Ya mitigada (v0.1.0, spec 014); esta spec la conserva (CA14) → sin cambio |
| RS1.b — Comprobar la propiedad del recurso | dentro | CA12, CA13, CA15, CA18 |
| RS1.c — Fijar el provider del guard `sanctum` (una sesión de un tipo de cuenta no vale como la del otro) | dentro | CA16 |
| RS1.d — Añadir tests de acceso denegado | fuera | Ya mitigada (v0.1.0, spec 014); las rutas nuevas suman sus tests de acceso denegado por actor (CA11–CA16, CA18) como refuerzo → sin cambio |

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe (objetivo 5 del
roadmap, [observability.md](../../observability.md)). Es una desviación de P14, como en 021, 023 y
024, que se justifica en el Constitution Check de `/plan`. Cuando exista, deberán quedar
registrados con actor, acción, recurso, resultado y fecha:
- cada lectura de las recetas y recomendaciones de una cita por el paciente (lectura de datos de
  salud; CA5);
- cada obtención del QR de una cita por el paciente (credencial de acceso; CA1, CA7);
- los accesos denegados de CA11, CA12, CA14, CA15, CA16 y CA18.

## Diseño
El paciente debe sentirse orientado en cinco segundos: al entrar sabe si tiene cita, cuándo y con
quién, y tiene su QR a mano para la puerta. El historial y las recetas son lectura tranquila, sin
botones que no puede usar. No es un diseño nuevo: reutiliza el inicio del panel (rama del paciente),
la tarjeta de próxima cita, la lista e historial de citas del expediente (tarjetas en móvil, tabla en
escritorio) y el bloque del QR del "Detalle de la cita" de la 024, con componentes `x-ui.*`.
Sistema: [docs/design/system.md](../../design/system.md). Acta del
ciclo de diseño y candidato aceptado (c3, solo con componentes existentes): [design.md](design.md).

Pantallas que toca:
- Inicio del panel del paciente (`/dashboard`, hoy con "Próxima cita: aún no agendada"): próxima
  cita con su QR, o el estado vacío.
- Historial de citas del paciente: lista de sus citas.
- Detalle de una cita del paciente (diálogo): datos de la cita, QR si está abierta, recetas y
  recomendaciones si está completada.
- Menú lateral del paciente: solo sus pantallas.

Recursos de marca: los del sistema (logo, paleta, tipografía); no se diseña nada nuevo.

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Próxima cita y su QR en la primera pantalla | Fecha, hora y QR completo (≥ 240 × 240 px) visibles sin desplazarse a 390, 768 y 1440 px | CA1 |
| K2 | Estados vacíos | "Aún no tienes una cita agendada" y "Aún no tienes citas registradas" visibles cuando corresponde | CA2, CA4 |
| K3 | Historial legible | Una tarjeta por cita por debajo de 768 px y tabla a partir de 768 px, sin desborde horizontal | CA3 |
| K4 | Literales del detalle | "Descargar QR" solo en citas asignadas o reprogramadas; recetas solo en completadas; "Esta cita no tiene recetas" cuando no hay | CA5–CA8 |
| K5 | Menú del paciente | Ningún enlace a pantallas del personal | CA10 |
| K6 | Estabilidad visual | La próxima cita y el QR no hacen saltar la pantalla al cargar (CLS ≤ 0.1) | CA1, CA9 |

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: pesos por defecto.
Medidos en el ciclo de `/design-spec` ([design.md](design.md)) salvo K5 y los estados sin
candidato (historial vacío, detalle de una cita abierta o cancelada, cita sin recetas), que se
verifican en `/implement` y `/review` con `npm run test:ui` (ver Decisiones).

## Requisitos no funcionales
- Accesibilidad: las pantallas del portal cumplen WCAG 2.1 AA (constitución) y pasan
  `npm run test:ui` a 390, 768 y 1440 px (desbordes, contraste, foco, teclado, controles ≥ 44 px).
- Rendimiento: con 100 citas en el historial, el portal y el historial muestran sus datos en menos
  de 2 s para el 95 % de las cargas en el entorno local.
- Textos en español, en tuteo y sin términos técnicos.
- Descubribilidad: ninguna pantalla del portal aparece en buscadores (no se enlaza en el mapa del
  sitio y pide no indexarse); el sitio público no cambia.
- Ningún dato de salud ni de contacto aparece en logs ni en mensajes de error (P11).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | brecha | ¿Qué ve el paciente de una cita completada? | Recetas (medicamento, dosis, frecuencia, duración, instrucciones) y recomendaciones del doctor; sin motivo, síntomas, diagnóstico, procedimiento ni observaciones | usuario |
| 2026-11-08 | brecha | ¿Solo consulta o también actúa sobre sus citas? | Solo consulta; agendar, cancelar y reprogramar quedan fuera | usuario |
| 2026-11-08 | brecha | ¿Qué correcciones de RS1 cierra esta spec? | RS1.b y RS1.c (RS1.a y RS1.d ya mitigadas por la 014; se añaden tests de acceso denegado de las rutas nuevas) | usuario |
| 2026-11-08 | brecha | ¿Dividir la spec (~20 criterios)? | Se mantiene una sola spec: una pantalla y una entrega para cerrar el MVP | usuario |
| 2026-11-08 | contradicción | `shared/contract.md` exige `design.md` aprobado antes de `/plan` en specs con interfaz; el usuario pide omitir `/design-spec` | Se omite `/design-spec`: se reutilizan pantallas y componentes existentes y K1–K6 se verifican en `/implement` y `/review`, como hizo la 024 (plan D8). Se vuelve a confirmar en `/plan` | usuario (petición del 2026-11-08) |
| 2026-11-08 | implícita | El QR del paciente: ¿qué contiene y en qué citas aparece? | El mismo de la 024 (solo el identificador de la cita), solo en citas asignadas o reprogramadas | `docs/specs/024-qr-de-cita-para-el-acceso/spec.md` CA1, CA7 |
| 2026-11-08 | implícita | La cuenta desactivada no puede iniciar sesión | Ya se rechaza al iniciar sesión; esta spec añade que una sesión abierta antes de desactivarla tampoco sirve | `docs/specs/001-inicio-y-cierre-de-sesion/spec.md`, `docs/security.md` |
| 2026-11-08 | implícita | Auditoría | Desviación de P14 mientras no exista el registro, con los eventos listados para cuando exista | `docs/observability.md`, specs 021, 023 y 024 |
| 2026-11-08 | implícita | El paciente sigue sin acceso a las rutas del personal | 403 como fija la 014 | `docs/specs/014-control-de-acceso-y-errores/spec.md` |
| 2026-11-08 | supuesto | ¿Qué es la "próxima cita"? | La asignada o reprogramada más cercana cuya fecha es hoy o posterior (zona horaria de la clínica), aunque su hora de hoy ya haya pasado, para que el paciente que llega tarde siga teniendo su QR | agente |
| 2026-11-08 | supuesto | ¿Qué responde una cita ajena? | Lo mismo que una inexistente (404), para no revelar que existe; a diferencia de las rutas del personal (403), aquí el recurso no es del paciente | agente |
| 2026-11-08 | supuesto | Paciente desactivado con sesión abierta | 403 y vista "Sin permiso", igual que el personal inactivo en `/dashboard` y `/agenda` | agente (por analogía con la spec 020) |
| 2026-11-08 | supuesto | Orden y tamaño del historial | Todas las citas, de la más reciente a la más antigua; el umbral de rendimiento se fija con 100 citas | agente |
| 2026-11-08 | supuesto | Estado vacío de la próxima cita | "Aún no tienes una cita agendada" con enlace a `/contacto`, en lugar del texto actual "Te avisaremos cuando haya disponibilidad" (promete algo que no ocurre) | agente |
| 2026-11-09 | diseño | ¿Se hace el ciclo de `/design-spec`? | Sí, ciclo completo (sustituye a la decisión del 2026-11-08 de omitirlo): el ciclo aceptó c2 (82/100). K5 se verifica en `/implement` | usuario; [design.md](design.md) |
| 2026-11-09 | diseño | c2 no reutiliza los componentes existentes | Se descarta c2 y se acepta c3, hecho solo con componentes existentes y sin rúbrica: la cita y su QR juntos en el inicio con la tarjeta de próxima cita y el bloque del QR de la 024 | usuario; [design.md](design.md) |
| 2026-11-10 | contradicción | El inicio de c3 usa el saludo del tablero y deja fuera la insignia "Mi cuenta", el rol "Paciente" y la inicial que fijan 016 (CA30, `RoleCopyTest`) y 020 (A16, `DashboardScreenTest`) | Se queda el inicio de c3: la 025 extiende 016 y 020 y sus tests se ajustan; el rol del paciente sigue en el menú lateral (`/analyze 025` A3) | usuario |
| 2026-11-10 | diseño | ¿Hay una petición de "detalle de una cita" en el portal? | No: el detalle se compone con los datos de la lista y las peticiones de recetas y de QR, así que CA11 y CA12 hablan de esas peticiones (plan D4; `/analyze 025` A9) | usuario |

## Notas para /plan
- Reutilizar la rama del paciente de `resources/views/pages/dashboard.blade.php` (hoy un marcador),
  `components/dashboard/next-appointment.blade.php`,
  `components/records/appointments-history-table.blade.php` y el bloque del QR de
  `components/calendar/view-appointment-modal.blade.php`; layout `layouts/patient.blade.php` y el
  sidebar con `$sidebarRole = 'paciente'`. Diseño aprobado: c3 en [design.md](design.md).
- Rutas de API propias del paciente bajo `/api/v1`, identificadas por la sesión (p. ej. "mis citas"),
  sin `patientId` en la ruta; comprobación de pertenencia en el caso de uso y 404 para ajenas.
- RS1.c: fijar el provider del guard `sanctum` en `config/auth.php` / separar los tipos de token
  (`user-auth` frente a `patient-auth`) y probarlo con identificadores que coincidan.
- El paciente desactivado con token vivo: hoy `LoginService` solo comprueba el estado al emitir el
  token; falta comprobarlo en cada petición del portal (middleware o caso de uso).
- Generación del QR: reutilizar el puerto y adaptador de la 024; `Cache-Control: private, no-store`.
- `npm run test:ui` y `php artisan ui:audit-data` deben incluir al paciente con próxima cita, con
  historial, sin citas y con una cita completada con recetas.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-08 | Creada en `draft` | /specify |
| 2026-11-08 | Aprobada | Aprobación del usuario |
| 2026-11-10 | Extiende 016 y 020; CA11 y CA12 sin "detalle"; RS1.d fuera (ya mitigada) | `/analyze 025` (A3, A9, A17), aprobado por el usuario |
| 2026-11-11 | `implemented`: los 20 criterios con sus tests en verde | `/implement 025` |
