---
spec: 024-qr-de-cita-para-el-acceso
status: approved
constitution_version: 1.2.0
created: 2026-11-04
---

# Plan · 024 QR de cita para el acceso

## Enfoque técnico
**Appointments** suma una lectura: `GET /api/v1/appointments/{id}/qr`. Devuelve un SVG que solo
contiene el UUID de la cita. Va en el mismo grupo (`auth:sanctum` + `staff`) y con el mismo permiso
(`appointments.view-detail`) que `GET /appointments/{id}`. El caso de uso valida el id con `AppointmentId`
y el controlador convierte su `InvalidArgumentException` en 400, como la consulta interna (023);
`AppointmentsService::findById` da el 404. El QR lo dibuja un puerto de aplicación,
`QrCodeGeneratorInterface`, con un adaptador sobre `chillerlan/php-qrcode` 6.0.1 (ADR 0007). No se
guarda nada: no hay migración, R2 ni enlaces temporales.

En la web, el diálogo "Detalle de la cita" (`view-appointment-modal`) suma un bloque de QR que solo
se ve en citas asignadas o reprogramadas. Ese diálogo ya lo usan la agenda, el inicio y el
expediente. El bloque carga la imagen con `<img src="/api/v1/appointments/{id}/qr">`: es del mismo
origen, así que lo admite la CSP actual (`img-src 'self' data:`) y la sesión viaja en la cookie
como en el resto de la API. "Descargar QR" convierte en el navegador esa misma imagen a PNG y la
descarga, porque un SVG no se ve bien al mandarlo por WhatsApp (D3).

