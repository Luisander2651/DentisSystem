---
spec: 019-reportes-mensuales
verdict: changes_requested
round: 1
date: 2026-10-21
base: 909f3bf
head: 03abd4f
human_signoff: pending
---

# Review · 019 Reportes mensuales

## Resumen
Cambios pedidos. Un bloqueante: en el mes en curso la tasa de asistencia cuenta en el numerador
las citas completadas hoy o con fecha futura, pero el divisor solo incluye las de fecha pasada, y
eso incumple CA3 (puede pasar de 100 %) (R1). Hay además cinco importantes: un test de zona
horaria que no prueba la zona, un regex que cruza componentes, el temporal del Excel que queda si
la escritura falla, y el foco y el contorno destacado en la pantalla. El usuario decidió
corregirlos todos, junto con seis menores baratos, en T058–T072. Los demás menores van al roadmap.
Conteo: bloqueante 1 · importante 5 · menor 30

Revisores con contexto limpio: spec (A), constitución y calidad (C+D), seguridad (S) y
accesibilidad. El diff de código ocupa 238 KB, así que cada revisor empezó por los archivos de su
encargo. B (plan y alcance) y la preparación para release los hizo la sesión principal con
`scope.md`. No se lanzó revisor de SEO: `/reportes` es privada y el sitio público no cambia.
`design:accessibility-review` no se usó; el revisor de accesibilidad aplicó la lista de la skill.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie, T048; el código no cambió desde `04a18a7`) | ✅ 1653 en verde, 0 fallos (línea base: 1 fallo ajeno de `DeployScriptsBehaviourTest`, que pasó) |
| `vendor/bin/pint --dirty --format agent` (57 archivos PHP de la 019) | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` completo, dos veces | ✅ 278 visitas, 0 fallos en las dos |
| `python .ai/bin/aidd.py validate docs/specs/019-reportes-mensuales` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReportsScreenTest`, `StaffNavigationTest`, `screens.json` (`reportes`) | ⚠ | El mes por defecto y el mes en curso cerca de medianoche no se prueban en la zona de la clínica (R2) |
| CA2 | `GetMonthlyReportTest`, `MonthlyReportServiceTest`, `UiComponentsTest` | ⚠ | El test del stat comparado puede pasar con el componente roto (R3); en pantalla solo se ve la diferencia, igual que el ejemplo de la spec (R28) |
| CA3 | `GetMonthlyReportTest`, `RateTest`, `RatePropertiesTest` | ❌ | R1 |
| CA4 | `GetMonthlyReportTest` | ✅ | |
| CA5, CA6 | `GetMonthlyReportTest`, `MonthlyReportTest`, `RankedItemTest` | ✅ | El desempate por nombre es aproximado con `ñ` (R27) |
| CA7 | `GetMonthlyReportTest`, `screens.json` | ⚠ | El contorno del máximo no se dice al lector de pantalla (R6) |
| CA8 | `DownloadMonthlyReportTest`, `ExcelMatchesScreenTest`, `screens.json` (`reportes-descarga`) | ⚠ | Temporal que queda si la escritura falla (R4) |
| CA9 | `GetMonthlyReportTest`, `DownloadMonthlyReportTest` | ⚠ | R2 |
| CA10 | `ReportsAccessControlTest`, `ReportsUseCasesAuthorizationTest`, `WebAccessDeniedPagesTest`, `GuestRedirectTest` | ✅ | |
| CA11 | `MonthlyReportValidationTest`, `ReportMonthTest` | ⚠ | `2026-07\n` se acepta (R7) |
| CA12 | `MonthlyReportNoPatientDataTest` | ✅ | Sin dirección ni datos clínicos sembrados (R26) |
| CA13 | `ExcelFormulaInjectionTest` | ✅ | |
| CA14 | `DownloadThrottleTest` | ✅ | El 429 no lleva `Retry-After` (R30) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; los 71 archivos de código tienen tarea (`scope.md`).
- Partes del plan sin implementar: ninguna. T001, T047, T050 y T051 no tienen diff de código: son
  tareas de documentación y de comprobación.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño, plan y tareas aprobados antes de `909f3bf` |
