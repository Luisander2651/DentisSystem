---
spec: 028-paginacion-de-listas
status: approved
exploration: loop
threshold: 80
floor: 3
budget: 3
accepted: c3
score: 80
system_version: 2.1.0
created: 2026-11-18
updated: 2026-11-18
---

# Diseño · 028 Paginación de las listas del panel y del portal

Hecho con `/design-spec 028` sobre `docs/design/system.md`. Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se
conservan). Cada candidato son dos páginas: las pantallas reales de Pacientes (`c<N>-tarjetas.html`,
página 2 de 8) y "Mis citas" (`c<N>-tabla.html`, página 1 de 3), guardadas con `ui-audit` sobre los
datos de prueba (`design/base/`), con la lista recortada a una página y el control añadido.

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 11/12 | 3/3 | — | descartado: `G-sin-js` a 390 px, "Anterior" y "Siguiente" solo con ícono | `.ai/inspect/028-c1-tarjetas/report.md`, `.ai/inspect/028-c1-tabla/report.md` |
| `c2` | 12/12 | 3/3 | 70 | descartado: bajo el umbral. A 390 px el orden Anterior → números → Siguiente se rompe en la tabla, el control se reparte distinto en cada pantalla y "Anterior" deshabilitado parece sin estilo | `.ai/inspect/028-c2-tarjetas/report.md`, `.ai/inspect/028-c2-tabla/report.md` |
| `c3` | 12/12 | 3/3 | 80 | aceptado | `.ai/inspect/028-c3-tarjetas/report.md`, `.ai/inspect/028-c3-tabla/report.md` |

## Resultado

**c3**, aceptado con 80/100 (umbral 80, ningún punto bajo 3; mejor que c2 en jerarquía, primera
pantalla y lectura, igual en sistema). Páginas: `design/candidatos/c3-tarjetas.html` y
`design/candidatos/c3-tabla.html`.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde (CA11) | sin desplazamiento horizontal | sin desborde a 390, 768 y 1440 en las dos páginas | ✅ |
| G-toque (CA11) | ≥ 44 px por debajo de 1024 px | todos los botones del control miden 44 px | ✅ |
| G-foco (CA11) | foco visible | contorno de 2 px en tinta con separación de 2 px | ✅ |
| G-contraste | 4.5:1 · 3:1 texto grande | sin fallos | ✅ |
| G-tokens (P15) | solo colores del sistema | sin fallos | ✅ |
| G-sin-js · G-reducido · G-h1 · G-solapamiento · G-aparece · G-imagenes · G-red | — | sin fallos | ✅ |
| K1 · Textos del control (CA1, CA2, CA3) | "Mostrando", "Anterior", "Siguiente" | 3/3 en las dos páginas, a todos los anchos | ✅ |
| K2 · Titulares | ≤ 2 líneas | ≤ 2 líneas | ✅ |
| K3 · Estabilidad visual | CLS ≤ 0.1 | 0 | ✅ |
| M1 · Página actual (CA3, a mano) | se distingue sin depender solo del color y se anuncia | borde de 2 px y texto en 700 además del fondo rosa; `aria-current="page"` y nombre "Página 2, actual" | ✅ |
| M2 · Extremos (CA3, a mano) | se ven y se anuncian como no disponibles | texto en `muted` sobre `canvas`; `aria-disabled="true"` y nombre "Página anterior, no disponible" | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 30 | 4 | `028-c3-tarjetas/1440-claro.png` · pie de la lista: resumen a la izquierda con las cifras en negrita y Anterior → 1 [2] 3 4 … 8 → Siguiente en una fila; la actual destaca por borde y relleno. A 390 px se lee en dos filas |
| Primera pantalla | 20 | 4 | `028-c3-tabla/390-claro.png` y `768-claro.png` · el resumen sobre el control, el 1 marcado y "Anterior" en gris: se entiende enseguida dónde se está, cuántas hay y cómo avanzar |
| Coherencia con el sistema y la marca | 30 | 4 | `028-c3-tarjetas/1440-claro.png` · mismos borde y radio que las acciones de las tarjetas y los filtros; la actual en el rosa del filtro "Todos" y del menú; en la tabla vive en el pie de la tarjeta |
| Lectura y ritmo | 20 | 4 | `028-c3-tarjetas/768-claro.png` y `028-c3-tabla/768-claro.png` · resumen y control apilados y centrados con aire respecto a la lista; a 390 px todo cabe en una rejilla de ancho completo |
| Diferencia frente a una plantilla | 0 | — | sin peso: un control de páginas debe ser familiar |

