---
id: 009
slug: seguimiento-clinico-y-recetas
status: approved
confidence: media
created: 2026-09-22
---

# 009 · Seguimiento clínico y recetas

## Problema
Al terminar una cita, la clínica necesita registrar qué se le hizo al paciente (motivo, síntomas,
diagnóstico, procedimiento, observaciones, recomendaciones) y las recetas emitidas.

## Historias de usuario
- Como doctor o administrador, quiero completar una cita registrando su seguimiento clínico y las recetas.
- Como doctor o administrador, quiero consultar el seguimiento de una cita completada.
- Como doctor o administrador, quiero corregir un seguimiento o una receta ya registrados desde el panel.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un administrador y una cita no cancelada sin seguimiento, cuando hace `POST /api/v1/appointments/{id}/complete` con motivo, síntomas, diagnóstico y procedimiento (obligatorios), observaciones y recomendaciones (opcionales) y cero o más recetas en línea, entonces se crean el seguimiento y las recetas y la cita pasa a `completada` de forma atómica.
- [x] CA2 · Dada una cita inexistente, cuando se completa, entonces responde con error de no encontrada.
- [x] CA3 · Dada una cita cancelada, cuando se intenta completar, entonces se rechaza como transición de estado inválida.
- [x] CA4 · Dada una cita que ya tiene seguimiento, cuando se intenta completar otra vez, entonces se rechaza (seguimiento 1:1 con la cita).
- [ ] CA5 · Dado un administrador, cuando hace `GET /api/v1/appointments/{id}/tracking`, entonces obtiene el seguimiento con sus recetas (se muestra en el detalle de la cita).
- [ ] CA6 · Dado un administrador, cuando usa `PUT /api/v1/appointment-tracking/{id}` y el CRUD de `/appointment-tracking/{id}/prescriptions`, entonces edita el seguimiento y las recetas.
- [x] CA7 · (abuso) Como staff no administrador o paciente, intento completar una cita o leer su seguimiento → 403.
- [x] CA8 · (abuso) Como atacante, provoco un error interno → resuelto por 014 (v0.1.0): 500 genérico (antes: el 500 incluye `$e->getMessage()`).
- [ ] CA9 · Dado un doctor activo, cuando completa una cita, consulta su seguimiento o edita el seguimiento y las recetas, entonces se le permite → **HOY NO SE CUMPLE**: solo el administrador puede (decisión del 2026-09-22).
- [ ] CA10 · (abuso) Como asistente o paciente, intento completar una cita, leer o editar su seguimiento → se rechaza (403). Hoy se cumple para ambos porque solo el administrador tiene permiso; debe seguir cumpliéndose al abrirlo al doctor.
- [ ] CA11 · Dado un doctor o administrador, cuando abre el detalle de una cita completada en el panel, entonces puede corregir el seguimiento y añadir, cambiar o borrar recetas → **HOY NO SE CUMPLE**: la API existe pero ninguna pantalla la usa.
- [ ] CA12 · (abuso) Como miembro del staff, intento marcar una cita como `completada` desde la edición de la cita (spec 007), sin registrar seguimiento → se rechaza; solo se completa con este flujo → **HOY NO SE CUMPLE**: `PUT /api/v1/appointments/{id}` con `status=completada` lo permite.
- [ ] CA13 · Dada una receta, cuando se registra o edita, entonces la duración va de 1 a 365 días y la frecuencia de 1 a 24 veces al día; fuera de rango se rechaza.

## Fuera de alcance
- Impresión o envío de recetas al paciente.

## Seguridad y privacidad
- Datos sensibles involucrados: **datos de salud** (diagnóstico, síntomas, procedimiento, medicación).
- Quién puede hacer qué: hoy, solo administrador (`only.admin` + `assertCan('appointment-tracking.*')`). Deseado: administrador y doctor; asistente y paciente denegados (CA9, CA10).
- Casos de abuso: CA7, CA8, CA10, CA12.

## Requisitos no funcionales
- Escritura atómica (transacción) al completar la cita.

## Preguntas abiertas
- Ninguna.

## Supuestos
- Ninguno.

## Notas para /plan
- Este módulo no tuvo unidad AI-DLC propia; sus tests se migraron en la Unidad 0.
- Spec nueva pendiente para CA9, CA11 y CA12. Implica:
  - dar a los doctores los permisos `appointment-tracking.*`, que hoy son solo del administrador, y sacar esas rutas de `only.admin`;
  - añadir una pantalla de edición en el panel;
  - quitar `completada` de los estados admitidos por `PUT /appointments/{id}` (cambia U1 BR-3 y la spec 007, CA4).
- Relación con la spec 013: completar la cita expira su QR, y eso solo ocurrirá por este flujo si se aplica CA12.
- Pregunta para esa spec: ¿un doctor puede completar cualquier cita o solo las asignadas a él?

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/AppointmentTracking/**`, `routes/api.php:145-155`, `resources/js/pages/agenda/complete-appointment.js`, `resources/js/pages/agenda/view-appointment.js`, `resources/views/components/calendar/complete-appointment-modal.blade.php`
- Tests: `tests/Modules/AppointmentTracking/Integration/CompleteAppointmentTest.php`

## Observaciones (solo si status=inferred)
- Sin tests para consultar ni editar el seguimiento ni para el CRUD de recetas.
- Datos clínicos sin cifrar en reposo.
- Límites observados en los value objects: motivo, diagnóstico y procedimiento no vacíos; síntomas como lista de textos no vacíos; `DurationDays` 1–365 y `DailyFrequency` 1–24.
- 6 controladores devuelven `$e->getMessage()` en el 500.
- Usa la grafía `Application` (el resto de módulos usa `Aplication`).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aclaraciones: seguimiento por doctor y administrador (CA9), edición desde el panel (CA11), completar solo con seguimiento (CA12); pendientes de spec nueva | /clarify |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: CA8 resuelto | /release |

## Aclaraciones
### Sesión 2026-09-22
- P: ¿Quién debe poder registrar y consultar el seguimiento clínico y las recetas? → R: Doctor y administrador.
- P: ¿Qué se hace con la API de edición de seguimiento y recetas, que no usa ninguna pantalla? → R: La usará el panel web.
- P: ¿Una cita puede marcarse como completada sin registrar su seguimiento clínico? → R: No, siempre con seguimiento.
