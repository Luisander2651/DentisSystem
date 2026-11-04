---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 8877ff9                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: avisos de seguridad de follow-redirects y symfony/mime

## Problema
La puerta de seguridad de `/release` (v0.8.0, 2026-11-04) da dos avisos que no estaban al liberar
v0.7.0:

- `follow-redirects` 1.16.1 (transitiva de `axios` 1.20.0), **alta**: GHSA-mrwp-f3q8-3g9p (los
  arrays `sensitiveHeaders` de otro *realm* se descartan y las credenciales propias pueden seguir
  una redirección). Detiene el release.
- `symfony/mime` 7.4.19 (de `laravel/framework` y `symfony/mailer`), **media**: CVE-2026-45070
  (inyección de cabeceras de correo con caracteres no válidos en nombres de parámetros MIME). No
  detiene el release, pero se corrige en el mismo carril.

## Cambio
Dentro de los rangos que ya declaran `package.json` (`axios` ^1.20.0 pide `follow-redirects`
^1.16.0) y `composer.json` (`laravel/framework` pide `symfony/mime` ^7.2.0); solo cambian
`package-lock.json` y `composer.lock`.

- `npm update follow-redirects --before=2026-10-28`: 1.16.1 → **1.16.2** (publicada el 2026-10-25,
  10 días; mínimo 7). Cambios entre las dos: usar `Array.isArray` al leer `sensitiveHeaders` (el
  aviso) y un aviso de financiación. Sin cambios incompatibles.
- `composer update symfony/mime`: v7.4.19 → **v7.4.22** (publicada el 2026-10-28, 7 días). Cambios:
  rechazar nombres de parámetro con caracteres no válidos (el CVE) y la fecha de un test. Parche,
  sin cambios incompatibles.
- `npm` volvió a escribir el nombre raíz del lockfile como `html` (el nombre de la carpeta en el
  contenedor) y quitó el de `packages[""]`; se restauró `DentissaApp`, como en 019/R31. Laravel
  Boost reescribió la versión de PHP en `CLAUDE.md` al actualizar con Composer; se revirtió.

Commit `chore(chore-dependency-audit): update follow-redirects and symfony/mime` con `Ficha:` en el
cuerpo.

## Escalado
`lane-check` al empezar (`--files package-lock.json composer.lock`): 2 archivos, 0 de código.
"Escalar: no. El cambio cabe en el carril." Versiones de dependencias que ya existen, sin cambio de
comportamiento. Al cerrar (diff real desde `8877ff9`): 4 archivos, 1 contado como código; además de
los dos lockfiles y esta ficha, el único otro es `Memoria_de_estadia_Dentissa.docx`, sin seguimiento
y ajeno al cambio. "Escalar: no."

## Verificación
2026-11-04, en local:
- Línea base: la suite completa del usuario en `main` dio 1979 de 1980; el único fallo
  (`RolePermissionsTest`, sin la fila `auth.session.view`) se corrigió en `de9a7d4` antes del merge.
- `npm audit --audit-level=high`: 0 vulnerabilidades (antes, 1 alta). `composer audit --locked`: sin
  avisos (antes, 1 media). `npm ci --dry-run`: el lockfile es coherente.
- `npm run build`: compila.
- Suite Pest completa en serie (`memory_limit=2G`): 1983 en verde, 0 fallos.
- `npm run test:ui` no se corrió en local: `follow-redirects` solo lo usa el adaptador de Node de
  `axios`, no el del navegador, y no cambia ningún recurso de la interfaz. Lo corre el CI del PR.
- Sin review con subagente: en `/chore` la validación es la review.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-04 | brecha | Una vulnerabilidad alta de npm sin excepción detiene `/release` v0.8.0 | Carril `/chore` aparte, en la rama `chore/dependency-audit`, antes de preparar la versión; incluye también el aviso medio de `symfony/mime` | usuario |
