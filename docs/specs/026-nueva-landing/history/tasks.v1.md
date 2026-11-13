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

Referencias fijas de todas las tareas de página: la propuesta en `nueva-landing/dentissa-laravel/`
(composición que no se cambia, `design.md` → Resultado) y `design/criterios.json`.
`evaluate.mjs` = `node E:/AI-DD-Setup/skills/design-spec/scripts/evaluate.mjs <url> --criteria
docs/specs/026-nueva-landing/design/criterios.json --out .ai/inspect/026-<página>` sobre la build
servida en local (`npm run build` y la app en Docker), con Playwright global (T002).

Los avisos del validador por tareas de 4–5 archivos cuentan tests y carpetas de imágenes o fuentes:
ninguna tarea toca más de 3 archivos de código.

## Constitution Check
Las tareas no cambian el Constitution Check de [plan.md](../plan.md):

| Principio | Resultado | Cómo lo cumplen las tareas | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño y plan `approved` | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada rebanada lleva su test (Pest, Node o `ui-audit`), visto fallar antes de implementar | test: `./vendor/bin/pest --parallel` y `npm run test:ui` en CI; T023 |
| P3 Capas del módulo | ✅ | T012 pone la vigencia en el caso de uso con `PromotionEntity`; T017 usa `GetGalleryImagesUseCase`; ningún import nuevo entre módulos | manual: revisión en `/review` y búsqueda de `Infrastructure` dentro de `Domain/` |
| P4 Contrato de API primero | ➖ | Ninguna tarea toca `/api/v1` | — |
| P5 Autorización en el servidor | ➖ | Rutas públicas sin cambio de autorización | — |
| P6 Validación con FormRequest | ➖ | Sin entradas al servidor | — |
| P7 Errores sin detalles internos | ✅ | T013 y T017 fuerzan el fallo del caso de uso y afirman la página vacía sin el mensaje | test: T013, T017 |
| P8 Secretos fuera del repositorio | ✅ | T003 añade `SITE_NEW_LANDING` a `.env.example` y solo `config/site.php` llama a `env()` | test: `gitleaks` en CI; lint: `grep -rn "env(" app/` en T003 |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ✅ | Ninguna tarea toca `composer.json` ni `package.json`; fuentes e imágenes son archivos; Playwright es global (T002) | manual: diff de `composer.json`/`package.json` en `/review` |
| P11 Datos sensibles y modelo de amenazas | ✅ | TM1–TM5 en sus rebanadas (Cobertura); el único `Log::` nuevo (T017) no lleva datos de visitantes | manual: `aidd.py validate`; revisión de `Log::` en `/review` |
| P12 Producción con aprobación humana | ✅ | Línea base de auditorías en 0 (plan); T095–T098 para `/release` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Vigencia y orden en T012; el JS solo presenta (T008, T014, T018, T021) | manual: revisión del JS y Blade en `/review` |
| P14 Trazabilidad | ✅ | T017 registra `public_gallery.load_failed` con contexto estructurado; sin auditoría (no hay datos sensibles) | test: T017 |
| P15 Solo el sistema de diseño | ✅ | T001 añade los tokens primero; T004 crea `SiteStylesTokensTest` antes de copiar ningún CSS; T023 corre `ui-audit` | test: `DesignTokensTest` (T001), `SiteStylesTokensTest` (T004), `OnlyDesignTokensTest` y `npm run test:ui` (T023) |

