---
id: 026
slug: nueva-landing
status: released
released: v0.11.0 (2026-11-17)
created: 2026-11-12
extends: [011, 016, 022]
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
- [x] CA1 · Dado un visitante, cuando abre `/`, `/acerca-de-nosotros`, `/galeria` o `/contacto`, entonces ve la página con el diseño nuevo (barra superior, pie y botón flotante de WhatsApp comunes) en las mismas direcciones de hoy, y las páginas de Aviso de Privacidad y Términos y Condiciones usan la misma barra superior y el mismo pie sin cambiar su texto. Reemplaza 016 CA22 (composición conservada) en esas seis pantallas.
- [x] CA2 · Dado contenido publicado como visible en "Contenido", cuando un visitante abre el inicio, entonces ve las promociones vigentes ordenadas por la que vence antes, las certificaciones y los testimonios visibles, con los datos que hoy se capturan (nombre, descripción, fechas, porcentaje, imagen; la descripción de una certificación ocupa el lugar de la institución) y sin campos que el staff no captura.
- [x] CA3 · Dado que no hay promociones vigentes, certificaciones o testimonios visibles, cuando se abre el inicio, entonces cada sección muestra su estado vacío del diseño (aviso sin promociones activas, sección de certificaciones oculta, invitación "¿Ya nos visitaste?" que lleva a WhatsApp) y no una sección rota.
- [x] CA4 · Dado un fallo al leer el contenido, cuando se abre el inicio, entonces la página se muestra con los estados vacíos en lugar de un error (mantiene 011 CA2).
- [x] CA5 · Dadas imágenes visibles en la galería de "Contenido", cuando se abre `/galeria`, entonces esas imágenes y sus descripciones vienen en el HTML que entrega el servidor (sin esperar a una carga posterior) y se ven en el recorrido del consultorio; sin imágenes, se muestra un estado vacío. Reemplaza 011 CA3.
- [x] CA6 · Dado un visitante en `/contacto`, cuando elige un motivo, escribe su mensaje y envía, entonces se abre WhatsApp con el mensaje ya escrito hacia el número de la clínica; nada se guarda ni se envía a la clínica por otra vía; sin JavaScript, la página ofrece un enlace directo a WhatsApp. Reemplaza 011 CA4 en lo que toca a "sin formulario".
- [x] CA7 · Dado cualquier dato de contacto (WhatsApp, teléfono, correo, dirección, horario, mapa, redes), cuando se revisa el sitio, entonces se muestra el dato real si la clínica lo dio y, si no, un valor provisional coherente (los que hoy usa el sitio: WhatsApp `521234567890`, teléfono `+52 55 1234 5678`, correo `contacto@dentissa.com`, dirección "Lázaro Cárdenas, Michoacán" (la ciudad de la clínica, enmienda del 2026-11-15; antes "Av. Universidad 1200, Colonia Centro, Ciudad de México, CP 03100"), horario de lunes a viernes de 9:00 a 19:00 y sábado de 9:00 a 14:00, Instagram y Facebook de la clínica), para que el sitio se vea completo; cada valor provisional está marcado como tal en la configuración, se sustituye sin tocar las vistas y no se publica a los buscadores; no queda ningún marcador de texto (p. ej. "[CORREO]").
- [x] CA8 · Dada la medición de Lighthouse en perfil móvil sobre la build de producción, cuando se mide cada una de las cuatro rutas, entonces obtiene Rendimiento ≥ 90, Accesibilidad 100, Buenas prácticas 100 y SEO 100.
- [x] CA9 · Dado un visitante que pidió reducir el movimiento en su sistema, o un navegador sin gráficos 3D, cuando abre el inicio, entonces ve una versión estática del recorrido de tratamientos con el mismo texto y sin animaciones automáticas.
- [x] CA10 · Dado un visitante que solo usa teclado o lector de pantalla, cuando recorre las cuatro páginas a 390, 768 y 1440 px, entonces alcanza todos los enlaces y controles (menú, carruseles, selector del antes y después, formulario de contacto) en orden lógico, con foco visible y nombre accesible, y los carruseles automáticos se pueden pausar.
- [x] CA11 · Dada cada ruta indexable (las cuatro páginas y las dos legales), cuando se lee su HTML, entonces declara el idioma español, lleva título y descripción propios y únicos (descripción de 70–160 caracteres), una URL canónica absoluta sobre `https://dentissapp.com` sin barra final, las etiquetas para compartir (título, descripción, tipo, URL igual a la canónica e idioma) y el color de tema rosa de la marca.
- [x] CA12 · Dado un buscador, cuando pide `/sitemap.xml`, entonces recibe las seis rutas indexables con su URL canónica y ninguna ruta de acceso ni del panel; y `robots.txt` declara ese sitemap.
- [x] CA13 · Dados el inicio y contacto, cuando se lee su HTML, entonces incluyen los datos estructurados de clínica dental solo con información visible y confirmada en la página (nombre, dirección, teléfono, horario).
- [x] CA17 · Dado el contenido del sitio, cuando se revisan el recorrido de tratamientos del inicio, los servicios y el antes y después de la galería, entonces solo nombran tratamientos que Dentissa ofrece (los servicios que publica hoy su sitio en producción: Limpieza y Prevención, Ortodoncia, Implantes Dentales, Blanqueamiento Dental y Odontopediatría) y ninguno que no ofrezca.
- [x] CA18 · Dado un visitante en `/galeria`, cuando elige un tratamiento en el antes y después (Blanqueamiento Dental, Ortodoncia o Implantes Dentales), entonces ve una dentadura ilustrada en 3D con el "antes" de ese tratamiento a un lado de una línea y el "después" al otro, y puede mover la línea con el puntero y con el teclado (flechas, Inicio y Fin); el nombre y la descripción del tratamiento elegido se muestran como texto. Si el navegador no puede mostrar gráficos 3D, ve un dibujo plano equivalente con el mismo control; si pidió reducir el movimiento, la dentadura no gira sola y la línea no se mueve sola.
- [x] CA19 · Dado un enlace de cualquier ruta indexable compartido en WhatsApp o una red social, cuando se genera la vista previa, entonces muestra la imagen general para compartir de 1200×630 con la marca, hecha con el sistema de diseño ampliado, y su texto alternativo.
- [x] CA14 · (abuso) Como visitante, intento ver contenido marcado como oculto o una promoción vencida en cualquier página del sitio → no aparece en el HTML, en los datos estructurados ni en el sitemap.
- [x] CA15 · (abuso) Como administrador malintencionado o con una cuenta comprometida, publico una promoción, certificación, testimonio o descripción de imagen con código o marcado → el sitio lo muestra como texto y no se ejecuta.
- [x] CA16 · (abuso) Como visitante, escribo en el formulario de contacto un texto con marcado o caracteres especiales → el mensaje llega a WhatsApp como texto literal y la página no lo interpreta.

