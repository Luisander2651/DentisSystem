---
spec: 016-sistema-de-diseno-aplicado
status: approved
constitution_version: 1.1.2
created: 2026-10-06
---

# Plan · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

Versión 3 (2026-10-07), corregida con `--fix` tras el `/analyze` ronda 2 (A32–A56) y la reaprobación
de la spec con CA40 y CA41. La versión 2 corrigió la ronda 1 (A1–A31). Cada corrección cita su
hallazgo.

## Enfoque técnico
Los colores, radios y tamaños de control se declaran una sola vez como tokens en `@theme` de
`resources/css/app.css`, con los valores de la variante **B · Neutra** de
[design/propuesta.html](design/propuesta.html), elegida por el usuario. El mismo bloque **retira la
paleta por defecto de Tailwind** (`--color-*: initial`), de modo que una utilidad como
`text-slate-400` deja de existir y solo se puede pintar con tokens. Las 60 vistas y los 37 archivos
JS se migran por grupos de pantallas a esos tokens y a los componentes Blade compartidos:
`x-ui.button`, `x-ui.input`, `x-ui.brand` (nuevo), `x-ui.dialog` (nuevo, sobre el elemento nativo
`<dialog>`), `x-ui.status` (nuevo, región que anuncia mensajes) y cinco piezas pequeñas nuevas
(`stat`, `chip`, `badge`, `record`, `section-title`). Los rechazos
de acceso de las pantallas web pasan a vistas propias (`errors/403`, `errors/404`) sin tocar las
respuestas de `/api/v1`. Los arreglos del móvil usan las utilidades responsivas de Tailwind, sin
cambiar la disposición a 1440 px salvo el encabezado único de gestión de contenido. No hay
migraciones, endpoints nuevos ni dependencias nuevas.

La verificación tiene dos mitades: lo que se comprueba leyendo el código o la respuesta HTML va en
Pest; lo que solo existe en un navegador (contraste pintado, tamaños, desborde, foco, diálogos,
nombres accesibles) lo mide `ui-audit`, un script propio con Node y Chrome sin interfaz.

La spec es grande (41 criterios, 60 vistas). Se mantiene en una sola por decisión del usuario
(2026-10-04) y se planea en ocho fases dentro de la misma rama (ver "Rollout").

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 016 `approved` (reaprobada con CA35–CA41); todo cambio traza a un criterio. | manual: `python .ai/bin/aidd.py status`; `/analyze` antes de implementar |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA9–CA13, CA22, CA25–CA29, CA31–CA38 y CA40 solo se pueden probar en un navegador y su test es `npm run test:ui`, fuera de Pest — aprobado por el usuario el 2026-10-06 | La enmienda 1.2.0 de esta misma spec aclara P2 para admitirlo (CA24; A7). Todo lo que sí se puede comprobar en Pest va en Pest: páginas de error, contratos, textos, estructura, tokens, comando y seeder. No hay value objects nuevos ni modificados. | — |
| P3 Capas del módulo | ✅ | Solo cambia presentación (`resources/`), dos middlewares y un comando de `app/Core`, y `bootstrap/app.php`. Ningún `Domain/` se toca ni se añaden dependencias entre módulos. | manual: `/review` del diff; `grep` de `Infrastructure` en `Domain/` sin resultados nuevos |
| P4 Contrato de API primero | ✅ | Ningún endpoint de `/api/v1` cambia: ni el 403 de `only.admin` ni el 404 y el 405 de rutas inexistentes (A1). | test: `OnlyAdminApiContractTest`, `ApiNotFoundContractTest` |
| P5 Autorización en el servidor | ✅ | No cambia quién puede qué. Cambia la respuesta de `OnlyAdmin` en rutas web, así que cada ruta web protegida lleva su test de acceso denegado por actor. | test: `WebAccessDeniedPagesTest` (dataset ruta × actor) |
| P6 Validación de entrada con FormRequest | ➖ | Ningún endpoint cambia su entrada. | — |
| P7 Errores sin detalles internos | ✅ | Las respuestas 403 y 404 de rutas web llevan un texto fijo en español, en HTML y en JSON; nunca el mensaje de la excepción, la ruta pedida ni trazas (A5). Los 500 no cambian. | test: `WebAccessDeniedPagesTest`, `NotFoundPageTest` (con y sin `Accept: application/json`) |
| P8 Secretos fuera del repositorio | ✅ | Sin variables nuevas ni `env()`: el comando de sesión se registra en `routes/console.php` con `app()->environment()` (A15). No guarda credenciales. | lint: `gitleaks` en CI; test: `UiAuditSessionCommandTest` |
| P9 Migraciones reversibles | ➖ | Sin migraciones. | — |
| P10 Dependencias nuevas con ADR | ✅ | Ninguna dependencia nueva. Se actualizan dos transitivas de desarrollo vulnerables; `package.json` solo gana scripts, sin cambios en dependencias (A26). | manual: diff de dependencias de `package.json` (ninguno) y de `package-lock.json` en `/review`; lint: `npm audit --audit-level=high` en CI |
| P11 Datos sensibles y modelo de amenazas | ✅ | Las pantallas con datos personales y de salud cambian de aspecto, no de contenido. Las capturas versionadas se hacen solo con datos de `UiAuditSeeder` (A6). Sin logs nuevos. Modelo de amenazas incluido. | manual: `aidd.py validate`; `/review` de `Log::` (ninguno nuevo) |
| P12 Producción con aprobación y rollback | ✅ | Rollback por tag, sin migraciones. Las vulnerabilidades previas se corrigen en la primera fase; si no hubiera versión elegible, excepción con vencimiento (A18). | manual: `/release` exige la confirmación; lint: SCA en CI |
| P13 Lógica de negocio en el backend | ✅ | Los cambios de JS son de presentación. El texto por rol se decide en Blade con el rol del servidor. | manual: `/review` del JS y Blade tocados |
| P14 Trazabilidad | ❌ aceptado: la página "Sin permiso" no emite el evento de auditoría de acceso denegado porque el registro de auditoría aún no existe (objetivo 5 del roadmap) — aprobado por el usuario el 2026-10-06 | Misma desviación aceptada en la spec 014. Los puntos únicos de rechazo web no cambian de lugar. | — |

Restricción "WCAG 2.1 AA en pantallas nuevas o modificadas": completa, no acotada (A8). La cubren
CA6–CA9, CA12–CA14, CA29, CA35–CA38 y CA40 con `ui-audit` y Pest, más una revisión con
`design:accessibility-review` al cerrar cada grupo de pantallas.

Enmienda 1.2.0 que esta spec aplica (CA24), aprobada por el usuario el 2026-10-06: P15 nuevo y
aclaración de P2 (ver "Contratos y datos" → "Enmienda de la constitución").

## Cambios por módulo
Todas las rutas existen salvo las marcadas (nuevo).

