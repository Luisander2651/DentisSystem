---
spec: 026-nueva-landing
plan: plan.md
status: draft
---

# Tareas · 026 Nueva landing del sitio público

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

Versión 2 (`/analyze 026` ronda 1, regenerada sin tareas hechas; la anterior en
`history/tasks.v1.md`). La numeración se rehizo porque ninguna tarea de la v1 se empezó ni tiene
commits; los números no se corresponden con los de la v1.

Referencias fijas de las tareas de página:
- La propuesta en `nueva-landing/dentissa-laravel/` es la composición que no se cambia
  (`design.md` → Resultado).
- `evaluate.mjs <página>` = `node E:/AI-DD-Setup/skills/design-spec/scripts/evaluate.mjs
  http://localhost:8000<ruta> --criteria docs/specs/026-nueva-landing/design/criterios-<página>.json
  --out .ai/inspect/026-<página>`, sobre la build servida en local, con Playwright global (T002).
- `ui-audit <pantalla>` = `npm run test:ui -- --only <pantalla>` a 390, 768 y 1440 px. Cada tarea
  que cambia una página actualiza en ese momento sus entradas de `tests/Browser/screens.json` y su
  línea base de `baseline-1440.json`, para que CI siga en verde (A3).
- Cada página tiene una entrada de Vite `resources/js/site/pages/<página>.js` que importa su hoja y
  sus scripts. `vite.config.js` recoge esa carpeta sola (T006), así que ninguna vista apunta a una
  entrada que no existe (A4).
- Las tareas de página tocan hasta cinco archivos de código: una página no se puede partir sin
  dejar `ui-audit` en rojo entre medias (A3, A4). Los avisos del validador por número de archivos
  cuentan también tests, imágenes y fuentes.

## Constitution Check
Las tareas no cambian el Constitution Check de [plan.md](plan.md):

| Principio | Resultado | Cómo lo cumplen las tareas | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño y plan `approved` antes de `/implement` | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada rebanada lleva su test (Pest, Node o `ui-audit`), visto fallar antes de implementar, y deja `ui-audit` de su pantalla en verde | test: `./vendor/bin/pest --parallel` y `npm run test:ui` en CI; T024 |
| P3 Capas del módulo | ✅ | T013 pone la vigencia en `PromotionEntity` y el caso de uso; T019 usa `GetGalleryImagesUseCase`; sin imports nuevos entre módulos | manual: revisión en `/review` y búsqueda de `Infrastructure` dentro de `Domain/` |
| P4 Contrato de API primero | ➖ | Ninguna tarea toca `/api/v1` | — |
| P5 Autorización en el servidor | ➖ | Rutas públicas sin cambio de autorización | — |
| P6 Validación con FormRequest | ➖ | Sin entradas al servidor | — |
| P7 Errores sin detalles internos | ✅ | T014 y T019 fuerzan el fallo del caso de uso y afirman la página vacía sin el mensaje | test: T014, T019 |
| P8 Secretos fuera del repositorio | ✅ | T003 añade `SITE_NEW_LANDING` a `.env.example` y solo `config/site.php` llama a `env()` | test: `gitleaks` en CI; lint: `grep -rn "env(" app/` en T003 |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ✅ | Ninguna tarea toca `composer.json` ni `package.json`; fuentes e imágenes son archivos con procedencia; Playwright es global (T002) | manual: diff de `composer.json`/`package.json` en `/review` |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM5 en sus rebanadas (Cobertura); el único `Log::` nuevo (T019) no lleva datos de visitantes | manual: `aidd.py validate`; revisión de `Log::` en `/review` |
| P12 Producción con aprobación humana | ✅ | Línea base de auditorías en 0 (plan); T095–T098 para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Vigencia y orden en T013; T015 comprueba que `home.js` no filtra ni reordena | manual: revisión del JS y Blade en `/review`; test: T015 |
| P14 Trazabilidad | ✅ | T019 registra `public_gallery.load_failed` con contexto estructurado; sin auditoría (no hay datos sensibles) | test: T019 |
| P15 Solo el sistema de diseño | ✅ | T001 añade colores, radios, altos, fuentes y componentes al sistema; T004 crea `SiteStylesTokensTest` antes de copiar ningún CSS | test: `DesignTokensTest` (T001), `SiteStylesTokensTest` (T004), `OnlyDesignTokensTest` y `npm run test:ui` (cada página, T024) |

