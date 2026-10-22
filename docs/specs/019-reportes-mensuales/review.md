---
spec: 019-reportes-mensuales
verdict: approved
round: 3
date: 2026-10-22
base: bafbccb
head: ff9ad96
human_signoff: Luisander2651, 2026-10-22
---

# Review · 019 Reportes mensuales

## Resumen
Aprobada, ronda 3. Los tres hallazgos que el usuario decidió corregir en la ronda 2 están
corregidos (R37, R39, R40, en T073–T074): el test del temporal usa una carpeta propia y ya no
depende del directorio temporal compartido, el exportador relanza siempre la excepción original y
el test de R11 exige el atributo `hidden` suelto. Queda un menor nuevo: la rama de R39 (fallo de
`close()` durante la recuperación) no tiene test (R43). El usuario aceptó R43 al roadmap y firmó la review
(P12, definición de terminado).
Ronda anterior: [history/review.r2.md](history/review.r2.md).
Conteo (ronda 3): bloqueante 0 · importante 0 · menor 1

Revisión `--rerun` sin subagentes: `code.diff` de `bafbccb..ff9ad96` ocupa 5 KB (3 archivos de
código, 0 sin tarea, 0 tareas sin diff); releídos desde cero.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php -d memory_limit=1G ./vendor/bin/pest` (serie, sobre `e80c805`, mismo código que `ff9ad96`) | ✅ 1671 en verde, 0 fallos (la línea base `DeployScriptsBehaviourTest` ya no falla) |
| `./vendor/bin/pest --parallel --processes=4` (suite completa) | ✅ 1671 en verde |
| `./vendor/bin/pest --parallel` (12 procesos, suite completa, dos veces) | ⚠ 211 y 4 fallos distintos cada vez, ninguno de Reports: `SQLSTATE[08006] … timeout expired` hacia `db`, `ProcessTimedOutException` y `HealthCheckTest`. Saturación de Docker Desktop local con 12 workers; con 4 procesos pasa todo. La rama no se ha subido, así que no hay ejecución de CI que lo confirme |
| `./vendor/bin/pest --parallel tests/Modules/Reports` (dos veces) | ✅ 187 en verde las dos |
| `vendor/bin/pint --dirty --format agent` | ✅ (`--test` sobre todo el repo marca archivos antiguos con CRLF, ajenos a la 019) |
| `npm run build` | ✅ |
| `npm run test:ui` | ➖ no ejecutado: el diff no toca vistas ni JS |
| `python .ai/bin/aidd.py validate docs/specs/019-reportes-mensuales` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA8, CA12 | `DownloadMonthlyReportTest` ("writes the file in its own folder…") | ✅ | R37: carpeta propia; falla si el exportador ignora el directorio o si no borra el archivo a medio escribir (comprobado en T073 y T074) |
| CA1 | `ReportsScreenTest` (R11) | ✅ | R40: falla con `aria-hidden="true"` y con `class="hidden"` (comprobado en T073) |
| CA2–CA7, CA9–CA11, CA13, CA14 | Sin cambios desde la ronda 2 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea).
- Partes del plan sin implementar: ninguna.
- El constructor nuevo del exportador tiene un parámetro opcional; el binding de
  `AppServiceProvider.php:150` no cambia y el contenedor usa el valor por defecto.

## Constitution Check (sobre el código)
Sin cambios desde la ronda 2. P2: el test de T073 falló antes de T074 (el Excel quedaba en
`/tmp`) y se comprobó rompiendo el `unlink`. P3: el cambio queda en `Infrastructure`.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (6 commits de `bafbccb..ff9ad96`) | ✅ 0 hallazgos |
| semgrep (23 reglas, 527 archivos) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy (`docker/`) | ✅ sin fallos HIGH/CRITICAL |

El diff no debilita TM1–TM7: el archivo a medio escribir se sigue borrando (ahora también si
`close()` falla), el directorio temporal no lo controla ninguna entrada del usuario (solo el
constructor, con el del sistema por defecto) y no hay logs nuevos.

## Observabilidad
R39 corregido: si `close()` falla al recuperarse, `report.export_failed` registra la clase de la
excepción original.

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R37 | corregido | `OpenSpoutMonthlyReportExporter::__construct(?string $temporaryDirectory)`; el test usa `reporte-dentissa-test-<aleatorio>` y lo borra al final |
| R39 | corregido (sin test → R43) | `close()` en su propio `try` que ignora su fallo; `unlink` y `throw $e` después |
| R40 | corregido | regex con lookaheads: `\sdata-reports-content[\s>=]` y `\shidden(?:[\s>]\|="")` dentro de la misma etiqueta |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R43 | menor | `app/Modules/Reports/Infrastructure/Export/OpenSpoutMonthlyReportExporter.php:84` | Ningún test hace fallar `close()` durante la recuperación: en el caso de la semana rota `close()` no lanza, así que volver a `finally` (el código de antes de R39) seguiría pasando la suite | Un test con un escritor que falla también al cerrar, o aceptarlo al roadmap |

## Preparación para release
Sin cambios desde la ronda 1: rollback con `docker/prod/rollback.sh`, sin migraciones ni flags,
docs actualizadas (T090, T091), SCA en 0.

## Tareas añadidas
- ninguna

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

Aceptados por el usuario el 2026-10-22 (ronda 3):
- **R43** → roadmap: ningún test hace fallar `close()` durante la recuperación del exportador (R39 sin protección)
