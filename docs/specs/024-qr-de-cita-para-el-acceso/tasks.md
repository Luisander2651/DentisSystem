---
spec: 024-qr-de-cita-para-el-acceso
plan: plan.md
status: approved
impl_base: 6b49614
---

# Tareas · 024 QR de cita para el acceso

> Línea base 2026-11-04 (`6b49614`, Pest en serie con `memory_limit=1G`): 1 983 pasan y ninguno falla (833 s). `npm audit --audit-level=high`: sin hallazgos. `composer audit`: 8 avisos de Symfony publicados el 2026-11-04 (`symfony/filesystem`, `http-foundation` ×4 incluido CVE-2026-118514, `http-kernel`, `routing` con CVE-2026-118516 y `yaml`), ajenos a esta spec; se tratan aparte (decisión del usuario).

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

Convenciones de esta spec:
- **Suite en verde por tarea:** cada test Pest nuevo o cambiado se escribe, se comprueba que falla
  por la razón esperada y se deja con `->skip('024: pendiente de T0xx')`. La tarea que lo
  implementa quita esa marca. Al cerrar cada tarea, la suite queda en verde salvo esas marcas. T050
  falla si queda alguna.
- **Tests de integración:** en `tests/Modules/Appointments/Integration`, base
  `AppointmentsIntegrationTestCase` declarada con `uses()`. Los de unidad, en
  `tests/Modules/Appointments/Unit/{Infrastructure,Application}`.
- "contrato" = [plan.md](plan.md) → "Contratos y datos"; "adaptador" = `ChillerlanQrCodeGenerator`
  con las opciones fijas del plan ("Cambios por módulo").
- `ui-audit` = `npm run test:ui -- --only agenda,dashboard,expedientes` a 390, 768 y 1440 px, **en
  local** (Chrome del equipo), tras `php artisan ui:audit-data`. No admite `skip`: se comprueba que
  falla por la razón esperada antes de T023 y que pasa después, y se anota el resultado.
- **Pest de regresión en serie** (`./vendor/bin/pest`, sin `--parallel`), por los timeouts de
  PostgreSQL en paralelo (017/R12). CI sigue corriendo `--parallel`.
- Comandos dentro del contenedor: `docker compose exec app …`.

## Constitution Check
Las tareas no cambian el Constitution Check de [plan.md](plan.md):

| Principio | Resultado | Cómo lo cumplen las tareas | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 024 y plan `approved` | manual: `aidd.py status` |
| P2 Test que falla antes | ✅ | T010–T016 antes que T020–T023; cada test se ve fallar por la razón esperada | test: T050 |
| P3 Capas del módulo | ✅ | T015 extiende el test de arquitectura a los archivos nuevos (T020, T021) | test: T015 |
| P4 Contrato de API primero | ✅ | T013 fija cada código y cabecera del contrato antes de T022 | test: T013 |
| P5 Autorización en el servidor | ✅ | T014 (denegado por actor) y T012 (`assertCan` primero) | test: T012, T014 |
| P6 Validación con FormRequest | ➖ | Sin cuerpo ni consulta; id con `AppointmentId` → 400 (plan) | test: T013 |
| P7 Errores sin detalles internos | ✅ | T013 fuerza el generador y el repositorio a fallar | test: T013 |
| P8 Secretos | ➖ | Sin variables nuevas | — |
| P9 Migraciones | ➖ | Sin migraciones | — |
| P10 Dependencias con ADR | ✅ | T002 instala exactamente la versión del ADR 0007 | manual: T002, `/review` |
| P11 Datos sensibles y amenazas | ✅ | TM1–TM8 con test (Cobertura); sin logs nuevos | test: T013, T010 |
| P12 Producción con aprobación | ✅ | T001 fija la línea base de auditorías; T095–T098 quedan para `/release` | manual: `/release` |
| P13 Lógica en el backend | ✅ | T013 prueba que el endpoint responde para citas cerradas; la web solo oculta | test: T013 |
| P14 Trazabilidad y auditoría | ❌ aceptado: sin registro de auditoría ni correlación hasta el objetivo 5 — aprobado por el usuario el 2026-11-04 (plan) | T091 anota los puntos de emisión de la 024 en `docs/observability.md` | manual: T091 |
| P15 Solo el sistema de diseño | ✅ | T023 usa `x-ui.button` y tokens; T050 corre `OnlyDesignTokensTest` y `ui-audit` | test: T050 |

