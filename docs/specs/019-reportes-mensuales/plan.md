---
spec: 019-reportes-mensuales
status: approved   # aprobado por el usuario, 2026-10-19
constitution_version: 1.2.0
created: 2026-10-19
---

# Plan · 019 Reportes mensuales

## Enfoque técnico
Un módulo nuevo `Reports` ([ADR 0006](../../adr/0006-modulo-reports-de-solo-lectura.md))
calcula el reporte de un mes en el servidor. Un repositorio de solo lectura agrega en PostgreSQL
citas, tratamientos, pacientes y staff, y devuelve solo conteos y nombres. El servicio de dominio
`MonthlyReportService` arma el reporte del mes y del anterior: tasas, diferencias y si cada cambio
es favorable, desfavorable o neutro. El mismo objeto alimenta dos salidas: un JSON para la
pantalla y un `.xlsx` hecho con OpenSpout 5.12.0
([ADR 0005](../../adr/0005-openspout-para-excel.md)), que escribe cada celda de texto de forma
explícita como texto. Las rutas van en el grupo `only.admin` y los casos de uso exigen
`reports.view` y `reports.export`, ambos solo del administrador. La descarga tiene su propio
límite de 10 por minuto por usuario, con un 429 en español. La pantalla `/reportes` sigue la
composición C de `design.md` con componentes `x-ui.*` y un indicador de cambio nuevo en el
sistema. Sin migraciones.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 019 `approved` y `design.md` `approved` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene su test de integración o unidad, escrito antes que el código (Trazabilidad). `ReportMonth` es un value object nuevo: tiene tests de propiedades (`ReportMonthPropertiesTest`, Eris) además de los de unidad | test: Trazabilidad; `ReportMonthPropertiesTest` |
| P3 Capas del módulo | ✅ | Controller invocable → UseCase (`assertCan`) → `MonthlyReportService` → `MonthlyReportRepositoryInterface`; el exportador es un puerto de Application (`MonthlyReportExporterInterface`) implementado en Infrastructure. `Domain/` sin Eloquent, facades ni `Infrastructure`. Dependencias nuevas Reports → Appointments, Patients y Users solo en Infrastructure, con [ADR 0006](../../adr/0006-modulo-reports-de-solo-lectura.md) | test: `ReportsArchitectureTest` (ningún `use App\Modules\…\Infrastructure`, `Illuminate\Support\Facades` ni `Eloquent` en `Reports/Domain`); manual: `/review` |
| P4 Contrato de API primero | ✅ | Dos endpoints con su contrato en "Contratos y datos" | test: `GetMonthlyReportTest` fija la forma del JSON; `DownloadMonthlyReportTest` fija cabeceras y hojas |
| P5 Autorización en el servidor | ✅ | `auth:sanctum` + `only.admin` en las rutas y `assertCan('reports.view' / 'reports.export')` en los casos de uso; la ruta web `/reportes` en el grupo `only.admin`. Test de acceso denegado por cada actor no autorizado y por endpoint | test: `ReportsAccessControlTest`, `RolePermissionsTest` |
| P6 Validación con FormRequest | ✅ | `MonthlyReportRequest` (reglas en array, mensajes en español) valida `month` en ambos endpoints; `ReportMonth::create()` vuelve a validar en el dominio | test: `MonthlyReportValidationTest` (dataset de meses inválidos) |
| P7 Errores sin detalles internos | ✅ | 500 con `UnexpectedErrorResponse`; 422 con el mensaje del FormRequest; 403 de `AuthorizationException`; 429 con texto fijo en español | test: `UnexpectedReportErrorTest` (fallo del repositorio → 500 genérico), `MonthlyReportValidationTest`, `DownloadThrottleTest` |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración ni variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones: solo lectura de tablas existentes. Índices: `appointments(date, status)` ya existe (`2026_08_24_120000`); `patients.created_at` no tiene índice, pero el volumen es de prototipo y el tiempo se mide con 2 000 citas (RNF); un índice queda para cuando haya datos reales | — |
| P10 Dependencias nuevas con ADR | ✅ | `openspout/openspout` 5.12.0, fijada sin rango, verificada (25 días, MIT, sin scripts ni transitivas) en [ADR 0005](../../adr/0005-openspout-para-excel.md) | manual: `/review` compara `composer.json` con el ADR; `composer audit` en CI |
| P11 Datos sensibles y modelo de amenazas | ✅ | El reporte no contiene datos de pacientes (solo agregados; CA12). Modelo de amenazas TM1–TM6. Un solo log nuevo (`report.export_failed`) sin datos de pacientes | test: TM1–TM6; `MonthlyReportNoPatientDataTest`; manual: `/review` de `Log::` |
| P12 Producción con aprobación humana | ✅ | Release con `/release` y aprobación del dueño. Línea base de dependencias el 2026-10-19: `composer audit` y `npm audit --audit-level=high` sin hallazgos | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Conteos, tasas, diferencias, máximos y favorabilidad se calculan en el servidor y llegan hechos en el JSON; la pantalla solo pinta. El Excel sale del mismo objeto | test: `MonthlyReportServiceTest` (unidad) y `GetMonthlyReportTest`; `ExcelMatchesScreenTest` compara las cifras del JSON con las del archivo |
| P14 Trazabilidad y auditoría | ❌ aceptado: la spec pide registrar los accesos denegados de CA10, pero el registro de auditoría aún no existe (objetivo 5 del roadmap) — aprobado por el usuario el 2026-10-19 | Cuarta vez después de 014, 017 y 018. Ver o descargar el reporte no es lectura de datos sensibles (solo totales). Punto de emisión identificado en "Observabilidad". Se recomienda priorizar el objetivo 5 antes de otra spec con permisos | — |
| P15 Solo el sistema de diseño | ✅ | Componentes `x-ui.*` existentes más el indicador de cambio nuevo, que T001 añade a `system.md` y `system.html` junto con la regla de color ampliada (`design.md` → "Cambios a incorporar al sistema"). Intensidad de la semana con `bg-primary/25` y `/55`, que el sistema ya permite en fondos | test: `OnlyDesignTokensTest`, `DesignTokensTest`, `UiCopyTest`, `npm run test:ui` |

