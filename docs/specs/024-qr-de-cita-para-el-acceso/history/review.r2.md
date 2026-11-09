---
spec: 024-qr-de-cita-para-el-acceso
verdict: changes_requested
round: 2
date: 2026-11-08
base: d6b4c1c
head: 4c4a8fa
human_signoff: no requerido
---

# Review · 024 QR de cita para el acceso

## Resumen
Cambios pedidos: R1–R5 de la ronda 1 quedan corregidos y no hay bloqueantes. El usuario decide
corregir antes de liberar dos importantes nuevos. R16: reabrir la misma cita antes de que responda
la primera apertura duplica los datos del detalle. R17: el SVG del QR no tiene tamaño propio, y
Firefox puede no dibujarlo al descargar el PNG. Los menores (R18–R21) se aceptan sin tarea.
Conteo de la ronda: bloqueante 0 · importante 2 · menor 4

Ronda `--rerun` con un diff pequeño (14 KB, 4 archivos de código), revisada en la sesión principal
releyendo los archivos desde cero. Incorpora los hallazgos de una `/code-review` del usuario sobre
la misma rama, verificados uno por uno en el código. Uno se descartó (ver "Descartados").

## Verificación automática
El código de `4c4a8fa` es el mismo que verificó T027 el 2026-11-08: el único commit posterior al
último cambio de código solo toca `tasks.md` y `spec.md`. Se reutilizan esos resultados.

| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (completa, en serie, `memory_limit=1G`) | ✅ 2 019 pasan, 0 fallan (línea base T050: 2 019 y 0) |
| `npm run test:ui` (9 tandas, 105 entradas a 390, 768 y 1440 px) | ✅ sin fallos de pantalla. Solo hubo fallos por el límite de la API (429 tras 6 intentos), que pasan al repetir la entrada sola (nota de T027) |
| Flujos nuevos de T024–T026, dos corridas | ✅ |
| `vendor/bin/pint --dirty` | ✅ |
| `npm run build` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/024-qr-de-cita-para-el-acceso` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA4, CA7, CA9–CA12 | Sin cambios desde la ronda 1 (`AppointmentQrTest`, `AppointmentsAccessControlTest`, unidad y propiedades) | ✅ | |
| CA5 | ui-audit `agenda-cita-detalle-pendiente`: "Una respuesta tardía no monta el QR en un detalle cerrado", "Cerrar con Escape limpia el QR" | ✅ | Falta cubrir la reapertura de la misma cita y "cerrar A, abrir B" (R16) |
| CA6 | ui-audit "Descargar QR ofrece el PNG de la cita" (`downloaded`, `image/png`) | ✅ | Solo en Chrome; en Firefox, R17. Espera fija de 300 ms (R18) |
| CA8 | ui-audit "Si el QR no carga…" (`visibleAfter`, `announcedOnce`) | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin nota. `view-appointment-modal.blade.php` (T025) y
  `docs/design/system.md` están justificados en la nota de T025 (decisión del usuario).
- Partes del plan sin implementar: ninguna.

## Seguimiento de la ronda 1
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R1 | ✅ corregido para otra cita y para el detalle cerrado | `view-appointment.js:305` (y en el seguimiento y en el `catch`); flujo "Una respuesta tardía…". La reapertura de la misma cita sigue abierta (R16) |
| R2 | ✅ corregido | Oyente de `close` en `view-appointment.js:401-409`; flujo "Cerrar con Escape limpia el QR" |
| R3 | ✅ corregido | `showQrFailure()` con `announceError` (`view-appointment.js:216-220`); sin `role="alert"` en el Blade; `announcedOnce` |
| R4 | ✅ corregido | `visibleAfter` sobre `[data-view-appointment-qr-error]` |
| R5 | ✅ corregido | Expectativa `downloaded` en `checks-dialogs.mjs`; flujo con `waitFor` del botón habilitado |
| R6–R15 | aceptados (ronda 1) | Sin cambios; los lleva `/release` al roadmap |

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 | ✅ | Cada corrección con su flujo, visto fallar sin el cambio y comprobado rompiendo lo protegido (notas de T024–T026) |
| P13 | ✅ | El JS solo presenta; nada de autorización en el cliente |
| P15 | ✅ | Sin colores ni clases nuevas fuera de los tokens |
| Resto | sin cambios desde la ronda 1 | El diff no toca PHP, rutas ni dependencias |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`d6b4c1c..HEAD`) | ✅ sin hallazgos |
| semgrep `p/php` | ➖ el rango no toca PHP; resultado de la ronda 1 vigente (0 hallazgos) |
| `composer audit` / `npm audit` | ➖ sin cambios de dependencias. Siguen los 8 avisos de Symfony de la línea base, pendientes de un `/chore` aparte (decisión del usuario, 2026-11-08) |
| trivy | ➖ sin cambios en `docker/` |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 XSS en el JS | ✅ | `showQrFailure` anuncia el texto fijo del aviso. El arnés de pruebas solo sustituye `click` y `createObjectURL` mientras corre un flujo que lo pide |
| Credencial equivocada en pantalla (R1 de la ronda 1) | ✅ | La guarda impide montar el QR de otra cita. La reapertura de la misma cita (R16) muestra el QR correcto |

## Observabilidad
- Sin logs ni eventos nuevos. P14 sigue aceptado en el plan.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rutas públicas | ➖ | El diff solo toca el detalle de la cita del panel y las pruebas de navegador |
- Sección "Descubribilidad" del plan aplicada: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R16 | importante | `resources/js/pages/agenda/view-appointment.js:305,359,364,383,393-398` | La guarda compara solo el id de la cita. Si se abre A, se cierra y se reabre A antes de que responda la primera petición, las dos respuestas pasan: `addRow` añade las filas dos veces y `showQr` corre dos veces. Además, la guarda está repetida en cuatro sitios y la limpieza corre dos veces al pulsar "Cerrar" (en `closeModal` y en el oyente de `close`). El flujo de R1 no cubre "cerrar A, abrir B" ni la reapertura (origen: `/code-review` #1, #4, #6 y #7) | Un contador de aperturas que `open()` incrementa y comprueba tras cada `await`, también en `onload` y `onerror`, y que el `close` invalida. La limpieza solo en el oyente de `close`. Flujos para la reapertura de la misma cita y para "cerrar A, abrir B" |
| R17 | importante | `app/Modules/Appointments/Infrastructure/Qr/ChillerlanQrCodeGenerator.php:22-28` | El SVG sale con solo `viewBox` (comprobado: `<svg … viewBox="0 0 37 37" preserveAspectRatio="xMidYMid">`), sin `width` ni `height`. Firefox puede no dibujar en un canvas un SVG sin tamaño propio, y "Descargar QR" fallaría siempre allí. `ui-audit` solo usa Chrome. No se comprobó en Firefox (origen: `/code-review` #3) | Una salida propia que extienda `QRMarkupSVG` y añada `width` y `height` a la raíz, con un test |
| R18 | menor | `tests/Browser/screens.json` (flujo "Descargar QR ofrece el PNG de la cita") | Espera fija de 300 ms a `canvas.toBlob`, que es asíncrono; en un CI lento puede fallar sin motivo (origen: `/code-review` #9) | Esperar por condición a que haya una descarga registrada |
| R19 | menor | `resources/js/pages/agenda/view-appointment.js:263` | El fondo del PNG sale de `--color-surface`. Si el token quedara vacío, `fillStyle` se queda en negro y el PNG sería ilegible. Hoy el token existe (`#ffffff`) y se usa en todo el sistema (origen: `/code-review` #2) | Blanco fijo para el archivo exportado, o una comprobación del valor |
| R20 | menor | `resources/js/pages/agenda/view-appointment.js:199-245` | `resetQr` y `showQr` solo comprueban `qrSection` y usan `qrImage`, `qrError` y `qrDownload` sin comprobarlos; el `try` de `showQr` no puede fallar (origen: `/code-review` #8) | Comprobar los cuatro elementos juntos al iniciar |
| R21 | menor | `app/Modules/Appointments/Infrastructure/Http/Controllers/GetAppointmentQrController.php:126`; `app/Providers/AppServiceProvider.php:112` | Comentarios dentro del código donde no hay nada complejo, contra la guía de Boost de `CLAUDE.md`; siguen el patrón de los controladores vecinos (origen: `/code-review` #10) | Moverlos al PHPDoc |

### Descartados
- `/code-review` #5, "el endpoint da el QR de citas completadas o canceladas". Es una decisión de
  la spec y del plan: el endpoint responde 200 para cualquier cita existente (plan, Constitution
  Check P13 y contrato, línea 89) y lo fija `AppointmentQrTest`. La regla de entrada la aplica el
  microservicio. No es un defecto.

## Preparación para release
- Rollback factible: sí, sin cambios desde la ronda 1 (`docker/prod/rollback.sh`, sin migraciones).
- Migraciones: no hay.
- Docs: `docs/design/system.md` refleja el aviso sin `role="alert"` (T025).
- SCA: los 8 avisos de Symfony siguen sin severidad publicada ni excepción. Se resuelven con un
  `/chore` aparte antes de `/release` (decisión del usuario, 2026-11-08).

## Tareas añadidas
- T028 ← R16
- T029 ← R17
- T030 ← definición de terminado tras las correcciones de la ronda 2

## Aceptados sin tarea
Decisión del usuario, 2026-11-08: los menores de la ronda 2 pasan al roadmap como deuda (los de
la ronda 1, R6–R15, están en `history/review.r1.md`).
- **R6** → roadmap: el QR copia el id tal como llega (mayúsculas)
- **R7** → roadmap: sin cita reprogramada en los datos de ui-audit
- **R8** → roadmap: entradas del asistente y del doctor sin expectativas del QR
- **R9** → roadmap: ningún test fija `throttle:api` en la ruta del QR
- **R10** → roadmap: ADR 0007 describe mal el ayudante que decodifica el QR en los tests
- **R11** → roadmap: prueba de rendimiento del QR con una sola medida
- **R12** → roadmap: propiedades de Eris con generador constante
- **R13** → roadmap: formato roto y renombre ajeno en `checks-composition.mjs`
- **R14** → roadmap: el marco vacío del QR sigue visible cuando falla
- **R15** → roadmap: "Descargar QR" deshabilitado sin estado accesible
- **R18** → roadmap: espera fija de 300 ms en el flujo de descarga del QR
- **R19** → roadmap: el fondo del PNG del QR depende de `--color-surface`
- **R20** → roadmap: comprobaciones de nulos incompletas en el bloque del QR
- **R21** → roadmap: comentarios dentro del código en el controlador y el proveedor del QR