## Preparación
- [x] T001 Medir la línea base: Pest completa en serie, `composer audit` y `npm audit --audit-level=high`, y anotar el resultado al inicio de este archivo (`impl_base` en el frontmatter) — `docs/specs/024-qr-de-cita-para-el-acceso/tasks.md` — hecho cuando: el resultado y el commit base quedan anotados
- [x] T002 Añadir `chillerlan/php-qrcode` 6.0.1 con `composer require --no-interaction chillerlan/php-qrcode:6.0.1` — `composer.json`, `composer.lock` — hecho cuando: `composer.json` fija `"6.0.1"` sin rango, `composer show` lista `chillerlan/php-qrcode 6.0.1` y `chillerlan/php-settings-container 3.3.0` y `composer audit` no da hallazgos nuevos frente a T001 (ADR 0007) — depende: T001
  - nota: 2026-11-04: la línea base ya tiene 8 avisos de Symfony publicados ese día (ver T001); se tratan aparte, por decisión del usuario.
- [x] T003 Crear el ayudante que rasteriza con GD los módulos oscuros del SVG (8 px por módulo y margen blanco) y lo decodifica con `QRCode::readFromBlob()` — `tests/Support/DecodesQrSvg.php` — hecho cuando: Pint pasa y un SVG generado con la librería en un test temporal se decodifica al texto original (luego lo ejercitan T010 y T013) — depende: T002

## Tests (antes de implementar)
- [x] T010 [P] Test de unidad del adaptador: el SVG empieza por `<?xml`/`<svg`; no contiene `<script`, `href` ni `xlink`; decodificado con T003 da el texto exacto, sin prefijo ni espacios; nivel de corrección M; cada QR se genera en menos de 150 ms (RNF, TM8; A6) — `tests/Modules/Appointments/Unit/Infrastructure/ChillerlanQrCodeGeneratorTest.php` — hecho cuando: falla porque la clase no existe — cubre: CA1, CA2 — depende: T003
- [x] T011 [P] Test de propiedades con Eris (`UsesEris`): para UUID v4 aleatorios, decodificar(svg(id)) = id, con pocas iteraciones; y `AppointmentId::random()` produce un UUID versión 4 (TM3; A7) — `tests/Modules/Appointments/Unit/Infrastructure/AppointmentQrPropertiesTest.php` — hecho cuando: falla porque la clase no existe — cubre: CA1 — depende: T003
- [x] T012 [P] Test de unidad del caso de uso: `assertCan('appointments.view-detail')` se llama antes del repositorio; sin permiso no se pide la cita ni se genera el QR; con cita inexistente devuelve `null` sin llamar al generador; id no UUID lanza la excepción de `AppointmentId` — `tests/Modules/Appointments/Unit/Application/GetAppointmentQrUseCaseTest.php` — hecho cuando: falla porque la clase no existe — cubre: CA3, CA4, CA10
- [x] T013 Test de integración del endpoint según el contrato — `tests/Modules/Appointments/Integration/AppointmentQrTest.php` — hecho cuando: falla con 404 de ruta inexistente — cubre: CA1, CA2, CA3, CA4, CA10, CA11, CA12 — depende: T003
  - nota: casos: 200 con `image/svg+xml` y contenido decodificado = id (CA1); dos peticiones, mismo contenido (CA2); cita completada y cancelada → 200 (P13); UUID sin cita → 404 con el mismo cuerpo que `GET /appointments/{id}` (CA3); `no-es-uuid` → 400 `{"error": "Invalid appointment id."}` (CA4; A1); paciente con sesión pidiendo el QR de **su** cita → 403 (CA10); mock de `QrCodeGeneratorInterface` que lanza y mock del repositorio que lanza → `{"error": "Internal server error"}` (CA11, TM7); `Cache-Control` con `no-store` y `private` (CA12, TM4); la CSP global sin `'unsafe-inline'` en `script-src` y `X-Content-Type-Options: nosniff` (TM6; A2); el SVG no contiene el nombre del paciente ni el del doctor (TM5). Sin aserción de tiempo (A6).
