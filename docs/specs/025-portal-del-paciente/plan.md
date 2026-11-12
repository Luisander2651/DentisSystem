---
spec: 025-portal-del-paciente
status: approved
constitution_version: 1.2.1
created: 2026-11-10
---

# Plan · 025 Portal del paciente

## Enfoque técnico
El portal es de solo lectura y se arma con lo que ya existe. La API suma cuatro lecturas bajo
`/api/v1/portal`, que identifican al paciente **por su sesión** y nunca por un dato de la petición:
sus citas, su próxima cita, las recetas de una cita y el QR de una cita. Cada caso de uso obtiene
primero el paciente activo de la sesión y después comprueba que la cita es suya; una cita ajena
responde igual que una inexistente (404). Las respuestas usan recursos propios del portal, que solo
llevan los campos que el paciente puede ver, y todas salen con `Cache-Control: private, no-store`.

Para cerrar RS1.c, el guard `sanctum` fija el provider `users` y aparece un guard `patient` (mismo
driver, provider `patients`): cada guard solo acepta tokens de su tipo de cuenta. Las rutas pasan a
autenticarse con `auth:sanctum,patient` y la separación la hacen dos middlewares hermanos: `staff`
(ya existe) y `patient` (nuevo, `EnsureActivePatient`). Así un paciente sigue recibiendo 403 en las
rutas del personal (CA14, como fija la 014) y el personal recibe 403 en el portal (CA16).

