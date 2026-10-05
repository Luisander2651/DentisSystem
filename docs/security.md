---
status: approved
updated: 2026-10-04
---

# Seguridad de Dentissa

## Referencias
- OWASP Top 10: edición 2025 (la vigente al inicializar; actualizar al cambiar de edición).
- Nivel ASVS objetivo: 2 (datos de salud).
- Adicionales: OWASP API Security Top 10 (la app expone `/api/v1` y tendrá un cliente Android).
- Compliance: **LFPDPPP** (Ley Federal de Protección de Datos Personales en Posesión de los Particulares, México). Verificar el texto vigente (la ley se reformó en 2025) y su reglamento antes de cargar datos reales.

## Obligaciones derivadas de la LFPDPPP
Los datos de salud son **datos personales sensibles** según la LFPDPPP. Obligaciones que afectan al
sistema. Ninguna está implementada; todas son requisito **antes de cargar datos reales de pacientes**:

| Obligación | Qué implica en Dentissa | Estado |
|---|---|---|
| Aviso de privacidad | Publicarlo en el sitio y mostrarlo en el registro de pacientes y en el alta por el staff | pendiente |
| Consentimiento expreso y por escrito para datos sensibles | Registrar el consentimiento del paciente (fecha, versión del aviso) antes de guardar datos médicos o clínicos | pendiente |
| Derechos ARCO (acceso, rectificación, cancelación, oposición) | Procedimiento y medio para que el paciente los ejerza; exportar y suprimir sus datos | pendiente |
| Medidas de seguridad administrativas, técnicas y físicas | Cerrar los riesgos 1 y 2 (control de acceso y cifrado en reposo), además de backups cifrados y control de accesos al VPS | pendiente |
| Registro de accesos y vulneraciones | Auditar los accesos a expedientes y tener un procedimiento para notificar vulneraciones a los titulares | pendiente |
| Encargados (terceros que tratan datos) | Twilio (teléfono, nombre y cita), Brevo (email) y Cloudflare R2: revisar sus términos y citarlos en el aviso | pendiente |

Estas obligaciones son requisitos de producto: cada una se aborda con su propia spec vía `/specify`.
Esta tabla es una guía técnica, no asesoría legal: conviene validarla con un especialista.

## Clasificación de datos
| Dato | Clasificación | Dónde vive | Protección |
|---|---|---|---|
| Datos médicos (tipo de sangre, alergias, medicamentos, última visita) | restringida | `medical_data` | ninguna en reposo (sin `encrypted`) |
| Seguimiento clínico (motivo, síntomas, diagnóstico, procedimiento, observaciones) y recetas | restringida | `appointment_tracking`, `appointment_tracking_prescriptions` | ninguna en reposo |
| Contacto del paciente (teléfono, contacto de emergencia, email) y dirección | confidencial | `contact_info`, `addresses`, `patients` | ninguna en reposo; el teléfono aparece en logs de WhatsApp |
| Contraseñas de staff y pacientes | restringida | `users.password`, `patients.password` | bcrypt (12 rondas); `UserModel` las oculta, `PatientModel` no tiene `$hidden` |
| Tokens de sesión | restringida | `personal_access_tokens`, cookie `auth_token` | hash en BD; cookie httpOnly sin cifrar, `secure` solo con HTTPS |
| Tokens de reset | restringida | Redis, TTL 900 s | un solo uso, se borra al consumirse |
| Datos del staff (nombre, email, rol) | interna | `users`, `roles` | acceso solo administrador |
| Credenciales de Twilio, Brevo, R2, PostgreSQL | restringida | `.env` (ignorado por git) | fuera del repo; Twilio y Brevo leídas con `config()` desde la spec 015 (sin `env()` fuera de `config/`, `NoEnvOutsideConfigTest`) |
| Contenido público e imágenes | pública | tablas de contenido, Cloudflare R2 | el estado "oculto" no se respeta en la API pública |

