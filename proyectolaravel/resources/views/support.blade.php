<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Soporte · Gestor de incidencias</title>
    @vite([
        'resources/css/app.css',
        'resources/css/support.css',
        'resources/js/support.js',
    ])
</head>
<body class="support-page">
    <div id="support-app" class="support-shell">
        <header class="library-topbar support-topbar">
            <a class="brand" href="{{ url('/') }}" aria-label="Biblioteca">
                <span class="brand-mark">S</span>
                <span>
                    <strong>Soporte</strong>
                    <small>Gestor de incidencias</small>
                </span>
            </a>

            <nav class="main-nav support-nav" aria-label="Secciones">
                <a class="nav-link" href="{{ url('/') }}">
                    Biblioteca
                </a>
                <a class="nav-link is-active" href="{{ route('support') }}">
                    Soporte
                </a>
            </nav>

            <div id="session-toolbar" class="session-toolbar" hidden>
                <span id="session-user"></span>
                <span id="session-role" class="role-chip"></span>
                <button id="logout-button" class="button button-ghost" type="button">
                    Cerrar sesión
                </button>
            </div>
        </header>

        <main class="support-main">
            <div id="support-toast" class="support-toast" role="alert" hidden></div>

            <section id="auth-view" class="auth-layout">
                <div class="auth-intro">
                    <p class="eyebrow">Proyecto 2</p>
                    <h1>Gestiona incidencias de principio a fin.</h1>
                    <p>
                        Autenticación, permisos, tickets, comentarios, adjuntos,
                        PDF y procesos asíncronos desde una única interfaz.
                    </p>
                    <a class="text-link" href="{{ url('/') }}">
                        Volver a la biblioteca →
                    </a>
                </div>

                <div class="auth-card">
                    <div class="auth-tabs">
                        <button class="auth-tab is-active" data-auth-tab="login" type="button">
                            Iniciar sesión
                        </button>
                        <button class="auth-tab" data-auth-tab="register" type="button">
                            Registrarse
                        </button>
                    </div>

                    <form id="login-form" class="stack-form">
                        <label>
                            Email
                            <input name="email" type="email" required value="customer@example.com">
                        </label>
                        <label>
                            Contraseña
                            <input name="password" type="password" required value="password">
                        </label>
                        <button class="button button-primary" type="submit">
                            Entrar
                        </button>
                    </form>

                    <form id="register-form" class="stack-form" hidden>
                        <label>
                            Nombre
                            <input name="name" type="text" required>
                        </label>
                        <label>
                            Email
                            <input name="email" type="email" required>
                        </label>
                        <label>
                            Contraseña
                            <input name="password" type="password" required minlength="8">
                        </label>
                        <label>
                            Repite la contraseña
                            <input name="password_confirmation" type="password" required minlength="8">
                        </label>
                        <button class="button button-primary" type="submit">
                            Crear cuenta
                        </button>
                    </form>

                    <div class="demo-users">
                        <span>Usuarios de prueba</span>
                        <button type="button" data-demo-email="customer@example.com">Customer</button>
                        <button type="button" data-demo-email="agent@example.com">Agent</button>
                        <button type="button" data-demo-email="test@example.com">Admin</button>
                    </div>
                </div>
            </section>

            <section id="dashboard-view" hidden>
                <div class="support-heading">
                    <div>
                        <p class="eyebrow">Panel de soporte</p>
                        <h1>Incidencias</h1>
                        <p class="support-muted">
                            Selecciona un ticket para consultar su historial y ejecutar acciones.
                        </p>
                    </div>
                    <button id="new-ticket-button" class="button button-primary" type="button">
                        Nuevo ticket
                    </button>
                </div>

                <div class="support-grid">
                    <aside class="support-panel ticket-list-panel">
                        <div class="panel-heading">
                            <h2>Tickets</h2>
                            <button id="refresh-tickets-button" class="icon-button" type="button" title="Actualizar">
                                ↻
                            </button>
                        </div>
                        <div id="tickets-list" class="tickets-list"></div>
                    </aside>

                    <section class="support-panel detail-panel">
                        <div id="empty-detail" class="empty-state">
                            <span class="empty-icon">✓</span>
                            <h2>Selecciona un ticket</h2>
                            <p>El detalle, los comentarios y los adjuntos aparecerán aquí.</p>
                        </div>

                        <div id="ticket-detail" hidden>
                            <div class="detail-header">
                                <div>
                                    <p id="detail-ticket-id" class="eyebrow"></p>
                                    <h2 id="detail-title"></h2>
                                    <span id="detail-status" class="status-chip"></span>
                                </div>
                                <div class="detail-actions">
                                    <button id="download-pdf-button" class="button button-secondary" type="button">
                                        Descargar PDF
                                    </button>
                                    <button id="delete-ticket-button" class="button button-danger" type="button">
                                        Borrar
                                    </button>
                                </div>
                            </div>

                            <div class="detail-meta">
                                <span id="detail-customer"></span>
                                <span id="detail-agent"></span>
                            </div>

                            <form id="ticket-form" class="detail-form">
                                <label>
                                    Título
                                    <input id="ticket-title" name="title" type="text" required>
                                </label>
                                <label>
                                    Descripción
                                    <textarea id="ticket-description" name="description" rows="4" required></textarea>
                                </label>
                                <label>
                                    Estado
                                    <select id="ticket-status" name="status">
                                        <option value="open">Abierto</option>
                                        <option value="in_progress">En progreso</option>
                                        <option value="resolved">Resuelto</option>
                                    </select>
                                </label>
                                <button class="button button-primary" type="submit">
                                    Guardar cambios
                                </button>
                            </form>

                            <div class="assignment-box">
                                <div>
                                    <h3>Asignación</h3>
                                    <p class="support-muted">Introduce el ID del usuario agente.</p>
                                </div>
                                <form id="assign-form" class="inline-form">
                                    <input id="agent-id" type="number" min="1" placeholder="ID del agente" required>
                                    <button class="button button-secondary" type="submit">
                                        Asignar
                                    </button>
                                </form>
                                <button id="close-ticket-button" class="button button-secondary" type="button">
                                    Cerrar ticket resuelto
                                </button>
                            </div>

                            <section class="comments-section">
                                <div class="panel-heading">
                                    <h3>Comentarios</h3>
                                    <button id="refresh-comments-button" class="icon-button" type="button" title="Actualizar">
                                        ↻
                                    </button>
                                </div>
                                <div id="comments-list" class="comments-list"></div>

                                <form id="comment-form" class="comment-form">
                                    <textarea id="comment-body" rows="3" placeholder="Escribe un comentario..." required></textarea>
                                    <div class="comment-actions">
                                        <label class="file-input">
                                            <span>Adjuntar archivo</span>
                                            <input id="comment-file" type="file" accept=".jpg,.jpeg,.png,.pdf,.txt,.doc,.docx">
                                        </label>
                                        <button class="button button-primary" type="submit">
                                            Comentar
                                        </button>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <div id="create-ticket-modal" class="support-modal" hidden>
            <div class="support-modal-backdrop" data-close-create-ticket></div>
            <div
                class="support-modal-panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-ticket-title"
            >
                <div class="support-modal-header">
                    <div>
                        <p class="eyebrow">Nueva incidencia</p>
                        <h2 id="create-ticket-title">Crear ticket</h2>
                        <p class="support-muted">
                            Describe el problema con el mayor detalle posible.
                        </p>
                    </div>
                    <button
                        id="close-create-ticket-button"
                        class="icon-button"
                        type="button"
                        data-close-create-ticket
                        aria-label="Cerrar"
                    >
                        ✕
                    </button>
                </div>

                <form id="create-ticket-form" class="stack-form">
                    <label>
                        Título
                        <input
                            id="create-ticket-title-input"
                            name="title"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="Ej. No puedo renovar un préstamo"
                        >
                    </label>
                    <label>
                        Descripción
                        <textarea
                            id="create-ticket-description-input"
                            name="description"
                            rows="6"
                            required
                            placeholder="Explica qué pasa, desde cuándo y qué has intentado."
                        ></textarea>
                    </label>
                    <div class="support-modal-actions">
                        <button
                            class="button button-secondary"
                            type="button"
                            data-close-create-ticket
                        >
                            Cancelar
                        </button>
                        <button class="button button-primary" type="submit">
                            Crear ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
