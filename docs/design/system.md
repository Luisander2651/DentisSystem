---
status: approved         # draft | approved (solo el usuario aprueba) — 2.0.0 aprobado por el usuario, 2026-10-08 (spec 016, T001)
source: chosen           # chosen: elegido entre opciones · extracted: documentado del código
version: 2.0.0
extracted_with: 1.11.2   # versión del plugin que extrajo el sistema 1.0.0 (archivado en history/)
html: docs/design/system.html
canvas: null
widths: [390, 1440]
created: 2026-10-04
updated: 2026-10-08
---

# Sistema de diseño de Dentissa

Sistema **elegido** (versión 2.0.0): la variante **B · Neutra** de la
[propuesta de la spec 016](../specs/016-sistema-de-diseno-aplicado/design/propuesta.html). Sustituye
al sistema 1.0.0, que describía la interfaz tal como estaba y queda archivado en
[history/system.1.0.0.md](history/system.1.0.0.md). Toda pantalla nueva o modificada parte de estos
valores; un valor que no esté aquí se añade primero aquí (sección "Cambios a incorporar" del plan
que lo necesite). Vista: [system.html](system.html).

La spec 016 aplica este sistema a todas las pantallas. Hasta que termine, el código convive con
valores del sistema anterior en las vistas aún sin migrar (ver "Deuda de diseño"), y las capturas del
inventario muestran el aspecto anterior: se rehacen al cerrar la spec.

## Dirección

La marca y la composición de las pantallas se mantienen; cambian la paleta, los tamaños de control
y las piezas compartidas. Tinta pizarra casi negra sobre los grises fríos que la app ya usaba,
tarjetas blancas de esquinas redondeadas y fuente del sistema. El rosa de la marca (`#d75078`) se
reserva para la acción principal y los acentos no textuales; el texto va siempre en tinta. Hay dos
superficies: el sitio público (`/`, `/acerca-de-nosotros`, `/galeria`, `/contacto`) y el panel del
staff con menú lateral.

Los tokens se declaran una sola vez en `@theme` de `resources/css/app.css`, que además retira la
paleta por defecto de Tailwind: una utilidad de color cuyo nombre no sea un token no existe. Los
nombres de este documento son los del código.

## Color

Un token por fila. `DesignTokensTest` exige que los valores de esta tabla sean los de `@theme` y que
cada par declarado cumpla su contraste (WCAG: ≥ 4.5:1 texto, ≥ 3:1 bordes de control, foco e
iconos).

| Token | Valor | Utilidades | Uso | Contraste |
|---|---|---|---|---|
| `--color-primary` | `#d75078` | `bg-primary`, `border-primary`, `decoration-primary` | Acción principal y acentos no textuales | 3.96:1 sobre `--color-surface` (acento ≥ 3:1); nunca texto |
| `--color-primary-hover` | `#dc6588` | `hover:bg-primary-hover` | Hover de la acción principal: más claro, nunca más oscuro | `--color-ink` encima 5.61:1 |
| `--color-primary-soft` | `#fbe9ee` | `bg-primary-soft` | Elemento activo y avisos | `--color-ink` encima 16.13:1 |
| `--color-secondary` | `#f2b0a6` | `border-secondary`, `bg-secondary` | Bordes de avisos e insignias, fondos decorativos | decorativo |
| `--color-ink` | `#0b1120` | `text-ink`, `bg-ink`, `outline-ink` | Texto principal, texto sobre el primario, foco, superficie oscura del visor | 4.76:1 sobre `--color-primary` · 18.83:1 sobre `--color-surface` |
| `--color-muted` | `#556274` | `text-muted`, `placeholder:text-muted` | Texto secundario, iconos de acción y texto de ejemplo de los campos | 6.20:1 sobre `--color-surface` · 5.93:1 sobre `--color-canvas` · 5.31:1 sobre `--color-primary-soft` |
| `--color-canvas` | `#f8fafc` | `bg-canvas` | Fondo de página | — |
| `--color-surface` | `#ffffff` | `bg-surface` | Tarjetas, diálogos, campos | — |
| `--color-on-dark` | `#ffffff` | `text-on-dark`, `outline-on-dark` | Texto sobre peligro y sobre tinta; foco sobre superficies oscuras | 6.47:1 sobre `--color-danger` · 18.83:1 sobre `--color-ink` |
| `--color-line` | `#e2e8f0` | `border-line` | Separadores y bordes de tarjeta | decorativo |
| `--color-field` | `#7c8aa0` | `border-field` | Borde de campo y de control | 3.50:1 sobre `--color-surface` · 3.35:1 sobre `--color-canvas` |
| `--color-overlay` | `rgb(11 17 32 / 0.55)` | `bg-overlay` | Capa tras un diálogo | — |
| `--color-danger` | `#b91c1c` | `bg-danger`, `text-danger` | Eliminar y errores | 5.91:1 sobre `--color-danger-soft` · 6.47:1 sobre `--color-surface` |
| `--color-danger-soft` | `#fef2f2` | `bg-danger-soft` | Fondo de errores y de "Cancelada" | `--color-danger` encima 5.91:1 |
| `--color-success` | `#047857` | `text-success` | "Activo", "Completada" | 5.21:1 sobre `--color-success-soft` |
| `--color-success-soft` | `#ecfdf5` | `bg-success-soft` | Fondo de esos estados | `--color-success` encima 5.21:1 |
| `--color-warning` | `#b45309` | `text-warning` | "Asignada" | 4.84:1 sobre `--color-warning-soft` |
| `--color-warning-soft` | `#fffbeb` | `bg-warning-soft` | Fondo de ese estado | `--color-warning` encima 4.84:1 |
| `--color-info` | `#1d4ed8` | `text-info` | "Reprogramada" | 6.16:1 sobre `--color-info-soft` |
| `--color-info-soft` | `#eff6ff` | `bg-info-soft` | Fondo de ese estado | `--color-info` encima 6.16:1 |
| `--color-whatsapp` | `#25d366` | `bg-whatsapp` | Botón de WhatsApp, con texto en tinta | `--color-ink` encima 9.49:1 |

