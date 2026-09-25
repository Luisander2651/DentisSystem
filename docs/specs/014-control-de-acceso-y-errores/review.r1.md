---
spec: 014-control-de-acceso-y-errores
verdict: changes_requested
round: 1
date: 2026-09-25
base: c1d259e
head: 45acc0c
human_signoff: pending
---

# Review · 014 Control de acceso a pacientes y citas, y errores sin detalles internos

## Resumen
**changes_requested.** No hay bloqueantes: los 16 criterios están implementados y probados de verdad, la
suite pasa y la constitución se cumple (P14 dentro de su excepción aceptada). El usuario decide corregir
antes de liberar seis hallazgos importantes (R1–R6: la red de errores puede perder reportes, dos tests
que prometen más de lo que comprueban y tres textos o controles de la interfaz del doctor) y registrar
una excepción con vencimiento para las vulnerabilidades previas de npm (R7).

Conteo: 0 bloqueantes · 7 importantes · 22 menores. Revisores independientes: spec, plan y alcance,
constitución, seguridad (OWASP), calidad y tests, y accesibilidad; más un `/code-review` previo sin bugs.

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest` (serie) | ✅ 740 passed, 0 fallos (línea base: 495, 0 fallos) |
| `./vendor/bin/pest --parallel` | ⚠ intermitente: timeouts de conexión a PostgreSQL con 12 procesos (entorno; deuda en el roadmap). En serie, 740/740 |
| `vendor/bin/pint --test` (archivos PHP del rango) | ✅ |
| `npm run build` | ✅ |
| `aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PatientsAccessControlTest`, `AppointmentsAccessControlTest` (admin) + tests funcionales existentes | ✅ | |
| CA2 | Datasets (asistente), `AddressTest`, `PatientRecordTest`, `GetAppointmentsTest` | ✅ | |
| CA3 | Datasets (doctor) | ✅ | |
| CA4 | `RecordsScreenTest`, `StaffNavigationTest` | ✅ | Textos y columna "Acciones" del doctor: R5, R6 |
| CA5 | Datasets (paciente) + foto de la BD sin cambios | ✅ | Sin caso "sobre sí mismo" (R22) |
| CA6 | Datasets (asistente), test de contraseña | ✅ | |
| CA7 | Datasets (doctor), test de contraseña | ✅ | |
| CA8 | Datasets (inactivo), `RecordsScreenTest`, `RolePermissionsTest` | ✅ | Solo administrador inactivo (R22) |
| CA9 | Datasets (sin sesión), `GlobalErrorFallbackTest` | ✅ | |
| CA10 | `AppointmentsAccessControlTest`, `GetAppointmentsTest` | ✅ | |
| CA11 | `PatientsAccessControlTest` (hash de la contraseña) | ✅ | |
| CA12 | `UnexpectedErrorTest` ×4, `GlobalErrorFallbackTest` | ✅ | 6 áreas cubiertas |
| CA13 | `Patients/UnexpectedErrorTest`, `GlobalErrorFallbackTest` | ✅ | |
| CA14 | `PatientBusinessMessagesTest` | ✅ | |
| CA15 | `StaffNavigationTest` | ✅ | |
| CA16 | `PatientBusinessMessagesTest` | ✅ | Solo alta, no edición (R22) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin nota. T076 (selector de doctores), el enlace "Ver Expedientes Clínicos" del asistente (T062) y las `StorageException` de contenido (T064) tienen nota. Los archivos de `.ai/`, `docs/` y `AIDLC.md` del rango vienen de `/init` y de las actualizaciones del flujo, no de la implementación.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec y plan aprobados; tareas trazadas |
| P2 | ✅ | Tests en rojo antes (commit `2c4e602`) y en verde después |
| P3 | ✅ | `assertCan` primera instrucción en cada caso de uso tocado; `GetTreatmentsController` pasa por su caso de uso |
| P4 | ✅ | Contrato en `plan.md` fijado por los datasets |
| P5 | ✅ | `EnsureActiveStaff` + mapa de permisos; denegación por actor probada |
| P6 | ➖ | Sin cambios de entrada |
| P7 | ✅ | `UnexpectedErrorResponse` + red global; sin `getMessage()` en ningún 500 |
| P8 | ✅ | Sin `env()` en el código tocado |
| P9 | ➖ | Sin migraciones |
| P10 | ➖ | Sin dependencias nuevas |
| P11 | ✅ | Log sin mensaje; controlador de citas sin teléfono ni nombre; excepciones de Patients sin valores |
| P12 | ➖ | Sin producción |
| P13 | ✅ | La UI solo oculta; el servidor decide |
| P14 | ❌ aceptado | Sin `request_id` ni auditoría (excepción aprobada el 2026-09-24) |
| Restricciones (WCAG 2.1 AA) | ✅ | Sin barreras; mejoras en R4–R6 y menores |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks) | No ejecutado: no instalado (decisión del usuario; R27) |
| SAST (semgrep) | No ejecutado: no instalado (R27) |
| SCA (`composer audit`) | ✅ sin avisos |
| SCA (`npm audit`) | ❌ crít: 2 · alta: 7 · media: 1, previas a la 014 (R7) |
| Contenedores (trivy) | No ejecutado: no instalado (R27) |

| Tema OWASP 2025 | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | Dos capas (`staff` + `assertCan`), denegado por defecto; propiedad del recurso → spec 013 |
| A02 Configuración | ➖ | Red de `api/*` independiente de `APP_DEBUG`; vistas web sin cambios (RS10) |
| A03 Cadena de suministro | ❌ previo | R7 |
| A04 Datos sensibles | ✅ | Respuestas sin valores; logs del código tocado limpios. OB2.b fuera de alcance (D7) |
| A05 Inyección / XSS | ✅ | `escapeHtml` intacto; plantilla estática; Blade escapa |
| A06 Diseño inseguro | ✅ | TM1–TM12 con control y test |
| A07 Autenticación | ✅ | 401 JSON; inactivos rechazados en las dos capas |
| A09 Registro y monitoreo | ⚠ | R1; P14 aceptado |
| A10 Condiciones excepcionales | ✅ | Fallo cerrado: 403 en controladores y red global |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (diferidos a la spec del objetivo 5; P14 aceptado).
- Datos sensibles en logs del código tocado: ninguno. `POST /appointments` sigue registrando el teléfono desde el caso de uso y el listener de WhatsApp: OB2.b, fuera de alcance (D7), no es hallazgo de esta spec.
- `request_id` en los logs nuevos: no (P14 aceptado). Riesgo de pérdida de reportes: R1.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `bootstrap/app.php:56-60` | El reporte por defecto se suprime según la URL (`api/*`), no según si la red va a registrar la excepción: un `report()`/`rescue()` o un error posterior a la respuesta en `api/*` se perdería sin rastro. Hoy no hay ninguno en `app/`. | Suprimir solo lo que el render registra (marcar la excepción desde el render o reportarla sin datos) y test con `report()` en una ruta `api/*` |
| R2 | importante | `tests/Modules/Users/Integration/RolePermissionsTest.php:18-38` | La matriz cubre 15 de los 32 permisos del mapa y no prueba la denegación por defecto de un permiso desconocido. | Comparar con el mapa completo y añadir el caso de permiso inexistente |
| R3 | importante | `tests/Modules/Appointments/Integration/CreateAppointmentTest.php:25-31`, `UpdateAppointmentTest.php:29-38` | Los casos "…for any doctor…" duplican otros casos y la cita pertenece a un administrador, no a un doctor. | Asignar la cita a un Doctor distinto del actor y verificar en BD |
| R4 | importante | `resources/views/pages/dashboard.blade.php:40` | El saludo del doctor usa el texto del paciente ("Tu salud dental…"). | Texto propio para el doctor |
| R5 | importante | `resources/views/pages/records/index.blade.php:15, 42` | La cabecera y la tarjeta prometen "gestionar" y "ver y editar" al doctor, que ve en solo lectura. | Textos según `$canEdit` |
| R6 | importante | `resources/views/components/records/{contact-info,address,medical-data}-table.blade.php` (`<th>Acciones</th>`), `resources/js/pages/records/index.js` | El doctor ve la columna "Acciones" vacía (y el lector de pantalla la anuncia). | `<th>` y `<td>` de acciones solo con `$canEdit`; ajustar `colspan` |
| R7 | importante | `package-lock.json` (sin cambios en el rango) | `npm audit`: 2 críticas y 7 altas en dependencias de desarrollo y build (vite, rollup, postcss, axios, concurrently…), previas a la 014. `/release` bloquea sin excepción. | Excepción con vencimiento en `docs/security.md` y entrada en el roadmap (decisión del usuario) |
| R8 | menor | `tasks.md` (T052, T064, T013, T061, T063, T065, T022/T047, T091) | Faltan las notas de los aceptados C2, C3, C6, C9, C11, C13, D7 y D8 (los cambios sí están aplicados). | Añadir las notas |
| R9 | menor | `plan.md:69` | Dice "5 archivos de vista" (son 4; errata aceptada C11). | Anotar en T063 |
| R10 | menor | `PatientsAccessControlTest.php:141-147`, `AppointmentsAccessControlTest.php` | Los datasets afirman solo el código; el cuerpo `{"error"}`/`{"message"}` no se fija por ruta, y las escrituras permitidas no comprueban que algo cambió. | `assertJsonStructure(['error'])` en las ramas 403 |
| R11 | menor | `RolePermissionsTest.php:48` | Comentario de duda del usuario y `actingAsNonAdminUser('Administrador')`. | Quitar el comentario y usar `actingAsAdmin()` (decisión del usuario; va con R2) |
| R12 | menor | `PatientsAccessControlTest.php:186-188` | `POST`/`DELETE /patients` se excluyen del test de rutas sin afirmar `only.admin`. | `toContain('only.admin')` para esas dos rutas |
| R13 | menor | `AppointmentsAccessControlTest.php:101` | `POST /appointments` como admin dispara el listener de WhatsApp (cola sync) sin `fakeTwilio()`; hoy no llama a Twilio solo porque el paciente no tiene teléfono. | `fakeTwilio()` en `beforeEach` |
| R14 | menor | `AppointmentsAccessControlTest.php:139-146`, `GetAppointmentsTest.php:77-83` | Casos repetidos respecto a la matriz. | Dejar uno como regresión explícita de CA10 |
| R15 | menor | `Patients/UnexpectedErrorTest.php:30-35`, `GlobalErrorFallbackTest.php:36-52` | Captura de logs duplicada. | Trait `CapturesLogs` en `tests/Support` |
| R16 | menor | `RecordsScreenTest.php`, `StaffNavigationTest.php:28-43` | Falta el caso admin en expedientes; la navegación no distingue menú lateral de panel. | Añadir caso admin y acotar las aserciones |
| R17 | menor | `bootstrap/app.php:56, 62, 68` | Closures de `report()`/`render()` sin tipo de retorno. | `: ?bool`, `: ?JsonResponse` |
| R18 | menor | 5 controladores de contenido (`catch (StorageException)`) | La rama es ya idéntica a la genérica. | Eliminarla |
| R19 | menor | `records/index.blade.php:9`, `sidebar.blade.php:50-53`, `dashboard.blade.php`, `routes/web.php:45` | Listas de roles copiadas fuera del mapa de permisos; `data-records-can-edit` no lo usa el JS. | Centralizar (p. ej. exponer el permiso a la vista) |
| R20 | menor | `dashboard.blade.php:206-225`, `records/index.js:82` | Icono "+" en el enlace a expedientes; "Acciones para hoy" para un solo enlace; panel del paciente casi vacío; tarjeta "Citas Hoy" del asistente; línea en blanco extra. | Ajustes de UI |
| R21 | menor | `records/index.blade.php:104`, `records/index.js` | Los botones "Eliminar" comparten nombre accesible y no tienen foco visible propio (previo). | `aria-label` por tipo y `focus-visible:ring` |
| R22 | menor | `PatientsAccessControlTest.php:85,103`, `PatientBusinessMessagesTest.php:13`, `routes/web.php:73-75` | Huecos de cobertura: paciente sobre sí mismo, asistente/doctor inactivos, email duplicado en `PUT`, páginas web `/agenda` y `/pacientes`. | Añadir casos |
| R23 | menor | `AuthorizationException.php:17`, `bootstrap/app.php:62-66` | El 403 revela el nombre interno del permiso. | Mensaje genérico; el permiso, al log de auditoría futuro |
| R24 | menor | `PatientRoleException.php:14`, `PatientStatusException.php:14` | Aún repiten el valor recibido (rol, estado; sin datos de salud). | Unificar con CA16 |
| R25 | menor | `plan.md`, fila TM12 | Mapeada a A04:2025; debería ser A01:2025. | Corregir el mapeo |
| R26 | menor | `EnsureActiveStaff.php:9`; `RetriveDataForScheduledAppointmenEventUseCase.php:24-29` | Core importa el `UserModel` de Users (patrón previo); el caso de uso de citas registra un id antes de `assertCan`. | Deuda de capas; mover el log tras la autorización |
| R27 | menor | `.ai/project.yaml` → `security.tools` | gitleaks, semgrep y trivy no se ejecutaron (no instalados; decisión del usuario). | Instalarlos o correrlos en CI (`.ai/ci/ai-dd.yml`) |
| R28 | menor | `AIDLC.md` (commit `f33c9e6`) | Ruta protegida creada por `/init`, fuera de una tarea visible. | Registrar la aprobación en el CHANGELOG |
| R29 | menor | `tasks.md` (nota de T012) | Fallo intermitente de un caso "guest": causa probable, timeouts de PostgreSQL en paralelo (ver T064). | Resolver con la deuda de `--parallel` |

## Preparación para release
- Rollback factible: sí. `git revert` del merge y redeploy; sin migraciones ni datos que revertir.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (justificado en el plan).
- Docs actualizadas: `architecture.md` (T090) y `observability.md` (T091) sí; `deployment.md` sin cambios necesarios. `architecture.md` → "Observabilidad" aún dice que los logs de Laravel no llegan a Loki (contradice el canal `stderr` confirmado; previo a la 014).
- Cobertura de riesgos: RS1.a, RS1.d, RS3.a, OB2.a y OB10.a mitigadas y probadas; RS1 figura como parcialmente mitigado (RS1.b, RS1.c → spec 013). Ningún riesgo declarado mitigado con correcciones pendientes.
- SCA: R7 bloquearía `/release` sin la excepción que se añade en T083.

## Tareas añadidas
- T077 ← R1
- T078 ← R2, R11
- T079 ← R3
- T080 ← R4
- T081 ← R5
- T082 ← R6 (vistas)
- T084 ← R6 (JS)
- T083 ← R7 (axios)
- T086 ← R7 (excepción EX1; añadida en `/analyze` ronda 6)
- T085 ← R4, R5, R6 (tests previos; añadida en `/analyze` ronda 6, P2)

Menores (R8–R29): aceptados sin tarea; quedan como referencia para seguimiento.
