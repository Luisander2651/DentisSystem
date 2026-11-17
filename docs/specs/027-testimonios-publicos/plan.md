---
spec: 027-testimonios-publicos
status: approved
constitution_version: 1.2.1
created: 2026-11-17
---

# Plan · 027 Testimonios enviados desde el sitio público

## Enfoque técnico
Una página `/opinion` del sitio público con un formulario HTML que se envía con `POST /opinion`, una ruta web con CSRF y sesión. Funciona sin JavaScript, igual que el resto del sitio.

El envío pasa por:
1. un `FormRequest`, con los largos, la casilla y los mensajes en español;
2. un límite de 3 envíos aceptados cada 10 minutos por IP, contado en el controlador con `RateLimiter` después de validar, para que los envíos con errores no gasten intentos;
3. un caso de uso público del submódulo Testimonios, que crea el testimonio siempre oculto con `TestimonialEntity::create()` + `hidden()` y lo guarda con el servicio existente.

El campo trampa se revisa en el controlador. Si viene relleno, la respuesta es la misma que la de un envío correcto y cuenta para el límite, sin llamar al caso de uso. La API pública de testimonios pasa a devolver solo los visibles (CA11). Tras el envío, la respuesta redirige a `/opinion` con un aviso en la sesión. No hay datos nuevos ni migraciones: la columna `description` (255) fija el máximo de la opinión.

## Cambios por módulo
| Módulo / archivo | Cambio | Riesgo |
|---|---|---|
| `app/Modules/ContentManagement/Modules/Testimonios/Aplication/DTOs/SubmitPublicTestimonialDTO.php` (nuevo) | Nombre y opinión; nada más | Bajo |
| `…/Testimonios/Aplication/UseCases/SubmitPublicTestimonialUseCase.php` (nuevo) | Crea el testimonio oculto (la fecha es `created_at`, del servidor), lo guarda y registra `public_testimonial.received` con el id y `consent: true` | Bajo |
| `…/Testimonios/Domain/Repositories/TestimonialRepositoryInterface.php`, `…/Domain/Service/TestimonialsService.php`, `…/Infrastructure/Persistence/Eloquent/…Repository.php` | `save()` devuelve el id de la fila (A5); los demás casos de uso lo ignoran | Bajo |
| `…/Testimonios/Domain/ValueObjects/TestimonialAuthor.php` | El largo se mide en caracteres (`mb_strlen`), no en bytes (A9) | Bajo |
| `…/Testimonios/Infrastructure/HTTP/Controllers/GetPublicTestimonialsController.php` (nuevo) y `routes/api.php` | `GET /api/v1/public/testimonials` lista solo los visibles (CA11, A1) | Bajo |
| `…/Testimonios/Infrastructure/HTTP/Requests/SubmitPublicTestimonialRequest.php` (nuevo) | `name` 2–80, `opinion` 20–255 tras normalizar los saltos de línea a `\n` (A10), `consent` aceptado, `website` (trampa) opcional; mensajes en español | Bajo |
| `…/Testimonios/Infrastructure/HTTP/Controllers/SubmitPublicTestimonialController.php` (nuevo) | Límite (`RateLimiter`, 3 aceptados cada 10 min por IP): pasado, 429 con la página y el mensaje de CA5. Trampa → éxito sin guardar. Si no, caso de uso y redirección con aviso. Un fallo inesperado vuelve al formulario con un mensaje genérico propio y registra `public_testimonial.save_failed` solo con la clase (P7, A3) | Bajo |
| `routes/web.php` | `GET /opinion` (vista) y `POST /opinion`, en el grupo `web` (CSRF) | Bajo |
| `resources/views/site/opinion.blade.php` (nuevo), `resources/css/site/pages/opinion.css` y `resources/js/site/pages/opinion.js` (nuevos) | Página con el layout del sitio, formulario, errores por campo y aviso de agradecimiento | Bajo |
| `resources/views/layouts/site.blade.php`, `resources/views/components/site/seo.blade.php` | `@section('noindex')` pone `<meta name="robots" content="noindex">` | Bajo |
| `resources/views/site/inicio.blade.php` | "Dejar mi opinión" lleva a `/opinion`, también con testimonios publicados | Bajo |
| `tests/Browser/screens.json`, `baseline-1440.json` | Entradas `opinion` y `opinion-errores` de `ui-audit`, con la línea base generada con `ui:audit-data` | Bajo |
| `tests/Modules/ContentManagement/Integration/PublicSiteContentTest.php` | El caso del enlace "Dejar mi opinión" pasa de WhatsApp a `/opinion` (A14) | Bajo |

