---
id: 012
slug: gestion-de-contenido
status: approved
confidence: media
created: 2026-09-22
---

# 012 · Gestión del contenido público

## Problema
El administrador necesita publicar y ocultar certificaciones, imágenes de galería, promociones y
testimonios del sitio público sin tocar código.

## Historias de usuario
- Como administrador, quiero crear, editar, ocultar y borrar certificaciones, imágenes, promociones y testimonios desde `/contenido`.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [ ] CA1 · Dado un administrador, cuando usa `POST/PUT/DELETE/GET /api/v1/certifications[/{id}]`, entonces gestiona certificaciones (nombre, descripción, estado, fecha, imagen).
- [ ] CA2 · Dado un administrador, cuando usa `/api/v1/gallery-images`, entonces gestiona imágenes de galería (url, descripción, estado).
- [ ] CA3 · Dado un administrador, cuando usa `/api/v1/promotions`, entonces gestiona promociones (nombre, descripción, estado, porcentaje de descuento, vigencia).
- [ ] CA4 · Dado un administrador, cuando usa `/api/v1/testimonials`, entonces gestiona testimonios (autor, descripción, estado); la interfaz no tiene modal de creación.
- [ ] CA5 · Dada una imagen subida, cuando se guarda, entonces `StorageProvider` exige extensión en lista blanca (jpg, jpeg, png, gif, webp, avif) y MIME `image/*`, la renombra con UUID y la guarda en R2 (disco `s3`).
- [ ] CA6 · (abuso) Como staff no administrador o paciente, intento gestionar contenido → 403 (`only.admin` + `assertCan('manage.*')`).
- [ ] CA7 · (abuso) Como administrador malintencionado o con cuenta comprometida, subo un archivo de más de **5 MB**, con un lado mayor de **2000 px**, o que no es una imagen decodificable → se rechaza con un mensaje que indica el límite → **HOY NO SE CUMPLE**: no hay límite de tamaño ni de dimensiones (solo 64 MB de PHP) y la imagen no se re-codifica (U5 hallazgo 4).
- [x] CA8 · (abuso) Como atacante, provoco un error interno → resuelto por 014 (v0.1.0): 500 genérico (antes: 16 de 17 controladores devuelven `$e->getMessage()` (U5 hallazgo 3)).
- [ ] CA9 · Dada una promoción visible cuya fecha de fin ya pasó (o cuya fecha de inicio aún no llega), cuando se consulta el sitio público, entonces no se muestra; en el panel sigue visible para el administrador → **HOY NO SE CUMPLE**: la landing muestra toda promoción `visible` sin mirar sus fechas.
- [ ] CA10 · Dado un administrador en la pestaña de testimonios de `/contenido`, cuando quiere añadir uno, entonces tiene un formulario de creación → **HOY NO SE CUMPLE**: solo hay edición y borrado en la interfaz.
- [ ] CA11 · Dado un fallo al subir la imagen nueva al reemplazar la de un elemento, cuando ocurre, entonces el elemento conserva su imagen anterior; dado un fallo al borrar un elemento, entonces ni el registro ni la imagen quedan huérfanos → **HOY NO SE CUMPLE**: la imagen anterior se borra antes de subir la nueva y la imagen se borra antes que el registro (U5 hallazgos 9 y 10).

## Fuera de alcance
- Programación de publicación (más allá de ocultar promociones fuera de su vigencia); versiones del contenido.
- Envío de testimonios por los pacientes.

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales de R2; nombres de autores de testimonios.
- Quién puede hacer qué: solo administrador.
- Casos de abuso: CA6, CA7, CA8.

## Requisitos no funcionales
- Imágenes: máximo 5 MB y 2000 px en el lado mayor (CA7).

## Preguntas abiertas
- Ninguna.

## Supuestos
- Ante un fallo al reemplazar o borrar una imagen, el comportamiento deseado es conservar la imagen anterior y no dejar huérfanos (CA11); se tomó sin preguntar por ser la única opción razonable.
- La vigencia de una promoción se evalúa por fecha (sin hora) en la zona horaria de la clínica.

## Notas para /plan
- Era la Unidad 5 de AI-DLC, en diseño funcional con 16 hallazgos y 6 preguntas sin responder. Estas aclaraciones responden la Pregunta 4 de ese plan (límite de imágenes); si se retoma AI-DLC, trasladarla.
- Spec nueva pendiente para CA7, CA9, CA10 y CA11.
- CA9 cambia también lo que muestra el sitio público (spec 011, ya aprobada): la landing y `GET /api/v1/public/promotions` deben filtrar por vigencia. Tratarlo en la misma spec nueva y extender la 011 vía `/specify --edit 011`.
- Considerar re-codificar las imágenes al subirlas (redimensionar a 2000 px en lugar de rechazar es una alternativa a decidir en el plan).

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/ContentManagement/**` (121 archivos PHP, submódulos `Modules/{Certificaciones,Galeria,Promociones,Testimonios}`), `app/Modules/ContentManagement/StorageProvider.php`, `routes/api.php:121-143`, `routes/web.php:75`, `resources/views/pages/contenido/*`, `resources/js/pages/contenido/*`
- Tests: sin tests (no existe `tests/Modules/ContentManagement`)

## Observaciones (solo si status=inferred)
- Sin FormRequest en ningún endpoint (U5 hallazgo 2).
- El borrado elimina la imagen antes que el registro, y `updateImage()` borra la anterior antes de subir la nueva: un fallo deja datos inconsistentes o pierde la imagen (U5 hallazgos 9 y 10).
- Los `Get*UseCase` reciben el servicio de autorización y no lo usan (U5 hallazgo 8).
- Dos clases `CertificationException` distintas; `UserEloquentModel` duplicado en tres submódulos; `CertificationId::fromInt(0)` como marcador (U5 hallazgos 11–13).
- Sin unicidad de nombres (U5 hallazgo 15). Tabla `galery_images` con errata.
- `TestimonialState` (`draft`/`published`/`archived`) es código muerto: la columna `state` se eliminó en la migración `2026_03_10_001542_update_testimonials_table.php`; el único estado real es `visible`/`oculto` (resuelve U5 hallazgo 7).
- Usa `Infrastructure/HTTP` en vez de `Http`, y varios archivos sin `declare(strict_types=1)`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-23 | Aclaraciones: imágenes de 5 MB y 2000 px (CA7), promociones fuera de vigencia ocultas (CA9), crear testimonios desde el panel (CA10); pendientes de spec nueva | /clarify |
| 2026-09-23 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: CA8 resuelto | /release |

## Aclaraciones
### Sesión 2026-09-23
- P: ¿Qué límite deben tener las imágenes que sube el administrador? → R: Hasta 5 MB y 2000 px en el lado mayor.
- P: ¿Una promoción cuya fecha de fin ya pasó debe seguir mostrándose en el sitio público? → R: No, se oculta automáticamente; solo se muestran promociones vigentes.
- P: ¿El administrador debe poder crear testimonios desde el panel? → R: Sí.
