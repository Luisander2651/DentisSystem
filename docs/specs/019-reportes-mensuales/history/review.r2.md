---
spec: 019-reportes-mensuales
verdict: changes_requested
round: 2
date: 2026-10-21
base: 03abd4f
head: bafbccb
human_signoff: pending
---

# Review · 019 Reportes mensuales

## Resumen
Cambios pedidos, ronda 2. Las trece correcciones de la ronda 1 se hicieron (R1–R7, R9–R11, R24,
R30, R31, en T058–T072), y el bloqueante R1 quedó cerrado con sus tests. Queda un importante
nuevo: el test del temporal de R4 compara todo el directorio temporal compartido, y con
`pest --parallel`, como corre CI, fallaría a veces sin un error real (R37). El usuario decidió
corregirlo junto con dos menores (R39, R40) en T073–T074. Los otros menores van al roadmap.
Ronda anterior: [history/review.r1.md](../history/review.r1.md).
Conteo (ronda 2): bloqueante 0 · importante 1 · menor 5

Revisión `--rerun` con un solo revisor (perfil `critical`, encargos A, C+D y S juntos):
`code.diff` de `03abd4f..bafbccb` ocupa 37 KB (25 archivos de código, 0 sin tarea).

## Verificación automática
| Comando | Resultado |
|---|---|
| `php -d memory_limit=1G ./vendor/bin/pest` (serie) | ✅ 1671 en verde, 0 fallos |
| `vendor/bin/pint --dirty --format agent` | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui -- --only reportes` (tests nuevos, dos veces seguidas) | ✅ 12 visitas, 0 fallos en las dos |
| `npm run test:ui` completo | ⚠ 278 visitas, 3 con fallos, todos de `contacto` (página pública, ajena a la 019): el mapa de Google Maps incrustado no carga desde el equipo (`curl` a google.com/maps sin respuesta en 20 s). En T048 pasaba; lo decide CI |
| `python .ai/bin/aidd.py validate docs/specs/019-reportes-mensuales` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1, CA9 | `ReportsScreenTest`, `GetMonthlyReportTest` (cambio de mes a las 20:00 del 31 en la clínica) | ✅ | R2; el caso de las 00:30 no distingue la zona (R38) |
| CA2, CA3 | `GetMonthlyReportTest` (0 de 1 con completadas hoy y en el futuro), `AppointmentCountsTest`, `RateTest`, `RatePropertiesTest`, `UiComponentsTest` | ✅ | R1, R3, R24 |
| CA7 | `screens.json` (`textIn` "más citas") | ✅ | R6 |
| CA8, CA12 | `DownloadMonthlyReportTest` (temporal tras un fallo), `screens.json` (nombre mal codificado) | ⚠ | R4 corregido; su test es inestable en paralelo (R37) |
| CA11 | `ReportMonthTest`, `MonthlyReportValidationTest` | ✅ | R7; por HTTP, `TrimStrings` recorta el salto de línea y se lee el mes |
| CA14 | `DownloadThrottleTest` (`Retry-After`, `X-RateLimit-*`) | ✅ | R30 |
| CA4–CA6, CA10, CA13 | Sin cambios desde la ronda 1 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios desde la ronda 1. P2: cada corrección con su test que fallaba antes, o comprobado
rompiendo (T062, T063). P3: `AppointmentCounts` y `Rate` siguen sin dependencias del framework.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (17 commits de `03abd4f..bafbccb`) | ✅ 0 hallazgos |
| semgrep (23 reglas, 527 archivos) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy (`docker/`) | ✅ sin fallos HIGH/CRITICAL |

El diff no debilita TM1–TM7: el limitador conserva sus cabeceras, el temporal se borra al fallar
la escritura y no hay logs nuevos. El invariante nuevo de `AppointmentCounts` no puede lanzar con
datos reales: `completed_past` es un subconjunto de `completed` y de `not_cancelled_past`, y es
disjunto de `unclosed`.

## Observabilidad
Sin cambios desde la ronda 1. Si `close()` falla dentro del `catch`, `report.export_failed`
registraría la clase de esa excepción y no la original (R39).

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `MonthlyReportService::attendance()` con `completedPast`; `GetMonthlyReportTest` (0 de 1), `AppointmentCountsTest`, `Rate::of` rechaza numerador > divisor |
| R2 | corregido | `ReportsScreenTest`, `GetMonthlyReportTest`, `MonthlyReportValidationTest` a las 20:00 del 31; fallan con `now('UTC')` |
| R3 | corregido | `UiComponentsTest`: cada stat por separado, anclado y sin `/s` |
| R4 | corregido (test inestable → R37) | `OpenSpoutMonthlyReportExporter::export()` con `try`/`finally` y `unlink` |
| R5 | corregido | foco al selector antes de ocultar el bloque; `focused` en `screens.json` |
| R6 | corregido | `span.sr-only` " (más citas)"; `textIn` en `screens.json` |
| R7 | corregido | `\z` en `ReportMonth` y `MonthlyReportRequest`; `ReportMonthTest` |
| R9 | corregido | sin `window.reportsPage`; `globalUndefined` |
| R10 | corregido (flujo débil → R42) | `decodeURIComponent` en `try`/`catch` |
| R11 | corregido el estado inicial (resto → R41) | `hidden` en la vista; `ReportsScreenTest` (regex laxo → R40) |
| R24 | corregido | propiedad nueva en `RatePropertiesTest` |
| R30 | corregido | `$headers` en la respuesta del 429; `DownloadThrottleTest` |
| R31 | corregido | `package-lock.json` con `"name": "DentissaApp"` en la raíz y en `packages[""]` |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R37 | importante | `tests/Modules/Reports/Integration/DownloadMonthlyReportTest.php` (test "leaves no temporary file behind…") | Compara todos los `reporte-dentissa-*` del directorio temporal compartido antes y después. Con `pest --parallel` (CI, `.github/workflows/tests.yml:105`), otra descarga simultánea crea uno y el test falla sin error real | El exportador recibe su directorio temporal; el test usa una carpeta propia (T073, T074) |
| R38 | menor | `tests/Modules/Reports/Integration/GetMonthlyReportTest.php` (test "starts the next month of the clinic after its midnight…") | A las 00:30 del 1 de noviembre UTC y la clínica ya están en noviembre: el caso no distingue la zona, y ningún test fija el límite de mes de `newPatients` en la hora de la clínica | Paciente dado de alta a las 20:00 del 31 de octubre: cuenta en octubre |
| R39 | menor | `app/Modules/Reports/Infrastructure/Export/OpenSpoutMonthlyReportExporter.php:78` | Si `close()` lanza dentro del `catch`, sale esa excepción y se pierde la original | `close()` en su propio `try` que ignora su fallo; relanzar siempre la original (T074) |
| R40 | menor | `tests/Modules/Reports/Integration/ReportsScreenTest.php:104` | `\bhidden\b` también casa con `aria-hidden` o con la clase `hidden` | Exigir el atributo suelto (T073) |
| R41 | menor | `resources/js/pages/reportes/index.js` (`load()`) | Mientras carga otro mes sigue a la vista el anterior, con su título | Ocultarlo o marcarlo `aria-busy` al empezar `load()` |
| R42 | menor | `tests/Browser/screens.json` (flujo "nombre de archivo mal codificado") | Solo afirma una ausencia (`notAnnounced`): pasaría si el clic no llegara | Añadir una expectativa positiva |

## Preparación para release
Sin cambios desde la ronda 1: rollback con `docker/prod/rollback.sh`, sin migraciones ni flags, docs
actualizadas, SCA en 0.

## Tareas añadidas
- T073, T074 ← R37, R39, R40

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-21 (ronda 1, [history/review.r1.md](../history/review.r1.md)):
- **R8** → roadmap: aserción de tiempo real del test de rendimiento, sensible a la carga
- **R12** → roadmap: doble log del fallo de descarga y `report.export_failed` también cuando falla la lectura
- **R13** → roadmap: respuesta 422 repetida en los controladores de Reports
- **R14** → roadmap: `MonthlyReport::create()` sin validación y tendencias como cadenas sueltas (enums)
- **R15** → roadmap: `$today` reasignado en `MonthlyReportService::build()`
- **R16** → roadmap: lector de Excel duplicado y helpers globales en los tests de Reports
- **R17** → roadmap: el test del `@vite` de Reportes mira el archivo fuente
- **R18** → roadmap: nombre engañoso de un test de `MonthlyReportTest`
- **R19** → roadmap: test de `ComparisonTest` que solo fija constantes
- **R20** → roadmap: aserciones triviales en `AppointmentCountsPropertiesTest`
- **R21** → roadmap: oráculo copiado de producción en `MonthlyReportPropertiesTest`
- **R22** → roadmap: `ReportsArchitectureTest` solo mira líneas `use`
- **R23** → roadmap: umbral de intensidad duplicado entre JS y leyenda
- **R25** → roadmap: el test de la cita reprogramada no la reprograma
- **R26** → roadmap: CA12 sin dirección ni datos clínicos sembrados
- **R27** → roadmap: desempate por nombre aproximado con `ñ` y acentos
- **R28** → roadmap: aclarar en CA2 que en pantalla basta la diferencia
- **R29** → roadmap: `ExcelMatchesScreenTest` sin el total por hora
- **R32** → roadmap: texto visible del bloque de error igual para 4xx
- **R33** → roadmap: sin anuncio al cargar y una petición por cada flecha del selector
- **R34** → roadmap: botón de descarga sin aspecto de deshabilitado
- **R35** → roadmap: `role="list"` en las listas de Reportes
- **R36** → roadmap: nombre completo del día en lugar de `abbr title`

Aceptados por el usuario el 2026-10-21 (ronda 2):
- **R38** → roadmap: el caso de las 00:30 no distingue la zona; falta el límite de `newPatients` a las 20:00 del 31
- **R41** → roadmap: el mes anterior sigue a la vista mientras carga otro
- **R42** → roadmap: el flujo del nombre mal codificado sin expectativa positiva
