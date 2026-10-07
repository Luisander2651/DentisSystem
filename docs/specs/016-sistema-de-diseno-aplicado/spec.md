---
id: 016
slug: sistema-de-diseno-aplicado
status: approved
created: 2026-10-06
extends: [014]   # cambia cómo se presenta un acceso denegado en las pantallas web
---

# 016 · Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

## Problema
La interfaz de Dentissa salió de una maquetación rápida. Sus colores de marca están escritos a mano
en cada pantalla, hay dos rosas para la misma acción, varios textos y bordes no alcanzan el
contraste mínimo, muchos controles son demasiado pequeños para el dedo en el móvil, cada diálogo y
cada botón tiene su propio tamaño, la agenda se desborda en el móvil, la clínica no tiene logo ni
icono de pestaña, y quien entra a una pantalla que no le corresponde ve una página genérica en
inglés o datos en crudo. Afecta al staff, que usa el panel a diario, y a los pacientes y visitantes
del sitio público. Mientras no se corrija, la interfaz incumple la accesibilidad que exige la
constitución (WCAG 2.1 AA) y cada pantalla nueva repite el desorden.

Esta spec aplica la paleta nueva de la clínica, ordena la interfaz sobre un único sistema de diseño
y cierra la deuda `DS1`–`DS11`, `DS13` y `DS14` de [docs/design/system.md](../../design/system.md).
`DS12` queda fuera (ver "Fuera de alcance").

## Historias de usuario
- Como dueña de la clínica, quiero que toda la interfaz use los colores y el logo de mi marca para que el sistema se reconozca como de la clínica.
- Como persona con baja visión, quiero que los textos, los bordes de los campos y el indicador de foco tengan contraste suficiente para leer y usar cada pantalla.
- Como miembro del staff que navega con teclado o con lector de pantalla, quiero ver siempre dónde está el foco, que cada diálogo anuncie su título y que cada pantalla tenga un título principal, para trabajar sin ratón.
- Como miembro del staff que usa el móvil, quiero controles lo bastante grandes y una agenda que quepa en la pantalla para trabajar sin acercar ni desplazar de lado.
- Como miembro del staff, quiero que los botones, campos y diálogos se vean y se comporten igual en todas las pantallas, y que los textos estén bien escritos, para no dudar de lo que hace cada uno.
- Como usuario con sesión que entra a una pantalla que mi rol no puede ver o a una dirección que no existe, quiero una página clara con la marca y un camino de vuelta para no quedarme atascado.
- Como responsable del proyecto, quiero que el sistema de diseño quede como regla escrita para que las pantallas futuras no vuelvan a escribir colores y tamaños sueltos.

## Criterios de aceptación

### Paleta y sistema de diseño
- [ ] CA1 · Dado cualquier pantalla del sitio público, de acceso o del panel, cuando se revisa, entonces sus colores de marca son los de la paleta nueva (principal y secundario, ver "Decisiones") y no queda ningún uso de los rosas anteriores.
- [ ] CA2 · Dado el sistema de diseño, cuando se revisa la interfaz, entonces cada color sale de un valor con nombre declarado una sola vez en el sistema, y ninguna pantalla escribe un color suelto.
- [ ] CA3 · Dado cualquier pantalla, cuando muestra una acción principal, entonces usa el mismo color que las demás acciones principales y un único aspecto al pasar el cursor.
- [ ] CA4 · Dado un botón con fondo del color principal, cuando se muestra, entonces su texto es oscuro, no blanco.
- [ ] CA5 · Dado un enlace, el elemento activo del menú o un título que hoy va en rosa sobre fondo claro, cuando se muestra, entonces su texto es oscuro y el rosa aparece solo como acento (subrayado, borde, icono o fondo suave).

