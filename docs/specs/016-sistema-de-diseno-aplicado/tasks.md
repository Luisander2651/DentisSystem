---
spec: 016-sistema-de-diseno-aplicado
plan: plan.md
status: draft
---

# Tareas · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

Convenciones de esta spec:
- "tokens" = los de la tabla "Tokens" del plan; "propuesta" = [design/propuesta.html](design/propuesta.html), variante B.
- "migrar" una vista o un JS = sustituir colores, radios y tamaños escritos a mano por tokens y por los componentes `x-ui.*`, y corregir sus textos (acentos, sin jerga técnica), sin cambiar su comportamiento ni su disposición a 1440 px. Al migrar un archivo se quita de la lista de pendientes de T011.
- `ui-audit` = `npm run test:ui`. Cada tarea de pantalla lo ejecuta filtrado a sus pantallas; "pasa" significa sin fallos en esas pantallas a 390 y 1440 px.
- Las fases del plan (Rollout) son los subtítulos de "Implementación". Cada fase termina con la suite Pest en verde.

## Constitution Check
Las tareas no añaden nada al análisis de [plan.md](plan.md); se revisa lo que el desglose pudo romper.

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec antes que código | ✅ | Toda tarea traza a un criterio o a un cambio del plan (tablas de Cobertura). | manual: `/analyze 016` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T009–T022) van antes de la implementación y cada tarea de implementación depende del suyo. | test: T079 (suite Pest y `ui-audit` dos veces) |
| P3 Capas del módulo | ✅ | Ninguna tarea toca `Domain/`; `app/Core` solo en T003 y T069. | manual: `/review` |
| P4 Contrato de API primero | ✅ | T016 fija el contrato de `api/*` antes de T069. | test: T016 |
| P5 Autorización en el servidor | ✅ | T014 prueba cada ruta web de administrador por actor antes de cambiar `OnlyAdmin` (T069). | test: T014 |
| P6 Validación de entrada con FormRequest | ➖ | Ninguna tarea cambia la entrada de un endpoint. | — |
| P7 Errores sin detalles internos | ✅ | T014 y T015 afirman que las páginas no muestran el mensaje interno ni la ruta. | test: T014, T015 |
| P8 Secretos fuera del repositorio | ✅ | T003 no guarda credenciales; el seeder (T004) no contiene contraseñas reutilizables. | lint: `gitleaks` en CI; test: T009 |
| P9 Migraciones reversibles | ➖ | Sin migraciones. | — |
| P10 Dependencias nuevas con ADR | ✅ | T002 solo actualiza el lockfile; ninguna tarea añade paquetes. | lint: `npm audit` en T002 y T079 |
| P11 Datos sensibles y modelo de amenazas | ✅ | Cada `TM#` tiene tarea de control y de test (tabla de amenazas). | manual: `aidd.py validate`, T079 |
| P12 Producción con aprobación y rollback | ✅ | T095–T098 quedan para `/release`; T002 cierra las vulnerabilidades previas. | manual: `/release` |
| P13 Lógica de negocio en el backend | ✅ | Las tareas de JS son de presentación; el texto por rol se decide en Blade (T028). | manual: `/review` |
| P14 Trazabilidad | ❌ aceptado: la página "Sin permiso" no emite evento de auditoría hasta el objetivo 5 del roadmap — aprobado por el usuario el 2026-10-06 | Ninguna tarea añade auditoría; `OnlyAdmin` sigue siendo el punto único de rechazo. | — |
| Definición de terminado | ✅ | T079 (Pint, Pest, build, `ui-audit`, `npm audit`, `aidd.py validate`), T078 (`CHANGELOG.md`) y T090–T092. | test: T079 |