Resultado: 11 ✅ · 3 ➖ · 1 ❌ aceptado (P14).

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Dependencias | `composer.json` y `composer.lock`: `"openspout/openspout": "5.12.0"` | bajo: sin transitivas |
| Core · Autorización | `app/Core/Authorization/CurrentActorAuthorizationService.php`: `reports.view` y `reports.export` → `$adminOnly` | bajo: lo fija `RolePermissionsTest` |
| Reports · Domain (nuevo) | `app/Modules/Reports/Domain/ValueObjects/ReportMonth.php` (`create('2026-07', today)`: formato `AAAA-MM`, desde 2020-01 hasta el mes de `today`; `previous()`, `firstDay()`, `lastDay()`, `isCurrent(today)`, etiqueta "julio de 2026"); `Domain/Exceptions/ReportMonthException.php` | bajo |
| Reports · Domain (nuevo) | `Domain/ValueObjects/AppointmentCounts.php` (total, completadas, canceladas, reprogramadas pendientes, asignadas pendientes, sin cerrar, no canceladas con fecha pasada), `Rate.php` (porcentaje con un decimal o "sin datos" con divisor cero), `Comparison.php` (actual, anterior, diferencia, tendencia `favorable`/`unfavorable`/`neutral`/`none`), `RankedItem.php` (nombre, valor, extra opcional, `isTop`), `MonthlyReport.php` (todo el reporte) | medio: aquí viven las reglas de CA2–CA7 |
| Reports · Domain (nuevo) | `Domain/Repositories/MonthlyReportRepositoryInterface.php`: `appointmentCounts(ReportMonth, today)`, `newPatients(ReportMonth)`, `activePatients()`, `treatmentRanking(ReportMonth)`, `staffLoad(ReportMonth)`, `demandByWeekdayAndHour(ReportMonth)` (matriz día ISO 1–7 × hora 0–23); solo tipos del dominio y escalares | bajo |
| Reports · Domain (nuevo) | `Domain/Service/MonthlyReportService.php::build(ReportMonth $month, CarbonImmutable $today): MonthlyReport`: pide al repositorio el mes y el anterior y calcula tasas (asistencia = completadas ÷ no canceladas con fecha pasada; cancelación = canceladas ÷ total), diferencias, tendencia por indicador (tabla de `design.md`), franjas de la primera a la última hora con citas, totales por día y por hora, y máximos con empates | medio |
| Reports · Application (nuevo) | `Application/DTOs/MonthlyReportDTO.php` (`::create(string $month)`); `Application/UseCases/GetMonthlyReportUseCase.php` (`assertCan('reports.view')`, `ReportMonth::create($dto->month, now())`, `build`); `Application/UseCases/ExportMonthlyReportUseCase.php` (`assertCan('reports.export')`, `build`, exportador → ruta del archivo temporal y nombre `reporte-dentissa-AAAA-MM.xlsx`); `Application/Contracts/MonthlyReportExporterInterface.php` | bajo |
| Reports · Infrastructure (nuevo) | `Infrastructure/Persistence/EloquentMonthlyReportRepository.php`: consultas de agregación con el query builder de `AppointmentModel`, `TreatmentModel`, `PatientModel` y `UserModel` (`whereBetween('date', …)`, `groupBy`, `selectRaw('extract(isodow from date)')`, `extract(hour from time)`); nunca selecciona columnas de pacientes salvo `count` | medio: conoce el esquema de tres módulos (ADR 0006) |
| Reports · Infrastructure (nuevo) | `Infrastructure/Export/OpenSpoutMonthlyReportExporter.php`: hojas Resumen, Tratamientos, Staff y Demanda; solo `StringCell` y `NumericCell` explícitos (nunca `Row::fromValues()` ni `Cell::fromValue()`, ADR 0005); encabezados en negrita sobre `FBE9EE`, primera fila de datos fija, anchos de columna, `0.0%` en tasas y "Sin datos" como texto; aviso "Mes en curso: datos hasta hoy, <fecha>." en Resumen si aplica; escribe en un archivo temporal de `sys_get_temp_dir()` | medio |
| Reports · Infrastructure (nuevo) | `Infrastructure/Http/Controllers/GetMonthlyReportController.php` y `DownloadMonthlyReportController.php` (invocables, `final`; la descarga responde `response()->download(...)->deleteFileAfterSend()`); `Infrastructure/Http/Requests/MonthlyReportRequest.php`; `Infrastructure/Http/Resources/MonthlyReportResource.php` | bajo |
| Providers | `app/Providers/AppServiceProvider.php`: enlaces de `MonthlyReportRepositoryInterface` y `MonthlyReportExporterInterface`; limitador `report-downloads` (`Limit::perMinute(10)->by($request->user()->id)` con respuesta 429 `{"error": "Demasiadas descargas. Espera un momento e inténtalo de nuevo."}`) | bajo |
| Rutas | `routes/api.php`, grupo `auth:sanctum` + `only.admin`: `GET /reports/monthly` y `GET /reports/monthly/excel` (`throttle:report-downloads`). `routes/web.php`, grupo `only.admin`: `GET /reportes` → `pages.reportes.index`, nombre `reportes.index` | bajo |
| Interfaz · menú | `resources/views/components/ui/sidebar.blade.php`: sección `'reportes'` ("Reportes", `/reportes`, ícono `chart` nuevo en el mismo estilo de trazo) al final de la pestaña del administrador | bajo |
| Interfaz · pantalla (nueva) | `resources/views/pages/reportes/index.blade.php`: `x-ui.page-hero` con selector de mes (`x-ui.input` tipo `select` con los meses desde enero de 2020 hasta el actual, el último mes completo elegido, o el de `?mes=AAAA-MM` si es válido) y "Descargar Excel"; contenedores de cifras, semana, tratamientos y staff; región de estado del marco. `resources/views/components/ui/trend.blade.php` (nuevo componente del sistema) | medio |
| Interfaz · JS (nuevo) | `resources/js/pages/reportes/index.js` (registrado en `vite.config.js`): `fetch('/api/v1/reports/monthly?month=…', { credentials: 'include' })`, pinta cifras con su indicador, la semana, las listas y los estados (vacío, mes en curso, error con "Intentar de nuevo"); actualiza `?mes=` con `history.replaceState`. Descarga: `fetch` del Excel → `Blob` → enlace temporal con el nombre de `Content-Disposition`; botón con giro y `aria-disabled`; 429 → anuncia el `error` del cuerpo (texto de CA14), resto con `announceFailure`. `resources/js/ui/trend.js` pinta el mismo marcado que `x-ui.trend` | medio |
| Datos de prueba de la interfaz | `database/seeders/UiAuditSeeder.php`: sin cambios; julio de 2026 ya tiene citas de dos doctores y tres tratamientos (las cifras de `design.md`) | — |
| Tests de navegador | `tests/Browser/screens.json`: `reportes` (`/reportes?mes=2026-07`), `reportes-sin-citas` (`?mes=2026-08`), `reportes-mes-en-curso` (mes actual) como administrador; `error-403-asistente-reportes` (`/reportes` como asistente) | bajo |
| Sistema de diseño | `docs/design/system.md` y `system.html` (T001): componente "Indicador de cambio", regla de color ampliada (verde y ámbar para tendencias), inventario con las vistas de Reportes | bajo |
| Documentación | `docs/architecture.md` (T090): módulo `Reports`, sin `Estadisticas`; `docs/specs/README.md`: quitar "Estadísticas" de pendientes; `docs/security.md`: puntos de entrada nuevos | bajo |

