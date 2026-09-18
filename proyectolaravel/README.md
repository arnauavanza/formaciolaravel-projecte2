# Proyecto 2 — Gestor de incidencias

Aplicación Laravel para gestionar incidencias de soporte sobre la base del Proyecto 1.

El proyecto conserva el dominio de biblioteca del Proyecto 1 y añade:

- Tickets con estados y transiciones.
- Autenticación API con Sanctum.
- Roles y permisos con Spatie.
- Policies y middleware propios.
- Services, Repository e implementación Fake.
- Comentarios y adjuntos.
- Emails y generación de PDF.
- Jobs, colas y scheduler.
- Comando `tickets:auto-close`.

## Stack

- PHP 8.3+
- Laravel 13
- Laravel Sail / Docker
- MySQL
- Redis
- Meilisearch
- Sanctum
- Spatie Laravel Permission
- Dompdf
- PHPUnit
- Laravel Pint

## Preparar el entorno

Desde esta carpeta:

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

La aplicación estará disponible en:

```text
http://localhost:8001
```

Servicios locales:

- Mailpit: `http://localhost:8026`
- MySQL: puerto local `3307`
- Redis: puerto local `6380`
- Meilisearch: puerto local `7701`

## Usuarios de ejemplo

El seeder crea usuarios de prueba:

```text
test@example.com
customer@example.com
agent@example.com
```

Contraseña de desarrollo:

```text
password
```

Los roles disponibles son:

- `customer`
- `agent`
- `supervisor`
- `admin`

## Autenticación API

Registro:

```http
POST /api/auth/register
```

Login:

```http
POST /api/auth/login
```

Las rutas protegidas necesitan:

```http
Authorization: Bearer {token}
```

Endpoints de sesión:

```http
GET  /api/auth/me
POST /api/auth/logout
```

## Tickets

Endpoints principales:

```http
GET    /api/tickets
POST   /api/tickets
GET    /api/tickets/{ticket}
PATCH  /api/tickets/{ticket}
DELETE /api/tickets/{ticket}
```

Acciones de dominio:

```http
POST /api/tickets/{ticket}/assign
POST /api/tickets/{ticket}/close
GET  /api/tickets/{ticket}/pdf
```

Estados:

```text
open → in_progress → resolved → closed
```

## Comentarios y adjuntos

Listar comentarios:

```http
GET /api/tickets/{ticket}/comments
```

Crear comentario:

```http
POST /api/tickets/{ticket}/comments
```

Subir adjunto:

```http
POST /api/comments/{comment}/attachments
```

Descargar adjunto:

```http
GET /api/comments/{comment}/attachments/{attachment}/download
```

Los adjuntos se almacenan en el disco privado `local` y solo se sirven después de comprobar la autorización del ticket.

## Emails, PDF y colas

Los emails y la generación de PDF se procesan mediante Jobs.

Iniciar un worker:

```bash
./vendor/bin/sail artisan queue:work
```

Ver trabajos fallidos:

```bash
./vendor/bin/sail artisan queue:failed
```

Reintentar trabajos fallidos:

```bash
./vendor/bin/sail artisan queue:retry all
```

## Cierre automático

Simular el cierre sin modificar datos:

```bash
./vendor/bin/sail artisan tickets:auto-close --dry-run
```

Ejecutar el cierre:

```bash
./vendor/bin/sail artisan tickets:auto-close
```

El scheduler lo ejecuta diariamente a las 02:00:

```bash
./vendor/bin/sail artisan schedule:work
```

## Tests y calidad

Ejecutar toda la suite:

```bash
./vendor/bin/sail artisan test
```

Comprobar formato:

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

Comprobar rutas:

```bash
./vendor/bin/sail artisan route:list
```

Comprobar migraciones:

```bash
./vendor/bin/sail artisan migrate:status
```

## Arquitectura

```text
HTTP Request
    ↓
Middleware
    ↓
Policy
    ↓
Controller
    ↓
Service
    ↓
Repository
    ↓
Eloquent / Database
```

Las acciones importantes se reutilizan desde la API y desde comandos:

- `AssignTicketService`
- `CloseTicketService`
- `UpdateTicketService`
- `TicketHistoryPdfService`

## Criterios cubiertos

- Autenticación por token.
- Autorización por rol, permiso, propiedad y estado.
- Roles extensibles mediante seeders.
- Controladores separados de la lógica de negocio.
- Repository sustituible por Fake.
- Tests de API, Services, Jobs, PDFs, comentarios y adjuntos.
- Procesos pesados ejecutados mediante colas.
- Comando automático reutilizando el mismo Service que la API.