## Preparación
- [ ] T001 Actualizar el sistema de diseño al sistema elegido: tabla Color, radios, tamaños, componentes y reglas de "Cambios a incorporar al sistema" del plan; `source: chosen`, `version: 2.0.0`, elección de la variante B en "Decisiones", deuda `DS1`–`DS11`, `DS13`, `DS14` → "la resuelve 016" — `docs/design/system.md`, `docs/design/system.html`, `docs/design/history/ (generado por copia del sistema 1.0.0)` — hecho cuando: `system.md` lista los tokens del plan con su contraste y `system.html` los muestra; el sistema anterior queda archivado — cubre: CA23
- [ ] T002 Actualizar `shell-quote` y `source-map-js` a la versión corregida con al menos 7 días de publicada — `package-lock.json` — hecho cuando: `npm audit --audit-level=high` no reporta nada, `package.json` no cambia y `npm run build` termina bien
- [ ] T003 Crear el comando `ui:audit-session {rol}`, que emite un token de un usuario de `UiAuditSeeder`, y registrarlo solo en `local` y `testing` — `app/Core/Console/UiAuditSessionCommand.php`, `bootstrap/app.php` — hecho cuando: T009 pasa — depende: T004, T009
- [ ] T004 Crear `UiAuditSeeder`: un usuario por rol, un paciente con expediente completo y otro vacío, citas en un mes fijo con los cuatro estados, tres tratamientos y contenido en las cuatro secciones, con imágenes neutras; se niega a correr en `production` — `database/seeders/UiAuditSeeder.php` — hecho cuando: `php artisan db:seed --class=UiAuditSeeder` deja esos datos en una base vacía y falla con `APP_ENV=production`
- [ ] T005 Crear el esqueleto de `ui-audit`: arranca Chrome sin interfaz, toma la sesión con `ui:audit-session`, recorre las pantallas y diálogos del inventario a 390 × 844 y 1440 × 900 y ejecuta los módulos de comprobación; acepta `--only <pantalla>` y `--baseline` — `tests/Browser/ui-audit.mjs`, `tests/Browser/screens.json`, `package.json` — hecho cuando: `npm run test:ui` recorre las 65 pantallas y diálogos y termina con código distinto de cero si un módulo reporta un fallo — depende: T003, T004
- [ ] T006 Guardar la línea base de composición a 1440 px (orden de secciones, encabezados y acciones por pantalla) antes de tocar vistas — `tests/Browser/baseline-1440.json (generado por npm run test:ui -- --baseline)`, `tests/Browser/checks-composition.mjs` — hecho cuando: el archivo existe para las 65 pantallas y `ui-audit` falla si se reordena una sección a mano — cubre: CA22 — depende: T005
- [ ] T007 Enmendar la constitución: principio P15 con el texto aprobado en el plan, versión 1.2.0 y entrada en "Enmiendas" — `docs/constitution.md` — hecho cuando: `aidd.py validate` sin errores y la constitución muestra P15 — cubre: CA24 — depende: T001
- [ ] T008 Actualizar `.ai/project.yaml → design` (`source: chosen`, `status: approved`) para que coincida con el frontmatter de `system.md` — `.ai/project.yaml` — hecho cuando: `aidd.py validate` sin errores — depende: T001

## Tests (antes de implementar)
- [ ] T009 [P] Test del comando de sesión: no existe con `APP_ENV=production` y solo emite sesiones de usuarios sembrados — `tests/Modules/Core/Integration/UiAuditSessionCommandTest.php` — hecho cuando: falla porque el comando no existe — cubre: TM5
- [ ] T010 [P] Test de tokens: los valores de la tabla Color de `system.md` son los de `@theme`, y cada par declarado cumple su contraste (texto ≥ 4.5:1; borde de campo y foco ≥ 3:1) — `tests/Modules/Core/Unit/Design/DesignTokensTest.php` — hecho cuando: falla porque `@theme` no declara los tokens — cubre: CA2, CA4, CA6, CA7 — depende: T001
- [ ] T011 [P] Test de solo tokens sobre `resources/views` y `resources/js`: sin colores literales, sin utilidades arbitrarias de color, sin los rosas antiguos, sin `text-primary` como color de texto, sin tamaños de letra arbitrarios menores de 12 px y sin `focus:outline-none`; cada regla con su control positivo; arranca con la lista de archivos pendientes — `tests/Modules/Core/Unit/Design/OnlyDesignTokensTest.php`, `tests/Modules/Core/Unit/Design/pending-files.php` — hecho cuando: los controles positivos pasan y el test falla si se saca un archivo de la lista sin migrarlo — cubre: CA1, CA2, CA3, CA5, CA8, CA24, CA29
- [ ] T012 [P] Test de textos: ninguna palabra sin acento del inventario ni término técnico (`API`, `backend`, `rutas admin`) en vistas ni en textos de JS; usa la misma lista de pendientes — `tests/Modules/Core/Unit/Design/UiCopyTest.php` — hecho cuando: falla sobre un archivo pendiente al sacarlo de la lista — cubre: CA16, CA30
- [ ] T013 [P] Test de piezas sin uso: no existen `x-ui.table`, `welcome.blade.php` ni la rama "ver y editar" de la tarjeta de cita — `tests/Modules/Core/Unit/Design/UnusedUiTest.php` — hecho cuando: falla porque las tres existen — cubre: CA17
- [ ] T014 [P] Test de "Sin permiso": cada ruta web protegida × actor sin permiso (asistente, doctor, paciente, staff inactivo) → 403 con la vista `errors.403`, texto en español, enlace a `/dashboard`, sin el mensaje interno ni datos de la pantalla — `tests/Modules/Core/Integration/WebAccessDeniedPagesTest.php` — hecho cuando: falla porque hoy responde JSON o la página por defecto — cubre: CA18, CA20, TM1, TM2
- [ ] T015 [P] Test de "No encontrada": con y sin sesión, 404 con la vista `errors.404`, enlace a `/dashboard` o a `/`, y una ruta con marcado no aparece en el cuerpo — `tests/Modules/Core/Integration/NotFoundPageTest.php` — hecho cuando: falla porque hoy responde la página por defecto — cubre: CA19, CA20, TM2, TM3
- [ ] T016 [P] Test de contrato: `api/*` con `only.admin` responde el JSON y el 403 actuales para cada actor sin permiso — `tests/Modules/Core/Integration/OnlyAdminApiContractTest.php` — hecho cuando: pasa hoy y fija el contrato antes de T069 — cubre: TM4
- [ ] T017 [P] Test de visitante: sin sesión, cada pantalla protegida redirige a `/login` — `tests/Modules/Core/Integration/GuestRedirectTest.php` — hecho cuando: pasa hoy y fija el comportamiento antes de T071 — cubre: CA21, TM7
- [ ] T018 [P] Test de estructura: cada pantalla, por rol, tiene exactamente un `h1`, la marca con su texto alternativo, `<link rel="icon">` hacia un archivo propio y ningún recurso de marca externo; la galería del panel no incluye la dirección interna de las imágenes en texto visible — `tests/Modules/Core/Integration/PageStructureTest.php` — hecho cuando: falla por los `h1` y la marca que faltan — cubre: CA14, CA15, CA32
- [ ] T019 [P] Test de textos por rol: insignia del encabezado y rol mostrado para administrador, asistente, doctor y paciente, según "Qué ve cada rol" del plan — `tests/Modules/Core/Integration/RoleCopyTest.php` — hecho cuando: falla porque todos ven "para administradores" — cubre: CA30
- [ ] T020 Comprobaciones generales de `ui-audit`, por pantalla: sin desborde horizontal, controles táctiles ≥ 44 × 44 px a 390 px, alturas y radios dentro de la escala, letra de campo ≥ 16 px y texto ≥ 12 px, contraste del texto y de los bordes de campo pintados, un solo estilo de foco, botón principal con un solo fondo y un solo hover, nada con texto recortado — `tests/Browser/checks-global.mjs` — hecho cuando: reporta los fallos conocidos de la revisión del 2026-10-06 (M01, M10, M13 y los tamaños) — cubre: CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10, CA28, CA29 — depende: T005
- [ ] T021 Comprobaciones de diálogos de `ui-audit`: título como nombre accesible, foco dentro al abrir y devuelto al cerrar, Escape cierra, ancho y alto dentro de la ventana con las acciones alcanzables — `tests/Browser/checks-dialogs.mjs` — hecho cuando: reporta M03 y M09 — cubre: CA12, CA13, CA26 — depende: T005
- [ ] T022 Comprobaciones por pantalla de `ui-audit`: agenda a 390 px sin desborde y con días legibles, expediente sin desplazamiento lateral interno y a la vista al elegir paciente, primer elemento de cada listado a ≤ 844 px, tarjeta de galería ≤ 844 px, un encabezado por pantalla, tarjeta sin datos ≤ 320 px, cabecera sin hueco — `tests/Browser/checks-screens.mjs` — hecho cuando: reporta M02, M04, M05, M06, M07, M08, M11 y M12 — cubre: CA11, CA25, CA27, CA31, CA32, CA33, CA34 — depende: T005

