---
result: fail
round: 4
mode: delta
constitution_version: 1.1.2
date: 2026-09-24
spec_sha: c3688721a805
plan_sha: cfa38cfe0333
tasks_sha: 9413b4ab55b6
---

# Análisis · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Ronda 4, **delta** sobre la ronda 3 ([analysis.r3.md](analysis.r3.md)). Solo cambió `spec.md` (37
líneas, migración a IDs de riesgos de `/init --upgrade` 1.6.0): plan y tareas son idénticos a la
ronda 3. La base del delta es el commit `ee1a146`, que coincide con las huellas de la ronda 3; la
copia de `.ai/cache/` era anterior y no se usó. No hay cambio estructural: ningún módulo, contrato
ni criterio nuevo.

## Resumen
**fail.** Sigue abierto el único ALTO (C1, reporte por defecto de Laravel con datos). Plan y tareas
no se tocaron desde la ronda 3, así que C1–C13 siguen abiertos. El delta añade 6 hallazgos (D1–D6)
sobre la cobertura de riesgos nueva: ninguno crítico. La comprobación crítica de la categoría 7
pasa: ni el plan ni las tareas declaran mitigado un riesgo con correcciones fuera.

Conteo de abiertos: 0 CRÍTICOS · 1 ALTO · 7 MEDIOS · 11 BAJOS.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (CA14 con test débil: C5) |
| Amenazas con control y test | 12 / 12 declaradas; 10 / 12 efectivas (C1) |
| Correcciones de riesgo "dentro" con cambio y test | 4 / 5 (OB2.a sin test automatizado: D2) |
| Correcciones de riesgo "fuera" con destino real | 7 / 11 (RS5.a, RS8.a, RS10.a, RS10.b sin destino real; OB10.b solo en parte: D3, D4) |
| Principios de la constitución evaluados | 16 / 16 (en la ronda 3; sin cambios de constitución) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| C1 | ALTA | abierto | Plan (bootstrap, D3, TM7, TM11), T033 y T015 sin cambios |
| C2 | MEDIA | abierto | T052 sin T075 en `depende` |
| C3 | MEDIA | abierto | T064 no admite el 403 de `AuthorizationException` |
| C4 | MEDIA | abierto | Plan, T047 y T012 sin el caso de `time` nulo |
| C5 | MEDIA | abierto | Sin aserciones de texto de mensajes de negocio |
| C6 | BAJA | abierto | T013, caso `/record` solo afirma el log |
| C7 | BAJA | abierto | `plan.md`, fila TM11 |
| C8 | BAJA | abierto | T022 sin renombrar casos |
| C9 | BAJA | abierto | T061 |
| C10 | BAJA | abierto | T020, T021 |
| C11 | BAJA | abierto | Plan, fila Frontend (4 archivos de vista) |
| C12 | BAJA | resuelto con efecto nuevo (→ D1) | La spec ahora pide estados por corrección; T065 y T091 siguen con la numeración antigua |
| C13 | BAJA | abierto | `plan.md`, Riesgos residuales (y aún dice "riesgo 10") |
| C14 | BAJA | abierto | `spec.md`, CA16 antes que CA15 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| D1 | Cobertura de riesgos | MEDIA | `spec.md` (Notas para /plan) vs `plan.md` (Impacto en arquitectura), T065, T091 | Plan y T065 hablan de "riesgo 1 parcialmente mitigado" y "riesgo 3 mitigado", pero el estado ahora es por corrección. T091 no actualiza OB2.a ni OB10.a, y nada actualiza la nota de RS9.a. Absorbe C12. | `/plan --fix D1` y `/tasks --fix D1`: T065 marca RS1.a, RS1.d y RS3.a como mitigadas (RS1.b y RS1.c siguen pendientes en la 013) y actualiza la nota de RS9.a; T091 marca OB2.a y OB10.a como mitigadas. |
| D2 | Cobertura de riesgos | MEDIA | `spec.md`, fila OB2.a; T047 | OB2.a está "dentro", pero la asocia a CA13, que es del log de un error inesperado. Los `Log::info` con teléfono y nombre son logs de éxito y no tienen test automatizado. | `/specify --edit` (fila OB2.a → P11) y `/tasks --fix D2`: caso con `Log::spy()` en `CreateAppointmentTest` que afirme que ningún `Log::` contiene el teléfono ni el nombre de prueba. |
| D3 | Cobertura de riesgos | MEDIA | `spec.md`, fila RS10.a; `roadmap.md`, objetivos 4 y 5 | RS10.a va al "objetivo 5", pero el objetivo 5 manda `APP_DEBUG=false` (OB5.b) al objetivo 4, que no lo lista. Error de la migración. | `/specify --edit`: destino objetivo 4. **Decisión del usuario:** añadir RS10.a y OB5.b a la lista de riesgos del objetivo 4. |
| D4 | Cobertura de riesgos | MEDIA | `spec.md`, filas RS5.a, RS8.a, RS10.b, OB10.b; T071 | RS5.a y RS8.a quedan "pendiente de objetivo" (sin destino). RS10.b va a "Pendientes y deuda", donde no hay entrada. OB10.b incluye whatsApp, y T071 solo añade Auth y Users. | **Decisión del usuario** sobre los destinos. Después, `/specify --edit` y `/tasks --fix D4` (T071). |
| D5 | Inconsistencia | BAJA | `roadmap.md`, objetivo 1 | El objetivo 5 manda OB2.a al objetivo 1, y OB10 apunta al objetivo 1, pero el objetivo 1 no los lista. | Añadir OB2.a y OB10.a al objetivo 1 (con confirmación; puede ir en T071). |
| D6 | Ambigüedad | BAJA | `spec.md`, fila RS1.a | Asocia RS1.a a CA15 (menú e inicio), pero ocultar opciones no es la restricción del servidor (P13). | `/specify --edit`: quitar CA15 de la fila. |

