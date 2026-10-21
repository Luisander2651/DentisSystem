---
id: 019
slug: reportes-mensuales
status: approved
created: 2026-10-19
extends: []
---

# 019 · Reportes mensuales

## Problema
El administrador no tiene forma de saber cómo le fue a la clínica en un mes: cuántas citas se
atendieron o se cancelaron, cuántos pacientes nuevos llegaron, qué tratamientos se piden más o
cómo se reparte el trabajo entre los doctores. Hoy tendría que contar a mano en la agenda, cita
por cita. Sin estos números no puede comparar un mes con otro ni llevar el resumen fuera del
sistema para revisarlo o compartirlo. Los mismos cálculos los reutilizará después el tablero de
inicio (roadmap → Pendientes y deuda).

## Historias de usuario
- Como administrador, quiero ver los indicadores de un mes, comparados con el mes anterior, para
  saber cómo va la clínica sin contar citas a mano.
- Como administrador, quiero descargar el reporte del mes en un archivo de Excel para revisarlo,
  guardarlo o compartirlo fuera del sistema.
- Como responsable de los datos de la clínica, quiero que el reporte solo lo vea el administrador
  y que el archivo no lleve datos de pacientes, para no sacar datos personales del sistema.

## Criterios de aceptación
- [x] CA1 · Dado un administrador, cuando abre "Reportes" desde su menú, entonces ve el reporte
  del último mes completo y puede elegir otro mes (mes y año); al cambiarlo, todos los
  indicadores se recalculan para ese mes. Cada cita cuenta en el mes de su fecha actual (la de
  después de reprogramarla), en la zona horaria de la clínica.
- [x] CA2 · Dado un mes elegido, cuando se muestra el reporte, entonces aparecen el total de citas
  del mes y cuántas hay en cada estado (completadas, canceladas, reprogramadas pendientes de
  atender, asignadas pendientes de atender y sin cerrar, que son las de fecha ya pasada que
  siguen asignadas o reprogramadas), y junto a cada número el del mes anterior y la diferencia
  (p. ej. "42 · +6 respecto a septiembre").
- [x] CA3 · Dado un mes con citas, cuando se muestra el reporte, entonces aparecen la tasa de
  asistencia (completadas entre el total de citas no canceladas cuya fecha ya pasó) y la tasa de
  cancelación (canceladas entre el total del mes), en porcentaje con un decimal, con las del mes
  anterior. Dado un mes sin citas o un divisor en cero, la tasa se muestra como "Sin datos", no
  como 0 % ni como error, y la comparación con un mes anterior sin citas también dice "Sin datos".
- [x] CA4 · Dado un mes elegido, cuando se muestra el reporte, entonces aparecen los pacientes
  nuevos del mes (dados de alta en ese mes) con los del mes anterior y su diferencia, y los
  pacientes activos (ficha en estado activo) al momento de consultar.
- [x] CA5 · Dado un mes elegido, cuando se muestra el reporte, entonces aparece la lista de
  tratamientos con su número de citas no canceladas del mes, de mayor a menor (empate por
  nombre); los tratamientos sin citas en el mes no aparecen.
- [x] CA6 · Dado un mes elegido, cuando se muestra el reporte, entonces aparece cada miembro del
  staff con citas asignadas en el mes (aunque hoy esté inactivo) con su total de citas no
  canceladas y cuántas completó, de mayor a menor carga.
- [x] CA7 · Dado un mes elegido, cuando se muestra el reporte, entonces aparece cuántas citas no
  canceladas hubo por día de la semana (lunes a domingo) y por hora de inicio (franjas de una
  hora), con la franja y el día de más citas destacados.
- [x] CA8 · Dado el reporte de un mes en pantalla, cuando el administrador pulsa "Descargar
  Excel", entonces recibe un archivo de Excel llamado `reporte-dentissa-AAAA-MM` con una hoja de
  resumen (mes, fecha de generación, citas por estado, tasas y pacientes, con el mes anterior) y
  una hoja por cada lista (tratamientos, staff y demanda por día y hora), con los mismos números
  que la pantalla; se abre en Excel sin avisos de reparación.
- [x] CA9 · Dado el mes en curso, cuando el administrador lo elige, entonces el reporte se muestra
  y se descarga con el aviso "Mes en curso: datos hasta hoy" en pantalla y en el archivo. Dado un
  mes futuro, no se puede elegir.
- [x] CA10 · (abuso) Como asistente, doctor, staff inactivo, paciente o sin sesión, intento ver
  el reporte o descargarlo, por la pantalla o por otra vía → se rechaza con acceso denegado, no
  se genera ningún archivo y la entrada "Reportes" no aparece en mi menú.
- [x] CA11 · (abuso) Como administrador, pido el reporte de un mes inválido (mes 13, formato
  incorrecto, un mes futuro o anterior a enero de 2020) por otra vía → se rechaza con un mensaje
  en español que dice qué mes se acepta, sin archivo y sin error interno.