## Preparación
- [ ] T001 Ampliar el sistema de diseño a 2.1.0 con la variante "sitio público": tokens nuevos de color (`--color-primary-deep`, `--color-line-strong`, `--color-muted-soft`, `--color-canvas-alt`, `--color-primary-tint`, `--color-primary-tint-2`, `--color-secondary-soft`, `--color-success-tint`), `--font-display`, `--font-serif`, `--space-1…8`, `--duration-fast/base`, `--ease-spring` en un bloque `@theme static`; tabla Color, Tipografía (títulos fluidos y regla de fuentes), Movimiento y Decisiones de `system.md`; `system.html`; añadir los colores a `design/criterios.json → tokens.colors` si falta alguno — `resources/css/app.css`, `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `DesignTokensTest` pasa con los tokens nuevos y su contraste documentado (los que no pintan texto, "decorativo"), `npm run build` deja `--color-primary-deep` y `--font-serif` en el CSS generado (prueba de concepto de `@theme static`; si no, bloque `:root` en `app.css` y nota) y la suite está en verde — cubre: CA1
- [ ] T002 [P] Instalar Playwright global con Chromium en el equipo y comprobar `evaluate.mjs` contra la portada actual servida en local — ningún archivo del repositorio (equipo local) — hecho cuando: `evaluate.mjs http://localhost:8000/ --criteria …` termina con código 0 o 1 (no 3) y deja `report.md`; nota con la versión instalada
- [ ] T003 [P] Crear `config/site.php` (interruptor `new_landing`, nombre, URL, contacto, horario, redes, tratamientos, títulos y descripciones de las seis rutas indexables) con los datos que hoy sirve producción y añadir `SITE_NEW_LANDING=true` a `.env.example` — `config/site.php`, `.env.example`, `tests/Modules/Core/Unit/SiteConfigTest.php` — hecho cuando: `SiteConfigTest` pasa (ningún valor con `[Pendiente`, `[CORREO` ni el número de ejemplo `521234567890` salvo confirmación del usuario; cinco tratamientos; seis rutas con título único y descripción de 70–160 caracteres; horario de lunes a sábado), `grep -rn "env(" app/` sin resultados nuevos y la suite está en verde — cubre: CA7, CA11, CA17
  - nota: si el número de WhatsApp, el correo o las redes de producción son de ejemplo, la tarea se bloquea y pregunta al usuario el dato real (plan → Riesgos).
- [ ] T004 [P] Crear `SiteStylesTokensTest`: ningún `#…`, `rgb(`, `hsl(` ni `font-family` literal en `resources/css/site/**` (salvo `var(`), ningún `style=` con color ni `fill`/`stroke` literal en `resources/views/site/**` y `components/site/**`, ningún host externo en `@import`, `@font-face`, `<link>` o `<script>` del sitio; cada regla con su control positivo — `tests/Modules/Core/Unit/Design/SiteStylesTokensTest.php` — hecho cuando: los controles positivos detectan cada literal de ejemplo, el test pasa sobre las carpetas aún vacías y la suite está en verde — cubre: CA1, CA8

## Implementación

