---
date: 2026-11-03
since: 8550f2c (sync.last)
head: 4446f58
status: closed
---
# Conciliación 2026-11-03

## Resumen
`aidd.py drift` desde `8550f2c` revisa 113 commits y marca 3 sin `(NNN)` ni `Ficha:`, que son 2
cambios hechos fuera del flujo por decisión del usuario: el login de la app móvil (PR #40) y las
páginas de Aviso de Privacidad y Términos y Condiciones (PR #41). Ambos añaden comportamiento, así
que no caben en una ficha de carril. Tocan archivos de siete specs `released`; ninguna contradice sus
criterios. Sí cambian criterios de dos specs `approved` que describen el sistema actual: 001
(CA4) y, en menor medida, 002 y 011. Sin huecos en `architecture.md`, sin specs inferidas por validar
ni fichas abiertas viejas.

`/release` solo publica specs `implemented` con review aprobada o fichas `done`; ninguno de los dos
cambios lo es todavía, así que la propuesta elegida decide cómo se libera.

## Commits fuera del flujo
| commit | qué es | propuesta | decisión |
|---|---|---|---|
| `142b773` feat(auth): add mobile login for active administrators | Comportamiento nuevo: `POST /api/v1/auth/mobile/login` (solo administradores activos, token en el cuerpo, un token por actor y canal). Cambia 001 CA4 ("se revocan todos los tokens previos del actor") y es una excepción a 001 CA1 / BR-4 (el token nunca va en el cuerpo) solo para esta ruta | spec nueva 021 con `extends: [001]` que diga qué criterios de 001 cambia | spec 021 creada como registro (`draft`); liberado en v0.7.0 sin plan, tareas ni review (decisión del usuario, 2026-11-03) |
| `8bae49b` feat(landing): add privacy notice and terms and conditions pages · `65fa1ab` fix(landing): keep legal links off wrapped text lines | Comportamiento nuevo: `/aviso-de-privacidad`, `/terminos-y-condiciones`, enlaces del pie (cierra 016/R47), leyenda en el registro y `meta description` por página. El segundo commit es parte del mismo cambio (arreglo de composición en CI) | spec nueva 022 con `extends: [011, 002]` | spec 022 creada como registro (`draft`); liberado en v0.7.0 sin plan, tareas ni review (decisión del usuario, 2026-11-03) |

## Specs con deriva
| spec | commits | archivos | ¿respeta sus criterios? | propuesta |
|---|---|---|---|---|
| 014 control de acceso y errores (`released`) | `142b773`, `8bae49b` | `routes/api.php`, `routes/web.php` | sí: rutas nuevas públicas por diseño; el 403 del login móvil sigue el formato `{"error"}` de la spec | nada |
| 015 primer despliegue (`released`) | `8bae49b`, `65fa1ab` | `resources/views/pages/auth/register.blade.php` | sí: solo se añade la leyenda legal; la imagen de acceso y el formulario siguen igual | nada |
| 016 sistema de diseño (`released`) | `8bae49b`, `65fa1ab` | vistas públicas, componentes `x-landing.legal-*`, inventario y línea base de `test:ui` | sí: solo tokens y componentes del sistema; `UiCopyTest`, `OnlyDesignTokensTest`, `PageStructureTest` y `npm run test:ui` en verde en CI. La diferencia del registro está admitida en `checks-composition.mjs` con el criterio "páginas legales (sin spec, 2026-11-03)" | nada; criterio cambiado a `022 CA7` |
| 017, 018, 019, 020 (`released`) | `8bae49b`, `65fa1ab` | solo `tests/Browser/screens.json` y `baseline-1440.json`, archivos compartidos del inventario | sí: sus entradas no cambian | nada |

Specs `approved` (no las marca `drift`, pero describen el comportamiento que cambió):
- **001 inicio y cierre de sesión:** CA4 deja de ser cierto para el staff (un token por canal, `user-auth` y `user-mobile`); CA1 sigue siendo cierto para `/api/v1/auth/login`.
- **002 registro de pacientes:** el formulario muestra la leyenda de aceptación con enlaces; no cambia ningún criterio de la API.
- **011 sitio público:** CA4 enumera las páginas estáticas; ahora hay dos más.

## Arquitectura
Sin huecos.

## Specs inferidas
Ninguna sin validar.

## Fichas abiertas
Ninguna (`2026-10-15-fix-reprogramar-cita` está `escalated` a la spec 017, ya liberada).

## Cierre
El usuario eligió primero el camino 1 (specs 021 y 022 por el flujo completo) y, al ver el coste, decidió desplegar sin plan, tareas ni review (2026-11-03). Las specs 021 y 022 quedan en `draft` como registro del comportamiento liberado en v0.7.0; `aidd.py status` las seguirá mostrando con `/plan` como siguiente paso. Las desviaciones de P1, P4, P11 y P14 que eso implica quedan aceptadas por el usuario para esta versión. `sync.last` pasa a `4446f58`.

## Liberación v0.7.0
- Tag `v0.7.0` sobre `c9bb3e0` (merge del PR #42). Código ya verificado en CI en los PR #40 y #41 (Pest en paralelo, build, `test:ui`, Pint, artefactos y seguridad del PR); suite local en serie en cada uno.
- Producción: despliegue manual del usuario en el droplet con `docker/prod/deploy.sh v0.7.0` el 2026-11-03 (4 min 33 s; sin migraciones; verificación local 14/14; se conservan las imágenes de v0.7.0 y v0.6.0) y `verify.sh --remote` 30/30 desde el equipo del usuario.
- Comprobación de las rutas nuevas (19:40 UTC): `/aviso-de-privacidad`, `/terminos-y-condiciones` y `/register` responden 200; el aviso muestra la versión 1.0 y los datos del responsable como pendientes; el pie del inicio enlaza a las dos páginas; el registro muestra la leyenda; el login móvil sin datos responde 422.
- Vigilancia de 15 min (19:40–19:55 UTC): 112 peticiones a `/up`, `/`, `/login`, `/aviso-de-privacidad`, `/terminos-y-condiciones`, `/register`, `/api/v1/appointments` sin sesión y `POST /api/v1/auth/mobile/login` sin datos, sin 5xx (máximo 1,5 s). Todas con la respuesta esperada (200 en las páginas, 401 en la ruta protegida y 422 en el login móvil). Sin rollback.
- Pendiente para el usuario: iniciar sesión con un administrador desde la app (o con una petición manual) contra producción, y sustituir los datos del responsable en `config/legal.php` antes de cargar datos reales.