- [x] T014 Añadir la fila `'GET /appointments/{id}/qr' => $allStaff` al dataset de acceso — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: los casos nuevos fallan (404 de ruta) para todos los actores — cubre: CA9, CA10
- [x] T015 [P] Extender el test de arquitectura a `Aplication/Support/QrCodeGeneratorInterface.php` y `Aplication/UseCases/GetAppointmentQrUseCase.php`: sin `chillerlan`, `Illuminate\Support\Facades` ni `Infrastructure`. El ayudante actual solo lee `Domain/` y su patrón no incluye `chillerlan`: se generaliza con la carpeta base y el patrón suma `chillerlan` para esos archivos (A5) — `tests/Modules/Appointments/Unit/AppointmentsArchitectureTest.php` — hecho cuando: falla porque los archivos no existen — cubre: P3
- [x] T016 Añadir al arnés una expectativa `image` (`{ selector, loaded, minWidth, minHeight }`) y demostrarla contra una pantalla existente; luego añadir a `ui-audit` las comprobaciones del QR en el detalle — `tests/Browser/checks-dialogs.mjs`, `tests/Browser/screens.json` — hecho cuando: la expectativa nueva pasa y falla como se espera sobre una imagen existente (anotado), y en local `ui-audit` falla porque no encuentra `[data-view-appointment-qr]` — cubre: CA5, CA6, CA7, CA8
  - nota: 2026-11-04: `image` y `attribute` añadidas a `checks-dialogs.mjs`. La expectativa `image` se vio fallar sobre el QR antes de ajustar su tamaño (222 px con el mínimo en 224) y pasar después; el flujo de error se vio fallar con un texto esperado falso. No había otra imagen en un diálogo para demostrarla antes de T023.
  - nota: `agenda-cita-detalle-pendiente` y `dashboard-detalle-*` (y la entrada del detalle del expediente, si existe): expectativa `image` sobre el QR: cargada y de al menos 240 × 240 px (K1; A3); "Descargar QR" presente (K2); `alt` "Código QR de la cita del …"; sin salto al cargar (K4); flujo "Descargar QR" sin errores de consola; `data-view-appointment-qr-filename` del botón empieza por `cita-` y termina en `.png` (TM5; A3). Flujo de error con `answers` (`to: '/qr'`, estado 500). `agenda-cita-detalle` (completada) y `agenda-cita-detalle-cancelada`: sin bloque del QR ni "Descargar QR" (CA7). Flujo de error (CA8): con la petición del QR forzada a fallar, el resto del detalle visible y el aviso en el lugar del QR. Comprobar en `UiAuditSeeder` que hay citas asignadas, completadas y canceladas (las hay: días 06, 08, 20 y 22).

## Implementación
- [x] T020 Crear el puerto `QrCodeGeneratorInterface` (`svg(string $content): string`), el adaptador `ChillerlanQrCodeGenerator` (`final readonly`, opciones fijas del plan) y su enlace — `app/Modules/Appointments/Aplication/Support/QrCodeGeneratorInterface.php`, `app/Modules/Appointments/Infrastructure/Qr/ChillerlanQrCodeGenerator.php`, `app/Providers/AppServiceProvider.php` — hecho cuando: T010 y T011 pasan (sin `skip`) — cubre: CA1, CA2 — depende: T010, T011
  - nota: 2026-11-04: el ayudante de T003 ya no rasteriza con GD. El detector de la librería fallaba en ~1 de cada 1 000 UUID con cualquier escala (medido), y la propiedad de T011 salía inestable. Ahora arma la matriz exacta con los módulos oscuros del SVG y la pasa al decodificador (`Decoder::decodeMatrix()` por reflexión). Sigue decodificando la respuesta real (D6); 0 fallos en 1 000 UUID.
