---
spec: 028-paginacion-de-listas
verdict: approved
round: 3
date: 2026-11-20
base: 59be294
head: 35f1b09
human_signoff: Luisander2651 2026-11-20 (ronda 3)
---

# Review · 028 Paginación de las listas del panel y del portal

## Resumen
Ronda 3 (`--rerun`) sobre `59be294..35f1b09`: el único importante abierto de la ronda 2 (R28) está
corregido por T039, y el diff no trae hallazgos nuevos. La suite, el build, el validador y gitleaks
están en verde. Sin bloqueantes ni importantes: `approved`, pendiente de la firma del dueño del
repositorio (constitución → definición de terminado).
Conteo: bloqueante 0 · importante 0 · menor 0

Revisor: la sesión principal, releyendo el diff desde cero, sin subagentes, porque `code.diff`
pesa 7 KB (un solo archivo, `tests/Browser/screens.json`). Sin revisores de accesibilidad ni de SEO:
la ronda no toca vistas, JS de la interfaz ni rutas públicas.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2842 passed, sobre `3db00be` (el commit siguiente solo cambia docs) |
| `vendor/bin/pint --test` | ➖ la ronda no cambia PHP (ronda 2: passed) |
| `npm run build` | ✅ |
| `npm run test:ui -- --only pacientes-direccion` | ✅ dos veces (3 visitas cada una). Comprobado en rojo sin `search` ("de 29") y sin `status` ("de 26") en `resources/js/pages/patients/index.js`. `ui-audit` completo no se repite: el código de la aplicación no cambia desde la ronda 2, que lo pasó entero |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA7, CA9–CA14 | Ver ronda 2 (`history/review.r2.md`) | ✅ | Sin cambios en esta ronda |
| CA8 | `PaginationScriptTest`; `expect.address` en `pacientes-buscar-otra-pagina` y `pacientes-pagina-2`; `pacientes-direccion` responde solo a `page` 2, `search` "prueba" y `status` "active" y exige "Mostrando 13–24 de 40" | ✅ | R28 corregido (T039) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea).
- Partes del plan sin implementar: ninguna (0 tareas sin diff).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ✅ | T039 comprobada rompiendo el envío de `search` y de `status` |
| Resto | ✅ | Sin cambios en el código de la aplicación desde la ronda 2 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`59be294..HEAD`) | ✅ 0 hallazgos |
| semgrep `p/php` · trivy config | ➖ la ronda no toca `app/`, `config/`, `bootstrap/`, `routes/` ni `docker/` (ronda 1: 0 hallazgos) |
| `composer audit` · `npm audit --audit-level=high` | ➖ `deps.stat` vacío (ronda 2: 0 vulnerabilidades) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| Todos | ➖ | La ronda solo cambia datos de prueba del navegador |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (spec → "Auditoría": ninguno).
- Datos sensibles en logs del código tocado: no hay código de la aplicación tocado.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R28 | — | tests/Browser/screens.json:3746 | Corregido (T039) | — |

## Preparación para release
- Rollback factible: sí (`docker/prod/rollback.sh`; sin esquema que revertir; el formato del log de nginx vuelve con la imagen `web`).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D4).
- Docs actualizadas (architecture, deployment): sí (T090, T091).
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: RS9.c declarada mitigada por T031 con su test y pendiente de verificar en Loki (T097); RS15.a sigue pendiente, sin marcarse mitigada.

## Tareas añadidas
- Ninguna en esta ronda (T039 ← R28 se añadió en la ronda 2).

## Aceptados sin tarea
- **R10** → roadmap: carrera entre contar y leer una página da 500 (`Page::create`)
- **R11** → roadmap: búsqueda sin acentos duplicada en Patients y Tratamientos (`/refactor` pendiente)
- **R12** → roadmap: el test de la barra invertida no protege su escape
- **R13** → roadmap: casos de uso y DTOs sin uso en Users y Appointments (`/refactor` pendiente)
- **R14** → roadmap: `error_log` de nginx con la consulta en errores de upstream
- **R15** → roadmap: "Citas sin cerrar" sin test de desempate por id
- **R16** → roadmap: tres formas de pasar la página al caso de uso (`/refactor` pendiente)
- **R17** → roadmap: los casos `counts` del 500 de Contenido no llegan a `countByStatus`
- **R18** → roadmap: `counts.total` de galería con `status` de texto libre
- **R19** → roadmap: duplicación de las listas de Contenido y del cargador paginado (`/refactor` pendiente)
- **R20** → roadmap: dependencia de autorización sin uso en los casos de uso públicos de Contenido (`/refactor` pendiente)
- **R21** → roadmap: `href` del control con la búsqueda anterior y sin `pestana`
- **R22** → roadmap: el control intercepta Ctrl/Cmd + clic
- **R23** → roadmap: foco perdido si falla el cambio de página
- **R24** → roadmap: CA6 en navegador sin comprobar la ausencia del control
- **R25** → roadmap: números de `meta` sin forzar en `innerHTML`
- **R26** → roadmap: el anuncio no dice el rango ni el total
- **R27** → roadmap: `estado` en inglés en la dirección
- **R29** → roadmap: espacio tras `=` en `GAP` de `pagination.js`
- **R30** → roadmap: espacio tras `:` en `texts` de `checks-dialogs.mjs`
