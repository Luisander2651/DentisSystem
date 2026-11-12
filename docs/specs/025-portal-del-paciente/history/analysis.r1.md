---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-11-10
spec_sha: 361f6c4f12e5
plan_sha: 6dfe2fb49e6f
tasks_sha: 378f0f66495f
---

# Análisis · 025 Portal del paciente

## Resumen
`fail`. Hay un hallazgo CRÍTICO: P6 está marcado como "no aplica" en rutas nuevas con parámetro, sin
excepción aprobada (A1). Hay además cinco ALTOS que dejarían la suite en rojo o sin una prueba real:
el cambio de guards rompe las páginas de error del paciente antes de su tarea (A2); el inicio de c3
cambia criterios de las specs 016 y 020, que no están en `extends` (A3); falta `/mis-citas` en
`GuestRedirectTest` (A4); `npm run test:ui` no puede medir K1 ni K6 (A5); y hay una fila
`contradicción` sin decisión del usuario en `design.md` (A6).

El análisis lo hizo un subagente independiente (perfil `critical`) que contrastó los artefactos con
el código. Cada hallazgo se verificó después en el archivo citado.

Conteo: CRÍTICA 1 · ALTA 5 · MEDIA 7 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 20 / 20 |
| Amenazas con control y test | 11 / 12 (TM12 es riesgo aceptado, plan R4) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P6) | CRÍTICA | `plan.md` Constitution Check, fila P6; `tasks.md` Constitution Check, fila P6 | P6 exige `FormRequest` y 422 en todo endpoint nuevo. Las cuatro rutas reciben `{id}` y el plan responde 400 validando con `AppointmentId`, marcándolo "➖ no aplica" sin excepción aprobada ni enmienda. Es el mismo criterio de 023 y 024 | Decisión del usuario: enmienda PATCH de P6 o excepción para esta spec. Después, `/plan 025 --fix A1`. Test: `PortalAppointmentQrTest` y `PortalPrescriptionsTest` (`no-es-uuid` → 400) |
| A2 | Constitución (suite en verde) | ALTA | `tasks.md` T002, T003; `resources/views/layouts/error.blade.php:7`; `tests/Modules/Core/Integration/NotFoundPageTest.php:35-43`; `WebAccessDeniedPagesTest.php` | Al pasar al paciente al guard `patient`, `layouts/error` (que lee `auth('sanctum')`) deja de reconocerlo y fallan los casos `paciente` de `NotFoundPageTest` y `WebAccessDeniedPagesTest`. T002 no termina en verde sin T003 | `/tasks 025 --fix A2`: el cambio de guards y el de `layouts/error` quedan en una tarea que termina con `NotFoundPageTest` y `WebAccessDeniedPagesTest` en verde con token real de paciente |
| A3 | Inconsistencia (`extends`) | ALTA | `spec.md` frontmatter `extends: [001, 009, 024]`; `design.md` "Componentes → Inicio"; `tasks.md` T030; `RoleCopyTest.php:66-94`; `DashboardScreenTest.php:168-174` | c3 sustituye el `x-ui.page-hero` del inicio del paciente por el saludo del tablero: se pierden la insignia "Mi cuenta", `data-role-label="inicio"` ("Paciente") y la inicial. Eso cambia 016 CA30 y 020 A16, que protegen `RoleCopyTest` y `DashboardScreenTest`. Ninguna de las dos specs está en `extends` y T030 no toca esos tests | Decisión del usuario (qué conserva el inicio del paciente); después, `/specify 025 --edit` (añadir 016 y 020 a `extends` si cambian) y `/tasks 025 --fix A3`. Test: `RoleCopyTest` y `DashboardScreenTest` en verde con el inicio final |
| A4 | Constitución (suite en verde) | ALTA | `tasks.md` T020; `tests/Modules/Core/Integration/GuestRedirectTest.php:19-44` | `GuestRedirectTest` exige que su lista coincida exactamente con las rutas web con sesión. T020 añade `/mis-citas` sin tocar ese test, así que falla | `/tasks 025 --fix A4`: T020 añade `/mis-citas` a la lista. Test: "covers every web route that asks for a session" y la redirección a `/login` |
| A5 | Cobertura (K1, K6) / P2 | ALTA | `spec.md` "Criterios de diseño" K1, K6; `plan.md` "Estrategia de pruebas → Navegador"; `tasks.md` T030; `tests/Browser/checks-screens.mjs:216-222` | El arnés de `test:ui` no comprueba en entradas de pantalla que un elemento esté en la primera pantalla ni su tamaño mínimo, y no mide CLS (la única comprobación "en vista" es la del día de la agenda). `portal-inicio` no podría fallar por K1 ni K6, y el plan no usa `evaluate.mjs` sobre la build | `/plan 025 --fix A5` y `/tasks 025 --fix A5`: una prueba de navegador que falle con el QR (≥ 240 × 240 px), la fecha, la hora o "Descargar QR" fuera de la primera pantalla, o con CLS > 0.1, y pase con el inicio de c3 |
| A6 | Constitución (Decisiones) | ALTA | `design.md` "Decisiones", fila `contradicción` del 2026-11-09 (K5 y `G-sin-js`) | La fila `contradicción` tiene fuente "agente", sin la decisión del usuario | Decisión del usuario: confirmar que K5 sale de los criterios medidos y se verifica en `/implement`; registrar la fuente en `design.md` |
| A7 | Inconsistencia (plan ↔ framework) | MEDIA | `plan.md` D7 y "Contratos y datos"; `tasks.md` T004, T011; `vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:103-115` | `AuthenticatesRequests` tiene prioridad sobre los middlewares de ruta sin prioridad, así que el 401 de `auth` (y el 403 de `patient` si va antes) no pasa por `no-store`. D7 da por hecho lo contrario y el test unitario de T004 no lo detectaría | `/plan 025 --fix A7` y `/tasks 025 --fix A7`: el 401 y los 403 del grupo `portal` llevan `Cache-Control: private, no-store`. Test: peticiones reales sin sesión, de personal y de paciente inactivo en `PortalAppointmentsTest` |
| A8 | Ambigüedad (datos de prueba) | MEDIA | `plan.md` "Cambios por módulo → Pruebas de navegador"; `tasks.md` T030, T031; `tests/Browser/ui-audit.mjs:57`; `UiAuditSessionCommand` | `paciente.vacio` existe en la semilla, pero `ui:audit-session` y `ROLES` no lo admiten. Los estados vacíos de `test:ui` no tienen con qué entrar | `/tasks 025 --fix A8`: los estados vacíos se prueban con respuestas simuladas (`flows`), que el arnés ya admite. Test: `portal-inicio-vacio` y `portal-mis-citas-vacio` |
| A9 | Inconsistencia (D4 ↔ CA11, CA12) | MEDIA | `spec.md` CA11, CA12; `plan.md` D4 | CA11 y CA12 hablan del "detalle de una cita"; con D4 no hay ruta de detalle y la spec no lo recoge | `/specify 025 --edit`: registrar en "Decisiones" que el detalle del portal se compone de la lista, las recetas y el QR, y ajustar la redacción de CA11 y CA12 (con aprobación del usuario) |
| A10 | Pruebas que no prueban | MEDIA | `plan.md` "Estrategia de pruebas → QR, Recetas"; `tasks.md` T015, T017 | Si la cita ajena de prueba está abierta, una implementación que mire el estado antes que la pertenencia pasaría el test y respondería 409 a una ajena cerrada, revelando que existe | Aceptable como nota: cita ajena completada y cancelada → 404 con el mismo cuerpo que un UUID inexistente (QR); ajena completada con seguimiento → 404 (recetas) |
| A11 | Pruebas que no prueban | MEDIA | `plan.md` "Estrategia de pruebas → Guards"; `tasks.md` T002, T018; `tests/Modules/Auth/Integration/AuthIntegrationTestCase.php:64-73` | En un mismo test, el guard conserva el usuario de la primera petición; la prueba de ids iguales con dos tokens podría pasar o fallar por la caché del guard | Aceptable como nota: cada petición con otro token empieza sin guards en caché (como `forgetAuthState()`); `GuardSeparationTest` falla si se quita el provider del guard `sanctum` |
| A12 | Cobertura (RNF, TM11) | MEDIA | `spec.md` "Requisitos no funcionales → Rendimiento"; `plan.md` Trazabilidad (RNF y TM11) | La spec pide < 2 s en el 95 % de las cargas con 100 citas; el plan solo cuenta consultas. TM11 cita `PortalPerformanceTest`, que no prueba el límite de peticiones | Aceptable como nota: medida a mano con 100 citas en T050 (se anota el tiempo); TM11 se apoya en el test existente de `throttle:api` |
| A13 | Inconsistencia (`[P]`) | MEDIA | `tasks.md` T022, T024, T025 | T024 comprueba con `UiComponentsTest` sin listarlo y es `[P]` junto a T022, que lo edita | `/tasks 025 --fix A13`: ninguna pareja `[P]` comparte `UiComponentsTest.php` |
| A14 | Pruebas que no prueban | BAJA | `plan.md` "Web" y Descubribilidad; `tasks.md` T020 | No existe `sitemap.xml` (SE3 pendiente), así que "no lista `/mis-citas`" se cumple siempre | Nota: basta `noindex` mientras no exista el sitemap |
| A15 | Inconsistencia (plan ↔ código) | BAJA | `plan.md` "Vistas que leen el guard", R5; `tasks.md` T003; `resources/views/layouts/admin.blade.php:2` | El paciente nunca pinta `layouts/admin`; solo `layouts/error` necesita el cambio | Se resuelve con A2 |
| A16 | Ambigüedad | BAJA | `plan.md` "Interfaz · portal"; `tasks.md` T020 | No se dice de qué layout hereda `pages/portal/appointments`; con `layouts.admin`, el view composer cargaría `role` sobre `PatientModel` y fallaría | Nota: hereda de `layouts.patient`. Test: `PortalPagesTest` (200 y menú del paciente) |
| A17 | Cobertura de riesgos | BAJA | `spec.md` "Cobertura de riesgos" (RS1.d `dentro`); `plan.md` R6 | RS1.d figura como `dentro` en la spec y como ya mitigada (v0.1.0) en el plan. No se declara nada mitigado de más | Con A9 en `/specify --edit`: RS1.d mitigada en v0.1.0 (spec 014) y los tests nuevos como refuerzo |
| A18 | Cobertura | BAJA | `plan.md` "Pantallas" ("Revisar con `design:design-handoff`") | Paso del plan sin tarea; el plugin no está disponible en esta sesión | `/plan 025 --fix A18`: quitar la nota |
| A19 | Cobertura | BAJA | `spec.md` CA6; `tasks.md` T017, T032 | Falta el caso con recomendaciones y sin recetas | Nota: `PortalPrescriptionsTest` y una entrada `portal-detalle-*` con "Recomendaciones" y "Esta cita no tiene recetas" |
| A20 | Inconsistencia (convención) | BAJA | `tasks.md` T010, T015, T016; `AGENTS.md` (`execute(DTO)`) | Solo T013 nombra su DTO | Nota: cada caso de uso nuevo nombra su DTO `final readonly` con `::create()` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-10:
- **A1:** enmienda PATCH de P6 (constitución 1.2.1) → `/plan --fix A1`.
- **A3:** el inicio del paciente queda como c3; la 025 extiende 016 y 020 y sus tests se ajustan
  (`/specify --edit`, `/plan --fix A3`, `/tasks --fix A3`).
