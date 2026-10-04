---
id: 011
slug: sitio-publico
status: approved
confidence: alta
created: 2026-09-22
---

# 011 · Sitio público de la clínica

## Problema
La clínica necesita un sitio público que muestre sus promociones, certificaciones, testimonios y
galería, y la forma de contactarla.

## Historias de usuario
- Como visitante, quiero ver las promociones vigentes, certificaciones y testimonios de la clínica.
- Como visitante, quiero ver la galería y cómo contactar a la clínica.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [ ] CA1 · Dado un visitante, cuando abre `/`, entonces `ShowLandingPageController` renderiza en el servidor las promociones, certificaciones y testimonios con estado `visible`, cacheados 10 minutos.
- [ ] CA2 · Dado un fallo al leer el contenido, cuando se abre `/`, entonces se muestra la portada vacía en lugar de un error.
- [ ] CA3 · Dado un visitante, cuando abre `/galeria`, entonces el JS carga las imágenes desde `GET /api/v1/public/gallery-images`.
- [ ] CA4 · Dado un visitante, cuando abre `/contacto` o `/acerca-de-nosotros`, entonces ve páginas estáticas (enlace a WhatsApp y mapa, sin formulario).
- [ ] CA5 · Dado un visitante, cuando llama `GET /api/v1/public/{certifications,promotions,testimonials}`, entonces obtiene el contenido (sin consumidor en el frontend).
- [ ] CA6 · (abuso) Como visitante, pido contenido marcado como oculto por la API pública → **HOY NO SE CUMPLE**: certificaciones devuelve siempre los ocultos y galería, promociones y testimonios aceptan `?status=oculto` (U5 hallazgo 1).

## Fuera de alcance
- Formulario de contacto; envío de testimonios por pacientes.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno previsto; el contenido oculto no debería ser público.
- Quién puede hacer qué: cualquiera → leer contenido visible.
- Casos de abuso: CA6.

## Requisitos no funcionales
- Rate limiting de la API pública: 10/min por IP.

## Preguntas abiertas
- Ninguna.

## Supuestos
- La intención es mostrar solo contenido `visible` (así lo hace la landing renderizada en el servidor).

## Notas para /plan
- Retomar las 6 preguntas pendientes del plan AI-DLC de la Unidad 5 (`aidlc-docs/construction/plans/unit-5-contentmanagement-functional-design-plan.md`).

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/ContentManagement/Infrastructure/HTTP/Controllers/ShowLandingPageController.php`, `routes/web.php:6-16`, `routes/api.php:74-91`, `resources/views/pages/landing/*`, `resources/js/pages/landing/galeria.js`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- Los listados cambian de forma según el número de resultados: un elemento devuelve un objeto y cero o varios un array (U5 hallazgo 5).
- La caché de la landing no se invalida al publicar (hasta 10 min de retraso) (U5 hallazgo 16).
- Galería usa `visible`/`hidden` y el resto `visible`/`oculto` (U5 hallazgo 6).
- `welcome.blade.php` no se usa.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: 500 genérico | /release |
