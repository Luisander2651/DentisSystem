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
- [ ] T014 Añadir la fila `'GET /appointments/{id}/qr' => $allStaff` al dataset de acceso — `tests/Modules/Appointments/Integration/AppointmentsAccessControlTest.php` — hecho cuando: los casos nuevos fallan (404 de ruta) para todos los actores — cubre: CA9, CA10
- [ ] T015 [P] Extender el test de arquitectura a `Aplication/Support/QrCodeGeneratorInterface.php` y `Aplication/UseCases/GetAppointmentQrUseCase.php`: sin `chillerlan`, `Illuminate\Support\Facades` ni `Infrastructure`. El ayudante actual solo lee `Domain/` y su patrón no incluye `chillerlan`: se generaliza con la carpeta base y el patrón suma `chillerlan` para esos archivos (A5) — `tests/Modules/Appointments/Unit/AppointmentsArchitectureTest.php` — hecho cuando: falla porque los archivos no existen — cubre: P3
- [ ] T016 Añadir al arnés una expectativa `image` (`{ selector, loaded, minWidth, minHeight }`) y demostrarla contra una pantalla existente; luego añadir a `ui-audit` las comprobaciones del QR en el detalle — `tests/Browser/checks-dialogs.mjs`, `tests/Browser/screens.json` — hecho cuando: la expectativa nueva pasa y falla como se espera sobre una imagen existente (anotado), y en local `ui-audit` falla porque no encuentra `[data-view-appointment-qr]` — cubre: CA5, CA6, CA7, CA8
  - nota: `agenda-cita-detalle-pendiente` y `dashboard-detalle-*` (y la entrada del detalle del expediente, si existe): expectativa `image` sobre el QR: cargada y de al menos 240 × 240 px (K1; A3); "Descargar QR" presente (K2); `alt` "Código QR de la cita del …"; sin salto al cargar (K4); flujo "Descargar QR" sin errores de consola; `data-view-appointment-qr-filename` del botón empieza por `cita-` y termina en `.png` (TM5; A3). Flujo de error con `answers` (`to: '/qr'`, estado 500). `agenda-cita-detalle` (completada) y `agenda-cita-detalle-cancelada`: sin bloque del QR ni "Descargar QR" (CA7). Flujo de error (CA8): con la petición del QR forzada a fallar, el resto del detalle visible y el aviso en el lugar del QR. Comprobar en `UiAuditSeeder` que hay citas asignadas, completadas y canceladas (las hay: días 06, 08, 20 y 22).

