---
result: fail
round: 2
mode: full
constitution_version: 1.1.2
date: 2026-10-07
spec_sha: 8c926574a521
plan_sha: c84b1e75530d
tasks_sha: 6561df4ba8d4
---

# Análisis · 016 Sistema de diseño aplicado

## Resumen
`fail`. Sin hallazgos críticos. De los 31 hallazgos de la ronda 1, 28 quedaron resueltos (8 con
efecto nuevo) y 3 abiertos en parte. La versión 2 introdujo 5 hallazgos altos: una colisión de
nombres de token que pintaría mal los campos, siete tareas con un criterio inalcanzable en su
orden, mensajes que no se anunciarían con un diálogo abierto, la operación con teclado sin test y
la limpieza del seeder sin sitio ni prueba. Análisis completo (cambió más del 40 % y la spec ganó
criterios), hecho por un revisor independiente de contexto limpio, en solo lectura.

Conteo de hallazgos nuevos: CRÍTICA 0 · ALTA 5 · MEDIA 14 · BAJA 6

`aidd.py validate`: 0 errores, 1 aviso (spec grande). A32 se comprobó después compilando el tema:
`text-field` genera `color: var(--color-field)`, no el tamaño de letra.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 39 / 39 (con hueco real en CA6, CA14, CA29, CA32 y CA37) |
| Amenazas con control y test | 8 / 8 (TM5 parcial) |
| Principios de la constitución evaluados | 14 / 14, más Restricciones y Definición de terminado |