En la web, la rama del paciente de `/dashboard` y la pantalla nueva `/mis-citas` siguen el
candidato c3 de [design.md](design.md): solo componentes existentes. El bloque del QR del detalle
(024) y el dibujo de recetas e historial se extraen a piezas compartidas, sin cambiar lo que ve el
personal. No hay migraciones ni dependencias nuevas.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 025 `approved` y `design.md` `approved` (c3) | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y cada TM tiene su test escrito antes que el código (Trazabilidad). No hay value objects nuevos; la regla de "próxima cita" lleva un test de propiedades con Eris. Lo que solo existe en el navegador va en entradas nuevas de `npm run test:ui` | test: `./vendor/bin/pest --parallel` y `npm run test:ui` en CI; Trazabilidad |
| P3 Capas del módulo | ✅ | Controller invocable → UseCase → Domain Service → repositorio. Los casos de uso no leen `Auth`: piden el paciente a `AuthorizationServiceInterface::currentPatientId()` (D2). AppointmentTracking ya depende de `AppointmentsService` (018); no hay dependencias nuevas entre módulos | manual: `/review` y búsqueda de `use App\\Modules\\*\\Infrastructure` dentro de `Domain/`; test: `AppointmentsArchitectureTest` incluye los archivos nuevos de `Aplication/` |
| P4 Contrato de API primero | ✅ | Contrato de las cuatro rutas en "Contratos y datos"; ninguna ruta existente cambia su respuesta | test: `PortalAppointmentsTest`, `PortalAppointmentQrTest` y `PortalPrescriptionsTest` fijan cada código HTTP |
| P5 Autorización en el servidor | ✅ | `auth:sanctum,patient` + `patient` + `currentPatientId()` en el caso de uso + comprobación de pertenencia de la cita. Test de acceso denegado por actor: sin sesión, administrador, asistente, doctor, personal inactivo, paciente inactivo y paciente ajeno | test: `PortalAccessControlTest` (dataset por ruta y actor), `GuardSeparationTest`; manual: `/review` con `shared/security-checklist.md` |
| P6 Validación con FormRequest | ➖ | Las rutas nuevas no reciben cuerpo ni consulta: solo el id de la cita en la ruta, que valida `AppointmentId` y responde 400, como permite P6 desde la versión 1.2.1 (`/analyze 025` A1). Cualquier parámetro que llegue se ignora (CA13) | test: `PortalAppointmentQrTest` y `PortalPrescriptionsTest` (id no UUID → 400); `PortalAppointmentsTest` (`patient_id` ajeno ignorado) |
| P7 Errores sin detalles internos | ✅ | 500 con `UnexpectedErrorResponse`; 400, 404 y 409 con textos fijos sin datos del paciente | test: `PortalUnexpectedErrorTest` (repositorio y generador que fallan → `{"error": "Internal server error"}`); `grep -rn "getMessage()"` en los controladores nuevos |
| P8 Secretos fuera del repositorio | ➖ | Sin configuración secreta ni variables nuevas; `config/auth.php` no lee `env()` nuevos | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones | — |
| P10 Dependencias nuevas con ADR | ➖ | Sin paquetes nuevos: el QR usa `chillerlan/php-qrcode` (ADR 0007) | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Recetas y recomendaciones son datos de salud: modelo de amenazas TM1–TM11. Sin logs nuevos; los recursos del portal excluyen motivo, síntomas, diagnóstico, procedimiento y observaciones | test: `aidd.py validate`; `PortalNoClinicalDataTest`; manual: `/review` de las llamadas a `Log::` |
| P12 Producción con aprobación humana | ✅ | Release con `/release` y aprobación del dueño. Línea base de dependencias: ver D10 | manual: `/release` exige la confirmación y bloquea con hallazgos de `security.tools` |
| P13 Lógica de negocio en el backend | ✅ | Qué es la "próxima cita", el orden del historial, en qué citas hay QR y en cuáles recetas lo decide el servidor. La web solo pinta | test: `PortalAppointmentsTest`, `PortalAppointmentQrTest` (cita cerrada → 409); manual: `/review` del JS y Blade tocados |
| P14 Trazabilidad y auditoría | ❌ aceptado: la spec lista los eventos, pero el registro de auditoría y la correlación por petición no existen todavía (objetivo 5 del roadmap) — aprobado por el usuario el 2026-11-10 | Igual que en 014, 017–021, 023 y 024. Puntos de emisión anotados en "Observabilidad" | — |
| P15 Solo el sistema de diseño | ✅ | c3 usa solo componentes y tokens existentes; `design.md` no pide cambios al sistema | test: `OnlyDesignTokensTest` y `DesignTokensTest`; `npm run test:ui` en CI |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Configuración | `config/auth.php`: guard `sanctum` con `provider: users`; guard nuevo `patient` (`driver: sanctum`, `provider: patients`) | **Alto**: afecta a todas las sesiones; lo cubren `GuardSeparationTest` y la suite de acceso existente |
| Core · Middlewares | Nuevo `EnsureActivePatient` (alias `patient`): solo un `PatientModel` activo; 403 en JSON para `api/*` y página "Sin permiso" en la web. Nuevo `NoStoreResponse` (alias `no-store`): `Cache-Control: private, no-store` en toda respuesta del grupo, también en los errores | Bajo |
| Core · Authorization | `AuthorizationServiceInterface::currentPatientId(): string` y su implementación en `CurrentActorAuthorizationService`: devuelve el id si el actor es un paciente activo; si no, `AuthorizationException` (403) | Bajo |
| Core · Http | `ShowDashboardPageController`: un paciente inactivo recibe 403, como el personal inactivo | Bajo |
| `bootstrap/app.php` | Alias `patient` y `no-store` | Bajo |
| Rutas | `routes/api.php` y `routes/web.php`: `auth:sanctum` → `auth:sanctum,patient` en todos los grupos (los middlewares `staff` y `only.admin` siguen rechazando al paciente con 403). Grupo nuevo `portal` (`auth:sanctum,patient`, `patient`, `no-store`) con las cuatro rutas. Web: `/mis-citas` (`auth:sanctum,patient` + `patient`), nombre `portal.appointments` | Medio: `GuestRedirectTest` busca la cadena `auth:sanctum` y hay que ajustarlo |
| Vistas que leen el guard | `layouts/error.blade.php` usa `auth('sanctum')` para el "camino de vuelta" de las páginas de error: pasa a reconocer también al paciente (guard `patient`). Va en la misma tarea que el cambio de guards, para que `NotFoundPageTest` y `WebAccessDeniedPagesTest` sigan en verde. `layouts/admin.blade.php` no cambia: el paciente nunca lo pinta (`/analyze 025` A2, A15) | Medio: lo cubren `NotFoundPageTest` y `WebAccessDeniedPagesTest` |
| Appointments · Aplication | `UseCases/GetPatientAppointmentsUseCase` (todas las citas del paciente, de la más reciente a la más antigua), `GetPatientNextAppointmentUseCase` (la asignada o reprogramada más cercana desde hoy; recibe la fecha de hoy en su DTO), `GetPatientAppointmentQrUseCase` (cita propia y abierta). DTOs `final readonly` con `::create()`. Reutilizan `AppointmentsService` y `QrCodeGeneratorInterface`; sin cambios en el repositorio | Bajo |
| Appointments · Infrastructure | Controladores invocables `final`: `GetPortalAppointmentsController`, `GetPortalNextAppointmentController`, `GetPortalAppointmentQrController`. Recurso `PortalAppointmentResource` | Bajo |
| AppointmentTracking · Application | `UseCases/GetPatientPrescriptionsUseCase`: cita propia; si está completada y tiene seguimiento, sus recomendaciones y recetas; en cualquier otro caso, vacío | Bajo |
| AppointmentTracking · Infrastructure | `GetPortalPrescriptionsController`; recursos `PortalPrescriptionsResource` y `PortalPrescriptionResource` | Bajo |
| Interfaz · piezas compartidas | Componente nuevo `components/appointments/qr-block.blade.php`, extraído tal cual de `calendar/view-appointment-modal` (que pasa a usarlo). JS: `resources/js/ui/appointment-qr.js` (mostrar, aviso de fallo y descarga en PNG), `resources/js/ui/prescriptions.js` y `resources/js/ui/appointments-history.js`, extraídos de `agenda/view-appointment.js` y `records/index.js`, que pasan a importarlos. `records/appointments-history-table` admite `title` y `empty-text` (por defecto, los actuales). `dashboard/next-appointment` admite un slot `actions` (por defecto, los botones actuales) | **Medio**: el detalle lo comparten agenda, inicio y expediente; sus entradas de `test:ui` no deben cambiar |
| Interfaz · portal | `pages/dashboard.blade.php` (rama del paciente según c3: el saludo del tablero sustituye a `x-ui.page-hero`, así que el inicio deja la insignia "Mi cuenta", el rol "Paciente" y la inicial; `RoleCopyTest` y `DashboardScreenTest` se ajustan, spec → Decisiones 2026-11-10), `pages/portal/appointments.blade.php` (`x-ui.page-hero` + historial), `components/portal/appointment-detail-modal.blade.php` (`x-ui.dialog`). JS `resources/js/pages/portal/home.js`, `appointments.js` y `appointment-detail.js`, registrados en `vite.config.js`. `x-ui.sidebar`: el rol `paciente` ofrece "Inicio" y "Mis citas". `noindex` en las dos pantallas (D8) | Medio |
| Pruebas de navegador | `tests/Browser/checks-screens.mjs` (o el `checks-*.mjs` que corresponda): comprobaciones nuevas para entradas de pantalla: elementos dentro de la primera pantalla, tamaño mínimo de una imagen y CLS máximo, declaradas en `screens.json` (`/analyze 025` A5). `tests/Browser/screens.json`: entradas del portal. `UiAuditSeeder`: la cuenta `paciente` con una cita asignada futura, una reprogramada, una completada con recetas, una completada sin seguimiento y una cancelada; `paciente.vacio` ya existe | Bajo |
| Tests de apoyo | `tests/Support/ActingAsPatient`: `actingAs($patient, 'patient')`; ayudante para pedir con un token real | Medio: lo usan los tests de acceso existentes |
| Docs | `docs/architecture.md`, `docs/security.md` (RS1.b y RS1.c, superficie de ataque), `docs/observability.md`, `docs/seo.md`, `docs/design/system.md` (bloque del QR como componente), `AGENTS.md` (el portal ya tiene pantallas), `CHANGELOG.md` | Bajo |