### Historia 1 y 4: el sitio nuevo con el contenido que publica el staff
- [ ] T005 Traer las fuentes y la base del CSS del sitio: `woff2` de Inter Tight (400–700) e Instrument Serif (normal e itálica), subconjunto latino, con `OFL.txt`; `site.css` como entrada con `@font-face` (`font-display: swap`), la base y la compatibilidad con el preflight de la propuesta, con cada color y fuente pasados a `var(--…)`; entrada en Vite — `resources/fonts/ (descargadas del repositorio oficial de cada familia)`, `resources/css/site/site.css`, `vite.config.js` — hecho cuando: `SiteStylesTokensTest` pasa, `npm run build` genera la hoja con las fuentes locales y la suite está en verde — cubre: CA1, CA8 — depende: T001, T004
- [ ] T006 Traer los componentes y las animaciones de la propuesta con sus colores en tokens (mapeo de `design.md` → Cambios: existentes, transparencias con `color-mix` o el token con opacidad, y los nueve nuevos) — `resources/css/site/components.css`, `resources/css/site/animations.css`, `resources/css/site/site.css` — hecho cuando: `SiteStylesTokensTest` pasa, ningún color de la propuesta queda sin su token y `npm run build` está en verde — cubre: CA1, CA9 — depende: T005
- [ ] T007 Crear el layout del sitio y su barra superior y pie (con el botón flotante de WhatsApp) con datos de `config/site.php`, enlace "Saltar al contenido", `rel="noopener noreferrer"` en todo `target="_blank"` — `resources/views/layouts/site.blade.php`, `resources/views/components/site/header.blade.php`, `resources/views/components/site/footer.blade.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php` — hecho cuando: `PublicSitePagesTest` pasa sobre una vista de prueba que extiende el layout (barra, pie y botón de WhatsApp con el número de config; página activa marcada; todo `target=_blank` con `noopener`; contacto y horario del pie = config) y la suite está en verde — cubre: CA1, CA7 — depende: T003, T006
- [ ] T008 Traer `core.js` (menú, revelados con versión reducida, estado "Abierto/Cerrado" calculado con el horario de `data-*` que pinta el servidor y la hora de `America/Mexico_City` con `Intl`) y su prueba con Node — `resources/js/site/core.js`, `vite.config.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php`, `tests/Modules/ContentManagement/Unit/render-site-scripts.mjs` — hecho cuando: `SiteScriptsTest` pasa (lunes 18:59 abierto, 19:00 cerrado; sábado 13:59 abierto, 14:00 cerrado; domingo cerrado; con el reloj en UTC el resultado sigue siendo el de la clínica; con `prefers-reduced-motion` los revelados quedan visibles sin animar) y la suite está en verde — cubre: CA7, CA9, CA10 — depende: T007
- [ ] T009 Pasar el Aviso de Privacidad y los Términos al layout nuevo cuando `site.new_landing` está activo, sin cambiar su texto — `resources/views/pages/landing/aviso-privacidad.blade.php`, `resources/views/pages/landing/terminos.blade.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php` — hecho cuando: el test pasa (con el interruptor activo, las dos rutas con barra y pie nuevos; apagado, con `layouts.landing`), `LegalPagesTest` sigue en verde y la suite está en verde — cubre: CA1 — depende: T007
- [ ] T012 [P] Descartar en `GetLandingContentUseCase` las promociones vencidas o aún no iniciadas (con `PromotionEntity`, zona `America/Mexico_City`) y ordenarlas por fin de vigencia — `app/Modules/ContentManagement/Application/UseCases/GetLandingContentUseCase.php`, `tests/Modules/ContentManagement/Unit/Application/GetLandingContentUseCaseTest.php` — hecho cuando: el test pasa con reloj fijo `2026-11-12 12:00` (fin `2026-11-12` se muestra, `2026-11-11` no, inicio `2026-11-13` no; fines 20 y 15 → primero la del 15; solo se pide estado `visible`) y la suite está en verde — cubre: CA2, CA14
- [ ] T013 Crear la vista del inicio con el contenido real (mapeo del plan → Contratos y datos; sin institución, tipo ni estrellas), sus estados vacíos, el recorrido y los servicios con los tratamientos de config, las fotos de la propuesta con `data-origen="generada"`, `width`/`height` y la de la primera pantalla con `fetchpriority="high"`; `ShowLandingPageController` elige vista según el interruptor — `resources/views/site/inicio.blade.php`, `resources/css/site/pages/inicio.css`, `app/Modules/ContentManagement/Infrastructure/HTTP/Controllers/ShowLandingPageController.php`, `public/images/site/ (copiadas de nueva-landing)`, `tests/Modules/ContentManagement/Integration/PublicSiteContentTest.php` — hecho cuando: `PublicSiteContentTest` pasa (promociones vigentes en orden: una → solo cupón, varias → cupón con la que vence antes y boletos; certificaciones visibles y, con 0, sin sección; testimonios: 1 sin carrusel, 2 con carrusel, 4 con cinta, 0 con invitación a WhatsApp; ocultos ausentes; `<script>` en nombre o descripción sale escapado; el caso de uso lanza → 200 con estados vacíos, sin el mensaje y con `Log::error`; interruptor apagado → `pages.landing.inicio`), `SiteStylesTokensTest` en verde, `evaluate.mjs` pasa en `/` y la suite está en verde · verificación manual: inicio a 390 y 1440 px contra la propuesta — cubre: CA1, CA2, CA3, CA4, CA14, CA15, CA17 — depende: T008, T012
  - nota: la vista solo usa salida escapada de Blade para el contenido (TM2).
