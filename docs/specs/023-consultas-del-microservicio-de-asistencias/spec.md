---
id: 023
slug: consultas-del-microservicio-de-asistencias
status: draft                # registro: liberado en v0.8.0 sin plan ni review (decisión del usuario, 2026-11-04)
created: 2026-11-04
extends: [021, 014]
---

# 023 · Consultas del microservicio de asistencias

## Problema
El microservicio de asistencias (otro repositorio) controla la puerta, los focos y los ventiladores
de la sala y registra las entradas de los pacientes. Necesita dos cosas de Dentissa que hoy no
existen:

- Antes de aceptar una conexión de la app móvil o una orden (abrir la puerta, encender un foco),
  confirmar que la credencial de la app sigue siendo de un administrador activo. La credencial dura
  24 horas (021) y en ese tiempo el administrador pudo ser desactivado o cambiar de rol.
- Cuando el lector de la puerta escanea el QR de un paciente, consultar la cita para decidir si la
  puerta se abre. Ahí no hay ningún usuario con sesión, solo el lector, así que el microservicio
  necesita identificarse por sí mismo. También consulta las citas del día para la app.

Esta spec documenta un cambio que se implementó antes de escribirla, por decisión del usuario, y
que se concilió con `/sync` el 2026-11-04.

## Historias de usuario
- Como responsable de la clínica, quiero que un administrador desactivado o que perdió su rol deje
  de poder abrir la puerta desde la app aunque su sesión no haya caducado.
- Como paciente, quiero que la puerta se abra al mostrar el QR de mi cita de hoy, aunque lleguen
  varios pacientes en el mismo minuto.
- Como administrador, quiero ver en la app las citas de hoy de todos los doctores.
- Como responsable de la clínica, quiero que solo el microservicio pueda consultar las citas sin
  sesión de usuario.

## Criterios de aceptación
- [ ] CA1 · Dada la credencial de la app o del panel web de un administrador activo, cuando el
  microservicio pregunta quién es su dueño, entonces recibe su identificador, su nombre, su correo,
  su rol y su estado, y la respuesta indica que no debe guardarse en cachés intermedias.
- [ ] CA2 · Dada una credencial caducada (más de 24 horas), cerrada con el cierre de sesión o que no
  existe, cuando el microservicio pregunta quién es su dueño, entonces se rechaza como sesión
  inválida.
- [ ] CA3 · (abuso) Como administrador desactivado, o al que le cambiaron el rol después de iniciar
  sesión, uso mi credencial todavía vigente → se rechaza con acceso denegado y no se devuelve
  ningún dato.
- [ ] CA4 · (abuso) Como doctor, asistente o paciente, uso mi credencial para preguntar quién soy →
  se rechaza con acceso denegado.
- [ ] CA5 · Dada la llave de servicio correcta, cuando el microservicio consulta una cita por su
  identificador, entonces recibe su fecha, hora, estado, paciente, doctor y tratamiento, con los
  mismos campos que ve el staff en el detalle de la cita; si la cita no existe, se responde que no
  se encontró, y si el identificador no tiene un formato válido, que es inválido.
- [ ] CA6 · Dada la llave de servicio correcta, cuando el microservicio consulta las citas de hoy,
  entonces recibe todas las citas del día de la clínica (zona horaria de Ciudad de México), de
  todos los doctores.
- [ ] CA7 · (abuso) Como atacante, consulto citas sin llave, con una llave incorrecta, o cuando el
  servidor no tiene ninguna llave configurada → se rechaza como no autorizado y no se devuelve
  ningún dato.
- [ ] CA8 · (abuso) Como miembro del staff o paciente con sesión abierta, consulto las rutas del
  microservicio sin la llave → se rechaza como no autorizado: una sesión de usuario no sustituye a
  la llave.
- [ ] CA9 · Dadas más de 10 lecturas de QR en un minuto, todas desde la dirección del
  microservicio y con la llave correcta, cuando llegan, entonces todas se responden; el límite del
  microservicio es de 600 consultas por minuto.
- [ ] CA10 · (abuso) Como atacante, intento adivinar la llave con más de 10 intentos por minuto
  desde la misma dirección → se rechazan los que pasan del límite, y el microservicio sigue
  respondiendo con la llave correcta aunque llegue desde esa misma dirección.
- [ ] CA11 · (abuso) Como atacante, provoco un error inesperado en cualquiera de estas consultas →
  la respuesta es un error genérico, sin detalles internos.

## Fuera de alcance
- El microservicio, el firmware de los lectores y la app Android (otros repositorios).
- El QR firmado, con caducidad y de un solo uso que describe la spec 013: aquí el QR lleva solo el
  identificador de la cita, y el microservicio decide si la cita es de hoy y si ya tiene entrada.
