---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-10-15
spec_sha: bd4d219e2406
plan_sha: a92e2a9631cf
tasks_sha: 13acfdef1dde
---

# Análisis · 017 Reprogramar y cancelar citas

## Resumen
`fail`. El plan cambia códigos de `/api/v1` de forma incompatible sin la excepción a P4 (A1). Al
desglose le faltan pruebas de navegador de CA5 y del flujo de cancelar (A2, A3), y ninguna tarea
ajusta los tests y la línea base de composición que la regla nueva rompe (A4, A5). Análisis hecho
por un subagente independiente; cada hallazgo se verificó contra los archivos.
Conteo: CRÍTICA 1 · ALTA 4 · MEDIA 10 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 10 (CA5 sin tarea que escriba su test) |
| Amenazas con control y test | 5 / 5 (TM3 parcial: A9) |
| Principios de la constitución evaluados | 15 / 15 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución | CRÍTICA | plan.md → Constitution Check P4, Contratos ("Cambios frente a 007"), D1 | Cambio incompatible de códigos (409 → 422) dentro de `/api/v1`; P4 exige `/api/v2` y no hay excepción registrada | Decisión del usuario (2026-10-15): excepción en esta spec, `❌ aceptado` en P4 con motivo → `/plan --fix A1`. `UpdateAppointmentTest` y `CreateAppointmentTest` fijan el código de cada rechazo |
| A2 | Cobertura | ALTA | tasks.md T015, T030, Cobertura (CA5); plan.md → Estrategia "Navegador" | Ninguna tarea prueba que, tras un 422 en "Reprogramar cita" y en "Agendar cita", el diálogo siga abierto, conserve lo escrito y muestre el texto del servidor; el caso genérico de `checks-a11y.mjs:781-810` no lo comprueba | `/tasks --fix A2`: comprobación nueva en el navegador, que falla antes de T030, para los dos diálogos |
| A3 | Cobertura / P2 | ALTA | tasks.md T015 ("sin enviar"), T033; plan.md → Trazabilidad CA1, CA7 | El flujo de cancelar (un solo `PUT {status: cancelada}`, cierre de los dos diálogos, aviso) y "No, mantenerla", cerrar o fondo sin petición, y el aviso "Cita reprogramada.", solo tienen verificación manual | `/tasks --fix A3`: con la API interceptada, confirmar envía exactamente un `PUT` y anuncia el aviso; salir sin confirmar no envía nada (`checks-dialogs.mjs`) |
| A4 | Inconsistencia | ALTA | `tests/Modules/whatsApp/Integration/WhatsAppFlowLogsTest.php:56`, `AppointmentsIntegrationTestCase.php:34`, `AppointmentsAccessControlTest.php:101,104` | Fechas fijas de septiembre de 2026 sin reloj fijo: con la regla nueva, el test de 015 CA18 (RS9.a) y las filas del administrador en la tabla de permisos pasan a 422 | `/tasks --fix A4`: tarea que fija el reloj en esos tests; la suite completa en verde en T034 |
| A5 | Inconsistencia | ALTA | `tests/Browser/baseline-1440.json:90,93`; `checks-composition.mjs`; tasks.md T030, T033 | Sin línea base para `agenda-cita-reprogramar` y `agenda-cita-cancelar`; la de `agenda-cita-detalle-pendiente` y la renombrada cambian; ninguna tarea la regenera | `/tasks --fix A5`: regenerar la línea base de las entradas de la agenda tras T033, revisando el diff; `ui-audit` en verde en T034 |
| A6 | Inconsistencia | MEDIA | tasks.md T020, T021, T022, T024 | T020 retira un método que la entidad y `AppointmentAplicationExceptions` aún usan, y T021 cambia firmas que usan los casos de uso: la suite queda en rojo entre tareas, contra la convención de la spec | `/tasks --fix A6`: reordenar o fusionar para que la suite salga en verde al cerrar cada tarea (salvo los `skip('017:')`) |
| A7 | Inconsistencia | MEDIA | plan.md → Pantallas, "Detalle de cita"; `view-appointment.js:261-267` | "Reprogramar" cierra el detalle antes de abrir su diálogo; al cerrarlo, el foco no puede volver a "Reprogramar" | `/plan --fix A7`: el foco vuelve al disparador de la cita en el calendario (comportamiento actual de `dialog.js`); lo comprueba la verificación de foco al cerrar de `checks-dialogs` |
| A8 | Seguridad | MEDIA | spec CA10; plan.md TM1, `UpdateAppointmentRequest` | El FormRequest valida antes de `assertCan`: un no administrador con un cuerpo inválido recibe 422, no acceso denegado | Decisión del usuario (2026-10-15): 403 con cualquier cuerpo → `/plan --fix A8` y `/tasks --fix A8`; test: cuerpos inválidos por actor en `AppointmentsAccessControlTest` |
| A9 | Cobertura (TM3) | MEDIA | plan.md TM3; `UpdateAppointmentUseCase.php:82-83` | `PUT {status: completada}` sobre una cita cancelada la reabre; TM3 no lo cubre | Decisión del usuario (2026-10-15): incluirlo → `/specify --edit 017` (criterio nuevo), `/plan --fix A9`, `/tasks --fix A9`; test: 409 en `UpdateAppointmentTest` |
| A10 | Inconsistencia (`extends`) | MEDIA | plan.md → Configuración y Riesgos | La zona global cambia también "citas de hoy" y la vigencia de las promociones (011, 012); el plan solo menciona `created_at` y esas specs no están en `extends` | Decisión del usuario (2026-10-15): global y declararlo → `/specify --edit 017` (`extends`), `/plan --fix A10`; test: "citas de hoy" en el límite del día en la zona de la clínica |
| A11 | Inconsistencia | MEDIA | plan.md → Impacto en arquitectura; spec.md → Notas para /plan; tasks.md T090–T092 | Nadie anota el cambio en el Historial de 007, y la spec y el plan describen distinto el cambio de 007 CA3 | `/specify --edit` (redacción) y `/tasks --fix A11` (tarea que lo registra, o nota de que lo hace `/release`) |
| A12 | Inconsistencia (diseño) | MEDIA | design.md → "Cambios a incorporar al sistema", "Componentes"; tasks.md T001 | La regla "en cancelar, solo en el botón que la confirma" contradice la advertencia en `danger` del mismo diálogo; design.md dice "Exclusivos: ninguno" y el plan crea un componente propio | `/design-spec 017 --fix`: la regla dice "solo dentro de la confirmación (botón y advertencia)" y el componente queda declarado; se revisa en `/review` contra `system.md` |
| A13 | Inconsistencia | MEDIA | tasks.md → convención de navegador | Con la política de no hacer push en `/implement`, no queda demostrado que las pruebas de navegador fallan antes y pasan después (P2) | `/tasks --fix A13`: `npm run test:ui -- --only agenda` en local en T015 y en las tareas de interfaz, con el resultado anotado |
| A14 | Conflicto | MEDIA | `docs/roadmap.md:15` | El objetivo 4 envía OB7.a, OB7.b y OB8.a a "017 (monitoreo, por crear)"; 017 es esta spec | Propuesta: destino "spec de monitoreo por crear" sin número, en el mismo commit que `/plan --fix` (pendiente de que el usuario la confirme) |
| A15 | Constitución (P6) | MEDIA | plan.md D6 | `POST` gana un rechazo 422 sin FormRequest (invariante de dominio); zona gris de "reglas de validación" en P6 | Propuesta: confirmar D6 tal cual (la entrada de `POST` no cambia); `CreateAppointmentTest` fija el 422 y su texto (pendiente de que el usuario la confirme) |
| A16 | Cobertura de riesgos | BAJA | plan.md → Riesgos ("Datos de `ui:audit-data`") | El riesgo no aplica: `screens.json` fija `now` en 2026-07-22, el seeder usa julio de 2026 y las comprobaciones interceptan la API | `/plan --fix A16`: cerrarlo con ese motivo |
| A17 | Cobertura | BAJA | tasks.md T002 | Nada comprueba que `.env.example` declare `APP_TIMEZONE` | Añadir `APP_TIMEZONE` a `EnvExampleDefaultsTest` |
| A18 | Cobertura | BAJA | tasks.md T015 | CA8 solo con la cita completada, no la cancelada; CA7 sin el caso de cerrar la confirmación con la X o el fondo | Añadir ambos casos a `checks-dialogs.mjs` |
| A19 | Ambigüedad | BAJA | spec.md → Decisiones, fila `supuesto` del margen | El supuesto fija el límite `<=` de la regla sin confirmación explícita | Aceptado: la spec, aprobada por el usuario, ya dice "igual o anterior" |
| A20 | Ambigüedad | BAJA | spec CA5; `status.js:170` | Ante un 409 (choque o cita cerrada) el diálogo muestra el texto genérico "choca con otro registro"; CA5 no define el texto | Aceptado: el texto genérico es el del sistema para 409; se afirma en la prueba de navegador |
| A21 | Ambigüedad | BAJA | plan.md → contrato del `PUT` | No define `status` con `whatsapp_reminder`, pone "404 no existe (hoy 409)" en rechazos y no da el texto de `cannotChangeClosedAppointment` | `/plan --fix A21`: precisarlo y fijarlo en `UpdateAppointmentTest` |
| A22 | Inconsistencia | BAJA | T001; `system.md:261-262`; design.md → Pantallas | El inventario de `system.md` y los nombres de capturas siguen con "Editar cita"; design.md usa `agenda-cita-editar` y el plan `agenda-cita-reprogramar` | `/design-spec 017 --fix` y `/tasks --fix A22`: nombres nuevos en el inventario |

## Decisiones pendientes del usuario
- A14: ¿destino de OB7.a, OB7.b y OB8.a en el roadmap? Propuesta: "spec de monitoreo por crear", sin número.
- A15: ¿se confirma D6 (sin FormRequest en `POST`, porque su entrada no cambia)?

## Aceptados
Aceptados el 2026-10-15 (decisión del usuario sobre A1, A8, A9 y A10; propuesta para A17–A20):
- **A17** → nota de T002: añadir `APP_TIMEZONE` a `EnvExampleDefaultsTest`
- **A18** → nota de T015: CA8 también con la cita cancelada del seeder; CA7 también al cerrar con la X y con el fondo
- **A19** → spec: el límite `<=` ya está en el texto aprobado de CA3 y CA4
- **A20** → nota de T015: ante un 409, afirmar el texto genérico del sistema en la región del diálogo