## Implementación

### Fase 2 · Tokens
- [ ] T025 Declarar los tokens en `@theme` (colores, radios, `--size-control`, tamaños de letra) y el estilo de foco único en `@layer base` — `resources/css/app.css` — hecho cuando: T010 pasa y `npm run build` genera las utilidades `bg-primary`, `text-ink` y `border-field` — cubre: CA2, CA4, CA6, CA7, CA8 — depende: T001, T010

### Fase 3 · Componentes
- [ ] T026 Rehacer `x-ui.button`: variantes `primary`, `secondary`, `danger` e `icon`, un solo tamaño (44 px, radio de control), texto en tinta sobre el primario, un solo hover — `resources/views/components/ui/button.blade.php` — hecho cuando: T011 pasa para el archivo · verificación manual: botones a 390 y 1440 px contra la sección 2 de la propuesta — cubre: CA3, CA4, CA9, CA10 — depende: T025
- [ ] T027 Rehacer `x-ui.input`: 44 px de alto, letra de 16 px, borde de campo, error enlazado con `aria-describedby`, botón de mostrar contraseña de 44 px, textos con acentos — `resources/views/components/ui/input.blade.php` — hecho cuando: T011 y T012 pasan para el archivo · verificación manual: campos contra la sección 3 de la propuesta — cubre: CA7, CA9, CA10, CA16, CA29 — depende: T025
- [ ] T028 Título de página como `h1` e insignia según el rol del actor — `resources/views/components/ui/h1.blade.php`, `resources/views/components/ui/page-hero.blade.php` — hecho cuando: T019 pasa y T011 pasa para ambos archivos — cubre: CA14, CA30 — depende: T019, T025
- [ ] T029 Crear la marca: icono en 64, 192 y 512 px y logo horizontal de 520 px desde `storage/app/public/Logos_Melissa_Lopez/`, `favicon.ico` real y el componente `x-ui.brand` (icono + "Dentissa", o logo completo) — `public/images/brand/ (generado por el redimensionado con Intervention Image)`, `public/favicon.ico`, `resources/views/components/ui/brand.blade.php` — hecho cuando: los archivos existen, `favicon.ico` pesa más de 0 bytes y el componente pinta el texto alternativo · verificación manual: el icono se ve en la pestaña del navegador — cubre: CA15 — depende: T025
- [ ] T030 Crear el diálogo base sobre `<dialog>`: título enlazado, cuerpo desplazable, pie fijo con las acciones, ancho y alto máximos de la ventana, y el módulo que abre y cierra por `data-dialog-open` y `data-dialog-close` devolviendo el foco — `resources/views/components/ui/dialog.blade.php`, `resources/js/ui/dialog.js` — hecho cuando: un diálogo de prueba montado con el componente pasa T021 a 390 y 1440 px — cubre: CA12, CA13, CA26 — depende: T021, T025
- [ ] T031 Crear `x-ui.stat` (contador compacto, tres por fila a 390 px) — `resources/views/components/ui/stat.blade.php` — hecho cuando: T011 pasa para el archivo — cubre: CA33 — depende: T025

