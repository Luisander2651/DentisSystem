---
result: pass
round: 2
mode: delta
constitution_version: 1.2.1
date: 2026-11-17
spec_sha: 565a174fcc69
plan_sha: af54c3198c20
tasks_sha: 849a2ed2f94d
---

# Análisis · 027 Testimonios enviados desde el sitio público

## Resumen
Delta sobre la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)), hecho sin subagente. Releí desde cero las secciones de spec, plan y tareas que cambiaron. El diff es pequeño y no hay cambio estructural: entra una tarea nueva (T006) y un criterio de abuso nuevo (CA11), que vienen de A1.

Los 16 hallazgos quedan resueltos o aceptados. Sin hallazgos nuevos.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 11 / 11 |
| Amenazas con control y test | 7 / 7 |
| Principios de la constitución evaluados | los tocados por el delta: P6 (excepción aceptada) y P7 (con tarea) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | CA11, TM7, T006; cobertura de RS4.a en la spec |
| A2 | ALTA | resuelto | plan → Abuso y TM5: el test pone la app fuera de `testing`; T002 |
| A3 | ALTA | resuelto | plan → controlador (`public_testimonial.save_failed`, mensaje genérico); T002 lo prueba |
| A4 | MEDIA | aceptado | Constitution Check P6 ❌ aceptado; Contratos |
| A5 | MEDIA | resuelto | plan → `save()` devuelve el id; T001 |
| A6 | MEDIA | aceptado | CA4 (hasta 10 minutos) |
| A7 | MEDIA | resuelto | CA5 y plan: solo cuentan los aceptados; T002 |
| A8 | BAJA | resuelto | plan → Datos (`created_at`); T002 ignora `created_at` |
| A9 | BAJA | resuelto | `TestimonialAuthor` en caracteres; T001 con emoji |
| A10 | BAJA | resuelto | FormRequest normaliza los saltos de línea; T002 |
| A11 | MEDIA | resuelto | TM3 con `escapeHtml` del JS de Contenido; test estático en T005 |
| A12 | BAJA | resuelto | T005, con uno y con varios |
| A13 | BAJA | resuelto | T002, staff con sesión |
| A14 | BAJA | resuelto | T005 actualiza `PublicSiteContentTest` |
| A15 | BAJA | resuelto | Log con `consent: true`; spec → Seguridad |
| A16 | BAJA | resuelto | Contratos y controlador: 429 con la página, sin redirigir |

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Enmiendas propuestas a la constitución
- P6: admitir que un formulario web sin JavaScript responda 302 con los errores en la sesión. No bloquea.

## Aceptados
Aceptados por el usuario el 2026-11-17:
- **A4** → plan: excepción de P6 en el formulario web.
- **A6** → spec: el testimonio publicado aparece en el inicio en cuanto caduca la caché (como mucho 10 min).