## Preparación
- [ ] T001 Ampliar el sistema de diseño a 2.1.0 con la variante "sitio público" según `design.md` → Cambios: colores nuevos, `--font-display`, `--font-serif`, `--space-1…8`, `--duration-fast/base`, `--ease-out`, `--ease-spring`, `--weight-regular`, `--weight-strong`, `--text-body`, radios `--radius-hair…2xl` y `--spacing-control-lg` en un bloque `@theme static`; `DesignTokensTest` lee también ese bloque; `system.md` (Color, Tipografía con títulos fluidos y regla de fuentes, Espaciado y radios, Componentes del sitio público, Movimiento, Decisiones) y `system.html` — `resources/css/app.css`, `tests/Modules/Core/Unit/Design/DesignTokensTest.php`, `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` pasa con los tokens nuevos (valor y contraste documentados; "decorativo" los que no pintan texto) y falla si se cambia el valor de uno en `app.css` (control), `npm run build` deja `--color-primary-deep`, `--radius-xl` y `--font-serif` en el CSS generado (prueba de concepto de `@theme static`; si no, nota y bloque alternativo que el test también lea) y la suite está en verde — cubre: CA1
- [ ] T002 [P] Instalar Playwright global con Chromium en el equipo y comprobar `evaluate.mjs` contra la portada actual servida en local — ningún archivo del repositorio (equipo local) — hecho cuando: `evaluate.mjs` termina con código 0 o 1 (no 3) y deja `report.md`; nota con la versión instalada
- [ ] T003 [P] Crear `config/site.php` (interruptor `new_landing`, nombre, `url` = `seo.site_url`, contacto con `null` para lo no confirmado, horario, tratamientos, títulos y descripciones de las seis rutas) y `SITE_NEW_LANDING=true` en `.env.example` — `config/site.php`, `.env.example`, `tests/Modules/Core/Unit/SiteConfigTest.php` — hecho cuando: `SiteConfigTest` pasa (WhatsApp presente y distinto de `521234567890`; ningún valor con `[Pendiente`, `[CORREO`, `+52 55 1234 5678`, `contacto@dentissa.com` ni `https://facebook.com`/`https://instagram.com` genéricos; cinco tratamientos; seis rutas con título único y descripción de 70–160 caracteres; `site.url` igual a `seo.site_url` de `.ai/project.yaml`), `grep -rn "env(" app/` sin resultados nuevos y la suite está en verde — cubre: CA7, CA11, CA17
  - nota: el usuario debe dar el número real de WhatsApp (y, si quiere, teléfono, correo y redes); sin él la tarea se bloquea (`/analyze 026` A6).