## Decisiones pendientes del usuario
Resueltas por el usuario el 2026-09-24:
- **C1:** se corrige antes de implementar (`/plan --fix C1`, `/tasks --fix C1`).
- **D3:** RS10.a y OB5.b se añaden a la lista de riesgos del objetivo 4 del roadmap; destino de RS10.a en la spec: objetivo 4.
- **D4:** RS5.a, RS8.a, RS10.b y las trazas de whatsApp de OB10.b van a entradas propias de "Pendientes y deuda" del roadmap.
- **Aceptados para `/implement`:** los BAJOS y los MEDIOS que no cambian el diseño (lista abajo).

Correcciones antes de la ronda 5: C1, C4, D1, D2, D3 y D4 (con D6 y C14 en la misma edición de la spec).

## Aceptados
Aceptados por el usuario el 2026-09-24; se resuelven en `/implement` como nota de la tarea indicada:
- C2: T052 también depende de T075 (orden de `agenda/treatments`).
- C3: T064 admite `getMessage()` en el 403 de `AuthorizationException`.
- C5: T066 añade aserciones de texto del 404 de paciente y del 409 de relación 1:1.
- C6: T013 afirma también el cuerpo genérico en `GET /patients/{patientId}/record`.
- C7: la fila TM11 del plan se lee como T013 (`RuntimeException` con un email de prueba).
- C8: T022 renombra los casos "any authenticated active staff… (not just admin)" de `CreateAppointmentTest` y `UpdateAppointmentTest`.
- C9: el criterio de T061 se acota al menú lateral de asistente, doctor y paciente.
- C10: T020 y T021 usan `actingAsNonAdminUser('Asistente')` de forma explícita.
- C11: en T063, son 4 archivos de vista (la cifra del plan es una errata).
- C13: T065 añade a `security.md` los residuales "doctor y asistente ven a todos los pacientes" y "logs de Auth y Users".
- D5: T071 propone añadir OB2.a y OB10.a a la lista de riesgos del objetivo 1.
- C14 y D6: se corrigen en el `/specify --edit` de D2–D4 (orden de CA15/CA16; quitar CA15 de la fila RS1.a).
