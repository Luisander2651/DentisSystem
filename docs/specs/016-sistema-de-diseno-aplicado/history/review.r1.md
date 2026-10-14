---
spec: 016-sistema-de-diseno-aplicado
verdict: changes_requested
round: 1
date: 2026-10-14
base: 744284f
head: b23745f
human_signoff: pending
---

# Review · 016 Sistema de diseño aplicado: paleta nueva, accesibilidad y pantallas de error

Ronda 1. Rango `744284f..b23745f` (`impl_base` de `tasks.md`): 154 archivos de código, unas
15 000 líneas de diff (`code.diff` de 1,07 MB). Por su tamaño se repartió en cuatro revisores
independientes con los encargos de spec, constitución, calidad y seguridad juntos: servidor y
tests PHP; la herramienta `ui-audit`; tokens, componentes, marcos y pantallas públicas y de
acceso; y pantallas del panel. Los dos últimos hicieron también la revisión de accesibilidad: la
sesión no ofrece el plugin `design` (`design:accessibility-review`, `design:design-critique`), así
que aplicaron la lista de la skill. Cada hallazgo de esta tabla se comprobó abriendo el archivo;
los bloqueantes R1 y R2 se reprodujeron además en la app local.

## Resumen
**changes_requested.** La implementación cubre la spec en lo esencial y la verificación automática
está en verde, pero hay cinco bloqueantes. Con sesión iniciada, la página 405 lleva al inicio del
sitio (CA41), y su test pasaba sin usar la cookie de sesión. El aviso de rol no reconocido al
editar un usuario no se ve. Y en varios formularios los errores no quedan ligados a su campo
(CA37): la validación antes de enviar de los diálogos de contenido y de las prescripciones, y los
errores 422 del servidor en todos los formularios. A eso se suman importantes que el usuario
decidió corregir: regresiones de la migración (campos válidos marcados con error, diálogos que se
cierran al arrastrar, errores que desaparecen) y comprobaciones de `ui-audit` que dejan criterios
con menos protección de la que dice el plan.

Conteo: bloqueante 5 · importante 29 · menor 27

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel --recreate-databases` (local, `b23745f` sin cambios de código desde `efdb9f4`) | ✅ 1249 pasan; 1 fallo intermitente de PostgreSQL en paralelo (`DeleteAppointmentTest`, que solo pasa 5/5; ya en "Pendientes y deuda"). Sin marcas `016: pendiente` |
| `vendor/bin/pint` sobre los PHP del PR (CI, paso "Pint sobre los archivos PHP del PR") | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui` (CI, push 38525097662 y PR 38525102845 sobre `efdb9f4`) | ✅ dos ejecuciones sin fallos, paso bloqueante |
| `python .ai/bin/aidd.py validate docs/specs/016-sistema-de-diseno-aplicado` | ✅ 0 errores |

## Criterios de aceptación
Los 43 criterios tienen test de servidor (Pest) o de navegador (`ui-audit`) según la trazabilidad
del plan, y todos pasan. Los que no se cumplen del todo o cuyo test no prueba lo que dice:

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA12 | `checks-dialogs` | ⚠ | El foco se pierde al recargar el listado tras guardar (R15); el test no comprueba el foco de vuelta en las entradas con `confirmOnEscape` ni que el nombre sea el título (R28, R29) |
| CA30 | `UiCopyTest`, `RoleCopyTest` | ⚠ | Cumple, pero ningún test protege la etiqueta "Password" (R16) |
| CA37 | `checks-a11y` | ❌ | Errores de validación de contenido y de prescripciones, y errores 422 de todos los formularios, sin ligar a su campo (R3, R4, R5); errores de campo que no se anuncian al salir de él (R11); comprobaciones que pasan en silencio (R17–R20) |
| CA40, CA43 | `checks-a11y` | ⚠ | "Agenda una Cita" y "Ver Ubicación": un botón dentro de un enlace (R10) |
| CA41 | `OtherErrorPagesTest` | ❌ | El 405 con sesión enlaza al inicio del sitio; el caso "signed in" usa `actingAs` y no la cookie (R1) |
| CA25, CA32, CA34, CA38, CA42 y RNF de 768 px | `checks-screens`, `checks-a11y`, `checks-global` | ⚠ | La app cumple; el test no mide todo lo que dice (R21, R23, R25–R27) |

