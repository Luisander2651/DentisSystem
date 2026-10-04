---
id: 015
slug: primer-despliegue-vps
status: implemented
created: 2026-09-29
extends: []
---

# 015 · Despliegue de producción en el VPS

## Problema
Dentissa **ya está desplegada a mano y en servicio** en un droplet con el dominio
`dentissapp.com`: https detrás del proxy de Cloudflare, certificado de Let's Encrypt en el origen,
cortafuegos con solo SSH, 80 y 443, y la composición de desarrollo adaptada a mano (commit
`4b3aecf`, runbook del usuario). Esta spec no crea un despliegue desde cero: convierte ese
despliegue manual en uno **repetible, verificable y reversible**. Hoy el mismo entorno sirve para
local y para producción (y ya no arranca en local), el código y las dependencias se instalan a mano
en el servidor, el panel de logs puede quedar expuesto por la IP directa (Docker publica puertos
por encima del cortafuegos), no hay cabeceras de seguridad, la aplicación ve la IP de Cloudflare en
lugar de la del visitante, los envíos de WhatsApp y del correo de restablecimiento registran el
teléfono, el nombre y el email del paciente, el enlace de ese correo apunta a `localhost`, no hay
backups ni un rollback probado, y las herramientas de build arrastran vulnerabilidades críticas
y altas cuya excepción (EX1) vence con este despliegue. Afecta al dueño del repositorio, que
opera el servidor, y a cualquier persona que visite el sitio. Sin resolverlo no se puede liberar
ninguna versión a producción (P12). Esta spec cubre la primera parte del objetivo 4 del roadmap:
las alertas y la retención de logs (OB7, OB8) quedan fuera, en la spec 016. Retira los datos
personales de todos los logs que cita OB2 (RS9), pero de las trazas solo las de whatsApp, así que
atiende OB10 **parcialmente** (de Auth solo el controlador del restablecimiento; el resto queda en Pendientes); el resto de riesgos citados, por
completo.

## Historias de usuario
- Como dueño del repositorio, quiero entornos local y de producción separados para desarrollar en mi equipo sin romper el servidor y viceversa.
- Como dueño del repositorio, quiero desplegar una versión etiquetada con un procedimiento repetible, con backup previo y un rollback ensayado, para poder volver atrás si algo falla.
- Como staff de la clínica, quiero que las confirmaciones por WhatsApp y los correos de restablecer contraseña se envíen en producción para que el sistema funcione igual que en local.
- Como visitante o usuario del sistema, quiero que el sitio use siempre conexión cifrada y no revele detalles internos para que mis datos viajen protegidos.
- Como dueño del repositorio, quiero que solo el sitio web sea accesible desde Internet y que la base de datos, la caché y el panel de logs no lo sean, para reducir la superficie de ataque.
- Como dueño del repositorio, quiero que la aplicación identifique la IP real de cada visitante aunque el tráfico llegue por Cloudflare, para que los límites de peticiones y los logs funcionen por persona.
- Como paciente, quiero que el envío de mis recordatorios y de mis correos no deje mi teléfono, mi nombre ni mi email en los logs del sistema, para que mis datos personales no se expongan.
- Como usuario que olvidó su contraseña, quiero que el enlace del correo me lleve al sitio real para poder restablecerla en producción.