Reglas de color:
- El rosa nunca es color de **texto** (3.96:1 sobre blanco). Sí pinta iconos, subrayados y bordes,
  donde basta 3:1: los iconos de acento usan `text-primary` dentro de un contenedor marcado
  `data-accent`, el único sitio donde `OnlyDesignTokensTest` lo admite.
- No se usa ningún color fuera de esta tabla: ni literales ni la paleta por defecto de Tailwind.
  `white` y `black` tampoco existen: se usan `surface`, `on-dark` e `ink`. Sí se admiten
  `transparent`, `current` e `inherit`.
- El texto de ejemplo de todo campo se pinta en `muted`, fijado en `@layer base`.
- Opacidad: un token admite modificador de opacidad solo en fondos, bordes y sombras
  (`bg-surface/90`, `border-on-dark/20`, `shadow-primary/20`), nunca en texto.
- Degradados: solo entre tokens y decorativos; ningún texto se apoya en un degradado sin que
  `ui-audit` mida su contraste sobre el tono más desfavorable.
- Modo oscuro: no aplica.

## Tipografía

| Token | Familia | Pesos | Uso |
|---|---|---|---|
| `--font-sans` | `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif` | 400, 500, 600, 700, 800 | Todo |
| `--font-mono` | no aplica | — | — |

| Nivel | Móvil | Escritorio | Interlineado |
|---|---|---|---|
| Título del sitio público (`h1`) | 36px / 800 | 60px / 800 | 1.1 · 1.0 |
| Título de página del panel (`h1`, uno por pantalla) | 30px / 600 | 30px / 600 | 1.2 |
| Título de las páginas de acceso y de error (`h1`, en tinta) | 30px / 600 | 36px / 600 | 1.2 · 1.1 |
| Subtítulo con acción (`h2`, `x-ui.section-title`) | 18px / 700 | 20px / 700 | 1.4 |
| Título de tarjeta y de sección (`h3`) | 16px / 600 | igual | 1.5 |
| Texto | 16px | 16px | 1.5 |
| Texto pequeño (tablas, botones, etiquetas de campo 14px / 600) | 14px | 14px | 1.43 |
| `--text-control` (letra de todo campo) | 16px | 16px | 1.5 |
| `--text-min` (texto más pequeño permitido: etiquetas en mayúsculas, insignias) | 12px | 12px | 1.33 |

Fuentes: del sistema; ninguna fuente web se carga. Ningún texto mide menos de 12 px y ningún campo
menos de 16 px.

## Espaciado, radios, bordes y sombras

| Token | Valor | Utilidades | Uso |
|---|---|---|---|
| `--radius-control` | 12px | `rounded-control` | Botones y campos |
| `--radius-box` | 16px | `rounded-box` | Avisos, contadores y barras |
| `--radius-card` | 24px | `rounded-card` | Tarjetas y diálogos |
| `--spacing-control` | 44px | `h-control`, `min-h-control`, `size-control` | Alto de botón, campo, filtro y elemento de menú |
| `--text-control` | 16px | `text-control` | Letra de campo |
| `--text-min` | 12px | `text-min` | Texto más pequeño permitido |

- Insignias y filtros usan `rounded-full`. No hay más radios: desaparecen los de 6, 8 y 32 px.
- Escala de espaciado: la de Tailwind, múltiplos de 4 px.
- Margen lateral de pantalla: 16 px a 390 px · 24 px a 1440 px; menú lateral de 320 px en escritorio.
- Bordes: 1 px (2 px en el campo con error y en el día de hoy del calendario). Sombras: `shadow-sm`
  en tarjetas; las sombras teñidas usan un token con opacidad (`shadow-primary/20`), nunca un valor
  arbitrario.
- Objetivo de toque mínimo: **44 × 44 px** a 390 px en botones, campos, selectores, filtros,
  elementos de menú, iconos de acción y enlaces solos en su línea o en una lista de navegación. Los
  enlaces dentro de un párrafo quedan fuera, como permite WCAG.
- Dos tokens de espacios distintos nunca producen la misma utilidad (por eso la letra de campo es
  `--text-control` y no `--text-field`, que chocaría con el color `--color-field`).

## Foco

Un solo estilo, fijado en `@layer base`: contorno de 3 px con 2 px de separación, en `ink` sobre
superficies claras y en `on-dark` dentro de un contenedor oscuro marcado `data-surface="dark"` (visor
de la galería, controles sobre imagen). `focus:outline-none` no se usa.

