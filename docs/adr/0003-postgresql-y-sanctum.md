---
id: 0003
status: accepted
date: 2026-09-22
---

# ADR 0003 · PostgreSQL 16 como base de datos y tokens Sanctum en cookie

> Decisión preexistente, registrada a posteriori por `/init`.

## Contexto
La aplicación necesita una base relacional con UUIDs y JSON, y autenticación para dos tipos de
actor (staff y pacientes) desde vistas Blade que llaman a la API con `fetch`.

## Decisión
- PostgreSQL 16 en todos los entornos (desarrollo con Docker, CI y tests, que corren contra una base real `dentissa_testing`).
- Tokens personales de Laravel Sanctum emitidos en el login y entregados solo en la cookie `auth_token` (httpOnly, excluida del cifrado de cookies); `InjectSanctumTokenFromCookie` la convierte en `Authorization: Bearer`. Un token activo por actor.

## Alternativas consideradas
- SQLite — `.env.example` lo trae por defecto del skeleton, pero no refleja producción.
- Sanctum SPA con sesión — no se adoptó; el flujo actual es token en cookie.

## Consecuencias
- Positivas: el token no es accesible desde JS; paridad de base entre tests y producción.
- Negativas: la protección CSRF de Sanctum stateful no aplica al Bearer inyectado y depende de `SameSite`; el token viaja sin cifrar dentro de la cookie. Ver [security.md](../security.md).