- [x] T021 Crear `GetAppointmentQrUseCase` (`execute(GetAppointmentByIdDTO): ?string`): `assertCan` → `AppointmentId` → `findById` → `svg($id->value)` — `app/Modules/Appointments/Aplication/UseCases/GetAppointmentQrUseCase.php` — hecho cuando: T012 y T015 pasan — cubre: CA3, CA4, CA10 — depende: T012, T015, T020
- [x] T022 Crear `GetAppointmentQrController` (invocable, `final`, los `catch` de `GetAppointmentByIdController` más `InvalidArgumentException` → 400 `{"error": "Invalid appointment id."}` como `GetInternalAppointmentByIdController` (A1), cabeceras del contrato sin CSP propia (A2)) y registrar la ruta junto a `/{id}/tracking` — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetAppointmentQrController.php`, `routes/api.php` — hecho cuando: T013 y T014 pasan — cubre: CA1–CA4, CA9–CA12 — depende: T013, T014, T021
- [x] T023 Añadir el bloque del QR al detalle y su lógica: se muestra solo en citas asignadas o reprogramadas; `<img>` de 240 × 240 px reservados con `src` a la API y `alt`; aviso con `role="alert"` en el evento `error`; "Descargar QR" dibuja la imagen en un `canvas` fijo de 1024 × 1024 px rellenado antes de blanco, con `drawImage(img, 0, 0, 1024, 1024)`, y descarga `cita-AAAA-MM-DD-HHMM.png`, nombre que también pone en `data-view-appointment-qr-filename` del botón (D3, D4; A3, A4); se limpia al abrir otra cita o cerrar, y `load`/`error` comprueban que la cita activa sigue siendo la misma (R4); el bloque va en un `try` propio (R3) — `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: T016 pasa en `ui-audit` local · verificación manual: escanear con un teléfono el QR de la pantalla y el PNG descargado; los dos muestran solo el UUID de la cita, y el PNG tiene fondo blanco al abrirlo sobre un fondo oscuro (A4) — cubre: CA5, CA6, CA7, CA8 — depende: T016, T022
  - nota: ui-audit local 2026-11-04: 21 visitas de las 7 entradas del detalle (agenda, inicio y expediente) a 390, 768 y 1440 px, 0 con fallos, incluidos los flujos "Descargar QR" y el de error. La composición a 1440 px de las 4 entradas con QR queda admitida en `baseline-1440.json` (024 CA5). Verificación manual con un teléfono pendiente del usuario.

## Cierre
- [x] T050 Comprobar la definición de terminado: Pint (`--dirty --format agent`); Pest completa en serie sin ninguna marca `024:` de `skip` y sin fallos nuevos frente a T001; `npm run build`; `ui-audit` completo en local; `composer audit` y `npm audit --audit-level=high`; `grep -rn "getMessage()"` sin resultados en el controlador nuevo; `aidd.py validate` sin errores — hecho cuando: todo pasa y el resultado queda anotado — depende: T020–T023
  - nota: 2026-11-04: Pint sin cambios pendientes; Pest completa en serie, 2 019 pasan y ninguno falla (1 983 de la línea base + 36), sin marcas `024:`; `npm run build` correcto; `ui-audit` completo, 306 visitas: las dos entradas del detalle pendiente de asistente y doctor diferían a 1440 px por el QR y se admitieron en `ADMITTED` (024 CA5) junto con las otras cuatro, y `tratamientos-crear` a 768 px agotó una espera una vez y pasó al repetirla (pantalla ajena a la spec); `composer audit` con los mismos 8 avisos de Symfony de T001 y `npm audit` limpio; `aidd.py validate` sin errores.
  - nota: la búsqueda de `getMessage()` encontró cuatro `catch` copiados del detalle en `GetAppointmentQrController`; tres no podían dispararse con este caso de uso. Se quitaron: queda el 400 por id inválido y el 403 lo da el render global de `AuthorizationException`, con el mismo cuerpo.
