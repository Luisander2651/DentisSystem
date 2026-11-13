---
spec: 026-nueva-landing
status: draft
constitution_version: 1.2.1
created: 2026-11-12
---

# Plan · 026 Nueva landing del sitio público

## Enfoque técnico
Se trasplanta la propuesta de `nueva-landing/` **sin cambiar su composición**: sus vistas Blade, su
CSS propio y su JavaScript vanilla entran en el proyecto bajo `resources/views/site/`,
`resources/css/site/` y `resources/js/site/`, con un layout nuevo `layouts/site.blade.php`. El CSS
sigue siendo CSS plano (no se reescribe a utilidades de Tailwind, que movería el diseño), pero cada
color, radio, alto de control, fuente y curva sale de un `var(--…)` declarado una sola vez en
`@theme` de `resources/css/app.css`; un test nuevo prohíbe literales en `resources/css/site/` y
exige que toda variable usada esté declarada. La barra y el pie usan el logo real de la clínica
(`x-ui.brand`). El contenido
deja de ser de ejemplo: datos de la clínica y tratamientos en `config/site.php`, promociones,
certificaciones y testimonios desde el caso de uso actual, y la galería se renderiza en el servidor
con un controlador nuevo. Los metadatos SEO, el JSON-LD, el sitemap y la imagen para compartir se
generan desde esa misma configuración. Un interruptor `site.new_landing` permite volver a las vistas
viejas sin desplegar; las vistas viejas se borran después de liberar, solo con confirmación del
usuario.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 026 `approved` y `design.md` `approved` | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada tarea con su test Pest (HTML servido, JS con Node) o su comprobación de `npm run test:ui` | test: suite Pest en CI y `npm run test:ui` en CI; `/review` cruza Trazabilidad |
| P3 Capas del módulo | ✅ | Controladores invocables nuevos → `GetLandingContentUseCase` / `GetGalleryImagesUseCase` existentes; el filtro de vigencia usa `PromotionEntity` (dominio) en el caso de uso, no en la vista. Sin dependencia nueva entre módulos (CA17 usa `config/site.php`, no el catálogo de Appointments) | manual: revisión en `/review` y `grep "use App\\Modules\\.*\\Infrastructure" app/Modules/ContentManagement/**/Domain` |
| P4 Contrato de API primero | ➖ | No se crea ni cambia ningún endpoint de `/api/v1`; la ruta pública de galería queda igual | — |
| P5 Autorización en el servidor | ➖ | Rutas públicas de solo lectura cuya autorización no cambia; el contenido oculto se filtra en el servidor (CA14, TM1) | — |
| P6 Validación con FormRequest | ➖ | Ninguna entrada nueva llega al servidor: el formulario de contacto abre WhatsApp en el navegador | — |
| P7 Errores sin detalles internos | ✅ | Las páginas degradan a estados vacíos y registran con `Log::error` sin el mensaje en la respuesta (CA4) | test: `PublicSiteContentTest` fuerza la excepción del caso de uso → 200 con estados vacíos y sin texto de la excepción |
| P8 Secretos fuera del repositorio | ✅ | Variable nueva `SITE_NEW_LANDING` leída solo en `config/site.php` y añadida a `.env.example`; datos de contacto públicos en config | test: CI `gitleaks`; lint: `grep -rn "env(" app/` sin resultados en código tocado |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ✅ | Ninguna dependencia en `composer.json` ni `package.json`. Fuentes OFL como archivos `woff2` versionados; Playwright solo global en el equipo del desarrollador (decisión del usuario) | manual: diff de `composer.json`/`package.json` en `/review` (debe ser vacío) |
| P11 Datos sensibles y modelo de amenazas | ✅ | No toca datos sensibles; hay modelo de amenazas por las entradas externas (contenido publicado y formulario) | manual: `aidd.py validate` (sección presente); revisión de `Log::` en `/review` |
| P12 Producción con aprobación y rollback | ✅ | Rollout con interruptor y rollback por versión; `composer audit --locked` y `npm audit` en 0 (línea base 2026-11-12) | manual: `/release` exige confirmación y corre `security.tools` |
| P13 Lógica de negocio en el backend | ✅ | Vigencia de promociones, contenido visible y orden en el servidor. El JS solo presenta: "Abierto/Cerrado" con el horario que entrega el servidor, compositor de WhatsApp, carruseles | manual: revisión del JS y Blade tocados en `/review` |
| P14 Trazabilidad | ✅ | No toca datos sensibles ni auth: sin eventos de auditoría. El único log nuevo (fallo al leer la galería) usa contexto estructurado y hereda `X-Request-Id` del middleware existente | test: `PublicSiteContentTest` afirma el `Log::error` con contexto; manual: revisión en `/review` |
| P15 La interfaz usa solo el sistema de diseño | ✅ | Tokens nuevos primero en `system.md` y `@theme` (T001); CSS del sitio solo con `var(--…)`; vistas sin `style` con color | test: `OnlyDesignTokensTest`, `DesignTokensTest` y el nuevo `SiteStylesTokensTest`; `npm run test:ui` en CI |

