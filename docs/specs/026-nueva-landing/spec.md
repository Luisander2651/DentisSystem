---
id: 026
slug: nueva-landing
status: approved
created: 2026-11-12
extends: [011, 016]
---

# 026 · Nueva landing del sitio público

## Problema
El sitio público (inicio, acerca de nosotros, galería y contacto) mantiene el aspecto de la primera
versión: no transmite la identidad que la clínica quiere mostrar y no está preparado para los
buscadores. Ya existe un diseño nuevo, pero se hizo aparte del sistema y no toma el contenido que el
staff publica. Además, el roadmap pide un sitio indexable (objetivo 7): hoy las rutas no tienen
descripción ni canónica, no hay sitemap y la galería llega vacía en el HTML. Si no se resuelve, el
sitio sigue con el diseño viejo y es difícil de encontrar. Esta spec atiende el objetivo 7
**parcialmente** (ver "Cobertura de riesgos").

## Historias de usuario
- Como visitante, quiero un sitio claro y atractivo que me deje saber en segundos qué ofrece la
  clínica y cómo pedir una cita, para decidir si la contacto.
- Como visitante, quiero ver las promociones vigentes, las certificaciones, los testimonios y las
  fotos que la clínica publica, para confiar en ella.
- Como visitante con un teléfono o una conexión lenta, quiero que el sitio cargue rápido y se pueda
  usar con teclado o lector de pantalla, para no abandonarlo.
- Como administrador, quiero que el contenido que publico en "Contenido" aparezca en el sitio nuevo
  sin cambiar cómo lo cargo, para no hacer trabajo doble.
- Como dueño de la clínica, quiero que el sitio aparezca bien en buscadores y al compartirlo, para
  atraer pacientes nuevos.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante, cuando abre `/`, `/acerca-de-nosotros`, `/galeria` o `/contacto`, entonces ve la página con el diseño nuevo (barra superior, pie y botón flotante de WhatsApp comunes) en las mismas direcciones de hoy, y las páginas de Aviso de Privacidad y Términos y Condiciones usan la misma barra superior y el mismo pie sin cambiar su texto.
