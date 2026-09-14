<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Biblioteca · Laravel API</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="library-app" class="library-shell">
            <header class="library-topbar">
                <a href="{{ url('/') }}" class="brand" aria-label="Inicio de la biblioteca">
                    <span class="brand-mark">B</span>
                    <span>
                        <strong>Biblioteca</strong>
                        <small>Laravel API</small>
                    </span>
                </a>

                <nav class="main-nav" aria-label="Secciones de la biblioteca">
                    <button type="button" class="nav-link is-active" data-view-target="dashboard-view">
                        Resumen
                    </button>
                    <button type="button" class="nav-link" data-view-target="authors-view">
                        Autores
                    </button>
                    <button type="button" class="nav-link" data-view-target="members-view">
                        Miembros
                    </button>
                    <button type="button" class="nav-link" data-view-target="books-view">
                        Libros
                    </button>
                    <button type="button" class="nav-link" data-view-target="loans-view">
                        Préstamos
                    </button>
                </nav>

                <div class="api-status" title="Estado de GET /api/health">
                    <span id="api-status-dot" class="status-dot is-loading"></span>
                    <span id="api-status-text">Comprobando API…</span>
                </div>
            </header>

            <div class="page-content">
                <div id="flash-message" class="flash-message" role="alert" hidden></div>

                <main>
                    <section id="dashboard-view" class="view">
                        <div class="section-heading hero-heading">
                            <div>
                                <p class="eyebrow">Panel de control</p>
                                <h1>Gestiona tu biblioteca</h1>
                                <p class="section-description">
                                    Esta interfaz Blade consume directamente los endpoints JSON de la API.
                                </p>
                            </div>
                            <button type="button" class="button button-secondary" data-action="refresh-all">
                                Actualizar datos
                            </button>
                        </div>

                        <div class="stat-grid">
                            <article class="stat-card stat-card-blue">
                                <span class="stat-label">Autores</span>
                                <strong id="stat-authors">—</strong>
                                <span class="stat-detail">disponibles en el catálogo</span>
                            </article>
                            <article class="stat-card stat-card-teal">
                                <span class="stat-label">Miembros</span>
                                <strong id="stat-members">—</strong>
                                <span class="stat-detail">registrados en la biblioteca</span>
                            </article>
                            <article class="stat-card stat-card-violet">
                                <span class="stat-label">Libros</span>
                                <strong id="stat-books">—</strong>
                                <span class="stat-detail">con autor y géneros</span>
                            </article>
                            <article class="stat-card stat-card-amber">
                                <span class="stat-label">Préstamos</span>
                                <strong id="stat-loans">—</strong>
                                <span class="stat-detail">históricos registrados</span>
                            </article>
                            <article class="stat-card stat-card-green">
                                <span class="stat-label">Préstamos activos</span>
                                <strong id="stat-active-loans">—</strong>
                                <span class="stat-detail">pendientes de devolución</span>
                            </article>
                        </div>

                        <div class="dashboard-grid">
                            <article class="panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Acciones rápidas</p>
                                        <h2>¿Qué quieres gestionar?</h2>
                                    </div>
                                </div>
                                <div class="quick-actions">
                                    <button type="button" class="quick-action" data-view-target="authors-view">
                                        <span class="quick-icon quick-icon-blue">A</span>
                                        <span>
                                            <strong>Autores</strong>
                                            <small>Crear, consultar, editar o eliminar</small>
                                        </span>
                                        <span class="quick-arrow">→</span>
                                    </button>
                                    <button type="button" class="quick-action" data-view-target="books-view">
                                        <span class="quick-icon quick-icon-violet">L</span>
                                        <span>
                                            <strong>Libros</strong>
                                            <small>Administrar catálogo y géneros</small>
                                        </span>
                                        <span class="quick-arrow">→</span>
                                    </button>
                                    <button type="button" class="quick-action" data-view-target="members-view">
                                        <span class="quick-icon quick-icon-teal">M</span>
                                        <span>
                                            <strong>Miembros</strong>
                                            <small>Gestionar lectores y teléfonos</small>
                                        </span>
                                        <span class="quick-arrow">→</span>
                                    </button>
                                    <button type="button" class="quick-action" data-view-target="loans-view">
                                        <span class="quick-icon quick-icon-amber">P</span>
                                        <span>
                                            <strong>Préstamos</strong>
                                            <small>Filtrar y registrar devoluciones</small>
                                        </span>
                                        <span class="quick-arrow">→</span>
                                    </button>
                                </div>
                            </article>

                            <article class="panel api-reference">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Referencia</p>
                                        <h2>Endpoints conectados</h2>
                                    </div>
                                </div>
                                <ul class="endpoint-list">
                                    <li><code>GET /api/health</code><span>estado de la API</span></li>
                                    <li><code>/api/authors</code><span>CRUD de autores</span></li>
                                    <li><code>/api/members</code><span>CRUD de miembros</span></li>
                                    <li><code>/api/books</code><span>CRUD de libros</span></li>
                                    <li><code>/api/loans</code><span>crear, listar y filtrar</span></li>
                                    <li><code>GET/PATCH/DELETE /api/loans/{id}</code><span>detalle y gestión</span></li>
                                    <li><code>POST /api/loans/{id}/return</code><span>devolución</span></li>
                                </ul>
                            </article>
                        </div>
                    </section>

                    <section id="authors-view" class="view" hidden>
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Catálogo</p>
                                <h1>Autores</h1>
                                <p class="section-description">
                                    Administra los autores que pueden asociarse a los libros.
                                </p>
                            </div>
                            <button type="button" class="button button-secondary" data-action="reload-authors">
                                Recargar
                            </button>
                        </div>

                        <div class="content-grid">
                            <article class="panel form-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow" id="author-form-eyebrow">Nuevo registro</p>
                                        <h2 id="author-form-title">Crear autor</h2>
                                    </div>
                                </div>
                                <form id="author-form">
                                    <input type="hidden" id="author-id">
                                    <label class="field">
                                        <span>Nombre</span>
                                        <input id="author-name" name="name" type="text" maxlength="255" required
                                               placeholder="Ej.: Gabriel García Márquez">
                                    </label>
                                    <div id="author-form-errors" class="form-errors" hidden></div>
                                    <div class="form-actions">
                                        <button type="submit" class="button button-primary" id="author-submit">
                                            Crear autor
                                        </button>
                                        <button type="button" class="button button-ghost" data-action="reset-author-form">
                                            Limpiar
                                        </button>
                                    </div>
                                </form>
                            </article>

                            <article class="panel table-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Registros</p>
                                        <h2>Autores registrados</h2>
                                    </div>
                                    <span id="authors-count" class="count-label">—</span>
                                </div>
                                <div class="table-wrap">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Creado</th>
                                                <th class="actions-column">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="authors-table-body">
                                            <tr><td colspan="3" class="loading-cell">Cargando autores…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="authors-pagination" class="pagination"></div>
                            </article>
                        </div>
                    </section>

                    <section id="members-view" class="view" hidden>
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Circulación</p>
                                <h1>Miembros</h1>
                                <p class="section-description">
                                    Gestiona los lectores que pueden realizar préstamos.
                                </p>
                            </div>
                            <button type="button" class="button button-secondary" data-action="reload-members">
                                Recargar
                            </button>
                        </div>

                        <div class="content-grid">
                            <article class="panel form-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow" id="member-form-eyebrow">Nuevo registro</p>
                                        <h2 id="member-form-title">Crear miembro</h2>
                                    </div>
                                </div>
                                <form id="member-form">
                                    <input type="hidden" id="member-id">
                                    <label class="field">
                                        <span>Nombre</span>
                                        <input id="member-name" type="text" maxlength="255" required
                                               placeholder="Ej.: Ana García">
                                    </label>
                                    <label class="field">
                                        <span>Teléfono</span>
                                        <input id="member-phone" type="tel" maxlength="30"
                                               placeholder="Opcional">
                                    </label>
                                    <div id="member-form-errors" class="form-errors" hidden></div>
                                    <div class="form-actions">
                                        <button type="submit" class="button button-primary" id="member-submit">
                                            Crear miembro
                                        </button>
                                        <button type="button" class="button button-ghost" data-action="reset-member-form">
                                            Limpiar
                                        </button>
                                    </div>
                                </form>
                            </article>

                            <article class="panel table-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Registros</p>
                                        <h2>Miembros registrados</h2>
                                    </div>
                                    <span id="members-count" class="count-label">—</span>
                                </div>
                                <div class="table-wrap">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Teléfono</th>
                                                <th class="actions-column">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="members-table-body">
                                            <tr><td colspan="3" class="loading-cell">Cargando miembros…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section id="books-view" class="view" hidden>
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Catálogo</p>
                                <h1>Libros</h1>
                                <p class="section-description">
                                    Crea y organiza libros, autores y géneros.
                                </p>
                            </div>
                            <button type="button" class="button button-secondary" data-action="reload-books">
                                Recargar
                            </button>
                        </div>

                        <div class="content-grid">
                            <article class="panel form-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow" id="book-form-eyebrow">Nuevo registro</p>
                                        <h2 id="book-form-title">Crear libro</h2>
                                    </div>
                                </div>
                                <form id="book-form">
                                    <input type="hidden" id="book-id">
                                    <label class="field">
                                        <span>Título</span>
                                        <input id="book-title" name="title" type="text" maxlength="255" required
                                               placeholder="Ej.: Cien años de soledad">
                                    </label>
                                    <label class="field">
                                        <span>Autor</span>
                                        <select id="book-author" name="author_id" required>
                                            <option value="">Selecciona un autor</option>
                                        </select>
                                    </label>
                                    <div class="form-row">
                                        <label class="field">
                                            <span>ISBN</span>
                                            <input id="book-isbn" name="isbn" type="text" maxlength="20"
                                                   placeholder="Opcional">
                                        </label>
                                        <label class="field">
                                            <span>Año</span>
                                            <input id="book-year" name="published_year" type="number" min="0"
                                                   max="{{ date('Y') }}" placeholder="Opcional">
                                        </label>
                                    </div>
                                    <fieldset class="field">
                                        <legend>Géneros</legend>
                                        <div id="book-genres" class="checkbox-grid">
                                            <span class="muted-text">Cargando géneros…</span>
                                        </div>
                                    </fieldset>
                                    <div id="book-form-errors" class="form-errors" hidden></div>
                                    <div class="form-actions">
                                        <button type="submit" class="button button-primary" id="book-submit">
                                            Crear libro
                                        </button>
                                        <button type="button" class="button button-ghost" data-action="reset-book-form">
                                            Limpiar
                                        </button>
                                    </div>
                                </form>
                            </article>

                            <article class="panel table-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Registros</p>
                                        <h2>Libros registrados</h2>
                                    </div>
                                    <span id="books-count" class="count-label">—</span>
                                </div>
                                <form id="book-filters" class="filters">
                                    <label class="field compact-field">
                                        <span>Ordenar por</span>
                                        <select id="book-sort">
                                            <option value="title">Título</option>
                                            <option value="published_year">Año</option>
                                            <option value="created_at">Fecha de alta</option>
                                        </select>
                                    </label>
                                    <label class="field compact-field">
                                        <span>Dirección</span>
                                        <select id="book-direction">
                                            <option value="asc">Ascendente</option>
                                            <option value="desc">Descendente</option>
                                        </select>
                                    </label>
                                    <label class="field compact-field">
                                        <span>Por página</span>
                                        <select id="book-per-page">
                                            <option value="10">10</option>
                                            <option value="15" selected>15</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                    </label>
                                    <button type="submit" class="button button-secondary">Aplicar</button>
                                </form>
                                <div class="table-wrap">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Libro</th>
                                                <th>Autor</th>
                                                <th>Géneros</th>
                                                <th class="actions-column">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="books-table-body">
                                            <tr><td colspan="4" class="loading-cell">Cargando libros…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="books-pagination" class="pagination"></div>
                            </article>
                        </div>
                    </section>

                    <section id="loans-view" class="view" hidden>
                        <div class="section-heading">
                            <div>
                                <p class="eyebrow">Circulación</p>
                                <h1>Préstamos</h1>
                                <p class="section-description">
                                    Consulta el historial y registra devoluciones.
                                </p>
                            </div>
                            <button type="button" class="button button-secondary" data-action="reload-loans">
                                Recargar
                            </button>
                        </div>

                        <div class="content-grid">
                            <article class="panel form-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow" id="loan-form-eyebrow">Nuevo registro</p>
                                        <h2 id="loan-form-title">Crear préstamo</h2>
                                    </div>
                                </div>
                                <form id="loan-form">
                                    <input type="hidden" id="loan-form-id">
                                    <label class="field">
                                        <span>Miembro</span>
                                        <select id="loan-form-member" required>
                                            <option value="">Selecciona un miembro</option>
                                        </select>
                                    </label>
                                    <label class="field">
                                        <span>Libro</span>
                                        <select id="loan-form-book" required>
                                            <option value="">Selecciona un libro</option>
                                        </select>
                                    </label>
                                    <div class="form-row">
                                        <label class="field">
                                            <span>Fecha de préstamo</span>
                                            <input id="loan-borrowed-at" type="datetime-local" required>
                                        </label>
                                        <label class="field">
                                            <span>Fecha de vencimiento</span>
                                            <input id="loan-due-at" type="datetime-local" required>
                                        </label>
                                    </div>
                                    <label class="field">
                                        <span>Fecha de devolución</span>
                                        <input id="loan-returned-at" type="datetime-local">
                                        <small class="field-help">Déjala vacía para crear un préstamo activo.</small>
                                    </label>
                                    <div id="loan-form-errors" class="form-errors" hidden></div>
                                    <div class="form-actions">
                                        <button type="submit" class="button button-primary" id="loan-submit">
                                            Crear préstamo
                                        </button>
                                        <button type="button" class="button button-ghost" data-action="reset-loan-form">
                                            Limpiar
                                        </button>
                                    </div>
                                </form>
                            </article>

                            <article class="panel help-panel">
                                <div class="panel-header">
                                    <div>
                                        <p class="eyebrow">Reglas de negocio</p>
                                        <h2>Antes de guardar</h2>
                                    </div>
                                </div>
                                <ul class="rule-list">
                                    <li>Un miembro puede tener varios libros prestados a la vez.</li>
                                    <li>Un libro no puede tener dos préstamos activos simultáneos.</li>
                                    <li>La fecha de vencimiento debe ser posterior al préstamo.</li>
                                    <li>Una devolución convierte el préstamo en histórico.</li>
                                </ul>
                            </article>
                        </div>

                        <div class="panel">
                            <form id="loan-filters" class="filters filters-loans">
                                <label class="field compact-field">
                                    <span>Estado</span>
                                    <select id="loan-active">
                                        <option value="">Todos</option>
                                        <option value="1">Activos</option>
                                        <option value="0">Devueltos</option>
                                    </select>
                                </label>
                                <label class="field compact-field">
                                    <span>Miembro</span>
                                    <select id="loan-member">
                                        <option value="">Todos</option>
                                    </select>
                                </label>
                                <label class="field compact-field">
                                    <span>Ordenar por</span>
                                    <select id="loan-sort">
                                        <option value="borrowed_at">Préstamo</option>
                                        <option value="due_at">Vencimiento</option>
                                        <option value="returned_at">Devolución</option>
                                        <option value="created_at">Creación</option>
                                    </select>
                                </label>
                                <label class="field compact-field">
                                    <span>Dirección</span>
                                    <select id="loan-direction">
                                        <option value="desc">Descendente</option>
                                        <option value="asc">Ascendente</option>
                                    </select>
                                </label>
                                <label class="field compact-field">
                                    <span>Por página</span>
                                    <select id="loan-per-page">
                                        <option value="10">10</option>
                                        <option value="15" selected>15</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </label>
                                <button type="submit" class="button button-secondary">Aplicar filtros</button>
                            </form>
                        </div>

                        <div class="notice notice-info">
                            <strong>Nota:</strong>
                            puedes crear, editar, eliminar y devolver préstamos desde este panel.
                        </div>

                        <article class="panel table-panel">
                            <div class="panel-header">
                                <div>
                                    <p class="eyebrow">Historial</p>
                                    <h2>Préstamos registrados</h2>
                                </div>
                                <span id="loans-count" class="count-label">—</span>
                            </div>
                            <div class="table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Miembro</th>
                                            <th>Libro</th>
                                            <th>Fechas</th>
                                            <th>Estado</th>
                                            <th class="actions-column">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="loans-table-body">
                                        <tr><td colspan="5" class="loading-cell">Cargando préstamos…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="loans-pagination" class="pagination"></div>
                        </article>
                    </section>
                </main>
            </div>

            <div id="detail-modal" class="modal" hidden aria-hidden="true">
                <div class="modal-backdrop" data-action="close-modal"></div>
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                    <div class="modal-header">
                        <h2 id="modal-title">Detalle</h2>
                        <button type="button" class="icon-button" data-action="close-modal" aria-label="Cerrar">
                            ×
                        </button>
                    </div>
                    <div id="modal-body" class="modal-body"></div>
                </div>
            </div>
        </div>
    </body>
</html>
