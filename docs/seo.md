---
status: inferred
updated: 2026-10-15
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
  (`resources/views/layouts/landing.blade.php`); la galería pública carga sus imágenes después con
  JavaScript (SE5).
- No se indexa: la 404, las pantallas de acceso y las rutas del panel — mecanismo: `noindex` en meta
  (hoy solo en las páginas de error: SE4).
- Páginas de error: 404 propia `resources/views/errors/404.blade.php` (spec 016, con `noindex`);
  rutas privadas sin sesión → redirección a `/login` (`302`).
- Archivos generados: `robots.txt` estático en `public/`; no hay `sitemap.xml` (SE3).
- Compartir: sin Open Graph ni imagen para compartir (SE6).
- Íconos: `favicon.ico`, `images/brand/icon-192.png` y `apple-touch-icon` (180) de la marca de la
  clínica (spec 016); sin `theme-color` (SE8).
- Datos estructurados: ninguno (SE7).
- Presupuesto: la constitución no fija presupuesto de rendimiento; Lighthouse SEO no medido.

## Inventario de rutas
| Ruta | Indexable | Título | Descripción | Canónica | `h1` | Open Graph | Datos estructurados | Evidencia |
|---|---|---|---|---|---|---|---|---|
| `/` | sí | ✅ "Dentissa - Clínica Dental Premium" | ❌ | ❌ | ✅ 1 | ❌ | ❌ | `GET /` 200, `lang="es"` |
| `/acerca-de-nosotros` | sí | ✅ "Acerca de Nosotros - Dentissa" | ❌ | ❌ | ✅ 1 | ❌ | ❌ | `GET` 200 |
| `/galeria` | sí | ✅ "Galería de Sonrisas - Dentissa" | ❌ | ❌ | ✅ 1 | ❌ | ❌ | `GET` 200; el HTML dice "Cargando galería..." y las imágenes llegan por `GET /api/v1/public/gallery-images` (`resources/js/pages/landing/galeria.js:21`) |
| `/contacto` | sí | ✅ "Contacto y Ubicación - Dentissa" | ❌ | ❌ | ✅ 1 | ❌ | ❌ | `GET` 200 |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | no (pantallas de acceso) | ➖ | ➖ | ➖ | ✅ 1 | ➖ | ➖ | `GET /login` y `/register` 200 **sin** `noindex` (SE4) |
| Panel (`/dashboard`, `/agenda`, `/pacientes`…) | no (exigen sesión) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `GET /agenda` sin sesión → `302` a `/login` |
| URL inexistente | no (404) | ✅ "No encontrada - Dentissa" | ➖ | ➖ | ✅ 1 | ➖ | ➖ | `GET /pagina-que-no-existe` → `404`, `noindex`, página propia |

## Brechas
### SE1 · media — Las rutas indexables no tienen descripción
Evidencia: ninguna de `/`, `/acerca-de-nosotros`, `/galeria` y `/contacto` lleva
`<meta name="description">` (`GET` a producción, 2026-10-15); el layout
`resources/views/layouts/landing.blade.php` no la declara.
Correcciones:
- SE1.a Una descripción propia y única por ruta indexable, de 70–160 caracteres, que describa esa página — estado: pendiente

### SE2 · media — Sin canónica y con variantes de URL que no redirigen
Evidencia: ninguna ruta indexable lleva `<link rel="canonical">`; `https://www.dentissapp.com/`
responde `200` sin redirigir a `https://dentissapp.com/`, y `/galeria/` (con barra final) responde
`200` como `/galeria` (`GET`, 2026-10-15).
Correcciones:
- SE2.a Canónica absoluta, construida desde `seo.site_url`, en cada ruta indexable — estado: pendiente
- SE2.b `www.dentissapp.com` redirige con `301` a `https://dentissapp.com` (misma ruta) — estado: pendiente
- SE2.c La barra final redirige con `301` a la URL sin barra — estado: pendiente

### SE3 · media — No hay sitemap
Evidencia: `GET /sitemap.xml` → `404`; `robots.txt` (`public/robots.txt`) solo dice
`User-agent: *` y `Disallow:`, sin línea `Sitemap:`.
Correcciones:
- SE3.a `sitemap.xml` con las cuatro rutas indexables y su URL canónica absoluta, sin rutas de acceso ni del panel — estado: pendiente
- SE3.b `robots.txt` declara `Sitemap: https://dentissapp.com/sitemap.xml` — estado: pendiente

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
- SE5.a Las imágenes de la galería y sus descripciones llegan en el HTML que renderiza el servidor, como ya hace el inicio con promociones, certificaciones y testimonios — estado: pendiente

### SE6 · baja — Sin Open Graph ni imagen para compartir
Evidencia: ninguna ruta lleva `og:*` (`GET`, 2026-10-15). El sistema de diseño
(`docs/design/system.md`, 2.0.0) no define la "Tarjeta para compartir".
Correcciones:
- SE6.a `og:title`, `og:description`, `og:type`, `og:url` (igual a la canónica) y `og:locale` en cada ruta indexable — estado: pendiente
- SE6.b Imagen general para compartir de 1200×630 desde la tarjeta del sistema de diseño, con `og:image:alt` — estado: pendiente

### SE7 · baja — Sin datos estructurados
Evidencia: ninguna ruta lleva JSON-LD (`GET`, 2026-10-15).
Correcciones:
- SE7.a `Dentist` (o `LocalBusiness`) en el inicio y en contacto, solo con datos visibles en la página (nombre, dirección, teléfono, horario) — estado: pendiente

### SE8 · baja — Sin `theme-color`
Evidencia: ninguna ruta lleva `<meta name="theme-color">` (tema 12).
Correcciones:
- SE8.a `theme-color` con el color de fondo del sistema de diseño en todos los layouts — estado: pendiente

## Brechas por confirmar
- Lighthouse SEO de las rutas indexables — depende de: medirlo sobre producción — cómo confirmarla: Lighthouse en el navegador sobre las cuatro rutas.
- Vista previa al compartir y resultados enriquecidos — depende de: SE6 y SE7 — cómo confirmarla: herramientas de inspección de las redes y de resultados enriquecidos, tras corregirlas.

## Verificación en producción
| Fecha | Comprobación | Resultado | Evidencia |
|---|---|---|---|
| 2026-10-15 | `robots.txt` responde 200 sin `Disallow: /` | ✅ | `GET /robots.txt` |
| 2026-10-15 | `sitemap.xml` responde 200 | ❌ | `GET /sitemap.xml` → 404 (SE3) |
| 2026-10-15 | Producción sin `noindex` en rutas indexables | ✅ | `GET` a las cuatro rutas |
| 2026-10-15 | URL inexistente → 404 propia con `noindex` | ✅ | `GET /pagina-que-no-existe` |
| 2026-10-15 | Ruta privada sin sesión → redirección a `/login` | ✅ | `GET /agenda` → 302 |

## Exclusiones deliberadas
- Ninguna por ahora.

## Pendientes operativos
- Dar de alta el sitio en Google Search Console (y Bing Webmaster Tools) y enviar el sitemap cuando exista (SE3).
- Analítica: no hay; decidir si se quiere.

## Historial
| Fecha | Cambio | Origen |
|---|---|---|
| 2026-10-15 | Documento creado con la auditoría sobre producción (v0.2.0) | `/init --upgrade` → `/seo audit` |