## Componentes

Vista en `system.html` → Componentes. Componentes Blade: `resources/views/components/ui/`; módulos
de navegador: `resources/js/ui/`.

### Botón · `x-ui.button`
Cuatro variantes y un solo tamaño (44 px de alto, radio `control`, 14px / 600):
- `primary`: fondo `primary`, texto en tinta, hover `primary-hover`. Una sola acción principal por pantalla.
- `secondary`: fondo `surface`, texto en tinta, borde `field`.
- `danger`: fondo `danger`, texto `on-dark`.
- `icon`: 44 × 44 px, fondo `surface`, borde `field`; exige nombre accesible.

Enlace: texto en tinta, 600, subrayado de 2 px en `primary`.

### Campo de formulario · `x-ui.input`
44 px de alto, letra `text-control` (16 px) en tinta, borde `field`, radio `control`. Etiqueta
visible asociada (14px / 600), marca de obligatorio, texto de ayuda en `muted`, error en `danger`
bajo el campo y enlazado con `aria-describedby` (borde de 2 px en `danger`). El botón de mostrar
contraseña mide 44 px y tiene nombre. Los campos de correo, nombre, contraseña y teléfono declaran
su propósito con `autocomplete`.

### Marca · `x-ui.brand`
En las barras, el icono (blanco sobre rosa) junto a "Dentissa"; el logo completo en el pie del sitio,
el acceso y las páginas de error. Siempre con texto alternativo. Archivos en `public/images/brand/`
y `public/favicon.ico`.

### Diálogo · `x-ui.dialog` y `resources/js/ui/dialog.js`
Sobre el elemento nativo `<dialog>`: título enlazado con `aria-labelledby`, foco dentro al abrir y
devuelto al cerrar, Escape cierra, capa `overlay`. Ancho y alto máximos los de la ventana, con el
cuerpo desplazable y el pie de acciones fijo. Radio `card`. Lleva su propia región de estado.

### Región de estado · `x-ui.status` y `resources/js/ui/status.js`
Una por marco, siempre montada y vacía (`role="status"`); los errores de guardado usan
`role="alert"`. El módulo anuncia en la región del diálogo abierto si lo hay y, si no, en la del
marco. Muestra un texto propio en español por tipo de error y solo deja pasar los mensajes de
validación.

### Contador · `x-ui.stat`
Tarjeta compacta de radio `box`: etiqueta en mayúsculas de 12 px en `muted` y cifra de 20px / 700.
Tres por fila a 390 px.

### Filtro · `x-ui.chip`
Botón de 44 px de alto, `rounded-full`, borde `field`, texto en tinta; el elegido
(`aria-pressed="true"`) lleva fondo `primary-soft` y borde `primary`. Los grupos de filtros van en
una fila desplazable dentro del ancho.

### Insignia de estado · `x-ui.badge` y `resources/js/ui/badge.js`
Píldora de 12px / 700 con texto además de color: "Asignada" (`warning`), "Completada" y "Activo"
(`success`), "Cancelada" e "Inactivo" (`danger`), "Reprogramada" (`info`) y la neutra de marca
(tinta sobre `primary-soft` con borde `secondary`). El módulo de navegador pinta el mismo marcado.

### Registro · `x-ui.record`
Par de etiqueta (12 px, mayúsculas, `muted`) y valor, separado por una línea `line`. Sustituye a
las tablas del expediente por debajo de `md`.

### Subtítulo con acción · `x-ui.section-title`
Un `h2` con su acción a la derecha.

### Lista o tarjeta
Tarjeta `surface`, radio `card`, borde `line`, `shadow-sm`. Aviso informativo: fondo `primary-soft`,
borde `secondary`, radio `box`, texto en tinta.

### Página de error
Marco propio con el logo completo, el código en `muted`, un `h1`, un texto fijo en español y un botón
principal de vuelta. Pantallas: "Sin permiso" (403), "No encontrada" (404), método no permitido (405)
y formulario caducado (419).

### Estados de pantalla
Vacío: texto centrado y completo, sin recortes. Carga: `animate-spin` en el botón que envía. Error:
mensaje en la región de estado y, si es de un campo, bajo el campo.

## Movimiento

