---
spec: 019-reportes-mensuales
status: approved         # reaprobado por el usuario, 2026-10-20, tras /design-spec --fix A33
exploration: options     # none: sin composiciones (motivo en "Dirección elegida")
system_version: 2.0.0
created: 2026-10-19
updated: 2026-10-20
---

# Diseño · 019 Reportes mensuales

Hecho con `/design-spec 019` sobre el sistema `docs/design/system.md`. Composiciones en
`design/opcion-<a|b|c>.html` (las descartadas se conservan). Son maquetas autocontenidas que
imitan los componentes con los tokens del sistema y llevan el ícono oficial; la pantalla real se
construye con los componentes `x-ui.*`, el menú lateral y `x-ui.brand` del código, no con sus
clases `c-*`.

## Dirección elegida

**Composición C · "La semana del mes, con el parte"**, pedida por el usuario como combinación de
[A · El parte del mes](design/opcion-a.html) y [B · La semana del mes](design/opcion-b.html):
de B, la columna de cifras y la tabla de hora por día de la semana; de A, las tarjetas de
tratamientos más solicitados y de carga por miembro del staff, con sus barras. Además, cada cifra
comparable lleva un indicador de cambio respecto al mes anterior. Vista: [opcion-c.html](design/opcion-c.html).

El mes doblado en una sola semana: la tabla de hora por día dice de un vistazo cuándo se llena la
clínica, y la columna de cifras dice cuánto y si fue mejor o peor que el mes anterior.

- **Motivo:** el usuario quiere ver de un golpe las cifras con su tendencia y la demanda de la
  semana, y conservar el ranking de tratamientos y staff con barras, que se lee mejor que una tabla.
- **Vecino más parecido:** Agenda (encabezado, contadores en fila y calendario mensual) → aquí la
  rejilla es de hora × día de la semana, sin fechas ni pacientes, y las cifras van agrupadas en una
  columna, cada una con su indicador de cambio.
- **Riesgo asumido:** la tabla con intensidad de color roza el cliché del "mapa de calor" →
  cifra en cada casilla, leyenda, totales por hora y por día en los márgenes (CA7) y contorno en el
  máximo además del color. Los indicadores de cambio añaden un uso nuevo del verde y del ámbar →
  se escribe primero en el sistema ("Cambios a incorporar al sistema").

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Reportes · mes con citas (julio de 2026) | `opcion-c.html#reporte-m` | `opcion-c.html#reporte-i` | `opcion-c.html#reporte-d` | CA1–CA8 |
| Reportes · mes sin citas | `opcion-c.html#vacio-m` | igual que el menor, a todo el ancho | ídem | CA3 |
| Reportes · mes en curso | `opcion-c.html#encurso-m` | ídem | ídem | CA9 |
| Reportes · descarga en curso y límite de descargas | `opcion-c.html#descarga-m` | ídem | ídem | CA8, CA14 |
| Reportes · error al cargar | `opcion-c.html#error-m` | ídem | ídem | — |
| Menú lateral del administrador con "Reportes" | `opcion-c.html#reporte-m` (plegado) | — | `opcion-c.html#reporte-d` | CA1, CA10 |
| Archivo de Excel, hoja Resumen | `opcion-c.html#excel` | — | — | CA8, CA12, CA13 |

## Composición y jerarquía

- **Orden de lectura:**
  1. Encabezado (`x-ui.page-hero`): "Reportes", su descripción, el selector "Mes" y la acción
     principal "Descargar Excel" a la derecha (a 390 px, debajo y a todo el ancho).
  2. Aviso "Mes en curso: datos hasta hoy, <fecha>." solo en el mes en curso.
  3. "<Mes> en cifras": rejilla de contadores de dos columnas. Citas del mes y pacientes activos
     ocupan las dos columnas; el resto, una. Orden: citas del mes; completadas · canceladas;
     sin cerrar · pacientes nuevos; reprogramadas pendientes · asignadas pendientes;
     asistencia · cancelación; pacientes activos. Debajo, la clave de colores y la nota
     "Sin cerrar".
  4. "Cuándo llegan las citas": tabla de hora por día de la semana.
  5. "Tratamientos más solicitados" y "Carga por miembro del staff": listas ordenadas con barra.
- **Proporciones:** desde 880 px, cifras 5 : 7 semana y luego tratamientos 7 : 5 staff (la lista
  de tratamientos suele ser la más larga). Por debajo, una columna en el orden de lectura.
