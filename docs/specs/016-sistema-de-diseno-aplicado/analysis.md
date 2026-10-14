---
result: fail
round: 4
mode: full
constitution_version: 1.2.0
date: 2026-10-13
spec_sha: 751d42578f7a
plan_sha: 618983a60e4e
tasks_sha: 4a38819dce45
---

# Análisis · 016 Sistema de diseño aplicado

## Resumen
Análisis completo: la constitución pasó de 1.1.2 a 1.2.0 (P15, T007) y la spec amplió su alcance
a 768 px el 2026-10-11. No hay críticos. El resultado es `fail` porque el usuario decide corregir
los dos ALTOS: el corte de 1024 px no tiene criterio ni test, y a 768 px hay menús y un diálogo
sin revisar (A75); además, un error de campo no queda ligado a su campo (A82). El resto son restos
de la redacción anterior a 768 px y documentación del plan que no siguió a la implementación.
Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 9 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 41 / 41 (el corte de 1024 px no tiene criterio: A75) |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A57–A72, A74 | ALTA–BAJA | resuelto | Notas en las tareas que citan cada ID; `aidd.py validate` sin errores |
| A73 | BAJA | abierto (→ A88) | Aceptado "para la próxima edición"; la spec se editó el 2026-10-11 sin aplicarlo |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A75 | Cobertura / Constitución P2 | ALTA | `spec.md` → Decisiones 2026-10-11; `tasks.md` (ninguna tarea); `tests/Browser/screens.json` | "La barra lateral del panel y el menú del sitio público se despliegan desde 1024 px" no tiene criterio, tarea ni test. `inicio-menu` y `dashboard-menu` solo se recorren a 390 px, y el menú plegable abierto, única navegación a 768 px, no se audita a ese ancho. | Debe existir un criterio para el corte de 1024 px y una tarea con test que falle sin el cambio. `ui-audit` revisa los dos menús abiertos a 768 px y, a 1024 px, que la barra lateral y el menú del sitio se ven desplegados sin desborde. `/specify --edit`, luego `/plan --fix A75`, luego `/tasks --fix A75` |
| A76 | Inconsistencia | MEDIA | `tasks.md` → Convenciones ("'Pasa' significa sin fallos ahí, a 390 y 1440 px"); T005; `plan.md` → Estrategia de pruebas → Navegador y Trazabilidad CA13 ("a 390 × 844 y 1440 × 900"); `ui-audit.mjs:4`; fila RNF del plan sin "ni contenido fuera de su caja" | Las definiciones de qué recorre `ui-audit` y cuándo "pasa" una tarea contradicen el RNF ("390, 768 y 1440 px"). | Que digan 390 × 844, 768 × 1024 y 1440 × 900, con las excepciones nombradas. Lo comprueba la ejecución por defecto de `npm run test:ui`. Las capturas de CA23 siguen a 390 y 1440. `/plan --fix A76`, `/tasks --fix A76` |
| A77 | Cobertura (CA12, CA13, CA26) | MEDIA | `screens.json` `agenda-dia` solo a 1440; T005, T067; `system.md` ("el diálogo solo existe desde 768 px") | "Citas del día" existe a 768 px, ancho obligatorio, y no se audita a ese ancho. `AGENTS.md` y `system.md` afirman que se revisa cada diálogo a 768. | `agenda-dia` se recorre a 768 y 1440 px con `checks-dialogs` sin fallos. Con la tarea de A75 |
| A78 | Inconsistencia | MEDIA | `system.md` → Inventario ("capturada (solo existe a 390 px)") | Los menús plegables existen ahora por debajo de 1024 px, también a 768. | Corregir el texto. Las capturas siguen a 390. Con la tarea de documentación de A75 |
| A79 | Inconsistencia | MEDIA | `spec.md` → Fuera de alcance ("no cambian … la navegación"); Decisiones 2026-10-06 ("¿En qué anchos se comprueba? · 390 y 1440 px") | La navegación sí cambia entre 768 y 1023 px, y la fila antigua no dice que la sustituye la del 2026-10-11. | Fuera de alcance admite el cambio de navegación por debajo de 1024 px, y la fila del 2026-10-06 remite a la del 2026-10-11. `/specify --edit` |
| A80 | Constitución P1 (procedimiento) | MEDIA | `spec.md` → Historial (última fila 2026-10-07) | El cambio de alcance del 2026-10-11 (RNF y decisión, con fuente "usuario") no tiene fila en Historial ni reaprobación. | Una fila con el cambio y su aprobación. Lo comprueba `aidd.py validate`. `/specify --edit` |
| A81 | Constitución / Inconsistencia | MEDIA | `plan.md` frontmatter `constitution_version: 1.1.2`; Constitution Check de plan y tareas (P1–P14, P2 "❌ aceptado") | El plan no se reevaluó con la 1.2.0: falta P15, y P2 sigue como desviación aunque la 1.2.0 ya la admite. Evaluación propia: P15 se cumple (`DesignTokensTest`, `OnlyDesignTokensTest` con `pending-files.php` vacío, `test:ui` bloqueante en CI). | Fila de P15 con sus tests, P2 al día y versión 1.2.0 en plan y tareas. `/plan --fix A81` |
| A82 | Cobertura (CA37) | ALTA | CA37; nota de T038; `resources/js/pages/auth/reset-password.js` (`announceError('Las contraseñas no coinciden.')`) | CA37 exige que el error de un campo quede asociado a ese campo. "Las contraseñas no coinciden" va solo a la región de estado. | El error queda ligado al campo de confirmación (marcado inválido y descrito por el mensaje), y `checks-a11y` cubre la validación en el navegador de restablecer contraseña con un caso que falla sin la corrección. `/tasks --fix A82` |
| A83 | Cobertura (RNF WCAG 2.1 AA) | MEDIA | Notas de T035 y T038 | Pendientes de la revisión de accesibilidad sin decisión ni deuda: los enlaces que abren otra pestaña no lo avisan, el menú plegable no se cierra con Escape y no se anuncia "Enviando…" (4.1.3). | Cada uno se corrige con su caso en `ui-audit`, que falla sin la corrección. `/plan --fix A83`, `/tasks --fix A83` |
| A84 | Cobertura (verificación manual) | MEDIA | T044, T067, T070 ("con un lector de pantalla…"); `plan.md` → Trazabilidad CA12, CA37 y "Manual" | Se cerraron sin la comprobación con lector de pantalla que pedía su condición de cierre. | Decisión del usuario: la sustituyen las revisiones automáticas de `ui-audit` (`checks-a11y`, `checks-dialogs`). El plan lo refleja. `/plan --fix A84` |
| A85 | Conflicto con otras specs | MEDIA | `015-primer-despliegue-vps/plan.md` ("ninguna nueva (spec 016)"; "La retención de Loki va en la 016") | La 016 renumeró el monitoreo a 017 y corrigió la spec de la 015, pero no su plan. | El plan de la 015 apunta a la 017, con fila en su Historial. Con la tarea de documentación de A75 |
| A86 | Inconsistencia / dependencias | BAJA | `plan.md` → P10 ("`package.json` solo gana scripts"); `package.json` `overrides.shell-quote`; RS16.a | El plan no refleja el override aprobado (nota de T002), y su retirada no tiene destino. La verificación de la dependencia sí está registrada. | El plan menciona el override, y su retirada queda en roadmap o en `security.md`. `npm audit` en CI lo sigue comprobando. `/plan --fix A86` |
| A87 | Inconsistencia | BAJA | `plan.md` → Errores web; archivos de T071 (`routes/web.php`) frente a su nota (`bootstrap/app.php`, `then:`) | El plan no dice dónde vive la ruta de respaldo. | Plan al día. Lo fijan `NotFoundPageTest` y `ApiNotFoundContractTest`. `/plan --fix A87` |
| A88 | Ambigüedad (redacción) | BAJA | `spec.md` → Diseño ("dos nuevas"); `plan.md` → Enfoque; T076 ("las 4 nuevas") | Sigue abierto A73. | Cuatro vistas de error y dos capturas nuevas (A58). `/specify --edit`, `/plan --fix A88` |
| A89 | Constitución (redacción) | BAJA | `docs/constitution.md` frontmatter `last_amended: 2026-09-24`; tabla de Enmiendas | La fecha no recoge la enmienda 1.2.0 del 2026-10-08, y la tabla no está en orden. | `last_amended: 2026-10-08` y filas en orden. Con la tarea de documentación de A75 |
| A90 | Ambigüedad (supuesto) | MEDIA | `ui-audit.mjs` (`mobile: width < 768`; 768 se audita como escritorio con ratón); CA9 | A 768 px se usa el menú plegable, una interfaz táctil, pero no se exige el tamaño táctil, y no hay decisión que lo excluya. | Decisión del usuario: los 44 × 44 px de CA9 se exigen también a 768 px, y `checks-global` los mide a ese ancho. `/specify --edit` (CA9), `/plan --fix A90`, `/tasks --fix A90` |

