---
id: 005
slug: gestion-de-pacientes
status: approved
confidence: alta
created: 2026-09-22
---

# 005 · Gestión de pacientes y sus datos de contacto, dirección y datos médicos

## Problema
La clínica necesita registrar a sus pacientes y mantener sus datos de contacto, dirección y datos
médicos (tipo de sangre, alergias, medicamentos, última visita al dentista).

## Historias de usuario
- Como administrador, quiero dar de alta y de baja pacientes.
- Como miembro del staff, quiero consultar y actualizar los datos de un paciente y sus subrecursos.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un administrador, cuando hace `POST /api/v1/patients`, entonces crea el paciente con contraseña bcrypt; un email ya usado por otro paciente responde 409 (U2 BR-1, BR-4).
- [x] CA2 · Dado un administrador, cuando hace `DELETE /api/v1/patients/{id}`, entonces elimina al paciente; un id inexistente responde 404 (U2 BR-6).
- [x] CA3 · Dado un usuario autenticado, cuando hace `GET /api/v1/patients` (filtrable por estado) o `GET /api/v1/patients/{id}`, entonces obtiene los pacientes.
- [x] CA4 · Dado un usuario autenticado, cuando hace `PUT /api/v1/patients/{id}` sin ningún campo, entonces responde 409 (U2 BR-3).
- [x] CA5 · Dado un paciente, cuando se hace `POST` de `address`, `contact-info` o `medical-data` y ya existe uno, entonces responde 409 (relación 1:1, U2 BR-2).
- [x] CA6 · Dado un `patientId` inexistente, cuando se crea un subrecurso, entonces responde 404 (U2 BR-7).
- [x] CA7 · Dado un UUID malformado en la URL, entonces responde 400, no 500 (U2 BR-8).
- [x] CA8 · Dado un subrecurso inexistente, cuando se hace `PUT`, entonces no se crea (sin upsert: create y update son operaciones separadas) (U2 BR-9).
- [x] CA9 · Dado un nombre de 3 o más palabras, cuando se guarda, entonces las dos últimas son el apellido y cada palabra se capitaliza (U2 BR-10).
- [x] CA10 · (abuso) Como miembro del staff que no es administrador, intento crear o borrar un paciente → 403.
- [ ] CA11 · (abuso) Como paciente autenticado, intento leer o modificar los datos (incluidos los médicos) de otro paciente → **HOY NO SE CUMPLE**: el acceso está permitido (ver Observaciones).
- [ ] CA12 · (abuso) Como atacante, provoco un error interno → **HOY NO SE CUMPLE**: el 500 incluye `$e->getMessage()`.

## Fuera de alcance
- Expediente consolidado (spec 006).
- Portal para que el paciente edite sus propios datos.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre, email, teléfono, contacto de emergencia, dirección) y **datos de salud** (tipo de sangre, alergias, medicamentos).
- Quién puede hacer qué: administrador → crear y borrar; cualquier actor autenticado → leer y modificar (comportamiento actual, no deseado).
- Casos de abuso: CA10, CA11, CA12.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas
- Ninguna.

## Supuestos
- El comportamiento de autorización actual se aceptó en AI-DLC U2 (BR-5) solo como fuera de alcance de ese ciclo; no es el comportamiento deseado.

## Notas para /plan
- Objetivo 1 del roadmap: restringir estas rutas a staff (p. ej. middleware `only.staff`), comprobar propiedad del recurso y fijar el provider del guard.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Patients/**`, `routes/api.php:108-111,163-178`, `resources/js/pages/patients/*.js`, `resources/js/pages/records/index.js`
- Tests: `tests/Modules/Patients/Integration/PatientCrudTest.php`, `AddressTest.php`, `ContactInfoTest.php`, `MedicalDataTest.php`, `tests/Modules/Patients/Unit/*`

## Observaciones (solo si status=inferred)
- **Control de acceso roto (A01):** listar, obtener, actualizar paciente y todo el CRUD de contacto, dirección y datos médicos solo exigen `auth:sanctum`, y el guard `sanctum` no fija provider en `config/auth.php`, por lo que acepta tokens de pacientes. Un paciente autorregistrado (spec 002) puede leer y modificar datos de salud de cualquier otro paciente. Cualquier staff puede cambiar la contraseña de un paciente con `PUT /patients/{id}`.
- Datos de salud y contacto sin cifrar en reposo (sin casts `encrypted`).
- `PatientModel` no tiene `$hidden`: si se serializa directamente, expondría el hash de la contraseña.
- Sin FormRequest: la validación es solo en value objects (incumple P6 para cambios futuros).
- 15 controladores devuelven `$e->getMessage()` en el 500.
- Algunos UseCases de subrecursos usan el repositorio directamente, saltando el Domain Service.
- `PUT` sin campos responde 409; la convención de Users (U4 BR-22) es 422.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
