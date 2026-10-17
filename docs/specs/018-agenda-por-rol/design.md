---
spec: 018-agenda-por-rol
status: approved         # aprobado por el usuario, 2026-10-17
exploration: none        # none: sin composiciones (motivo en "Dirección elegida")
system_version: 2.0.0
created: 2026-10-17
updated: 2026-10-17
---

# Diseño · 018 Agenda por rol

Hecho con `/design-spec 018` sobre el sistema `docs/design/system.md`. Sin composiciones.

## Dirección elegida

**Sin exploración.** La spec no crea pantallas ni cambia la composición de ninguna. Abre la agenda,
el detalle de la cita, la sección "Registro clínico" y el diálogo de completar, que ya existen, a
dos roles más, y muestra a cada rol solo las acciones que puede usar (`references/spec-mode.md` §1:
"solo cambia datos o comportamiento dentro de componentes ya diseñados").

- **Motivo:** todas las piezas están en el inventario del sistema y capturadas como administrador
  ("Agenda, mes con citas", "Agenda, detalle de cita", "Agenda, detalle de cita asignada", "Agenda,
  completar cita").
- **Vecino más parecido:** la misma agenda vista por el administrador → se diferencia solo en qué
  botones aparecen y, para el doctor, en qué citas aparecen.
- **Riesgo asumido:** el pie del detalle del doctor queda solo con "Cerrar" y la tarjeta del
  asistente sin "Completar" → los botones ocultos no ocupan sitio, así que no quedan huecos; el
  doctor completa desde la tarjeta, como hoy el administrador.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Agenda, mes con citas · asistente | capturas `agenda-asistente-390.png` (nueva; igual a la del administrador) | no aplica (sin filas) | `agenda-asistente-1440.png` | CA1 |
| Agenda, mes con citas · doctor (solo sus citas, sin "Agendar nueva cita") | `agenda-doctor-390.png` (nueva) | no aplica | `agenda-doctor-1440.png` | CA5, CA8 |
| Agenda, citas para hoy vacías · doctor | `agenda-doctor-hoy-sin-citas-390.png` (nueva) | no aplica | ídem a 1440 px | CA5 |
| Detalle de cita asignada · asistente ("Cancelar cita", "Reprogramar", "Cerrar") | `agenda-cita-detalle-pendiente-asistente-390.png` (nueva) | no aplica | ídem a 1440 px | CA3, CA4 |
| Detalle de cita asignada · doctor (solo "Cerrar") y su tarjeta con "Completar" | `agenda-cita-detalle-pendiente-doctor-390.png` (nueva) | no aplica | ídem a 1440 px | CA6 |
| Detalle de cita completada · doctor, con registro clínico | `agenda-cita-detalle-doctor-390.png` (nueva) | no aplica | ídem a 1440 px | CA9 |
| Detalle de cita completada · doctor, sin registro clínico | mensaje dentro de "Registro clínico" | no aplica | ídem | CA9 |
| Detalle de cita completada · asistente (sin "Registro clínico") | `agenda-cita-detalle-asistente-390.png` (nueva) | no aplica | ídem a 1440 px | CA4 |
| Agendar cita · asistente | `agenda-cita-crear-390.png` (sin cambios; ahora también como asistente) | no aplica | ídem a 1440 px | CA2 |
| Completar cita · doctor | `agenda-cita-completar-390.png` y sus pasos (sin cambios; ahora también como doctor) | no aplica | ídem a 1440 px | CA12 |
| Menú lateral · asistente y doctor con "Agenda" | capturas del menú de cada rol | no aplica | ídem | CA1, CA5 |

## Composición y jerarquía

- **Agenda (página):** igual para los tres roles: título, filtros por estado, calendario y "Citas
  para hoy".
  - Asistente: idéntica a la del administrador, con el botón "Agendar nueva cita".
  - Doctor: sin el botón "Agendar nueva cita"; la descripción del encabezado dice "Tus citas
    asignadas." en lugar de la del administrador; "Citas para hoy" vacío dice "No tienes citas
    para hoy.".
- **Acciones por rol y lugar** (el orden y las variantes no cambian; solo desaparecen las que el
  rol no puede usar):

  | Rol | Tarjeta de la cita (citas para hoy y del día), asignada o reprogramada | Pie del detalle, asignada o reprogramada | Pie del detalle, completada o cancelada |
  |---|---|---|---|
  | Administrador | Completar (`primary`) · ver detalle (como hoy) | Cancelar cita · Reprogramar · Cerrar (como hoy) | Cerrar |
  | Asistente | ver detalle | Cancelar cita · Reprogramar · Cerrar | Cerrar |
  | Doctor (cita suya) | Completar (`primary`) · ver detalle | Cerrar | Cerrar |

- **Registro clínico en el detalle:** la misma sección "Registro clínico" que ve hoy el
  administrador (Motivo, Síntomas, Diagnóstico, Procedimiento, Observaciones, Recomendaciones y
  recetas), en lista de términos y sin campos de formulario, para el administrador y para el doctor
  en sus citas completadas. Sin seguimiento: "Esta cita no tiene seguimiento registrado." en texto
  `muted` dentro de la sección. El asistente no ve la sección.
- **Diálogo de completar:** el mismo de dos pasos, sin cambios, ahora también para el doctor.
- **Menú lateral:** la entrada "Agenda" aparece para asistente y doctor en la misma posición que
  para el administrador.
- **Puntos de corte:** los del sistema. **Momento protagonista:** ninguno.

## Componentes

- **Del sistema:** `x-ui.dialog` (detalle, agendar, reprogramar, completar, confirmar cancelación),
  `x-ui.button` (`secondary`, `primary`, `danger` solo en la confirmación de cancelar, como en 017),
  `x-ui.page-hero`, insignias de estado de la cita, región de estado del diálogo y del marco
  (`resources/js/ui/status.js`), menú lateral.
- **Exclusivos de esta pantalla:** ninguno nuevo; la confirmación de cancelar de la 017 se reutiliza
  tal cual.
- **Adornos generados desde datos:** ninguno.

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Inventario de vistas: filas nuevas de la tabla "Pantallas" con rol Asistente o Doctor (agenda, detalle pendiente, detalle completado con y sin registro clínico, agenda del doctor sin citas hoy) | fijo | — | — | La agenda deja de ser solo del administrador |
| Inventario de vistas: "Acceso denegado (asistente en ruta de administrador)" deja de capturarse en `/agenda` y pasa a otra ruta solo de administrador (p. ej. `/usuarios`), con capturas `error-403-admin-*` regeneradas | fijo | — | — | `/agenda` ya no rechaza al asistente |

## Movimiento

Ninguno propio: los diálogos usan la apertura y el cierre del sistema.

## Accesibilidad

- **Contraste** (de la tabla Color del sistema; sin pares nuevos):
  - `ink` sobre `surface`: 18.83:1.
  - `muted` sobre `surface` (mensaje sin seguimiento): 6.20:1.
  - `field` (borde) sobre `surface`: 3.50:1.
- **Encabezados y estructura:** la página conserva su `h1`; "Registro clínico" sigue como `h3`
  dentro del diálogo, con su título enlazado.
- **Teclado:** el orden de foco de la tarjeta y del pie sigue el orden visual; los botones que el rol no puede usar
  no existen en el orden de tabulación (ocultos, no solo deshabilitados).
- **Lectores de pantalla:** ninguna acción oculta se anuncia; el registro clínico del doctor no
  tiene controles editables, así que no se anuncia como formulario.
- **Sin animación:** no aplica.

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Botones deshabilitados en gris para lo que el rol no puede hacer | Los botones no aparecen; el servidor rechaza igual cualquier otra vía (P13) |
| Agenda propia distinta para el doctor ("Mis citas") | La misma agenda, recortada a sus citas, con una descripción que lo dice |
| Registro clínico en campos de formulario deshabilitados para dar sensación de "solo lectura" | Lista de términos, como ya lo ve el administrador |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-17 | diseño | ¿Hace falta explorar composiciones? | No: sin pantallas nuevas ni recomposición; se aplica la regla de exploración de `/design-spec` (se omite si solo cambian datos o comportamiento dentro de componentes ya diseñados) | /design-spec 018 |
| 2026-10-17 | refinado | La fila anterior citaba un archivo del plugin que no está en el repositorio | Pasa a tipo `diseño` con la regla escrita en la propia fila | /analyze 018 A16 |
| 2026-10-17 | diseño | ¿Cómo se muestra lo que el rol no puede hacer? | Se oculta el botón, sin versión deshabilitada | /design-spec |
| 2026-10-17 | diseño | ¿Cómo se distingue la agenda del doctor? | Misma página; descripción "Tus citas asignadas." y vacío "No tienes citas para hoy." | /design-spec |
| 2026-10-17 | implícita | ¿Cómo se ve el registro clínico en solo lectura? | Igual que hoy para el administrador: sección "Registro clínico" con lista de términos | `resources/views/components/calendar/view-appointment-modal.blade.php` |