## Autenticación y autorización
- Mecanismo de autenticación: tokens personales de Sanctum emitidos en `POST /api/v1/auth/login`, entregados solo en la cookie `auth_token` (httpOnly, `SameSite=lax`, excluida del cifrado de cookies) e inyectados como `Authorization: Bearer` por `InjectSanctumTokenFromCookie`. Un token activo por actor; caducidad 1440 min. Dos tipos de actor con tabla propia: staff (`users`) y pacientes (`patients`). Sin MFA.
- Modelo de autorización: roles de staff `Administrador`, `Asistente`, `Doctor` (ids fijos 1/2/3) y actor `patient`. `CurrentActorAuthorizationService::assertCan` aplica un mapa `permiso → roles` (spec 014): todo el staff activo lee pacientes, expedientes, historial y detalle de citas; administrador y asistente gestionan contacto, dirección y datos médicos; el resto (datos básicos y contraseña del paciente, altas y bajas, agenda, catálogo, contenido, usuarios y seguimiento clínico) es solo del administrador. Denegado por defecto; un staff inactivo no tiene permisos.
- Dónde se aplica: middleware `auth:sanctum` + `only.admin` (`app/Core/Middlewares/OnlyAdmin.php`) o `staff` (`app/Core/Middlewares/EnsureActiveStaff.php`: solo staff activo, opcionalmente por rol), y `assertCan` en los casos de uso. Las vistas de expedientes usan `staff:administrador,asistente,doctor`. No hay Policies ni Gates. Errores inesperados de la API: `UnexpectedErrorResponse` y la red de `withExceptions` en `bootstrap/app.php` (500 genérico, sin el mensaje en la respuesta ni en el log).

## Superficie de ataque
| Punto de entrada | Tipo | Autenticado | Notas |
|---|---|---|---|
| `GET /api/v1/public/*` | HTTP público | no | contenido oculto expuesto (spec 011) |
| `POST /api/v1/auth/{login,register,send-reset-password-email,reset-password}` | HTTP público | no | 10 req/min por IP |
| `POST /api/v1/auth/logout` | HTTP | sí | |
| `/api/v1/{users,treatments,certifications,gallery-images,promotions,testimonials}`, `POST/DELETE /patients`, completar cita y seguimiento | HTTP | sí, solo admin | `only.admin` + `assertCan` |
| `/api/v1/agenda/*`, `GET/PUT /patients/*`, subrecursos de paciente, expediente, `/appointments/*` | HTTP | sí, staff activo | `staff` + `assertCan` por rol (spec 014); pacientes → 403 |
| Web `/`, `/contacto`, `/galeria`, `/acerca-de-nosotros`, `/login`, `/register`, `/forgot-password`, `/reset-password` | HTTP público | no | vistas GET |
| Web `/dashboard`, `/agenda`, `/pacientes`, `/usuarios`, `/tratamientos`, `/contenido`, `/expedientes-clinicos` | HTTP | sí | rol por middleware (`only.admin` o `staff:…`) |
| `/up` | HTTP público | no | health check |
| Listeners en cola (WhatsApp, Email) | cola `database` | — | solo salida hacia Twilio y Brevo; sin webhooks entrantes |

## Manejo de secretos
- Dónde se guardan: `.env` por entorno (ignorado por git; `git log --all -- .env` vacío). En el VPS: `.env` con permisos 600.
- Twilio y Brevo se leen con `env()` en código (deben pasar a `config/services.php`, P8).
- `docker-compose.yml` y `docker-compose.prod.yml` toman todas las credenciales del `.env` (spec 015, CA10; `ComposeFilesTest`). `phpunit.xml` y `.github/workflows/tests.yml` conservan `admin`/`example`: son credenciales ficticias de la base de datos efímera de tests, fuera de CA10 (decisión D10 del plan 015); no reutilizarlas en ningún entorno.
- Rotación: TODO(init): sin procedimiento definido (decisión del usuario, 2026-09-22). Mínimo propuesto hasta definirlo: rotar las claves de Twilio, Brevo y R2 al pasar a producción y ante cualquier sospecha de exposición.

## Herramientas
Adoptadas en la inicialización. gitleaks, semgrep y trivy no están instalados en el equipo: se
ejecutan con sus imágenes de Docker desde la raíz del repositorio (decisión del usuario,
2026-10-04), y así corrieron en `/implement`, `/review` y `/release` de las specs 014 y 015. El
workflow de CI que las automatizaría está preparado en `.ai/ci/ai-dd.yml`, **inactivo**. Los
comandos exactos están en `.ai/project.yaml → security.tools`.

