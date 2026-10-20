---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-10-20
spec_sha: 3531bd8adac9
plan_sha: 9e3191131bc2
tasks_sha: acce36d30b7b
---

# Análisis · 019 Reportes mensuales

## Resumen
`fail`. Hay un hallazgo CRÍTICO: P2 pide tests de propiedades para cada value object nuevo y solo
`ReportMonth` los tiene. Hay además cuatro ALTOS: la línea base de `ui-audit` llega tarde, los
estados de error y de límite no tienen prueba de navegador, el selector de mes no existe como
componente y varias tareas se cierran con tests que dependen de tareas posteriores. La cobertura de
criterios y amenazas está completa. Lo demás son precisiones del contrato y del sistema de diseño.
Análisis hecho por un subagente independiente (perfil `critical`, Opus); cada hallazgo se comprobó
en el archivo citado.
Conteo: CRÍTICA 1 · ALTA 4 · MEDIA 14 · BAJA 11

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 |
| Amenazas con control y test | 6 / 6 (en TM1 no se ejercita `assertCan`, A7) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P2) | CRÍTICA | plan → Constitution Check P2; T032, T033 | P2 exige tests de propiedades (Eris) para todo value object nuevo. `AppointmentCounts`, `Rate`, `Comparison`, `RankedItem` y `MonthlyReport` no tienen test propio; T032 se da por hecha cuando "compilan" | Cada value object nuevo tiene su test de unidad y de propiedades antes que el código (`Rate`: un decimal, `null` con divisor 0, entre 0 y 100; `Comparison`: diferencia = actual − anterior, tendencia por tipo de indicador). La otra opción es una excepción aceptada por el usuario. `/plan --fix`, `/tasks --fix` |
| A2 | Tareas (orden) | ALTA | T024, T044–T049 | `checks-composition.mjs` falla con "sin línea base" a 1440 px en toda entrada que no esté en `baseline-1440.json`. T049 escribe la línea base después de T048, así que T044–T048 no pueden cumplir "`ui-audit` pasa". Además, la falla esperada en T024 no es "404": `ui-audit` no mira el código HTTP, y antes de T044 `error-403-asistente-reportes` tampoco pasa | Toda tarea que exige que `ui-audit` pase en una entrada nueva tiene antes la línea base de esa entrada, con el diff revisado. El motivo de falla de T024 se comprueba en local. `/tasks --fix` |
| A3 | Constitución (P2) / Cobertura | ALTA | T024, T045, T046; plan → Trazabilidad CA8, CA14 | Varios estados solo se ven en el navegador: error con "Intentar de nuevo", carga, "Preparando el archivo…", aviso del 429 y descarga por `Blob`. Ninguna entrada de `screens.json` los prueba (`ui-audit` ya puede sustituir respuestas) | Hay entradas de `ui-audit` para el 500 al cargar y para el 429 al descargar, con el texto anunciado y el foco. Fallan antes de T045/T046 y pasan después. `/plan --fix`, `/tasks --fix` |
| A4 | Inconsistencia (código) | ALTA | plan → Interfaz · pantalla; design.md → Componentes; T044 | El selector de mes se pide como "`x-ui.input` tipo `select`", pero `input.blade.php` solo pinta `<input>`. /implement tendría que decidir un cambio del sistema que T001 no recoge | Decisión del usuario (abajo). Después, `/design-spec 019 --fix` y `/plan --fix`; `OnlyDesignTokensTest` y `ui-audit` (44 px, etiqueta) lo comprueban |
| A5 | Tareas (dependencias) | ALTA | T035, T036, T037, T040, T041 | Varias tareas solo se cierran cuando pase una tarea posterior ("tras T038", "tras T041"). T041 exige T016 completo, pero los casos de la ruta web llegan en T044 | Cada tarea se cierra con un criterio comprobable en ese momento. T041 se acota a T016 en su endpoint. `/tasks --fix` |
| A6 | Tareas (dependencias) | MEDIA | T038, T041, T044, T045 | Varias tareas exigen tests de los que no dependen: T038 de T021 y T022; T041 de T021; T044 y T045 de T024 | Se declara como dependencia cada test que exige el "hecho cuando". `/tasks --fix` |
| A7 | Cobertura (P5, TM1) | MEDIA | plan TM1; T016, T035 | `only.admin` rechaza a todos los actores no autorizados antes del caso de uso, así que ningún test comprueba que `assertCan('reports.view'/'reports.export')` se llame primero | Un test de cada caso de uso con un actor sin permiso afirma `AuthorizationException` sin llamar al repositorio ni al exportador. `/plan --fix`, `/tasks --fix` |
| A8 | Inconsistencia / P13 | MEDIA | plan → Contratos (`rates`); design.md; hoja Resumen | Las tasas no traen `difference` en el JSON, pero la pantalla y el Excel muestran la diferencia; sin ella la pantalla tendría que calcularla | El contrato fija la diferencia de las tasas (valor, puntos porcentuales y redondeo), afirmada en `GetMonthlyReportTest` y `ExcelMatchesScreenTest`. `/plan --fix` |
| A9 | Inconsistencia (código) | MEDIA | plan → Reports · Application; T031, T034, T035 | Las firmas reciben `CarbonImmutable $today`, pero `now()` devuelve `Carbon` mutable (no hay `Date::use`) → `TypeError` | La firma y lo que se le pasa coinciden; `GetMonthlyReportTest` con `travelTo()` lo detecta. `/plan --fix` |
| A10 | Constitución (P13) | MEDIA | plan → Interfaz · pantalla, D8; T044 | La vista calcula sola la lista de meses, el último mes completo y si `?mes=` es válido: la regla de rango quedaría en tres sitios | La lista de meses y el mes elegido salen del servidor con `ReportMonth`. Lo verifica `ReportsScreenTest` (`?mes=` válido, `2026-13`, `2030-01`). `/plan --fix` |
| A11 | Sistema de diseño | MEDIA | design.md → Composición y Accesibilidad; T001, T044 | `x-ui.stat` no usa `dl/dt/dd`, está pensado para tres por fila a 390 px y no tiene sitio para el indicador; el diseño pide dos columnas con indicador | Decisión del usuario (abajo); el cambio va a T001 y `system.md`. `ui-audit` lo comprueba |
| A12 | Sistema de diseño | MEDIA | plan → Interfaz · JS; T046; system.md → Región de estado | El sistema dice que `status.js` usa un texto propio por tipo de error (para el 429, `FAILURES.busy`) y solo deja pasar los de validación. El plan anuncia el texto del servidor, una excepción no registrada | Decisión del usuario (abajo), registrada en el sistema y probada en `ui-audit` (A3) |
| A13 | Terminología | MEDIA | spec CA2 ("+6 respecto a septiembre"); design.md/opcion-c ("+8 vs junio", "junio: Sin datos"); T043 ("+2 frente a junio", "Sin comparación") | El texto del indicador tiene tres redacciones, y el caso sin comparación dos | Un único texto fijado en design.md y afirmado en un test (A19). `/design-spec 019 --fix` |
| A14 | Inconsistencia | MEDIA | design.md → Decisiones (punto decimal); tasks → convenciones, T011 | El diseño usa "28.6 %" y las tareas "28,6 %" | Las tareas usan el formato de design.md. `/tasks --fix` |
| A15 | Ambigüedad | MEDIA | plan → Excel `0.0%`; T017 | No se dice si la celda guarda la fracción redondeada o la exacta, ni el modo de redondeo (6.25 → 6.3 o 6.2) | El contrato fija el valor de la celda y el redondeo, con un caso x.x5 en `MonthlyReportServiceTest` y `ExcelMatchesScreenTest`. `/plan --fix` |
| A16 | Ambigüedad | MEDIA | spec CA6; T014, T037 | Falta el orden de la carga del staff en caso de empate. Tampoco se sabe si aparece un miembro cuyas citas del mes están todas canceladas | Decisión del usuario (abajo), con casos en `GetMonthlyReportTest`. `/plan --fix` |
| A17 | Seguridad | MEDIA | plan → Modelo de amenazas; T043, T045 | Los nombres de tratamientos y del staff llegan al DOM por JS, y `?mes=` se refleja en la vista, pero solo se modela la inyección en Excel | Amenaza de XSS almacenado o reflejado con su control (insertar como texto) y un test con un nombre `<img onerror>`. `/plan --fix`, `/tasks --fix` |
| A18 | Constitución (P14) | MEDIA | plan → P14 y Observabilidad | La desviación aceptada solo cubre el registro de auditoría. `report.export_failed` tampoco tiene correlación `X-Request-Id` (OB3), que también es P14 | Decisión del usuario (abajo); la fila ❌ aceptada cita OB3 sin declararlo mitigado. `/plan --fix` |
| A19 | Constitución (P2) | MEDIA | T043 | `x-ui.trend` y `trend.js` no tienen un test que falle antes; nada comprueba que Blade y JS pinten lo mismo | Un test que falla antes (vista Blade con las cuatro variantes, o `ui-audit`). `/tasks --fix` |
| A20 | Ambigüedad | BAJA | plan → T031/T035; `bootstrap/app.php` | No se define qué pasa si `ReportMonthException` llega al caso de uso tras pasar el FormRequest (cambio de mes entre los dos relojes): hoy sería un 500 | Esa excepción da el mismo 422, o el FormRequest y `ReportMonth` usan el mismo "hoy". `/plan --fix` |
| A21 | Ambigüedad | BAJA | plan → contrato `demand` | Se mezclan índices: base 0 en `by_weekday` y `matrix`, ISO 1–7 en `top_weekdays` y horas reales en `top_hours` | La convención queda escrita en el contrato y fijada en `GetMonthlyReportTest`. `/plan --fix` |
| A22 | Tareas | BAJA | T050 | El comando no dice dónde dejar las capturas | El comando de T050 genera las capturas en `docs/design/capturas/`. `/tasks --fix` |
| A23 | Duplicación | BAJA | T016, T023 | El 403 de `/reportes` se prueba en dos archivos | Una sola fuente para la ruta web. `/tasks --fix` |
| A24 | Inconsistencia | BAJA | spec CA14; plan TM5 | "Ver el reporte en pantalla no se limita", pero el JSON tiene el límite general `api` (100/min) | Significa "sin límite propio"; se precisa en el plan |
| A25 | Documentación | BAJA | T051, T090 | Faltan el mapa de permisos de `security.md` y la línea de `architecture.md` que cita `app/Modules/Estadisticas` | Incluirlos en esas tareas. `/tasks --fix` |
| A26 | Constitución (terminado) | BAJA | T048 | La definición de terminado pide `pest --parallel`; T048 corre en serie por 017/R12 | CI sigue corriendo `--parallel`; la serie local es adicional |
| A27 | Ambigüedad | BAJA | spec → Decisiones (7 `supuesto`); design.md (4) | Los supuestos del agente fijan comportamiento (2020-01, 10/min, 3 s, tasas, staff inactivo, franjas, empates, punto decimal, Excel sin color) | Decisión del usuario (abajo) |
| A28 | Terminología | BAJA | roadmap (tablero) frente a spec CA2 | "Sin cerrar" está definido de dos formas | El roadmap alinea la definición con CA2 |
| A29 | Cobertura | BAJA | plan → "Revisar con el plugin `design` antes de /implement" | Ese paso no tiene tarea | Se retira la frase o se añade la tarea. `/plan --fix` |
| A30 | Cobertura | BAJA | T001 | El "hecho cuando" no comprueba los cambios de `system.md` y `system.html` | Revisión anotada de `system.html` con las cuatro variantes y la regla. `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas por el usuario el 2026-10-20:
- **A1:** se añaden tests de unidad y de propiedades (Eris) para `AppointmentCounts`, `Rate`,
  `Comparison`, `RankedItem` y `MonthlyReport`, antes que el código → `/plan --fix`, `/tasks --fix`.
- **A4 y A11:** se amplía el sistema: `x-ui.select` nuevo (mismo estilo que `x-ui.input`, 44 px) y
  una variante de `x-ui.stat` con `dl/dt/dd`, dos columnas y sitio para el indicador; T001 los
  registra → `/design-spec 019 --fix`, `/plan --fix`, `/tasks --fix`.
- **A12:** el 429 de la descarga muestra el texto de CA14, como tipo propio en `status.js`
  registrado en el sistema → `/design-spec 019 --fix`, `/plan --fix`.
- **A16:** empate de la carga del staff ordenado por nombre; quien solo tuvo citas canceladas no
  aparece → `/plan --fix` (casos en `GetMonthlyReportTest`).
- **A18:** la desviación aceptada de P14 se amplía a la correlación; OB3 queda pendiente para el
  objetivo 5, sin declararse mitigado → `/plan --fix`.
- **A27:** supuestos de la spec y de `design.md` confirmados sin cambios → se marcan
  `(confirmado por el usuario, 2026-10-20)`.

## Aceptados
Aceptados por el usuario el 2026-10-20:
- **A24** → plan: "no se limita" significa sin límite propio; el JSON sigue con el límite general `api`.
- **A26** → nota de T048: CI sigue corriendo `pest --parallel`; la ejecución en serie es adicional, en local.
- **A28** → roadmap: alinear la definición de "sin cerrar" del tablero de inicio con CA2 (asignadas o reprogramadas con fecha pasada).