- [ ] CA2 · Dado contenido publicado como visible en "Contenido", cuando un visitante abre el inicio, entonces ve las promociones vigentes ordenadas por la que vence antes, las certificaciones y los testimonios visibles, con los datos que hoy se capturan (nombre, descripción, fechas, porcentaje, imagen) y sin campos que el staff no captura.
- [ ] CA3 · Dado que no hay promociones vigentes, certificaciones o testimonios visibles, cuando se abre el inicio, entonces cada sección muestra su estado vacío del diseño (aviso sin promociones activas, sección de certificaciones oculta, invitación "¿Ya nos visitaste?" que lleva a WhatsApp) y no una sección rota.
- [ ] CA4 · Dado un fallo al leer el contenido, cuando se abre el inicio, entonces la página se muestra con los estados vacíos en lugar de un error (mantiene 011 CA2).
- [ ] CA5 · Dadas imágenes visibles en la galería de "Contenido", cuando se abre `/galeria`, entonces esas imágenes y sus descripciones vienen en el HTML que entrega el servidor (sin esperar a una carga posterior) y se ven en el recorrido del consultorio; sin imágenes, se muestra un estado vacío. Reemplaza 011 CA3.
- [ ] CA6 · Dado un visitante en `/contacto`, cuando escribe su nombre y su mensaje y envía, entonces se abre WhatsApp con el mensaje ya escrito hacia el número de la clínica; nada se guarda ni se envía a la clínica por otra vía. Reemplaza 011 CA4 en lo que toca a "sin formulario".
- [ ] CA7 · Dado cualquier dato de contacto mostrado (teléfono, WhatsApp, correo, dirección, horario, redes), cuando se revisa el sitio, entonces es el mismo que muestra hoy el sitio en producción y no queda ningún texto provisional (p. ej. "[CORREO]").
- [ ] CA8 · Dada la medición de Lighthouse en perfil móvil sobre la build de producción, cuando se mide cada una de las cuatro rutas, entonces obtiene Rendimiento ≥ 90, Accesibilidad 100, Buenas prácticas 100 y SEO 100.
- [ ] CA9 · Dado un visitante que pidió reducir el movimiento en su sistema, o un navegador sin gráficos 3D, cuando abre el inicio, entonces ve una versión estática del recorrido de tratamientos con el mismo texto y sin animaciones automáticas.
- [ ] CA10 · Dado un visitante que solo usa teclado o lector de pantalla, cuando recorre las cuatro páginas a 390, 768 y 1440 px, entonces alcanza todos los enlaces y controles (menú, carruseles, selector del antes y después, formulario de contacto) en orden lógico, con foco visible y nombre accesible, y los carruseles automáticos se pueden pausar.
- [ ] CA11 · Dada cada ruta indexable (las cuatro páginas y las dos legales), cuando se lee su HTML, entonces lleva título y descripción propios y únicos (descripción de 70–160 caracteres), una URL canónica absoluta sobre `https://dentissapp.com` sin barra final, las etiquetas para compartir (título, descripción, tipo, URL igual a la canónica e idioma) y el color de tema de la marca.
- [ ] CA12 · Dado un buscador, cuando pide `/sitemap.xml`, entonces recibe las seis rutas indexables con su URL canónica y ninguna ruta de acceso ni del panel; y `robots.txt` declara ese sitemap.
- [ ] CA13 · Dados el inicio y contacto, cuando se lee su HTML, entonces incluyen los datos estructurados de clínica dental solo con información visible en la página (nombre, dirección, teléfono, horario).
- [ ] CA17 · Dado el contenido del sitio, cuando se revisan el recorrido de tratamientos del inicio, los servicios y el antes y después de la galería, entonces solo nombran tratamientos que Dentissa ofrece en su catálogo y ninguno que no ofrezca.
- [ ] CA18 · Dado un visitante en `/galeria`, cuando elige un tratamiento en el antes y después, entonces ve un par de fotos de antes y después de ese tratamiento que puede comparar deslizando, también con teclado; hasta tener las fotos reales se usan imágenes de muestra.
- [ ] CA19 · Dado un enlace de cualquier ruta indexable compartido en WhatsApp o una red social, cuando se genera la vista previa, entonces muestra la imagen general para compartir de 1200×630 con la marca, hecha con el sistema de diseño ampliado, y su texto alternativo.
- [ ] CA14 · (abuso) Como visitante, intento ver contenido marcado como oculto o una promoción vencida en cualquier página del sitio → no aparece en el HTML, en los datos estructurados ni en el sitemap.
- [ ] CA15 · (abuso) Como administrador malintencionado o con una cuenta comprometida, publico una promoción, certificación, testimonio o descripción de imagen con código o marcado → el sitio lo muestra como texto y no se ejecuta.
- [ ] CA16 · (abuso) Como visitante, escribo en el formulario de contacto un texto con marcado o caracteres especiales → el mensaje llega a WhatsApp como texto literal y la página no lo interpreta.

## Fuera de alcance
- Rediseño de iniciar sesión, registrarse, recuperar y restablecer contraseña → spec siguiente (incluye SE4.a, `noindex` en esas pantallas).
- Campos de contenido nuevos: institución o tipo en certificaciones (el diseño se adapta a lo que hoy se captura) y calificación con estrellas en testimonios (pendiente → roadmap, Pendientes y deuda).
- Fotos reales de antes y después de pacientes y su gestión desde el panel: esta spec usa imágenes de muestra.
- Cambios en la pantalla "Contenido" del panel o en cómo el staff publica.
- Redirecciones de `www` y de la barra final (SE2.b, SE2.c): configuración del servidor → objetivo 7 del roadmap.
- Formulario de contacto que guarde mensajes o envíe correos; envío de testimonios por pacientes.
- Corregir la API pública de contenido (011 CA6, listados de un elemento): no la usa el sitio nuevo.
- Analítica, banners de cookies y alta en Search Console.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno. El sitio solo muestra contenido público que publica el
  staff; el formulario de contacto no guarda nada y solo abre WhatsApp en el dispositivo del
  visitante.