## Conformidad con el plan
- Cambios fuera del plan: `.gitignore` (carpeta local de capturas de `--screenshots`) y `tests/Browser/audit-lib.mjs`, justificados en las notas de T005, T020 y T033. Ninguna ruta protegida cambió.
- Partes del plan sin implementar: ninguna. T001, T007, T074, T076 y T078 solo cambian documentos o capturas generadas, fuera de `code.diff`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | Toda tarea traza a un criterio; spec reaprobada el 2026-10-13 |
| P2 Test que falla antes y pasa después | ⚠ | Los tests existen y fallaron antes de cada cambio (notas de tareas); R1 es un falso positivo de `OtherErrorPagesTest` y R17–R32 son comprobaciones de `ui-audit` que pueden no fallar |
| P3 Capas del módulo | ✅ | Solo cambian `resources/`, dos middlewares, dos comandos de `app/Core`, el seeder y `bootstrap/app.php` |
| P4 Contrato de API primero | ✅ | `OnlyAdminApiContractTest` y `ApiNotFoundContractTest`: `api/*` sin cambios |
| P5 Autorización en el servidor | ✅ | `WebAccessDeniedPagesTest` por ruta y actor |
| P6 Validación con FormRequest | ➖ | Ningún endpoint cambia su entrada |
| P7 Errores sin detalles internos | ✅ | Vistas y JSON fijos en español; ni la excepción ni la URL se imprimen |
| P8 Secretos fuera del repositorio | ✅ | gitleaks en CI sin hallazgos; los comandos no guardan credenciales |
| P9 Migraciones reversibles | ➖ | Sin migraciones |
| P10 Dependencias con ADR | ✅ | Ningún paquete nuevo; `overrides` de `shell-quote` aprobado y con retirada en el roadmap |
| P11 Datos sensibles | ✅ | Sin logs nuevos; capturas solo con datos de `UiAuditSeeder` |
| P12 Producción con aprobación y rollback | ✅ | Rollback por tag, sin migraciones |
| P13 Lógica en el backend | ✅ | JS de presentación; texto por rol decidido en Blade |
| P14 Trazabilidad | ❌ aceptado | Desviación aceptada por el usuario el 2026-10-06 (sin registro de auditoría hasta el objetivo 5) |
| P15 Solo el sistema de diseño | ✅ | `DesignTokensTest` y `OnlyDesignTokensTest` sobre todo `resources/`, lista de pendientes vacía |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (secretos, CI del PR) | ✅ 0 hallazgos |
| semgrep `p/php` (SAST, CI del PR) | ✅ 0 hallazgos |
| `composer audit` / `npm audit --audit-level=high` (SCA) | ✅ crít: 0 · alta: 0 (CI y local); el job falló una vez por un corte de red con packagist y pasó al relanzarlo |
| trivy config (contenedores) | ✅ sin cambios en `docker/` |

| Tema OWASP 2025 | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | Middlewares conservan sus comprobaciones (TM1, `WebAccessDeniedPagesTest`); visitante sin sesión → `/login` (TM7) |
| A03 Cadena de suministro | ✅ | Sin paquetes nuevos; versiones con más de 7 días (TM6) |
| A05 Inyección | ✅ | La página 404 no refleja la URL (TM3); sin `{!! !!}` nuevos con datos; el `innerHTML` de los scripts escapa los datos de la API |
| A07 Autenticación | ✅ | `ui:audit-session` y `UiAuditSeeder` solo en `local` y `testing`, con test (TM5) |
| A10 Condiciones excepcionales | ✅ | Errores web con texto fijo, también en JSON (TM2); la ruta de respaldo no abre sesión (TM8) |