| Módulo | Cambio | Riesgo |
|---|---|---|
| Dependencias · `package-lock.json`, `docs/security.md` | `npm audit fix` para `shell-quote` y `source-map-js`; estado de RS16.a (A18). | bajo |
| Sistema de diseño · `docs/design/system.md`, `docs/design/system.html`, `.ai/project.yaml` | Tokens y componentes nuevos, `source: chosen`, versión 2.0.0, deuda `DS` → "la resuelve 016". El sistema extraído se archiva en `docs/design/history/`. | bajo |
| Constitución · `docs/constitution.md`, `.ai/project.yaml` | P15 nuevo y P2 aclarado, versión 1.2.0, entrada en "Enmiendas"; `constitution_version` de `project.yaml` (A19). | bajo |
| Tokens · `resources/css/app.css` | `@theme` con la tabla "Tokens", `--color-*: initial` y foco único en `@layer base`. | alto: al retirar la paleta por defecto, toda vista sin migrar perdería sus colores (ver Rollout) |
| Componentes · `resources/views/components/ui/button.blade.php`, `input.blade.php`, `h1.blade.php`, `page-hero.blade.php` | Botón con variantes `primary`, `secondary`, `danger`, `icon` (nombre accesible obligatorio) y un solo tamaño. Campo de 44 px y 16 px, etiqueta visible, marca de obligatorio y error enlazado con `aria-describedby`. `page-hero` pinta el título como `h1` e insignia según el rol. | medio |
| Componentes nuevos · `resources/views/components/ui/{brand,dialog,stat,status,chip,badge,record,section-title}.blade.php`, `resources/js/ui/{dialog,status,badge}.js`, `resources/js/app.js` | Marca, diálogo base (con su propia región de estado), contador, región de estado, filtro, insignia de estado, registro (etiqueta y valor) y subtítulo con acción (A47). `dialog.js` y `status.js` se importan desde `app.js`, que ya cargan todos los marcos (A13); `badge.js` lo importan los scripts que pintan estados. | medio |
| Marca · `tests/Browser/make-brand-assets.mjs` (nuevo), `public/images/brand/` (nuevo), `public/favicon.ico` | El script genera, con Node y Chrome y sin dependencias, el icono en 64, 180 (`apple-touch-icon`), 192 y 512 px, el logo horizontal de 520 px, la imagen de acceso y `favicon.ico` (A23, A31). Origen: `storage/app/public/Logos_Melissa_Lopez/` y `storage/app/public/login.jpg`, que no se versionan. | bajo |
| Sitio público · `resources/views/layouts/landing.blade.php`, `components/landing/{nav,footer}.blade.php`, `pages/landing/{inicio,acerca,galeria,contacto}.blade.php`, `resources/js/pages/landing/{inicio,galeria}.js` | Tokens, `x-ui.brand`, logo completo en el pie, insignias dentro del ancho, botón de WhatsApp con texto en tinta, visor de galería con foco claro sobre fondo oscuro. | medio |
| Acceso · `resources/views/layouts/app.blade.php`, `pages/auth/*.blade.php` (5), `resources/js/pages/auth/*.js` (2) | Tokens, logo completo, imagen versionada, etiqueta "Contraseña", acentos. El panel lateral pasa a rosa suave con texto en tinta, el logo completo y la foto (decisión del usuario; A38). `auth/logout` es un documento propio sin marco y lleva sus propias etiquetas de icono (A25). | bajo |
| Panel (marco) · `resources/views/layouts/{dashboard,admin,patient}.blade.php`, `components/ui/sidebar.blade.php`, `pages/dashboard.blade.php`, `resources/js/pages/dashboard.js` | Tokens, `x-ui.brand`, cabecera sin hueco, elemento activo con acento, rol en español, insignia por rol. El inicio del panel monta los diálogos de crear paciente y crear usuario (A9). | medio |
| Pacientes y usuarios · `pages/patients/index.blade.php`, `pages/usuarios/index.blade.php`, `components/ui/{create,edit}-{patient,user}-modal.blade.php`, `confirm-delete-modal.blade.php`, `resources/js/pages/{patients,usuarios}/*.js` | Tokens, componentes compartidos, diálogos sobre `x-ui.dialog`, contadores en una fila. `confirm-delete-modal` lo usan pacientes, usuarios y expedientes. | medio |
| Expedientes · `pages/records/index.blade.php`, `components/records/*.blade.php` (4), `resources/js/pages/records/index.js` | Igual, más: en móvil el expediente elegido sustituye a la lista (CA31) y cada tabla pasa a pares de etiqueta y valor por debajo de `md` (CA27). Monta el diálogo de eliminar y el de detalle de cita (A9). | alto |
| Tratamientos · `pages/tratamientos/index.blade.php`, `resources/js/pages/tratamientos/index.js` | Tokens, cuatro diálogos, textos sin jerga técnica. | bajo |
| Contenido · `pages/contenido/**` (16 vistas), `resources/js/pages/contenido/**` (17 archivos) | Tokens; un solo encabezado de página también a 1440 px, con el nombre de cada pestaña como `h2` junto a su acción (A10); 11 diálogos sobre `x-ui.dialog`; tarjeta de galería que cabe en una pantalla. | alto |
| Agenda · `pages/agenda/index.blade.php`, `components/calendar/*.blade.php` (6), `resources/js/pages/agenda/*.js` (4) | Tokens, filtros en una fila desplazable, calendario con días que son botones, número de citas por día y lista del día bajo el calendario en el móvil; el diálogo "Citas del día" solo se abre desde `md` (decisión del usuario; A48); 5 diálogos sobre `x-ui.dialog`, tarjeta "Citas para hoy" sin hueco; se retira la rama "ver y editar". `view-appointment-modal` y su JS también los usa expedientes (A9). | alto |
| Errores web · `app/Core/Middlewares/OnlyAdmin.php`, `app/Core/Middlewares/EnsureActiveStaff.php`, `bootstrap/app.php`, `routes/web.php`, `resources/views/errors/{403,404,405,419}.blade.php` (nuevos), `resources/views/layouts/error.blade.php` (nuevo) | Los dos middlewares responden `abort(403)` sin mensaje fuera de `api/*`. `bootstrap/app.php` da a los 403, 404, 405 y 419 de rutas web un cuerpo JSON fijo en español cuando se pide JSON (A49). Ruta de respaldo para el 404 web, que excluye `api/*` y no pasa por el grupo `web`. | medio |
| Limpieza · `resources/views/components/ui/table.blade.php`, `resources/views/welcome.blade.php` | Se eliminan (decisión del usuario, 2026-10-06). | bajo |
| Pruebas · `tests/Modules/Core/Unit/Design/*` (nuevo), `tests/Modules/Core/Integration/*` (nuevos), `tests/Browser/` (nuevo), `database/seeders/UiAuditSeeder.php` (nuevo), `app/Core/Console/UiAuditSessionCommand.php` y `UiAuditDataCommand.php` (nuevos), `routes/console.php`, `package.json` (scripts), `.github/workflows/tests.yml` | Tests del sistema, de errores y de estructura; `ui-audit` con sus datos sembrados; paso nuevo en CI. | medio |

## Contratos y datos

### Endpoints y datos
Sin endpoints nuevos, sin cambios en `/api/v1` y sin migraciones.