## Contratos y datos
Grupo `/api/v1/portal`. Middlewares: `throttle:api`, `sanctum.cookie`, `auth:sanctum,patient`,
`patient`, `no-store`. Ninguna ruta lee cuerpo ni consulta; el paciente sale de la sesión.

Respuestas comunes a las cuatro rutas:

| Código | Cuándo | Cuerpo |
|---|---|---|
| 401 | Sin sesión o token inválido | `{"message": "Unauthenticated."}` |
| 403 | Personal (cualquier rol, activo o no) | `{"error": "Only patients can access this resource."}` |
| 403 | Paciente inactivo | `{"error": "Your account is inactive."}` |
| 429 | Más de 100 peticiones por minuto | El de `throttle:api` |
| 500 | Fallo inesperado | `{"error": "Internal server error"}` |

Todas, también las de error que pasan por el grupo, llevan `Cache-Control: private, no-store`.

### `GET /api/v1/portal/appointments`
200 → `{"data": [PortalAppointment, …]}`, todas las citas del paciente ordenadas por fecha y hora
descendentes; sin citas, `{"data": []}`.

`PortalAppointment`: `id`, `date` (`AAAA-MM-DD`), `time` (`HH:MM`), `status` (`asignada`,
`reprogramada`, `completada`, `cancelada`), `treatment_name`, `doctor_name`. Sin `patient_id`,
`user_id`, `treatment_id` ni recordatorio de WhatsApp.

### `GET /api/v1/portal/appointments/next`
200 → `{"data": PortalAppointment}` o `{"data": null}`. Es la cita asignada o reprogramada con
fecha de hoy o posterior (zona horaria de la clínica) más cercana; una de hoy cuenta aunque su hora
ya pasó. Se declara antes que las rutas con `{id}`.

### `GET /api/v1/portal/appointments/{id}/prescriptions`
| Código | Cuándo | Cuerpo |
|---|---|---|
| 200 | Cita propia completada con seguimiento | `{"data": {"recommendations": string\|null, "prescriptions": [{"medication", "dosage", "daily_frequency", "duration_days", "instructions"}]}}` |
| 200 | Cita propia sin seguimiento, sin recetas o no completada | `{"data": {"recommendations": null, "prescriptions": []}}` |
| 400 | `id` no es un UUID | `{"error": "Invalid appointment id."}` |
| 404 | La cita no existe o es de otro paciente | `{"error": "Appointment not found."}` |

Sin `id` de receta ni de seguimiento, y sin motivo, síntomas, diagnóstico, procedimiento ni
observaciones.

### `GET /api/v1/portal/appointments/{id}/qr`
| Código | Cuándo | Cuerpo |
|---|---|---|
| 200 | Cita propia asignada o reprogramada | SVG con solo el id (el mismo de la 024); `Content-Type: image/svg+xml`, `Content-Disposition: inline; filename="cita.svg"` |
| 400 | `id` no es un UUID | `{"error": "Invalid appointment id."}` |
| 404 | La cita no existe o es de otro paciente | `{"error": "Appointment not found."}` |
| 409 | Cita propia completada o cancelada | `{"error": "The QR code is only available for open appointments."}` |

### Sin cambios de contrato
Las rutas del personal responden lo mismo que hoy a cada actor (un paciente, 403). `GET
/appointments/{id}/qr` del personal no cambia.

### Pantallas
| Ruta | Pantalla | Estados |
|---|---|---|
| `/dashboard` (paciente) | Inicio según `design/candidatos/c3.html` y `c3-vacio.html` | Cargando (espacio reservado de la tarjeta y del QR, 240 × 240 px); con próxima cita; sin cita ("Aún no tienes una cita agendada" + "Contacta a la clínica" → `/contacto`); fallo de la próxima cita o del QR (aviso en su lugar, el resto igual) |
| `/mis-citas` | `c3-citas.html` | Cargando; con citas (tarjetas bajo 768 px, tabla desde 768 px); "Aún no tienes citas registradas"; fallo (aviso) |
| Detalle (diálogo) | `c3-detalle.html` y `c3-detalle-qr.html` | Abierta: datos + QR + "Descargar QR". Completada: datos + recomendaciones (si hay) + recetas o "Esta cita no tiene recetas". Cancelada: solo datos. Fallo de recetas o de QR: aviso en su lugar |

