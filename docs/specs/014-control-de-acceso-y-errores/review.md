---
spec: 014-control-de-acceso-y-errores
verdict: approved
round: 3
date: 2026-09-25
base: 328aed9
head: 364adec
human_signoff: pending
---

# Review · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Ronda 3 (`--rerun`) sobre la ronda 2 ([review.r2.md](review.r2.md)). Revisa los hallazgos abiertos
R30–R36, el diff `328aed9..364adec` y la verificación automática completa. No reabre los menores
aceptados en rondas anteriores.

## Resumen
**approved.** R30–R36 quedaron corregidos y el diff no introduce hallazgos nuevos. Falta la
confirmación humana (`human_signoff`) antes de `/release`.

Conteo de la ronda: 0 bloqueantes · 0 importantes · 0 menores.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie) | ✅ 800 passed (10158 aserciones), 0 fallos |
| `vendor/bin/pint --dirty` | ✅ |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores (2 avisos previos: tamaño de la spec y "riesgo 1" en el Historial) |

## Seguimiento de la ronda 2
| R | Estado | Evidencia |
|---|---|---|
| R30 | corregido | `RecordsScreenTest.php:65`: la captura `((?:(?!</thead>).)*)` no cruza otro `</thead>`. Comprobado a mano: con la cabecera "Acciones" de `address-table` cambiada, el caso positivo falla para administrador y asistente |
| R31 | corregido | Nota de T083 en `tasks.md`: 9 dependencias transitivas con versión; `npm view`: MIT, sin scripts de instalación, más de 7 días |
| R32 | corregido | `GlobalErrorFallbackTest.php:96` usa `"\n"` |
| R33 | corregido | `docs/security.md:118`: RS16 tras RS1 y RS2 (Alta), antes de RS3 |
| R34 | corregido | `docs/security.md:89`: EX1 lo formula como condición y describe el estado real (`vite.config.js:18` escucha en `0.0.0.0`, `docker-compose.yml:20` publica `5173:5173`); deuda en el roadmap |
| R35 | corregido | Historial de `spec.md` en orden cronológico; cita T077–T086 |
| R36 | corregido | `dashboard.blade.php:40` con texto neutro; `StaffNavigationTest.php:56-57` lo afirma y niega "de tus pacientes" |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos, SAST, contenedores | No ejecutados: no instalados (R27, aceptado en la ronda 1) |
| SCA (`composer audit`, `npm audit`) | Sin cambios de dependencias en el rango; mismo resultado que la ronda 2, cubierto por EX1 |

Sin cambios en rutas protegidas. Sin datos sensibles nuevos en logs ni en respuestas.

## Observabilidad
- Eventos de auditoría: no aplica (diferidos; P14 aceptado).
- Datos sensibles en logs del código tocado: ninguno (el rango solo toca tests, un texto de vista y docs).
- `request_id`: no (P14 aceptado).

## Hallazgos
Ninguno nuevo.

## Preparación para release
- Rollback factible: sí (revert del merge; sin migraciones).
- Migraciones: no hay.
- Docs: `architecture.md` y `observability.md` al día; `security.md` con RS16 y EX1 (con su condición).
- SCA: sin críticas ni altas fuera de la excepción EX1 vigente.
- Cobertura de riesgos: sin cambios respecto a la ronda 2; RS16.a aceptada (EX1).

## Tareas añadidas
Ninguna.