| Petición | Hoy | Con esta spec |
|---|---|---|
| Web con sesión, rol sin permiso | `only.admin`: JSON `{"error": "Only administrators…"}` con 403. `staff`: página 403 por defecto, en inglés | 403 con la vista `errors.403` |
| La misma, con `Accept: application/json` | `{"error": …}` o `{"message": "Only staff can access this resource."}` | 403 con `{"message": "No tienes permiso para ver esta pantalla."}` |
| Web, dirección inexistente | 404 con la página por defecto de Laravel | 404 con la vista `errors.404`; sin cookie de sesión nueva |
| La misma, con `Accept: application/json` | 404 con un mensaje que incluye la ruta | 404 con `{"message": "No encontramos esta página."}` |
| Web, método no permitido (405) o formulario caducado (419) | página por defecto de Laravel, en inglés | la vista `errors.405` o `errors.419`; con `Accept: application/json`, cuerpo fijo en español |
| Web sin sesión, pantalla protegida | redirección a `/login` | igual |
| `api/*` con rol sin permiso | JSON `{"error": …}` con 403 | igual |
| `api/*`, ruta inexistente o verbo no admitido | JSON 404 o 405 | igual: la ruta de respaldo no captura `api/*` |

### Tokens (variante B · Neutra)
Fuente: [design/propuesta.html](design/propuesta.html). Los nombres son los que generan las
utilidades en Tailwind 4, comprobados con una prueba desechable el 2026-10-07 (A3): se compiló este
`@theme` con `tailwindcss` 4.1.18 y existen `bg-primary`, `text-ink`, `text-muted`, `bg-canvas`,
`border-field`, `border-line`, `bg-overlay`, `rounded-control`, `h-control`, `min-h-control`,
`size-control` y `text-control`; con `--color-*: initial` dejan de existir `text-slate-400` y
`bg-red-600`. Una segunda compilación (A32) mostró que un token de color y uno de tamaño de letra
con el mismo nombre chocan: `text-field` pintaba el color del borde. Por eso el tamaño de letra del
campo se llama `--text-control`, y ningún par de tokens comparte nombre de utilidad.

| Token | Valor | Utilidades | Uso | Contraste |
|---|---|---|---|---|
| `--color-primary` | `#d75078` | `bg-primary`, `border-primary`, `decoration-primary` | Acción principal y acentos no textuales | 3.96:1 sobre superficie (acento ≥ 3:1) |
| `--color-primary-hover` | `#dc6588` | `hover:bg-primary-hover` | Hover de la acción principal: más claro, nunca más oscuro (A4) | tinta encima 5.61:1 |
| `--color-primary-soft` | `#fbe9ee` | `bg-primary-soft` | Elemento activo y avisos | tinta encima 16.13:1 |
| `--color-secondary` | `#f2b0a6` | `border-secondary`, `bg-secondary` | Bordes de avisos e insignias, fondos decorativos | decorativo |
| `--color-ink` | `#0b1120` | `text-ink`, `bg-ink`, `outline-ink` | Texto principal, texto sobre el primario, superficie oscura del visor | 4.76:1 sobre primario · 18.83:1 sobre superficie |
| `--color-muted` | `#556274` | `text-muted`, `placeholder:text-muted` | Texto secundario, iconos de acción y texto de ejemplo de los campos (A39) | 6.20:1 sobre superficie · 5.93:1 sobre lienzo · 5.31:1 sobre primario suave |
| `--color-canvas` | `#f8fafc` | `bg-canvas` | Fondo de página | — |
| `--color-surface` | `#ffffff` | `bg-surface` | Tarjetas, diálogos, campos | — |
| `--color-on-dark` | `#ffffff` | `text-on-dark`, `outline-on-dark` | Texto sobre peligro y sobre tinta; foco sobre superficies oscuras (A2, A11) | 6.47:1 sobre peligro · 18.83:1 sobre tinta |
| `--color-line` | `#e2e8f0` | `border-line` | Separadores y bordes de tarjeta | decorativo |
| `--color-field` | `#7c8aa0` | `border-field` | Borde de campo y de control | 3.50:1 sobre superficie · 3.35:1 sobre lienzo |
| `--color-overlay` | `rgb(11 17 32 / 0.55)` | `bg-overlay` | Capa tras un diálogo | — |
| `--color-danger` / `--color-danger-soft` | `#b91c1c` / `#fef2f2` | `bg-danger`, `text-danger`, `bg-danger-soft` | Eliminar y errores | 5.91:1 |
| `--color-success` / `--color-success-soft` | `#047857` / `#ecfdf5` | `text-success`, `bg-success-soft` | "Activo", "Completada" | 5.21:1 |
| `--color-warning` / `--color-warning-soft` | `#b45309` / `#fffbeb` | `text-warning`, `bg-warning-soft` | "Asignada" | 4.84:1 |
| `--color-info` / `--color-info-soft` | `#1d4ed8` / `#eff6ff` | `text-info`, `bg-info-soft` | "Reprogramada" | 6.16:1 |
| `--color-whatsapp` | `#25d366` | `bg-whatsapp` | Botón de WhatsApp, con texto en tinta | tinta encima 9.49:1 |
| `--radius-control` · `--radius-box` · `--radius-card` | 12 px · 16 px · 24 px | `rounded-control`, `rounded-box`, `rounded-card` | Botones y campos · avisos, contadores y barras · tarjetas y diálogos. Insignias y filtros usan `rounded-full` (A27) | — |
| `--spacing-control` | 44 px | `h-control`, `min-h-control`, `size-control` | Alto de botón, campo, filtro y elemento de menú | — |
| `--text-control` · `--text-min` | 16 px · 12 px | `text-control`, `text-min` | Letra de campo · texto más pequeño permitido | — |

Reglas:
- El rosa nunca es color de **texto** (3.96:1 sobre blanco). Sí puede pintar iconos, subrayados y bordes, donde basta 3:1: los iconos de acento usan `text-primary` dentro de un contenedor marcado `data-accent`, que `OnlyDesignTokensTest` admite solo ahí (A30).
- El foco es un contorno de 3 px con 2 px de separación: en tinta sobre superficies claras y en `on-dark` dentro de un contenedor oscuro (`data-surface="dark"`: visor de la galería, controles sobre imagen) (A11).
- No se usa ningún color fuera de esta tabla: ni literales ni la paleta por defecto de Tailwind (A2). `white` y `black` tampoco existen: se usan `surface`, `on-dark` e `ink`.
- El texto de ejemplo de todo campo se pinta en `muted` (6.20:1), fijado en `@layer base` para que ningún campo herede el 50 % por defecto (A39).
- **Opacidad, degradados y sombras (A38):** un token admite modificador de opacidad solo en fondos, bordes y sombras (`bg-surface/90`, `border-on-dark/20`, `shadow-primary/20`), nunca en texto: `text-*/NN` falla en `OnlyDesignTokensTest`. Los degradados solo van entre tokens y son decorativos; ningún texto se apoya en un degradado sin que `ui-audit` mida su contraste sobre el tono más desfavorable.

### Cambios a incorporar al sistema
La primera tarea actualiza `docs/design/system.md` y `system.html` antes de tocar vistas. Aplica la
regla "del sistema extraído al elegido" (`references/design.md` §3), no la de rediseño (§2): la
marca y la composición se mantienen, y la propuesta en HTML con dos variantes sustituyó a las
opciones (A29).

