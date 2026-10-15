# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

## [0.2.0] - 2026-10-14
Sistema de diseño aplicado a toda la interfaz, con accesibilidad WCAG 2.1 AA y páginas de error propias (spec 016).

### Added
- Sistema de diseño 2.0.0 aplicado a toda la interfaz (spec 016): paleta nueva con el logo de la clínica, un solo tamaño de control de 44 px, foco visible único, diálogos sobre un componente base y componentes `x-ui.*` para botones, campos, filtros, contadores e insignias. El texto ya no va en rosa y todos los contrastes cumplen 4.5:1.
- Páginas propias en español para acceso sin permiso (403), dirección inexistente (404), acción no disponible (405) y formulario caducado (419), con el camino de vuelta a tu inicio si tienes sesión; las rutas web responden un JSON fijo en español cuando se pide JSON (spec 016).
- `npm run test:ui` (spec 016): comprobaciones de interfaz en el navegador (desbordes, tamaños, contraste, foco, teclado, diálogos y composición) sobre las 75 pantallas y diálogos a 390, 768 y 1440 px. CI las ejecuta en cada push y deciden el resultado.
- Icono de pestaña e iconos de la marca en `public/images/brand/` (spec 016).
- Documentación del diseño actual de la interfaz en `docs/design/`: colores, tipografía y componentes medidos en la app, capturas de todas las pantallas a 390 y 1440 px y la lista de deuda de diseño (`DS1`–`DS14`). No cambia la aplicación.
- CI: el workflow `ai-dd` queda activo en `.github/workflows/`. En cada PR valida las specs, el formato (Pint) de los archivos PHP cambiados y la seguridad de lo que cambia el PR (gitleaks, semgrep, auditoría de dependencias y trivy); cada lunes revisa el repositorio completo sin bloquear. Acciones e imágenes fijadas a una versión y semgrep sin envío de métricas.

### Changed
- Agenda (spec 016): los días del calendario son botones que se usan con teclado y muestran su número de citas; en el móvil, las citas del día elegido aparecen bajo el calendario y se anuncian al tocar el día. Los expedientes muestran pares de etiqueta y valor en el móvil en lugar de tablas.
- El menú lateral del panel y el menú del sitio público se despliegan desde 1024 px; por debajo se usa el menú plegable, que se cierra con Escape y devuelve el foco a su botón. Los controles táctiles miden al menos 44 px también a 768 px (spec 016).
- Al enviar un formulario o una acción (como eliminar), se anuncia "Enviando…" a los lectores de pantalla; los enlaces que abren otra pestaña lo avisan; y en restablecer contraseña, el error de contraseñas que no coinciden queda en su campo (spec 016).
- Los errores de un formulario quedan marcados en su campo y se anuncian sin mover el foco, también los que devuelve el servidor; un campo vacío obligatorio se marca al enviar, y un error de guardado sigue a la vista mientras el diálogo esté abierto (spec 016).
- Teléfono, dirección, código postal, tipo de sangre y los nombres de tratamientos, promociones y certificaciones admiten números y signos; editar un paciente deja el diálogo abierto con su error si no se pudo guardar; los diálogos ya no se cierran al soltar el ratón fuera de ellos (spec 016).
- Textos del panel con acentos y sin términos técnicos; las fechas se muestran en español sea cual sea el idioma del servidor (spec 016).
- En los diálogos de gestión de contenido y de citas, un guardado que falla deja el diálogo abierto con su mensaje (spec 016).
- Flujo AI-DD actualizado a 1.11.2: validador y plantillas nuevas, herramientas de seguridad con su estado (se ejecutan con sus imágenes de Docker) y tabla "Decisiones" en la spec 013.

### Removed
- Componente `x-ui.table` y vista `welcome.blade.php`, que nadie usaba (spec 016).

### Security
- Herramientas de desarrollo actualizadas a versiones sin vulnerabilidades altas ni críticas (`shell-quote` y `source-map-js`; RS16.a, spec 016).

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
