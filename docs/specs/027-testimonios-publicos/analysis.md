---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-11-17
spec_sha: 982879f00406
plan_sha: f15b4ba7ded9
tasks_sha: 91b10d2336a6
---

# Análisis · 027 Testimonios enviados desde el sitio público

## Resumen
Lo hizo un subagente de contexto limpio, que leyó la spec, el plan, las tareas, la constitución y el código de Testimonios. Todos los hallazgos se verificaron en el código.

El resultado es `fail` por tres hallazgos altos:
- la API pública ya devuelve los testimonios ocultos (RS4.a), lo que contradice la moderación de la spec;
- el test de CSRF tal como estaba planteado no prueba nada en Pest;
- la verificación de P7 no tenía tarea.

Todos se corrigen con `/plan` y `/tasks --fix`. Las decisiones del usuario están abajo.

Conteo: CRÍTICA 0 · ALTA 3 · MEDIA 5 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con rebanada (test e implementación) | 10 / 10 |
| Amenazas con control y test | 4 / 6 (TM3 con un control inexistente; TM5 con un test que no prueba) |
| Principios de la constitución evaluados | 15 / 15 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Seguridad | ALTA | `routes/api.php:95-97`, `GetTestimonialsUseCase`; plan → Modelo de amenazas | `GET /api/v1/public/testimonials` devuelve también los ocultos (RS4.a): el spam sin moderar sería legible al instante | `/specify --edit`, `/plan` y `/tasks --fix`: la API pública devuelve solo los visibles (decisión del usuario). Test: una opinión enviada no aparece en esa respuesta |
| A2 | Pruebas | ALTA | plan TM5, CA9 | Pest se salta `ValidateCsrfToken` mientras corre los tests; un POST sin `_token` no da 419 | Test con el entorno fuera de `testing`, que falla si la ruta sale del grupo `web` o se excluye del token |
| A3 | Constitución P7 | ALTA | plan → Constitution Check P7; tareas | El test de P7 no estaba en ninguna tarea y el 500 genérico de `bootstrap/app.php` solo cubre `api/*` | T002: si el guardado falla, la página vuelve con un mensaje genérico propio y se registra solo la clase de la excepción; un test fuerza el fallo |
| A4 | Constitución P6 | MEDIA | plan → Contratos | P6 pide 422 ante una entrada inválida; el formulario web responde 302 con los errores | Excepción de P6 para formularios web aceptada por el usuario |
| A5 | Inconsistencia | MEDIA | plan (log con el id); `TestimonialsService::save(): void` | El caso de uso no conoce el id de la fila creada | `save()` del repositorio y del servicio devuelve el id; test: el `testimonial_id` del log es el de la fila |
| A6 | Inconsistencia | MEDIA | CA4; caché de 10 min de `ShowLandingPageController` | Al publicar, el inicio la muestra cuando caduca la caché | Aceptado por el usuario: hasta 10 min, como el resto del contenido; CA4 lo dice |
| A7 | Ambigüedad | MEDIA | CA5 | El límite contaría también los envíos inválidos | Decisión del usuario: solo cuentan los envíos aceptados (válidos o con la trampa); test: 3 inválidos y luego uno válido pasa |
| A8 | Inconsistencia | BAJA | plan → Datos (`date`) | La columna `date` ya no existe; la fecha es `created_at` | El plan habla de `created_at`; test con `created_at` en la petición |
| A9 | Inconsistencia | BAJA | `TestimonialAuthor` (`strlen` en bytes) | 80 caracteres de 4 bytes darían un 500 | El valor se mide en caracteres (`mb_strlen`); test con emoji |
| A10 | Ambigüedad | BAJA | CA2, opinión de 255 | Los saltos de línea llegan como CRLF (2 caracteres) | El servidor normaliza a `\n` antes de validar; test con saltos de línea |
| A11 | Pruebas | MEDIA | TM3 | El recurso JSON no escapa; quien escapa en "Contenido" es `escapeHtml` del JS de la pantalla | TM3 con el control real; test: el JS de Contenido escapa el nombre y la opinión |
| A12 | Pruebas | BAJA | `GetTestimonialsController` | Con un solo resultado devuelve un objeto, no una lista | El test de CA4 cubre uno y varios testimonios |
| A13 | Cobertura | BAJA | plan → Por rol | El caso "staff con sesión envía y queda oculto" no tenía tarea | En T002 |
| A14 | Cobertura | BAJA | `PublicSiteContentTest.php:126` | El test existente fija el enlace a WhatsApp | T005 lo actualiza a `/opinion` |
| A15 | Seguridad | BAJA | Spec → privacidad | El consentimiento no dejaba constancia | Decisión del usuario: el log de recepción registra el consentimiento con el id |
| A16 | Inconsistencia | BAJA | plan → `AppServiceProvider` | "Vuelve a /opinion" contra "429 con la vista" | El cuarto envío responde 429 con la página y el mensaje, sin redirigir |

## Decisiones pendientes del usuario
Ninguna: resueltas el 2026-11-17 (A1, A4, A6, A7 y A15).

## Enmiendas propuestas a la constitución
- P6: admitir que un formulario web sin JavaScript responda 302 con los errores en la sesión. No bloquea; la decide el usuario.

## Aceptados
Aceptados por el usuario el 2026-11-17:
- **A4** → plan: excepción de P6 en el formulario web.
- **A6** → spec: el testimonio publicado aparece en el inicio en cuanto caduca la caché (como mucho 10 min).
