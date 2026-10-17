---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: 7e8d891
relates: [011]
covers: [CA3]
escalated_to: null
---

# Fix: la galería pública no muestra nada cuando hay una sola imagen

## Problema
En `/galeria`, si solo hay una imagen visible, la página no pinta ninguna y muestra el aviso de
fallo de conexión. Con dos o más imágenes se ve bien. Incumple 011 CA3 (el JS carga las imágenes
desde `GET /api/v1/public/gallery-images`).

## Reproducción
`tests/Modules/ContentManagement/Unit/PublicGalleryScriptTest.php` ejecuta el `galeria.js` real con
Node sobre un DOM mínimo (`render-public-gallery.mjs`) y le responde el JSON del endpoint. Antes del
arreglo:

```
⨯ it shows the image when the endpoint returns a single image as an object
Failed asserting that 0 is identical to 1.
✓ it shows every image when the endpoint returns a list
✓ it shows the empty state when the endpoint returns no images
```

## Causa raíz
`GetGalleryImagesController` responde un objeto suelto (`GalleryImageResource`) cuando el resultado
tiene un solo elemento y una lista en los demás casos. `landing/galeria.js` daba por hecho que
siempre era una lista: `records.length` es `undefined` sobre el objeto, así que no muestra el
estado vacío, y `records.map` lanza `TypeError`, que el `catch` convierte en el aviso de fallo y
deja la lista vacía. El panel de contenido (`contenido/galeria/index.js`) no falla porque ya
normaliza la respuesta con `normalizeGalleryPayload`.

## Cambio
`resources/js/pages/landing/galeria.js`: `normalizeGalleryPayload` convierte la respuesta en lista
(la lista tal cual, el objeto de una imagen en `[objeto]`, cualquier otra cosa en `[]`) antes de
pintar. El contrato de la API no cambia.

## Escalado
- `aidd.py lane-check` con `resources/js/pages/landing/galeria.js`: "Escalar: no. El cambio cabe
  en el carril" (1 archivo de código).
- Al cerrar, sobre el diff real: igual, sin motivos nuevos.

## Verificación
- `PublicGalleryScriptTest`: 3/3 en verde (12 aserciones); con el arreglo revertido falla el caso
  de una imagen (0 tarjetas en vez de 1).
- Módulo ContentManagement y `tests/Modules/Core/Unit/Design`: 110 en verde.
- Suite completa (`pest --parallel --recreate-databases`): 1344 en verde, 1 fallo intermitente
  (`PatientCrudTest`, `PDOException`) que pasa al correr el archivo solo (18/18). La línea base en
  `7e8d891` también tiene fallos intermitentes ajenos (`DeployScriptsBe…` por
  `ProcessTimedOutException`, `QueryException` por bases paralelas sin esquema). No aumentan.
- Pint: sin cambios pendientes.
- Review ligera (subagente): sin bloqueantes. La causa explica el síntoma y el arreglo la ataca
  sin tocar la API. No hay otros sitios con el defecto: `galeria.js` es el único consumidor de
  un endpoint público, y el panel (certificaciones, galería, promociones, testimonios) ya
  normaliza la respuesta. Ningún motivo de escalado.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-17 | supuesto | ¿Se arregla el formato del backend o el JS? | El JS acepta el formato actual; el contrato de la API no cambia | usuario |
| 2026-10-17 | supuesto | Review: comprobar la URL pedida (CA3) | Corregido: el test verifica `/api/v1/public/gallery-images` | review |
| 2026-10-17 | supuesto | Review: el test no comprueba que no salga el aviso de fallo | Aceptado: el conteo de tarjetas ya detecta el bug y simular `status.js` agrega fragilidad | review |
| 2026-10-17 | supuesto | Review: falta el caso de respuesta no OK | Aceptado: fuera del bug; ese camino no cambió | review |
| 2026-10-17 | supuesto | Review: `module.registerHooks` requiere Node ≥ 22.15 | Aceptado: contenedor en 24.x y CI usa la última 22.x | review |
