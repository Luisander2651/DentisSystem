---
lane: chore
status: released             # open → done → released · escalated (→ escalated_to)
released: v0.5.1 (2026-10-23)
base: ac669d2                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: cabecera desactualizada del roadmap

## Problema
La cabecera de `docs/roadmap.md` sigue en `updated: 2026-10-04` y la etapa actual dice "v0.1.0
desde el 2026-10-04". Desde entonces se liberaron v0.2.0, v0.3.0, v0.3.1, v0.4.0 y v0.5.0
(2026-10-22, spec 019), así que quien lee el roadmap ve una versión de producción equivocada.

## Cambio
`docs/roadmap.md`: `updated` pasa a `2026-10-22` y la etapa actual dice que producción está en
`dentissapp.com` desde el 2026-10-04 con v0.1.0 y que la versión actual es v0.5.0, del 2026-10-22
(fechas de `CHANGELOG.md`). No se tocó nada más del roadmap.

Commit `docs(chore-cabecera-roadmap): update the roadmap header` con `Ficha:` en el cuerpo.

## Escalado
`lane-check` al empezar (`--files docs/roadmap.md`) y al cerrar (diff real desde `ac669d2`): 0
archivos de código. "Escalar: no. El cambio cabe en el carril."

## Verificación
2026-10-22:
- `aidd.py validate`: sin errores.
- No se corrieron la suite, Pint ni el build: el diff solo toca Markdown de `docs/`, sin código,
  configuración ni dependencias.
- Sin review con subagente: en `/chore` la validación es la review.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-22 | supuesto | ¿Rama nueva o la actual? | La rama actual `chore/sync-2026-10-22`, que ya concilia docs | agente |