Conteo: 10 cumplen · 5 no aplican · ninguno viola.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`resources/css/app.css`, `docs/design/system.md`, `system.html`, `DesignTokensTest`) | Bloque `@theme static` con los tokens del sitio público (colores nuevos, `--font-display`, `--font-serif`, `--space-*`, `--duration-*`, `--ease-out`, `--ease-spring`, `--weight-*`, `--text-body`, radios `--radius-hair…2xl`, `--spacing-control-lg`); `system.md` 2.1.0 con la variante "sitio público", la regla de fuentes y los componentes del sitio; `DesignTokensTest` lee también ese bloque | Medio: `DesignTokensTest` exige que cada token documentado tenga su valor y su par de contraste |
| `resources/fonts/` (nuevo) | `inter-tight-{400,500,600,700}.woff2` e `instrument-serif-{regular,italic}.woff2` (subconjunto latino) con `OFL.txt` | Bajo |
| `resources/css/site/` (nuevo) | Hojas de la propuesta (`base`, `components`, `animations`, `compat`, `pages/{inicio,nosotros,galeria,contacto}.css`) y entrada `site.css` con `@font-face`; `tokens.css` de la propuesta se elimina (sus valores ya están en `@theme`) | Medio: 381 apariciones de color escrito a mano (91 `#fff`, 26 `#d75078`…) y `style` en línea |
| `resources/js/site/` (nuevo) | `core.js`, `home.js`, `tooth3d.js` (cargado diferido), `gallery.js`, `contact.js`, entradas por página; `forms.js` no entra (es de las pantallas de acceso) | Medio: rendimiento del WebGL en móvil |
| `resources/views/layouts/site.blade.php` (nuevo) y `resources/views/components/site/{header,footer,whatsapp-float,seo}.blade.php` (nuevos) | Layout con `<head>` SEO (título, descripción, canónica, Open Graph, `theme-color`, JSON-LD, precarga de fuentes y de la imagen LCP) | Bajo |
| `resources/views/site/{inicio,nosotros,galeria,contacto}.blade.php` (nuevos) | Vistas de la propuesta con datos de `config/site.php` y del contenido real; `style` en línea → clases | Medio |
| `resources/views/pages/landing/{aviso-privacidad,terminos}.blade.php` | Pasan a extender `layouts.site` (cuando el interruptor está activo); su texto no cambia | Bajo: `LegalPagesTest` |
| `config/site.php` (nuevo) | Interruptor `new_landing`, datos de contacto (copiados de las vistas de producción), horario, redes, tratamientos, títulos y descripciones por ruta | Bajo |
| ContentManagement · `Application/UseCases/GetLandingContentUseCase.php` y `Modules/Promociones/Domain/Entities/PromotionEntity.php` | La entidad decide si está vigente (inicio y fin) con el reloj de la aplicación en `America/Mexico_City` (hoy `isExpired()` usa `new DateTime()`, que ignora `travelTo` y `UiAuditClock`); el caso de uso descarta las no vigentes y ordena por fin | Bajo: hoy la vista vieja muestra también vencidas visibles |
| ContentManagement · `Infrastructure/HTTP/Controllers/ShowLandingPageController.php` | Vista según el interruptor | Bajo |
| ContentManagement · `Infrastructure/HTTP/Controllers/ShowGalleryPageController.php` (nuevo) | Galería renderizada en el servidor con imágenes `visible`, caché de 10 min y degradación a vacío | Bajo |
| Core · `app/Core/Http/ShowSitemapController.php` (nuevo, junto a `ShowDashboardPageController`) | `GET /sitemap.xml` desde las rutas indexables de `config/site.php` | Bajo |
| `routes/web.php` | `/galeria` y `/sitemap.xml` a controladores; `/acerca-de-nosotros` y `/contacto` eligen vista según el interruptor | Bajo |
| `public/robots.txt` | Línea `Sitemap: https://dentissapp.com/sitemap.xml` | Bajo |
| `public/images/site/` (nuevo) | Fotos de la propuesta (`data-origen="generada"`) y pares de antes y después de muestra con `CREDITOS.md` | Bajo |
| `tests/Browser/make-brand-assets.mjs`, `public/images/brand/compartir.png` | La última tarea añade la tarjeta 1200×630 al generador existente (Chrome sin interfaz, sin dependencias) | Bajo |
| `tests/Browser/screens.json`, `baseline-1440.json` y `database/seeders/UiAuditSeeder.php` | Cada tarea de página actualiza sus pantallas y su línea base (selectores del sitio nuevo); los datos de ui-audit producen varias promociones, ≥ 4 testimonios, galería con 0/1/≥ 3 imágenes e inicio vacío | Medio: la línea base de composición cambia |
| `vite.config.js` | Entradas `resources/css/site/site.css` y `resources/js/site/*.js` | Bajo |
| `.env.example` | `SITE_NEW_LANDING=true` | Bajo |