- [ ] T004 [P] Crear `SiteStylesTokensTest` con control positivo y negativo por regla: en `resources/css/site/**` ningún `#…`, `rgb(`, `hsl(`, `border-radius` en px ni `min-height`/`height` en px de un control (`btn`, `seg`, campos); `font-family` literal solo dentro de `@font-face`; toda `var(--x)` usada está declarada en `app.css` o en la propia hoja; en `resources/views/site/**` y `components/site/**` ningún `style=` con color ni `fill`/`stroke` literal; en `resources/js/site/**` ningún término de tratamiento descartado (lista del test); ningún host externo en `@import`, `@font-face`, `<link>` o `<script>` del sitio; cada `woff2` de `resources/fonts` con su licencia — `tests/Modules/Core/Unit/Design/SiteStylesTokensTest.php` — hecho cuando: los controles detectan cada caso, el test pasa sobre las carpetas aún vacías y la suite está en verde — cubre: CA1, CA8, CA17
- [ ] T005 [P] Ampliar los datos de `ui-audit` para que produzcan los estados de `design.md` → Pantallas: tres promociones vigentes y una vencida, cuatro testimonios, galería con 0, 1 y 3 imágenes e inicio sin contenido (un escenario elegible del comando), con la vigencia sobre `UiAuditClock` — `database/seeders/UiAuditSeeder.php`, `app/Core/Console/UiAuditDataCommand.php`, `tests/Modules/Core/Integration/UiAuditSeederTest.php` — hecho cuando: `UiAuditSeederTest` pasa con cada escenario y la suite está en verde — cubre: CA3, CA5

## Implementación

