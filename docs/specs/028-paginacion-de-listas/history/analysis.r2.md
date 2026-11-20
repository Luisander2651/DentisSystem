---
result: fail
round: 2
mode: full
constitution_version: 1.2.1
date: 2026-11-18
spec_sha: 6cff30b8c918
plan_sha: 4600daff723d
tasks_sha: 54fcbc7a4f7a
---

# Análisis · 028 Paginación de las listas del panel y del portal

## Resumen
No pasa. De la ronda 1 se resolvieron 21 de 25 hallazgos (dos con un efecto nuevo); A15 y A22
siguen abiertos en parte. Hay dos críticos nuevos de la constitución: T013 modifica un servicio de
dominio que depende de una clase de infraestructura (P3), y buscar por correo pondría el email en
la consulta GET, que nginx registra y Alloy envía a Loki (P11). Hay cinco altos: el retiro de
`AppointmentPage` está incompleto, `UserServiceTest` se rompe, faltan datos para la página 2 de
Usuarios y Tratamientos, el detalle del portal depende de la lista completa y faltan entradas de
`ui-audit` por tarea. Modo completo (las tareas se regeneraron), con subagente independiente;
cada hallazgo se verificó en los archivos.
Conteo (nuevos): CRÍTICA 2 · ALTA 5 · MEDIA 2 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 14 / 14 (con salvedades: A22, A30, A31) |
| Amenazas con control y test | 7 / 7 (falta la amenaza de los logs de acceso: A27) |
| Principios de la constitución evaluados | 15 / 15 (P3 y P11 fallan: A26, A27) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | `plan.md` P4 "❌ aceptado" con aprobación del usuario y D1 corregido; T090 |
| A2 | CRÍTICA | resuelto | `PendingTrackingService` y `PendingTrackingLayersTest` (T029) |
| A3 | ALTA | resuelto con efecto nuevo (→ A31, A32) | D13 y la definición de "en verde" |
| A4 | ALTA | resuelto | T028 y T029 incluyen casos de uso y tests unitarios |
| A5 | ALTA | resuelto | T009 y plan → Rendimiento, sin reloj |
| A6 | ALTA | resuelto | 422 por endpoint; `ListPageRequest` en T026 y T027; comodines en T006 y T013 |
| A7 | MEDIA | resuelto | T005 con `screens.json` y `baseline-1440.json` |
| A8 | MEDIA | resuelto | Las tareas de pantalla con cambio visible incluyen la línea base |
| A9 | MEDIA | resuelto | Parciales de Contenido, componente del historial y `usuarios/index.js` |
| A10 | MEDIA | resuelto | `PendingPageRequest`; `per_page=50` → 10 |
| A11 | MEDIA | resuelto con efecto nuevo (→ A28) | D12 |
| A12 | MEDIA | resuelto | `spec.md` CA2 y `extends` |
| A13 | MEDIA | resuelto | T020–T023 |
| A14 | MEDIA | resuelto | Plan → Observabilidad; T008, T012 |
| A15 | MEDIA | abierto (parcial) | T012 usa `UnexpectedErrorResponse`, pero T091 no actualiza RS15.a en `security.md` |
| A16 | MEDIA | resuelto | Plan → Códigos; T026 |
| A17 | MEDIA | resuelto | T020 |
| A18 | MEDIA | resuelto | T011 |
| A19 | BAJA | resuelto | Spec CA9; T004 |
| A20 | BAJA | resuelto | Spec CA4; plan D14; T013 |
| A21 | BAJA | resuelto | Plan; T023 |
| A22 | BAJA | abierto (parcial) | El plan dice `PortalPerformanceTest` con 5 y 5 000 y `LIMIT`/`OFFSET`; T027 dice 5 y 100 sin `LIMIT`/`OFFSET` |
| A23 | BAJA | resuelto | T091; nota de T027 |
| A24 | BAJA | resuelto | T001 |
| A25 | BAJA | resuelto | T005, T030 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A26 | Constitución (P3) | CRÍTICA | T013; `TreatmentsService.php` L10, L15; plan P3 | T013 modifica un servicio de `Domain/` que importa y recibe `EloquentTreatmentRepository` | Decisión del usuario → `/plan 028 --fix A26`, `/tasks 028 --fix A26`: el servicio depende de `TreatmentsRepositoryInterface`, enlazada en `AppServiceProvider` (test: `AppointmentsArchitectureTest` con `TreatmentsService` en su dataset) |
| A27 | Seguridad (P11) | CRÍTICA | Plan P11 y Modelo de amenazas; `docker/nginx/*.conf` (sin `log_format`: formato `combined` con la consulta); `docker-compose.prod.yml` (Alloy recoge los contenedores) | Buscar por correo pone el email en `GET /api/v1/patients?search=…` y en `?busqueda=`; nginx lo registra y llega a Loki. Hoy la búsqueda va en el navegador: la exposición es nueva | Decisión del usuario → `/plan 028 --fix A27`, `/tasks 028 --fix A27`: amenaza TM8; los logs de acceso no contienen la consulta de `search`/`busqueda` (test: comprobación de la configuración de nginx; tras el deploy, en Loki no aparece `search=`) |
| A28 | Inconsistencia / P2 | ALTA | T028, T029; plan (constante en `PendingPageRequest`); `AppointmentPageTest.php`; `AppointmentPageException.php`; `AppointmentsArchitectureTest.php` L63; `AppointmentsService::findUnclosed` | Retirar `AppointmentPage` deja sin tocar su test de ejemplos, su excepción y la comprobación de arquitectura; llevar el tope a un `FormRequest` obligaría a `Domain/` a importar HTTP; D12 solo aprueba retirar `AppointmentPagePropertiesTest` | Decisión del usuario (retirar `AppointmentPageTest`) → `/plan 028 --fix A28`, `/tasks 028 --fix A28`: el tope de 10 vive en Domain; `grep -rn AppointmentPage app tests` sin resultados y la suite en verde |
| A29 | Pruebas | ALTA | T010; `tests/Modules/Users/Unit/Domain/UserServiceTest.php` L22 | La clase anónima que implementa `UserRepositoryInterface` deja de compilar al añadir métodos | `/tasks 028 --fix A29`: `UserServiceTest` en T010 y en verde |
| A30 | Cobertura | ALTA | T005, T012, T015; `UiAuditSeeder` (5 usuarios, 3 tratamientos) | `usuarios-pagina-2` y `tratamientos-pagina-2` no tienen página 2 con los datos de prueba | `/tasks 028 --fix A30`: T005 siembra al menos 13 usuarios (sin cambiar los selectores de doctor) y 13 tratamientos; `UiAuditSeederTest` lo comprueba |
| A31 | Inconsistencia | ALTA | T027; `resources/js/pages/portal/appointment-detail.js` L153-154 (cargado en Mis citas y en el inicio del portal) | El detalle del portal vuelve a pedir la lista sin página y busca la cita por id: tras T027 falla para cualquier cita fuera de las 10 primeras | `/plan 028 --fix A31`, `/tasks 028 --fix A31`: el detalle no depende de la lista completa (test: entrada de `ui-audit` con el detalle desde la página 2; `portal-inicio*` y `portal-detalle-abierta` en verde) |
| A32 | Constitución (P2, D13) | MEDIA | T026, T028, T029, T030 | Los `--only` omiten pantallas que consumen lo que cambia (`dashboard-completar`, `dashboard-cancelar`, `dashboard-registrar-seguimiento`, el resto de `dashboard-*`; `portal-mis-citas*` en T026) | `/tasks 028 --fix A32` |
| A33 | Constitución (P2) | MEDIA | T002 | `PageRequest` no tiene propiedades Eris | `/tasks 028 --fix A33`: propiedades de `PageRequest` (tamaño = min(n, 50); página < 1 → excepción) |
| A34 | Inconsistencia | BAJA | Plan → Contratos; `routes/api.php` L164-166 | `GET /api/v1/appointment-tracking/without-tracking` usa el mismo controlador y no se nombra | `/plan 028 --fix A34` |
| A35 | Ambigüedad | BAJA | Plan → Contratos (`/patients`, `status`) | `status=x` pasaría de 400 a 422 sin fijarlo | `/plan 028 --fix A35`: 422 con su caso en `PatientsPaginationTest` |
| A36 | Pruebas que no prueban | BAJA | Plan → Trazabilidad CA1 | Con los datos de prueba el texto sería "de 25 pacientes", no "de 30" | `/plan 028 --fix A36` |

## Decisiones pendientes del usuario
- A26: ¿T013 corrige la dependencia de `TreatmentsService` (interfaz y enlace) o se registra una excepción a P3?
- A27: ¿se ajusta el formato de log de nginx en esta spec (TM8) o se registra una excepción a P11 con vencimiento?
- A28: ¿se aprueba retirar también `AppointmentPageTest.php`?

## Enmiendas propuestas a la constitución
- P11: "Cómo se verifica" incluye los logs de acceso del servidor web; un dato de contacto en la consulta de un GET cuenta como dato en logs.
- P3: verificarlo con los `*ArchitectureTest`, incluida la regla "un Domain Service no recibe clases de Infrastructure".

## Aceptados
Ninguno todavía.
