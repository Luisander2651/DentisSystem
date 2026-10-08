---
result: fail
round: 1
mode: full
constitution_version: 1.1.2
date: 2026-10-06
spec_sha: d3175dd0c992
plan_sha: ae674a6ec9b4
tasks_sha: fcac000eaadd
---

# Análisis · 016 Sistema de diseño aplicado

## Resumen
`fail`. No hay hallazgos críticos y la cobertura formal está completa, pero hay 9 hallazgos altos:
supuestos del plan que no se sostienen contra el framework o el código (nombres de utilidades de
Tailwind, alcance de `Route::fallback`, contraste del hover), criterios que quedarían incumplidos
sin que ningún test lo detecte (colores de la paleta por defecto de Tailwind, respuestas con
`Accept: application/json`, diálogos montados en otras pantallas) y dos desviaciones de la
constitución sin registrar (P2 y el alcance de WCAG 2.1 AA). Análisis hecho por un revisor
independiente de contexto limpio, en solo lectura; no se ejecutaron tests, build ni auditorías.

Conteo: CRÍTICA 0 · ALTA 9 · MEDIA 14 · BAJA 8

`aidd.py validate`: 0 errores, 1 aviso (spec grande).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 34 / 34 (con hueco real de verificación en CA2, CA3, CA4, CA6, CA8, CA12, CA13, CA14, CA16, CA20, CA22 y CA26) |
| Amenazas con control y test | 7 / 7 (TM5 parcial; TM2 y TM3 sin el caso JSON) |
| Principios de la constitución evaluados | 14 / 14, más Restricciones y Definición de terminado |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura / P4 | ALTA | `plan.md` → "Endpoints y datos", decisión "404 por `Route::fallback`"; T071, T015, T016 | El plan afirma que el 404 JSON de `api/*` no cambia y ningún test lo fija. Un fallback en el grupo web captura también los GET a rutas inexistentes de `api/*` y los GET a rutas de API de otro verbo (hoy 405). `routes/api.php` no tiene fallback. Inferido del framework, no ejecutado. | Un GET a una ruta inexistente de `api/v1` y un GET a una ruta de API solo-POST conservan el código y el cuerpo JSON de hoy, sin vista HTML. Test Pest de contrato que pasa hoy y sigue pasando tras T071, como dependencia de T071. `/plan 016 --fix` |
| A2 | Cobertura (CA2, CA6) | ALTA | `plan.md` → `OnlyDesignTokensTest`, "Tokens"; T011, T025, T033 | El test solo prohíbe literales, utilidades arbitrarias y los rosas antiguos. Las utilidades de la paleta por defecto de Tailwind pasan: 1160 usos de `slate`, 221 `red`, 39 `emerald`, 22 `amber`, 12 `sky`, 9 `blue`, 6 `pink`, entre ellos 127 `text-slate-400` (2.63:1). CA2 exige que cada color salga de un valor con nombre del sistema. Faltan tokens para colores que seguirán existiendo: fondo del botón de WhatsApp, blanco sobre peligro, superficies oscuras (visor de la galería, capas de diálogo). | Ninguna vista ni JS usa un color que no sea un token de la tabla Color, incluida la paleta por defecto de Tailwind; todo color que permanece tiene token con su contraste. Regla en `OnlyDesignTokensTest` con control positivo (p. ej. `text-slate-400`). `/plan 016 --fix` |
| A3 | Ambigüedad | ALTA | `plan.md` → "Enfoque técnico", "Tokens"; T025 | Supuesto sin comprobar. En Tailwind 4, `--color-border-field` genera `border-border-field`, no `border-field`; `--color-text-muted` genera `text-text-muted`; `--color-bg`, `bg-bg`. `--size-control` no pertenece a ningún espacio de nombres del tema y no genera utilidades. El criterio de hecho de T025 no puede cumplirse como está escrito. | El plan fija la correspondencia token → utilidad, comprobada con una prueba desechable ("Probar antes de planear"), y tabla, `system.md` y T025 quedan alineados. Un test afirma que cada utilidad que usan los componentes existe en el CSS compilado. `/plan 016 --fix` |
| A4 | Cobertura (CA3, CA4, CA6) | ALTA | `plan.md` → "Tokens", Trazabilidad CA3; `design/propuesta.html`; T026 | El plan no define el color del hover de la acción principal. El de la propuesta (`#d75078` mezclado al 88 % con la tinta, `#bf486d`) da 3.91:1 con la tinta encima. Cualquier hover más oscuro que el primario incumple CA6, porque el par base está en 4.76:1. | El hover de la acción principal es un token con su par declarado y texto ≥ 4.5:1. `DesignTokensTest` incluye el par tinta/hover y `ui-audit` mide el contraste en hover. `/plan 016 --fix` |
| A5 | Seguridad (CA20, TM2, TM3) | ALTA | `plan.md` → TM2, TM3; T014, T015, T069 | `bootstrap/app.php` responde JSON cuando la petición lo espera, y `EnsureActiveStaff` hace `abort(403, $message)` con mensaje en inglés. Una pantalla web pedida con `Accept: application/json` recibe ese mensaje en crudo; un 404 así devuelve un mensaje con la ruta pedida. Los tests planeados solo cubren la respuesta HTML. | En rutas web, el 403 y el 404 no contienen el mensaje interno, texto en inglés ni la ruta pedida, sea cual sea la cabecera `Accept`. Casos con `Accept: application/json` en `WebAccessDeniedPagesTest` y `NotFoundPageTest`. `/plan 016 --fix` |
| A6 | Inconsistencia (reglas del flujo) | ALTA | `plan.md` → "Cambios a incorporar al sistema" (Capturas); T076 | T076 rehace las capturas con `/init --upgrade --redo-design`, que re-extrae el sistema como `extracted` y "no aplica a un sistema `chosen`" (`skills/init/references/design.md` §3b). Tras T001 el sistema ya es `chosen` 2.0.0: T076 no es ejecutable o desharía T001. Tampoco dice con qué datos se capturan. | Las capturas de las 65 pantallas y las 2 nuevas se regeneran a 390 y 1440 px sin re-extraer el sistema y solo con datos de `UiAuditSeeder`. Verificación: `aidd.py validate`, `system.md` sigue `chosen` 2.0.0. `/plan 016 --fix` |
| A7 | Constitución (P2) | ALTA | `plan.md` y `tasks.md` → Constitution Check, P2 | P2 exige un test Pest en `tests/Modules/<Módulo>/` para todo cambio de comportamiento. CA11, CA12, CA13, CA25, CA26, CA27 y CA31 solo tienen test en Node, fuera de Pest. La decisión del usuario consta en "Decisiones", pero la fila figura como ✅ y no como desviación aceptada. | Registrar P2 como desviación aceptada con usuario y fecha, o que la enmienda 1.2.0 haga que P2 admita `npm run test:ui` para criterios que solo existen en un navegador. Verificación: `aidd.py validate`. **Decisión del usuario** |
| A8 | Constitución (Restricciones) | ALTA | `spec.md` → Requisitos no funcionales; `plan.md` → Trazabilidad RNF | La constitución exige WCAG 2.1 AA en las pantallas modificadas y la spec modifica todas. Spec y plan lo acotan a CA6–CA9, CA12–CA14 y CA29 sin decisión registrada. Sin test ni verificación manual: nombre accesible de botones de icono e imágenes, etiquetas de campo, mensajes de estado y error anunciados, contraste no textual fuera de campos y foco. | Las pantallas tocadas cumplen WCAG 2.1 AA completo, o la acotación consta como decisión del usuario con destino para el resto. Comprobaciones de nombre accesible, etiqueta y región de estado en `ui-audit` o `PageStructureTest`. **Decisión del usuario** |
| A9 | Cobertura (CA12, CA13, CA26) | ALTA | `plan.md` → Estrategia de pruebas; T005, T043, T048, T066 | `pages/records/index.blade.php` monta el diálogo de eliminar y el de detalle de cita, y `pages/dashboard.blade.php` los de crear paciente y crear usuario. Ninguno de esos contextos está en el inventario que recorre `ui-audit`. Además, T043 (fase 5) y T066 (fase 7) migran componentes que usa expedientes (fase 6): entre esas tareas, expedientes queda con diálogos rotos sin que nada lo detecte. | `screens.json` incluye todo diálogo en cada pantalla que lo monta. Las tareas que migran un componente compartido declaran todas las pantallas que lo usan y pasan `ui-audit` en ellas. `/plan 016 --fix` y `/tasks 016 --fix` |
| A10 | Inconsistencia (CA14, CA22, CA34) | MEDIA | `plan.md` → fila "Contenido"; T028, T051, T052, T055, T058, T061 | `x-ui.page-hero` se usa en `contenido/index` y en sus cuatro pestañas. Al pintar el título como `h1`, `/contenido` tendrá 5. El plan no dice qué encabezado queda a 1440 px: quitarlo cambia la composición (CA22); ocultarlo solo en móvil deja varios `h1` (CA14). | Decidir qué encabezado queda en `/contenido` a 390 y a 1440 px. Exactamente un `h1` (`PageStructureTest`) y línea base coherente. **Decisión del usuario** |
| A11 | Cobertura (CA8) | MEDIA | `plan.md` → `--color-focus`; T025, T035 | El foco único en tinta da cerca de 1:1 sobre el visor de la galería pública (fondo casi negro) y sobre controles encima de imágenes. | El indicador de foco tiene ≥ 3:1 contra el fondo adyacente en todas las superficies. `ui-audit` lo mide en el visor. `/plan 016 --fix` |
| A12 | Ambigüedad (CA22) | MEDIA | `plan.md` → "Composición a 1440 px"; T006 | La línea base guarda encabezados y acciones, que la propia spec cambia (`h2` → `h1`, textos corregidos, rama retirada, encabezado único). No se define qué se normaliza. | La línea base compara estructura, no texto ni nivel de encabezado, y lista las diferencias admitidas por criterio. `ui-audit` falla al reordenar una sección y no con los cambios de CA14, CA16 y CA17. `/plan 016 --fix` |
| A13 | Inconsistencia | MEDIA | T030; `plan.md` → `resources/js/ui/dialog.js` | Ninguna tarea dice cómo se carga `dialog.js` ni toca `vite.config.js`. T030 pide "un diálogo de prueba montado" sin archivo ni pantalla donde montarlo. | El plan fija cómo se carga el módulo y T030 lista ese archivo y el soporte de prueba. Verificación: `npm run build` incluye el módulo. `/plan 016 --fix`, `/tasks 016 --fix` |
| A14 | Inconsistencia (formato) | MEDIA | T011, T012 | T012 `[P]` usa el archivo de pendientes que crea T011 `[P]`, sin dependencia y sin listarlo. | T012 depende de T011 o pierde `[P]`, y lista el archivo. `/tasks 016 --fix` |
| A15 | Ambigüedad (TM5, P8) | MEDIA | `plan.md` → TM5; T003, T009 | `app/Core/Console` no se autodescubre y `bootstrap/app.php` se evalúa antes de cargar el entorno; P8 prohíbe `env()` fuera de `config/`. No se dice cómo T009 prueba el entorno `production` desde una suite en `testing`. | En producción el comando no aparece en Artisan ni puede ejecutarse. T009 arranca la aplicación en entorno `production` y lo afirma; T003 lista el lugar real del registro. `/plan 016 --fix` |
| A16 | Ambigüedad | MEDIA | `plan.md` → "corre en local y en CI"; T005, T075 | No hay Chrome en la imagen de Docker y `docker/` es ruta protegida. No se define dónde corre `ui-audit` en local ni con qué entorno y base se levanta la app en CI. | El plan fija dónde corre `ui-audit` en local y en CI y cómo obtiene la sesión, sin tocar rutas protegidas. `/plan 016 --fix` |
| A17 | Ambigüedad (CA16) | MEDIA | `plan.md` → `UiCopyTest`; T012 | El test usa una lista abierta de palabras; no existe el inventario. Fuera de la lista hay al menos "Galeria", "Cerrando sesion", "Cerrar sesion" y "catalogo". | La lista sale de una extracción completa de los textos visibles, revisada por el usuario, y queda cerrada en un archivo de datos. `/plan 016 --fix` |
| A18 | Cobertura de riesgos | MEDIA | `plan.md` → fila "Dependencias", TM6; T002, T078 | T002 corrige vulnerabilidades de npm, y `docs/security.md` tiene RS16 con RS16.a "mitigada (v0.1.0)". Ni plan ni tareas lo citan ni actualizan. No se define qué pasa si no hay versión corregida con 7 días. | Plan y tareas declaran RS16.a dentro del alcance, con su estado en `security.md`, y la salida si no hay versión elegible. `/plan 016 --fix` |
| A19 | Inconsistencia | MEDIA | T007, T008; `.ai/project.yaml` | T007 sube la constitución a 1.2.0 y ninguna tarea actualiza `constitution_version` de `.ai/project.yaml`. P15 entra en vigor en la fase 2, cuando su verificación aún tiene lista de pendientes y `ui-audit` no está en CI. | La versión coincide en todos los archivos que la declaran, y la enmienda se aplica cuando su verificación ya corre. `/tasks 016 --fix` |
| A20 | Cobertura (TM5) | MEDIA | `plan.md` → Riesgos; T004 | El rechazo del seeder en producción es un control de seguridad sin test. | Test Pest: el seeder falla en `production` sin escribir datos. `/tasks 016 --fix` |
| A21 | Inconsistencia (tareas) | MEDIA | T043–T046, T053–T063, T066–T068 | Las vistas de cada grupo de diálogos se migran en una tarea y su JS en la siguiente; el criterio de la primera es solo lint, así que entre ambas los diálogos no abren. | Cada tarea termina con sus diálogos funcionando (`ui-audit --only`), o vista y JS de un diálogo van juntos. `/tasks 016 --fix` |
| A22 | Seguridad | MEDIA | `plan.md` → Modelo de amenazas; T071 | El fallback hace pasar toda dirección inexistente por el grupo web: sesión y cookie en cada 404. Inferido. | Decidir si un 404 de visitante crea sesión; si no debe, afirmarlo en `NotFoundPageTest`, o registrar el riesgo como aceptado. **Decisión del usuario** |
| A23 | Ambigüedad (tarea) | MEDIA | T029; `plan.md` → fila "Marca" | "Generado por el redimensionado con Intervention Image" no es un comando revisable; Intervention no escribe `.ico`; falta `apple-touch-icon` en T029. | T029 nombra el comando que genera cada archivo. `PageStructureTest` afirma que existen y pesan más de 0 bytes. `/plan 016 --fix` |
| A24 | Inconsistencia | BAJA | `plan.md` → Estrategia de pruebas (Manual); T070 | La prueba con lector de pantalla de la página de error no está en ninguna tarea. | Añadirla al criterio de hecho de T070. |
| A25 | Inconsistencia (cifras) | BAJA | `plan.md` → "Enfoque técnico", filas "Contenido" y "Marca" | `contenido/**` tiene 17 archivos JS, no 14; 11 JS con color hexadecimal, no 10; `auth/logout` es un documento propio sin layout. | Corregir las cifras y dejar explícito que `logout` lleva sus propias etiquetas de icono. |
| A26 | Inconsistencia | BAJA | `plan.md` → P10 | P10 se verifica con "`package.json` sin cambios", pero T005 le añade un script. | "Sin cambios en dependencias". |
| A27 | Inconsistencia (propuesta ↔ plan) | BAJA | `design/propuesta.html`; `plan.md` → "Radios y tamaños" | El plan elimina el radio de 16 px y la propuesta lo usa, además de un `#fff` literal. La propuesta no muestra nada a 1440 px, aunque las tareas piden compararla a ese ancho. | Alinear la escala y precisar que a 1440 px la referencia son las capturas. |
| A28 | Conflicto | BAJA | `plan.md` → Riesgos; spec 013 CA23 | La spec 013 hará que un paciente en rutas de staff pase de 403 a 401, lo que cambia "Qué ve cada rol" y el dataset de T014. | Anotar que la 013 deberá extender la 016 y ajustar `WebAccessDeniedPagesTest`. |
| A29 | Inconsistencia (reglas de diseño) | BAJA | T001, T008 | T008 fija `design.status: approved` sin paso de aprobación del sistema 2.0.0; el plan no dice que aplica la regla "del sistema extraído al elegido" y no la de rediseño. | Registrarlo en "Decisiones" y añadir la aprobación del usuario al criterio de T001. |
| A30 | Ambigüedad | BAJA | `spec.md` CA9, CA5; T011 | No se define si los enlaces dentro de un párrafo o del pie son "enlaces de acción" de 44 px. Prohibir `text-primary` impide el acento rosa en iconos, que CA5 permite. | Definir qué selecciona `ui-audit` como control táctil y cómo se pinta el acento en iconos. |
| A31 | Ambigüedad | BAJA | T036, T075 | Las pantallas de acceso cargan `storage/login.jpg`, que no se versiona: en CI saldrá rota. | Decidir si esa imagen se versiona con la marca o se excluye de las medidas. **Decisión del usuario** |