| Token | Valor | Uso |
|---|---|---|
| `--duration-fast` | 150ms | `transition`, `transition-colors` (valor por defecto de Tailwind) |
| `--duration-base` | 200ms (también 300ms) | `duration-200` en botones y menú; `duration-300` en la barra del sitio público |
| `--ease-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | Curva por defecto de Tailwind |

Sin cambios respecto al sistema 1.0.0. Ninguna animación declara versión con
`prefers-reduced-motion` (DS12, fuera de la spec 016).

## Pantallas principales

| Pantalla | Móvil | Escritorio | Componentes |
|---|---|---|---|
| Inicio (sitio público) | [capturas/inicio-390.png](capturas/inicio-390.png) | [capturas/inicio-1440.png](capturas/inicio-1440.png) | barra, tarjetas, preguntas frecuentes, pie |
| Inicio del panel | [capturas/dashboard-administrador-390.png](capturas/dashboard-administrador-390.png) | [capturas/dashboard-administrador-1440.png](capturas/dashboard-administrador-1440.png) | menú lateral, encabezado, contadores |
| Agenda | [capturas/agenda-con-citas-390.png](capturas/agenda-con-citas-390.png) | [capturas/agenda-con-citas-1440.png](capturas/agenda-con-citas-1440.png) | calendario, filtros, diálogos |
| Pacientes | [capturas/pacientes-390.png](capturas/pacientes-390.png) | [capturas/pacientes-1440.png](capturas/pacientes-1440.png) | búsqueda, tarjetas, diálogos |
| Expediente | [capturas/expediente-390.png](capturas/expediente-390.png) | [capturas/expediente-1440.png](capturas/expediente-1440.png) | tarjetas, tablas, estado vacío |

## Inventario de vistas

**Todas** las vistas de la app, con capturas de la app real hechas con datos de prueba. Nada se
dibuja a mano. Las capturas son las del sistema 1.0.0 (2026-10-04): muestran el aspecto anterior y
se rehacen al cerrar la spec 016 (T076), junto con las de las pantallas nuevas de error. **65 de 66 capturadas** (128 imágenes); la única sin captura es `welcome.blade.php`, que no tiene ruta.

| Vista | Ruta / plantilla | Rol | Capturas | Estado |
|---|---|---|---|---|
| Inicio | `/` · `pages/landing/inicio.blade.php` | público | [390](capturas/inicio-390.png) · [1440](capturas/inicio-1440.png) | capturada |
| Inicio, menú móvil abierto | `/` · `components/landing/nav.blade.php` | público | [390](capturas/inicio-menu-390.png) | capturada (solo existe a 390 px) |
| Acerca de Nosotros | `/acerca-de-nosotros` · `pages/landing/acerca.blade.php` | público | [390](capturas/acerca-390.png) · [1440](capturas/acerca-1440.png) | capturada |
| Galería | `/galeria` · `pages/landing/galeria.blade.php` | público | [390](capturas/galeria-390.png) · [1440](capturas/galeria-1440.png) | capturada |
| Galería, visor de imagen | `/galeria` (visor) | público | [390](capturas/galeria-visor-390.png) · [1440](capturas/galeria-visor-1440.png) | capturada |
| Contacto | `/contacto` · `pages/landing/contacto.blade.php` | público | [390](capturas/contacto-390.png) · [1440](capturas/contacto-1440.png) | capturada |
| Bienvenida de Laravel | `welcome.blade.php` | — | — | no accesible (plantilla sin ruta) |
| Iniciar sesión | `/login` · `pages/auth/login.blade.php` | público | [390](capturas/login-390.png) · [1440](capturas/login-1440.png) | capturada |
| Registrarse | `/register` · `pages/auth/register.blade.php` | público | [390](capturas/register-390.png) · [1440](capturas/register-1440.png) | capturada |
| Recuperar contraseña | `/forgot-password` · `pages/auth/forgot-password.blade.php` | público | [390](capturas/forgot-password-390.png) · [1440](capturas/forgot-password-1440.png) | capturada |
| Restablecer contraseña (con token) | `/reset-password?token=…` · `pages/auth/reset-password.blade.php` | público | [390](capturas/reset-password-390.png) · [1440](capturas/reset-password-1440.png) | capturada |
| Restablecer contraseña (sin token) | `/reset-password` | público | [390](capturas/reset-password-sin-token-390.png) · [1440](capturas/reset-password-sin-token-1440.png) | capturada |
| Cerrar sesión | `/logout` · `pages/auth/logout.blade.php` | Paciente | [390](capturas/logout-390.png) · [1440](capturas/logout-1440.png) | capturada |
| Inicio del panel | `/dashboard` · `pages/dashboard.blade.php` | Administrador | [390](capturas/dashboard-administrador-390.png) · [1440](capturas/dashboard-administrador-1440.png) | capturada |
| Inicio del panel | `/dashboard` | Asistente | [390](capturas/dashboard-asistente-390.png) · [1440](capturas/dashboard-asistente-1440.png) | capturada |
| Inicio del panel | `/dashboard` | Doctor | [390](capturas/dashboard-doctor-390.png) · [1440](capturas/dashboard-doctor-1440.png) | capturada |
| Inicio del panel | `/dashboard` | Paciente | [390](capturas/dashboard-paciente-390.png) · [1440](capturas/dashboard-paciente-1440.png) | capturada |
| Menú lateral abierto en móvil | `/dashboard` · `components/ui/sidebar.blade.php` | Administrador | [390](capturas/dashboard-menu-390.png) | capturada (solo existe a 390 px) |
| Agenda, mes actual sin citas | `/agenda` · `pages/agenda/index.blade.php` | Administrador | [390](capturas/agenda-390.png) · [1440](capturas/agenda-1440.png) | capturada |
| Agenda, mes con citas | `/agenda` (julio de 2026) | Administrador | [390](capturas/agenda-con-citas-390.png) · [1440](capturas/agenda-con-citas-1440.png) | capturada |
| Agenda, citas del día | `components/calendar/day-details-modal.blade.php` | Administrador | [390](capturas/agenda-dia-390.png) · [1440](capturas/agenda-dia-1440.png) | capturada |
| Agenda, detalle de cita | `components/calendar/view-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-detalle-390.png) · [1440](capturas/agenda-cita-detalle-1440.png) | capturada |
| Agenda, detalle de cita asignada (con "Editar cita") | `components/calendar/view-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-detalle-pendiente-390.png) · [1440](capturas/agenda-cita-detalle-pendiente-1440.png) | capturada |
| Agenda, editar cita | `components/calendar/edit-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-editar-390.png) · [1440](capturas/agenda-cita-editar-1440.png) | capturada |
| Agenda, agendar cita | `components/calendar/create-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-crear-390.png) · [1440](capturas/agenda-cita-crear-1440.png) | capturada |
| Agenda, completar cita (paso 1) | `components/calendar/complete-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-completar-390.png) · [1440](capturas/agenda-cita-completar-1440.png) | capturada |
| Agenda, completar cita con error de validación | `components/calendar/complete-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-completar-validacion-390.png) · [1440](capturas/agenda-cita-completar-validacion-1440.png) | capturada |
| Agenda, completar cita (paso 2, sin recetas) | `components/calendar/complete-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-completar-paso2-390.png) · [1440](capturas/agenda-cita-completar-paso2-1440.png) | capturada |
| Agenda, completar cita (paso 2, con una receta) | `components/calendar/complete-appointment-modal.blade.php` | Administrador | [390](capturas/agenda-cita-completar-receta-390.png) · [1440](capturas/agenda-cita-completar-receta-1440.png) | capturada |
| Pacientes | `/pacientes` · `pages/patients/index.blade.php` | Administrador | [390](capturas/pacientes-390.png) · [1440](capturas/pacientes-1440.png) | capturada |
| Pacientes, nuevo paciente | `components/ui/create-patient-modal.blade.php` | Administrador | [390](capturas/pacientes-crear-390.png) · [1440](capturas/pacientes-crear-1440.png) | capturada |
| Pacientes, editar | `components/ui/edit-patient-modal.blade.php` | Administrador | [390](capturas/pacientes-editar-390.png) · [1440](capturas/pacientes-editar-1440.png) | capturada |
| Pacientes, confirmar eliminación | `components/ui/confirm-delete-modal.blade.php` | Administrador | [390](capturas/pacientes-eliminar-390.png) · [1440](capturas/pacientes-eliminar-1440.png) | capturada |
| Expedientes clínicos | `/expedientes-clinicos` · `pages/records/index.blade.php` | Administrador | [390](capturas/expedientes-390.png) · [1440](capturas/expedientes-1440.png) | capturada |
| Expediente de un paciente | `/expedientes-clinicos/{patientId}` | Administrador | [390](capturas/expediente-390.png) · [1440](capturas/expediente-1440.png) | capturada |
| Expediente, formulario de contacto | `components/records/contact-info-table.blade.php` | Administrador | [390](capturas/expediente-contacto-form-390.png) · [1440](capturas/expediente-contacto-form-1440.png) | capturada |
| Expediente, formulario de dirección | `components/records/address-table.blade.php` | Administrador | [390](capturas/expediente-direccion-form-390.png) · [1440](capturas/expediente-direccion-form-1440.png) | capturada |
| Expediente, formulario de datos médicos | `components/records/medical-data-table.blade.php` | Administrador | [390](capturas/expediente-medicos-form-390.png) · [1440](capturas/expediente-medicos-form-1440.png) | capturada |
| Expediente de un paciente | `/expedientes-clinicos/{patientId}` | Doctor | [390](capturas/expediente-doctor-390.png) · [1440](capturas/expediente-doctor-1440.png) | capturada |
| Tratamientos | `/tratamientos` · `pages/tratamientos/index.blade.php` | Administrador | [390](capturas/tratamientos-390.png) · [1440](capturas/tratamientos-1440.png) | capturada |
| Tratamientos, detalle | `pages/tratamientos/index.blade.php` (modal) | Administrador | [390](capturas/tratamientos-ver-390.png) · [1440](capturas/tratamientos-ver-1440.png) | capturada |
| Tratamientos, nuevo | `pages/tratamientos/index.blade.php` (modal) | Administrador | [390](capturas/tratamientos-crear-390.png) · [1440](capturas/tratamientos-crear-1440.png) | capturada |
| Tratamientos, editar | `pages/tratamientos/index.blade.php` (modal) | Administrador | [390](capturas/tratamientos-editar-390.png) · [1440](capturas/tratamientos-editar-1440.png) | capturada |
| Tratamientos, confirmar eliminación | `pages/tratamientos/index.blade.php` (modal) | Administrador | [390](capturas/tratamientos-eliminar-390.png) · [1440](capturas/tratamientos-eliminar-1440.png) | capturada |
| Contenido, pestaña Galería | `/contenido` · `pages/contenido/index.blade.php` | Administrador | [390](capturas/contenido-galeria-390.png) · [1440](capturas/contenido-galeria-1440.png) | capturada |
| Contenido, nueva imagen | `pages/contenido/galeria/create-modal.blade.php` | Administrador | [390](capturas/contenido-galeria-crear-390.png) · [1440](capturas/contenido-galeria-crear-1440.png) | capturada |
| Contenido, editar imagen | `pages/contenido/galeria/edit-modal.blade.php` | Administrador | [390](capturas/contenido-galeria-editar-390.png) · [1440](capturas/contenido-galeria-editar-1440.png) | capturada |
| Contenido, eliminar imagen | `pages/contenido/galeria/` (modal) | Administrador | [390](capturas/contenido-galeria-eliminar-390.png) · [1440](capturas/contenido-galeria-eliminar-1440.png) | capturada |
| Contenido, pestaña Promociones | `/contenido` | Administrador | [390](capturas/contenido-promociones-390.png) · [1440](capturas/contenido-promociones-1440.png) | capturada |
| Contenido, nueva promoción | `pages/contenido/promociones/create-modal.blade.php` | Administrador | [390](capturas/contenido-promociones-crear-390.png) · [1440](capturas/contenido-promociones-crear-1440.png) | capturada |
| Contenido, editar promoción | `pages/contenido/promociones/edit-modal.blade.php` | Administrador | [390](capturas/contenido-promociones-editar-390.png) · [1440](capturas/contenido-promociones-editar-1440.png) | capturada |
| Contenido, eliminar promoción | `pages/contenido/promociones/` (modal) | Administrador | [390](capturas/contenido-promociones-eliminar-390.png) · [1440](capturas/contenido-promociones-eliminar-1440.png) | capturada |
| Contenido, pestaña Certificaciones | `/contenido` | Administrador | [390](capturas/contenido-certificaciones-390.png) · [1440](capturas/contenido-certificaciones-1440.png) | capturada |
| Contenido, nueva certificación | `pages/contenido/certificaciones/create-modal.blade.php` | Administrador | [390](capturas/contenido-certificaciones-crear-390.png) · [1440](capturas/contenido-certificaciones-crear-1440.png) | capturada |
| Contenido, editar certificación | `pages/contenido/certificaciones/edit-modal.blade.php` | Administrador | [390](capturas/contenido-certificaciones-editar-390.png) · [1440](capturas/contenido-certificaciones-editar-1440.png) | capturada |
| Contenido, eliminar certificación | `pages/contenido/certificaciones/` (modal) | Administrador | [390](capturas/contenido-certificaciones-eliminar-390.png) · [1440](capturas/contenido-certificaciones-eliminar-1440.png) | capturada |
| Contenido, pestaña Testimonios | `/contenido` | Administrador | [390](capturas/contenido-testimonios-390.png) · [1440](capturas/contenido-testimonios-1440.png) | capturada |
| Contenido, editar testimonio | `pages/contenido/testimonios/edit-modal.blade.php` | Administrador | [390](capturas/contenido-testimonios-editar-390.png) · [1440](capturas/contenido-testimonios-editar-1440.png) | capturada |
| Contenido, eliminar testimonio | `pages/contenido/testimonios/` (modal) | Administrador | [390](capturas/contenido-testimonios-eliminar-390.png) · [1440](capturas/contenido-testimonios-eliminar-1440.png) | capturada |
| Usuarios | `/usuarios` · `pages/usuarios/index.blade.php` | Administrador | [390](capturas/usuarios-390.png) · [1440](capturas/usuarios-1440.png) | capturada |
| Usuarios, nuevo usuario | `components/ui/create-user-modal.blade.php` | Administrador | [390](capturas/usuarios-crear-390.png) · [1440](capturas/usuarios-crear-1440.png) | capturada |
| Usuarios, editar | `components/ui/edit-user-modal.blade.php` | Administrador | [390](capturas/usuarios-editar-390.png) · [1440](capturas/usuarios-editar-1440.png) | capturada |
| Usuarios, confirmar eliminación | `components/ui/confirm-delete-modal.blade.php` | Administrador | [390](capturas/usuarios-eliminar-390.png) · [1440](capturas/usuarios-eliminar-1440.png) | capturada |
| Página no encontrada | ruta inexistente · página por defecto de Laravel | público | [390](capturas/error-404-390.png) · [1440](capturas/error-404-1440.png) | capturada |
| Acceso denegado (paciente en ruta de staff) | `/expedientes-clinicos` · página por defecto de Laravel | Paciente | [390](capturas/error-403-staff-390.png) · [1440](capturas/error-403-staff-1440.png) | capturada |
| Acceso denegado (asistente en ruta de administrador) | `/agenda` · respuesta JSON del middleware `only.admin` | Asistente | [390](capturas/error-403-admin-390.png) · [1440](capturas/error-403-admin-1440.png) | capturada |

