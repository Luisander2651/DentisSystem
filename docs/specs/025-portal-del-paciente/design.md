---
spec: 025-portal-del-paciente
status: approved         # aprobado por el usuario el 2026-11-09 (c3)
exploration: loop        # ciclo por criterios (decisión del usuario, 2026-11-09)
threshold: 80
floor: 3
budget: 5
accepted: c3
score: 0                 # c3 no pasó por la rúbrica: lo acepta el usuario (ver Decisiones)
system_version: 2.0.0
created: 2026-11-09
updated: 2026-11-09
---

# Diseño · 025 Portal del paciente

Hecho con `/design-spec 025` sobre `docs/design/system.md` 2.0.0. Criterios en la spec ("Criterios
de diseño") y en `design/`, un archivo por pantalla: `criterios.json` (inicio con próxima cita),
`criterios-vacio.json` (inicio sin cita), `criterios-citas.json` (Mis citas) y
`criterios-detalle.json` (detalle de una cita completada). Candidatos en `design/candidatos/`,
cuatro páginas por candidato: `cN.html`, `cN-vacio.html`, `cN-citas.html` y `cN-detalle.html`. Los
descartados se conservan. Las medidas se hicieron con `evaluate.mjs` y `playwright-core` 1.64.1,
instalado fuera del repositorio, sobre el Chrome del equipo. La rúbrica de c1 y c2 la puntuó un
evaluador independiente (perfil `critical`) que solo vio las capturas.

El ciclo aceptó c2 (82/100), pero el usuario lo descartó porque inventaba piezas y rompía la
composición del sistema. **c3** rehace la idea de c2 solo con el marcado de los componentes que ya
existen y la hoja de estilos compilada de la app; se hizo fuera del ciclo, sin rúbrica, y el usuario
lo aprobó el 2026-11-09. c3 añade una quinta página, `c3-detalle-qr.html` (detalle de una cita
asignada), que no tiene criterios propios.

## Candidatos

Puertas: 12 por página. KPIs: 3 por página (4 en Mis citas).

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 12/12 en las cuatro páginas | 3/3 · 3/3 · 4/4 · 3/3 | 66 | descartado: bajo el umbral y `plantilla` 2 (< 3). Hueco de unos 450 px entre los datos y el QR a 1440 px y saludo con más peso que la fecha; cinco botones "Ver detalle" iguales en el historial, sin distinguir la cita por venir; recetas con los datos sin etiquetar y en gris. En su primera medida falló `G-sin-js` por el KPI del menú (K5): ver Decisiones | `.ai/inspect/025-c1/report.md` (y `-vacio`, `-citas`, `-detalle`) |
| `c2` | 12/12 en las cuatro páginas | 3/3 · 3/3 · 4/4 · 3/3 | 82 | descartado por el usuario pese a superar el umbral: no reutiliza los componentes existentes (pase con talón, grupos "Por venir / Anteriores", receta etiquetada) y rompe la composición de `system.html` | `.ai/inspect/025-c2/report.md` (y `-vacio`, `-citas`, `-detalle`) |
| `c3` | 12/12 en las cuatro páginas | 3/3 · 3/3 · 4/4 · 3/3 | sin puntuar | aceptado por decisión del usuario, sin rúbrica (ver Decisiones) | `.ai/inspect/025-c3/report.md` (y `-vacio`, `-citas`, `-detalle`) |

## Resultado

**c3**, aceptado por el usuario el 2026-11-09 sin puntuación de rúbrica. Páginas:
`design/candidatos/c3.html`, `c3-vacio.html`, `c3-citas.html`, `c3-detalle.html` y
`c3-detalle-qr.html`.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| Las 12 puertas (`G-desborde`, `G-h1`, `G-red`, `G-contraste`, `G-toque`, `G-tokens`, `G-solapamiento`, `G-aparece`, `G-imagenes`, `G-foco`, `G-reducido`, `G-sin-js`) | Valores por defecto | Sin fallos a 390, 768 y 1440 px en las cuatro páginas con criterios | ✅ |
| K1 · Próxima cita y su QR en la primera pantalla (CA1) | "Próxima cita", "Jueves 12 de noviembre", "10:30", "Muestra este código en la recepción" y "Descargar QR" a la vista | 5/5 a la vista a 390, 768 y 1440 px (QR de 240 × 240 px) | ✅ |
| K2 · Estado vacío en la primera pantalla (CA2) | "Aún no tienes una cita agendada" y "Contacta a la clínica" a la vista | 2/2 a la vista a 390, 768 y 1440 px | ✅ |
| K3 · Historial con sus estados (CA3) | "Mis citas", los cuatro estados y "Ver" presentes; sin desborde | 6/6 presentes; tarjetas a 390 px y tabla a 768 y 1440 px sin desplazamiento horizontal | ✅ |
| K3b · De la más reciente a la más antigua (CA3) | 26/11/2026, 12/11/2026, 20/10/2026, 14/09/2026 | En orden | ✅ |
| K4 · Literales del detalle completado (CA5) | "Detalle de la cita", "Recomendaciones", "Recetas", dosis, frecuencia, duración, indicaciones y "Cerrar" | 8/8 presentes | ✅ |
| K6 · Estabilidad visual | CLS ≤ 0.1 | Dentro del objetivo en las cuatro páginas | ✅ |
| K7 · Títulos en dos líneas como máximo | ≤ 2 | ≤ 2 líneas | ✅ |
| K2 (historial vacío, CA4), K4 (QR solo en citas abiertas, sin recetas; CA6–CA8) y K5 (menú, CA10) | Los de la spec | No se miden en el candidato: dependen del estado de los datos o del menú móvil con JavaScript. Los verifica su tarea en `/implement` con `npm run test:ui` y tests | pendiente |

Rúbrica: c3 no se puntuó. La última puntuación del ciclo es la de c2 (82: jerarquía 4, primera
pantalla 5, sistema 4, lectura 4, plantilla 3), que no describe a c3.

## Imágenes de muestra

Ninguna. El QR de los candidatos es `generada`: codifica un UUID de prueba.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Inicio · con próxima cita | `candidatos/c3.html` a 390: saludo, tarjeta de la cita y debajo el QR con "Descargar QR"; después los accesos | 768: el QR a la izquierda de su texto y sus botones | 1440: la cita y su QR en 7 de 12 columnas y los accesos ("Mis citas", contacto) en 5 | CA1, CA9 |
| Inicio · sin próxima cita | `candidatos/c3-vacio.html` a 390 | 768 | 1440: accesos en la columna derecha | CA2 |
| Mis citas · con citas | `candidatos/c3-citas.html` a 390: una tarjeta por cita | 768: tabla | 1440: tabla | CA3 |
| Detalle · cita completada | `candidatos/c3-detalle.html` a 390 | 768 | 1440 | CA5 |
| Detalle · cita asignada | `candidatos/c3-detalle-qr.html`: el mismo diálogo con el bloque del QR | — | — | CA6 (sin criterios propios) |
| Mis citas · vacío; detalle · cancelada, sin recetas; menú | Sin candidato: mismas piezas y sus literales de la spec | — | — | CA4, CA7, CA8, CA10 |

## Componentes

Ningún componente nuevo: todo sale de piezas que ya existen.

- **Estructura:** `layouts/dashboard` con `x-ui.sidebar` en el rol `paciente` ("Inicio", "Mis citas"
  y "Cerrar sesión").
- **Inicio:** el saludo y las secciones de la rama del personal de `pages/dashboard` (título en
  negrita con la fecha de hoy, rejilla de 12 columnas); `x-dashboard.next-appointment` (franja rosa
  a la izquierda, hora de 40 px con su estado, fecha, tratamiento y doctor) bajo la etiqueta
  "Próxima cita" con su punto rosa; debajo, el bloque del QR del detalle de la 024 (marco
  `rounded-box border-field`, imagen de 240 × 240 px, aviso de error y "Descargar QR") con el texto
  "Muestra este código en la recepción." y el botón "Ver detalle". Accesos "Mis citas" y
  "¿Necesitas cambiar tu cita?" como secciones del tablero con su enlace subrayado en rosa.
- **Inicio vacío:** la misma sección con "Aún no tienes una cita agendada", su texto de apoyo y
  `x-ui.button` secundario "Contacta a la clínica".
- **Mis citas:** `x-ui.page-hero` (insignia "Mi cuenta", título "Mis citas" y descripción) y
  `records/appointments-history-table` tal cual: tarjetas por debajo de 768 px, tabla con Fecha,
  Hora, Tratamiento, Doctor, Estado y Acciones desde 768 px, botón "Ver" y fechas `dd/mm/aaaa`.
- **Detalle:** `x-ui.dialog` con las filas, "Recomendaciones" y las tarjetas de recetas de
  `calendar/view-appointment-modal` (medicamento y dosis en el título; duración y frecuencia en una
  línea; instrucciones debajo) y, en citas abiertas, su bloque del QR.
- **Avisos:** `x-ui.status`.

Para `/plan`: el texto vacío de `appointments-history-table` hoy dice "Este paciente aún no tiene
citas registradas."; el portal necesita "Aún no tienes citas registradas" (CA4), así que ese texto
pasa a ser configurable. El bloque del QR se usa en dos sitios (inicio y detalle): conviene
extraerlo del diálogo a un componente compartido sin cambiar su aspecto.

## Cambios a incorporar al sistema

Ninguno: ni tokens ni componentes nuevos. Solo se usan dos utilidades de disposición de Tailwind que
la hoja compilada aún no trae (`md:flex-row` y `md:items-center`), para poner el QR junto a su texto
desde 768 px.

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`,
  `G-foco`), sin fallos.
- **Encabezados y estructura:** un `h1` por pantalla (el saludo o "Mis citas"; en la app, el título
  del diálogo es `h2`); "Próxima cita", "Historial de citas", "Recomendaciones" y "Recetas" como
  encabezados de sección; el QR con el texto alternativo "Código QR de acceso de tu cita del
  <fecha>"; cada "Ver" nombra su cita con texto para lectores de pantalla ("Ver la cita del 12/11/2026").
  **Teclado:** menú → "Descargar QR" → "Ver detalle" → accesos; el diálogo atrapa el foco y lo
  devuelve al botón que lo abrió. **Decorativos ocultos:** íconos y el punto rosa con `aria-hidden`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-09 | diseño | ¿Ciclo de candidatos o `exploration: none`? | Ciclo completo; el plan se hace con lo que resulte. Sustituye a la decisión del 2026-11-08 de omitir `/design-spec` | usuario |
| 2026-11-09 | contradicción | K5 (menú del paciente) hacía fallar `G-sin-js` a 390 px: el menú móvil se abre con un botón y sin JavaScript no se ve | K5 sale de `criterios*.json`; CA10 lo verifica su tarea en `/implement` (test del menú y `npm run test:ui`) | usuario (confirmado el 2026-11-10, `/analyze 025` A6) |
| 2026-11-09 | supuesto | K1 pide el QR completo en la primera pantalla y `evaluate.mjs` no mide imágenes | Se mide con el texto que va justo debajo del QR ("Muestra este código en la recepción") y con "Descargar QR": si ambos están a la vista, el QR también | agente |
| 2026-11-09 | supuesto | Estados que no caben en una página (historial vacío, detalle de una cita abierta o cancelada, cita sin recetas) | Sin candidato propio: usan las mismas piezas y los literales de la spec; se verifican en `/implement` | agente |
| 2026-11-09 | diseño | Nombre del historial en el menú | "Mis citas" | agente (c2) |
| 2026-11-09 | diseño | Textos de apoyo que la spec no fija | "Muestra este código en la recepción." (corto, para que "Descargar QR" quepa en la primera pantalla a 390 px), "Cuando la clínica te agende una, aquí verás la fecha, la hora y tu código QR de acceso." y los accesos "Mis citas" y "¿Necesitas cambiar tu cita?" (a `/contacto`) | agente (c2, ajustado en c3) |
| 2026-11-09 | diseño | c2 superó el umbral (82), pero no reutiliza los componentes existentes y rompe la composición de `system.html` | Se descarta c2 y se acepta **c3**: la misma idea armada solo con componentes existentes, hecha fuera del ciclo y sin rúbrica. El usuario lo acepta sin puntuación, es decir, por debajo del umbral de 80 | usuario ("dale me gusta c3 ahora si") |
| 2026-11-09 | diseño | El título de "Mis citas" usaba el encabezado del saludo del tablero | Usa `x-ui.page-hero`, como las demás páginas interiores | usuario |
| 2026-11-09 | supuesto | Formatos heredados de los componentes: fechas `dd/mm/aaaa`, botón "Ver", frecuencia "3 veces al día" y recetas sin etiquetas por dato | Se conservan tal cual (los cuatro datos de la receta están presentes, CA5); `criterios-citas.json` y `criterios-detalle.json` se ajustaron a esos literales. El subtítulo "Historial de citas" de la tabla también se conserva | agente |