Archivos que **no** se tocan hasta después de liberar (decisión del usuario): `resources/views/pages/landing/{inicio,acerca,galeria,contacto}.blade.php`, `resources/views/components/landing/{nav,footer}.blade.php`, `resources/js/pages/landing/{inicio,galeria}.js`, `tests/Modules/ContentManagement/Unit/PublicGalleryScriptTest.php` y `render-public-gallery.mjs`.

## Contratos y datos

**Sin endpoints ni migraciones.** Rutas web:

| Ruta | Antes | Después |
|---|---|---|
| `GET /` | `ShowLandingPageController` → `pages.landing.inicio` | igual → `site.inicio` (interruptor activo) |
| `GET /acerca-de-nosotros`, `GET /contacto` | closure → vista vieja | closure → `site.nosotros` / `site.contacto` o la vieja |
| `GET /galeria` | closure → vista vieja + `fetch` a la API | `ShowGalleryPageController` → `site.galeria` con imágenes en el HTML |
| `GET /sitemap.xml` | 404 | 200 `application/xml`, seis `<url><loc>` |

**Datos que reciben las vistas** (mapeo desde la propuesta):

| Propuesta | Real | Nota |
|---|---|---|
| promoción `titulo`, `descripcion`, `porcentaje`, `inicio`, `fin` | `name`, `description`, `discount_percentage`, `start_date`, `end_date` | vigentes y ordenadas en el servidor |
| certificación `titulo`, `institucion`, `tipo`, `fecha`, `imagen_url` | `name`, `description`, —, `date`, `image_url` | la descripción ocupa el lugar de la institución (decisión del usuario); se quita el tipo; sin imagen → diploma de la propuesta |
| testimonio `nombre`, `texto`, `calificacion`, `fecha` | `author`, `description`, —, — | sin estrellas ni fecha (pendiente en el roadmap) |
| galería (dibujos `.frame-art`) | `url`, `description` de imágenes `visible` | `<img>` con `alt` = descripción |

**`config/site.php`** (forma):
```php
'new_landing' => (bool) env('SITE_NEW_LANDING', true),
'name' => 'Dentissa', 'url' => 'https://dentissapp.com',
'contact' => ['whatsapp' => '…', 'phone' => null, 'email' => null, 'address' => '…', 'maps_url' => '…', 'instagram' => null, 'facebook' => null],  // null = no confirmado: no se muestra ni va al JSON-LD
'schedule' => [1 => ['09:00', '19:00'], …, 6 => ['09:00', '14:00'], 0 => null],
'treatments' => ['Limpieza y Prevención', 'Ortodoncia', 'Implantes Dentales', 'Blanqueamiento Dental', 'Odontopediatría'],
'pages' => ['/' => ['title' => …, 'description' => …], …],   // seis rutas indexables
```
Producción solo tiene datos de ejemplo (`521234567890`, `+52 55 1234 5678`, `contacto@dentissa.com`,
redes genéricas): solo se cargan los que el usuario confirme; los demás quedan en `null` y la
vista y el JSON-LD los omiten (decisión del usuario, `/analyze 026` A6). El WhatsApp es obligatorio:
sin él, T003 se bloquea. Los tratamientos salen de la sección "Nuestros Servicios" del inicio
actual (decisión del usuario) y sus textos (también los del antes y después) los pinta el servidor
desde config, no el JS. `site.url` es igual a `seo.site_url`.

**Pantallas y estados** (de `design.md` → Pantallas): inicio con y sin contenido (cupón/boletos o
franja vacía; certificaciones ocultas sin registros; invitación "¿Ya nos visitaste?" con 0
testimonios; carrusel con ≥ 2; cinta con ≥ 4), galería con y sin imágenes, versión estática del
recorrido (movimiento reducido o sin WebGL), contacto, legales con barra y pie nuevos. Accesibilidad
medible: puertas de `design/criterios.json` y comprobaciones de `ui-audit` (foco, teclado,
contraste, toque) a 390, 768 y 1440 px.