- **Color, radios y tamaños:** la tabla de arriba sustituye a la actual. Desaparecen los radios de 6, 8 y 32 px y las alturas de 24 a 48 px.
- **Componentes:** botón (4 variantes, un tamaño), campo, marca, diálogo base, contador, región de estado y página de error; y como componentes Blade propios (A47): filtro (`x-ui.chip`), insignia de estado (`x-ui.badge`, con su gemela `resources/js/ui/badge.js` para los estados que pinta el navegador), registro de etiqueta y valor (`x-ui.record`) y subtítulo con acción (`x-ui.section-title`, un `h2`).
- **Reglas de uso:** una acción principal por pantalla; texto nunca en rosa; ningún color fuera de los tokens.
- **Frontmatter:** `source: chosen`, `version: 2.0.0`, `status: draft` hasta que el usuario apruebe el sistema 2.0.0; entonces `approved` aquí y en `.ai/project.yaml` (A29). Deuda `DS1`–`DS11`, `DS13`, `DS14` → "la resuelve 016".
- **Capturas:** al cerrar, el paso de CI ejecuta `ui-audit --screenshots` sobre su base recién migrada, que solo contiene datos de `UiAuditSeeder`, y publica las capturas de las 65 pantallas y las 4 nuevas a 390 y 1440 px como artefacto; de ahí se copian al repositorio, sin re-extraer el sistema (A6, A37).

### Pantallas y estados

| Pantalla | Componentes | Estados | Accesibilidad medible |
|---|---|---|---|
| Sin permiso (nueva) | página de error, logo, botón principal | único | un `h1`; botón a `/dashboard`; sin datos de la pantalla pedida |
| No encontrada (nueva) | página de error, logo, botón principal | con sesión → `/dashboard`; sin sesión → `/` | un `h1` |
| Método no permitido y formulario caducado (nuevas) | página de error, logo, botón principal | únicos | un `h1`; enlace de vuelta como en "No encontrada" |
| Diálogos (unos 25, en cada pantalla que los monta) | `x-ui.dialog` | abierto, con error de validación, enviando | `<dialog>` con `aria-labelledby`; foco dentro; Escape cierra; el foco vuelve al control que lo abrió; ancho y alto ≤ pantalla con cuerpo desplazable y pie fijo |
| Formularios | `x-ui.input`, `x-ui.status` | vacío, con error, enviando, guardado | etiqueta visible asociada; obligatorios marcados; error asociado al campo; resultado anunciado en la región de estado |
| Listados | encabezado, contadores, buscador, filtros, tarjetas | con datos, vacío, cargando, error | primer elemento a ≤ 844 px a 390 px; controles ≥ 44 × 44 px; botones de icono con nombre |
| Expediente | encabezado, registros | con datos, vacío por sección, formulario abierto | a 390 px sin desplazamiento lateral; el expediente elegido queda a la vista |
| Agenda | filtros, calendario, lista del día, diálogos | mes sin citas, con citas, día elegido | a 390 px la página mide 390 px; cada día es un botón, muestra su número de citas y lo incluye en su nombre accesible; "Citas del día" es lista en el móvil y diálogo desde `md` |
| Acceso | marco, panel lateral rosa suave (desde `md`), formulario | vacío, con error, enviando | texto en tinta sobre rosa suave (16.13:1) |
| Gestión de contenido | un `h1` ("Gestión de contenido"), pestañas, `h2` por pestaña | cuatro pestañas | un solo `h1` a 390 y a 1440 px |
| Todas | — | — | un `h1`; un estilo de foco; texto ≥ 12 px; campos ≥ 16 px; iconos y bordes con información ≥ 3:1 |

**Región de estado (CA37):** una por marco, siempre montada y vacía (`role="status"`); el texto
llega después y se retira al terminar. Los errores de guardado usan `role="alert"`. Como un
`<dialog>` abierto deja inerte el resto del documento, **cada diálogo lleva su propia región** dentro
de `x-ui.dialog`, y `resources/js/ui/status.js` anuncia en la región del diálogo abierto si lo hay
y, si no, en la del marco (A34). Marcos que la montan: sitio público, acceso, cerrar sesión y panel
(una sola vez en `layouts/dashboard`, que heredan `admin` y `patient`) (A43).

**Teclado (CA40; A35):** todo lo que hace algo al pulsarlo es un control nativo (`button`, `a`,
campo) o tiene rol, foco y activación con Intro y barra espaciadora. Los días del calendario y las
tarjetas de la galería pública pasan a ser botones.

**Control táctil (CA9):** `ui-audit` mide botones, campos, selectores, filtros, elementos de menú,
iconos de acción y enlaces que están solos en su línea o en una lista de navegación. Los enlaces
dentro de un párrafo quedan fuera, como permite WCAG para objetivos en línea (A30).

Nota: revisar con el plugin `design` (`design:design-handoff`) antes de `/implement`, usar
`design:ux-copy` para los textos de las páginas de error, estados vacíos y confirmaciones, y
`design:accessibility-review` al cerrar cada grupo.

### Qué ve cada rol
No cambian los permisos; cambian textos que hoy son iguales para todos.

| Elemento | Administrador | Asistente | Doctor | Paciente |
|---|---|---|---|---|
| Insignia del encabezado | "Panel de administración" | "Panel de asistente" | "Panel clínico" | "Mi cuenta" |
| Rol en la cabecera y en el inicio | Administrador | Asistente | Doctor | Paciente |
| Pantalla sin permiso | no aplica | 403 en agenda, pacientes, usuarios, tratamientos y contenido | igual que asistente | 403 en todas las anteriores y en expedientes |
| Botón de la página 403 | — | a su inicio | a su inicio | a su inicio |

Cuando se planee la spec 013, que hará que un paciente en rutas de staff reciba 401 en vez de 403,
deberá declarar `extends: [016]` y ajustar esta tabla y `WebAccessDeniedPagesTest` (A28).

### Enmienda de la constitución (CA24)
Texto aprobado por el usuario el 2026-10-06 (versión 1.2.0, una sola enmienda):

> **P15. La interfaz usa solo el sistema de diseño.**
> **Regla:** Toda vista y todo JS de página nuevo o modificado toma colores, radios y tamaños de control de los tokens de `resources/css/app.css` y usa los componentes de `docs/design/system.md`. Ningún color literal ni valor arbitrario de color fuera del archivo de tokens. Un valor que falte se añade primero al sistema.
> **Cómo se verifica:** `OnlyDesignTokensTest` y `DesignTokensTest` en la suite Pest; `npm run test:ui` en CI.
> **Por qué:** La interfaz llegó a tener 8 rosas en unas 620 apariciones y contrastes insuficientes (deuda `DS1`–`DS3`).

> **P2, frase añadida a la regla:** Lo que solo existe en un navegador (medidas de pantalla, contraste pintado, foco, diálogos, nombres accesibles) se prueba con `npm run test:ui` (`tests/Browser/`), que también debe fallar sin el cambio y pasar con él.
> **P2, "Cómo se verifica":** se añade `npm run test:ui` en CI.

La enmienda se escribe en la fase 2, cuando `OnlyDesignTokensTest` ya existe. Durante las fases 2 a
7 ese test trabaja con una lista de archivos pendientes: P15 se exige a lo nuevo o modificado, y
cada archivo sale de la lista al migrarse. `ui-audit` corre en local desde la fase 1 y en CI desde
la fase 8 (A19).

## Estrategia de pruebas