El escaneo, las reglas de entrada y salida y la puerta son del microservicio (spec 005 de SocketIO).
Core no recibe ninguna petición nueva por el escaneo (spec, "Flujo de acceso").

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 024 `approved` el 2026-11-04 | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene un test de integración o de unidad escrito antes que el código (Trazabilidad). No hay value objects nuevos. La propiedad "decodificar(svg(id)) = id" se prueba con Eris sobre UUID aleatorios. Lo que solo existe en el navegador (QR a la vista, sin salto, ausente en completadas, aviso de error, descarga) va en entradas y flujos nuevos de `npm run test:ui` | test: Trazabilidad; `AppointmentQrPropertiesTest`; `npm run test:ui` (entradas `agenda-detalle-qr`, `agenda-detalle-completada`) |
| P3 Capas del módulo | ✅ | Controller invocable → `GetAppointmentQrUseCase` (`assertCan` primero) → `AppointmentsService::findById` → `AppointmentsRepositoryInterface`. El generador de QR es un puerto de aplicación (`Aplication/Support/`), no de dominio: dibujar el QR es un formato de salida, no una regla de negocio (D1). El adaptador vive en `Infrastructure/Qr/` y se enlaza en `AppServiceProvider`. Sin dependencias nuevas entre módulos | test: `AppointmentsArchitectureTest` (ningún `chillerlan`, `Illuminate\Support\Facades` ni `Infrastructure` en `Domain/` ni en `Aplication/`); manual: `/review` |
| P4 Contrato de API primero | ✅ | Contrato en "Contratos y datos": un endpoint nuevo; los demás no cambian | test: `AppointmentQrTest` fija 200 (tipo y cabeceras), 400, 404 y 500; `AppointmentsAccessControlTest`, 401 y 403 |
| P5 Autorización en el servidor | ✅ | `auth:sanctum` + `staff` + `assertCan('appointments.view-detail')`, igual que el detalle. No hay comprobación de pertenencia: todo el staff ve el detalle de cualquier cita (014, 018 CA8), y el paciente no tiene acceso. Test de acceso denegado por actor (paciente, staff inactivo, sin sesión) | test: `AppointmentsAccessControlTest` (fila `GET /appointments/{id}/qr`); `AppointmentQrTest` (paciente con cita propia → 403) |
| P6 Validación con FormRequest | ➖ | El endpoint no recibe cuerpo ni consulta: solo el id de la ruta. `AppointmentId` lo valida y el controlador responde 400 como la consulta interna (023, spec CA4); `GET /appointments/{id}` responde hoy 500 en ese caso, un bug aparte (D9). Un parámetro de consulta que llegue se ignora | test: `AppointmentQrTest` (id no UUID → 400) |
| P7 Errores sin detalles internos | ✅ | 500 con `UnexpectedErrorResponse`. 404 y 400 con los textos de las rutas de citas, sin datos del paciente | test: `AppointmentQrTest` (generador y repositorio que fallan → `{"error": "Internal server error"}`) |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración ni variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ✅ | `chillerlan/php-qrcode` 6.0.1 fijada, de 231 días, con ADR 0007 y prueba de concepto | manual: diff de `composer.json` contra el ADR en `/review`; `composer audit` |
| P11 Datos sensibles y modelo de amenazas | ✅ | El QR solo lleva el UUID, sin datos personales ni clínicos, pero es una credencial de acceso: modelo de amenazas TM1–TM8. Sin logs nuevos. El archivo descargado no lleva el nombre del paciente (D4) | test: TM1–TM8; `AppointmentQrTest` (el SVG no contiene el nombre del paciente) |
| P12 Producción con aprobación humana | ✅ | Release con `/release` y aprobación del dueño. Línea base de dependencias antes de implementar: `composer audit` y `npm audit --audit-level=high` | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | El contenido del QR y la autorización se deciden en el servidor. La web solo oculta el QR en citas cerradas; el endpoint responde para cualquier cita existente (spec, "Decisiones"), y la regla real de quién entra la aplica el microservicio | test: `AppointmentQrTest` (cita completada → 200); manual: `/review` del JS |
| P14 Trazabilidad y auditoría | ❌ aceptado: la spec pide registrar cada obtención del QR y los accesos denegados, pero el registro de auditoría aún no existe y tampoco la correlación por petición, ambos del objetivo 5 del roadmap — aprobado por el usuario el 2026-11-04 | Igual que en 014, 017, 018, 019, 020, 021 y 023. Los puntos de emisión quedan anotados en "Observabilidad" | — |
| P15 Solo el sistema de diseño | ✅ | Bloque con `x-ui.button` y tokens de `app.css` (`bg-surface`, `border-field`, `rounded-box`, `text-muted`). El fondo del QR es `bg-surface` (blanco; el panel no tiene modo oscuro, así que K3 se cumple con ese fondo). Los colores internos del SVG son contenido de imagen, no estilos de la interfaz. Sin tokens nuevos | test: `OnlyDesignTokensTest`, `DesignTokensTest`, `npm run test:ui` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Dependencias | `composer.json`/`composer.lock`: `chillerlan/php-qrcode` `6.0.1` | Bajo: una transitiva; ADR 0007 |
| Appointments · Aplication | `Support/QrCodeGeneratorInterface` (`svg(string $content): string`); `UseCases/GetAppointmentQrUseCase` (`execute(GetAppointmentByIdDTO): ?string`, reutiliza el DTO del detalle) | Bajo |
| Appointments · Infrastructure | `Qr/ChillerlanQrCodeGenerator` (`final readonly`; opciones fijas: `QRMarkupSVG`, `outputBase64 = false`, `EccLevel::M`, `addQuietzone = true`, `connectPaths = false`, `drawLightModules = false`); `Http/Controllers/GetAppointmentQrController` (invocable, `final`, los `catch` de `GetAppointmentByIdController` más `InvalidArgumentException` → 400, como `GetInternalAppointmentByIdController`) | Bajo |
| Core · Providers | `AppServiceProvider`: `bind(QrCodeGeneratorInterface, ChillerlanQrCodeGenerator)` | Bajo |
| Rutas | `routes/api.php`: `Route::get('/{id}/qr', GetAppointmentQrController::class)` en el grupo `appointments` de `auth:sanctum` + `staff`, junto a `/{id}/tracking` | Bajo |
| Interfaz | `components/calendar/view-appointment-modal.blade.php`: bloque `data-view-appointment-qr` (figura con `<img>` de 240 × 240 px reservados, aviso de error y `x-ui.button` "Descargar QR"). `resources/js/pages/agenda/view-appointment.js`: lo muestra en citas abiertas, fija `src` y `alt`, gestiona `error` y la descarga, y lo limpia al abrir otra cita o cerrar | Medio: el diálogo lo comparten agenda, inicio y expediente; lo cubren las entradas de `test:ui` de las tres pantallas |
| Pruebas de navegador | `tests/Browser/screens.json`: entradas nuevas del detalle con QR y sin QR; `UiAuditSeeder` ya siembra citas abiertas y completadas (comprobar) | Bajo |
| Docs | `docs/architecture.md` (puerto de QR, dependencia), `docs/security.md` (superficie de ataque: endpoint nuevo), `docs/design/system.md` (bloque de QR dentro del detalle), `CHANGELOG.md` | Bajo |

