---
id: 021
slug: sesion-app-movil
status: draft                # registro: liberado en v0.7.0 sin plan ni review (decisión del usuario, 2026-11-03)
created: 2026-11-03
extends: [001]
---

# 021 · Inicio de sesión de la app móvil

## Problema
La app Android de asistencias (Dentissa Asistencias: citas de hoy, entradas de los pacientes y
control de la puerta, los focos y los ventiladores de la sala) necesita que el administrador entre
con su cuenta de Dentissa y que la app conserve una credencial de sesión para presentarla en cada
petición, a Dentissa y al microservicio de asistencias. El inicio de sesión web entrega esa
credencial de forma que solo el navegador la usa y ninguna app puede leerla. Además, hoy cada
inicio de sesión cierra todas las demás sesiones del actor (001 CA4): entrar en la app cerraría el
panel web del administrador, y entrar en el panel cerraría la app.

Esta spec documenta un cambio que se implementó antes de escribirla, por decisión del usuario, y
que se concilió con `/sync` el 2026-11-03.

## Historias de usuario
- Como administrador, quiero iniciar sesión en la app móvil con mi correo y mi contraseña para
  consultar y controlar la sala desde el teléfono.
- Como administrador, quiero que mi sesión del panel web siga abierta cuando entro en la app, y al
  revés, para no tener que volver a entrar cada vez que cambio de una a otra.
- Como administrador, quiero cerrar la sesión de la app sin cerrar la del panel web.
- Como responsable de la clínica, quiero que solo los administradores activos puedan entrar en la
  app para que nadie más pueda abrir la puerta ni cambiar la sala.

## Criterios de aceptación
- [ ] CA1 · Dado un administrador activo con su contraseña correcta, cuando inicia sesión desde la
  app, entonces la respuesta le entrega una credencial de sesión junto con su identificador, su
  nombre, su correo y su tipo de actor; la respuesta indica que no debe guardarse en cachés
  intermedias y no deja ninguna credencial guardada en el navegador.
- [ ] CA2 · Dada la credencial que recibió la app, cuando la presenta en una operación protegida,
  entonces Dentissa la reconoce como la sesión de ese administrador, con sus mismos permisos,
  durante 24 horas (la misma caducidad que la sesión web).
- [ ] CA3 · (abuso) Como doctor o asistente activo, intento iniciar sesión en la app con mi
  contraseña correcta → se rechaza con acceso denegado, no recibo ninguna credencial y mi sesión
  del panel web, si la tengo, sigue abierta.
- [ ] CA4 · (abuso) Como paciente, intento iniciar sesión en la app con mi contraseña correcta → se
  rechaza con acceso denegado, no recibo ninguna credencial y mi sesión web, si la tengo, sigue
  abierta.
- [ ] CA5 · Dado un correo que no existe o una contraseña incorrecta, cuando se intenta iniciar
  sesión en la app, entonces se rechaza como credenciales inválidas, con la misma respuesta en los
  dos casos.
- [ ] CA6 · Dado un administrador inactivo con su contraseña correcta, cuando intenta iniciar sesión
  en la app, entonces se rechaza como cuenta inactiva y no se crea ninguna sesión.
- [ ] CA7 · Dado un intento sin correo, sin contraseña o con un correo mal formado, cuando se envía,
  entonces se rechaza como datos inválidos indicando qué campo falla.
- [ ] CA8 · Dado un administrador con sesión abierta en el panel web, cuando inicia sesión en la
  app, entonces la sesión web sigue válida; y dado un administrador con sesión abierta en la app,
  cuando inicia sesión en el panel web, entonces la sesión de la app sigue válida. Reemplaza 001
  CA4 para el staff: cada actor tiene una sesión activa **por canal** (panel web y app), no una
  sola.
- [ ] CA9 · Dado un administrador con sesión abierta en la app, cuando vuelve a iniciar sesión en
  la app, entonces la credencial anterior de la app deja de valer (una sola sesión de app activa
  por administrador).
- [ ] CA10 · Dado un administrador con sesión en la app y en el panel web, cuando cierra la sesión
  desde la app, entonces solo deja de valer la credencial de la app y la sesión web sigue abierta.
- [ ] CA11 · (abuso) Como atacante, intento adivinar contraseñas con más de 10 intentos por minuto
  desde la misma dirección → se rechazan los intentos que pasan del límite, igual que en el inicio
  de sesión web (001 CA8).
- [ ] CA12 · (abuso) Como atacante, provoco un error inesperado al iniciar sesión en la app → la
  respuesta es un error genérico, sin detalles internos.

## Fuera de alcance
- La consulta "quién soy" con la que el microservicio confirma que la credencial sigue valiendo y
  que el usuario sigue siendo administrador activo (spec aparte).
- Las consultas internas de citas para el microservicio y su llave de servicio (spec aparte).
- La app Android y el microservicio de asistencias (otro repositorio).
- El acceso de doctor y recepción a la app: usan el panel web (decisión del usuario, 2026-11-03).
- Sesiones de la app más largas que 24 horas o renovación automática de la credencial.
- Cerrar las sesiones al restablecer la contraseña: sigue pendiente en `docs/security.md` y aplica igual a la web y a la app.
- Segundo factor de autenticación.
- Permitir las llamadas desde el visor web de la app si no usa peticiones nativas (se decide al
  integrar la app).

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (contraseña y credencial de sesión, clasificación
  "restringida" en `docs/security.md`) y el correo del administrador. No toca datos de salud.