### Contraste y foco
- [ ] CA6 · Dado cualquier texto de cualquier pantalla (incluidos etiquetas, textos de ayuda, mensajes de error, estados de cita y el botón de WhatsApp), cuando se mide contra su fondo, entonces su contraste es de al menos 4.5:1, o de 3:1 si es texto grande.
- [ ] CA7 · Dado cualquier campo de formulario, cuando se mide su borde contra el fondo, entonces el contraste es de al menos 3:1.
- [ ] CA8 · Dado cualquier control interactivo, cuando recibe el foco con el teclado, entonces muestra el mismo indicador de foco que los demás controles, con contraste de al menos 3:1; ningún control queda sin indicador visible.

### Tamaños y móvil
- [ ] CA9 · Dado un ancho de 390 px, cuando se mide cualquier control táctil (botones, enlaces de acción, iconos, filtros, elementos de menú), entonces mide al menos 44 × 44 px.
- [ ] CA10 · Dado dos controles del mismo tipo (botón o campo) en pantallas distintas, cuando se comparan, entonces tienen la misma altura y el mismo radio, tomados del conjunto cerrado de tamaños que define el sistema de diseño.
- [ ] CA11 · Dado un ancho de 390 px, cuando se abre la agenda con o sin citas, entonces la página no se desplaza de lado y se pueden ver y abrir las citas de cualquier día del mes.

### Diálogos y estructura
- [ ] CA12 · Dado cualquier diálogo, cuando se abre, entonces un lector de pantalla anuncia su título, el foco queda dentro del diálogo mientras está abierto y, al cerrarlo, vuelve al control que lo abrió.
- [ ] CA13 · Dado un diálogo con más contenido que el alto de la ventana (a 390 × 844 y 1440 × 900 px), cuando se abre, entonces no supera el alto de la ventana: su contenido se desplaza dentro y sus acciones siguen alcanzables.
- [ ] CA14 · Dado cualquier pantalla, cuando se revisa su estructura, entonces tiene exactamente un título principal de página.

### Marca y textos
- [ ] CA15 · Dado el sitio público, las pantallas de acceso y el panel, cuando se muestran, entonces usan el mismo logo de la clínica, con un texto alternativo que la nombra, y la pestaña del navegador muestra un icono derivado de ese logo.
- [ ] CA16 · Dado cualquier texto del panel y de las pantallas de acceso, cuando se lee, entonces está escrito con la ortografía del español (acentos, eñes y signos de apertura), como ya lo está el sitio público.
- [ ] CA17 · Dado el inventario de interfaz sin uso o inalcanzable del sistema de diseño, cuando se revisa, entonces cada pieza está en uso o se retiró, y ninguna queda sin uso.

### Pantallas de error
- [ ] CA18 · (abuso) Como usuario con sesión, entro a una pantalla que mi rol no puede ver → veo una página "Sin permiso" con la marca, un mensaje en español y un botón que me lleva a mi inicio; la página no muestra ningún dato de la pantalla pedida y el acceso sigue rechazado como denegado. Sustituye a lo que se ve hoy: una página en negro con un mensaje en crudo (p. ej. un asistente que abre la agenda) o la página genérica en inglés (p. ej. un paciente que abre expedientes).
- [ ] CA19 · Dado cualquier persona, cuando entra a una dirección que no existe, entonces ve una página "No encontrada" con la marca y un enlace de vuelta: a su inicio si tiene sesión, al inicio del sitio público si no la tiene.
- [ ] CA20 · (abuso) Como usuario curioso, provoco un acceso denegado o una dirección inexistente en cualquier pantalla web → nunca recibo datos en crudo, texto en inglés ni detalles técnicos (nombres internos, trazas, rutas del servidor).
- [ ] CA21 · Dado un visitante sin sesión, cuando entra a una pantalla protegida, entonces se le lleva a iniciar sesión, como hoy.

