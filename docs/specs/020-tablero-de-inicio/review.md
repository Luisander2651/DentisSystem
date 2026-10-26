---
spec: 020-tablero-de-inicio
verdict: changes_requested
round: 1
date: 2026-10-26
base: ae818f4
head: 7462312
human_signoff: pending
---

# Review · 020 Tablero de inicio

## Resumen
Sin bloqueantes: los 16 criterios tienen test que pasa y los controles TM1–TM9 están en el código.
Hay 14 importantes que el usuario decide corregir antes de liberar:
- un doble "Completar" simultáneo responde 500;
- el registro de seguimiento no limita longitudes y da 500 con un medicamento largo;
- hay huecos de accesibilidad en el inicio (foco al reintentar, nombres repetidos, cifra "0" mientras carga, buscador sin anuncio);
- gitleaks marca un UUID de test que haría fallar el CI del PR;
- cinco tests no prueban del todo lo que dicen.

Los menores van al roadmap.
Conteo: bloqueante 0 · importante 14 · menor 20

Revisores: spec (A) y seguridad (S) con `opus`; constitución y calidad del backend, y accesibilidad
con constitución y calidad del frontend, con `sonnet`. El diff (367 KB) se repartió por zonas. La
skill `design` no está habilitada: la accesibilidad se revisó con la lista de `/review`. El
recorrido con NVDA no se hizo, por decisión del usuario (nota de T046).

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie, 2026-10-26, mismo código que `head`) | ✅ 1 890 pasan, 0 fallos (línea base: el timeout de PostgreSQL de 017/R12, que no apareció) |
| `pint --test` sobre los 76 archivos PHP del diff | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` completo, dos veces (09:15 y 12:11) | ✅ en las entradas del inicio; los 18 fallos de composición a 1440 px quedaron admitidos en T051 y el acotado posterior pasa (1 espera intermitente, 017/R12) |
| `evaluate.mjs` con `criterios.json` y `criterios-doctor.json` | ✅ puertas 12/12 · KPIs 5/5 en los dos |
| `aidd.py validate docs/specs/020-tablero-de-inicio` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `TodaySummaryTest`, `DashboardScreenTest`, ui-audit `dashboard-*` | ✅ | |
| CA2 | `TodayAgendaTest`, `TodayAgendaPropertiesTest`, `TodaySummaryTest` | ⚠ | La propiedad "after" copia la fórmula de la implementación (R6) |
| CA3 | `TodayAgendaTest`, `TodaySummaryTest` | ✅ | |
| CA4 | `UnclosedAppointmentsTest`, `AppointmentPageTest` | ✅ | Orden exacto comprobado |
| CA5 | `UnclosedAppointmentsTest`, ui-audit `dashboard-completar` | ✅ | |
| CA6 | `UnclosedAppointmentsTest`, ui-audit `dashboard-cancelar` | ✅ | |
| CA7 | `CompletedWithoutTrackingTest` | ⚠ | El orden no se comprueba (R4) |
| CA8 | `RegisterAppointmentTrackingTest`, `RegisterAppointmentTrackingValidationTest` | ⚠ | Sin flujo de navegador con 422 ni con envío correcto (R12) |
| CA9 | `DashboardScreenTest`, ui-audit `expedientes-buscar` | ⚠ | Ocultar "Registrar seguimiento" sin siguiente cita no se comprueba (R5) |
| CA10 | `DashboardScreenTest`, ui-audit flujo "mes del administrador" | ✅ | |
| CA11 | `DashboardScreenTest`, `StaffNavigationTest`, `RoleCopyTest` | ✅ | |
| CA12 | ui-audit flujos de vacío, carga, error y "Reintentar" | ⚠ | No comprueba que solo se repita esa sección (R13); foco perdido (R7) |
| CA13 | `DashboardAccessControlTest`, `TodaySummaryTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest` | ✅ | |
| CA14 | `DashboardAccessControlTest`, `TrackingUseCasesAuthorizationTest` | ✅ | |
| CA15 | `RegisterAppointmentTrackingTest` | ⚠ | El 409 de la carrera no se prueba (R1); completar a la vez da 500 (R2) |
| CA16 | `DashboardAccessControlTest`, `DashboardScreenTest`, ui-audit `dashboard-inactivo` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los 3 archivos sin tarea que los cite tienen nota que los justifica (`AppointmentPageException` en T031, `section-error.blade.php` en T045, `AppointmentsAccessControlTest` en T035, decisión del usuario).
- Partes del plan sin implementar: ninguna; T001, T052, T053 y T055 son de documentación y capturas.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Tests en el módulo | ✅ | `tests/Modules/{Appointments,AppointmentTracking,Core}`, `it()` en inglés, base por `uses()` |
| P2 Test antes | ✅ con matiz | Los tests existen y fallaban antes (notas de tareas); R1, R4–R6, R12 y R13 no prueban del todo |
| P3 Capas | ✅ | Domain sin Eloquent (`*ArchitectureTest`); excepción D20 de `ShowDashboardPageController`. La lista de archivos del test de arquitectura es manual (menor R25) |
| P4 Contrato primero | ✅ | Recursos con campos fijos fijados por T013–T017 |
| P5 Autorización en el servidor | ✅ | `assertCan` primero (`TrackingUseCasesAuthorizationTest`), `DoctorScope` |
| P6 FormRequest | ⚠ | `RegisterAppointmentTrackingRequest` sin límites de longitud ni de número de recetas (R3) |
| P7 Errores genéricos | ⚠ | 500 en completar simultáneo (R2) y con medicamento de más de 255 caracteres (R3) |
| P8–P10 | ➖ | Sin configuración, migraciones ni dependencias nuevas |
| P11 Datos sensibles | ✅ | `DashboardNoClinicalDataTest`, `DashboardLogsTest` |
| P12 Producción con aprobación | ➖ | Lo aplica `/release` |
| P13 Lógica en el backend | ✅ | Siguiente cita, contadores, `can` y `day_label` vienen del servidor |
| P14 Auditoría | ❌ aceptado | Excepción del plan (D8): objetivo 5 |
| P15 Sistema de diseño | ✅ con matiz | `border-l-[6px]` y `text-[40px]` documentados en `system.md` pero sin token (menor R26) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`ae818f4..HEAD`) | ❌ 1 hallazgo, falso positivo: `TRACKING_AUTH_ACTOR_ID` es un UUID fijo de test (`generic-api-key`, commit 6282754); haría fallar el job de CI del PR (R14) |
| semgrep `p/php` | ✅ 0 hallazgos (548 archivos) |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config `docker/` | ✅ 0 |
| DAST | ➖ no aplica (sin staging) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `auth:sanctum` + `staff`; `DoctorScope` ignora la query; pertenencia antes de leer el seguimiento; TM1–TM4 con test que falla al quitar el control |
| A02:2025 Configuración | ✅ | `UiAuditClock` comprueba el entorno en cada petición (`UiAuditClockTest` con `production` y `staging`); depende solo de `APP_ENV` (menor R30) |
| A03:2025 Cadena de suministro | ➖ | Sin dependencias nuevas |
| A04:2025 Datos sensibles | ✅ | Recursos sin datos clínicos ni de contacto |
| A05:2025 Inyección / XSS | ✅ | Eloquent con bindings; `textContent` y plantillas; el único `innerHTML` pasa por `escapeHtml` |
| A06:2025 Diseño inseguro | ⚠ | Sin límites de entrada en el registro (R3); el control de carrera de TM5 sin test (R1) |
| A07:2025 Autenticación | ✅ | Sin cambios; RS1.c sigue pendiente (spec 013) |
| A08:2025 Integridad / CSRF | ➖ | `POST /tracking` bajo RS5 (pendiente, documentado) |
| A09:2025 Registro | ✅ / ➖ | Sin logs nuevos; auditoría aceptada para el objetivo 5 |
| A10:2025 Condiciones excepcionales | ⚠ | 500 en lugar de 409/422 (R2, R3) |
| API4:2023 Consumo sin límite | ⚠ | Listas limitadas a 10; cuerpo del registro sin tope (R3) |
| Proceso del agente | ✅ | Ninguna ruta protegida tocada; sin instrucciones a agentes en el código |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (P14 ❌ aceptado, objetivo 5); puntos de emisión documentados en `observability.md` (T091).
- Datos sensibles en logs del código tocado: ninguno (`DashboardLogsTest` siembra valores y comprueba que no aparecen).
- `request_id` presente en los logs nuevos: no hay logs nuevos (OB3.a pendiente, objetivo 5).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Modules/AppointmentTracking/Integration/RegisterAppointmentTrackingTest.php:157` | El test de doble envío es secuencial: la segunda petición la corta `findByAppointmentId` del caso de uso y nunca llega al `catch (UniqueConstraintViolationException)` de `EloquentAppointmentCompletionRepository.php:62`; quitar el catch deja la suite en verde (TM5, CA15) | Un caso que llegue al repositorio con el seguimiento ya insertado: 409 "Esta cita ya tiene seguimiento registrado." y una sola fila |
| R2 | importante | `app/Modules/AppointmentTracking/Infrastructure/Persistence/Eloquent/EloquentAppointmentCompletionRepository.php:26` | `complete()` no convierte la violación de la restricción única: dos "Completar" simultáneos de la misma cita dan 500 en el segundo, no el 409 de CA15 | El mismo catch que `register()`, con su test |
| R3 | importante | `app/Modules/AppointmentTracking/Infrastructure/Http/Requests/RegisterAppointmentTrackingRequest.php:40` | Sin `max` de longitud ni tope de síntomas y recetas; medicamento o dosis de más de 255 caracteres (columna `string`) dan 500 en lugar de 422, también al completar (sus value objects no validan longitud) | Límites decididos por el usuario: medicamento y dosis 255; textos 2000; síntomas hasta 20 de 255; recetas hasta 20 |
| R4 | importante | `tests/Modules/AppointmentTracking/Integration/CompletedWithoutTrackingTest.php:101` | `toEqualCanonicalizing` ignora el orden "de la más reciente a la más antigua" de CA7 | Comparar con el orden exacto, con dos citas del mismo día a horas distintas |
| R5 | importante | `tests/Browser/screens.json` (flujos "vacío de cada sección") | Que "Registrar seguimiento" no aparezca sin siguiente cita (CA9) no lo comprueba nada | Añadir la ausencia del botón visible al flujo |
| R6 | importante | `tests/Modules/Appointments/Unit/Domain/TodayAgendaPropertiesTest.php:93` | El oráculo de `afterMore` repite la fórmula de la implementación; pasaría con `after` siempre vacío; `isPast` sin propiedad | Oráculo independiente: las dos siguientes por hora y el resto; `isPast` contra la hora |
| R7 | importante | `resources/js/pages/dashboard.js:47` | Al pulsar "Reintentar", la sección oculta el bloque con el botón enfocado y el foco cae en `body` (WCAG 2.4.3) | Llevar el foco al título de la sección antes de recargar; si vuelve a fallar, al nuevo "Reintentar" |
| R8 | importante | `resources/js/pages/dashboard.js:242` | Las cargas de una sección (reintento, `agendaPage.reload`, tras completar o cancelar) no descartan respuestas viejas: una anterior puede pintar encima de una nueva | Número de secuencia por sección; se descarta la respuesta que no es la última |
| R9 | importante | `resources/views/pages/dashboard.blade.php:90` | "Citas sin cerrar" y "Completadas sin seguimiento" del resumen del día dicen "0" mientras cargan o si fallan | Mostrar "–" hasta tener el dato y "no disponible" si falla |
| R10 | importante | `resources/views/pages/dashboard.blade.php` (plantillas de filas y siguiente cita) | "Completar", "Cancelar cita", "Registrar seguimiento" y "Ver detalle" tienen el mismo nombre accesible en cada fila (WCAG 2.4.6) | Texto `sr-only` " de {paciente} a las {hora}" detrás del visible, como "Consultar expediente" |
| R11 | importante | `resources/js/pages/records/index.js:964` | El buscador de expedientes cambia la lista sin anunciar el resultado (WCAG 4.1.3) | Anunciar el número de pacientes o "No se encontraron pacientes" por `status.js`, con una espera corta |
| R12 | importante | `tests/Browser/screens.json` (`dashboard-registrar-seguimiento`) | Ningún flujo responde 422 (errores bajo cada campo con `markServerErrors`, A40) ni 200 (envío a `/tracking` y "Seguimiento registrado.") | Dos flujos: 422 con errores por campo y 200 con la petición y el aviso |
| R13 | importante | `tests/Browser/screens.json:228` (flujo "Reintentar") | No comprueba que solo se repita la petición de esa sección (CA12) ni dónde queda el foco | `requests` de la sección y foco tras reintentar |
| R14 | importante | `tests/Modules/AppointmentTracking/Unit/Application/TrackingUseCasesAuthorizationTest.php:28` | gitleaks (`generic-api-key`) marca el UUID fijo `TRACKING_AUTH_ACTOR_ID`; el job de gitleaks del PR fallaría | Registrar la huella del hallazgo en `.gitleaksignore` |
| R15 | menor | `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/RegisterAppointmentTrackingController.php:56` | El 404 se distingue del 409 comparando textos de mensaje; la rama de id no UUID sin test | Método o excepción propia para "no encontrada"; test con `not-a-uuid` |
| R16 | menor | `app/Modules/AppointmentTracking/Domain/Exceptions/AppointmentTrackingException.php:21` | `alreadyExistsForAppointment()` quedó sin usos | Eliminarla |
| R17 | menor | `app/Modules/AppointmentTracking/Domain/Repositories/PendingTrackingRepositoryInterface.php:20` | Devuelve `array{items,total}` en lugar del VO `AppointmentPage` que usan las otras dos lecturas | Devolver `AppointmentPage` |
| R18 | menor | `app/Modules/AppointmentTracking/Infrastructure/Persistence/Eloquent/EloquentPendingTrackingRepository.php:22` | Usa `mapToDomain` de `EloquentAppointmentRepository`, fuera de su interfaz (aceptado en el plan, A23) | Mapeo compartido por interfaz |
| R19 | menor | `app/Modules/Appointments/Infrastructure/Http/Controllers/GetTodaySummaryController.php:28` | `now()` se evalúa en el caso de uso y en el controlador: en el cambio de minuto `now` e `is_past` pueden no coincidir | Que `TodayAgenda` exponga su `now` |
| R20 | menor | `app/Core/Middlewares/UiAuditClock.php:68` y otros | Comentarios en línea `// Spec 020 …` en vez de PHPDoc | Pasarlos al PHPDoc |
| R21 | menor | `tests/Modules/Appointments/Integration/DashboardNoClinicalDataTest.php` | Casos de "sin seguimiento" duplicados línea a línea | Un dataset de tres URLs |
| R22 | menor | `tests/Modules/Appointments/Integration/DashboardPerformanceTest.php` | Aserción de tiempo de pared `< 2 s`, sensible a la carga | Grupo `performance` o margen mayor |
| R23 | menor | `app/Modules/Appointments/Infrastructure/Http/Resources/DashboardAppointmentResource.php:80` | `can()` resuelve la autorización con `app()` por cada cita; `next` repite los campos del recurso a mano | Inyectar o precalcular las capacidades |
| R24 | menor | `resources/views/pages/dashboard.blade.php:101` | "Acciones rápidas" va en `<nav>`: no es navegación | `<section aria-labelledby>` |
| R25 | menor | `tests/Modules/AppointmentTracking/Unit/AppointmentTrackingArchitectureTest.php:38` | Lista manual de archivos de Domain; `AppointmentPageException` y los nuevos quedan fuera | Recorrer `Domain/**` |
| R26 | menor | `resources/views/components/dashboard/next-appointment.blade.php:11` | `border-l-[6px]` y `text-[40px]`, documentados en `system.md` pero sin token | Tokens en `@theme` |
| R27 | menor | `resources/views/pages/dashboard.blade.php:38` | `data-dashboard-role` y `data-dashboard-can-*` no los lee nadie | Quitarlos |
| R28 | menor | `resources/views/pages/dashboard.blade.php:14` | La hora del saludo no se actualiza al recargar, la línea "Ahora" sí | Actualizarla con `data.now` o quitarla |
| R29 | menor | `resources/js/pages/dashboard.js` (`paintMonth`, `paintAgenda`) | `previous_short_label` ausente pintaría "undefined"; la siguiente de la agenda se distingue a la vista solo por el borde | `?? ''`; etiqueta visible |
| R30 | menor | `app/Core/Middlewares/UiAuditClock.php:56` | La protección de TM9 depende solo de `APP_ENV` (`.env.example` trae `local`) | Segunda condición por configuración, apagada por defecto |
| R31 | menor | `app/Modules/AppointmentTracking/Application/UseCases/RegisterAppointmentTrackingUseCase.php:45` | Un doctor distingue cita inexistente (404) de ajena (403); bajo impacto con UUID v4 | Misma respuesta en los dos casos |
| R32 | menor | `resources/js/pages/records/index.js` (`matchingSearch`) | La búsqueda distingue acentos ("maria" no encuentra "María") | Normalizar con NFD |
| R33 | menor | `tests/Browser/screens.json` (flujos "nombre con HTML" y "mes del administrador") | XSS solo en hoy y siguiente cita; el asistente sin flujo de permisos; el mes compara cifras sembradas y no cubre negativo, "sin cambio" ni singular | Ampliar los flujos con respuestas propias |
| R34 | menor | `tests/Modules/Appointments/Integration/TodaySummaryTest.php:22` | Todos los tests de "hoy" a las 11:40, cuando la fecha coincide en Ciudad de México y en UTC | Un caso a las 23:30 de Ciudad de México |