## Observabilidad
- Eventos de auditoría de la spec: ninguno (P14 aceptado; la spec no emite eventos).
- Datos sensibles en logs del código tocado: ninguno; la spec no añade logs.
- `request_id` en logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `bootstrap/app.php:96-115`, `resources/views/layouts/error.blade.php:7`; `tests/Modules/Core/Integration/OtherErrorPagesTest.php:28-38` | CA41: el 405 se lanza antes de los middlewares de ruta, así que no se inyecta el token de la cookie y quien tiene sesión ve "Ir al inicio del sitio" (reproducido: `POST /dashboard` con `auth_token` → 405 con ese enlace). El caso con sesión del test usa `actingAs` y no la cookie | La página de error resuelve la sesión desde la cookie aunque no haya corrido el middleware; el test usa `withUnencryptedCookie('auth_token', …)` como `NotFoundPageTest` |
| R2 | bloqueante | `resources/js/pages/usuarios/edit-user.js:135-140` | Regla BR-28: el aviso "El rol de este usuario no es válido…" se escribe antes de `openDialog`, en la región del marco, que queda inerte detrás del diálogo: no se ve ni se anuncia | Abrir el diálogo antes de avisar (o aviso fijo junto al selector de rol, ligado con `aria-describedby`) |
| R3 | bloqueante | `resources/js/pages/contenido/certificaciones/create-modal.js:147`, `certificaciones/edit-modal.js:167`, `promociones/create-modal.js:119`, `promociones/edit-modal.js:139`, `galeria/create-modal.js:142` | CA37: la validación antes de enviar da un mensaje general y no marca ningún campo (descripción, fecha e imagen no son `x-ui.input`) | Cada campo vacío con su error ligado, como `setFieldError` de `complete-appointment.js` |
| R4 | bloqueante | `resources/js/pages/agenda/complete-appointment.js:242, 468` | CA37: el error de una prescripción solo sale en la región general, sin marcar los campos de su fila | `setFieldError` en cada campo inválido de la fila |
| R5 | bloqueante | `resources/views/pages/auth/register.blade.php:186-189`, `resources/js/pages/patients/create-patient.js:135` y todos los `announceFailure(status, payload)` de formularios | CA37: los errores 422 del servidor solo se anuncian (el primero) y no quedan en su campo (`payload.errors.<campo>`) | Ayudante común en `ui/status.js` que marca cada campo de `payload.errors`; cada formulario lo usa |
| R6 | importante | `resources/js/pages/auth/reset-password.js:65-73` | El error de contraseñas distintas desaparece en cada tecla: el `input` de `x-ui.input` corre después y lo quita; solo vuelve al salir del campo | El componente respeta un error propio del script, o el script lo reaplica después |
| R7 | importante | `resources/js/pages/auth/reset-password.js:149` | Condición invertida en `finally`: tras un fallo el botón queda deshabilitado y no se puede reintentar | Rehabilitar cuando el éxito sigue oculto |
| R8 | importante | `resources/js/ui/status.js:41-42` | El temporizador vuelve a buscar la región al vaciarla: si el diálogo se cerró, vacía la del marco antes de tiempo y deja "Enviando…" fijo en la del diálogo, que aparece al reabrirlo | Vaciar el mismo `target` donde se escribió; vaciar la región del diálogo al cerrarlo |
| R9 | importante | `resources/js/ui/dialog.js:49-54` | Pulsar dentro del diálogo y soltar sobre el fondo lo cierra (`click` con `target === dialog`) y se pierde lo escrito | Cerrar solo si `pointerdown` también fue en el fondo |
| R10 | importante | `resources/views/pages/landing/inicio.blade.php:27-36` | "Agenda una Cita" y "Ver Ubicación": un `<button>` dentro de un `<a>` (controles anidados, dos paradas de tabulador; el botón interno no avisa de la otra pestaña) | `x-ui.button` con `href`, sin el enlace exterior |
| R11 | importante | `resources/views/components/ui/input.blade.php:90, 139-175` | Se quitó `aria-live` del error de campo: el error que se detecta al salir del campo queda ligado pero no se anuncia (CA37) | Anunciar el paso de válido a inválido |
| R12 | importante | `resources/views/components/records/contact-info-table.blade.php:14`, `address-table.blade.php:14-20`, `medical-data-table.blade.php:13`, diálogos de certificaciones y promociones, `pages/tratamientos/index.blade.php:103, 153` | Regresión: teléfono, calle, código postal, tipo de sangre y nombres de certificación, promoción y tratamiento usan `variant="string"` (solo letras): "+52 81 1234 5678", "Av. Juárez 123", "64000", "O+" o "ISO 9001" se marcan "Solo se permite texto." (no impide guardar) | Variante de texto libre en esos campos |
| R13 | importante | `resources/js/ui/status.js:9, 42` con los scripts de diálogos | Se retiraron las cajas de error fijas y el error de guardado desaparece a los 12 s aunque siga sin corregirse | El error de un diálogo abierto se mantiene hasta el siguiente envío o el cierre |
| R14 | importante | `resources/js/pages/agenda/index.js:366` | A 390 px, tocar un día solo repinta la lista bajo el calendario, fuera de la pantalla: no se ve ni se anuncia nada | Anunciar las citas del día elegido y llevar la lista a la vista |
| R15 | importante | `complete-appointment.js:383`, `create-appointment.js:638`, `contenido/*/edit-modal.js`, `records/index.js:920`, `patients/edit-patient.js:239`, `usuarios/edit-user.js:240` | CA12: tras guardar se repinta el listado y el control que abrió el diálogo ya no existe; el foco cae a `body` | Devolver el foco al control equivalente (o, si se eliminó, al encabezado del listado) |
| R16 | importante | `tests/Modules/Core/Unit/Design/forbidden-words.php:61` | CA30: ningún test protege la etiqueta "Password" corregida | Añadirla a la lista con su control positivo |
| R17 | importante | `tests/Browser/checks-a11y.mjs:446` | El caso de contraseñas distintas solo exige algo si no sale ninguna petición: si se quita la validación del cliente, pasa | Fallar también si el formulario envía con contraseñas distintas |
| R18 | importante | `tests/Browser/checks-a11y.mjs:579, 611` | "Se anuncia el error / el guardado" lee el texto actual de cualquier región: un texto anterior lo satisface | Exigir un anuncio nuevo, posterior al envío y distinto de "Enviando…" |
| R19 | importante | `tests/Browser/checks-a11y.mjs:572-597` | CA37 "sin mover el foco": nada lo comprueba | Fallar si el foco cambia tras anunciar |
| R20 | importante | `tests/Browser/checks-a11y.mjs:432-434, 464-467, 568-570, 599-601` | Saltos en silencio: sin botón de envío reconocible, sin petición o con 0 o varios formularios, el caso devuelve `[]` | Declarar en `screens.json` qué entradas envían y fallar si no envían |
| R21 | importante | `tests/Browser/checks-a11y.mjs:156-158` | CA38: el contraste del icono se mide con el color del botón, no con el del `svg` | Medir el color del icono |
| R22 | importante | `tests/Browser/checks-global.mjs:60-68, 225`; `checks-dialogs.mjs:40-100` | CA26: un contenido más ancho que el cuerpo del diálogo, que se desplaza de lado dentro de él, no se detecta | Fallar si el cuerpo del diálogo se desplaza de lado |
| R23 | importante | `tests/Browser/checks-global.mjs:225` | El borde de pantalla solo se mide por debajo de 768 px: a 768 un menú abierto o un contenido que sale no se mide (CA42, RNF) | Medirlo por debajo de 1024 px |
| R24 | importante | `tests/Browser/checks-global.mjs:131-145` | CA6: el contraste ignora la `opacity` del elemento y de sus ancestros | Componer la opacidad acumulada |
| R25 | importante | `tests/Browser/checks-screens.mjs:40-55` | CA25: solo busca textos cortados; si el número de citas de un día se oculta a 390 px, pasa | Exigir texto visible en cada día con citas |
| R26 | importante | `tests/Browser/checks-screens.mjs:95-107` | CA34: si falta `#today-appointments-container` o tiene citas, la tarjeta vacía no se mide y no se avisa | Fallar si no se cumple la precondición |
| R27 | importante | `tests/Browser/checks-screens.mjs:135-141` | CA32: la identificación por descripción solo se prueba en negativo | Comparar con la descripción sembrada |
| R28 | importante | `tests/Browser/checks-dialogs.mjs:117, 128-135` | CA12: en entradas con `confirmOnEscape`, el clic de aceptar sobrescribe el control que abrió el diálogo y el foco de vuelta no se verifica | Guardar el control antes de pulsar Escape |
| R29 | importante | `tests/Browser/checks-dialogs.mjs:23-25` | CA12: el nombre se lee del DOM y no se compara con el título | Nombre del árbol de accesibilidad igual al título |
| R30 | importante | `tests/Browser/ui-audit.mjs:635-637` | Al sexto intento con 429 se devuelve la pantalla sin sus datos como si pasara | Fallar con "limitada por la API" |
| R31 | importante | `tests/Browser/ui-audit.mjs:223-227, 646-652` | No se comprueba que la página auditada sea la de la entrada: una sesión inválida audita el login | Comparar `location.pathname` con la entrada |
| R32 | importante | `tests/Browser/ui-audit.mjs:536-540` | Un módulo `checks-*.mjs` que falta se omite en silencio | Fallar si falta |
| R33 | importante | `tests/Browser/checks-a11y.mjs:183-205, 521-527` | CA40: los candidatos salen solo del cursor o de `onclick`, y nunca se pulsa Intro ni Espacio | Detectar los manejadores de clic y activar con teclado en un caso |
| R34 | importante | `tests/Browser/ui-audit.mjs:719-727` | CA22: `--baseline` reescribe sin límite las entradas admitidas y los diálogos | Congelar cada entrada admitida una vez recapturada |
| R35 | menor | `bootstrap/app.php:39-41` | La ruta de respaldo solo registra GET/HEAD: un POST a una dirección web inexistente da 405 en vez de 404 | Documentarlo o registrarla para todos los métodos, con test |
| R36 | menor | `PageStructureTest.php:47`, `RoleCopyTest.php:27` | Andamiaje de pendientes ya vacío | Retirarlo |
| R37 | menor | `PageStructureTest.php:19-41` | CA14 y CA15 no cubren las páginas de error | Añadirlas |
| R38 | menor | `resources/js/ui/status.js:137-138` | `fetch(url, null)` lanza al leer `init.method` | `init?.method` |
| R39 | menor | `resources/js/app.js:12-18` | Al pasar de escritorio a móvil el menú plegable aparece abierto | Cerrarlo al cruzar 1024 px |
| R40 | menor | `components/ui/brand.blade.php:20` | El icono se anuncia "Logo de Dentissa Dentissa" junto al texto | `alt=""` junto al texto |
| R41 | menor | `components/ui/input.blade.php:34, 169` | Un obligatorio vacío muestra el mensaje de formato | Mensaje de campo obligatorio |
| R42 | menor | `pages/dashboard.blade.php:33` | Título de pestaña "Dashboard - Dentissa" | "Inicio - Dentissa" |
| R43 | menor | `pages/dashboard.blade.php:52` | `substr` corta una inicial acentuada | `mb_substr` |
| R44 | menor | `pages/landing/inicio.blade.php:251` | Emoji decorativo sin `aria-hidden` | `aria-hidden="true"` |
| R45 | menor | `resources/js/pages/landing/galeria.js:22` | `response.json()` antes de `response.ok`: un 500 en HTML se anuncia como "sin conexión" | Comprobar `ok` primero |
| R46 | menor | `pages/landing/galeria.blade.php:43` | `<img src="">` en el visor | Omitir `src` hasta abrirlo |
| R47 | menor | `components/landing/footer.blade.php:105-106` | "Aviso de Privacidad" y "Términos de Servicio" enlazan a `#` | Enlazar a páginas reales o retirarlos |
| R48 | menor | `pages/auth/login.blade.php:8` (y register, forgot, reset) | Formularios sin `method="post"`: sin JS, el correo y la contraseña irían en la URL | `method="post"` |
| R49 | menor | `complete-appointment.js:116-128` | Quitar o agregar una prescripción pierde el foco | Llevar el foco a la fila vecina o a la nueva |
| R50 | menor | `complete-appointment.js:116, 124` | Leyendas y botones de prescripción iguales en todas las filas | Numerarlos |
| R51 | menor | `tratamientos/index.js:330`, `usuarios/index.blade.php:190`, `patients/index.js:168` | "Ver detalle", "Editar" y "Ver expediente" sin el nombre del elemento | `sr-only` con el nombre |
| R52 | menor | `records/index.blade.php:48`, `patients/index.blade.php:46` | Filtros sin `role="group"` ni `aria-label` | Añadirlos |
| R53 | menor | `patients/index.js:145` | Nombre del paciente como `h3` bajo el `h1` | `h2` |
| R54 | menor | `patients/index.blade.php:87` | "¿Está segura de que desea…" (usted y femenino) frente al tuteo del resto | "¿Seguro que quieres eliminar…" |
| R55 | menor | `usuarios/*-user.js:14`, `contenido/galeria/edit-modal.js:9` | Código muerto tras la migración | Eliminarlo |
| R56 | menor | `usuarios/edit-user.js:243` | El error de guardado sale dos veces (diálogo y página) | Solo en el diálogo |
| R57 | menor | `tests/Browser/ui-audit.mjs:815-823` | Con 0 visitas el proceso termina con 0 | Devolver 2 |
| R58 | menor | `tests/Browser/checks-global.mjs:138` | Solo se miden 12 textos sobre imagen por pantalla | Sin límite o avisar |
| R59 | menor | `tests/Browser/checks-global.mjs:260, 356` | El contraste del foco sobre imagen no se mide | Medir con los píxeles |
| R60 | menor | `tests/Browser/checks-global.mjs:240-247`, `checks-global.mjs:19-39`, `ui-audit.mjs:235-240, 552-557`, `checks-dialogs.mjs:90-92`, `audit-lib.mjs:132-136` | Calidad del test: muestreo de foco por clase, `FALLBACK_TOKENS` silencioso, clic y relleno sintéticos, foco atrapado con Tab sin comprobar, estilo sin transiciones inyectado dos veces, funciones duplicadas | Ver el detalle de cada punto en la sugerencia del revisor |
| R61 | menor | `tests/Browser/ui-audit.mjs:479-504`, `screens.json` | Tokens de `ui-audit` sin revocar y perfil temporal que queda si se corta con Ctrl+C; la navegación a 1024 se mide en un solo rol y no a 1440 | Revocar al terminar; más entradas de navegación |

