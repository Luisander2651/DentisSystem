---
spec: 016-sistema-de-diseno-aplicado
status: approved
constitution_version: 1.1.2
created: 2026-10-06
---

# Plan · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

## Enfoque técnico
Los colores, tamaños y radios pasan a declararse una sola vez como tokens en `@theme` de
`resources/css/app.css` (Tailwind 4 genera de ahí las utilidades `bg-primary`, `text-ink`…), con los
valores de la variante **B · Neutra** de [design/propuesta.html](design/propuesta.html), elegida por
el usuario. Las 46 vistas y los 10 archivos JS que hoy escriben colores a mano se migran por grupos de
pantallas a esos tokens y a cuatro componentes Blade compartidos: `x-ui.button`, `x-ui.input`,
`x-ui.brand` (nuevo) y `x-ui.dialog` (nuevo, sobre el elemento nativo `<dialog>`, que ya da foco
atrapado, cierre con Escape y devolución del foco). Los rechazos de acceso de las pantallas web
pasan a vistas propias (`errors/403`, `errors/404`) sin tocar las respuestas de `/api/v1`. Los
arreglos del móvil se resuelven con las utilidades responsivas de Tailwind, sin cambiar la
disposición a 1440 px. No hay migraciones, ni endpoints nuevos, ni dependencias nuevas.

La verificación tiene dos mitades: lo que se puede comprobar leyendo el código o la respuesta HTML
va en Pest; lo que solo existe en un navegador (contraste pintado, tamaños, desborde, foco,
diálogos) lo mide un script propio con Node y Chrome sin interfaz, el mismo método de la revisión a
390 px del 2026-10-06, que corre en local y en CI.

La spec es grande (34 criterios, unas 60 vistas). Se mantiene en una sola por decisión del usuario
(2026-10-04) y se planea en ocho fases entregables por separado dentro de la misma rama (ver
"Rollout").

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Spec 016 `approved` el 2026-10-06; todo cambio de este plan traza a un criterio. | manual: `python .ai/bin/aidd.py status`; `/analyze` antes de implementar |
| P2 Test que falla antes y pasa después | ✅ | Cada criterio tiene al menos un test (Trazabilidad). Los de comportamiento y de código van en Pest (`tests/Modules/Core`); los que solo se pueden medir en un navegador van en `tests/Browser/ui-audit.mjs` (decisión del usuario, 2026-10-06). No hay value objects nuevos ni modificados. | test: suite Pest + `npm run test:ui`, ambos en CI |
| P3 Capas del módulo | ✅ | Solo cambia presentación (`resources/`) y dos piezas de `app/Core` (un middleware y un comando de consola de pruebas). Ningún `Domain/` se toca ni se añaden dependencias entre módulos. | manual: `/review` del diff; `grep` de `Infrastructure` en `Domain/` sin resultados nuevos |
| P4 Contrato de API primero | ✅ | Ningún endpoint de `/api/v1` cambia. `OnlyAdmin` sigue respondiendo el mismo JSON y el mismo 403 en `api/*`. | test: `OnlyAdminApiContractTest` |
| P5 Autorización en el servidor | ✅ | No cambia quién puede qué. Cambia la respuesta de `OnlyAdmin` en rutas web (de JSON a vista 403), así que cada ruta web de administrador lleva su test de acceso denegado por actor. | test: `WebAccessDeniedPagesTest` (dataset ruta × actor) |
| P6 Validación de entrada con FormRequest | ➖ | Ningún endpoint cambia su entrada. | — |
| P7 Errores sin detalles internos | ✅ | Las vistas 403 y 404 muestran un texto fijo en español; nunca el mensaje de la excepción, la ruta pedida ni trazas. Los 500 no cambian. | test: `WebAccessDeniedPagesTest` y `NotFoundPageTest` afirman que el cuerpo no contiene el mensaje interno ni la ruta |
| P8 Secretos fuera del repositorio | ✅ | Sin variables nuevas ni `env()`. El comando de sesión de prueba no guarda credenciales: emite un token de un usuario sembrado y solo existe fuera de producción. | lint: `gitleaks` en CI; test: `UiAuditSessionCommandTest` |
| P9 Migraciones reversibles | ➖ | Sin migraciones. | — |
| P10 Dependencias nuevas con ADR | ✅ | Ninguna dependencia nueva. Se actualizan dos transitivas de desarrollo vulnerables (`shell-quote`, `source-map-js`) a versiones con al menos 7 días de publicadas; actualizar no requiere ADR. | manual: diff de `package.json` (sin cambios) y `package-lock.json` en `/review`; lint: `npm audit --audit-level=high` en CI |
| P11 Datos sensibles y modelo de amenazas | ✅ | Las pantallas con datos personales y de salud cambian de aspecto, no de contenido. CA32 retira de la galería el nombre y la dirección internos del archivo. Sin logs nuevos. Modelo de amenazas incluido. | manual: `aidd.py validate`; `/review` de `Log::` (ninguno nuevo) |
| P12 Producción con aprobación y rollback | ✅ | Rollout con rollback por tag, sin migraciones. Las vulnerabilidades altas y críticas previas se corrigen en la primera tarea. | manual: `/release` exige la confirmación; lint: SCA en CI |
| P13 Lógica de negocio en el backend | ✅ | Los cambios de JS son de presentación (diálogos, calendario, listas). El texto por rol de la insignia se decide en Blade con el rol del servidor; ocultar no sustituye ninguna comprobación. | manual: `/review` del JS y Blade tocados |
| P14 Trazabilidad | ❌ aceptado: la página "Sin permiso" no emite el evento de auditoría de acceso denegado porque el registro de auditoría aún no existe (objetivo 5 del roadmap) — aprobado por el usuario el 2026-10-06 | Misma desviación aceptada en la spec 014. El punto único de rechazo web (`OnlyAdmin`, `EnsureActiveStaff`) no cambia de lugar, así que la auditoría se añadirá ahí sin tocar esta spec. | — |