Los datos generales del detalle salen de la lista ya cargada (D4); el diálogo solo pide las recetas
o el QR. Accesibilidad: un `h1` por pantalla, el diálogo se abre con `resources/js/ui/dialog.js`
(foco dentro y de vuelta al botón que lo abrió), avisos por `resources/js/ui/status.js`, controles
de al menos 44 px, texto alternativo del QR "Código QR de acceso de tu cita del <fecha>" y cada
botón "Ver" con el nombre de su cita para lectores de pantalla.

### Qué ve cada rol
| Actor | `/dashboard` | `/mis-citas` | Menú | API `/portal/*` |
|---|---|---|---|---|
| Paciente activo | Su inicio (c3: saludo, sin insignia ni rol en el encabezado) | Su historial | "Inicio", "Mis citas" y "Cerrar sesión"; nada del personal | 200 |
| Paciente inactivo con sesión | "Sin permiso" (403) | "Sin permiso" (403) | — | 403 |
| Personal activo | Su tablero, sin cambios | "Sin permiso" (403) | Sin "Mis citas" | 403 |
| Personal inactivo | "Sin permiso", como hoy | "Sin permiso" (403) | — | 403 |
| Sin sesión | Va a `/login` | Va a `/login` | — | 401 |

### Datos
Sin migraciones ni tablas nuevas. Los tokens existentes siguen valiendo: `tokenable_type` no cambia.

## Estrategia de pruebas
Reloj fijo en los tests de fechas: 2026-09-17 11:40 (America/Mexico_City), el de `UiAuditClock`.

**Guards** (`tests/Modules/Auth/Integration/GuardSeparationTest.php`, con tokens reales en la
cabecera, no `actingAs`):
- Token de paciente: `Auth::guard('sanctum')` no lo reconoce; el guard `patient` sí. Token de
  personal: al revés.
- Un usuario del personal y un paciente creados **con el mismo UUID**: con el token del paciente,
  `/portal/appointments` devuelve las citas del paciente y `/appointments` responde 403; con el del
  personal, `/appointments` responde 200 y `/portal/appointments` 403 (CA16).
- Token de paciente en una ruta del personal → 403, no 401 (CA14).

**Acceso** (`tests/Modules/Appointments/Integration/PortalAccessControlTest.php`): dataset de las
cuatro rutas × actores. Sin sesión → 401. Administrador, asistente, doctor y personal inactivo →
403. Paciente inactivo → 403. Paciente activo → 200 (o 404 con un id ajeno). Además, el paciente
inactivo y el activo contra rutas del personal (expediente, pacientes, agenda, detalle, QR y
seguimiento del personal) → 403 (CA14, CA15).

**Citas** (`PortalAppointmentsTest.php`):
- Orden: citas del 26/11, 12/11, 20/10 y 14/09 salen en ese orden; dos del mismo día, la de hora
  mayor primero.
- Solo las propias: con citas de otro paciente en la base, no aparecen; con `?patient_id=<otro>` y
  con ese campo en el cuerpo, la respuesta no cambia (CA13).
- Próxima cita, fronteras con el reloj en 2026-09-17 11:40: asignada hoy a las 09:00 → es la
  próxima; asignada ayer (16/09) → no; completada o cancelada hoy → no; hoy 16:00 y mañana 09:00 →
  la de hoy; reprogramada mañana y asignada pasado mañana → la de mañana; sin ninguna → `null`.
- Campos: exactamente los seis de `PortalAppointment`.
- Cabecera `Cache-Control: private, no-store` en 200, 401 (sin sesión), 403 (personal y paciente
  inactivo) y 404, con peticiones reales a las rutas del portal (no basta el test del middleware).

**QR** (`PortalAppointmentQrTest.php`): cita propia asignada y reprogramada → 200 y el SVG
decodificado (`DecodesQrSvg`) es el id; completada y cancelada → 409 sin SVG (CA18); ajena → 404
con el mismo cuerpo que un UUID sin cita (CA12); `no-es-uuid` → 400.

**Recetas** (`tests/Modules/AppointmentTracking/Integration/PortalPrescriptionsTest.php`):
completada con seguimiento y dos recetas → recomendaciones y los cinco campos de cada una;
completada sin seguimiento → vacío con 200 (CA6); con seguimiento sin recomendaciones → `null`;
asignada y cancelada → vacío; ajena → 404; id inválido → 400.

**Datos clínicos** (`PortalNoClinicalDataTest.php`): con un seguimiento cuyos motivo, síntomas,
diagnóstico, procedimiento y observaciones llevan marcas únicas, ninguna respuesta del portal las
contiene, ni el teléfono, el correo o el nombre de otro paciente (CA17).

**Errores** (`PortalUnexpectedErrorTest.php`): repositorio de citas, repositorio de seguimiento y
generador de QR que lanzan → 500 genérico en cada ruta (CA19).

**Rendimiento** (`PortalPerformanceTest.php`): con 100 citas, la lista y la próxima cita responden
con un número de consultas acotado y constante (sin N+1), como `DashboardPerformanceTest`.

