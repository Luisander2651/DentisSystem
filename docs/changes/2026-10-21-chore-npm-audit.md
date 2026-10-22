---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 5596109                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: dependencias de npm con vulnerabilidades altas

## Problema
`npm audit --audit-level=high` da 2 vulnerabilidades altas que no estaban en la línea base del
2026-10-19 (plan de la spec 019) y que bloquearían `/release`:

- `follow-redirects` 1.16.0 (transitiva de `axios` 1.20.0): GHSA-q3jh-wrc6-jgq8 (las cabeceras en
  forma de array esquivan el borrado de credenciales al redirigir a otro origen) y
  GHSA-8r9p-f939-6h3c (el escape de la expresión de `sensitiveHeaders` puede exponer cabeceras con
  credenciales). Va en el bundle del navegador.
- `vite` 7.0.0–7.3.6, por `launch-editor`: GHSA-698v-4r3x-3vcv, GHSA-r825-w8mm-5pxw y
  GHSA-587h-7wmj-h5pm (ejecución de comandos y comprobaciones esquivables en Windows). Solo afecta
  al servidor de desarrollo, no a lo que se despliega.

Se detectó en T048 de la spec 019 (verificación completa); la 019 no cambió dependencias de npm.

## Cambio
`npm update follow-redirects vite`, dentro de los rangos que ya declara `package.json` (`axios` ^1.20.0
trae `follow-redirects`; `vite` ^7.0.7): solo cambia `package-lock.json`.

- `follow-redirects` 1.16.0 → **1.16.1** (publicada el 2026-10-01, 20 días; mínimo 7). Cambios entre
  las dos: escapar todos los modificadores de la expresión de `sensitiveHeaders` y admitir cabeceras
  en forma de array clave/valor (los dos avisos). Sin cambios incompatibles.
- `vite` 7.3.6 → **7.3.7** (publicada el 2026-10-06, 15 días). Cambios: `launch-editor` a 2.14.2 (los
  tres avisos), el nombre de archivo de `transformIndexHtml` sin parámetros y `safeModulePaths` por id.
  Parche, sin cambios incompatibles.

Commit `chore(chore-npm-audit): update follow-redirects and vite` con `Ficha:` en el cuerpo.

## Escalado
`lane-check` al empezar (`--files package-lock.json`) y al cerrar (diff real desde `5596109`): 1–2
archivos, 0 de código. "Escalar: no. El cambio cabe en el carril." Versiones de dependencias que ya
existen, sin cambio de comportamiento.

## Verificación
2026-10-21, en local:
- `npm audit --audit-level=high`: 0 vulnerabilidades (antes, 2 altas). `composer audit --locked`: sin avisos.
- `npm run build` con Vite 7.3.7: compila.
- Suite Pest completa en serie: 1653 en verde, 0 fallos (una corrida anterior, con la actualización y
  el build en paralelo, dio 2 fallos por un timeout de conexión con PostgreSQL, el problema de
  entorno de 017/R12; repetida sin carga, en verde).
- `npm run test:ui` completo, dos veces: 278 visitas (95 entradas), 0 con fallos en las dos.
- Sin review con subagente: en `/chore` la validación es la review.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-21 | brecha | Dos vulnerabilidades altas nuevas de npm, ajenas a la 019, bloquean T048 y `/release` | Carril `/chore` aparte, en la rama `feat/019-reportes-mensuales`, antes de cerrar T048 | usuario |