- [x] T051 [P] Documentar el bloque del QR dentro del detalle de la cita — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: el componente aparece con su uso, tamaño mínimo y textos — depende: T023
- [x] T052 [P] Añadir `GET /api/v1/appointments/{id}/qr` (staff) a "Superficie de ataque" — `docs/security.md` — hecho cuando: la fila está con su control — depende: T022

## Correcciones de /review (ronda 1)
- [x] T024 Que una respuesta de otra cita o llegada con el detalle cerrado nunca monte el QR, y limpiar el detalle se cierre como se cierre: en `open()`, tras cada `await`, salir si `activeAppointmentId !== appointmentId`; el `resetQr()` y `activeAppointmentId = null` pasan a un oyente del evento `close` del `<dialog>` (Escape, la X, el fondo y "Cerrar"), como pide `ui/dialog.js`. Test primero en ui-audit, entrada `agenda-cita-detalle-pendiente`: flujo "Una respuesta tardía no monta el QR" que retiene (`hold`) la respuesta de `GET /api/v1/appointments/{id}`, pulsa Escape, la suelta (`release`) y exige que `[data-view-appointment-qr]` siga oculto y `[data-view-appointment-qr-image]` sin `src`; y flujo "Cerrar con Escape limpia el QR" que, con el QR cargado, pulsa Escape y exige la imagen sin `src` — `resources/js/pages/agenda/view-appointment.js`, `tests/Browser/screens.json`, `tests/Browser/checks-dialogs.mjs` (solo si falta una expectativa para "sin atributo") — hecho cuando: los dos flujos fallan sin el cambio (anotado), pasan con él dos veces seguidas en `npm run test:ui -- --only agenda-cita-detalle-pendiente` y las entradas del detalle de inicio y expediente siguen en verde — cubre: CA5, CA6 — depende: T023
  - nota: añadida por /review: R1, R2
  - nota: 2026-11-08: los dos flujos ("Una respuesta tardía no monta el QR en un detalle cerrado" y "Cerrar con Escape limpia el QR") fallaron sin el cambio por su aserción (QR visible y `src` puesto) y pasan con él dos veces seguidas a 390, 768 y 1440 px. Comprobado rompiendo la guarda: sin la comprobación tras el `await` del detalle, el flujo de la respuesta tardía vuelve a fallar aunque exista el oyente de `close`. El oyente no limpia si el detalle ya se volvió a abrir (`modal.open`), para que un `close` atrasado no anule la cita nueva. `dashboard-detalle-*`, `expediente-administrador-cita-detalle*` y `agenda-cita-detalle*` siguen en verde. `checks-dialogs.mjs` no cambió: `absent` con `[src]` basta.
- [x] T025 Anunciar el fallo del QR por la región de estado del diálogo: en el `onerror` de `showQr` y en su `catch`, además de mostrar el aviso, `announceError('No pudimos cargar el código QR. Cierra el detalle y vuelve a abrirlo.')`. Test primero: el flujo "Si el QR no carga…" de `agenda-cita-detalle-pendiente` exige `announced` con "No pudimos cargar el código QR" — `resources/js/pages/agenda/view-appointment.js`, `tests/Browser/screens.json` — hecho cuando: el flujo falla sin el cambio (anotado) y pasa con él dos veces seguidas; revisión de accesibilidad: un solo anuncio por fallo (`announcedOnce`) — cubre: CA8 — depende: T024
  - nota: añadida por /review: R3
  - nota: 2026-11-08: con `role="alert"` en el aviso oculto, el arnés contaba su texto como anunciado al cargar y `announcedOnce` pasaba sin el arreglo; con `announceError` además, se habría anunciado dos veces. Por decisión del usuario se quita `role="alert"` del aviso (archivo extra: `resources/views/components/calendar/view-appointment-modal.blade.php`) y se ajusta su descripción en `docs/design/system.md`. Sin el `announceError` el flujo falla ("se anunció 0 veces") y con él pasa dos veces seguidas a 390, 768 y 1440 px; `dashboard-detalle-*` y el detalle del expediente siguen en verde.