### Historia 1 y 4: el sitio nuevo con el contenido que publica el staff
- [ ] T006 Traer las fuentes y la base del CSS del sitio: `woff2` de Inter Tight (400–700) e Instrument Serif (normal e itálica), subconjunto latino, con `OFL.txt` (origen, versión, hash y herramienta de recorte, fuera de `package.json`); `site.css` con `@font-face` (`font-display: swap`), base y compatibilidad con el preflight, todo en `var(--…)`; `vite.config.js` añade `site.css` y recoge las entradas de `resources/js/site/pages/` — `resources/fonts/ (descargadas)`, `resources/css/site/site.css`, `vite.config.js` — hecho cuando: `SiteStylesTokensTest` pasa, `npm run build` genera la hoja con las fuentes locales y la suite está en verde — cubre: CA1, CA8 — depende: T001, T004
- [ ] T007 Traer los componentes y las animaciones con colores, radios y altos en tokens (mapeo de `design.md` → Cambios); la barra con su menú plegable y su punto de corte viven aquí, no en las hojas de página; los revelados ocultan solo cuando hay JavaScript (clase en `<html>`) y nunca con movimiento reducido — `resources/css/site/components.css`, `resources/css/site/animations.css`, `resources/css/site/site.css` — hecho cuando: `SiteStylesTokensTest` pasa, ningún color, radio ni alto de control de la propuesta queda sin token y `npm run build` está en verde — cubre: CA1, CA9 — depende: T006
- [ ] T008 Crear el layout del sitio con `lang="es"`, "Saltar al contenido", barra y pie de la propuesta con el logo y el favicon reales (`x-ui.brand`, 016 CA15), botón flotante de WhatsApp, datos de `config/site.php` (lo que está en `null` no se pinta) y `rel="noopener noreferrer"` en todo `target="_blank"` — `resources/views/layouts/site.blade.php`, `resources/views/components/site/header.blade.php`, `resources/views/components/site/footer.blade.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php` — hecho cuando: `PublicSitePagesTest` pasa sobre una vista de prueba que extiende el layout (`lang="es"`; logo de `/images/brand/` con `data-origen="propia"`; favicon e ícono de la marca; WhatsApp de config; un dato en `null` no aparece; todo `target=_blank` con `noopener noreferrer`; horario del pie = config) y la suite está en verde — cubre: CA1, CA7 — depende: T003, T007
- [ ] T009 Traer `core.js` (marca de JavaScript en `<html>`, revelados con versión reducida, estado "Abierto/Cerrado" con el horario en `data-*` que pinta el servidor y la hora de `America/Mexico_City` con `Intl`) y cargarlo desde el layout — `resources/js/site/core.js`, `resources/views/layouts/site.blade.php`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php`, `tests/Modules/ContentManagement/Unit/render-site-scripts.mjs` — hecho cuando: `SiteScriptsTest` pasa (lunes 18:59 abierto, 19:00 cerrado; sábado 13:59 abierto, 14:00 cerrado; domingo cerrado; con el reloj en UTC sigue siendo el de la clínica; con movimiento reducido los revelados quedan visibles), `PublicSitePagesTest` afirma que el layout carga `core.js` y la suite está en verde — cubre: CA7, CA9, CA10 — depende: T008
- [ ] T010 Pasar el Aviso de Privacidad y los Términos al layout nuevo cuando `site.new_landing` está activo, sin cambiar su texto ni su `@section('title'/'description')`; sus pantallas de `ui-audit` con los selectores nuevos — `resources/views/pages/landing/aviso-privacidad.blade.php`, `resources/views/pages/landing/terminos.blade.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php`, `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: el test pasa (con el interruptor activo, barra y pie nuevos; apagado, `layouts.landing`), `LegalPagesTest` y `PageStructureTest` siguen en verde, `ui-audit aviso-privacidad` y `terminos` pasan a 390, 768 y 1024 (menú plegable incluido) y la suite está en verde — cubre: CA1 — depende: T009
- [ ] T013 [P] Decidir la vigencia en `PromotionEntity` (inicio y fin, reloj de la aplicación en `America/Mexico_City`) y en `GetLandingContentUseCase` descartar las no vigentes y ordenar por fin — `app/Modules/ContentManagement/Modules/Promociones/Domain/Entities/PromotionEntity.php`, `app/Modules/ContentManagement/Application/UseCases/GetLandingContentUseCase.php`, `tests/Modules/ContentManagement/Unit/Application/GetLandingContentUseCaseTest.php` — hecho cuando: el test pasa con `travelTo('2026-11-12 12:00')` (fin `2026-11-12` se muestra, `2026-11-11` no, inicio `2026-11-13` no; fines 20 y 15 → primero la del 15; solo se pide `visible`) y la suite está en verde — cubre: CA2, CA14
- [ ] T014 Crear el inicio con el contenido real (mapeo del plan → Contratos y datos: descripción de la certificación como institución; sin tipo ni estrellas), sus estados vacíos, recorrido y servicios con los tratamientos y sus textos desde config, fotos de la propuesta con `data-origen="generada"`, `width`/`height` y `fetchpriority="high"` en la de la primera pantalla; `ShowLandingPageController` elige vista según el interruptor; entrada `pages/inicio.js` que importa `inicio.css`; pantallas `inicio` de `ui-audit` — `resources/views/site/inicio.blade.php`, `resources/css/site/pages/inicio.css`, `resources/js/site/pages/inicio.js`, `app/Modules/ContentManagement/Infrastructure/HTTP/Controllers/ShowLandingPageController.php`, `public/images/site/ (copiadas de nueva-landing)`, `tests/Modules/ContentManagement/Integration/PublicSiteContentTest.php`, `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: `PublicSiteContentTest` pasa (una promoción → solo cupón; varias → cupón con la que vence antes y boletos; certificación con su descripción; 0 certificaciones → sin sección; testimonios 1/2/4 → sin carrusel/carrusel/cinta; 0 → invitación a WhatsApp; ocultos ausentes; `<script>` en nombre o descripción sale escapado; el caso de uso lanza → 200 con estados vacíos, sin el mensaje y con `Log::error`; interruptor apagado → `pages.landing.inicio`), `evaluate.mjs inicio` pasa, `ui-audit inicio` pasa y la suite está en verde · verificación manual: inicio a 390 y 1440 px contra la propuesta — cubre: CA1, CA2, CA3, CA4, CA14, CA15, CA17 — depende: T005, T010, T013
  - nota: la vista solo usa salida escapada de Blade para el contenido (TM2).
- [ ] T015 Traer `home.js` (promociones sin filtrar ni reordenar, carrusel y cinta de testimonios con botón de pausa y pausa al tener el foco o fuera de pantalla, preguntas frecuentes) importado por `pages/inicio.js` — `resources/js/site/home.js`, `resources/js/site/pages/inicio.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (con el reloj en UTC+1 y fin = hoy en la clínica, la promoción sigue visible y el orden es el del HTML; el carrusel se detiene con el botón y con el foco dentro; con movimiento reducido no avanza solo; textos con `textContent`), `ui-audit inicio` pasa y la suite está en verde — cubre: CA2, CA10 — depende: T014
- [ ] T017 Crear "Acerca de nosotros" con su entrada `pages/nosotros.js` y servirla desde `/acerca-de-nosotros` según el interruptor; pantallas `acerca` de `ui-audit` — `resources/views/site/nosotros.blade.php`, `resources/css/site/pages/nosotros.css`, `resources/js/site/pages/nosotros.js`, `routes/web.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php`, `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: el test pasa (200 con el layout nuevo y un `h1`; interruptor apagado → `pages.landing.acerca`), `evaluate.mjs nosotros` pasa, `ui-audit acerca` pasa y la suite está en verde · verificación manual: a 390 y 1440 px contra la propuesta — cubre: CA1, CA7 — depende: T010
- [ ] T019 Servir la galería desde el servidor: `ShowGalleryPageController` con `GetGalleryImagesUseCase` (estado `visible`), caché de 10 min, vacío ante fallo con `Log::error('public_gallery.load_failed', …)`; la vista con el recorrido del consultorio según `design.md` (0 → aviso, 1–2 → fila estática, ≥ 3 → recorrido), `<img>` con `alt` = descripción; entrada `pages/galeria.js` con `galeria.css`; ruta `/galeria`; pantallas `galeria` de `ui-audit` — `app/Modules/ContentManagement/Infrastructure/HTTP/Controllers/ShowGalleryPageController.php`, `routes/web.php`, `resources/views/site/galeria.blade.php`, `resources/css/site/pages/galeria.css`, `resources/js/site/pages/galeria.js`, `tests/Modules/ContentManagement/Integration/PublicGalleryPageTest.php`, `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: `PublicGalleryPageTest` pasa (imágenes visibles en el HTML sin JS; ocultas ausentes; 0, 1 y 3 imágenes con su composición; el caso de uso lanza → 200 vacía con el log y sin el mensaje; segunda petición sin consulta a la base; TTL de 10 min; interruptor apagado → `pages.landing.galeria`), `ui-audit galeria` pasa y la suite está en verde — cubre: CA5, CA14 — depende: T005, T010
- [ ] T020 Traer `gallery.js` (recorrido horizontal y comparador de antes y después con control deslizante por flechas, Inicio y Fin; textos de tratamientos tomados del HTML, no escritos en el JS) importado por `pages/galeria.js` — `resources/js/site/gallery.js`, `resources/js/site/pages/galeria.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (flecha derecha mueve el control y actualiza `aria-valuenow`; el selector cambia el par visible y su `aria-pressed`), `SiteStylesTokensTest` pasa (ningún término descartado en el JS), `ui-audit galeria` pasa y la suite está en verde — cubre: CA10, CA17, CA18 — depende: T019
- [ ] T021 Poner el antes y después con fotos: pares de muestra por tratamiento del selector (Blanqueamiento, Ortodoncia, Implantes) de bancos con licencia libre, `data-origen="muestra"`, `CREDITOS.md` con autor, fuente, licencia y hash; nombres y textos desde config — `public/images/site/antes-despues/ (descargadas)`, `resources/views/site/galeria.blade.php`, `tests/Modules/ContentManagement/Integration/PublicGalleryPageTest.php` — hecho cuando: el test pasa (un par de `<img>` con `alt` "Antes"/"Después" por tratamiento, cada uno en la lista de config, `data-origen` presente), `evaluate.mjs galeria` pasa (K7 incluido), `ui-audit galeria` pasa y la suite está en verde · verificación manual: galería a 390 y 1440 px contra la propuesta — cubre: CA17, CA18 — depende: T020
- [ ] T022 Crear contacto con el formulario de la propuesta (motivo y mensaje, sin `action` ni `@csrf`), el enlace directo a WhatsApp visible sin JavaScript, "Abierto/Cerrado", dirección, enlaces de mapa y horario; entrada `pages/contacto.js` con `contacto.css`; ruta `/contacto` según el interruptor; pantallas `contacto` de `ui-audit` — `resources/views/site/contacto.blade.php`, `resources/css/site/pages/contacto.css`, `resources/js/site/pages/contacto.js`, `routes/web.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php`, `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: el test pasa (200 con layout nuevo; formulario sin `action` ni `@csrf`; enlace a `https://wa.me/<número de config>` presente en el HTML; datos y horario = config; todo `target=_blank` de la página con `noopener noreferrer`; interruptor apagado → `pages.landing.contacto`), `evaluate.mjs contacto` pasa, `ui-audit contacto` pasa y la suite está en verde · verificación manual: a 390 y 1440 px contra la propuesta — cubre: CA1, CA6, CA7 — depende: T010
- [ ] T023 Traer `contact.js` importado por `pages/contacto.js`: compone `https://wa.me/<número>?text=` con el motivo elegido y el mensaje codificados con `encodeURIComponent`; con mensaje vacío no abre WhatsApp y marca el campo — `resources/js/site/contact.js`, `resources/js/site/pages/contacto.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (mensaje con `<img src=x onerror=alert(1)>` llega codificado en la URL y no crea nodos; vacío → no abre y marca el campo), `ui-audit contacto` pasa y la suite está en verde — cubre: CA6, CA16 — depende: T022
- [ ] T025 Comprobar en las cuatro páginas que solo se nombran los tratamientos de `config/site.php` (recorrido, servicios, selector del antes y después) y que todo `target="_blank"` de las seis rutas lleva `noopener noreferrer`; corregir cualquier texto de la propuesta que nombre otro tratamiento — `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php`, `resources/views/site/inicio.blade.php` — hecho cuando: el test pasa (cada tratamiento nombrado está en la lista; ninguno de los términos descartados aparece) y la suite está en verde — cubre: CA17 — depende: T014, T017, T021, T022

### Historia 3: rápido y accesible en cualquier teléfono
- [ ] T016 Traer `tooth3d.js` importado de forma diferida por `pages/inicio.js` tras el evento `load`; sin WebGL o con `prefers-reduced-motion` no crea el lienzo y deja la versión estática del recorrido con sus textos — `resources/js/site/tooth3d.js`, `resources/js/site/pages/inicio.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (sin `WebGLRenderingContext` → sin `<canvas>` y con la clase de versión estática; con movimiento reducido igual), `evaluate.mjs inicio` pasa G-reducido, K4a y K2b (LCP ≤ 2.5 s), `ui-audit inicio` pasa y la suite está en verde — cubre: CA8, CA9 — depende: T015
- [ ] T024 Añadir a `ui-audit` los estados que faltan con los escenarios de T005 (inicio sin contenido, carrusel y cinta, galería con 0 y 1 imagen, menú móvil abierto en cada página) y correr `ui-audit` completo del sitio — `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: `npm run test:ui -- --only inicio`, `acerca`, `galeria`, `contacto`, `aviso-privacidad` y `terminos` pasan a 390, 768 y 1440 px; `OnlyDesignTokensTest` y `DesignTokensTest` en verde · verificación manual: NVDA recorre el inicio y la galería (menú, carrusel con pausa, antes y después) — cubre: CA3, CA10 — depende: T016, T023, T025

### Historia 5: buscadores y compartir
- [ ] T011 Crear el componente SEO del layout: título y descripción (las legales, desde su `@section`, como única fuente), canónica absoluta sin barra, `og:title`, `og:description`, `og:type`, `og:url`, `og:locale` (`es_MX`), `og:site_name`, `theme-color` = `--color-primary` y JSON-LD `Dentist` cuando la ruta lo pide en config, sin datos en `null`, con `json_encode(JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)`; precarga de las dos fuentes de la primera pantalla — `resources/views/components/site/seo.blade.php`, `resources/views/layouts/site.blade.php`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: `PublicSiteSeoTest` pasa en las dos rutas legales (`lang="es"`, una canónica, una sola meta description igual a la de su vista, `og:url` = canónica, `theme-color` = valor del token en `app.css`; sin JSON-LD), con un control de JSON-LD que contiene `</script>` y sigue parseando, `LegalPagesTest` en verde y la suite está en verde — cubre: CA11, CA13, CA15 — depende: T010
- [ ] T012 Servir `/sitemap.xml` desde las seis rutas indexables de config y declararlo en `robots.txt` — `app/Core/Http/ShowSitemapController.php`, `routes/web.php`, `public/robots.txt`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa (200 `application/xml`, XML válido con seis `loc` absolutos sin barra final y ninguna ruta de acceso ni del panel; `robots.txt` con `Sitemap: https://dentissapp.com/sitemap.xml` y sin `Disallow: /`) y la suite está en verde — cubre: CA12 — depende: T003, T011
- [ ] T026 Extender `PublicSiteSeoTest` a las cuatro páginas: título y descripción únicos entre las seis rutas, `lang="es"`, un `h1`, JSON-LD `Dentist` solo en inicio y contacto con nombre, dirección, teléfono y horario iguales a los visibles y sin los datos en `null` — `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa y la suite está en verde — cubre: CA11, CA13 — depende: T012, T014, T017, T021, T022
- [ ] T027 Generar la imagen general para compartir (última tarea, con el sistema ya ampliado): tarjeta de 1200×630 con el logo real, la frase principal de config y las fuentes del sitio, añadida al generador existente; `og:image` absoluto, `og:image:width`/`height` y `og:image:alt` en el componente SEO; la tarjeta en `system.md`/`system.html` (`#compartir`) — `tests/Browser/make-brand-assets.mjs`, `public/images/brand/compartir.png (generado por npm run brand:assets)`, `resources/views/components/site/seo.blade.php`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa (seis rutas con `og:image` absoluto a un archivo que existe, PNG de 1200×630 y ≤ 300 kB, `og:image:alt`; la frase registrada en el generador coincide con la de config) y la suite está en verde · verificación manual: la tarjeta contra `system.html#compartir` (zona segura, título sin cortes, contraste) — cubre: CA19 — depende: T026

