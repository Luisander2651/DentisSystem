---
id: 008
slug: catalogo-de-tratamientos
status: approved
confidence: alta
created: 2026-09-22
---

# 008 · Catálogo de tratamientos

## Problema
La clínica necesita un catálogo de tratamientos con su duración para poder agendar citas y calcular
choques de horario.

## Historias de usuario
- Como administrador, quiero crear, editar y eliminar tratamientos con su duración en minutos.
- Como miembro del staff, quiero elegir un tratamiento al agendar una cita.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un administrador, cuando usa `POST/GET/PUT/DELETE /api/v1/treatments[/{id}]`, entonces gestiona tratamientos con nombre, descripción y duración (`time`, minutos).
- [x] CA2 · Dada una duración inválida para el value object `TreatmentTime`, entonces se rechaza.
- [x] CA3 · Dado un usuario autenticado, cuando hace `GET /api/v1/agenda/treatments`, entonces obtiene el catálogo para agendar sin requerir rol de administrador (U1 BR-5, corrección).
- [x] CA4 · (abuso) Como staff no administrador o paciente, intento gestionar tratamientos en `/api/v1/treatments` → 403.

## Fuera de alcance
- Precios de tratamientos.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno.
- Quién puede hacer qué: administrador → gestionar; cualquier autenticado → leer catálogo de agenda.
- Casos de abuso: CA4.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas
- Ninguna.

## Supuestos
- `time` nulo (tratamientos anteriores a la migración de 2026-06-12) no se trata como error.

## Notas para /plan
- Ninguna.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Appointments/Domain/Service/TreatmentsService.php`, `routes/api.php:113-119,157`, `resources/js/pages/tratamientos/index.js`, `routes/web.php:79`
- Tests: `tests/Modules/Appointments/Integration/TreatmentTest.php`, `tests/Modules/Appointments/Unit/ValueObjects/TreatmentTimeTest.php`

## Observaciones (solo si status=inferred)
- `TreatmentsService` (Domain) importa la clase concreta `EloquentTreatmentRepository`; `TreatmentsRepositoryInterface` existe pero no está enlazada en `AppServiceProvider` (incumple P3).
- No verificado: cómo se comporta el cálculo de solapamiento con tratamientos de `time` nulo.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: CA3, el catálogo de la agenda es solo para el administrador; 500 genérico; `agenda/treatments` devuelve `time` nulo como `0` | /release |