Comprobado sin hallazgo: ningún test afirma el JSON de `only.admin` sobre una ruta web; `<dialog>` y
los `data-*` no chocan con la CSP; los valores de "Tokens" coinciden con la variante B y sus
contrastes; las 60 vistas y los 37 JS tienen tarea; las rutas citadas existen o están marcadas como
nuevas; `layouts/patient` y `auth/logout` están cubiertos; las filas `contradicción` tienen fuente
`usuario`; ninguna spec extendida o vecina queda contradicha; DS1–DS14 están dentro o fuera con
destino.

No se pudo comprobar: las auditorías de dependencias, las utilidades que Tailwind genera de verdad
(A3) y el alcance exacto del fallback sobre `api/*` (A1), que son inferencias del framework; la
suite Pest; Chrome en el runner de CI; el driver de sesión de producción.

## Decisiones pendientes del usuario
Resueltas el 2026-10-06:
- A7: la enmienda 1.2.0 aclara P2 para admitir la prueba en navegador (decisión del usuario).
- A8: WCAG 2.1 AA completo, con criterios nuevos en la spec (decisión del usuario) → `/specify --edit`.
- A10: un solo encabezado en gestión de contenido, en móvil y en escritorio (decisión del usuario) → `/specify --edit`.
- A31: la imagen de las pantallas de acceso se versiona con la marca (decisión del usuario).
- A22: sin respuesta; se toma la opción conservadora (un 404 de visitante no crea sesión, afirmado con test) para que el usuario la revise al aprobar el plan corregido.

## Aceptados
Ninguno todavía.
