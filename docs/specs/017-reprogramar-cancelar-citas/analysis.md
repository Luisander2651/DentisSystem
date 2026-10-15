---
result: fail
round: 2
mode: full
constitution_version: 1.2.0
date: 2026-10-15
spec_sha: 2563023a1a12
plan_sha: 3820c642c2df
tasks_sha: 20799a80d1aa
---

# Análisis · 017 Reprogramar y cancelar citas

## Resumen
`fail`. Los hallazgos de la ronda 1 quedaron resueltos (cuatro con un efecto menor nuevo), pero el
contrato de reprogramar no dice si se aplica `whatsapp_reminder`, y la casilla del diálogo podría
quedar sin efecto sin que ningún test lo detecte (A23). Quedan siete hallazgos medios de cobertura y
de orden de tareas que se corrigen con `--fix`. Modo completo porque la spec ganó CA11. Análisis
hecho por un subagente independiente; cada hallazgo se verificó contra los archivos.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 7 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 4 / 5 (TM5 parcial: A26) |
| Principios de la constitución evaluados | 15 / 15 (P4 ❌ aceptado, D10) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | plan.md → Constitution Check P4 `❌ aceptado`; Decisiones D10 |
| A2 | ALTA | resuelto | T018 (422 en "Reprogramar cita" y "Agendar cita"); la estrategia del plan quedó atrás (→ A32) |
| A3 | ALTA | resuelto | T018 (un solo `PUT {status: cancelada}`, aviso, salir sin petición) |
| A4 | ALTA | resuelto | T014 (líneas 101 y 104), T016; plan.md → Riesgos |
| A5 | ALTA | resuelto | T035 |
| A6 | MEDIA | resuelto con efecto nuevo (→ A25) | T020 conserva el método hasta T024, pero T021 no depende de T012 |
| A7 | MEDIA | resuelto | plan.md → Pantallas; `dialog.js:96-116`, `view-appointment.js:261-267`; T015 |
| A8 | MEDIA | resuelto | spec CA10; plan P5, D11; T014, T023; `bootstrap/app.php:87-91` traduce `AuthorizationException` a 403 |
| A9 | MEDIA | resuelto | CA11; plan D12, TM3; T010, T012, T021; `CompleteAppointmentUseCase.php:52-54` rechaza antes de `complete()` |
| A10 | MEDIA | resuelto | spec `extends`, RNF; plan D13; T002 |
| A11 | MEDIA | resuelto | T036 |
| A12 | MEDIA | resuelto con efecto nuevo (→ A31) | design.md → Anti-clichés aún dice "Rojo solo en el botón que confirma" |
| A13 | MEDIA | resuelto con efecto nuevo (→ A27) | `--only agenda` deja fuera el detalle de cita del expediente |
| A14 | MEDIA | resuelto con efecto nuevo (→ A33) | `docs/roadmap.md:15` corregido; queda `docs/specs/015-primer-despliegue-vps/plan.md:286` |
| A15 | MEDIA | resuelto | plan.md D6 confirmada |
| A16 | BAJA | resuelto | plan.md → Riesgos; `screens.json` `now` 2026-07-22, seeder en julio de 2026 |
| A21 | BAJA | resuelto | plan.md → contrato del `PUT` |
| A22 | BAJA | resuelto con efecto nuevo (→ A30) | T001 renombra el inventario, pero ninguna tarea genera sus capturas |

