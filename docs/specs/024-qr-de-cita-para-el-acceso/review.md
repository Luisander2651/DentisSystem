---
spec: 024-qr-de-cita-para-el-acceso
verdict: approved
round: 3
date: 2026-11-09
base: 4c4a8fa
head: 950d445
human_signoff: pending
---

# Review · 024 QR de cita para el acceso

## Resumen
Aprobada. R16 y R17 de la ronda 2 están corregidos y el diff de la ronda no abre hallazgos nuevos.
No quedan bloqueantes ni importantes abiertos. Los menores (R6–R15 de la ronda 1 y R18–R21 de la
ronda 2) están aceptados sin tarea y `/release` los pasa al roadmap.
Conteo de la ronda: bloqueante 0 · importante 0 · menor 0

Ronda `--rerun` con un diff pequeño (13,5 KB, 5 archivos de código), revisada en la sesión
principal releyendo el diff desde cero.

## Verificación automática
El código de `950d445` es el que verificó T030 el 2026-11-09: el último commit solo toca
`tasks.md` y `spec.md`.

| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (completa, en serie, `memory_limit=1G`) | ✅ 2 020 pasan, 0 fallan (2 019 de T027 más el test de T029) |
| `npm run test:ui` (9 tandas, 105 entradas, 333 visitas a 390, 768 y 1440 px) | ✅ 0 fallos, sin ningún 429 |
| Flujos nuevos de T028, dos corridas | ✅ |
| `vendor/bin/pint --dirty` | ✅ |
| `npm run build` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/024-qr-de-cita-para-el-acceso` | ✅ 0 errores |
| Verificación manual de T029 en Firefox | ✅ el usuario, 2026-11-09: el PNG se descarga y el teléfono lee el identificador |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `AppointmentQrTest`; `ChillerlanQrCodeGeneratorTest` (con el tamaño propio del SVG); `AppointmentQrPropertiesTest` | ✅ | |
| CA2–CA4, CA7, CA9–CA12 | Sin cambios desde la ronda 1 | ✅ | |
| CA5 | ui-audit `agenda-cita-detalle-pendiente`: respuesta tardía, Escape, reabrir la misma cita y "cerrar A, abrir B" | ✅ | |
| CA6 | ui-audit "Descargar QR ofrece el PNG de la cita"; Firefox, manual (T029) | ✅ | |
| CA8 | ui-audit "Si el QR no carga…" | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `SizedQrMarkupSvg` es parte del adaptador de
  `Infrastructure/Qr` (plan D1, ADR 0007); el puerto y el caso de uso no cambian.
- Partes del plan sin implementar: ninguna.

## Seguimiento de la ronda 2
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R16 | ✅ corregido | `openingCount` en `view-appointment.js`: `open()` lo incrementa y lo comprueba tras cada `await`, también en `onload` y `onerror`, y el `close` lo invalida; `closeModal()` solo cierra el diálogo. Flujos "Reabrir la misma cita no duplica el detalle" y "Cerrar A y abrir B no muestra el QR de A"; sin la guarda fallan los tres flujos de la carrera (nota de T028) |
| R17 | ✅ corregido | `SizedQrMarkupSvg::header()` añade `width` y `height` (8 px por módulo); test en `ChillerlanQrCodeGeneratorTest` que falló sin el cambio; comprobado en Firefox por el usuario |
| R18–R21 | aceptados (ronda 2) | Sin cambios |

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 | ✅ | Cada corrección con su test, visto fallar sin el cambio (notas de T028 y T029) |
| P3 | ✅ | `chillerlan` solo en `Infrastructure/Qr`; `AppointmentsArchitectureTest` en verde |
| P10 | ✅ | Sin dependencias nuevas; la clase extiende la salida SVG de la librería ya aprobada (ADR 0007) |
| P13, P15 | ✅ | El JS solo presenta; sin valores visuales nuevos |
| Resto | sin cambios desde la ronda 1 | |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`4c4a8fa..HEAD`) | ✅ sin hallazgos |
| semgrep `p/php` (`app/Modules/Appointments/Infrastructure/Qr`) | ✅ 0 hallazgos |
| `composer audit` / `npm audit` | ➖ sin cambios de dependencias. Siguen los 8 avisos de Symfony de la línea base, que resolverá un `/chore` aparte antes de `/release` (decisión del usuario, 2026-11-08) |
| trivy | ➖ sin cambios en `docker/` |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Inyección en el SVG | ✅ | `width` y `height` son enteros calculados de la matriz (`%d`); el test sin `<script>`, `href`, `xlink` ni `onload` sigue en verde |
| Credencial equivocada en pantalla | ✅ | Ninguna respuesta tardía rellena el detalle, ni siquiera de la misma cita (R16) |

## Observabilidad
- Sin logs ni eventos nuevos. P14 sigue aceptado en el plan.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rutas públicas | ➖ | El diff solo toca el detalle de la cita del panel, el generador del QR y sus pruebas |
- Sección "Descubribilidad" del plan aplicada: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| — | — | — | Sin hallazgos nuevos en esta ronda | — |

## Preparación para release
- Rollback factible: sí. `docker/prod/rollback.sh` a la versión anterior; sin migraciones ni datos.
  Los QR ya enviados siguen funcionando: el contenido (el id de la cita) no cambia, solo el tamaño
  del documento.
- Migraciones: no hay.
- Docs: `architecture.md` (T090), `observability.md` (T091), `security.md` (T052) y
  `design/system.md` (T051, T025) al día.
- SCA: los 8 avisos de Symfony siguen sin severidad publicada ni excepción en `security.md`. Se
  resuelven con un `/chore` aparte antes de `/release` (decisión del usuario, 2026-11-08).

## Tareas añadidas
- Ninguna en esta ronda (las de la ronda 2, T028–T030, están hechas).

## Aceptados sin tarea
Decisiones del usuario del 2026-11-08 (rondas 1 y 2): pasan al roadmap como deuda.
- **R6** → roadmap: el QR copia el id tal como llega (mayúsculas)
- **R7** → roadmap: sin cita reprogramada en los datos de ui-audit
- **R8** → roadmap: entradas del asistente y del doctor sin expectativas del QR
- **R9** → roadmap: ningún test fija `throttle:api` en la ruta del QR
- **R10** → roadmap: ADR 0007 describe mal el ayudante que decodifica el QR en los tests; subir de versión exige revisar ese ayudante y `SizedQrMarkupSvg`
- **R11** → roadmap: prueba de rendimiento del QR con una sola medida
- **R12** → roadmap: propiedades de Eris con generador constante
- **R13** → roadmap: formato roto y renombre ajeno en `checks-composition.mjs`
- **R14** → roadmap: el marco vacío del QR sigue visible cuando falla
- **R15** → roadmap: "Descargar QR" deshabilitado sin estado accesible
- **R18** → roadmap: espera fija de 300 ms en el flujo de descarga del QR
- **R19** → roadmap: el fondo del PNG del QR depende de `--color-surface`
- **R20** → roadmap: comprobaciones de nulos incompletas en el bloque del QR
- **R21** → roadmap: comentarios dentro del código en el controlador y el proveedor del QR