## Estrategia de pruebas
- **Integración Pest** (`tests/Modules/ContentManagement/Integration/`, base
  `ContentManagementIntegrationTestCase`, parseo del HTML con `DOMXPath` como `LegalPagesTest`):
  - `PublicSiteContentTest`: contenido visible, vigencia, orden, estados vacíos, degradación ante
    fallo, escape de marcado.
  - `PublicGalleryPageTest`: imágenes en el HTML, oculta fuera, vacío, fallo.
  - `PublicSitePagesTest`: las cuatro rutas y las dos legales con layout nuevo, interruptor apagado
    sirve las vistas viejas, datos de contacto = `config/site.php`, ningún marcador `[CORREO`,
    tratamientos solo de la lista.
  - `PublicSiteSeoTest` (`tests/Modules/Core/Integration/`): título y descripción únicos (70–160),
    una canónica, Open Graph, `theme-color` = `--color-primary`, JSON-LD que se parsea, sitemap y
    robots, `og:image` válido.
- **Unidad de JS con Node** (patrón de `PublicGalleryScriptTest`): `SiteScriptsTest` con un
  `render-site-scripts.mjs` que carga los scripts del sitio en un DOM mínimo: compositor de
  WhatsApp (motivo + mensaje, marcado → texto literal codificado), estado abierto/cerrado con hora
  fijada en la zona de la clínica, `home.js` sin filtrar ni reordenar promociones (con el reloj del
  navegador en otra zona, la que vence hoy para la clínica sigue visible).
- **Diseño**: `SiteStylesTokensTest` (`tests/Modules/Core/Unit/Design/`) — ningún `#…`, `rgb(`,
  `hsl(`, `border-radius` en px ni `min-height`/`height` en px de un control (`btn`, `seg`, campos)
  literal en `resources/css/site/**`; `font-family` literal solo dentro de `@font-face`; toda
  `var(--x)` usada tiene su token declarado en `app.css` o se define en la propia hoja; ningún
  `style=` con color en las vistas del sitio; ningún término de tratamiento descartado en
  `resources/js/site/**`; fuentes desde `resources/fonts` con su licencia y ningún host externo. Cada
  regla con control positivo y negativo. `DesignTokensTest` cubre los tokens nuevos (lee también el
  bloque `@theme static`).
- **Estructura**: `PageStructureTest` (016) sigue exigiendo el logo y el favicon de la marca en las
  rutas públicas que adoptan el layout nuevo.
- **Navegador**: `npm run test:ui` — cada tarea de página actualiza sus entradas de `screens.json` y
  su línea base y deja `--only <pantalla>` en verde; `evaluate.mjs` de
  `design-spec` con `design/criterios.json` sobre la build servida en local (Playwright global,
  decisión del usuario) en cada tarea de página.
- **Lighthouse** (decisión del usuario): manual en Chrome, perfil móvil, sobre la build de
  producción en local en T092 y sobre producción en T095; resultados en `review.md` y `docs/seo.md`.

**Fronteras con ejemplo** (reloj fijo `2026-11-12 12:00` en `America/Mexico_City`):
- Vigencia: promoción con `end_date = 2026-11-12` **se muestra** (vence hoy); con `2026-11-11` **no**.
  Con `start_date = 2026-11-13` **no** se muestra (aún no empieza).
- Orden: dos vigentes que terminan el 2026-11-20 y el 2026-11-15 → primero la del 15 (cupón grande).
- Testimonios: 1 → sin carrusel; 2 → carrusel; 3 → sin cinta; 4 → con cinta.
- Certificaciones: 0 → la sección no está en el HTML; 1 → presente.
- Descripción SEO: 69 caracteres falla, 70 pasa; 160 pasa, 161 falla (el test recorre las seis).
- Vigencia con el reloj de la aplicación: el test usa `travelTo` y `UiAuditClock` la respeta (la
  promoción sembrada por `UiAuditSeeder` sigue visible en el estado "con contenido").
- Galería: 0 imágenes → aviso; 1 → un marco sin recorrido (el caso que ya falló, ficha
  2026-10-17); 3 → recorrido horizontal.
- Abierto/Cerrado: lunes 18:59 → "Abierto"; 19:00 → "Cerrado"; sábado 13:59 abierto, 14:00 cerrado;
  domingo cerrado. Con el reloj del navegador en otra zona (UTC), el resultado es el de la clínica.
- Interruptor: `true` → `site.*`; `false` → vistas viejas (un caso por ruta).

**Entradas nuevas**: contenido publicado con `<script>`, `"` o `</script>` en nombre o descripción
→ texto escapado en HTML y en JSON-LD (`JSON_HEX_TAG`); descripción de imagen vacía → `alt=""` y
la imagen sigue; `image_url` vacía en certificación → diploma de la propuesta. Formulario de
contacto vacío → no abre WhatsApp y muestra el error del campo. Sin JavaScript, el
formulario no tiene `action` externo (la CSP `form-action 'self'` lo bloquearía) y la página muestra
un enlace directo a WhatsApp (decisión del usuario, A17).