## Fuera de alcance
- Rediseño de iniciar sesión, registrarse, recuperar y restablecer contraseña → spec siguiente (incluye SE4.a, `noindex` en esas pantallas).
- Campos de contenido nuevos: institución o tipo en certificaciones (el diseño se adapta a lo que hoy se captura) y calificación con estrellas en testimonios (pendiente → roadmap, Pendientes y deuda).
- Fotos reales de antes y después de pacientes y su gestión desde el panel: el antes y después es una dentadura ilustrada, no un caso real.
- Cambios en la pantalla "Contenido" del panel o en cómo el staff publica.
- Redirecciones de `www` y de la barra final (SE2.b, SE2.c): configuración del servidor → objetivo 7 del roadmap.
- Formulario de contacto que guarde mensajes o envíe correos; envío de testimonios por pacientes.
- Contenido oculto expuesto por la API pública de contenido (011 CA6, `docs/security.md` RS4.a): el sitio nuevo no usa esa API; sigue pendiente → spec de corrección de la API pública.
- `theme-color` del panel y del portal del paciente (parte de SE8.a) → roadmap, Pendientes y deuda.
- Analítica, banners de cookies y alta en Search Console.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno. El sitio solo muestra contenido público que publica el
  staff; el formulario de contacto no guarda nada y solo abre WhatsApp en el dispositivo del
  visitante.