**Unidad** (`tests/Modules/Appointments/Unit/` y `tests/Modules/AppointmentTracking/Unit/`): cada
caso de uso llama a `currentPatientId()` antes que al repositorio y, si lanza, no lee nada;
`PatientNextAppointmentPropertiesTest` (Eris): para listas aleatorias de citas, la próxima es
siempre abierta, de hoy o posterior, y ninguna otra abierta desde hoy es anterior a ella.
`CurrentActorAuthorizationServiceTest`: `currentPatientId()` con paciente activo, paciente
inactivo, personal y sin actor.

**Web** (`tests/Modules/Core/Integration/PortalPagesTest.php`): paciente activo → `/dashboard` y
`/mis-citas` con 200, `noindex` y un menú sin enlaces del personal (CA10); paciente inactivo → 403
con "Sin permiso" en ambas; personal → `/mis-citas` 403; sin sesión → `/login`
(`GuestRedirectTest`, ajustado). `sitemap.xml` no lista `/mis-citas`.

**Navegador** (`npm run test:ui`, entradas nuevas en `screens.json`, a 390, 768 y 1440 px):
`portal-inicio` (fecha, hora, QR de al menos 240 × 240 px y "Descargar QR" dentro de la primera
pantalla y CLS ≤ 0.1, con las comprobaciones nuevas del arnés, que deben fallar si el QR queda
fuera de la primera pantalla o la tarjeta salta al cargar: K1, K6), `portal-inicio-vacio` (K2), `portal-inicio-fallo-qr` y
`portal-inicio-fallo-cita` (CA9, con la respuesta simulada), `portal-mis-citas` (K3),
`portal-mis-citas-vacio` (K2), `portal-detalle-abierta` (QR y descarga), `portal-detalle-completada`
(recetas), `portal-detalle-sin-recetas`, `portal-detalle-cancelada` (K4), `portal-detalle-fallo`
(CA9) y `portal-menu` (K5). Las entradas del detalle del personal (agenda, inicio y expediente) se
vuelven a ejecutar sin cambios.