## Modelo de amenazas
No hay datos sensibles ni autenticación; aplica por las entradas externas (contenido que publica el
staff y texto del visitante en el formulario).

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: contenido oculto o promoción vencida en el HTML, el JSON-LD o el sitemap | A01:2025 | `GetLandingContentUseCase`, `ShowGalleryPageController`, sitemap | Filtro `visible` y vigencia en el servidor; el sitemap solo lista rutas de config. Riesgo restante fuera de alcance: la API pública de contenido (RS4.a) | `PublicSiteContentTest` y `PublicGalleryPageTest`: oculto y vencido ausentes del HTML y del JSON-LD |
| TM2 | Tampering → XSS almacenado por contenido publicado con marcado | A05:2025 (Injection) | vistas `site.*`, JSON-LD | Solo la salida escapada de Blade (nunca la salida sin escapar `{!! !!}`) para contenido; JSON-LD con `json_encode(JSON_HEX_TAG\|JSON_HEX_AMP\|JSON_UNESCAPED_UNICODE)`; CSP actual sin `unsafe-inline` en scripts | `PublicSiteContentTest`: `<script>` en nombre y descripción sale escapado; JSON-LD con `</script>` sigue parseando |
| TM3 | Tampering → XSS en el DOM desde el formulario o datos en atributos | A05:2025 | `contact.js`, `gallery.js`, `home.js` | `textContent` y `encodeURIComponent`; ningún `innerHTML` con datos | `SiteScriptsTest`: mensaje con `<img onerror>` llega codificado a la URL de WhatsApp y no crea nodos; lint en `/review`: `grep innerHTML resources/js/site` |
| TM4 | Spoofing: pestaña abierta por un enlace externo controla la página (tabnabbing) | A05:2025 | enlaces a WhatsApp, Maps, redes | `rel="noopener noreferrer"` en todo `target="_blank"` | `PublicSitePagesTest`: todo `target=_blank` lleva `noopener` |
| TM5 | DoS: páginas públicas consultan la base en cada visita | A06:2025 (Insecure design) | inicio y galería | Caché de 10 min (como hoy) | `PublicGalleryPageTest`: segunda petición sin consulta a la base (contador de consultas) |

