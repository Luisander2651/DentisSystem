---
spec: 027-testimonios-publicos
plan: plan.md
status: approved
impl_base: 01704a6
---

# Tareas · 027 Testimonios enviados desde el sitio público

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`

## Rebanadas
- [x] T001 Crear el caso de uso público que guarda una opinión siempre oculta (`created_at` del servidor) y registra `public_testimonial.received` con el id real y `consent: true`; `save()` del repositorio y del servicio devuelve el id; el autor se mide en caracteres — `app/Modules/ContentManagement/Modules/Testimonios/Aplication/UseCases/SubmitPublicTestimonialUseCase.php` (con su DTO), `app/Modules/ContentManagement/Modules/Testimonios/Domain/Service/TestimonialsService.php` (con la interfaz y el repositorio Eloquent), `app/Modules/ContentManagement/Modules/Testimonios/Domain/ValueObjects/TestimonialAuthor.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: el test nuevo (fila oculta con la hora del servidor; el log con el id de la fila y sin el nombre ni la opinión; un nombre de 80 emoji se guarda) pasa y la suite está en verde — cubre: CA2, CA8
  - nota: los dos casos fallaron antes (sin las clases) y pasan después. Comprobado rompiendo: sin `hidden()` y con el nombre en el log falla un caso cada vez. El DTO va junto al caso de uso; `save()` devuelve el id en la interfaz, el servicio y el repositorio Eloquent (los demás casos de uso lo ignoran). `TestimonialAuthor` mide en caracteres (A9).
- [x] T002 Recibir el formulario: `FormRequest` con largos (saltos de línea normalizados), casilla y mensajes en español; controlador con el límite de 3 envíos aceptados cada 10 min por IP, la trampa y el fallo genérico (P7); `POST /opinion` en el grupo `web` — `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/HTTP/Requests/SubmitPublicTestimonialRequest.php`, `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/HTTP/Controllers/SubmitPublicTestimonialController.php`, `routes/web.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos de fronteras, entradas inválidas, opinión de 255 con saltos de línea, `status=visible` y `created_at` ignorados, staff con sesión (queda oculto), trampa, límite (cuarto aceptado 429; 3 inválidos no cuentan; tras 10 min pasa), fallo forzado con mensaje genérico y sin CSRF (419, fuera de `testing`) pasan, y la suite está en verde — cubre: CA2, CA3, CA5, CA6, CA8, CA9 — depende: T001
  - nota: 24 casos nuevos fallaron antes (sin ruta) y pasan después. Comprobado rompiendo: sin la trampa, sin el límite, sin normalizar los saltos de línea y con `/opinion` excluida del token CSRF, falla su caso cada vez. El fallo de P7 se fuerza simulando el repositorio (el caso de uso es `final`). Vista mínima `site/opinion.blade.php` para la respuesta 429; la página completa es T003. Sin `AppServiceProvider`: el límite vive en el controlador para contar solo los envíos aceptados (A7).
- [x] T003 Crear la página `/opinion`: formulario con errores por campo asociados y aviso de agradecimiento en la región de estado — `resources/views/site/opinion.blade.php`, `resources/css/site/pages/opinion.css`, `resources/js/site/pages/opinion.js`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos de la página (tres campos, enlace al aviso de privacidad, errores asociados, aviso tras enviar) pasan y la suite está en verde · verificación manual: la página a 390 y 1440 px y recorrido con teclado — cubre: CA1, CA10 — depende: T002
  - nota: los 4 casos nuevos fallaron antes (vista mínima) y pasan después. Al construir la página apareció un defecto de orden: tras tres envíos, el agradecimiento del anterior seguía en la sesión y tapaba el aviso del límite; el aviso del límite va primero. La casilla se mide en `em` (la zona de toque de 44 px es su etiqueta, con el token). Verificación manual 2026-11-17 (agente, Chrome): a 390 y 1440 px la página se ve con el layout del sitio y los errores junto a cada campo; con teclado, Tab recorre nombre, opinión, casilla, enlace al aviso y botón, sin pasar por la trampa, y tras enviar el foco va a "¡Gracias por tu opinión!".
- [ ] T005 `noindex` en `/opinion` y "Dejar mi opinión" del inicio hacia ella en los dos estados; la opinión publicada sale escapada y el listado de staff la devuelve oculta — `resources/views/layouts/site.blade.php`, `resources/views/components/site/seo.blade.php`, `resources/views/site/inicio.blade.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos (`noindex`, fuera del sitemap, enlace en los dos estados, HTML escapado al publicarse, listado de staff con la opinión oculta con uno y con varios, el JS de Contenido escapa nombre y opinión) pasan, el caso de `PublicSiteContentTest` del enlace queda en `/opinion` y la suite está en verde — cubre: CA1, CA4, CA7, CA9 — depende: T003
- [x] T006 La API pública de testimonios lista solo los visibles — `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/HTTP/Controllers/GetPublicTestimonialsController.php`, `routes/api.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: el caso nuevo (una opinión enviada y otra oculta no aparecen; una visible sí; el listado de staff no cambia) pasa y la suite está en verde — cubre: CA11 — depende: T002
  - nota: el caso público falló antes (la API devolvía "Oculto" y "Recién enviada", también con `?status=oculto`) y pasa después; el del staff ya pasaba (el listado del staff no cambia) y queda fijado, con uno y con varios testimonios (A12). `GetPublicTestimonialsController` ignora el estado de la consulta y conserva la forma de la respuesta.
- [ ] T004 Añadir a `ui-audit` las entradas `opinion` y `opinion-errores` con su línea base, generada con los datos de `ui:audit-data` — `tests/Browser/screens.json`, `tests/Browser/baseline-1440.json` — hecho cuando: `npm run test:ui -- --only opinion` pasa dos veces seguidas en los tres anchos — cubre: CA10 — depende: T005

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` (ruta `/opinion` y caso de uso público de Testimonios) y `docs/seo.md` (`/opinion` en el inventario, con `noindex`)
- [ ] T091 Actualizar `docs/security.md` (entrada pública nueva: límite, trampa y moderación) y `docs/observability.md` (log `public_testimonial.received`)
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T003, T005 | T003, T005 |
| CA2 | T001, T002 | T001, T002 |
| CA3 | T002 | T002 |
| CA4 | T005 | T005 |
| CA5 | T002 | T002 |
| CA6 | T002 | T002 |
| CA7 | T005 | T005 |
| CA8 | T001, T002 | T001, T002 |
| CA9 | T002, T005 | T002, T005 |
| CA10 | T003, T004 | T003, T004 |
| CA11 | T006 | T006 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T002 | T002 |
| TM2 | T002 | T002 |
| TM3 | T005 | T005 |
| TM4 | T001 | T001, T002 |
| TM5 | T002 | T002 |
| TM6 | T001 | T001 |
| TM7 | T006 | T006 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Testimonios · Aplication | T001 |
| Testimonios · Infrastructure HTTP | T002 |
| `routes/web.php` | T002 |
| Testimonios · Domain (repositorio, servicio, `TestimonialAuthor`) | T001 |
| API pública de testimonios (`routes/api.php`) | T006 |
| `resources/views/site/opinion.blade.php` y CSS/JS | T003 |
| `layouts/site`, `components/site/seo` | T005 |
| `resources/views/site/inicio.blade.php` | T005 |
| `tests/Browser/*` | T004 |

## Constitution Check
| Principio | Resultado | Verificación |
|---|---|---|
| P1–P15 | Igual que el plan | Cada rebanada lleva su test y termina con la suite en verde; P15 con `npm run test:ui` en T004 |
