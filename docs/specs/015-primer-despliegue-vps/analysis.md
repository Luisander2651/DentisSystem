---
result: pass
round: 4
mode: delta
constitution_version: 1.1.2
date: 2026-09-29
spec_sha: 9c5bb9d699ae
plan_sha: 602bf436b8db
tasks_sha: 4b0df73fa037
---

# Análisis · 015 Despliegue de producción en el VPS

## Resumen
**pass.** No hay hallazgos CRÍTICOS ni ALTOS.

Modo delta: el diff de 387 líneas contra la ronda 3, más el seguimiento de A51–A59:
- 6 resueltos;
- 3 resueltos con efecto nuevo: A51 → A62 y A63, A53 → A60, A54 → A61.

Conteo nuevo: 0 CRÍTICOS · 0 ALTOS · 4 MEDIOS · 3 BAJOS. El usuario los aceptó el 2026-09-29:
- A61, con una aclaración de redacción en CA18 (→ spec);
- el resto, como notas de tarea para `/implement`.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 19 / 19 |
| Amenazas con control y test | 15 / 15 |
| Principios de la constitución evaluados | los que toca el delta (P11, P12): ✅ con los aceptados |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A51 | ALTA | resuelto con efecto nuevo (→ A62, A63) | `name: dentissa`, volúmenes `external` con `${DATA_VOLUME_PREFIX:?}`; la pila manual es el proyecto `dentissystem` |
| A52 | ALTA | resuelto | clon en `/home/deploy/dentissa`; `DentisSystem` intacto (T050); `--to-manual` con `compose start` |
| A53 | ALTA | resuelto con efecto nuevo (→ A60) | controlador en CA18 y T047; `EmailIntegrationTestCase` sin eventos fingidos |
| A54 | ALTA | resuelto con efecto nuevo (→ A61) | `zend.exception_ignore_args=On` (D20, T032, T018); casos de worker en T007 y T008 |
| A55 | MEDIA | resuelto | T051 vuelve con `--first` |
| A56 | MEDIA | resuelto | `compose.sh` con valor neutro; T035 y T044 sin `.deploy/current` |
| A57 | BAJA | resuelto | binding contextual sustituido solo en el test |
| A58 | BAJA | resuelto | etiquetas completas; ninguna etapa con `:latest` |
| A59 | BAJA | resuelto | `dentissa-*`, D9 y orden de Decisiones |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A60 | Dependencias | MEDIA | T041, T047 | Con cola `sync`, la traza del controlador solo desaparece con T047, así que el "hecho cuando" de T041 (T008 completo) es circular. | Aceptado → nota de T041 |
| A61 | Inconsistencia (P11) | MEDIA | spec CA18, plan D20 | CA18 decía "ni trazas", pero el `report()` del worker escribe la traza del framework (sin argumentos por D20). | Aceptado → spec: redacción aclarada (decisión del usuario, 2026-09-29) y nota de T007 y T008 |
| A62 | Cobertura de riesgos (P12) | MEDIA | `docker-compose.prod.yml`, D18, T034 | Con volúmenes de datos compartidos y rollback en los dos sentidos, versiones más nuevas de Postgres, Redis, Loki o Grafana podrían dejar datos ilegibles para la pila manual. | Aceptado → nota de T017, T034 y T058 |
| A63 | Viabilidad | MEDIA | T058 | Faltan precondiciones: la pila de desarrollo choca con la manual, la pila manual necesita sus dependencias, el clon necesita un tag, y los permisos solo se comprueban en Linux. | Aceptado → nota de T058 |
| A64 | Inconsistencia | BAJA | plan fila `verify.sh`, T018 | La comprobación "las dos `db` en marcha" está en T018 y en Riesgos, pero no en la fila del plan. | Aceptado → nota de T018 |
| A65 | Ambigüedad | BAJA | T035 | En local solo se puede ejecutar el modo `--from-manual`. | Aceptado → nota de T035 |
| A66 | Cobertura de riesgos | BAJA | `deploy.sh --first`, `--to-manual` | `storage/app/public` no se sincroniza entre pilas tras el primer cambio. | Aceptado → nota de T057 y T058 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-09-29:
- **A60** → nota de T041: se da por hecha con los casos de T008 que no dependen del log de `SendResetPasswordEmailController`; T047 completa T008.
- **A61** → spec: CA18 permite que la traza del sistema de un trabajo en segundo plano indique archivos y líneas, sin argumentos ni datos personales (decisión del usuario, 2026-09-29).
- **A61** → nota de T007, T008: el caso del worker comprueba, sobre la salida formateada del log (no solo sobre el contexto del spy), que no hay argumentos ni datos personales.
- **A62** → nota de T017, T034, T058: Postgres, Redis, Loki y Grafana de producción tienen la misma versión que en `4b3aecf` (T017 lo compara). T058 arranca la pila manual después de que producción haya escrito datos y comprueba que los lee.
- **A63** → nota de T058: se anotan las precondiciones antes de empezar: pila de desarrollo bajada, dependencias de la pila manual instaladas, tag en el clon, y ejecución en WSL o Linux.
- **A64** → nota de T018: `verify.sh --local` sale con código ≠ 0 si la `db` de la pila manual y la de producción están en marcha a la vez.
- **A65** → nota de T035: en local se prueba `--from-manual` contra la pila de desarrollo; el modo de producción se prueba en T058.
- **A66** → nota de T057, T058: `deployment.md` documenta que `storage/app/public` no se sincroniza entre pilas tras el primer cambio; T058 lo comprueba subiendo un archivo en cada pila.