Cobertura de abuso: CA14 → TM1 · CA15 → TM2 · CA16 → TM3.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: cuatro páginas con el diseño nuevo en las mismas rutas; legales con barra y pie nuevos | `layouts/site`, `site.*`, `routes/web.php`, legales | integración · `PublicSitePagesTest` · cada ruta 200 con `header`/`footer`/botón de WhatsApp del sitio; legales igual y su `h1` sin cambios; interruptor apagado → vista vieja · `evaluate.mjs` K1 · verificación manual: comparar cada página con la propuesta a 390 y 1440 |
| CA2: promociones vigentes ordenadas, certificaciones y testimonios visibles con campos reales | `PromotionEntity`, `GetLandingContentUseCase`, `site.inicio` | unidad · `GetLandingContentUseCaseTest` · fronteras con `travelTo` · integración · `PublicSiteContentTest` · vigentes en orden de fin; ocultas fuera; descripción de la certificación en el lugar de la institución; sin estrellas ni tipo |
| CA3: estados vacíos de cada sección | `site.inicio` | integración · `PublicSiteContentTest` · sin contenido: franja sin promociones, sin sección de certificaciones, invitación con enlace a WhatsApp · `ui-audit` pantalla `inicio-vacio` |
| CA4: fallo al leer → página con estados vacíos | `ShowLandingPageController` | integración · `PublicSiteContentTest` · el caso de uso lanza → 200, estados vacíos, `Log::error` |
| CA5: galería en el HTML del servidor; vacío | `ShowGalleryPageController`, `site.galeria` | integración · `PublicGalleryPageTest` · `<img>` con `src` y `alt` en la respuesta sin JS; oculta fuera; sin imágenes → estado vacío; fallo → vacío · `evaluate.mjs` G-sin-js |
| CA6: compositor abre WhatsApp con motivo y mensaje; nada se guarda; enlace sin JS | `contact.js`, `site.contacto` | unidad JS · `SiteScriptsTest` · URL `https://wa.me/<num>?text=` con motivo y mensaje · integración · `PublicSitePagesTest` · el formulario no tiene `action` ni `@csrf`; hay un enlace directo a WhatsApp visible sin JS |
| CA7: solo datos confirmados, sin marcadores ni ejemplos | `config/site.php`, vistas | unidad · `SiteConfigTest` · rechaza los valores de ejemplo conocidos · integración · `PublicSitePagesTest` · un dato en `null` no aparece en ninguna página ni en el JSON-LD; ninguna aparición de `[CORREO`, `521234567890`, `+52 55 1234 5678` ni de las redes genéricas |
| CA8: Lighthouse móvil ≥ 90 / 100 / 100 / 100 | carga diferida, fuentes locales con precarga, imágenes con tamaño | `evaluate.mjs` K2a–K2d por página · verificación manual: Lighthouse móvil en Chrome sobre la build de producción local (T092) y sobre producción (T095), registrado en `review.md` y `docs/seo.md` |
| CA9: movimiento reducido o sin WebGL → versión estática | `tooth3d.js`, `inicio.css` | `evaluate.mjs` G-reducido y K4a · `ui-audit` con `prefers-reduced-motion` · unidad JS · `SiteScriptsTest` · sin `WebGLRenderingContext` no crea el lienzo y deja la versión estática |
| CA10: teclado y lector de pantalla; carruseles con pausa | vistas y `home.js`, `gallery.js` | `npm run test:ui` · foco visible, orden, nombres accesibles a 390/768/1440 · `evaluate.mjs` G-foco, G-toque · verificación manual: NVDA en inicio y galería |
| CA11: idioma, título, descripción, canónica, Open Graph y `theme-color` por ruta indexable | `layouts/site`, `components/site/seo`, `config/site.php` | integración · `PublicSiteSeoTest` · seis rutas: `lang="es"`, valores únicos, 70–160 caracteres, una sola meta description (las legales con su `@section` como única fuente), canónica absoluta sin barra, `og:url` = canónica, `og:locale=es_MX`, `theme-color` = `--color-primary` |
| CA12: sitemap con seis rutas; robots lo declara | `ShowSitemapController`, `public/robots.txt` | integración · `PublicSiteSeoTest` · XML válido con seis `loc` y ninguna de acceso o panel; `robots.txt` contiene la línea `Sitemap:` |
| CA13: JSON-LD `Dentist` en inicio y contacto con datos visibles y confirmados | `components/site/seo` | integración · `PublicSiteSeoTest` · JSON-LD parseable, `@type` `Dentist`, nombre, dirección, teléfono y horario iguales a los visibles; un dato en `null` no aparece; ausente en otras rutas |
| CA14 (abuso): oculto o vencido no aparece | TM1 | integración · `PublicSiteContentTest`, `PublicGalleryPageTest` |
| CA15 (abuso): contenido con código se muestra como texto | TM2 | integración · `PublicSiteContentTest` |
| CA16 (abuso): marcado en el formulario → texto literal | TM3 | unidad JS · `SiteScriptsTest` |
| CA17: solo tratamientos de Dentissa | `config/site.php → treatments`, `site.inicio`, `site.galeria`, `gallery.js` | integración · `PublicSitePagesTest` · cada tratamiento nombrado en recorrido, servicios y selector está en la lista; ninguno de la lista de términos descartados · `SiteStylesTokensTest` · ningún término descartado en `resources/js/site/**` |
| CA18: antes y después con fotos por tratamiento, comparable con teclado | `site.galeria`, `gallery.js`, `public/images/site/antes-despues/` | integración · `PublicGalleryPageTest` · un par de `<img>` por tratamiento del selector con `data-origen` · `npm run test:ui` · el control deslizante responde a flechas · `evaluate.mjs` K7 |
| CA19: imagen para compartir 1200×630 | `make-brand-assets.mjs`, `public/images/brand/compartir.png`, `components/site/seo` | integración · `PublicSiteSeoTest` · `og:image` absoluto en las seis rutas, archivo existe, 1200×630, ≤ 300 kB, `og:image:alt` · verificación manual: tarjeta contra `system.html#compartir` (última tarea) y vista previa real en WhatsApp en T095 |
| TM1 | filtro en el servidor | integración · `PublicSiteContentTest`, `PublicGalleryPageTest` · oculto y vencido ausentes |
| TM2 | escape de Blade y JSON-LD | integración · `PublicSiteContentTest` · marcado escapado |
| TM3 | `textContent`, `encodeURIComponent` | unidad JS · `SiteScriptsTest` · mensaje con marcado codificado |
| TM4 | `rel="noopener noreferrer"` | integración · `PublicSitePagesTest` · todo `target=_blank` de las seis rutas lleva `noopener noreferrer` |
| TM5 | caché de 10 min | integración · `PublicGalleryPageTest` · segunda petición sin consulta |
| RNF: WCAG 2.1 AA | vistas, CSS | `npm run test:ui` y puertas de `evaluate.mjs` (contraste, toque, foco) |
| RNF: sin servicios de terceros para mostrarse | layout, CSS | `SiteStylesTokensTest` · ningún host externo en `<link>`/`<script>`/`@import`/`@font-face` · `evaluate.mjs` G-red (salvo origen de imágenes de R2 en galería) |
| RNF: contenido publicado visible en ≤ 10 min | caché de 10 min en inicio y galería | integración · `PublicGalleryPageTest` · TTL de la caché = 10 min |
| RNF: descubribilidad | ver Descubribilidad | `PublicSiteSeoTest` |

