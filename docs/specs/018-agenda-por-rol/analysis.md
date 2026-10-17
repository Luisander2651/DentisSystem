---
result: pass
round: 2
mode: delta
constitution_version: 1.2.0
date: 2026-10-17
spec_sha: 54ee99f28668
plan_sha: 82254a9275e4
tasks_sha: 488be210421c
---

# Análisis · 018 Agenda por rol

## Resumen
Pasa. De los 16 hallazgos de la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)), 14
quedan resueltos y 2 a medias (A4 en T025, A16 en "Dirección elegida" de `design.md`). La revisión
de los cambios añade 9 hallazgos: uno ALTO (A17, datos de prueba que también cambian capturas del
administrador), dos MEDIOS y seis BAJOS. Ninguno cambia el diseño, y el usuario los acepta como
notas de las tareas afectadas. Revisión delta con un subagente independiente (el diff ocupa
~65 KB); cada hallazgo se comprobó contra el código.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 2 (+ A4 abierto) · BAJA 6 (+ A16 abierto)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 13 / 13 |
| Amenazas con control y test | 7 / 7 |
| Principios de la constitución evaluados | 15 / 15 (P14 ❌ aceptado) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | Plan D11, casos de uso nuevos y contrato de selectores; T013, T037, T038. `UserService::findByRoleAndStatus()` existe; la dependencia Appointments → Users ya existía |
| A2 | ALTA | resuelto con efecto nuevo (→ A18) | T040 renombra el prop; T029 pasa `withSelectors`; T016 lo comprueba |
| A3 | ALTA | resuelto con efecto nuevo (→ A17) | El plan cita `database/seeders/UiAuditSeeder.php`; T018 (seeder) y T019 (`screens.json`) |
| A4 | ALTA | abierto en T025 | El plan fija el 409 (D12) y T014 lo afirma, pero la descripción de T025 aún dice "404 si no existe" → aceptado como nota de T025 |
| A5 | ALTA | resuelto | T014 cubre paciente, inactivo (403) y sin sesión (401) en `/tracking`; TM7 con T027 y T014 |
| A6 | MEDIA | resuelto | T013 enumera los casos que cambian y las rutas nuevas; T027 depende de T013 |
| A7 | MEDIA | resuelto | D10, contrato, T012 y T039; ningún test afirmaba el 409 de listado vacío |
| A8 | MEDIA | resuelto | RNF de la spec y fila en Decisiones |
| A9 | MEDIA | resuelto | `extends` con 008 y criterios que cambian en "Notas para /plan" |
| A10 | BAJA | resuelto | Fila P9 corregida |
| A11 | BAJA | resuelto | Plan cita 014, 016 y 017 |
| A12 | BAJA | resuelto | Lista de tests que cambian corregida |
| A13 | BAJA | resuelto | T010 amplía el test existente |
| A14 | BAJA | resuelto | T019 con ids y `now` por entrada |
| A15 | BAJA | resuelto | CA8 acotado a los listados de la agenda |
| A16 | BAJA | abierto en `design.md` → Dirección elegida | La fila de Decisiones está corregida, pero la línea 18 sigue citando `references/spec-mode.md` §1 → aceptado como nota de T001 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A17 | Conflicto | ALTA | T018, T034; `plan.md` → Datos de prueba | El segundo doctor y la cita completada sin seguimiento aparecen en las pantallas que ve el administrador (`agenda-con-citas`, `agenda-dia`, "Citas para hoy") y el usuario nuevo en `/usuarios`. T034 exige que el diff de la línea base no toque entradas del administrador, y eso no se puede cumplir | Toda entrada del administrador que cambie en la línea base cambia solo por los datos nuevos, y cada una queda enumerada y revisada en la nota de T034 (test: `ui-audit` sin `--baseline` en verde) |
| A18 | Inconsistencia | MEDIA | T029, T040 | T029 se cierra con "T016 pasa", pero los casos de selectores de T016 solo pasan tras T040, que depende de T029 | T029 se cierra con los casos de T016 que no dependen del diálogo de agendar; los de selectores se cierran en T040 |
| A19 | Ambigüedad | MEDIA | T013; `AppointmentsAccessControlTest` (tabla genérica y `buildRequest`) | La tabla genérica espera éxito de todo actor permitido. En `/complete` y `/tracking` el resultado depende de quién es dueño de la cita y de si tiene seguimiento | En la tabla genérica, esas dos rutas solo comprueban la denegación por actor y el middleware; la pertenencia y el éxito se prueban en T014 |
| A20 | Ambigüedad | BAJA | T018; `plan.md` → Datos de prueba | La cita completada con seguimiento y receta del doctor de prueba ya existe en el seeder (día 06) | Se reutiliza; solo es nueva la completada sin seguimiento |
| A21 | Cobertura de riesgos | BAJA | Plan → Riesgos (selectores); T011, T013, T038 | `GET /users` y `GET /treatments` están tras `only.admin`, así que su 403 no prueba que el asistente no tenga `users.view` ni `treatments.view`; lo prueban las filas de `RolePermissionsTest` | T011 mantiene `users.view` y `treatments.view` solo para el administrador y se cita como test del riesgo de D11 |
| A22 | Inconsistencia | BAJA | `plan.md` → Contratos, fila `GET /appointments` | El cuerpo real es `{"message": "Appointments retrieved successfully", "data": [...]}`, no solo `{"data": []}` | T012 afirma el cuerpo real con `data` vacío |
| A23 | Inconsistencia | BAJA | `plan.md` D9 | D9 cuenta 4 specs extendidas; ahora son 5 (008) | La cuenta de D9 es de antes de añadir la 008; la decisión del usuario se mantiene |
| A24 | Inconsistencia | BAJA | `tasks.md` → tabla de amenazas, TM7 | El plan usa `CompleteAppointmentTest` como test de TM7, pero la fila no incluye T015 | T015 cuenta como test de TM7 |
| A25 | Inconsistencia | BAJA | T019; `design.md` → Pantallas | `agenda-cita-crear-asistente`, `agenda-cita-detalle-doctor-sin-seguimiento` y `agenda-cita-completar-doctor` no tienen id en la tabla "Pantallas" del diseño | El inventario de `system.md` usa los ids de T019 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-17:
- **A4** → nota de T025: una cita inexistente responde 409, como hoy (D12), no 404.
- **A16** → nota de T001: al tocar el inventario, cambiar en `design.md` → "Dirección elegida" la cita a `references/spec-mode.md` por la regla de exploración de `/design-spec`, igual que en su fila de Decisiones.
- **A17** → nota de T034: las entradas del administrador que cambien en la línea base (`agenda-con-citas`, `agenda-dia`, `usuarios` y las que aparezcan) se revisan y se enumeran en la nota, con el motivo (datos nuevos de T018).
- **A18** → nota de T029, T040: T029 se cierra con los casos de T016 que no dependen del diálogo de agendar; los de selectores se cierran en T040.
- **A19** → nota de T013: en la tabla genérica, `POST /complete` y `GET /tracking` solo comprueban la denegación por actor y el middleware; la pertenencia y el éxito quedan en T014.
- **A20** → nota de T018: se reutiliza la cita completada con seguimiento y receta del día 06; solo es nueva la completada sin seguimiento.
- **A21** → nota de T011: `users.view` y `treatments.view` siguen solo para el administrador; es el test del riesgo de D11.
- **A22** → nota de T012: el listado vacío se afirma con el cuerpo real (`message` y `data` vacío).
- **A23** → plan: la cuenta de D9 es anterior a añadir la 008; la decisión de mantener la spec junta sigue vigente.
- **A24** → nota de T015: `CompleteAppointmentTest` también es test de TM7.
- **A25** → nota de T001, T019: los ids de T019 son los del inventario de `system.md`.
