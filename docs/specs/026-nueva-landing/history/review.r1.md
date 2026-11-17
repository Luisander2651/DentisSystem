---
spec: 026-nueva-landing
verdict: changes_requested
round: 1
date: 2026-11-16
base: 7fae5a3
head: 6cb178e
human_signoff: pending
---

# Review · 026 Nueva landing del sitio público

## Resumen
Se piden cambios por un bloqueante de accesibilidad: R1, la cinta de servicios del inicio se mueve sola sin forma de pausarla, contra CA10 y la ficha M-3. Además hay cinco importantes que el usuario decide corregir (R2–R6) y un cambio de contenido que pide (R7). El resto de la spec cumple:
- los 19 criterios tienen tests que pasan;
- seguridad sin hallazgos;
- SEO con las seis filas de Descubribilidad aplicadas;
- Lighthouse por encima del umbral en las cuatro rutas.

Revisaron cinco subagentes de contexto limpio: spec, constitución y calidad, seguridad, accesibilidad y SEO. Como el diff de código pesa 571 KB, cada revisor partió de los archivos de su encargo. No se usaron `design:accessibility-review` ni `design:design-critique`, porque `design` no está en `skills.enabled`.

Conteo: bloqueante 1 · importante 7 · menor 20

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2491 tests, 0 fallos (sin línea base de fallos) |
| `pint --test` sobre los PHP del rango | ✅ (sobre todo el repo falla por archivos anteriores con CRLF: línea base ajena a la spec) |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores |
| `npm run test:ui` en las pantallas públicas | ✅, salvo `inicio-testimonios` a 1440 px, intermitente desde T024 (R8) |
| `evaluate.mjs` de las cuatro páginas | Acerca y contacto pasan completas. Galería e inicio solo fallan en las excepciones aceptadas: K2d sin las imágenes del staff, G-aparece de la bienvenida (M-6) y el falso positivo de G-solapamiento del "12" de la promoción. Informes en `.ai/inspect/026-*-t092/` |
| Lighthouse móvil (usuario, incógnito, build local con gzip) | Rendimiento 94 · 99 · 95 · 99 (inicio, acerca, galería, contacto). Accesibilidad, buenas prácticas y SEO 100 en las cuatro |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PublicSitePagesTest`, `ui-audit` | ✅ | |
| CA2 | `PublicSiteContentTest`, `GetLandingContentUseCaseTest` | ✅ | menor R22 (certificación sin descripción) |
| CA3 | `PublicSiteContentTest` | ✅ | |
| CA4 | `PublicSiteContentTest` | ✅ | |
| CA5 | `PublicGalleryPageTest` | ✅ | menor R11 (`data-src` desde la 4.ª foto, con `<noscript>`) |
| CA6 | `SiteScriptsTest` (compositor), `PublicSitePagesTest` | ✅ | menor R25 (número sin normalizar) |
| CA7 | `SiteConfigTest` | ⚠ | R2: un horario mal escrito se toma como real |
| CA8 | Lighthouse del usuario | ✅ | se repite sobre producción en T095 |
| CA9 | `SiteScriptsTest`, `evaluate.mjs` | ✅ | |
| CA10 | `ui-audit`, `SiteScriptsTest` | ❌ | R1 bloqueante; R3–R6 importantes |
| CA11 | `PublicSiteSeoTest` | ✅ | menor R20 |
| CA12 | `PublicSiteSeoTest` | ✅ | |
| CA13 | `PublicSiteSeoTest` | ⚠ | R2 (horario vacío publicado); menor R19 |
| CA14 | `PublicSiteContentTest`, `PublicGalleryPageTest` | ✅ | |
| CA15 | `PublicSiteContentTest`, `PublicSiteSeoTest` | ✅ | menor R21 (falta el caso de la certificación) |
| CA16 | `SiteScriptsTest` (compositor) | ✅ | |
| CA17 | `PublicSitePagesTest` (términos descartados) | ⚠ | R7: especialidades de acerca fuera de los cinco tratamientos |
| CA18 | `PublicGalleryPageTest`, `SiteScriptsTest` | ✅ | |
| CA19 | `PublicSiteSeoTest` | ✅ | tarjeta revisada por el usuario |

## Conformidad con el plan
Archivos cambiados sin una tarea que los cite. Todos están justificados:
- `PublicSiteSchedule.php`: nota de T009.
- `EloquentGalleryImageRepository.php`: nota de T019, fallo previo corregido con aprobación del usuario.
- `checks-global.mjs`: nota de T014, excepción acotada decidida por el usuario.
- `phpunit.xml` y `TestMemoryLimitTest.php`: ficha `docs/changes/2026-11-16-fix-memoria-suite-paralela.md`.
- `docker/nginx/default.conf`: gzip solo en local para medir Lighthouse, aprobado por el usuario el 2026-11-16 (nota A55 de T092).

Partes del plan sin implementar: ninguna. T028 no aparece en el diff porque solo toca documentos.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | spec, plan y tareas aprobados; `analysis.md` ronda 6 `pass` |
| P2 Test que falla antes | ✅ | notas "comprobado rompiendo" en las tareas |
| P3 Capas del módulo | ✅ | controladores `final readonly` invocables y casos de uso existentes |
| P4 Contrato de API | ✅ | rutas web, sin `/api/v1` nuevo (menor R16: el filtro `status` del repositorio de galería no tiene test de API) |
| P5 Autorización | ➖ | solo rutas públicas de lectura |
| P6 Validación con FormRequest | ➖ | sin entradas nuevas al servidor |
| P7 Errores sin detalles internos | ✅ | galería vacía y log con solo la clase de la excepción |
| P8 Secretos | ✅ | gitleaks sin hallazgos; `env()` solo en `config/site.php` |
| P9 Migraciones | ➖ | sin migraciones |
| P10 Dependencias con ADR | ✅ | sin cambios en lockfiles; WebGL sin librerías |
| P11 Datos sensibles y amenazas | ✅ | TM1–TM5 con control y test |
| P12 Producción | ✅ | rollback con `SITE_NEW_LANDING` y `rollback.sh`; SCA sin altas |
| P13 Lógica en el backend | ✅ | vigencia de promociones en el caso de uso |
| P14 Trazabilidad | ✅ | sin auditoría (no toca datos sensibles); `request_id` sigue la desviación aceptada (OB3) |
| P15 Sistema de diseño | ✅ | `SiteStylesTokensTest`, `OnlyDesignTokensTest`, `DesignTokensTest` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.30.1 (rango) | ✅ 0 hallazgos en 63 commits |
| semgrep 1.178.0 (`p/php`) | ✅ 0 hallazgos |
| composer audit / npm audit | crít: 0 · alta: 0 · baja: 1. `laravel/framework` 12.69.3, CVE-2026-128083, "Task Output Injection - Concurrency Process", corregido en 12.71.0. La app no usa `Concurrency` (R28) |
| trivy 0.74.0 (`docker/`) | ✅ 0 configuraciones erróneas altas o críticas |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | contenido oculto y promociones vencidas fuera del HTML, el JSON-LD y el sitemap (CA14, tests) |
| A03 Inyección / XSS | ✅ | texto del staff escapado y JSON-LD con `JSON_HEX_TAG` (comprobado rompiéndolo); el compositor no escribe en la página |
| A05 Configuración | ✅ | CSP con nonce y `noopener noreferrer` en las pestañas nuevas |
| A06 Componentes | ⚠ | aviso bajo de Laravel (R28) |
| A09 Registro | ✅ | `public_gallery.load_failed` sin datos de visitantes |

## Observabilidad
- **Eventos de auditoría:** no aplica; la spec no toca datos sensibles, autenticación ni permisos.
- **Datos sensibles en logs:** ninguno. `public_gallery.load_failed` solo lleva la clase de la excepción. El log del inicio con la excepción completa ya existía antes de la spec.
- **`request_id`:** no está en los logs nuevos. Es la desviación de P14 aceptada hasta la spec del objetivo 5 (OB3, `docs/observability.md`). El plan decía "del contexto", pero ese contexto aún no existe.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Respuestas (404, privada) | ✅ | `/pagina-que-no-existe` → 404 con `noindex`; `/agenda` → 302 a `/login` |
| 3. Indexación y canónica | ✅ | canónica única y absoluta = `og:url` = `<loc>`, sin barra final |
| 4. Título y descripción | ✅ | únicos; descripciones de 113–156 caracteres (menor R9: títulos cortos en galería y acerca) |
| 5. Contenido sin JS | ✅ | galería en el HTML (menor R11) |
| 6. Imágenes | ✅ | menor R10 (certificaciones sin `width`/`height`) |
| 7. Sitemap y robots | ✅ | seis URL en `application/xml`; `Sitemap:` declarado |
| 8. Datos estructurados | ✅ | `Dentist` solo en `/` y `/contacto`, sin datos provisionales |
| 9. Compartir | ✅ | Open Graph completo, 1200×630 con `og:image:alt` |
| 12. Íconos y `theme-color` | ✅ | favicon, 192, apple-touch; `#d75078` |