## Implementación
- [ ] T020 Crear el puerto `QrCodeGeneratorInterface` (`svg(string $content): string`), el adaptador `ChillerlanQrCodeGenerator` (`final readonly`, opciones fijas del plan) y su enlace — `app/Modules/Appointments/Aplication/Support/QrCodeGeneratorInterface.php`, `app/Modules/Appointments/Infrastructure/Qr/ChillerlanQrCodeGenerator.php`, `app/Providers/AppServiceProvider.php` — hecho cuando: T010 y T011 pasan (sin `skip`) — cubre: CA1, CA2 — depende: T010, T011
- [ ] T021 Crear `GetAppointmentQrUseCase` (`execute(GetAppointmentByIdDTO): ?string`): `assertCan` → `AppointmentId` → `findById` → `svg($id->value)` — `app/Modules/Appointments/Aplication/UseCases/GetAppointmentQrUseCase.php` — hecho cuando: T012 y T015 pasan — cubre: CA3, CA4, CA10 — depende: T012, T015, T020
- [ ] T022 Crear `GetAppointmentQrController` (invocable, `final`, los `catch` de `GetAppointmentByIdController` más `InvalidArgumentException` → 400 `{"error": "Invalid appointment id."}` como `GetInternalAppointmentByIdController` (A1), cabeceras del contrato sin CSP propia (A2)) y registrar la ruta junto a `/{id}/tracking` — `app/Modules/Appointments/Infrastructure/Http/Controllers/GetAppointmentQrController.php`, `routes/api.php` — hecho cuando: T013 y T014 pasan — cubre: CA1–CA4, CA9–CA12 — depende: T013, T014, T021
- [ ] T023 Añadir el bloque del QR al detalle y su lógica: se muestra solo en citas asignadas o reprogramadas; `<img>` de 240 × 240 px reservados con `src` a la API y `alt`; aviso con `role="alert"` en el evento `error`; "Descargar QR" dibuja la imagen en un `canvas` fijo de 1024 × 1024 px rellenado antes de blanco, con `drawImage(img, 0, 0, 1024, 1024)`, y descarga `cita-AAAA-MM-DD-HHMM.png`, nombre que también pone en `data-view-appointment-qr-filename` del botón (D3, D4; A3, A4); se limpia al abrir otra cita o cerrar, y `load`/`error` comprueban que la cita activa sigue siendo la misma (R4); el bloque va en un `try` propio (R3) — `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: T016 pasa en `ui-audit` local · verificación manual: escanear con un teléfono el QR de la pantalla y el PNG descargado; los dos muestran solo el UUID de la cita, y el PNG tiene fondo blanco al abrirlo sobre un fondo oscuro (A4) — cubre: CA5, CA6, CA7, CA8 — depende: T016, T022

## Cierre
- [ ] T050 Comprobar la definición de terminado: Pint (`--dirty --format agent`); Pest completa en serie sin ninguna marca `024:` de `skip` y sin fallos nuevos frente a T001; `npm run build`; `ui-audit` completo en local; `composer audit` y `npm audit --audit-level=high`; `grep -rn "getMessage()"` sin resultados en el controlador nuevo; `aidd.py validate` sin errores — hecho cuando: todo pasa y el resultado queda anotado — depende: T020–T023
- [ ] T051 [P] Documentar el bloque del QR dentro del detalle de la cita — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: el componente aparece con su uso, tamaño mínimo y textos — depende: T023
- [ ] T052 [P] Añadir `GET /api/v1/appointments/{id}/qr` (staff) a "Superficie de ataque" — `docs/security.md` — hecho cuando: la fila está con su control — depende: T022

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: puerto de QR en `Aplication/Support` y su adaptador `Infrastructure/Qr` (`chillerlan/php-qrcode`, ADR 0007); el QR que Core genera y el microservicio de asistencias lee (023)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `observability.md`, los puntos de emisión de la 024 para el objetivo 5 (obtención del QR en `GetAppointmentQrUseCase`; accesos denegados). Sin cambios en `deployment.md` (sin variables ni pasos nuevos; `composer install` ya va en la imagen)
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - Sin staging (`deploy.environments: [local, prod]`): la verificación se hace en producción tras T097, con cuentas de prueba. El asistente abre el detalle de una cita asignada desde la agenda y desde el inicio: ve el QR y lo descarga; el PNG y la pantalla, escaneados con un teléfono, muestran solo el UUID (CA1, CA5, CA6). Una cita completada no muestra QR (CA7). En Loki, sin 500 en `/api/v1/appointments/*/qr`.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T013 | T020, T022 |
| CA2 | T010, T013 | T020, T022 |
| CA3 | T012, T013 | T021, T022 |
| CA4 | T012, T013 | T021, T022 |
| CA5 | T016 | T023 |
| CA6 | T016 | T023 |
| CA7 | T016 | T023 |
| CA8 | T016 | T023 |
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
| Appointments · Infrastructure | T020 (adaptador), T022 |
| Core · Providers | T020 |
| Rutas | T022 |
| Interfaz | T023 |
| Pruebas de navegador | T016 |
| Docs | T051, T052, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Respuesta < 300 ms en local | T010 (< 150 ms por QR) |
| Corrección de error al menos M | T010 |
| SVG como imagen, nunca como marcado | T023 (revisión en `/review`), T016 |
| Texto alternativo del QR | T016 |
| Lectura real con un teléfono | T023 (verificación manual), T095 |