## Criterios de aceptación
- [x] CA1 · Dado un equipo de desarrollo con el repositorio en la rama principal, cuando se levanta el entorno local con el procedimiento de `AGENTS.md`, entonces la aplicación responde en `http://localhost:8000` sin certificados ni dominio y el frontend se recarga en caliente al editar una vista.
- [x] CA2 · Dado un tag `vX.Y.Z`, cuando se construye la versión de producción, entonces esta incluye los assets del frontend ya compilados y solo las dependencias de ejecución (sin herramientas de desarrollo ni de test), y no necesita el servidor de desarrollo del frontend para mostrar ninguna pantalla.
- [x] CA3 · Dado producción en marcha con la configuración optimizada para producción, cuando se crea una cita para un paciente con teléfono o se pide restablecer una contraseña, entonces el mensaje de WhatsApp o el correo se envía en menos de 1 minuto usando las credenciales configuradas en el servidor; y si el proceso que atiende la cola se detiene, se reinicia solo en menos de 1 minuto.
- [x] CA4 · Dado cualquier visitante, cuando entra por `http://` a `dentissapp.com` o `www.dentissapp.com`, entonces se le redirige de forma permanente a `https://` y la respuesta indica al navegador que use solo conexión cifrada en visitas futuras.
- [x] CA5 · Dada cualquier página o respuesta de la API en producción, cuando el navegador la recibe, entonces incluye las protecciones contra ser embebida en otro sitio, contra la interpretación de tipos de contenido y una política de contenido que solo permite recursos del propio sitio y de los orígenes que la aplicación ya usa; y las pantallas de login, agenda, pacientes, expediente y sitio público funcionan sin recursos bloqueados.
- [x] CA6 · (abuso) Como sitio de otro origen, intento llamar a la API de Dentissa desde el navegador de un usuario con sesión iniciada → el navegador bloquea la respuesta; las llamadas desde el propio dominio siguen funcionando.
- [x] CA7 · (abuso) Como visitante, provoco un error inesperado en producción → recibo una página o respuesta genérica sin trazas, rutas de archivos, consultas ni valores de configuración.
- [x] CA8 · Dada la plantilla de configuración versionada del proyecto, cuando alguien la copia para crear un entorno nuevo, entonces por defecto tiene el modo de depuración apagado, el nivel de log `info`, las sesiones cifradas y la base de datos y la caché del stack real (PostgreSQL y el cliente de Redis instalado).
- [x] CA9 · (abuso) Como atacante en Internet, escaneo los puertos de la IP directa del droplet (sin pasar por el proxy) → solo responden el sitio web (80 y 443) y el acceso administrativo por SSH; la base de datos, la caché, el almacén de logs, el panel de logs y el servidor de desarrollo del frontend no responden. El panel de logs es accesible para el dueño solo a través de un túnel SSH.
- [x] CA10 · Dado el repositorio, cuando se revisan los archivos versionados de los entornos, entonces no contienen ninguna contraseña ni credencial: todas se toman de la configuración no versionada del servidor.
- [x] CA11 · Dado producción en marcha, cuando se consulta la comprobación de salud, entonces responde correcto solo si la aplicación, la base de datos y la caché responden; si la base de datos o la caché no responden, indica fallo en menos de 5 segundos.
- [x] CA12 · Dado producción en marcha, cuando pasa un día, entonces existe un backup nuevo de la base de datos en el droplet, se conservan los de los últimos 7 días y se borran los más antiguos; además, cada despliegue genera un backup antes de aplicar cambios en la base de datos. (abuso) Como visitante, intento descargar un backup desde el sitio web → no es accesible.
- [x] CA13 · Dado un despliegue de ensayo de una versión sobre la anterior, cuando se ejecuta el rollback documentado (volver a la versión anterior y restaurar su backup), entonces la aplicación vuelve a responder correcto con los datos del backup, y `docs/deployment.md` registra los pasos ejecutados y el tiempo medido, sin `TODO` en las secciones de despliegue y rollback.
- [x] CA14 · Dado el tag a liberar, cuando se ejecuta la auditoría de dependencias del proyecto, entonces no reporta vulnerabilidades críticas ni altas y la excepción EX1 queda cerrada.
- [x] CA15 · Dado un despliegue, un rollback o una restauración de backup en producción, cuando termina, entonces queda registrado en el servidor quién lo ejecutó, qué versión, cuándo y con qué resultado.
- [x] CA16 · Dado el tráfico que llega a través del proxy de Cloudflare, cuando un visitante hace peticiones, entonces el límite de peticiones por IP y los logs usan la IP real del visitante; dos visitantes distintos detrás del mismo nodo del proxy no comparten el límite.
- [x] CA17 · (abuso) Como cliente que se conecta sin pasar por el proxy, envío una cabecera que dice venir de otra IP → la aplicación no la acepta y sigue usando la IP de la conexión para el límite y los logs.
- [x] CA18 · Dado el envío de una confirmación de cita por WhatsApp o de un correo de restablecimiento de contraseña, de principio a fin (desde la creación de la cita o la solicitud, incluido el controlador que la recibe, hasta el proveedor, y también cuando el envío falla procesado en segundo plano), cuando se revisan los logs, entonces no contienen el teléfono, el nombre, el email del destinatario, las variables de la plantilla ni el mensaje de la excepción del proveedor; los logs de la aplicación no incluyen trazas, y la traza que registra el sistema al fallar un trabajo en segundo plano solo indica archivos y líneas, sin argumentos ni datos personales.
- [x] CA19 · Dado producción con su dominio configurado, cuando alguien pide restablecer su contraseña, entonces el enlace del correo apunta a `https://dentissapp.com/…` (el dominio configurado del sitio) y no a `localhost`.