- Quién puede hacer qué: cualquier visitante → leer contenido visible y vigente; nadie puede ver
  contenido oculto ni promociones vencidas en las páginas del sitio (la API pública de contenido
  conserva su brecha RS4.a, fuera de alcance).
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
| SE8.a — `theme-color` en los layouts (sitio público) | dentro | CA11 (rosa de la marca, decisión del usuario) |
| SE8.a — `theme-color` en los layouts (panel y portal) | fuera | otra superficie → roadmap, Pendientes y deuda |
| RS4.a — No exponer contenido oculto en la API pública | fuera | el sitio nuevo no usa la API pública; las páginas filtran en el servidor (CA14) → spec de corrección de la API pública |

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
dejó el acta en [design.md](design.md): se adopta la propuesta tal cual, sin ciclo de candidatos, y solo se adapta donde lo exigen esta spec, la constitución o una puerta.

Pantallas: inicio, acerca de nosotros, galería y contacto, más la barra superior, el pie y el botón
de WhatsApp que comparten con las páginas legales.

Qué debe lograr cada pantalla:
- **Inicio:** en cinco segundos, qué hace la clínica y "Agenda tu cita por WhatsApp" visible sin
  desplazarse; después, confianza (promociones, certificaciones, testimonios, preguntas frecuentes,
  ubicación).
- **Acerca de nosotros:** quiénes son, trayectoria, equipo y valores; termina en la llamada a visitar.
- **Galería:** las fotos reales del consultorio que publica el staff y el antes y después con la dentadura 3D por tratamiento.
- **Contacto:** escribir por WhatsApp en un paso, ver si la clínica está abierta, dirección, mapa y horario.

Recursos de marca: logo y favicon reales de la clínica (los del sistema, spec 016 CA15; no el logo dibujado de la propuesta), colores del sistema, fotos de la
clínica y diplomas de ejemplo en `nueva-landing/…/public/img/dentissa/`.

Qué debe diseñarse: estados vacíos de cada sección con contenido (CA3, CA5), la versión estática del
recorrido 3D (CA9), el antes y después con la dentadura 3D y su dibujo plano (CA18) y, al final, la tarjeta para compartir de
1200×630 (CA19). Los textos se adaptan a lo que Dentissa hace (sus tratamientos reales), no la
clínica al diseño (CA17).

## Criterios de diseño
Modo: persuadir · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | La acción principal en la primera pantalla del inicio y de "Acerca de nosotros" | "Agenda una Cita" visible sin desplazarse a 390, 768 y 1440 px | CA1 |
| K2 | Lighthouse móvil de las cuatro rutas | Rendimiento ≥ 90; Accesibilidad, Buenas prácticas y SEO 100 | CA8 |
| K3 | Estados vacíos con contenido cero | cada sección muestra su estado vacío sin desbordes | CA3, CA5 |
| K4 | Movimiento reducido | sin animaciones automáticas y con el texto del recorrido | CA9 |
| K5 | Teclado y foco | todos los controles alcanzables con foco visible a 390, 768 y 1440 px | CA10 |
| K6 | Colores fuera de tokens | 0 | constitución P15 |
| K7 | Antes y después | dentadura 3D por tratamiento de la lista de servicios (CA17), comparable con puntero y teclado a 390 y 1440 px, con dibujo plano sin gráficos 3D y fluida en un teléfono de gama baja | CA17, CA18 |
| K8 | Tarjeta para compartir | 1200×630, marca legible a tamaño de miniatura | CA19 |

Detalle medible (CLS ≤ 0.1, LCP local ≤ 2.5 s, JavaScript ≤ 60 kB y primera visita ≤ 900 kB por página, titulares ≤ 3 líneas, fuentes cargadas, ≥ 1 escena y ≥ 3 fondos propios en el inicio) en [design/criterios.json](design/criterios.json); se mide sobre las páginas reales en `/implement` y `/review`.

