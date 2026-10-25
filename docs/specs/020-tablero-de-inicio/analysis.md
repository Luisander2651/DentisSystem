---
result: pass
round: 3
mode: delta
constitution_version: 1.2.0
date: 2026-10-24
spec_sha: 4455acdc1983
plan_sha: 51e6b6527cd7
tasks_sha: f15b69e3ad3a
---

# Análisis · 020 Tablero de inicio

## Resumen
Pasa. Los nueve hallazgos de la ronda 2 (A25–A33) quedaron resueltos; tres con un efecto nuevo,
que se registra como hallazgo. No hay CRÍTICOS ni ALTOS. Los 2 MEDIOS y los 5 BAJOS nuevos no
cambian el diseño y el usuario los aceptó como notas de sus tareas. Delta hecho por un subagente
independiente (diff de ~55 KB); cada hallazgo se verificó en el código.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 |
| Amenazas con control y test | 9 / 9 (TM9 con un control que funciona en la web real) |
| Principios de la constitución evaluados | 6 / 15 en esta delta (P2, P4, P5, P6, P11, P15) más la Definición de terminado; el resto no cambia desde la ronda 2 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A25 | ALTA | resuelto con efecto nuevo (→ A35, A36) | D21 y T056; T026 depende de T057 → T056 → T008 |
| A26 | MEDIA | resuelto | T058 y T059; `seededAccount` solo resuelve correos de `UiAuditSeeder::STAFF_EMAILS`, que ya incluye `inactivo`. El control de 016 TM5 (solo cuentas del seeder) se mantiene |
| A27 | ALTA | resuelto | Registro sin condición en `web` y `api`, entorno comprobado en cada petición; el test cambia `app['env']` como el precedente de `UiAuditSessionCommandTest` |
| A28 | MEDIA | resuelto (matiz → A39) | D24: número fijo de consultas por endpoint; T021 y T050 |
| A29 | MEDIA | resuelto | T029 sustituye las dos aserciones en inglés y exige que no quede ninguna |
| A30 | MEDIA | resuelto (redacción → A40) | Contrato, D23 y T017: `symptoms` opcional; un elemento vacío da 422 |
| A31 | MEDIA | resuelto con efecto nuevo (→ A34) | Cifras fijadas en `UiAuditSeederTest` (T002) y usadas en T026 |
| A32 | ALTA | resuelto con efecto nuevo (→ A37) | D22 y T057; `evaluate.mjs` acepta un HTML local; `.ai/inspect/` está en `.gitignore` y CI no lo publica |
| A33 | BAJA | resuelto | Título "<Mes en curso>" con la desviación de c5 declarada; dos entradas `dashboard-detalle-*` |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A34 | Cobertura de riesgos / Ambigüedad (CA10) | MEDIA | `plan.md` → Datos de prueba de la interfaz; T002 | El seeder no fija el `created_at` de los pacientes, y "pacientes nuevos" filtra por `created_at`. Si el test siembra con el reloj en D16, los pacientes cuentan como nuevos de septiembre; `ui:audit-data` corre con la hora real y los crea en el mes real. El test pasaría y el flujo del mes fallaría | Las cifras fijadas son las que produce `ui:audit-data` corrido con la hora real y leído con la hora de D16. Test: `UiAuditSeederTest` siembra sin reloj fijo y consulta `reports/monthly?month=2026-09` con el reloj en D16 |
| A35 | Cobertura (P2) | MEDIA | T056 "hecho cuando" | Solo se observa `load` con una respuesta vacía. Quedan sin prueba propia antes de T026 varias rutas en un flujo, la secuencia 500 → real y una respuesta retenida desde la carga | Cada clave nueva se prueba con un flujo temporal que falla por la razón esperada: dos rutas respondidas en la carga, 500 y después la respuesta real, y una respuesta retenida desde la carga |
| A36 | Duplicación | BAJA | D21; `plan.md` → Runner; T056 | `hold`, `times` y la espera (`expect.visibleAfter`) ya existen desde la 019 | T056 reutiliza esas claves; lo nuevo es `load` y la lista `answers`. Test: las entradas que ya usan `hold`, `times` o `visibleAfter` dan el mismo resultado |
| A37 | Ambigüedad | BAJA | D22 ("a cada ancho") frente a T057 (un archivo por entrada) | `evaluate` abre un solo archivo y lo redimensiona; no está dicho a qué ancho se pinta la copia | Un archivo por entrada pintado a un ancho fijado (los estilos responden al redimensionar). Test: el "hecho cuando" de T057 nombra el archivo que mide `evaluate` |
| A38 | Cobertura de riesgos (D16) | BAJA | `plan.md` → Core · reloj; T006, T007 | Con `now()` fijado durante la petición, `XSRF-TOKEN` y la cookie de sesión caducan el 17-09-2026 y el navegador las borra. Hoy no rompe ninguna entrada, pero un POST real daría 419 | El reloj fijo no afecta a la caducidad de las cookies. Test en `UiAuditClockTest`: con la cabecera, la `XSRF-TOKEN` de la respuesta caduca después de la hora real |
| A39 | Ambigüedad (Definición de terminado) | BAJA | T050 | Correr solo `DashboardPerformanceTest` con `--parallel` no reproduce la carga de CI | T050 corre la suite completa con `--parallel` dos veces y anota el resultado de `DashboardPerformanceTest` |
| A40 | Redacción | BAJA | `plan.md` → 422 de `POST /tracking`; T047 | "Escribe el síntoma o quítalo." es un texto nuevo de la API, no del diálogo; T047 no dice dónde se marca `symptoms.N` | Texto declarado como nuevo; T047 marca `symptoms.N` bajo el campo de síntomas. Test: T017 fija el texto |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-24:
- **A34** → nota de T002: `UiAuditSeederTest` siembra sin reloj fijo, como `ui:audit-data`, y fija las cifras de `reports/monthly?month=2026-09` leídas con el reloj en D16; si hace falta, el seeder fija las fechas de alta de los pacientes de agosto y septiembre.
- **A35** → nota de T056: el "hecho cuando" prueba cada clave nueva con un flujo temporal que falla por la razón esperada (dos rutas en la carga, 500 y después la respuesta real, respuesta retenida desde la carga).
- **A36** → nota de T056: reutilizar `hold`, `times` y `expect.visibleAfter` existentes; solo `load` y `answers` son nuevos; las entradas que ya los usan dan el mismo resultado.
- **A37** → nota de T057: un archivo por entrada pintado a 1440 px; el "hecho cuando" nombra el archivo que mide `evaluate`.
- **A38** → nota de T006, T007: la caducidad de `XSRF-TOKEN` y de la cookie de sesión no depende del reloj fijo; `UiAuditClockTest` lo comprueba.
- **A39** → nota de T050: suite completa con `--parallel` dos veces, con el resultado de `DashboardPerformanceTest` anotado.
- **A40** → nota de T017, T047: "Escribe el síntoma o quítalo." es texto nuevo de la API; el diálogo marca `symptoms.N` bajo el campo de síntomas.

## Siguiente paso
Aprobar `tasks.md` y seguir con `/implement 020`.
