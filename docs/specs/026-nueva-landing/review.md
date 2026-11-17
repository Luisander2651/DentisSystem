---
spec: 026-nueva-landing
verdict: approved
round: 4
date: 2026-11-17
base: be15dc8
head: c2ce7e5
human_signoff: pending
---

# Review · 026 Nueva landing del sitio público

## Resumen
Ronda 4 (`--rerun`), abierta durante `/release`. El CI del PR #54 encontró un defecto de la spec: el visor de certificaciones dejaba de estar fijo a la ventana. Lo había introducido la optimización de `content-visibility` de T092 y lo corrige T052. CI también mostró líneas base del inicio generadas con datos locales.

El diff de código de esta ronda mide 53 líneas en 6 archivos. Lo revisé sin subagente, releyendo cada archivo:
- **CSS:** quita `#certificaciones` de la regla de `content-visibility`.
- **`SiteStylesTokensTest`:** el test protege esa exclusión.
- **`baseline-1440.json`:** las líneas de `inicio` e `inicio-testimonios`, regeneradas con solo `ui:audit-data`, como en CI.
- **`ui-audit.mjs`, `screens.json` y `ai-dd.yml`:** cada uno con su ficha `/fix` y su review ligera.

No hay hallazgos nuevos. La firma humana vuelve a `pending` porque cambió código después de la ronda 3.

Rondas anteriores: [r1](history/review.r1.md), [r2](history/review.r2.md), [r3](history/review.r3.md).

Conteo de esta ronda: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| CI del PR #54 en `c2ce7e5` (`tests.yml`): Pest + `npm run test:ui` (346 visitas, 123 entradas) | ✅ |
| CI del PR #54 (`ai-dd.yml`): artefactos, Pint, seguridad de los cambios (con trivy, ya corregido) | ✅ |
| Local: `ui-audit inicio` con los datos de CI | ✅ dos veces seguidas; `inicio-testimonios` 5 de 5 |
| Local: `ui-audit inicio-visor-certificado` | ✅ en 390, 768 y 1440 |
| `aidd.py validate` | ✅ |

## Seguimiento
| ID | Estado | Evidencia |
|---|---|---|
| Visor de certificaciones desplazado (CI, `inicio-visor-certificado` a 1440 px) | corregido por T052 | `inicio.css`: `#certificaciones` fuera de `content-visibility`; caso de `SiteStylesTokensTest`; CI en verde |
| Composición del inicio en CI (región 10) | corregido | Líneas base regeneradas con `ui:audit-data`; CI en verde |
| R8 | corregido | Ficha `docs/changes/2026-11-17-fix-ui-audit-testimonios-intermitente.md`; CI en verde |

## Seguridad
- **Herramientas:** iguales que en la ronda 3. El job de seguridad del PR vuelve a ejecutar trivy, tras la ficha `docs/changes/2026-11-17-fix-trivy-varios-objetivos.md`, y pasa.
- **SCA:** sin cambios de dependencias.

## Hallazgos
Ninguno.

## Preparación para release
- **Rendimiento:** sacar las certificaciones de `content-visibility` añade su maquetación al primer layout del inicio. Lighthouse móvil se repite sobre producción en T095.
- **Sin cambios:** el resto de la preparación es igual que en la ronda 3 (rollback, migraciones, documentación, datos provisionales y fotos generadas).

## Tareas añadidas
- T052 ← visor de certificaciones desplazado (encontrado por CI durante `/release`)

## Aceptados sin tarea
Siguen vigentes los de las rondas 1 y 2: R9–R29 y R32, R34, R35, R36 (ver [r3](history/review.r3.md)).

- **R9** → roadmap: títulos de galería y acerca por debajo de 30 caracteres.
- **R10** → roadmap: certificaciones sin `width`/`height`.
- **R11** → roadmap: galería con `data-src` desde la 4.ª foto.
- **R12** → roadmap: `PublicSiteSchedule::days()` sin uso.
- **R13** → roadmap: tests unitarios de la agrupación del horario.
- **R14** → roadmap: resumen del horario repetido en tres vistas.
- **R15** → roadmap: `SiteScriptsTest` sin comprobación previa de `node`.
- **R16** → roadmap: test de API del filtro `status` de galería.
- **R17** → roadmap: `PublicSiteSeoTest` compara el inicio con el texto de contacto.
- **R18** → roadmap: etiquetas OG y `theme-color` probadas solo en las legales.
- **R19** → roadmap: horario visible del inicio sin comparar con el JSON-LD.
- **R20** → roadmap: `og:description` sin comprobar en las cuatro páginas.
- **R21** → roadmap: caso de abuso de CA15 sin certificación.
- **R22** → roadmap: certificación sin descripción muestra "Dentissa".
- **R23** → roadmap: `site.timezone` sin uso y zona repetida.
- **R24** → roadmap: botones con estado y etiqueta que cambian a la vez.
- **R25** → roadmap: `SITE_WHATSAPP` sin normalizar.
- **R26** → roadmap: `aria-expanded` estático en el `<summary>` del menú.
- **R27** → roadmap: preguntas frecuentes sin `aria-controls`.
- **R28** → roadmap: subir `laravel/framework` a ≥ 12.71.0 con un `/chore`.
- **R29** → roadmap: tarjetas del equipo que solo giran con el puntero.
- **R32** → roadmap: el aviso de copiado no cancela los temporizadores anteriores.
- **R34** → roadmap: test de integración del `inert` de la bienvenida.
- **R35** → roadmap: comentario de `initVisit` fuera de su lugar.
- **R36** → roadmap: la entrada de acerca enumera especialidades a mano.