### Móvil (390 px)
Hallazgos `M01`–`M16` de la revisión de las 65 pantallas a 390 px (ver "Diseño").
- [ ] CA25 · Dado un mes con citas a 390 px, cuando se mira el calendario, entonces cada día con citas muestra un texto completo y legible (como mínimo la hora de cada cita o el número de citas del día), nunca texto cortado a media palabra.
- [ ] CA26 · Dado cualquier diálogo a 390 px, cuando se abre, entonces todo su contenido y sus acciones quedan dentro del ancho de la pantalla, sin nada cortado por los lados.
- [ ] CA27 · Dado el expediente de un paciente a 390 px, cuando se consultan contacto, dirección, datos médicos e historial de citas, entonces todos los datos de cada registro se leen sin desplazar la tabla de lado y sin columnas ni mensajes cortados.
- [ ] CA28 · Dado cualquier pantalla a 390 px, cuando se revisa, entonces ningún elemento con información queda recortado por el borde de la pantalla ni truncado (incluidas las insignias de la imagen principal del inicio público y el selector de archivo de los formularios); los recortes de fondos decorativos no cuentan.
- [ ] CA29 · Dado cualquier campo de formulario a 390 px, cuando se mide su letra, entonces es de al menos 16 px; y ningún texto de ninguna pantalla mide menos de 12 px.
- [ ] CA30 · Dado un usuario de cualquier rol, cuando ve el panel, entonces los textos corresponden a su rol y están en español y sin términos técnicos: la insignia del encabezado no dice "administradores" a quien no lo es, el rol del paciente no se muestra en inglés, la etiqueta de la contraseña está en español y ningún texto menciona la API, el backend ni rutas internas.
- [ ] CA31 · Dado un miembro del staff a 390 px, cuando elige un paciente en expedientes clínicos, entonces el expediente de ese paciente queda a la vista sin tener que recorrer la lista de pacientes.
- [ ] CA32 · Dada la galería de gestión de contenido a 390 px, cuando se listan las imágenes, entonces cada tarjeta cabe en una pantalla (844 px de alto), se identifica por su descripción y no por el nombre interno del archivo, no muestra la dirección interna de la imagen y todo texto sobre una imagen cumple el contraste de CA6.
- [ ] CA33 · Dado cualquier listado del panel (pacientes, expedientes, tratamientos, contenido, usuarios) a 390 px, cuando se abre la pantalla, entonces el primer elemento del listado empieza dentro de la primera pantalla (844 px).
- [ ] CA34 · Dado cualquier pantalla del panel a 390 px, cuando se revisa, entonces no hay espacio vacío sin función: gestión de contenido tiene un solo encabezado de página, una tarjeta sin datos (como "Citas para hoy" sin citas) no mide más de 320 px de alto y la cabecera del panel no tiene hueco bajo su contenido.

### Lo que no cambia y lo que queda escrito
- [ ] CA22 · Dado cualquier pantalla a 1440 px, cuando se compara con su captura anterior, entonces conserva su composición: las mismas secciones en la misma disposición, las mismas acciones, la misma tipografía y los mismos flujos; solo cambian colores, tamaños, radios, foco, diálogos, logo y textos corregidos. A 390 px se conserva igual, salvo los cambios de disposición que piden CA11 y CA25–CA34.
- [ ] CA23 · Dado el sistema de diseño, cuando termina esta spec, entonces su documento y su vista describen la interfaz nueva, con capturas de todas las pantallas a 390 y 1440 px, y la deuda `DS1`–`DS11`, `DS13` y `DS14` figura como resuelta por esta spec.
- [ ] CA24 · Dada la constitución, cuando termina esta spec, entonces incluye el principio "La interfaz usa solo el sistema de diseño", con su forma de verificación, aprobado por el usuario como enmienda.