Puertas: las de `design-spec/references/criterios.md` §2. Rúbrica (no se puntúa: el diseño lo aceptó el usuario): pesos por defecto; `riqueza` 15
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
| 2026-11-12 | brecha | ¿Qué brechas SEO cierra la spec? | Las que viven en las páginas: SE1.a, SE2.a, SE3.a, SE3.b, SE5.a, SE6.a, SE6.b, SE7.a, SE8.a (solo el sitio público); fuera SE2.b, SE2.c, SE4.a y SE8.a en panel y portal | usuario |
| 2026-11-12 | brecha | La spec pasa de 10 criterios | Se mantiene junta: el usuario fijó el alcance (sitio + SEO de las páginas) y ya separó las pantallas de acceso | usuario |
| 2026-11-12 | implícita | ¿De dónde sale el contenido? | Del contenido que el staff publica hoy, sin cambiar cómo se carga ni sus campos; el diseño se adapta | usuario ("adaptándola a lo que ya hay") · `docs/specs/011-sitio-publico/spec.md` |
| 2026-11-12 | implícita | Direcciones de las páginas | Las de hoy: `/`, `/acerca-de-nosotros`, `/galeria`, `/contacto` | `docs/seo.md` → Inventario de rutas |
| 2026-11-12 | implícita | Solo contenido visible y promociones vigentes en la zona de la clínica | Se mantiene | `docs/specs/011-sitio-publico/spec.md` CA1, Historial 2026-10-16 |
| 2026-11-12 | implícita | URL canónica | `https://dentissapp.com`, sin `www` ni barra final | `docs/seo.md` → Reglas del sitio |
| 2026-11-12 | diseño | Diseño de la spec | Se adopta la propuesta de `nueva-landing/` sin ciclo de candidatos; acta en [design.md](design.md) | usuario |
| 2026-11-12 | brecha | Datos de contacto: la propuesta y producción traen marcadores y datos de ejemplo (`[CORREO]`, `521234567890`, `+52 55 1234 5678`, redes genéricas) | Solo se muestran y se publican los datos confirmados; WhatsApp obligatorio | usuario (`/analyze 026` A6) |
| 2026-11-12 | supuesto | Las páginas legales comparten el layout del sitio | Adoptan la barra y el pie nuevos sin cambiar su texto y entran en el sitemap como rutas indexables; de la 022 se mantienen el texto, el título, la versión, la descripción propia y los enlaces del pie (CA6, CA9, CA10) | agente (`/analyze 026` A9) |
| 2026-11-12 | brecha | El antes y después de la propuesta es un dibujo con tratamientos de ejemplo | Se muestra con fotos (imágenes de muestra hasta tener las reales) y el contenido se adapta a lo que Dentissa hace: solo los tratamientos que ofrece (ver la fila de A26) | usuario |
| 2026-11-12 | brecha | Campos nuevos de contenido | Certificaciones sin campos nuevos; estrellas de testimonios quedan pendientes en el roadmap | usuario |
| 2026-11-12 | brecha | Imagen para compartir (SE6.b) | Entra en esta spec como última tarea, cuando el sistema de diseño ampliado ya exista | usuario |
| 2026-11-12 | brecha | K1 en las cuatro páginas | Solo en inicio y "Acerca de nosotros", donde la propuesta pone el botón | usuario (`/analyze 026` A5) |
| 2026-11-12 | brecha | Campos del formulario de contacto | Los de la propuesta: motivo y mensaje, sin nombre; sin JavaScript, enlace directo a WhatsApp | usuario (`/analyze 026` A14, A17) |
| 2026-11-12 | brecha | Logo y favicon | Los reales de la clínica (016 CA15), no los de la propuesta | usuario (`/analyze 026` A10) |
| 2026-11-12 | brecha | Descripción de las certificaciones | Se muestra en el lugar de la institución | usuario (`/analyze 026` A15) |
| 2026-11-12 | brecha | `theme-color` | Rosa de la marca en el sitio público; panel y portal fuera | usuario (`/analyze 026` A8) |
| 2026-11-12 | brecha | Fuente de los tratamientos (CA17) | Los servicios de la landing de producción, no el catálogo de la agenda | usuario (`/analyze 026` A26) |
| 2026-11-12 | supuesto | El formulario de contacto de la propuesta | Solo compone un mensaje de WhatsApp en el navegador; no es un formulario de contacto en el sentido de 011 "Fuera de alcance" | agente |
| 2026-11-12 | brecha | Composición conservada de las pantallas públicas (016 CA22) | 026 CA1 sustituye 016 CA22 en `/`, `/acerca-de-nosotros`, `/galeria`, `/contacto`, `/aviso-de-privacidad` y `/terminos-y-condiciones`: su composición es la de la propuesta | usuario (rediseño aprobado; `/analyze 026` A31) |
| 2026-11-12 | brecha | Datos de contacto reales no disponibles | Todos los datos de contacto usan valores provisionales (los del sitio actual) marcados como provisionales, fuera del JSON-LD, hasta que la clínica dé los reales; así el sitio conserva la mayor parte de la propuesta (mapa, "Abierto/Cerrado", redes) | usuario |
| 2026-11-12 | brecha | Dirección, horario y mapa de la propuesta son de ejemplo | Siguen la regla de los datos de contacto: solo si se confirman | usuario (`/analyze 026` A6, A37) |
| 2026-11-12 | supuesto | "Abierto / Cerrado" de inicio y contacto | Se calcula con el horario publicado y la hora de la clínica (`America/Mexico_City`), no la del visitante | agente |