Comprobado sin hallazgo: `.ai/project.yaml` (anchos, `constitution_version` y `design.*`);
frontmatter y reglas de `system.md`, `AGENTS.md`, `CHANGELOG.md` y `architecture.md` con 768 y
1024; el código usa `lg:` y `innerWidth >= 1024` como dice la decisión; las capturas a 390 y 1440
coinciden con CA23; la composición se compara solo a 1440; RS16.a mitigada dentro de alcance y
coherente con `security.md`; DS1–DS11, DS13 y DS14 "resuelta por 016", y DS12 en el roadmap;
`extends: [014]` sin criterio contradicho; `pending-files.php` vacío; TM5 con control y test.

No se pudo comprobar en el análisis: el comportamiento con lector de pantalla (cubierto por la
decisión de A84) y el contenido entre 1024 y 1279 px, que no es ancho obligatorio.

## Decisiones pendientes del usuario
Resueltas el 2026-10-13:
- A75 y A77: se añade criterio y test para el corte de 1024 px, y se auditan a 768 px los menús abiertos y "Citas del día".
- A82: se corrige ahora.
- A84: bastan las revisiones automáticas de `ui-audit`; no se hace la prueba con lector de pantalla.
- A83 y A90: se corrigen los tres pendientes de accesibilidad, y los 44 px se exigen también a 768 px.

## Aceptados
Ninguno en esta ronda: todos los hallazgos se corrigen antes de `/review`.
