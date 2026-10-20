---
id: 0006
status: accepted
date: 2026-10-19
---

# ADR 0006 · Módulo `Reports` de solo lectura sobre citas, pacientes y staff

## Contexto
La spec 019 calcula indicadores mensuales que cruzan citas (Appointments), tratamientos (también
en Appointments), pacientes (Patients) y staff (Users). El tablero de inicio, que viene después,
reutilizará los mismos cálculos. La arquitectura nombraba un módulo `Estadisticas` que nunca tuvo
código ni existe en el disco. P3 exige un ADR para cada dependencia nueva entre módulos.

## Decisión
- Crear `app/Modules/Reports/{Domain,Application,Infrastructure}` (grafía nueva: `Application` y
  `Http`, como pide `AGENTS.md`).
- `Domain/` define el reporte (valores y reglas: tasas, diferencias, favorable o desfavorable) y
  `MonthlyReportRepositoryInterface`, que devuelve solo agregados: conteos, nombres de tratamientos
  y nombres del staff. Nunca devuelve entidades ni datos de pacientes.
- `Infrastructure/Persistence/EloquentMonthlyReportRepository` lee con consultas de agregación
  sobre los modelos `AppointmentModel`, `TreatmentModel`, `PatientModel` y `UserModel`, igual que
  `AppointmentModel` ya relaciona esos modelos. No escribe, no tiene migraciones ni modelos
  propios.
- Dependencias nuevas: `Reports → Appointments`, `Reports → Patients` y `Reports → Users`, solo
  en `Infrastructure/` y solo de lectura. Ningún módulo depende de `Reports`, salvo el tablero de
  inicio cuando exista.
- Se retira `Estadisticas` de `docs/architecture.md` y de `docs/specs/README.md`.

## Alternativas consideradas
- **Dentro de Appointments:** sin módulo nuevo, pero mezcla en la agenda cálculos de pacientes y
  staff, y el tablero dependería de Appointments para todo.
- **Usar los servicios de dominio de cada módulo** (`AppointmentsService`, `UserService`…): respeta
  las fronteras, pero obliga a cargar entidades completas para contarlas en memoria y a añadir
  métodos de agregación a cada módulo; para 2 000 citas por mes no cumpliría la medida de 3 s sin
  añadir consultas especiales en cada uno.
- **Vistas de base de datos:** rápidas, pero añaden migraciones y esquema que mantener por un
  cálculo que cambia con la spec.

## Consecuencias
- Positivas: un solo lugar para los indicadores, que el tablero reutiliza; las consultas agregan
  en PostgreSQL y no cargan filas de pacientes.
- Negativas: `Reports` conoce el esquema de tres módulos: un cambio de columna en `appointments`,
  `patients` o `users` puede romperlo. Lo cubren sus tests de integración, que crean datos reales
  en esas tablas.
