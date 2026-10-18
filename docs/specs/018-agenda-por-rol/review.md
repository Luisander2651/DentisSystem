---
spec: 018-agenda-por-rol
verdict: approved
round: 2
date: 2026-10-18
base: afe1416
head: a6a0641
human_signoff: Luisander2651, 2026-10-18
---

# Review · 018 Agenda por rol

## Resumen
Aprobada. Las cinco correcciones de la ronda 1 (R1, R2, R3, R4, R10) se hicieron como T041–T043:
son solo tests, cada una cierra su hallazgo y las que protegen un control se comprobaron rompiéndolo.
No hay hallazgos nuevos. Los menores aceptados de la ronda 1 (R5–R9, R11, R12) siguen igual y van
al roadmap en `/release`. Ronda anterior: [history/review.r1.md](history/review.r1.md).
Conteo (ronda 2): bloqueante 0 · importante 0 · menor 0

Revisión `--rerun` sin subagentes: `code.diff` de `afe1416..a6a0641` ocupa 7 KB (4 archivos de
test) y se releyó desde cero.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php -d memory_limit=1G ./vendor/bin/pest` (serie) | ✅ 1452 en verde, 0 fallos (línea base: 1 fallo ajeno, que no se repitió) |
| `vendor/bin/pint --dirty` | ✅ |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |
| `npm run build` · `npm run test:ui` | Sin cambios de vistas ni JS desde la ronda 1 (solo tests de Pest); vigentes los de la ronda 1 |

## Criterios de aceptación
Sin cambios de comportamiento desde la ronda 1; los trece criterios siguen ✅. Refuerzos:

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA2 | `AgendaByRoleTest` (choque del asistente con mensaje exacto) | ✅ | R10 |
| CA4, CA9 | `AgendaScreenByRoleTest` ("tells the appointment detail of the agenda whose clinical follow-up each role may read") | ✅ | R1; falla al pasar `all` al asistente |
| CA7, CA11 | `AppointmentsAccessControlTest` ("refuses booking to the doctor before reading the body…"; tabla con estados permitidos cerrados) | ✅ | R2, R3; falla si se valida antes de `assertCan` |
| CA10 | `DoctorTrackingTest` (cuerpos reales y rechazo exacto) | ✅ | R4 |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 4 archivos, todos citados por T041–T043).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1. P2: los tests nuevos que pasan desde el principio protegen lo
implementado y se comprobaron rompiendo el control (notas de T041 y T042).

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks · semgrep · `composer audit` / `npm audit` · trivy | ✅ en la ronda 1; la ronda 2 solo cambia tests, sin dependencias, rutas protegidas ni código de la aplicación |

## Observabilidad
- Sin cambios respecto a la ronda 1 (P14 aceptado hasta el objetivo 5).

## Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | resuelto (T041) | `AgendaScreenByRoleTest`, caso por rol con `all`/`none`/`own` y `actorId` |
| R2 | importante | resuelto (T042) | `AppointmentsAccessControlTest`, doctor × `POST` con seis cuerpos → 403 y tablas sin cambios |
| R3 | menor | resuelto (T042) | Tabla genérica: `toBeIn([200, 201, 404, 409, 422])` |
| R4 | menor | resuelto (T043) | `DoctorTrackingTest`: cuerpo por operación y `assertExactJson` |
| R10 | menor | resuelto (T043) | `AgendaByRoleTest`: `assertConflict()` con el mensaje exacto |
| R5–R9, R11, R12 | menor | aceptados → roadmap | Ver abajo |

## Hallazgos
Ninguno nuevo.

## Preparación para release
- Rollback factible: sí; `docker/prod/rollback.sh` a la imagen anterior, sin datos que revertir.
- Migraciones compatibles con la versión anterior: no hay migraciones ni cambios de configuración.
- Docs actualizadas: `architecture.md` (T090), `observability.md` (T091) y `security.md` (T036);
  `deployment.md` sin cambios necesarios.

## Tareas añadidas
- T041 ← R1
- T042 ← R2, R3
- T043 ← R4, R10

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-18:
- **R5** → roadmap: `doctorScope()` duplicado en los dos casos de uso de listados.
- **R6** → roadmap: capacidades de la agenda y del expediente deducidas de nombres de rol en Blade.
- **R7** → roadmap: contrato de `currentActorId()` (docblock frente a lo que comprueba).
- **R8** → roadmap: los 403 nombran el permiso interno (transversal desde 014).
- **R9** → roadmap: el selector de doctores devuelve email, rol y estado del staff.
- **R11** → roadmap: `role="alert"` aparte al fallar la carga del registro clínico.
- **R12** → roadmap: "Prescripciones" como `h3` dentro de "Registro clínico".
