---
id: 002
slug: registro-de-pacientes
status: approved
confidence: alta
created: 2026-09-22
---

# 002 · Autorregistro de pacientes

## Problema
Los pacientes necesitan crear su propia cuenta desde el sitio público sin intervención del staff.

## Historias de usuario
- Como paciente nuevo, quiero registrarme con mi nombre, email y contraseña para tener una cuenta en la clínica.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dados `first_name`, `last_name`, `email`, `password` y `confirm_password` válidos, cuando hace `POST /api/v1/auth/register`, entonces se crea un paciente con `status=active` y `role=patient` (no modificables desde el request), responde 201 y queda autenticado con la cookie `auth_token` (U3 BR-5, BR-6).
- [x] CA2 · Dado un email ya usado por un paciente **o por un miembro del staff**, cuando se registra, entonces responde 409 con el mismo mensaje en ambos casos (U3 BR-13).
- [x] CA3 · Dada una contraseña de menos de 8 caracteres o una confirmación distinta, cuando se registra, entonces responde 422 (U3 BR-14, BR-20).
- [x] CA4 · Dado un nombre inválido para el value object, cuando se registra, entonces responde 400.
- [x] CA5 · Dado un fallo en el registro, cuando responde el error, entonces el usuario no queda autenticado (U3 BR-6).
- [x] CA6 · (abuso) Como atacante, intento registrarme enviando `status` o `role` en el cuerpo → se ignoran y la cuenta es siempre paciente activo.
- [x] CA7 · (abuso) Como atacante, uso el registro para averiguar si un email pertenece al staff → la respuesta 409 no distingue el tipo de cuenta.

## Fuera de alcance
- Alta de staff (spec 004).
- Verificación de email, captcha.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre, email) y credenciales.
- Quién puede hacer qué: cualquiera → registrarse como paciente.
- Casos de abuso: CA6, CA7.

## Requisitos no funcionales
- Rate limiting 10/min por IP.

## Preguntas abiertas
- Ninguna.

## Supuestos
- Mientras no haya portal de paciente funcional, la cuenta de paciente solo sirve para iniciar sesión.

## Notas para /plan
- Ninguna.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Auth/Domain/Service/RegisterService.php`, `app/Modules/Auth/Infrastructure/Http/Controllers/RegisterController.php`, `app/Modules/Auth/Infrastructure/Http/Requests/RegisterRequest.php`, `resources/views/pages/auth/register.blade.php`
- Tests: `tests/Modules/Auth/Integration/RegisterTest.php`, `WhitespacePasswordTest.php`, `tests/Modules/Auth/Unit/RegisterServiceTest.php`

## Observaciones (solo si status=inferred)
- **Riesgo alto:** un paciente autorregistrado obtiene un token que el guard `sanctum` acepta en las rutas de pacientes y citas protegidas solo con `auth:sanctum` (ver specs 005, 006 y 007). Registrarse da acceso inmediato a datos de otros pacientes.
- La unicidad en el alta de staff no se comprueba contra pacientes (U4 BR-2, límite conocido).
- No hay verificación de email ni captcha.
- El layout `patient.blade.php` existe, pero no hay pantallas funcionales para el paciente; el sidebar le muestra "agenda" y "expedientes", que le devuelven 403.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