- Quién puede hacer qué:
  - administrador activo → obtiene una sesión de app;
  - doctor, asistente, administrador inactivo y paciente → rechazados, sin crear ni cerrar sesiones;
  - cualquier persona → puede intentarlo, con el límite de intentos.
- Casos de abuso:
  - Como doctor o asistente, intento obtener una sesión de app → se rechaza (CA3).
  - Como paciente, intento obtener una sesión de app → se rechaza (CA4).
  - Como atacante, intento adivinar contraseñas a gran volumen → se limita (CA11).
  - Como atacante, provoco un error interno para ver detalles del sistema → error genérico (CA12).
  - Como atacante, uso un intento rechazado para cerrar la sesión de otra persona → no se cierra
    ninguna sesión al rechazar (CA3, CA4).

## Auditoría
Esta spec no emite eventos de auditoría: el registro de auditoría aún no existe y se construye en
una spec aparte (objetivo 5 del roadmap, [observability.md](../../observability.md)). Cuando
exista, deberán quedar registrados con actor (o correo intentado), dirección, resultado y fecha:
- cada inicio de sesión de la app, exitoso o fallido (CA1, CA5, CA6);
- cada rechazo por no ser administrador (CA3, CA4).

Es una desviación de P14 que se justifica en el Constitution Check de `/plan`, como en las specs
014, 017, 018, 019 y 020.

## Requisitos no funcionales
- La credencial solo viaja por conexiones cifradas en producción y la respuesta que la contiene no
  se guarda en cachés intermedias.
- Los errores no revelan detalles internos (P7) y los logs no llevan la contraseña, la credencial
  ni el correo (P11).
- El inicio de sesión web conserva su comportamiento: su respuesta y su forma de entregar la
  credencial no cambian (001 CA1).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-03 | brecha | ¿Quién puede iniciar sesión en la app? | Solo administradores activos; doctor y recepción usan el panel web | usuario |
| 2026-11-03 | brecha | ¿Cuánto dura la sesión de la app? | 24 horas, como la web | usuario |
| 2026-11-03 | brecha | ¿Puede un administrador tener sesión en el panel web y en la app a la vez? | Sí: una sesión por canal (CA8) | usuario |
| 2026-11-03 | contradicción | 001 CA1 (BR-4) dice que la credencial nunca va en el cuerpo de la respuesta, y la app necesita leerla | Se mantiene para el inicio de sesión web; la app tiene su propio inicio de sesión que sí la entrega (CA1) | usuario |
| 2026-11-03 | contradicción | 001 CA4 dice que cada inicio de sesión cierra todas las sesiones del actor | Para el staff, una sesión por canal (CA8, CA9); los pacientes no cambian | usuario |
| 2026-11-03 | supuesto | ¿Qué responde un actor que no es administrador pero dio su contraseña correcta? | Acceso denegado, distinto de credenciales inválidas, para que la app pueda explicarlo; solo lo ve quien conoce la contraseña | agente |
| 2026-11-03 | cierre | El cambio se implementó y se integró (PR #40) antes de esta spec | Spec escrita a partir del código y los tests, para conciliar con `/sync` (camino 1: mantenerlo documentado) | usuario |
| 2026-11-03 | implícita | Formato de los errores | Error genérico en fallos inesperados | `docs/constitution.md` P7 |

## Notas para /plan
- Implementado en `142b773` (PR #40): `POST /api/v1/auth/mobile/login` en el grupo `auth` de
  `routes/api.php`, con el mismo `throttle:api`.
- `MobileLoginController` (Http) → `MobileLoginUseCase` (Aplication) →
  `LoginService::loginAdministratorOnMobile()` (Domain); reutiliza `LoginRequest` y `LoginDTO`.
  `MobileAccessDeniedException` → 403.
- Tokens de Sanctum con nombre por canal: `user-auth` (web) y `user-mobile` (app). El login web
  borra todos los tokens del usuario salvo `user-mobile`; el móvil, solo `user-mobile`.
- Respuesta `{"message","data":{token,actorType,actorId,name,email}}` con
  `Cache-Control: no-store`, sin cookie. 401 credenciales o cuenta inactiva, 403 no administrador,
  422 validación, 429 límite, 500 genérico.
- Tests: `tests/Modules/Auth/Integration/MobileLoginTest.php` y dos casos en
  `RateLimitAndErrorLeakTest.php`.
- CORS: si la app llama desde el WebView con `fetch` y no con `CapacitorHttp`, hay que añadir
  `https://localhost` a `config/cors.php`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-03 | Spec creada a partir del cambio ya integrado (PR #40) | `/sync` 2026-11-03, decisión del usuario |
| 2026-11-03 | Se queda en `draft` como registro del comportamiento; liberado en v0.7.0 sin plan, tareas ni review | Decisión del usuario: desplegar sin el flujo completo |
| 2026-11-03 | Desplegado en producción con v0.7.0 (verificación local 14/14, remota 30/30, vigilancia de 15 min sin 5xx) | Registro de la liberación en `docs/sync.md` |