## Fuera de alcance
- Rediseño: no cambian la tipografía, la navegación ni los flujos, ni la disposición de las pantallas a 1440 px. A 390 px la disposición cambia solo donde lo piden CA11 y CA25–CA34 (decisión del usuario, 2026-10-06).
- Paginación o carga progresiva de los listados largos: CA32 y CA33 acortan cada elemento y lo que hay antes del listado, no el número de elementos.
- `DS12`, movimiento sin alternativa para quien pide menos movimiento: sigue en "Pendientes y deuda" del roadmap (decisión del usuario, 2026-10-04).
- Modo oscuro.
- Contadores del inicio del panel, que muestran "-": tendrán su propia spec (decisión del usuario, 2026-10-04).
- Portal del paciente: no tiene pantallas; no se crean aquí.
- Arreglo de la galería pública cuando hay un solo registro: va después de esta spec (roadmap → "Pendientes y deuda").
- Pantalla propia para errores inesperados del servidor y para sesión caducada: esta spec cubre solo "sin permiso" y "no encontrada".
- Cambios en quién puede ver o hacer qué: los permisos son los de la spec 014; aquí solo cambia cómo se presenta el rechazo.
- Registro de auditoría de los accesos denegados: objetivo 5 del roadmap.
- Crear o retocar el logo: se integra el que entrega la clínica, tal cual.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno nuevo. Las pantallas que ya muestran datos personales y de salud cambian de aspecto, no de contenido.
- Quién puede hacer qué: sin cambios respecto a la spec 014. Un usuario con sesión que entra a una pantalla que su rol no puede ver recibe la página "Sin permiso" (CA18); un visitante sin sesión va a iniciar sesión (CA21).
- Casos de abuso (cada uno con su criterio `CA` marcado `(abuso)`):
  - Como usuario con sesión sin permiso para una pantalla, intento abrirla por su dirección → se rechaza y veo "Sin permiso" sin ningún dato de esa pantalla (CA18).
  - Como usuario curioso, provoco rechazos y direcciones inexistentes para obtener detalles internos → las páginas de error no revelan nada técnico (CA20).

## Auditoría
Esta spec no emite eventos de auditoría: no cambia quién accede a qué, solo cómo se presenta el
rechazo. El registro de auditoría aún no existe y se construye en una spec aparte (objetivo 5 del
roadmap, [observability.md](../../observability.md)); cuando exista, los accesos denegados de CA18
quedarán registrados de forma centralizada, sin cambios en esta spec. Desviación de P14 que se
justifica en el Constitution Check de `/plan`, igual que en la spec 014.

## Diseño
Quien usa Dentissa debe sentir una sola aplicación, de la clínica, ordenada y legible: los mismos
colores, el mismo logo y los mismos controles en el sitio público, el acceso y el panel. Toca
**todas** las pantallas del inventario de [docs/design/system.md](../../design/system.md) y añade
dos nuevas: "Sin permiso" y "No encontrada".

No es un rediseño: se conserva la composición actual (CA22), salvo en el móvil donde la revisión a
390 px encontró pantallas rotas o difíciles de usar (CA25–CA34). `/plan` presenta la propuesta de uso
de la paleta nueva aplicada a pantallas reales, con el contraste de cada par comprobado, para que el
usuario la apruebe antes de implementar (roadmap, objetivo 6).

Revisión a 390 px (2026-10-06): las 65 pantallas y diálogos, con Chrome sin interfaz emulando un
móvil de 390 × 844 px. La agenda y sus diálogos son lo único que se desborda; el resto tiene
problemas de uso. Todos entran en esta spec, para no dejar deuda (decisión del usuario, 2026-10-06):