**Pest, unitarias (`tests/Modules/Core/Unit/Design/`):**
- `DesignTokensTest`: los valores de la tabla Color de `docs/design/system.md` son los de `@theme`; cada par declarado cumple su contraste, incluidos tinta sobre el hover (A4) y el texto de ejemplo (A39); la paleta por defecto está retirada; y dos tokens de espacios distintos nunca producen la misma utilidad (A32).
- `OnlyDesignTokensTest`: recorre `resources/views` y `resources/js` y falla ante un color literal, una utilidad arbitraria de color, **cualquier utilidad de color cuyo nombre no sea un token** (control positivo: `text-slate-400`) (A2), `text-primary` fuera de `data-accent`, opacidad sobre un color de texto (`text-*/NN`), un degradado con un nombre que no sea token (A38), un tamaño de letra arbitrario menor de 12 px y `focus:outline-none`. Cada regla con su control positivo. Además, sobre los archivos **pendientes**: toda utilidad de color que usan existe entre los tokens y el bloque transitorio, para que nada pierda su color sin aviso mientras dura la migración (A40).
- `UiCopyTest`: falla ante las palabras de `forbidden-words.php`, lista cerrada que sale de extraer todos los textos visibles de vistas y JS y que el usuario revisa (A17), y ante términos técnicos (`API`, `backend`, `rutas admin`).
- `UnusedUiTest`: `x-ui.table`, `welcome.blade.php`, la rama "ver y editar" y la rama de marcado antiguo de `ui/dialog.js` no existen.

**Pest, integración (`tests/Modules/Core/Integration/`):**
- `WebAccessDeniedPagesTest`: ruta web protegida × actor sin permiso → 403, vista `errors.403`, texto en español, enlace a `/dashboard`, sin el mensaje interno ni datos de la pantalla; y el mismo caso con `Accept: application/json` (A5).
- `NotFoundPageTest`: con y sin sesión; enlace correcto; sin la ruta pedida en el cuerpo; un visitante no recibe cookie de sesión nueva (A22); y el caso con `Accept: application/json` (A5).
- `ApiNotFoundContractTest`: un GET a una ruta inexistente de `api/v1` y un GET a una ruta de API solo-POST conservan código y cuerpo JSON de hoy (A1).
- `OnlyAdminApiContractTest`: `api/*` conserva el JSON y el 403 de hoy.
- `OtherErrorPagesTest`: un método no permitido y un formulario caducado sobre una pantalla web responden su vista propia en español, y el cuerpo fijo con `Accept: application/json` (A49).
- `GuestRedirectTest`: sin sesión → `/login` en cada pantalla protegida.
- `PageStructureTest`: cada pantalla, por rol, tiene exactamente un `h1`, la marca con su texto alternativo, `<link rel="icon">` y `apple-touch-icon` hacia archivos propios que existen y pesan más de 0 bytes, la imagen de acceso versionada y cada campo con su etiqueta asociada.
- `RoleCopyTest`: insignia y rol por actor.
- `UiAuditSessionCommandTest`: con la aplicación arrancada en entorno `production`, el comando no aparece en la lista de Artisan ni puede ejecutarse; en `testing` solo emite sesiones de usuarios del seeder (A15).
- `UiAuditSeederTest`: el seeder solo corre en `local` y `testing` (falla sin escribir nada en `production` y en cualquier otro nombre de entorno) (A20, A56); sembrar dos veces no duplica; y la limpieza borra solo lo que el seeder marcó y deja intacto un registro ajeno (A36).
- Tests existentes que afirman marcado cambiado (`StaffNavigationTest`, `RecordsScreenTest`, `WebUnexpectedErrorTest`) se actualizan en la misma tarea que cambia ese marcado, sin cambiar lo que comprueban (A41).

**Suite en verde por fase (A41):** cada test nuevo se escribe, se comprueba que falla por la razón
esperada y se deja marcado como pendiente de su tarea (`->skip('016: pendiente de T0xx')`). La tarea
que lo implementa quita la marca. Así la suite Pest termina en verde en cada fase, y la verificación
final falla si queda alguna marca.

**Navegador (`tests/Browser/ui-audit.mjs`, `npm run test:ui`):** Node y Chrome sin interfaz por el
protocolo DevTools, sin dependencias. Recorre `screens.json`: las 65 pantallas y diálogos del
inventario más cada diálogo en **cada pantalla que lo monta** (expediente como administrador,
asistente y doctor; inicio del panel) (A9), a 390 × 844 y 1440 × 900. Módulos:
- `checks-global`: desborde, controles táctiles, alturas y radios, letra, contraste del texto, del texto de ejemplo y de los bordes pintados, foco (también sobre superficies oscuras), hover del botón principal y su contraste, recortes.
- `checks-dialogs`: nombre accesible, foco al abrir y al cerrar, Escape, ancho y alto.
- `checks-screens`: agenda, expediente, listados, encabezados, tarjetas vacías, cabecera, y la tarjeta de galería **pintada**: su alto, que se identifica por su descripción y que no muestra el nombre ni la dirección internos (A42).
- `checks-a11y`: nombre accesible de todo control e imagen con información, etiqueta visible por campo y marca de obligatorio, error asociado a su campo, región de estado que recibe el mensaje, contraste ≥ 3:1 de iconos de acción y bordes de control, estados con texto además de color (CA35–CA38); que la región que recibe un mensaje esté en el árbol de accesibilidad también con un diálogo abierto (A34); y que no haya ningún elemento con manejador de clic que no sea un control nativo ni tenga rol, foco y activación por teclado (CA40; A35), con control positivo sobre el calendario actual.
- `checks-composition`: línea base a 1440 px.

**Dónde corre (A16, A46):** el script recibe dos parámetros: `--base-url` y `--artisan`, el prefijo
con el que llama a Artisan para obtener la sesión (`ui:audit-session <rol>`).
- En local: Node y Chrome del equipo contra la app de Docker; por defecto `--base-url
  http://localhost:8000` y `--artisan "docker compose exec -T app php artisan"`.
- En CI: en el job de `tests.yml`, con base recién migrada, `php artisan serve` y el Chrome del
  runner; `--artisan "php artisan"`. No se toca `docker/`.

**Datos (A36, A37):** el comando `ui:audit-data` siembra con `UiAuditSeeder` y, con `--clean`, retira
solo lo que el seeder marcó. En local los datos sembrados conviven con los de desarrollo; por eso
ninguna comprobación depende de cuántos registros haya: `ui-audit` mide el primer elemento de cada
listado y la estructura, no los totales. Las capturas versionadas se toman solo en CI.

**Composición a 1440 px (CA22; A12, A37):** `baseline-1440.json` guarda, por pantalla, la secuencia de
regiones (marco, encabezado, contadores, filtros, listado, tarjetas) con su posición relativa y los
**tipos** de acción de cada región; de un listado solo cuenta su primer elemento. No guarda textos,
niveles de encabezado ni totales, así que da lo mismo en local y en CI. Diferencias admitidas,
declaradas en el propio archivo con su criterio: gestión de contenido pierde un encabezado (CA34) y
la tarjeta de cita pierde la rama retirada (CA17). Cualquier otra diferencia falla.

**Manual:** lector de pantalla en un diálogo y en una página de error; `design:accessibility-review`
y aspecto de cada grupo contra `design/propuesta.html` a 390 px y contra las capturas actuales de
`docs/design/capturas/` a 1440 px, porque la propuesta no dibuja el escritorio (A27).