## Preparación para release
- Rollback factible: sí, redesplegar `v0.1.0`; la spec no tiene migraciones ni cambia la API.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `docs/architecture.md` (T090) y `docs/deployment.md` (T091) describen tokens, componentes, errores web, ruta de respaldo, paso `ui-audit` de CI y purga de caché.
- SCA: sin vulnerabilidades críticas ni altas; RS16.a mitigada por corrección, con la retirada del `overrides` en el roadmap.

## Tareas añadidas
No quedan números libres en T001–T089: por decisión del usuario (2026-10-14) se reabren las tareas
hechas que ya tocan esos archivos, cada una con la nota "reabierta por /review: R#".
- T087 ← R1 (test) · T071 ← R1
- T081 ← R2, R3, R4, R5, R6, R10, R11, R12, R13, R17, R18, R19, R20, R21 (tests de `checks-a11y`)
- T021 ← R9, R15, R28, R29 (tests de `checks-dialogs`) · T022 ← R14, R25, R26, R27 (tests de `checks-screens`) · T020 ← R23 (test de `checks-global`) · T005 ← R20, R30, R31, R32 (`ui-audit.mjs` y `screens.json`) · T012 ← R16
- T082 ← R5, R8, R13 (`ui/status.js`) · T030 ← R9, R15 (`ui/dialog.js`) · T027 ← R11, R12 (`x-ui.input`)
- T038 ← R5, R6, R7 · T036 ← R5 (registro e inicio de sesión) · T043 ← R5 · T046 ← R2, R5 · T047 ← R5, R12 · T048 ← R5
- T049, T050 ← R12 · T053, T056, T059 ← R3, R5 · T054 ← R3 · T057, T060 ← R3, R12 · T062 ← R5
- T066 ← R4, R5 · T065 ← R14 · T033 ← R10
- T079 y T092 se reabren: verificación final y cierre tras las correcciones.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-14 (los menores y los importantes de `ui-audit` que no
debilitan un criterio):
- **R22** → roadmap: `ui-audit` no detecta un contenido que se desplaza de lado dentro del cuerpo de un diálogo (CA26).
- **R24** → roadmap: `ui-audit` no compone la opacidad al medir el contraste (CA6).
- **R33** → roadmap: `ui-audit` busca lo pulsable por cursor y `onclick`, y no activa con teclado (CA40).
- **R34** → roadmap: `--baseline` no congela las entradas admitidas de la composición (CA22).
- **R35** → roadmap: un POST a una dirección web inexistente da 405 en vez de 404.
- **R36** → roadmap: retirar el andamiaje de pendientes de `PageStructureTest` y `RoleCopyTest`.
- **R37** → roadmap: `PageStructureTest` sin las páginas de error.
- **R38** → roadmap: `fetch(url, null)` en el envoltorio de `ui/status.js`.
- **R39** → roadmap: menú plegable abierto al pasar de escritorio a móvil.
- **R40** → roadmap: logo anunciado dos veces junto al texto "Dentissa".
- **R41** → roadmap: mensaje de campo obligatorio vacío.
- **R42** → roadmap: título de pestaña "Dashboard".
- **R43** → roadmap: inicial acentuada cortada en el inicio del panel.
- **R44** → roadmap: emoji decorativo sin `aria-hidden`.
- **R45** → roadmap: la galería pública confunde un 500 con falta de conexión.
- **R46** → roadmap: `<img src="">` en el visor de la galería.
- **R47** → roadmap: enlaces del pie a `#`.
- **R48** → roadmap: formularios de acceso sin `method="post"`.
- **R49** → roadmap: foco al quitar o agregar prescripciones.
- **R50** → roadmap: prescripciones sin numerar para el lector.
- **R51** → roadmap: acciones repetidas sin el nombre del elemento.
- **R52** → roadmap: filtros sin `role="group"`.
- **R53** → roadmap: nombre del paciente como `h3`.
- **R54** → roadmap: confirmación de eliminar paciente sin tuteo.
- **R55** → roadmap: código muerto en scripts de usuarios y galería.
- **R56** → roadmap: error de guardado de usuario repetido en la página.
- **R57** → roadmap: `ui-audit` termina con 0 sin visitas.
- **R58** → roadmap: límite de 12 textos sobre imagen.
- **R59** → roadmap: contraste del foco sobre imagen sin medir.
- **R60** → roadmap: calidad interna de `ui-audit` (muestreo de foco, tokens de respaldo, clic sintético, foco atrapado, estilo duplicado, funciones duplicadas).
- **R61** → roadmap: tokens de `ui-audit` sin revocar y navegación medida en un solo rol.
