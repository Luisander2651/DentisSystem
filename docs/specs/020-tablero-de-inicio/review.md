---
spec: 020-tablero-de-inicio
verdict: approved
round: 2
date: 2026-10-26
base: 7462312
head: 494034f
human_signoff: Luisander2651 2026-10-26
---

# Review · 020 Tablero de inicio

## Resumen
Aprobada. Los 14 importantes de la ronda 1 ([history/review.r1.md](history/review.r1.md)) están
corregidos (T065–T080) y sus tests fallan si se rompe lo que protegen. La ronda nueva no encuentra
bloqueantes ni importantes. Seis menores nuevos van al roadmap con los veinte de la ronda 1.
Conteo: bloqueante 0 · importante 0 · menor 6

Modo `--rerun` sobre `7462312..494034f` (63 KB de código): un revisor independiente (`opus`) con los
encargos de spec, constitución, calidad y seguridad juntos.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie) | ✅ 1 905 pasan; los 3 de `DashboardScreenTest` (CA11, el "–" de T076) corregidos en T076 y `DashboardScreenTest` 28 pasan |
| `pint --test` (81 archivos PHP de la spec) | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` de `dashboard*`, `expediente*` y `agenda-cita-completar*`, dos veces | ✅ 97 visitas (34 entradas), 0 fallos en cada una |
| `evaluate.mjs` con los dos criterios | ✅ puertas 12/12 · KPIs 5/5 |
| `aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA16 | Los de la ronda 1, más T065, T067, T072–T075, T079 y T080 | ✅ | Los ⚠ de la ronda 1 (CA2, CA7, CA8, CA9, CA12, CA15) quedan cubiertos |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `AppointmentTrackingIntegrationTestCase.php` (sin tarea que lo cite) lleva el helper de T065, anotado en su nota. T079 y T080 se añadieron durante /implement con decisión del usuario.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test antes | ✅ | Cada corrección con su test que fallaba antes (notas de T065–T080) |
| P6 FormRequest | ✅ | `RegisterAppointmentTrackingRequest` con `max` y mensajes en español |
| P7 Errores genéricos | ✅ | 409 en completar simultáneo (T066); 422/400 en lugar de 500 con textos largos (T068–T070) |
| P15 Sistema de diseño | ✅ | Sin valores nuevos fuera de los tokens; `sr-only` para los nombres |
| Resto | sin cambios | Ver [history/review.r1.md](history/review.r1.md) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks `v8.30.1` (`ae818f4..HEAD`) | ✅ sin hallazgos (`.gitleaksignore`, T071) |
| semgrep `p/php` | ✅ 0 hallazgos |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config `docker/` | ✅ 0 (ronda 1; `docker/` no cambia) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Inyección / XSS | ✅ | `nameAction()` y el anuncio del buscador usan `textContent` |
| A06:2025 Diseño inseguro | ✅ | Límites de entrada (T068–T070); carrera de TM5 con test (T065) |
| A10:2025 Condiciones excepcionales | ✅ | 409 y 422/400 en lugar de 500 |
| API4:2023 Consumo sin límite | ✅ | Hasta 20 síntomas y 20 recetas por registro |

## Observabilidad
- Sin cambios respecto a la ronda 1: no hay logs nuevos; P14 aceptado para el objetivo 5.

## Hallazgos
Seguimiento de la ronda 1:

| ID | Estado | Evidencia |
|---|---|---|
| R1 | resuelto | `RegisterAppointmentTrackingTest.php:157` llega al `catch` de `register()` con `missExistingTrackingOnRead()`; sin el catch, 500 |
| R2 | resuelto | `EloquentAppointmentCompletionRepository.php:31` con su catch; `CompleteAppointmentTest.php:287` (409, la cita sigue `asignada`, una fila) |
| R3 | resuelto | `RegisterAppointmentTrackingRequest.php:40`; `Medication` y `Dosage` con `MAX_LENGTH` y `mb_strlen`; 11 casos por encima y uno en el límite |
| R4 | resuelto | `CompletedWithoutTrackingTest.php:106`, orden exacto con desempate por hora |
| R5 | resuelto | `absent` del botón visible en "vacío de cada sección" |
| R6 | resuelto | Oráculo desde las líneas generadas y propiedad de `isPast` |
| R7 | resuelto | Foco al `h2` de la sección; flujo "Reintentar" con `focused` |
| R8 | resuelto | `begin`/`isLatest` en las cuatro lecturas; flujo "carrera de una sección" (T079) |
| R9 | resuelto | "…" mientras carga y "no disponible" si falla; "…" en lugar de "–" por CA11 |
| R10 | resuelto | `nameAction()` con " de {paciente} a las {hora}"; flujo "nombre de cada acción" |
| R11 | resuelto | Anuncio tras 400 ms, uno por búsqueda; `announcedOnce` |
| R12 | resuelto | Flujos de 422 por campo y de registro aceptado |
| R13 | resuelto | Detecta que no se relee la agenda; no distingue relecturas de `without-tracking` o del mes, que responderían lo mismo |
| R14 | resuelto | `.gitleaksignore` con la huella |