### Fase 4 · Sitio público y acceso
- [ ] T032 Migrar el marco del sitio público: `x-ui.brand` en la barra, logo completo en el pie, etiquetas de icono — `resources/views/layouts/landing.blade.php`, `resources/views/components/landing/nav.blade.php`, `resources/views/components/landing/footer.blade.php` — hecho cuando: T011, T012 y T018 pasan para esas vistas — cubre: CA1, CA5, CA15 — depende: T026, T029
- [ ] T033 Migrar el inicio público, con las insignias de la imagen principal dentro del ancho y el botón de WhatsApp con texto en tinta — `resources/views/pages/landing/inicio.blade.php`, `resources/js/pages/landing/inicio.js` — hecho cuando: T011 pasa y `ui-audit --only inicio` pasa — cubre: CA1, CA6, CA28 — depende: T020, T032
- [ ] T034 Migrar "Acerca de nosotros" y "Contacto" — `resources/views/pages/landing/acerca.blade.php`, `resources/views/pages/landing/contacto.blade.php` — hecho cuando: T011 pasa y `ui-audit` pasa en ambas — cubre: CA1, CA9 — depende: T032
- [ ] T035 Migrar la galería pública y su visor al diálogo base — `resources/views/pages/landing/galeria.blade.php`, `resources/js/pages/landing/galeria.js` — hecho cuando: T011 pasa y `ui-audit` pasa en galería y en su visor · verificación manual: las cuatro pantallas del sitio público a 390 y 1440 px contra la propuesta y contra `docs/design/capturas/` — cubre: CA1, CA12, CA22 — depende: T030, T032
- [ ] T036 Migrar el marco de acceso, iniciar sesión y registrarse: logo completo, etiqueta "Contraseña", acentos — `resources/views/layouts/app.blade.php`, `resources/views/pages/auth/login.blade.php`, `resources/views/pages/auth/register.blade.php` — hecho cuando: T011, T012 y T018 pasan para esas vistas — cubre: CA1, CA15, CA16, CA30 — depende: T027, T029
- [ ] T037 Migrar recuperar y restablecer contraseña y cerrar sesión — `resources/views/pages/auth/forgot-password.blade.php`, `resources/views/pages/auth/reset-password.blade.php`, `resources/views/pages/auth/logout.blade.php` — hecho cuando: T011 y T012 pasan · verificación manual: las seis pantallas de acceso a 390 y 1440 px contra la propuesta — cubre: CA1, CA16, CA22 — depende: T036
- [ ] T038 Migrar los textos y clases de los scripts de acceso — `resources/js/pages/auth/forgot-password.js`, `resources/js/pages/auth/reset-password.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en las pantallas de acceso — cubre: CA1, CA16 — depende: T037

### Fase 5 · Panel, pacientes, usuarios y tratamientos
- [ ] T039 Migrar los tres marcos del panel: etiquetas de icono y fondo con tokens — `resources/views/layouts/dashboard.blade.php`, `resources/views/layouts/admin.blade.php`, `resources/views/layouts/patient.blade.php` — hecho cuando: T011 y T018 pasan para esos marcos — cubre: CA1, CA15 — depende: T029
- [ ] T040 Migrar el menú lateral: `x-ui.brand`, elemento activo en tinta con acento, rol en español, elementos de 44 px y cabecera sin hueco bajo su contenido — `resources/views/components/ui/sidebar.blade.php` — hecho cuando: T011, T012 y T019 pasan y `ui-audit` no reporta M11 — cubre: CA5, CA9, CA30, CA34 — depende: T022, T039
- [ ] T041 Migrar el inicio del panel para los cuatro roles — `resources/views/pages/dashboard.blade.php`, `resources/js/pages/dashboard.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en los cuatro inicios y en el menú abierto · verificación manual: inicio del panel por rol a 390 y 1440 px contra la propuesta — cubre: CA1, CA22, CA30 — depende: T028, T031, T040
- [ ] T042 Migrar el listado de pacientes: contadores en una fila, buscador y filtros compactos, tarjetas con controles de 44 px — `resources/views/pages/patients/index.blade.php`, `resources/js/pages/patients/index.js` — hecho cuando: T011 pasa y `ui-audit --only pacientes` pasa (primer paciente a ≤ 844 px) — cubre: CA1, CA9, CA33 — depende: T022, T041
- [ ] T043 Migrar al diálogo base los diálogos de crear y editar paciente y el de confirmar eliminación (compartido con usuarios) — `resources/views/components/ui/create-patient-modal.blade.php`, `resources/views/components/ui/edit-patient-modal.blade.php`, `resources/views/components/ui/confirm-delete-modal.blade.php` — hecho cuando: T011 y T012 pasan para las tres — cubre: CA12, CA13, CA26 — depende: T030
- [ ] T044 Adaptar los scripts de pacientes al módulo de diálogos — `resources/js/pages/patients/create-patient.js`, `resources/js/pages/patients/edit-patient.js`, `resources/js/pages/patients/delete-patient.js` — hecho cuando: T011 pasa, los tests de pacientes existentes pasan y `ui-audit` pasa en los tres diálogos — cubre: CA12, CA13, CA26 — depende: T043
- [ ] T045 Migrar el listado de usuarios y sus diálogos de crear y editar — `resources/views/pages/usuarios/index.blade.php`, `resources/views/components/ui/create-user-modal.blade.php`, `resources/views/components/ui/edit-user-modal.blade.php` — hecho cuando: T011 y T012 pasan para las tres — cubre: CA1, CA12, CA33 — depende: T043
- [ ] T046 Adaptar los scripts de usuarios al módulo de diálogos — `resources/js/pages/usuarios/create-user.js`, `resources/js/pages/usuarios/edit-user.js`, `resources/js/pages/usuarios/delete-user.js` — hecho cuando: T011 pasa y `ui-audit` pasa en usuarios y sus tres diálogos · verificación manual: pacientes y usuarios a 390 y 1440 px contra la propuesta — cubre: CA12, CA13, CA22, CA26 — depende: T045
- [ ] T047 Migrar tratamientos y sus cuatro diálogos, con textos sin jerga técnica — `resources/views/pages/tratamientos/index.blade.php`, `resources/js/pages/tratamientos/index.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en tratamientos y sus diálogos · verificación manual: tratamientos a 390 y 1440 px contra la propuesta — cubre: CA1, CA12, CA16, CA30, CA33 — depende: T030, T031

### Fase 6 · Expedientes y contenido
- [ ] T048 Migrar expedientes: en móvil, al elegir un paciente se muestra su expediente en lugar de la lista, con un enlace para volver; a partir de `md`, la disposición actual — `resources/views/pages/records/index.blade.php`, `resources/js/pages/records/index.js` — hecho cuando: T011 y T012 pasan, `RecordsScreenTest` pasa y `ui-audit` no reporta M06 ni M12 en expedientes — cubre: CA1, CA31, CA33 — depende: T022, T031
- [ ] T049 Migrar los registros de contacto y dirección: pares de etiqueta y valor por debajo de `md`, tabla actual desde `md` — `resources/views/components/records/contact-info-table.blade.php`, `resources/views/components/records/address-table.blade.php` — hecho cuando: T011 y T012 pasan y `ui-audit` no reporta desplazamiento lateral en esas secciones ni en sus formularios — cubre: CA27 — depende: T048
- [ ] T050 Migrar los registros de datos médicos e historial de citas, con el mensaje de sección vacía completo — `resources/views/components/records/medical-data-table.blade.php`, `resources/views/components/records/appointments-history-table.blade.php` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en el expediente de administrador y de doctor · verificación manual: expediente a 390 y 1440 px contra la propuesta y contra `docs/design/capturas/` — cubre: CA22, CA27 — depende: T049
- [ ] T051 Migrar el marco de gestión de contenido: un solo encabezado de página y pestañas como filtros de 44 px — `resources/views/pages/contenido/index.blade.php`, `resources/js/pages/contenido/index.js`, `resources/js/pages/contenido/tabs.js` — hecho cuando: T011 y T012 pasan y `ui-audit` no reporta M08 — cubre: CA1, CA34 — depende: T022, T031
- [ ] T052 Migrar el listado de galería: tarjeta que cabe en una pantalla, identificada por su descripción, sin nombre ni dirección internos, sin texto ilegible sobre la imagen — `resources/views/pages/contenido/galeria/index.blade.php`, `resources/js/pages/contenido/galeria/index.js` — hecho cuando: T011 y T018 pasan y `ui-audit` no reporta M07 — cubre: CA6, CA32, CA33 — depende: T051
- [ ] T053 Migrar al diálogo base los tres diálogos de galería — `resources/views/pages/contenido/galeria/create-modal.blade.php`, `resources/views/pages/contenido/galeria/edit-modal.blade.php`, `resources/views/pages/contenido/galeria/delete-modal.blade.php` — hecho cuando: T011 y T012 pasan para las tres — cubre: CA12, CA13, CA28 — depende: T030, T052
- [ ] T054 Adaptar los scripts de los diálogos de galería — `resources/js/pages/contenido/galeria/create-modal.js`, `resources/js/pages/contenido/galeria/edit-modal.js`, `resources/js/pages/contenido/galeria/delete-modal.js` — hecho cuando: T011 pasa y `ui-audit` pasa en los tres diálogos (sin M09 ni M13) — cubre: CA12, CA13, CA26, CA28 — depende: T053
- [ ] T055 Migrar el listado de promociones — `resources/views/pages/contenido/promociones/index.blade.php`, `resources/js/pages/contenido/promociones/index.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en la pestaña — cubre: CA1, CA33 — depende: T051
- [ ] T056 Migrar al diálogo base los tres diálogos de promociones — `resources/views/pages/contenido/promociones/create-modal.blade.php`, `resources/views/pages/contenido/promociones/edit-modal.blade.php`, `resources/views/pages/contenido/promociones/delete-modal.blade.php` — hecho cuando: T011 y T012 pasan para las tres — cubre: CA12, CA13 — depende: T030, T055
- [ ] T057 Adaptar los scripts de los diálogos de promociones — `resources/js/pages/contenido/promociones/create-modal.js`, `resources/js/pages/contenido/promociones/edit-modal.js`, `resources/js/pages/contenido/promociones/delete-modal.js` — hecho cuando: T011 pasa y `ui-audit` pasa en los tres diálogos — cubre: CA12, CA13, CA26 — depende: T056
- [ ] T058 Migrar el listado de certificaciones — `resources/views/pages/contenido/certificaciones/index.blade.php`, `resources/js/pages/contenido/certificaciones/index.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en la pestaña — cubre: CA1, CA33 — depende: T051
- [ ] T059 Migrar al diálogo base los tres diálogos de certificaciones — `resources/views/pages/contenido/certificaciones/create-modal.blade.php`, `resources/views/pages/contenido/certificaciones/edit-modal.blade.php`, `resources/views/pages/contenido/certificaciones/delete-modal.blade.php` — hecho cuando: T011 y T012 pasan para las tres — cubre: CA12, CA13 — depende: T030, T058
- [ ] T060 Adaptar los scripts de los diálogos de certificaciones — `resources/js/pages/contenido/certificaciones/create-modal.js`, `resources/js/pages/contenido/certificaciones/edit-modal.js`, `resources/js/pages/contenido/certificaciones/delete-modal.js` — hecho cuando: T011 pasa y `ui-audit` pasa en los tres diálogos (sin M09) — cubre: CA12, CA13, CA26 — depende: T059
- [ ] T061 Migrar el listado de testimonios — `resources/views/pages/contenido/testimonios/index.blade.php`, `resources/js/pages/contenido/testimonios/index.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en la pestaña — cubre: CA1, CA29, CA33 — depende: T051
- [ ] T062 Migrar al diálogo base los dos diálogos de testimonios — `resources/views/pages/contenido/testimonios/edit-modal.blade.php`, `resources/views/pages/contenido/testimonios/delete-modal.blade.php` — hecho cuando: T011 y T012 pasan para las dos — cubre: CA12, CA13 — depende: T030, T061
- [ ] T063 Adaptar los scripts de los diálogos de testimonios — `resources/js/pages/contenido/testimonios/edit-modal.js`, `resources/js/pages/contenido/testimonios/delete-modal.js` — hecho cuando: T011 pasa y `ui-audit` pasa en los dos diálogos · verificación manual: las cuatro pestañas de contenido a 390 y 1440 px contra la propuesta y contra `docs/design/capturas/` — cubre: CA12, CA13, CA22, CA26 — depende: T062