- [x] T026 Endurecer las pruebas de navegador de CA6 y CA8: el flujo de CA8 exige además `visibleAfter` sobre `[data-view-appointment-qr-error]` (`ms: 0`); el arnés gana la expectativa `downloaded` ({ name, type }), que registra en la página los `click` de enlaces con `download` y el tipo del `Blob` pasado a `URL.createObjectURL`, y el flujo "Descargar QR" espera a que el botón esté habilitado (`waitFor` sobre `[data-view-appointment-qr-download]:not([disabled])`) y exige `downloaded` con nombre `cita-AAAA-MM-DD-HHMM.png` (patrón) y tipo `image/png` — `tests/Browser/checks-dialogs.mjs`, `tests/Browser/screens.json` — hecho cuando: con `downloadQr` saliendo al principio el flujo de CA6 falla, y sin `qrError.classList.remove('hidden')` el de CA8 falla (los dos anotados y revertidos); con el código actual pasan dos veces seguidas — cubre: CA6, CA8 — depende: T025
  - nota: añadida por /review: R4, R5
  - nota: 2026-11-08: el arnés gana la acción `waitFor` en los flujos y la expectativa `downloaded`, que registra los enlaces con `download` en lugar de seguirlos, con el tipo del `Blob` de su dirección. El flujo de CA6 pasa a llamarse "Descargar QR ofrece el PNG de la cita". Comprobado rompiendo lo protegido: con `downloadQr` saliendo al principio, CA6 falla ("no se ofreció ningún archivo"), y sin `qrError.classList.remove('hidden')`, CA8 falla ("ya no está a la vista"). Se restauró y los dos pasan dos veces seguidas a 390, 768 y 1440 px.
- [x] T027 Repetir la comprobación de la definición de terminado tras T024–T026: Pint, Pest completa en serie sin fallos nuevos frente a T050, `npm run build`, `ui-audit` completo en local y `aidd.py validate` — hecho cuando: todo pasa y el resultado queda anotado — depende: T024, T025, T026
  - nota: 2026-11-08: Pint sin cambios; Pest completa en serie, 2 019 pasan y ninguno falla (igual que T050); `npm run build` correcto; gitleaks sin hallazgos en los commits nuevos; `aidd.py validate` sin errores. `ui-audit` completo no cabía en la memoria del equipo de una vez (se cortó dos veces), así que se corrió en 9 tandas de 12 entradas: 105 entradas a 390, 768 y 1440 px. Los únicos fallos fueron por el límite de la API (429 tras 6 intentos), que una entrada pesada anterior deja casi agotado: `dashboard-administrador-flujos`, `dashboard-registrar-seguimiento`, `agenda-cita-detalle-pendiente`, `agenda-cita-reprogramar` y `agenda-cita-cancelar`. Todas pasan al repetirlas solas o en su tanda; ningún fallo de pantalla.

