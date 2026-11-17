---
lane: fix
status: released
released: v0.11.0 (2026-11-17)
base: a69b731
relates: [026]
covers: [CA10]
escalated_to: null
---

# Fix: `ui-audit inicio-testimonios` falla de forma intermitente

## Problema
`npm run test:ui -- --only inicio-testimonios` a 1440 px falla en algunas corridas y pasa en otras, sin que cambie el código. Ocurre desde T024 de la spec 026 (2026-11-16), también con el CSS de aquel commit. El fallo está en la composición: según la corrida, cuentan o no la tarjeta del testimonio y el título de la sección siguiente.

CI ejecuta `npm run test:ui` en cada push y decide con su resultado, así que un fallo intermitente deja CI en rojo al azar. Es R8 de la review de la 026, y la nota A56 de T095 lo exige resuelto antes de staging.

## Reproducción
La prueba es la propia entrada de `ui-audit`:
- **Antes del arreglo**, con la línea base en el estado asentado: `npm run test:ui -- --only inicio-testimonios --width 1440` pasa 3 de 6 veces seguidas. Cuando falla, dice: `[composition] composición a 1440 px: región 18 de 24: se esperaba heading en x=10 % … y hay link en x=74 %`, o la tarjeta del testimonio en la región 12.
- **Réplica en Playwright**, con los mismos clics y el esqueleto de `checks-composition.mjs`. Midiendo a 0, 40, 120 y 350 ms tras el último clic:
  - hasta 120 ms, la tarjeta tiene opacidad 0–0,05 y no entra en el esqueleto;
  - a 350 ms tiene opacidad 0,85 y sí entra.

## Causa raíz
El inicio revela sus secciones al entrar en pantalla. Les añade `.in`, y eso arranca una animación de opacidad de 0 a 1 de unos 900–1000 ms. La composición descarta lo que tiene opacidad 0.

Los pasos de la entrada desplazan la página hasta los testimonios con el clic. Después, `ui-audit` solo espera a que la red quede quieta (`settle()`): no espera a que terminen esas animaciones ni a que el observador arranque las de las secciones vecinas, que empiezan uno o dos cuadros más tarde. Por eso la captura caía en cualquier punto del revelado. La línea base misma se había guardado así: en T024 y otra vez en T044, ambas a mitad de animación.

No es un defecto de la página. Un visitante ve las secciones completas en cuanto terminan de entrar.

## Cambio
- `tests/Browser/ui-audit.mjs`:
  - paso nuevo `animationsDone: true`, que espera 300 ms seguidos sin ninguna animación finita en curso;
  - no cuentan las infinitas (cinta, anillos) ni las ligadas al desplazamiento;
  - está documentado en la cabecera.
- `tests/Browser/screens.json`: `inicio-testimonios` espera a `.ts-card.in` y después a `animationsDone`.
- `tests/Browser/baseline-1440.json`: la línea de `inicio-testimonios`, reescrita en el estado asentado (con la tarjeta y el título de la sección siguiente).

Un primer intento solo esperaba al final de las animaciones en curso. Pasó 3 de 10 veces, porque el revelado de la sección siguiente arrancaba después. Por eso el paso exige una ventana de calma, como `settle()` con la red.

## Escalado
`aidd.py lane-check … --files tests/Browser/ui-audit.mjs tests/Browser/screens.json tests/Browser/baseline-1440.json`, al empezar y al cerrar: 3 archivos, 0 de código, sin escalar. Sin `--files`, la comprobación lista además los archivos sin versionar del usuario en `nueva-landing/`, que no son de esta ficha.

## Verificación
- **Con el arreglo:** `inicio-testimonios` pasa 10 de 10 veces seguidas, y `npm run test:ui -- --only inicio` (4 entradas, 9 visitas) pasa dos veces seguidas en los tres anchos. Eso cumple la condición A56 de T095.
- **Review ligera:**
  - Con el ejecutor y la entrada revertidos y la línea base nueva, la entrada vuelve a fallar (3 de 6 pasan); al restaurarlos pasa.
  - La causa explica los dos síntomas: la tarjeta y el título que aparecían y desaparecían.
  - El arreglo ataca la causa, porque espera al estado asentado; no relaja la comparación.
- **Otros sitios:** las demás entradas con clics (menús, visor de certificaciones) miden un diálogo o una hoja con su propia raíz y pasan estables. Si otra entrada desplaza la página hasta secciones que se revelan, debe usar el mismo paso.
- **Sin PHP en el diff:** la suite de Pest no cambia.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | implícita | ¿Defecto de la página o de la prueba? | De la prueba: la página se ve completa en cuanto termina de revelarse; la captura no esperaba ese estado | réplica con capturas a 0–350 ms |
| 2026-11-16 | usuario | Arreglarlo antes de liberar la 026 | `/fix` aparte antes de staging | review de la 026 (R8), nota A56 de T095 |
