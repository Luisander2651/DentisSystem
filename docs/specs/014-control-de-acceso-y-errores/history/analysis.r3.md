---
result: fail
round: 3
mode: full
constitution_version: 1.1.2
date: 2026-09-24
spec_sha: f1b32437493e
plan_sha: cfa38cfe0333
tasks_sha: 9413b4ab55b6
---

# Análisis · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Tercer análisis, sobre la spec editada, el plan v3 y las tareas v3. Los anteriores (A1–A29 y B1–B23)
quedaron resueltos con las constituciones 1.1.1 y 1.1.2, ediciones de la spec y las versiones v2 y v3
de plan y tareas.

## Resumen
**fail**, pendiente de la decisión del usuario sobre C1. No hay hallazgos CRÍTICOS. El único ALTO
(C1) es un hueco real de P11: la red global define la respuesta, pero Laravel sigue **reportando**
por su cuenta las excepciones no capturadas, con `getMessage()` (incluidos los bindings del SQL) y
la traza. Se recomienda corregirlo, y un ALTO que se decide corregir deja el resultado en `fail`.
Los demás son ajustes menores. Todos se verificaron contra los artefactos y el código.

Conteo: 0 CRÍTICOS · 1 ALTO · 4 MEDIOS · 9 BAJOS.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 (el test de CA14 no afirma el texto de los mensajes: C5) |
| Amenazas con control y test | 12 / 12 declaradas; 10 / 12 con control en todos sus caminos (TM7 y TM11 sin la red global: C1) |
| Principios de la constitución evaluados | 16 / 16 (P1–P14, Restricciones, Definición de terminado) |

## Hallazgos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Recomendación |
|---|---|---|---|---|---|
| C1 | Seguridad (P11, CA13) | ALTA | plan (bootstrap, D3, TM7, TM11); T033, T015 | La red de `withExceptions` solo cambia el render. El reporte por defecto de Laravel registra `getMessage()` y la traza de toda excepción no capturada (`\Error`/`TypeError` fuera del `catch (\Exception)`, fallos en middleware, controladores sin try/catch). Además, si la red usa el helper, el error queda registrado dos veces. | T033: que no se reporten por defecto las excepciones que atiende la red en `api/*`, de modo que solo quede el log del helper. T015: caso con `Log::spy` y una `QueryException` con datos de prueba sin capturar. Plan: añadir la red a TM7 y TM11. |
| C2 | Dependencias | MEDIA | T052 | "T012 completo" incluye el orden de `agenda/treatments`, que depende de T075. | Añadir T075 a `depende`. |
| C3 | Inconsistencia | MEDIA | T064 vs patrón (paso 1) | El 403 de `AuthorizationException` devuelve `getMessage()` (`DeleteAppointmentTest.php:28` lo afirma), y T064 no lo admite. | Admitir el 403 de `AuthorizationException` en T064. |
| C4 | Contrato (P4, CA1) | MEDIA | plan (`GetTreatmentsController`, Contratos); T047 | Al pasar por `GetTreatmentsUseCase`, `time` nulo (spec 008) deja de ser `null` en `agenda/treatments`: se mapea con el value object, igual que en el catálogo de administración. | Declarar el cambio en el contrato y en la nota a la 008, y añadir a T012 un tratamiento con `time` nulo que fije el resultado. |
| C5 | Cobertura (CA14) | MEDIA | T020–T022; Trazabilidad CA14 | Los tests existentes solo comprueban códigos, no textos: un cambio involuntario del mensaje de negocio no se detectaría. | Añadir a T066 aserciones de texto de mensajes representativos (404 de paciente, 409 de relación 1:1). |
| C6 | Cobertura (RNF P7) | BAJA | T013 | El caso del expediente solo comprueba el log, no el cuerpo genérico. | Añadir la aserción del cuerpo. |
| C7 | Inconsistencia | BAJA | plan, fila TM11 vs Estrategia y T013 | El test de TM11 se describe de dos formas. | Unificar: `RuntimeException` con email de prueba. |
| C8 | Inconsistencia | BAJA | T022; `CreateAppointmentTest.php:20`, `UpdateAppointmentTest.php:29` | Nombres de casos "any authenticated active staff… (not just admin)" que contradicen la spec. | Renombrarlos en T022. |
| C9 | Cobertura (P2) | BAJA | T061 | El caso admin de T019 ya pasa hoy. | Criterio acotado al menú lateral de asistente, doctor y paciente. |
| C10 | Ambigüedad | BAJA | T020, T021 | `actingAsNonAdminUser()` ya usa `'Asistente'` por defecto: el cambio de actor no cambia nada. | Hacer explícito `actingAsNonAdminUser('Asistente')`. |
| C11 | Redacción | BAJA | plan, fila Frontend de expedientes | Son 4 archivos de vista, no 5. | Corregir. |
| C12 | Documentación | BAJA | T065, T091 | `observability.md` (brecha 2) y `security.md` (riesgo 9) citan los `Log::info` que T047 elimina. | Actualizar la brecha 2 y el riesgo 9. |
| C13 | Seguridad | BAJA | plan, Riesgos residuales | Faltan dos residuales: doctor y asistente ven a todos los pacientes, y los logs de Auth y Users siguen con `getMessage()`. | Añadirlos. |
| C14 | Redacción | BAJA | spec, Criterios de aceptación | CA16 aparece antes que CA15. | Reordenar. |

## Aceptados
- Ninguno todavía.
