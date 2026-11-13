---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-11-12
spec_sha: f086ea63a1c5
plan_sha: 0ae131b0f061
tasks_sha: 2fc16211530f
---

# Análisis · 026 Nueva landing del sitio público

## Resumen
`fail`: un hallazgo crítico (P15: radios, tamaños y componentes de la propuesta fuera del sistema)
y siete altos que dejarían la suite en rojo en tareas intermedias (`DesignTokensTest` con
`@theme static`, `ui-audit` con selectores viejos, entradas de Vite sin cargar) o que hacen el
diseño incumplible (K1 en galería y contacto, datos de contacto de ejemplo, vigencia con reloj real,
SE8.a parcial). Análisis hecho por un subagente independiente; cada hallazgo se verificó en los
archivos. Las decisiones del usuario están abajo; las correcciones van por `/specify --edit`,
`/design-spec --fix`, `/plan --fix` y `/tasks --fix`.
Conteo: CRÍTICA 1 · ALTA 7 · MEDIA 15 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 19 / 19 (con reservas en CA1, CA6, CA7) |
| Amenazas con control y test | 5 / 5 (con reservas en TM4, TM5) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P15) | CRÍTICA | plan → Enfoque; T001, T004, T006 | La propuesta usa ~90 `border-radius` fuera de la escala 12/16/24/full (10, 14, 18, 28, 32, 2, 3 px…), tamaños sueltos (`min-height:46px`) y controles propios no documentados; solo los colores pasan a tokens y ningún test lo comprueba | El sistema 2.1.0 incorpora la escala de radios, los tamaños y los componentes del sitio público (decisión del usuario); `SiteStylesTokensTest` rechaza `border-radius`/`min-height` literales con control positivo · `/design-spec 026 --fix`, `/plan --fix`, `/tasks --fix` |
| A2 | Constitución (P2) | ALTA | T001; `DesignTokensTest` (`/@theme\s*\{/`) | La expresión no lee `@theme static {` ni un bloque `:root`; los tokens nuevos salen "sin declarar" y la suite queda en rojo | Los tokens del bloque elegido los lee `DesignTokensTest` (valor y contraste); T001 incluye el test · `/tasks --fix` |
| A3 | Constitución (P2) | ALTA | T013, T016, T017, T020 vs T023; `tests/Browser/screens.json` | `screens.json` y `baseline-1440.json` usan selectores y composición del sitio viejo (`#mobile-menu-btn`, `#gallery-lightbox`…); `npm run test:ui` falla en CI desde T013 hasta T023 | Cada tarea de página deja `npm run test:ui -- --only <pantalla>` en verde con sus entradas y línea base; T023 solo añade estados · `/tasks --fix` |
| A4 | Constitución (P2) | ALTA | T007/T008, T013–T015, T017/T018, T020/T021; `vite.config.js` | Los scripts y hojas se crean en tareas distintas de la vista que los carga; o la vista apunta a una entrada inexistente o el script no se conecta; `inicio/nosotros/contacto.css` no se registran | Cada hoja y script se registra y carga en la misma tarea que lo crea; test que afirma que el HTML carga su script · `/tasks --fix` |
| A5 | Ambigüedad | ALTA | spec → Criterios de diseño K1; `criterios.json` | "Agenda una Cita" solo existe en inicio y nosotros; K1 fallaría en galería y contacto | K1 solo en inicio y nosotros (decisión del usuario) · `/specify --edit`, `/design-spec --fix` |
| A6 | Ambigüedad (supuesto) | ALTA | spec → Decisiones "Datos de contacto"; CA7; T003 | Los datos de producción son de ejemplo (WhatsApp `521234567890`, teléfono `+52 55 1234 5678`, correo de otro dominio, redes genéricas) | Solo se muestran y van al JSON-LD los datos confirmados; el WhatsApp es obligatorio (T003 se bloquea sin él) (decisión del usuario); `SiteConfigTest` rechaza los valores de ejemplo · `/specify --edit`, `/tasks --fix` |
| A7 | Pruebas que no prueban | ALTA | T012; `PromotionEntity::isExpired()` | `new DateTime()` ignora el reloj de la app (`travelTo`, `UiAuditClock`); no hay "aún no empieza"; la entidad no está en T012 | Vigencia con el reloj de la aplicación en `America/Mexico_City` para inicio y fin; la entidad entra en T012; test con `travelTo` en las tres fronteras · `/tasks --fix` |
| A8 | Cobertura de riesgos | ALTA | spec → Cobertura SE8.a; plan → Descubribilidad | SE8.a pide `theme-color` en todos los layouts; la 026 solo cubre el sitio con otro valor y deja el resto como "deuda" sin destino | SE8.a parcial: dentro el sitio público (rosa de la marca, decisión del usuario), fuera panel y portal → roadmap; SE8 no se cierra · `/specify --edit`, `/plan --fix` |
| A9 | Inconsistencia (extends) | MEDIA | spec frontmatter; 022 CA6, CA9, CA10 | Cambia barra, pie y sitemap de las legales sin `extends: 022` | Añadir 022 a `extends` y declarar qué criterios de la 022 se mantienen; `LegalPagesTest` y `ui-audit` de legales en T009 · `/specify --edit` |
| A10 | Inconsistencia (016) | MEDIA | spec → Diseño; 016 CA15; `PageStructureTest` | La propuesta trae su logo SVG y otro favicon; `PageStructureTest` exige el logo y favicon de la marca | Logo y favicon reales de la clínica (decisión del usuario); T007 pasa `PageStructureTest` · `/specify --edit`, `/design-spec --fix`, `/tasks --fix` |
| A11 | Inconsistencia | MEDIA | T004 vs T005 | T004 prohíbe `font-family` literal y T005 lo necesita en `@font-face` | Literal permitido solo dentro de `@font-face`, con controles · `/tasks --fix` |
| A12 | Cobertura | MEDIA | T001; CSS de la propuesta | `--ease-out`, `--weight-strong`, `--weight-regular`, `--text-body` usados y no declarados | Toda `var(--…)` del CSS del sitio tiene token declarado o es local; regla en `SiteStylesTokensTest` · `/tasks --fix` |
| A13 | Constitución (P2) | MEDIA | T009; `pages/*.css` | El menú plegable vive en las hojas de página; las legales no lo tendrían | La barra funciona igual en cualquier página del layout; `ui-audit` de legales a 390/768/1024 en T009 · `/tasks --fix` |
| A14 | Inconsistencia | MEDIA | CA6, T021 vs propuesta | La propuesta no tiene campo de nombre | Motivo + mensaje, sin nombre (decisión del usuario) · `/specify --edit`, `/tasks --fix` |
| A15 | Inconsistencia (datos) | MEDIA | CA2; plan → Contratos | El plan descarta la descripción de certificaciones, que sí se captura | Se muestra en el lugar de la institución (decisión del usuario) · `/specify --edit`, `/plan --fix` |
| A16 | Constitución (P13) | MEDIA | T014; `home.js` | `home.js` vuelve a filtrar y ordenar promociones con el reloj del visitante | El JS no filtra ni reordena; `SiteScriptsTest` con otra zona horaria · nota de T014 |
| A17 | Seguridad (CSP) | MEDIA | `SecurityHeaders` `form-action 'self'`; formulario de contacto | El envío sin JS a `wa.me` lo bloquea la CSP | Sin `action` externo; sin JS, enlace de respaldo a WhatsApp (decisión del usuario); `PublicSitePagesTest` lo afirma · `/tasks --fix` |
| A18 | Seguridad | MEDIA | spec → Fuera de alcance, Seguridad; plan TM1 | 011 CA6 es el contenido oculto en la API pública (RS4.a), no "listados de un elemento"; la afirmación "nadie ve lo oculto" no cubre la API | Corregir la referencia, RS4.a fuera con destino, la afirmación limitada al HTML; TM1 nombra el riesgo restante · `/specify --edit`, `/plan --fix` |
| A19 | Pruebas que no prueban | MEDIA | layout de la propuesta (`lang` del locale `en`) | El sitio saldría con `lang="en"` | `lang="es"` en el layout; `PublicSiteSeoTest` en las seis rutas · nota de T007 y T010 |
| A20 | Pruebas que no prueban | MEDIA | T022; `gallery.js` (`BA_INFO`) | Textos de tratamientos dentro del JS, fuera del alcance del test de HTML | Textos de tratamientos desde el servidor; regla sobre `resources/js/site/**` · `/tasks --fix` |
| A21 | Cobertura | MEDIA | T023; `UiAuditSeeder` | Los datos de ui-audit no producen varias promociones, carrusel, cinta ni estados vacíos | `ui-audit` puede producir cada estado de `design.md` → Pantallas; `UiAuditSeederTest` · `/tasks --fix` |
| A22 | Ambigüedad | MEDIA | CA5; `design.md` → Pantallas | La propuesta no define galería vacía ni con N imágenes | `design.md` define vacío y N imágenes; `PublicGalleryPageTest` con 0 y 1 · `/design-spec --fix` |
| A23 | Seguridad (descargas) | MEDIA | T005, T019 | Sin herramienta declarada para el subconjunto ni procedencia verificable | Origen, versión y hash de cada archivo descargado en `OFL.txt`/`CREDITOS.md`, herramienta fuera de `package.json` · nota de T005 y T019 |
| A24 | Pruebas que no prueban | BAJA | T007/TM4 | TM4 solo sobre el layout y solo `noopener` | Todo `target=_blank` de las seis rutas con `noopener noreferrer` · nota de T022 |
| A25 | Cobertura (TM) | BAJA | plan TM5 | "Rate limiting existente" no aplica a rutas web | Quitarlo; el control es la caché · `/plan --fix` |
| A26 | Inconsistencia | BAJA | CA17 vs plan | "catálogo" en la spec, lista de servicios en el plan | Alinear CA17 y registrar la decisión en la spec · `/specify --edit` |
| A27 | Pruebas que no prueban | BAJA | T017; T024 | Falta el caso de una imagen (ya falló antes); "oculto en JSON-LD" no prueba nada | Caso de 1 imagen; CA14 por HTML y sitemap · nota de T017 y T024 |
| A28 | Inconsistencia | BAJA | T010 vs vistas legales | Dos fuentes de título y descripción legales | Una sola fuente; `LegalPagesTest` y `PublicSiteSeoTest` · nota de T010 |
| A29 | Cobertura de riesgos | BAJA | `docs/seo.md` SE3.a; `site.url` | seo.md dice cuatro rutas; URL duplicada | T026 actualiza SE3.a a seis; `SiteConfigTest`: `site.url` = `seo.site_url` · nota de T003 y T026 |

## Decisiones pendientes del usuario
Resueltas el 2026-11-12:
- A1 → ampliar el sistema 2.1.0 con radios, tamaños y componentes del sitio público.
- A6 → solo datos confirmados; lo no confirmado no se muestra ni va al JSON-LD; WhatsApp obligatorio.
- A5 → K1 solo en inicio y nosotros.
- A14 → formulario de la propuesta (motivo + mensaje), sin nombre.
- A10 → logo y favicon reales de la clínica.
- A15 → descripción de la certificación en el lugar de la institución.
- A17 → sin `action` externo; sin JS, enlace de respaldo a WhatsApp; la CSP no cambia.
- A8 → `theme-color` rosa de la marca en el sitio público; panel y portal pendientes; SE8 no se cierra.

## Enmiendas propuestas a la constitución
- P15 (PATCH): que "Cómo se verifica" incluya las hojas CSS fuera de `@theme` (color, radio y tamaño de control), no solo `OnlyDesignTokensTest`/`DesignTokensTest`. No bloquea; la decide el usuario.

## Aceptados
Ninguno todavía: todas las correcciones se aplican en spec, diseño, plan y tareas antes de la ronda 2.