## Modelo de amenazas
La spec no añade datos, endpoints ni entradas, pero cambia cómo responde el servidor a un acceso
denegado y añade una herramienta de pruebas con sesión.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: al cambiar `OnlyAdmin` o `EnsureActiveStaff`, una pantalla protegida queda accesible a otro rol o a un paciente | A01:2025 Broken Access Control | middlewares, `routes/web.php` | Los middlewares conservan sus comprobaciones; solo cambia la forma de la respuesta | `WebAccessDeniedPagesTest` (cada ruta × asistente, doctor, paciente, staff inactivo) |
| TM2 | Information disclosure: la respuesta 403 o 404 revela el mensaje interno, la ruta, trazas o datos de la pantalla, en HTML o en JSON | A10:2025 Mishandling of Exceptional Conditions | `errors/*`, `bootstrap/app.php` | Texto fijo; ni la vista ni el JSON imprimen la excepción o la URL | `WebAccessDeniedPagesTest`, `NotFoundPageTest` (con y sin `Accept: application/json`) |
| TM3 | Tampering: la dirección inexistente se refleja en la página y permite inyectar marcado | A05:2025 Injection | `errors/404`, ruta de respaldo | La respuesta no refleja ninguna parte de la petición | `NotFoundPageTest` con una ruta que contiene marcado |
| TM4 | Tampering: los cambios alteran el contrato de la API que consumen el JS y la futura app (403 de `only.admin`, 404 y 405) | A01:2025 Broken Access Control | `OnlyAdmin` en `api/*`, ruta de respaldo | La rama `api/*` no cambia y la ruta de respaldo excluye `api/*` | `OnlyAdminApiContractTest`, `ApiNotFoundContractTest` |
| TM5 | Elevation: el comando de sesión de prueba o el seeder se usan en producción para obtener una cuenta de administrador | A07:2025 Authentication Failures | `UiAuditSessionCommand`, `UiAuditSeeder` | Los dos comandos solo se registran en `local` y `testing`; el de sesión solo emite sesiones de usuarios del seeder; el seeder solo corre en `local` y `testing`, y su limpieza solo borra lo que marcó | `UiAuditSessionCommandTest`, `UiAuditSeederTest` |
| TM6 | Tampering: las actualizaciones de dependencias introducen un paquete comprometido | A03:2025 Software Supply Chain Failures | `package-lock.json` | Solo versiones con al menos 7 días de publicadas; sin paquetes nuevos | lint: `npm audit --audit-level=high` y revisión del lockfile en `/review` |
| TM7 | Information disclosure: un visitante sin sesión distingue qué pantallas protegidas existen | A01:2025 Broken Access Control | rutas web protegidas | Sin sesión, toda pantalla protegida redirige igual a `/login`; la ruta de respaldo no las captura | `GuestRedirectTest` |
| TM8 | Denial of service: cada dirección inexistente crea una sesión en el servidor | A10:2025 Mishandling of Exceptional Conditions | ruta de respaldo | El 404 no pasa por el grupo `web`: lee el token de la cookie y no abre sesión | `NotFoundPageTest` (sin cookie de sesión nueva) |

Casos de abuso de la spec: CA18 → TM1 y TM2; CA20 → TM2 y TM3.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: paleta nueva, sin rosas anteriores | Tokens; migración de todas las vistas y JS | `OnlyDesignTokensTest`; verificación manual: cada grupo a 390 px contra `design/propuesta.html` |
| CA2: cada color con nombre, una sola vez | `resources/css/app.css`; migración | `OnlyDesignTokensTest` (solo nombres de token), `DesignTokensTest` (paleta por defecto retirada) |
| CA3: una acción principal y un solo hover | `x-ui.button`, token de hover | `DesignTokensTest`; `ui-audit` `checks-global` (fondo y hover idénticos en todas las pantallas) |
| CA4: texto oscuro sobre el primario | `x-ui.button` | `DesignTokensTest` (tinta/primario y tinta/hover ≥ 4.5); `ui-audit` (color pintado, en reposo y en hover) |
| CA5: enlaces y activo en tinta, rosa como acento | Tokens; `sidebar`, `nav`, enlaces | `OnlyDesignTokensTest` (`text-primary` solo en `data-accent`); `ui-audit` (contraste de enlaces) |
| CA6: texto ≥ 4.5:1 | Tokens; estados; WhatsApp | `DesignTokensTest`; `ui-audit` `checks-global` |
| CA7: borde de campo ≥ 3:1 | `x-ui.input`, campos | `DesignTokensTest`; `ui-audit` |
| CA8: un solo foco visible ≥ 3:1 | `@layer base`; `data-surface="dark"` | `OnlyDesignTokensTest`; `ui-audit` (contorno y su contraste al enfocar cada control, incluido el visor) |
| CA9: controles táctiles ≥ 44 × 44 px a 390 px | Componentes, filtros, menú, iconos | `ui-audit` `checks-global` |
| CA10: misma altura y radio por tipo de control | Tokens; componentes | `ui-audit` `checks-global` |
| CA11: agenda sin desborde a 390 px | `pages/agenda`, `calendar/grid` | `ui-audit` `checks-screens` |
| CA12: diálogo anuncia título, atrapa y devuelve el foco | `x-ui.dialog`, `ui/dialog.js` | `ui-audit` `checks-dialogs`; verificación manual: lector de pantalla en "Nueva cita" |
| CA13: diálogo no supera el alto de la ventana | `x-ui.dialog` | `ui-audit` `checks-dialogs` a 390 × 844 y 1440 × 900 |
| CA14: un título principal por pantalla | `page-hero`, marcos, contenido | `PageStructureTest` |
| CA15: mismo logo, texto alternativo, icono de pestaña | `x-ui.brand`, `public/images/brand`, marcos | `PageStructureTest`; verificación manual: pestaña del navegador |
| CA16: ortografía del español | Vistas y textos de JS | `UiCopyTest` |
| CA17: ninguna pieza sin uso | Eliminaciones | `UnusedUiTest` |
| CA18 (abuso): página "Sin permiso" | middlewares, `errors/403` | `WebAccessDeniedPagesTest` |
| CA19: página "No encontrada" | ruta de respaldo, `errors/404` | `NotFoundPageTest` |
| CA41: páginas para método no permitido y formulario caducado | `errors/405`, `errors/419`, `bootstrap/app.php` | `OtherErrorPagesTest` |
| CA20 (abuso): sin datos en crudo, inglés ni detalles técnicos | `errors/*`, middlewares, `bootstrap/app.php` | `WebAccessDeniedPagesTest`, `NotFoundPageTest`, `OtherErrorPagesTest` (HTML y JSON) |
| CA21: sin sesión → iniciar sesión | sin cambio | `GuestRedirectTest` |
| CA22: composición conservada a 1440 px | Todas las vistas | `ui-audit` `checks-composition`; verificación manual: cada grupo a 1440 px contra `docs/design/capturas/` |
| CA23: sistema y capturas actualizados, deuda resuelta | `docs/design/*` | manual: `aidd.py validate`; verificación manual: `system.html` muestra las pantallas nuevas |
| CA24: enmienda de la constitución | `docs/constitution.md` | `OnlyDesignTokensTest` (verificación de P15); manual: versión 1.2.0, P15 y P2 en "Enmiendas" |
| CA25: citas legibles en el calendario | `calendar/grid`, `agenda/index.js` | `ui-audit` `checks-screens` |
| CA26: diálogos dentro del ancho | `x-ui.dialog` | `ui-audit` `checks-dialogs` a 390 px |
| CA27: registros del expediente sin tablas cortadas | `components/records/*` | `ui-audit` `checks-screens` |
| CA28: nada recortado ni truncado | `pages/landing/inicio`, selector de archivo | `ui-audit` `checks-global` |
| CA29: campos ≥ 16 px, texto ≥ 12 px | `x-ui.input`, tokens | `OnlyDesignTokensTest`; `ui-audit` `checks-global` |
| CA30: textos por rol, en español, sin jerga | `page-hero`, `sidebar`, `dashboard`, `login` | `RoleCopyTest`, `UiCopyTest` |
| CA31: expediente a la vista al elegir paciente | `pages/records/index`, `records/index.js` | `ui-audit` `checks-screens` |
| CA32: tarjeta de galería | `contenido/galeria/index` y su JS | `ui-audit` `checks-screens` (alto, descripción como identificación, sin nombre ni dirección internos, sobre la tarjeta pintada) |
| CA33: primer elemento del listado en la primera pantalla | Encabezado, `x-ui.stat`, filtros | `ui-audit` `checks-screens` |
| CA34: sin espacio vacío sin función | `contenido/index` y pestañas, `agenda`, `sidebar` | `ui-audit` `checks-screens`; `PageStructureTest` (un `h1` en `/contenido`) |
| CA35: nombre accesible de controles e imágenes | `x-ui.button`, `x-ui.brand`, vistas | `ui-audit` `checks-a11y`; verificación manual: `design:accessibility-review` por grupo |
| CA36: etiqueta visible y obligatorios | `x-ui.input`, formularios | `PageStructureTest` (etiqueta asociada); `ui-audit` `checks-a11y` |
| CA37: mensajes anunciados, error asociado al campo | `x-ui.status`, `x-ui.dialog`, `ui/status.js`, `x-ui.input`, scripts de página | `ui-audit` `checks-a11y` (también con un diálogo abierto); verificación manual: lector de pantalla al guardar un paciente con error |
| CA38: contraste no textual ≥ 3:1; nada solo por color | Tokens (`muted`, `field`), insignias de estado | `DesignTokensTest`; `ui-audit` `checks-a11y` |
| CA40: todo se usa con teclado | calendario, galería pública, componentes | `ui-audit` `checks-a11y` (sin elementos con clic que no sean controles) |
| CA39: sin vulnerabilidades altas ni críticas | `package-lock.json`; estado de RS16.a en `docs/security.md` | lint: `npm audit --audit-level=high` y `composer audit --locked` (verificación final y CI) |
| RNF: WCAG 2.1 AA | Todo | los de CA6–CA9, CA12–CA14, CA29, CA35–CA38 y CA40; verificación manual: `design:accessibility-review` por grupo |
| RNF: sin desplazamiento horizontal a 390 y 1440 px | Todo | `ui-audit` `checks-global` |
| RNF: sin regresiones | Todo | suite Pest completa en CI |
| RNF: logo, icono e imagen de acceso propios | `public/images/brand` | `PageStructureTest` |
| TM1 | middlewares | `WebAccessDeniedPagesTest` |
| TM2 | `errors/*`, `bootstrap/app.php` | `WebAccessDeniedPagesTest`, `NotFoundPageTest` |
| TM3 | `errors/404` | `NotFoundPageTest` |
| TM4 | `OnlyAdmin`, ruta de respaldo | `OnlyAdminApiContractTest`, `ApiNotFoundContractTest` |
| TM5 | comando y seeder | `UiAuditSessionCommandTest`, `UiAuditSeederTest` |
| TM6 | `package-lock.json` | lint: `npm audit --audit-level=high` en CI |
| TM7 | rutas web | `GuestRedirectTest` |
| TM8 | ruta de respaldo | `NotFoundPageTest` |