- Registrar la asistencia en Dentissa: la guarda el microservicio en su propia base de datos.
- Rotación automática de la llave de servicio o varias llaves a la vez.
- Permitir las llamadas desde el visor web de la app si no usa peticiones nativas (sigue como en
  021).

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (credencial de sesión y llave de servicio,
  "restringida" en `docs/security.md`), el correo del administrador y, en las citas, el nombre del
  paciente. Las consultas de citas no devuelven datos clínicos ni de contacto.
- Quién puede hacer qué:
  - administrador activo → consulta quién es el dueño de su credencial;
  - doctor, asistente, administrador inactivo y paciente → rechazados (403);
  - microservicio con la llave → consulta una cita y las citas de hoy;
  - cualquiera sin la llave, incluido el staff con sesión → rechazado (401).
- Casos de abuso: CA3, CA4, CA7, CA8, CA10 y CA11.
- Las consultas de citas no usan los permisos por rol (P5) porque no hay usuario: la llave
  identifica al microservicio. Es una excepción a P5 aceptada por el usuario para esta versión.

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe (objetivo 5 del
roadmap, [observability.md](../../observability.md)). Cuando exista, deberán quedar registrados:
- cada rechazo por llave inválida, con dirección y fecha (CA7);
- cada consulta de una cita por el microservicio, con la cita y la fecha (CA5).

Es una desviación de P14, como en 021.

## Requisitos no funcionales
- La llave se compara en tiempo constante, vive solo en el `.env` (P8) y no aparece en logs.
- Sin llave configurada, todas las consultas internas se rechazan: un ajuste olvidado nunca abre la
  puerta.
- Los errores no revelan detalles internos (P7).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-04 | brecha | ¿Quién puede preguntar quién es el dueño de una credencial? | Solo un administrador activo, como el inicio de sesión de la app | usuario (arquitectura del microservicio) |
| 2026-11-04 | brecha | ¿Cómo se identifica el microservicio cuando no hay usuario? | Con una llave de servicio compartida en una cabecera | usuario (arquitectura del microservicio) |
| 2026-11-04 | contradicción | P5 pide permisos por rol en cada operación y aquí no hay actor | Las consultas internas confían en la llave; excepción aceptada | usuario |
| 2026-11-04 | brecha | El límite general (10 por minuto por dirección sin usuario) cerraría la puerta a partir de la undécima lectura | Límite propio: 600 por minuto con la llave correcta y 10 por dirección con una llave incorrecta (CA9, CA10) | usuario (revisión del PR #44) |
| 2026-11-04 | supuesto | ¿Qué responde un identificador de cita mal formado? | Que es inválido, sin repetir el valor recibido | agente |
| 2026-11-04 | cierre | El cambio se implementó y se integró (PR #44) antes de esta spec | Spec escrita a partir del código y los tests, para conciliar con `/sync` | usuario |

## Notas para /plan
- Implementado en `fc3e495`, `b892701` y `de9a7d4` (PR #44).
- `GET /api/v1/auth/me`: `auth:sanctum` + `only.admin` → `GetAuthenticatedUserController` →
  `GetAuthenticatedUserUseCase` (`assertCan('auth.session.view')`, solo administrador) →
  `UserService::findById`; responde `UserResource` con `Cache-Control: no-store`.
- `GET /api/v1/internal/appointments/today` y `/{id}`: fuera de `throttle:api`, con
  `throttle:internal-service` (en `AppServiceProvider`) y `service.key` (`EnsureServiceKey`, compara
  `X-Service-Key` con `config('services.attendance.key')` mediante `hash_equals`).
  `GetInternalAppointmentByIdUseCase` y `GetInternalTodayAppointmentsUseCase`, sin `assertCan`,
  sobre `AppointmentsService`; responden `AppointmentResource`.
- Variable `ATTENDANCE_SERVICE_KEY` (`.env.example`, `docs/deployment.md`); la misma llave va en
  `CORE_SERVICE_KEY` del microservicio.
- Tests: `tests/Modules/Auth/Integration/GetAuthenticatedUserTest.php`,
  `tests/Modules/Appointments/Integration/InternalAppointmentsTest.php` y la fila
  `auth.session.view` de `RolePermissionsTest`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-04 | Spec creada a partir del cambio ya integrado (PR #44) | `/sync` 2026-11-04, decisión del usuario |
| 2026-11-04 | Se queda en `draft` como registro del comportamiento; liberado en v0.8.0 sin plan, tareas ni review | Decisión del usuario: liberar como en v0.7.0 |
| 2026-11-04 | Desplegado en producción con v0.8.0 (verificación local 14/14, remota 30/30, vigilancia de 15 min sin 5xx) | Registro de la liberación en `docs/sync.md` |
