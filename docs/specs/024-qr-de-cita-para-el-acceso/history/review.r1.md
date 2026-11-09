---
spec: 024-qr-de-cita-para-el-acceso
verdict: changes_requested
round: 1
date: 2026-11-08
base: 6b49614
head: d6b4c1c
human_signoff: no requerido
---

# Review · 024 QR de cita para el acceso

## Resumen
Cambios pedidos: no hay bloqueantes y los 12 criterios tienen test, pero el usuario decide corregir
antes de liberar los 5 importantes (R1–R5: el QR de otra cita tras reabrir el detalle, la limpieza al
cerrar con Escape o la X, el anuncio del fallo y dos pruebas de navegador que no comprueban lo que
dicen). La suite, Pint, el build, las pruebas de navegador del QR y las herramientas de seguridad
pasan con lo mismo que la línea base. Los 10 menores (R6–R15) se aceptan sin tarea.
Conteo: bloqueante 0 · importante 5 · menor 10

Revisión independiente con cuatro subagentes: A (spec), C+D (constitución y calidad), S
(seguridad) y accesibilidad. B (plan y alcance) y la preparación para release, en la sesión
principal. El plugin `design` de Anthropic no estaba disponible en esta sesión
(`design:accessibility-review` y `design:design-critique`), así que el revisor de accesibilidad
aplicó la lista propia de la skill. Sin `design/criterios.json` (la spec no pasó por
`/design-spec`, plan D8), así que no se ejecutó `evaluate.mjs`; K1–K4 los cubre ui-audit.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (completa, en serie, `memory_limit=1G`) | ⚠ 2 018 pasan, 1 falla (línea base T050: 2 019 pasan y 0 fallan). El fallo es `DeployScriptsBehaviourTest.php:279`: `git tag v2` superó 60 s. El archivo no lo toca esta spec y pasa 14/14 al repetirlo aislado. Es intermitente del entorno, no un fallo nuevo |
| Tests nuevos de la spec (`AppointmentQrTest`, `Unit/Infrastructure`) | ✅ 23 pasan |
| `npm run test:ui -- --only` en `agenda-cita-detalle-pendiente`, `expediente-administrador-cita-detalle-pendiente` y `agenda-cita-detalle-cancelada`, dos corridas | ✅ 0 fallos en ambas (390, 768 y 1440 px) |
| `vendor/bin/pint --test` (PHP del rango) | ✅ |
| `npm run build` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/024-qr-de-cita-para-el-acceso` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `tests/Modules/Appointments/Integration/AppointmentQrTest.php`; `ChillerlanQrCodeGeneratorTest`; `AppointmentQrPropertiesTest` | ✅ | Con un UUID en mayúsculas en la URL, el QR lo copia tal cual (R6) |
| CA2 | `AppointmentQrTest` (dos peticiones, mismo contenido) | ✅ | |
| CA3 | `AppointmentQrTest` | ✅ | |
| CA4 | `AppointmentQrTest` (dataset de 3 ids no válidos) | ✅ | |
| CA5 | ui-audit `agenda-cita-detalle-pendiente`, `dashboard-detalle-*`, `expediente-administrador-cita-detalle-pendiente` | ✅ | Ninguna cita reprogramada en los datos de prueba (R7); las entradas del asistente y del doctor en la agenda no afirman el QR (R8) |
| CA6 | ui-audit flujo "Descargar QR no deja errores" | ⚠ | El flujo no comprueba que se descargue el PNG (R5) |
| CA7 | ui-audit `agenda-cita-detalle-cancelada` y completada | ✅ | |
| CA8 | ui-audit flujo "Si el QR no carga…" | ⚠ | `textIn` lee el texto oculto y no exige que el aviso se vea (R4) |
| CA9 | `AppointmentsAccessControlTest` | ✅ | |
| CA10 | `AppointmentsAccessControlTest`; `AppointmentQrTest` | ✅ | |
| CA11 | `AppointmentQrTest` (fallo del generador y del repositorio) | ✅ | |
| CA12 | `AppointmentQrTest` (`Cache-Control: private, no-store`) | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan:
  - `tests/Browser/baseline-1440.json` y `tests/Browser/checks-composition.mjs`: admisión `024 CA5`
    de la línea base visual, justificada en la nota de T050.
  - En la misma entrada se renombra el criterio de una admisión ajena ("páginas legales" → "022
    CA7"), sin efecto (R13).
- Partes del plan sin implementar: ninguna. T001, T051 y T052 solo tocan docs.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec 024 `approved` → `implemented` |
| P2 | ✅ | Tests de integración, unidad, propiedades y ui-audit por criterio. Debilidades en R4, R5 y R12 |
| P3 | ✅ | Puerto en `Aplication/Support`. `AppointmentsArchitectureTest` prohíbe `chillerlan`, facades, Eloquent e Infrastructure en el puerto y el caso de uso. Caso de uso `final readonly` con `assertCan` primero |
| P4 | ✅ | Contrato en el plan; cada código HTTP lo fija un test |
| P5 | ✅ | `auth:sanctum` + `staff` + `assertCan('appointments.view-detail')`; denegados probados por actor |
| P6 | ➖ | Sin cuerpo; el id lo valida `UuidIdentifier` (400) |
| P7 | ✅ | Dos tests afirman el 500 genérico exacto |
| P8 | ➖ | Sin variables nuevas |
| P9 | ➖ | Sin migraciones |
| P10 | ✅ | `chillerlan/php-qrcode` fijada en `6.0.1`, ADR 0007 `accepted` (la descripción del ayudante de test no coincide, R10) |
| P11 | ✅ | Sin logs nuevos; el QR solo lleva el UUID y el archivo no tiene datos del paciente |
| P12 | ➖ | Lo aplica `/release` |
| P13 | ✅ | El JS solo decide mostrar; la API vuelve a autorizar |
| P14 | ❌ aceptado | Sin registro de auditoría (objetivo 5); aceptado por el usuario en el plan (2026-11-04), con los puntos de emisión en `observability.md` |
| P15 | ✅ | Tokens y `x-ui.button`; el JS lee `--color-surface` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`6b49614..HEAD`) | ✅ 0 hallazgos (21 commits) |
| semgrep `p/php` | ✅ 0 hallazgos (23 reglas, 563 archivos) |
| `composer audit` | ⚠ 8 avisos de Symfony sin severidad publicada (`filesystem`, `http-foundation` ×4 con CVE-2026-118514, `http-kernel`, `routing` con CVE-2026-118516, `yaml`). Son los mismos de la línea base T001, ajenos a esta spec; se tratan aparte por decisión del usuario (2026-11-04). `/release` debe decidir sobre ellos |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |
| trivy `config` (docker/) | ✅ 0 hallazgos HIGH/CRITICAL |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `routes/api.php` grupo `auth:sanctum` + `staff`; `assertCan` en `GetAppointmentQrUseCase`; matriz en `AppointmentsAccessControlTest` |
| A01:2025 IDOR | ✅ | Todo el staff ve cualquier cita por diseño; el paciente recibe 403 incluso con la suya |
| A02:2025 Configuración (cabeceras, caché) | ✅ | `private, no-store`; CSP global sin `unsafe-inline` en scripts y `object-src 'none'`; `nosniff` |
| A03:2025 Cadena de suministro | ✅ | Dependencia con ADR, versión exacta y antigüedad comprobada |
| A05:2025 Inyección / XSS en SVG y JS | ✅ | SVG generado desde un UUID validado; test sin `<script>`, `href`, `xlink` ni `onload`; el JS usa `src` con `encodeURIComponent` y ningún `innerHTML` con la respuesta |
| A07:2025 Autenticación | ✅ | Sin sesión → 401 |
| A09:2025 Logs | ➖ | Sin logs nuevos; auditoría aceptada como desviación (P14) |
| A10:2025 Errores | ✅ | 500 genérico; el mensaje del id no válido no refleja la entrada |
| Limitación de tasa | ✅ | `throttle:api`; ningún test lo fija (R9) |
| Credencial al portador | ➖ | Riesgo aceptado en el plan (R1 del plan) |
| Rutas protegidas e instrucciones a agentes | ✅ | Sin cambios en `docker/`, `docker-compose.yml`, `phpunit.xml`; sin instrucciones sospechosas |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no. El registro aún no existe (P14 aceptado);
  los puntos de emisión están en `docs/observability.md` (T091).
- Datos sensibles en logs del código tocado: ninguno (no hay logs nuevos).
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos; la correlación aún no está
  implementada en el proyecto).

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rutas públicas | ➖ | El diff no toca rutas públicas, layouts públicos, metadatos, `robots.txt` ni el sitemap: solo una ruta de API autenticada y un diálogo del panel |
- Sección "Descubribilidad" del plan aplicada: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `resources/js/pages/agenda/view-appointment.js:281-327` | `open()` no comprueba, después del `await`, que la cita siga siendo la activa. Si se abre la cita A, se cierra y se abre la B antes de que responda A, el detalle de B muestra y descarga el QR de A (la credencial equivocada). La guarda de `onload`/`onerror` no lo evita, porque `src` ya se asignó | Tras cada `await`, `if (activeAppointmentId !== appointmentId) return;`, o un `AbortController` por apertura |
| R2 | importante | `resources/js/pages/agenda/view-appointment.js:370-375` | `resetQr()` y `activeAppointmentId = null` solo corren en `closeModal` (botón Cerrar). Al cerrar con Escape o con la X (`data-dialog-close`), el QR y la cita activa siguen montados. `ui/dialog.js:122` pide escuchar el evento `close` | Hacer la limpieza en el evento `close` del `<dialog>` |
| R3 | importante | `resources/views/components/calendar/view-appointment-modal.blade.php:24`; `view-appointment.js:230-234` | El aviso del QR es un `role="alert"` que pasa de oculto a visible con el texto ya escrito; varios lectores no lo anuncian. `onerror` no llama a `announceError`, como hacen los demás errores del diálogo | Llamar a `announceError(...)` en `onerror` |
| R4 | importante | `tests/Browser/screens.json:1442-1465`; `tests/Browser/checks-dialogs.mjs:380` | El flujo de CA8 usa `textIn`, que lee `textContent` sin mirar si se ve. El texto ya está en el Blade dentro de un `<p class="hidden">`, así que el flujo pasaría aunque el aviso nunca apareciera | Exigir que `[data-view-appointment-qr-error]` sea visible (`visibleAfter`) o que la imagen tenga `invisible` |
| R5 | importante | `tests/Browser/screens.json:1423-1440` | El flujo de CA6 solo comprueba que el diálogo sigue abierto. Pasaría si `downloadQr` no hiciera nada o si el botón siguiera deshabilitado | Capturar la descarga (`URL.createObjectURL` o el `click` del enlace) y exigir un `image/png` con el nombre `cita-AAAA-MM-DD-HHMM.png`; como mínimo, el botón habilitado antes del clic |
| R6 | menor | `app/Modules/Appointments/Aplication/UseCases/GetAppointmentQrUseCase.php:38-44` | Codifica el id tal como llega en la URL. Con un UUID en mayúsculas, PostgreSQL encuentra la cita y el QR sale en mayúsculas, que no es "exactamente el identificador" de CA1 | Codificar el id de la cita encontrada o normalizarlo a minúsculas, con un test |
| R7 | menor | `app/Core/Console/UiAuditDataCommand.php` | Los datos de ui-audit no tienen ninguna cita reprogramada, así que ninguna entrada comprueba el QR en ese estado (CA5) | Añadir una cita reprogramada y su entrada de detalle |
| R8 | menor | `tests/Browser/screens.json` (`agenda-cita-detalle-pendiente-asistente`, `-doctor`) | Se admiten en la línea base por el QR, pero no afirman `image`, `alt` ni "Descargar QR" | Copiar las expectativas del QR a esas entradas |
| R9 | menor | `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php:285` | Ningún test fija `throttle:api` en la ruta del QR (TM8); si saliera del grupo, nada fallaría | Afirmar el middleware en la ruta |
| R10 | menor | `docs/adr/0007-chillerlan-php-qrcode-para-qr.md:32`; `tests/Support/DecodesQrSvg.php:79-94` | El ADR dice que el ayudante rasteriza con GD, pero arma la matriz y llama por reflexión a internos privados de 6.0.1 | Corregir el ADR y anotar que subir de versión exige revisar el ayudante |
| R11 | menor | `tests/Modules/Appointments/Unit/Infrastructure/ChillerlanQrCodeGeneratorTest.php:53-62` | La prueba de 150 ms mide una sola ejecución; sensible al ruido de CI | Mediana o mejor de N ejecuciones |
| R12 | menor | `tests/Modules/Appointments/Unit/Infrastructure/AppointmentQrPropertiesTest.php:17-39` | Las propiedades usan `Generators::constant(null)` y crean el UUID dentro del cuerpo: Eris no controla la entrada ni encoge. La segunda prueba un value object existente | Generar los UUID con un generador de Eris |
| R13 | menor | `tests/Browser/checks-composition.mjs:70` | Formato roto (`},    {`) y renombre de una admisión ajena ("022 CA7") dentro de la rama | Separar la línea y dejar constancia del renombre |
| R14 | menor | `resources/js/pages/agenda/view-appointment.js:230-234` | Cuando falla la imagen, el marco de 240 × 240 con borde sigue visible y vacío junto al aviso; `system.md` dice que el QR se oculta | Ocultar también el marco al fallar |
| R15 | menor | `resources/views/components/calendar/view-appointment-modal.blade.php:25` | "Descargar QR" queda deshabilitado mientras carga sin explicar por qué ni anunciar cuando está listo | Estado "Cargando código QR…" asociado o anuncio al cargar |

## Preparación para release
- Rollback factible: sí. `docker/prod/rollback.sh` a la versión anterior; sin migraciones ni datos;
  los QR ya enviados solo contienen el id de la cita.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (plan → Rollout).
- Docs actualizadas: `architecture.md` (T090), `observability.md` (T091), `security.md` (T052),
  `design/system.md` (T051); `deployment.md` sin cambios necesarios (sin variables ni pasos nuevos).
- SCA: los 8 avisos de Symfony de la línea base no tienen severidad publicada ni excepción en
  `security.md`; `/release` debe confirmar su severidad y decidir (actualizar con `/chore` o
  registrar una excepción) antes de producción.
- Cobertura de riesgos: la spec no declara correcciones de riesgos `RS`/`OB`; RS1.b y RS1.c siguen
  pendientes (los cubrirá la spec 025).

## Tareas añadidas
- T024 ← R1, R2
- T025 ← R3
- T026 ← R4, R5
- T027 ← definición de terminado tras las correcciones

## Aceptados sin tarea
Decisión del usuario, 2026-11-08: R1–R5 se corrigen (ver "Tareas añadidas"); los menores pasan al roadmap como deuda.
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