| Hallazgo | Gravedad | Criterios |
|---|---|---|
| M01 — los filtros de la agenda ensanchan la página a 480 px | alta | CA11 |
| M02 — las citas del calendario se leen "1.." o "0.." | alta | CA25 |
| M03 — los diálogos de la agenda salen cortados por la derecha | alta | CA26 |
| M04 — la tarjeta "Citas para hoy" mide unos 640 px estando vacía | baja | CA34 |
| M05 — tablas del expediente con columnas y mensajes cortados | media | CA27 |
| M06 — el expediente elegido aparece debajo de toda la lista de pacientes | media | CA31 |
| M07 — tarjetas de galería de unos 870 px, con nombre interno y dirección del archivo | media | CA32 |
| M08 — dos encabezados seguidos en gestión de contenido | baja | CA34 |
| M09 — diálogos más altos que la pantalla, con las acciones fuera de la vista | media | CA13 |
| M10 — insignias del inicio público recortadas | baja | CA28 |
| M11 — cabecera del panel con espacio vacío | baja | CA34 |
| M12 — el primer elemento de cada listado aparece a más de una pantalla del inicio | media | CA33 |
| M13 — selector de archivo truncado | baja | CA28 |
| M14 — rechazos y errores en crudo o en inglés | alta | CA18, CA19, CA20 |
| M15 — insignia "para administradores" en todos los roles; "PATIENT"; "Password" | media | CA30 |
| M16 — textos técnicos a la vista del staff | baja | CA30 |
| Campos con letra de 14 px y etiquetas de 10 y 11 px | media | CA29 |
| Controles táctiles menores de 44 px | media | CA9 |

Deuda de diseño que cierra:

| Deuda | Alcance | Criterios / motivo y destino |
|---|---|---|
| DS1 — sin valores con nombre para los colores | dentro | CA2 |
| DS2 — dos rosas y dos aspectos al pasar el cursor para la misma acción | dentro | CA1, CA3 |
| DS3 — contraste insuficiente en textos y bordes | dentro | CA4, CA5, CA6, CA7 |
| DS4 — controles táctiles menores de 44 px | dentro | CA9 |
| DS5 — sin escala de tamaños ni radios | dentro | CA10 |
| DS6 — tres estilos de foco, algunos invisibles | dentro | CA8 |
| DS7 — la agenda se desborda a 390 px | dentro | CA11 |
| DS8 — sin diálogo base, título sin enlazar, diálogos más altos que la ventana | dentro | CA12, CA13 |
| DS9 — sin logo en archivo ni icono de pestaña | dentro | CA15 |
| DS10 — rechazos de acceso sin pantalla propia | dentro | CA18, CA19, CA20 |
| DS11 — textos del panel sin acentos | dentro | CA16 |
| DS12 — movimiento sin alternativa | fuera | no obligatorio por ahora (decisión del usuario, 2026-10-04) → roadmap, "Pendientes y deuda" |
| DS13 — interfaz sin uso o inalcanzable | dentro | CA17 |
| DS14 — pantallas del panel sin título principal | dentro | CA14 |

## Requisitos no funcionales
- Accesibilidad: todas las pantallas cumplen WCAG 2.1 AA en lo que cubren CA6–CA9, CA12–CA14 y CA29 (constitución, "Restricciones").
- Anchos de comprobación: 390 y 1440 px, sin desplazamiento horizontal de página en ninguna pantalla.
- Sin regresiones: todo lo que el staff y los visitantes pueden hacer hoy sigue funcionando igual (CA22); la suite de tests existente pasa sin cambios en su comportamiento esperado.
- El logo y el icono de pestaña se sirven desde la propia aplicación, no desde terceros.

## Preguntas abiertas
- Ninguna.