La sección "Descubribilidad" del plan está aplicada en las seis rutas y el sitemap, con `PublicSiteSeoTest`. La barra final (SE2.c) responde 200; está fuera de esta spec.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `resources/views/site/inicio.blade.php:95`, `resources/css/site/pages/inicio.css:81-82` | La cinta de servicios se mueve sin fin (`marquee 40s infinite`) y solo se pausa con el puntero encima. No tiene botón de pausa, ni pausa con el foco, ni pausa táctil. Con movimiento reducido queda quieta pero recortada. Incumple CA10, M-3 y WCAG 2.2.2. | Botón de pausa (`aria-pressed`) y pausa con el foco, como en los testimonios. Con movimiento reducido, la lista completa visible. |
| R2 | importante | `config/site.php:46,63-69` | Un `SITE_SCHEDULE` definido pero mal escrito (`1-5 9:00-19:00`) se descarta sin aviso. Queda como dato real (`provisional.schedule = false`) con el horario vacío. El sitio dice "Cerrado" toda la semana y el JSON-LD publica `openingHoursSpecification` vacío. Contra CA7 y CA13. | Si no queda ninguna regla válida, usar el provisional y marcarlo como tal. Caso con valor inválido en `SiteConfigTest`. |
| R3 | importante | `resources/js/site/home.js:285-295` | En la bienvenida, el bloque de texto que se desvanece y el bloque final antes de aparecer quedan en opacidad 0, pero sus enlaces siguen en el orden de tabulación. Se enfocan sin verse (WCAG 2.4.7). | Que el bloque invisible no reciba foco (`inert` mientras esté oculto). Test en `SiteScriptsTest`. |
| R4 | importante | `resources/views/site/inicio.blade.php:291` | `aria-live="polite"` sobre el testimonio que rota solo: cada cambio automático se anuncia (WCAG 2.2.2 y 4.1.3). | Sin región viva mientras rota sola; anunciar solo los cambios que pide el visitante. |
| R5 | importante | `resources/views/site/contacto.blade.php:121` | `.map` tiene `role="img"` y dentro van los enlaces de Google Maps y Waze y el botón "Copiar dirección". Los hijos de `img` son presentacionales, así que esos controles pueden quedar fuera del árbol accesible. | `role="img"` solo en la ilustración (`.plane`), con los controles fuera. |
| R6 | importante | `resources/js/site/pages/contacto.js:116-139` | "Copiado" y "Dirección copiada" solo cambian el texto del botón. No se anuncian en la región de estado (WCAG 4.1.3), y el `x-ui.status` del layout nunca recibe texto. | Anunciar la confirmación en la región de estado del layout. |
| R7 | importante | `resources/views/site/nosotros.blade.php:26-27` | "Acerca de nosotros" lista como especialidades "Implantología" y "Estética dental", que no son nombres de los cinco tratamientos de `config/site.php` (espíritu de CA17). | Decisión del usuario: que las especialidades salgan de `config('site.treatments')`. |
| R8 | importante | `tests/Browser/screens.json` (`inicio-testimonios`) | `ui-audit inicio-testimonios` a 1440 px falla de forma intermitente desde T024: la composición depende de qué secciones se revelaron al desplazar. | Ficha `/fix` aparte antes de staging (A56, nota de T095). |
| R9 | menor | `config/site.php` (`pages`) | "Galería - Dentissa" (18 caracteres) y "Acerca de nosotros - Dentissa" (29) quedan por debajo del rango orientativo de 30–60. | Alargarlos o anotar en `seo.md` que es deliberado. |
| R10 | menor | `resources/views/site/inicio.blade.php:238` | Las `<img>` de certificaciones no declaran `width`/`height`. | `aspect-ratio` o medidas en `.cf-mat img`. |
| R11 | menor | `resources/views/site/galeria.blade.php:72` | Desde la 4.ª foto, `src` es un gif de 1×1 y la URL real va en `data-src` más `<noscript>`. | `loading="lazy"` nativo, o test de que cada URL está en `src` o en `<noscript>`. |
| R12 | menor | `app/Core/Http/PublicSiteSchedule.php:62` | `days()` no se usa. | Eliminarlo. |
| R13 | menor | `app/Core/Http/PublicSiteSchedule.php:33` | Agrupación con centinela poco legible y sin test unitario directo (hueco intermedio, un solo día, todo cerrado). | Tests unitarios y bucle más simple. |
| R14 | menor | `components/site/header.blade.php:14`, `site/contacto.blade.php:21`, `site/nosotros.blade.php:15` | Resumen del horario copiado en tres vistas. | `summary()` en `PublicSiteSchedule`. |
| R15 | menor | `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php:24` | Sin `node` falla con un error de proceso poco claro. | Comprobación previa con mensaje claro. |
| R16 | menor | `EloquentGalleryImageRepository.php:43` | El filtro `status` cambia lo que responde la API de galería y no tiene test de API. | Test de integración de la API con `?status=`. |
| R17 | menor | `tests/Modules/Core/Integration/PublicSiteSeoTest.php:240` | Con `'/'` compara el JSON-LD con el texto de `/contacto`, no con el de la misma página. | Leer el texto de la misma ruta. |
| R18 | menor | `PublicSiteSeoTest.php:74-92` | `og:url`, `og:type`, `og:locale` y `theme-color` solo se prueban en las legales. | Dataset con las seis rutas. |
| R19 | menor | `PublicSiteSeoTest.php` | Mismo hueco que R17 para el horario visible del inicio. | Junto con R17. |
| R20 | menor | `PublicSiteSeoTest.php` | CA11 en las cuatro páginas sin comprobar `og:description`. | Junto con R18. |
| R21 | menor | `PublicSiteContentTest.php:151-161` | El caso de abuso de CA15 no cubre nombre y descripción de una certificación. | Certificación con marcas HTML que debe salir escapada. |
| R22 | menor | `resources/views/site/inicio.blade.php:232` | Una certificación sin descripción muestra "Dentissa" como institución. | Omitir la institución si no hay descripción. |
| R23 | menor | `config/site.php:84` | `site.timezone` sin uso; la zona está repetida en cuatro archivos. | Leerla de config o quitar la clave. |
| R24 | menor | `home.js:536-541`, `core.js:224-227`, `header.blade.php:60` | Los botones de alternancia cambian a la vez el estado (`aria-pressed`/`aria-expanded`) y la etiqueta. | Etiqueta fija y el estado en el atributo. |
| R25 | menor | `config/site.php:63-66` | `SITE_WHATSAPP` no se normaliza; con espacios o `+`, `wa.me` se rompe. | Quitar lo que no sea dígito. |
| R26 | menor | `header.blade.php:60` | `aria-expanded="false"` estático en `<summary>`: sin JavaScript no se actualiza. | Escribirlo solo desde JS. |
| R27 | menor | `inicio.blade.php:335-371` | Las preguntas frecuentes sin `aria-controls`; `data-action` sin uso. | `aria-controls` y quitar `data-action`. |
| R28 | menor | `composer.lock` (`laravel/framework` 12.69.3) | CVE-2026-128083 de severidad baja, corregido en 12.71.0; la app no usa `Concurrency`. | `/chore` para subir Laravel. |
| R29 | menor | `nosotros.blade.php:134-146` | Las tarjetas del equipo solo giran con el puntero; la frase trasera existe en `sr-only`. | Hacerlas enfocables o ajustar la pista. |
| R30 | menor | `inicio.blade.php:95` | `aria-label` en un `div` sin rol (la cinta). | Se resuelve con R1. |

