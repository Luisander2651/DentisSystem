---
lane: fix
status: released             # open → done → released · escalated (→ escalated_to)
released: v0.5.1 (2026-10-23)
base: 5395762                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [009, 018]          # specs cuyo comportamiento toca ([] si ninguna)
covers: [CA1, CA12]          # 009 CA1 (completar con recetas en línea) · 018 CA12 (el doctor completa)
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: completar una cita con una receta sin instrucciones

## Problema
Al completar una cita desde la agenda, rellenar el seguimiento y añadir una receta sin
"Instrucciones (opcional)", "Completar cita" responde 500. El log local lo muestra el 2026-10-23
(`unexpected_error` en `CompleteAppointmentController`, `Error` en
`app/Modules/AppointmentTracking/Domain/ValueObjects/Instructions.php:13`). Las instrucciones son
opcionales (009 CA1, 018 CA12), así que la cita debería completarse.

## Reproducción
`tests/Modules/AppointmentTracking/Integration/CompleteAppointmentTest.php` →
`it('completes appointment with a prescription without instructions')`, con tres casos (clave
omitida, `null`, en blanco). Antes del arreglo, los tres en rojo:

```
Failed asserting that 500 is identical to 201.
The following exception occurred during the last request: Error
Tests:    3 failed (3 assertions)
```

El error es `Cannot modify readonly property App\Modules\AppointmentTracking\Domain\ValueObjects\Instructions::$value`.

## Causa raíz
`Instructions` es una `final readonly class` con la propiedad promovida `$value`. PHP inicializa
la propiedad promovida al entrar en el constructor, y el constructor intentaba normalizar un valor
vacío reasignando `$this->value = null`. Una propiedad `readonly` ya inicializada no se puede
volver a escribir, así que cualquier receta sin instrucciones (el formulario de "Completar cita"
omite la clave si el campo está vacío, y el middleware convierte `""` en `null`) lanzaba `Error`,
y el controlador lo devolvía como 500 genérico. El defecto es de origen (e708ed6, 2026-09-06): los
tests existentes siempre mandaban instrucciones. El mismo fallo afectaba a crear una receta sin
instrucciones (`CreatePrescriptionUseCase`) y a leer de la base una receta con `instructions` nulo
(`PrescriptionEntity`), que usan el mismo `fromNullable()`.

## Cambio
`app/Modules/AppointmentTracking/Domain/ValueObjects/Instructions.php`: la normalización
(`null` o en blanco → `null`) pasa del constructor a `fromNullable()`, que construye el objeto una
sola vez con el valor final, como su vecino `Observations`; `create()` delega en `fromNullable()`.
Commit `fix(fix-completar-cita-sin-instrucciones): …` con `Ficha:` en el cuerpo.

## Escalado
- Al empezar: `lane-check` → "Carril: fix · base: 5395762 · 3 archivo(s), 1 de código. Escalar:
  no." No cambia comportamiento especificado (lo restituye: 009 CA1, 018 CA12), ni contrato,
  datos, seguridad, diseño ni dependencias.

- Al cerrar: `lane-check` sobre el diff desde `5395762` → sin motivos de escalado.

## Verificación
- Test nuevo: 3/3 en verde tras el arreglo; módulo AppointmentTracking: 60 pasados.
- Suite completa (`pest --parallel`): 1674 pasados, 0 fallos. Pint: `passed`. Semgrep (`p/php`)
  sobre `Instructions.php`: sin hallazgos.
- Review ligera (subagente `standard`): sin hallazgos. Con solo `Instructions.php` revertido, el
  test nuevo falla (3/3, 500 en vez de 201); restaurado, pasa. La causa explica el síntoma, el
  arreglo la ataca, el texto no vacío se conserva igual y no hay otra clase `readonly` en `app/`
  que reasigne una propiedad promovida en su constructor. No toca contrato, datos, seguridad,
  diseño ni dependencias.
- Release v0.5.1: CI en verde en `main` sobre `316a495` (run 40002681640); imágenes
  `dentissa-app:v0.5.1` y `dentissa-web:v0.5.1` construidas desde `git archive 316a495` y
  verificadas en local (arreglo presente, `www-data`, sin `.env`, `.git`, `public/hot`,
  `node_modules`, tests, Node ni Composer; mismos 29 assets de Vite en `app` y `web`);
  `composer audit` y `npm audit` con 0 vulnerabilidades. Tag `v0.5.1` sobre `316a495`.
- Aprobado por Luisander2651 el 2026-10-23 12:39 (18:39 UTC) para v0.5.1 ("desplegar v0.5.1 a
  producción").
- Producción: despliegue manual del usuario en el droplet con `docker/prod/deploy.sh v0.5.1`
  (3 min 11 s; sin migraciones; verificación local 14/14) y `verify.sh --remote` 30/30,
  confirmados por el usuario. Verificación manual del usuario el 2026-10-23: completar una cita
  con una receta sin instrucciones se guarda correctamente.
- Vigilancia de 15 min (19:23–19:38 UTC): 140 peticiones a `/up`, `/`, `/login`, `/agenda` y
  `/api/v1/appointments` sin sesión, sin 5xx (máximo 3,4 s). Todas con la respuesta esperada
  (200, 200, 200, 401 y 401); `/agenda` dio 401 porque la sonda mandaba `Accept: application/json`
  (sin esa cabecera redirige a `/login`, comprobado). Sin rollback.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-23 | supuesto | ¿Rama del arreglo? | `fix/completar-cita-sin-instrucciones` desde `main`, no sobre `feat/020-tablero-de-inicio` (solo docs de la 020) | agente |
