---
id: 006
slug: expediente-clinico
status: approved
confidence: alta
created: 2026-09-22
---

# 006 · Expediente clínico consolidado

## Problema
El personal clínico necesita ver en una sola pantalla todo lo que se sabe de un paciente: datos
personales, contacto, dirección, datos médicos e historial de citas.

## Historias de usuario
- Como administrador o asistente, quiero abrir el expediente de un paciente para preparar su atención.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un usuario autenticado, cuando hace `GET /api/v1/patients/{patientId}/record`, entonces recibe paciente, contacto, dirección y datos médicos consolidados (`PatientRecordResource`); un paciente inexistente responde 404.
- [x] CA2 · Dado un usuario autenticado, cuando hace `GET /api/v1/appointments/patient/{patientId}`, entonces recibe el historial de citas del paciente.
- [ ] CA3 · Dado un actor con rol administrador o asistente, cuando abre `/expedientes-clinicos` o `/expedientes-clinicos/{patientId}`, entonces ve la pantalla; con cualquier otro rol la ruta web lo rechaza.
- [ ] CA4 · Dado un expediente abierto, cuando se editan contacto, dirección o datos médicos desde la pantalla, entonces se usan los endpoints de la spec 005.
- [x] CA5 · (abuso) Como paciente o doctor autenticado, intento `GET /api/v1/patients/{otroId}/record` → resuelto por 014 (v0.1.0): resuelto para pacientes; el doctor sí lee expedientes (solo lectura) (antes: la API solo exige `auth:sanctum`; la restricción por rol está únicamente en la ruta web).

## Fuera de alcance
- Descarga o impresión del expediente.
- Registro de auditoría de accesos al expediente.

## Seguridad y privacidad
- Datos sensibles involucrados: **datos de salud** y datos personales.
- Quién puede hacer qué: pantalla → administrador y asistente; API → cualquier actor autenticado (actual, no deseado).
- Casos de abuso: CA5.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas
- Ninguna.

## Supuestos
- El rol Doctor debería ver expedientes; hoy la ruta web solo admite administrador y asistente y el sidebar no tiene entradas para Doctor. Se deja para `/clarify`.

## Notas para /plan
- Mover la comprobación de rol de los closures de `routes/web.php` a middleware y aplicarla también a la API.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Patients/Infrastructure/Http/Controllers/PatientRecord/GetPatientRecordByPatientIdController.php`, `app/Modules/Patients/Infrastructure/Http/Resources/PatientRecordResource.php`, `routes/web.php:45-67`, `resources/js/pages/records/index.js`, `resources/views/components/records/*`
- Tests: `tests/Modules/Patients/Integration/PatientRecordTest.php`, `tests/Modules/Appointments/Integration/GetAppointmentsTest.php`

## Observaciones (solo si status=inferred)
- La comprobación de rol de la pantalla está duplicada inline en closures de `routes/web.php`.
- No se registran los accesos a historiales clínicos (auditoría, ASVS nivel 2).
- El controlador del expediente devuelve `$e->getMessage()` en el 500.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: CA1 y CA2 para staff; CA3 incluye al doctor, en solo lectura; CA5 resuelto | /release |
