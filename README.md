# Proyecto 1: API de biblioteca con Laravel

Aplicación de formación para aprender Laravel construyendo una biblioteca. El
proyecto expone una API REST y una interfaz Blade que consume esa API desde el
navegador.

El código de Laravel está dentro de `proyectolaravel/`. Los comandos de Docker,
Artisan, Composer y NPM deben ejecutarse desde esa carpeta.

## Funcionalidades

- CRUD de autores.
- CRUD de libros.
- CRUD de miembros.
- CRUD de préstamos.
- Devolución de préstamos.
- Catálogo de géneros para los formularios.
- Filtros, ordenación y paginación de libros y préstamos.
- Relaciones Eloquent entre autores, libros, géneros, miembros y préstamos.
- Validación mediante `FormRequest`.
- Respuestas de la API mediante `JsonResource`.
- Datos de ejemplo mediante factories y seeders.
- Tests de los endpoints y de las reglas principales.
- Interfaz gráfica Blade en `http://localhost:8001`.

Una persona puede tener varios préstamos activos al mismo tiempo. La
restricción del sistema es que un mismo libro no puede estar prestado
activamente a dos personas a la vez.

## Tecnologías

- PHP 8.3 o superior.
- Laravel 13.
- MySQL 8.4 mediante Docker.
- Redis, Meilisearch, Mailpit y Selenium mediante Docker Compose.
- Blade para la interfaz.
- Vite, Tailwind CSS y JavaScript para los recursos frontend.
- PHPUnit para los tests.
- Laravel Pint para el formato del código PHP.

## Requisitos del equipo

Instala únicamente:

- Git.
- Docker Desktop, Docker Engine o Docker dentro de WSL2.

No es necesario instalar PHP, MySQL ni Node.js en el sistema anfitrión. Se
utilizan los servicios definidos en `proyectolaravel/compose.yaml`.

Comprueba que Docker y Compose están disponibles:

```bash
docker --version
docker compose version
```

## Preparación del entorno

### 1. Entrar en el proyecto

Desde la carpeta que contiene este README:

```bash
cd proyectolaravel
```

### 2. Crear el archivo de entorno

```bash
cp .env.example .env
```

Para utilizar el MySQL de Docker, revisa que estas variables estén en
`.env`:

```dotenv
APP_URL=http://localhost:8001
APP_PORT=8001
VITE_PORT=5174

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

FORWARD_DB_PORT=3307
FORWARD_REDIS_PORT=6380
FORWARD_MEILISEARCH_PORT=7701
FORWARD_MAILPIT_PORT=1026
FORWARD_MAILPIT_DASHBOARD_PORT=8026
```

`DB_HOST` debe ser `mysql`, no `localhost`, porque Laravel se conecta al
servicio MySQL desde otro contenedor. `APP_PORT` es el puerto de la aplicación
en el navegador. Si el puerto 8001 está ocupado, cambia `APP_PORT` y
`APP_URL` por el mismo puerto.

### 3. Instalar las dependencias PHP

Si la carpeta `vendor/` ya existe, puede continuarse con el siguiente paso.
En una copia limpia, `vendor/` no está versionada. Si Composer está instalado
en el equipo, ejecuta:

```bash
composer install
```

Si no quieres instalar Composer en el equipo anfitrión, puedes preparar las
dependencias con la imagen oficial de Composer:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD:/app" \
    -w /app \
    composer:2 install --no-interaction --no-scripts
```

### 4. Levantar los servicios

```bash
docker compose up -d --build
```

Comprueba que los contenedores están activos:

```bash
docker compose ps
```

Los servicios principales son:

| Servicio | Uso | Puerto del equipo |
|---|---|---:|
| `laravel.test` | Aplicación Laravel | `8001` |
| `mysql` | Base de datos | `3307` |
| `redis` | Caché y colas | `6380` |
| `meilisearch` | Motor de búsqueda | `7701` |
| `mailpit` | Correo de desarrollo | `8026` |

### 5. Preparar Laravel y el frontend

Ejecuta estos comandos desde `proyectolaravel/`:

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

### 6. Crear la base de datos y los datos de ejemplo

Este comando elimina las tablas existentes, ejecuta todas las migraciones y
llama a los seeders:

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

El seeder de la biblioteca crea aproximadamente:

- 20 autores.
- 100 libros.
- 8 géneros.
- 30 miembros.
- 25 préstamos activos.
- 40 préstamos devueltos.

### 7. Abrir la aplicación

Interfaz web:

```text
http://localhost:8001
```

Comprobación de la API:

```text
http://localhost:8001/api/health
```

La respuesta esperada del endpoint de salud es:

```json
{
    "status": "ok",
    "service": "library-api"
}
```

## Desarrollo diario

Arrancar los servicios:

```bash
docker compose up -d
```

Pararlos sin borrar la base de datos:

```bash
docker compose down
```

Ver los logs de Laravel:

```bash
docker compose logs -f laravel.test
```

Ejecutar Artisan:

```bash
docker compose exec laravel.test php artisan <comando>
```

Ejemplos:

```bash
docker compose exec laravel.test php artisan route:list --path=api
docker compose exec laravel.test php artisan migrate:status
docker compose exec laravel.test php artisan tinker
```

Para borrar también los volúmenes de Docker, incluida la base de datos:

```bash
docker compose down -v
```

Utiliza este último comando solo cuando quieras empezar desde cero.

## Endpoints de la API

La URL base es `http://localhost:8001/api`.

