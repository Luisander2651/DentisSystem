---
result: fail
round: 2
mode: full
constitution_version: 1.2.0
date: 2026-10-20
spec_sha: 8b6e01c2b1aa
plan_sha: afa32e145fe4
tasks_sha: ab9515f25294
---

# Análisis · 019 Reportes mensuales

## Resumen
`fail`. De los 30 hallazgos de la ronda 1, 25 quedaron resueltos y A24 está recogido en el plan.
A2, A3 y A10 se resolvieron pero introdujeron problemas nuevos, y A23 sigue abierto en parte.
Los dos ALTOS nuevos están en la prueba de navegador. La herramienta no puede ejecutar los flujos
que pide T024: un 429 servido desde la prueba cuenta como límite de la API, no hay respuesta
retrasada ni expectativas de ausencia, de variable global o de foco, y no hay paso para elegir una
opción por posición. Además, T045 y T049 exigen que pasen flujos que solo pasan tras T046. No hay
CRÍTICOS. Hizo el análisis un subagente independiente en modo completo, porque `tasks.md` cambió
más del 40 % (perfil `critical`, Opus). Cada hallazgo se comprobó en el archivo citado
(`ui-audit.mjs:133-137` y `:549-567`).
Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 3 · BAJA 9 (incluido A23)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 (la parte de navegador de CA14 depende de A31 y A32) |
| Amenazas con control y test | 7 / 7 (la parte almacenada de TM7 depende de A31) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | plan P2, D12; T010, T025–T028 antes de T031–T033 (detalle menor en A40) |
| A2 | ALTA | resuelto con efecto nuevo (→ A32) | convención de línea base y motivo de T024 corregidos; T045 y T049 exigen flujos que fallan hasta T046 |
| A3 | ALTA | resuelto con efecto nuevo (→ A31) | T024 añade los flujos; la herramienta de T004 no puede ejecutarlos |
| A4 | ALTA | resuelto | design.md `x-ui.select`; plan D13; T001, T052, T053 |
| A5 | ALTA | resuelto | convención "Piezas antes del cableado"; T035, T039 con T029; T041 acotada |
| A6 | MEDIA | resuelto | dependencias de T038, T041, T044, T045 |
| A7 | MEDIA | resuelto | plan P5; T029 |
| A8 | MEDIA | resuelto | contrato `rates.difference`; D16; T014, T017 |
| A9 | MEDIA | resuelto | `CarbonInterface`, D17; T010 |
| A10 | MEDIA | resuelto con efecto nuevo (→ A35) | `ShowReportsPageController` usa `ReportMonth` sin caso de uso |
| A11 | MEDIA | resuelto | contador comparado; T001, T052, T053 |
| A12 | MEDIA | resuelto | design.md, D14, T054, T046 |
| A13 | MEDIA | resuelto | tabla única de textos en design.md; queda el ejemplo de la spec (A37) |
| A14 | MEDIA | resuelto | punto decimal en las convenciones de tareas |
| A15 | MEDIA | resuelto | `Rate` mitad hacia arriba; Excel con `0.286`; T026, T014, T017 |
| A16 | MEDIA | resuelto | D15; T014, T037 |
| A17 | MEDIA | resuelto | TM7; T023 (reflejado); la parte almacenada depende de A31 |
| A18 | MEDIA | resuelto | P14 con OB3.a; T091 |
| A19 | MEDIA | resuelto | T052 y T043 |
| A20 | BAJA | resuelto | contrato 422; T015, T036, T041 |
| A21 | BAJA | resuelto | contrato `demand`; T014 |
| A22 | BAJA | resuelto | T050 con `--screenshots` (`ui-audit.mjs:396`) |
| A23 | BAJA | abierto (parcial) | T016 prueba `/reportes` para paciente e inactivo, y `WebAccessDeniedPagesTest.php:53-55` ya recorre `DENIED_ADMIN_SCREENS` para ellos |
| A24 | BAJA | aceptado, recogido | plan → Providers |
| A25 | BAJA | resuelto | T051, T090 |
| A26 | BAJA | aceptado | → nota de T048 |
| A27 | BAJA | resuelto | supuestos confirmados en spec y design.md |
| A28 | BAJA | aceptado | → roadmap |
| A29 | BAJA | resuelto | plan, sin revisión con el plugin |
| A30 | BAJA | resuelto | T001 con verificación manual |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A31 | Inconsistencia (código) / Cobertura (P2, TM7, CA14) | ALTA | T004, T024; plan → Tests de navegador, D19; `ui-audit.mjs:133-155, 549-567, 638-647`; `checks-dialogs.mjs` | La herramienta que deja T004 no puede ejecutar los flujos de T024. (1) Todo 429 del origen, también uno servido desde la prueba, activa `rateLimited`, y la entrada se repite con esperas de 61 s hasta "limitada por la API". (2) `intercept` responde al momento, así que un estado intermedio ("Preparando el archivo…", `aria-disabled`) no se puede observar. (3) Las expectativas de un flujo no permiten afirmar ausencia de elementos, una variable global ni que un control se alcanza con Tab. (4) No hay paso para elegir una opción por posición (A43). Además, el plan dice que hoy los flujos solo corren en diálogos, pero `runFresh` ya los corre en cualquier entrada | Antes de T024 la herramienta permite que un flujo: responda un 429 sin contarlo como límite de la API; deje una respuesta pendiente mientras comprueba el estado intermedio; afirme ausencia, una variable global y que un control es alcanzable con Tab; y elija una opción por posición. Se comprueba con `npm run test:ui` local completo sin cambios en las entradas existentes, y con cada flujo nuevo fallando por el motivo esperado antes de T045/T046. `/plan --fix`, `/tasks --fix` |
| A32 | Tareas (criterio comprobable) | ALTA | T045, T049, T046 | Los flujos de descarga van en la entrada `reportes`, y T045 y T049 exigen que `reportes` pase cuando esos flujos solo pasan tras T046 | Cada "hecho cuando" se puede cumplir al cerrar la tarea: los flujos de descarga van en una entrada propia que se exige desde T046, o la falla esperada queda nombrada. Se comprueba anotando la salida de `ui-audit`. `/tasks --fix` |
| A33 | Ambigüedad / Sistema de diseño | MEDIA | design.md → Componentes y Accesibilidad; plan → Estados; `opcion-c.html:386-387`; `status.js` | La región de estado solo escribe texto y borra los errores a los 12 s, pero la composición C pinta un bloque `role="alert"` con "Intentar de nuevo" dentro. No está fijado dónde va el botón ni si el error caduca | design.md y plan fijan que el error de carga es un bloque de la página (como en la composición C) que no desaparece hasta reintentar, y qué anuncia la región de estado. Lo comprueba el flujo "error de carga" (texto, botón alcanzable con Tab tras más de 12 s). `/design-spec --fix`, `/plan --fix` |
| A34 | Ambigüedad (P13, CA8) | MEDIA | plan → Exportador y hojas; T017, T040 | No está fijado si la diferencia (conteos y tasas) es un número con formato de signo o un texto "+3.2", ni el signo negativo | El contrato fija tipo y formato de las celdas de diferencia, positivas y negativas; lo afirma `DownloadMonthlyReportTest` con un caso negativo. `/plan --fix` |
| A35 | Constitución (P3) | MEDIA | plan → `ShowReportsPageController`, Constitution Check P3; T044 | El controlador de la pantalla llama al dominio sin caso de uso y la fila P3 no lo justifica | La fila P3 declara y justifica la excepción (la vista no lleva datos y la protege `only.admin`), o la pantalla pasa por un caso de uso. Lo comprueban `ReportsScreenTest` y `ReportsArchitectureTest`. `/plan --fix` |
| A36 | Ambigüedad (P13) | BAJA | contrato `previous_label` ("junio de 2026") frente a design.md ("+8 vs junio"); T043 | El indicador muestra el mes sin el año y el JSON solo trae la etiqueta con año | El contrato trae la etiqueta corta, o design.md fija que se usa la larga. Lo afirman `GetMonthlyReportTest` y `UiComponentsTest`. `/plan --fix` |
| A37 | Terminología | BAJA | spec CA2 ("+6 respecto a septiembre") | El ejemplo de la spec conserva una redacción que se retiró del diseño | Aceptar: el ejemplo es ilustrativo y design.md manda en los textos |
| A38 | Cobertura de riesgos | BAJA | plan → TM2, Riesgos | TM2 se apoya en `auth:sanctum`, que acepta tokens de pacientes (RS1.c pendiente, spec 013). El control real es `OnlyAdmin`, pero RS1.c no se cita como fuera de alcance | El plan cita RS1.c fuera de alcance (destino: spec 013) y no mitigado; `ReportsAccessControlTest` (paciente → 403) lo cubre. `/plan --fix` |
| A39 | Tareas (verificación) | BAJA | T053 | `x-ui.stat` se usa en 9 vistas y la regresión solo corre `--only agenda` | El "hecho cuando" cubre todas las pantallas con `x-ui.stat`. `/tasks --fix` |
| A40 | Cobertura (P2) | BAJA | plan → Estrategia; T028 | Falta el test de unidad de `MonthlyReport` | T028 lo añade. `/tasks --fix` |
| A41 | Ambigüedad | BAJA | T017 (D10) | En un test HTTP, `deleteFileAfterSend()` solo borra al enviar el contenido, así que el test no puede comprobarlo tal como está escrito | T017 envía el contenido de la respuesta y comprueba después que el temporal no existe. `/tasks --fix` |
| A42 | Cobertura | BAJA | plan → `ReportsScreenTest` ("script registrado"); T023 | T023 no comprueba que la vista cargue su script | T023 lo incluye. `/tasks --fix` |
| A43 | Ambigüedad | BAJA | T024 (`reportes-mes-en-curso`); `ui-audit.mjs:549-567` | No hay paso para elegir la primera opción del selector | Se resuelve con A31 |

## Decisiones pendientes del usuario
Resueltas por el usuario el 2026-10-20:
- **A33:** el error de carga es un bloque `role="alert"` de la página, en lugar del reporte, con
  "Intentar de nuevo" dentro, y se queda hasta que se reintente (como en la composición C). La
  región de estado solo anuncia el texto → `/design-spec 019 --fix`, `/plan --fix`.
- **A35:** se acepta que `ShowReportsPageController` llame a `ReportMonth` sin caso de uso, con la
  excepción justificada en la fila P3 (la vista no lleva datos del reporte, solo la lista de meses,
  y la protege `only.admin`) → `/plan --fix`.

## Aceptados
Aceptados por el usuario el 2026-10-20:
- **A24** → plan: "no se limita" significa sin límite propio; el JSON sigue con el límite general `api`.
- **A26** → nota de T048: CI sigue corriendo `pest --parallel`; la ejecución en serie es adicional, en local.
- **A28** → roadmap: alinear la definición de "sin cerrar" del tablero de inicio con CA2 (asignadas o reprogramadas con fecha pasada).
- **A37** → spec: el ejemplo de CA2 ("+6 respecto a septiembre") es ilustrativo; los textos del indicador los fija `design.md` (aceptado el 2026-10-20).