- [x] CA12 · (abuso) Como administrador, descargo el reporte de un mes con citas → el archivo no
  contiene nombres, teléfonos, emails, direcciones ni datos clínicos o médicos de ningún paciente;
  solo totales, nombres de tratamientos y nombres del staff.
- [x] CA13 · (abuso) Dado un tratamiento o un miembro del staff cuyo nombre empieza por `=`, `+`,
  `-` o `@`, cuando se descarga el reporte → el nombre aparece como texto tal cual y Excel no lo
  interpreta como fórmula.
- [x] CA14 · (abuso) Como administrador, pido más de 10 descargas del reporte en un minuto → las
  siguientes se rechazan con el mensaje "Demasiadas descargas. Espera un momento e inténtalo de
  nuevo." hasta que pase el minuto; ver el reporte en pantalla no se limita.

## Fuera de alcance
- Ingresos, pagos, presupuestos y aceptación de tratamientos: el sistema no guarda precios.
- Inasistencias separadas de cancelaciones: no existe el estado "no asistió".
- Ocupación del sillón y huecos libres: no hay horario de la clínica.
- Pacientes que no vuelven y cualquier lista o detalle con datos de pacientes (en pantalla o en
  el archivo).
- Gráficas de tendencia de varios meses; solo se compara con el mes anterior.
- Reportes por rango de fechas libre, por semana o por año.
- Envío del reporte por email o generación automática cada mes.
- Exportar a PDF o CSV.
- Reportes para el asistente o el doctor.
- El tablero de inicio, que es otra spec y reutilizará estos cálculos.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno sale del sistema. El reporte agrega citas y pacientes en
  totales; los únicos nombres que muestra son los de tratamientos (pública) y del staff (interna,
  solo visible para el administrador, según `docs/security.md`).
- Quién puede hacer qué:

  | Acción | Administrador | Asistente | Doctor | Paciente / inactivo / sin sesión |
  |---|---|---|---|---|
  | Ver el reporte de un mes | sí | denegado | denegado | denegado |
  | Descargar el Excel | sí (máx. 10 por minuto) | denegado | denegado | denegado |

- Casos de abuso:
  - Como asistente, doctor, paciente o sin sesión, intento ver o descargar el reporte → se
    rechaza (CA10).
  - Como administrador, pido un mes inválido por otra vía → se rechaza sin error interno (CA11).
  - Como administrador, busco datos de pacientes en el archivo → no los hay (CA12).
  - Como alguien con permiso para nombrar tratamientos o staff, pongo un nombre que Excel
    ejecutaría como fórmula → aparece como texto (CA13).
  - Como administrador o con su sesión robada, pido descargas en masa → se limitan (CA14).

## Auditoría
Esta spec no emite eventos de auditoría propios: el reporte solo contiene totales, sin datos
personales ni de salud, así que verlo o descargarlo no es una lectura de datos sensibles
(`docs/observability.md` → Registro de auditoría). Los accesos denegados de CA10 son eventos
obligatorios del registro, que aún no existe y se construye en una spec aparte (objetivo 5 del
roadmap); cuando exista, deberán quedar registrados con actor, acción, recurso y fecha.
Desviación de P14 que se justifica en el Constitution Check de `/plan`, como en las specs 014,
017 y 018.