## Contratos y datos
Sin migraciones ni cambios de esquema.

### `GET /api/v1/reports/monthly?month=AAAA-MM`
| Actor | Respuesta |
|---|---|
| Administrador, mes válido | 200 `{"data": {...}}` (abajo) |
| Administrador, mes inválido (formato, mes 13, futuro, anterior a 2020-01) | 422 `{"message": "Elige un mes entre enero de 2020 y el mes en curso.", "errors": {"month": ["Elige un mes entre enero de 2020 y el mes en curso."]}}` |
| Asistente, doctor | 403 (`only.admin`, con cualquier dato) |
| Staff inactivo, paciente | 403 |
| Sin sesión | 401 |

```json
{"data": {
  "month": "2026-07", "label": "julio de 2026", "previous_label": "junio de 2026",
  "is_current": false, "data_until": null,
  "appointments": {
    "total":       {"value": 8, "previous": 0, "difference": 8, "trend": "favorable"},
    "completed":   {"value": 2, "previous": 0, "difference": 2, "trend": "favorable"},
    "cancelled":   {"value": 1, "previous": 0, "difference": 1, "trend": "unfavorable"},
    "unclosed":    {"value": 5, "previous": 0, "difference": 5, "trend": "unfavorable"},
    "rescheduled_pending": {"value": 0, "previous": 0, "difference": 0, "trend": "neutral"},
    "assigned_pending":    {"value": 0, "previous": 0, "difference": 0, "trend": "neutral"}
  },
  "rates": {
    "attendance":   {"value": 28.6, "previous": null, "numerator": 2, "denominator": 7, "trend": "none"},
    "cancellation": {"value": 12.5, "previous": null, "numerator": 1, "denominator": 8, "trend": "none"}
  },
  "patients": {
    "new":    {"value": 0, "previous": 0, "difference": 0, "trend": "neutral"},
    "active": {"value": 2, "as_of": "2026-10-19"}
  },
  "treatments": [{"name": "Limpieza dental de prueba", "appointments": 3, "is_top": true}, …],
  "staff": [{"name": "Daniela Prueba", "appointments": 5, "completed": 2, "is_top": true}, …],
  "demand": {
    "hours": [9, 10, 11, 12, 13, 14, 15, 16],
    "matrix": [[1,0,1,0,0,0,0], …],
    "by_weekday": [2,1,4,0,0,0,0], "by_hour": [2,1,1,2,0,0,0,1],
    "top_weekdays": [3], "top_hours": [9, 12], "total": 7
  }
}}
```
- `value` de una tasa `null` = "Sin datos"; `trend: "none"` si falta el mes anterior o alguno de
  los dos es `null`.
