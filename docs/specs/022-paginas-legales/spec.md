---
id: 022
slug: paginas-legales
status: draft                # registro: liberado en v0.7.0 sin plan ni review (decisión del usuario, 2026-11-03)
created: 2026-11-03
extends: [011, 002]
---

# 022 · Aviso de Privacidad y Términos y Condiciones

## Problema
El sitio público enlaza "Aviso de Privacidad" y "Términos de Servicio" desde el pie de página, pero
los dos enlaces no llevan a ninguna parte (016/R47). La ley mexicana de protección de datos
(LFPDPPP) obliga a la clínica a publicar un aviso de privacidad y a mostrarlo cuando recaba datos,
y el sistema guarda datos de salud, que la ley trata como sensibles; `docs/security.md` lo tiene
como obligación pendiente antes de cargar datos reales. Quien se registra como paciente tampoco ve
qué condiciones acepta.

Esta spec atiende **parcialmente** esa obligación: publica el aviso y lo muestra en el registro
de pacientes; el consentimiento expreso guardado, los derechos ARCO dentro del sistema y el aviso en
el alta por el staff quedan fuera. Documenta un cambio que se implementó antes de escribirla, por
decisión del usuario, y que se concilió con `/sync` el 2026-11-03.

## Historias de usuario
- Como visitante o paciente, quiero leer el Aviso de Privacidad para saber qué datos míos trata la
  clínica, para qué, con quién los comparte y cómo ejercer mis derechos.
- Como visitante, quiero leer los Términos y Condiciones para saber las reglas de uso del sitio y
  de mi cuenta.
- Como paciente que se registra, quiero ver qué documentos acepto al crear mi cuenta y poder
  abrirlos antes de hacerlo.
- Como responsable de la clínica, quiero cambiar los datos del responsable y la versión de cada
  documento en un solo lugar, sin tocar el texto de las páginas.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante sin sesión, cuando abre el Aviso de Privacidad, entonces ve una página
  pública con el título "Aviso de Privacidad", la versión, la fecha de última actualización y un
  índice en el que cada entrada lleva a su sección.
- [ ] CA2 · Dado el Aviso de Privacidad, cuando se lee, entonces explica, cada punto en su propia
  sección: quién es el responsable, su domicilio y su correo de contacto; qué datos se tratan,
  separando los datos personales sensibles (salud, expediente, seguimiento y recetas); las
  finalidades necesarias y la secundaria; cómo negarse a la secundaria; qué proveedores tratan
  datos por cuenta de la clínica (envío de WhatsApp, envío de correo, protección del sitio y
  almacenamiento de imágenes, y alojamiento de los servidores); cómo ejercer los derechos de
  acceso, rectificación, cancelación y oposición; cómo retirar el consentimiento o limitar el uso;
  qué cookies usa el sitio; y cómo se avisan los cambios al aviso.
- [ ] CA3 · Dado un visitante sin sesión, cuando abre los Términos y Condiciones, entonces ve una
  página pública con el título "Términos y Condiciones", la versión, la fecha de última
  actualización y un índice con aceptación, qué ofrece el sitio, la cuenta, las citas, el alcance
  de la información, el uso adecuado, la propiedad intelectual, la responsabilidad, la privacidad,
  los cambios, la ley aplicable y el contacto.
- [ ] CA4 · Dado que el nombre del responsable, su domicilio, su correo de privacidad o la entidad
  de los tribunales cambian, cuando se actualizan en su único lugar de configuración, entonces las
  dos páginas los muestran sin cambiar su texto; mientras falten, cada dato se muestra como
  "[Pendiente: …]".
- [ ] CA5 · Dada una nueva versión de un documento, cuando se actualizan su número de versión y su
  fecha en ese mismo lugar, entonces la página muestra la versión y la fecha nuevas.
