# Dentissa 🦷

Dentissa es el sistema de gestión que construí para digitalizar la operación diaria de una clínica dental: agenda de citas, expedientes de pacientes, seguimiento clínico con recetas, gestión de tratamientos y el contenido público del sitio (galería, promociones, certificaciones, testimonios). Es una aplicación web full-stack en Laravel pensada para que el equipo administrativo y clínico deje de depender de agendas de papel u hojas de cálculo sueltas.

> Documentación técnica ampliada (diagramas de arquitectura, modelo de datos y flujo del caso de uso principal) en [`ARCHITECTURE.md`](./ARCHITECTURE.md).

## ¿Por qué construí esto?

Una clínica dental necesita coordinar tres cosas a la vez: quién atiende a quién y cuándo, qué se le hizo a cada paciente en cada visita, y mantener informado al paciente (recordatorios de cita por WhatsApp). Dentissa junta esas tres piezas en un solo sistema en vez de depender de herramientas desconectadas entre sí.

## Funcionalidades principales

- **Agenda de citas**: creación, edición, cancelación y reprogramación de citas, con validación automática de solapamiento de horarios por doctor/día.
- **Confirmación por WhatsApp**: al crear una cita, se dispara automáticamente un mensaje de confirmación al paciente vía Twilio (envío asíncrono, no bloquea la respuesta al usuario).
- **Gestión de pacientes**: datos de contacto, dirección y datos médicos (alergias, medicamentos, tipo de sangre) por paciente.
- **Seguimiento clínico / expedientes**: al completar una cita se registra diagnóstico, síntomas, procedimiento realizado y recomendaciones, junto con las recetas asociadas (medicamento, dosis, frecuencia, duración).
- **Gestión de tratamientos**: catálogo de tratamientos disponibles con su duración.
- **Panel administrativo**: gestión de usuarios/roles del staff y control de acceso (solo administradores pueden gestionar usuarios, tratamientos y contenido público).
- **Sitio público**: landing, galería, certificaciones, promociones y testimonios, editables desde el panel de contenido.
- **Recuperación de contraseña por email** vía Brevo.

## Cómo está construido

Es un monolito modular en Laravel: cada dominio de negocio (Citas, Pacientes, Usuarios, Seguimiento Clínico, Contenido, WhatsApp, Email, Autenticación) vive en su propio módulo bajo `app/Modules/`, con sus propias capas de Dominio, Aplicación e Infraestructura — el mismo espíritu de una arquitectura hexagonal, pero sin la sobrecarga de separarlo en microservicios. El detalle completo, con diagramas, está en [`ARCHITECTURE.md`](./ARCHITECTURE.md).

### Stack

| | |
|---|---|
| **Backend** | Laravel 12 · PHP 8.2 |
| **Autenticación** | Laravel Sanctum (SPA vía cookie) |
| **Base de datos** | PostgreSQL |
| **Cache / Colas** | Redis (Predis) |
| **Frontend** | Blade + JavaScript vanilla por página, Axios, Vite, Tailwind CSS 4 |
| **WhatsApp** | Twilio SDK |
| **Email transaccional** | Brevo |
| **Testing** | PHPUnit |

## Puesta en marcha local

### Requisitos previos

- PHP 8.2+ con Composer
- Node.js + npm
- PostgreSQL en ejecución
- Redis en ejecución
- Cuentas/credenciales de Twilio (WhatsApp) y Brevo (email) si vas a probar esos flujos

### Instalación

```bash
# 1. Clona el repositorio e instala dependencias
composer install
npm install

# 2. Copia el archivo de entorno y genera la clave de la app
cp .env.example .env
php artisan key:generate
```

### Configura tu `.env`

El `.env.example` trae valores de ejemplo para SQLite/desarrollo local; para levantar el proyecto tal como está pensado (PostgreSQL + Redis + integraciones) ajusta al menos:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dentissa
DB_USERNAME=postgres
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis

TWILIO_SID=
TWILIO_AUTH_TOKEN=
TWILIO_PHONE_NUMBER=
TWILIO_APPOINTMENT_TEMPLATE_SID=

BREVO_EMAIL_SENDER_API_KEY=
BREVO_RESET_PASSWORD_TEMPLATE_ID=
```

### Migraciones y arranque

```bash
# 3. Corre las migraciones (cada módulo trae las suyas propias, se cargan automáticamente)
php artisan migrate

# 4. Levanta todo en desarrollo (servidor, worker de colas y Vite) en un solo comando
composer run dev
```

Esto deja la app corriendo en `http://localhost:8000`. El worker de colas (`php artisan queue:work`) es imprescindible para que se envíen las confirmaciones de WhatsApp, ya que ese envío ocurre de forma asíncrona.

### Tests

```bash
composer run test
```

## Estructura del proyecto (resumen)

```
app/
├── Core/            → autorización, value objects y middlewares compartidos
├── Modules/          → un módulo por dominio de negocio (Domain/Application/Infrastructure)
│   ├── Appointments/
│   ├── AppointmentTracking/
│   ├── Auth/
│   ├── ContentManagement/
│   ├── Email/
│   ├── Patients/
│   ├── Users/
│   └── whatsApp/
└── Providers/
resources/js/pages/   → un módulo JS vanilla por pantalla (agenda, pacientes, expedientes, ...)
routes/                → web.php (vistas) y api.php (API /v1)
```

Para el detalle de cada capa, el modelo de datos completo y el flujo paso a paso de creación de una cita, revisa [`ARCHITECTURE.md`](./ARCHITECTURE.md).

## Estado del proyecto

En desarrollo activo. El módulo más reciente es `AppointmentTracking` (seguimiento clínico y recetas), que extiende el ciclo de vida de una cita más allá de agendarla.