## Contratos y datos
### `GET /api/v1/appointments/{id}/qr`
- Middlewares: `throttle:api`, `sanctum.cookie`, `auth:sanctum`, `staff`. Permiso:
  `appointments.view-detail` (admin, asistente, doctor).
- Request: sin cuerpo ni consulta. `id`: UUID de la cita.

| Código | Cuándo | Cuerpo | Cabeceras |
|---|---|---|---|
| 200 | La cita existe (en cualquier estado) | SVG; decodificado = `id` exacto | `Content-Type: image/svg+xml`, `Cache-Control: private, no-store`, `Content-Disposition: inline; filename="cita.svg"`; además las globales de `SecurityHeaders` (CSP con `script-src 'self' 'nonce-…'` y `object-src 'none'`, `X-Content-Type-Options: nosniff`) |
| 400 | `id` no es un UUID | `{"error": "Invalid appointment id."}` | JSON |
| 401 | Sin sesión | `{"message": "Unauthenticated."}` | JSON |
| 403 | Paciente, staff inactivo o sin permiso | El de `EnsureActiveStaff` / `AuthorizationException` | JSON |
| 404 | UUID sin cita | `{"error": "Appointment not found."}` | JSON |
| 429 | Más de 100 peticiones por minuto del mismo usuario | El de `throttle:api` | JSON |
| 500 | Fallo inesperado (generador o base de datos) | `{"error": "Internal server error"}` | JSON |

- El 404 usa el texto de `GET /appointments/{id}` (CA3) y el 400 el de la consulta interna (023,
  CA4). La CSP no es propia de esta respuesta: `SecurityHeaders` la pone en todas y sobrescribiría
  cualquier otra (A2).
- No cambian: `GET /appointments/{id}`, `/internal/appointments/*` ni `/auth/me`.

### Datos
Sin migraciones ni tablas. El QR se genera en cada petición.

## Estrategia de pruebas
**Integración** (`tests/Modules/Appointments/Integration/AppointmentQrTest.php`, base
`AppointmentsIntegrationTestCase`):
- 200 con `image/svg+xml`; el SVG, rasterizado y decodificado con el ayudante, es exactamente el id
  (CA1). Dos peticiones devuelven el mismo contenido (CA2).
- La cita completada o cancelada también devuelve 200 (el endpoint no filtra por estado; P13).
- 404 con un UUID sin cita (CA3); 400 con `no-es-uuid` (CA4).
- Paciente con sesión pidiendo el QR de **su propia** cita → 403 (CA10).
- Generador que lanza (mock de `QrCodeGeneratorInterface`) y repositorio que lanza → 500 genérico
  (CA11).
- Cabeceras `Cache-Control: private, no-store` (CA12), y la CSP global sin `'unsafe-inline'` en
  `script-src` y `nosniff` (TM6).
- Id no UUID → 400 `{"error": "Invalid appointment id."}` (CA4).
- El SVG no contiene el nombre del paciente ni el del doctor.

**Acceso** (`AppointmentsAccessControlTest`): fila `'GET /appointments/{id}/qr' => $allStaff`. El
dataset ya cubre admin, asistente, doctor, staff inactivo, paciente y sin sesión (CA9, CA10).

**Unidad** (`tests/Modules/Appointments/Unit/`):
- `Infrastructure/ChillerlanQrCodeGeneratorTest`: el SVG empieza por `<?xml` / `<svg`, no contiene
  `<script`, `href` ni `xlink`, y decodificado da el texto de entrada, sin prefijo ni espacios.
- `AppointmentQrPropertiesTest` (Eris, `UsesEris`): para UUID v4 aleatorios, decodificar(svg(id)) = id
  (pocas iteraciones: cada decodificación tarda decenas de ms).
- `Application/GetAppointmentQrUseCaseTest`: `assertCan` se llama antes del repositorio; con
  permiso denegado no se genera ningún QR.
- `AppointmentsArchitectureTest`: incluye los archivos nuevos de `Aplication/`.

**Ayudante de test** (`tests/Support/DecodesQrSvg.php`): rasteriza con GD los `path` oscuros del SVG
(8 px por módulo, margen blanco) y lo lee con `QRCode::readFromBlob()`. Probado en la prueba de
concepto del ADR 0007.

**Navegador** (`npm run test:ui`, `tests/Browser/screens.json`):
- `agenda-detalle-qr` (asistente; también desde el inicio y el expediente): el detalle de una cita
  asignada muestra la imagen del QR completa a 390, 768 y 1440 px, con al menos 240 × 240 px (K1),
  y "Descargar QR" (K2). Sin salto al cargar (K4). Flujo: "Descargar QR" no deja errores en la
  consola.