## Decisiones
Cada brecha, contradicción o supuesto resuelto, con su origen (`shared/contract.md` → "Decisiones").

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-04 | diseño | ¿Cuál es la paleta de marca? | Principal `#d75078` y secundario `#f2b0a6`; sustituyen a `#E91E63` y `#B5114A`. | `docs/design/system.md` → Decisiones (usuario) |
| 2026-10-04 | brecha | La spec supera los 10 criterios y cubre varias capacidades. ¿Se divide? | No: una sola spec con toda la deuda salvo DS12. | `docs/roadmap.md` objetivo 6 (usuario) |
| 2026-10-04 | diseño | ¿Dónde se propone el principio "La interfaz usa solo el sistema de diseño"? | En esta spec, que crea los valores con nombre (CA24). | `docs/design/system.md` → Decisiones (usuario) |
| 2026-10-06 | contradicción | El texto blanco sobre `#d75078` da 3.96:1 y la constitución exige WCAG 2.1 AA (4.5:1). | Se conserva `#d75078` en los botones y el texto encima pasa a un color oscuro (CA4). | usuario |
| 2026-10-06 | brecha | El rosa como color de texto sobre fondo claro (enlaces, elemento activo, títulos de acceso) da el mismo 3.96:1. | Texto oscuro con el rosa como acento; no se añade un tercer rosa (CA5). | usuario |
| 2026-10-06 | brecha | No hay logo en archivo. ¿De dónde sale? | La clínica ya tiene uno, en versiones con fondo y sin fondo; se integra tal cual y de él sale el icono de pestaña (CA15). | usuario |
| 2026-10-06 | brecha | ¿Hasta dónde llega el cambio visual? | Paleta nueva y orden (tamaños, radios, foco, diálogos) conservando la composición; no es un rediseño (CA22). | usuario |
| 2026-10-06 | brecha | La revisión a 390 px encontró 16 problemas (M01–M16); 11 no estaban cubiertos y 6 de ellos piden cambiar la disposición, lo que choca con CA22. ¿Cuáles entran? | Todos, en esta spec, para no dejar deuda. CA22 conserva la composición a 1440 px y admite a 390 px los cambios de CA11 y CA25–CA34. | usuario |
| 2026-10-06 | brecha | ¿Qué ve quien entra con sesión a una pantalla que su rol no puede ver? | Una página "Sin permiso" propia con botón para volver a su inicio, no una redirección (CA18). | usuario |
| 2026-10-06 | contradicción | La spec 015 y el roadmap reservaban el número 016 para el monitoreo, y 016 es el siguiente libre. | Esta spec es la 016; el monitoreo pasa a ser la 017 y se corrige solo esa referencia en la spec 015 y en el roadmap. | usuario |
| 2026-10-06 | implícita | ¿En qué anchos se comprueba? | 390 y 1440 px. | `.ai/project.yaml` → `design.widths` |
| 2026-10-06 | implícita | ¿Quién registra los accesos denegados? | La spec del objetivo 5 del roadmap; esta no emite eventos de auditoría. | `docs/roadmap.md` objetivo 5; spec 014 → "Auditoría" |
| 2026-10-06 | implícita | ¿Cuál es el tamaño táctil mínimo? | 44 × 44 px a 390 px. | `docs/roadmap.md` objetivo 6; `docs/design/system.md` DS4 |
| 2026-10-06 | supuesto | ¿A dónde vuelve el enlace de "No encontrada"? | A su inicio si tiene sesión; al inicio del sitio público si no (CA19). | /specify |
| 2026-10-06 | supuesto | ¿Cuál es "su inicio" para cada rol? | El inicio del panel que cada rol ya ve hoy. | /specify |
| 2026-10-06 | supuesto | ¿Qué specs extiende? | Solo la 014 (presentación del acceso denegado en pantallas web). En las specs 001–012 cambia el aspecto, no ningún criterio. | /specify |
| 2026-10-06 | supuesto | DS13: ¿cada pieza sin uso se usa o se retira? | Lo decide el usuario pieza por pieza en `/plan`; la spec solo exige que ninguna quede sin uso (CA17). | /specify |
| 2026-10-06 | supuesto | ¿Qué umbrales miden los hallazgos del móvil? | Primer elemento de un listado dentro de 844 px (CA33); tarjeta de galería de 844 px como máximo (CA32); tarjeta sin datos de 320 px como máximo (CA34); letra de campo de 16 px y texto mínimo de 12 px (CA29). | /specify |
| 2026-10-06 | supuesto | CA32 quita de la tarjeta de galería el nombre interno y la dirección del archivo. ¿Se pierde algo? | No: son datos internos sin uso para quien gestiona el contenido; la imagen y su descripción la identifican. | /specify |
| 2026-10-06 | supuesto | ¿Cubre la pantalla de error inesperado del servidor? | No; solo "sin permiso" y "no encontrada", que son las que cita DS10. | /specify |