Problemas que el evaluador dejó señalados en el aceptado (no bajan de 4 ningún punto; se revisan en
la revisión manual de `/implement`):
1. A 390 px los números se estiran en columnas iguales y el "…" queda estrecho entre ellos.
2. "Anterior" deshabilitado todavía pesa parecido a un botón activo.
3. A 1440 px el resumen queda lejos del control en la lista de tarjetas.

## Imágenes de muestra

Ninguna.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Pacientes · página intermedia (2 de 8) con "…" | `candidatos/c3-tarjetas.html` a 390 | a 768 | a 1440 | CA1, CA3, CA5, CA11 |
| Mis citas · primera página (1 de 3), "Anterior" no disponible | `candidatos/c3-tabla.html` a 390 | a 768 | a 1440 | CA2, CA3, CA11 |

Las demás listas usan el mismo control: Expedientes clínicos, Usuarios, Tratamientos y las cuatro
pestañas de Contenido como Pacientes (bajo la rejilla de tarjetas); el historial del expediente y
las dos secciones de pendientes del inicio como Mis citas (en el pie de su tarjeta).

## Componentes

- **Del sistema:** tarjeta (`rounded-card`), botones de 44 px con borde `field` y radio `control`,
  enlace con foco visible; los mismos tokens que `x-ui.button` (variante `secondary`) y `x-ui.chip`.
- **Exclusivos de esta pantalla:** ninguno: el control es un componente nuevo del sistema (abajo).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Componente **Paginación** (`x-ui.pagination` y su pintado desde los datos en el navegador) | corte 768 px y 1024 px | `nav` con nombre ("Páginas de <lista>"). Resumen "Mostrando X–Y de N <registros>" (14 px, `muted`, cifras en `ink` 600) centrado arriba. Debajo, rejilla de dos columnas: fila 1 los números a todo el ancho (primera, actual, vecina siguiente, "…", última); fila 2 "‹ Anterior" y "Siguiente ›" a medias. Gap 8 px | Desde 768 px: una sola fila centrada Anterior → 1 … vecinas … última → Siguiente, con las dos vecinas de la actual; desde 1024 px el resumen a la izquierda y la fila a la derecha | Ninguna lista del sistema tenía paginación (spec 028, CA1–CA3, CA11) |
| Botón de página | fijo | 44 × 44 px mínimo, borde 1 px `field`, fondo `surface`, radio `control`, 14 px / 600 en `ink`, hover `canvas`, foco contorno 2 px `ink` separado 2 px | — | Igual que `x-ui.button` `secondary` |
| Página actual | fijo | `aria-current="page"`, borde 2 px `primary`, fondo `primary-soft`, 700; nombre "Página N, actual" | — | Se distingue sin depender solo del color (CA3) |
| "Anterior" / "Siguiente" no disponibles | fijo | sin enlace, `aria-disabled="true"`, borde `field`, fondo `canvas`, texto `muted`; nombre "Página anterior, no disponible" | — | CA3 |
| "…" | fijo | 24 px de ancho, `muted`, oculto a lectores de pantalla | — | CA3 |
| Lugar | fijo | bajo la rejilla de tarjetas con 24 px arriba; en una tabla, en el pie de su tarjeta con borde superior `line` y 16 px de relleno | — | CA1, CA2 |

Sin tokens nuevos: solo usa `primary`, `primary-soft`, `ink`, `muted`, `canvas`, `surface`, `line`,
`field`, `radius-control` y `spacing-control`.

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`).
- **Estructura:** `nav` con nombre propio por lista; el resumen en una región de estado que se
  anuncia al cambiar de página; los números en una lista; cada enlace con nombre ("Página 3",
  "Página 8, última", "Página siguiente"); "…" con `aria-hidden`.
- **Teclado:** orden Anterior → números → Siguiente en el DOM a todos los anchos (a 390 px la rejilla
  solo cambia la posición visual); los enlaces no disponibles no reciben foco.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-18 | diseño | Criterios: K1–K3 con el script, M1 y M2 a mano, `plantilla` con peso 0, presupuesto 3 | Aprobados | usuario |
| 2026-11-18 | diseño | ¿Cómo se prueban los candidatos con pantallas reales? | Pacientes y Mis citas guardadas con `ui-audit` (instantánea autocontenida) y modificadas; el control usa solo `var(--…)` de los tokens | agente |
| 2026-11-18 | diseño | Candidato aceptado | c3, 80/100 a la primera evaluación que pasa el umbral | `/design-spec 028` |
| 2026-11-18 | diseño | Aprobación del acta | Aprobada; los tres problemas que dejó el evaluador se revisan en la revisión manual de `/implement` | usuario |