- [ ] CA6 · Dada cualquier página pública (inicio, acerca de nosotros, galería, contacto y las dos
  páginas legales), cuando se mira el pie, entonces "Aviso de Privacidad" y "Términos y
  Condiciones" llevan a sus páginas y ningún enlace del pie apunta a ninguna parte. Cierra 016/R47
  y cambia el texto "Términos de Servicio" por "Términos y Condiciones".
- [ ] CA7 · Dado el formulario de registro de pacientes, cuando se abre, entonces antes del botón
  "Registrarme" se lee "Al registrarte aceptas el Aviso de Privacidad y los Términos y
  Condiciones", con un enlace a cada página. Extiende 002: no hay casilla ni se guarda el
  consentimiento.
- [ ] CA8 · Dada una de las páginas legales, cuando se lee hasta el final de la sección que menciona
  a la otra, entonces hay un enlace a la otra página; y los Términos enlazan además a "Contacto y
  Ubicación".
- [ ] CA9 · Dada una búsqueda en internet, cuando aparece una de las dos páginas, entonces muestra su
  propio título ("Aviso de Privacidad - Dentissa" o "Términos y Condiciones - Dentissa") y su
  propia descripción de 70 a 160 caracteres, y las dos se pueden indexar. Extiende 011 CA4: el sitio
  tiene dos páginas estáticas más.
- [ ] CA10 · Dadas las dos páginas y el registro, cuando se abren a 390, 768 y 1440 px, entonces no
  hay desplazamiento horizontal, cada enlace o botón mide al menos 44 px de alto y de ancho, el
  contraste cumple WCAG 2.1 AA, el foco es visible al recorrerlas con el teclado y cada página
  tiene un solo título principal.

## Fuera de alcance
- Guardar el consentimiento expreso del paciente (fecha y versión del aviso aceptado) y la casilla
  obligatoria del registro.
- El procedimiento de derechos de acceso, rectificación, cancelación y oposición dentro del sistema
  (exportar o suprimir datos).
- Mostrar el aviso en el alta de pacientes por el staff.
- Los datos reales del responsable y la validación del texto por un especialista legal.
- Políticas propias de la clínica sobre cancelaciones, inasistencias o pagos (los términos son
  genéricos).
- Consultar versiones anteriores de los documentos desde el sitio.
- Mapa del sitio, dirección canónica e imagen para compartir de las páginas: siguen como brechas
  de todo el sitio en `docs/seo.md`.
- Editar los textos desde el panel de contenido.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno. Las páginas son públicas y estáticas, describen el
  tratamiento de datos pero no muestran ni reciben datos de nadie.
- Quién puede hacer qué: cualquier visitante, con o sin sesión, puede leer las dos páginas; nadie
  puede cambiarlas desde el sitio.
- Casos de abuso: no aplica; las páginas no tienen entradas, permisos ni datos de usuarios.

