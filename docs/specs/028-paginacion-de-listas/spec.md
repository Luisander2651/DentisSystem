---
id: 028
slug: paginacion-de-listas
status: approved
created: 2026-11-18
extends: [004, 005, 006, 008, 012, 020, 025]
---

# 028 · Paginación de las listas del panel y del portal

## Problema
Hoy cada lista del panel y del portal del paciente trae y pinta todos sus registros de una vez:
Pacientes, Expedientes clínicos, Usuarios, Tratamientos, las cuatro pestañas de Contenido, el
historial de citas del expediente, "Mis citas" del portal y los pendientes del inicio. Con los datos
de prueba ya hay 30 pacientes en una sola columna de tarjetas, y la lista de Pacientes a 1440 px mide
casi 3 000 px de alto. Cuando la clínica tenga cientos de pacientes y citas, las pantallas se
volverán muy largas, lentas de cargar y difíciles de recorrer. Además, los testimonios crecen solos
con la página de opiniones (spec 027). Hace falta que cada lista muestre una página a la vez, con
un control para moverse entre páginas, y que buscar y filtrar sigan encontrando cualquier registro.

## Historias de usuario
- Como staff de la clínica, quiero ver las listas del panel por páginas, para encontrar y recorrer los registros sin pantallas interminables aunque la clínica crezca.
- Como staff de la clínica, quiero que la búsqueda y los filtros encuentren cualquier registro, esté en la página que esté, para no tener que recorrer páginas a mano.
- Como staff de la clínica, quiero ver los pendientes del inicio y el historial de citas de un paciente por páginas, para que el inicio y el expediente sigan siendo legibles con muchas citas.
- Como paciente, quiero ver "Mis citas" por páginas, para revisar mi historial aunque tenga muchas citas.

## Criterios de aceptación
- [ ] CA1 · Dado que una lista tiene más registros de los que caben en una página, cuando el staff abre Pacientes, Expedientes clínicos, Usuarios, Tratamientos o cualquier pestaña de Contenido (Galería, Promociones, Certificaciones, Testimonios), entonces ve solo los primeros **12** y, bajo la lista, el texto "Mostrando 1–12 de N <registros>" y el control de páginas.
- [ ] CA2 · Dado que una tabla tiene más de **10** filas, cuando se abre el historial de citas del expediente de un paciente, "Mis citas" del portal o las secciones "Citas sin cerrar" y "Completadas sin seguimiento" del inicio, entonces se ven 10 filas por página, con el mismo texto y control de páginas. Cada sección del inicio se pagina por separado.
- [ ] CA3 · Dado el control de páginas, cuando se usa, entonces muestra "Anterior", los números de página (la primera, la última, la actual y sus vecinas, con "…" en los huecos) y "Siguiente". La página actual se distingue y se anuncia como actual. "Anterior" no se puede usar en la primera página ni "Siguiente" en la última. Al cambiar de página cambia la lista y el texto "Mostrando…", y la vista vuelve al inicio de la lista.
- [ ] CA4 · Dado un registro que está en otra página, cuando el staff lo busca por nombre o correo (Pacientes, Expedientes clínicos) o por nombre o descripción (Tratamientos), o elige el filtro "Activos" o "Inactivos", entonces el resultado incluye **todos** los registros que coinciden, paginado igual, empezando en la página 1.
- [ ] CA5 · Dada una lista paginada o filtrada, cuando se ven los contadores de la cabecera (Total, Activos e Inactivos en Pacientes y Expedientes; por rol en Usuarios; Total, Con duración y Promedio en Tratamientos), entonces cuentan todos los registros, no solo los de la página visible.
- [ ] CA6 · Dada una lista con registros que caben en una página, cuando se abre, entonces no aparece el control de páginas. Dada una lista vacía, o una búsqueda sin resultados, entonces se ve el mismo estado vacío de hoy, sin control de páginas.
- [ ] CA7 · Dada cualquier lista paginada, cuando se recorren todas sus páginas, entonces cada registro aparece exactamente una vez, en un orden fijo: el orden actual de cada lista o, donde hoy no lo hay, los más recientes primero.
- [ ] CA8 · Dado que el staff está en la página 3 de Pacientes con una búsqueda escrita, cuando recarga la pantalla o vuelve a ella con "Atrás" del navegador, entonces ve la misma página con la misma búsqueda y filtro. Lo mismo en Expedientes clínicos, Usuarios, Tratamientos, Contenido (pestaña y página) y "Mis citas".
- [ ] CA9 · Dada una página que no existe (por ejemplo la 999, una negativa o un texto), cuando se pide desde la dirección, entonces se muestra la última página que existe, o la primera si el valor no es un número, sin mensaje de error.
- [ ] CA10 · Dado que el staff crea, edita o elimina un registro desde una lista paginada, cuando termina, entonces la lista se actualiza sin salir de la página en la que estaba y el texto "Mostrando…" y los contadores reflejan el cambio. Si eliminó el único registro de la última página, pasa a la página anterior.
- [ ] CA11 · Dado el control de páginas a 390, 768 y 1440 px, cuando se muestra con muchas páginas, entonces cabe en el ancho sin desplazamiento horizontal, cada botón mide al menos 44 px de alto a 390 px y se puede usar solo con el teclado, con el foco visible.
- [ ] CA12 · Dada una lista con 5 000 registros, cuando se pide una página, entonces solo llegan al navegador los registros de esa página y la pantalla la muestra en menos de 1 s en local.
- [ ] CA13 · (abuso) Como usuario malintencionado, pido una página de 10 000 registros cambiando el tamaño de página → recibo como máximo 50 por página; nunca la lista completa.
- [ ] CA14 · (abuso) Como paciente, intento pedir las páginas de las citas de otro paciente, o como staff sin el rol necesario, una página de una lista que no puedo ver → se rechaza igual que hoy, sin revelar cuántos registros hay.

