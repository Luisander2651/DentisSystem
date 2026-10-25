---
result: fail
round: 2
mode: delta
constitution_version: 1.2.0
date: 2026-10-24
spec_sha: 4455acdc1983
plan_sha: d9b3c26beb82
tasks_sha: cff812a0f74b
---

# Análisis · 020 Tablero de inicio

## Resumen
No pasa: quedan 3 hallazgos ALTOS nuevos. Los flujos de navegador de T026 no caben en el runner
actual (A25). El control de TM9 depende del entorno al registrar el middleware, que en la web aún
no está cargado (A27). Y `evaluate` mide sin la hora fija, así que K3 no puede pasar (A32). Los 24
hallazgos de la ronda 1 quedaron resueltos; 8 con un efecto nuevo, que se registra como hallazgo
nuevo. Delta hecho por un subagente independiente (el diff ocupa ~109 KB); cada hallazgo se
verificó en el código.
Conteo: CRÍTICA 0 · ALTA 3 · MEDIA 5 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (las partes de navegador de CA1, CA2, CA4, CA7, CA12 y CA16 dependen de A25 y A26) |
| Amenazas con control y test | 9 / 9 (el test de TM9 depende de A27) |
| Principios de la constitución evaluados | 10 / 15 en esta delta (P1–P7, P11, P13, P15 y la Definición de terminado); P8, P9, P10, P12 y P14 no cambian desde la ronda 1 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | CA2 editado y sacado de "Fuera de alcance"; D17; el inicio incluye `edit-appointment-modal`; T045; T026 `dashboard-detalle` |
| A2 | ALTA | resuelto con efecto nuevo (→ A32) | T002 y D16 siembran el 17-09-2026 con 15 sin cerrar, pero `evaluate.mjs` no envía `X-Ui-Audit-Now` |
| A3 | ALTA | resuelto | D16 (hora fija); diferencia admitida en `ADMITTED` de `checks-composition.mjs`; T051; T050 corre a dos horas distintas |
| A4 | ALTA | resuelto | T026 `expedientes-buscar` con `expect.focused`; T048 depende de T026 |
| A5 | ALTA | resuelto con efecto nuevo (→ A25) | T026 declara los flujos de vacío, carga, error y Reintentar, pero `screens.json` no los puede expresar |
| A6 | ALTA | resuelto | P3 y D20 registran la excepción con la decisión del usuario; T042; T024 |
| A7 | ALTA | resuelto | T054 `DashboardLogsTest` va antes y entra en el "hecho cuando" de T035, T038 y T041 |
| A8 | MEDIA | resuelto | TM1 nombra el control de cada lectura; T038 |
| A9 | MEDIA | resuelto con efecto nuevo (→ A29) | CA15 editado; T029 y T049 |
| A10 | MEDIA | resuelto | CA10 editado; `previous_short_label` y textos fijos; T022 |
| A11 | MEDIA | resuelto con efecto nuevo (→ A31) | El flujo del mes compara con los datos de un test de integración, no con los sembrados |
| A12 | MEDIA | resuelto con efecto nuevo (→ A28) | T021 define qué se mide, pero exige la suite en serie |
| A13 | MEDIA | resuelto | 403 por el render de `bootstrap/app.php`; T019 afirma el cuerpo; 018/R8 fuera, con destino en el roadmap |
| A14 | MEDIA | resuelto | Riesgos: RS5.a fuera, con destino en el roadmap; T052 y T053 la dejan pendiente |
| A15 | MEDIA | resuelto | T002 actualiza `UiAuditSeederTest`, primero en rojo |
| A16 | MEDIA | resuelto | CA11 editado; T024 ("Ángela" → "Á"); T045 (`mb_substr`) |
| A17 | MEDIA | resuelto con efecto nuevo (→ A26) | CA16, D19, T042 y T024; `dashboard-inactivo` no puede abrir sesión en ui-audit |
| A18 | MEDIA | resuelto | T027 lista los 8 archivos de `Domain/` |
| A19 | MEDIA | resuelto con efecto nuevo (→ A30) | Contrato del 422 campo por campo; T017 |
| A20 | BAJA | resuelto | Plan (diálogos reutilizados), T047 y T026 |
| A21 | BAJA | resuelto | La acción rápida abre "Completar cita" y T026 lo comprueba |
| A22 | BAJA | resuelto con efecto nuevo (→ A25) | Se quitan los 13 s, pero la espera de 12 s no tiene acción en el runner |
| A23 | BAJA | resuelto | P3 cita `AppointmentTrackingModel::appointment()`; diferencia `doctor_name` / `user_name` declarada |
| A24 | BAJA | resuelto | T028 en `tests/Modules/AppointmentTracking/Unit/Application/`; T038 en el control de TM4 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A25 | Cobertura / Constitución (P2) | ALTA | T026; `plan.md` → Tests de navegador; `tests/Browser/checks-dialogs.mjs` (`runFlows`, `answers`) | El runner instala la intercepción después de cargar la página, responde una sola ruta por flujo (`answer.to`) y no tiene retención (`hold`) ni espera en los flujos. Las secciones del inicio cargan al abrir la página, así que vacío por sección, carga, error de una sección, "Reintentar" y la espera de 12 s no se pueden expresar en `screens.json`. T026 no puede fallar por la razón esperada y ninguna tarea cambia el runner | Una tarea anterior a T026 permite, en los flujos, responder a las lecturas desde la carga de la página, retener una respuesta, responder distinto a peticiones sucesivas y esperar. Test: el ui-audit completo da el mismo resultado en las entradas existentes, y un flujo de prueba en `dashboard-administrador` falla por la razón esperada. `/plan --fix`, `/tasks --fix` |
| A26 | Cobertura | MEDIA | T026 `dashboard-inactivo`; `app/Core/Console/UiAuditSessionCommand.php` (`seededAccount`) | El comando de sesión solo entrega sesión al staff activo y al paciente. Para el staff inactivo sembrado no hay sesión, y ninguna tarea cambia el comando ni su test (016 TM5) | ui-audit puede entrar como el staff inactivo sembrado, sin aceptar cuentas fuera del conjunto fijo. Test: el test del comando de sesión con el rol inactivo, y `dashboard-inactivo` en rojo antes de T042. `/plan --fix`, `/tasks --fix` |
| A27 | Seguridad (TM9) | ALTA | `plan.md` → Core · reloj, TM9, D16; T006, T007 | El plan registra `UiAuditClock` en `bootstrap/app.php` "solo en local y testing". La función de `withMiddleware` corre al resolverse el kernel HTTP, y en la web eso pasa antes de cargar el entorno; en Pest ya está cargado. Preguntar el entorno al registrar pasa los tests pero en la web falla o no registra nunca. Además, "en production el middleware no está en la pila" no se puede comprobar sin rearrancar la aplicación | TM9 se apoya en una garantía que funciona en la web real: el entorno se comprueba en cada petición (precedente `UiAuditSeeder::ALLOWED_ENVIRONMENTS`), y el registro no depende del entorno. Test: `UiAuditClockTest` con el entorno en `production` (la cabecera no tiene efecto) y una petición a `/dashboard` sin error con el middleware registrado. `/plan --fix` (TM9, D16), `/tasks --fix` (T006, T007) |
| A28 | Constitución (Definición de terminado) / RNF | MEDIA | T021; `plan.md` → Estrategia de pruebas | El umbral de 2 s se exige "con la suite en serie", pero la Definición de terminado y CI corren `pest --parallel`: o falla de forma intermitente o no se cumple | El test es estable bajo `--parallel`, o el plan dice cómo se aísla, sin `skip`. Test: `DashboardPerformanceTest` verde dos veces en `pest --parallel`. `/plan --fix`, `/tasks --fix` |
| A29 | Inconsistencia | MEDIA | T029, T049; `CompleteAppointmentTest.php` | Dos casos existentes afirman hoy los textos en inglés, uno con el id. T029 solo añade casos y T049 exige que "el resto siga en verde": tras T049 esos dos fallan | T029 sustituye esas dos aserciones por las del contrato. Test: `CompleteAppointmentTest` completo en verde tras T049. `/tasks --fix` |
| A30 | Inconsistencia | MEDIA | `plan.md` → `POST /tracking` (422 de `symptoms`); T017; CA8 | CA8 pide los campos y las reglas de completar, y completar admite `symptoms` vacío (`Symptoms::fromArray` solo rechaza elementos vacíos). "Escribe al menos un síntoma." sería una regla nueva que el diálogo no tiene | Decidido: mismas reglas que completar; los síntomas pueden quedar vacíos y un elemento vacío se rechaza. Test: T017 sobre `symptoms` vacío (se acepta) y con un elemento vacío (422). `/plan --fix`, `/tasks --fix` |
| A31 | Ambigüedad / Cobertura (CA10) | MEDIA | `plan.md` → Tests de navegador (mes del administrador); T026 | Las cifras esperadas son "las de `DashboardMatchesReportsTest`", que usa sus propios datos, no los de `ui:audit-data`. T026 deja el texto abierto | Los textos esperados salen de los datos sembrados de septiembre de 2026, fijados en un test, o se comparan en la misma corrida con `/reportes` del mismo mes. Test: el flujo falla si una cifra pintada difiere. `/plan --fix`, `/tasks --fix` |
| A32 | Cobertura (diseño K3) | ALTA | T046, T050; D16 | `evaluate.mjs` no envía `X-Ui-Audit-Now`. Con la fecha real, las citas por atender del 17-09 pasan a "sin cerrar": salen 19, se pinta "y 9 más" y K3 ("y 5 más") falla. El "hecho cuando" de T046 no se puede alcanzar | `evaluate` mide `/dashboard` con el servidor en la hora de D16, o los datos dan K1–K4 con cualquier fecha real. Test: `evaluate` con `criterios.json` y `criterios-doctor.json` pasa en T046. `/plan --fix`, `/tasks --fix` |
| A33 | Redacción | BAJA | `plan.md` → Interfaz · pantalla ("Octubre hasta hoy"); T026 (`dashboard-detalle`) | El título de la sección contradice "todo el mes" (CA10). Cada entrada de `screens.json` lleva un solo rol, así que el detalle del administrador y el del asistente son dos entradas | Título coherente con CA10 y dos entradas con su id. `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Ninguna. Decididas el 2026-10-24:
- **A30:** registrar usa las mismas reglas que completar; los síntomas pueden quedar vacíos (decisión del usuario).
- **A26, A28, A29, A31, A33:** se corrigen ahora junto con los ALTOS, no como notas de `/implement` (decisión del usuario).

## Aceptados
Ninguno en esta ronda.

## Siguiente paso
`/plan 020 --fix A25,A26,A27,A28,A30,A31,A32,A33` y `/tasks 020 --fix A25–A33`; después
`/analyze 020` (ronda 3, delta).