## Observabilidad
No toca datos sensibles, autenticación ni permisos: sin eventos de auditoría.
- Logs nuevos: `public_gallery.load_failed` → `error` → `exception` (clase), con `request_id` del
  contexto; igual que el existente `Unable to load landing page content`. Sin datos de visitantes.
- Eventos de auditoría: ninguno.
- Métricas y alertas: ninguna nueva; Grafana/Loki ya recogen los `error`.
- Cómo se verifica tras el deploy: `GET` a las seis rutas y a `/sitemap.xml` con `verify.sh`
  (200) y búsqueda en Loki de `public_gallery.load_failed` y `Unable to load landing` en la hora
  siguiente (debe estar vacía).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/` | sí | `config/site.php → pages./.title` | propia, 70–160 | `https://dentissapp.com` | sí | `Dentist` | `PublicSiteSeoTest` |
| `/acerca-de-nosotros` | sí | propio | propia | `https://dentissapp.com/acerca-de-nosotros` | sí | — | `PublicSiteSeoTest` |
| `/galeria` | sí | propio | propia | `…/galeria` | sí | — | `PublicSiteSeoTest`, `PublicGalleryPageTest` |
| `/contacto` | sí | propio | propia | `…/contacto` | sí | `Dentist` | `PublicSiteSeoTest` |
| `/aviso-de-privacidad`, `/terminos-y-condiciones` | sí | los actuales ("… - Dentissa") | las actuales | `…/aviso-de-privacidad`, `…/terminos-y-condiciones` | sí | — | `PublicSiteSeoTest`, `LegalPagesTest` |
| `/sitemap.xml` | no (archivo) | — | — | — | — | — | `PublicSiteSeoTest` |

- Valores comunes: `og:site_name` = "Dentissa"; `og:locale` = `es_MX`; `og:type` = `website`;
  `theme-color` = `#d75078` (`--color-primary`, el de la propuesta; decisión del usuario, A8) solo en
  el layout del sitio; `lang="es"` en el layout; JSON-LD con
  `name`, `address` (`PostalAddress`), `telephone`, `openingHoursSpecification` y `url` desde
  `config/site.php`. Sin `twitter:*` (exclusión: las redes leen Open Graph).
- Renderizado de las rutas nuevas: igual que `seo.rendering` (`ssr`); la galería deja de depender de JS (SE5.a).
- Imagen para compartir: **general** (`public/images/brand/compartir.png`, 1200×630) para las seis
  rutas, estática, generada con `make-brand-assets.mjs` (Chrome sin interfaz, sin dependencias). Datos
  que obligan a regenerarla: nombre y frase principal; `PublicSiteSeoTest` compara esa frase con
  `config/site.php`.
- Brechas `SE`: SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b y SE7.a dentro; SE8.a **parcial**
  (dentro el sitio público; panel y portal → roadmap); SE2.b, SE2.c y SE4.a fuera → roadmap objetivo
  7 y spec de pantallas de acceso. SE8 no se da por cerrada.

## Rollout
- Feature flag: `site.new_landing` (`SITE_NEW_LANDING`, por defecto `true`). Con `false`, las cuatro
  rutas y las legales vuelven a las vistas viejas; SEO, sitemap y robots siguen activos (son
  compatibles con ambas).
- Orden de despliegue: un solo deploy (`docker/prod/deploy.sh vX.Y.Z`); sin migraciones. Tras el
  deploy, `php artisan cache:forget landing.content.visible` no hace falta (misma forma de datos),
  pero la caché de la galería es nueva.
- Compatibilidad: no hay estado compartido nuevo; la API pública de galería queda igual.
- Rollback: (1) inmediato: `SITE_NEW_LANDING=false` en el `.env` de producción y
  `compose.sh exec app php artisan config:cache`; (2) completo: `docker/prod/rollback.sh` a la
  versión anterior (`docs/deployment.md` → Rollback). Sin datos que revertir.
- Métricas a vigilar: errores 5xx en las seis rutas (Loki), `public_gallery.load_failed`, y
  Lighthouse sobre producción (T095).
- **Después de liberar**, con confirmación del usuario: un `/chore` borra las vistas, componentes y
  JS viejos, `PublicGalleryScriptTest` y el interruptor.

## Decisiones (→ ADR si son arquitectónicas)
- **CSS de la propuesta como CSS plano con `var()`** (frente a reescribirlo con utilidades de
  Tailwind): conserva el diseño aprobado tal cual y P15 se cumple porque todo valor sale de `@theme`.
  `SiteStylesTokensTest` extiende la verificación a `resources/css/site/`.
- **Tokens del sitio en `@theme static`** en `app.css`, para que Tailwind 4.1 emita sus variables
  aunque ninguna utilidad las use. Prueba de concepto en T001 (`npm run build` y buscar
  `--color-primary-deep` en el CSS generado); si no, se declaran en un bloque `:root` dentro de
  `app.css` documentado en `system.md`.