- [ ] T014 Traer `home.js` (promociones, carrusel y cinta de testimonios con botón de pausa y pausa al tener el foco o fuera de pantalla, preguntas frecuentes) — `resources/js/site/home.js`, `vite.config.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (el carrusel se detiene con el botón y con el foco dentro; con movimiento reducido no avanza solo; los textos los pone con `textContent`) y la suite está en verde — cubre: CA2, CA10 — depende: T013
- [ ] T016 Crear la vista "Acerca de nosotros" y servirla desde `/acerca-de-nosotros` según el interruptor — `resources/views/site/nosotros.blade.php`, `resources/css/site/pages/nosotros.css`, `routes/web.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php` — hecho cuando: el test pasa (200 con el layout nuevo y un `h1`; datos de contacto de config; interruptor apagado → `pages.landing.acerca`), `evaluate.mjs` pasa en `/acerca-de-nosotros` y la suite está en verde · verificación manual: a 390 y 1440 px contra la propuesta — cubre: CA1, CA7 — depende: T008
- [ ] T017 Servir la galería desde el servidor: `ShowGalleryPageController` con `GetGalleryImagesUseCase` (estado `visible`), caché de 10 min, vacío ante fallo con `Log::error('public_gallery.load_failed', …)`; la vista con el recorrido del consultorio hecho de las imágenes publicadas (`<img>` con `alt` = descripción) y su estado vacío; ruta `/galeria` — `app/Modules/ContentManagement/Infrastructure/HTTP/Controllers/ShowGalleryPageController.php`, `routes/web.php`, `resources/views/site/galeria.blade.php`, `tests/Modules/ContentManagement/Integration/PublicGalleryPageTest.php` — hecho cuando: `PublicGalleryPageTest` pasa (imágenes visibles en el HTML sin JS, ocultas ausentes, sin imágenes → estado vacío, el caso de uso lanza → 200 vacía con el log y sin el mensaje, segunda petición sin consulta a la base, TTL de 10 min, interruptor apagado → `pages.landing.galeria`) y la suite está en verde — cubre: CA5, CA14 — depende: T008
- [ ] T018 Traer la hoja y el script de la galería (recorrido horizontal y antes y después con control deslizante que responde a flechas, Inicio y Fin) — `resources/css/site/pages/galeria.css`, `resources/js/site/gallery.js`, `vite.config.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (flecha derecha mueve el control y actualiza `aria-valuenow`; el selector cambia el par visible y su `aria-pressed`), `SiteStylesTokensTest` en verde y la suite está en verde — cubre: CA10, CA18 — depende: T017
- [ ] T019 Poner el antes y después con fotos: pares de muestra por tratamiento del selector (Blanqueamiento, Ortodoncia, Implantes) descargados de bancos con licencia libre, `data-origen="muestra"`, `CREDITOS.md` con autor, fuente y licencia — `public/images/site/antes-despues/ (descargadas)`, `resources/views/site/galeria.blade.php`, `tests/Modules/ContentManagement/Integration/PublicGalleryPageTest.php` — hecho cuando: el test pasa (un par de `<img>` con `alt` "Antes"/"Después" por tratamiento, cada tratamiento en la lista de config, `data-origen` presente), `evaluate.mjs` pasa en `/galeria` (K7 incluido) y la suite está en verde · verificación manual: galería a 390 y 1440 px contra la propuesta — cubre: CA17, CA18 — depende: T018
  - nota: las fotos son de muestra; `design.md` → Imágenes de muestra las lista para sustituirlas.
- [ ] T020 Crear la vista de contacto (compositor de WhatsApp sin `action` al servidor ni `@csrf`, "Abierto/Cerrado", dirección, enlace a Maps, horario) y servirla desde `/contacto` según el interruptor — `resources/views/site/contacto.blade.php`, `resources/css/site/pages/contacto.css`, `routes/web.php`, `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php` — hecho cuando: el test pasa (200 con layout nuevo; formulario sin envío al servidor; datos y horario = config; interruptor apagado → `pages.landing.contacto`), `evaluate.mjs` pasa en `/contacto` y la suite está en verde · verificación manual: a 390 y 1440 px contra la propuesta — cubre: CA1, CA6, CA7 — depende: T008
- [ ] T021 Traer `contact.js`: compone `https://wa.me/<número de config>?text=` con nombre y mensaje codificados con `encodeURIComponent`, no abre WhatsApp con campos vacíos y muestra el error del campo — `resources/js/site/contact.js`, `vite.config.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (mensaje con `<img src=x onerror=alert(1)>` llega codificado en la URL y no crea nodos; vacío → no abre y marca el campo) y la suite está en verde — cubre: CA6, CA16 — depende: T020
- [ ] T022 Comprobar en las cuatro páginas que solo se nombran los tratamientos de `config/site.php` (recorrido, servicios, selector del antes y después) y corregir cualquier texto de la propuesta que nombre otro — `tests/Modules/ContentManagement/Integration/PublicSitePagesTest.php`, `resources/views/site/inicio.blade.php` — hecho cuando: el test pasa (cada tratamiento nombrado está en la lista; ninguno de una lista de términos de la propuesta descartados aparece) y la suite está en verde — cubre: CA17 — depende: T013, T016, T019, T020

### Historia 3: rápido y accesible en cualquier teléfono
- [ ] T015 Traer `tooth3d.js` cargado tras el evento `load` y solo en el inicio; sin WebGL o con `prefers-reduced-motion` no crea el lienzo y deja la versión estática del recorrido con sus textos — `resources/js/site/tooth3d.js`, `vite.config.js`, `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php` — hecho cuando: `SiteScriptsTest` pasa (sin `WebGLRenderingContext` → sin `<canvas>` y con la clase de versión estática; con movimiento reducido igual), `evaluate.mjs` en `/` pasa G-reducido, K4a y K2b (LCP ≤ 2.5 s) y la suite está en verde — cubre: CA8, CA9 — depende: T014
- [ ] T023 Añadir a `ui-audit` las pantallas del sitio nuevo y sus estados (inicio con y sin contenido, menú móvil, antes y después, contacto, legales) y rehacer su línea base de composición — `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: `npm run test:ui -- --only inicio`, `acerca`, `galeria`, `contacto` y las legales pasan a 390, 768 y 1440 px (contraste, foco, teclado, toque, desbordes); `OnlyDesignTokensTest` y `DesignTokensTest` en verde · verificación manual: NVDA recorre el inicio y la galería (menú, carrusel con pausa, antes y después) — cubre: CA3, CA10 — depende: T015, T021, T022

