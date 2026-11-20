---
result: fail
round: 3
mode: delta
constitution_version: 1.2.1
date: 2026-11-18
spec_sha: 6cff30b8c918
plan_sha: 4de89b1bee04
tasks_sha: bbf273948883
---

# Análisis · 028 Paginación de las listas del panel y del portal

## Resumen
No pasa por un hallazgo alto nuevo: el control de T031 no fija que `access_log` vaya dentro de
cada bloque `server`, y un `access_log` a nivel `http` se suma al de la imagen de nginx (con la
consulta) en lugar de reemplazarlo (A37). Los 13 hallazgos abiertos de la ronda 2 quedan
resueltos (A27 y A31 con un efecto nuevo; A32 en parte). Delta con subagente independiente
(perfil `standard`) sobre las secciones y tareas corregidas; la copia de la ronda 2 no se guardó,
así que el delta se acotó a mano a esas secciones.
Conteo (nuevos): CRÍTICA 0 · ALTA 1 · MEDIA 3 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 14 / 14 |
| Amenazas con control y test | 8 / 8 (TM8 con control incompleto: A37) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A15 | MEDIA | resuelto | T091 (RS15.a por corrección); T012 |
| A22 | BAJA | resuelto | Plan Trazabilidad CA12 y T027 coinciden (5 y 100, `LIMIT`/`OFFSET`) |
| A26 | CRÍTICA | resuelto | T013 (interfaz, enlace, `AppointmentsArchitectureTest`); comentario obsoleto → A41 |
| A27 | CRÍTICA | resuelto con efecto nuevo (→ A37) | T031, TM8, T097 |
| A28 | ALTA | resuelto | T028 y T029 cubren los 11 archivos con `AppointmentPage` |
| A29 | ALTA | resuelto | T010 |
| A30 | ALTA | resuelto | T005 (13 usuarios y 13 tratamientos); orden de los datos → A38 |
| A31 | ALTA | resuelto con efecto nuevo (→ A39) | T027, D15 |
| A32 | MEDIA | resuelto en parte (→ A40) | T026, T027, T028, T030 |
| A33 | MEDIA | resuelto | T002 |
| A34 | BAJA | resuelto | Plan → Contratos |
| A35 | BAJA | resuelto | Plan; T008 |
| A36 | BAJA | resuelto | Plan CA1 ("de 25") |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A37 | Seguridad (P11, TM8) | ALTA | T031; plan TM8 y P11; `docker/nginx/prod.conf` (servidores 80 y 443), `default.conf` | Los `.conf` van en `conf.d/` (contexto `http`); un `access_log` ahí se suma al `access_log … main` de la imagen (con `$request`) en vez de reemplazarlo. El test descrito pasaría con un `access_log` a nivel `http` | `/plan 028 --fix A37`, `/tasks 028 --fix A37`: `access_log` con el formato propio dentro de cada `server` (también el del puerto 80); `NginxProdConfTest` recorre todos los `server` y comprueba que cada uno lo declara y que no hay otro `access_log`; comprobación manual con `?search=` en `docker compose logs nginx` |
| A38 | Ambigüedad / pruebas | MEDIA | T005, T012, T026, T027 | Los datos nuevos sin orden fijo empujan a la página 2 los elementos que pulsan entradas con id fijo (`usuarios-editar`, `usuarios-eliminar`, `portal-detalle-*`, `expediente-*-cita-detalle`) | `/tasks 028 --fix A38`: los datos nuevos son más antiguos que los existentes; `UiAuditSeederTest` comprueba que los ids fijos de las entradas caen en la página 1 |
| A39 | Inconsistencia | MEDIA | T027; `resources/js/pages/portal/home.js`; `resources/js/ui/appointments-history.js` | T027 no lista los archivos que abren el detalle ni fija cómo le pasan la cita | `/tasks 028 --fix A39`: listar esos archivos y el mecanismo; una entrada que responde `{data: []}` a `/api/v1/portal/appointments` y sigue mostrando el detalle |
| A40 | Constitución (P2, D13) | MEDIA | T029 (`--only`) | El `--only` de T029 es más corto que el de T028 sobre las mismas pantallas | `/tasks 028 --fix A40` |
| A41 | Inconsistencia | BAJA | T013; cabecera de `AppointmentsArchitectureTest.php`; `Appointments/Integration/UnexpectedErrorTest.php` L29 | Dos comentarios dirán que `TreatmentsService` depende de Eloquent | `/tasks 028 --fix A41` |

## Decisiones pendientes del usuario
Ninguna.

## Enmiendas propuestas a la constitución
- Las de las rondas 1 y 2 (P2, P3, P11).

## Aceptados
Ninguno.
