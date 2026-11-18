---
spec: 028-paginacion-de-listas
status: approved
constitution_version: 1.2.1
created: 2026-11-18
---

# Plan · 028 Paginación de las listas del panel y del portal

## Enfoque técnico
Una pieza común de paginación en `app/Core` (petición de página y página de resultados como value
objects) y, en cada módulo, un método de repositorio **nuevo** que devuelve una página ordenada de
forma estable, con su total y los contadores de la cabecera calculados en el servidor. Los
endpoints de las 11 listas siguen en `/api/v1` con un cambio aditivo: `data` conserva la misma lista
de elementos y se añade `meta` (página, total, última página y contadores); la búsqueda y los
filtros pasan al servidor. En el navegador, un módulo compartido `resources/js/ui/pagination.js`
pinta el control aprobado en `design.md` (candidato `c3`) desde `meta`, guarda página, búsqueda y
filtro en la dirección y vuelve a pedir la página al cambiarlos. Los métodos de repositorio y casos
de uso actuales no cambian: los usan la agenda, el portal y los selectores.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 028 `approved` y `design.md` `approved` | manual: `python .ai/bin/aidd.py status`; revisión en `/review` |
| P2 Test que falla antes y pasa después | ✅ | Cada tarea con su test Pest en `tests/Modules/<Módulo>/`; los value objects nuevos con tests de propiedades (Eris); el control en el navegador con `npm run test:ui` | test: `./vendor/bin/pest --parallel` y `npm run test:ui` en CI; trazabilidad en `/review` |
| P3 Capas del módulo | ✅ | Controller → UseCase nuevo → Domain Service → `*RepositoryInterface` con `findPage()`; `Domain/` solo importa los value objects de `app/Core/Domain/Pagination` (como hoy `UuidIdentifier`), no Eloquent | manual: `/review` y `grep -rn "use App\\\\Modules\\\\.*\\\\Infrastructure" app/Modules/*/Domain` sin resultados |
| P4 Contrato de API primero | ✅ | Contrato de los 11 endpoints abajo; cambio aditivo en `/api/v1` (`data` igual + `meta`), decisión del usuario D1; Contenido deja de devolver un objeto suelto con un solo resultado | test: tests de integración que fijan 200, 403, 422 y la forma de `meta` por endpoint |
| P5 Autorización en el servidor | ✅ | Mismos middlewares; los casos de uso nuevos llaman a `assertCan()` (también los de Contenido, que hoy no lo hacían); el portal toma al paciente de la sesión e ignora cualquier identificador de la consulta | test: un test de acceso denegado por actor no autorizado en cada endpoint paginado (CA14, TM2) |
| P6 Validación de entrada con FormRequest | ✅ | Un `FormRequest` por endpoint con `page`, `per_page`, `search` y los filtros existentes (reglas en array, mensajes en español); entrada inválida → 422 | test: payload inválido por endpoint → 422 |
| P7 Errores sin detalles internos | ✅ | Los controladores nuevos o tocados responden `UnexpectedErrorResponse` en 500 y registran con `Log::error` | test: excepción forzada → `{"error": "Internal server error"}` en un endpoint por módulo; `grep -rn "getMessage()" app/Modules/*/Infrastructure/Http*` en código tocado |
| P8 Secretos fuera del repositorio | ➖ | No hay secretos ni variables nuevas | — |
| P9 Migraciones reversibles | ➖ | Sin migraciones: con 5 000 registros no hacen falta índices nuevos (CA12 se mide) | — |
| P10 Dependencias nuevas con ADR | ➖ | Sin paquetes nuevos: `paginate()` de Eloquent no se usa; la página se arma con `count()`, `offset()` y `limit()` | — |
| P11 Datos sensibles y modelo de amenazas | ✅ | Modelo de amenazas abajo; la búsqueda (nombre o correo) no se escribe en logs | test: TM1–TM7; manual: `/review` de las llamadas a `Log::` tocadas |
| P12 Producción con aprobación | ✅ | Despliegue por tag con `deploy.sh` tras aprobación; línea base de dependencias limpia (`composer audit` y `npm audit` sin avisos, 2026-11-18) | manual: `/release` pide la confirmación y corre `security.tools` |
| P13 Lógica de negocio en el backend | ✅ | Búsqueda, filtros, orden, límite por página, página fuera de rango y contadores en el servidor; el JS solo pinta `meta` y guarda la dirección | manual: `/review` del JS tocado; test: integración de contadores y búsqueda |
| P14 Trazabilidad | ✅ | No hay eventos de auditoría nuevos (spec → Auditoría: no aplica); los 500 se registran con el contexto de petición existente | test: el test de 500 por módulo comprueba el `Log::error`; manual: `/review` |
| P15 Solo el sistema de diseño | ✅ | Componente "Paginación" añadido primero a `system.md` y `system.html` (T001); solo tokens existentes | test: `OnlyDesignTokensTest`, `DesignTokensTest` y `npm run test:ui` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Core (nuevo) | `app/Core/Domain/Pagination/PageRequest.php` (página ≥ 1, tamaño 1–50, con `MAX = 50`), `Page.php` (elementos, total, página, tamaño, última página, desde, hasta), `PaginationException.php`; `app/Core/Http/Requests/PageQueryRules.php` (reglas y mensajes comunes de `page`, `per_page` y `search`); `app/Core/Http/PageMeta.php` (arma `meta` desde un `Page` y los contadores) | Bajo |
| Patients | `PatientsRepositoryInterface::findPage()` y `countByStatus()`; `EloquentPatientRepository` (búsqueda `ILIKE` en nombre, apellido, nombre completo y correo con comodines escapados; orden `created_at desc, id desc`); `ListPatientsPageUseCase` + DTO; `GetPatientsRequest` nuevo; `GetPatientsByStatusController` pasa al caso de uso nuevo. `GetPatientsByStatusUseCase` y `/agenda/patients` no cambian | Medio: `/api/v1/patients` lo usan Pacientes y Expedientes |
| Users | `UserRepositoryInterface::findPage()` y `countByRole()`; orden `created_at desc, id desc`; `ListUsersPageUseCase`; `GetUsersRequest` añade `page` y `per_page`; `GetUsersByRoleAndStatusController` al caso de uso nuevo. `findByRoleAndStatus` (doctores de la agenda) no cambia | Bajo |
| Appointments (tratamientos) | `TreatmentsRepositoryInterface::findPage()` y `stats()` (total, con duración, promedio); búsqueda en nombre o descripción; orden `name, id`; `ListTreatmentsPageUseCase`; `GetTreatmentsAdminRequest` nuevo; `GetTreatmentsAdminController`. `/agenda/treatments` no cambia | Bajo |
| Appointments (citas) | `AppointmentsRepositoryInterface::findPageByPatient()` (orden `date desc, time desc, id desc` en SQL); `ListPatientAppointmentsPageUseCase` (staff) y `ListPortalAppointmentsPageUseCase` (paciente de la sesión); `GetAppointmentsByPatientIdController` y `GetPortalAppointmentsController`; `findUnclosedBefore()` recibe la página (desplazamiento) además del límite; `GetUnclosedAppointmentsController` lee `page` | Medio: `findByStatusAndDate` sigue intacto para agenda, hoy, disponibilidad y próxima cita |
| AppointmentTracking | `PendingTrackingRepositoryInterface::findCompletedWithoutTracking()` recibe la página; `GetCompletedWithoutTrackingController` lee `page`; desempate `id desc` en el orden | Bajo |
| ContentManagement (4 submódulos) | Por cada uno (`Galeria`, `Promociones`, `Certificaciones`, `Testimonios`): `findPage()` y `countByStatus()` en su repositorio (orden `created_at desc, id desc`), caso de uso `List*PageUseCase` con `assertCan`, `FormRequest`, y un controlador admin nuevo (`List*PageController`) en las rutas admin de `routes/api.php` (líneas 140, 147, 154, 161). Los controladores actuales siguen en las rutas `/public/*` sin cambios | Medio: los controladores se comparten hoy con las rutas públicas |
| Rutas | `routes/api.php`: las 4 rutas admin de Contenido apuntan a los controladores nuevos; el resto, sin cambios de ruta | Bajo |
| Interfaz (nuevo) | `resources/js/ui/pagination.js` (ventana de páginas, pintado del control desde `meta`, lectura y escritura de `pagina`, `busqueda` y `estado` en la dirección con `history.replaceState`, anuncio por `ui/status.js`); `resources/views/components/ui/pagination.blade.php` (el `nav` vacío con su nombre); estilos con tokens en `resources/css/app.css` | Medio: pieza nueva compartida por 9 pantallas |
| Interfaz (pantallas) | `resources/js/pages/patients/index.js`, `records/index.js` (lista e historial), `usuarios` (JS en línea de `pages/usuarios/index.blade.php`), `tratamientos/index.js`, `contenido/{galeria,promociones,certificaciones,testimonios}/index.js` y `contenido/tabs.js` (pestaña en la dirección), `portal/appointments.js`, `dashboard.js` (paginar "Citas sin cerrar" y "Completadas sin seguimiento" en lugar de "y N más"), `ui/appointments-history.js` si hace falta; las vistas Blade correspondientes montan `x-ui.pagination` | Medio |
| Sistema de diseño | `docs/design/system.md` y `system.html`: componente "Paginación" de `design.md` → "Cambios a incorporar al sistema" (T001) | Bajo |
| Datos de prueba | `UiAuditSeeder`: más de 10 citas para la paciente del portal y para el expediente de prueba, y más de 12 elementos en una pestaña de Contenido, para que haya varias páginas | Bajo |
| Tests de navegador | `tests/Browser/screens.json`: entradas de página 2 y de última página, búsqueda en otra página, y `snapshot: true` en `pacientes` y `portal-mis-citas` para `evaluate.mjs`; `baseline-1440.json` reescrita para las pantallas que cambian | Medio: líneas base de composición |