Nuevos:

| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R35 | menor | `app/Modules/AppointmentTracking/Infrastructure/Http/Controllers/CompleteAppointmentController.php:29` | Al completar, los textos y el número de síntomas o recetas no tienen tope, y un medicamento o una dosis de 256 caracteres dan 400 con el texto inglés del value object, que el diálogo muestra como fallo genérico; el diálogo no tiene `maxlength` | `maxlength` en el diálogo compartido o las mismas reglas al completar |
| R36 | menor | `tests/Modules/AppointmentTracking/Integration/RegisterAppointmentTrackingValidationTest.php:88` | Los límites solo se prueban con ASCII; nada protege que se cuenten caracteres y no bytes | Un caso con `str_repeat('ñ', 255)` y 256 |
| R37 | menor | `resources/views/pages/dashboard.blade.php:90` | "…" dentro del enlace puede no leerse con la puntuación por defecto de un lector de pantalla | `sr-only` "cargando" mientras vale "…" |
| R38 | menor | `tests/Browser/screens.json:246` | Sin flujo para "vuelve a fallar → foco al nuevo Reintentar" | Flujo con dos 500 seguidos |
| R39 | menor | `resources/js/pages/dashboard.js:544` | Si una carga más nueva sustituye a la del reintento y luego falla, el foco se queda en el título | Mover el foco desde `failed()` cuando está en el título de esa sección |
| R40 | menor | `resources/js/pages/dashboard.js:115` | En las listas de varios días, el nombre accesible no incluye el día | Añadir `day_label` al sufijo |

## Preparación para release
- Rollback factible: sí; `docker/prod/rollback.sh`, sin migraciones.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D6).
- Docs actualizadas: sin cambios desde la ronda 1; `deployment.md` sin cambios.
- `.gitleaksignore` nuevo en la raíz: lo lee el job de gitleaks de CI.

## Tareas añadidas
- Ninguna en esta ronda. Las de la ronda 1 (T065–T078) y las añadidas durante /implement (T079 ← R8, T080 ← límite de la API en la prueba de navegador) están hechas.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-26 (ronda 1, "menores al roadmap"); los de esta ronda siguen la misma decisión:
- **R15** → roadmap: el 404 del registro se distingue del 409 por el texto del mensaje; la rama de id no UUID sin test
- **R16** → roadmap: `AppointmentTrackingException::alreadyExistsForAppointment()` sin usos
- **R17** → roadmap: `PendingTrackingRepositoryInterface` no devuelve `AppointmentPage`
- **R18** → roadmap: `EloquentPendingTrackingRepository` usa `mapToDomain` fuera de la interfaz
- **R19** → roadmap: `now()` evaluado dos veces en el resumen de hoy y en sin cerrar
- **R20** → roadmap: comentarios en línea de la spec 020 en lugar de PHPDoc
- **R21** → roadmap: duplicación en `DashboardNoClinicalDataTest`
- **R22** → roadmap: aserción de tiempo de pared en `DashboardPerformanceTest`
- **R23** → roadmap: `can()` de los recursos del inicio con `app()` por cita
- **R24** → roadmap: "Acciones rápidas" como `<nav>`
- **R25** → roadmap: lista manual de archivos de Domain en los tests de arquitectura
- **R26** → roadmap: 6 px y 40 px de "Siguiente cita" sin token
- **R27** → roadmap: atributos `data-dashboard-role` y `data-dashboard-can-*` sin uso
- **R28** → roadmap: la hora del saludo no se actualiza
- **R29** → roadmap: `paintMonth` sin protección ante datos incompletos; la siguiente de la agenda solo por color a la vista
- **R30** → roadmap: el reloj de la prueba depende solo de `APP_ENV`
- **R31** → roadmap: 404 frente a 403 en el registro distingue cita inexistente de ajena
- **R32** → roadmap: el buscador de expedientes distingue acentos
- **R33** → roadmap: flujos de navegador de XSS, permisos del asistente y mes con datos sembrados
- **R34** → roadmap: sin test de "hoy" cerca de medianoche
- **R35** → roadmap: completar sin los límites del registro y con 400 en inglés para medicamento o dosis largos
- **R36** → roadmap: límites del seguimiento sin caso multibyte
- **R37** → roadmap: "…" del total en carga sin texto para lector de pantalla
- **R38** → roadmap: sin flujo de "vuelve a fallar" tras reintentar
- **R39** → roadmap: foco tras un reintento sustituido por una carga más nueva
- **R40** → roadmap: el nombre accesible de las listas pendientes sin el día
