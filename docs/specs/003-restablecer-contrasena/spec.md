---
id: 003
slug: restablecer-contrasena
status: approved
confidence: alta
created: 2026-09-22
---

# 003 · Restablecer contraseña por email

## Problema
Un usuario (staff o paciente) que olvidó su contraseña necesita recuperarla sin intervención de un
administrador.

## Historias de usuario
- Como usuario que olvidó su contraseña, quiero recibir un enlace por email para definir una nueva.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un email registrado, cuando hace `POST /api/v1/auth/send-reset-password-email`, entonces se genera un token aleatorio de 40 caracteres guardado en Redis con TTL de 900 s y se despacha el evento que envía el correo por Brevo; responde 200 (U3 BR-10).
- [x] CA2 · Dado un email no registrado, cuando solicita el reset, entonces responde el mismo 200 con el mismo mensaje, sin generar token ni despachar evento (U3 BR-9).
- [x] CA3 · Dado un email ausente o mal formado, cuando solicita el reset, entonces responde 422, nunca 500 (U3 BR-16).
- [x] CA4 · Dado un `token` válido en el cuerpo y una `new_password` de al menos 8 caracteres, cuando hace `POST /api/v1/auth/reset-password`, entonces se actualiza la contraseña y el token se borra de Redis (U3 BR-17, BR-18).
- [x] CA5 · Dado un token inexistente, expirado o ya usado, cuando intenta el reset, entonces responde 404 (U3 BR-10, BR-17).
- [x] CA6 · Dado un email existente como staff y como paciente (datos previos), cuando se restablece, entonces se resuelve primero el staff, de forma determinista (U3 BR-11, BR-19).
- [x] CA7 · Dada una `new_password` con espacios en los bordes, cuando se restablece, entonces se recortan los bordes y el login posterior con la misma entrada funciona (U3 BR-23).
- [x] CA8 · (abuso) Como atacante, uso el formulario para enumerar cuentas → la respuesta es idéntica exista o no el email.
- [x] CA9 · (abuso) Como atacante, reutilizo un token ya consumido → responde 404.
- [ ] CA10 · (abuso) Como atacante con un token de sesión robado, la víctima restablece su contraseña → **HOY NO SE CUMPLE**: los tokens Sanctum existentes no se revocan tras el reset (ver Observaciones).

## Fuera de alcance
- Cambio de contraseña autenticado desde el perfil.

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales, email.
- Quién puede hacer qué: cualquiera → solicitar y ejecutar un reset con un token válido.
- Casos de abuso: CA8, CA9, CA10.

## Requisitos no funcionales
- Vigencia del token: 15 minutos, un solo uso.

## Preguntas abiertas
- Ninguna.

## Supuestos
- Redis está disponible en todos los entornos (lo exige el flujo).

## Notas para /plan
- El módulo Email (listener Brevo) no tiene tests propios; era la Unidad 6 de AI-DLC.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Auth/Domain/Service/PasswordResetService.php`, `app/Modules/Auth/Aplication/UseCases/SendEmailForChangePasswordUseCase.php`, `app/Modules/Auth/Infrastructure/Http/Requests/ResetPasswordRequest.php`, `app/Modules/Email/Infrastructure/Listeners/SendPasswordResetListener.php`, `app/Modules/Email/Infrastructure/ExternalApi/BrevoApi.php`, `resources/js/pages/auth/reset-password.js`
- Tests: `tests/Modules/Auth/Integration/SendResetPasswordEmailTest.php`, `ResetPasswordTest.php`, `PasswordResetFlowPropertiesTest.php`, `PasswordResetTokenPropertiesTest.php`

## Observaciones (solo si status=inferred)
- `PasswordResetService::resetPassword` no revoca los tokens Sanctum existentes (CA10).
- `BrevoApi` lee la API key con `env()` fuera de `config/` (incumple P8; se rompe con `config:cache`); `BREVO_*` no están en `.env.example`.
- `SendPasswordResetListener` importa clases de Appointments y whatsApp que no usa; `SendEmailForChangePasswordEvent` importa `AppointmentEntity`.
- Posible doble registro del listener (autodescubrimiento + `$listen`), lo que enviaría dos correos; sin verificar.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