## Diseño
Deben sentirse claras y confiables: quien llega busca una respuesta concreta ("¿con quién
comparten mis datos?", "¿qué pasa si cancelo?") y la encuentra en segundos desde el índice. Texto
de lectura cómoda, sin adornos, con el mismo pie y la misma barra que el resto del sitio público.

Pantallas que toca:
- Aviso de Privacidad y Términos y Condiciones: título, versión y fecha, índice y secciones;
- el pie del sitio público (dos enlaces);
- el registro de pacientes (leyenda con dos enlaces).

Qué debe lograr cada pantalla: en las páginas legales, que quien llega vea en la primera pantalla
qué documento es, de cuándo es y el índice; en el registro, que la leyenda se lea antes de
"Registrarme" sin restarle protagonismo al botón.

Recursos de marca: logo, paleta y componentes del sistema de diseño
([docs/design/system.md](../../design/system.md)). Solo con componentes y tokens del sistema.

## Criterios de diseño
Modo: leer · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Título, versión e índice en la primera pantalla | "Aviso de Privacidad", "Versión" y "Contenido" a la vista a 390 y 1440 px | CA1 |
| K2 | Título, versión e índice en la primera pantalla | "Términos y Condiciones", "Versión" y "Contenido" a la vista a 390 y 1440 px | CA3 |
| K3 | Leyenda del registro | "Al registrarte aceptas el Aviso de Privacidad y los Términos y Condiciones" presente antes de "Registrarme" | CA7 |

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: pesos por defecto.
No se miden en el diseño: el contenido de cada sección (CA2), los datos de configuración (CA4, CA5),
los enlaces del pie y entre páginas (CA6, CA8) y la descripción para buscadores (CA9). Los
verifican los tests de integración y `npm run test:ui` (CA10).

## Requisitos no funcionales
- Las dos páginas se muestran completas sin JavaScript y forman parte de la prueba en navegador a
  390, 768 y 1440 px (P15, WCAG 2.1 AA).
- Los textos están en español correcto y sin términos técnicos (P15).
- Ningún enlace depende de dónde se parte una línea de texto: los enlaces van en su propia línea,
  para que la composición sea la misma con cualquier fuente.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-03 | brecha | ¿Con qué datos del responsable se publica? | Pendientes: se muestran como "[Pendiente: …]" y se cambian en un solo lugar (CA4) | usuario |
| 2026-11-03 | brecha | ¿Texto fijo o editable desde el panel de contenido? | Fijo, versionado con el código | usuario |
| 2026-11-03 | brecha | ¿Qué se muestra en el registro? | La leyenda con los dos enlaces, sin casilla ni consentimiento guardado (CA7) | usuario |
| 2026-11-03 | brecha | ¿Qué políticas de citas incluyen los términos? | Genéricas: no hay contacto con el cliente | usuario |
| 2026-11-03 | supuesto | ¿Aviso integral, simplificado o corto? | Integral, con los apartados de la ley; validación legal pendiente | agente |
| 2026-11-03 | supuesto | ¿Se afirma algo que el sistema aún no cumple? | No: se quitó "cada persona ve solo lo que necesita" (el control de acceso por recurso sigue abierto en `docs/security.md`) y los términos no prometen funciones del portal del paciente, que aún no tiene pantallas | agente |
| 2026-11-03 | diseño | En CI, los enlaces dentro de una frase cambiaban de posición con las fuentes de Linux y fallaba la composición a 1440 px | Los enlaces van en su propia línea (requisito no funcional) | agente, CI del PR #41 |
| 2026-11-03 | implícita | Publicar el aviso y mostrarlo al recabar datos | Obligación pendiente antes de cargar datos reales | `docs/security.md` → Obligaciones derivadas de la LFPDPPP |
| 2026-11-03 | cierre | El cambio se implementó y se integró (PR #41) antes de esta spec | Spec escrita a partir del código y los tests, para conciliar con `/sync` (camino 1: mantenerlo documentado) | usuario |

## Notas para /plan
- Implementado en `8bae49b` y `65fa1ab` (PR #41).
- Rutas web `/aviso-de-privacidad` (`aviso-privacidad`) y `/terminos-y-condiciones` (`terminos`)
  en `routes/web.php`; vistas en `resources/views/pages/landing/`.
- Componentes `x-landing.legal-document` y `x-landing.legal-section`; datos en `config/legal.php`.
- El layout público acepta `@section('description')` para la `meta description`.
- Tests: `tests/Modules/Core/Integration/LegalPagesTest.php`, `PageStructureTest.php` y las
  entradas `aviso-privacidad` y `terminos` de `tests/Browser/screens.json`. La diferencia de
  composición del registro está admitida en `tests/Browser/checks-composition.mjs` con el criterio
  `022 CA7`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-03 | Spec creada a partir del cambio ya integrado (PR #41) | `/sync` 2026-11-03, decisión del usuario |
| 2026-11-03 | Se queda en `draft` como registro del comportamiento; liberado en v0.7.0 sin plan, tareas ni review | Decisión del usuario: desplegar sin el flujo completo |