- **Puntos de corte:** los del sistema (menú lateral desde 1024 px) y uno propio de 880 px para
  pasar a dos columnas: a 768 px las dos columnas dejarían la semana en celdas de menos de 30 px.
- **Momento protagonista:** ninguno. Es una pantalla de lectura.

## Componentes

- **Del sistema:** `x-ui.page-hero`; `x-ui.button` `primary` ("Descargar Excel", con giro
  mientras se prepara el archivo y `aria-disabled`); tarjeta (`surface`, `card`, `line`,
  `shadow-sm`); aviso informativo (mes en curso); región de estado del marco (`status.js`) para el
  límite de descargas, "Preparando el archivo…" y el anuncio del error de carga; menú lateral con
  la entrada "Reportes" solo para el administrador.
- **Nuevo en el sistema · error de carga de la pantalla** (A33, decisión del usuario,
  2026-10-20): si el reporte no carga, un bloque (sin `role="alert"`, A48) ocupa el lugar de las cifras, la
  semana y las listas (`#error-m` de la composición C). Lleva borde de 1 px `danger`, fondo
  `danger-soft`, radio `box`, relleno de 12 × 16 px, texto de 14 px en tinta con el título en
  `danger` y 700 ("No se pudo cargar el reporte."), la explicación ("No se perdió ningún dato.
  Revisa tu conexión e inténtalo de nuevo.") y el botón `secondary` "Intentar de nuevo" dentro.
  No caduca: se queda hasta que se reintenta o se elige otro mes. Al reintentar, el bloque
  desaparece y la región de estado anuncia "Cargando el reporte…". La región de estado del marco
  solo anuncia el texto del error, sin botón y con su caducidad habitual de 12 s. El foco no se
  mueve al aparecer el bloque; "Intentar de nuevo" queda en el orden de tabulación justo después
  de "Descargar Excel".
- **Nuevo en el sistema · selector** (`x-ui.select`, A4): el `<select>` que hoy se escribe a mano
  en los diálogos, como componente con las mismas medidas de `x-ui.input`: 44 px de alto, letra
  `text-control` (16 px) en tinta, borde `field`, radio `control`, etiqueta visible asociada
  (14 px / 600), texto de ayuda en `muted` y error en `danger` enlazado con `aria-describedby`. Aquí
  es el selector "Mes", con los meses del más reciente (el mes en curso) al más antiguo (enero de
  2020) y etiquetas como "Julio de 2026".
- **Nuevo en el sistema · contador comparado** (variante de `x-ui.stat`, A11): para cifras con
  indicador de cambio. Va dentro de una lista de términos (`dl`) en rejilla de **dos columnas** a
  cualquier ancho, y cada contador es un par `dt` (etiqueta en mayúsculas de 12 px en `muted`) /
  `dd` (cifra de 20 px / 700 y debajo el indicador). La opción `wide` ocupa las dos columnas. La
  variante compacta actual (tres por fila a 390 px) no cambia.
- **Nuevo en el sistema · indicador de cambio** (`x-ui.trend`; lo reutilizará el tablero de
  inicio): píldora `rounded-full` de 12 px / 700, que puede partirse en dos líneas. Textos únicos
  (A13), con `<mes anterior>` en minúsculas:

  | Caso | Flecha | Texto visible | Texto solo para lectores de pantalla | Colores |
  |---|---|---|---|---|
  | Diferencia favorable | ▲ o ▼ según suba o baje | `+8 vs junio` · `−8 vs julio` | ` (favorable)` | `success` sobre `success-soft` |
  | Diferencia desfavorable | ▲ o ▼ | `+1 vs junio` | ` (desfavorable)` | `warning` sobre `warning-soft` |
  | Diferencia en un indicador neutro (pendientes) | ▲ o ▼ | `+1 vs junio` | — | `muted` sobre `surface`, borde `line` |
  | Diferencia 0 | = | `sin cambio` | ` (sin cambio)` | `muted` sobre `surface`, borde `line` |
  | El mes anterior no tiene dato (tasa sin datos) | — | `junio: Sin datos` | ` (sin comparación)` | `muted` sobre `surface`, borde `line` |
  | El mes elegido no tiene dato y el anterior sí | — | `julio: 28.6 %` como texto en `muted`, sin píldora | — | — |

  - La diferencia de las tasas se escribe en puntos con un decimal: `+3.2 pts vs junio`.
  - El signo negativo es `−` (U+2212). La flecha es decorativa (`aria-hidden`).
  - Pacientes activos no lleva indicador.

  El color lo decide el significado del indicador, no la dirección:

  | Indicador | Subir es |
  |---|---|
  | Citas del mes, completadas, asistencia, pacientes nuevos | favorable |
  | Canceladas, sin cerrar, cancelación | desfavorable |
  | Reprogramadas pendientes, asignadas pendientes | neutro (siempre gris) |
  | Pacientes activos | sin comparación |
- **Exclusivos de esta pantalla:**
  - **Semana del mes:** `<table>` con encabezados de día (abreviados con `abbr`: Lun… Dom), una
    fila por franja de una hora desde la primera hasta la última con citas del mes, columna y fila
    de totales. Celdas de 40 px de alto, radio `control`, separación de 2 px, cifra en tinta 14 px
    / 700. Intensidad de un solo tono: 0 → `surface` con borde `line` y cifra en `muted`; 1 →
    `primary` al 25 %; 2 → `primary` al 55 %; 3 o más → `primary`. El total máximo por hora y por
    día lleva contorno interior de 2 px en `ink` (empate: todos). Leyenda debajo, oculta a
    lectores de pantalla porque las cifras ya están en la tabla.
  - **Lista ordenada con barra** (tratamientos y staff): nombre 14 px / 600 y valor 14 px / 700
    a la derecha; debajo, barra de 8 px `rounded-full` con fondo `line` y relleno `primary`
    proporcional al mayor de la lista. El primero lleva " · más citas". La barra es decorativa
    (`aria-hidden`); la cifra está en el texto.
- **Adornos generados desde datos:** las barras y la intensidad salen de las cifras; el contorno,
  del máximo. Ninguno lleva información que no esté en el texto.

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Regla de color: el verde (`success`) y el ámbar (`warning`) también marcan un cambio favorable o desfavorable en un indicador, siempre con flecha y texto; el rojo sigue solo para eliminar, cancelar una cita y errores | fijo | — | — | Indicadores de cambio de la 019 (decisión del usuario, 2026-10-19) |
| Componente "Indicador de cambio" (`x-ui.trend`), con la tabla de textos de "Componentes" | fijo | píldora 12 px / 700, `rounded-full` | — | Se comparte con el tablero de inicio |
| Componente "Selector" (`x-ui.select`) | fijo | 44 px, `text-control`, borde `field`, radio `control`, etiqueta visible | igual | Selector "Mes" (A4); los `<select>` de los diálogos podrán pasar a él |
| Variante "Contador comparado" de `x-ui.stat`: `dt`/`dd` dentro de un `dl` de dos columnas, con indicador bajo la cifra y opción `wide` | fijo | dos columnas | dos columnas | Cifras con tendencia (A11); la variante compacta sigue a tres por fila |
| Región de estado: además de los textos por tipo de error, una pantalla puede pedir un texto propio para un caso, escrito en el código y nunca tomado de la respuesta: aquí el 429 de la descarga dice "Demasiadas descargas. Espera un momento e inténtalo de nuevo." en lugar del genérico de `busy` | fijo | — | — | CA14 (A12, decisión del usuario, 2026-10-20) |
| Estados de pantalla · error: cuando falla la carga de toda la pantalla (no un guardado), un bloque de error en la página, persistente y con "Intentar de nuevo"; la región de estado sigue anunciando el texto | fijo | borde `danger`, fondo `danger-soft`, radio `box`, 12 × 16 px, 14 px | igual | Error de carga del reporte (A33, decisión del usuario, 2026-10-20); la regla actual solo prevé la región de estado |
| Pareja de contraste `--color-muted` sobre `--color-surface` para el indicador neutro | fijo | 6.20:1 (ya declarada) | — | Sin token nuevo |

Ningún token nuevo. La intensidad de la semana usa `primary` con opacidad en fondos, que el
sistema ya permite.

## Movimiento

Ninguno. Al cambiar de mes las cifras se sustituyen sin animación; la única señal de espera es el
giro del botón de descarga, que ya está en el sistema.

## Accesibilidad

- **Contraste** (del sistema): `success` sobre `success-soft` 5.21:1; `warning` sobre
  `warning-soft` 4.84:1; `muted` sobre `surface` 6.20:1; tinta sobre `primary` 4.76:1 (y más
  sobre `primary` al 25 % y al 55 %); `muted` sobre `canvas` 5.93:1; `danger` sobre
  `danger-soft` 5.91:1 y tinta sobre `danger-soft` 17.21:1 (bloque de error de carga).
- **Encabezados y estructura:** un `h1` ("Reportes"); un `h2` por tarjeta; las cifras como lista
  de términos (`dl`); la semana como `table` con `caption` (oculto a la vista), `th scope` en días
  y horas y fila de totales en `tfoot`; tratamientos y staff como `ol`.
- **Teclado:** selector de mes → "Descargar Excel" → contenido; con el error de carga, "Intentar de
  nuevo" va después de "Descargar Excel". Nada más es interactivo.
- **Color nunca solo:** el indicador de cambio lleva flecha, número y texto para lectores de
  pantalla; la semana lleva la cifra en cada casilla y contorno en el máximo.
- **Decorativos ocultos:** barras, leyenda de la semana y clave de colores (`aria-hidden`).
- **Estados:** la región de estado del marco está siempre montada; el aviso de límite y el error
  usan `role="alert"` con qué pasó, que no se perdió nada y cómo reintentar; solo la región de
  estado anuncia, una vez (A48). El error de carga
  además se pinta como bloque persistente en la página con "Intentar de nuevo"; el anuncio de la
  región caduca y el bloque no.

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Fila de contadores igual a la de la Agenda | Columna de cifras 5 : 7 junto a la semana, cada una con su tendencia |
| Verde = sube, rojo = baja | El color dice si el cambio es bueno o malo para la clínica; el rojo queda reservado |
| Mapa de calor sin números | Cifra en cada casilla, totales en los márgenes y leyenda |
| Gráficas de pastel para tratamientos | Lista ordenada con barra fina y cifra a la derecha |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-19 | diseño | ¿Qué composición? | C: cifras y semana de B, tratamientos y staff de A, con indicador de cambio | usuario |
| 2026-10-19 | diseño | ¿Qué significa el color del cambio? | Si es favorable o desfavorable para la clínica, no la dirección | usuario |
| 2026-10-19 | contradicción | El sistema reserva el rojo para eliminar, cancelar y errores, y el verde para "Completada" y "Activo" | Desfavorable en ámbar, favorable en verde, neutro en gris; se amplía la regla de color del sistema y el rojo sigue reservado | usuario (`docs/design/system.md` → Reglas de uso) |
| 2026-10-19 | supuesto | ¿Qué franjas de hora se muestran en la semana? | De la primera a la última con citas del mes (no hay horario de la clínica) | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Qué se destaca si hay empate en el máximo? | Todas las franjas o días empatados | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | Números en el reporte | Punto decimal (`28.6 %`), como se escribe en México | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿El Excel lleva los colores del cambio? | No: la diferencia con signo y sin color | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | diseño | ¿Se aprueba el diseño? | Aprobado (composición C) | usuario |
| 2026-10-20 | refinado | El selector de mes se pedía como `x-ui.input`, que solo pinta `<input>` | Componente nuevo `x-ui.select` con las medidas de `x-ui.input`; se añade a "Cambios a incorporar al sistema" | usuario (`/analyze` A4) |
| 2026-10-20 | refinado | `x-ui.stat` no usa `dl` y es de tres por fila, pero el diseño pide dos columnas con indicador | Variante "contador comparado" de `x-ui.stat` (`dt`/`dd` en un `dl` de dos columnas, opción `wide`) | usuario (`/analyze` A11) |
| 2026-10-20 | refinado | El sistema solo muestra textos propios por tipo de error y el 429 ya tiene uno genérico | El 429 de la descarga muestra el texto de CA14, escrito en el código; la regla se registra en el sistema | usuario (`/analyze` A12) |
| 2026-10-20 | refinado | El texto del indicador tenía tres redacciones | Una sola tabla de textos (la de la composición C: "+8 vs junio", "sin cambio", "junio: Sin datos") y diferencia de tasas en puntos (`+3.2 pts vs junio`) | `/analyze` A13 · composición C |
| 2026-10-20 | diseño | ¿Se aprueba el diseño refinado? | Aprobado | usuario |
| 2026-10-20 | refinado | La región de estado solo escribe texto y borra los errores a los 12 s, pero la composición C pinta el error de carga como bloque con "Intentar de nuevo" | Bloque `role="alert"` persistente en la página en lugar del reporte, con el botón dentro; la región de estado solo anuncia el texto; se amplía la regla "Estados de pantalla" del sistema | usuario (`/analyze` A33) |
| 2026-10-20 | diseño | ¿Se aprueba el bloque de error de carga? | Aprobado | usuario |
| 2026-10-20 | refinado | El bloque de error y la región de estado anunciarían el mismo texto dos veces | Solo la región de estado anuncia; el bloque es contenido visible sin `role="alert"` | usuario (`/analyze` A48) |
