---
status: approved         # draft | approved (solo el usuario aprueba) — aprobado por el usuario, 2026-10-04
source: extracted        # chosen: elegido entre opciones · extracted: documentado del código
version: 1.0.0
extracted_with: 1.11.2   # versión del plugin que lo extrajo
html: docs/design/system.html
canvas: null
widths: [390, 1440]
created: 2026-10-04
updated: 2026-10-04
---

# Sistema de diseño de Dentissa

Describe la interfaz **tal como está hoy**, sin mejoras: lo que se midió en la app y lo que declara
el código. Toda pantalla nueva o modificada parte de estos valores; un valor que no esté aquí se
añade primero aquí (sección "Cambios a incorporar" del plan que lo necesite). Vista:
[system.html](system.html).

## Dirección

Sistema extraído del código el 2026-10-04 (`/init --upgrade`, ai-dd 1.11.2). No hubo una dirección
escrita: la interfaz es Tailwind CSS 4 con su paleta por defecto (grises `slate`) más una familia de
rosas escritos como valores arbitrarios, tarjetas blancas de esquinas muy redondeadas sobre un fondo
casi blanco con un degradado rosa tenue, y fuente del sistema. Hay dos superficies: el sitio público
(`/`, `/acerca-de-nosotros`, `/galeria`, `/contacto`) y el panel del staff con menú lateral.

**Procedimiento.** 65 vistas y estados capturados con Chrome sin interfaz a 390 × 844 y 1440 × 900 px
sobre la base local de pruebas, con un usuario por rol. Antes de capturar se sustituyeron por valores
ficticios los datos personales que había en la base local (un correo, teléfonos, una dirección) y las
imágenes subidas a la galería y a certificaciones, que ahora son imágenes neutras "Imagen de prueba"
(decisión del usuario, 2026-10-04). Las capturas de página completa se toman con la página arriba y los elementos
`sticky` (la barra del sitio público) en su lugar del flujo, que es donde se ven sin desplazar. En cada captura se midieron los estilos calculados (`getComputedStyle`). Los
nombres de token de este documento (`--color-*`, `--radius-*`…) son **del documento**: el código no
los declara (deuda DS1). Las equivalencias con Tailwind van entre paréntesis.

## Color

| Token | Valor | Uso | Contraste sobre su fondo |
|---|---|---|---|
| `--color-bg` | `#f8fafc` (`slate-50`) | Fondo base; el panel usa un degradado `#FFF7FA` → `slate-50` → blanco | — |
| `--color-surface` | `#ffffff` | Tarjetas, menú lateral, diálogos, campos | — |
| `--color-text` | `#0f172b` (`slate-900`) | Texto principal y títulos | 17.04:1 sobre `--color-bg` |
| `--color-text-muted` | `#62748e` (`slate-500`) | Texto secundario, elementos de menú | 4.76:1 sobre `--color-surface` |
| `--color-text-faint` | `#90a1b9` (`slate-400`) | Etiquetas en mayúsculas, rol, textos de ayuda | **2.63:1** sobre `--color-surface` (no cumple, DS3) |
| `--color-primary` | `#E91E63` | Acción principal ("Nuevo paciente", "Completar"), logo del panel, título de las páginas de acceso | — |
| `--color-on-primary` | `#ffffff` | Texto sobre la acción principal | **4.35:1** sobre `--color-primary` (no cumple, DS3) |
| `--color-primary-strong` | `#B5114A` | Segunda acción principal ("Consultar Expediente"), enlaces, elemento activo, marca del sitio público | 6.68:1 sobre blanco |
| `--color-primary-soft` | `#FDF1F6` | Fondo del elemento activo y de avisos | `--color-primary-strong` encima: 6.07:1 |
| `--color-border` | `#e2e8f0` (`slate-200`) | Bordes de tarjeta, separadores y campos claros | 1.23:1 sobre `--color-surface` (no cumple 3:1 como borde de campo, DS3) |
| `--color-border-field` | `#cad5e2` (`slate-300`) | Borde de campos de formulario | 1.49:1 (no cumple 3:1, DS3) |
| `--color-border-brand` | `#F5C2D6` | Borde del encabezado de página y de avisos | decorativo |
| `--color-focus` | `#E91E63` | Borde del campo con foco (también `#B5114A`, DS6) | 4.35:1 sobre `--color-surface` |
| `--color-focus-ring` | `#F8BBD0` | Anillo de foco (también `#B5114A` al 10 %, DS6) | **1.61:1** (no cumple 3:1, DS6) |
| `--color-danger` | `#e7000b` (`red-600`) | Eliminar, errores | 4.77:1 con texto blanco encima; **4.36:1** sobre `--color-danger-bg` (no cumple, DS3) |
| `--color-danger-bg` | `#fef2f2` (`red-50`) | Fondo de avisos y botones de borrado | — |
| `--color-success` | `#007a55` (`emerald-700`) | Estados "Activo" y "Completada" | 5.09:1 sobre `--color-success-bg` |
| `--color-success-bg` | `#ecfdf5` (`emerald-50`) | Fondo de esos estados | — |
| `--color-whatsapp` | `#00bc7d` (`emerald-500`) | Botón WhatsApp, punto de estado | texto blanco encima: **2.47:1** (no cumple, DS3) |
| `--color-warning` | `#bb4d00` (`amber-700`) | Estado "Asignada" | 4.85:1 sobre `--color-warning-bg` |
| `--color-warning-bg` | `#fffbeb` (`amber-50`) | Fondo de ese estado | — |

