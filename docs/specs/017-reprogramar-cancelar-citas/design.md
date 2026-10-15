---
spec: 017-reprogramar-cancelar-citas
status: approved         # aprobado por el usuario, 2026-10-15
exploration: none        # none: sin composiciones (motivo en "Dirección elegida")
system_version: 2.0.0
created: 2026-10-15
updated: 2026-10-15
---

# Diseño · 017 Reprogramar y cancelar citas

Hecho con `/design-spec 017` sobre el sistema `docs/design/system.md`. Sin composiciones.

## Dirección elegida

**Sin exploración.** La spec no crea pantallas ni cambia la composición de ninguna: ajusta las
acciones del pie del detalle de la cita, quita un campo del diálogo de edición y añade una
confirmación que sigue el patrón de confirmación que ya existe para eliminar
(`references/spec-mode.md` §1: "solo cambia datos o comportamiento dentro de componentes ya
diseñados").

- **Motivo:** las tres piezas ya están diseñadas y capturadas en el inventario del sistema
  ("Agenda, detalle de cita asignada", "Agenda, editar cita", "confirmar eliminación").
- **Vecino más parecido:** la confirmación de eliminar paciente → se diferencia en el texto y en
  que cancelar no borra la cita: la deja visible como "Cancelada".
- **Riesgo asumido:** tres botones en el pie del detalle se parten en dos filas a 390 px → el pie
  del diálogo ya se envuelve (`flex-wrap`) y cada botón mantiene 44 px de alto.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Detalle de cita asignada o reprogramada | captura `agenda-cita-detalle-pendiente-390.png` con el pie nuevo | no aplica (sin filas) | `agenda-cita-detalle-pendiente-1440.png` con el pie nuevo | CA7, CA9 |
| Detalle de cita completada o cancelada | `agenda-cita-detalle-390.png` (sin cambios: solo "Cerrar") | no aplica | `agenda-cita-detalle-1440.png` | CA8 |
| Reprogramar cita · reposo | `agenda-cita-editar-390.png` sin el campo Estado | no aplica | `agenda-cita-editar-1440.png` sin el campo Estado | CA1, CA9 |
| Reprogramar cita · con error | mensaje en la región de estado del diálogo | no aplica | ídem | CA2, CA3, CA5 |
| Agendar cita · con error de fecha pasada | mensaje en la región de estado del diálogo | no aplica | ídem | CA4, CA5 |
| Confirmar cancelación | `pacientes-eliminar-390.png` como referencia | no aplica | `pacientes-eliminar-1440.png` como referencia | CA7 |

## Composición y jerarquía

- **Detalle de la cita, pie (de izquierda a derecha):** "Cancelar cita" · "Reprogramar" ·
  "Cerrar", los tres `secondary`. Solo aparecen "Cancelar cita" y "Reprogramar" en citas asignadas
  o reprogramadas; en las demás queda solo "Cerrar", como hoy. No hay acción principal: ninguna de
  las tres es el camino esperado.
- **Diálogo "Reprogramar cita"** (antes "Editar cita"):
  - Texto de entrada: "Elige la nueva fecha y hora de la cita."
  - Campos, en este orden: Fecha, Hora y "Enviar recordatorio por WhatsApp". Desaparece el campo
    Estado.
  - El selector de fecha no ofrece días anteriores a hoy. Es solo una ayuda: el servidor decide
    (CA3).
  - Pie: "Cerrar" (`secondary`) y "Reprogramar" (`primary`). El botón de salir deja de llamarse
    "Cancelar" para no confundirse con cancelar la cita.
- **Diálogo de confirmación** (tamaño `sm`, mismo esqueleto que confirmar eliminación):
  - Título: "Cancelar cita".
  - Mensaje: "¿Seguro que quieres cancelar la cita de **<paciente>** el <fecha> a las <hora>?"
  - Advertencia en `danger`: "Esta acción no se puede deshacer."
  - Pie: "No, mantenerla" (`secondary`) y "Sí, cancelar la cita" (`danger`).
- **Avisos** (región de estado del marco, tras cerrar): "Cita reprogramada." y "Cita cancelada.".
  Los errores van en la región del diálogo abierto (CA5), con el texto del servidor de la spec.
- **Puntos de corte:** los del sistema. **Momento protagonista:** ninguno.

## Componentes

- **Del sistema:** `x-ui.dialog` (detalle, reprogramar, confirmación `size="sm"`), `x-ui.button`
  (`secondary`, `primary`, `danger`), región de estado del diálogo y del marco
  (`resources/js/ui/status.js`), campos de fecha y hora como están hoy.
- **Exclusivos de esta pantalla:** ninguno.
- **Adornos generados desde datos:** ninguno.

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Regla de uso "Rojo solo para eliminar y errores" → "Rojo solo para eliminar, cancelar una cita y errores; en cancelar, solo en el botón que la confirma" | fijo | — | — | Cancelar no se deshace y la insignia "Cancelada" ya es roja (decisión del usuario, 2026-10-15) |
| Token `--color-danger`, columna "Uso": "Eliminar, cancelar una cita y errores" | fijo | `#b91c1c` (sin cambio) | — | Coherencia con la regla |
| Inventario de vistas: "Agenda, editar cita" pasa a "Agenda, reprogramar cita"; nueva fila "Agenda, confirmar cancelación" | fijo | — | — | Pantallas que cambian de nombre o aparecen |

## Movimiento

Ninguno propio: los diálogos usan la apertura y el cierre del sistema.

## Accesibilidad

- **Contraste** (de la tabla Color del sistema):
  - `on-dark` sobre `danger`: 6.47:1.
  - `danger` sobre `surface`: 6.47:1.
  - `ink` sobre `surface`: 18.83:1.
  - `ink` sobre `primary`: 4.76:1.
  - `field` (borde) sobre `surface`: 3.50:1.
- **Encabezados y estructura:** cada diálogo tiene su título enlazado. El de confirmación es
  "Cancelar cita", y el nombre del paciente va en el mensaje, no en el título.
- **Teclado:**
  - Al abrir la confirmación, el foco va a "No, mantenerla", la opción segura. Escape equivale a
    "No, mantenerla".
  - Al cerrar la confirmación sin cancelar, el foco vuelve a "Cancelar cita".
  - Tras cancelar o reprogramar, el foco lo gestiona `dialog.js` como tras guardar en el resto de
    diálogos.
- **Sin animación:** no aplica.

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Botón rojo de "Cancelar" siempre visible en el detalle | Rojo solo en el botón que confirma; en reposo, botón normal |
| "Cancelar" con dos sentidos en el mismo flujo (cerrar el diálogo y anular la cita) | "Cerrar" o "No, mantenerla" para salir; "Cancelar cita" solo para anularla |
| Confirmación genérica "¿Estás seguro?" | El mensaje nombra al paciente, la fecha y la hora |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-15 | implícita | ¿Hace falta explorar composiciones? | No: sin pantallas nuevas ni recomposición | `references/spec-mode.md` §1 |
| 2026-10-15 | contradicción | La regla del sistema reserva el rojo para eliminar y errores; cancelar una cita es destructivo | Rojo solo en "Sí, cancelar la cita"; la regla se amplía en el sistema | usuario |
| 2026-10-15 | diseño | Texto del botón que sale del diálogo de reprogramar | "Cerrar" en lugar de "Cancelar", para que "Cancelar" no tenga dos sentidos | /design-spec |
| 2026-10-15 | diseño | Foco inicial de la confirmación | En la opción segura, "No, mantenerla" | /design-spec |