**Manual**: comparar las cuatro pantallas con los candidatos c3 a 390, 768 y 1440 px; escanear con
un teléfono el QR de la pantalla y el PNG descargado.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Spoofing: alguien sin sesión lee citas, recetas o QR | A07:2025 · API2:2023 | Grupo `portal` | `auth:sanctum,patient` | `PortalAccessControlTest` (401) |
| TM2 | Information disclosure / Elevation: un paciente lee la cita, las recetas o el QR de otro cambiando el id (IDOR) | A01:2025 · API1:2023 | Casos de uso del portal | Pertenencia: `PatientId` de la cita = paciente de la sesión; si no, 404 idéntico al de inexistente | `PortalAppointmentQrTest`, `PortalPrescriptionsTest` (ajena → 404, mismo cuerpo) |
| TM3 | Tampering: el paciente manda el id de otro paciente para listar sus citas | A01:2025 · API3:2023 | Rutas del portal | No hay parámetro de paciente: sale de la sesión; lo que llegue se ignora | `PortalAppointmentsTest` (`patient_id` ajeno) |
| TM4 | Elevation: una sesión de un tipo de cuenta se acepta como la del otro (confusión de tipo, ids iguales) | A01:2025 · A07:2025 | Guards `sanctum` y `patient` | Provider fijo por guard + `staff` / `patient` comprueban la clase del actor | `GuardSeparationTest` |
| TM5 | Elevation: el paciente usa las rutas del personal | A01:2025 · API5:2023 | Rutas del personal | `staff` y `only.admin`, sin cambios | `PortalAccessControlTest`; `AppointmentsAccessControlTest`, `PatientsAccessControlTest` existentes |
| TM6 | Elevation: un paciente desactivado sigue consultando con su sesión abierta | A01:2025 · A07:2025 | `EnsureActivePatient`, `currentPatientId()`, `ShowDashboardPageController` | El estado se lee de la base en cada petición | `PortalAccessControlTest`, `PortalPagesTest`, `CurrentActorAuthorizationServiceTest` |
| TM7 | Information disclosure: las respuestas llevan datos clínicos internos o de terceros | A01:2025 · API3:2023 · LFPDPPP | Recursos del portal | Recursos propios con lista cerrada de campos; no se reutilizan los del personal | `PortalNoClinicalDataTest`; `PortalAppointmentsTest` (campos exactos) |
| TM8 | Spoofing: el paciente obtiene el QR de una cita cerrada para volver a entrar | A06:2025 (diseño inseguro) | `GetPatientAppointmentQrUseCase` | Solo citas asignadas o reprogramadas; si no, 409 | `PortalAppointmentQrTest` |
| TM9 | Information disclosure: un fallo interno filtra rutas, SQL o datos | A10:2025 | Controladores | `UnexpectedErrorResponse` | `PortalUnexpectedErrorTest` |
| TM10 | Information disclosure: citas, recetas o QR quedan en la caché del navegador o de un proxy | A02:2025 | `NoStoreResponse` | `Cache-Control: private, no-store` en todo el grupo | `PortalAppointmentsTest` (cabecera en 200 y en errores) |
| TM11 | Denial of service: peticiones masivas a la lista o al QR | API4:2023 | Grupo `portal` | `throttle:api` (sin cambios) y consultas acotadas | `PortalPerformanceTest` |
| TM12 | Spoofing: alguien con el QR reenviado o fotografiado entra por el paciente | A07:2025 | Puerta (fuera de Core) | Aceptado en la 024 (R1): credencial al portador | — (riesgo aceptado; ver R4) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: próxima cita con su QR a la vista | `GetPatientNextAppointmentUseCase`; rama del paciente de `dashboard`; `portal/home.js` | `PortalAppointmentsTest` (fronteras de la próxima cita); `PatientNextAppointmentPropertiesTest`; ui-audit `portal-inicio` · verificación manual: comparar con `c3.html` a 390, 768 y 1440 px |
| CA2: "Aún no tienes una cita agendada" y contacto | `next` → `null`; estado vacío del inicio | `PortalAppointmentsTest` (`null`); ui-audit `portal-inicio-vacio` |
| CA3: historial completo, de la más reciente a la más antigua | `GetPatientAppointmentsUseCase`; `pages/portal/appointments`; `ui/appointments-history.js` | `PortalAppointmentsTest` (orden y campos); ui-audit `portal-mis-citas` |
| CA4: "Aún no tienes citas registradas" | `empty-text` del historial | `PortalAppointmentsTest` (`[]`); ui-audit `portal-mis-citas-vacio` |
| CA5: recomendaciones y recetas de una cita completada | `GetPatientPrescriptionsUseCase`; `ui/prescriptions.js`; diálogo del portal | `PortalPrescriptionsTest` (cinco campos); ui-audit `portal-detalle-completada` |
| CA6: "Esta cita no tiene recetas", sin error | Respuesta vacía con 200 | `PortalPrescriptionsTest` (sin seguimiento, sin recetas, sin recomendaciones); ui-audit `portal-detalle-sin-recetas` |
| CA7: QR y "Descargar QR" en citas abiertas | `GetPatientAppointmentQrUseCase`; `x-appointments.qr-block`; `ui/appointment-qr.js` | `PortalAppointmentQrTest` (SVG decodificado = id); ui-audit `portal-detalle-abierta` · verificación manual: escanear el PNG descargado |
| CA8: sin QR en completadas o canceladas; cancelada sin recetas | Diálogo del portal; caso de uso de recetas | `PortalPrescriptionsTest` (cancelada → vacío); ui-audit `portal-detalle-completada`, `portal-detalle-cancelada` |
| CA9: fallo del QR o de las recetas → aviso en su lugar | `portal/home.js`, `portal/appointment-detail.js` | ui-audit `portal-inicio-fallo-qr`, `portal-inicio-fallo-cita`, `portal-detalle-fallo` |
| CA10: aterriza en su portal; menú solo con lo suyo | `x-ui.sidebar` (rol `paciente`) | `PortalPagesTest` (menú sin enlaces del personal); ui-audit `portal-menu` |
| CA11 (abuso): sin sesión → 401; en la web, al inicio de sesión | `auth:sanctum,patient` | `PortalAccessControlTest`; `GuestRedirectTest` |
| CA12 (abuso): cita ajena → 404 sin indicios | Pertenencia en los casos de uso | `PortalAppointmentQrTest`, `PortalPrescriptionsTest` |
| CA13 (abuso): id de otro paciente ignorado | Paciente tomado de la sesión | `PortalAppointmentsTest` |
| CA14 (abuso): rutas del personal → 403 y "Sin permiso" | `staff`, `only.admin` sin cambios, tras `auth:sanctum,patient` | `PortalAccessControlTest`; `GuardSeparationTest`; suites de acceso existentes |
| CA15 (abuso): paciente desactivado con sesión abierta → 403 | `EnsureActivePatient`; `currentPatientId()`; `ShowDashboardPageController` | `PortalAccessControlTest`; `PortalPagesTest` |
| CA16 (abuso): personal en el portal → 403; sesiones no intercambiables | Guards con provider; `patient` | `GuardSeparationTest` (ids iguales); `PortalAccessControlTest`; `PortalPagesTest` |
| CA17 (abuso): sin datos clínicos internos ni de terceros | Recursos del portal | `PortalNoClinicalDataTest` |
| CA18 (abuso): QR de cita cerrada → rechazado | 409 en el caso de uso | `PortalAppointmentQrTest` |
| CA19 (abuso): fallo interno → 500 genérico | `UnexpectedErrorResponse` | `PortalUnexpectedErrorTest` |
| CA20 (abuso): respuestas sin caché | `NoStoreResponse` | `PortalAppointmentsTest`, `PortalAppointmentQrTest`, `PortalPrescriptionsTest` (cabecera) |
| TM1: sin sesión | `auth:sanctum,patient` | `PortalAccessControlTest` |
| TM2: cita ajena | Pertenencia | `PortalAppointmentQrTest`, `PortalPrescriptionsTest` |
| TM3: id de paciente manipulado | Sin parámetro de paciente | `PortalAppointmentsTest` |
| TM4: confusión de tipo de cuenta | Guards con provider | `GuardSeparationTest` |
| TM5: paciente en rutas del personal | `staff`, `only.admin` | `PortalAccessControlTest` |
| TM6: paciente desactivado | Estado leído en cada petición | `PortalAccessControlTest`, `PortalPagesTest`, `CurrentActorAuthorizationServiceTest` |
| TM7: datos de más en las respuestas | Recursos propios | `PortalNoClinicalDataTest` |
| TM8: QR de cita cerrada | 409 | `PortalAppointmentQrTest` |
| TM9: detalles internos en errores | `UnexpectedErrorResponse` | `PortalUnexpectedErrorTest` |
| TM10: cachés | `NoStoreResponse` | `PortalAppointmentsTest` |
| TM11: carga | `throttle:api`; consultas acotadas | `PortalPerformanceTest` |
| TM12: QR al portador | Fuera de Core | — (riesgo aceptado, R4) |
| RNF: WCAG 2.1 AA y `npm run test:ui` a 390, 768 y 1440 px | Pantallas del portal | ui-audit, todas las entradas `portal-*` · verificación manual: recorrido con teclado del detalle |
| RNF: 100 citas en menos de 2 s | Consultas acotadas | `PortalPerformanceTest` (número de consultas constante; no mide tiempo, inestable en paralelo) |
| RNF: textos en español, en tuteo y sin términos técnicos | Vistas y JS del portal | ui-audit (textos de cada entrada) · verificación manual: `/review` de los textos |
| RNF: el portal no aparece en buscadores | `noindex`; fuera del sitemap | `PortalPagesTest` |
| RNF: sin datos de salud ni de contacto en logs o errores | Sin `Log::` nuevos; errores con texto fijo | `PortalUnexpectedErrorTest` · verificación manual: `/review` de las llamadas a `Log::` |
| K1–K6 de "Criterios de diseño" | Pantallas del portal | ui-audit `portal-inicio` (K1, K6), `portal-inicio-vacio` y `portal-mis-citas-vacio` (K2), `portal-mis-citas` (K3), `portal-detalle-*` (K4), `portal-menu` (K5) |

