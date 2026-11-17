---
id: 027
slug: testimonios-publicos
status: approved
created: 2026-11-17
extends: [026]
---

# 027 · Testimonios enviados desde el sitio público

## Problema
Hoy, "Dejar mi opinión" del inicio lleva a WhatsApp. El paciente tiene que escribir por chat y el staff tiene que copiar el texto a mano en "Contenido". Muchas opiniones se pierden en ese paso, y la sección de testimonios crece despacio aunque la clínica ya permite gestionar testimonios. Hace falta que el visitante deje su opinión desde el sitio y que el staff solo tenga que revisarla y publicarla.

## Historias de usuario
- Como visitante que ya fue a la clínica, quiero dejar mi opinión desde el sitio, sin escribir por WhatsApp ni crear una cuenta, para contar mi experiencia en un minuto.
- Como staff de la clínica, quiero que cada opinión llegue oculta a "Contenido", para revisarla y publicarla solo si es adecuada.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante en el inicio, cuando pulsa "Dejar mi opinión", entonces llega a la página "Deja tu opinión" (`/opinion`). La página tiene un formulario con su nombre (como quiere aparecer), su opinión y una casilla obligatoria para aceptar que se publiquen su nombre y su opinión, con enlace al aviso de privacidad. El enlace aparece tanto cuando no hay testimonios publicados como cuando los hay. Funciona sin JavaScript. Reemplaza en la 026 el enlace a WhatsApp de "Dejar mi opinión".
- [ ] CA2 · Dados un nombre de 2 a 80 caracteres, una opinión de 20 a 255 caracteres y la casilla marcada, cuando el visitante envía, entonces la opinión se guarda como testimonio **oculto**, con la fecha de envío. El visitante ve un mensaje de agradecimiento que explica que se publicará cuando la clínica la revise, y la opinión no aparece en el inicio.
- [ ] CA3 · Dado un envío con un dato que falta o no cumple su largo, o sin la casilla marcada, cuando se envía, entonces no se guarda nada. El formulario vuelve con un mensaje en español junto a cada campo con error y conserva lo que se había escrito.
- [ ] CA4 · Dada una opinión recién enviada, cuando el staff abre "Contenido" → Testimonios, entonces la ve como oculta y puede publicarla con lo que ya existe. Al publicarla, aparece en el inicio.
- [ ] CA5 · (abuso) Como visitante malintencionado, envío más de 3 opiniones en 10 minutos desde la misma conexión → a partir de la cuarta se rechaza con el mensaje "Enviaste varias opiniones seguidas. Inténtalo de nuevo en unos minutos." y no se guarda.
- [ ] CA6 · (abuso) Como robot de spam, relleno el campo trampa invisible que una persona no ve → la respuesta es la misma que la de un envío correcto, pero no se guarda nada.
- [ ] CA7 · (abuso) Como atacante, escribo código o etiquetas HTML en el nombre o la opinión → se guarda como texto y se muestra como texto, nunca como código, en "Contenido" y en el inicio cuando se publica.
- [ ] CA8 · (abuso) Como atacante, envío en la petición un estado "visible", una fecha u otros campos → se ignoran: la opinión siempre llega oculta y con la fecha del servidor.
- [ ] CA9 · Dado un buscador, cuando lee `/opinion`, entonces la página lleva `noindex` y no aparece en `sitemap.xml`. Un envío sin el token de la sesión (CSRF) se rechaza.
- [ ] CA10 · Dado un visitante que usa teclado o lector de pantalla, cuando recorre `/opinion` a 390, 768 y 1440 px, entonces alcanza cada campo y el botón con foco visible y nombre accesible. Los errores se asocian a su campo y el mensaje de agradecimiento se anuncia.

## Fuera de alcance
- Calificación con estrellas (sigue en el roadmap: requiere cambiar los datos y "Contenido").
- Verificar que quien opina fue paciente; foto o correo del autor.
- Avisar al staff por correo o WhatsApp de cada opinión nueva.
- Testimonios desde el portal del paciente.
- Cambios en "Contenido" → Testimonios (moderación con lo existente).

## Seguridad y privacidad
- **Datos:** el nombre que el visitante elige mostrar y su opinión son datos personales que él mismo publica. La casilla obligatoria recoge su consentimiento para publicarlos (LFPDPPP), y el aviso de privacidad vigente cubre el tratamiento. No se pide teléfono, correo ni datos de salud.
- **Entrada pública sin sesión:** límite de envíos por conexión (CA5), campo trampa (CA6), largos máximos (CA2), texto siempre escapado (CA7), campos de servidor no modificables (CA8) y token CSRF (CA9).
- **Moderación:** nada se publica sin que el staff lo apruebe (CA2, CA4).

## Auditoría
No aplica: no toca autenticación ni permisos, y el registro de auditoría aún no existe (objetivo 5 del roadmap). Se registra un log de cada opinión recibida sin el nombre ni el texto (ver plan).

## Diseño
Página sencilla con el layout del sitio público: el título "Deja tu opinión", una frase de apoyo, el formulario y, al enviar, el mensaje de agradecimiento con un enlace para volver al inicio. Usa los botones, campos y tokens del sistema de diseño (`docs/design/system.md`) sin componentes nuevos. Sin candidatos ni `design.md`: la pantalla reutiliza piezas del sistema y se comprueba con `ui-audit` (decisión del usuario: spec corta).

## Requisitos no funcionales
- **Accesibilidad:** WCAG 2.1 AA, comprobada con `npm run test:ui` a 390, 768 y 1440 px.
- **Descubribilidad:** `/opinion` no se indexa (`noindex`) y queda fuera del sitemap. Es una página de servicio, no de contenido.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | brecha | Quién puede enviar | Cualquier visitante, sin cuenta | usuario |
| 2026-11-17 | brecha | Moderación | Llega oculto; el staff lo publica en "Contenido" | usuario |
| 2026-11-17 | brecha | Campos | Nombre, opinión y casilla de consentimiento con enlace al aviso de privacidad | usuario |
| 2026-11-17 | implícita | Largo de la opinión | 20 a 255 caracteres: la columna actual mide 255 y no se cambian los datos | `app/Modules/ContentManagement/Modules/Testimonios/Infrastructure/Persistence/Eloquent/Migrations/2025_12_25_235501_create_testimonials_table.php` |
| 2026-11-17 | supuesto | Límite de envíos | 3 cada 10 minutos por conexión | — |
| 2026-11-17 | brecha | Flujo | Spec corta: spec, plan y tareas aprobados juntos | usuario |

## Notas para /plan
- Formulario HTML con envío al servidor (sin depender de JavaScript), en una ruta web con CSRF.
- La API de staff (`POST /api/v1/testimonials`) no cambia.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-17 | Spec creada | Pedido del usuario |
