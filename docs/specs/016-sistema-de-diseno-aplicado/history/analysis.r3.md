---
result: pass
round: 3
mode: full
constitution_version: 1.1.2
date: 2026-10-07
spec_sha: 379ee4902323
plan_sha: 40a80982112e
tasks_sha: 1d22608d44ca
---

# Análisis · 016 Sistema de diseño aplicado

## Resumen
`pass`. Sin hallazgos críticos ni violaciones de la constitución sin excepción registrada. Los 25
hallazgos de la ronda 2 y los 3 que seguían abiertos de la ronda 1 están resueltos. La ronda 3 dejó
18 hallazgos nuevos (1 alto, 11 medios, 6 bajos); ninguno cambia el diseño, y el usuario los aceptó
el 2026-10-07 como notas de tarea que `/implement` debe cumplir. Análisis completo (la spec ganó
CA40 y CA41), hecho por un revisor independiente de contexto limpio, en solo lectura.

Conteo de hallazgos nuevos: CRÍTICA 0 · ALTA 1 · MEDIA 11 · BAJA 6

`aidd.py validate`: 0 errores, 1 aviso (spec grande).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 41 / 41 |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 14 / 14, más Restricciones y Definición de terminado |

Tareas: 93 (86 de trabajo y 7 fijas); ninguna con más de 3 archivos; 14 `[P]` sin archivos
compartidos; sin dependencias a tareas inexistentes ni ciclos. La tabla de Cobertura coincide con
los `cubre:` (salvo A72) y la de amenazas, con las 8 TM.

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A3 | ALTA | resuelto | Nombres fijados y compilados; T010 (colisiones); T027 y `checks-global` miden el valor pintado |
| A16 | MEDIA | resuelto | `--base-url` y `--artisan`; T005 |
| A21 | MEDIA | resuelto | Rollout "Diálogos compartidos"; convención "sigue funcionando"; T083 espera a T043, T046 y T048 |
| A32 | ALTA | resuelto | `--text-control`; T010 afirma que no hay colisiones; T027 mide 16 px y tinta |
| A33 | ALTA | resuelto | Convención "tarea de pantalla = sin sus diálogos" |
| A34 | ALTA | resuelto | Región por diálogo; T030, T081, T044 |
| A35 | ALTA | resuelto con efecto nuevo (→ A62) | CA40; plan "Teclado"; T081, T035, T064, T065 |
| A36 | ALTA | resuelto con efecto nuevo (→ A60, A65) | `ui:audit-data --clean`; T004; T024 |
| A37 | MEDIA | resuelto | Línea base independiente de los datos; capturas solo desde CI |
| A38 | MEDIA | resuelto con efecto nuevo (→ A68) | Decisión del usuario; regla de opacidad, degradados y sombras; T011, T036 |
| A39 | MEDIA | resuelto | Texto de ejemplo en `muted`; T010, T020, T025 |
| A40 | MEDIA | resuelto | T011 sobre los archivos pendientes; T025 |
| A41 | MEDIA | resuelto con efecto nuevo (→ A61, A71) | Convención de marcas de pendiente; T041, T048; T079 |
| A42 | MEDIA | resuelto | CA32 sobre la tarjeta pintada; T022, T052 |
| A43 | MEDIA | resuelto | Región en T032, T036, T037 y una sola en T039 |
| A44 | MEDIA | resuelto | T047 cubre CA14 |
| A45 | MEDIA | resuelto | Cierre de dependencias de T073 y T079 |
| A46 | MEDIA | resuelto | T005 con `--artisan "php artisan"` |
| A47 | MEDIA | resuelto con efecto nuevo (→ A57) | `chip`, `badge`, `record`, `section-title`; T085, T086 |
| A48 | MEDIA | resuelto | Decisión en la spec; T005, T065, T067 |
| A49 | MEDIA | resuelto con efecto nuevo (→ A58, A64, A73) | CA41; roadmap; T087, T088, T071 |
| A50 | MEDIA | resuelto | Criterio de T007 |
| A51 | BAJA | resuelto | Convención "sigue funcionando" |
| A52 | BAJA | resuelto | Verificación manual en T044 |
| A53 | BAJA | resuelto | `cubre:` de T002, T003 y T004; encabezado |
| A54 | BAJA | resuelto | Hover de la propuesta corregido |
| A55 | BAJA | resuelto | P2 incluye CA22 |
| A56 | BAJA | resuelto | T004 y T024 con lista de entornos permitidos |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A57 | Inconsistencia (CA27) | ALTA | T049, T050; `plan.md` → `x-ui.record` | Las filas del expediente las pinta `resources/js/pages/records/index.js` dentro de los `tbody`; T049 y T050 solo listan los componentes Blade, y `x-ui.record` no tiene gemela para el navegador. `RecordsScreenTest` exige que sigan el `thead` y los `tbody id`. | Aceptado → nota de T049 y T050 |
| A58 | Cobertura (CA41) | MEDIA | CA41; T087, T088, T071, T076 | Ninguna ruta web puede producir un 419 y el 405 no se abre navegando; ninguna de las dos está en el inventario de `ui-audit`. | Decisión del usuario; aceptado → nota de T087, T088, T076 |
| A59 | Inconsistencia (criterio) | MEDIA | T065, T070 | Ninguna tarea exige "`ui-audit` pasa" en la agenda sin diálogos ni en las páginas de error antes de la fase de cierre. | Aceptado → nota de T065 y T070 |
| A60 | Inconsistencia (orden) | MEDIA | T004, T003, T024 | T004 se da por hecha cuando T024 pasa, pero el comando de datos lo registra T003, que depende de T004. | Aceptado → nota de T004 y T003 |
| A61 | Inconsistencia (orden) | MEDIA | T018, T019, T028, T040, T041 | T019 no puede pasar completo hasta T041; varios casos de T018 no tienen tarea que quite su marca de pendiente. | Aceptado → nota de T018, T019, T028, T040, T041 |
| A62 | Ambigüedad (CA40) | MEDIA | `plan.md` → `checks-a11y`; T081 | La regla de teclado no dice qué hacer con los manejadores delegados en contenedores (19) ni con el cierre por fondo (10). | Aceptado → nota de T081 |
| A63 | Cobertura (CA16, CA30) | MEDIA | CA16, CA30; `UiCopyTest` | 32 archivos muestran el mensaje de la API tal cual, y varios están en inglés; el test de textos es estático y no los ve. | Decisión del usuario; aceptado → nota de T082 y T081 |
| A64 | Seguridad (TM4) | MEDIA | TM4; T080 | T080 solo prueba GET: un POST a una ruta inexistente de la API podría cambiar de 404 a 405 sin que ningún test lo vea. | Aceptado → nota de T080 |
| A65 | Ambigüedad (TM5) | MEDIA | `plan.md` → "Datos"; T004, T024 | "Lo que el seeder marcó" no está definido y no hay migraciones; tampoco de dónde salen las imágenes neutras ni si la limpieza borra archivos y tokens. | Aceptado → nota de T004 y T024 |
| A66 | Constitución (WCAG 1.3.5) | MEDIA | `checks-a11y`; T027, T036, T081 | Ningún campo declara su propósito (`autocomplete`) y nada lo comprueba. | Aceptado → nota de T018, T027 y T036 |
| A67 | Riesgos (CI) | MEDIA | T075, T076 | `ui-audit` entra en CI al final; la fuente del sistema difiere entre el equipo y el runner; el paso necesita `storage:link`, rama subida y PR. | Aceptado → nota de T038, T075 y T076 |
| A68 | Constitución (P15) | BAJA | T011, T036 | El test de solo tokens no mira radios, ni menciona `transparent` y `current`, ni la sombra arbitraria de las vistas de acceso. | Aceptado → nota de T011 y T036 |
| A69 | Inconsistencia (orden) | BAJA | T030, T082 | T030 monta la región del diálogo antes de que exista `x-ui.status`. | Aceptado → nota de T030 |
| A70 | Inconsistencia | BAJA | T064 | Los días del calendario los crea el script (T065), no la vista de T064. | Aceptado → nota de T064 |
| A71 | Inconsistencia (formato) | BAJA | Convenciones; T040 | Quitar un archivo de pendientes y quitar una marca editan archivos que las tareas no declaran; T040 cambia el menú que afirma `StaffNavigationTest`. | Aceptado → nota de T011 y T040 |
| A72 | Inconsistencia (`cubre:`) | BAJA | T060 | Los diálogos de certificaciones también tienen selector de archivo. | Aceptado → nota de T060 |
| A73 | Ambigüedad (redacción) | BAJA | `spec.md`, `plan.md` | Restos anteriores a CA41: "dos pantallas nuevas", "un comando", "65 y 4 nuevas". | Aceptado → spec y plan, en la próxima edición |
| A74 | Propuesta vs plan | BAJA | `design/propuesta.html`; T036, T051, T063 | La propuesta abre en la variante A, pinta el encabezado de contenido al revés que el plan y no dibuja el panel de acceso ni el escritorio. | Aceptado → nota de T035, T036 y T051 |