### Historia 5: buscadores y compartir
- [ ] T010 Crear el componente SEO del layout: título, descripción, canónica absoluta sin barra, `og:title`, `og:description`, `og:type`, `og:url`, `og:locale` (`es_MX`), `og:site_name`, `theme-color` (= `--color-primary`) y JSON-LD `Dentist` cuando la ruta lo pide en config, con `json_encode(JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)`; precarga de las dos fuentes de la primera pantalla — `resources/views/components/site/seo.blade.php`, `resources/views/layouts/site.blade.php`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: `PublicSiteSeoTest` pasa en las dos rutas legales (una canónica, título y descripción de config, `og:url` = canónica, `theme-color` = valor del token en `app.css`; sin JSON-LD), con un control de JSON-LD que contiene `</script>` y sigue parseando, y la suite está en verde — cubre: CA11, CA13, CA15 — depende: T009
- [ ] T011 Servir `/sitemap.xml` desde las seis rutas indexables de config y declararlo en `robots.txt` — `app/Core/Http/ShowSitemapController.php`, `routes/web.php`, `public/robots.txt`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa (200 `application/xml`, XML válido con seis `loc` absolutos sin barra final y ninguna ruta de acceso ni del panel; `robots.txt` con `Sitemap: https://dentissapp.com/sitemap.xml` y sin `Disallow: /`) y la suite está en verde — cubre: CA12 — depende: T003
- [ ] T024 Extender `PublicSiteSeoTest` a las cuatro páginas: título y descripción únicos entre las seis rutas, un `h1`, JSON-LD `Dentist` solo en inicio y contacto con nombre, dirección, teléfono y horario iguales a los visibles, sin contenido oculto ni vencido en el JSON-LD — `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa y la suite está en verde — cubre: CA11, CA13, CA14 — depende: T010, T013, T016, T019, T020
- [ ] T025 Generar la imagen general para compartir (última tarea, con el sistema ya ampliado): tarjeta de 1200×630 con la marca, la frase principal de config y las fuentes del sitio, añadida al generador existente; `og:image` absoluto, `og:image:width`/`height` y `og:image:alt` en el componente SEO; la tarjeta en `system.md`/`system.html` (`#compartir`) — `tests/Browser/make-brand-assets.mjs`, `public/images/brand/compartir.png (generado por npm run brand:assets)`, `resources/views/components/site/seo.blade.php`, `tests/Modules/Core/Integration/PublicSiteSeoTest.php` — hecho cuando: el test pasa (seis rutas con `og:image` absoluto a un archivo que existe, PNG de 1200×630 y ≤ 300 kB, `og:image:alt`; la frase de la tarjeta registrada en el generador coincide con la de config) y la suite está en verde · verificación manual: la tarjeta contra `system.html#compartir` (zona segura, título sin cortes, contraste) — cubre: CA19 — depende: T024

