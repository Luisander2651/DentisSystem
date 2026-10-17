---
lane: fix
status: escalated            # open → done → released · escalated (→ escalated_to)
base: 0de3619
relates: [007]
covers: [CA4, CA5, CA14]
escalated_to: 017
---

# Fix: reprogramar una cita desde la agenda

## Problema
En la agenda, al guardar el diálogo "Editar cita" con el estado "Asignada" o "Reprogramada", el
servidor rechaza el cambio con 409 y la cita no se reprograma. Además se puede reprogramar una
cita a una fecha u hora que ya pasó (007 CA14, **HOY NO SE CUMPLE**).

## Reproducción
Manual (2026-10-14): abrir una cita asignada → "Editar cita" → cambiar fecha y hora, dejar el
estado en "Asignada" → "Guardar cambios" → aviso de error y la cita no cambia. No se escribió el
test porque la ficha se escaló antes de empezar; el test que falla será la primera tarea de la
spec 017.

## Causa raíz
- El diálogo envía siempre fecha, hora, estado y recordatorio en la misma petición. El servidor
  primero reprograma (la cita pasa a `reprogramada`) y después aplica el estado, que solo admite
  `completada` o `cancelada` (007 CA4): con `asignada` o `reprogramada` lanza la transición
  inválida. El selector de estado no tiene razón de ser: reprogramar ya fija el estado.
- La validación de "fecha y hora juntas" solo corre cuando no llegan estado ni recordatorio, así
  que desde la interfaz nunca corre.
- No existe ninguna comprobación contra la fecha y hora actuales al crear ni al reprogramar.
- Colateral (fuera de esta ficha): el recordatorio de WhatsApp se invierte cada vez que llega el
  campo, sea cual sea su valor, y el diálogo siempre lo envía.

## Escalado
- `aidd.py lane-check` con los archivos previstos: "Escalar: sí — tamaño: 9 archivos de código
  (máximo 5)".
- Triaje manual (`lanes.md` §3), además: motivo 1 (cambia comportamiento: regla nueva de fecha
  pasada, botón "Cancelar cita", sin selector de estado) y motivo 2 (la petición de reprogramar
  deja de aceptar el estado).
- Decisión: escalar a la spec 017 (usuario, 2026-10-15).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-15 | brecha | ¿Arreglo en el carril o flujo completo? | Escalar a la spec 017 `reprogramar-cancelar-citas` | usuario |
| 2026-10-15 | brecha | ¿El recordatorio de WhatsApp que se invierte entra aquí? | No: el recordatorio aún no funciona al 100 % | usuario |
| 2026-10-15 | supuesto | Cambios manuales sin commit en `Appointments` | Descartados a petición del usuario; copia en el scratchpad de la sesión | usuario |