- `agenda-detalle-completada`: sin bloque de QR ni "Descargar QR" (CA7, K2).
- Flujo de error: con la petición del QR forzada a fallar, el resto del detalle se ve y aparece el
  aviso (CA8). Si el arnés no permite interceptar la petición, se fuerza abriendo el detalle de
  una cita que el arnés borra antes (404).

**Manual**: escanear el QR de la pantalla y el PNG descargado con un teléfono (lector genérico) y
comprobar que muestran solo el UUID.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Spoofing / Elevation: un paciente, staff inactivo o visitante sin sesión obtiene el QR de una cita y entra con él | A01:2025 · API1:2023 | `GET /appointments/{id}/qr` | `auth:sanctum` + `staff` + `assertCan('appointments.view-detail')` | `AppointmentsAccessControlTest`; `AppointmentQrTest` (paciente con cita propia → 403) |
| TM2 | Spoofing: alguien que tiene el QR (foto, reenvío) entra en lugar del paciente | A07:2025 · A06:2025 (diseño inseguro) | Puerta (fuera de Core) | Aceptado en la spec para la maqueta: el QR es una credencial al portador. Lo mitigan el administrador, que compara a la persona con el nombre del paciente que devuelve el microservicio (petición a SocketIO 005), y las reglas del microservicio: solo hoy, solo estados abiertos, una entrada. Revisar antes de tener datos reales (R1) | — (fuera de Core; registrado en "Riesgos y mitigaciones") |
| TM3 | Information disclosure: adivinar el id de otra cita para fabricar su QR | A01:2025 | `UuidIdentifier` | UUID v4 de `Str::uuid()` (122 bits aleatorios). Sin endpoint que enumere citas fuera del staff | `AppointmentQrPropertiesTest` (`AppointmentId::random()` es UUID versión 4) |
| TM4 | Information disclosure: el QR o la respuesta quedan en una caché compartida, en el historial o en un proxy | A02:2025 (configuración insegura) | Respuesta del endpoint | `Cache-Control: private, no-store` | `AppointmentQrTest` (cabeceras) |
| TM5 | Information disclosure: el SVG o el nombre del archivo descargado llevan datos personales | A01:2025 · LFPDPPP | Adaptador; `view-appointment.js` | Solo el UUID en el QR; el archivo se llama `cita-AAAA-MM-DD-HHMM.png`, sin nombre del paciente | `AppointmentQrTest` (sin nombres en el SVG); ui-audit (atributo `download` del enlace) |
| TM6 | Tampering / XSS: el SVG ejecuta código al abrirse en el panel o directamente en una pestaña | A05:2025 (inyección) | Adaptador; respuesta | El SVG lo genera la librería a partir de un UUID ya validado, sin texto libre. En el panel se pinta como `<img>` (los scripts de un SVG en `<img>` nunca corren). Abierto directo: la CSP global (`script-src 'self' 'nonce-…'`, sin `unsafe-inline`; `object-src 'none'`) bloquea scripts en línea, y `nosniff` | `ChillerlanQrCodeGeneratorTest` (sin `<script`, `href`); `AppointmentQrTest` (CSP global y `nosniff`) |
| TM7 | Information disclosure: un fallo del generador filtra rutas o clases | A10:2025 (manejo de errores) | Controlador | `UnexpectedErrorResponse` | `AppointmentQrTest` (500 genérico) |
| TM8 | Denial of service: pedir muchos QR seguidos para cargar la CPU | A06:2025 · API4:2023 | Endpoint | `throttle:api` (100/min por usuario); generar un QR cuesta ~14 ms y no toca disco | `ChillerlanQrCodeGeneratorTest` (< 150 ms por QR) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: SVG decodificado = id exacto, sin URL, prefijo, JSON ni espacios | Adaptador; caso de uso; controlador; ruta | `AppointmentQrTest` (decodifica la respuesta); `ChillerlanQrCodeGeneratorTest`; `AppointmentQrPropertiesTest` |
| CA2: mismo contenido en cada petición, nada guardado | Generación sin estado | `AppointmentQrTest` (dos peticiones, mismo contenido decodificado) |
| CA3: UUID sin cita → 404 | `findById` → `null` → 404 | `AppointmentQrTest` |
| CA4: id no válido → 400 | `AppointmentId`; `catch (InvalidArgumentException)` en el controlador | `AppointmentQrTest` |
| CA5: QR dentro del detalle en citas abiertas, desde agenda, inicio y expediente | `view-appointment-modal`; `view-appointment.js` | ui-audit `agenda-detalle-qr`, `dashboard-detalle-*`, expediente (K1, K4) |
| CA6: "Descargar QR" descarga una imagen | `view-appointment.js` (PNG desde la imagen; D3) | ui-audit flujo "Descargar QR" · manual: escanear el PNG |
| CA7: sin QR en completadas o canceladas | `view-appointment.js` | ui-audit `agenda-detalle-completada` (K2) |
| CA8: fallo del QR → aviso en su lugar, resto del detalle igual | `view-appointment.js` (evento `error` del `<img>`) | ui-audit flujo de error |
| CA9 (abuso): sin sesión → 401 | `auth:sanctum` | `AppointmentsAccessControlTest` |
| CA10 (abuso): paciente → 403, incluso con su propia cita | `staff` | `AppointmentsAccessControlTest`; `AppointmentQrTest` |
| CA11 (abuso): fallo al generar → 500 genérico | `UnexpectedErrorResponse` | `AppointmentQrTest` |
| CA12 (abuso): sin cachés | Cabeceras de la respuesta | `AppointmentQrTest` |
| TM1: QR para actores no autorizados | Middlewares + `assertCan` | `AppointmentsAccessControlTest`, `AppointmentQrTest` |
| TM2: QR reenviado o fotografiado | Fuera de Core (microservicio y administrador) | — (riesgo aceptado R1) |
| TM3: adivinar ids | UUID v4 | `AppointmentQrPropertiesTest` |
| TM4: cachés | `no-store` | `AppointmentQrTest` |
| TM5: datos personales en SVG o archivo | Adaptador; nombre del archivo | `AppointmentQrTest`; ui-audit |
| TM6: SVG con código | Librería + `<img>` + CSP | `ChillerlanQrCodeGeneratorTest`, `AppointmentQrTest` |
| TM7: detalles internos en 500 | `UnexpectedErrorResponse` | `AppointmentQrTest` |
| TM8: carga por peticiones masivas | `throttle:api`; generación barata | `ChillerlanQrCodeGeneratorTest` (tiempo) |
| RNF: < 300 ms en local | Generación en memoria | `ChillerlanQrCodeGeneratorTest` (< 150 ms por QR; sin medir la petición HTTP, inestable en paralelo) |
| RNF: corrección de error al menos M | Opciones del adaptador | `ChillerlanQrCodeGeneratorTest` (nivel de corrección leído del resultado decodificado) |
| RNF: SVG como imagen, nunca como marcado | `<img src>` | manual: `/review` del JS (sin `innerHTML` con la respuesta) |
| RNF: texto alternativo "Código QR de la cita del <fecha>" | `view-appointment.js` | ui-audit (nombres accesibles) |

