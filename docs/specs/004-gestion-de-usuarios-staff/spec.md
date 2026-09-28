---
id: 004
slug: gestion-de-usuarios-staff
status: approved
confidence: alta
created: 2026-09-22
---

# 004 · Gestión de usuarios del staff

## Problema
El administrador de la clínica necesita dar de alta, modificar, desactivar y eliminar las cuentas
del personal (Administrador, Asistente, Doctor).

## Historias de usuario
- Como administrador, quiero crear cuentas para el personal con su rol para que accedan al sistema.
- Como administrador, quiero desactivar o cambiar el rol de un miembro del staff para controlar su acceso.
- Como administrador, quiero listar el personal filtrado por rol y estado.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un administrador activo, cuando hace `POST /api/v1/users` con nombre, apellido, email, contraseña (mín. 8) y rol, entonces se crea el usuario siempre `active` y responde 201 (U4 BR-3, BR-11).
- [x] CA2 · Dado un email ya usado por otro miembro del staff, cuando se crea, entonces responde 409 (U4 BR-2).
- [x] CA3 · Dado un administrador, cuando hace `PUT /api/v1/users/{id}`, entonces actualiza solo los campos enviados y conserva el resto; una petición sin campos útiles responde 422 (U4 BR-5, BR-22).
- [x] CA4 · Dado un `email` en la actualización, cuando se envía, entonces responde 422 porque el email es inmutable (U4 BR-4, BR-23).
- [x] CA5 · Dado un administrador, cuando hace `DELETE /api/v1/users/{id}`, entonces se borra la fila (borrado duro) (U4 BR-6).
- [x] CA6 · Dado un administrador, cuando hace `GET /api/v1/users` con `role` y/o `status` opcionales, entonces se aplican como AND y sin coincidencias devuelve 200 con colección vacía (U4 BR-7).
- [x] CA7 · Dada cualquier respuesta del módulo, cuando se serializa un usuario, entonces solo incluye `id`, `first_name`, `last_name`, `email`, `role_id`, `status`, nunca el hash (U4 BR-8, BR-15).
- [x] CA8 · Dado un id inexistente, responde 404; dado un UUID malformado, responde 400 (U4 BR-18, BR-19).
- [x] CA9 · Dado un nombre compuesto, cuando se guarda, entonces se capitaliza por palabra (U4 BR-29).
- [x] CA10 · (abuso) Como asistente, doctor o paciente autenticado, intento cualquier ruta de `/api/v1/users` → 403; sin autenticar → 401 (U4 BR-1).
- [x] CA11 · (abuso) Como administrador, intento desactivarme, cambiarme el rol o borrarme a mí mismo → 403 (U4 BR-21).
- [x] CA12 · (abuso) Como atacante, provoco un error interno → 500 genérico sin detalles (U4 BR-13).

## Fuera de alcance
- Rediseño del mapeo rol ↔ id (1/2/3 fijo; U4 BR-9, diferido).
- Regla del "último administrador" (descartada por decisión del usuario).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales del staff y credenciales.
- Quién puede hacer qué: solo administrador activo → todas las acciones; resto → denegado.
- Casos de abuso: CA10, CA11, CA12.

## Requisitos no funcionales
- El listado carga el rol con eager loading (U4 BR-27).

## Preguntas abiertas
- Ninguna.

## Supuestos
- Ninguno.

## Notas para /plan
- Ninguna.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Users/**`, `app/Core/Middlewares/OnlyAdmin.php`, `app/Core/Authorization/CurrentActorAuthorizationService.php`, `database/seeders/RoleSeeder.php`, `resources/js/pages/usuarios/*.js`, `resources/views/components/ui/create-user-modal.blade.php`, `edit-user-modal.blade.php`
- Tests: `tests/Modules/Users/Integration/*`, `tests/Modules/Users/Unit/*`

## Observaciones (solo si status=inferred)
- `UserRoleId` traduce roles con ids 1/2/3 escritos a mano; un `role_id` fuera de ese rango rompe la lectura de la colección completa (U4 BR-9).
- El email del staff no se comprueba contra la tabla de pacientes.
- `UserModel` tiene `id`, `password`, `status` y `role_id` en `$fillable`; hoy no es explotable porque la persistencia pasa por entidades.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
