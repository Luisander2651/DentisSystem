---
lane: fix
status: done
base: 81131e8
relates: []
covers: []
escalated_to: null
---

# Fix: la suite en paralelo se queda sin memoria

## Problema
`./vendor/bin/pest --parallel` falla a veces (2 de 3 corridas el 2026-11-16) porque un proceso de
Pest muere con `Allowed memory size of 134217728 bytes exhausted`, casi siempre en
`tests/Modules/Users/Integration/RolePermissionsTest.php`. El archivo pasa solo. Obliga a correr la
suite con `--passthru-php="'-d' 'memory_limit=256M'"` y deja la suite poco fiable.

## Reproducción
`tests/Modules/Core/Unit/TestMemoryLimitTest.php` comprueba que los tests corren con al menos
512 MB. Antes del arreglo falla con `memory_limit is 134217728 bytes`. La suite completa en paralelo, sin `--passthru-php`, reproduce el fallo en 2 de 3
corridas.

## Causa raíz
El contenedor no carga ningún `php.ini` (`php_ini_loaded_file()` vacío), así que PHP usa su valor
por defecto de 128 MB, y `phpunit.xml` no fija otro. Cada proceso empieza en unos 50 MB y la memoria
crece con cada test que ejecuta (una aplicación nueva por test). No hay un archivo que la dispare:
el que más usa solo es `NoEnvOutsideConfigTest` (76 MB). Medido el 2026-11-16 con
`memory_get_peak_usage(true)` y sin límite, el proceso que más usó en la suite en paralelo llegó a
117 MB, y `tests/Modules/Core` en serie a 137 MB. Como el reparto de archivos entre los 12 procesos
cambia en cada corrida, a veces uno pasa de 128 MB; `RolePermissionsTest`, con 121 casos, suele ser
el que lo cruza.

## Cambio
`phpunit.xml` fija `<ini name="memory_limit" value="512M"/>`: más del cuádruple del pico medido por
proceso y margen para correr módulos enteros en serie. Afecta solo a los tests (local y CI), no a la
aplicación. Commit `fix(fix-memoria-suite-paralela): raise the memory limit of the test suite`.

## Escalado
`lane-check` al empezar y al cerrar (con `--files phpunit.xml tests/Modules/Core/Unit/TestMemoryLimitTest.php`;
sin `--files` también cuenta la carpeta `nueva-landing/` del usuario, que no está en git): 2
archivos, 1 de código; un motivo, `seguridad: rutas protegidas` por `phpunit.xml`. El cambio no
debilita ningún test (solo sube la memoria); el usuario aceptó seguir en el carril (Decisiones).

## Verificación
- `TestMemoryLimitTest`: en rojo antes del arreglo (`memory_limit is 134217728 bytes`), en verde
  después; quitar la línea `<ini>` lo vuelve a poner en rojo.
- Suite completa con el comando de `AGENTS.md` (`./vendor/bin/pest --parallel`, sin
  `--passthru-php`): antes, 2 de 3 corridas con un proceso caído por memoria; después, 3 de 3 en
  verde (2431 tests).
- Pint en verde sobre el test nuevo.
- Review ligera hecha en la misma sesión, sin subagente: la causa (límite por defecto de 128 MB y
  crecimiento por test, pico medido de 117 MB) explica el fallo intermitente y por qué cae en
  distintos puntos (`config/database.php`, `php-code-coverage`); el arreglo ataca el límite, no un
  caso concreto; CI usa el mismo `phpunit.xml`, así que no hay otro sitio con el mismo defecto. El
  crecimiento por test queda registrado en Decisiones, sin arreglar.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-16 | implícita | ¿Qué comportamiento se espera? | La suite debe pasar sin opciones extra: es el comando de `AGENTS.md` | `AGENTS.md` → Comandos |
| 2026-11-16 | supuesto | ¿Buscar y quitar el crecimiento de memoria por test? | No en esta ficha: no es un defecto de la aplicación y no hay un test culpable; perseguirlo sería un `/refactor` aparte si el pico se acerca otra vez al límite | medición del 2026-11-16 |
| 2026-11-16 | supuesto | `lane-check` pide escalar por `phpunit.xml` (ruta protegida) | Seguir en `/fix`: una línea que solo sube la memoria y no toca ningún test | usuario |
| 2026-11-16 | supuesto | ¿Rama propia? | Se hace en `feat/026-nueva-landing`, donde apareció; viaja con la 026 | usuario ("resuelve en lista") |