| Tipo | Herramienta | Comando | Estado | Cuándo corre |
|---|---|---|---|---|
| Secretos | gitleaks | `docker run --rm -v "$PWD:/repo" zricethezav/gitleaks:latest detect --no-banner --source /repo` | instalada (imagen de Docker) | /implement, /review |
| SAST | semgrep | `docker run --rm -v "$PWD:/src" -w /src semgrep/semgrep:latest semgrep scan --config p/php --metrics=off --error app config bootstrap routes` | instalada (imagen de Docker) | /implement (archivos tocados), /review |
| SCA (dependencias) | composer audit / npm audit | `docker compose exec -T app composer audit && docker compose exec -T app npm audit --audit-level=high` | instalada | /review, /release |
| Contenedores / IaC | trivy | `docker run --rm -v "$PWD:/repo" aquasec/trivy:latest config --severity HIGH,CRITICAL /repo/docker` | instalada (imagen de Docker) | /review, /release |
| DAST | — | — | no-aplica (no hay staging) | — |

Ninguna herramienta envía código ni métricas a un servicio externo: semgrep usa el conjunto de
reglas fijado `p/php`, que descarga de semgrep.dev, con `--metrics=off` (nunca `--config auto`,
que exige métricas). Las imágenes se descargan de Docker Hub.

## Permisos del agente
Reglas base en `shared/agent-security.md` del plugin. Específicas de este proyecto:
- Antigüedad mínima de paquetes nuevos: 7 días.
- Rutas protegidas adicionales: `AIDLC.md`, `.aidlc-rule-details/`, `aidlc-docs/`, `docker-compose.yml`, `docker/`, `phpunit.xml`.
- Servicios externos aprobados para enviar código o datos: ninguno.
- Dominios de red que el agente necesita: packagist.org, repo.packagist.org, registry.npmjs.org, github.com.
- El agente no lee `.env` ni ejecuta comandos contra Twilio, Brevo o R2 reales.

## Excepciones aceptadas
| ID | Hallazgo | Severidad | Motivo | Aprobado por | Vence |
|---|---|---|---|---|---|
| EX1 | RS16.a: vulnerabilidades de npm en herramientas de build y desarrollo (vite, rollup, postcss, nanoid, picomatch, concurrently, shell-quote): 2 críticas, 5 altas | crítica / alta | Prototipo sin producción; los paquetes no llegan al navegador (solo se usan al compilar o en `composer run dev`); axios, que sí llega, se actualizó a 1.20.0 (spec 014, T083). Condición: el servidor de desarrollo de vite (puerto 5173) no se expone fuera de la máquina de desarrollo; hoy `vite.config.js` escucha en `0.0.0.0` y `docker-compose.yml` publica `5173:5173` en todas las interfaces, así que solo se usa en redes de confianza | Dueño del repositorio (decisión del usuario, 2026-09-25) | Al empezar el objetivo 4 del roadmap (primer despliegue al VPS) y como tarde el 2026-12-31 | **Cerrada (spec 015, 2026-09-29)**: `npm audit fix` deja 0 vulnerabilidades (T003); RS16.a mitigada |

## Riesgos conocidos
Ordenados por severidad. Ninguno bloquea mientras el prototipo no tenga datos reales; **todos los
de severidad alta bloquean el primer despliegue a producción** (P12) salvo excepción registrada arriba.

Formato 1.5.6 (numeración añadida por `/init --upgrade` a 1.6.0, 2026-09-24; contenido sin
cambios). Estados: pendiente · en curso (spec NNN) · mitigada (vX.Y.Z) · aceptada (excepción EX<n>).
"(derivada)" marca una corrección que el riesgo no escribía de forma explícita y se deduce de su
descripción o del principio que la exige. Un riesgo solo está mitigado cuando todas sus
correcciones están mitigadas o aceptadas.

