---
status: inferred
updated: 2026-11-16
---

# SEO técnico de Dentissa

Fuente de verdad de lo que un buscador puede rastrear, indexar e interpretar del sitio público y de
cómo se ve al compartirlo (`shared/contract.md` → "SEO técnico"). Los temas que se comprueban están
en `shared/seo-checklist.md`; aquí van las reglas del sitio, el estado de cada ruta y las brechas.
No incluye palabras clave, estrategia de contenido ni enlaces externos.

Auditado el 2026-10-15 sobre producción (v0.2.0) con peticiones `GET` a `robots.txt`,
`sitemap.xml`, las rutas públicas y de acceso, una URL inexistente y una ruta privada sin sesión
(permiso del usuario, 2026-10-15). Exploración del código hecha sin subagente: el sitio público son
cuatro vistas Blade sobre un solo layout.

## Reglas del sitio
- URL canónica: https://dentissapp.com · variante única: sin `www`, sin barra final
  (confirmado por el usuario, 2026-10-15); las demás deben redirigir con `301`/`308` (hoy no lo
  hacen: SE2).
- Idiomas: es.
- Renderizado: ssr — Laravel renderiza cada ruta en el servidor con Blade
  (`resources/views/layouts/site.blade.php` desde la spec 026; `layouts/landing.blade.php` con
  `SITE_NEW_LANDING=false`); la galería nueva trae sus imágenes en el HTML (SE5.a).
- No se indexa: la 404, las pantallas de acceso y las rutas del panel — mecanismo: `noindex` en meta
  (hoy en las páginas de error y en las dos pantallas del portal del paciente, spec 025: SE4).
- Páginas de error: 404 propia `resources/views/errors/404.blade.php` (spec 016, con `noindex`);
  rutas privadas sin sesión → redirección a `/login` (`302`).
- Cabecera de las rutas indexables (spec 026, `resources/views/components/site/seo.blade.php`):
  título y descripción propios (las cuatro páginas desde `config/site.php → pages`, las legales
  desde su vista), canónica absoluta sobre `config('site.url')` sin barra final ni consulta.
- Archivos generados: `robots.txt` estático en `public/` con `Sitemap: https://dentissapp.com/sitemap.xml`;
  `/sitemap.xml` lo sirve `App\Core\Http\ShowSitemapController` con las seis rutas de
  `config/site.php → indexable` (SE3).
- Compartir: Open Graph en las seis rutas (`og:title`, `og:description`, `og:type` = `website`,
  `og:url` = canónica, `og:locale` = `es_MX`, `og:site_name`) e imagen general
  `images/brand/compartir.png` (1200×630, con `og:image:alt`), estática, generada con
  `npm run brand:assets`; se regenera si cambian el nombre, la frase (`config/site.php → tagline`) o
  la ciudad. Sin `twitter:*`: las redes leen Open Graph.
- Íconos: `favicon.ico`, `images/brand/icon-192.png` y `apple-touch-icon` (180) de la marca de la
  clínica (spec 016); `theme-color` `#d75078` (`--color-primary`, `config/site.php → theme_color`)
  solo en el sitio público (SE8.a parcial).
- Datos estructurados: `Dentist` (JSON-LD) en `/` y `/contacto` con nombre y URL y, cuando la clínica
  da sus datos reales, dirección, teléfono y horario; un dato provisional nunca se publica (SE7).
- Presupuesto: la constitución no fija presupuesto de rendimiento; Lighthouse SEO no medido.