## Observabilidad
- Logs nuevos: ninguno. Los errores inesperados quedan en el log saneado de
  `UnexpectedErrorResponse`, sin datos del paciente.
- Eventos de auditoría: no se emiten (P14 ❌ aceptado, aprobado por el usuario el 2026-11-10). Puntos de emisión para cuando
  exista el registro: lectura de recetas en `GetPatientPrescriptionsUseCase` (actor, cita,
  resultado); obtención del QR en `GetPatientAppointmentQrUseCase`; accesos denegados en
  `EnsureActivePatient`, `EnsureActiveStaff`, el 404 por cita ajena y el 409 del QR.
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: entrar como paciente de prueba, ver la próxima cita con su QR,
  abrir "Mis citas" y el detalle de una completada; entrar como administrador y comprobar que la
  agenda y el detalle con QR siguen igual. En Loki, sin 500 en `/api/v1/portal/*` y sin un aumento
  de 401 en el resto de la API (señal de un fallo en los guards).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/dashboard` (paciente) | no (exige sesión) | "Inicio - Dentissa" | — | — | no | — | `PortalPagesTest` (`noindex`); `GuestRedirectTest` (sin sesión → `/login`) |
| `/mis-citas` | no (exige sesión) | "Mis citas - Dentissa" | — | — | no | — | `PortalPagesTest` (`noindex`, fuera de `sitemap.xml`); `GuestRedirectTest` |

- Renderizado de las rutas nuevas: igual que `seo.rendering`.
- Imagen para compartir: no aplica (rutas privadas).
- Brechas `SE` que atiende: ninguna.

## Rollout
- Feature flag: no aplica. Son pantallas nuevas para un actor que hoy solo ve un marcador; el
  cambio de guards no admite convivir a medias.
- Orden de despliegue: una sola imagen con `deploy.sh`; sin migraciones. La imagen reconstruye la
  caché de configuración, de la que depende el cambio de `config/auth.php`.
- Compatibilidad: los tokens ya emitidos siguen valiendo. Ninguna respuesta existente cambia.
- Rollback: `docker/prod/rollback.sh` a la versión anterior; no hay datos que revertir.
- Métricas a vigilar tras el deploy: 401, 403 y 500 por ruta en Loki durante la ventana de
  vigilancia; un pico de 401 en rutas del personal indica un problema con los guards.

## Decisiones (→ ADR si son arquitectónicas)
- **D1** RS1.c con dos guards del mismo driver: `sanctum` (provider `users`) y `patient` (provider
  `patients`), y `auth:sanctum,patient` en las rutas. Alternativas: dejar un solo guard sin
  provider y confiar en los `instanceof` de los middlewares (no cierra RS1.c), o `auth:sanctum`
  solo en las rutas del personal (el paciente recibiría 401 y sería enviado al inicio de sesión,
  contra CA14). Comprobado en `vendor/laravel/sanctum/src/Guard.php` (`hasValidProvider`) el
  2026-11-10; la prueba con tokens reales es la primera tarea (`GuardSeparationTest`). No requiere
  ADR: no cambia el stack ni añade dependencias.
- **D2** El paciente de la sesión se obtiene con `AuthorizationServiceInterface::currentPatientId()`,
  que hace de `assertCan()` para el paciente (la tabla de permisos es de roles del personal). Es la
  primera llamada de cada caso de uso del portal.
- **D3** Rutas bajo `/api/v1/portal`, sin id de paciente. Las recetas van en AppointmentTracking y
  lo demás en Appointments, cada lectura en el módulo dueño del dato.
- **D4** No hay ruta de "detalle de cita" del portal: los datos generales ya vienen en la lista y
  en la próxima cita, y el diálogo solo pide recetas o QR. Así el fallo de una parte no tumba el
  resto (CA9) y hay menos superficie. CA11 y CA12 se prueban en las cuatro rutas que existen (spec → Decisiones del 2026-11-10).
- **D5** Id no UUID → 400 (como el QR de la 024); cita ajena o inexistente → 404 con el mismo
  cuerpo; QR de cita cerrada → 409.
- **D6** La "próxima cita" y el orden se calculan en el caso de uso sobre las citas del paciente
  que ya devuelve el repositorio, sin tocar su interfaz. La fecha de hoy llega en el DTO desde el
  controlador, como en el tablero.
- **D7** Toda respuesta del grupo `portal` lleva `Cache-Control: private, no-store`, también el 401
  de `auth` y los 403 de `patient`. Laravel ejecuta la autenticación antes que los middlewares de
  ruta sin prioridad, así que el mecanismo debe cubrir esas respuestas (`/analyze 025` A7); lo
  comprueban peticiones reales sin sesión, de personal y de paciente inactivo.
- **D8** `noindex` en las dos pantallas del portal con una etiqueta `meta`, mediante un `@stack` en
  la cabecera de `layouts/dashboard` que solo usan las vistas del portal. El resto del panel no
  cambia.
- **D9** Web: `/mis-citas` como ruta con vista, igual que `/agenda`; el inicio sigue en
  `ShowDashboardPageController`. Las vistas no llevan datos protegidos: los pide el JS a la API.
- **D10** Línea base de dependencias: no se pudo ejecutar el SCA al planear (Docker apagado). La
  última conocida es la del release de v0.9.0 (2026-11-09): solo los 8 avisos de Symfony con la
  excepción EX2, que vence el 2026-11-12 y se cierra con el `/chore` previsto para el 2026-11-11.
  La primera tarea vuelve a ejecutar `composer audit` y `npm audit --audit-level=high`.
- **D11** Tamaño: la spec tiene 20 criterios y sigue como una sola (decisión del usuario,
  2026-11-08, registrada en la spec).
- **D12** Formatos heredados de los componentes (fechas `dd/mm/aaaa`, botón "Ver", "3 veces al
  día", recetas sin etiqueta por dato y el subtítulo "Historial de citas"): se conservan, como fija
  `design.md`.

## Impacto en arquitectura
- `docs/architecture.md`: autenticación con dos guards (`sanctum` para el personal, `patient` para
  pacientes) y los middlewares `staff` / `patient`; grupo `/api/v1/portal`; piezas de interfaz
  compartidas (`x-appointments.qr-block`, `ui/appointment-qr.js`, `ui/prescriptions.js`,
  `ui/appointments-history.js`).
- `docs/security.md`: RS1.b y RS1.c pasan a "en curso (spec 025)" y, al liberar, a mitigadas (RS1.a
  y RS1.d ya lo están desde v0.1.0); "Superficie de ataque" con las cuatro rutas.
- `docs/observability.md`: puntos de emisión pendientes de auditoría del portal.
- `docs/seo.md`: `/mis-citas` en el inventario de rutas como no indexable.
- `docs/design/system.md`: el bloque del QR como componente; el historial con título y texto vacío
  configurables.
- `AGENTS.md`: el portal del paciente ya tiene pantallas.

## Riesgos y mitigaciones
- **R1** El cambio de guards toca la autenticación de toda la aplicación. Un error dejaría sin
  acceso al personal o al paciente. Mitigación: es la primera tarea, con `GuardSeparationTest` y la
  suite completa de acceso en verde antes de seguir; verificación tras el deploy con las dos
  cuentas; rollback sin datos.
- **R2** `actingAs($patient, 'sanctum')` en los tests existentes salta la comprobación del provider.
  Mitigación: `ActingAsPatient` pasa al guard `patient` y las pruebas de separación usan tokens
  reales.
- **R3** Extraer el QR, las recetas y el historial a piezas compartidas puede romper el detalle del
  personal en agenda, inicio y expediente. Mitigación: se extrae sin cambiar el marcado, y sus
  entradas de `test:ui` deben pasar sin tocar sus expectativas.
- **R4** El QR sigue siendo una credencial al portador (TM12; R1 de la 024). Ahora el paciente
  puede descargarlo y reenviarlo. Aceptado para la maqueta; se revisa antes de cargar datos reales.
- **R5** `layouts/error` lee `auth('sanctum')`: con el provider fijo dejaría de ver al paciente y
  sus páginas 404 y 403 perderían el camino de vuelta. Mitigación: se ajusta en la misma tarea de
  los guards y lo cubren `NotFoundPageTest` y `WebAccessDeniedPagesTest`.
- **R6** Correcciones de control de acceso: RS1.b y RS1.c quedan dentro de esta spec; RS1.a y
  RS1.d ya están mitigadas (v0.1.0, spec 014). El estado de cada una lo actualiza `/release`.
- **R7** P14: sin registro de auditoría, las lecturas de recetas por el paciente no dejan rastro
  hasta el objetivo 5 del roadmap.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-10 | Creación en `draft`; P14 pendiente de aceptación del usuario | /plan 025 |
| 2026-11-10 | Aprobado, con P14 ❌ aceptado | Aprobación del usuario |
| 2026-11-10 | Correcciones de `/analyze` ronda 1 (A1, A2, A3, A5, A7, A9, A15, A18): P6 según la constitución 1.2.1, `layouts/error` con el cambio de guards, inicio de c3 con los tests de 016 y 020, arnés de navegador para K1 y K6, `no-store` también en 401 y 403 | Decisiones del usuario |
| 2026-11-10 | Aprobado de nuevo tras `/analyze` ronda 2 | Aprobación del usuario |