| P2 Test que falla antes | ✅ | Notas de T010–T029 con el motivo de falla y las comprobaciones rompiendo el control |
| P3 Capas del módulo | ✅ | `ReportsArchitectureTest`; excepción aceptada de `/reportes`; el test solo mira líneas `use` (R22) |
| P4 Contrato de API primero | ✅ | `MonthlyReportResource`; forma fija en `GetMonthlyReportTest` |
| P5 Autorización en el servidor | ✅ | `only.admin` + `assertCan`; `ReportsAccessControlTest` por actor |
| P6 FormRequest | ✅ | `MonthlyReportRequest` (con R7) |
| P7 Errores sin detalles | ✅ | `UnexpectedReportErrorTest` |
| P8 Secretos | ✅ | gitleaks sin hallazgos |
| P9 Migraciones | ➖ | Sin migraciones |
| P10 Dependencias con ADR | ✅ | OpenSpout 5.12.0, ADR 0005 |
| P11 Datos sensibles y amenazas | ✅ | TM1–TM7 con control y test; `MonthlyReportNoPatientDataTest` |
| P12 Producción | ➖ | Lo aplica `/release` |
| P13 Lógica en el backend | ✅ | Cálculos en `MonthlyReportService`; `ExcelMatchesScreenTest`; el umbral visual de intensidad vive en el JS (R23) |
| P14 Trazabilidad | ✅ | `report.export_failed` documentado; sin `request_id` por OB3.a pendiente (como planeó T091) |
| P15 Sistema de diseño | ✅ | `tests/Modules/Core/Unit/Design` y `ui-audit` en verde |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (64 commits de `909f3bf..03abd4f`) | ✅ 0 hallazgos |
| semgrep (23 reglas, 527 archivos) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy | ✅ sin hallazgos |

| Tema OWASP (2021) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | TM1, TM2: `ReportsAccessControlTest`, `WebAccessDeniedPagesTest` |
| A03 Inyección | ✅ | TM4: solo `StringCell`/`NumericCell`, `ExcelFormulaInjectionTest`; consultas con enlaces |
| A04 Diseño inseguro | ✅ | TM5: `throttle:report-downloads`, `DownloadThrottleTest` |
| A05 Configuración | ✅ | Sin cambios de configuración; 429 sin `Retry-After` (R30) |
| A06 Componentes vulnerables | ✅ | SCA en 0; OpenSpout verificado en el plan |
| A09 Registro | ✅ | Sin datos de pacientes en logs; doble log del mismo fallo (R12) |
| TM3 Divulgación | ✅ | Solo agregados; temporal sin borrar si falla la escritura (R4) |
| TM6 Entrada | ✅ | `month` validado; salto de línea final aceptado (R7) |
| TM7 XSS en la pantalla | ✅ | Solo `textContent`; flujo XSS de `screens.json` |

Proceso: la dependencia nueva (OpenSpout) tiene verificación y ADR. No se tocaron rutas
protegidas. No hay instrucciones dirigidas a agentes en el código. El nombre raíz del lockfile
cambió en el carril de npm (R31).

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: la spec no pide auditoría propia; los
  accesos denegados usan los puntos de emisión existentes (OB1.a pendiente, documentado en T091).
- Datos sensibles en logs del código tocado: ninguno; `report.export_failed` solo lleva el mes y
  la clase de la excepción.
- `request_id` en los logs nuevos: no, por OB3.a pendiente, como registra `observability.md`.
  `report.export_failed` también se emite cuando falla la lectura y no el archivo (R12).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `app/Modules/Reports/Infrastructure/Persistence/EloquentMonthlyReportRepository.php:35`, `app/Modules/Reports/Domain/Service/MonthlyReportService.php:60` | La asistencia divide todas las completadas del mes entre las no canceladas con fecha pasada. En el mes en curso, una cita completada hoy o con fecha futura (`AppointmentEntity::complete()` no restringe la fecha) entra en el numerador y no en el divisor, y la tasa puede pasar de 100 %. Incumple CA3. `Rate::of` lo acepta. | Numerador `completed_past`; `Rate` rechaza numerador > divisor (T058–T061) |