- Quién puede hacer qué: cualquier visitante → leer contenido visible y vigente; nadie puede ver
  contenido oculto ni promociones vencidas desde el sitio.
- Casos de abuso:
  - Como visitante, intento ver contenido oculto o vencido → no aparece (CA14).
  - Como administrador con una cuenta comprometida, publico contenido con código → se muestra como texto (CA15).
  - Como visitante, escribo marcado en el formulario de contacto → se trata como texto (CA16).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| SE1.a — Descripción propia y única por ruta indexable | dentro | CA11 |
| SE2.a — Canónica absoluta en cada ruta indexable | dentro | CA11 |
| SE2.b — `www` redirige con 301 | fuera | configuración del servidor → roadmap objetivo 7 |
| SE2.c — La barra final redirige con 301 | fuera | configuración del servidor → roadmap objetivo 7 |
| SE3.a — `sitemap.xml` con las rutas indexables | dentro | CA12 |
| SE3.b — `robots.txt` declara el sitemap | dentro | CA12 |
| SE4.a — `noindex` en las pantallas de acceso | fuera | van con su rediseño → spec siguiente de pantallas de acceso |
| SE5.a — Galería con su contenido en el HTML | dentro | CA5 |
| SE6.a — Etiquetas para compartir en cada ruta indexable | dentro | CA11 |
| SE6.b — Imagen general para compartir | dentro | CA19 (última tarea, con el sistema de diseño ya ampliado) |
| SE7.a — Datos estructurados de clínica dental | dentro | CA13 |
| SE8.a — `theme-color` en los layouts | dentro | CA11 (layouts del sitio público; los demás layouts → deuda) |

## Auditoría
No aplica: la spec no toca datos sensibles, autenticación ni permisos; solo muestra contenido
público ya publicado.

## Diseño
Rediseño del sitio público a partir de la propuesta entregada en `nueva-landing/`: cálido, moderno y
confiable, con tipografía propia (una sans y una serif de acento), subrayados a mano, animaciones al
desplazarse y un diente en 3D que recorre los tratamientos. Se conserva ese aspecto. Sistema:
[docs/design/system.md](../../design/system.md), que se **amplía** con una variante "sitio público"
(las dos fuentes, servidas desde el propio sitio, y los tokens extra de espaciado, duración y
movimiento); los colores sueltos de la propuesta se sustituyen por tokens. `/design-spec 026`
documenta la propuesta como primer candidato y la mide.

Pantallas: inicio, acerca de nosotros, galería y contacto, más la barra superior, el pie y el botón
de WhatsApp que comparten con las páginas legales.

Qué debe lograr cada pantalla:
- **Inicio:** en cinco segundos, qué hace la clínica y "Agenda tu cita por WhatsApp" visible sin
  desplazarse; después, confianza (promociones, certificaciones, testimonios, preguntas frecuentes,
  ubicación).
- **Acerca de nosotros:** quiénes son, trayectoria, equipo y valores; termina en la llamada a visitar.
- **Galería:** las fotos reales del consultorio que publica el staff y el antes y después con fotos por tratamiento.
- **Contacto:** escribir por WhatsApp en un paso, ver si la clínica está abierta, dirección, mapa y horario.

Recursos de marca: logotipo y colores del sistema 2.0.0, favicon de la propuesta, fotos de la
clínica y diplomas de ejemplo en `nueva-landing/…/public/img/dentissa/`.

Qué debe diseñarse: estados vacíos de cada sección con contenido (CA3, CA5), la versión estática del
recorrido 3D (CA9), el antes y después con fotos (CA18) y, al final, la tarjeta para compartir de
1200×630 (CA19). Los textos se adaptan a lo que Dentissa hace (sus tratamientos reales), no la
clínica al diseño (CA17).