Otros rosas en uso sin función propia: `#FFF7FA` (inicio del degradado, hover claro), `#d61b5b` y
`#D81B60` (dos hover del mismo botón, DS2). Estado "Reprogramada": `blue-700` sobre `blue-50`
(6.28:1). Usos en el código, contados por el explorador: `#B5114A` 275, `#F5C2D6` 92, `#E91E63` 88,
`#FDF1F6` 61, `#FFF7FA` 35, `#F8BBD0` 32, `#D61B5B` 21, `#D81B60` 15.

Todo color de texto declara su contraste calculado (WCAG: ≥ 4.5:1 texto normal, ≥ 3:1 texto grande
y bordes de control). Modo oscuro: no aplica (ninguna clase `dark:`; `color-scheme: normal` en las
primera tanda de 114 capturas).

## Tipografía

| Token | Familia | Pesos | Uso |
|---|---|---|---|
| `--font-sans` | `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif` (único token real, `resources/css/app.css`) | 400, 500, 600, 700, 800 | Todo |
| `--font-mono` | no aplica | — | — |

| Nivel | Móvil | Escritorio | Interlineado |
|---|---|---|---|
| Título del sitio público (`h1`) | 36px / 800 | 60px / 800 | 1.1 · 1.0 |
| Título de página del panel (es un `h2`, DS14) | 30px / 600 | 30px / 600 | 1.2 |
| Título de las páginas de acceso (`h1`, color `--color-primary`) | 30px / 600 | 36px / 600 | 1.2 · 1.1 |
| Título de tarjeta y de sección (`h3`) | 16px / 600 (también 18 y 20px / 700) | igual | 1.5 |
| `--text-body` | 16px | 16px | 1.5 |
| `--text-small` (tablas, campos, botones, etiquetas de campo 14px / 500) | 14px | 14px | 1.43 |
| Etiqueta en mayúsculas espaciadas | 10–12px / 600–700 | igual | — |

Fuentes: del sistema; ninguna fuente web se carga (`document.fonts` vacío en todas las capturas).
`welcome.blade.php` declara Instrument Sans, pero no tiene ruta.

## Espaciado, radios, bordes y sombras

- Escala de espaciado: la de Tailwind, múltiplos de 4 px (`--space-1` … `--space-8` = 4, 8, 12, 16,
  24, 32, 48, 64 px). Con excepciones arbitrarias (`min-h-[100px]`, `text-[10px]`, `text-[11px]`).
- Margen lateral de pantalla: 16 px a 390 px (menú de 358 px) · 24 px a 1440 px; menú lateral de
  320 px en escritorio.
- Radios medidos: 6 y 8 px (campos de algunos formularios), 12 px y 16 px (botones y campos), 24 px
  (tarjetas, `rounded-3xl`), 32 px (encabezado de página) y píldora (`rounded-full`). Sin escala
  única (DS5).
- Bordes: 1 px. Sombras: `shadow-sm` en tarjetas; `shadow-lg` teñida de rosa en el logo y la acción
  principal.
- Objetivo de toque mínimo: **no definido**. Medido a 390 px: de 24 a 48 px de alto (DS4).

## Componentes

Vista en `system.html` → Componentes (recortes de las capturas). Componentes Blade:
`resources/views/components/{ui,calendar,records,landing}/`.

