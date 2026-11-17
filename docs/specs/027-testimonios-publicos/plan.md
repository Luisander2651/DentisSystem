---
spec: 027-testimonios-publicos
status: draft
constitution_version: 1.2.1
created: 2026-11-17
---

# Plan · 027 Testimonios enviados desde el sitio público

## Enfoque técnico
Una página `/opinion` del sitio público con un formulario HTML que se envía con `POST /opinion`, una ruta web con CSRF y sesión. Funciona sin JavaScript, igual que el resto del sitio.

El envío pasa por:
1. un `FormRequest`, con los largos, la casilla y los mensajes en español;
2. un limitador propio, de 3 envíos cada 10 minutos por IP;
3. un caso de uso público del submódulo Testimonios, que crea el testimonio siempre oculto con `TestimonialEntity::create()` + `hidden()` y lo guarda con el servicio existente.

El campo trampa se revisa en el controlador. Si viene relleno, la respuesta es la misma que la de un envío correcto, sin llamar al caso de uso. Tras el envío, la respuesta redirige a `/opinion` con un aviso en la sesión. No hay datos nuevos ni migraciones: la columna `description` (255) fija el máximo de la opinión.

## Cambios por módulo
| Módulo / archivo | Cambio | Riesgo |
|---|---|---|
| `app/Modules/ContentManagement/Modules/Testimonios/Aplication/DTOs/SubmitPublicTestimonialDTO.php` (nuevo) | Nombre y opinión; nada más | Bajo |
| `…/Testimonios/Aplication/UseCases/SubmitPublicTestimonialUseCase.php` (nuevo) | Crea el testimonio oculto con la fecha del servidor y lo guarda; registra `public_testimonial.received` con el id | Bajo |
| `…/Testimonios/Infrastructure/HTTP/Requests/SubmitPublicTestimonialRequest.php` (nuevo) | `name` 2–80, `opinion` 20–255, `consent` aceptado, `website` (trampa) opcional; mensajes en español | Bajo |
| `…/Testimonios/Infrastructure/HTTP/Controllers/SubmitPublicTestimonialController.php` (nuevo) | Trampa → éxito sin guardar; si no, caso de uso y redirección con aviso | Bajo |
| `routes/web.php` | `GET /opinion` (vista) y `POST /opinion` con `throttle:public-testimonials` | Bajo |
| `app/Providers/AppServiceProvider.php` | Limitador `public-testimonials`: 3 cada 10 min por IP; al pasarse, vuelve a `/opinion` con el error de CA5 y código 429 | Bajo |
| `resources/views/site/opinion.blade.php` (nuevo), `resources/css/site/pages/opinion.css` y `resources/js/site/pages/opinion.js` (nuevos) | Página con el layout del sitio, formulario, errores por campo y aviso de agradecimiento | Bajo |
| `resources/views/layouts/site.blade.php`, `resources/views/components/site/seo.blade.php` | `@section('noindex')` pone `<meta name="robots" content="noindex">` | Bajo |
| `resources/views/site/inicio.blade.php` | "Dejar mi opinión" lleva a `/opinion`, también con testimonios publicados | Bajo |
| `tests/Browser/screens.json`, `baseline-1440.json` | Entradas `opinion` y `opinion-errores` de `ui-audit` | Bajo |

## Contratos y datos
- **`GET /opinion`:** 200, HTML con `noindex`.
- **`POST /opinion`** (form-urlencoded, con `_token`):
  - válido → 302 a `/opinion` con `opinion_sent` en la sesión;
  - inválido → 302 de vuelta con los errores y lo escrito;
  - más de 3 envíos en 10 min → 429 con la vista y el error de CA5;
  - sin CSRF → 419.
- **Datos:** sin cambios. `testimonials` recibe `author`, `description`, `status = oculto` y `date` del servidor.
- **API:** sin cambios. El staff sigue usando `/api/v1/testimonials`.

## Estrategia de pruebas
Integración en `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php`.

**Fronteras:**
- **Nombre:** 1 carácter falla y 2 pasa; 80 pasa y 81 falla.
- **Opinión:** 19 falla y 20 pasa; 255 pasa y 256 falla.
- **Límite de envíos:** el cuarto envío dentro de los 10 minutos falla, y otro envío tras `travel(11)->minutes()` pasa.

**Entradas inválidas:**
- campos ausentes, vacíos, de tipo arreglo o solo espacios;
- casilla sin marcar;
- `status=visible` y `date` en la petición (se ignoran).