## Criterios de diseño
Modo: persuadir · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | La acción principal en la primera pantalla del inicio | "Agenda tu cita" visible sin desplazarse a 390 y 1440 px | CA1 |
| K2 | Lighthouse móvil de las cuatro rutas | Rendimiento ≥ 90; Accesibilidad, Buenas prácticas y SEO 100 | CA8 |
| K3 | Estados vacíos con contenido cero | cada sección muestra su estado vacío sin desbordes | CA3, CA5 |
| K4 | Movimiento reducido | sin animaciones automáticas y con el texto del recorrido | CA9 |
| K5 | Teclado y foco | todos los controles alcanzables con foco visible a 390, 768 y 1440 px | CA10 |
| K6 | Colores fuera de tokens | 0 | constitución P15 |
| K7 | Antes y después | par de fotos por tratamiento del catálogo, comparable con puntero y teclado a 390 y 1440 px | CA17, CA18 |
| K8 | Tarjeta para compartir | 1200×630, marca legible a tamaño de miniatura | CA19 |

Puertas: las de `design-spec/references/criterios.md` §2. Rúbrica: pesos por defecto; `riqueza` 15
porque la propuesta se apoya en fondos, tipografía y movimiento (§6).

## Requisitos no funcionales
- Rendimiento y calidad: los umbrales de Lighthouse de CA8, medidos en perfil móvil sobre la build
  de producción; se vuelven a medir en producción tras liberar.
- Accesibilidad: WCAG 2.1 AA en las cuatro páginas y la barra, el pie y el botón compartidos
  (constitución → Restricciones); contraste suficiente también sobre las fotos y los fondos de color.
- Descubribilidad: las seis rutas indexables aparecen en buscadores con su título y descripción
  propios y su vista previa al compartir (CA11–CA13); ninguna ruta de acceso ni del panel aparece en
  el sitemap.
- El sitio no depende de servicios de terceros para mostrarse (fuentes y scripts servidos desde el
  propio sitio); el mapa y WhatsApp son enlaces o incrustaciones que no bloquean la carga.
- El contenido publicado tarda como máximo lo mismo que hoy en verse en el sitio (hasta 10 minutos).

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-12 | contradicción | La propuesta trae CSS propio, colores literales y dos fuentes de Google; P15 y `docs/design/system.md` exigen solo tokens y la fuente del sistema | Ampliar el sistema de diseño con una variante "sitio público" (fuentes servidas desde el sitio y tokens extra) y sustituir los colores literales por tokens, conservando el aspecto | usuario |
| 2026-11-12 | brecha | ¿Entran las pantallas de acceso de la propuesta? | No: spec aparte, junto con SE4.a | usuario |
| 2026-11-12 | brecha | Metas de Lighthouse | Móvil: Rendimiento ≥ 90, Accesibilidad 100, Buenas prácticas 100, SEO 100 en las cuatro rutas | usuario |
| 2026-11-12 | brecha | ¿Qué brechas SEO cierra la spec? | Las que viven en las páginas: SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b, SE7.a, SE8.a; fuera SE2.b, SE2.c y SE4.a | usuario |
| 2026-11-12 | brecha | La spec pasa de 10 criterios | Se mantiene junta: el usuario fijó el alcance (sitio + SEO de las páginas) y ya separó las pantallas de acceso | usuario |
| 2026-11-12 | implícita | ¿De dónde sale el contenido? | Del contenido que el staff publica hoy, sin cambiar cómo se carga ni sus campos; el diseño se adapta | usuario ("adaptándola a lo que ya hay") · `docs/specs/011-sitio-publico/spec.md` |
| 2026-11-12 | implícita | Direcciones de las páginas | Las de hoy: `/`, `/acerca-de-nosotros`, `/galeria`, `/contacto` | `docs/seo.md` → Inventario de rutas |
| 2026-11-12 | implícita | Solo contenido visible y promociones vigentes en la zona de la clínica | Se mantiene | `docs/specs/011-sitio-publico/spec.md` CA1, Historial 2026-10-16 |
| 2026-11-12 | implícita | URL canónica | `https://dentissapp.com`, sin `www` ni barra final | `docs/seo.md` → Reglas del sitio |
| 2026-11-12 | supuesto | Datos de contacto: la propuesta trae marcadores (`[CORREO]`, número de ejemplo) | Se usan los datos que muestra hoy el sitio en producción | agente |
| 2026-11-12 | supuesto | Las páginas legales comparten el layout del sitio | Adoptan la barra y el pie nuevos sin cambiar su texto y entran en el sitemap como rutas indexables | agente |
| 2026-11-12 | brecha | El antes y después de la propuesta es un dibujo con tratamientos de ejemplo | Se muestra con fotos (imágenes de muestra hasta tener las reales) y el contenido se adapta a lo que Dentissa hace: solo tratamientos de su catálogo | usuario |
| 2026-11-12 | brecha | Campos nuevos de contenido | Certificaciones sin campos nuevos; estrellas de testimonios quedan pendientes en el roadmap | usuario |
| 2026-11-12 | brecha | Imagen para compartir (SE6.b) | Entra en esta spec como última tarea, cuando el sistema de diseño ampliado ya exista | usuario |
| 2026-11-12 | supuesto | El formulario de contacto de la propuesta | Solo compone un mensaje de WhatsApp en el navegador; no es un formulario de contacto en el sentido de 011 "Fuera de alcance" | agente |
| 2026-11-12 | supuesto | "Abierto / Cerrado" de inicio y contacto | Se calcula con el horario publicado y la hora de la clínica (`America/Mexico_City`), no la del visitante | agente |