## Fuera de alcance
- Alertas de tasa de errores 5xx y de logins fallidos, y retención del almacén de logs (OB7, OB8): spec 016.
- Cargar datos reales de pacientes: requiere cerrar antes la comprobación de propiedad del recurso, el cifrado en reposo de los datos de salud y las obligaciones de la LFPDPPP (restricciones de la constitución).
- Copia de los backups fuera del droplet y cifrado de volumen y backups (roadmap, Próxima etapa).
- Entorno de staging y despliegue automático desde CI.
- Cambiar de proveedor de certificados o de proxy: se mantienen Let's Encrypt en el origen y Cloudflare en modo Full (strict). Solo se ajusta la renovación del certificado de origen para que funcione con el sitio en marcha (ver Supuestos).
- Limitar el acceso web del origen a los rangos de Cloudflare (el cortafuegos ya limita a 22, 80 y 443).
- Retirar las trazas y mensajes de excepción del resto de logs de Auth (OB10.b, salvo el controlador del restablecimiento): Pendientes y deuda.
- Registro de auditoría de accesos de la aplicación (objetivo 5).
- Activar el workflow `.ai/ci/ai-dd.yml`.

## Seguridad y privacidad
- Datos sensibles involucrados: los backups contienen toda la base de datos (datos personales,
  de salud y hashes de credenciales, según `docs/security.md`), aunque en esta versión solo hay
  datos ficticios. Las credenciales de base de datos, caché, proveedores (Twilio, Brevo, R2) y
  panel de logs.
- Quién puede hacer qué: solo el dueño del repositorio despliega, revierte, restaura backups y
  accede al panel de logs, siempre por SSH. Nadie accede a estos desde el sitio web.
- Casos de abuso:
  - Como sitio de otro origen, intento usar la sesión del usuario contra la API → se bloquea (CA6).
  - Como visitante, provoco errores para obtener detalles internos → respuesta genérica (CA7).
  - Como atacante en Internet, intento conectar a la base de datos, la caché o el panel de logs → no responden (CA9).
  - Como visitante, intento descargar un backup o la configuración del servidor → no es accesible (CA12).
  - Como atacante en la red, intento interceptar el tráfico forzando `http://` → se redirige y el navegador recuerda usar solo `https://` (CA4).
  - Como sitio malicioso, intento embeber Dentissa en un marco para engañar al usuario → el navegador lo impide (CA5).
  - Como cliente sin pasar por el proxy, falsifico la cabecera de IP para esquivar el límite de peticiones → se ignora (CA17).
  - Como persona con acceso a los logs, busco teléfonos, nombres o emails de pacientes en los envíos de WhatsApp y de correo → no aparecen (CA18).

## Cobertura de riesgos
Cada corrección de cada riesgo citado (IDs de `docs/deployment.md`, `docs/observability.md` y
`docs/security.md`). OB7 y OB8 quedan **fuera** (spec 016); OB10 se atiende **parcialmente** (solo
whatsApp); el resto, dentro.

| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RD1.a Ensayar el rollback una vez | dentro | CA13 |
| RD1.b Backups automáticos de PostgreSQL | dentro | CA12 |
| RD2.a Imagen de producción | dentro | CA1, CA2 |
| RD3.a Worker de colas | dentro | CA3 |
| RD4.a Twilio y Brevo leídos con la configuración cacheada | dentro | CA3 |
| RD5.a `APP_DEBUG=false` y `SESSION_ENCRYPT=true` en la plantilla | dentro | CA8 |
| RD5.b `DB_CONNECTION=pgsql` y `REDIS_CLIENT=predis` en la plantilla | dentro | CA8 |
| RD6.a No publicar PostgreSQL ni Redis en el host | dentro | CA9 |
| RD6.b Credenciales de los entornos desde configuración no versionada | dentro | CA10 |
| OB5.a `LOG_LEVEL=info` por defecto | dentro | CA8 (traída del objetivo 5, decisión del usuario, 2026-09-29) |
| OB5.b `APP_DEBUG=false` por defecto | dentro | CA7, CA8 |
| OB6.a Grafana y Loki no publicados; acceso por túnel SSH | dentro | CA9 |
| OB7.a Alerta de tasa de errores 5xx | fuera | monitoreo separado del despliegue (decisión del usuario, 2026-09-29) → spec 016 |
| OB7.b Alerta de logins fallidos | fuera | monitoreo separado del despliegue (decisión del usuario, 2026-09-29) → spec 016 |
| OB8.a Retención de Loki | fuera | monitoreo separado del despliegue (decisión del usuario, 2026-09-29) → spec 016 |
| OB9.a `/up` comprueba PostgreSQL y Redis | dentro | CA11 |
| RS6.a Cabeceras CSP, HSTS, X-Frame-Options y X-Content-Type-Options | dentro | CA4, CA5 |
| RS6.b CORS restrictivo | dentro | CA6 |
| RS10.a `APP_DEBUG=false` en la plantilla | dentro | CA7, CA8 |
| RS10.b `SESSION_ENCRYPT=true` en la plantilla | dentro | CA8 |
| RS16.a Herramientas de build sin vulnerabilidades conocidas | dentro | CA14 (cierra EX1) |
| OB4.a `LOG_CHANNEL=stderr` en `.env.example` | dentro | CA8 |
| RS9.a Retirar teléfono, nombre y variables de plantilla de los logs | dentro | CA18 (todos los archivos citados en OB2) |
| RS9.b `LOG_LEVEL=info` por defecto en `.env.example` | dentro | CA8 |
| OB2.a Retirar teléfono y nombre de `CreateAppointmentController` | fuera | ya mitigada por la spec 014 → spec 014 |
| OB2.b Retirar datos personales del resto de logs citados | dentro | CA18 (whatsApp, Email y `RetriveDataForScheduledAppointmenEventUseCase`) |
| OB10.a Respuestas 500 sin el mensaje de la excepción | fuera | ya mitigada por la spec 014 → spec 014 |
| OB10.b Retirar trazas y mensajes de los logs de Auth y whatsApp | dentro, parcial | CA18: whatsApp completo y el controlador del restablecimiento de Auth; el resto de Auth → Pendientes y deuda |

## Auditoría
Esta spec no toca el registro de auditoría de la aplicación (objetivo 5). Sí exige que las
operaciones sobre producción dejen rastro:
- Despliegue, rollback y restauración de backup → registra quién, qué versión, cuándo y con qué resultado (CA15).

## Requisitos no funcionales
- La comprobación de salud responde en menos de 5 segundos, también cuando falla una dependencia (CA11).
- Un despliegue sin migraciones deja el sitio sin servicio menos de 1 minuto (sin usuarios reales hoy; supuesto).
- El rollback completo, incluida la restauración del backup, se ejecuta en menos de 15 minutos (medido en CA13).
- Pantallas existentes sin cambios visuales; no aplican criterios WCAG nuevos.

## Preguntas abiertas
Ninguna.