### Botón
`x-ui.button` declara cinco variantes (`principal` blanco con borde rosa, `primary` `#E91E63`,
`danger`, `warning`, `ok`), 14px / 600, radio 8 px, foco con `focus-visible:ring-2` y
`disabled:opacity-60`. La mayoría de los botones de las pantallas **no lo usan** y escriben sus
clases: alturas medidas de 24, 28, 30, 32, 36, 38, 40, 44, 46 y 48 px y radios de 8, 12, 16 px y
píldora (DS5). Botones de icono (editar, eliminar, ver) de 28–38 px con `aria-label`.

### Campo de formulario
`x-ui.input`: etiqueta visible 14px / 500 `slate-700`, campo de 14px. Tres formas medidas: 48 px de
alto con radio 16 px sin borde (búsquedas), 38 px con radio 8 o 12 px y borde `slate-300`
(formularios en diálogos), 47 px con radio 16 px sobre `slate-50` (completar cita). Texto de ayuda
"(opcional)" en `slate-400`.

### Lista o tarjeta
Tarjeta blanca, radio 24 px, borde `slate-200`, `shadow-sm`: paciente, usuario, tratamiento,
contenido, contador. Tablas (`components/records/*`) con cabecera 14px / 600 sobre una fila gris claro y
celdas de 14 px. `x-ui.table` existe y no se usa (DS13).

### Aviso, diálogo y mensajes
Aviso informativo rosa (`#FDF1F6`, borde `#F5C2D6`); aviso de borrado rojo claro. Diálogos: capa
oscura con desenfoque y tarjeta centrada de radio 24–32 px; **no hay componente base**, cada diálogo
repite su marcado (DS8). Estados de cita como píldoras de color (asignada ámbar, completada verde,
cancelada roja, reprogramada azul).