### RS1 · Alta — Control de acceso roto (A01)
Las rutas de pacientes, subrecursos, expediente y citas solo exigen `auth:sanctum`, y el guard `sanctum` no fija provider en `config/auth.php`, así que acepta tokens de pacientes. Un paciente autorregistrado puede leer y modificar datos de salud de otros pacientes y crear o modificar citas ajenas; cualquier staff puede cambiar la contraseña de un paciente. (Roadmap objetivo 1; specs 005, 006, 007.)

Correcciones:
- RS1.a Restringir las rutas de pacientes, subrecursos, expediente y citas a staff — estado: mitigada (v0.1.0; spec 014)
- RS1.b Comprobar la propiedad del recurso — estado: pendiente (spec 013, CA24)
- RS1.c Fijar el provider del guard `sanctum` — estado: pendiente (spec 013, CA23)
- RS1.d Añadir tests de acceso denegado — estado: mitigada (v0.1.0; spec 014): `PatientsAccessControlTest`, `AppointmentsAccessControlTest`, `RolePermissionsTest`

RS1 queda **parcialmente mitigado** hasta que la spec 013 cierre RS1.b y RS1.c.

### RS2 · Alta — Datos de salud sin cifrar en reposo

Correcciones:
- RS2.a Casts `encrypted` en los campos clínicos y de contacto — estado: pendiente (roadmap, Próxima etapa)
- RS2.b Cifrado de volumen y de backups — estado: pendiente (roadmap, Próxima etapa)

### RS16 · Alta — Dependencias de npm con vulnerabilidades conocidas (A03:2025)
`npm audit` (2026-09-25) reporta 2 críticas y 5 altas en herramientas de build y desarrollo (vite, rollup, postcss, nanoid, picomatch, concurrently, shell-quote). axios, que se incluye en el bundle del navegador, ya se actualizó a 1.20.0 (spec 014, T083).

Correcciones:
- RS16.a Actualizar las herramientas de build a versiones sin avisos — estado: mitigada (v0.1.0; spec 015): `npm audit fix` sin cambios mayores; EX1 cerrada

### RS3 · Media — Fuga de detalles internos
48 controladores devuelven `$e->getMessage()` en respuestas 500 (Patients, Appointments, AppointmentTracking, ContentManagement).

Correcciones:
- RS3.a Respuestas 500 genéricas, sin `$e->getMessage()` (derivada, P7) — estado: mitigada (v0.1.0; spec 014): `UnexpectedErrorTest` (Patients, Appointments, AppointmentTracking, ContentManagement), `GlobalErrorFallbackTest`

RS3 mitigado (spec 014).

### RS4 · Media — Contenido oculto expuesto en la API pública
(spec 011).

Correcciones:
- RS4.a No exponer contenido oculto en la API pública (derivada, spec 011) — estado: pendiente

### RS5 · Media — CSRF
El Bearer inyectado desde la cookie no pasa por la protección CSRF de Sanctum; depende de `SameSite=lax`.

Correcciones:
- RS5.a `SameSite=strict` o una cabecera o token anti-CSRF en peticiones que cambian estado — estado: pendiente

### RS6 · Media — Sin cabeceras de seguridad
(CSP, HSTS, X-Frame-Options, X-Content-Type-Options) ni `config/cors.php` publicado.