- [x] T028 Una sola apertura válida a la vez: `open()` toma un número de apertura (contador del módulo que incrementa al abrir y que el evento `close` también incrementa), y tras cada `await`, en `onload` y en `onerror` del QR sale si ya no es la apertura vigente; las cuatro guardas por id se sustituyen por esa comprobación y `closeModal()` solo cierra el diálogo (la limpieza queda en el oyente de `close`). Test primero en ui-audit, entrada `agenda-cita-detalle-pendiente`: flujo "Reabrir la misma cita no duplica el detalle", que retiene (`hold`, `times: 1`) la primera respuesta del detalle, pulsa Escape, vuelve a abrir la misma cita desde la lista del día, la suelta y exige `absent` de `[data-view-appointment-general] > :nth-child(7)` (seis filas); y flujo "Cerrar A y abrir B no muestra el QR de A", que retiene el detalle de A, pulsa Escape, abre otra cita asignada de la lista del mismo día, suelta y exige `absent` de `[data-view-appointment-qr-image][src*="c9b5cc9b-ca9b-5db8-9750-dcd95f733644"]` — `resources/js/pages/agenda/view-appointment.js`, `tests/Browser/screens.json` — hecho cuando: el flujo de la reapertura falla sin el cambio (anotado), los dos pasan con él dos veces seguidas en `npm run test:ui -- --only agenda-cita-detalle-pendiente`, y los flujos de T024–T026 y las entradas del detalle de inicio y expediente siguen en verde; revisión de accesibilidad: el foco vuelve al control que abrió el detalle — cubre: CA5, CA6 — depende: T027
  - nota: añadida por /review: R16
  - nota: 2026-11-09: "Reabrir la misma cita no duplica el detalle" falló sin el cambio (séptima fila presente) y "Cerrar A y abrir B no muestra el QR de A" pasaba ya con la guarda por id (B = `d8584879-bb42-53c7-b2d3-3317fad3c0a6`, la otra cita asignada del día 22). Con el contador de aperturas los dos pasan dos veces seguidas a 390, 768 y 1440 px. Comprobado rompiendo la guarda tras el `await` del detalle: fallan los tres flujos de la carrera (T024 y estos dos). `closeModal()` solo cierra el diálogo; la limpieza y el fin de la apertura van en el oyente de `close`, que también conserva el foco de vuelta (ui/dialog.js). Inicio, expediente y agenda en verde; `agenda-cita-reprogramar` y `agenda-cita-cancelar` dieron 429 tras otras entradas y pasan solas.
- [x] T029 Dar tamaño propio al SVG del QR: una salida `SizedQrMarkupSvg` que extiende `QRMarkupSVG` y añade a la raíz `width` y `height` iguales al lado del `viewBox` multiplicado por 8, usada como `outputInterface` en `ChillerlanQrCodeGenerator`. Test primero en `ChillerlanQrCodeGeneratorTest`: la raíz `<svg>` lleva `width` y `height` iguales y positivos, y el SVG se sigue decodificando al texto exacto — `app/Modules/Appointments/Infrastructure/Qr/SizedQrMarkupSvg.php`, `app/Modules/Appointments/Infrastructure/Qr/ChillerlanQrCodeGenerator.php`, `tests/Modules/Appointments/Unit/Infrastructure/ChillerlanQrCodeGeneratorTest.php` — hecho cuando: el test falla sin el cambio y pasa con él; `AppointmentQrTest`, `AppointmentQrPropertiesTest` y `AppointmentsArchitectureTest` siguen en verde; los flujos del QR de ui-audit siguen en verde (el QR se sigue viendo a 240 × 240 px) · verificación manual: en Firefox, "Descargar QR" de una cita asignada descarga un PNG legible con un teléfono — cubre: CA1, CA6 — depende: T027
  - nota: añadida por /review: R17
  - nota: 2026-11-09: el test nuevo de `ChillerlanQrCodeGeneratorTest` falló sin el cambio (sin `width`) y pasa con él; la raíz sale con `width="296" height="296"` para un `viewBox` de 37 × 37. chillerlan 6.0.1 no tiene una opción para el tamaño, así que `SizedQrMarkupSvg` sobrescribe `header()` (salida propia, mecanismo documentado de la librería; revisar al subir de versión, como el ayudante de T003). Generador, propiedades, endpoint y arquitectura en verde (30 tests); los flujos del QR de ui-audit siguen en verde con el QR a 240 × 240 px.
  - nota: verificación manual OK 2026-11-09 (usuario): en Firefox, "Descargar QR" descarga el PNG y el teléfono lee el identificador de la cita.