### Estados de pantalla
Vacío: texto centrado ("Sin citas registradas.", "No hay actividad reciente para mostrar.", "Sin
promociones por el momento"), sin acción. Carga: `animate-spin` en algunos botones. Error de acceso:
páginas por defecto de Laravel o JSON crudo (DS10).

## Movimiento

| Token | Valor | Uso |
|---|---|---|
| `--duration-fast` | 150ms | `transition`, `transition-colors`, `transition-all` (valor por defecto de Tailwind) |
| `--duration-base` | 200ms (también 300ms) | `duration-200` en botones y menú; `duration-300` en la barra del sitio público |
| `--ease-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | Curva por defecto de Tailwind |

También: `hover:scale-[1.02]` en tarjetas de cita, `backdrop-blur-sm` en la capa de los diálogos.
Ninguna animación declara versión con `prefers-reduced-motion` (DS12).

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
dibuja a mano. **65 de 66 capturadas** (128 imágenes); la única sin captura es `welcome.blade.php`, que no tiene ruta.

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

- Hoy no hay reglas escritas. Lo observado: una acción principal rosa por pantalla, arriba a la
  derecha del encabezado; rojo solo para borrar; verde, ámbar, azul y rojo para estados de cita.
- Ningún valor literal de color, tamaño o espaciado fuera de los tokens: **aún no se cumple** (DS1);
  el principio "solo tokens" de la constitución se propondrá en la spec del objetivo 6 del roadmap
  (decisión del usuario, 2026-10-04).

## Deuda de diseño

Lo que el código hace hoy y no cumple este sistema o las pautas de accesibilidad. No bloquea el
código previo; se corrige cuando una spec toca la pantalla o como objetivo del roadmap.

| ID | Problema | Dónde (evidencia) | Corrección propuesta | Estado |
|---|---|---|---|---|
| DS1 | No hay tokens de diseño: los colores de marca son valores arbitrarios repetidos (unas 620 apariciones de 8 rosas) y el único token es `--font-sans` | `resources/css/app.css` (`@theme`); vistas y JS de `resources/` | Declarar los colores en `@theme` y sustituir los valores arbitrarios | pendiente |
| DS2 | Dos rosas para la misma función de acción principal (`#E91E63` y `#B5114A`) y dos hover del mismo botón (`#d61b5b`, `#D81B60`) | `capturas/pacientes-1440.png` ("Nuevo paciente") frente a `capturas/expedientes-1440.png` ("Consultar Expediente") | Elegir uno como principal y documentar el uso del otro | pendiente |
| DS3 | Contraste insuficiente: texto blanco sobre `#E91E63` 4.35:1; `slate-400` sobre blanco 2.63:1; blanco sobre el verde de WhatsApp 2.47:1; `red-600` sobre `red-50` 4.36:1; bordes de campo 1.23–1.49:1 | `system.html` → Color (pares en rojo); `capturas/pacientes-1440.png`, `capturas/inicio-menu-390.png` | Oscurecer el rosa principal o usar `#B5114A`; subir etiquetas a `slate-500`; borde de campo ≥ 3:1 | pendiente |
| DS4 | Objetivos de toque menores de 44 px a 390 px: "Eliminar" 70×30, "Editar" 58×30, menú 36×36, iconos 28×28 y 32×32, "Consultar Expediente" 147×28, filtros de 32 px de alto, "Cancelar" 38 px | medidas de `capturas/*-390.png` (p. ej. `capturas/expediente-390.png`, `capturas/pacientes-390.png`) | Alto mínimo de 44 px en controles táctiles | pendiente |
| DS5 | Sin escala de tamaños: 10 alturas de botón (24–48 px), 4 de campo (38–48 px) y radios de 6, 8, 12, 16, 24, 32 px y píldora para el mismo tipo de control; `x-ui.button` casi no se usa | `resources/views/components/ui/button.blade.php`; medidas de todas las capturas | Tamaños y radios fijos en el componente y usarlo en las pantallas | pendiente |
| DS6 | Tres estilos de foco distintos y `focus:outline-none` 62 veces; el anillo `#F8BBD0` da 1.61:1 y el de `#B5114A` al 10 % es casi invisible | `resources/views/components/ui/input.blade.php`, modales de `pages/contenido/` y `components/calendar/` | Un solo estilo de foco visible (≥ 3:1) | pendiente |
| DS7 | Desbordamiento horizontal en la agenda a 390 px (el contenido mide 480 px) | `capturas/agenda-390.png`; `resources/views/components/calendar/grid.blade.php` | Calendario adaptado al ancho móvil | pendiente |
| DS8 | No hay componente base de diálogo: unos 20 diálogos repiten marcado y estilos; `aria-labelledby="modal-title"` apunta a un id que no existe; los de agendar y completar cita son más altos que una ventana de 900 px | `resources/views/components/ui/*-modal.blade.php`, `components/calendar/*-modal.blade.php`, `pages/contenido/**`; `capturas/agenda-cita-completar-1440.png` | Componente de diálogo único con título enlazado, foco atrapado y alto máximo | pendiente |
| DS9 | Marca sin recursos: no hay archivo de logo (dos SVG en línea distintos, anillo en el sitio público y escudo en el panel) y `public/favicon.ico` pesa 0 bytes | `components/landing/nav.blade.php:10`, `components/ui/sidebar.blade.php:93`, `public/favicon.ico` | Un logo en archivo y un favicon real | pendiente |
| DS10 | Los rechazos de acceso no tienen pantalla propia: `/agenda` con un rol no administrador muestra JSON crudo y en inglés; 403 y 404 usan la página por defecto de Laravel, sin marca ni camino de vuelta | `capturas/error-403-admin-1440.png`, `capturas/error-403-staff-1440.png`, `capturas/error-404-1440.png` | Vistas de error con la marca y un enlace de regreso; el middleware responde con vista en rutas web | pendiente |
| DS11 | Textos del panel sin acentos ("Gestion de contenido", "Expedientes clinicos", "Iniciar Sesion", "Esta accion eliminara la promocion") frente al sitio público, que sí los lleva | `capturas/contenido-promociones-eliminar-1440.png`, `capturas/expediente-1440.png`, `capturas/login-1440.png` | Corregir la ortografía de los textos del panel | pendiente |
| DS12 | Movimiento sin alternativa: ninguna regla `prefers-reduced-motion` ni `motion-reduce:` en `resources/`; `transition-all` 94 veces; clases `animate-in…` sin CSS que las defina | `resources/views/**`, `resources/js/pages/**` | Versión sin movimiento y animar solo `transform` y `opacity` | pendiente |
| DS13 | Interfaz sin uso o inalcanzable: `x-ui.table` sin usos, `welcome.blade.php` sin ruta y la rama de botones "ver y editar" de la tarjeta de cita, que solo se pinta para quien no es administrador en una pantalla solo para administradores (editar sí es accesible desde el detalle de la cita) | `resources/views/components/ui/table.blade.php`, `resources/views/welcome.blade.php`, `resources/js/pages/agenda/index.js:202` | Decidir por pieza: usarla o retirarla | pendiente |
| DS14 | La mayoría de las pantallas del panel no tienen `h1`: el título de página es un `h2` (hay `h1` en 14 de 58 capturas a 390 px) | `resources/views/components/ui/page-hero.blade.php`; medidas de las capturas | Título de página como `h1` | pendiente |

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