Los aceptados de la ronda 1 tienen su nota: A17 en T004 (antes T002), A18 en T015 y T018, A20 en T018.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A23 | Ambigüedad / Inconsistencia | ALTA | plan.md → contrato del `PUT` (fila Reprogramar), D4; T012, T024, T030 | La fila Reprogramar admite `whatsapp_reminder?` sin decir si se aplica; T024 describe tres ramas exclusivas sin el recordatorio al reprogramar, y la interfaz lo envía cuando la casilla cambió. La casilla de CA9 podría no hacer nada sin que un test lo detecte | `/plan --fix A23` y `/tasks --fix A23`: reprogramar con `whatsapp_reminder` deja la cita `reprogramada` y aplica el recordatorio (test en `UpdateAppointmentTest`, T012) |
| A24 | Ambigüedad (contrato) | MEDIA | plan.md → contrato del `PUT`; T012, T025; `AppointmentAplicationExceptions.php:11-14`; `UpdateAppointmentController.php:52-53` | El contrato no define el cuerpo vacío (hoy 409, `noInfoProvided`), y T025 traduce a 422 solo una fábrica de una clase que hoy va entera a 409 | Decisión del usuario (2026-10-15): sigue en 409 → `/plan --fix A24` y `/tasks --fix A24`: `UpdateAppointmentTest` fija el 409 del cuerpo vacío |
| A25 | Inconsistencia (orden) | MEDIA | T021 (depende) ; `UpdateAppointmentTest.php` fechas 2026-09-0x | T021 introduce la regla del pasado, pero las fechas fijas de `UpdateAppointmentTest` solo pasan a relativas en T012, que no está entre sus dependencias; además `AppointmentInThePastException` acaba en 500 hasta T022/T025 | `/tasks --fix A25`: al cerrar T021, todos los casos sin `skip` de `UpdateAppointmentTest` y `CreateAppointmentTest` en verde (dependencia de T012 declarada) |
| A26 | Cobertura (TM5 / P7) | MEDIA | plan.md → TM5; `tests/Modules/Appointments/Integration/UnexpectedErrorTest.php:15-35` | El test de TM5 cubre el listado de citas y tratamientos, no `PUT` ni `POST /appointments`, cuyos `catch` reestructuran T022 y T025 | `/plan --fix A26` y `/tasks --fix A26`: un error inesperado en `PUT` y en `POST /appointments` responde exactamente `{"error":"Internal server error"}` (dos casos en `UnexpectedErrorTest`) |
| A27 | Cobertura (P2 / P15) | MEDIA | T015, T031, T034; `resources/views/pages/records/index.blade.php:100,111` | El detalle de cita también se monta en el expediente; `ui-audit --only agenda` no lo recorre y "el expediente no muestra los botones" no tiene comprobación automática | `/tasks --fix A27`: `checks-dialogs` afirma que en `expediente-*-cita-detalle` no hay `[data-view-appointment-edit]` ni `[data-view-appointment-cancel-appointment]`; el `ui-audit` local de T031 y T034 incluye esas pantallas |
| A28 | Cobertura (CA9) | MEDIA | spec CA9; T015 | T015 solo comprueba la ausencia del estado; los textos "Reprogramar" y "Reprogramar cita" quedan a mano | `/tasks --fix A28`: T015 afirma el texto del botón del detalle y el título del diálogo |
| A29 | Cobertura (CA7) | MEDIA | plan.md → Pantallas ("error en su región"); T015, T018; `checks-a11y.mjs:1058-1075` | Nada prueba el error al confirmar la cancelación | `/tasks --fix A29`: ante un 409 o un 500, la confirmación sigue abierta y anuncia el error en su región (T018) |
| A30 | Inconsistencia (efecto de A22) | MEDIA | T001; design.md → Cambios al sistema; `docs/design/capturas/` (solo `agenda-cita-editar-*`) | El inventario pasa a enlazar `agenda-cita-reprogramar-*` y `agenda-cita-cancelar-*`, que ninguna tarea genera, y `agenda-cita-detalle-pendiente-*` queda desfasada | `/tasks --fix A30`: tarea posterior a T033 que genera esas capturas; el inventario solo enlaza archivos que existen |
| A31 | Inconsistencia (diseño) | BAJA | design.md → Anti-clichés | "Rojo solo en el botón que confirma" contradice la regla corregida (botón y advertencia) | Aceptado → nota de T001 |
| A32 | Inconsistencia | BAJA | plan.md → Estrategia de pruebas (Navegador), Trazabilidad CA8 | La estrategia no recoge el 422 de agendar, el flujo de cancelar con la API interceptada ni la cita cancelada de CA8, que ya están en T015 y T018 | `/plan --fix A32` (junto con A23) |
| A33 | Conflicto (resto de A14) | BAJA | `docs/specs/015-primer-despliegue-vps/plan.md:286` | "Métricas y alertas: ninguna nueva (spec 017)" sigue apuntando a 017 como spec de monitoreo | `/plan --fix A33`: "spec de monitoreo por crear", en el mismo commit |
| A34 | Duplicación y conflicto | BAJA | spec.md → Fuera de alcance; `docs/specs/013-codigos-qr-por-cita/spec.md` CA6, CA7 | 013 (approved, sin plan) pide renovar o expirar el QR al reprogramar o cancelar; 017 no lo menciona | Aceptado → spec |
| A35 | Constitución (P2, precisión) | BAJA | T018; `create-appointment.js:344,593` | El 422 de "Agendar cita" probablemente ya se muestra en el diálogo: esa parte de T018 pasa antes del cambio | Aceptado → nota de T018 |
| A36 | Ambigüedad | BAJA | plan.md D8; T012 | La precedencia "cita cerrada (409) antes que fecha pasada (422)" no tiene test | Aceptado → nota de T012 |
| A37 | Cobertura de riesgos | BAJA | plan.md → Riesgos; `docs/security.md` RS1.d, RS9.a | T014 y T016 tocan los tests que evidencian RS1.d y RS9.a, y el plan no los cita | `/plan --fix A37`: citarlos en Riesgos; T034 los mantiene en verde |
| A38 | Mecánica | BAJA | T035; `ui-audit.mjs:736-747` | `--baseline` conserva las claves guardadas: `agenda-cita-editar` quedaría huérfana | Aceptado → nota de T035 |

## Decisiones pendientes del usuario
Ninguna. A24 decidida por el usuario el 2026-10-15: el cuerpo vacío sigue en 409.

## Aceptados
Aceptados por el usuario el 2026-10-15:
- **A17** → nota de T004: añadir `APP_TIMEZONE` a `EnvExampleDefaultsTest` (antes nota de T002)
- **A18** → nota de T015, T018: CA8 también con la cita cancelada; CA7 también al cerrar con la X y con el fondo
- **A19** → spec: el límite `<=` ya está en el texto aprobado de CA3 y CA4
- **A20** → nota de T018: ante un 409, afirmar el texto genérico del sistema en la región del diálogo
- **A31** → nota de T001: alinear el anticliché de `design.md` con la regla (botón y advertencia)
- **A34** → spec: 013 no tiene plan; su `/plan` deberá contemplar reprogramar y cancelar de 017
- **A35** → nota de T018: el 422 de "Agendar cita" es caracterización; el "falla antes" se exige a reprogramar y cancelar
- **A36** → nota de T012: reprogramar una cita cancelada a una fecha pasada responde 409 con el texto de cita cerrada
- **A38** → nota de T035: el diff no deja entradas obsoletas de la agenda (`agenda-cita-editar`)
