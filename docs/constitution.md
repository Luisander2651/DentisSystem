---
version: 1.2.0
ratified: 2026-09-23
last_amended: 2026-09-24
status: approved
---

# Constitución de Dentissa

Principios innegociables. Toda spec, plan y tarea debe cumplirlos. `/plan` y `/tasks` incluyen un
**Constitution Check** que evalúa cada principio; un incumplimiento sin justificación bloquea.

El código existente anterior a esta constitución que no cumple un principio **no bloquea** por sí
mismo: está registrado como deuda en [roadmap.md](roadmap.md) y en las "Observaciones" de cada spec.
Los principios se exigen a todo código **nuevo o modificado**.

## Principios

### P1. Spec antes que código
**Regla:** Ninguna implementación de comportamiento sin una spec `approved` en `docs/specs/`.
**Cómo se verifica:** `python .ai/bin/aidd.py status`; el hook del plugin avisa al editar código sin spec activa; revisión en `/review`.
**Por qué:** El proyecto acumuló contradicciones entre README, documentos y código; la spec es la única fuente de verdad del comportamiento.

### P2. Test que falla antes y pasa después
**Regla:** Todo cambio de comportamiento lleva un test Pest en `tests/Modules/<Módulo>/` que falla sin el cambio y pasa con él. Los value objects nuevos o modificados llevan tests de propiedades (Eris, `UsesEris`). Lo que solo existe en un navegador (medidas de pantalla, contraste pintado, foco, diálogos, nombres accesibles) se prueba con `npm run test:ui` (`tests/Browser/`), que también debe fallar sin el cambio y pasar con él.
**Cómo se verifica:** `./vendor/bin/pest --parallel` en CI; `npm run test:ui` en CI; `/review` comprueba la trazabilidad criterio → test.
**Por qué:** La suite (≈437 casos, con property-based testing) es lo que permitió endurecer las Unidades 1–4 sin regresiones.

### P3. Capas del módulo
**Regla:** Flujo Controller (invocable) → UseCase → Domain Service → `*RepositoryInterface`. El código de `Domain/` no importa modelos Eloquent, facades ni clases de `Infrastructure/`. Una dependencia nueva entre módulos requiere un ADR.
**Cómo se verifica:** `/review` y búsqueda de `use App\\Modules\\*\\Infrastructure` dentro de `Domain/`.
**Por qué:** Es la arquitectura declarada (hexagonal por módulo) y hoy hay fugas conocidas (p. ej. `TreatmentsService`, `LoginService`).

### P4. Contrato de API primero
**Regla:** Todo endpoint nuevo o modificado de `/api/v1` define en la spec o en el plan su request, sus respuestas y sus códigos HTTP antes de implementarse. Un cambio incompatible se publica como `/api/v2`.
**Cómo se verifica:** Sección de contrato en `plan.md`; tests de integración que fijan cada código HTTP.
**Por qué:** El frontend JS y la futura app Android consumen la API sin tipos compartidos; el contrato escrito es el único acuerdo entre capas.

### P5. Autorización en el servidor, por rol y por recurso
**Regla:** Todo endpoint nuevo, o existente cuya autorización cambia (middleware, permisos o actores admitidos), verifica en el servidor el tipo de actor (staff/paciente), el rol y la propiedad del recurso (middleware + `assertCan` + comprobación de pertenencia), y tiene al menos un test de acceso denegado por cada actor no autorizado. Cambiar solo el manejo de errores de un endpoint no obliga a añadirle esos tests.
**Cómo se verifica:** Tests `(abuso)` en la spec y en `tests/Modules/*/Integration`; `/review` con `shared/security-checklist.md`.
**Por qué:** La API maneja datos de salud y hoy varios endpoints solo exigen `auth:sanctum` (riesgo A01 documentado en [security.md](security.md)).

### P6. Validación de entrada con FormRequest
**Regla:** Todo endpoint nuevo, o existente cuya entrada cambia (parámetros, cuerpo o reglas de validación), valida su entrada con un `FormRequest` (reglas en array, mensajes propios), además de las invariantes de los value objects. Entrada inválida → 422. Cambiar solo la autorización o el manejo de errores de un endpoint no obliga a añadirle un `FormRequest`.
**Cómo se verifica:** Test de integración con payload inválido por endpoint; `/review`.
**Por qué:** Patrón ya adoptado en Auth y Users (SECURITY-05); evita que entradas malformadas degraden a 500.