## Observabilidad
- Logs nuevos: ninguno. Las respuestas 403 y 404 no registran nada nuevo.
- Eventos de auditoría: ninguno. El acceso denegado no se audita hasta el objetivo 5 del roadmap (desviación de P14 aceptada por el usuario el 2026-10-06).
- Métricas y alertas: sin cambios. Las alertas de 5xx siguen pendientes de la spec 017.
- Cómo se verifica tras el deploy: `/pagina-que-no-existe` responde 404 con la marca; con un usuario asistente, `/agenda` responde 403 con "Sin permiso"; `/api/v1/ruta-que-no-existe` sigue respondiendo JSON; en Loki no aparecen 5xx nuevos en los 15 minutos siguientes.

## Rollout
- Feature flag: no aplica. El cambio es de presentación sobre un prototipo sin datos ni usuarios reales.
- Orden de despliegue: una sola imagen, sin migraciones. Dentro de la rama, ocho fases: (1) dependencias, datos y herramientas de prueba, (2) sistema, constitución y tokens, (3) componentes, (4) sitio público y acceso, (5) marco del panel, pacientes, usuarios y tratamientos, (6) expedientes y contenido, (7) agenda, (8) errores, limpieza, capturas y cierre.
- **Convivencia de pantallas migradas y sin migrar:** al retirar la paleta por defecto, una vista sin migrar perdería sus colores. Por eso el `@theme` de la fase 2 declara, en un bloque aparte marcado como transitorio, los nombres antiguos que las vistas pendientes aún usan; la fase 8 lo elimina. `DesignTokensTest` falla si el bloque transitorio existe cuando la lista de pendientes está vacía, y `OnlyDesignTokensTest` falla si un archivo pendiente usa un color que no está ni en los tokens ni en el bloque (A40). Así la app se ve completa al cerrar cada fase.
- **Diálogos compartidos entre fases (A9, A21):** `resources/js/ui/dialog.js` abre y cierra tanto el marcado antiguo (un `div` que se muestra y se oculta) como el nuevo (`<dialog>`). Primero pasan a usarlo los scripts de cada pantalla, con el marcado antiguo aún en pie; después se migra la vista del diálogo, cuando todos los scripts que lo abren ya lo usan. Ninguna tarea termina con un diálogo que no abre. La fase 8 retira la rama del marcado antiguo.
- Compatibilidad: convive con la versión anterior durante el deploy; no cambia la API ni los datos.
- Rollback: redesplegar el tag anterior (`v0.1.0`) siguiendo "Rollback" de `docs/deployment.md`. No hay nada que revertir en la base.
- Después del deploy: purgar la caché de Cloudflare (hoy manual), porque cambian `favicon.ico` y `public/images/brand/`.
- Métricas a vigilar tras el deploy: tasa de 5xx y número de 403 y 404 en Loki; carga de CSS y JS sin 404 de recursos.

