---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-11-04
spec_sha: 3fdabe6425e9
plan_sha: 966d673c1a12
tasks_sha: aeadf6cc7f9c
---

# Análisis · 024 QR de cita para el acceso

## Resumen
**Fail.** No hay hallazgos CRÍTICOS, pero sí 2 ALTOS que contradicen el código actual:
- **A1:** un id que no es UUID **no** da 400 en `GET /appointments/{id}`, sino 500. La spec y el
  plan copian un comportamiento que no existe.
- **A2:** el middleware global `SecurityHeaders` sobrescribe la `Content-Security-Policy` que el
  plan pone en la respuesta del QR.

Además hay 2 MEDIOS: al arnés de `ui-audit` le faltan las comprobaciones que pide T016 (A3), y el
PNG descargado saldría con fondo transparente (A4). Los demás son ajustes de tareas.

Análisis en la sesión principal, sin subagente (el usuario no pidió uno). Los hallazgos ALTOS se
verificaron en el código: `UuidIdentifier.php:25-28`, `GetAppointmentByIdController.php:43-51`,
`GetInternalAppointmentByIdController.php:46-47`, `bootstrap/app.php:52` y
`SecurityHeaders.php:27-34`.

Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 2 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 7 / 8 (TM2 fuera de Core, riesgo aceptado R1) |
| Principios de la constitución evaluados | 15 / 15 |
| Tareas con "hecho cuando" | 22 / 22 (sin contar T090–T098) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia (código) | ALTA | spec CA4; plan → P6, "Contratos y datos" (400), Enfoque; T012, T013, T022 | `AppointmentId` hereda de `UuidIdentifier`, que lanza `InvalidArgumentException`, no `ValueObjectsException`. `GetAppointmentByIdController` no la captura, así que cae en el `catch (\Exception)` y **responde 500**. Solo la consulta interna (023) la convierte en `400 {"error": "Invalid appointment id."}`. "400, igual que la consulta de la cita" es falso, y T022, si copia los `catch` del detalle, haría que T013 fallara | La spec, en CA4, cita la consulta interna (023) en lugar de la del detalle (`/specify --edit`). El plan fija `400 {"error": "Invalid appointment id."}` y dice que `GetAppointmentQrController` captura `InvalidArgumentException` como `GetInternalAppointmentByIdController`. T022 lo hace explícito. El 500 del detalle es un bug aparte: se registra para `/fix`, fuera de esta spec |
| A2 | Inconsistencia (código) / P11 | ALTA | plan → contrato (cabecera `Content-Security-Policy: default-src 'none'…`), TM6, D2; T013 | `SecurityHeaders` se añade a todas las respuestas (`bootstrap/app.php:52`) y hace `headers->set('Content-Security-Policy', …)` **después** del controlador: la CSP propia del QR nunca llega. T013 fallaría, o T022 tendría que tocar un middleware global fuera del plan | Se quita la CSP propia del contrato. TM6 se apoya en la CSP global (`script-src 'self' 'nonce-…'`, sin `unsafe-inline`, y `object-src 'none'`), que bloquea un `<script>` dentro de un SVG abierto directamente, y en `nosniff`. Además, el SVG solo se pinta como `<img>`. T013 afirma que la respuesta lleva la CSP global sin `'unsafe-inline'` en `script-src`, y `nosniff` |
| A3 | Tareas / P2 | MEDIA | T016; `tests/Browser/checks-dialogs.mjs:110-130` | Las expectativas de `ui-audit` son `text`/`is`, `absent`, `visibleAfter` y nombres accesibles. No existe ninguna para "imagen cargada con al menos 240 × 240 px" (K1) ni para el nombre del archivo de descarga (TM5). El `<a download>` de D3 se crea y se libera en el clic, así que su atributo no queda para comprobarlo. Ninguna tarea extiende el arnés. El fallo forzado del QR (CA8) sí es posible con `answers` (`to: '/qr'`), porque `intercept` cubre `*/api/*` también para imágenes | T016 suma al arnés una expectativa `image` (`{ selector, loaded, minWidth, minHeight }`) y la demuestra primero contra una pantalla existente (como A44 de la 019). El nombre de la descarga se expone en el botón (`data-view-appointment-qr-filename`, que pone T023 al cargar el QR) y T016 lo comprueba con `text`/atributo. Archivos de T016: `screens.json` y `checks-dialogs.mjs` |
| A4 | Ambigüedad / Diseño | MEDIA | plan → D3; T023 | El adaptador usa `drawLightModules = false` y el SVG no tiene fondo. Si se dibuja en un `canvas` y se exporta a PNG, el fondo queda **transparente**: en un chat de WhatsApp en modo oscuro, módulos negros sobre fondo oscuro no se leen. Además, un SVG sin `width`/`height` puede tener `naturalWidth` 0 en algunos navegadores | D3 y T023: el `canvas` tiene tamaño fijo (1024 × 1024 px), se rellena de blanco antes de dibujar y la imagen se dibuja con `drawImage(img, 0, 0, 1024, 1024)`. La verificación manual de T023 abre el PNG sobre un fondo oscuro |
| A5 | Tareas | BAJA | T015; `AppointmentsArchitectureTest.php:20-24` | `appointmentsDomainViolations()` solo lee archivos de `Domain/` (ruta fija) y su patrón no incluye `chillerlan`. T015 no puede aplicarla tal cual a `Aplication/` | T015 generaliza el ayudante con la carpeta base (o añade uno para `Aplication/`) y suma `chillerlan` al patrón prohibido para esos archivos |
| A6 | Tareas | BAJA | T013 (RNF < 300 ms) | Una aserción de tiempo sobre una petición HTTP completa es inestable con `--parallel` en CI (017/R12) | El tiempo se mide sobre el adaptador en T010 (cota holgada, p. ej. < 150 ms por QR). T013 no mide tiempo |
| A7 | Cobertura | BAJA | plan → TM3; T011 | El test de TM3 (decodificar = id) no prueba lo que controla TM3: que el id no se puede adivinar | T011 suma un caso: `AppointmentId::random()` produce un UUID versión 4 (el control real de TM3) |
| A8 | Ambigüedad | BAJA | spec → K4; plan → P15 | K4 y K3 hablan de "modo oscuro", pero el panel no tiene modo oscuro (`app.css` sin variantes `dark`) | El plan ya lo aclara en P15. Al pasar por `/specify --edit` (A1), K3 dice "fondo blanco con margen" sin mencionar el modo oscuro |

## Comprobaciones sin hallazgo
- `sanctum.cookie` copia el token de la cookie `auth_token` a `Authorization`, así que un `<img>`
  del mismo origen se autentica igual que `fetch`.
- Una petición de imagen sin sesión a `api/*` responde JSON 401 (`shouldRenderJsonWhen` con
  `api/*`); el `<img>` dispara `error` y CA8 se cumple.
- El paciente recibe 403 en `staff` (`EnsureActiveStaff`, 014 CA5); CA10 coincide.
- `/{id}/qr` no choca con `/{id}` ni con `/patient/{patientId}` (otro número de segmentos).
- La CSP global admite `img-src 'self'`, así que D2 funciona sin cambios.
- `UiAuditSeeder` siembra citas asignadas, completadas y canceladas (días 06, 08, 20 y 22).
- Las entradas `agenda-cita-detalle`, `-cancelada`, `-pendiente` y `dashboard-detalle-*` existen
  y se pueden extender.

## Siguiente paso
Corregir A1–A8: primero `/specify --edit` (CA4 y K3), luego el plan y las tareas, y después
`/analyze` en modo delta.