Estados: `capturada` · `sin captura (motivo)` · `no accesible (motivo)`. Los diálogos se capturan
al tamaño de la ventana; los más altos que la ventana (agendar y completar cita) salen cortados
porque así se ven. Solo se provocó un error de validación (completar cita sin motivo); "completar
cita" se recorrió hasta el paso 2 sin enviarlo, así que no escribió nada.

## Reglas de uso

- Una acción principal por pantalla, arriba a la derecha del encabezado.
- El texto nunca va en rosa; enlaces y elemento activo en tinta, con el rosa como acento.
- Ningún color, radio ni tamaño de control fuera de los tokens (P15 de la constitución, que añade la
  spec 016): lo verifican `OnlyDesignTokensTest`, `DesignTokensTest` y `npm run test:ui`.
- Rojo solo para eliminar y errores; verde, ámbar, azul y rojo para estados de cita, siempre con
  texto además de color.
- Todo lo que hace algo al pulsarlo es un control nativo (`button`, `a`, campo) o tiene rol, foco y
  activación con Intro y barra espaciadora.
- Un solo `h1` por pantalla.
- La composición a 1440 px no cambia respecto al sistema 1.0.0, salvo el encabezado único de
  gestión de contenido.

## Deuda de diseño

Lo que el código hace hoy y no cumple este sistema o las pautas de accesibilidad. La spec 016
resuelve toda la deuda salvo DS12; cada fila pasa a "resuelta por 016" al cerrarla.