## Fuera de alcance
- El selector de paciente al agendar una cita (sigue mostrando todos los pacientes; si crece, se resuelve con un buscador en otra spec).
- La agenda (calendario y citas del día): ya se limita por mes y día.
- Reportes: muestra totales del mes, no listas de registros.
- El sitio público, incluidos el orden y el tope de los testimonios del inicio (en "Pendientes y deuda" del roadmap, spec corta aparte).
- Elegir cuántos registros se ven por página, ordenar por columna o el desplazamiento infinito ("Cargar más").
- Nuevos filtros o búsquedas en listas que hoy no los tienen (Usuarios, Contenido, historiales).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales de pacientes (nombre, correo) y del staff, y citas de pacientes, con la clasificación de `docs/security.md`. Esta spec no añade datos nuevos: cambia cuántos registros se entregan de una vez.
- Quién puede hacer qué: los mismos permisos de hoy. Cada página de una lista la puede pedir solo quien hoy puede ver esa lista completa. El paciente solo pagina sus propias citas.
- Casos de abuso:
  - Como usuario malintencionado, pido una página enorme para sacar la lista completa de una vez → se limita a 50 registros por página (CA13).
  - Como paciente o staff sin el rol, pido páginas de una lista ajena → se rechaza como hoy, sin totales (CA14).
  - Como usuario malintencionado, envío una página o una búsqueda con valores inválidos o texto con caracteres especiales → se trata como página inválida (CA9) o como texto literal, sin error interno (P7).

## Auditoría
No aplica a esta spec: paginar no añade lecturas ni cambios nuevos. Las lecturas de listas con datos
de pacientes se registran (o no) igual que hoy; registrar las lecturas de datos sensibles es el
objetivo 5 del roadmap (`docs/observability.md`).

## Diseño
Una sola pieza nueva, el control de páginas con su texto "Mostrando…", igual en todas las listas
del panel y del portal; las pantallas conservan su composición. Debe sentirse discreto y familiar:
se entiende de un vistazo en qué página se está y cuántos registros hay. Sistema:
[docs/design/system.md](../../design/system.md). Pantallas: Pacientes, Expedientes clínicos,
expediente de un paciente (historial de citas), Usuarios, Tratamientos, Contenido (cuatro
pestañas), inicio del panel (dos secciones de pendientes) y "Mis citas" del portal.
Qué debe diseñarse: el control de páginas a 390, 768 y 1440 px, en lista de tarjetas y bajo una
tabla, con sus estados (primera, intermedia y última página, muchas páginas con "…", foco) y su
lugar en cada pantalla.
Acta de diseño: [design.md](design.md) (candidato `c3`, 80/100).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 3 candidatos. Se miden dos páginas por
candidato: Pacientes en tarjetas (página 2 de 8) y "Mis citas" en tabla (página 1 de 3), en
`design/criterios.json`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Textos del control de páginas | "Mostrando", "Anterior" y "Siguiente" visibles en las dos páginas | CA1, CA2, CA3 |
| K2 | Titulares | ≤ 2 líneas | modo operar |
| K3 | Estabilidad visual | CLS ≤ 0.1 | modo operar |
| M1 | Página actual (a mano) | se distingue sin depender solo del color y se anuncia como actual | CA3 |
| M2 | Extremos (a mano) | "Anterior" en la primera y "Siguiente" en la última se ven y se anuncian como no disponibles | CA3 |

