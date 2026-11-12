---
spec: 025-portal-del-paciente
verdict: changes_requested
round: 1
date: 2026-11-11
base: a72087d
head: 999a059
human_signoff: pending
---

# Review · 025 Portal del paciente

## Resumen
El portal cumple los 20 criterios y el modelo de amenazas: separación de guards, propiedad de la
cita antes del estado, paciente desactivado con token vigente, `no-store`, sin datos clínicos y 500
genérico, todo con tests que lo prueban. Queda un fallo de Pint en un test de la spec (R1) y cuatro
importantes sobre tests que no prueban del todo lo que dicen y el foco tras "Reintentar" en Mis
citas (R2–R5), que el usuario decidió corregir antes de liberar. Los menores se aceptan como deuda.
Conteo: bloqueante 1 · importante 4 · menor 27

Revisores independientes con contexto limpio: spec (A), constitución y calidad (C+D), seguridad
(S) y accesibilidad y diseño; además, la `/code-review` que el usuario lanzó sobre la rama, cuyos
hallazgos se deduplicaron aquí. Sin `design:accessibility-review` ni `design:design-critique`
(`design` no está en `skills.enabled`): el revisor aplicó la lista de la skill.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2180 pasan, 3 fallan: `DeployScriptsBehaviourTest` ×2 (línea base, `git init` > 60 s) y `CurrentActorAuthorizationServiceTest` (conexión a la base de datos agotada; pasa dos veces en serie). Sin fallos nuevos |
| `npm run test:ui -- --only portal` y `--only detalle-portal` (pruebas nuevas, dos veces) | ✅ 18 + 12 visitas sin fallos, las dos veces |
| `vendor/bin/pint --test` sobre los archivos PHP de la spec | ❌ `tests/Modules/Auth/Integration/GuardSeparationTest.php` (`fully_qualified_strict_types`, `ordered_imports`) → R1 |
| `npm run build` | ✅ (compilado en T032; sin cambios de JS después) |
| `npm run test:ui` completo | ✅ al cerrar T032: 333 visitas, 3 tiempos de espera ajenos al portal que pasan solos |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores (aviso: `analysis.md` desactualizado por T029, añadida durante /implement) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PortalAppointmentsTest` (próxima cita), `GetPatientNextAppointmentUseCaseTest`, `PatientNextAppointmentPropertiesTest`, ui-audit `portal-inicio` | ⚠ | Cumple; la zona horaria de la clínica no tiene caso en la frontera de la noche (R3) y la tarjeta no comprueba el tratamiento y el doctor (R12) |
| CA2 | `PortalAppointmentsTest` (sin próxima cita → `null`), ui-audit `portal-inicio-vacio` | ✅ | |
| CA3 | `PortalAppointmentsTest` (orden), `GetPatientAppointmentsUseCaseTest`, ui-audit `portal-mis-citas` | ✅ | |
| CA4 | `PortalAppointmentsTest` (lista vacía), ui-audit `portal-mis-citas-vacio` | ✅ | |
| CA5 | `PortalPrescriptionsTest` (cinco campos), ui-audit `portal-detalle-completada` | ⚠ | Cumple; la entrada lee la receta por posición sin orden fijo y no comprueba instrucciones ni recomendaciones (R5) |
| CA6 | `PortalPrescriptionsTest`, ui-audit `portal-detalle-sin-recetas` | ✅ | |
| CA7 | `PortalAppointmentQrTest` (SVG = id), ui-audit `portal-detalle-abierta` (descarga del PNG) | ✅ | Escaneo con teléfono: verificación manual OK (usuario, T032) |
| CA8 | `PortalPrescriptionsTest` (cancelada → vacío), ui-audit `portal-detalle-completada`, `portal-detalle-cancelada` | ✅ | |
| CA9 | ui-audit `portal-inicio-fallo-qr`, `portal-inicio-fallo-cita`, `portal-mis-citas-fallo`, `portal-detalle-fallo`, `portal-detalle-abierta` | ⚠ | Cumple; en Mis citas el foco no vuelve a "Reintentar" si falla otra vez (R4) |
| CA10 | `RoleCopyTest`, `DashboardScreenTest`, ui-audit `portal-menu` | ✅ | |
| CA11 | `PortalAccessControlTest` (sin sesión → 401) | ✅ | |
| CA12 | `PortalAppointmentQrTest`, `PortalPrescriptionsTest` (ajena = inexistente) | ✅ | |
| CA13 | `PortalAppointmentsTest` (`patient_id` ajeno ignorado) | ✅ | |
| CA14 | `PortalAccessControlTest`, `GuardSeparationTest` | ✅ | |
| CA15 | `PortalAccessControlTest`, `PortalPagesTest`, `EnsureActivePatientTest` | ✅ | |
| CA16 | `PortalAccessControlTest`, `GuardSeparationTest`, `PortalPagesTest` | ✅ | |
| CA17 | `PortalNoClinicalDataTest`, `PortalAppointmentResourceTest`, `PortalPrescriptionsResourceTest` | ✅ | |
| CA18 | `PortalAppointmentQrTest` (completada y cancelada → 409) | ✅ | |
| CA19 | `PortalUnexpectedErrorTest` | ⚠ | Cumple; los dobles no exigen la llamada, así que el test pasaría sin inyectar el fallo (R2) |
| CA20 | `PortalAppointmentsTest` (200, 401, 403), `NoStoreResponseTest` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin justificar. Sin tarea que los cite: el DTO y la excepción que
  usan los casos de uso nuevos, los dobles de `AuthorizationServiceInterface` en cinco tests (método
  nuevo `currentPatientId()`) y `tests/Browser/baseline-1440.json` (generado por la auditoría).
  Añadidas durante /implement con aprobación del usuario: T028 y T029.
- Partes del plan sin implementar: ninguna. T001 y T051 solo tocan documentación.
- El plan pedía extender el test de arquitectura a los archivos nuevos de Application; solo se
  extendió el de Appointments (R21).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P13, P15 | ✅ | Revisor C+D sobre el código real; cada `test:` del Constitution Check existe y falla si se rompe, salvo lo que recogen R2 y R3 |
| P14 Trazabilidad y auditoría | ❌ aceptado | Aprobado por el usuario el 2026-11-10; puntos de emisión en `docs/observability.md` (T091) |
| Dependencias nuevas | ✅ | `composer.json` y `package.json` sin cambios; `deps.stat` vacío |
| Lint (Pint) | ❌ | R1 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`a72087d..HEAD`, 30 commits) | ✅ 0 hallazgos |
| semgrep `p/php` | ✅ 0 hallazgos (23 reglas, 579 archivos) |
| composer audit | crít: 0 · alta: 0 · 8 avisos de Symfony ignorados por EX2 (vence 2026-11-12; los quita el `/chore` de Symfony 7.4.23) |
| npm audit `--audit-level=high` | ✅ 0 vulnerabilidades |
| trivy | ➖ no aplica: el diff no toca `docker/` ni `docker-compose.yml` |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (autorización en servidor, guards, BOLA, paciente de la sesión) | ✅ | `routes/api.php` grupo portal; `config/auth.php`; `EnsureActivePatient`; `GuardSeparationTest`, `PortalAccessControlTest`, `PortalAppointmentQrTest`, `PortalPrescriptionsTest` |
| A01/A07:2025 Paciente desactivado con token vigente | ✅ | `EnsureActivePatient`, `currentPatientId()`; `PortalAccessControlTest`, `PortalPagesTest` |
| A01:2025 · API3:2023 Exposición excesiva | ✅ | Recursos con campos cerrados; `PortalNoClinicalDataTest` |
| A02:2025 Configuración (no-store, CSP, nosniff, noindex) | ✅ | `NoStoreResponse` antes de `AuthenticatesRequests`; `PortalAppointmentsTest`, `PortalPagesTest` |
| A05:2025 Inyección / XSS | ✅ | `textContent` en el JS del portal; `escapeHtml` en el historial; ids con `encodeURIComponent` |
| A06:2025 Diseño inseguro (QR de cita cerrada) | ✅ | 409; `PortalAppointmentQrTest` |
| A10:2025 Errores y fallo cerrado | ✅ | `UnexpectedErrorResponse`; `PortalUnexpectedErrorTest` (ver R2) |
| API4:2023 Consumo de recursos | ✅ | `throttle:api`; `PortalPerformanceTest` (mismas consultas con 5 y 100 citas) |
| A08:2025 CSRF · A03:2025 Cadena de suministro · SSRF | ➖ | Solo GET; sin dependencias nuevas; sin peticiones salientes |
| Proceso (rutas protegidas, instrucciones a agentes) | ✅ | El diff no toca `docker/`, `docker-compose.yml`, `phpunit.xml`, `AIDLC.md` ni `aidlc-docs/` |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no; P14 ❌ aceptado (2026-11-10), puntos de
  emisión documentados en `docs/observability.md`.
- Datos sensibles en logs del código tocado: ninguno; sin `Log::` nuevos, los 500 pasan por
  `UnexpectedErrorResponse` (sin el mensaje). Fuera del diff, `LogoutController` registra el mensaje
  de la excepción (R30).
- `request_id` presente en los logs nuevos: no hay logs nuevos (OB3.a pendiente, objetivo 5).

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Estados HTTP | ✅ | `GET /pagina-que-no-existe` → 404 con `noindex`; `GET /mis-citas` sin sesión → 302 a `/login` (vista previa local, 2026-11-11) |
| 3. Indexación | ✅ | `/dashboard` del paciente y `/mis-citas` con `noindex` (`PortalPagesTest`); ninguna ruta pública cambia |
- Sección "Descubribilidad" del plan aplicada: sí; las dos pantallas del portal en `docs/seo.md` como
  no indexables. `layouts/error` solo cambia el enlace de vuelta (sesión de paciente), sin metadatos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `tests/Modules/Auth/Integration/GuardSeparationTest.php:86` | Pint falla (`fully_qualified_strict_types`, `ordered_imports`): `Illuminate\Support\Str::uuid()` en línea sin `use`. Se coló porque `pint --dirty` solo mira lo no commiteado | Importar `Illuminate\Support\Str` |
| R2 | importante | `tests/Modules/Appointments/Integration/PortalUnexpectedErrorTest.php:28-62` | Los `shouldReceive(...)->andThrow(...)` no llevan `->once()`: si el caso de uso dejara de llamar a ese método, Mockery lanzaría `BadMethodCallException`, el controlador la convertiría en el mismo 500 y el test pasaría sin inyectar el fallo | `->once()` en cada doble |
| R3 | importante | `tests/Modules/Appointments/Integration/PortalAppointmentsTest.php:83-112` | El caso "in the clinic time zone" fija las 11:40 en Ciudad de México, el mismo día en UTC: pasaría igual si "hoy" se calculara en UTC | Caso a las 23:30 de la clínica (ya el día siguiente en UTC) con una cita abierta de ese día, que debe ser la próxima |
| R4 | importante | `resources/js/pages/portal/appointments.js:84-95` | Tras "Reintentar", si la carga falla otra vez, el foco se queda en el `h1` y no pasa al nuevo "Reintentar" (R7 del inicio, que el comentario cita) | Igual que `home.js`: tras la carga, si el foco sigue en el título o en el documento y hay error, enfocar el nuevo "Reintentar" |
| R5 | importante | `tests/Browser/screens.json` (`portal-detalle-completada`) | Lee la receta sembrada con `div:last-child`, pero `EloquentPrescriptionRepository::findAllByAppointmentTrackingId` no ordena: el orden depende de PostgreSQL y la entrada puede fallar a veces. Tampoco comprueba las instrucciones ni el texto de las recomendaciones (CA5) | Comprobar por texto (`textIn`), sin posición, la receta, su línea de duración y frecuencia, sus instrucciones y las recomendaciones |
| R6 | menor | `app/Modules/Appointments/Aplication/UseCases/GetPatientNextAppointmentUseCase.php:33` | Carga todo el historial y filtra en PHP para quedarse con una cita (medido: 47 ms en frío con 100 citas) | Consulta con estado abierto, fecha ≥ hoy, orden y `LIMIT 1` en el repositorio |
| R7 | menor | `resources/js/pages/portal/appointment-detail.js:153` | Cada apertura del detalle vuelve a pedir la lista completa | Pasar la cita ya cargada al abrir |
| R8 | menor | `routes/api.php:179` | `auth:sanctum,patient` en el grupo del portal prueba primero el guard del staff (una búsqueda de token más por petición) | Valorar `auth:patient,sanctum`, conservando el 403 del staff (CA16) |
| R9 | menor | `GetPatientAppointmentQrUseCase.php:43`, `GetPatientNextAppointmentUseCase.php:34` | La regla "cita abierta" (asignada o reprogramada) se repite, además de en `TodayAgenda`, `DashboardAppointmentResource` y el JS | `AppointmentStatus::isOpen()` |
| R10 | menor | `GetPatientAppointmentsUseCase.php:33`, `resources/js/ui/appointments-history.js:77` | El historial se ordena en PHP y otra vez en el navegador | Ordenar en el repositorio |
| R11 | menor | `resources/js/ui/appointments-history.js:3`, `resources/js/pages/portal/*.js` | `escapeHtml`, `toDisplay`, días, meses y formatos de fecha duplicados | Exportarlos una vez desde `resources/js/ui/` |
| R12 | menor | `tests/Browser/screens.json` (`portal-inicio`) | No comprueba el texto de tratamiento y doctor de la tarjeta (CA1) | `textIn` de `[data-next-detail]` |
| R13 | menor | `app/Core/Http/ShowDashboardPageController.php:31`, `EnsureActivePatient.php` | Bloques casi iguales para la cuenta inactiva del staff y del paciente | Un solo bloque o un helper común |
| R14 | menor | `resources/views/pages/dashboard.blade.php:262` | El saludo del paciente perdió el respaldo `?? 'Usuario'` | Recuperarlo |
| R15 | menor | `GetPortalAppointmentQrController.php:48`, `PortalAppointmentResource.php:24`, `ShowDashboardPageController.php:35`, `routes/api.php:181` | Comentarios en línea sin complejidad que los pida; el de `routes/api.php` es engañoso | Pasarlos a PHPDoc o quitarlos |
| R16 | menor | `resources/js/pages/portal/home.js:28` | Dos sentencias en una línea (`let ticket = 0;`) | Una por línea |
| R17 | menor | `GetPortalAppointmentQrController.php:44-48`, `GetPortalPrescriptionsController.php:36` | `catch (AppointmentAplicationExceptions)` responde el 409 a cualquier excepción de esa clase, y `catch (InvalidArgumentException)` el 400 a cualquier validación | Excepción propia del QR cerrado; validar el id antes del `try` |
| R18 | menor | `tests/Modules/Core/Integration/GuestRedirectTest.php:35` | El filtro exige `auth:sanctum,patient` exacto: una ruta futura con `auth:sanctum` a secas no entraría | `str_starts_with($middleware, 'auth:sanctum')` |
| R19 | menor | `tests/Modules/Core/Integration/PortalPagesTest.php:71-76` | "noindex fuera del personal" solo prueba `/agenda`, no el `/dashboard` del staff | Añadir `/dashboard` con los tres roles |
| R20 | menor | `tests/Support/ActingAsPatient.php:34-40` | `withPatientToken()` no la usa ningún test | Usarla en `GuardSeparationTest` o quitarla |
| R21 | menor | `tests/Modules/AppointmentTracking/Unit/AppointmentTrackingArchitectureTest.php` | `GetPatientPrescriptionsUseCase` no entra en ninguna comprobación de P3 | Caso equivalente al de Appointments |
| R22 | menor | tests unitarios del portal | Cinco clases anónimas de `AuthorizationServiceInterface` | Un fake en `tests/Support` |
| R23 | menor | `tests/Modules/Appointments/Integration/PortalPerformanceTest.php:21-24, 62-75` | Línea en blanco tras `{`; el caso de la próxima cita afirma el total de la lista | Mover esa aserción al caso de Mis citas |
| R24 | menor | `tests/Modules/AppointmentTracking/Integration/PortalPrescriptionsTest.php:93-100` | El dataset de cita abierta o cancelada omite `reprogramada` | Añadirla |
| R25 | menor | `resources/js/pages/portal/appointment-detail.js:144-189` | El detalle carga sin `aria-busy`: al llegar el contenido el lector no oye nada | `aria-busy` en el cuerpo mientras carga |
| R26 | menor | `appointment-detail.js:140, 167` y `appointment-detail-modal.blade.php:9, 23` | El error del detalle y de las recetas se ve dos veces: en línea y en la región de estado del diálogo | Uno solo a la vista |
| R27 | menor | `appointment-detail.js:116-141` | "Recetas" aparece con la lista vacía hasta que responde la API | Mostrar la sección al tener respuesta o un "Cargando…" propio |
| R28 | menor | `resources/views/pages/portal/appointments.blade.php:22` | `<section aria-label="Historial de citas">` crea un landmark que repite el nombre del `h2` | Un `<div>` |
| R29 | menor | `resources/js/ui/appointments-history.js:86`, `records/appointments-history-table.blade.php:11-23` | "Ver la cita del …" sin la hora (dos citas el mismo día empatan); `th` sin `scope="col"` y tabla y lista sin nombre | Hora en el texto oculto; `scope` y `aria-label` |
| R30 | menor | `app/Modules/Auth/Infrastructure/Http/Controllers/LogoutController.php:45-47` (fuera del diff) | El logout registra `getMessage()` y la traza; los pacientes ya llegaban antes | `/fix` aparte con `UnexpectedErrorResponse` |
| R31 | menor | `app/Providers/AppServiceProvider.php:236` (fuera del diff) | El limitador `api` usa el id sin el tipo de cuenta; con dos guards, dos cuentas con el mismo id compartirían cupo (impacto despreciable con UUID) | Clave con el tipo de cuenta |
| R32 | menor | `resources/js/ui/appointments-history.js:86` | `viewAttribute` va sin escapar en `innerHTML`; hoy solo recibe constantes | Validarlo o usar `setAttribute` |

## Preparación para release
- Rollback factible: sí, `docker/prod/rollback.sh` a la versión anterior; sin migraciones ni datos
  que revertir.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas (architecture, deployment): sí; `architecture.md` (T090), `observability.md`
  (T091), `deployment.md` sin cambios necesarios.
- SCA: sin críticas ni altas sin excepción; EX2 vence el 2026-11-12, así que el `/chore` de
  Symfony 7.4.23 debe ir antes de liberar.
- Cobertura de riesgos: RS1.b y RS1.c en curso (spec 025), con control y test cada una; ninguna
  figura como mitigada antes de liberar.

## Tareas añadidas
- T033 ← R1
- T034 ← R2
- T035 ← R3
- T036 ← R4
- T037 ← R5

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-11 como deuda:
- **R6** → roadmap: la próxima cita del portal filtra todo el historial en PHP en lugar de una consulta con `LIMIT 1`
- **R7** → roadmap: el detalle del portal vuelve a pedir la lista completa en cada apertura
- **R8** → roadmap: `auth:sanctum,patient` en el grupo del portal hace una búsqueda de token de más
- **R9** → roadmap: la regla "cita abierta" repetida en varios casos de uso, recursos y JS
- **R10** → roadmap: el historial se ordena en PHP y otra vez en el navegador
- **R11** → roadmap: utilidades de escape y de fechas duplicadas en el JS del portal
- **R12** → roadmap: `portal-inicio` no comprueba tratamiento y doctor de la tarjeta
- **R13** → roadmap: comprobación de cuenta inactiva repetida entre staff y paciente
- **R14** → roadmap: el saludo del paciente sin el respaldo "Usuario"
- **R15** → roadmap: comentarios en línea en controladores, recursos y rutas del portal
- **R16** → roadmap: dos sentencias en una línea en `portal/home.js`
- **R17** → roadmap: el 409 y el 400 de los controladores del portal capturan clases amplias
- **R18** → roadmap: `GuestRedirectTest` filtra por el middleware exacto
- **R19** → roadmap: `PortalPagesTest` no prueba el `noindex` fuera del `/dashboard` del staff
- **R20** → roadmap: `withPatientToken()` sin uso
- **R21** → roadmap: `GetPatientPrescriptionsUseCase` sin comprobación de arquitectura
- **R22** → roadmap: dobles de autorización duplicados en los tests unitarios
- **R23** → roadmap: aserciones de `PortalPerformanceTest` en el caso equivocado
- **R24** → roadmap: `PortalPrescriptionsTest` sin `reprogramada`
- **R25** → roadmap: el detalle del portal sin `aria-busy` mientras carga
- **R26** → roadmap: el error del detalle se ve dos veces
- **R27** → roadmap: "Recetas" vacía mientras carga
- **R28** → roadmap: landmark repetido en Mis citas
- **R29** → roadmap: "Ver" sin la hora y tabla del historial sin `scope` ni nombre
- **R30** → roadmap: `LogoutController` registra el mensaje de la excepción (fuera del diff, `/fix`)
- **R31** → roadmap: clave del limitador `api` sin el tipo de cuenta
- **R32** → roadmap: `viewAttribute` sin escapar en el historial compartido