## Observabilidad
- Logs nuevos: ninguno. Los errores inesperados quedan en el log saneado de
  `UnexpectedErrorResponse`, como en toda la API.
- Eventos de auditoría: P14 ❌ aceptado (aprobado por el usuario el 2026-11-04). Puntos de emisión para cuando
  exista el registro: obtención del QR en `GetAppointmentQrUseCase` (actor, cita, resultado);
  accesos denegados en el render de `AuthorizationException` y en `EnsureActiveStaff` (CA9, CA10).
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: abrir el detalle de una cita asignada en producción, ver el QR,
  descargarlo y escanearlo con un teléfono. En Loki, sin 500 en `/api/v1/appointments/*/qr`.

## Descubribilidad
No aplica: la ruta es privada de la API del panel; la spec no crea ni cambia rutas públicas.

## Rollout
- Feature flag: no aplica. Es una lectura nueva y un bloque nuevo en el detalle.
- Orden de despliegue: una sola imagen con `deploy.sh` (incluye `composer install` con el lock
  nuevo); sin migraciones.
- Compatibilidad: ningún endpoint existente cambia. El microservicio no depende de este endpoint.
- Rollback: `docker/prod/rollback.sh` a la versión anterior. No hay datos que revertir; los QR ya
  enviados siguen funcionando porque solo contienen el id de la cita.
- Métricas a vigilar tras el deploy: 500 y 429 en `/api/v1/appointments/*/qr` en Loki.

## Decisiones (→ ADR si son arquitectónicas)
- **D1** El puerto del generador va en `Aplication/Support/`, no en `Domain/`: dibujar un QR es un
  formato de salida y el dominio de citas no cambia. Es el primer puerto que no es repositorio en
  el módulo; no requiere ADR porque no cruza módulos ni cambia capas.