## Contratos y datos
- **`GET /opinion`:** 200, HTML con `noindex`.
- **`POST /opinion`** (form-urlencoded, con `_token`):
  - válido → 302 a `/opinion` con `opinion_sent` en la sesión;
  - inválido → 302 de vuelta con los errores y lo escrito (excepción de P6 para formularios web, aceptada por el usuario: A4);
  - más de 3 envíos aceptados en 10 min → 429 con la página y el mensaje de CA5, sin redirigir;
  - fallo inesperado → 302 de vuelta con el mensaje genérico propio;
  - sin CSRF → 419.
- **`GET /api/v1/public/testimonials`:** solo testimonios visibles. El listado de staff (`/api/v1/testimonials`) no cambia.
- **Datos:** sin cambios de esquema. `testimonials` recibe `author`, `description`, `status = oculto` y `created_at` del servidor (la columna `date` ya no existe).
- **API:** sin cambios. El staff sigue usando `/api/v1/testimonials`.

## Estrategia de pruebas
Integración en `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php`.

**Fronteras:**
- **Nombre:** 1 carácter falla y 2 pasa; 80 pasa y 81 falla.
- **Opinión:** 19 falla y 20 pasa; 255 pasa y 256 falla.
- **Límite de envíos:** el cuarto envío aceptado dentro de los 10 minutos falla (429), y otro envío tras `travel(11)->minutes()` pasa; 3 envíos inválidos seguidos de uno válido pasan.
- **Largo en caracteres:** un nombre de 80 caracteres de 4 bytes (emoji) se guarda; una opinión de 255 caracteres con saltos de línea pasa.

**Entradas inválidas:**
- campos ausentes, vacíos, de tipo arreglo o solo espacios;
- casilla sin marcar;
- `status=visible` y `date` en la petición (se ignoran).

**Abuso:**
- trampa rellena: éxito sin fila;
- HTML en los campos: escapado en el HTML del inicio, y el JS de "Contenido" pasa el nombre y la opinión por `escapeHtml` (test estático);
- sin CSRF: el test pone la app en un entorno distinto de `testing`, porque Pest se salta la comprobación de CSRF en `testing` (A2), y espera 419;
- API pública: una opinión enviada no aparece en `GET /api/v1/public/testimonials`.
- **P7:** un fallo forzado del guardado devuelve el mensaje genérico, sin detalle interno.

**Por rol:** es pública. Un staff con sesión que envía crea igual un testimonio oculto, sin permisos especiales. El listado de "Contenido" (existente) lo devuelve como oculto, con uno y con varios testimonios (A12).

**Interfaz:** `ui-audit` en `opinion` a 390, 768 y 1440 px (foco, nombres y contraste), más un estado con errores.

## Modelo de amenazas
| ID | Amenaza (STRIDE / OWASP 2025) | Control | Test |
|---|---|---|---|
| TM1 | Denegación de servicio y spam por volumen (A04/A06) | Límite de 3 envíos aceptados cada 10 min por IP | límite: el cuarto envío, 429, sin fila nueva |
| TM2 | Spam automatizado (A04) | Campo trampa: éxito aparente sin guardar | trampa rellena → sin fila |
| TM3 | Inyección / XSS almacenado (A05) | Salida escapada por Blade en el inicio y por `escapeHtml` del JS de "Contenido" (el JSON no escapa) | HTML en los campos sale escapado en el inicio; test estático del JS de Contenido |
| TM4 | Manipulación del estado (elevación: publicarse solo) (A01) | El caso de uso no lee estado ni fecha de la petición; siempre `hidden()` | `status=visible` → oculto |
| TM5 | CSRF (A01) | Ruta del grupo `web` con `ValidateCsrfToken` | sin token → 419, con la app fuera del entorno `testing` |
| TM6 | Datos personales en logs (A09) | Log solo con el id del testimonio y el consentimiento | el log no contiene el nombre ni la opinión |
| TM7 | Divulgación de contenido sin moderar (A01) | La API pública lista solo los visibles | una opinión enviada no aparece en la API pública |