## Contratos y datos

**Parámetros comunes** (consulta, todos opcionales):

| Parámetro | Regla | Si falta | Inválido |
|---|---|---|---|
| `page` | entero ≥ 1 | 1 | 422 `{"message": …, "errors": {"page": ["La página debe ser un número entero mayor que cero."]}}` |
| `per_page` | entero ≥ 1 | 12 en tarjetas · 10 en tablas | < 1 o no entero → 422; **> 50 → se usa 50** (CA13, sin error) |
| `search` | texto ≤ 100 caracteres, recortado; `''` = sin búsqueda | sin búsqueda | > 100 → 422 |

Una página mayor que la última devuelve la última (y `meta.page` lo dice); con 0 resultados,
`meta.page = 1`, `last_page = 1`, `from = to = null` (CA9). Orden estable con desempate por `id`
(CA7).

**Respuesta de las listas** (Pacientes, Usuarios, Tratamientos, historial del expediente, Mis
citas, las cuatro de Contenido):

```json
{
  "data": [ { …mismo recurso que hoy… } ],
  "meta": { "page": 2, "per_page": 12, "total": 87, "last_page": 8, "from": 13, "to": 24,
            "counts": { "total": 87, "active": 84, "inactive": 3 } }
}
```

`meta.total` cuenta lo que coincide con búsqueda y filtro (para "Mostrando… de N"); `meta.counts`
cuenta **toda** la lista, sin búsqueda ni filtro (CA5). Las listas sin contadores (historial, Mis
citas) no llevan `counts`.