## Decisiones (→ ADR si son arquitectónicas)
- **Una sola spec** con 41 criterios; no se divide (decisión del usuario, 2026-10-04).
- **Variante B · Neutra** de `design/propuesta.html` (decisión del usuario, 2026-10-06; tipo diseño).
- **Marca:** el consultorio se llama Dentissa y los textos lo conservan; el logo es el de la doctora. En las barras, el icono (blanco sobre rosa) junto a "Dentissa"; el logo completo, en el pie del sitio, el acceso y las páginas de error (decisión del usuario, 2026-10-06).
- **Imagen de acceso versionada** junto con la marca (decisión del usuario, 2026-10-06; A31).
- **Tokens en `@theme` con la paleta por defecto retirada** (A2): es la única forma de que el build, y no solo un test, impida un color fuera del sistema. Comprobado con una prueba desechable el 2026-10-07 (A3).
- **Hover más claro que el primario** (`#dc6588`): uno más oscuro no puede cumplir 4.5:1 con el texto en tinta (A4).
- **Diálogo sobre `<dialog>` nativo:** foco atrapado, Escape, fondo inerte y devolución del foco sin dependencias. No choca con la CSP (comprobado en `/analyze`). Durante la migración, el módulo de diálogos acepta el marcado antiguo y el nuevo, para que vistas y scripts puedan migrarse en tareas separadas sin dejar nada roto (A21); `UnusedUiTest` afirma al final que la rama antigua ya no existe.
- **Pruebas de interfaz con script propio (Node + Chrome)**, sin dependencias ni ADR (decisión del usuario, 2026-10-06). Comprobado con una prueba de concepto el 2026-10-06 sobre las 65 pantallas a 390 px.
- **P2:** desviación aceptada y enmienda que la convierte en regla (decisión del usuario, 2026-10-06; A7).
- **WCAG 2.1 AA completo**, con CA35–CA38 y revisión por grupo (decisión del usuario, 2026-10-06; A8).
- **Gestión de contenido con un solo encabezado**, en móvil y en escritorio (decisión del usuario, 2026-10-06; A10).
- **Sesión para las pruebas de navegador:** comando de consola registrado en `routes/console.php` solo en `local` y `testing` (A15). Alternativa descartada: contraseñas de prueba en el repositorio.
- **404 web por una ruta de respaldo fuera del grupo `web`:** solo lee el token de la cookie para saber si hay sesión, excluye `api/*` y no abre sesión (A1, A22). La opción conservadora de A22 la tomó `/analyze` sin respuesta del usuario; queda para que la revise al aprobar este plan.
- **403 y 404 de rutas web con cuerpo fijo también en JSON** (A5).
- **Expediente en el móvil:** al elegir un paciente se muestra el expediente en lugar de la lista, con un enlace para volver; a 1440 px se conserva la disposición actual.
- **Tablas del expediente:** por debajo de `md`, pares de etiqueta y valor; desde `md`, la tabla actual.
- **Calendario en el móvil:** cada día muestra el número de citas y, al tocarlo, la lista del día aparece debajo. A 1440 px se conservan las etiquetas actuales.
- **Capturas** con `ui-audit --screenshots` y datos del seeder, no con `/init --upgrade --redo-design` (A6).
- **Activos de marca** generados por un script propio con Node y Chrome, que también escribe el `.ico`; sin dependencias (A23).
- **Piezas sin uso (DS13):** se retiran las tres (decisión del usuario, 2026-10-06).
- **Panel de acceso en rosa suave con texto en tinta** (decisión del usuario, 2026-10-07; A38).
- **"Citas del día":** lista bajo el calendario en el móvil; diálogo desde `md` (decisión del usuario, 2026-10-07; A48).
- **Páginas propias también para 405 y 419; las del servidor web quedan fuera**, en el roadmap (decisión del usuario, 2026-10-07; A49).
- **Región de estado dentro de cada diálogo** (A34) y **controles nativos para todo lo pulsable** (A35).
- **Datos de prueba por comando** (`ui:audit-data`, con `--clean`), porque `db:seed` no admite opciones propias de un seeder (A36); capturas versionadas solo desde CI (A37).
- **Piezas repetidas como componentes** (`chip`, `badge`, `record`, `section-title`) para que ninguna tarea invente su marcado (A47).
- **Vulnerabilidades previas — RS16.a, dentro del alcance (A18):** `docs/security.md` la da por mitigada en v0.1.0, pero `npm audit` reporta de nuevo `shell-quote` (crítica, vía `concurrently`) y `source-map-js` (alta, vía `vite` y `tailwindcss`), ambas herramientas de desarrollo. Se actualizan en la primera fase a la versión corregida con al menos 7 días de publicada, y RS16.a se anota como reabierta y vuelta a mitigar. Si en ese momento no hubiera versión elegible, se registra una excepción en `docs/security.md` con motivo, aprobación del usuario y vencimiento a 14 días, y la tarea queda bloqueada hasta entonces. `composer audit --locked` no reporta nada.
- **P14:** desviación aceptada por el usuario el 2026-10-06.
- **P15:** texto aprobado tal cual por el usuario el 2026-10-06.
- Ninguna decisión requiere ADR: no hay dependencias nuevas ni cambios de arquitectura.

## Impacto en arquitectura
Al implementar (tarea T090) habrá que actualizar en `docs/architecture.md`:
- **Frontend:** tokens en `@theme` sin paleta por defecto, componentes `x-ui.*`, `resources/js/ui/`, vistas de error.
- **Backend:** middlewares con vista en rutas web, ruta de respaldo, respuestas JSON fijas de 403 y 404 web.
- **Deuda técnica y riesgos observados:** retirar lo que esta spec resuelve.

También: `docs/security.md` (fila de las rutas web y RS16.a), `AGENTS.md` (`npm run test:ui` y la regla de solo tokens), `docs/deployment.md` (paso de CI y purga de caché) y `docs/roadmap.md` al liberar.

## Riesgos y mitigaciones
- **Regresiones al migrar 60 vistas y 37 archivos JS.** Mitigación: migración por grupos con Pest y `ui-audit` en verde al cerrar cada tarea de pantalla; los diálogos conservan sus `id` y atributos `data-*`.
- **Retirar la paleta por defecto rompe el aspecto de lo no migrado.** Mitigación: bloque transitorio de nombres antiguos, que se elimina en la fase 8 y cuya permanencia hace fallar un test.
- **`<dialog>` cambia cómo se abren unos 25 diálogos.** Mitigación: un único módulo que acepta el marcado antiguo y el nuevo mientras dura la migración; `ui-audit` abre cada diálogo en cada pantalla que lo monta al cerrar cada tarea.
- **Test de navegador inestable en CI.** Mitigación: esperas por condición; dos ejecuciones en `/implement` y `/review`; el paso de CI se añade en la última fase.
- **El texto oscuro sobre el rosa cumple por poco (4.76:1; el máximo con negro es 5.3:1).** Mitigación: `DesignTokensTest` fija el par; el rosa se reserva para la acción principal.
- **La tinta casi negra y los bordes más oscuros cambian el aspecto más de lo que sugiere "solo paleta".** Mitigación: verificación manual por grupo, con confirmación del usuario.
- **`UiAuditSeeder` sobre la base local de desarrollo.** Mitigación: es idempotente, marca lo que crea, `ui:audit-data --clean` retira solo eso y solo corre en `local` y `testing`; las tres cosas tienen test.
- **Caché de Cloudflare con el favicon vacío anterior.** Mitigación: purga manual tras el deploy.
- **Spec 013 (QR por cita), aprobada y sin plan:** toca Appointments, no las vistas de la agenda; cambiará el 403 del paciente por 401 (ver "Qué ve cada rol").