| ID | Problema | Dónde (evidencia) | Corrección propuesta | Estado |
|---|---|---|---|---|
| DS1 | No hay tokens de diseño: los colores de marca son valores arbitrarios repetidos (unas 620 apariciones de 8 rosas) y el único token es `--font-sans` | `resources/css/app.css` (`@theme`); vistas y JS de `resources/` | Declarar los colores en `@theme` y sustituir los valores arbitrarios | la resuelve 016 |
| DS2 | Dos rosas para la misma función de acción principal (`#E91E63` y `#B5114A`) y dos hover del mismo botón (`#d61b5b`, `#D81B60`) | `capturas/pacientes-1440.png` ("Nuevo paciente") frente a `capturas/expedientes-1440.png` ("Consultar Expediente") | Elegir uno como principal y documentar el uso del otro | la resuelve 016 |
| DS3 | Contraste insuficiente: texto blanco sobre `#E91E63` 4.35:1; `slate-400` sobre blanco 2.63:1; blanco sobre el verde de WhatsApp 2.47:1; `red-600` sobre `red-50` 4.36:1; bordes de campo 1.23–1.49:1 | `system.html` → Color (pares en rojo); `capturas/pacientes-1440.png`, `capturas/inicio-menu-390.png` | Oscurecer el rosa principal o usar `#B5114A`; subir etiquetas a `slate-500`; borde de campo ≥ 3:1 | la resuelve 016 |
| DS4 | Objetivos de toque menores de 44 px a 390 px: "Eliminar" 70×30, "Editar" 58×30, menú 36×36, iconos 28×28 y 32×32, "Consultar Expediente" 147×28, filtros de 32 px de alto, "Cancelar" 38 px | medidas de `capturas/*-390.png` (p. ej. `capturas/expediente-390.png`, `capturas/pacientes-390.png`) | Alto mínimo de 44 px en controles táctiles | la resuelve 016 |
| DS5 | Sin escala de tamaños: 10 alturas de botón (24–48 px), 4 de campo (38–48 px) y radios de 6, 8, 12, 16, 24, 32 px y píldora para el mismo tipo de control; `x-ui.button` casi no se usa | `resources/views/components/ui/button.blade.php`; medidas de todas las capturas | Tamaños y radios fijos en el componente y usarlo en las pantallas | la resuelve 016 |
| DS6 | Tres estilos de foco distintos y `focus:outline-none` 62 veces; el anillo `#F8BBD0` da 1.61:1 y el de `#B5114A` al 10 % es casi invisible | `resources/views/components/ui/input.blade.php`, modales de `pages/contenido/` y `components/calendar/` | Un solo estilo de foco visible (≥ 3:1) | la resuelve 016 |
| DS7 | Desbordamiento horizontal en la agenda a 390 px (el contenido mide 480 px) | `capturas/agenda-390.png`; `resources/views/components/calendar/grid.blade.php` | Calendario adaptado al ancho móvil | la resuelve 016 |
| DS8 | No hay componente base de diálogo: unos 20 diálogos repiten marcado y estilos; `aria-labelledby="modal-title"` apunta a un id que no existe; los de agendar y completar cita son más altos que una ventana de 900 px | `resources/views/components/ui/*-modal.blade.php`, `components/calendar/*-modal.blade.php`, `pages/contenido/**`; `capturas/agenda-cita-completar-1440.png` | Componente de diálogo único con título enlazado, foco atrapado y alto máximo | la resuelve 016 |
| DS9 | Marca sin recursos: no hay archivo de logo (dos SVG en línea distintos, anillo en el sitio público y escudo en el panel) y `public/favicon.ico` pesa 0 bytes | `components/landing/nav.blade.php:10`, `components/ui/sidebar.blade.php:93`, `public/favicon.ico` | Un logo en archivo y un favicon real | la resuelve 016 |
| DS10 | Los rechazos de acceso no tienen pantalla propia: `/agenda` con un rol no administrador muestra JSON crudo y en inglés; 403 y 404 usan la página por defecto de Laravel, sin marca ni camino de vuelta | `capturas/error-403-admin-1440.png`, `capturas/error-403-staff-1440.png`, `capturas/error-404-1440.png` | Vistas de error con la marca y un enlace de regreso; el middleware responde con vista en rutas web | la resuelve 016 |
| DS11 | Textos del panel sin acentos ("Gestion de contenido", "Expedientes clinicos", "Iniciar Sesion", "Esta accion eliminara la promocion") frente al sitio público, que sí los lleva | `capturas/contenido-promociones-eliminar-1440.png`, `capturas/expediente-1440.png`, `capturas/login-1440.png` | Corregir la ortografía de los textos del panel | la resuelve 016 |
| DS12 | Movimiento sin alternativa: ninguna regla `prefers-reduced-motion` ni `motion-reduce:` en `resources/`; `transition-all` 94 veces; clases `animate-in…` sin CSS que las defina | `resources/views/**`, `resources/js/pages/**` | Versión sin movimiento y animar solo `transform` y `opacity` | pendiente |
| DS13 | Interfaz sin uso o inalcanzable: `x-ui.table` sin usos, `welcome.blade.php` sin ruta y la rama de botones "ver y editar" de la tarjeta de cita, que solo se pinta para quien no es administrador en una pantalla solo para administradores (editar sí es accesible desde el detalle de la cita) | `resources/views/components/ui/table.blade.php`, `resources/views/welcome.blade.php`, `resources/js/pages/agenda/index.js:202` | Decidir por pieza: usarla o retirarla | la resuelve 016 |
| DS14 | La mayoría de las pantallas del panel no tienen `h1`: el título de página es un `h2` (hay `h1` en 14 de 58 capturas a 390 px) | `resources/views/components/ui/page-hero.blade.php`; medidas de las capturas | Título de página como `h1` | la resuelve 016 |