| Endpoint | Rol (sin cambios) | Filtros | Tamaño | `counts` | Orden |
|---|---|---|---|---|---|
| `GET /api/v1/patients` | staff | `status`, `search` (nombre, apellido, nombre completo o correo) | 12 | `total`, `active`, `inactive` | `created_at desc, id desc` |
| `GET /api/v1/users` | administrador | `status`, `role` | 12 | `total`, `administrador`, `asistente`, `doctor` | `created_at desc, id desc` |
| `GET /api/v1/treatments` | administrador | `search` (nombre o descripción) | 12 | `total`, `with_duration`, `average_minutes` (entero, o `null` sin datos) | `name, id` |
| `GET /api/v1/appointments/patient/{id}` | staff con `appointments.patient-history.view` | — | 10 | — | `date desc, time desc, id desc` |
| `GET /api/v1/portal/appointments` | paciente (el de la sesión) | — | 10 | — | `date desc, time desc, id desc` |
| `GET /api/v1/{gallery-images,promotions,certifications,testimonials}` (admin) | administrador | — | 12 | `total`, `visible`, `hidden` | `created_at desc, id desc` |

`GET /api/v1/patients/…` sigue respondiendo `{"message", "data"}` donde hoy lo hace. Contenido
admin pasa a `{"data": [...], "meta": {...}}` siempre (hoy: lista suelta, u objeto suelto con un
solo resultado); las rutas `/api/v1/public/*` no cambian.

