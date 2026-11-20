---
spec: 028-paginacion-de-listas
verdict: changes_requested
round: 2
date: 2026-11-20
base: 5d9ed13
head: 59be294
human_signoff: pending
---

# Review · 028 Paginación de las listas del panel y del portal

## Resumen
Ronda 2 (`--rerun`) sobre `5d9ed13..59be294`: los 9 importantes de la ronda 1 (R1–R9) están
corregidos con pruebas que fallarían con lo protegido roto. La suite, Pint, el build, el validador,
gitleaks, las auditorías de dependencias y `ui-audit` completo están en verde. Queda 1 importante
nuevo: la entrada `pacientes-direccion` no prueba que la búsqueda y el filtro de la dirección
lleguen a la API, porque con los datos sembrados darían la misma lista sin ellos (R28). El usuario
decide corregirlo antes de liberar (T039).
Conteo: bloqueante 0 · importante 1 · menor 2

Revisores: un subagente con contexto limpio y los encargos A, C+D y S juntos, modelo `opus` (perfil
`critical`), porque `code.diff` pesa 53 KB. B (plan y alcance) y la preparación para release, en la
sesión principal. Sin revisor de accesibilidad aparte: lo cubrió el mismo revisor sobre
`pagination.js` y las vistas tocadas, junto con `PaginationScriptTest` y `ui-audit`. Sin revisor de
SEO: la ronda no toca rutas públicas.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2842 passed (46984 assertions) |
| `vendor/bin/pint --test` sobre los PHP de la rama (como CI) | ✅ passed |
| `npm run build` | ✅ |
| `npm run test:ui` (137 entradas, 411 visitas) | ✅. En la corrida completa, `agenda-cita-cancelar` @ 390 falló por límite de la API (429 tras 6 intentos) y la corrida se cortó por falta de memoria del equipo tras `error-403-admin`; esa entrada y las 5 que faltaban, repetidas con `--only`, en verde (18 visitas, 0 fallos). La entrada no es de la 028. Las entradas nuevas de T034–T036, dos veces en verde durante `/implement` |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PatientsPaginationTest`, `UsersPaginationTest`, `TreatmentsPaginationTest`, `ContentPaginationTest`; `pacientes-pagina-2`, `contenido-galeria-pagina-2` | ✅ | |
| CA2 | `PatientAppointmentsPaginationTest`, `PortalAppointmentsPaginationTest`, `UnclosedAppointmentsTest`, `CompletedWithoutTrackingTest`; `portal-mis-citas-pagina-2`, `expediente-historial-pagina-2`, `dashboard-pendientes-pagina-2`; `UiAuditSeederTest` | ✅ | R9 corregido (T038) |
| CA3 | `PaginationScriptTest` (4, 5, 2 de 8 y 3 de 5 en las dos ventanas; extremos no disponibles; peso de la actual) | ✅ | R1, R2 y R3 corregidos (T033) |
| CA4 | `PatientsPaginationTest`, `TreatmentsPaginationTest`; `pacientes-buscar-otra-pagina` (desde `?pagina=2`, buscar y filtrar → "Mostrando 1–12"), `maxlength` en las tres pantallas | ✅ | R4 y R5 corregidos (T034, T035) |
| CA5 | `counts` en cada `*PaginationTest` | ✅ | |
| CA6 | `PaginationScriptTest` (`last_page` 1); `portal-mis-citas-vacio` | ✅ | |
| CA7 | ids únicos recorriendo páginas en cada `*PaginationTest` | ✅ | |
| CA8 | `PaginationScriptTest`; `expect.address` en `pacientes-buscar-otra-pagina` y `pacientes-pagina-2`; `pacientes-direccion` | ⚠ | La escritura de la dirección ya se prueba (R6). La carga con `busqueda` y `estado` solo prueba el campo y el chip, no la petición (R28) |
| CA9 | `PageRequestTest`, `PageTest`, cada `*PaginationTest` (página 999 → la última) | ✅ | |
| CA10 | `PatientsPaginationTest`; `pacientes-eliminar-ultima-pagina` (desde `?pagina=3` → "Mostrando 13–24 de 24", `pagina=2`) | ✅ | R7 corregido (T036) |
| CA11 | `npm run test:ui` a 390, 768 y 1440 | ✅ | |
| CA12 | `PatientsPaginationPerformanceTest`, `PortalPerformanceTest` | ✅ | |
| CA13 | `per_page=10000` en cada `*PaginationTest`; 60 citas → 50 en Mis citas y en el historial | ✅ | R8 corregido (T037) |
| CA14 | 403 sin `meta` por actor no autorizado en cada módulo; `?patient_id=` ajeno en el portal | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` lista `tests/Browser/checks-composition.mjs` sin tarea que lo cite: es la entrada `ADMITTED` que exige la nueva composición de `pacientes-buscar-otra-pagina` al empezar en `?pagina=2` (T035, `d7331e8`), reflejada igual en `baseline-1440.json`.
- Partes del plan sin implementar: ninguna (0 tareas sin diff).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | T033–T038 añadidas por la ronda 1 |
| P2 Test que falla antes y pasa después | ✅ | Cada corrección con su test; `pacientes-direccion` no prueba la petición (R28) |
| P3–P7, P13 | ✅ | La ronda no toca capas, contratos, autorización, FormRequests ni errores del servidor |
| P8 Secretos | ➖ | gitleaks limpio en los 8 commits de la ronda |
| P9 Migraciones | ➖ | Sin migraciones |
| P10 Dependencias con ADR | ➖ | `deps.stat` vacío |
| P11 Datos sensibles | ✅ | El seeder solo borra citas de pacientes `@ui-audit.dentissa.test` que no sembró, tras `assertAllowedEnvironment` |
| P15 Solo el sistema de diseño | ✅ | Peso por variante con tokens (`font-semibold` / `font-bold`), `OnlyDesignTokensTest` en verde |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`5d9ed13..HEAD`, 8 commits) | ✅ 0 hallazgos |
| semgrep `p/php` | ➖ no repetido: la ronda no toca `app/`, `config/`, `bootstrap/` ni `routes/` (ronda 1: 0 hallazgos) |
| `composer audit` · `npm audit --audit-level=high` | crít: 0 · alta: 0 · media: 0 |
| trivy config `docker/` | ➖ no repetido: la ronda no toca `docker/` (ronda 1: 0) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Inyección (XSS) | ✅ | `innerHTML` de `pagination.js` solo recibe textos fijos, números de `meta` y `pageHref` hecho con `URLSearchParams.toString()`; la búsqueda de la dirección va a `input.value` |
| A01:2025 Control de acceso | ➖ | La ronda no toca autorización |
| Proceso | ✅ | Sin dependencias nuevas, sin rutas protegidas tocadas, sin instrucciones a agentes en el código |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (spec → "Auditoría": ninguno).
- Datos sensibles en logs del código tocado: ninguno; la ronda no añade logs.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1–R9 | — | — | Corregidos (T033–T038); ver Criterios de aceptación | — |
| R28 | importante | tests/Browser/screens.json:3746 | `pacientes-direccion` carga `?pagina=2&busqueda=prueba&estado=active`, pero todos los pacientes sembrados se apellidan "Prueba" y están activos (`database/seeders/UiAuditSeeder.php:296`): la lista es la misma sin búsqueda ni filtro, y `address` repite la dirección con la que se abre. Pasaría aunque la pantalla no enviara `search` ni `status` a la API (CA8) | Responder el `GET` solo con la consulta esperada (`query`) y un total propio, y exigir ese total en "Mostrando" |
| R29 | menor | resources/js/ui/pagination.js:19 | `const GAP ='…'` sin espacio tras `=` | `const GAP = '…'` |
| R30 | menor | tests/Browser/checks-dialogs.mjs:416 | `texts:Object.fromEntries(...)` sin espacio tras `:` | `texts: Object.fromEntries(...)` |

## Preparación para release
- Rollback factible: sí, sin cambios respecto a la ronda 1 (`docker/prod/rollback.sh`; sin esquema que revertir).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D4).
- Docs actualizadas (architecture, deployment): sí (T090, T091); la ronda no cambia contratos ni despliegue.
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: sin cambios respecto a la ronda 1 (RS9.c pendiente de verificar en Loki, T097; RS15.a pendiente).

## Tareas añadidas
- T039 ← R28

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
- **R29** → roadmap: espacio tras `=` en `GAP` de `pagination.js`
- **R30** → roadmap: espacio tras `:` en `texts` de `checks-dialogs.mjs`