Estados: `pendiente` · `la resuelve NNN` · `resuelta por NNN` · `no se confirma (re-extracción
AAAA-MM-DD: evidencia)`. Un ID nunca se reutiliza: una re-extracción conserva los vigentes y numera
lo nuevo a partir del último.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-04 | diseño | ¿Documentar el diseño que usa hoy el código? | Sí, documentarlo; sin cambiar código | usuario |
| 2026-10-04 | supuesto | ¿Con qué datos se hacen las capturas? | Con la base local, sustituyendo antes los datos personales reales y las imágenes subidas por valores e imágenes de prueba | usuario |
| 2026-10-04 | contradicción | El código tiene dos rosas de acción principal | Se documentan los dos (`--color-primary`, `--color-primary-strong`); elegir es DS2 | /init --upgrade |
| 2026-10-04 | brecha | Los contadores del inicio del panel muestran "-" | No es un fallo: el inicio del panel es un módulo aún vacío y tendrá su propia spec más adelante (roadmap → Pendientes y deuda) | usuario |
| 2026-10-04 | diseño | ¿Qué deuda entra en el roadmap? | Una sola spec (objetivo 6) con toda la deuda salvo DS12, que queda fuera por no ser obligatoria | usuario |
| 2026-10-04 | diseño | ¿Cuál de los dos rosas queda como principal (DS2)? | Ninguno: la paleta cambia a `#d75078` (principal) y `#f2b0a6` (secundario); los actuales salieron de una maquetación rápida. Se aplica con tokens en la spec del objetivo 6, que incluye la propuesta de diseño de su uso. Este documento sigue describiendo lo que hay hoy hasta que esa spec lo cambie | usuario |
| 2026-10-04 | implícita | Anchos de comprobación | 390 y 1440 px | `.ai/project.yaml` |
| 2026-10-04 | diseño | ¿Se aprueba este documento como descripción del diseño actual? | Aprobado | usuario |
| 2026-10-04 | diseño | ¿Cuándo se añade a la constitución el principio "La interfaz usa solo el sistema de diseño"? | En la spec del objetivo 6 del roadmap, que crea los tokens; esa spec va antes que el arreglo de la galería pública | usuario |
| 2026-10-04 | contradicción | La primera extracción dio por inaccesible "Editar cita" y por no capturable el paso 2 de "Completar cita" | Corregido: editar se abre desde el detalle de una cita asignada, y completar solo escribe al confirmar el paso 2; ambos capturados | usuario |
| 2026-10-06 | diseño | ¿Qué variante de la propuesta de la spec 016 se aplica? | La variante B · Neutra: tinta pizarra y los grises fríos que la app ya usa, con `#d75078` como principal y `#f2b0a6` como secundario | usuario |
| 2026-10-06 | diseño | ¿Cómo se usa el logo? | El consultorio se llama Dentissa; en las barras, el icono junto a "Dentissa"; el logo completo de la doctora en el pie del sitio, el acceso y las páginas de error | usuario |
| 2026-10-07 | diseño | ¿Cómo queda el panel lateral de las pantallas de acceso? | En rosa suave con texto en tinta, el logo completo y la foto | usuario |
| 2026-10-08 | diseño | Paso del sistema extraído (1.0.0) al elegido (2.0.0) | Hecho en la spec 016 (T001): tokens, componentes y reglas de "Cambios a incorporar al sistema" del plan; el sistema 1.0.0 queda en `history/` | docs/specs/016-sistema-de-diseno-aplicado/plan.md |
| 2026-10-08 | diseño | ¿Se aprueba el sistema 2.0.0 (variante B)? | Aprobado, tras ver la pantalla de pacientes migrada a 390 y 1440 px en una rama de vista previa | usuario |
| 2026-10-08 | diseño | ¿La spec 016 aplica el sistema al sitio público, que tendrá un rediseño completo en otra spec? | Sí: se queda en la 016 con su pase de paleta, marca, tamaños y accesibilidad; el rediseño futuro partirá de los tokens | usuario |