**Pendientes del inicio** (`GET /api/v1/agenda/unclosed`, `GET /api/v1/agenda/without-tracking`):
aceptan `page`; la forma actual se conserva y se añade `meta`:

```json
{ "data": { "items": [ … hasta 10 … ], "total": 15 }, "meta": { "page": 2, "per_page": 10, "last_page": 2, "from": 11, "to": 15 } }
```

**Códigos:** 200 · 401 sin sesión · 403 actor o rol no autorizado (sin `meta` ni totales) · 404
paciente inexistente en el historial (como hoy) · 422 parámetros inválidos · 500 genérico.

**Datos:** sin migraciones ni cambios de esquema.

**Pantallas y estados** (diseño: `design.md`, candidato `c3`):
- Control bajo la rejilla de tarjetas (Pacientes, Expedientes, Usuarios, Tratamientos, Contenido) o
  en el pie de la tarjeta de la tabla (historial, Mis citas, las dos secciones de pendientes).
- Estados: carga (la región de carga actual de cada pantalla), vacío (el estado vacío actual, sin
  control; CA6), una sola página (sin control; CA6), error (el aviso actual con "Reintentar").
- Accesibilidad: `nav` con nombre "Páginas de <lista>"; enlaces con nombre ("Página 3", "Página 8,
  última", "Página siguiente"); actual con `aria-current="page"`; no disponibles sin `href`,
  `aria-disabled="true"` y fuera del orden de foco; "…" con `aria-hidden`; al cambiar de página, el
  foco va al título de la lista y la región de estado de la pantalla anuncia "Página N de M"
  (D6). Botones ≥ 44 px y foco visible (CA11).
- Dirección (CA8): `?pagina=N` (y `busqueda`, `estado`; en Contenido, `pestana`) con
  `history.replaceState`. El historial del expediente y las secciones del inicio no la usan.
- **Qué ve cada rol:** no cambia ningún permiso; cada rol ve las mismas listas que hoy.

## Estrategia de pruebas
- **Unit (Core):** `PageRequest` y `Page` con ejemplos y propiedades (Eris): para cualquier total y
  tamaño, `from`/`to`/`last_page` coherentes y la página fuera de rango se ajusta a la última.
  Fronteras: `per_page` 50 → 50 y 51 → 50; `page` 1 válida y 0 → excepción; total 0 → `last_page` 1.
- **Unit (JS):** `pagination.js` ejecutado en Node con un DOM mínimo (patrón de
  `tests/Modules/ContentManagement/Unit/SiteScriptsTest.php`): ventana de números (8 páginas en la
  2 → 1 2 3 4 … 8; en la 1 de 3 → 1 2 3 sin "…"; en la 8 de 8 → 1 … 7 8), extremos no disponibles,
  lectura de `?pagina=abc` → 1 y `?pagina=-3` → 1 (CA9), una sola página → sin control (CA6).
- **Integración por endpoint** (`tests/Modules/<Módulo>/Integration/*PaginationTest.php`): con 30
  pacientes de factoría, página 1 → 12 y `meta` correcto; página 3 → 6; página 999 → la 3 (CA9);
  `per_page=10000` → 50 (CA13); `per_page=0`, `page=abc`, `search` de 101 caracteres → 422;
  búsqueda que encuentra a alguien de la página 3 y lo devuelve en la página 1 (CA4); `search` con
  `%` y `_` busca el texto literal (TM3); `counts` igual con y sin `status`/`search` (CA5); orden
  estable con `created_at` repetido: recorrer todas las páginas da cada id una vez (CA7). Por rol:
  paciente en `/patients` → 403 sin `meta`; asistente en `/users` → 403; paciente B no ve citas de
  A aunque mande `patient_id` (CA14). Usuarios con 0 resultados → `from`/`to` `null`.
- **Pendientes del inicio:** con 15 sin cerrar, página 2 → 5 elementos y `total` 15; el doctor solo
  ve las suyas en cada página (regla por rol con su caso contrario: el administrador ve todas).
- **Contrato que cambia:** se actualizan los tests que fijan la forma antigua:
  `PublicTestimonialSubmissionTest` (objeto suelto con un resultado), `PortalPerformanceTest`
  (100 sin paginar → 10 por página y total 100), `PatientCrudTest` (filtro), `GetUsersTest`
  (conteos), `TreatmentTest`, `UnclosedAppointmentsTest` y `CompletedWithoutTrackingTest` (forma
  con `meta`). Las rutas `/public/*` conservan sus tests.
- **Rendimiento (CA12):** con 5 000 pacientes y 5 000 citas de una paciente, cada endpoint responde
  con ≤ 12 (o 10) elementos y en < 300 ms en el contenedor local, como `PortalPerformanceTest`.
- **Navegador (`npm run test:ui`):** entradas nuevas en `screens.json`: `pacientes-pagina-2`
  (clic en "Siguiente", texto "Mostrando 13–24 de…"), `pacientes-buscar-otra-pagina`,
  `portal-mis-citas-pagina-2`, `expediente-historial-pagina-2`, `dashboard-pendientes-pagina-2`,
  `contenido-galeria-pagina-2`, y el control a 390, 768 y 1440 (desborde, toque, foco, contraste
  ya los comprueban los módulos de `ui-audit`). Volver con "Atrás" a la misma página (CA8) como
  flujo de `pacientes-pagina-2`.
- **Diseño:** `evaluate.mjs` con `design/criterios.json` sobre las instantáneas de `pacientes` y
  `portal-mis-citas` de la build servida en local.
- **Entradas nuevas con otro tipo:** `page[]=1`, `per_page=1.5`, `search[]=x` → 422, nunca 500.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: pedir `per_page` enorme para sacar la lista completa | A01:2025 · API3:2023 | `PageRequest` | Tope de 50 en el dominio, sin depender del JS | integración: `per_page=10000` → 50 elementos (CA13) |
| TM2 | Elevation of privilege: pedir páginas de una lista sin el rol | A01:2025 · API1/API5:2023 | Endpoints paginados | Middlewares actuales + `assertCan()` en cada caso de uso nuevo (Contenido incluido) antes de contar | integración: 403 sin `meta` por actor no autorizado (CA14) |
| TM3 | Tampering / injection: búsqueda con `%`, `_`, comillas o SQL | A05:2025 (Injection) | Repositorios con `search` | Consultas con parámetros; comodines escapados; texto recortado ≤ 100 | integración: `search=%` no devuelve todo; `search="' OR 1=1"` → 0 resultados y 200 |
| TM4 | Denial of service: `page` enorme o búsqueda larga en bucle | A04:2025 · API4:2023 | `PageRequest`, FormRequests | `page` ajustada a la última (sin `OFFSET` gigante), `search` ≤ 100, límite `throttle:api` existente | integración: `page=999999999` → última página en < 300 ms; `search` de 101 → 422 |
| TM5 | Information disclosure: los totales revelan cuántos pacientes hay a quien no puede verlos | A01:2025 | `PageMeta` | Los contadores se calculan después de `assertCan()`; un 403 no lleva `meta` | integración: incluido en los tests de CA14 |
| TM6 | Spoofing / BOLA: el paciente manda el id de otro en la consulta del portal | A01:2025 · API1:2023 | `GetPortalAppointmentsController` | El paciente sale de la sesión; los parámetros que no están en el `FormRequest` se ignoran | integración: paciente B con `?patient_id=A` solo ve sus citas (CA14) |
| TM7 | Information disclosure en errores | A10:2025 | Controladores nuevos | `UnexpectedErrorResponse` + `Log::error` sin la búsqueda | integración: excepción forzada → cuerpo genérico (P7) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: 12 por página en listas de tarjetas, con "Mostrando…" y control | Core Pagination; Patients, Users, Treatments, Content `findPage`; `pagination.js`; pantallas | integración · `tests/Modules/Patients/Integration/PatientsPaginationTest.php` · 30 pacientes → 12 y `meta`; análogos en `tests/Modules/Users/Integration/UsersPaginationTest.php`, `tests/Modules/Appointments/Integration/TreatmentsPaginationTest.php`, `tests/Modules/ContentManagement/Integration/ContentPaginationTest.php`; navegador · `screens.json` `pacientes-pagina-2`, `contenido-galeria-pagina-2` · texto "Mostrando 13–24 de 30 pacientes" |
| CA2: 10 filas en tablas e inicio, secciones por separado | Appointments `findPageByPatient`, `findUnclosedBefore`; AppointmentTracking; `dashboard.js`, `records/index.js`, `portal/appointments.js` | integración · `tests/Modules/Appointments/Integration/PatientAppointmentsPaginationTest.php`, `PortalAppointmentsPaginationTest.php`, `UnclosedAppointmentsTest.php` (página 2), `tests/Modules/AppointmentTracking/Integration/CompletedWithoutTrackingTest.php` (página 2); navegador · `portal-mis-citas-pagina-2`, `expediente-historial-pagina-2`, `dashboard-pendientes-pagina-2` |
| CA3: Anterior, números con "…", Siguiente; actual anunciada; extremos no disponibles; vuelve al inicio de la lista | `pagination.js`, `x-ui.pagination` | unit JS · `tests/Modules/Core/Unit/PaginationScriptTest.php` · ventana 1 2 3 4 … 8, extremos con `aria-disabled`, `aria-current`; navegador · `pacientes-pagina-2` · foco en el título tras "Siguiente"; verificación manual: M1 y M2 de `design.md` contra `candidatos/c3-*.html` a 390 y 1440 px |
| CA4: búsqueda y filtros en todos los registros, desde la página 1 | Patients y Treatments `search`; `status` en el servidor; JS reinicia a página 1 | integración · `PatientsPaginationTest.php` · paciente de la página 3 encontrado en la 1 con `search` y con `status=inactive`; `TreatmentsPaginationTest.php` · por descripción; navegador · `pacientes-buscar-otra-pagina` |
| CA5: contadores de toda la lista (Contenido incluido), sin importar búsqueda ni filtro | `countByStatus`, `countByRole`, `stats`; `PageMeta`; JS lee `meta.counts` | integración · cada `*PaginationTest.php` · `counts` igual con y sin `status`/`search`; `TreatmentsPaginationTest.php` · promedio sobre todos |
| CA6: sin control con una página; estado vacío actual sin control | `pagination.js` | unit JS · `PaginationScriptTest.php` · `last_page` 1 → no pinta; navegador · `pacientes` con búsqueda sin resultados y `portal-mis-citas-vacio` · sin `nav` |
| CA7: cada registro una vez, orden fijo | Orden con desempate por `id` en cada `findPage` | integración · cada `*PaginationTest.php` · `created_at` repetido, recorrer todas las páginas → ids únicos y completos; Mis citas más reciente primero |
| CA8: recargar o "Atrás" conserva página, búsqueda y filtro | `pagination.js` (`replaceState`), pantallas | unit JS · `PaginationScriptTest.php` · lee y escribe `pagina`, `busqueda`, `estado`; navegador · flujo de `pacientes-pagina-2` · recargar con `?pagina=2&busqueda=…` muestra lo mismo |
| CA9: página inexistente → última; no numérica → primera | `PageRequest`/`Page` (ajuste), `pagination.js` (lectura) | unit · `tests/Modules/Core/Unit/Pagination/PageTest.php` y `PagePropertiesTest.php`; integración · `page=999` → última; unit JS · `?pagina=abc` → 1 |
| CA10: crear, editar o eliminar sin salir de la página; si queda vacía, la anterior | JS de cada pantalla vuelve a pedir la página actual; el servidor ajusta la página | integración · `PatientsPaginationTest.php` · última página con un elemento borrado → la anterior; navegador · `pacientes-eliminar` en página 3 |
| CA11: sin desborde, 44 px, teclado y foco a 390/768/1440 | Estilos del control con tokens | navegador · `npm run test:ui` (desborde, toque, foco, contraste) en las entradas nuevas; diseño · `evaluate.mjs` con `criterios.json` |
| CA12: con 5 000 registros solo llega la página, < 1 s | `findPage` con `limit`/`offset` y `count()` | integración · `tests/Modules/Patients/Integration/PatientsPaginationPerformanceTest.php` y `PortalPerformanceTest.php` · 5 000 → 12/10 elementos, < 300 ms; verificación manual: la pantalla muestra la página en < 1 s en local con 5 000 pacientes |
| CA13 (abuso): `per_page` 10 000 → máximo 50 | `PageRequest::MAX` | integración · cada `*PaginationTest.php` · `per_page=10000` → 50 |
| CA14 (abuso): listas ajenas → rechazo como hoy, sin totales | Middlewares + `assertCan` + sesión del portal | integración · paciente en `/patients`, asistente en `/users` y en Contenido, doctor en `/treatments` → 403 sin `meta`; paciente B con `patient_id` de A → solo las suyas |
| TM1 | `PageRequest::MAX` | = CA13 |
| TM2 | `assertCan` en los casos de uso nuevos | = CA14; `ContentPaginationTest.php` · `assertCan` con un doble que deniega → 403 |
| TM3 | Búsqueda con parámetros y comodines escapados | integración · `PatientsPaginationTest.php` · `search=%`, `search=_`, `' OR 1=1` |
| TM4 | Ajuste de página y longitud de búsqueda | integración · `page=999999999` → última en < 300 ms; `search` de 101 → 422 |
| TM5 | Contadores tras `assertCan` | = CA14 (403 sin `meta`) |
| TM6 | Paciente de la sesión | integración · `PortalAppointmentsPaginationTest.php` · `?patient_id=` ajeno ignorado |
| TM7 | `UnexpectedErrorResponse` | integración · un test de 500 por módulo (Patients, Users, Appointments, AppointmentTracking, ContentManagement) |
| RNF: rendimiento < 1 s con 5 000 | = CA12 | = CA12 |
| RNF: WCAG 2.1 AA en el control | `pagination.js`, `x-ui.pagination` | navegador · `npm run test:ui` (a11y, foco, contraste); unit JS · nombres accesibles y `aria-current` |
| RNF: textos en español sin términos técnicos | Textos del control | unit · `tests/Modules/Core/Unit/Design/UiCopyTest.php` · textos del control |

## Observabilidad
- Logs nuevos: ninguno de éxito. Los 500 de los controladores nuevos: `Log::error('<lista>.page_failed', ['exception' => clase])`, con el contexto de petición existente y sin la búsqueda ni datos del paciente.
- Eventos de auditoría: ninguno (spec → "Auditoría": no aplica; la auditoría de lecturas es el objetivo 5).
- Métricas y alertas: sin cambios.
- Cómo se verifica tras el deploy: en Grafana (Loki), sin `page_failed` en la hora siguiente al deploy; abrir Pacientes y Mis citas en producción y pasar a la página 2.

## Descubribilidad
No aplica: no crea ni cambia rutas públicas; las rutas `/api/v1/public/*` y las páginas del sitio
no cambian.

## Rollout
- Feature flag: no aplica (decisión del usuario D4): prototipo sin datos reales; un interruptor obligaría a mantener dos formas de cada lista y de cada respuesta.
- Orden de despliegue: una sola versión (tag) con backend y frontend juntos en la misma imagen; sin migraciones.
- Compatibilidad: una pestaña abierta con el JS anterior sigue funcionando contra la API nueva, porque `data` conserva su forma (vería solo la primera página hasta recargar). Excepción: Contenido, cuyo JS anterior ya acepta `{"data": [...]}`.
- Rollback: `docker/prod/rollback.sh` a la versión anterior; no hay esquema que revertir.
- Métricas a vigilar tras el deploy: 5xx y `page_failed` en Loki; tiempo de respuesta de `/api/v1/patients` y `/api/v1/portal/appointments`.

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Contrato: mismo `/api/v1` con cambio aditivo (`data` igual + `meta`) en lugar de `/api/v2`. Hoy solo los consume el JS de esta app, que se despliega junto; la app Android no existe (spec 021 en `draft`) (decisión del usuario, 2026-11-18).
- D2 · Contadores: siempre de toda la lista, sin búsqueda ni filtro, en `meta.counts` (decisión del usuario, 2026-11-18; spec CA5).
- D3 · Contenido entra en CA5 (decisión del usuario, 2026-11-18; spec editada y aprobada de nuevo).
- D4 · Sin feature flag (decisión del usuario, 2026-11-18).
- D5 · Tamaño de la spec: una sola spec pese a tocar 6 módulos y extender 7 specs (decisión del usuario, 2026-11-18, en `/specify`; confirmada al planear).
- D6 · Anuncio del cambio de página: por la región de estado única de cada pantalla (`ui/status.js`), no con una región propia en el resumen como sugería `design.md` → Accesibilidad, porque el sistema exige una sola región por marco (`system.md` → "Región de estado"). El resultado para quien usa lector de pantalla es el mismo.
- D7 · Paginación sin `paginate()` de Eloquent: `count()` + `offset`/`limit` dentro del repositorio, para que `Domain/` reciba un `Page` propio y no un `LengthAwarePaginator` (P3). Opciones: `paginate()` (menos código, acopla la capa de dominio a Illuminate) o cursor (no da total ni números de página). Se elige `count` + `offset`.
- D8 · Métodos nuevos, no cambiar los actuales: `findByStatusAndDate`, `findByRoleAndStatus` (pacientes y usuarios) y `findAllByIdByName` tienen consumidores fuera de estas listas (agenda, disponibilidad, selectores, portal).
- D9 · Contenido admin con controladores nuevos: los actuales los comparten las rutas `/public/*`; separarlos evita cambiar la API pública, que queda fuera de esta spec.
- D10 · Parámetro de búsqueda en la dirección `busqueda` (no `buscar`): `?buscar=1` ya significa "abrir con el buscador enfocado" en Expedientes.
- D11 · Sin índices nuevos: con 5 000 registros la consulta paginada cabe en el objetivo; CA12 lo mide y, si no, se añade el índice en una migración dentro de la misma tarea.
- Sin ADR: no hay dependencias nuevas ni dependencias nuevas entre módulos (todos ya dependen de `app/Core`).

## Impacto en arquitectura
- `docs/architecture.md`: convenciones de la API (paginación de listas: `page`, `per_page`, `search`, forma de `meta`) y la pieza `app/Core/Domain/Pagination` en la descripción de `app/Core`.

## Riesgos y mitigaciones
- Pantallas que dependían de la lista completa (contadores, búsqueda en el navegador): todas pasan a `meta`; los tests de integración de contadores lo fijan.
- Romper consumidores ocultos del mismo endpoint (selector de pacientes, doctores, tratamientos de la agenda; sitio público): usan otros controladores y métodos que no cambian (D8, D9); sus tests siguen en la suite.
- Orden inestable con `created_at` repetido: desempate por `id` y test que recorre todas las páginas.
- Líneas base de composición de `ui-audit` que cambian en muchas pantallas: se reescriben en la tarea de cada pantalla, revisando las capturas.
- Tamaño del cambio (9 pantallas, 11 endpoints): tareas por lista, cada una vertical (API + pantalla + tests) y verde por sí sola.
- Detalles que dejó el evaluador del diseño ("…" estrecho a 390 px, peso de "Anterior" deshabilitado, distancia del resumen a 1440 px): revisión manual en la tarea del componente, con `/design-spec 028 --fix` si cambian un valor.
