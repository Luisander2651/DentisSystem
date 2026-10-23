---
spec: 020-tablero-de-inicio
status: approved         # draft | approved (solo el usuario aprueba) — aprobado por el usuario, 2026-10-23
exploration: loop
threshold: 80
floor: 3
budget: 5
accepted: c5
score: 80
system_version: 2.0.0
created: 2026-10-22
updated: 2026-10-23
---

# Diseño · 020 Tablero de inicio

Hecho con `/design-spec 020` sobre `docs/design/system.md` 2.0.0. Criterios en la spec ("Criterios
de diseño") y en `design/criterios.json` (administrador) y `design/criterios-doctor.json` (doctor).
Candidatos en `design/candidatos/`, uno por rol: `cN.html` y `cN-doctor.html`. Los descartados se
conservan. Las medidas se hicieron con `evaluate.mjs` y `playwright-core` 1.64.1, instalado fuera
del repositorio, sobre el Chrome del equipo. La rúbrica la puntuó un evaluador independiente
(perfil `critical`) que solo vio las capturas.

## Candidatos

Puertas: 12 por página. KPIs: 5 por página.

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 10/12 · doctor 11/12 | 5/5 · 5/5 | — | descartado: G-desborde a 390 px (39 px, botones que no partían línea) y G-solapamiento (texto para lectores del indicador de cambio) | `.ai/inspect/020-c1/report.md` |
| `c2` | 12/12 · 12/12 | 5/5 · 5/5 | 69 | descartado: la tarjeta rosa de la siguiente cita competía con "Nueva cita" y dejaba un hueco; pendientes como muro de botones; contadores con aspecto de Reportes | `.ai/inspect/020-c2/report.md` |
| `c3` | 12/12 · 12/12 | 5/5 · 5/5 | 77 | descartado: agenda de hoy completa con todo el detalle; pendientes densos y columnas desiguales; el mes con las píldoras de Reportes | `.ai/inspect/020-c3/report.md` |
| `c4` | 12/12 · 12/12 | 5/5 · 5/5 | 77 | descartado: a 390 px las citas pasadas en tres líneas apretadas; lo pendiente fuera de la primera pantalla del móvil; filas de pendientes largas | `.ai/inspect/020-c4/report.md` |
| `c5` | 12/12 · 12/12 | 5/5 · 5/5 | 80 | aceptado | `.ai/inspect/020-c5/report.md` |

## Resultado

**c5**, aceptado con 80/100 (umbral 80, ningún punto por debajo de 3). Páginas:
`design/candidatos/c5.html` (administrador) y `design/candidatos/c5-doctor.html` (doctor).

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-contraste, G-toque, G-foco, G-tokens, G-h1 | Puertas por defecto (4.5:1, 44 px, un `h1`, solo tokens) | sin fallos a 390, 768 y 1440 px, en las dos páginas | ✅ |
| G-solapamiento, G-aparece, G-imagenes, G-reducido, G-sin-js, G-red | Puertas por defecto | sin fallos, en las dos páginas | ✅ |
| K1 · "Siguiente cita" en la primera pantalla (CA2) | a la vista a 390 y 1440 px | a la vista en 390 y 1440 px, administrador y doctor | ✅ |
| K2 · Contadores de hoy en la primera pantalla (CA3) | los 4 a la vista a 1440 px | 4/4, administrador y doctor | ✅ |
| K3 · Literales del administrador (CA4–CA10) | 12 textos presentes | 12/12 | ✅ |
| K4 · Literales del doctor (CA5, CA8, CA9) | 4 textos presentes | 4/4 | ✅ |
| K5 · Títulos | ≤ 2 líneas | ≤ 2 líneas | ✅ |
| K6 · Estabilidad visual | CLS ≤ 0.1 | 0 | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 25 | 4 | `film-1440-00` · la siguiente cita, con la hora en grande y la barra rosa, domina; el orden siguiente cita → cómo va el día → agenda con "Ahora · 11:40" es evidente. No llega a 5: "Citas sin cerrar" son 10 filas con 20 botones iguales |
| Primera pantalla | 25 | 4 | `film-390-00` · a 390 px se ven el siguiente paciente, los cuatro contadores y los dos pendientes antes del pliegue. No llega a 5: "Nueva cita" a todo el ancho queda por encima de la siguiente cita |
| Coherencia con el sistema y la marca | 20 | 4 | `film-1440-00` · aviso informativo del sistema para los pendientes, estados con color y texto, una sola acción principal. No llega a 5: "Registrar seguimiento" se parte en dos líneas en las acciones rápidas a 1440 px y la rejilla del doctor deja una celda vacía |
| Lectura y ritmo | 15 | 4 | `film-1440-01` · el mes con cifra, etiqueta y comparación; pendientes agrupados por día. No llega a 5: a 390 px la línea de detalle de las citas pasadas empieza con "·" |
| Diferencia frente a una plantilla | 15 | 4 | `film-1440-00` · la línea "Ahora", la siguiente cita marcada en la propia agenda, "Después: …" y la barra del día con los colores de estado salen del contenido. Las listas de pendientes siguen siendo genéricas |

Problemas que el evaluador dejó abiertos. No impiden aceptar c5, pero la tarea que construye el
inicio los corrige sin tocar ningún criterio:
1. A 390 px, la línea de detalle de las citas pasadas no empieza con "·".
2. Las acciones rápidas no parten línea a 1440 px: una sola columna en la tarjeta. El doctor, con
   tres acciones, no deja celdas vacías.
3. A 390 px, las acciones de cada cita sin cerrar ocupan menos (en la misma fila que el paciente a
   partir de 768 px, como a 1440).

## Imágenes de muestra

Ninguna. La única imagen es el ícono de la marca (`public/images/brand/icon-64.png`, `data-origen="propia"`).

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Inicio del administrador, día con citas y pendientes | `candidatos/c5.html` a 390 | a 768 | a 1440 | CA1–CA10 |
| Inicio del doctor, sus citas y sus pendientes | `candidatos/c5-doctor.html` a 390 | a 768 | a 1440 | CA1–CA9 |
| Inicio del asistente | no diseñada aparte: la del administrador sin "Nuevo paciente", sin "Completar" ni "Registrar seguimiento", sin "Completadas sin seguimiento" y sin el mes | — | — | CA9, CA14 |
| Estados de carga, vacío y error por sección | no diseñados aparte: textos de la spec en el lugar de la lista, bloque de error de carga de `system.md` | — | — | CA1, CA4, CA7, CA12 |

## Componentes

**Del sistema:**
- `x-ui.button`: `primary` solo en "Nueva cita", en el encabezado del administrador. "Completar",
  "Ver detalle", "Cancelar cita" y "Registrar seguimiento" van en `secondary`, igual en todos los
  roles. El doctor no tiene acción principal en el encabezado.
- `x-ui.badge`: estados de las citas de hoy y de la siguiente cita.
- Lista o tarjeta: todas las secciones.
- Aviso informativo: resumen de pendientes en "Cómo va el día", con dos enlaces y su total.
- Enlace: "Abrir la agenda", "Ver reportes", "Ver en la agenda".
- `x-ui.status` y el bloque de error de carga de la pantalla, uno por sección (CA12).
- Confirmación de cancelar cita (017), diálogo de completar (018), diálogo de agendar y
  `x-ui.create-patient-modal`, reutilizados tal cual.

**Exclusivos de esta pantalla:**
- **Siguiente cita:** tarjeta `surface` con una franja de 6 px en `primary` a la izquierda. Encima,
  la etiqueta "Siguiente cita" (12 px en mayúsculas, `muted`, punto `primary`). Debajo, la hora
  (40 px / 700, cifras tabulares) con su insignia, el paciente (20 px / 700), el tratamiento y el
  doctor en `muted`, las acciones y la línea "Después: …" con las dos citas siguientes y cuántas
  más quedan.
- **Cómo va el día:** barra apilada de 12 px, `rounded-full`, decorativa (`aria-hidden`), con
  completadas en `success`, por atender en `warning` y canceladas en `danger`. Debajo, una `dl` de
  cuatro columnas separadas por `line`: cifra de 24 px / 700 con su muestra de color y etiqueta de
  12 px en `muted`.
- **Agenda de hoy:** las citas anteriores a la hora actual van compactas, en 14 px y `muted`, en
  una línea a partir de 768 px. La línea "Ahora · HH:MM" es una regla de 2 px en `primary`
  (`aria-hidden`). La siguiente cita lleva una marca de 4 px en `primary` en el borde izquierdo y
  "(siguiente)" solo para lectores de pantalla.
- **Pendientes agrupados por día:** rótulo del día (12 px en mayúsculas, `muted`, "Ayer · miércoles
  21") y filas con hora, paciente, tratamiento y doctor. Las acciones van a la derecha desde 768 px.
  Sin insignia de estado. "y N más sin cerrar" con "Ver en la agenda".
- **Octubre hasta hoy:** lista de tres filas con la cifra (24 px / 700), la etiqueta y la
  comparación en texto `muted` ("+6 respecto a septiembre (36)"), sin `x-ui.trend`, para no
  parecerse a "Reportes".

Orden a menos de 1024 px: siguiente cita, cómo va el día, acciones rápidas, agenda de hoy, mes,
citas sin cerrar y completadas sin seguimiento. Desde 1024 px: dos columnas (7/5). A la izquierda,
la siguiente cita y la agenda; a la derecha, el día, las acciones y el mes. Debajo, los pendientes
a todo el ancho.

## Cambios a incorporar al sistema

Ningún token nuevo. Se añaden al sistema estas piezas y reglas:

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Regla de color: barra del día | fijo | completadas `success`, por atender `warning`, canceladas `danger`, siempre con la cifra y la etiqueta al lado | — | Los colores de estado se usan como relleno no textual; el rojo sigue siendo solo de cancelar |
| Componente "Siguiente cita" | fijo | franja de 6 px `primary`, hora 40 px / 700 | — | El rosa como acento, no como fondo (evaluación de c2) |
| Componente "Línea de ahora" de una lista del día | fijo | regla de 2 px `primary` con "Ahora · HH:MM" en 12 px / 700 `muted` | — | Separa lo pasado de lo que viene |
| Inventario de vistas | — | Inicio del panel del administrador, el asistente y el doctor con datos, y los estados vacío y error | — | Sustituye las capturas actuales del inicio |

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (G-contraste, G-toque, G-foco) en las dos
  páginas a 390, 768 y 1440 px.
- **Encabezados y estructura:** un `h1` (saludo). Un `h2` por sección, que etiqueta su `section`
  con `aria-labelledby`. Los días de los pendientes van en `h3`. La agenda es una `ol`; los
  contadores y el mes, `dl` y `ul`. Las acciones rápidas van en un `nav` con nombre.
- **Teclado:** el orden sigue el del DOM, que es el visual a cada ancho (en móvil se reordena sin
  cambiar el DOM de cada columna). Los avisos de pendientes son enlaces a su sección.
- **Decorativos ocultos:** la barra del día, las muestras de color, la línea "Ahora" y los íconos
  llevan `aria-hidden`. "(siguiente)" va solo para lectores de pantalla.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-22 | diseño | ¿Con qué criterios se acepta? | K1–K6 y las puertas por defecto; dos páginas por candidato (administrador y doctor) | usuario |
| 2026-10-22 | supuesto | ¿Cómo se mide sin Playwright en el proyecto? | `playwright-core` 1.64.1 (8 días publicada, sin scripts de instalación) en la carpeta temporal de la sesión, con el Chrome del equipo; no cambia las dependencias | usuario |
| 2026-10-22 | diseño | ¿Qué candidato se acepta? | c5, 80/100 en la quinta vuelta, con los tres problemas abiertos para la tarea que construye el inicio | /design-spec 020 |
| 2026-10-22 | implícita | ¿El doctor tiene acción principal? | No en el encabezado; "Completar" va en `secondary` como en el administrador (una sola acción principal por pantalla, y la del administrador es "Nueva cita") | `docs/design/system.md` → Reglas de uso |
| 2026-10-23 | diseño | ¿Se aprueba el acta? | Aprobada; los candidatos descartados se conservan en `design/candidatos/` | usuario |