R1 y R14 los encontraron dos y tres revisores; se cuentan una vez. `snapshotInPage` de `ui-audit.mjs`
(reemplazo de URL por subcadena) es de apoyo para `evaluate` y se descarta.

## Preparación para release
- Rollback factible: sí; `docker/prod/rollback.sh`, sin migraciones; los seguimientos registrados desde el inicio tienen la misma forma que los de completar.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D6).
- Docs actualizadas: `architecture.md` (T090), `security.md` (T053), `observability.md` (T091), `deployment.md` sin cambios.
- Riesgos: ninguno cambia de estado en esta spec; RS5.a, OB1.a y OB3.a siguen pendientes.

## Tareas añadidas
- T065 ← R1, R2
- T066 ← R2
- T067 ← R3
- T068 ← R3
- T069 ← R3
- T070 ← R3
- T071 ← R14
- T072 ← R4
- T073 ← R6
- T074 ← R5, R7, R8, R9, R10, R13
- T075 ← R11, R12
- T076 ← R7, R8, R9, R10
- T077 ← R11
- T078 ← verificación tras las correcciones

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-26:
- **R15** → roadmap: el 404 del registro se distingue del 409 por el texto del mensaje; la rama de id no UUID sin test
- **R16** → roadmap: `AppointmentTrackingException::alreadyExistsForAppointment()` sin usos
- **R17** → roadmap: `PendingTrackingRepositoryInterface` no devuelve `AppointmentPage`
- **R18** → roadmap: `EloquentPendingTrackingRepository` usa `mapToDomain` fuera de la interfaz
- **R19** → roadmap: `now()` evaluado dos veces en el resumen de hoy y en sin cerrar
- **R20** → roadmap: comentarios en línea de la spec 020 en lugar de PHPDoc
- **R21** → roadmap: duplicación en `DashboardNoClinicalDataTest`
- **R22** → roadmap: aserción de tiempo de pared en `DashboardPerformanceTest`
- **R23** → roadmap: `can()` de los recursos del inicio con `app()` por cita
- **R24** → roadmap: "Acciones rápidas" como `<nav>`
- **R25** → roadmap: lista manual de archivos de Domain en los tests de arquitectura
- **R26** → roadmap: 6 px y 40 px de "Siguiente cita" sin token
- **R27** → roadmap: atributos `data-dashboard-role` y `data-dashboard-can-*` sin uso
- **R28** → roadmap: la hora del saludo no se actualiza
- **R29** → roadmap: `paintMonth` sin protección ante datos incompletos; la siguiente de la agenda solo por color a la vista
- **R30** → roadmap: el reloj de la prueba depende solo de `APP_ENV`
- **R31** → roadmap: 404 frente a 403 en el registro distingue cita inexistente de ajena
- **R32** → roadmap: el buscador de expedientes distingue acentos
- **R33** → roadmap: flujos de navegador de XSS, permisos del asistente y mes con datos sembrados
- **R34** → roadmap: sin test de "hoy" cerca de medianoche
