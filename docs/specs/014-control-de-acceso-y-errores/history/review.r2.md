---
spec: 014-control-de-acceso-y-errores
verdict: changes_requested
round: 2
date: 2026-09-25
base: 45acc0c
head: 328aed9
human_signoff: pending
---

# Review · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

Ronda 2 (`--rerun`) sobre la ronda 1 ([review.r1.md](review.r1.md)). Revisa los hallazgos abiertos
R1–R7, el diff `45acc0c..328aed9` y la verificación automática completa. No reabre los menores
aceptados R8–R29.

## Resumen
**changes_requested.** R1–R7 quedaron corregidos y no hay bloqueantes. El usuario decide corregir antes
de liberar el importante nuevo R30, un test que no comprueba lo que dice en 2 de sus 3 tablas, y los
menores R31–R36. R30 afecta a la calidad del test, no a la seguridad: el servidor rechaza las escrituras
de quien no es administrador ni asistente, y eso ya lo prueban los datasets de acceso y la verificación
manual (aclaración del usuario, 2026-09-25).

Conteo de la ronda: 0 bloqueantes · 1 importante · 6 menores.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie) | ✅ 800 passed, 0 fallos |
| `vendor/bin/pint --test` (archivos PHP del rango) | ✅ |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores |

## Seguimiento de la ronda 1
| R | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `bootstrap/app.php:59-67` registra una vez, saneado, toda excepción inesperada de `api/*` y detiene el reporte por defecto; el render solo construye la respuesta; `from()` sigue registrando para los controladores. Test: `GlobalErrorFallbackTest` (un `report()` manual deja un único log sin el teléfono) |
| R2 | corregido | Matriz con las 32 claves, comprobadas contra el mapa; permiso desconocido denegado para los 3 roles |
| R3 | corregido | La cita pertenece a un Doctor distinto del administrador que actúa; `user_id` verificado en la base |
| R4 | corregido | Saludo propio del doctor; `StaffNavigationTest` |
| R5 | corregido | Textos según `$canEdit`; `RecordsScreenTest` |
| R6 | corregido | Cabecera, celda y `colspan` según `$canEdit` (Blade y `index.js`). El caso positivo del test es débil: R30 |
| R7 | corregido | axios 1.20.0 (publicada 2026-08-26); `npm audit`: 7 avisos, ninguno de axios; EX1 y RS16 con motivo, aprobador y vencimiento |
| R11, R17 | corregidos | Comentario retirado; tipos de retorno en `bootstrap/app.php` |
| R19 | corregido en parte | `index.js` ya usa `data-records-can-edit` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos, SAST, contenedores | No ejecutados: no instalados (R27, aceptado en la ronda 1) |
| SCA (`composer audit`) | ✅ sin avisos |
| SCA (`npm audit`) | crít: 2 · alta: 5 (vite, rollup y otras herramientas de build), cubiertas por la excepción EX1 vigente |

Sin cambios en rutas protegidas. Sin datos sensibles nuevos en logs ni en respuestas.

## Observabilidad
- Eventos de auditoría: no aplica (diferidos; P14 aceptado).
- Datos sensibles en logs del código tocado: ninguno; el log saneado no lleva el mensaje.
- `request_id`: no (P14 aceptado).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R30 | importante | `tests/Modules/Patients/Integration/RecordsScreenTest.php:65` | La regex `<thead[^>]*>(.*?)</thead>\s*<tbody id="…"` empieza en el primer `<thead>` de la página y cruza tablas: el caso "keeps the actions column" pasaría aunque dirección o datos médicos perdieran su cabecera. El caso del doctor sí es válido. | Impedir que la captura cruce otro `</thead>` (p. ej. `((?:(?!</thead>).)*)`) |
| R31 | menor | `package-lock.json`; nota de T083 | La actualización de axios cambió dependencias transitivas (nuevas: `https-proxy-agent`, `agent-base`, `debug`, `ms`; suben: `follow-redirects`, `form-data`, `hasown`, `es-object-atoms`, `proxy-from-env` 1→2), todas MIT y con más de 7 días, pero la verificación no quedó registrada. | Anotarlas en T083 (`agent-security.md` §2) |
| R32 | menor | `tests/Modules/Appointments/Integration/GlobalErrorFallbackTest.php:96-97` | Salto de línea literal en `implode` en lugar de `"\n"`. | Usar `"\n"` |
| R33 | menor | `docs/security.md:190` | RS16 (Alta) está entre riesgos Baja, pero la sección se ordena por severidad. | Moverla junto a los riesgos Alta |
| R34 | menor | `docs/security.md:89` (EX1) | El motivo dice que los paquetes "no llegan al navegador", pero los avisos de vite afectan al servidor de desarrollo (`composer run dev`). | Añadir la condición: el servidor de desarrollo no se expone fuera de localhost |
| R35 | menor | `spec.md`, Historial | La entrada "Implementadas…" precede a la de la ronda 1, y cita "T077–T084" (faltan T085, T086). | Ordenar y completar |
| R36 | menor | `resources/views/pages/dashboard.blade.php:40` | "…de tus pacientes" sugiere que el doctor solo ve a los suyos, pero ve a todos (RS14). | Texto neutro, p. ej. "Consulta los expedientes clínicos de los pacientes." |

## Preparación para release
- Rollback factible: sí (revert del merge; sin migraciones).
- Migraciones: no hay.
- Docs: `architecture.md` y `observability.md` al día; `security.md` con RS16 y EX1.
- SCA: sin críticas ni altas fuera de la excepción EX1 vigente.
- Cobertura de riesgos: sin cambios respecto a la ronda 1; RS16.a aceptada (EX1).

## Tareas añadidas
- T087 ← R30, R32
- T088 ← R31, R33, R34, R35
- T089 ← R36