## Preparación para release
- **Rollback:** factible. `SITE_NEW_LANDING=false` más `config:cache` vuelve a las vistas anteriores, y existe `rollback.sh`. El flag existe con valor por defecto `true`, según el plan.
- **Migraciones:** ninguna.
- **Docs actualizadas:** `architecture.md` (T090), `deployment.md` y `observability.md` (T091), `seo.md` y roadmap (T028).
- **SCA:** sin vulnerabilidades críticas ni altas.
- **Riesgos:** las brechas SE de la spec están "en curso" y SE8.a "parcial", por corrección; ninguna figura como mitigada antes de liberar.
- **Datos de contacto:** todos siguen provisionales (`SITE_*` vacías) por la enmienda de CA7. Quedan pendientes de la clínica: WhatsApp, teléfono, correo, dirección, mapa, redes y horario.
- **Fotos de la propuesta:** son generadas (`data-origen="generada"`) y el usuario aprueba que vayan a producción hasta tener fotos reales (2026-11-16).

## Tareas añadidas
- T044 ← R1, R30
- T045 ← R2
- T046 ← R3
- T047 ← R4
- T048 ← R5
- T049 ← R6
- T050 ← R7
- R8 → ficha `/fix` aparte, antes de staging (nota A56 de T095)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-16:
- **R9** → roadmap: títulos de galería y acerca por debajo de 30 caracteres.
- **R10** → roadmap: certificaciones sin `width`/`height`.
- **R11** → roadmap: galería con `data-src` desde la 4.ª foto.
- **R12** → roadmap: `PublicSiteSchedule::days()` sin uso.
- **R13** → roadmap: tests unitarios de la agrupación del horario.
- **R14** → roadmap: resumen del horario repetido en tres vistas.
- **R15** → roadmap: `SiteScriptsTest` sin comprobación previa de `node`.
- **R16** → roadmap: test de API del filtro `status` de galería.
- **R17** → roadmap: `PublicSiteSeoTest` compara el inicio con el texto de contacto.
- **R18** → roadmap: etiquetas OG y `theme-color` probadas solo en las legales.
- **R19** → roadmap: horario visible del inicio sin comparar con el JSON-LD.
- **R20** → roadmap: `og:description` sin comprobar en las cuatro páginas.
- **R21** → roadmap: caso de abuso de CA15 sin certificación.
- **R22** → roadmap: certificación sin descripción muestra "Dentissa".
- **R23** → roadmap: `site.timezone` sin uso y zona repetida.
- **R24** → roadmap: botones con estado y etiqueta que cambian a la vez.
- **R25** → roadmap: `SITE_WHATSAPP` sin normalizar.
- **R26** → roadmap: `aria-expanded` estático en el `<summary>` del menú.
- **R27** → roadmap: preguntas frecuentes sin `aria-controls`.
- **R28** → roadmap: subir `laravel/framework` a ≥ 12.71.0 con un `/chore`.
- **R29** → roadmap: tarjetas del equipo que solo giran con el puntero.
