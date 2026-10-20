---
spec: 019-reportes-mensuales
status: approved         # aprobado por el usuario, 2026-10-19
exploration: options     # none: sin composiciones (motivo en "Dirección elegida")
system_version: 2.0.0
created: 2026-10-19
updated: 2026-10-19
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

- **Del sistema:** `x-ui.page-hero`; `x-ui.input` como selector de mes; `x-ui.button`
  `primary` ("Descargar Excel", con giro mientras se prepara el archivo y `aria-disabled`);
  `x-ui.stat` para cada cifra, con el indicador de cambio debajo; tarjeta (`surface`, `card`,
  `line`, `shadow-sm`); aviso informativo (mes en curso); región de estado del marco
  (`status.js`) para "Demasiadas descargas." y el error de carga con "Intentar de nuevo"
  (`secondary`); menú lateral con la entrada "Reportes" solo para el administrador.
- **Nuevo en el sistema · indicador de cambio** (lo reutilizará el tablero de inicio): píldora
  `rounded-full` de 12 px / 700 con flecha (▲ sube, ▼ baja, = sin cambio) y texto
  ("+8 vs junio"), más un texto solo para lectores de pantalla ("favorable", "desfavorable").
  - Favorable: `success` sobre `success-soft`.
  - Desfavorable: `warning` sobre `warning-soft`.
  - Sin cambio, sin datos o neutro: `muted` sobre `surface` con borde `line`.

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
| Componente "Indicador de cambio" (`x-ui.trend` o el nombre que fije `/plan`) | fijo | píldora 12 px / 700, `rounded-full` | — | Se comparte con el tablero de inicio |
| Pareja de contraste `--color-muted` sobre `--color-surface` para el indicador neutro | fijo | 6.20:1 (ya declarada) | — | Sin token nuevo |

Ningún token nuevo. La intensidad de la semana usa `primary` con opacidad en fondos, que el
sistema ya permite.

## Movimiento

Ninguno. Al cambiar de mes las cifras se sustituyen sin animación; la única señal de espera es el
giro del botón de descarga, que ya está en el sistema.

## Accesibilidad

- **Contraste** (del sistema): `success` sobre `success-soft` 5.21:1; `warning` sobre
  `warning-soft` 4.84:1; `muted` sobre `surface` 6.20:1; tinta sobre `primary` 4.76:1 (y más
  sobre `primary` al 25 % y al 55 %); `muted` sobre `canvas` 5.93:1.
- **Encabezados y estructura:** un `h1` ("Reportes"); un `h2` por tarjeta; las cifras como lista
  de términos (`dl`); la semana como `table` con `caption` (oculto a la vista), `th scope` en días
  y horas y fila de totales en `tfoot`; tratamientos y staff como `ol`.
- **Teclado:** selector de mes → "Descargar Excel" → contenido. Nada más es interactivo.
- **Color nunca solo:** el indicador de cambio lleva flecha, número y texto para lectores de
  pantalla; la semana lleva la cifra en cada casilla y contorno en el máximo.
- **Decorativos ocultos:** barras, leyenda de la semana y clave de colores (`aria-hidden`).
- **Estados:** la región de estado del marco está siempre montada; el aviso de límite y el error
  usan `role="alert"` con qué pasó, que no se perdió nada y cómo reintentar.

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
| 2026-10-19 | supuesto | ¿Qué franjas de hora se muestran en la semana? | De la primera a la última con citas del mes (no hay horario de la clínica) | agente |
| 2026-10-19 | supuesto | ¿Qué se destaca si hay empate en el máximo? | Todas las franjas o días empatados | agente |
| 2026-10-19 | supuesto | Números en el reporte | Punto decimal (`28.6 %`), como se escribe en México | agente |
| 2026-10-19 | supuesto | ¿El Excel lleva los colores del cambio? | No: la diferencia con signo y sin color | agente |
| 2026-10-19 | diseño | ¿Se aprueba el diseño? | Aprobado (composición C) | usuario |