## Diseño
Debe sentirse como un resumen claro y tranquilo para quien administra la clínica: los números
importantes se leen de un vistazo, la comparación con el mes anterior se entiende sin pensar
(sube o baja, y cuánto) y nada parece una hoja de cálculo cruda.
Pantallas que toca: una pantalla nueva "Reportes" (selector de mes, resumen de citas y
pacientes con su comparación, tasas, listas de tratamientos, staff y demanda, botón "Descargar
Excel", estados de carga, mes sin citas, mes en curso y error) y la entrada en el menú lateral del
administrador. No debe parecerse a la agenda ni al tablero de inicio que vendrá después. El
archivo de Excel también se diseña: encabezados, anchos de columna y formato de porcentajes y
fechas legibles al abrirlo. Solo con componentes del sistema de diseño
([docs/design/system.md](../../design/system.md)).
Diseño de la spec: [design.md](design.md) (composición C: cifras con indicador de cambio y semana del mes, más tratamientos y staff con barras).

## Requisitos no funcionales
- El reporte de un mes con hasta 2 000 citas se muestra y se descarga en menos de 3 s.
- Los cálculos de los indicadores se hacen en el servidor; la pantalla y el archivo muestran los
  mismos números porque salen del mismo cálculo (P13).
- La comprobación de permisos se hace en el servidor; ocultar la entrada del menú nunca la
  sustituye. Cada operación de la tabla tiene una prueba de acceso denegado por cada actor no
  autorizado (P5).
- La pantalla de reportes pasa la prueba en navegador a 390, 768 y 1440 px (P15,
  `npm run test:ui`), con contraste, foco visible y uso con teclado (WCAG 2.1 AA); las listas
  se leen en orden con lector de pantalla.
- Los errores no revelan detalles internos (P7) y los logs del reporte no llevan datos de
  pacientes (P11).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-17 | brecha | ¿El reporte va en la spec del tablero o aparte? | Aparte, después de la 018 y antes del tablero | usuario (`docs/roadmap.md` → Pendientes y deuda) |
| 2026-10-19 | brecha | ¿Quién ve y descarga el reporte? | Solo el administrador | usuario |
| 2026-10-19 | brecha | ¿Formato del archivo? | Excel real con varias hojas, sin CSV ni PDF (comparados: CSV sin hojas ni formato y con los mismos riesgos; PDF no editable y más caro); la dependencia y su ADR se deciden en `/plan` | usuario |
| 2026-10-19 | brecha | ¿Solo totales o también el detalle con datos de pacientes? | Solo totales; ningún dato de pacientes en pantalla ni en el archivo | usuario |
| 2026-10-19 | brecha | ¿Qué indicadores entran? | Citas por estado con el mes anterior y tasas; pacientes nuevos y activos; tratamientos más solicitados; carga por miembro del staff; demanda por día y hora | usuario |
| 2026-10-19 | brecha | La spec pasa de ~10 criterios (14), ¿se divide? | Una sola spec: el Excel repite los cálculos de la pantalla | usuario |
| 2026-10-19 | implícita | ¿En qué zona horaria se decide a qué mes pertenece una cita? | La de la clínica, `America/Mexico_City`, la de toda la aplicación | `docs/specs/017-reprogramar-cancelar-citas/spec.md` |
| 2026-10-19 | implícita | ¿Qué es un paciente activo? | Ficha en estado activo | `docs/roadmap.md` (alcance del tablero, decisión del usuario 2026-10-17) |
| 2026-10-19 | implícita | ¿Ver el reporte es una lectura de datos sensibles que se audita? | No: solo totales; los accesos denegados sí, cuando exista el registro (objetivo 5) | `docs/observability.md` → Registro de auditoría |
| 2026-10-19 | supuesto | ¿Qué mes se muestra al abrir? | El último mes completo | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Cómo se calculan las tasas? | Asistencia: completadas entre no canceladas con fecha pasada; cancelación: canceladas entre el total del mes; un decimal; "Sin datos" con divisor en cero | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Las canceladas cuentan en tratamientos, carga y demanda? | No, solo citas no canceladas | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Qué cuenta como "pacientes nuevos"? | Fichas dadas de alta en el mes, por el staff o por autorregistro | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Quién aparece en la carga? | Cualquier miembro del staff con citas asignadas en el mes, también si hoy está inactivo | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Qué meses se aceptan? | Desde enero de 2020 hasta el mes en curso (marcado como parcial) | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Se limita la descarga? | Máximo 10 descargas por minuto por administrador; ver en pantalla no se limita | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | supuesto | ¿Cuánto puede tardar? | Menos de 3 s para un mes con hasta 2 000 citas | agente (confirmado por el usuario, 2026-10-20) |
| 2026-10-19 | diseño | ¿Cómo se ve la pantalla de reportes? | Composición C de `design.md`; el color del cambio dice si es favorable (verde) o desfavorable (ámbar) | /design-spec 019, usuario |

## Notas para /plan
- Formato `.xlsx`: el roadmap barajaba OpenSpout (ligera) o PhpSpreadsheet (más completa); la
  dependencia necesita ADR por P10 (versión fijada, ≥ 7 días de antigüedad).
- Los cálculos deben poder reutilizarse en el tablero de inicio (spec posterior).
- El módulo `Estadisticas` está vacío y sin alcance (`docs/specs/README.md`); decidir si estos
  cálculos van ahí o en un módulo nuevo `Reports`.
- CA13 es inyección de fórmulas (CSV/Excel injection): escribir las celdas como texto.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-19 | Creación en `draft` | /specify, desde el pendiente del roadmap |
| 2026-10-19 | Aprobada | Aprobación del usuario |
| 2026-10-21 | Implementada | /implement: T001–T091 hechas; CA1–CA14 con sus tests en verde (suite completa 1653/1653, `ui-audit` dos veces sin fallos) |
| 2026-10-21 | Vuelve a `approved` | /review ronda 1: changes_requested (R1 incumple CA3 en el mes en curso); correcciones en T058–T072 |
| 2026-10-21 | Implementada de nuevo | /implement: T058–T072 de la review ronda 1 (R1–R7, R9–R11, R24, R30, R31); suite completa 1671/1671 |
| 2026-10-21 | Vuelve a `approved` | /review ronda 2: changes_requested (R37, test inestable en paralelo); correcciones en T073–T074 |