### P7. Errores sin detalles internos
**Regla:** Ninguna respuesta a un error inesperado (500) incluye `$e->getMessage()`, trazas ni SQL: devuelve `{"error": "Internal server error"}` y el detalle va a `Log::error` con contexto. Los errores de negocio esperados (400, 404, 409, 422) pueden devolver su mensaje, siempre que no contenga datos personales ni de salud (P11).
**Cómo se verifica:** Test que fuerza la excepción y afirma el cuerpo genérico; `grep -rn "getMessage()" app/Modules/*/Infrastructure/Http*` en código tocado.
**Por qué:** Corregido en Auth y Users (SECURITY-15), pendiente en 48 controladores.

### P8. Secretos fuera del repositorio
**Regla:** Ningún secreto se versiona. `env()` solo se usa dentro de `config/`; el código lee `config('services.*')`. Toda variable nueva se añade por nombre a `.env.example`.
**Cómo se verifica:** `gitleaks detect --no-banner` en CI; `grep -rn "env(" app/` sin resultados en código tocado.
**Por qué:** Twilio y Brevo leen `env()` directamente y se rompen con `config:cache` en producción.

### P9. Migraciones reversibles y compatibles
**Regla:** Toda migración tiene `down()` funcional y es compatible con la versión anterior del código (expand → migrate → contract). Las migraciones viven en `app/Modules/<Módulo>/Infrastructure/Persistence/Eloquent/Migrations/`.
**Cómo se verifica:** `php artisan migrate` + `migrate:rollback --step=1` en local sobre la base de desarrollo; `/review`.
**Por qué:** El rollback propuesto en [deployment.md](deployment.md) depende de poder revertir el esquema.

### P10. Dependencias nuevas con ADR
**Regla:** No se añade ningún paquete Composer o npm sin un ADR en `docs/adr/` que justifique la necesidad, las alternativas y la versión fijada (sin `*`), con una antigüedad mínima de 7 días.
**Cómo se verifica:** Diff de `composer.json`/`package.json` contra ADRs en `/review`; hook del plugin.
**Por qué:** Superficie de cadena de suministro y coherencia con SECURITY-10 (versiones fijadas).

### P11. Datos sensibles protegidos y modelo de amenazas
**Regla:** Datos de salud (datos médicos, seguimiento clínico, recetas) y datos de contacto (teléfono, email, dirección) nunca se escriben en logs ni en mensajes de error. Toda feature que toque datos sensibles o autenticación incluye un modelo de amenazas en su `plan.md`.
**Cómo se verifica:** `aidd.py validate` (sección "Modelo de amenazas" en el plan); `/review` revisa las llamadas a `Log::` en código tocado.
**Por qué:** ASVS nivel 2 por manejar datos de salud; hoy el flujo de WhatsApp registra el teléfono y el nombre del paciente.

### P12. Producción con aprobación humana, rollback y sin vulnerabilidades altas
**Regla:** Ningún deploy a producción sin aprobación explícita del dueño del repositorio y sin un rollback documentado en [deployment.md](deployment.md). Ninguna vulnerabilidad crítica o alta llega a producción sin una excepción aprobada y con fecha de vencimiento en [security.md](security.md).
**Cómo se verifica:** `/release` exige la confirmación y bloquea con hallazgos de `security.tools`.
**Por qué:** Aún no existe entorno de producción; se fija la regla antes del primer despliegue al VPS.

### P13. Lógica de negocio en el backend
**Regla:** Las reglas de negocio (validaciones, permisos, cálculos de disponibilidad, estados) viven en el backend. El JS de página (`resources/js/pages/*`) y las vistas Blade solo presentan datos y llaman a `/api/v1`; ocultar un botón nunca sustituye a una comprobación en el servidor.
**Cómo se verifica:** `/review` del JS y Blade tocados.
**Por qué:** Hoy la interfaz muestra acciones que el backend rechaza (asistente y paciente en el sidebar), y la futura app Android debe obtener las mismas reglas.

### P14. Trazabilidad: correlación y auditoría
**Regla:** Cada petición lleva un identificador (`X-Request-Id`, aceptado del cliente si es válido o generado) que se devuelve en la respuesta y se añade al contexto de todos sus logs y de los trabajos en cola que dispare. Los logs nuevos usan contexto estructurado (`Log::info('evento', [...])`). Toda feature que toca datos sensibles, autenticación o permisos emite en el registro de auditoría (solo anexado) los eventos que define su spec, con actor, acción, recurso, resultado y fecha. Nivel de log por defecto en producción: `info`.
**Cómo se verifica:** `aidd.py validate` (secciones "Auditoría" en la spec y "Observabilidad" en el plan); un test por evento de auditoría; `/review`.
**Por qué:** La LFPDPPP trata los datos de salud como sensibles y hoy no queda rastro de quién accede a un expediente (riesgo 11 de [security.md](security.md), roadmap objetivo 5). Detalle en [observability.md](observability.md).

