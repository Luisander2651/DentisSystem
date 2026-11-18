---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-11-18
spec_sha: 9038d7aaae4f
plan_sha: aa22219fd211
tasks_sha: d20fdb19c153
---

# Análisis · 028 Paginación de las listas del panel y del portal

## Resumen
No pasa: dos hallazgos críticos de la constitución (P4: el cambio de contrato no es aditivo y la
premisa de que no hay app Android es falsa; P3: T037 modifica un caso de uso que llama al
repositorio sin servicio de dominio) y cuatro altos (las tareas de API dejan `ui-audit` en rojo
hasta su tarea de pantalla, el caso de uso de T037 tiene que cambiar su firma, los tests de < 300 ms
serían inestables y faltan los 422 por endpoint). Análisis completo con un subagente independiente
(perfil `critical`); cada hallazgo se verificó en los archivos citados.
Conteo: CRÍTICA 2 · ALTA 4 · MEDIA 12 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 14 / 14 (CA1 y CA2 no quedan en verde por sí solos en T008, T020–T026, T031 y T032: A3) |
| Amenazas con control y test | 7 / 7 (TM3 solo se prueba en Pacientes: A6) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P4) | CRÍTICA | `plan.md` Constitution Check P4 y D1; `GetGalleryImagesController.php` L32-36; `docs/specs/021-sesion-app-movil/spec.md`; `MobileLoginTest.php` L40 | No es un cambio aditivo: Contenido admin pasa de lista suelta (u objeto suelto con un resultado) a `{data, meta}`, y sin `page` todas las listas devuelven 12 en lugar de todo (misma forma, otro significado). La premisa de D1 es falsa: la app Android existe (021, liberada en v0.7.0 como registro); la 021 y la 023 documentan que consume `/auth/me` y `/internal/appointments/*`, y `MobileLoginTest` usa `GET /api/v1/users` solo como ruta protegida | Decisión del usuario (excepción a P4 registrada o `/api/v2`) → `/plan 028 --fix A1` corrige D1 y el Constitution Check. Se cumple si P4 queda ✅ con la excepción firmada por el usuario o con rutas v2, y un test fija que `/auth/me` y `/internal/*` no cambian |
| A2 | Constitución (P3) | CRÍTICA | T037; `GetCompletedWithoutTrackingUseCase.php` L19-29; `plan.md` P3 | El caso de uso que modifica T037 llama a `PendingTrackingRepositoryInterface` sin servicio de dominio | `/plan 028 --fix A2` y `/tasks 028 --fix A2`: la consulta paginada pasa por un servicio de dominio de AppointmentTracking (test: el caso de uso no importa `*RepositoryInterface`) |
| A3 | Constitución (P2, rebanadas) | ALTA | T008→T009/T011, T020–T026→T027/T028, T031→T033, T032→T034; `plan.md` Riesgos ("cada una vertical") | Las tareas de API cambian endpoints que el JS actual sigue usando: tras T008 los contadores de Pacientes dicen 12 y la búsqueda solo ve 12; `expedientes-buscar` (busca "Valeria") puede quedar fuera de la página 1; tras T032 falla el flujo de `portal-mis-citas` (`tr:last-child` = la cita más antigua). `npm run test:ui` queda en rojo hasta la tarea de pantalla | Decisión del usuario sobre el tamaño de tarea → `/tasks 028 --fix A3`: cada tarea deja en verde `npm run test:ui -- --only <pantallas que consumen el endpoint>` |
| A4 | Inconsistencia | ALTA | T037 y su nota; `GetCompletedWithoutTrackingUseCase.php` (`execute()` sin parámetros); `TrackingUseCasesAuthorizationTest.php` L134-146 | Cambiar el caso de uso no es opcional, y su test unitario lo llama sin argumentos (lo mismo en T036 con `DashboardUseCasesAuthorizationTest`) | `/tasks 028 --fix A4`: el caso de uso y sus tests unitarios son archivos fijos de T036 y T037; si pasan del límite, dividir |
| A5 | Pruebas que no prueban | ALTA | T010, T032; `plan.md` Estrategia de pruebas (< 300 ms); `PortalPerformanceTest.php` (cuenta consultas, no mide tiempo); roadmap 019/R8 | Un test de reloj de 300 ms con 5 000 filas por test sería inestable en `pest --parallel` | `/plan 028 --fix A5` y `/tasks 028 --fix A5`: CA12 se comprueba con ≤ 12/10 elementos, el mismo número de consultas con 5 y con 5 000 filas (inserción masiva) y la consulta con `LIMIT`/`OFFSET`; el < 1 s queda como verificación manual |
| A6 | Constitución (P6) | ALTA | T020, T022, T024, T026, T031, T032 | P6 pide un test de entrada inválida por endpoint y estas tareas no lo tienen; T032 no nombra su `FormRequest` (TM6 lo da por hecho). TM3 solo se prueba en Pacientes (T015 no prueba `%`/`_`) | `/tasks 028 --fix A6`: `page=abc` y `per_page=0` → 422 en cada endpoint; `ListPageRequest` explícito en T032; comodines en T015 |
| A7 | Cobertura | MEDIA | T005; `UiAuditSeeder` L113-135, L337-357 | La paciente del portal y el expediente de prueba son la misma (Paula); más de 10 citas cambian `portal-mis-citas`, `expediente-*`, agenda y reportes, y T005 no incluye `screens.json` ni `baseline-1440.json` | `/tasks 028 --fix A7`: fijar el mes y la posición de las citas nuevas o incluir esos archivos |
| A8 | Inconsistencia | MEDIA | `plan.md` Cambios por módulo y Riesgos; tareas de pantalla | Ninguna tarea de pantalla incluye `tests/Browser/baseline-1440.json` | `/tasks 028 --fix A8` |
| A9 | Inconsistencia | MEDIA | T027–T029, T033, T014 | Contenido: cada pestaña es un parcial Blade que ninguna tarea monta antes de T029; T033 no incluye `components/records/appointments-history-table.blade.php`; el JS de Usuarios es un `<script>` en línea que no puede importar `ui/pagination.js` | `/tasks 028 --fix A9`: montar el control en los parciales en T027/T028; añadir el componente del historial a T033; en T014, `resources/js/pages/usuarios/index.js` registrado en `vite.config.js` |
| A10 | Inconsistencia | MEDIA | `plan.md` Contratos (pendientes); T036, T037; `AppointmentPage.php` L16, L31-32; spec 020 TM8 | Con `ListPageRequest` (`per_page` hasta 50), `AppointmentPage::create` lanzaría `tooManyItems` → 500 y se rompería el tope de 10 de la 020 | `/plan 028 --fix A10`: los pendientes solo aceptan `page` (test: `per_page=50` → 10 elementos) |
| A11 | Duplicación | MEDIA | `plan.md` (Core `Page`); `AppointmentPage.php` y sus propiedades; roadmap 020/R17 | Dos value objects de página sin decidir cómo se arma `meta` de los pendientes | `/plan 028 --fix A11` |
| A12 | Inconsistencia (extends) | MEDIA | `spec.md` CA2 y `extends`; 020 CA4/CA7 ("y N más"); 025 CA3 ("ve todas"); 027 (`PublicTestimonialSubmissionTest`); 014 (`UnexpectedErrorTest`) | La spec no dice qué criterios de 020 y 025 reemplaza, y 027 y 014 no están en `extends` | `/specify --edit 028` |
| A13 | Pruebas que no prueban | MEDIA | `ContentManagement/Integration/UnexpectedErrorTest.php` L19-28; T020–T026 | El test simula métodos que las rutas admin dejarán de llamar y seguiría en verde por accidente | `/tasks 028 --fix A13`: simular `findPage`/`countByStatus` |
| A14 | Inconsistencia (P14) | MEDIA | `plan.md` Observabilidad; T008; `UnexpectedErrorResponse.php` L33-39 | `UnexpectedErrorResponse` ya registra `unexpected_error`; `page_failed` lo duplicaría y solo T008 lo nombra | `/plan 028 --fix A14`: un solo registro por error (test: un 500 deja un log) |
| A15 | Riesgos (RS15) | MEDIA | T013; `GetUsersByRoleAndStatusController.php` L43-50; `security.md` RS15 | El controlador que toca T013 registra `$e->getMessage()` | `/tasks 028 --fix A15`: `UnexpectedErrorResponse` y RS15.a por corrección en `security.md` |
| A16 | Inconsistencia | MEDIA | `plan.md` Contratos ("404 … como hoy"); T031; `GetAppointentByPatientIdUseCase.php` L21-30 | Hoy un paciente inexistente da 200 con lista vacía, no 404 | `/plan 028 --fix A16`: contrato 200 vacío, con su test |
| A17 | Constitución (P5) | MEDIA | T020–T026; `OnlyAdminApiContractTest` L28 | Añadir `assertCan` cambia la autorización de Contenido y solo se prueba al asistente | `/tasks 028 --fix A17`: asistente, doctor, paciente e inactivo → 403 sin `meta` |
| A18 | Cobertura (TM5) | MEDIA | T012, T013 | El caso de uso de Usuarios está repartido entre dos tareas y sin test de "no cuenta sin autorización" | `/tasks 028 --fix A18` |
| A19 | Ambigüedad | BAJA | `spec.md` CA9; `plan.md` L132; T004 | Para una página negativa la spec dice "la última" y el plan "la primera" | `/specify --edit 028`: una página negativa o 0 → la primera |
| A20 | Inconsistencia (008) | BAJA | `tratamientos/index.js` L261-263, L279-296; `plan.md` Contratos | La búsqueda actual de Tratamientos también encuentra por duración; el redondeo del promedio y su valor sin datos no se fijan | Decisión del usuario → `/specify --edit 028` y `/plan 028 --fix A20` |
| A21 | Inconsistencia | BAJA | `plan.md` D9; `routes/api.php` L86-98, L157-161 | `GetTestimonialsController` queda sin ruta; el admin de Contenido deja de aceptar `id`, `url` y `status` sin declararlo; no se nombran los permisos de `assertCan` | `/plan 028 --fix A21` |
| A22 | Inconsistencia | BAJA | `plan.md` Contratos (pendientes); T032 | El `meta` de los pendientes no lleva `total`; el volumen de CA12 en el portal (5 000 frente a 100) no coincide | `/plan 028 --fix A22` (con A5) |
| A23 | Seguridad | BAJA | `security.md` "Superficie de ataque"; T091; roadmap 025/R10 y 025/R23 | Las entradas nuevas no se documentan en `security.md`; T032 resuelve 025/R10 y toca 025/R23 sin citarlos | `/tasks 028 --fix A23` |
| A24 | Inconsistencia | BAJA | `design.md` → Accesibilidad; `plan.md` D6; T001 | T001 copiaría la región de estado propia que D6 descarta | `/tasks 028 --fix A24` |
| A25 | Pruebas que no prueban | BAJA | T038; `UiAuditSeeder` L421-447 | En el navegador solo "Citas sin cerrar" tiene página 2 | `/tasks 028 --fix A25`: más de 10 completadas sin seguimiento en T005 |

## Decisiones pendientes del usuario
- A1: ¿excepción registrada a P4 (las 11 listas son del panel web; la app Android consume `/auth/me` y `/internal/*`, que no cambian) o publicar las listas en `/api/v2`?
- A3: ¿tareas de API y pantalla juntas (más de 3 archivos de código por tarea) o tareas de API que adaptan lo mínimo del JS para dejar `ui-audit` en verde?
- A20: ¿la búsqueda de Tratamientos sigue encontrando por duración?

## Enmiendas propuestas a la constitución
- P3: verificarlo con los `*ArchitectureTest` existentes, incluida una regla "un UseCase no importa `*RepositoryInterface`".
- P2: una tarea que cambia un endpoint que consume una pantalla deja en verde también `npm run test:ui` de esa pantalla; los tests de rendimiento no comparan tiempos de reloj (precedente 019/R8).

## Aceptados
Ninguno todavía.
