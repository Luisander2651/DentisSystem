---
result: pass
round: 5
mode: delta
constitution_version: 1.2.0
date: 2026-10-13
spec_sha: 7c4082bef977
plan_sha: eaaf3fbb274c
tasks_sha: d3eabb3f3f86
---

# Análisis · 016 Sistema de diseño aplicado

## Resumen
Delta sobre la ronda 4, con subagente: la spec reaprobada (CA42, CA43; CA9, CA13 y CA37 ampliados),
el plan corregido y las tareas v4 (T089 nueva y diez tareas reabiertas por falta de números libres,
decisión del usuario). Los 16 hallazgos de la ronda 4 quedan resueltos o encargados a una tarea.
Quedan un hallazgo medio y cuatro bajos que no cambian el diseño, aceptados como notas.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 43 / 43 (la parte "o una acción" de CA37, por nota: A91) |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 5 / 5 afectados por el cambio (P1, P2, P10, P11, P15) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A75 | ALTA | resuelto | CA42; T089 (menús a 390 y 768, visita a 1024 con control negativo); T081 (Escape y foco); T032, T040 |
| A76 | MEDIA | resuelto | Convenciones de tareas y "Navegador" del plan con 390, 768 y 1440; nota en T005; cabecera de `ui-audit.mjs` en T089 |
| A77 | MEDIA | resuelto | T089 (`agenda-dia` a 768 y 1440); trazabilidad de CA13; nota en T067 |
| A78 | MEDIA | resuelto, pendiente de hacer | T001 reabierta |
| A79 | MEDIA | resuelto con efecto nuevo (→ A94) | "Fuera de alcance" y la fila de anchos del 2026-10-06 al día |
| A80 | MEDIA | resuelto | Historial de la spec con el 2026-10-11 y la reapertura del 2026-10-13 |
| A81 | MEDIA | resuelto | Plan en 1.2.0, P2 ✅ y fila P15 en plan y tareas |
| A82 | ALTA | resuelto, pendiente de hacer | CA37 lo nombra; caso en T081; T038 reabierta |
| A83 | MEDIA | resuelto con efecto nuevo (→ A91) | CA42 (Escape), CA43, CA37 ("Enviando…") |
| A84 | MEDIA | resuelto | Decisión en spec; plan sin prueba manual con lector |
| A85 | MEDIA | resuelto, pendiente de hacer (→ A95) | T007 reabierta |
| A86 | BAJA | resuelto | P10 y fila Dependencias del plan; T078 reabierta |
| A87 | BAJA | resuelto | Fila de Errores web del plan; la nota de T071 ya lo explicaba |
| A88 | BAJA | resuelto | Spec → Diseño; plan → Capturas; nota en T076 |
| A89 | BAJA | resuelto, pendiente de hacer | T007 reabierta |
| A90 | MEDIA | resuelto | CA9 a 390 y 768; plan → Control táctil; T089 con control negativo |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A91 | Cobertura / inconsistencia | MEDIA | CA37 ("un formulario o una acción"); notas de T082 y T081; plan → "Accesibilidad pendiente" | El anuncio de envío solo cubre formularios; las confirmaciones de eliminar envían sin formulario (p. ej. `patients/delete-patient.js`), y no se dice qué pasa si la validación previa bloquea el envío. | Aceptado → nota de T082 y T081 |
| A92 | Cobertura (CA42) / ambigüedad | BAJA | CA42; T089; trazabilidad de CA42 | Una visita a 1024 px no puede cubrir el panel y el sitio; nada recorre con el tabulador las opciones del menú abierto. | Aceptado → nota de T089 y T081 |
| A93 | Inconsistencia (orden) | BAJA | T032 (`depende: … T082`), T082 reabierta; T033 y T034 (`T032, T032`) | El orden documentado no coincide con las dependencias; dependencia repetida. | Aceptado → nota de T032 |
| A94 | Inconsistencia | BAJA | spec → Decisiones, 2026-10-06 ("44 × 44 px a 390 px") | No remite a la decisión del 2026-10-13. | Aceptado → spec, en la próxima edición |
| A95 | Ambigüedad (redacción) | BAJA | nota de T007; spec → Notas ("41 criterios"); Convenciones de tareas ("ronda 4 … 2026-10-11") | El plan de la 015 no tiene Historial (lo tiene su spec); la spec tiene 43 criterios; la ronda 4 fue el 2026-10-13. | Aceptado → nota de T007; spec y tareas, en la próxima edición |

Comprobado sin hallazgo: cada tarea reabierta toca como mucho 3 archivos y se ejecuta sin decidir
diseño (el menú del sitio tiene su script en `nav.blade.php`; el del panel, en `app.js`); los
`target="_blank"` están solo en `nav`, `footer`, `contacto` e `inicio`; CA42 y CA43 son
verificables y sin tecnología; los criterios no cambiados no contradicen lo cambiado (CA22 a 1440,
capturas a 390 y 1440); las tablas de cobertura y la reapertura de T079 y T092 son coherentes; sin
superficie de seguridad nueva.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-13:
- **A91** → nota de T082, T081: "Enviando…" en todo formulario y en toda acción que envía datos sin formulario (las ocho confirmaciones de eliminar), nunca cuando la validación previa impide enviar; T081 añade un caso de acción sin formulario y uno de contraseñas distintas. Si no cabe en 3 archivos, `/tasks --fix`.
- **A92** → nota de T089, T081: a 1024 px una visita del panel y una del sitio; un caso que recorre con el tabulador las opciones del menú abierto a 390 y 768 px.
- **A93** → nota de T032: orden ejecutable T089 → T081 → T040 y T038 → T082 → T032 → T033 y T034 → T001 → T007 → T078 → T079.
- **A94** → spec: la decisión del tamaño táctil del 2026-10-06 remite a la del 2026-10-13, en la próxima edición.
- **A95** → nota de T007: la fila de Historial de la 015 va en su `spec.md`. "43 criterios" en la spec y la fecha de las Convenciones, en la próxima edición.
