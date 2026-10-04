# Specs de Dentissa

Cada spec vive en `docs/specs/NNN-<slug>/spec.md`. Las marcadas `inferred` las generó `/init` a
partir del código el 2026-09-22: documentan lo que el sistema **hace hoy**, no lo que debería hacer.
Pasan a `approved` solo con tu confirmación explícita (revísalas o usa `/clarify NNN`).

En las specs inferidas, `[x]` significa que un test existente cubre el criterio y `[ ]` que el
comportamiento se observó en el código sin test. Los criterios `(abuso)` marcados **HOY NO SE
CUMPLE** describen el comportamiento deseado y la brecha actual; son el punto de partida de specs
nuevas de corrección.

Estado actualizado: `python .ai/bin/aidd.py status`.

## Inventario

| # | Spec | Módulo | Estado | Confianza | Tests | Endurecida (AI-DLC) |
|---|---|---|---|---|---|---|
| 001 | [Inicio y cierre de sesión](001-inicio-y-cierre-de-sesion/spec.md) | Auth | approved | alta | sí | U3 |
| 002 | [Autorregistro de pacientes](002-registro-de-pacientes/spec.md) | Auth | approved | alta | sí | U3 |
| 003 | [Restablecer contraseña](003-restablecer-contrasena/spec.md) | Auth, Email | approved | alta | sí (Email no) | U3 |
| 004 | [Gestión de usuarios del staff](004-gestion-de-usuarios-staff/spec.md) | Users | approved | alta | sí | U4 |
| 005 | [Gestión de pacientes](005-gestion-de-pacientes/spec.md) | Patients | approved | alta | sí | U2 |
| 006 | [Expediente clínico](006-expediente-clinico/spec.md) | Patients | approved | alta | sí | U2 |
| 007 | [Agenda de citas](007-agenda-de-citas/spec.md) | Appointments | approved | alta | parcial | U1 |
| 008 | [Catálogo de tratamientos](008-catalogo-de-tratamientos/spec.md) | Appointments | approved | alta | sí | U1 |
| 009 | [Seguimiento clínico y recetas](009-seguimiento-clinico-y-recetas/spec.md) | AppointmentTracking | approved | media | parcial | — |
| 010 | [Confirmación de cita por WhatsApp](010-confirmacion-de-cita-por-whatsapp/spec.md) | whatsApp | approved | media | no | — (U7 pendiente) |
| 011 | [Sitio público](011-sitio-publico/spec.md) | ContentManagement | approved | alta | no | — (U5 en curso) |
| 012 | [Gestión del contenido público](012-gestion-de-contenido/spec.md) | ContentManagement | approved | media | no | — (U5 en curso) |
| 013 | [Código QR por cita](013-codigos-qr-por-cita/spec.md) | Appointments | approved | — | — | — |
| 014 | [Control de acceso y errores sin detalles internos](014-control-de-acceso-y-errores/spec.md) | Patients, Appointments, AppointmentTracking, ContentManagement | released (v0.1.0) | — | — | — |
| 015 | [Despliegue de producción en el VPS](015-primer-despliegue-vps/spec.md) | Infraestructura, whatsApp, Email, Appointments, Auth | released (v0.1.0) | — | — | — |

## Pendientes sin spec
- **Dashboard por rol** (`/dashboard`, accesos rápidos): pantalla de navegación, sin reglas de negocio propias.
- **Portal del paciente**: layout `patient.blade.php` sin pantallas funcionales.
- **Estadísticas**: `app/Modules/Estadisticas` vacío, sin alcance definido.