## Supuestos
- La spec se divide en 015 (despliegue) y 016 (alertas y retención de logs) (decisión del usuario, 2026-09-29). Aun así la 015 tiene 19 criterios; se mantiene junta porque todos son requisitos del primer despliegue (decisión del usuario, 2026-09-29).
- Solo datos ficticios en esta versión (decisión del usuario, 2026-09-29).
- La app ya corre en `dentissapp.com` sin usuarios, así que el despliegue no necesita ventana de mantenimiento (confirmado por el usuario, 2026-09-29).
- Backups solo en el droplet, 7 días (decisión del usuario, 2026-09-29).
- RS6.b, RS10.b, RD4.a y RD5.b entran en esta spec (decisión del usuario, 2026-09-29).
- Los cambios ya hechos en el droplet (commit `4b3aecf`: TLS con Let's Encrypt, worker de cola, puertos de base de datos, caché y Loki cerrados, credenciales desde la configuración) son el punto de partida; se verifican contra estos criterios, no se rehacen.
- El único acceso administrativo al servidor es SSH con el usuario `deploy`; `ufw` permite solo 22, 80 y 443 (confirmado por el usuario, 2026-09-29). Como Docker publica puertos por encima de `ufw`, CA9 se comprueba contra la IP directa.
- El sitio pasa por el proxy de Cloudflare en modo SSL Full (strict) (confirmado por el usuario, 2026-09-29).
- El certificado de origen se obtuvo con `certbot --standalone` (confirmado por el usuario, 2026-09-29). Como la renovación así falla con el sitio en marcha, la spec incluye dejarla funcionando sin parar el servicio (decisión del usuario, 2026-09-29).
- La IP real del visitante (CA16, CA17) entra en esta spec (decisión del usuario, 2026-09-29).
- Los datos personales salen de todos los logs que cita OB2, lo que cierra OB2.b y RS9.a (CA18), y el enlace del correo de restablecimiento usa el dominio del sitio (CA19) (decisión del usuario, 2026-09-29, `/analyze` ronda 2: A33, A40, A41).
- CA3 se da por cumplido con el correo de restablecimiento y la recuperación del worker verificados en producción (T052); la parte de WhatsApp queda verificada solo con tests automáticos (`WhatsAppFlowLogsTest`, Twilio simulado) porque ese flujo va a cambiar, y su prueba en vivo pasa a la spec que lo cambie (decisión del usuario, 2026-10-02).
- CA14 se comprobó en T045 (`npm audit` y `composer audit` sin avisos); `/release` lo repite sobre el tag a liberar.

## Notas para /plan
- Separar `docker-compose.yml` (local, como antes de `4b3aecf`: puertos 8000 y 5173, sin TLS) de un `docker-compose.prod.yml` de override con lo de `4b3aecf`. Ambos son rutas protegidas.
- Grafana en `127.0.0.1:3000:3000`.
- Revisar el servicio `queue`: monta `.` sin los volúmenes anónimos de `vendor/` y `node_modules/` del servicio `app`; posiblemente no encuentra `vendor/autoload.php`.
- Etapa de build en `docker/Dockerfile` con `composer install --no-dev --optimize-autoloader` y `npm run build`.
- `npm audit fix` para vite, rollup, postcss y demás; cerrar EX1 en `docs/security.md`.
- CSP: arrancar en modo report-only si las vistas usan scripts o estilos inline; revisar orígenes de R2 y de las fuentes.
- `/up`: usar el evento `DiagnosingHealth` de Laravel para comprobar PostgreSQL y Redis.
- Backups: `pg_dump -Fc` por cron del host o un servicio en compose, fuera de `public/` y de cualquier volumen servido por nginx.
- Registro de despliegues: un log en el servidor (p. ej. `/srv/dentissa/deploys.log`), escrito por el script de despliegue.
- Twilio y Brevo: pasar de `env()` a `config('services.*')` (P8).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-29 | Creación | Objetivo 4 del roadmap, primera parte |
| 2026-09-29 | Aprobada | Aprobación del usuario |
| 2026-09-29 | Editada: parte del despliegue manual existente (Cloudflare, ufw, certbot), título, CA9 contra la IP directa, CA16–CA18 nuevos, OB2, OB4, OB10 y RS9 en Cobertura, certbot recogido en Supuestos | `/analyze` ronda 1: A1, A16, A18, A25, A28 y A29, y revisión en vivo de `dentissapp.com` |
| 2026-09-29 | Reaprobada tras la edición | Aprobación del usuario |
| 2026-09-29 | Editada: CA18 ampliado a todos los logs de OB2 (WhatsApp y correo), CA19 nuevo (enlace de restablecimiento), OB2.b y RS9.a completos | `/analyze` ronda 2: A33, A40, A41 y A47 |
| 2026-09-29 | Reaprobada tras la edición de la ronda 2 | Aprobación del usuario |
| 2026-09-29 | Editada: CA18 incluye el controlador que recibe la solicitud de restablecimiento y los fallos procesados en segundo plano | `/analyze` ronda 3: A53 y A54 |
| 2026-09-29 | Reaprobada tras la edición de la ronda 3 | Aprobación del usuario |
| 2026-09-29 | Aclarada la redacción de CA18: trazas del sistema en segundo plano solo sin argumentos ni datos personales (decisión del usuario, 2026-09-29) | `/analyze` ronda 4: A61 |