| 2026-11-15 | brecha | Antes y después: fotos de muestra (decisión del 2026-11-12) o la dentadura 3D de la propuesta revisada | Dentadura ilustrada en 3D con dibujo plano de respaldo; sin fotos de muestra ni sus créditos. Reemplaza la decisión del 2026-11-12 sobre el antes y después con fotos; los tratamientos siguen la lista de CA17 | usuario |

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
- El antes y después usa la dentadura 3D de `gallery.js` de la propuesta (revisión del 2026-11-15, WebGL sin librerías) con el dibujo 2D como respaldo; los tratamientos son los de la lista de servicios de CA17 (no el catálogo de la agenda). Ajustes pedidos al traerla: la encía queda casi plana sobre la pieza faltante (hoy deja una ventana redonda), sin la franja beige del cuello en el lado blanqueado y sin la forma de bloque del último diente inferior izquierdo. Los colores del shader entran como tokens decorativos, como los del diente 3D. Probarla en un teléfono de gama baja: cada cuadro cuesta ~30 % más que la versión anterior.
- Al liberar, actualizar `docs/seo.md` (inventario y estado de SE1–SE8) y el roadmap (objetivo 7 y deuda SEO).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-12 | Creación | /specify |
| 2026-11-12 | Antes y después con fotos y tratamientos reales (CA17, CA18); imagen para compartir dentro como última tarea (CA19); estrellas de testimonios pendientes | Aclaración del usuario |
| 2026-11-12 | Aprobada | Confirmación explícita del usuario |
| 2026-11-12 | `extends` 022; CA2, CA6, CA7, CA11, CA13, CA17, K1, SE8.a, RS4.a y logo según las decisiones del usuario sobre `/analyze 026` (A5, A6, A8, A9, A10, A14, A15, A17, A18, A26); vuelve a `draft` | `/analyze 026` ronda 1 |
| 2026-11-12 | Aprobada de nuevo | Confirmación explícita del usuario |
| 2026-11-12 | 026 CA1 sustituye 016 CA22 en las seis pantallas públicas; CA7 incluye dirección, horario y mapa; textos de K7 y Notas alineados con CA17; vuelve a `draft` | `/analyze 026` ronda 2 (A31, A37, A44) |
| 2026-11-12 | Aprobada de nuevo | Confirmación explícita del usuario |
| 2026-11-12 | Enmienda de CA7: WhatsApp provisional `521234567890` permitido hasta tener el real, marcado como provisional y fuera del JSON-LD | Pedida por el usuario: no tiene contacto directo con la doctora; lo pedirá si el proyecto gana el concurso |
| 2026-11-12 | Enmienda de CA7: todos los datos de contacto con valores provisionales marcados (no solo el WhatsApp), fuera del JSON-LD | Pedida por el usuario: conservar la mayor parte de la propuesta hasta tener los datos de la clínica |
| 2026-11-15 | CA18, K7, Diseño y Fuera de alcance: el antes y después pasa de fotos de muestra a la dentadura 3D de la propuesta revisada, con dibujo plano de respaldo; vuelve a `draft` | Pedido del usuario tras revisar el modelo 3D nuevo |
| 2026-11-15 | Aprobada de nuevo | Confirmación explícita del usuario |
| 2026-11-15 | Enmienda de CA7: la dirección provisional pasa a "Lázaro Cárdenas, Michoacán" (la ciudad de la clínica) y los títulos y descripciones dejan de decir "Ciudad de México" | Pedida por el usuario: la clínica está en Lázaro Cárdenas |
| 2026-11-17 | Corregido por la ficha 2026-11-17-fix-ui-audit-testimonios-intermitente en v0.11.0 (CA10) | /release |