## Notas para /plan
- Logo entregado por el usuario: `storage/app/public/Logos_Melissa_Lopez/`, con las carpetas `con_fondo/` y `sin_fondo/`. Esa ruta está ignorada por git: hay que copiar los archivos elegidos a una ubicación versionada. Sustituye al anillo de `components/landing/nav.blade.php` y al escudo de `components/ui/sidebar.blade.php`; `public/favicon.ico` pesa 0 bytes.
- Tokens: declarar los colores en `@theme` de `resources/css/app.css` y sustituir los valores arbitrarios (unas 620 apariciones de 8 rosas). El color oscuro del texto sobre el principal y el gris de bordes de campo los fija el plan con su contraste calculado.
- Escala: tamaños y radios fijos en `x-ui.button` y `x-ui.input`, y usar esos componentes en las pantallas. Componente base de diálogo que reemplace los ~20 diálogos repetidos (`aria-labelledby` apunta hoy a un id inexistente).
- Errores: el middleware `only.admin` responde JSON en rutas web (`/agenda` con asistente); 403 y 404 usan la página por defecto de Laravel. Las respuestas de `/api/v1` no cambian (P4, spec 014).
- DS13: preguntar al usuario por `x-ui.table`, `welcome.blade.php` y la rama "ver y editar" de la tarjeta de cita (`resources/js/pages/agenda/index.js`).
- Título de página: `components/ui/page-hero.blade.php` pinta un `h2`.
- Propuesta de diseño en HTML dentro del repo, con la paleta aplicada a pantallas reales y el contraste de cada par (contrato → "Sistema de diseño"); `design` está en `skills.enabled`.
- "Cambios a incorporar al sistema": actualizar `docs/design/system.md` y `system.html`, regenerar las capturas (`/init --upgrade --redo-design` archiva el sistema anterior en `docs/design/history/`).
- Enmienda de la constitución: principio nuevo → versión MINOR (1.2.0), con entrada en "Enmiendas"; solo con aprobación del usuario. Su verificación debe ser por test o lint (contrato → Constitution Check).
- Revisión a 390 px: hecha con Chrome sin interfaz (móvil de 390 × 844, táctil); el reporte, las capturas y los scripts quedaron en una carpeta temporal fuera del repositorio (incluyen imágenes de la galería local, no se versionan). Las medidas que importan están en los criterios CA25–CA34 y en la tabla de "Diseño"; el plan debe repetir la medición como test o verificación manual.
- M01: la fila de filtros de la agenda no hace salto de línea (470 px). M03 es consecuencia de M01. M05: las tablas están en `components/records/*`, dentro de una caja con `overflow-x-auto`. M12: los contadores van uno por fila en móvil.
- Spec grande (34 criterios): conviene planear por fases entregables (tokens y paleta → componentes y escala → diálogos → agenda móvil → errores → logo y textos).
- Al cerrar (`/release`): anotar en el Historial de la spec 014 que el acceso denegado en pantallas web muestra la página "Sin permiso".

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-06 | Creación | Objetivo 6 del roadmap |
| 2026-10-06 | CA25–CA34 nuevos (móvil a 390 px) y CA22 acotado: conserva la composición a 1440 px y admite en el móvil los cambios de CA11 y CA25–CA34 | Revisión de las 65 pantallas a 390 px (M01–M16); decisión del usuario |
| 2026-10-06 | Aprobada | Aprobación del usuario |