## Notas para /plan
- La propuesta está en `nueva-landing/dentissa-laravel/` (Blade + CSS propio + JS vanilla, sin
  dependencias npm; `tooth3d.js` es WebGL sin librerías). Su README describe las variables que
  esperan las vistas (`titulo`, `porcentaje`, `institucion`, `calificacion`…): mapearlas a los
  campos reales de ContentManagement (`name`, `description`, `discount_percentage`, `start_date`,
  `end_date`, `image_url`, `author`, `date`) y quitar lo que no tiene dato.
- Fuentes: Inter Tight e Instrument Serif, autoalojadas (sin Google Fonts) con `font-display` y
  precarga; añadirlas al `@theme` de `resources/css/app.css` y a `docs/design/system.md` como
  "Cambios a incorporar al sistema". `tokens.css` repite los tokens de `@theme`: dejar solo los extra.
- Revisar `OnlyDesignTokensTest`: la propuesta tiene colores en atributos `style` y SVG (`#f2b0a6`, `#fff`).
- Carga por página (`pageAssets`): el diente 3D y sus partículas no deben bloquear el LCP; cargarlo
  diferido y solo en el inicio.
- La galería pasa de `GET /api/v1/public/gallery-images` a renderizado en el servidor (SE5.a); la
  ruta de API puede quedar como está.
- `sitemap.xml` y la descripción de cada ruta: la enmienda MINOR "El sitio público se puede
  indexar" la propone la spec que cierre por completo el objetivo 7 (roadmap); esta lo cierra en parte.
- Medir Lighthouse en CI o en `npm run test:ui` si es viable sin dependencias nuevas; si no, medición
  manual registrada en `review.md` y en `docs/seo.md`.
- La imagen para compartir (CA19) es la **última tarea**, después de incorporar los tokens y las fuentes al sistema de diseño; la tarjeta se añade a `docs/design/system.md`.
- El antes y después pasa de dibujo CSS a pares de imágenes estáticas de muestra por tratamiento; los tratamientos se toman del catálogo (módulo Appointments) o se fijan a los que hoy existen en él, según decida el plan.
- Al liberar, actualizar `docs/seo.md` (inventario y estado de SE1–SE8) y el roadmap (objetivo 7 y deuda SEO).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-12 | Creación | /specify |
| 2026-11-12 | Antes y después con fotos y tratamientos reales (CA17, CA18); imagen para compartir dentro como última tarea (CA19); estrellas de testimonios pendientes | Aclaración del usuario |
| 2026-11-12 | Aprobada | Confirmación explícita del usuario |