- **La página carga `app.css` + `site.css`**: `app.css` (~11 kB comprimido) aporta las variables y
  las utilidades de las páginas legales y de `x-ui.*`; separar los tokens a otro archivo obligaría a
  cambiar `DesignTokensTest` sin beneficio medible.
- **Fuentes autoalojadas** (`woff2`, subconjunto latino, `font-display: swap`, precarga de los dos
  pesos de la primera pantalla). Sin paquete npm (P10); `OFL.txt` junto a los archivos.
- **Datos de la clínica y tratamientos en `config/site.php`** (frente a leer el catálogo en vivo):
  el recorrido 3D tiene sus estados dibujados para cuatro tratamientos y no podría seguir al
  catálogo; evita una dependencia nueva entre módulos (P3). Lista: la de "Nuestros Servicios" del
  inicio en producción (decisión del usuario, 2026-11-12).
- **Vigencia de promociones en el caso de uso** con `PromotionEntity` y zona
  `America/Mexico_City` (P13), en lugar del filtro en la vista que traía la propuesta.
- **Galería en el servidor** con `ShowGalleryPageController` + `GetGalleryImagesUseCase` existente,
  caché de 10 min como el inicio.
- **Sitemap dinámico** (controlador) en lugar de archivo estático, para que salga de la misma lista
  que los metadatos.
- **Lighthouse manual** + medidas automáticas (decisión del usuario, 2026-11-12).
- **Correcciones de `/analyze 026` ronda 1** (decisiones del usuario, 2026-11-12): radios, altos y
  componentes del sitio al sistema 2.1.0 (A1); K1 solo en inicio y nosotros, con criterios por
  página (A5); solo datos de contacto confirmados (A6); `theme-color` rosa solo en el sitio (A8);
  logo y favicon reales (A10); formulario de motivo y mensaje (A14); descripción de la certificación
  como institución (A15); sin `action` externo y enlace de respaldo (A17).
- **El menú plegable y la barra viven en `components.css`** (común), no en las hojas de página, para
  que funcionen en las páginas legales (A13).
- **Archivos viejos** se conservan hasta después de liberar y se borran con un `/chore` solo cuando
  el usuario lo confirme (decisión del usuario, 2026-11-12); de ahí el interruptor.
- **Playwright global** en el equipo para `evaluate.mjs` (decisión del usuario, 2026-11-12); no
  entra en `package.json` ni en CI.
- **Imagen para compartir estática** generada por `make-brand-assets.mjs`, última tarea (decisión
  del usuario en la spec).
- Sin ADR: ninguna dependencia nueva ni cambio de arquitectura.
- Línea base de dependencias (2026-11-12): `npm audit` 0 vulnerabilidades; `composer audit --locked`
  0 avisos (el `vendor/` del equipo está desactualizado; se audita el lock).

## Impacto en arquitectura
- `docs/architecture.md` → **Frontend**: el sitio público tiene su propio layout, CSS plano con
  tokens y JS en `resources/js/site/`; la galería ya no usa la API.
- → **Backend**: `ShowGalleryPageController` y `ShowSitemapController`; `config/site.php`.
- `docs/design/system.md` 2.1.0 (T001) y `docs/seo.md` (inventario y estado de SE1–SE8, al liberar).

## Riesgos y mitigaciones
- **Rendimiento móvil ≥ 90 con WebGL y ~55 animaciones**: diente cargado tras el evento `load` y
  solo en el inicio, imagen estática como LCP, fuentes precargadas, imágenes con `width`/`height`,
  `loading="lazy"` y `fetchpriority`. Si no llega a 90, se registra y se ajusta con `refinado` sin
  cambiar la composición.
- **Contraste sobre degradados y `--color-muted-soft`**: `ui-audit` mide el tono más desfavorable;
  la corrección va en el uso, no en el token.
- **Colores escritos a mano en la propuesta**: 381 apariciones (302 en CSS, 75 en vistas, 4 en JS),
  119 escrituras distintas que en su mayoría son tokens existentes o sus transparencias; solo ~9
  tonos nuevos. Reemplazo mecánico sin cambio visual: T001 crea los tokens y
  `SiteStylesTokensTest` impide que quede alguno.
- **Imágenes de muestra y generadas en producción**: `max_samples` y `data-origen` las listan;
  `/review` y `/release` las bloquean salvo decisión del usuario.
- **Datos de contacto provisionales en producción** (p. ej. `521234567890`): la tarea que crea
  `config/site.php` se bloquea y pregunta si el dato no es real.
- **Spec grande (19 criterios)**: tareas por página, cada una verde por sí misma.
- **Dos versiones del sitio conviviendo** hasta el `/chore`: `OnlyDesignTokensTest` sigue cubriendo
  las vistas viejas; el interruptor tiene su test.