**Abuso:** trampa rellena (éxito sin fila), HTML en los campos (texto escapado en el HTML del inicio y en el recurso de "Contenido") y envío sin CSRF.

**Por rol:** es pública. Un staff con sesión que envía crea igual un testimonio oculto, sin permisos especiales. El listado de "Contenido" (existente) lo devuelve como oculto.

**Interfaz:** `ui-audit` en `opinion` a 390, 768 y 1440 px (foco, nombres y contraste), más un estado con errores.

## Modelo de amenazas
| ID | Amenaza (STRIDE / OWASP 2025) | Control | Test |
|---|---|---|---|
| TM1 | Denegación de servicio y spam por volumen (A04/A06) | Limitador de 3 cada 10 min por IP | límite: el cuarto envío, 429, sin fila nueva |
| TM2 | Spam automatizado (A04) | Campo trampa: éxito aparente sin guardar | trampa rellena → sin fila |
| TM3 | Inyección / XSS almacenado (A05) | Validación de largos; salida siempre escapada por Blade y por los recursos JSON | HTML en los campos sale escapado en el inicio |
| TM4 | Manipulación del estado (elevación: publicarse solo) (A01) | El caso de uso no lee estado ni fecha de la petición; siempre `hidden()` | `status=visible` → oculto |
| TM5 | CSRF (A01) | Ruta del grupo `web` con `VerifyCsrfToken` | sin token → 419 |
| TM6 | Datos personales en logs (A09) | Log solo con el id del testimonio | el log no contiene el nombre ni la opinión |

## Observabilidad
- **Log nuevo:** `public_testimonial.received` (`info`), con `testimonial_id`. Sin nombre, opinión ni IP. No lleva `request_id`: es la desviación aceptada de P14 hasta el objetivo 5.
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
| P6 FormRequest | ✅ | `test: casos de validación` |
| P7 Errores sin detalles internos | ✅ | `test: un fallo del guardado responde con el mensaje genérico` |
| P8 Secretos | ➖ | Sin secretos nuevos |
| P9 Migraciones | ➖ | Sin migraciones |
| P10 Dependencias | ➖ | Sin dependencias nuevas |
| P11 Datos sensibles y amenazas | ✅ | TM1–TM6 con su test |
| P12 Producción | ✅ | `manual: /release` |
| P13 Lógica en el backend | ✅ | Estado y fecha los fija el caso de uso (`test`) |
| P14 Trazabilidad | ❌ aceptado: sin `request_id` ni auditoría hasta el objetivo 5, como en specs anteriores — aprobado por el usuario al aprobar este plan | — |
| P15 Sistema de diseño | ✅ | `test: SiteStylesTokensTest`, `OnlyDesignTokensTest` y `npm run test:ui` |

## Trazabilidad
| Criterio | Cambio(s) | Test(s) |
|---|---|---|
| CA1 | vista, rutas, inicio | integración · `PublicTestimonialSubmissionTest` · enlace del inicio en los dos estados; formulario con sus tres campos y el enlace al aviso |
| CA2 | caso de uso, controlador | integración · envío válido → fila oculta con la fecha de hoy, aviso visible, ausente del inicio |
| CA3 | FormRequest | integración · fronteras y entradas inválidas → sin fila, errores por campo y lo escrito conservado |
| CA4 | — (existente) | integración · el listado de staff devuelve la opinión como oculta; al publicarla aparece en el inicio |
| CA5 | limitador | integración · el cuarto envío → 429 y mensaje, sin fila; pasados 10 min, pasa |
| CA6 | controlador | integración · trampa rellena → mismo aviso, sin fila |
| CA7 | vistas (escape) | integración · `<script>` en los campos sale escapado en el inicio publicado y en el recurso |
| CA8 | caso de uso | integración · `status=visible` y `date` ignorados |
| CA9 | layout, rutas | integración · `noindex` en `/opinion`, ausente del sitemap; sin CSRF → 419 |
| CA10 | vista | `ui-audit` · `opinion` y `opinion-errores` a 390, 768 y 1440 px · verificación manual: recorrido con teclado |
| TM1 | limitador | integración · el cuarto envío → 429, sin fila |
| TM2 | controlador | integración · trampa rellena → sin fila |
| TM3 | vistas | integración · HTML escapado en el inicio y en el recurso |
| TM4 | caso de uso | integración · `status=visible` → oculto |
| TM5 | rutas web | integración · sin CSRF → 419 |
| TM6 | caso de uso | integración · el log no contiene el nombre ni la opinión |
