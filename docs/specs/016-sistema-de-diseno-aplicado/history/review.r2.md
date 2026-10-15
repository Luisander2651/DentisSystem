---
spec: 016-sistema-de-diseno-aplicado
verdict: changes_requested
round: 2
date: 2026-10-14
base: b23745f
head: 630592a
human_signoff: pending
---

# Review · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

Ronda 2 (`--rerun`) sobre la ronda 1 ([review.r1.md](../history/review.r1.md)). Revisa los hallazgos
que se decidió corregir (R1–R21, R23, R25–R32), el diff `b23745f..630592a` (34 archivos de código,
108 KB) y la verificación automática. Por el tamaño del diff, un único revisor independiente con
los encargos de spec, constitución, calidad y seguridad juntos; cada hallazgo se comprobó abriendo
el archivo. Los aceptados de la ronda 1 (R22, R24, R33–R61) no se reabren. Quedan fuera los
cambios sin comitear de `app/Modules/Appointments/`, que son trabajo ajeno a esta spec.

## Resumen
**changes_requested.** De los 32 hallazgos a corregir, 26 quedan corregidos, 5 corregidos con un
efecto nuevo y 1 sigue abierto (R17, en el test). No hay bloqueantes. El usuario decide corregir
antes de liberar los tres importantes que cambian lo que ve quien usa la app:
- R62: el componente de campo borra el error del servidor al salir del campo.
- R63: el anuncio del error de un campo no caduca dentro de un diálogo y aparece en la página al
  cancelarlo.
- R64: en "Completar cita", una fila con solo indicaciones se marca con error y la cita se
  completa igual.

Los importantes de `ui-audit` (R17, R65, R66) y los menores van al roadmap.

Conteo de la ronda: bloqueante 0 · importante 6 · menor 8

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel --recreate-databases` (local, `1f22e3d`, con los cambios ajenos apartados) | ✅ 1250 pasan; 1 fallo intermitente de PostgreSQL en paralelo (`WhatsAppFlowLogsTest`, que solo pasa 3/3; ya en "Pendientes y deuda") |
| Pint sobre los PHP del PR (CI) | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` | ✅ dos veces en CI sobre `1f22e3d` (push 38647428575 y PR 38647433400) y una en local (224 visitas, 0 fallos). `630592a` solo cambia documentos |
| `python .ai/bin/aidd.py validate docs/specs/016-sistema-de-diseno-aplicado` | ✅ 0 errores |

## Criterios de aceptación
Los 43 criterios tienen su test y pasan. Con observaciones de esta ronda:

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA37 | `checks-a11y` | ⚠ | El error que pone el servidor se pierde al salir del campo (R62); el anuncio del error de campo no caduca en un diálogo (R63); filas de prescripción con solo indicaciones (R64) |
| CA12 | `checks-dialogs` | ⚠ | El foco tras guardar no se comprueba si el diálogo no llega a cerrarse (R65) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; `scope.md` no da archivos sin tarea ni tareas sin diff.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1: P1–P13 y P15 ✅ o ➖; P14 ❌ aceptado por el usuario el
2026-10-06. P2: R17 y R65 son comprobaciones que pueden no fallar; quedan aceptadas al roadmap.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks, semgrep `p/php`, SCA y trivy (CI del PR) | ✅ sin hallazgos |

| Tema OWASP 2025 | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | El render de errores solo lee el token de la cookie para elegir el enlace de vuelta; no cambia ninguna autorización (`bootstrap/app.php:112-114`) |
| A05 Inyección | ✅ | `markFieldErrors` busca campos con `CSS.escape` del nombre y escribe el mensaje con `textContent` (`ui/status.js:232-275`) |

## Observabilidad
- Sin logs ni eventos de auditoría nuevos (P14 aceptado).