Enmienda que esta spec propone (CA24), **fuera** de la versión 1.1.2 evaluada arriba: un principio
P15 "La interfaz usa solo el sistema de diseño" (ver "Contratos y datos" → "Enmienda de la
constitución"). El plan ya lo cumple: su verificación es `OnlyDesignTokensTest`.

## Cambios por módulo
Todas las rutas existen salvo las marcadas (nuevo).

| Módulo | Cambio | Riesgo |
|---|---|---|
| Dependencias · `package-lock.json` | `npm audit fix` para `shell-quote` y `source-map-js` (transitivas de desarrollo). `package.json` no cambia. | bajo |
| Sistema de diseño · `docs/design/system.md`, `docs/design/system.html`, `.ai/project.yaml` | Primera tarea de código: tokens y componentes nuevos, `source: chosen`, versión 2.0.0, deuda `DS` marcada "la resuelve 016". El sistema extraído se archiva en `docs/design/history/`. | bajo |
| Constitución · `docs/constitution.md` | P15 nuevo, versión 1.2.0, entrada en "Enmiendas" (texto aprobado por el usuario, 2026-10-06). | bajo |
| Tokens · `resources/css/app.css` | Bloque `@theme` con los colores, radios y tamaños de control; estilo de foco único en `@layer base`; se desactivan los nombres de color que ya no se usan. | medio: un token mal nombrado deja utilidades sin generar |
| Componentes · `resources/views/components/ui/button.blade.php`, `input.blade.php`, `h1.blade.php`, `page-hero.blade.php` | Botón con variantes `primary`, `secondary`, `danger`, `icon` y un solo tamaño (44 px, radio de control). Campo de 44 px y letra de 16 px, con `aria-describedby` hacia su error. `page-hero` pinta el título como `h1` y recibe la insignia según el rol. | medio: se usan en todas las pantallas |
| Componentes · `resources/views/components/ui/brand.blade.php` (nuevo), `dialog.blade.php` (nuevo), `stat.blade.php` (nuevo), `resources/js/ui/dialog.js` (nuevo) | Marca (icono + "Dentissa"), diálogo base sobre `<dialog>` con título enlazado y pie fijo, contador compacto, y el módulo que abre y cierra diálogos por atributos `data-dialog-*`. | medio |
| Marca · `public/images/brand/` (nuevo), `public/favicon.ico`, `resources/views/layouts/*.blade.php` | Icono (blanco sobre rosa) en 64, 192 y 512 px, logo horizontal en 520 px de ancho, `favicon.ico` real y `apple-touch-icon`; etiquetas `<link rel="icon">` en los cinco layouts. Se copian desde `storage/app/public/Logos_Melissa_Lopez/`, que no se versiona. | bajo |
| Sitio público · `resources/views/layouts/landing.blade.php`, `components/landing/{nav,footer}.blade.php`, `pages/landing/{inicio,acerca,galeria,contacto}.blade.php`, `resources/js/pages/landing/{inicio,galeria}.js` | Tokens, `x-ui.brand` en la barra, logo completo en el pie, insignias del inicio dentro del ancho (CA28), botón de WhatsApp con texto oscuro. | medio |
| Acceso · `resources/views/layouts/app.blade.php`, `pages/auth/*.blade.php`, `resources/js/pages/auth/*.js` | Tokens, logo completo, etiqueta "Contraseña", acentos. | bajo |
| Panel (marco) · `resources/views/layouts/{dashboard,admin,patient}.blade.php`, `components/ui/sidebar.blade.php`, `pages/dashboard.blade.php`, `resources/js/pages/dashboard.js` | Tokens, `x-ui.brand`, cabecera sin hueco, elemento activo con acento, rol en español, insignia por rol. | medio |
| Pacientes y usuarios · `pages/patients/index.blade.php`, `pages/usuarios/index.blade.php`, `components/ui/{create,edit}-{patient,user}-modal.blade.php`, `confirm-delete-modal.blade.php`, `resources/js/pages/{patients,usuarios}/*.js` | Tokens, botones y campos compartidos, diálogos sobre `x-ui.dialog`, contadores en una fila y listado dentro de la primera pantalla. | medio |
| Expedientes · `pages/records/index.blade.php`, `components/records/*.blade.php` (4), `resources/js/pages/records/index.js` | Igual que el anterior, más: en móvil el expediente elegido se muestra en lugar de la lista, con un enlace para volver (CA31), y cada tabla pasa a filas de etiqueta y valor por debajo de `md` (CA27). | alto: la pantalla tiene tests de permisos (`RecordsScreenTest`) y lógica de edición por rol |
| Tratamientos · `pages/tratamientos/index.blade.php`, `resources/js/pages/tratamientos/index.js` | Tokens, diálogos, textos sin jerga técnica. | bajo |
| Contenido · `pages/contenido/**` (16 vistas), `resources/js/pages/contenido/**` (14 archivos) | Tokens, un solo encabezado, 11 diálogos sobre `x-ui.dialog`, tarjeta de galería que cabe en una pantalla y se identifica por su descripción. | alto: es el grupo con más archivos |
| Agenda · `pages/agenda/index.blade.php`, `components/calendar/*.blade.php` (6), `resources/js/pages/agenda/*.js` (4) | Tokens, filtros en una fila desplazable, calendario con número de citas por día y lista del día debajo, 5 diálogos sobre `x-ui.dialog`, tarjeta "Citas para hoy" sin hueco; se retira la rama "ver y editar" inalcanzable de la tarjeta de cita. | alto: es la pantalla rota hoy y la de más JS |
| Errores web · `app/Core/Middlewares/OnlyAdmin.php`, `routes/web.php`, `resources/views/errors/{403,404}.blade.php` (nuevos), `resources/views/layouts/error.blade.php` (nuevo) | `OnlyAdmin` responde `abort(403)` fuera de `api/*`, como ya hace `EnsureActiveStaff`. `Route::fallback` en `web.php` para que el 404 pase por el grupo web y sepa si hay sesión. | medio: cambia una respuesta que hoy es JSON |
| Limpieza · `resources/views/components/ui/table.blade.php`, `resources/views/welcome.blade.php` | Se eliminan (decisión del usuario, 2026-10-06). | bajo |
| Pruebas · `tests/Modules/Core/Unit/Design/*` (nuevo), `tests/Modules/Core/Integration/*` (nuevos), `tests/Browser/` (nuevo), `database/seeders/UiAuditSeeder.php` (nuevo), `app/Core/Console/UiAuditSessionCommand.php` (nuevo), `package.json` (script `test:ui`), `.github/workflows/tests.yml` | Tests del sistema, de las páginas de error y de estructura; script de navegador con sus datos sembrados; paso nuevo en CI. | medio: un test de navegador inestable frena los PR |

## Contratos y datos

### Endpoints y datos
Sin endpoints nuevos, sin cambios en `/api/v1` y sin migraciones.

| Petición | Hoy | Con esta spec |
|---|---|---|
| Web con sesión, rol sin permiso (`only.admin` o `staff:…`) | `only.admin`: JSON `{"error": "Only administrators can access this resource."}` con 403. `staff`: página 403 por defecto de Laravel, en inglés | 403 con la vista `errors.403` |
| Web, dirección inexistente | 404 con la página por defecto de Laravel | 404 con la vista `errors.404` |
| Web sin sesión, pantalla protegida | redirección a `/login` | igual |
| `api/*` con rol sin permiso | JSON `{"error": …}` con 403 | igual |
| `api/*`, ruta inexistente | JSON 404 | igual |

### Tokens (variante B · Neutra)
Fuente: [design/propuesta.html](design/propuesta.html). Contraste calculado por la propia página.

| Token | Valor | Uso | Contraste |
|---|---|---|---|
| `--color-primary` | `#d75078` | Acción principal y acentos | — |
| `--color-secondary` | `#f2b0a6` | Bordes de avisos e insignias, fondos decorativos | — |
| `--color-ink` | `#0b1120` | Texto principal y texto sobre el primario | 4.76:1 sobre primario · 18.83:1 sobre superficie |
| `--color-text-muted` | `#556274` | Texto secundario | 6.20:1 sobre superficie · 5.93:1 sobre fondo · 5.31:1 sobre primario suave |
| `--color-bg` | `#f8fafc` | Fondo base | — |
| `--color-surface` | `#ffffff` | Tarjetas, diálogos, campos | — |
| `--color-primary-soft` | `#fbe9ee` | Elemento activo y avisos | tinta encima: 16.13:1 |
| `--color-border` | `#e2e8f0` | Separadores y bordes de tarjeta (decorativo) | — |
| `--color-border-field` | `#7c8aa0` | Borde de campo | 3.50:1 sobre superficie · 3.35:1 sobre fondo |
| `--color-focus` | `#0b1120` | Contorno de foco, 3 px con 2 px de separación | 18.83:1 sobre superficie · 4.76:1 junto al primario |
| `--color-danger` / `-bg` | `#b91c1c` / `#fef2f2` | Eliminar y errores | 5.91:1 · blanco encima 6.47:1 |
| `--color-success` / `-bg` | `#047857` / `#ecfdf5` | "Activo", "Completada" | 5.21:1 |
| `--color-warning` / `-bg` | `#b45309` / `#fffbeb` | "Asignada" | 4.84:1 |
| `--color-info` / `-bg` | `#1d4ed8` / `#eff6ff` | "Reprogramada" | 6.16:1 |
| `--radius-control` · `--radius-card` · `--radius-pill` | 12 px · 24 px · 999 px | Botones y campos · tarjetas y diálogos · insignias y filtros | — |
| `--size-control` | 44 px | Alto de botón, campo, filtro y elemento de menú | — |
| `--text-field` · `--text-min` | 16 px · 12 px | Letra de campo · texto más pequeño permitido | — |

El rosa como texto queda prohibido: sobre blanco da 3.96:1. Solo se usa como fondo de la acción
principal y como acento no textual (subrayado, borde del elemento activo), donde basta 3:1.

### Cambios a incorporar al sistema
La primera tarea de código actualiza `docs/design/system.md` y `system.html` antes de tocar vistas:

- **Color:** la tabla de arriba sustituye a la actual. Desaparecen `--color-primary-strong`, `--color-on-primary`, `--color-text-faint`, `--color-border-brand`, `--color-focus-ring` y `--color-whatsapp`.
- **Radios y tamaños:** escala cerrada de la tabla; desaparecen los radios de 6, 8, 16 y 32 px y las alturas de 24 a 48 px.
- **Componentes:** botón (4 variantes, un tamaño), campo, marca, diálogo base, contador, filtro, insignia de estado, registro (etiqueta y valor), página de error.
- **Reglas de uso:** una acción principal por pantalla; texto nunca en rosa; ningún literal de color o tamaño fuera de los tokens.
- **Frontmatter:** `source: chosen`, `version: 2.0.0`; la elección de la variante B en "Decisiones" (tipo `diseño`, fuente `usuario`); deuda `DS1`–`DS11`, `DS13`, `DS14` → "la resuelve 016".
- **Capturas:** las 128 de `docs/design/capturas/` se rehacen al cerrar (con `/init --upgrade --redo-design`), junto con las dos pantallas nuevas.

### Pantallas y estados

| Pantalla | Componentes | Estados | Accesibilidad medible |
|---|---|---|---|
| Sin permiso (nueva) | página de error, logo, botón principal | único | un `h1`; el botón lleva a `/dashboard`; sin datos de la pantalla pedida |
| No encontrada (nueva) | página de error, logo, botón principal | con sesión → `/dashboard`; sin sesión → `/` | un `h1` |
| Diálogos (unos 25) | `x-ui.dialog` | abierto, con error de validación, enviando | `<dialog>` con `aria-labelledby` a su título; foco dentro; Escape cierra; el foco vuelve al control que lo abrió; ancho ≤ pantalla; alto ≤ pantalla con cuerpo desplazable y pie fijo |
| Listados (pacientes, expedientes, tratamientos, contenido, usuarios) | encabezado, contadores, buscador, filtros, tarjetas | con datos, vacío, cargando, error | primer elemento a ≤ 844 px del inicio a 390 px; controles ≥ 44 × 44 px |
| Expediente | encabezado, registros | con datos, vacío por sección, formulario abierto | a 390 px sin desplazamiento lateral; el expediente elegido queda a la vista |
| Agenda | filtros, calendario, lista del día, diálogos | mes sin citas, con citas, día elegido | a 390 px la página mide 390 px; cada día con citas muestra su número; el nombre accesible del día incluye el número de citas |
| Todas | — | — | un solo `h1`; un solo estilo de foco; texto ≥ 12 px; campos ≥ 16 px |

Nota: revisar con el plugin `design` (`design:design-handoff`) antes de `/implement`, y usar
`design:ux-copy` para los textos de las páginas de error, los estados vacíos y las confirmaciones.

### Qué ve cada rol
No cambian los permisos; cambian textos que hoy son iguales para todos.

| Elemento | Administrador | Asistente | Doctor | Paciente |
|---|---|---|---|---|
| Insignia del encabezado | "Panel de administración" | "Panel de asistente" | "Panel clínico" | "Mi cuenta" |
| Rol en la cabecera y en el inicio | Administrador | Asistente | Doctor | Paciente |
| Pantalla sin permiso | no aplica | 403 en agenda, pacientes, usuarios, tratamientos y contenido | igual que asistente | 403 en todas las anteriores y en expedientes |
| Botón de la página 403 | — | a su inicio | a su inicio | a su inicio |

### Enmienda de la constitución (CA24)
Texto aprobado por el usuario el 2026-10-06 junto con este plan (versión 1.2.0):

> **P15. La interfaz usa solo el sistema de diseño.**
> **Regla:** Toda vista y todo JS de página nuevo o modificado toma colores, radios y tamaños de control de los tokens de `resources/css/app.css` y usa los componentes de `docs/design/system.md`. Ningún color literal ni valor arbitrario de color fuera del archivo de tokens. Un valor que falte se añade primero al sistema.
> **Cómo se verifica:** `OnlyDesignTokensTest` y `DesignTokensTest` en la suite Pest; `npm run test:ui` en CI.
> **Por qué:** La interfaz llegó a tener 8 rosas en unas 620 apariciones y contrastes insuficientes (deuda `DS1`–`DS3`).

## Estrategia de pruebas

**Pest, unitarias (`tests/Modules/Core/Unit/Design/`):**
- `DesignTokensTest`: lee la tabla Color de `docs/design/system.md` y exige los mismos valores en `@theme`; calcula el contraste de cada par declarado (texto ≥ 4.5:1, borde de campo y foco ≥ 3:1).
- `OnlyDesignTokensTest`: recorre `resources/views` y `resources/js` y falla ante un color literal (`#…`, `rgb(`, `hsl(`), una utilidad arbitraria de color (`-[#…]`), un tamaño de letra arbitrario menor de 12 px, `focus:outline-none` y cualquiera de los rosas antiguos. Cada regla lleva un control positivo (detecta un literal de ejemplo).
- `UiCopyTest`: falla ante las palabras sin acento del inventario (`Gestion`, `clinica`, `Sesion`, `accion`, `contrasena`, `informacion`, `Descripcion`, `Duracion`, `direccion`, `medicos`…) y ante términos técnicos visibles (`API`, `backend`, `rutas admin`), en vistas y en textos de JS.
- `UnusedUiTest`: `x-ui.table`, `welcome.blade.php` y la rama "ver y editar" no existen.

**Pest, integración (`tests/Modules/Core/Integration/`):**
- `WebAccessDeniedPagesTest`: dataset ruta web × actor sin permiso → 403, vista `errors.403`, texto en español, enlace a `/dashboard`, sin el mensaje interno ni datos de la pantalla.
- `NotFoundPageTest`: con y sin sesión; enlace correcto; sin la ruta pedida en el cuerpo.
- `OnlyAdminApiContractTest`: `api/*` conserva el JSON y el 403 de hoy.
- `GuestRedirectTest`: sin sesión → `/login` en cada pantalla protegida.
- `PageStructureTest`: cada pantalla, por rol, tiene exactamente un `h1`, la marca con su texto alternativo y el `<link rel="icon">`.
- `RoleCopyTest`: insignia y rol por actor (tabla "Qué ve cada rol").
- `UiAuditSessionCommandTest`: el comando no está registrado con `APP_ENV=production`.
- Tests existentes que afirman respuestas de pantallas (`StaffNavigationTest`, `RecordsScreenTest`, `WebUnexpectedErrorTest`) se actualizan donde cambie el marcado, sin cambiar lo que comprueban.

**Navegador (`tests/Browser/ui-audit.mjs`, `npm run test:ui`):** Node y Chrome sin interfaz por el
protocolo DevTools, sin dependencias. Recorre las pantallas y diálogos del inventario a 390 × 844 y
1440 × 900 con los datos de `UiAuditSeeder` y una sesión por rol, y falla si alguna medida incumple
su criterio: desborde horizontal, controles menores de 44 px, alturas y radios fuera de la escala,
letra de campo y de texto, contraste del texto pintado, foco, diálogos (ancho, alto, foco atrapado y
devuelto, título anunciado), calendario, registros del expediente, recortes, posición del primer
elemento del listado, alto de tarjetas. Los tests nuevos de navegador se ejecutan dos veces en
`/implement` y `/review`; uno intermitente se corrige, no se reintenta en silencio.

**Composición a 1440 px (CA22):** antes de tocar vistas se guarda una línea base por pantalla
(orden de secciones, encabezados y acciones) en `tests/Browser/baseline-1440.json`; el script falla
si cambia. Además, verificación manual contra las capturas actuales de `docs/design/capturas/`.

**Manual:** lector de pantalla en un diálogo y en una página de error; aspecto de cada grupo de
pantallas contra `design/propuesta.html` a 390 y 1440 px.

## Modelo de amenazas
La spec no añade datos, endpoints ni entradas, pero cambia cómo responde el servidor a un acceso
denegado y añade una herramienta de pruebas con sesión.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: al cambiar `OnlyAdmin`, una pantalla de administrador queda accesible a otro rol o a un paciente | A01:2025 Broken Access Control | `OnlyAdmin`, `routes/web.php` | El middleware conserva sus tres comprobaciones; solo cambia la forma de la respuesta | `WebAccessDeniedPagesTest` (cada ruta × asistente, doctor, paciente, staff inactivo) |
| TM2 | Information disclosure: la página 403 o 404 revela el mensaje interno, la ruta, trazas o datos de la pantalla pedida | A10:2025 Mishandling of Exceptional Conditions | `errors/403`, `errors/404` | Texto fijo; la vista no imprime la excepción ni la URL | `WebAccessDeniedPagesTest`, `NotFoundPageTest` |
| TM3 | Tampering: la dirección inexistente se refleja en la página y permite inyectar marcado | A05:2025 Injection | `errors/404`, `Route::fallback` | La vista no refleja ninguna parte de la petición | `NotFoundPageTest` con una ruta que contiene marcado |
| TM4 | Tampering: el cambio de `OnlyAdmin` altera el contrato de la API que consumen el JS y la futura app | A01:2025 Broken Access Control | `OnlyAdmin` en `api/*` | La rama `api/*` no cambia | `OnlyAdminApiContractTest` |
| TM5 | Elevation: el comando que emite una sesión de prueba se usa en producción para obtener una sesión de administrador | A07:2025 Authentication Failures | `UiAuditSessionCommand` | El comando solo se registra en entornos `local` y `testing`, y solo emite sesiones de los usuarios de `UiAuditSeeder` | `UiAuditSessionCommandTest` |
| TM6 | Tampering: las actualizaciones de dependencias introducen un paquete comprometido | A03:2025 Software Supply Chain Failures | `package-lock.json` | Solo versiones con al menos 7 días de publicadas; sin paquetes nuevos; lockfile revisado | lint: `npm audit --audit-level=high` y revisión del diff del lockfile en `/review` |
| TM7 | Information disclosure: un visitante sin sesión distingue qué pantallas protegidas existen | A01:2025 Broken Access Control | rutas web protegidas | Sin sesión, toda pantalla protegida redirige igual a `/login` | `GuestRedirectTest` |

Casos de abuso de la spec: CA18 → TM1 y TM2; CA20 → TM2 y TM3.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: paleta nueva, sin rosas anteriores | Tokens; migración de todas las vistas y JS | `OnlyDesignTokensTest` (rosas antiguos); verificación manual: cada grupo a 390 y 1440 px contra `design/propuesta.html` |
| CA2: cada color con nombre, una sola vez | `resources/css/app.css` | `OnlyDesignTokensTest`, `DesignTokensTest` |
| CA3: una acción principal y un solo hover | `x-ui.button` | `OnlyDesignTokensTest`; `ui-audit` (fondo y hover del botón principal idénticos en todas las pantallas) |
| CA4: texto oscuro sobre el primario | `x-ui.button` variante `primary` | `DesignTokensTest` (par tinta/primario ≥ 4.5); `ui-audit` (color pintado) |
| CA5: enlaces y activo en tinta, rosa como acento | Tokens; `sidebar`, `nav`, enlaces | `OnlyDesignTokensTest` (sin `text-primary`); `ui-audit` (contraste de enlaces) |
| CA6: texto ≥ 4.5:1 | Tokens; estados; WhatsApp | `DesignTokensTest`; `ui-audit` (contraste del texto pintado, por pantalla) |
| CA7: borde de campo ≥ 3:1 | `x-ui.input`, campos | `DesignTokensTest`; `ui-audit` |
| CA8: un solo foco visible ≥ 3:1 | `@layer base`; se retiran los `focus:outline-none` | `OnlyDesignTokensTest`; `ui-audit` (contorno idéntico al enfocar cada control) |
| CA9: controles táctiles ≥ 44 × 44 px a 390 px | `x-ui.button`, `x-ui.input`, filtros, menú, iconos | `ui-audit` |
| CA10: misma altura y radio por tipo de control | Tokens de tamaño y radio; componentes | `ui-audit` (conjunto de alturas y radios por tipo) |
| CA11: agenda sin desborde a 390 px | `pages/agenda`, `components/calendar/grid` | `ui-audit` (ancho de página; abrir un día) |
| CA12: diálogo anuncia título, atrapa y devuelve el foco | `x-ui.dialog`, `resources/js/ui/dialog.js` | `ui-audit` (nombre accesible, foco al abrir y al cerrar); verificación manual: lector de pantalla en "Nueva cita" |
| CA13: diálogo no supera el alto de la ventana | `x-ui.dialog` | `ui-audit` a 390 × 844 y 1440 × 900 |
| CA14: un título principal por pantalla | `page-hero`, layouts | `PageStructureTest` |
| CA15: mismo logo, texto alternativo, icono de pestaña | `x-ui.brand`, `public/images/brand`, layouts | `PageStructureTest`; verificación manual: pestaña del navegador |
| CA16: ortografía del español | Vistas y textos de JS | `UiCopyTest` |
| CA17: ninguna pieza sin uso | Eliminaciones | `UnusedUiTest` |
| CA18 (abuso): página "Sin permiso" | `OnlyAdmin`, `errors/403` | `WebAccessDeniedPagesTest` |
| CA19: página "No encontrada" | `Route::fallback`, `errors/404` | `NotFoundPageTest` |
| CA20 (abuso): sin datos en crudo, inglés ni detalles técnicos | `errors/*`, `OnlyAdmin` | `WebAccessDeniedPagesTest`, `NotFoundPageTest` |
| CA21: sin sesión → iniciar sesión | sin cambio | `GuestRedirectTest` |
| CA22: composición conservada a 1440 px | Todas las vistas | `ui-audit` contra `baseline-1440.json`; verificación manual: cada grupo a 1440 px contra `docs/design/capturas/` |
| CA23: sistema y capturas actualizados, deuda resuelta | `docs/design/*` | manual: `aidd.py validate`; verificación manual: `system.html` muestra las pantallas nuevas |
| CA24: principio en la constitución | `docs/constitution.md` | `OnlyDesignTokensTest` (su verificación); manual: versión 1.2.0 y entrada en "Enmiendas" |
| CA25: citas legibles en el calendario | `components/calendar/grid`, `agenda/index.js` | `ui-audit` (sin texto truncado en días con citas) |
| CA26: diálogos dentro del ancho | `x-ui.dialog` | `ui-audit` a 390 px |
| CA27: registros del expediente sin tablas cortadas | `components/records/*` | `ui-audit` (sin desplazamiento lateral interno) |
| CA28: nada recortado ni truncado | `pages/landing/inicio`, selector de archivo | `ui-audit` (elementos con texto dentro del ancho) |
| CA29: campos ≥ 16 px, texto ≥ 12 px | `x-ui.input`, tokens | `OnlyDesignTokensTest` (tamaños arbitrarios); `ui-audit` |
| CA30: textos por rol, en español, sin jerga | `page-hero`, `sidebar`, `dashboard`, `login` | `RoleCopyTest`, `UiCopyTest` |
| CA31: expediente a la vista al elegir paciente | `pages/records/index`, `records/index.js` | `ui-audit` (posición del expediente tras elegir) |
| CA32: tarjeta de galería | `pages/contenido/galeria/index`, `galeria/index.js` | `ui-audit` (alto ≤ 844 px); `PageStructureTest` (sin dirección interna en el HTML) |
| CA33: primer elemento del listado en la primera pantalla | Encabezado, `x-ui.stat`, filtros | `ui-audit` |
| CA34: sin espacio vacío sin función | `pages/contenido/index`, `agenda`, `sidebar` | `ui-audit` (un encabezado; tarjeta vacía ≤ 320 px; cabecera sin hueco) |
| RNF: WCAG 2.1 AA en lo cubierto | Todo | los de CA6–CA9, CA12–CA14 y CA29 |
| RNF: sin desplazamiento horizontal a 390 y 1440 px | Todo | `ui-audit` en cada pantalla |
| RNF: sin regresiones | Todo | suite Pest completa en CI |
| RNF: logo e icono servidos por la aplicación | `public/images/brand` | `PageStructureTest` (rutas relativas, sin dominios externos) |
| TM1 | `OnlyAdmin` | `WebAccessDeniedPagesTest` |
| TM2 | `errors/*` | `WebAccessDeniedPagesTest`, `NotFoundPageTest` |
| TM3 | `errors/404` | `NotFoundPageTest` |
| TM4 | `OnlyAdmin` | `OnlyAdminApiContractTest` |
| TM5 | `UiAuditSessionCommand` | `UiAuditSessionCommandTest` |
| TM6 | `package-lock.json` | lint: `npm audit --audit-level=high` en CI |
| TM7 | rutas web | `GuestRedirectTest` |

## Observabilidad
- Logs nuevos: ninguno. Las páginas 403 y 404 no registran nada nuevo.
- Eventos de auditoría: ninguno. El acceso denegado no se audita hasta el objetivo 5 del roadmap (desviación de P14 aceptada por el usuario el 2026-10-06).
- Métricas y alertas: sin cambios. Las alertas de 5xx siguen pendientes de la spec 017.
- Cómo se verifica tras el deploy: `/pagina-que-no-existe` responde 404 con la marca; con un usuario asistente, `/agenda` responde 403 con la página "Sin permiso"; en Loki no aparecen 5xx nuevos en los 15 minutos siguientes.

## Rollout
- Feature flag: no aplica. El cambio es de presentación sobre un prototipo sin datos ni usuarios reales, y dos sistemas de tokens conviviendo detrás de un flag duplicarían las vistas.
- Orden de despliegue: una sola imagen, sin migraciones. Dentro de la rama, ocho fases que dejan la app usable en cada una: (1) dependencias y línea base, (2) sistema, constitución y tokens, (3) componentes, (4) sitio público y acceso, (5) marco del panel, pacientes, usuarios y tratamientos, (6) expedientes y contenido, (7) agenda, (8) errores, textos, limpieza, capturas. Entre la fase 2 y la 7 conviven pantallas migradas y sin migrar; `OnlyDesignTokensTest` empieza con una lista de archivos pendientes que se vacía fase a fase y debe quedar vacía al terminar.
- Compatibilidad: convive con la versión anterior durante el deploy; no cambia la API ni los datos. Una pestaña abierta con el JS anterior sigue funcionando hasta recargar.
- Rollback: redesplegar el tag anterior (`v0.1.0`) siguiendo "Rollback" de `docs/deployment.md`. No hay nada que revertir en la base.
- Después del deploy: purgar la caché de Cloudflare (hoy manual), porque cambian `favicon.ico` y los archivos de `public/images/brand/`, que no llevan huella en el nombre.
- Métricas a vigilar tras el deploy: tasa de 5xx y número de 403 y 404 en Loki; carga correcta de CSS y JS (sin 404 de recursos).

## Decisiones (→ ADR si son arquitectónicas)
- **Una sola spec** con 34 criterios; no se divide (decisión del usuario, 2026-10-04). Se compensa con las ocho fases.
- **Variante B · Neutra** de `design/propuesta.html`: tinta `#0b1120` y los grises fríos actuales; solo cambian los rosas (decisión del usuario, 2026-10-06; tipo diseño).
- **Marca:** el consultorio se llama Dentissa y los textos lo conservan; el logo es el de la doctora. En las barras va el icono (blanco sobre rosa) junto a "Dentissa"; el logo completo, en el pie del sitio, las pantallas de acceso y las páginas de error (decisión del usuario, 2026-10-06). Se elige el icono con fondo porque el de trazo fino no se distingue a 40 px.
- **Tokens en `@theme` de Tailwind 4**, no en un archivo CSS aparte ni en clases propias: es el mecanismo que el stack ya trae y genera las utilidades. Alternativa descartada: clases `.btn-*` en CSS, que duplican lo que hacen los componentes Blade.
- **Diálogo sobre `<dialog>` nativo**, no un componente con `div` y atrapado de foco a mano ni una librería: el navegador da foco atrapado, Escape, fondo inerte y devolución del foco sin dependencias. Comprobado en `design/propuesta.html` que el marcado cabe a 390 px con pie fijo.
- **Pruebas de interfaz con script propio (Node + Chrome)**, sin dependencias ni ADR (decisión del usuario, 2026-10-06). Comprobado con una prueba de concepto el 2026-10-06: el mismo método recorrió y midió las 65 pantallas y diálogos a 390 px. Alternativa descartada: plugin de navegador de Pest, que añade dependencias.
- **Sesión para las pruebas de navegador:** un comando de consola que emite un token de un usuario sembrado, registrado solo en `local` y `testing`. Alternativa descartada: contraseñas de prueba en el repositorio.
- **404 por `Route::fallback`:** una dirección inexistente no pasa hoy por el grupo web, así que la vista no puede saber si hay sesión. El fallback la hace pasar y permite elegir el destino del enlace (CA19).
- **`OnlyAdmin` en web responde con `abort(403)`**, igual que `EnsureActiveStaff`; la rama `api/*` no cambia. Contrato visible: ningún test existente afirma el JSON de `only.admin` en rutas web.
- **Expediente en el móvil:** al elegir un paciente se muestra el expediente en lugar de la lista, con un enlace para volver; a 1440 px se conserva la disposición actual (CA22, CA31). Alternativa descartada: desplazar la página hasta el expediente, que deja la lista por encima y no resuelve listas largas.
- **Tablas del expediente:** por debajo de `md` cada registro se pinta como pares de etiqueta y valor; a partir de `md`, la tabla actual (CA27).
- **Calendario en el móvil:** cada día muestra el número de citas y, al tocarlo, la lista del día aparece debajo con hora, paciente y estado (CA25). A 1440 px se conservan las etiquetas actuales.
- **Piezas sin uso (DS13):** se retiran `x-ui.table`, `welcome.blade.php` y la rama "ver y editar" (decisión del usuario, 2026-10-06).
- **Vulnerabilidades previas:** se corrigen en la primera tarea (decisión del usuario, 2026-10-06). `composer audit --locked` no reporta ninguna; el aviso de `league/commonmark` venía del `vendor/` desactualizado del host, no del lockfile. Quedan dos de npm, ambas herramientas de desarrollo: `shell-quote` (crítica, vía `concurrently`) y `source-map-js` (alta, vía `vite` y `tailwindcss`). `source-map-js` 1.2.2 se publicó el 2026-09-30 y la corrección de `shell-quote` es reciente: se instala la versión corregida que tenga al menos 7 días en el momento de la tarea.
- **P14:** desviación aceptada por el usuario el 2026-10-06 (ver Constitution Check).
- **P15:** el texto de "Enmienda de la constitución" queda aprobado tal cual (decisión del usuario, 2026-10-06). Prohíbe los colores literales; los radios y tamaños de control salen de los tokens, sin prohibir otros valores arbitrarios puntuales. La enmienda se escribe en `docs/constitution.md` (versión 1.2.0) en la fase 2.
- Ninguna decisión requiere ADR: no hay dependencias nuevas ni cambios de arquitectura.

## Impacto en arquitectura
Al implementar (tarea T090) habrá que actualizar en `docs/architecture.md`:
- **Frontend:** tokens en `@theme`, componentes `x-ui.*` compartidos, `resources/js/ui/dialog.js` y las vistas de error.
- **Backend:** `OnlyAdmin` responde con vista en rutas web; `Route::fallback`.
- **Deuda técnica y riesgos observados:** retirar lo que esta spec resuelve.

También: `docs/security.md` (fila de las rutas web: 403 con vista), `AGENTS.md` (comando `npm run test:ui` y la regla de solo tokens) y `docs/roadmap.md` al liberar.

## Riesgos y mitigaciones
- **Regresiones funcionales al migrar unas 60 vistas y 37 archivos JS.** Mitigación: migración por grupos con la suite Pest y `ui-audit` en verde al cerrar cada fase; los diálogos conservan sus `id` y atributos `data-*` para no reescribir la lógica de cada página.
- **`<dialog>` cambia cómo se abren unos 25 diálogos.** Mitigación: un único módulo `dialog.js`; cada grupo se migra completo en su fase; `ui-audit` abre cada diálogo.
- **Test de navegador inestable en CI.** Mitigación: esperas por condición, no por tiempo; dos ejecuciones en `/implement` y `/review`; el paso de CI se añade en la última fase, cuando el script ya es estable en local.
- **El texto oscuro sobre el rosa cumple por poco (4.76:1; el máximo posible con negro es 5.3:1).** Mitigación: `DesignTokensTest` fija el par; el rosa se reserva para la acción principal.
- **La tinta casi negra y los bordes más oscuros cambian el aspecto más de lo que sugiere "solo paleta".** Mitigación: verificación manual por grupo contra `design/propuesta.html`, con tu confirmación.
- **Datos de prueba de `UiAuditSeeder` en una base equivocada.** Mitigación: el seeder se niega a correr con `APP_ENV=production`.
- **Caché de Cloudflare con el favicon vacío anterior.** Mitigación: purga manual tras el deploy (Rollout).
- **Conflicto con la spec 013 (QR por cita), aprobada y sin plan:** toca el módulo Appointments, no las vistas de la agenda. Si se planea antes de liberar la 016, sus pantallas deben nacer ya con los tokens.
