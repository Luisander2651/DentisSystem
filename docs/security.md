---
status: approved
updated: 2026-09-23
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
| Credenciales de Twilio, Brevo, R2, PostgreSQL | restringida | `.env` (ignorado por git) | fuera del repo; Twilio y Brevo leídas con `env()` en código |
| Contenido público e imágenes | pública | tablas de contenido, Cloudflare R2 | el estado "oculto" no se respeta en la API pública |

## Autenticación y autorización
- Mecanismo de autenticación: tokens personales de Sanctum emitidos en `POST /api/v1/auth/login`, entregados solo en la cookie `auth_token` (httpOnly, `SameSite=lax`, excluida del cifrado de cookies) e inyectados como `Authorization: Bearer` por `InjectSanctumTokenFromCookie`. Un token activo por actor; caducidad 1440 min. Dos tipos de actor con tabla propia: staff (`users`) y pacientes (`patients`). Sin MFA.
- Modelo de autorización: roles de staff `Administrador`, `Asistente`, `Doctor` (ids fijos 1/2/3) y actor `patient`. `CurrentActorAuthorizationService::assertCan` concede todos sus permisos solo al administrador activo.
- Dónde se aplica: middleware `auth:sanctum` + `only.admin` (`app/Core/Middlewares/OnlyAdmin.php`), `assertCan` en los casos de uso, y comprobaciones inline de rol en closures de `routes/web.php`. No hay Policies ni Gates.

## Superficie de ataque
| Punto de entrada | Tipo | Autenticado | Notas |
|---|---|---|---|
| `GET /api/v1/public/*` | HTTP público | no | contenido oculto expuesto (spec 011) |
| `POST /api/v1/auth/{login,register,send-reset-password-email,reset-password}` | HTTP público | no | 10 req/min por IP |
| `POST /api/v1/auth/logout` | HTTP | sí | |
| `/api/v1/{users,treatments,certifications,gallery-images,promotions,testimonials}`, `POST/DELETE /patients`, completar cita y seguimiento | HTTP | sí, solo admin | `only.admin` + `assertCan` |
| `/api/v1/agenda/*`, `GET/PUT /patients/*`, subrecursos de paciente, expediente, `/appointments/*` | HTTP | sí, **cualquier actor** | **incluye pacientes** (ver Riesgos) |
| Web `/`, `/contacto`, `/galeria`, `/acerca-de-nosotros`, `/login`, `/register`, `/forgot-password`, `/reset-password` | HTTP público | no | vistas GET |
| Web `/dashboard`, `/agenda`, `/pacientes`, `/usuarios`, `/tratamientos`, `/contenido`, `/expedientes-clinicos` | HTTP | sí | rol por middleware o por closure |
| `/up` | HTTP público | no | health check |
| Listeners en cola (WhatsApp, Email) | cola `database` | — | solo salida hacia Twilio y Brevo; sin webhooks entrantes |

## Manejo de secretos
- Dónde se guardan: `.env` por entorno (ignorado por git; `git log --all -- .env` vacío). En el VPS: `.env` con permisos 600.
- Twilio y Brevo se leen con `env()` en código (deben pasar a `config/services.php`, P8).
- `docker-compose.yml` y `.github/workflows/tests.yml` contienen credenciales de PostgreSQL de desarrollo/CI en claro; no reutilizarlas en producción.
- Rotación: TODO(init): sin procedimiento definido (decisión del usuario, 2026-09-22). Mínimo propuesto hasta definirlo: rotar las claves de Twilio, Brevo y R2 al pasar a producción y ante cualquier sospecha de exposición.

## Herramientas
Adoptadas en la inicialización (ninguna estaba instalada). Hoy se ejecutan en local desde `/implement`, `/review` y `/release`; el workflow de CI que las automatiza está preparado en `.ai/ci/ai-dd.yml` pero **inactivo** hasta cerrar las brechas conocidas (riesgos 1–9):

| Tipo | Herramienta | Comando | Cuándo corre |
|---|---|---|---|
| Secretos | gitleaks | `gitleaks detect --no-banner` | /implement, CI |
| SAST | semgrep | `semgrep scan --config p/php --error` | /implement (archivos tocados), /review, CI |
| SCA (dependencias) | composer audit / npm audit | `composer audit && npm audit --audit-level=high` | /review, /release, CI |
| Contenedores / IaC | trivy | `trivy config --severity HIGH,CRITICAL docker/ docker-compose.yml` | /review, /release, CI |
| DAST | — | no aplica (no hay staging) | — |

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

## Riesgos conocidos
Ordenados por severidad. Ninguno bloquea mientras el prototipo no tenga datos reales; **todos los
de severidad alta bloquean el primer despliegue a producción** (P12) salvo excepción registrada arriba.

1. **Alta — Control de acceso roto (A01).** Las rutas de pacientes, subrecursos, expediente y citas solo exigen `auth:sanctum`, y el guard `sanctum` no fija provider en `config/auth.php`, así que acepta tokens de pacientes. Un paciente autorregistrado puede leer y modificar datos de salud de otros pacientes y crear o modificar citas ajenas; cualquier staff puede cambiar la contraseña de un paciente. Corrección: restringir a staff, comprobar la propiedad del recurso, fijar el provider y añadir tests de acceso denegado. (Roadmap objetivo 1; specs 005, 006, 007.)
2. **Alta — Datos de salud sin cifrar en reposo.** Corrección: casts `encrypted` en los campos clínicos y de contacto, más cifrado de volumen y backups.
3. **Media — Fuga de detalles internos.** 48 controladores devuelven `$e->getMessage()` en respuestas 500 (Patients, Appointments, AppointmentTracking, ContentManagement).
4. **Media — Contenido oculto expuesto en la API pública** (spec 011).
5. **Media — CSRF.** El Bearer inyectado desde la cookie no pasa por la protección CSRF de Sanctum; depende de `SameSite=lax`. Corrección: `SameSite=strict` o una cabecera o token anti-CSRF en peticiones que cambian estado.
6. **Media — Sin cabeceras de seguridad** (CSP, HSTS, X-Frame-Options, X-Content-Type-Options) ni `config/cors.php` publicado.
7. **Media — Subida de imágenes sin límite de tamaño ni re-codificación** (spec 012).
8. **Media — Los tokens de sesión no se revocan tras restablecer la contraseña** (spec 003).
9. **Media — Datos personales en logs:** el flujo de WhatsApp registra el teléfono y las variables de la plantilla; `.env.example` trae `LOG_LEVEL=debug`.
10. **Baja — Configuración por defecto insegura en `.env.example`:** `APP_DEBUG=true`, `SESSION_ENCRYPT=false`.
11. **Baja — Sin eventos de auditoría:** logins fallidos, accesos denegados y lecturas de expedientes no se registran.
12. **Baja — `PatientModel` sin `$hidden`** para el hash de la contraseña; `$fillable` amplio en `PatientModel` y `UserModel`.
13. **Baja — Acciones de GitHub fijadas por tag, no por SHA.**

Controles verificados: sin `DB::raw`/`whereRaw` con entrada del usuario; sin `{!! !!}` en Blade;
versiones de Composer fijadas (SECURITY-10); respuesta neutra en el reset (sin enumeración); rate
limiting en toda la API.

> Nota: `owasp-top10-protecciones.txt` (ignorado por git) declara controles para A01, A02, A05 y A06
> que el código no cumple por completo (ver riesgos 1, 5 y 10, y la ausencia de SCA hasta hoy). Este
> documento lo sustituye como fuente de verdad.