Tareas: 89 (82 de trabajo y 7 fijas); ninguna con más de 3 archivos; 13 `[P]` sin archivos
compartidos; sin dependencias a tareas inexistentes ni ciclos. La tabla de Cobertura coincide con
los `cubre:` fila por fila; la de amenazas tiene 3 discrepancias (A53).

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | Plan "Endpoints y datos", TM4; T080 antes de T071 |
| A2 | ALTA | resuelto con efecto nuevo (→ A38, A39, A40) | `--color-*: initial`, tokens `on-dark`, `overlay`, `whatsapp`; T011, T025 |
| A3 | ALTA | abierto en parte (→ A32, A40) | Nombres fijados y probados, pero dos tokens colisionan y ningún test afirma que las utilidades existan en el CSS compilado |
| A4 | ALTA | resuelto | Token `primary-hover` con par 5.61:1; T010, T020, T026 |
| A5 | ALTA | resuelto | Cuerpo JSON fijo; T014 y T015 con `Accept: application/json` |
| A6 | ALTA | resuelto con efecto nuevo (→ A37) | T076 con `ui-audit --screenshots` y datos del seeder |
| A7 | ALTA | resuelto | P2 ❌ aceptado con usuario y fecha; CA24; T007 |
| A8 | ALTA | resuelto con efecto nuevo (→ A34, A35) | CA35–CA38, `checks-a11y` (T081), revisión por grupo |
| A9 | ALTA | resuelto con efecto nuevo (→ A33) | `screens.json` con cada diálogo en cada pantalla que lo monta |
| A10 | MEDIA | resuelto | Decisión en la spec (CA22, CA34); T051, T052, T055, T058, T061 |
| A11 | MEDIA | resuelto | `on-dark` con `data-surface="dark"`; T025, T035, T020 |
| A12 | MEDIA | resuelto con efecto nuevo (→ A37) | Línea base sin textos ni niveles, con diferencias admitidas |
| A13 | MEDIA | resuelto | Importado desde `app.js`; T030 |
| A14 | MEDIA | resuelto | T012 sin `[P]` y dependiente de T011 |
| A15 | MEDIA | resuelto | `routes/console.php` con `app()->environment()`; T003, T009 |
| A16 | MEDIA | abierto en parte (→ A46) | Falta cómo obtiene la sesión el script en CI |
| A17 | MEDIA | resuelto | Lista cerrada revisada por el usuario; T012 |
| A18 | MEDIA | resuelto | CA39 y "Cobertura de riesgos"; T002; T079 |
| A19 | MEDIA | resuelto | T007 actualiza `constitution_version` |
| A20 | MEDIA | resuelto con efecto nuevo (→ A36, A56) | T024 |
| A21 | MEDIA | abierto en parte (→ A51) | Ningún diálogo queda roto; el criterio de las tareas de script no nombra su comprobación |
| A22 | MEDIA | resuelto | Ruta fuera de `web`, TM8, caso en T015 |
| A23 | MEDIA | resuelto | T023 con `npm run brand:assets` |
| A24 | BAJA | resuelto | T070 incluye el lector de pantalla |
| A25 | BAJA | resuelto | Cifras comprobadas |
| A26 | BAJA | resuelto | P10: "sin cambios en dependencias" |
| A27 | BAJA | resuelto | `--radius-box`; capturas como referencia a 1440 px |
| A28 | BAJA | resuelto | Nota en "Qué ve cada rol" |
| A29 | BAJA | resuelto con efecto nuevo (→ A50) | T001 con aprobación del usuario |
| A30 | BAJA | resuelto | Definición de control táctil y `data-accent` |
| A31 | BAJA | resuelto | Imagen versionada; T023, T036, T018 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A32 | Inconsistencia (CA29, CA6) | ALTA | `plan.md` → "Tokens", `--color-field` y `--text-field`; T025, T027 | Los dos tokens generan la misma utilidad `text-field`, y Tailwind resuelve primero el color: pintaría el texto del campo en `#7c8aa0` (3.50:1) y nunca fijaría 16 px. Comprobado compilando el tema. | Ningún par de tokens comparte nombre de utilidad. `DesignTokensTest` falla si dos tokens de espacios distintos producen la misma utilidad. `/plan 016 --fix` |
| A33 | Inconsistencia (orden) | ALTA | `tasks.md` → convención de `ui-audit`; T041, T042, T045, T050, T055, T058, T061 | Esas tareas exigen que `ui-audit` pase incluyendo los diálogos que la pantalla monta, pero esos diálogos se migran después y dependen de ellas. Con el marcado antiguo no pueden pasar. T041 dice además que `dashboard.js` abre diálogos, y no lo hace. | Cada criterio de hecho es alcanzable en su punto del orden: ninguna tarea exige un módulo de `ui-audit` sobre una pieza aún sin migrar. `/tasks 016 --fix` |
| A34 | Cobertura (CA37) | ALTA | `plan.md` → "Región de estado"; T082, T081 y tareas de scripts | La región de estado va una por marco, fuera de los diálogos, y un `<dialog>` modal deja inerte el resto del documento: una región inerte no se anuncia. Casi todos los formularios del panel viven en diálogos. T081 solo comprueba que la región recibe el texto. Inferido del estándar HTML. | Un mensaje originado con un diálogo abierto se anuncia desde una región que no esté inerte. `checks-a11y` afirma que la región que recibe el mensaje está en el árbol de accesibilidad con el diálogo abierto, más verificación manual. `/plan 016 --fix` |
| A35 | Constitución (WCAG 2.1.1) | ALTA | `spec.md` RNF; `plan.md` → `checks-a11y`; T035, T065, T081 | Nada comprueba que lo que se acciona con ratón se accione con teclado. Hoy los días del calendario y las tarjetas de la galería pública son `div` con `click`; `ui-audit` enumera controles y un `div` clicable queda fuera de todas las medidas. | Todo elemento con acción es enfocable y se activa con teclado. `checks-a11y` falla ante un elemento con manejador de clic que no sea control nativo ni tenga rol y foco. `/plan 016 --fix` |
| A36 | Inconsistencia (TM5) | ALTA | `plan.md` → "Dónde corre"; T004, T024, T075 | El seeder "se retira con `--clean`", pero `db:seed` no admite opciones propias de un seeder y T004 solo lista el seeder. "Idempotente" y la limpieza, que borra datos de la base local, no tienen test. | El plan fija cómo se invoca la limpieza y T004 lista ese archivo. Test: sembrar dos veces no duplica, y limpiar borra solo lo marcado. `/plan 016 --fix` |
| A37 | Ambigüedad (CA22, CA23) | MEDIA | `plan.md` → "Dónde corre", "Composición a 1440 px"; T006, T076 | En local `ui-audit` corre sobre la base de desarrollo con lo que ya tenga; en CI, sobre una base recién migrada. La línea base cuenta acciones por región, que depende del número de filas. | `ui-audit` mide lo mismo en local y en CI: la línea base generada en local pasa sin cambios en CI. `/plan 016 --fix` |
| A38 | Ambigüedad (CA1, CA2, CA6) | MEDIA | `plan.md` → "Tokens"; T011, T036, T037 | Colores que sobreviven sin token ni regla: el panel de las pantallas de acceso (degradado rosa oscuro con blancos translúcidos), unas 130 utilidades con opacidad, 29 degradados y 24 sombras de color. `--color-*: initial` retira también `white` y `black` (`bg-white` ×224, `text-white` ×68). | El plan fija el aspecto del panel de acceso con su par de contraste y si un token admite opacidad, y en qué usos. Controles positivos en `OnlyDesignTokensTest`. **Decisión del usuario** (aspecto del acceso) |
| A39 | Cobertura (CA6) | MEDIA | `plan.md` → "Tokens"; T010, T020, T027 | 56 campos con texto de ejemplo y solo 3 con color propio: por defecto se pinta al 50 % del color del texto, unos 3.55:1. | El texto de ejemplo de un campo cumple 4.5:1. Par declarado y medición en `checks-global`. `/plan 016 --fix` |
| A40 | Cobertura (bloque transitorio) | MEDIA | `plan.md` → Rollout; T025 | "La app se ve igual que antes" no tiene comprobación. Un nombre antiguo omitido deja la utilidad sin CSS sin que falle nada. Unas 1 880 utilidades dependen del bloque. | Test: toda utilidad de color usada en `resources/` existe entre los tokens y el bloque transitorio mientras haya pendientes. `/plan 016 --fix` |
| A41 | Inconsistencia (orden; P2) | MEDIA | `tasks.md` → "cada fase termina con la suite Pest en verde"; T013–T019, T074, T048 | Los tests escritos antes siguen rojos hasta la fase 8, así que ninguna fase puede terminar en verde. T074 actualiza al final tests cuyo marcado cambia en T041 y T048–T050. | Definir qué subconjunto debe estar verde al cerrar cada fase; cada test existente se actualiza en la tarea que cambia su marcado. `/tasks 016 --fix` |
| A42 | Cobertura (CA32) | MEDIA | T018, T022 | "Sin dirección interna visible" se asigna a un test de la respuesta del servidor, pero las tarjetas se pintan en el navegador: pasaría antes y después. | Las tres partes de CA32 se miden sobre la tarjeta pintada, en `checks-screens`. `/tasks 016 --fix` |
| A43 | Cobertura (CA37) | MEDIA | T032, T035, T037, T039, T082 | La región no se monta en el marco del sitio público ni en cerrar sesión, que muestran mensajes dinámicos. `admin` y `patient` extienden `dashboard`: montarla en los tres la duplicaría. | Todo marco con mensajes dinámicos monta exactamente una región. `/tasks 016 --fix` |
| A44 | Cobertura (CA14) | MEDIA | T047 | Tratamientos pinta su título con `<x-ui.h1 as="h2">`, no con `page-hero`: T028 no le da `h1` y T047 no cubre CA14. | T047 declara CA14 y exige `PageStructureTest` en `/tratamientos`. `/tasks 016 --fix` |
| A45 | Inconsistencia (dependencias) | MEDIA | T073, T079 | El cierre de dependencias de T073 no alcanza varias tareas que migran archivos, y exige "T013 completo", que necesita T072. | T073 depende de toda tarea que migra o elimina un archivo; T079, de todas las de trabajo. `/tasks 016 --fix` |
| A46 | Ambigüedad | MEDIA | `plan.md` → "Dónde corre"; T005, T075 | En local el script toma la sesión con `docker compose exec`; en CI no hay Compose. | T005 fija cómo recibe el script la URL y el modo de obtener la sesión, para ambos entornos. `/plan 016 --fix` |
| A47 | Ambigüedad (P15, CA10) | MEDIA | `plan.md` → "Cambios a incorporar al sistema" | El sistema declara "filtro", "insignia de estado", "registro" y el subtítulo de pestaña, pero no hay archivo ni tarea que los cree; unas 12 tareas tendrían que inventar el marcado. | El plan dice qué es cada uno (componente, función compartida o patrón con marcado fijo) y una tarea lo crea antes de usarse. `/plan 016 --fix` |
| A48 | Ambigüedad (CA11, CA25) | MEDIA | `plan.md` → "Calendario en el móvil"; T064, T065, T067 | En móvil la lista del día aparece debajo del calendario, pero se sigue migrando y midiendo el diálogo "Citas del día" a 390 px. | Decidir si ese diálogo existe a 390 px. **Decisión del usuario** |
| A49 | Ambigüedad (CA19, CA20) | MEDIA | `spec.md` CA19, CA20; `plan.md` → "Errores web" | En producción nginx responde algunos 404 con su propia página en inglés, y los 405 y 419 de rutas web usan la página por defecto de Laravel. `docker/` es ruta protegida. | Decidir si CA19 y CA20 se acotan a lo que responde la aplicación o se amplían. **Decisión del usuario** |
| A50 | Inconsistencia | MEDIA | T007, T001 | T007 actualiza `design.source` y `design.status` "cuando el usuario apruebe", sin criterio que lo compruebe. | El criterio de T007 incluye que coincidan con el frontmatter de `system.md`. `/tasks 016 --fix` |
| A51 | Ambigüedad | BAJA | T043, T046, T053, T056, T059, T062, T066 | "Los diálogos siguen abriendo y guardando" no nombra con qué se comprueba. | Mismo criterio que T030 más el caso de guardado de `checks-a11y`. |
| A52 | Inconsistencia | BAJA | `plan.md` → Trazabilidad CA37; T044 | La verificación manual "lector de pantalla al guardar un paciente con error" no está en ninguna tarea. | Añadirla a T044. |
| A53 | Inconsistencia (formato) | BAJA | T002, T003, T004; encabezado | T003 y T004 no declaran TM5 ni T002, TM6; el encabezado no explica que T077 no existe. | Alinear `cubre:` y anotarlo. |
| A54 | Inconsistencia | BAJA | `design/propuesta.html`; T026 | La propuesta sigue con el hover oscuro y nombres de token distintos de los del plan. | Corregir la propuesta o excluir el hover de la verificación de T026. |
| A55 | Constitución (P2) | BAJA | `plan.md` → Constitution Check | La lista de criterios que solo se prueban en navegador omite CA22. | Añadir CA22. |
| A56 | Seguridad (TM5) | BAJA | T004, T024 | El comando usa lista de permitidos y el seeder, lista de denegación: en un entorno con otro nombre el seeder correría. | El seeder solo corre en `local` y `testing`; el test lo afirma con un tercer nombre. |

Comprobado sin hallazgo: ningún diálogo queda roto entre tareas con el orden actual; la ruta de
respaldo del 404 es coherente con el código (la cookie del token no va cifrada y `SecurityHeaders`
es global); el comando en `routes/console.php` es factible; la imagen de acceso existe; gestión de
contenido tiene 5 encabezados, todos con tarea; los contrastes de "Tokens" coinciden; ninguna spec
vecina queda contradicha; RS16 y DS1–DS14 están declarados.

## Decisiones pendientes del usuario
- A38: ¿cómo queda el panel con imagen de las pantallas de acceso?
- A48: ¿el diálogo "Citas del día" sigue existiendo en el móvil?
- A49: ¿CA19 y CA20 cubren solo lo que responde la aplicación, o también las páginas de nginx y los 405 y 419?

## Aceptados
Ninguno todavía.