- **D2** El detalle carga el QR con `<img src>` a la API, no con `fetch` + `blob:`. La CSP actual no
  admite `blob:` en `img-src` y cambiarla abre más de lo necesario; `<img>` además impide que el SVG
  ejecute nada. Costo: ante un fallo, el aviso es genérico (el navegador no expone el código HTTP),
  lo que basta para CA8.
- **D3** "Descargar QR" descarga un **PNG** generado en el navegador: dibuja la imagen ya cargada
  en un `canvas` fijo de 1024 × 1024 px, rellenado antes de blanco (el SVG no tiene fondo y un PNG
  transparente no se lee en un chat oscuro), con `drawImage(img, 0, 0, 1024, 1024)` (un SVG sin
  `width`/`height` puede tener `naturalWidth` 0), y usa `toBlob()`. El nombre del archivo queda
  también en el botón (`data-view-appointment-qr-filename`) para que `ui-audit` lo compruebe. El SVG no se previsualiza al mandarlo por WhatsApp. Es solo
  presentación (P13) y no necesita otro endpoint ni otro formato en la API. El `<a download>` se
  dispara en el momento y su URL se libera enseguida, así que no hace falta cambiar la CSP.
- **D9** `GET /appointments/{id}` responde 500, no 400, con un id que no es UUID: `UuidIdentifier`
  lanza `InvalidArgumentException` y su controlador no la captura. Es un bug de la 007/014 fuera de
  esta spec; se corrige con `/fix`. El QR no copia ese comportamiento (A1).
- **D4** Nombre del archivo: `cita-AAAA-MM-DD-HHMM.png`, sin datos del paciente (TM5).
- **D5** `chillerlan/php-qrcode` 6.0.1 → ADR 0007 (P10).
- **D6** CA1 se prueba sobre la respuesta real: un ayudante de test rasteriza el SVG con GD y lo
  decodifica con la misma librería. Comparar el SVG con el que genera el adaptador no bastaría,
  porque dos errores iguales se anularían.
- **D8** Sin `/design-spec`: el cambio es un bloque dentro de un diálogo que ya existe y K1–K4 se
  comprueban con `npm run test:ui` (decisión del usuario, 2026-11-04).
- **D7** El endpoint no filtra por estado: responde para cualquier cita existente (spec,
  "Decisiones"). La web solo lo muestra en citas abiertas, y la regla de quién entra es del
  microservicio.

## Impacto en arquitectura
- `docs/architecture.md`: el módulo Appointments tiene un puerto de aplicación para generar QR y
  su adaptador (`chillerlan/php-qrcode`); la integración con el microservicio de asistencias (023)
  suma el QR que Core genera y el microservicio lee.
- `docs/security.md` → "Superficie de ataque": `GET /api/v1/appointments/{id}/qr` (staff).

## Riesgos y mitigaciones
- **R1** El QR es una credencial al portador sin firma ni uso único (TM2). Aceptado para la maqueta
  (spec, "Decisiones"). Antes de cargar datos reales: decidir si basta con la verificación del
  administrador o se vuelve a un código firmado.
- **R2** El ayudante de test depende del formato de los `path` del SVG. Si la librería lo cambia, el
  test de CA1 falla en lugar de pasar en falso. La versión está fijada.
- **R3** El diálogo del detalle es compartido por tres pantallas: un error en el JS del QR rompería
  el detalle en todas. Mitigación: el bloque del QR va en un `try` propio y CA8 se prueba en el
  navegador; las entradas de `test:ui` de las tres pantallas cubren el detalle.
- **R4** Carrera al abrir dos detalles seguidos: la imagen de la cita anterior podría verse en la
  nueva. Mitigación: se limpia `src` al abrir y el `load`/`error` comprueba que la cita activa
  sigue siendo la misma.
- **R5** El ack del microservicio aún no incluye el nombre del paciente (TM2). Es una petición al
  repositorio SocketIO (spec 005), fuera de este plan.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-04 | Creación en `draft` | /plan 024 |
| 2026-11-04 | Aprobado, con P14 ❌ aceptado y sin `/design-spec` (D8) | Aprobación del usuario |
| 2026-11-04 | Correcciones de `/analyze` ronda 1 (A1–A8): 400 como la consulta interna, CSP global, PNG con fondo blanco, tiempo en la prueba de unidad, TM3 con UUID v4; D9 | Decisión del usuario |