## Integración y documentación
- [ ] T028 Actualizar `docs/seo.md` (reglas del sitio, inventario de las seis rutas, `sitemap.xml` con seis rutas en SE3.a, Open Graph, datos estructurados, `theme-color`; SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b y SE7.a → "en curso (spec 026)"; SE8.a → "parcial: sitio público en la 026, panel y portal pendientes"; SE2.b, SE2.c y SE4.a siguen pendientes) y el roadmap (deuda SEO del sitio cubierta por la 026; `theme-color` del panel y del portal y RS4.a pendientes) — `docs/seo.md`, `docs/roadmap.md` — hecho cuando: `python .ai/bin/aidd.py validate` sin errores y cada corrección cambia de estado por separado — cubre: CA11, CA12, CA13, CA19 — depende: T027
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: Frontend (layout, CSS y JS del sitio público, entradas por página, galería sin API) y Backend (`ShowGalleryPageController`, `ShowSitemapController`, `config/site.php`)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: `SITE_NEW_LANDING` (variables y rollback inmediato con `config:cache`), log `public_gallery.load_failed`
- [ ] T092 Marcar spec como `implemented`
  - Antes de marcar: Lighthouse en Chrome, perfil móvil, sobre la build de producción servida en local en las cuatro rutas (Rendimiento ≥ 90, Accesibilidad, Buenas prácticas y SEO 100), con resultados en `review.md`; `evaluate.mjs` de las cuatro páginas sin fallos; imágenes de muestra y generadas listadas en `design.md`.

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): verificación en producción tras T097. Las seis rutas 200 con el diseño nuevo; `/sitemap.xml` y `robots.txt`; Lighthouse móvil en las cuatro rutas con los umbrales de CA8, anotado en `docs/seo.md` → Verificación en producción; vista previa al compartir `https://dentissapp.com` en WhatsApp con la imagen y el título (CA19); validador de resultados enriquecidos sobre inicio y contacto (CA13); en Loki, sin `public_gallery.load_failed` ni `Unable to load landing` en la hora siguiente.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`
  - Después, y solo con la confirmación del usuario: `/chore` que borra las vistas, componentes y JS viejos, `PublicGalleryScriptTest` y el interruptor (decisión del usuario, 2026-11-12).

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T004, T008, T010, T014, T017, T022 | T001, T006, T007, T008, T010, T014, T017, T022 |
| CA2 | T013, T014, T015 | T013, T014, T015 |
| CA3 | T005, T014, T024 | T005, T014 |
| CA4 | T014 | T014 |
| CA5 | T005, T019 | T005, T019 |
| CA6 | T022, T023 | T022, T023 |
| CA7 | T003, T008, T009, T017, T022 | T003, T008, T009, T017, T022 |
| CA8 | T004, T016 | T006, T016 |
| CA9 | T009, T016 | T007, T009, T016 |
| CA10 | T009, T015, T020, T024 | T009, T015, T020 |
| CA11 | T003, T011, T026 | T003, T011 |
| CA12 | T012 | T012 |
| CA13 | T011, T026 | T011 |
| CA14 | T013, T014, T019 | T013, T014, T019 |
| CA15 | T011, T014 | T011, T014 |
| CA16 | T023 | T023 |
| CA17 | T003, T004, T014, T020, T021, T025 | T003, T014, T020, T021, T025 |
| CA18 | T020, T021 | T020, T021 |
| CA19 | T027 | T027 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T013, T014, T019 | T013, T014, T019 |
| TM2 | T011, T014 | T011, T014 |
| TM3 | T015, T020, T023 | T023 |
| TM4 | T008, T022, T025 | T008, T022, T025 |
| TM5 | T019 | T019 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Sistema de diseño (`app.css`, `system.md`, `system.html`, `DesignTokensTest`) | T001, T027 |
| `resources/fonts/` | T006 |
| `resources/css/site/` | T006, T007, T014, T017, T019, T022 |
| `resources/js/site/` | T009, T014, T015, T016, T017, T019, T020, T022, T023 |
| `layouts/site` y `components/site/*` | T008, T009, T011, T027 |
| `resources/views/site/*` | T014, T017, T019, T021, T022, T025 |
| Páginas legales | T010 |
| `config/site.php` | T003 |
| ContentManagement · `PromotionEntity` y `GetLandingContentUseCase` | T013 |
| ContentManagement · `ShowLandingPageController` | T014 |
| ContentManagement · `ShowGalleryPageController` | T019 |
| Core · `ShowSitemapController` | T012 |
| `routes/web.php` | T012, T017, T019, T022 |
| `public/robots.txt` | T012 |
| `public/images/site/` | T014, T021 |
| `make-brand-assets.mjs` y `compartir.png` | T027 |
| `tests/Browser/screens.json`, `baseline-1440.json` y `UiAuditSeeder` | T005, T010, T014, T017, T019, T022, T024 |
| `vite.config.js` | T006 |
| `.env.example` | T003 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Lighthouse móvil 90 / 100 / 100 / 100 | T016 (medidas de `evaluate.mjs`), T092 (manual en local), T095 (manual en producción) |
| WCAG 2.1 AA | T010, T014, T017, T019, T022, T024 (`ui-audit` y `evaluate.mjs`) |
| Sin servicios de terceros para mostrarse | T004, T006 |
| Contenido publicado visible en ≤ 10 min | T019 |
| Descubribilidad | T011, T012, T026, T027, T095 |