### Fase 7 · Agenda
- [ ] T064 Migrar la agenda y su calendario: filtros en una fila desplazable dentro del ancho, días de al menos 44 px con el número de citas, lista del día debajo, tarjeta "Citas para hoy" sin hueco — `resources/views/pages/agenda/index.blade.php`, `resources/views/components/calendar/grid.blade.php` — hecho cuando: T011 y T012 pasan para ambas — cubre: CA1, CA9, CA11, CA34 — depende: T022, T031
- [ ] T065 Adaptar el script de la agenda: pintar el número de citas por día y la lista del día con hora, paciente y estado; nombre accesible del día con su número de citas; retirar la rama "ver y editar" inalcanzable — `resources/js/pages/agenda/index.js` — hecho cuando: T011 y T013 (parte de la rama) pasan y `ui-audit --only agenda` no reporta M01, M02 ni M04 — cubre: CA11, CA17, CA25 — depende: T013, T064
- [ ] T066 Migrar al diálogo base "Citas del día" y "Detalle de la cita" — `resources/views/components/calendar/day-details-modal.blade.php`, `resources/views/components/calendar/view-appointment-modal.blade.php`, `resources/js/pages/agenda/view-appointment.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en ambos diálogos y en el detalle de una cita asignada — cubre: CA12, CA13, CA26 — depende: T030, T065
- [ ] T067 Migrar al diálogo base "Nueva cita" y "Editar cita" — `resources/views/components/calendar/create-appointment-modal.blade.php`, `resources/views/components/calendar/edit-appointment-modal.blade.php`, `resources/js/pages/agenda/create-appointment.js` — hecho cuando: T011 y T012 pasan y `ui-audit` pasa en ambos diálogos · verificación manual: con un lector de pantalla, "Nueva cita" anuncia su título al abrirse y el foco vuelve al botón al cerrarla — cubre: CA12, CA13, CA26 — depende: T066
- [ ] T068 Migrar al diálogo base "Completar cita" (pasos 1 y 2, error de validación y receta) — `resources/views/components/calendar/complete-appointment-modal.blade.php`, `resources/js/pages/agenda/complete-appointment.js` — hecho cuando: T011 y T012 pasan, los tests de completar cita existentes pasan y `ui-audit` pasa en sus cuatro estados · verificación manual: la agenda y sus diálogos a 390 y 1440 px contra la propuesta y contra `docs/design/capturas/` — cubre: CA12, CA13, CA22, CA26 — depende: T067

### Fase 8 · Errores, limpieza y cierre
- [ ] T069 Hacer que `OnlyAdmin` responda `abort(403)` fuera de `api/*`, conservando sus tres comprobaciones y el JSON actual en `api/*` — `app/Core/Middlewares/OnlyAdmin.php` — hecho cuando: T016 sigue pasando y T014 deja de fallar por el JSON — cubre: CA18, CA20, TM1, TM4 — depende: T014, T016
- [ ] T070 Crear el marco de error y las vistas "Sin permiso" y "No encontrada": logo, texto fijo en español, un `h1`, botón al inicio; sin imprimir la excepción ni la URL — `resources/views/layouts/error.blade.php`, `resources/views/errors/403.blade.php`, `resources/views/errors/404.blade.php` — hecho cuando: T014 pasa y T011 y T012 pasan para las tres · verificación manual: ambas páginas a 390 y 1440 px contra la sección 5 de la propuesta — cubre: CA18, CA19, CA20, TM2, TM3 — depende: T029, T069
- [ ] T071 Añadir `Route::fallback` al grupo web para que el 404 conozca la sesión y enlace a `/dashboard` o a `/`, sin alterar las rutas protegidas — `routes/web.php` — hecho cuando: T015 pasa y T017 sigue pasando — cubre: CA19, CA21, TM7 — depende: T015, T017, T070
- [ ] T072 Eliminar `x-ui.table` y `welcome.blade.php` — `resources/views/components/ui/table.blade.php`, `resources/views/welcome.blade.php` — hecho cuando: T013 pasa — cubre: CA17 — depende: T013, T065
- [ ] T073 Vaciar la lista de archivos pendientes y migrar lo que quede (`app.js`, `bootstrap.js`) — `tests/Modules/Core/Unit/Design/pending-files.php`, `resources/js/app.js`, `resources/js/bootstrap.js` — hecho cuando: la lista está vacía y T011 y T012 pasan sobre todo `resources/` — cubre: CA1, CA2, CA16 — depende: T038, T047, T050, T063, T068, T070
- [ ] T074 Actualizar los tests existentes que afirman marcado cambiado, sin cambiar lo que comprueban — `tests/Modules/Users/Integration/StaffNavigationTest.php`, `tests/Modules/Patients/Integration/RecordsScreenTest.php`, `tests/Modules/Core/Integration/WebUnexpectedErrorTest.php` — hecho cuando: los tres pasan y siguen cubriendo los mismos criterios de las specs 014 y 015 — depende: T073
- [ ] T075 Añadir a CI el paso de `ui-audit`: sembrar `UiAuditSeeder`, levantar la app y ejecutar `npm run test:ui` — `.github/workflows/tests.yml` — hecho cuando: el paso corre en el PR y falla si `ui-audit` falla — depende: T073
- [ ] T076 Rehacer las capturas y cerrar la deuda en el sistema de diseño: capturas de todas las pantallas a 390 y 1440 px, incluidas las dos nuevas, y `DS1`–`DS11`, `DS13`, `DS14` como "resuelta por 016" — `docs/design/capturas/ (generado por /init --upgrade --redo-design)`, `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `aidd.py validate` sin errores y el inventario de vistas no tiene pantallas sin captura · verificación manual: `system.html` muestra las pantallas nuevas y ninguna captura es anterior a esta spec — cubre: CA23 — depende: T073
- [ ] T078 Documentar el cambio: `npm run test:ui` y la regla de solo tokens en `AGENTS.md`, fila de rutas web con vista 403 en `docs/security.md`, y entrada en `CHANGELOG.md` — `AGENTS.md`, `docs/security.md`, `CHANGELOG.md` — hecho cuando: los tres describen lo implementado y `AGENTS.md` sigue por debajo de 150 líneas — depende: T075
- [ ] T079 Verificación final: Pint sobre lo tocado, suite Pest completa, `npm run build`, `ui-audit` dos veces seguidas, `npm audit --audit-level=high`, `composer audit --locked` y `aidd.py validate` — sin archivos — hecho cuando: todo pasa y las dos ejecuciones de `ui-audit` dan el mismo resultado — cubre: CA23, TM6 — depende: T074, T075, T076, T078

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: Frontend (tokens en `@theme`, componentes `x-ui.*`, `resources/js/ui/dialog.js`, vistas de error), Backend (`OnlyAdmin` con vista en web, `Route::fallback`) y "Deuda técnica y riesgos observados"
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: paso `ui-audit` en el pipeline y purga de caché de Cloudflare para `favicon.ico` y `public/images/brand/`; `observability.md` sin cambios
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - No hay staging: se verifica en producción tras T097, como en la spec 015. `/pagina-que-no-existe` responde 404 con la marca; con un usuario asistente, `/agenda` responde 403 con "Sin permiso"; el icono de pestaña se ve tras purgar la caché de Cloudflare.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011 | T032, T033, T034, T035, T036, T037, T038, T039, T041, T042, T045, T047, T048, T051, T055, T058, T061, T064, T073 |
| CA2 | T010, T011 | T025, T073 |
| CA3 | T011, T020 | T026 |
| CA4 | T010, T020 | T025, T026 |
| CA5 | T011, T020 | T032, T040 |
| CA6 | T010, T020 | T025, T033, T052 |
| CA7 | T010, T020 | T025, T027 |
| CA8 | T011, T020 | T025 |
| CA9 | T020 | T026, T027, T034, T040, T042, T064 |
| CA10 | T020 | T026, T027 |
| CA11 | T022 | T064, T065 |
| CA12 | T021 | T030, T035, T043, T044, T045, T046, T047, T053, T054, T056, T057, T059, T060, T062, T063, T066, T067, T068 |
| CA13 | T021 | T030, T043, T044, T046, T053, T054, T056, T057, T059, T060, T062, T063, T066, T067, T068 |
| CA14 | T018 | T028 |
| CA15 | T018 | T029, T032, T036, T039 |
| CA16 | T012 | T027, T036, T037, T038, T047, T073 |
| CA17 | T013 | T065, T072 |
| CA18 | T014 | T069, T070 |
| CA19 | T015 | T070, T071 |
| CA20 | T014, T015 | T069, T070 |
| CA21 | T017 | T071 |
| CA22 | T006 | T035, T037, T041, T046, T050, T063, T068 |
| CA23 | T079 | T001, T076 |
| CA24 | T011 | T007 |
| CA25 | T022 | T065 |
| CA26 | T021 | T030, T043, T044, T046, T054, T057, T060, T063, T066, T067, T068 |
| CA27 | T022 | T049, T050 |
| CA28 | T020 | T033, T053, T054 |
| CA29 | T011, T020 | T027, T061 |
| CA30 | T012, T019 | T028, T036, T040, T041, T047 |
| CA31 | T022 | T048 |
| CA32 | T018, T022 | T052 |
| CA33 | T022 | T031, T042, T045, T047, T048, T052, T055, T058, T061 |
| CA34 | T022 | T040, T051, T064 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T069 | T014 |
| TM2 | T070 | T014, T015 |
| TM3 | T070 | T015 |
| TM4 | T069 | T016 |
| TM5 | T003 | T009 |
| TM6 | T002 | T079 |
| TM7 | T071 | T017 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Dependencias | T002 |
| Sistema de diseño | T001, T008, T076 |
| Constitución | T007 |
| Tokens | T025 |
| Componentes existentes (botón, campo, título, encabezado) | T026, T027, T028 |
| Componentes nuevos (marca, diálogo, contador) | T029, T030, T031 |
| Marca | T029, T032, T036, T039 |
| Sitio público | T032, T033, T034, T035 |
| Acceso | T036, T037, T038 |
| Panel (marco) | T039, T040, T041 |
| Pacientes y usuarios | T042, T043, T044, T045, T046 |
| Expedientes | T048, T049, T050 |
| Tratamientos | T047 |
| Contenido | T051, T052, T053, T054, T055, T056, T057, T058, T059, T060, T061, T062, T063 |
| Agenda | T064, T065, T066, T067, T068 |
| Errores web | T069, T070, T071 |
| Limpieza | T072, T073 |
| Pruebas | T003, T004, T005, T006, T009–T022, T074, T075 |
| Documentación | T078, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA en lo cubierto por CA6–CA9, CA12–CA14 y CA29 | T010, T011, T018, T020, T021 |
| Sin desplazamiento horizontal a 390 y 1440 px | T020 |
| Sin regresiones: la suite existente pasa | T074, T079 |
| Logo e icono servidos por la aplicación | T018 |