| R2 | importante | `tests/Modules/Reports/Integration/GetMonthlyReportTest.php:193` | "in the time of the clinic" no prueba la zona: `date` es DATE y todos los tests viajan a 2026-10-19 10:00. Nada prueba "hoy" cerca de medianoche, cuando UTC y la clínica están en meses distintos (mes por defecto, `is_current`, 422 del mes futuro) | Tests con 2026-10-31 20:00 y 2026-11-01 00:30 de la clínica (T062) |
| R3 | importante | `tests/Modules/Core/Integration/UiComponentsTest.php:50` | El regex con `/s` y `.*` puede cruzar de un stat al siguiente; pasaría con el `trend` fuera del `dd` | Afirmar cada stat por separado (T063) |
| R4 | importante | `app/Modules/Reports/Infrastructure/Export/OpenSpoutMonthlyReportExporter.php:59` | Si la escritura falla tras `tempnam()`, el `.xlsx` parcial queda en el directorio temporal; `deleteFileAfterSend` solo actúa si hay respuesta | `try`/`catch`, borrar y relanzar (T064, T065) |
| R5 | importante | `resources/js/pages/reportes/index.js:242` | "Intentar de nuevo" oculta el bloque que lo contiene y el foco cae al `body` | Mover el foco al selector del mes (T066, T067) |
| R6 | importante | `resources/js/pages/reportes/index.js:149` | El total con más citas solo se marca con un contorno; la leyenda es `aria-hidden`, así que el lector de pantalla no lo recibe | `span.sr-only` " (más citas)" (T066, T067) |
| R7 | menor | `app/Modules/Reports/Domain/ValueObjects/ReportMonth.php:36`, `app/Modules/Reports/Infrastructure/Http/Requests/MonthlyReportRequest.php:30` | `/^\d{4}-\d{2}$/` acepta un salto de línea final | `\z` (T068, T069) |
| R8 | menor | `tests/Modules/Reports/Integration/MonthlyReportPerformanceTest.php:58` | Aserción de tiempo real (< 3 s) sensible a la carga de la máquina | Margen mayor o comentario |
| R9 | menor | `resources/js/pages/reportes/index.js:348` | `window.reportsPage` sin consumidores | Eliminarlo (T066, T067) |
| R10 | menor | `resources/js/pages/reportes/index.js:296` | `decodeURIComponent` sin proteger: un nombre mal codificado se anuncia como "sin conexión" | `try`/`catch` con el nombre por defecto (T066, T067) |
| R11 | menor | `resources/views/pages/reportes/index.blade.php:44` | El contenido vacío se ve mientras llega la API (y queda el anterior mientras carga otro mes) | `hidden` inicial (T066, T067) |
| R12 | menor | `app/Modules/Reports/Infrastructure/Http/Controllers/DownloadMonthlyReportController.php:44` | Dos logs por el mismo fallo (`report.export_failed` y `unexpected_error`), y `report.export_failed` también cuando falla la lectura | Un log, emitido solo si falla el exportador |
| R13 | menor | `GetMonthlyReportController.php:25`, `DownloadMonthlyReportController.php:36` | Respuesta 422 armada a mano en los dos controladores | Método compartido |
| R14 | menor | `app/Modules/Reports/Domain/ValueObjects/MonthlyReport.php:49` | `create()` no valida la forma; tendencias y direcciones son cadenas sueltas | Enums `Trend`/`Direction` y validación |
| R15 | menor | `app/Modules/Reports/Domain/Service/MonthlyReportService.php:31` | Se reasigna `$today` con otro tipo | Variable nueva |
| R16 | menor | `tests/Modules/Reports/Integration/ExcelMatchesScreenTest.php:31`, `DownloadMonthlyReportTest.php:87` | Lector de Excel duplicado; `->call($test)` para usar `$this`; copias en `tempnam` sin borrar | Helper en `ReportsIntegrationTestCase` |
| R17 | menor | `tests/Modules/Reports/Integration/ReportsScreenTest.php:89` | El test del `@vite` busca texto en el archivo fuente | Afirmar sobre la respuesta renderizada |
| R18 | menor | `tests/Modules/Reports/Unit/Domain/MonthlyReportTest.php:90` | "has nothing about a patient" solo fija los nombres de las propiedades | Renombrarlo |
| R19 | menor | `tests/Modules/Reports/Unit/Domain/ComparisonTest.php:54` | El test solo fija el valor de tres constantes | Usar las constantes en el dataset |
| R20 | menor | `tests/Modules/Reports/Unit/Domain/AppointmentCountsPropertiesTest.php:39` | Aserciones que se cumplen por construcción de los generadores | Afirmar lo que se conserva |
| R21 | menor | `tests/Modules/Reports/Unit/Domain/MonthlyReportPropertiesTest.php:13` | El oráculo copia `nameKey` de producción | Orden explícito o `Collator` |
| R22 | menor | `tests/Modules/Reports/Unit/ReportsArchitectureTest.php:30` | Solo mira líneas `use`: no detecta nombres completos en línea ni `use` agrupados | Buscar los espacios de nombres en todo el contenido |
| R23 | menor | `resources/js/pages/reportes/index.js:35` | El corte "3 o más" de la intensidad está en el JS y en el texto de la leyenda | Una sola fuente |
| R24 | menor | `tests/Modules/Reports/Unit/Domain/RatePropertiesTest.php:17` | La propiedad fuerza `numerator <= denominator` y no detecta R1 | Se corrige con R1 (T060, T061) |
| R25 | menor | `tests/Modules/Reports/Integration/GetMonthlyReportTest.php:186` | La cita "reprogramada" se crea ya en julio, sin reprogramarla desde otro mes | Reprogramarla de junio a julio |
| R26 | menor | `tests/Modules/Reports/Integration/MonthlyReportNoPatientDataTest.php:133` | No siembra dirección ni seguimiento clínico con receta | Sembrarlos y buscarlos |
| R27 | menor | `app/Modules/Reports/Domain/ValueObjects/MonthlyReport.php:76` | El desempate convierte `ñ` en `n` y solo normaliza seis vocales | `Collator('es_MX')` o documentarlo |
| R28 | menor | `resources/js/ui/trend.js:73` | CA2 dice "el del mes anterior y la diferencia"; en pantalla solo se ve la diferencia (como el ejemplo de la spec y `design.md:105`) | Aclarar el texto de CA2 |
| R29 | menor | `tests/Modules/Reports/Integration/ExcelMatchesScreenTest.php:76` | No compara la columna total por hora ni las etiquetas de hora | Compararlas |
| R30 | menor | `app/Providers/AppServiceProvider.php:228` | El 429 propio descarta `Retry-After` y `X-RateLimit-*` | Pasar `$headers` (T070, T071) |
| R31 | menor | `package-lock.json:2` | El nombre raíz pasó de `DentissaApp` a `html` en el carril de npm | Restaurarlo (T072) |
| R32 | menor | `resources/views/pages/reportes/index.blade.php` (bloque de error) | El texto visible del bloque dice "Revisa tu conexión" también ante un 4xx | Texto según el tipo de fallo |
| R33 | menor | `resources/js/pages/reportes/index.js:279` | Sin anuncio al terminar de cargar; `change` pide un reporte por cada flecha del teclado | Anuncio breve y espera corta |
| R34 | menor | `resources/js/pages/reportes/index.js:301` | El botón con `aria-disabled` no cambia de aspecto mientras descarga | Estilo de deshabilitado del sistema |
| R35 | menor | `resources/views/pages/reportes/index.blade.php:88` | `ol` con `list-style` quitado pierde su rol en Safari | `role="list"` |
| R36 | menor | `resources/views/pages/reportes/index.blade.php:66` | `abbr title` no es fiable con lectores de pantalla | Nombre completo del día en `sr-only` |

## Preparación para release
- Rollback factible: sí. No hay migraciones ni flags; la versión anterior se restaura con
  `docker/prod/rollback.sh` y la ruta nueva desaparece con ella.
- Migraciones compatibles con la versión anterior: no hay.
- Docs actualizadas: `architecture.md` (T090) y `observability.md` (T091); `deployment.md` sin
  cambios (sin variables ni pasos nuevos); `security.md` (T051).
- SCA: 0 críticas y 0 altas tras el carril `docs/changes/2026-10-21-chore-npm-audit.md`.
- Cobertura de riesgos: ningún riesgo figura como mitigado con correcciones pendientes (OB1.a y
  OB3.a siguen pendientes, como dice el plan).

## Tareas añadidas
- T058, T059, T060, T061 ← R1 (y R24)
- T062 ← R2
- T063 ← R3
- T064, T065 ← R4
- T066, T067 ← R5, R6, R9, R10, R11
- T068, T069 ← R7
- T070, T071 ← R30
- T072 ← R31

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-21:
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
