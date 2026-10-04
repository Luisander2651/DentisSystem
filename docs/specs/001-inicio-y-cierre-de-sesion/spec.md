---
id: 001
slug: inicio-y-cierre-de-sesion
status: approved
confidence: alta
created: 2026-09-22
---

# 001 · Inicio y cierre de sesión

## Problema
El staff de la clínica y los pacientes necesitan autenticarse con email y contraseña para usar el
panel y la API, y poder cerrar su sesión.

## Historias de usuario
- Como miembro del staff, quiero iniciar sesión con mi email y contraseña para acceder al panel.
- Como paciente, quiero iniciar sesión con mi cuenta para acceder a mi portal.
- Como usuario autenticado, quiero cerrar sesión para que nadie más use mi cuenta en este equipo.

## Criterios de aceptación
<!-- [x] = cubierto por un test existente; [ ] = observado en el código, sin test -->
- [x] CA1 · Dado un email de staff con su contraseña correcta y cuenta activa, cuando hace `POST /api/v1/auth/login`, entonces responde 200 y emite la cookie `auth_token` (httpOnly, TTL `sanctum.expiration`); el token nunca va en el cuerpo (U3 BR-4).
- [x] CA2 · Dado un email que existe como staff y como paciente, cuando la contraseña no coincide con la del staff, entonces se intenta contra el paciente; cada actor autentica con su propia contraseña (U3 BR-1).
- [x] CA3 · Dada una cuenta inactiva, cuando la contraseña es incorrecta responde 401 de credenciales inválidas, y cuando es correcta responde 401 de cuenta inactiva (U3 BR-2).
- [x] CA4 · Dado un login exitoso, cuando se emite el nuevo token, entonces se revocan todos los tokens previos del actor (un solo dispositivo activo) (U3 BR-3).
- [x] CA5 · Dado un `email` ausente o mal formado o una `password` ausente, cuando se hace login, entonces responde 422 (U3 BR-20).
- [x] CA6 · Dada una contraseña con espacios en los bordes, cuando se hace login, entonces se recortan solo los bordes antes de comparar (U3 BR-23).
- [x] CA7 · Dado un usuario autenticado, cuando hace `POST /api/v1/auth/logout`, entonces el token se revoca y la cookie `auth_token` se expira en todas las ramas, incluidas las de error (U3 BR-8).
- [x] CA8 · (abuso) Como atacante, intento adivinar contraseñas con más de 10 peticiones por minuto desde la misma IP → responde 429 (U3 BR-12).
- [x] CA9 · (abuso) Como atacante, provoco un error interno en login o logout → la respuesta 500 es `{"error": "Internal server error"}` sin detalles (U3 BR-15).

## Fuera de alcance
- Registro de pacientes (spec 002) y restablecimiento de contraseña (spec 003).
- MFA, bloqueo progresivo por cuenta, "recordarme".

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (contraseñas con bcrypt, tokens Sanctum).
- Quién puede hacer qué: cualquiera → login; actor autenticado → logout.
- Casos de abuso: CA8, CA9.

## Requisitos no funcionales
- Rate limiting `throttle:api`: 10/min por IP sin autenticar, 100/min por usuario autenticado.

## Preguntas abiertas
- Ninguna.

## Supuestos
- El rate limiting por IP es suficiente para un prototipo.

## Notas para /plan
- Ver riesgos de CSRF y cifrado de cookie en [security.md](../../security.md) antes de tocar este flujo.

## Evidencia (solo si status=inferred)
- Archivos: `app/Modules/Auth/Domain/Service/LoginService.php`, `app/Modules/Auth/Infrastructure/Http/Controllers/LoginController.php`, `LogoutController.php`, `app/Modules/Auth/Infrastructure/Http/Requests/LoginRequest.php`, `app/Core/Middlewares/InjectSanctumTokenFromCookie.php`, `bootstrap/app.php:23-27`, `resources/views/pages/auth/login.blade.php`
- Tests: `tests/Modules/Auth/Integration/LoginTest.php`, `LoginPropertiesTest.php`, `LogoutTest.php`, `LogoutPropertiesTest.php`, `RateLimitAndErrorLeakTest.php`, `tests/Modules/Auth/Unit/LogoutServiceTest.php`
- Reglas previas: `aidlc-docs/construction/unit-3-auth/functional-design/business-rules.md` (BR-1…BR-4, BR-8, BR-12, BR-15, BR-20…BR-23)

## Observaciones (solo si status=inferred)
- La cookie `auth_token` está excluida del cifrado de cookies; el token viaja en claro dentro de ella.
- El Bearer inyectado desde la cookie no pasa por la protección CSRF de Sanctum stateful; depende de `SameSite=lax`.
- `LoginService` (capa Domain) usa modelos Eloquent y la facade `Hash` (incumple P3).
- El guard `sanctum` no fija provider en `config/auth.php`, así que acepta tokens de pacientes en rutas pensadas para staff (ver spec 005).
- No se registran intentos de login fallidos (auditoría).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Creación inferida del código | /init |
| 2026-09-22 | Aprobada por el usuario como descripción del comportamiento actual | Confirmación explícita |
| 2026-10-04 | Comportamiento modificado por 014 en v0.1.0: el logout sin sesión responde 401 en JSON | /release |