## Inventario de rutas
| Ruta | Indexable | Título | Descripción | Canónica | `h1` | Open Graph | Datos estructurados | Evidencia |
|---|---|---|---|---|---|---|---|---|
| `/` | sí | ✅ "Dentissa - Clínica dental en Lázaro Cárdenas" | ✅ propia | ✅ `https://dentissapp.com` | ✅ 1 | ✅ | ✅ `Dentist` | `PublicSiteSeoTest` (v0.11.0) |
| `/acerca-de-nosotros` | sí | ✅ "Acerca de nosotros - Dentissa" | ✅ propia | ✅ | ✅ 1 | ✅ | ➖ | `PublicSiteSeoTest` (v0.11.0) |
| `/galeria` | sí | ✅ "Galería - Dentissa" | ✅ propia | ✅ | ✅ 1 | ✅ | ➖ | `PublicSiteSeoTest`, `PublicGalleryPageTest` (spec 026, sin liberar; imágenes en el HTML) |
| `/contacto` | sí | ✅ "Contacto y ubicación - Dentissa" | ✅ propia | ✅ | ✅ 1 | ✅ | ✅ `Dentist` | `PublicSiteSeoTest` (v0.11.0) |
| `/aviso-de-privacidad` | sí | ✅ "Aviso de Privacidad - Dentissa" | ✅ propia | ✅ | ✅ 1 | ✅ | ➖ | `LegalPagesTest`, `PublicSiteSeoTest` (sin auditar en producción) |
| `/terminos-y-condiciones` | sí | ✅ "Términos y Condiciones - Dentissa" | ✅ propia | ✅ | ✅ 1 | ✅ | ➖ | `LegalPagesTest`, `PublicSiteSeoTest` (sin auditar en producción) |
| `/sitemap.xml` | no (archivo) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `PublicSiteSeoTest`: 200 `application/xml` con las seis rutas |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | no (pantallas de acceso) | ➖ | ➖ | ➖ | ✅ 1 | ➖ | ➖ | `GET /login` y `/register` 200 **sin** `noindex` (SE4) |
| Panel (`/dashboard`, `/agenda`, `/pacientes`…) | no (exigen sesión) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `GET /agenda` sin sesión → `302` a `/login` |
| Portal del paciente (`/dashboard` del paciente, `/mis-citas`) | no (exigen sesión de paciente) | ✅ "Inicio - Dentissa", "Mis citas - Dentissa" | ➖ | ➖ | ✅ 1 | ➖ | ➖ | sin sesión → `302` a `/login` (`GuestRedirectTest`); con sesión, `noindex` en meta (spec 025, `PortalPagesTest`) |
| URL inexistente | no (404) | ✅ "No encontrada - Dentissa" | ➖ | ➖ | ✅ 1 | ➖ | ➖ | `GET /pagina-que-no-existe` → `404`, `noindex`, página propia |

## Brechas
### SE1 · media — Las rutas indexables no tienen descripción
Evidencia: ninguna de `/`, `/acerca-de-nosotros`, `/galeria` y `/contacto` lleva
`<meta name="description">` (`GET` a producción, 2026-10-15); el layout
`resources/views/layouts/landing.blade.php` no la declara.
Correcciones:
- SE1.a Una descripción propia y única por ruta indexable, de 70–160 caracteres, que describa esa página — estado: mitigada (v0.11.0)

### SE2 · media — Sin canónica y con variantes de URL que no redirigen
Evidencia: ninguna ruta indexable lleva `<link rel="canonical">`; `https://www.dentissapp.com/`
responde `200` sin redirigir a `https://dentissapp.com/`, y `/galeria/` (con barra final) responde
`200` como `/galeria` (`GET`, 2026-10-15).
Correcciones:
- SE2.a Canónica absoluta, construida desde `seo.site_url`, en cada ruta indexable — estado: mitigada (v0.11.0)
- SE2.b `www.dentissapp.com` redirige con `301` a `https://dentissapp.com` (misma ruta) — estado: pendiente
- SE2.c La barra final redirige con `301` a la URL sin barra — estado: pendiente

### SE3 · media — No hay sitemap
Evidencia: `GET /sitemap.xml` → `404`; `robots.txt` (`public/robots.txt`) solo dice
`User-agent: *` y `Disallow:`, sin línea `Sitemap:`.
Correcciones:
- SE3.a `sitemap.xml` con las seis rutas indexables (las cuatro páginas y las dos legales) y su URL canónica absoluta, sin rutas de acceso ni del panel — estado: mitigada (v0.11.0)
- SE3.b `robots.txt` declara `Sitemap: https://dentissapp.com/sitemap.xml` — estado: mitigada (v0.11.0)

### SE4 · media — Las pantallas de acceso son indexables
Evidencia: `GET /login` y `GET /register` responden `200` sin `<meta name="robots">` ni cabecera
`X-Robots-Tag`; el layout de acceso (`resources/views/layouts/app.blade.php`) no lo declara.
Correcciones:
- SE4.a `noindex` en iniciar sesión, registrarse, recuperar y restablecer contraseña — estado: pendiente

