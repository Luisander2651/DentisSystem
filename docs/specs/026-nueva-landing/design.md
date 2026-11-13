---
spec: 026-nueva-landing
status: approved
exploration: none
threshold: 80
floor: 3
budget: 5
accepted: none
score: 0
system_version: 2.0.0
created: 2026-11-12
updated: 2026-11-12
---

# Diseño · 026 Nueva landing del sitio público

Hecho con `/design-spec 026` sobre `docs/design/system.md` (2.0.0). Criterios en la spec ("Criterios
de diseño") y en `design/criterios.json`. **Sin ciclo de candidatos**: ver "Resultado".

## Candidatos

Ninguno. No se generaron candidatos por decisión del usuario (ver "Decisiones").

## Resultado

**Sin diseño nuevo: se adopta la propuesta entregada.** El diseño aceptado es el de
`nueva-landing/dentissa-laravel/` (vistas `pages/inicio`, `nosotros`, `galeria`, `contacto`, el
layout `layouts/dentissa` y los componentes `header`, `footer` y `whatsapp-float`), tal como está.
El usuario lo eligió y no quiere alternativas que muevan su composición, tipografía ni movimiento.

La implementación solo puede cambiar lo que obliga la spec, la constitución o una puerta, y siempre
**sin cambiar la composición**:

1. **Tokens:** cada color literal pasa a un token con su mismo valor, y cada radio y alto de control
   a la escala del sitio público (ver "Cambios a incorporar al sistema"); los `style` en línea de las
   vistas pasan a clases.
2b. **Marca:** el logo y el favicon son los reales de la clínica (`x-ui.brand`, spec 016 CA15), no
   el logo dibujado de la propuesta; el resto de la barra y el pie no cambia.
2. **Fuentes:** Inter Tight e Instrument Serif (OFL) se sirven desde el propio sitio, sin Google Fonts.
3. **Contenido real:** datos de contacto reales cuando la clínica los dé y, mientras tanto, valores provisionales marcados (CA7), así que se conservan el mapa, "Abierto/Cerrado" y las redes de la propuesta; tratamientos de la lista de servicios (CA17),
   promociones, certificaciones, testimonios y galería desde "Contenido" (CA2, CA5), y se quita lo
   que no tiene dato (tipo y estrellas; la descripción de la certificación ocupa el lugar de la institución).
4. **Antes y después:** el dibujo CSS se sustituye por pares de fotos por tratamiento, con el mismo
   control deslizante y el mismo selector (CA18).
5. **Correcciones de puertas:** si al medir las páginas reales falla una puerta o un KPI
   (contraste, toque, foco, movimiento reducido, sin JavaScript, peso), se corrige ese detalle con
   el cambio mínimo y se registra como `refinado` en "Decisiones".

Los criterios de `design/criterios.json` (comunes) y de `design/criterios-<página>.json` (cada página con sus KPI propios, porque `evaluate.mjs` no filtra por página) se miden con `evaluate.mjs` sobre las páginas reales
servidas en local en la tarea que construye cada página, y otra vez en `/review`. La rúbrica no se
puntúa: la composición ya está aceptada por el usuario.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| Puertas de `criterios.md` §2 | todas | en `/implement`, por página | — |
| K1 · Acción principal en la primera pantalla (CA1) | "Agenda una Cita" a la vista a 390, 768 y 1440 en inicio y "Acerca de nosotros" | en `/implement` | — |
| K2a–K2f · Calidad de carga (CA8, CA10) | CLS ≤ 0.1 · LCP ≤ 2.5 s · JS ≤ 60 kB · primera visita ≤ 900 kB · titulares ≤ 3 líneas · las dos fuentes cargadas | en `/implement` | — |
| K4a, K4b · Riqueza del inicio (CA1, CA9) | ≥ 1 escena · ≥ 3 fondos propios | en `/implement` | — |
| K7 · Antes y después (CA18) | "Antes" y "Después" visibles | en `/implement` | — |
| K2, K3, K5, K8 (verificación en la tarea) | Lighthouse, estados vacíos, teclado, tarjeta | tests y `ui-audit` | — |

## Imágenes de muestra

| Imagen | Dónde | Fuente y licencia | Qué la sustituye |
|---|---|---|---|
| `recepcion.webp`, `consulta.webp`, `consultorio.webp`, `odontopediatria.webp`, `radiografia.webp`, `sonrisa.webp` | inicio, nosotros y galería (fotos fijas de la propuesta) | venían con la propuesta; por su aspecto parecen **generadas**, no fotos de la clínica — origen por confirmar con el usuario | fotos reales de la clínica; mientras tanto `data-origen="generada"` |
| `diploma-horizontal.svg`, `diploma-vertical.svg` | respaldo de certificaciones sin imagen | dibujos de la propuesta | la imagen que el staff sube a cada certificación |
| Pares de antes y después (por descargar) | galería, por tratamiento | bancos libres (Unsplash/Pexels), con autor y licencia en el repo; `data-origen="muestra"` | fotos reales de casos con consentimiento del paciente |

`/review` y `/release` exigen `max_samples: 0` salvo decisión del usuario.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Inicio · con contenido | `pages/inicio.blade.php` a 390 | 768 | 1440 | CA1, CA2, CA8, CA9, CA17 |
| Inicio · sin promociones, certificaciones ni testimonios | igual | igual | igual | CA3, CA4 |
| Inicio · sin promociones (refinado 2026-11-13) | sección propia: etiqueta "Promociones", `h2` "Por ahora no hay promociones activas", dibujo con anillos, un `%` en contorno y tres cupones flotantes, y dos acciones ("Pregúntanos por WhatsApp" y "Ver tratamientos"; sin prometer un aviso que no existe) | | | CA3 |
| Acerca de nosotros | `pages/nosotros.blade.php` | 768 | 1440 | CA1, CA7 |
| Galería · con imágenes / sin imágenes | `pages/galeria.blade.php` | 768 | 1440 | CA5, CA17, CA18 |
| Galería · 0 imágenes | el recorrido del consultorio muestra el aviso de la propuesta "Muy pronto añadiremos las fotos" con el enlace a WhatsApp, sin barra de recorrido | | | CA5 |
| Galería · 1 o 2 imágenes | fila estática de marcos, sin recorrido horizontal ni barra de progreso | | | CA5 |
| Galería · 3 o más imágenes | recorrido horizontal de la propuesta, un marco por imagen | | | CA5 |
| Contacto | `pages/contacto.blade.php` | 768 | 1440 | CA6, CA7, CA16 |
| Aviso de privacidad y Términos (barra y pie nuevos) | vistas actuales | 768 | 1440 | CA1 |
| Tarjeta para compartir 1200×630 | — | — | — | CA19 (última tarea; `spec-mode.md` §7) |

## Componentes

- **Del sistema:** marca (`x-ui.brand`) en barra y pie; botón de WhatsApp con `--color-whatsapp` y
  texto en tinta; región de estado para avisos.
- **Exclusivos del sitio público** (de la propuesta, se conservan): barra superior con página activa,
  pie, botón flotante de WhatsApp, botones `btn` (primario, fantasma, grande), subrayado a mano
  (`hl`), recorrido 3D del diente con su versión estática, cupón y boletos de promociones, fila
  deslizable de certificaciones, carrusel y cinta de testimonios, preguntas frecuentes, recorrido
  horizontal del consultorio, antes y después con selector, compositor de mensaje de WhatsApp y
  estado "Abierto / Cerrado".

## Cambios a incorporar al sistema

La primera tarea los aplica a `system.md` (versión **2.1.0**: añade una variante "sitio público"
sin cambiar los tokens existentes) y `system.html`, y a `@theme` de `resources/css/app.css`.

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-display` (Inter Tight 400–700) | fijo | autoalojada | — | tipografía de la propuesta; sin servicios externos |
| `--font-serif` (Instrument Serif, normal e itálica) | fijo | autoalojada | — | acentos de la propuesta |
| `--color-primary-deep` | fijo | `#a3345a` | — | rosa oscuro de la propuesta (fondos de llamada) |
| `--color-line-strong` | fijo | `#cbd5e1` | — | bordes y separadores sobre superficies |
| `--color-muted-soft` | fijo | `#94a3b8` | — | iconos y texto decorativo sobre tinta (no texto sobre claro) |
| `--color-canvas-alt` | fijo | `#eef2f6` | — | fondo alterno de secciones |
| `--color-primary-tint`, `--color-primary-tint-2`, `--color-secondary-soft` | fijo | `#f3c9d5` · `#f6d3dd` · `#fdf1ee` | — | fondos y degradados decorativos rosados |
| `--color-success-tint` | fijo | `#bfe9d6` | — | estado "Abierto" |
| `#fff`, `#000` → `--color-surface`/`--color-on-dark` e `--color-ink` | — | — | — | literales de la propuesta mapeados a tokens existentes (sombras: `--color-ink` con opacidad) |
| Escalas `--space-1…8`, `--duration-fast/base`, `--ease-out`, `--ease-spring`, `--weight-regular`, `--weight-strong`, `--text-body` | fijo | las de `tokens.css` | — | ritmo, movimiento y pesos que usa la propuesta (toda `var(--…)` del CSS del sitio tiene token) |
| Radios del sitio: `--radius-hair` 3px (2–6) · `--radius-sm` 10px (8–11) · `--radius-md` 14px · `--radius-lg` 20px (18–22) · `--radius-xl` 28px (26–30) · `--radius-2xl` 32px (32–40); se conservan `control` 12, `box` 16, `card` 24 y `full` | fijo | — | — | la propuesta usa ~20 radios distintos; se agrupan en una escala con diferencias de 4 px como máximo. Formas en `%` (círculos, el diente) se admiten |
| `--color-enamel`, `--color-enamel-white` y los demás tonos que pinta el diente 3D | fijo | los del shader de la propuesta | — | colores decorativos del modelo 3D (esmalte natural y blanqueado); el JS los lee del CSS al arrancar junto con `--color-primary` y `--color-secondary`, y no escribe ningún color (decisión del usuario, A30) |
| `--spacing-control-lg` 52px | fijo | — | — | botón grande de la propuesta (52–54 px); el normal usa `--spacing-control` (44) |
| Componentes del sitio público en `system.md` → Componentes: botón del sitio (`btn` primario, fantasma y grande), barra con menú plegable, pie con botón flotante de WhatsApp, selector segmentado, carrusel con pausa, comparador de antes y después, compositor de WhatsApp, visor de certificaciones (diálogo con foco atrapado, Escape y retorno del foco, como 016 CA12, CA13 y CA26) | — | — | — | se documentan como componentes exclusivos del sitio público (no sustituyen a `x-ui.*` en el panel) |
| Títulos del sitio público (`h-xl`, `h-lg`) | fluido 390–1440 | los de la propuesta | | sustituyen la fila "Título del sitio público" de Tipografía |
| Regla de fuentes | — | — | — | "Fuentes: del sistema" pasa a "del sistema en el panel; Inter Tight e Instrument Serif autoalojadas en el sitio público" |
| Tarjeta para compartir | fijo | 1200×630 | — | la define la última tarea (CA19) |

Los valores exactos de las fuentes, los títulos fluidos y cada opacidad se copian de
`nueva-landing/…/css/dentissa/tokens.css` y de las hojas de página; si un color de la propuesta no
cumple contraste donde pinta texto, se corrige en ese uso (refinado), no en el token.

## Movimiento

La propuesta trae unas 55 animaciones CSS y dos scripts de movimiento (`tooth3d.js`, `home.js`).
Se conservan, agrupadas en estas fichas; todas respetan ya `prefers-reduced-motion` en sus hojas.

### M-1 — Entradas de sección (`rise`, `reveal`, `reveal3d`)
- **Propósito y frecuencia:** evitar cambios bruscos al llegar a cada sección; una vez por visita.
- **Disparador:** entrada en pantalla; una sola vez. **Propiedades:** `transform`, `opacity`.
- **Reducido:** sin desplazamiento, visible desde el inicio · **Sin JavaScript:** todo visible (puerta `G-aparece`, `G-sin-js`) · **Táctil:** igual.

### M-2 — Recorrido 3D del diente (inicio)
- **Marca de escena:** el contenedor sticky del recorrido lleva `data-scene` (sin cambio visual), para que K4a lo mida desde que existe el diente (A34).
- **Propósito:** explicar los tratamientos que ofrece la clínica; ligado al scroll en un escenario sticky.
- **Disparador:** scroll; se repite. **Técnica:** WebGL sin librerías, cargado diferido para no bloquear el LCP.
- **Reducido o sin WebGL:** imagen estática y los cuatro textos visibles (CA9) · **Sin JavaScript:** textos completos · **Táctil:** mismo recorrido.
- **Coste:** ~13 kB de JS sin comprimir dentro del presupuesto de 60 kB de la página.

### M-3 — Carruseles y cintas (testimonios, certificaciones, marquesina)
- **Propósito:** mostrar varios elementos en poco espacio; se repiten.
- **Reglas:** con botón de pausa y pausa al tener el foco o fuera de pantalla (CA10, WCAG 2.2.2); sin movimiento automático en modo reducido.

### M-4 — Decorativos de fondo (orbes, partículas, rejilla, flotantes)
- **Propósito:** riqueza visual del sitio (peso 15 de `riqueza`); se repiten.
- **Reglas:** solo `transform`/`opacity`, `aria-hidden`, pausados fuera de pantalla y eliminados en modo reducido; nunca cruzan un texto.

### M-5 — Respuesta de controles y formularios (botones, preguntas frecuentes, compositor)
- **Propósito:** confirmar acciones; varias por visita, 100–250 ms. **Reducido:** sin desplazamiento.

## Accesibilidad

- **Contraste, toque y foco:** puertas `G-contraste`, `G-toque`, `G-foco` medidas en `/implement`.
  Riesgo conocido: textos sobre los degradados rosados y `--color-muted-soft` como texto.
- **Encabezados y estructura:** un `h1` por página (la propuesta ya lo cumple), `h2` por sección.
- **Teclado:** menú, carruseles (con pausa), selector y control del antes y después, compositor de
  WhatsApp, en orden de lectura (CA10).
- **Decorativos ocultos:** subrayados SVG, orbes, partículas y el lienzo del diente con `aria-hidden`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-12 | diseño | ¿Ciclo de candidatos para la 026? | No: se adopta la propuesta de `nueva-landing/` tal cual; solo se adapta donde lo exigen la spec, la constitución o una puerta. Acta con `exploration: none` | usuario |
| 2026-11-12 | diseño | Criterios de diseño | Aprobados; se miden sobre las páginas reales en `/implement` y `/review`, no sobre candidatos. Rúbrica sin puntuar | usuario |
| 2026-11-12 | brecha | Recursos | Se permite descargar las fuentes (OFL) y fotos de muestra de antes y después de bancos libres, con fuente y licencia | usuario |
| 2026-11-12 | supuesto | Origen de las fotos de la propuesta | Parecen generadas; se marcan `generada` y se listan para sustituirlas por fotos reales | agente |
| 2026-11-12 | cierre | Aprobación del acta | Aprobada | usuario |
| 2026-11-12 | refinado | `/analyze 026` A1: radios, altos y componentes fuera del sistema | Escala de radios y alto grande del sitio en el sistema 2.1.0 y componentes documentados; el aspecto no cambia más de 4 px en esquinas | usuario |
| 2026-11-12 | refinado | `/analyze 026` A5: K1 en las cuatro páginas | K1 solo en inicio y nosotros; criterios por página | usuario |
| 2026-11-12 | refinado | `/analyze 026` A10: logo y favicon | Los reales de la clínica | usuario |
| 2026-11-12 | refinado | `/analyze 026` A22: galería con 0, 1–2 y ≥ 3 imágenes | Estados definidos en Pantallas | agente (sobre la propuesta) |
| 2026-11-12 | cierre | Aprobación del acta tras `/analyze 026` ronda 1 | Aprobada | usuario |
| 2026-11-12 | refinado | `/analyze 026` A30: colores del JS | Tokens decorativos del esmalte; el JS lee los tokens al dibujar | usuario |
| 2026-11-12 | refinado | `/analyze 026` A32: estados por cantidad | Pest y revisión manual; `ui-audit` en CI con contenido | usuario |
| 2026-11-12 | refinado | `/analyze 026` A34, A36, A37, A44, A47 | Escena marcada; visor de certificaciones documentado; dirección, horario y mapa confirmados o fuera; textos alineados; `tokens.colors` = paleta del sistema | agente (sobre las decisiones del usuario) |
| 2026-11-12 | cierre | Aprobación del acta tras `/analyze 026` ronda 2 | Aprobada | usuario |
| 2026-11-13 | refinado | Estado vacío de promociones: una franja sencilla no le daba título a la sección | Se adopta el estado vacío de la propuesta revisada (`nueva-landing/dentissa-laravel/`, la anterior queda en `dentissa-laravel-old/`): sección con `h2` y dibujo; sus rosas `#e48aa5` y `#f7dbe3` pasan a `--color-primary-hover` y `--color-primary-tint-2` | usuario |
| 2026-11-13 | refinado | "Avísame de la próxima" prometía un aviso que la clínica no envía | Texto y botón pasan a "Pregúntanos por WhatsApp" con el mensaje "Hola, ¿tienen alguna promoción disponible?" | usuario |
| 2026-11-13 | refinado | Pregunta abierta del acordeón: `primary-deep` se veía oscuro y el rosa principal no llega a 4.5:1 con texto blanco (3.96:1) | Mezcla 45 % `primary` + 55 % `primary-deep` (≈ `#ba4168`): el rosa más claro cuyo texto blanco llega a 4.5:1 dentro de los recuadros (4.52:1) y 5.19:1 en la tarjeta; se probó texto en tinta sobre el rosa principal y se descartó | usuario |
