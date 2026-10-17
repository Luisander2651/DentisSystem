---
spec: 017-reprogramar-cancelar-citas
verdict: approved
round: 2
date: 2026-10-16
base: 02448ff
head: d6b7df0
human_signoff: Luisander2651, 2026-10-16
---

# Review · 017 Reprogramar y cancelar citas

## Resumen
Ronda de seguimiento (`--rerun`) sobre `02448ff..d6b7df0`: las correcciones T040–T045. Los cuatro hallazgos importantes de la ronda 1 quedan resueltos:
- **R1:** con fecha y hora vacías o en `null` la respuesta ya es el texto de CA2, y el test lo cubre.
- **R2:** el controlador elige el 422 por el tipo de la excepción, no por su texto.
- **R3:** la guardia del caso de uso y su 422 tienen test.
- **R11:** el fallo intermitente se debe a que la app agota la conexión a Postgres en el entorno local. No es una espera del test ni código de la spec. Queda como R12, aceptado al roadmap.

El código nuevo no añade hallazgos. El diff es de 10 KB, así que la revisión se hizo sin subagentes, releyendo los archivos desde cero.

Conteo (ronda 2): bloqueante 0 · importante 0 · menor 1 (R12)

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 1342 pasan (13 231 aserciones); línea base: 0 |
| `vendor/bin/pint --test` sobre los archivos PHP del rango | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui -- --only agenda,citas-del-dia,detalle-cita,reprogramar-cita,nueva-cita,cancelar-cita` | ⚠ No se repitió: el rango no toca interfaz ni tests de navegador. T045 corrió la convención diez veces sobre el mismo código (`041d110`; T045 y T092 solo cambian documentación). Las corridas 1–5 seguidas tuvieron 0 fallos. Las corridas 6 y 7 tuvieron 1 y 2 fallos, todos con 500 por `SQLSTATE[08006] … timeout expired` al conectar con Postgres (R12). Las corridas 8–10 tuvieron 0 fallos |
| `aidd.py validate` | ✅ sin errores |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | resuelto | `UpdateAppointmentRequest.php`: `Rule::requiredIf($this->exists('date') \|\| $this->exists('time'))` con `bail` y mensajes `*.required` con el texto de CA2. En el dataset de `UpdateAppointmentTest` › rejects a reschedule without date or without time hay dos casos nuevos: `'both empty…'` y `'both null'`. Ambos comprueban `errors.date.0` con el texto de CA2. El cuerpo vacío sigue en 409 porque no trae ninguna de las dos claves |
| R2 | resuelto | `RescheduleAndStatusTogetherException` es una clase `final` propia, capturada en su `catch` antes de `AppointmentAplicationExceptions`. Se retiró la comparación de `getMessage()`, y `rescheduleAndStatusTogether()` ya no tiene referencias |
| R3 | resuelto | Dos tests nuevos: «refuses in the use case a reschedule that travels with a status…», que llama al caso de uso con un DTO mezclado y comprueba que la cita no cambia, y «answers the refusal… as an input error of the status», que comprueba el 422 y `errors.status.0`. El segundo provoca la excepción desde el repositorio simulado porque por HTTP el FormRequest la intercepta antes; lo que prueba es la conversión en el controlador |
| R11 | resuelto → R12 | Nota de T045: diez corridas con la salida completa. La causa es la conexión a la base de datos en el entorno local, no la espera del test; los fallos aparecieron también en el expediente, que la spec no toca |
| R4–R10 | aceptados (ronda 1) | Sin cambios en este rango |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA2 | `UpdateAppointmentTest` › rejects a reschedule without date or without time (4 casos) | ✅ | R1 resuelto |
| CA6 | `UpdateAppointmentTest` › rejects a request that reschedules and changes the status at once; refuses in the use case…; answers the refusal… | ✅ | R3 resuelto |
| CA1, CA3–CA5, CA7–CA11 | Los mismos de la ronda 1 | ✅ | Sin cambios en este rango; la suite completa pasa |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los 6 archivos de código del rango tienen tarea (`scope.md`).
- T045 no tiene diff de código, porque la causa estaba fuera del test (ver su nota).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ✅ | T040 y T042 fallaban antes de T041 y T043 (commits `bac2382` y `4d61bd7`, anotado en las tareas) |
| P3 Capas del módulo | ✅ | La excepción nueva está en `Aplication/Exceptions` y el controlador traduce a HTTP |
| P6 Validación con FormRequest | ✅ | Reglas en array, mensajes en español |
| P7 Errores sin detalles internos | ✅ | El 422 lleva el texto fijo de CA6; el `catch (\Exception)` final sigue respondiendo con el 500 genérico |
| Resto | sin cambios | Igual que en la ronda 1 (`history/review.r1.md`); P4 sigue `❌ aceptado` (plan D10) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`02448ff..HEAD`, 8 commits) | ✅ 0 hallazgos |
| semgrep `p/php` (`app/Modules/Appointments`) | ✅ 0 hallazgos |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy config (`docker/`) | ✅ 0 |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `authorize()` sigue antes de las reglas; el cambio de reglas no lo altera |
| A06:2025 Diseño inseguro | ✅ | La no-mezcla de CA6 está en el FormRequest y en el caso de uso, cada una con su test |
| A10:2025 Manejo de condiciones excepcionales | ✅ | El orden de los `catch` mantiene el 409 del cuerpo vacío y el 500 genérico |
| Proceso | ✅ | Sin lockfiles, rutas protegidas ni instrucciones dirigidas a agentes |

## Observabilidad
- Sin logs ni eventos de auditoría nuevos en el rango.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rutas públicas | ➖ | El rango solo toca la API de citas y sus tests |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R12 | menor | entorno local (`docker-compose.yml`, `config/database.php:87-100`) | Cuando el equipo va justo de memoria, la app agota los 30 s de conexión de `pdo_pgsql` con `db`, aunque Postgres no registra errores ni reinicios. Eso da falsos fallos en `npm run test:ui` y en Pest en paralelo (`QueryException`). Ocurrió en las corridas 6 y 7 de T045 (log de `laravel-app` a las 15:43, 15:46 y 15:49). No afecta al código de la spec | Ficha `/fix` o `/chore` del entorno local: límites de memoria de los contenedores, o un aviso explícito de conexión agotada en `test:ui` para no confundirlo con un fallo de la interfaz |

## Preparación para release
- Rollback factible: sí. No hay cambios de esquema ni datos; igual que en la ronda 1.
- Migraciones: ninguna.
- Docs: `docs/architecture.md` ya describe el 422 de un `PUT` que mezcla acciones (T090); este rango no cambia el contrato.
- SCA: sin vulnerabilidades críticas ni altas.
- Antes del deploy: confirmar que el `.env` de producción no fija `APP_TIMEZONE` en otra zona (plan → Rollout).

## Tareas añadidas
Ninguna en esta ronda. En la ronda 1: T040 y T041 ← R1; T042, T043 y T044 ← R2 y R3; T045 ← R11.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-16 (ronda 1):
- **R4** → roadmap: mostrar en la agenda el texto de dominio de los 409 (cita cerrada, choque de horario) en lugar del genérico.
- **R5** → roadmap: «No, mantenerla», Escape, la X y el fondo durante el envío de la cancelación.
- **R6** → roadmap: `whatsapp_reminder` invierte en lugar de aplicar el valor (no idempotente).
- **R7** → roadmap: `reschedule()` guarda `updatedAt` con el reloj del sistema en lugar de `$now`.
- **R8** → roadmap: entrada de navegador del detalle de una cita cancelada en el expediente.
- **R9** → roadmap: el test de reproducción de la ficha debería comprobar el texto de CA6.
- **R10** → roadmap: categoría OWASP de TM2–TM4 (A06:2025, no A04).

Aceptado por el usuario el 2026-10-16 (ronda 2):
- **R12** → roadmap: en local la conexión a Postgres agota su tiempo cuando el equipo va justo de memoria y da falsos fallos en `test:ui` y en Pest en paralelo.