Comprobado sin hallazgo: el orden de las tareas es alcanzable (T025 con T010 y T011; T027 tiene
pantallas que ya usan `x-ui.input`); no hay más colisiones de nombres de token; todo color que
desaparece con la paleta tiene reemplazo deducible de la tabla; toda tarea que usa filtro o insignia
depende de T085; CA40 y CA41 son verificables y sin tecnología; el roadmap recoge lo que la spec
manda fuera; la ruta de respaldo no rompe los tests existentes; las imágenes de marca entran en las
imágenes de producción.

No se pudo comprobar: la compilación real del tema más allá de la prueba del plan, que el runner de
CI traiga Chrome, y el comportamiento exacto de la ruta de respaldo y de la simulación del entorno
`production`.

## Decisiones pendientes del usuario
Resueltas el 2026-10-07:
- A58: las páginas de método no permitido y formulario caducado se verifican con tests de servidor; comparten marco con las de "Sin permiso" y "No encontrada", que sí se miden y capturan en navegador.
- A63: los mensajes de la API entran en CA16 y CA30: la interfaz muestra su propio texto en español y solo deja pasar los mensajes de validación.

## Aceptados
Aceptados por el usuario el 2026-10-07:
- **A57** → nota de T049, T050: ambas tareas tocan también `resources/js/pages/records/index.js`, que es quien pinta las filas. Por debajo de `md` cada dato se emite con el mismo marcado que `x-ui.record` y el mensaje de sección vacía se lee completo; se conservan el `thead` y los `tbody id` que afirma `RecordsScreenTest`. Hecho cuando `checks-screens` pasa en esas secciones.
- **A58** → nota de T087, T088, T076: T087 provoca el 405 con un POST a una pantalla que solo admite GET y el 419 con una ruta de formulario registrada por el propio test, y afirma el enlace de vuelta con y sin sesión. 405 y 419 no entran en `screens.json`; las capturas nuevas de T076 son dos (403 y 404).
- **A59** → nota de T065, T070: T065 termina con `ui-audit --only agenda` sin fallos en la pantalla sin diálogos; T070, con `ui-audit` sin fallos en "Sin permiso" y "No encontrada".
- **A60** → nota de T004, T003: T004 registra su propio comando para que T024 pase completo en T004; T003 añade el de sesión.
- **A61** → nota de T018, T019, T028, T040, T041: T018 y T019 llevan un caso por comprobación y pantalla, cada uno con la marca de la tarea que lo desbloquea; T028 quita solo la de la insignia, T040 y T041 las del rol, y cada tarea de pantalla o de diálogo quita las de T018 de su pantalla.
- **A62** → nota de T081: el elemento que se evalúa es el destino efectivo del clic, no el contenedor que delega; el cierre por fondo queda exento si Escape cierra. Control positivo sobre el calendario actual y control negativo sobre un contenedor que delega en botones.
- **A63** → nota de T082, T081: `ui/status.js` muestra un texto propio en español por tipo de error (sin permiso, no encontrado, conflicto, error interno, sin conexión) y solo deja pasar los mensajes de validación; el caso de error de `checks-a11y` afirma que el mensaje anunciado no contiene palabras de la lista de `UiCopyTest`.
- **A64** → nota de T080: añadir un POST a una ruta inexistente de `api/v1`, que conserva código y cuerpo.
- **A65** → nota de T004, T024: el seeder marca sin tocar el esquema (dominio de correo reservado para usuarios y pacientes, claves foráneas para lo que depende de ellos, nombres exactos reservados y prefijo de ruta para tratamientos y contenido); las imágenes neutras se generan en el propio seeder; la limpieza borra también sus archivos y sus tokens; T024 lo afirma con un registro ajeno casi idéntico.
- **A66** → nota de T018, T027, T036: los campos de correo, nombre, contraseña y teléfono declaran su propósito con `autocomplete`, y `PageStructureTest` lo afirma en acceso y registro.
- **A67** → nota de T038, T075, T076: al cerrar la fase 4 se ejecuta `ui-audit` una vez en CI, sin bloquear, para detectar diferencias de fuente entre el equipo y el runner; el paso de T075 incluye `storage:link`; T075 y T076 requieren la rama subida y el PR abierto.
- **A68** → nota de T011, T036: el test admite `transparent`, `current` e `inherit`, y en archivos migrados falla ante un radio que no sea `control`, `box`, `card`, `full` o `none`, con control positivo; la sombra arbitraria de las vistas de acceso se sustituye por una sombra con token.
- **A69** → nota de T030: la región del diálogo se escribe con el mismo marcado que tendrá `x-ui.status`.
- **A70** → nota de T064: los días como botones son de T065; T064 solo prepara el contenedor y la zona de la lista.
- **A71** → nota de T011, T040: `pending-files.php` y la retirada de una marca de pendiente no cuentan como archivo de la tarea; T040 conserva los `href` literales que afirma `StaffNavigationTest` o actualiza ese test en la misma tarea.
- **A72** → nota de T060: el selector de archivo de certificaciones tampoco se trunca (CA28, sin M13).
- **A73** → spec, plan: alinear en la próxima edición las menciones a "dos pantallas nuevas", "un comando" y "65 y 4 nuevas".
- **A74** → nota de T035, T036, T051: la revisión del grupo usa la variante B de la propuesta; donde la propuesta y el plan difieren (encabezado de contenido, panel de acceso, escritorio), manda el plan.