### P15. La interfaz usa solo el sistema de diseño
**Regla:** Toda vista y todo JS de página nuevo o modificado toma colores, radios y tamaños de control de los tokens de `resources/css/app.css` y usa los componentes de `docs/design/system.md`. Ningún color literal ni valor arbitrario de color fuera del archivo de tokens. Un valor que falte se añade primero al sistema.
**Cómo se verifica:** `OnlyDesignTokensTest` y `DesignTokensTest` en la suite Pest; `npm run test:ui` en CI.
**Por qué:** La interfaz llegó a tener 8 rosas en unas 620 apariciones y contrastes insuficientes (deuda `DS1`–`DS3`).

## Restricciones
- Stack fijo: PHP 8.4, Laravel 12, PostgreSQL 16, Pest 3, Tailwind 4; cambiarlo requiere ADR.
- Prototipo sin datos reales de pacientes. **Antes de cargar datos reales**: cerrar el control de acceso (roadmap objetivo 1), cumplir las obligaciones de la LFPDPPP para datos personales sensibles (aviso de privacidad, consentimiento expreso, derechos ARCO; ver [security.md](security.md)) y cifrar en reposo los datos de salud.
- Accesibilidad: las pantallas nuevas o modificadas cumplen WCAG 2.1 AA.
- Se despliega en un VPS con Docker Compose (planificado); no se asume ningún servicio gestionado.
- Idioma: clases, métodos y variables en inglés; valores de dominio, rutas web y textos de UI en español. Dentro de un módulo existente se respeta su grafía (`Aplication`/`Application`, `Http`/`HTTP`); los módulos nuevos usan `Application` y `Http`.

## Definición de terminado
- [ ] `./vendor/bin/pest --parallel` pasa (en Docker local y en CI).
- [ ] `vendor/bin/pint --dirty` no deja cambios pendientes en los archivos tocados.
- [ ] Spec, `CHANGELOG.md` y `docs/architecture.md` (si cambió la estructura) actualizados.
- [ ] `python .ai/bin/aidd.py validate` sin errores.
- [ ] Revisión humana: el dueño del repositorio aprueba `/review` y el merge.

## Gobierno
- Cambiar esta constitución requiere subir `version` (SemVer: MAJOR elimina o redefine un
  principio, MINOR añade uno, PATCH aclara redacción) y registrar la enmienda abajo.
- Ante conflicto, la constitución prevalece sobre specs, planes y `AGENTS.md`.
- Mientras el flujo AI-DLC esté en pausa ([AIDLC.md](../AIDLC.md)), sus reglas de negocio aprobadas
  (BR-x en `aidlc-docs/`) son antecedentes; si se retoma, esta constitución sigue aplicando al
  código que produzca.

## Enmiendas
| Versión | Fecha | Cambio | Motivo |
|---|---|---|---|
| 1.0.0 | 2026-09-22 | Versión inicial | /init |
| 1.0.0 | 2026-09-23 | Ratificada por el usuario, sin cambios de contenido | Aprobación explícita |
| 1.2.0 | 2026-10-08 | Añade P15 (la interfaz usa solo el sistema de diseño) y aclara P2: lo que solo existe en un navegador se prueba con `npm run test:ui` | Spec 016 (CA24); textos aprobados por el usuario el 2026-10-06 |
| 1.1.0 | 2026-09-24 | Añade P14 (trazabilidad: correlación y auditoría) | Propuesta de `/init --upgrade` a 1.5.3, aprobada por el usuario |
| 1.1.2 | 2026-09-24 | Aclara el alcance de P5 (aplica cuando cambia la autorización del endpoint), con el mismo criterio que P6 | `/analyze 014` (B2); aprobado por el usuario |
| 1.1.1 | 2026-09-24 | Aclara el alcance de P6 (solo cuando cambia la entrada del endpoint) y de P7 (aplica a errores inesperados; los mensajes de negocio no llevan datos personales ni de salud) | `/analyze 014` (A2, A13); aprobado por el usuario |
