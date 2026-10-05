# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

### Added
- Documentación del diseño actual de la interfaz en `docs/design/`: colores, tipografía y componentes medidos en la app, capturas de todas las pantallas a 390 y 1440 px y la lista de deuda de diseño (`DS1`–`DS14`). No cambia la aplicación.

### Changed
- Flujo AI-DD actualizado a 1.11.2: validador y plantillas nuevas, herramientas de seguridad con su estado (se ejecutan con sus imágenes de Docker) y tabla "Decisiones" en la spec 013.

## [0.1.0] - 2026-10-04
Primera versión etiquetada y primera desplegada con el procedimiento reproducible (specs 014 y 015).

### Added
- Inicialización del flujo AI-DD: constitución, arquitectura, despliegue, seguridad, roadmap, ADRs y specs inferidas en `docs/`; validador en `.ai/bin/aidd.py`; workflow de CI `.ai/ci/ai-dd.yml`, guardado sin activar.
- Despliegue reproducible en el VPS (spec 015): imágenes de producción versionadas, una composición de producción aparte de la de desarrollo y scripts para desplegar, volver atrás, hacer y restaurar backups y verificar el servidor, con un registro de cada operación.
- Copia de seguridad diaria automática de la base de datos (spec 015), además de una antes de cada despliegue.
- Proceso de colas en producción (spec 015): los correos de restablecimiento de contraseña y los WhatsApp de confirmación vuelven a enviarse.
- La comprobación de salud `/up` responde con error en 1–2 segundos si la base de datos o la caché no responden (spec 015).

### Changed
- El certificado HTTPS se renueva sin detener el sitio, y Cloudflare valida el certificado del servidor (modo Full strict) (spec 015).
- Desarrollo local: la base de datos, la caché, Grafana y el servidor de vite solo escuchan en la propia máquina; `vendor/` y `node_modules/` viven en volúmenes de Docker (spec 015).
- Twilio y Brevo se configuran desde `config/services.php`, y el enlace del correo de restablecimiento usa la dirección pública del sitio (spec 015).

### Fixed
- El selector de doctores del formulario de cita respondía 500 a todos (rol `admin` inexistente).
- Volver a una versión anterior con restauración de la base deja la base exactamente como en el backup, y un backup dañado ya no la modifica (spec 015).
- Un despliegue aplica las migraciones antes de cambiar de versión: si fallan, la versión anterior sigue en servicio y la vuelta atrás apunta a la versión correcta (spec 015).
- Redesplegar la misma versión ya no borra las imágenes de la anterior, y antes de restaurar un backup se guarda una copia de la base actual (spec 015).
- Un despliegue que falla ya no puede quedar como versión a la que volver, y la versión en servicio no ve cambios hasta que el despliegue cambia de versión (spec 015).
- La comprobación de espacio en disco del servidor comparaba texto y daba por bueno un disco lleno (spec 015).

### Security
- Cabeceras de seguridad en todo el sitio (spec 015): política de contenido (CSP) con nonce, HSTS, protección contra ser embebido en otros sitios y contra la interpretación de tipos; sin versión de PHP ni de nginx en las respuestas.
- Solo el propio sitio puede leer la API desde un navegador (CORS restringido), y el límite de peticiones usa la IP real del visitante detrás de Cloudflare: una IP falsificada no lo esquiva (spec 015).
- Los logs de WhatsApp y del restablecimiento de contraseña ya no incluyen teléfono, nombre, email ni token, los de creación de citas ya no incluyen la fecha ni la hora, y las trazas no llevan argumentos (spec 015).
- Los archivos subidos y los estáticos también se sirven con las cabeceras que impiden interpretarlos como otro tipo o embeberlos en otro sitio, y la IP del visitante se resuelve en un solo punto (spec 015).
- Configuración segura por defecto en `.env.example` (sin modo debug, sesión cifrada, logs a `stderr` con nivel `info`) y credenciales de Docker fuera de los archivos de Compose (spec 015).
- Grafana, la base de datos y la caché ya no son accesibles desde Internet en la IP del servidor (spec 015).
- Dependencias de build sin vulnerabilidades conocidas (`npm audit fix`) y `laravel/framework` 12.69.3 (spec 015).
- Control de acceso por rol en pacientes, expedientes, agenda y citas (spec 014): solo staff activo; el doctor consulta en solo lectura, el asistente gestiona datos clínicos y el resto es del administrador. Los pacientes ya no acceden a estas rutas.
- Errores inesperados sin detalles internos: respuesta 500 genérica y log sin el mensaje de la excepción; 401 siempre en JSON en la API.
- Los mensajes de error de pacientes no repiten email, nombre, teléfono, código postal ni tipo de sangre, y la creación de citas deja de registrar el teléfono y el nombre del paciente.
- Todo error inesperado de la API deja un único log saneado, también si se reporta sin generar respuesta.
- axios actualizado a 1.20.0 (avisos altos de seguridad); las vulnerabilidades restantes de las herramientas de build quedan como excepción EX1 con vencimiento.

### Changed — flujo AI-DD actualizado a 1.8.1
- Validador 1.8.1 (`aidd.py review-pack`, `history`/`rotate`, lectura de Markdown en cp1252 y vocabulario en inglés).
- Plantilla de review con la sección "Aceptados sin tarea"; `language: es` en `.ai/project.yaml`.
- Rondas anteriores de `/analyze` y `/review` de la spec 014 movidas a `docs/specs/014-control-de-acceso-y-errores/history/`, con índice en `history/README.md`.

### Changed — flujo AI-DD actualizado a 1.6.0
- Validador 1.6.0 (`/analyze` delta con `aidd.py snapshot` y `changes`, cobertura de riesgos por corrección, aviso de specs grandes) y plantilla de spec con "Cobertura de riesgos"; `.ai/cache/` en `.gitignore`.
- Riesgos y brechas con IDs y estado por corrección: `RS1`–`RS13` en `security.md`, `OB1`–`OB11` en `observability.md` y `RD1`–`RD8` en `deployment.md` (contenido sin cambios; correcciones implícitas marcadas "derivada").
- Specs 013 y 014 con sección "Cobertura de riesgos"; objetivos 1, 4 y 5 del roadmap citan IDs.
- Sin copias `plan.vN.md`/`tasks.vN.md`: las versiones anteriores de la spec 014 quedan en git (`b8a71ce`, `ee1a146`).

### Changed — flujo AI-DD actualizado a 1.5.3
- Validador `.ai/bin/aidd.py` y plantillas de `docs/templates/` al día (secciones Auditoría y Observabilidad, `extends` en specs).
- Nuevo `docs/observability.md` (inferido) y bloque `observability` en `.ai/project.yaml`.
- Constitución 1.1.0: principio P14 (trazabilidad: correlación y auditoría).
- Roadmap: objetivos 4 y 5 ampliados con las brechas de observabilidad.
- `.ai/ci/ai-dd.yml` en modo baseline (sigue sin activar).

### Changed
- El flujo AI-DLC queda en pausa: sus reglas pasan de `CLAUDE.md` a `AIDLC.md`.