| Método | Endpoint | Acción |
|---|---|---|
| `GET` | `/health` | Comprobar el estado de la API |
| `GET`, `POST` | `/authors` | Listar y crear autores |
| `GET`, `PUT/PATCH`, `DELETE` | `/authors/{author}` | Consultar, editar y borrar un autor |
| `GET`, `POST` | `/books` | Listar y crear libros |
| `GET`, `PUT/PATCH`, `DELETE` | `/books/{book}` | Consultar, editar y borrar un libro |
| `GET` | `/genres` | Listar géneros |
| `GET`, `POST` | `/members` | Listar y crear miembros |
| `GET`, `PUT/PATCH`, `DELETE` | `/members/{member}` | Consultar, editar y borrar un miembro |
| `GET`, `POST` | `/loans` | Listar y crear préstamos |
| `GET`, `PUT/PATCH`, `DELETE` | `/loans/{loan}` | Consultar, editar y borrar un préstamo |
| `POST` | `/loans/{loan}/return` | Registrar una devolución |

### Filtros y ordenación

Libros:

```text
GET /api/books?sort=title&direction=asc&per_page=15
```

Valores permitidos para `sort`: `title`, `published_year` y `created_at`.

Préstamos:

```text
GET /api/loans?active=1&member_id=2&sort=due_at&direction=asc&per_page=15
```

Valores permitidos para `active`: `1` o `0`. También se puede filtrar por
`member_id`. Los valores permitidos para `sort` son `borrowed_at`, `due_at`,
`returned_at` y `created_at`.

### Ejemplos con cURL

```bash
curl http://localhost:8001/api/health
curl http://localhost:8001/api/authors
curl "http://localhost:8001/api/books?sort=title&direction=asc"
curl "http://localhost:8001/api/loans?active=1"
```

Crear un miembro:

```bash
curl -X POST http://localhost:8001/api/members \
    -H "Content-Type: application/json" \
    -d '{"name":"Ana García","phone":"600123123"}'
```

Crear un préstamo:

```bash
curl -X POST http://localhost:8001/api/loans \
    -H "Content-Type: application/json" \
    -d '{"member_id":1,"book_id":2,"borrowed_at":"2026-09-10","due_at":"2026-09-24"}'
```

Registrar una devolución:

```bash
curl -X POST http://localhost:8001/api/loans/1/return
```

## Estructura importante

```text
proyectolaravel/
├── app/
│   ├── Http/Controllers/       Acciones HTTP de la API
│   ├── Http/Requests/          Validación de entrada
│   ├── Http/Resources/         Formato de las respuestas JSON
│   └── Models/                 Modelos Eloquent y relaciones
├── database/
│   ├── factories/              Datos falsos reutilizables
│   ├── migrations/             Esquema versionado de la base de datos
│   └── seeders/                Datos iniciales y de demostración
├── resources/
│   ├── css/                    Estilos
│   ├── js/                     Lógica del frontend
│   └── views/                  Vistas Blade
├── routes/
│   ├── api.php                 Rutas JSON
│   └── web.php                 Ruta de la interfaz
├── tests/                      Tests unitarios y funcionales
├── compose.yaml                Servicios Docker
└── vite.config.js              Configuración de Vite
```

## Conceptos que se practican

1. **Routes**: relacionan una URL y un verbo HTTP con un controlador.
2. **Controllers**: coordinan la petición y devuelven la respuesta.
3. **Form Requests**: validan los datos antes de ejecutar la acción.
4. **Models y Eloquent**: representan tablas y relaciones.
5. **Resources**: definen el contrato JSON público.
6. **Factories y seeders**: permiten preparar escenarios reproducibles.
7. **Eager loading**: evita consultas N+1 mediante `with()`.
8. **Tests funcionales**: comprueban endpoints y reglas de negocio.
9. **Blade y Vite**: muestran una interfaz que consume la API.

## Tests y calidad

Ejecutar todos los tests:

```bash
docker compose exec -T laravel.test php artisan test
```

Comprobar el formato PHP sin modificar archivos:

```bash
docker compose exec -T laravel.test vendor/bin/pint --test
```

Aplicar el formato PHP:

```bash
docker compose exec -T laravel.test vendor/bin/pint
```

Compilar los recursos frontend:

```bash
docker compose exec -T laravel.test npm run build
```

Estado actual de la validación del proyecto:

- 41 tests pasados.
- 131 assertions.
- Pint correcto.
- Build de Vite correcto.

## Problemas frecuentes

### No aparece nada en `localhost`

Comprueba el puerto configurado:

```bash
docker compose ps
```

La aplicación de este entorno utiliza `http://localhost:8001`. Si has
modificado `.env`, reconstruye la configuración:

```bash
docker compose exec laravel.test php artisan config:clear
docker compose up -d
```

Si el contenedor no está activo, consulta sus logs:

```bash
docker compose logs laravel.test
```

### Error de conexión con MySQL

Dentro de Docker, `.env` debe contener `DB_HOST=mysql`. Después de cambiar
variables de entorno, ejecuta:

```bash
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan migrate:status
```

### La interfaz no carga los estilos

Recompila Vite:

```bash
docker compose exec laravel.test npm run build
```

Durante el desarrollo también se puede ejecutar Vite en modo observación:

```bash
docker compose exec laravel.test npm run dev -- --host=0.0.0.0
```

## Siguiente paso de aprendizaje

Antes de avanzar al proyecto 2, conviene poder explicar:

- Qué recorrido sigue una petición desde `routes/api.php` hasta un
  `JsonResource`.
- Por qué la validación está en `FormRequest` y no en el controlador.
- Qué relaciones existen entre los modelos.
- Por qué los listados usan `with()` y paginación.
- Cómo se garantiza que un libro no tenga dos préstamos activos.
- Cómo reproducir el estado inicial con `migrate:fresh --seed`.
- Cómo comprobar un cambio mediante un feature test. 