---
id: 0002
status: accepted
date: 2026-09-22
---

# ADR 0002 · Monolito modular en Laravel con capas hexagonales por módulo

> Decisión preexistente, registrada a posteriori por `/init` a partir del código y de
> `JUSTIFICACION_DE_LA_ARQUITECTURA_DENTISSA.txt`.

## Contexto
Una clínica dental necesita coordinar agenda, expedientes, comunicación con pacientes y contenido
público. Un solo desarrollador mantiene el sistema y el despliegue previsto es un único VPS.

## Decisión
Aplicación Laravel única (PHP 8.4, Laravel 12) organizada en `app/Modules/<Módulo>`, cada uno con
capas `Domain` / `Aplication|Application` / `Infrastructure`, repositorios como interfaces
enlazadas en `AppServiceProvider`, controladores invocables de una acción y comunicación entre
módulos por eventos de dominio procesados en cola cuando hay efectos externos.

## Alternativas consideradas
- Laravel MVC clásico (`app/Http/Controllers`, `app/Models`) — mezcla dominios a medida que crece.
- Microservicios — coste operativo desproporcionado para un VPS y un desarrollador.

## Consecuencias
- Positivas: dominios aislados y testeables (value objects con property-based testing); se puede extraer un módulo si hiciera falta.
- Negativas: más archivos por feature; los generadores `php artisan make:` no respetan la estructura; las rutas y bindings siguen centralizados.
