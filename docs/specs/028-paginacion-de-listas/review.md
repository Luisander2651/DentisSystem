---
spec: 028-paginacion-de-listas
verdict: changes_requested
round: 1
date: 2026-11-19
base: 280a96b
head: 5d9ed13
human_signoff: pending
---

# Review · 028 Paginación de las listas del panel y del portal

## Resumen
Sin bloqueantes: la suite, Pint sobre los archivos de la rama, el build, las herramientas de seguridad
y `ui-audit` completo están en verde, y cada criterio tiene implementación y prueba. Quedan 9
importantes: tres defectos del control de páginas (el "…" en móvil, los extremos no disponibles y
el peso de la página actual), los buscadores sin `maxlength`, cuatro pruebas que no prueban lo que
dicen y tres entradas nuevas de `ui-audit` que fallan en una segunda corrida local. El usuario decide
corregirlos todos (T033–T038) antes de liberar.
Conteo: bloqueante 0 · importante 9 · menor 18

Revisores: cuatro subagentes por módulo con contexto limpio (Core + Patients + Users; Appointments +
AppointmentTracking; ContentManagement; interfaz y accesibilidad), modelo `opus` (perfil `critical`),
porque `code.diff` pesa 508 KB. B (plan y alcance) y la preparación para release, en la sesión
principal. `design:accessibility-review` y `design:design-critique` no están disponibles en esta
sesión: el revisor de interfaz aplicó la lista de la skill y `design.md`. Sin revisor de SEO: el diff no
toca rutas públicas ni layouts del sitio (las rutas tocadas son del panel; plan → "Descubribilidad":
no aplica).

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2834 passed (45903 assertions). Antes de la review falló 1 (`GuardSeparationTest`, forma vieja de Mis citas), corregido en `ee60b87` |
| `vendor/bin/pint --test` sobre los PHP de la rama (como CI) | ✅ passed. Sobre todo el repo falla por deuda anterior (CRLF de la copia de Windows y estilo de archivos que la rama no toca) |
| `npm run build` | ✅ |
| `npm run test:ui` (135 entradas) | ✅ tras quitar contenido local de pruebas manuales que el seeder no conoce (8 promociones, 3 testimonios, 2 certificaciones; aprobado por el usuario). Las 17 entradas nuevas, segunda vez: 3 fallan por una cita que deja `agenda-cita-crear` (R9); sin ella, en verde |
| `python .ai/bin/aidd.py validate` | ✅ tras marcar los criterios y declarar RS9.c en el roadmap (`5d9ed13`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PatientsPaginationTest`, `UsersPaginationTest`, `TreatmentsPaginationTest`, `ContentPaginationTest`; `pacientes-pagina-2`, `contenido-galeria-pagina-2` | ✅ | |
| CA2 | `PatientAppointmentsPaginationTest`, `PortalAppointmentsPaginationTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest`; `portal-mis-citas-pagina-2`, `expediente-historial-pagina-2`, `dashboard-pendientes-pagina-2` | ⚠ | Entradas frágiles en una segunda corrida (R9) |
| CA3 | `PaginationScriptTest`; `pacientes-pagina-2` (foco en el título) | ⚠ | Falta el "…" en móvil (R1); extremos no disponibles sin nombre válido (R2) |
| CA4 | `PatientsPaginationTest`, `PatientsPageRepositoryTest`, `TreatmentsPaginationTest`; `pacientes-buscar-otra-pagina`, `expedientes-buscar` | ⚠ | El reinicio a la página 1 y el filtro no se prueban en el navegador (R5) |
| CA5 | `counts` en cada `*PaginationTest` | ✅ | |
| CA6 | `PaginationScriptTest` (`last_page` 1); `portal-mis-citas-vacio` | ✅ | El navegador no comprueba la ausencia del control (R24, menor) |
| CA7 | ids únicos recorriendo páginas en cada `*PaginationTest` | ✅ | Sin cerrar no tiene el caso de fecha repetida (R15, menor) |
| CA8 | `PaginationScriptTest` (`listStateQuery`, `readListState`); entradas `*-pagina-2` cargadas con `?pagina=2` | ⚠ | La escritura de la dirección no se prueba (R6) |
| CA9 | `PageRequestTest`, `PageTest`, cada `*PaginationTest` (página 999 → la última) | ✅ | |
| CA10 | `PatientsPaginationTest` (última página vacía → la anterior) | ⚠ | Sin prueba en navegador, que el plan pedía (R7) |
| CA11 | `npm run test:ui` a 390, 768 y 1440 | ✅ | |
| CA12 | `PatientsPaginationPerformanceTest`, `PortalPerformanceTest` (`LIMIT`/`OFFSET`, mismo número de consultas) | ✅ | |
| CA13 | `per_page=10000` en cada `*PaginationTest` | ⚠ | En Mis citas y en el historial, con 25 citas, no prueba el tope (R8) |
| CA14 | 403 sin `meta` por actor no autorizado en cada módulo; `?patient_id=` ajeno en el portal | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` lista 6 archivos sin tarea que los cite: `checks-composition.mjs` (entradas `ADMITTED`, obligatorias al cambiar líneas base), `checks-screens.mjs` (la galería sembrada solo se busca en la página 1), y `RegisterAppointmentTrackingTest`, `PortalAppointmentsTest`, `PortalUnexpectedErrorTest` y `GuardSeparationTest`, adaptados a la respuesta paginada en el commit de su tarea (T029, T027) o en `ee60b87`.
- Partes del plan sin implementar: ninguna. T001 aparece "sin diff" porque `docs/` queda fuera de `code.diff`; `docs/design/system.md` y `system.html` sí cambian. T031 se desvía del plan con nota en `tasks.md`: la ruta sale de `$request_uri` cortada en el `?` (no de `$uri`, que `try_files` convierte en `/index.php`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | Spec, diseño y plan aprobados; `impl_base` 280a96b |
| P2 Test que falla antes y pasa después | ✅ | Cada tarea con su test; hay tests que no prueban lo que dicen (R5–R8) |
| P3 Capas del módulo | ✅ | `PendingTrackingService` entre caso de uso y repositorio (`PendingTrackingLayersTest`); tres formas distintas de pasar la página al caso de uso (R16, menor) |
| P4 Contrato de API primero | ❌ aceptado | Excepción D1 del plan; `MobileLoginTest` e `InternalAppointmentsTest` sin cambios en toda la rama |
| P5 Autorización en el servidor | ✅ | `assertCan()` antes de consultar y contar en cada caso de uso nuevo; 403 sin `meta` |
| P6 FormRequest | ✅ | `PageQueryRules`, `ListPageRequest`, `PendingPageRequest` y los de cada módulo, mensajes en español |
| P7 Errores sin detalles internos | ✅ | `UnexpectedErrorResponse` en los controladores nuevos o tocados; test de 500 por módulo |
| P8 Secretos | ➖ | Sin secretos ni variables nuevas; gitleaks limpio |
| P9 Migraciones | ➖ | Sin migraciones |
| P10 Dependencias con ADR | ➖ | Sin paquetes nuevos (`deps.stat` vacío) |
| P11 Datos sensibles | ✅ | La búsqueda no se registra; nginx sin la consulta (T031) |
| P12 Producción con aprobación | ✅ | T096 |
| P13 Lógica en el backend | ✅ | Búsqueda, filtro, orden, tope y contadores en el servidor |
| P14 Trazabilidad | ✅ | Sin eventos de auditoría nuevos (excepción del objetivo 5 vigente) |
| P15 Solo el sistema de diseño | ✅ | Clases con tokens; `OnlyDesignTokensTest` en verde. Peso de la página actual distinto del candidato c3 (R3) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`280a96b..HEAD`, 37 commits) | ✅ 0 hallazgos |
| semgrep `p/php` | ✅ 0 hallazgos (23 reglas, 611 archivos) |
| `composer audit` · `npm audit --audit-level=high` | crít: 0 · alta: 0 · media: 0 |
| trivy config `docker/` | ✅ 0 configuraciones erróneas |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `assertCan()` antes de contar (TM2, TM5); paciente de la sesión en Mis citas, `patient_id` ignorado (TM6, `PortalAppointmentsPaginationTest`) |
| A05:2025 Inyección | ✅ | `whereRaw` con nombres de columna fijos y el texto como `?`; `%`, `_` y `\` escapados (`EloquentPatientRepository.php:129`). El test de la barra invertida no la protege (R12, menor) |
| A06:2025 Diseño inseguro | ✅ | `per_page` > 50 → 50 (`PageRequest::MAX_SIZE`, TM4); `search` ≤ 100 |
| A09:2025 Registro y monitoreo | ✅ | `log_format without_query` en cada `server` (`NginxProdConfTest`); verificado en local. El `error_log` de nginx puede llevar la línea de la petición (R14, menor) |
| A10:2025 Condiciones excepcionales | ✅ | 500 genérico; una carrera entre contar y leer puede dar 500 (R10, menor) |
| Proceso | ✅ | Ruta protegida `docker/` tocada solo en T031, aprobada (A27); sin dependencias nuevas; sin instrucciones a agentes en el código |

Fuera del rango y ya registrado: RS4.a (galería, promociones y certificaciones públicas devuelven lo
oculto sin `?status=visible`) sigue pendiente en `docs/security.md`; la 028 no lo toca ni lo empeora.

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (spec → "Auditoría": ninguno; objetivo 5).
- Datos sensibles en logs del código tocado: ninguno. Los 500 los registra `unexpected_error` sin la búsqueda; `GetUsersByRoleAndStatusController` deja de registrar el mensaje de la excepción.
- `request_id` presente en los logs nuevos: no hay logs nuevos; `request_id` sigue pendiente del objetivo 5 (desviación de P14 aceptada).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | resources/js/ui/pagination.js:42 | Por debajo de 768 px se ocultan las vecinas a dos páginas, pero el "…" solo sale donde hay hueco en la ventana completa: la 4 de 8 se ve `1 3 4 5 … 8` y la 5 de 8, `1 … 4 5 6 8` (CA3). `PaginationScriptTest` solo mira qué páginas son `near` | Huecos propios de la ventana móvil, visibles solo por debajo de 768 px; casos 4/8 y 5/8 en el test |
| R2 | importante | resources/js/ui/pagination.js:58 | "Anterior" y "Siguiente" no disponibles son un `<span>` con `aria-label` y `aria-disabled`: ARIA 1.2 no admite nombre ni `aria-disabled` en el rol genérico, y la ficha M2 no queda garantizada | `<a role="link" aria-disabled="true">` sin `href` |
| R3 | importante | resources/js/ui/pagination.js:14 | `CURRENT` hereda `font-semibold` de `ITEM` y añade `font-bold`; en el CSS compilado `.font-semibold` va después y gana: la página actual sale en 600, no en 700 (M1, candidato c3) | El peso en cada variante, no en `ITEM` |
| R4 | importante | resources/views/pages/patients/index.blade.php:38 (también records/index.blade.php:62, tratamientos/index.blade.php:38) | El servidor rechaza `search` de más de 100 caracteres (`PageQueryRules.php:48`) y los campos no tienen `maxlength`: con 101 caracteres, o `?busqueda=` larga en la dirección, la lista muestra "No se pudieron cargar…" | `maxlength="100"` y recortar `busqueda` a 100 en `readListState` |
| R5 | importante | tests/Browser/screens.json:3523 | `pacientes-buscar-otra-pagina` empieza en la página 1: si buscar o filtrar no reiniciara la página, seguiría en verde; el filtro Activos/Inactivos no se prueba (CA4) | Empezar en `?pagina=2`, buscar y filtrar, y comprobar "Mostrando 1–" |
| R6 | importante | tests/Browser/screens.json:3482 | CA8 solo se prueba cargando `?pagina=2`: ningún flujo comprueba que la dirección cambie tras "Siguiente", buscar o filtrar, ni una carga con `busqueda` y `estado`, que el plan prometía | Una comprobación de la dirección en `checks-dialogs.mjs` y flujos que la usen |
| R7 | importante | tests/Browser/screens.json:3468 | CA10 sin prueba en el navegador: `pacientes-eliminar` sigue en la página 1, aunque la trazabilidad dice "en página 3" | Flujo que elimine el único registro de la última página y compruebe la anterior y la dirección |
| R8 | importante | tests/Modules/Appointments/Integration/PortalAppointmentsPaginationTest.php:59 (también PatientAppointmentsPaginationTest.php:67) | `per_page=10000` con 25 citas espera 25: sin el tope de 50 seguiría en verde (CA13, TM1) | Más de 50 citas y 50 elementos con `meta.per_page` 50 |
| R9 | importante | database/seeders/UiAuditSeeder.php | `agenda-cita-crear` (que envía de verdad) deja una cita para la paciente del seeder y `ui:audit-data` no la borra: en la segunda corrida local fallan `portal-mis-citas-pagina-2`, `expediente-historial-pagina-2` y `portal-detalle-fallo`, que cuentan sus citas | Que el seeder borre las citas de sus pacientes que no son suyas |
| R10 | menor | app/Core/Domain/Pagination/Page.php:48 | Contar y leer van en dos consultas: si entra un registro entre ambas en una lista de una página, `totalBelowItems` acaba en 500 | Subir el total al número de elementos en lugar de lanzar |
| R11 | menor | app/Modules/Patients/Infrastructure/Persistence/Eloquent/EloquentPatientRepository.php:110-138 | La búsqueda sin acentos (`ACCENTED`, `likePattern()`, `withoutAccents()`) está copiada de `EloquentTreatmentRepository.php:96-124` | Un ayudante común en `app/Core` |
| R12 | menor | tests/Modules/Patients/Integration/PatientsPageRepositoryTest.php:143 | El caso de la barra invertida pasaría sin escaparla | Sembrar un paciente con `\` y buscarlo |
| R13 | menor | app/Modules/Users/Aplication/UseCases/GetUsersByRoleAndStatusUseCase.php:1; Appointments/Aplication/UseCases/{GetAppointentByPatientIdUseCase,GetTreatmentsUseCase,GetPatientAppointmentsUseCase}.php | Casos de uso y DTOs sin uso tras pasar a los paginados (`GetPatientAppointmentsUseCase` solo lo sostienen sus tests) | Retirarlos |
| R14 | menor | docker/nginx/prod.conf:45,63 (y default.conf:14) | El `error_log` de nginx también llega a Loki y lleva la línea de la petición, con la consulta, en los errores de upstream | Declararlo como riesgo residual y buscar `search=` también en esas líneas tras el deploy |
| R15 | menor | tests/Modules/Appointments/Integration/UnclosedAppointmentsTest.php:327 | "Citas sin cerrar" sin el caso de fecha y hora repetidas recorriendo las páginas (desempate por id, CA7) | El mismo caso que `CompletedWithoutTrackingTest` |
| R16 | menor | app/Modules/Appointments/Aplication/UseCases/ListPatientAppointmentsPageUseCase.php:23; ContentManagement/Modules/*/Aplication/UseCases/List*PageUseCase.php:27; GetTreatmentsAdminController.php:29 | Tres formas de pasar la página al caso de uso (`PageRequest`, DTO, constante en el servicio); Tratamientos arma un `PageRequest`, lo desarma en el DTO y lo vuelve a armar | Una sola forma |
| R17 | menor | tests/Modules/ContentManagement/Integration/UnexpectedErrorTest.php:19 | En los casos `counts` el mock falla antes, en `findPage`: nunca se llega a `countByStatus`; no se comprueba un solo `unexpected_error` (TM7) | `findPage` devuelve una página vacía y `Log::spy()` |
| R18 | menor | app/Modules/ContentManagement/Modules/Galeria/Infrastructure/Persistence/Eloquent/EloquentGalleryImageRepository.php | `counts.total` es visible + oculto, pero `status` de galería es texto libre: otro valor descuadraría `counts.total` y `meta.total` | Total con todos los grupos |
| R19 | menor | ContentManagement/Modules/*/{Eloquent*Repository,List*PageUseCase,List*PageController}.php; resources/js/pages/*/index.js | Mismo código cuatro veces en Contenido y el mismo cargador paginado en 9 pantallas | Ayudante común en Core y `createPagedLoader` en `ui/pagination.js` |
| R20 | menor | ContentManagement/Modules/{Galeria,Promociones,Certificaciones}/Aplication/UseCases/Get*UseCase.php | Ya solo sirven al sitio público e inyectan `AuthorizationServiceInterface` sin usarla; el `catch` de `AuthorizationException` de sus controladores es código muerto | Quitarlos |
| R21 | menor | resources/js/ui/pagination.js:50 | `pageHref` lee `location.search` antes de `writeListState`: los `href` llevan la búsqueda anterior y nunca `pestana` | Escribir el estado antes de pintar |
| R22 | menor | resources/js/ui/pagination.js:163-171 | El clic hace `preventDefault` también con Ctrl/Cmd/Mayús o botón central | No interceptar esos clics |
| R23 | menor | resources/js/pages/patients/index.js:206-211 (igual en el resto) | Si falla la petición de otra página, `render(null)` borra el enlace con el foco y cae al `body` | Mover el foco al título o al aviso |
| R24 | menor | tests/Browser/screens.json:1300, 3590 | `portal-mis-citas-vacio` y "sin coincidencias" de expedientes no comprueban la ausencia del control (CA6) | Añadir el `nav` visible a `absent` |
| R25 | menor | resources/js/ui/pagination.js:89 | `meta.from`, `to`, `total` y `page` entran en `innerHTML` sin forzarlos a número | `Number(...)` |
| R26 | menor | resources/js/ui/pagination.js:190 | Se anuncia "Página N de M" pero no el rango ni el total, que `design.md` pide en la región de estado | Anunciar también "mostrando X–Y de N" o ajustar la ficha |
| R27 | menor | resources/js/pages/patients/index.js:28 (records/index.js:58) | `estado` queda en inglés en la dirección (`?estado=inactive`) | `activos` / `inactivos` mapeados al valor de la API |

## Preparación para release
- Rollback factible: sí. `docker/prod/rollback.sh` a la versión anterior; sin esquema que revertir y el formato del log de nginx vuelve con la imagen `web` (plan → Rollout).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D4).
- Docs actualizadas (architecture, deployment): sí (T090, T091): convención de paginación, `app/Core/Domain/Pagination`, imagen `web` con el log sin la consulta; security, observability y roadmap al día.
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: RS9.c declarada mitigada por T031 con su test y pendiente de verificar en Loki (T097); RS15.a sigue pendiente (corregida solo en `GetUsersByRoleAndStatusController`), sin marcarse mitigada.

## Tareas añadidas
- T033 ← R1, R2, R3 (y el recorte de `busqueda` de R4)
- T034 ← R4
- T035 ← R5, R6
- T036 ← R7
- T037 ← R8
- T038 ← R9

## Aceptados sin tarea
- **R10** → roadmap: carrera entre contar y leer una página da 500 (`Page::create`)
- **R11** → roadmap: búsqueda sin acentos duplicada en Patients y Tratamientos (`/refactor` pendiente)
- **R12** → roadmap: el test de la barra invertida no protege su escape
- **R13** → roadmap: casos de uso y DTOs sin uso en Users y Appointments (`/refactor` pendiente)
- **R14** → roadmap: `error_log` de nginx con la consulta en errores de upstream
- **R15** → roadmap: "Citas sin cerrar" sin test de desempate por id
- **R16** → roadmap: tres formas de pasar la página al caso de uso (`/refactor` pendiente)
- **R17** → roadmap: los casos `counts` del 500 de Contenido no llegan a `countByStatus`
- **R18** → roadmap: `counts.total` de galería con `status` de texto libre
- **R19** → roadmap: duplicación de las listas de Contenido y del cargador paginado (`/refactor` pendiente)
- **R20** → roadmap: dependencia de autorización sin uso en los casos de uso públicos de Contenido (`/refactor` pendiente)
- **R21** → roadmap: `href` del control con la búsqueda anterior y sin `pestana`
- **R22** → roadmap: el control intercepta Ctrl/Cmd + clic
- **R23** → roadmap: foco perdido si falla el cambio de página
- **R24** → roadmap: CA6 en navegador sin comprobar la ausencia del control
- **R25** → roadmap: números de `meta` sin forzar en `innerHTML`
- **R26** → roadmap: el anuncio no dice el rango ni el total
- **R27** → roadmap: `estado` en inglés en la dirección
