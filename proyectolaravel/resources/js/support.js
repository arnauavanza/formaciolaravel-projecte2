const supportApp = document.getElementById('support-app');

if (supportApp) {
    const state = {
        user: null,
        tickets: [],
        selectedTicket: null,
        comments: [],
    };

    const byId = (id) => document.getElementById(id);

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const statusLabels = {
        open: 'Abierto',
        in_progress: 'En progreso',
        resolved: 'Resuelto',
        closed: 'Cerrado',
    };

    function showToast(message, isError = false) {
        const toast = byId('support-toast');

        toast.textContent = message;
        toast.hidden = false;
        toast.classList.toggle('is-error', isError);

        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => {
            toast.hidden = true;
        }, 4500);
    }

    function saveSession(payload) {
        state.user = payload.user;
    }

    function clearSession() {
        state.user = null;
        state.tickets = [];
        state.selectedTicket = null;
        state.comments = [];

        localStorage.removeItem('p2_user');
    }

    async function ensureCsrfToken(headers) {
        await fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
        });

        const cookie = document.cookie
            .split('; ')
            .find((value) => value.startsWith('XSRF-TOKEN='));

        if (cookie) {
            headers['X-XSRF-TOKEN'] = decodeURIComponent(
                cookie.split('=').slice(1).join('=')
            );
        }
    }

    async function apiRequest(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        const request = {
            ...options,
            headers,
            credentials: 'same-origin',
        };

        if (! ['GET', 'HEAD', 'OPTIONS'].includes(
            (request.method || 'GET').toUpperCase()
        )) {
            await ensureCsrfToken(headers);
        }

        if (
            request.body
            && !(request.body instanceof FormData)
            && typeof request.body !== 'string'
        ) {
            headers['Content-Type'] = 'application/json';
            request.body = JSON.stringify(request.body);
        }

        const response = await fetch(url, request);
        const responseText = await response.text();
        let payload = null;

        if (responseText) {
            try {
                payload = JSON.parse(responseText);
            } catch {
                payload = { message: responseText };
            }
        }

        if (response.status === 401) {
            clearSession();
            showAuth();
        }

        if (! response.ok) {
            throw new Error(
                payload?.message || `Request failed (${response.status})`
            );
        }

        return payload;
    }

    async function downloadFile(url, filename) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/pdf, application/octet-stream',
            },
        });

        if (! response.ok) {
            const responseText = await response.text();
            let payload = null;

            try {
                payload = JSON.parse(responseText);
            } catch {
                payload = { message: responseText };
            }

            throw new Error(
                payload?.message || `Download failed (${response.status})`
            );
        }

        const blob = await response.blob();
        const objectUrl = URL.createObjectURL(blob);
        const anchor = document.createElement('a');

        anchor.href = objectUrl;
        anchor.download = filename;
        document.body.append(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(objectUrl);
    }

    function showAuth() {
        byId('auth-view').hidden = false;
        byId('dashboard-view').hidden = true;
        byId('session-toolbar').hidden = true;
    }

    function showDashboard() {
        byId('auth-view').hidden = true;
        byId('dashboard-view').hidden = false;
        byId('session-toolbar').hidden = false;

        const roles = state.user?.roles || [];

        byId('session-user').textContent =
            `${state.user?.name || ''} · ${state.user?.email || ''}`;
        byId('session-role').textContent =
            roles.length ? roles.join(', ') : 'usuario';
    }

    function renderTickets() {
        const container = byId('tickets-list');

        if (! state.tickets.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No hay tickets visibles.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = state.tickets.map((ticket) => `
            <button
                class="ticket-card ${state.selectedTicket?.id === ticket.id ? 'is-selected' : ''}"
                type="button"
                data-ticket-id="${ticket.id}"
            >
                <strong>${escapeHtml(ticket.title)}</strong>
                <small>#${ticket.id} · cliente ${ticket.customer_id}</small>
                <span class="status-chip">${escapeHtml(statusLabels[ticket.status] || ticket.status)}</span>
            </button>
        `).join('');
    }

    function renderComments() {
        const container = byId('comments-list');

        if (! state.comments.length) {
            container.innerHTML = '<p class="support-muted">No hay comentarios.</p>';
            return;
        }

        container.innerHTML = state.comments.map((comment) => {
            const attachments = (comment.attachments || []).map((attachment) => `
                <button
                    class="attachment-link"
                    type="button"
                    data-download-comment="${comment.id}"
                    data-download-attachment="${attachment.id}"
                    data-download-name="${escapeHtml(attachment.original_name)}"
                >
                    Descargar ${escapeHtml(attachment.original_name)}
                </button>
            `).join('');

            return `
                <article class="comment">
                    <div class="comment-header">
                        <strong>${escapeHtml(comment.user?.name || `Usuario #${comment.user_id}`)}</strong>
                        <span>${escapeHtml(comment.created_at || '')}</span>
                    </div>
                    <p>${escapeHtml(comment.body)}</p>
                    ${attachments ? `<div class="attachment-list">${attachments}</div>` : ''}
                </article>
            `;
        }).join('');
    }

    function renderDetail() {
        const ticket = state.selectedTicket;

        if (! ticket) {
            byId('empty-detail').hidden = false;
            byId('ticket-detail').hidden = true;
            return;
        }

        byId('empty-detail').hidden = true;
        byId('ticket-detail').hidden = false;
        byId('detail-ticket-id').textContent = `Ticket #${ticket.id}`;
        byId('detail-title').textContent = ticket.title;
        byId('detail-status').textContent =
            statusLabels[ticket.status] || ticket.status;
        byId('detail-customer').textContent = `Cliente #${ticket.customer_id}`;
        byId('detail-agent').textContent =
            ticket.agent_id ? `Agente #${ticket.agent_id}` : 'Sin asignar';
        byId('ticket-title').value = ticket.title;
        byId('ticket-description').value = ticket.description;
        byId('ticket-status').value =
            ['open', 'in_progress', 'resolved'].includes(ticket.status)
                ? ticket.status
                : 'resolved';
        byId('close-ticket-button').disabled = ticket.status !== 'resolved';
        byId('download-pdf-button').disabled = ticket.status !== 'closed';
        byId('download-pdf-button').title =
            ticket.status === 'closed'
                ? 'Descargar PDF'
                : 'Disponible cuando el ticket esté cerrado';
        renderComments();
    }

    async function loadTickets() {
        const payload = await apiRequest('/api/tickets');

        state.tickets = payload.data || [];
        renderTickets();

        if (state.selectedTicket) {
            await selectTicket(state.selectedTicket.id);
        }
    }

    async function selectTicket(ticketId) {
        const payload = await apiRequest(`/api/tickets/${ticketId}`);

        state.selectedTicket = payload.data || payload;
        renderTickets();
        renderDetail();

        const comments = await apiRequest(
            `/api/tickets/${ticketId}/comments`
        );

        state.comments = comments.data || [];
        renderComments();
    }

    function openCreateTicketModal() {
        const modal = byId('create-ticket-modal');
        const form = byId('create-ticket-form');

        form.reset();
        modal.hidden = false;
        byId('create-ticket-title-input').focus();
    }

    function closeCreateTicketModal() {
        byId('create-ticket-modal').hidden = true;
    }

    async function createTicket(event) {
        event.preventDefault();

        const form = byId('create-ticket-form');
        const title = form.elements.title.value.trim();
        const description = form.elements.description.value.trim();

        if (! title || ! description) {
            showToast('Título y descripción son obligatorios.', true);

            return;
        }

        const payload = await apiRequest('/api/tickets', {
            method: 'POST',
            body: {
                title,
                description,
                customer_id: state.user.id,
            },
        });

        closeCreateTicketModal();
        await loadTickets();
        await selectTicket(payload.data.id);
        showToast('Ticket creado correctamente.');
    }

    async function updateTicket(event) {
        event.preventDefault();

        if (! state.selectedTicket) {
            return;
        }

        const payload = await apiRequest(
            `/api/tickets/${state.selectedTicket.id}`,
            {
                method: 'PATCH',
                body: {
                    title: byId('ticket-title').value,
                    description: byId('ticket-description').value,
                    status: byId('ticket-status').value,
                },
            }
        );

        state.selectedTicket = payload.data || payload;
        await loadTickets();
        showToast('Ticket actualizado.');
    }

    async function assignTicket(event) {
        event.preventDefault();

        if (! state.selectedTicket) {
            return;
        }

        await apiRequest(
            `/api/tickets/${state.selectedTicket.id}/assign`,
            {
                method: 'POST',
                body: {
                    agent_id: Number(byId('agent-id').value),
                },
            }
        );

        await loadTickets();
        showToast('Ticket asignado.');
    }

    async function closeTicket() {
        if (! state.selectedTicket || state.selectedTicket.status !== 'resolved') {
            return;
        }

        await apiRequest(
            `/api/tickets/${state.selectedTicket.id}/close`,
            { method: 'POST' }
        );

        await loadTickets();
        showToast('Ticket cerrado. El PDF y el email se han encolado.');
    }

    async function deleteTicket() {
        if (! state.selectedTicket || ! window.confirm('¿Borrar este ticket?')) {
            return;
        }

        await apiRequest(
            `/api/tickets/${state.selectedTicket.id}`,
            { method: 'DELETE' }
        );

        state.selectedTicket = null;
        await loadTickets();
        renderDetail();
        showToast('Ticket eliminado.');
    }

    async function createComment(event) {
        event.preventDefault();

        if (! state.selectedTicket) {
            return;
        }

        const body = byId('comment-body').value.trim();

        if (! body) {
            return;
        }

        const comment = await apiRequest(
            `/api/tickets/${state.selectedTicket.id}/comments`,
            {
                method: 'POST',
                body: { body },
            }
        );

        const file = byId('comment-file').files[0];

        if (file) {
            const formData = new FormData();
            formData.append('file', file);

            await apiRequest(
                `/api/comments/${comment.data.id}/attachments`,
                {
                    method: 'POST',
                    body: formData,
                }
            );
        }

        byId('comment-body').value = '';
        byId('comment-file').value = '';
        await selectTicket(state.selectedTicket.id);
        showToast('Comentario guardado.');
    }

    async function downloadAttachment(event) {
        const button = event.target.closest('[data-download-attachment]');

        if (! button) {
            return;
        }

        await downloadFile(
            `/api/comments/${button.dataset.downloadComment}/attachments/${button.dataset.downloadAttachment}/download`,
            button.dataset.downloadName,
        );
    }

    async function downloadPdf() {
        if (
            ! state.selectedTicket
            || state.selectedTicket.status !== 'closed'
        ) {
            showToast('El PDF estará disponible cuando el ticket esté cerrado.');
            return;
        }

        await downloadFile(
            `/api/tickets/${state.selectedTicket.id}/pdf`,
            `ticket-${state.selectedTicket.id}-history.pdf`,
        );
    }

    async function login(event) {
        event.preventDefault();

        const data = Object.fromEntries(new FormData(event.currentTarget));
        const payload = await apiRequest('/api/auth/login', {
            method: 'POST',
            body: data,
        });

        saveSession(payload);
        showDashboard();
        await loadTickets();
        showToast('Sesión iniciada.');
    }

    async function register(event) {
        event.preventDefault();

        const data = Object.fromEntries(new FormData(event.currentTarget));
        const payload = await apiRequest('/api/auth/register', {
            method: 'POST',
            body: data,
        });

        saveSession(payload);
        showDashboard();
        await loadTickets();
        showToast('Cuenta creada.');
    }

    async function logout() {
        try {
            await apiRequest('/api/auth/logout', { method: 'POST' });
        } finally {
            clearSession();
            showAuth();
        }
    }

    function setupAuthTabs() {
        document.querySelectorAll('[data-auth-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.authTab;

                document.querySelectorAll('[data-auth-tab]').forEach((item) => {
                    item.classList.toggle(
                        'is-active',
                        item.dataset.authTab === target,
                    );
                });

                byId('login-form').hidden = target !== 'login';
                byId('register-form').hidden = target !== 'register';
            });
        });
    }

    function setupEvents() {
        byId('login-form').addEventListener('submit', withErrorHandling(login));
        byId('register-form').addEventListener('submit', withErrorHandling(register));
        byId('logout-button').addEventListener('click', withErrorHandling(logout));
        byId('new-ticket-button').addEventListener('click', openCreateTicketModal);
        byId('create-ticket-form').addEventListener('submit', withErrorHandling(createTicket));
        byId('refresh-tickets-button').addEventListener('click', withErrorHandling(loadTickets));
        byId('ticket-form').addEventListener('submit', withErrorHandling(updateTicket));
        byId('assign-form').addEventListener('submit', withErrorHandling(assignTicket));
        byId('close-ticket-button').addEventListener('click', withErrorHandling(closeTicket));
        byId('delete-ticket-button').addEventListener('click', withErrorHandling(deleteTicket));
        byId('comment-form').addEventListener('submit', withErrorHandling(createComment));
        byId('refresh-comments-button').addEventListener(
            'click',
            withErrorHandling(() => selectTicket(state.selectedTicket.id)),
        );
        byId('download-pdf-button').addEventListener('click', withErrorHandling(downloadPdf));
        byId('comments-list').addEventListener('click', withErrorHandling(downloadAttachment));
        byId('tickets-list').addEventListener('click', withErrorHandling((event) => {
            const ticket = event.target.closest('[data-ticket-id]');

            if (ticket) {
                return selectTicket(ticket.dataset.ticketId);
            }
        }));

        document.querySelectorAll('[data-close-create-ticket]').forEach((el) => {
            el.addEventListener('click', closeCreateTicketModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! byId('create-ticket-modal').hidden) {
                closeCreateTicketModal();
            }
        });

        document.querySelectorAll('[data-demo-email]').forEach((button) => {
            button.addEventListener('click', () => {
                byId('login-form').elements.email.value = button.dataset.demoEmail;
                byId('login-form').elements.password.value = 'password';
            });
        });
    }

    function withErrorHandling(callback) {
        return async (event) => {
            try {
                await callback(event);
            } catch (error) {
                showToast(error.message, true);
            }
        };
    }

    async function init() {
        setupAuthTabs();
        setupEvents();

        try {
            const payload = await apiRequest('/api/auth/me');
            state.user = payload.data || payload;
            showDashboard();
            await loadTickets();
        } catch {
            clearSession();
            showAuth();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}