## Seguimiento de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `bootstrap/app.php:112-114`; `OtherErrorPagesTest.php:32,50` con la cookie |
| R2 | corregido | `usuarios/edit-user.js:131-147`; caso en `checks-a11y` |
| R3 | corregido | `markMissing` → `markFieldErrors` en los diálogos de contenido |
| R4 | corregido con efecto nuevo (→ R64) | `complete-appointment.js:234-270` |
| R5 | corregido con efecto nuevo (→ R62) | `ui/status.js:232-308` |
| R6, R7 | corregido | `auth/reset-password.js:64-80, 156` |
| R8 | corregido | `ui/status.js:58-76` |
| R9 | corregido | `ui/dialog.js:138-155`; usado en `complete-appointment.js` y `records/index.js` |
| R10 | corregido | `landing/inicio.blade.php:27-32` |
| R11 | corregido con efecto nuevo (→ R63) | `components/ui/input.blade.php:179-183` |
| R12–R16 | corregido | variante `text`; `status.js:60-64`; `agenda/index.js:367-371`; `ui/dialog.js:53-104`; `forbidden-words.php:74` |
| R17 | abierto | `checks-a11y.mjs:704-717`: con contraseñas que se envían, la 422 simulada marca el campo y el caso pasa aunque se quite la validación del navegador |
| R18, R19, R21, R23 | corregido | `checks-a11y.mjs`, `checks-global.mjs:226` |
| R20 | corregido con efecto nuevo (→ R65) | `sends` en `screens.json` |
| R25–R32 | corregido | `checks-screens.mjs`, `checks-dialogs.mjs`, `ui-audit.mjs` |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R62 | importante | `resources/views/components/ui/input.blade.php:157-169` con `resources/js/ui/status.js:232-275` | Al salir de un campo cuyo valor tiene un formato válido, `validateInput` quita el error y `aria-invalid`, también el que puso una 422 ("El correo ya está registrado") o `setFieldError`, sin que se haya corregido | El componente no quita un error que no puso él; ese error se retira al volver a escribir |
| R63 | importante | `resources/views/components/ui/input.blade.php:181-183` con `resources/js/ui/status.js:60-64` | El anuncio al salir de un campo inválido usa `announceError`: dentro de un diálogo no caduca y sigue tras corregir el campo; y si se sale del campo porque el diálogo se cierra, aparece 12 s en la página | El anuncio de un campo caduca y se retira al volver a validarlo, y no se hace cuando el diálogo se está cerrando |
| R64 | importante | `resources/js/pages/agenda/complete-appointment.js:241-250` frente a `:205-214` y `:519-526` | Una fila de prescripción con solo indicaciones se marca con error, pero `collectPrescriptions` la descarta y la cita se completa: lo que se ve contradice lo que se envía | Un mismo criterio de fila con datos en los dos sitios, y no se envía con campos marcados |
| R65 | importante | `tests/Browser/checks-dialogs.mjs:179-198`; `tests/Browser/checks-a11y.mjs:638, 876, 895` | Siguen saltos en silencio en entradas `sends`: el foco tras guardar no se comprueba si el diálogo no se cierra en 3 s, y el envío vacío o el cierre tras la 422 terminan sin las comprobaciones siguientes | Con `sends`, fallar en vez de devolver `[]`; declarar en `screens.json` lo que debe quedar abierto |
| R66 | importante | `tests/Browser/ui-audit.mjs:669-679` | Cada módulo con `runFresh` abre una visita completa por entrada aunque no le aplique (3 módulos × unas 65 entradas × 3 anchos), lo que multiplica las peticiones contra el límite de 100/min y, con R30, el riesgo de fallos por 429 en CI | Que cada `runFresh` declare si aplica antes de abrir la visita |
| R67 | menor | `resources/js/ui/status.js:287-291` | `sendingScope` prefiere `lastSubmitted` al diálogo abierto: un 422 de una acción sin formulario podría marcar campos de otro formulario | Prioridad: formulario del emisor, diálogo abierto, `lastSubmitted` dentro de él |
| R68 | menor | `tests/Browser/checks-screens.mjs:63-76` | Un día cuyo número coincide con su cantidad de citas pasa aunque la cantidad esté oculta | Excluir el número del día |
| R69 | menor | `tests/Browser/checks-screens.mjs` (`touchDayInPage`) | El observador no toma el texto previo de las regiones | Tomarlo antes del toque |
| R70 | menor | `tests/Browser/ui-audit.mjs:670-678` | La comprobación de la página (R31) no se aplica a las visitas de `runFresh` | Repetirla tras `enter` |
| R71 | menor | `resources/js/pages/tratamientos/index.js:529` | Fuera de "obligatorio", el error es `validationMessage`, en el idioma del navegador | Mensajes propios por cada `validity.*` |
| R72 | menor | `resources/js/pages/agenda/complete-appointment.js:106-107` | El asterisco de obligatorio se ve en una fila vacía cuyos campos tienen `aria-required="false"` | Mostrar el asterisco según la fila, o un texto de ayuda |
| R73 | menor | `resources/js/ui/dialog.js:58-83` | El observador de `followRerender` solo se desconecta con otra mutación pasado el plazo | Desconectarlo con un temporizador |
| R74 | menor | `tests/Modules/Core/Integration/OtherErrorPagesTest.php:48-49` | El comentario del caso 419 habla del 405 | Ajustarlo |

## Preparación para release
Sin cambios respecto a la ronda 1: rollback por tag (`v0.1.0`), sin migraciones ni flags; docs al
día; SCA sin vulnerabilidades.

## Tareas añadidas
Sin números libres en T001–T089: se reabren, con la nota "reabierta por /review (ronda 2)", las
tareas que ya tocan esos archivos.
- T081 ← R62, R63, R64 (casos de `checks-a11y`, primero)
- T027 ← R62, R63 (`x-ui.input`)
- T066 ← R64 (`complete-appointment.js`)
- T079 y T092 se reabren: verificación final y cierre tras las correcciones.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-14:
- **R17** → roadmap: el caso de contraseñas distintas que se envían pasa con la 422 simulada aunque se quite la validación del navegador.
- **R65** → roadmap: saltos en silencio en entradas `sends` de `checks-dialogs` y `checks-a11y`.
- **R66** → roadmap: `runFresh` abre visitas que no le aplican y multiplica las peticiones de `ui-audit`.
- **R67** → roadmap: `sendingScope` prefiere un formulario anterior al diálogo abierto.
- **R68** → roadmap: día con número igual a su cantidad de citas en `checks-screens`.
- **R69** → roadmap: observador de `touchDayInPage` sin texto previo.
- **R70** → roadmap: comprobación de la página en las visitas de `runFresh`.
- **R71** → roadmap: mensajes de validación del navegador en tratamientos.
- **R72** → roadmap: asterisco de obligatorio en filas de prescripción vacías.
- **R73** → roadmap: observador de `followRerender` sin desconexión por tiempo.
- **R74** → roadmap: comentario del caso 419 en `OtherErrorPagesTest`.