Correcciones:
- RS6.a Cabeceras de seguridad CSP, HSTS, X-Frame-Options y X-Content-Type-Options (derivada) — estado: mitigada (v0.1.0; spec 015): `SecurityHeaders` (CSP con nonce, `frame-ancestors 'none'`, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`) y HSTS en nginx; `SecurityHeadersTest`. Verificado en el dominio real con `verify.sh --remote dentissapp.com` (T051, 2026-10-01): HSTS, CSP, `X-Frame-Options: DENY`, `nosniff` y `Referrer-Policy` presentes, sin `X-Powered-By` ni versión de nginx; consola sin violaciones de CSP en las cinco pantallas (T052)
- RS6.b Publicar `config/cors.php` restrictivo (derivada) — estado: mitigada (v0.1.0; spec 015): solo `APP_URL`, sin credenciales; `CorsTest`

### RS7 · Media — Subida de imágenes sin límite de tamaño ni re-codificación
(spec 012).

Correcciones:
- RS7.a Límite de tamaño en la subida de imágenes (derivada, spec 012) — estado: pendiente
- RS7.b Re-codificar las imágenes subidas (derivada, spec 012) — estado: pendiente

### RS8 · Media — Los tokens de sesión no se revocan tras restablecer la contraseña
(spec 003).

Correcciones:
- RS8.a Revocar los tokens de sesión al restablecer la contraseña (derivada, spec 003) — estado: pendiente

### RS9 · Media — Datos personales en logs
el flujo de WhatsApp registra el teléfono y las variables de la plantilla; `.env.example` trae `LOG_LEVEL=debug`.

Correcciones:
- RS9.a Retirar teléfono, nombre y variables de plantilla de los logs (derivada; detalle en OB2) — estado: mitigada (v0.1.0; spec 015): los 7 archivos de OB2 más el controlador del restablecimiento; `WhatsAppFlowLogsTest`, `PasswordResetEmailTest`. La spec 014 ya había retirado los de `CreateAppointmentController` (OB2.a)
- RS9.b `LOG_LEVEL=info` por defecto en `.env.example` (derivada) — estado: mitigada (v0.1.0; spec 015): `EnvExampleDefaultsTest`

### RS10 · Baja — Configuración por defecto insegura en `.env.example`
`APP_DEBUG=true`, `SESSION_ENCRYPT=false`.

Correcciones:
- RS10.a `APP_DEBUG=false` en `.env.example` (derivada) — estado: mitigada (v0.1.0; spec 015): `EnvExampleDefaultsTest`, `WebUnexpectedErrorTest`
- RS10.b `SESSION_ENCRYPT=true` en `.env.example` (derivada) — estado: mitigada (v0.1.0; spec 015): `EnvExampleDefaultsTest`

### RS11 · Baja — Sin eventos de auditoría
logins fallidos, accesos denegados y lecturas de expedientes no se registran.

Correcciones:
- RS11.a Registrar eventos de auditoría de logins, accesos denegados y lecturas de expedientes (derivada; detalle en OB1) — estado: pendiente (roadmap objetivo 5)

### RS12 · Baja — `PatientModel` sin `$hidden`
para el hash de la contraseña; `$fillable` amplio en `PatientModel` y `UserModel`.

Correcciones:
- RS12.a `$hidden` para el hash de la contraseña en `PatientModel` (derivada) — estado: pendiente
- RS12.b Acotar `$fillable` en `PatientModel` y `UserModel` (derivada) — estado: pendiente

### RS13 · Baja — Acciones de GitHub fijadas por tag, no por SHA

Correcciones:
- RS13.a Fijar las acciones de GitHub a un SHA (derivada) — estado: pendiente

### RS14 · Baja — El staff clínico ve a todos los pacientes
Asistente y doctor leen el expediente de cualquier paciente, no solo de los que atienden (spec 014, "Fuera de alcance").

Correcciones:
- RS14.a Limitar la lectura del doctor a sus pacientes (derivada) — estado: pendiente (sin objetivo; decidir con el portal del paciente)

### RS15 · Baja — Logs de Auth y Users con el mensaje de la excepción
Los catch genéricos de Auth y Users registran `getMessage()` y la traza, y sus mensajes de negocio repiten el email (spec 014, "Fuera de alcance"; OB10.b).

Correcciones:
- RS15.a Pasar esos controladores a `UnexpectedErrorResponse` y quitar el email de sus mensajes (derivada) — estado: pendiente (roadmap, Pendientes y deuda)

Controles verificados: sin `DB::raw`/`whereRaw` con entrada del usuario; sin `{!! !!}` en Blade;
versiones de Composer fijadas (SECURITY-10); respuesta neutra en el reset (sin enumeración); rate
limiting en toda la API.

> Nota: `owasp-top10-protecciones.txt` (ignorado por git) declara controles para A01, A02, A05 y A06
> que el código no cumple por completo (ver riesgos 1, 5 y 10, y la ausencia de SCA hasta hoy). Este
> documento lo sustituye como fuente de verdad.