Puertas: las de `design-spec/references/criterios.md` §2 (sin desborde, toque ≥ 44 px, foco
visible, contraste, solo tokens; cubren CA11 y la constitución P15). Rúbrica: `plantilla` con peso 0
y jerarquía 30, primera pantalla 20, sistema 30, lectura 20.

## Requisitos no funcionales
- Rendimiento: la primera página de cualquier lista se muestra en menos de 1 s en local con 5 000 registros (CA12).
- Accesibilidad: WCAG 2.1 AA en el control de páginas (constitución P15): nombre accesible en cada botón, página actual anunciada, uso con teclado y foco visible (CA11).
- Textos en español y sin términos técnicos.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-18 | brecha | ¿Qué listas entran? | Pacientes, Expedientes clínicos, Usuarios, Tratamientos, las cuatro pestañas de Contenido, el historial de citas del expediente, "Mis citas" del portal y los pendientes del inicio | usuario |
| 2026-11-18 | brecha | ¿La búsqueda y los filtros buscan en todos los registros o solo en la página visible? | En todos los registros, con el resultado paginado (CA4) | usuario |
| 2026-11-18 | brecha | ¿Cómo se navega entre páginas? | Páginas numeradas con "Anterior" y "Siguiente" y el texto "Mostrando X–Y de N" (CA3) | usuario |
| 2026-11-18 | brecha | ¿Cuántos registros por página? | 12 en las listas de tarjetas, 10 en las tablas (CA1, CA2) | usuario |
| 2026-11-18 | brecha | La spec toca 7 specs y 6 módulos; el flujo recomienda dividirla | Una sola spec | usuario |
| 2026-11-18 | supuesto | ¿La página se conserva al recargar o volver con "Atrás"? | Sí, en las pantallas principales; en las secciones del inicio y el historial del expediente no (CA8) | agente |
| 2026-11-18 | supuesto | ¿Qué orden siguen las páginas? | El actual de cada lista; donde no hay orden definido, los más recientes primero (CA7) | agente |
| 2026-11-18 | supuesto | ¿Máximo por página si alguien lo cambia a mano? | 50 (CA13) | agente |
| 2026-11-18 | supuesto | ¿Qué pasa con una página que no existe? | Se muestra la última que existe, o la primera si no es un número (CA9) | agente |
| 2026-11-18 | diseño | Criterios medibles del diseño: K1–K3 con el script, M1 y M2 a mano; `plantilla` con peso 0 (un control de páginas debe ser familiar); presupuesto de 3 candidatos frente a los 5 del proyecto | Aprobados | usuario |
| 2026-11-18 | diseño | Diseño del control de páginas | Candidato `c3` aceptado con 80/100 tras tres candidatos; acta en [design.md](design.md) | `/design-spec 028` |
| 2026-11-18 | implícita | Accesibilidad y tamaño táctil del control | WCAG AA, ≥ 44 px a 390 px y solo componentes del sistema | `docs/constitution.md` P15, `docs/design/system.md` |
| 2026-11-18 | implícita | Errores con datos inválidos | Sin detalles internos | `docs/constitution.md` P7 |

## Notas para /plan
- Hoy Pacientes y Expedientes clínicos buscan y filtran en el navegador sobre la lista completa; CA4 exige moverlo al servidor.
- El sistema de diseño no tiene componente de paginación: `/design-spec 028` lo define y el plan lo incorpora al sistema ("Cambios a incorporar al sistema").
- Las respuestas de lista cambian de forma (datos de la página y totales): revisar los demás usos de esos mismos endpoints, como el selector de pacientes al agendar.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-18 | Spec aprobada | Aprobación del usuario |