- `trend`: `favorable` / `unfavorable` según la tabla de `design.md` (subir es favorable en
  total, completadas, asistencia y pacientes nuevos; desfavorable en canceladas, sin cerrar y
  cancelación); diferencia 0 → `neutral`; pendientes siempre `neutral`.
- "Sin cerrar": fecha anterior a hoy (día de la clínica) y estado `asignada` o `reprogramada`. Las
  de hoy y futuras son "pendientes" (D7).
- `demand` vacío (`hours: []`) si no hay citas no canceladas; `top_*` con todos los empatados.

### `GET /api/v1/reports/monthly/excel?month=AAAA-MM`
| Actor | Respuesta |
|---|---|
| Administrador, mes válido, ≤ 10 descargas en el último minuto | 200, `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `Content-Disposition: attachment; filename="reporte-dentissa-2026-07.xlsx"` |
| Administrador, descarga 11 dentro del minuto | 429 `{"error": "Demasiadas descargas. Espera un momento e inténtalo de nuevo."}` |
| Mes inválido | 422 como arriba, sin archivo |
| Asistente, doctor, inactivo, paciente | 403 · sin sesión 401 |

Hojas del archivo: **Resumen** (título "Dentissa · Reporte de <mes>", "Generado el dd/mm/aaaa
hh:mm · Comparado con <mes anterior>", aviso de mes en curso si aplica, tabla Indicador · mes ·
mes anterior · diferencia con signo, tasas en `0.0%` o "Sin datos", pacientes activos con su
fecha), **Tratamientos** (N.º, tratamiento, citas), **Staff** (N.º, nombre, citas, completadas) y
**Demanda** (tabla hora × día con totales, como en pantalla). Sin colores de tendencia.

### Pantalla `/reportes`
| Actor | Resultado |
|---|---|
| Administrador | 200 con la vista; menú con "Reportes" |
| Asistente, doctor, paciente | vista "Sin permiso" (403, como `/usuarios`); sin "Reportes" en el menú |
| Sin sesión | redirección a `/login` |

Estados: carga (región de estado con "Cargando el reporte…"), mes sin citas ("No hubo citas en
<mes>." en la semana y listas, tasas "Sin datos"), mes en curso (aviso informativo), error
(`role="alert"`: "No se pudo cargar el reporte. No se perdió ningún dato. Revisa tu conexión e
inténtalo de nuevo." + "Intentar de nuevo"), descarga en curso ("Preparando el archivo…") y
límite (texto de CA14). Accesibilidad según `design.md` → Accesibilidad.

### Qué ve cada rol
| Pantalla · elemento | Administrador | Asistente | Doctor | Test |
|---|---|---|---|---|
| Menú · "Reportes" | sí | no | no | `StaffNavigationTest` |
| `/reportes` | pantalla | "Sin permiso" | "Sin permiso" | `ReportsScreenTest`, `WebAccessDeniedPagesTest` |

Revisar con el plugin `design` (`design:design-handoff`) antes de `/implement`.

## Estrategia de pruebas
- **Unidad (`tests/Modules/Reports/Unit`):**
  - `ReportMonthTest` y `ReportMonthPropertiesTest` (Eris): formato, límites (2020-01, mes en
    curso, futuro), `previous()` cruzando año, etiquetas en español.
  - `MonthlyReportServiceTest` con un repositorio falso: tasas con un decimal y divisor cero,
    diferencias, tendencia por indicador (dataset con la tabla de `design.md`), franjas de hora de
    la primera a la última, totales, máximos con empate, mes anterior sin datos.
  - `ReportsArchitectureTest`: `Reports/Domain` sin `Infrastructure`, facades ni Eloquent.
- **Integración (`tests/Modules/Reports/Integration`, base `ReportsIntegrationTestCase` con
  `RefreshDatabase`, `ActingAsStaff`, `ActingAsPatient`, `fakeTwilio()` al crear citas con
  teléfono):**
  - `GetMonthlyReportTest`: las cifras de `design.md` con los datos de julio de 2026 creados en el
    test (CA2–CA7); cita reprogramada cuenta en el mes de su fecha actual (CA1); mes sin citas
    (CA3); staff inactivo con citas aparece (CA6); canceladas fuera de tratamientos, carga y
    demanda; mes en curso con `is_current` y `data_until` (CA9).
  - `MonthlyReportValidationTest`: dataset `2026-13`, `2026-7`, `julio`, `2019-12`, mes siguiente
    al actual, vacío → 422 con el mensaje exacto (CA11); también en el Excel, sin archivo.
  - `ReportsAccessControlTest`: cada endpoint y `/reportes` por actor (asistente, doctor, inactivo,
    paciente, sin sesión), con mes válido e inválido (CA10, TM1, TM2).
  - `DownloadMonthlyReportTest`: cabeceras y nombre; lee el archivo con el lector XLSX de OpenSpout:
    cuatro hojas con sus nombres, cifras iguales a las del JSON (`ExcelMatchesScreenTest`, CA8),
    aviso de mes en curso (CA9), `0.0%` en tasas.
  - `MonthlyReportNoPatientDataTest`: con pacientes de nombre, teléfono, email y datos médicos
    conocidos, ni el JSON ni ninguna celda del archivo contiene esos valores (CA12, TM3).
  - `ExcelFormulaInjectionTest`: tratamiento `=HYPERLINK("x")`, staff `+cmd`, `-1+1`, `@SUM(A1)` →
    en el XML de la hoja son `inlineStr` y no hay ningún elemento `<f>` (CA13, TM4).
  - `DownloadThrottleTest`: 10 descargas 200 y la 11.ª 429 con el texto exacto; tras
    `travel(61)->seconds()` vuelve a 200; `GET /reports/monthly` no se limita con 11 seguidas
    (CA14, TM5).
  - `MonthlyReportPerformanceTest`: 2 000 citas en un mes → JSON y Excel en menos de 3 s cada uno
    (RNF).
  - `UnexpectedReportErrorTest`: el repositorio lanza → 500 `UnexpectedErrorResponse` sin detalle
    (P7).
  - `ReportsScreenTest`: `/reportes` como administrador con `?mes=` válido, inválido y sin él (mes
    elegido en el selector), script registrado, entrada del menú.
  - Se actualizan: `RolePermissionsTest` (dos permisos), `StaffNavigationTest` (Reportes solo para
    el administrador), `WebAccessDeniedPagesTest` (`/reportes` en las pantallas de administrador).
- **Navegador (`npm run test:ui`):** entradas nuevas de `screens.json` a 390, 768 y 1440 px con los
  datos de `ui:audit-data` (julio y agosto de 2026, y el mes en curso); se ejecutan dos veces en
  `/implement`.
- **Regresión:** suites de Reports, Core, Users y Appointments en serie.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: asistente o doctor piden el reporte o el Excel llamando a la API | A01:2025 | `GET /reports/monthly*` | `only.admin` + `assertCan('reports.view' / 'reports.export')` | `ReportsAccessControlTest` |
| TM2 | Spoofing: paciente, staff inactivo o visitante sin sesión acceden al reporte | A01:2025 | API y `/reportes` | `auth:sanctum` + `only.admin` (activo, rol administrador) | `ReportsAccessControlTest`, `ReportsScreenTest` |
| TM3 | Information disclosure: el archivo o el JSON sacan datos personales de pacientes fuera del sistema | A01:2025 · LFPDPPP | Repositorio y exportador | El repositorio solo devuelve `count`, nombres de tratamientos y nombres del staff; el tipo `MonthlyReport` no tiene campos de pacientes | `MonthlyReportNoPatientDataTest` |
| TM4 | Tampering / ejecución: un nombre de tratamiento o de staff con fórmula se ejecuta al abrir el archivo (inyección de fórmulas) | A05:2025 (inyección) | Exportador | Solo `StringCell` explícito para textos; prohibidas las fábricas que deducen el tipo (ADR 0005) | `ExcelFormulaInjectionTest` |
| TM5 | Denial of service: descargas en masa con una sesión válida o robada | A06:2025 (diseño inseguro) · API4:2023 | `GET /reports/monthly/excel` | Limitador `report-downloads` de 10 por minuto por usuario, además del límite general de la API | `DownloadThrottleTest` |
| TM6 | Tampering: un mes manipulado (inyección en el parámetro, rango enorme, fecha futura) | A05:2025 (inyección) | `month` | `MonthlyReportRequest` con expresión regular y rango; `ReportMonth::create()`; el rango de fechas lo construye el servidor | `MonthlyReportValidationTest`, `ReportMonthPropertiesTest` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: "Reportes" en el menú, último mes completo por defecto, cambiar de mes; cita reprogramada en el mes de su fecha actual, zona de la clínica | Menú; ruta web; vista y JS; `ReportMonth`; repositorio por `date` | `ReportsScreenTest`, `StaffNavigationTest`, `GetMonthlyReportTest` (reprogramada); ui-audit `reportes` · verificación manual: cambiar de mes en `/reportes` a 390 y 1440 px contra `opcion-c.html` |
| CA2: citas por estado con mes anterior y diferencia | Repositorio `appointmentCounts`; servicio; recurso; vista | `MonthlyReportServiceTest`, `GetMonthlyReportTest`; ui-audit `reportes` |
| CA3: tasas con un decimal; "Sin datos" con divisor cero o mes sin citas | `Rate`; servicio; vista | `MonthlyReportServiceTest`, `GetMonthlyReportTest` (mes sin citas); ui-audit `reportes-sin-citas` |
| CA4: pacientes nuevos con comparación y activos | Repositorio `newPatients`, `activePatients` | `GetMonthlyReportTest` |
| CA5: tratamientos de mayor a menor, empate por nombre, sin canceladas ni ceros | Repositorio `treatmentRanking` | `GetMonthlyReportTest` |
| CA6: carga por staff con citas en el mes, aunque esté inactivo | Repositorio `staffLoad` | `GetMonthlyReportTest` |
| CA7: demanda por día y por hora con máximos destacados | Repositorio `demandByWeekdayAndHour`; servicio | `MonthlyReportServiceTest` (empates), `GetMonthlyReportTest`; ui-audit `reportes` · verificación manual: semana a 390 px (siete columnas sin desborde) contra `opcion-c.html` |
| CA8: Excel con hojas, nombre y mismas cifras; sin aviso de reparación | Exportador; controlador de descarga; JS de descarga | `DownloadMonthlyReportTest`, `ExcelMatchesScreenTest` · verificación manual: abrir `reporte-dentissa-2026-07.xlsx` en Excel y LibreOffice sin aviso de reparación |
| CA9: mes en curso con aviso; meses futuros no elegibles | `ReportMonth`; selector de mes; exportador | `GetMonthlyReportTest`, `DownloadMonthlyReportTest`, `ReportsScreenTest` (sin meses futuros en el selector); ui-audit `reportes-mes-en-curso` |
| CA10 (abuso): no administradores rechazados, sin archivo, sin entrada de menú | Rutas `only.admin`; permisos; menú | `ReportsAccessControlTest`, `StaffNavigationTest`, `WebAccessDeniedPagesTest`; ui-audit `error-403-asistente-reportes` |
| CA11 (abuso): mes inválido → mensaje en español, sin archivo ni error interno | `MonthlyReportRequest`; `ReportMonth` | `MonthlyReportValidationTest`, `ReportMonthPropertiesTest` |
| CA12 (abuso): el archivo no contiene datos de pacientes | Repositorio y exportador | `MonthlyReportNoPatientDataTest` |
| CA13 (abuso): nombres con `=`, `+`, `-`, `@` como texto | Exportador | `ExcelFormulaInjectionTest` |
| CA14 (abuso): más de 10 descargas por minuto se rechazan con el mensaje; la pantalla no se limita | Limitador; JS de descarga | `DownloadThrottleTest` · verificación manual: el aviso aparece en la región de estado con el texto de CA14 |
| TM1: asistente o doctor en la API | Rutas; permisos | `ReportsAccessControlTest` |
| TM2: paciente, inactivo o sin sesión | Rutas | `ReportsAccessControlTest`, `ReportsScreenTest` |
| TM3: datos de pacientes en la salida | Repositorio; exportador | `MonthlyReportNoPatientDataTest` |
| TM4: inyección de fórmulas | Exportador | `ExcelFormulaInjectionTest` |
| TM5: descargas en masa | Limitador | `DownloadThrottleTest` |
| TM6: mes manipulado | FormRequest; `ReportMonth` | `MonthlyReportValidationTest`, `ReportMonthPropertiesTest` |
| RNF: < 3 s con 2 000 citas | Consultas de agregación | `MonthlyReportPerformanceTest` |
| RNF: cálculos en el servidor, mismas cifras en pantalla y archivo | Servicio único | `ExcelMatchesScreenTest` |
| RNF: permisos en el servidor, denegado por actor | Rutas y casos de uso | `ReportsAccessControlTest` |
| RNF: navegador a 390, 768 y 1440 px, contraste, foco y teclado; listas en orden con lector de pantalla | Vista; `x-ui.trend` | `npm run test:ui` · verificación manual: recorrido con lector de pantalla (NVDA) de cifras, semana y listas |
| RNF: errores sin detalles internos y logs sin datos de pacientes | Controladores; log `report.export_failed` | `UnexpectedReportErrorTest`; manual: `/review` de `Log::` |

## Observabilidad
- Logs nuevos: `report.export_failed` (nivel `error`) si falla la generación del archivo, con
  `month` y la clase de la excepción; sin datos de pacientes ni el mensaje del error. Con el
  `request_id` del contexto cuando exista (P14, objetivo 5).
- Eventos de auditoría: la spec pide los accesos denegados de CA10; el registro aún no existe
  (P14 ❌ aceptado). Punto de emisión: el render de `AuthorizationException` y `OnlyAdmin`, ya
  identificados en 014. Ver o descargar el reporte no es lectura de datos sensibles.
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: como administrador, `/reportes` muestra el mes anterior y la
  descarga produce el archivo; como asistente, `GET /api/v1/reports/monthly` da 403; en Loki, sin
  500 en `/api/v1/reports*` ni `report.export_failed`.

## Descubribilidad
No aplica: `/reportes` es una ruta del panel, privada y no indexable; la spec no crea ni cambia
rutas públicas.

## Rollout
- Feature flag: no aplica. Es una pantalla nueva solo para el administrador, no cambia ningún
  flujo existente y el riesgo es bajo (D9).
- Orden de despliegue: una sola imagen con `deploy.sh` (`composer install` de la imagen trae
  OpenSpout); sin migraciones.
- Compatibilidad: no toca esquema ni contratos existentes; la versión anterior no tiene las rutas
  nuevas y no se rompe nada durante el cambio de contenedor.
- Rollback: `docker/prod/rollback.sh` a la versión anterior; no hay datos que revertir.
- Métricas a vigilar tras el deploy: 500 y 429 en `/api/v1/reports*` en Loki; `report.export_failed`.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 → [ADR 0005](../../adr/0005-openspout-para-excel.md)** OpenSpout 5.12.0 para el `.xlsx`, en
  lugar de PhpSpreadsheet o de escribir el XML a mano; verificada el 2026-10-19 y comprobada con
  una prueba de concepto el 2026-10-19 (decisión del usuario, 2026-10-19).
- **D2 → [ADR 0006](../../adr/0006-modulo-reports-de-solo-lectura.md)** Módulo nuevo `Reports` de
  solo lectura; se retira `Estadisticas` de la documentación (decisión del usuario, 2026-10-19).
- **D3** P14 ❌ aceptado para los accesos denegados (decisión del usuario, 2026-10-19).
- **D4** Dos endpoints (JSON y Excel) que comparten el mismo `MonthlyReport`, en lugar de generar
  el Excel en el navegador: el archivo no depende de JavaScript y las cifras salen del mismo
  cálculo (RNF, P13).
- **D5** La descarga se hace con `fetch` y `Blob`, no con un enlace directo, para que un 429, 403 o
  422 se anuncie en la región de estado en lugar de abrir un JSON en una pestaña.
- **D6** La favorabilidad (`trend`) se calcula en el servidor y llega en el JSON (P13); la
  pantalla solo traduce `favorable`/`unfavorable`/`neutral`/`none` a colores y textos.
- **D7** "Sin cerrar" se cuenta por día: una cita de hoy aún no está sin cerrar aunque su hora haya
  pasado. La spec dice "fecha ya pasada" y la agenda trabaja por días.
- **D8** El mes se pide como `month=AAAA-MM` en la API y `?mes=AAAA-MM` en la pantalla (rutas web en
  español, `AGENTS.md`); un `?mes=` inválido en la pantalla se ignora y muestra el último mes
  completo.
- **D9** Sin feature flag: pantalla nueva solo para el administrador, sin flujos existentes que
  cambien.
- **D10** El archivo se genera en el momento, en un temporal que se borra al enviarlo; no se guarda
  ni se cachea (siempre los datos de ese instante; 2 000 citas en < 3 s).
- **D11** La spec es grande (14 criterios) y se mantiene junta (decisión del usuario, 2026-10-19).

## Impacto en arquitectura
- `docs/architecture.md`: tabla de módulos (`Reports` en lugar de `Estadisticas`), dependencias
  entre módulos (Reports → Appointments, Patients, Users, solo lectura) y dependencia OpenSpout.
- `docs/security.md`: puntos de entrada `GET /api/v1/reports/monthly*` y `/reportes` (solo
  administrador, límite de descargas).
- `docs/specs/README.md`: retirar "Estadísticas" de "Pendientes sin spec".

## Riesgos y mitigaciones
- **Inyección de fórmulas por una fábrica de OpenSpout** → prohibidas `Row::fromValues()` y
  `Cell::fromValue()` en el exportador; `ExcelFormulaInjectionTest` lee el XML y falla si aparece
  un `<f>`.
- **Datos de pacientes en el archivo por una consulta mal hecha** → el repositorio devuelve tipos
  sin campos de pacientes y `MonthlyReportNoPatientDataTest` busca valores conocidos en el JSON y
  en cada celda.
- **El esquema de otros módulos cambia y rompe `Reports`** → tests de integración con datos reales
  en las tablas (ADR 0006).
- **Rendimiento con meses grandes** → agregación en PostgreSQL (nunca cargar filas de citas) y
  `MonthlyReportPerformanceTest` con 2 000 citas.
- **Zona horaria**: `appointments.date` ya se guarda como fecha local de la clínica (017) y
  `patients.created_at` en la zona de la aplicación; los tests fijan `now()` en
  `America/Mexico_City` cerca de la medianoche del cambio de mes.
- **Pest en paralelo con timeouts de PostgreSQL** (roadmap, 017/R12) → la regresión se corre en
  serie.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-19 | Creación en `draft` | /plan 019 |
| 2026-10-19 | Aprobado | Aprobación del usuario |