- [x] T030 Repetir la comprobación de la definición de terminado tras T028–T029: Pint, Pest completa en serie sin fallos nuevos frente a T027, `npm run build`, `ui-audit` completo en local (por tandas si no cabe en memoria) y `aidd.py validate` — hecho cuando: todo pasa y el resultado queda anotado — depende: T028, T029
  - nota: 2026-11-09: Pint sin cambios; Pest completa en serie, 2 020 pasan y ninguno falla (2 019 de T027 más el test de T029); `npm run build` correcto; gitleaks y semgrep sin hallazgos sobre lo nuevo; `aidd.py validate` sin errores. `ui-audit` en 9 tandas: 105 entradas, 333 visitas a 390, 768 y 1440 px, 0 con fallos (esta vez sin ningún 429).

## Integración y documentación
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura: puerto de QR en `Aplication/Support` y su adaptador `Infrastructure/Qr` (`chillerlan/php-qrcode`, ADR 0007); el QR que Core genera y el microservicio de asistencias lee (023)
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `observability.md`, los puntos de emisión de la 024 para el objetivo 5 (obtención del QR en `GetAppointmentQrUseCase`; accesos denegados). Sin cambios en `deployment.md` (sin variables ni pasos nuevos; `composer install` ya va en la imagen)
- [x] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [x] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097, con cuentas de prueba. El asistente abre el detalle de una cita asignada desde la agenda y desde el inicio: ve el QR y lo descarga; el PNG y la pantalla, escaneados con un teléfono, muestran solo el UUID (CA1, CA5, CA6). Una cita completada no muestra QR (CA7). En Loki, sin 500 en `/api/v1/appointments/*/qr`.
  - nota: 2026-11-09, en producción con v0.9.0. El usuario verificó con cuentas de prueba el QR en el detalle de una cita asignada desde la agenda y desde el inicio, la descarga del PNG, la lectura con un teléfono (solo el UUID) y que una cita completada no muestra QR (CA1, CA5, CA6, CA7): todo bien.
- [x] T096 Aprobación humana para producción
  - aprobado por Luisander2651 el 2026-11-09 07:02 para v0.9.0 ("desplegar v0.9.0 a producción")
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T013, T029 | T020, T022, T029 |
| CA2 | T010, T013 | T020, T022 |
| CA3 | T012, T013 | T021, T022 |
| CA4 | T012, T013 | T021, T022 |
| CA5 | T016, T024, T028 | T023, T024, T028 |
| CA6 | T016, T024, T026, T028, T029 | T023, T024, T028, T029 |
| CA7 | T016 | T023 |
| CA8 | T016, T025, T026 | T023, T025 |
| CA9 | T014 | T022 |
| CA10 | T012, T013, T014 | T021, T022 |
| CA11 | T013 | T022 |
| CA12 | T013 | T022 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021, T022 | T013, T014 |
| TM2 | — (fuera de Core; riesgo aceptado R1 del plan) | — |
| TM3 | T020 (contenido = id; UUID v4 existente) | T011 |
| TM4 | T022 | T013 |
| TM5 | T020, T023 | T013, T016 |
| TM6 | T020, T023 (CSP global existente) | T010, T013 |
| TM7 | T022 | T013 |
| TM8 | T022 (ruta bajo `throttle:api`) | T010 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Dependencias | T002 |
| Appointments · Aplication | T021, T020 (puerto) |
| Appointments · Infrastructure | T020 (adaptador), T022, T029 |
| Core · Providers | T020 |
| Rutas | T022 |
| Interfaz | T023, T024, T025, T028 |
| Pruebas de navegador | T016, T024, T025, T026, T028 |
| Docs | T051, T052, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Respuesta < 300 ms en local | T010 (< 150 ms por QR) |
| Corrección de error al menos M | T010 |
| SVG como imagen, nunca como marcado | T023 (revisión en `/review`), T016 |
| Texto alternativo del QR | T016 |
| Lectura real con un teléfono | T023 (verificación manual), T095 |
