---
spec: 016-sistema-de-diseno-aplicado
verdict: approved
round: 3
date: 2026-10-14
base: 630592a
head: 2887982
human_signoff: Luisander2651 (2026-10-14)
---

# Review · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

Ronda 3 (`--rerun`) sobre la ronda 2 ([review.r2.md](history/review.r2.md); la ronda 1 está en
[review.r1.md](history/review.r1.md)). Revisa los hallazgos que se decidió corregir (R62, R63,
R64), el diff `630592a..2887982` (3 archivos de código, 13 KB) y la verificación automática. Con
un diff de ese tamaño la revisión se hizo sin subagentes, releyendo los archivos desde el disco y no
desde la memoria de la sesión. No reabre los aceptados de las rondas anteriores. Quedan fuera los
cambios sin comitear de `app/Modules/Appointments/`, que son trabajo ajeno a esta spec.

## Resumen
**approved.** Confirmación humana: Luisander2651, 2026-10-14. Los tres importantes de la ronda 2
están corregidos y probados. El error que pone el servidor en un campo sobrevive a pasar por él
(R62). El error de un campo se anuncia en una región propia que se vacía al corregirlo y se va con
el diálogo (R63). Una prescripción con solo indicaciones pide sus datos y frena el envío (R64).
Queda un menor nuevo, aceptado al roadmap.

Conteo de la ronda: bloqueante 0 · importante 0 · menor 1

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel --recreate-databases` (local, con los cambios ajenos apartados) | ✅ 1248 pasan; 3 `PDOException` intermitentes de PostgreSQL en paralelo que pasan en serie (31/31) |
| Pint sobre los PHP del PR (CI) | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` | ✅ dos veces en CI sobre `6513294` (push 38654866077 y PR 38654869183) y una en local (224 visitas; la única espera agotada, `tratamientos-ver` a 390, pasó en dos repeticiones) |
| `npm audit --audit-level=high` · `composer audit --locked` | ✅ 0 · sin avisos |
| `python .ai/bin/aidd.py validate docs/specs/016-sistema-de-diseno-aplicado` | ✅ 0 errores |

## Criterios de aceptación
Los 43 criterios tienen su test y pasan. CA37, observado en la ronda 2, queda cubierto por los
casos R62, R63 y R64 de `checks-a11y`, que fallaron antes de cada corrección.

## Conformidad con el plan
- Cambios fuera del plan: ninguno; `scope.md` no da archivos sin tarea ni tareas sin diff.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a las rondas anteriores: P1–P13 y P15 ✅ o ➖; P14 ❌ aceptado por el usuario
el 2026-10-06. Definición de terminado: revisión humana registrada arriba.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks, semgrep `p/php`, SCA y trivy (CI del PR) | ✅ sin hallazgos |

El diff no añade entradas, permisos ni salida sin escapar: la región de anuncio del campo se
escribe con `textContent`.

## Observabilidad
- Sin logs ni eventos de auditoría nuevos (P14 aceptado).

## Seguimiento de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R62 | corregido | `components/ui/input.blade.php`: el componente marca sus errores con `data-ui-own-error` y, al salir del campo, no quita uno ajeno; caso en `checks-a11y` (`checkServerErrorStays`) |
| R63 | corregido | `components/ui/input.blade.php`: región `aria-live` propia del campo, vaciada al corregirlo; casos en `checks-a11y` (`checkFieldAnnouncementInDialog`) |
| R64 | corregido | `agenda/complete-appointment.js`: `collectPrescriptions` cuenta las indicaciones como dato de la fila, igual que `syncPrescriptionRequired`; caso en `checks-a11y` (`checkPrescriptionWithInstructionsOnly`) |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R75 | menor | `resources/views/components/ui/input.blade.php` (`validateInput`, anuncio al salir) | Al salir dos veces seguidas de un campo con el mismo error, la región del campo se vacía y se reescribe en la misma tarea: el texto no llega a cambiar y algunos lectores de pantalla no repiten el aviso | Reescribirlo tras una pausa breve, como hace `ui/status.js` con un mensaje repetido |

## Preparación para release
- Rollback factible: sí, redesplegar `v0.1.0`; sin migraciones ni cambios de API.
- Feature flags: el plan no pide ninguno.
- Docs: `docs/architecture.md` (T090) y `docs/deployment.md` (T091) al día.
- SCA: sin vulnerabilidades críticas ni altas; RS16.a mitigada por corrección.

## Tareas añadidas
Ninguna en esta ronda.

## Aceptados sin tarea
Aceptado por el usuario el 2026-10-14:
- **R75** → roadmap: el anuncio del error de un campo no se repite si se sale dos veces seguidas con el mismo error.