## Observabilidad
- **Logs nuevos:** `public_testimonial.received` (`info`), con `testimonial_id` y `consent: true`; `public_testimonial.save_failed` (`error`), con la clase de la excepción. Sin nombre, opinión ni IP. No lleva `request_id`: es la desviación aceptada de P14 hasta el objetivo 5.
- **Auditoría:** ninguna (no aplica: ver la spec).
- **Tras el deploy:** un envío de prueba aparece oculto en "Contenido"; el staff lo borra.

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/opinion` | no (`noindex`) | "Deja tu opinión - Dentissa" | propia | sí | no | — | `PublicTestimonialSubmissionTest` |

## Rollout
- **Feature flag:** no. Es una página nueva y el enlace del inicio cambia de destino. Si hay que revertir, `rollback.sh` vuelve a v0.11.0, y no hay datos nuevos que deshacer.
- **Orden:** un solo deploy, sin migraciones.
- **Métricas:** 5xx en `/opinion` y testimonios ocultos nuevos en "Contenido".

## Decisiones
| Decisión | Motivo |
|---|---|
| Ruta web con formulario HTML, no la API | Funciona sin JavaScript, con CSRF y errores por campo con la sesión, como el resto del sitio; la API de staff no cambia |
| Caso de uso público sin `assertCan` (excepción de P3/P5) | La acción es pública por diseño; la protección es el limitador, la trampa y la moderación |
| Sin migración: opinión ≤ 255 | La columna actual; ampliar el largo queda para la spec de estrellas |

## Riesgos y mitigaciones
- **Spam desde muchas IP:** la moderación evita que se publique. Si llega mucho, la siguiente medida es un desafío (fuera de alcance).
- **Opiniones legítimas largas:** 255 caracteres alcanzan para una opinión breve. El contador del campo lo hace visible.

## Constitution Check
| Principio | Resultado | Cómo se verifica |
|---|---|---|
| P1 Spec antes que código | ✅ | `manual: revisión en /review` |
| P2 Test que falla antes | ✅ | `test: PublicTestimonialSubmissionTest` |
| P3 Capas del módulo | ✅ controlador → caso de uso → servicio → repositorio (sin `assertCan`: acción pública, ver Decisiones) | `manual: revisión en /review` |
| P4 Contrato de API primero | ➖ | Sin endpoints de API nuevos; ruta web con contrato en este plan |
| P5 Autorización en el servidor | ✅ | Pública por diseño; nada se publica sin el staff (`test: status ignorado → oculto`) |
| P6 FormRequest | ❌ aceptado: el formulario web responde 302 con los errores en la sesión, no 422 — aprobado por el usuario el 2026-11-17 (A4) | `test: casos de validación` |
| P7 Errores sin detalles internos | ✅ | `test: un fallo forzado del guardado responde con el mensaje genérico (T002)` |
| P8 Secretos | ➖ | Sin secretos nuevos |
| P9 Migraciones | ➖ | Sin migraciones |
| P10 Dependencias | ➖ | Sin dependencias nuevas |
| P11 Datos sensibles y amenazas | ✅ | TM1–TM6 con su test |
| P12 Producción | ✅ | `manual: /release` |
| P13 Lógica en el backend | ✅ | Estado y fecha los fija el caso de uso (`test`) |
| P14 Trazabilidad | ❌ aceptado: sin `request_id` ni auditoría hasta el objetivo 5, como en specs anteriores — aprobado por el usuario el 2026-11-17 al aprobar este plan | — |
| P15 Sistema de diseño | ✅ | `test: SiteStylesTokensTest`, `OnlyDesignTokensTest` y `npm run test:ui` |

## Trazabilidad
| Criterio | Cambio(s) | Test(s) |
|---|---|---|
| CA1 | vista, rutas, inicio | integración · `PublicTestimonialSubmissionTest` · enlace del inicio en los dos estados; formulario con sus tres campos y el enlace al aviso |
| CA2 | caso de uso, controlador | integración · envío válido → fila oculta con la fecha de hoy, aviso visible, ausente del inicio |
| CA3 | FormRequest | integración · fronteras y entradas inválidas → sin fila, errores por campo y lo escrito conservado |
| CA4 | — (existente) | integración · el listado de staff devuelve la opinión como oculta (con uno y con varios); al publicarla, aparece en el inicio cuando caduca la caché |
| CA5 | controlador | integración · el cuarto envío aceptado → 429 y mensaje, sin fila; pasados 10 min, pasa; 3 inválidos no cuentan |
| CA6 | controlador | integración · trampa rellena → mismo aviso, sin fila |
| CA7 | vistas (escape) | integración · `<script>` en los campos sale escapado en el inicio publicado; unidad · el JS de Contenido escapa nombre y opinión |
| CA8 | caso de uso | integración · `status=visible` y `date` ignorados |
| CA9 | layout, rutas | integración · `noindex` en `/opinion`, ausente del sitemap; sin CSRF → 419 (fuera de `testing`) |
| CA11 | API pública | integración · una opinión enviada y otra oculta no aparecen en `GET /api/v1/public/testimonials`; una visible sí |
| CA10 | vista | `ui-audit` · `opinion` y `opinion-errores` a 390, 768 y 1440 px · verificación manual: recorrido con teclado |
| TM1 | controlador | integración · el cuarto envío → 429, sin fila |
| TM7 | API pública | integración · la opinión enviada no aparece en la API pública |
| TM2 | controlador | integración · trampa rellena → sin fila |
| TM3 | vistas, JS de Contenido | integración · HTML escapado en el inicio; unidad · `escapeHtml` en el JS de Contenido |
| TM4 | caso de uso | integración · `status=visible` → oculto |
| TM5 | rutas web | integración · sin CSRF → 419 |
| TM6 | caso de uso | integración · el log no contiene el nombre ni la opinión |