- **A6:** K5 sale de los criterios medidos y se verifica en `/implement` (fuente en `design.md`).
- **A9 y A17:** spec editada: sin petición de detalle, CA11 y CA12 ajustados, RS1.d fuera (ya
  mitigada en v0.1.0).
- **A10–A12, A14, A16, A19, A20:** aceptados como notas (abajo).

## Enmiendas propuestas a la constitución
- **P6 (PATCH):** ver A1 — aprobada y aplicada (1.2.1, 2026-11-10).
- **P3:** incluir en "Cómo se verifica" los `*ArchitectureTest` de cada módulo, que ya existen en
  Appointments y AppointmentTracking, además de la revisión.

## Aceptados
Aceptados por el usuario el 2026-11-10:
- **A10** → nota de T015, T017: cita ajena cerrada → 404 con el mismo cuerpo que un UUID inexistente
- **A11** → nota de T002, T018: sin guards en caché entre peticiones con tokens distintos; el test falla sin el provider
- **A12** → nota de T019, T050: TM11 con el test de `throttle:api`; medida a mano con 100 citas
- **A14** → nota de T020: `noindex` en lugar de la comprobación del sitemap mientras no exista
- **A16** → nota de T020: la vista hereda de `layouts.patient`
- **A19** → nota de T017, T032: cita con recomendaciones y sin recetas
- **A20** → nota de T010, T015, T016: cada caso de uso nombra su DTO