### SE5 · media — La galería pública no tiene su contenido en el HTML
Evidencia: el HTML de `/galeria` solo trae "Cargando galería..." y el visor vacío; las imágenes las
pinta `resources/js/pages/landing/galeria.js` tras pedir `/api/v1/public/gallery-images`. Con
`ssr`, el HTML de una ruta indexable debe traer su contenido principal (tema 5).
Correcciones:
- SE5.a Las imágenes de la galería y sus descripciones llegan en el HTML que renderiza el servidor, como ya hace el inicio con promociones, certificaciones y testimonios — estado: mitigada (v0.11.0)

### SE6 · baja — Sin Open Graph ni imagen para compartir
Evidencia: ninguna ruta lleva `og:*` (`GET`, 2026-10-15). El sistema de diseño
(`docs/design/system.md`, 2.0.0) no define la "Tarjeta para compartir".
Correcciones:
- SE6.a `og:title`, `og:description`, `og:type`, `og:url` (igual a la canónica) y `og:locale` en cada ruta indexable — estado: mitigada (v0.11.0)
- SE6.b Imagen general para compartir de 1200×630 desde la tarjeta del sistema de diseño, con `og:image:alt` — estado: mitigada (v0.11.0)

### SE7 · baja — Sin datos estructurados
Evidencia: ninguna ruta lleva JSON-LD (`GET`, 2026-10-15).
Correcciones:
- SE7.a `Dentist` (o `LocalBusiness`) en el inicio y en contacto, solo con datos visibles en la página (nombre, dirección, teléfono, horario) — estado: mitigada (v0.11.0)

### SE8 · baja — Sin `theme-color`
Evidencia: ninguna ruta lleva `<meta name="theme-color">` (tema 12).
Correcciones:
- SE8.a `theme-color` con el rosa de la marca (`--color-primary`, `#d75078`; decisión del usuario, A8 de `/analyze 026`) en todos los layouts — estado: parcial: sitio público mitigado (v0.11.0), panel y portal pendientes

## Brechas por confirmar
- Lighthouse SEO de las rutas indexables — depende de: medirlo sobre producción — cómo confirmarla: Lighthouse en el navegador sobre las cuatro rutas.
- Vista previa al compartir y resultados enriquecidos — depende de: liberar la spec 026 — cómo confirmarla: herramientas de inspección de las redes y de resultados enriquecidos sobre producción (T095).

## Verificación en producción
| Fecha | Comprobación | Resultado | Evidencia |
|---|---|---|---|
| 2026-10-15 | `robots.txt` responde 200 sin `Disallow: /` | ✅ | `GET /robots.txt` |
| 2026-11-17 | Rutas indexables, `sitemap.xml`, `robots.txt`, canónica, Open Graph, `theme-color` y JSON-LD en producción (v0.11.0) | ✅ | `GET` a las seis rutas y a `/sitemap.xml`; Lighthouse SEO 100 en las cuatro páginas (usuario); vista previa en WhatsApp con la tarjeta |
| 2026-10-15 | `sitemap.xml` responde 200 | ❌ | `GET /sitemap.xml` → 404 (SE3; resuelto en v0.11.0) |
| 2026-10-15 | Producción sin `noindex` en rutas indexables | ✅ | `GET` a las cuatro rutas |
| 2026-10-15 | URL inexistente → 404 propia con `noindex` | ✅ | `GET /pagina-que-no-existe` |
| 2026-10-15 | Ruta privada sin sesión → redirección a `/login` | ✅ | `GET /agenda` → 302 |

## Exclusiones deliberadas
- Sin `twitter:*`: las redes, WhatsApp incluido, leen Open Graph (spec 026).
- Una sola imagen para compartir, general, para las seis rutas: ninguna tiene contenido propio que la pida (spec 026).

## Pendientes operativos
- Dar de alta el sitio en Google Search Console (y Bing Webmaster Tools) y enviar `https://dentissapp.com/sitemap.xml`, que ya existe (v0.11.0).
- Analítica: no hay; decidir si se quiere.

## Historial
| Fecha | Cambio | Origen |
|---|---|---|
| 2026-10-15 | Documento creado con la auditoría sobre producción (v0.2.0) | `/init --upgrade` → `/seo audit` |
| 2026-11-16 | Reglas, inventario y estado de SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b y SE7.a (en curso) y SE8.a (parcial) con la spec 026; SE2.b, SE2.c y SE4.a siguen pendientes | spec 026, T028 |
| 2026-11-17 | SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b y SE7.a mitigadas y SE8.a parcial con v0.11.0; verificación en producción | `/release` 026 |