## Integración y documentación
- [ ] T026 Actualizar `docs/seo.md` (reglas del sitio, inventario de las seis rutas, `sitemap.xml`, Open Graph, datos estructurados, `theme-color`; SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b, SE7.a y SE8.a → "en curso (spec 026)"; SE2.b, SE2.c y SE4.a siguen pendientes) y el roadmap (deuda SEO del sitio público cubierta por la 026) — `docs/seo.md`, `docs/roadmap.md` — hecho cuando: `python .ai/bin/aidd.py validate` sin errores y cada corrección cambia de estado por separado — cubre: CA11, CA12, CA13, CA19 — depende: T025
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: Frontend (layout, CSS y JS del sitio público, galería sin API) y Backend (`ShowGalleryPageController`, `ShowSitemapController`, `config/site.php`)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: `SITE_NEW_LANDING` (variables y rollback inmediato con `config:cache`), log `public_gallery.load_failed`
- [ ] T092 Marcar spec como `implemented`
  - Antes de marcar: Lighthouse en Chrome, perfil móvil, sobre la build de producción servida en local en las cuatro rutas (Rendimiento ≥ 90, Accesibilidad, Buenas prácticas y SEO 100), con resultados en `review.md`; comparación de los datos de contacto con producción (CA7); `evaluate.mjs` en las cuatro rutas sin fallos; `max_samples` listado en `design.md`.

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
| CA1 | T004, T007, T009, T013, T016, T020 | T001, T005, T006, T007, T009, T013, T016, T020 |
| CA2 | T012, T013, T014 | T012, T013, T014 |
| CA3 | T013, T023 | T013 |
| CA4 | T013 | T013 |
| CA5 | T017 | T017 |
| CA6 | T020, T021 | T020, T021 |
| CA7 | T003, T007, T008, T016, T020 | T003, T007, T008, T016, T020 |
| CA8 | T004, T015 | T005, T015 |
| CA9 | T008, T015 | T006, T008, T015 |
| CA10 | T008, T014, T018, T023 | T008, T014, T018 |
| CA11 | T003, T010, T024 | T003, T010 |
| CA12 | T011 | T011 |
| CA13 | T010, T024 | T010 |
| CA14 | T012, T013, T017, T024 | T012, T013, T017 |
| CA15 | T010, T013 | T010, T013 |
| CA16 | T021 | T021 |
| CA17 | T003, T013, T019, T022 | T003, T013, T019, T022 |
| CA18 | T018, T019 | T018, T019 |
| CA19 | T025 | T025 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T012, T013, T017 | T012, T013, T017, T024 |
| TM2 | T010, T013 | T010, T013 |
| TM3 | T014, T018, T021 | T021 |
| TM4 | T007 | T007 |
| TM5 | T017 | T017 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Sistema de diseño (`app.css`, `system.md`, `system.html`) | T001, T025 |
| `resources/fonts/` | T005 |
| `resources/css/site/` | T005, T006, T013, T016, T018, T020 |
| `resources/js/site/` | T008, T014, T015, T018, T021 |
| `layouts/site` y `components/site/*` | T007, T010, T025 |
| `resources/views/site/*` | T013, T016, T017, T019, T020, T022 |
| Páginas legales | T009 |
| `config/site.php` | T003 |
| ContentManagement · `GetLandingContentUseCase` | T012 |
| ContentManagement · `ShowLandingPageController` | T013 |
| ContentManagement · `ShowGalleryPageController` | T017 |
| Core · `ShowSitemapController` | T011 |
| `routes/web.php` | T011, T016, T017, T020 |
| `public/robots.txt` | T011 |
| `public/images/site/` | T013, T019 |
| `make-brand-assets.mjs` y `compartir.png` | T025 |
| `tests/Browser/screens.json` y `baseline-1440.json` | T023 |
| `vite.config.js` | T005, T008, T014, T015, T018, T021 |
| `.env.example` | T003 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Lighthouse móvil 90 / 100 / 100 / 100 | T015 (medidas de `evaluate.mjs`), T092 (manual en local), T095 (manual en producción) |
| WCAG 2.1 AA | T023, `evaluate.mjs` en T013, T016, T019, T020 |
| Sin servicios de terceros para mostrarse | T004, T005 |
| Contenido publicado visible en ≤ 10 min | T017 |
| Descubribilidad | T010, T011, T024, T025, T095 |
