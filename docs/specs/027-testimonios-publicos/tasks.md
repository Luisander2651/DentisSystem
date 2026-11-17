---
spec: 027-testimonios-publicos
plan: plan.md
status: draft
---

# Tareas · 027 Testimonios enviados desde el sitio público

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`

## Rebanadas
- [ ] T001 Crear el caso de uso público que guarda una opinión siempre oculta, con la fecha del servidor, y registra `public_testimonial.received` solo con el id — `app/Modules/ContentManagement/Modules/Testimonios/Aplication/DTOs/SubmitPublicTestimonialDTO.php`, `app/Modules/ContentManagement/Modules/Testimonios/Aplication/UseCases/SubmitPublicTestimonialUseCase.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: el test nuevo (fila oculta con la fecha de hoy; el log sin el nombre ni la opinión) pasa y la suite está en verde — cubre: CA2, CA8
- [ ] T002 Recibir el formulario: `FormRequest` con largos, casilla y mensajes en español; controlador con la trampa; `POST /opinion` con el limitador `public-testimonials` (3 cada 10 min por IP) — `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/HTTP/Requests/SubmitPublicTestimonialRequest.php`, `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/HTTP/Controllers/SubmitPublicTestimonialController.php`, `routes/web.php`, `app/Providers/AppServiceProvider.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos de fronteras, entradas inválidas, `status=visible` ignorado, trampa, límite (cuarto envío 429; tras 10 min pasa) y sin CSRF (419) pasan, y la suite está en verde — cubre: CA2, CA3, CA5, CA6, CA8, CA9 — depende: T001
- [ ] T003 Crear la página `/opinion`: formulario con errores por campo asociados y aviso de agradecimiento en la región de estado — `resources/views/site/opinion.blade.php`, `resources/css/site/pages/opinion.css`, `resources/js/site/pages/opinion.js`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos de la página (tres campos, enlace al aviso de privacidad, errores asociados, aviso tras enviar) pasan y la suite está en verde · verificación manual: la página a 390 y 1440 px y recorrido con teclado — cubre: CA1, CA10 — depende: T002
- [ ] T005 `noindex` en `/opinion` y "Dejar mi opinión" del inicio hacia ella en los dos estados; la opinión publicada sale escapada y el listado de staff la devuelve oculta — `resources/views/layouts/site.blade.php`, `resources/views/components/site/seo.blade.php`, `resources/views/site/inicio.blade.php`, `tests/Modules/ContentManagement/Integration/PublicTestimonialSubmissionTest.php` — hecho cuando: los casos (`noindex`, fuera del sitemap, enlace en los dos estados, HTML escapado al publicarse, listado de staff con la opinión oculta) pasan y la suite está en verde — cubre: CA1, CA4, CA7, CA9 — depende: T003
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

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T002 | T002 |
| TM2 | T002 | T002 |
| TM3 | T005 | T005 |
| TM4 | T001 | T001, T002 |
| TM5 | T002 | T002 |
| TM6 | T001 | T001 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Testimonios · Aplication | T001 |
| Testimonios · Infrastructure HTTP | T002 |
| `routes/web.php`, `AppServiceProvider` | T002 |
| `resources/views/site/opinion.blade.php` y CSS/JS | T003 |
| `layouts/site`, `components/site/seo` | T005 |
| `resources/views/site/inicio.blade.php` | T005 |
| `tests/Browser/*` | T004 |

## Constitution Check
| Principio | Resultado | Verificación |
|---|---|---|
| P1–P15 | Igual que el plan | Cada rebanada lleva su test y termina con la suite en verde; P15 con `npm run test:ui` en T004 |
