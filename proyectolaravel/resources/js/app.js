const libraryApp = document.getElementById('library-app');

if (libraryApp) {
    const state = {
        authors: [],
        authorOptions: [],
        bookOptions: [],
        genres: [],
        members: [],
        authorPageUrl: '/api/authors',
        bookPageUrl: null,
        loanPageUrl: null,
        bookFilters: {
            sort: 'title',
            direction: 'asc',
            per_page: '15',
        },
        loanFilters: {
            active: '',
            member_id: '',
            sort: 'borrowed_at',
            direction: 'desc',
            per_page: '15',
        },
    };

    let flashTimeout;

    const byId = (id) => document.getElementById(id);

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatDate(value) {
        if (!value) {
            return '—';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        return new Intl.DateTimeFormat('es-ES', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    }

    function normalizeApiUrl(url) {
        if (!url) {
            return null;
        }

        const parsedUrl = new URL(url, window.location.origin);

        return `${parsedUrl.pathname}${parsedUrl.search}`;
    }

    function buildQuery(parameters) {
        const query = new URLSearchParams();

        Object.entries(parameters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                query.set(key, value);
            }
        });

        const queryString = query.toString();

        return queryString ? `?${queryString}` : '';
    }

    async function apiRequest(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...options.headers,
        };
        const request = {
            ...options,
            headers,
        };

        if (request.body && typeof request.body !== 'string') {
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

        if (!response.ok) {
            const error = new Error(
                payload?.message || `La API respondió con ${response.status}.`,
            );

            error.status = response.status;
            error.errors = payload?.errors || {};

            throw error;
        }

        return payload;
    }

    function notify(message, type = 'success') {
        const flash = byId('flash-message');

        flash.textContent = message;
        flash.className = `flash-message is-${type}`;
        flash.hidden = false;

        window.clearTimeout(flashTimeout);
        flashTimeout = window.setTimeout(() => {
            flash.hidden = true;
        }, 5000);
    }

    function renderFormErrors(containerId, error) {
        const container = byId(containerId);
        const validationMessages = Object.values(error.errors || {}).flat();
        const messages = validationMessages.length
            ? validationMessages
            : [error.message || 'No se pudo completar la operación.'];

        container.innerHTML = `
            <strong>Revisa los datos:</strong>
            <ul>${messages.map((message) => `<li>${escapeHtml(message)}</li>`).join('')}</ul>
        `;
        container.hidden = false;
    }

    function clearFormErrors(containerId) {
        const container = byId(containerId);

        container.innerHTML = '';
        container.hidden = true;
    }

    function setFormBusy(form, busy) {
        form.querySelectorAll('button, input, select').forEach((control) => {
            control.disabled = busy;
        });
    }

    function setTableMessage(bodyId, colspan, message) {
        byId(bodyId).innerHTML = `
            <tr>
                <td colspan="${colspan}" class="loading-cell">${escapeHtml(message)}</td>
            </tr>
        `;
    }

    function renderPagination(containerId, page, onNavigate) {
        const container = byId(containerId);

        container.innerHTML = '';

        if (!page?.meta || page.meta.last_page <= 1) {
            return;
        }

        const info = document.createElement('span');
        const controls = document.createElement('div');

        info.className = 'pagination-info';
        info.textContent = `Página ${page.meta.current_page} de ${page.meta.last_page}`;
        controls.className = 'pagination-controls';

        [
            ['Anterior', page.links?.prev],
            ['Siguiente', page.links?.next],
        ].forEach(([label, url]) => {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'button button-small button-secondary';
            button.textContent = label;
            button.disabled = !url;
            button.addEventListener('click', () => onNavigate(normalizeApiUrl(url)));
            controls.append(button);
        });

        container.append(info, controls);
    }

    function setApiStatus(healthy, message) {
        const dot = byId('api-status-dot');
        const text = byId('api-status-text');

        dot.className = `status-dot ${healthy ? 'is-healthy' : 'is-error'}`;
        text.textContent = message;
    }

    async function checkHealth() {
        try {
            const response = await apiRequest('/api/health');

            setApiStatus(true, response?.status === 'ok' ? 'API conectada' : 'API disponible');
        } catch {
            setApiStatus(false, 'API no disponible');
        }
    }

    function switchView(viewId) {
        document.querySelectorAll('.view').forEach((view) => {
            view.hidden = view.id !== viewId;
        });

        document.querySelectorAll('[data-view-target]').forEach((link) => {
            link.classList.toggle('is-active', link.dataset.viewTarget === viewId);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateStatistics(authors, members, books, loans, activeLoans) {
        byId('stat-authors').textContent = authors?.meta?.total ?? authors?.data?.length ?? '—';
        byId('stat-members').textContent = members?.meta?.total ?? members?.data?.length ?? '—';
        byId('stat-books').textContent = books?.meta?.total ?? books?.data?.length ?? '—';
        byId('stat-loans').textContent = loans?.meta?.total ?? loans?.data?.length ?? '—';
        byId('stat-active-loans').textContent =
            activeLoans?.meta?.total ?? activeLoans?.data?.length ?? '—';
    }

    async function loadStatistics() {
        try {
            const [authors, members, books, loans, activeLoans] = await Promise.all([
                apiRequest('/api/authors?per_page=100'),
                apiRequest('/api/members'),
                apiRequest('/api/books?per_page=100'),
                apiRequest('/api/loans?per_page=1'),
                apiRequest('/api/loans?active=1&per_page=1'),
            ]);

            updateStatistics(authors, members, books, loans, activeLoans);
        } catch {
            notify('No se pudieron cargar las estadísticas del resumen.', 'error');
        }
    }

    async function loadAuthorOptions() {
        try {
            const response = await apiRequest('/api/authors?per_page=100');

            state.authorOptions = response.data || [];
            renderAuthorOptions();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function renderAuthorOptions(selectedId = byId('book-author').value) {
        const select = byId('book-author');

        select.innerHTML = `
            <option value="">Selecciona un autor</option>
            ${state.authorOptions
                .map(
                    (author) =>
                        `<option value="${author.id}">${escapeHtml(author.name)}</option>`,
                )
                .join('')}
        `;
        select.value = selectedId || '';
    }

    async function loadBookOptions() {
        try {
            const response = await apiRequest('/api/books?per_page=100&sort=title&direction=asc');

            state.bookOptions = response.data || [];
            renderBookOptions();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function renderBookOptions(selectedId = byId('loan-form-book').value) {
        const select = byId('loan-form-book');

        select.innerHTML = `
            <option value="">Selecciona un libro</option>
            ${state.bookOptions
                .map(
                    (book) =>
                        `<option value="${book.id}">${escapeHtml(book.title)} — ${escapeHtml(
                            book.author?.name || 'Sin autor',
                        )}</option>`,
                )
                .join('')}
        `;
        select.value = selectedId || '';
    }

    async function loadGenres() {
        try {
            const response = await apiRequest('/api/genres');

            state.genres = response.data || [];
            renderGenreOptions();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function renderGenreOptions(selectedIds = []) {
        const selected = selectedIds.map(String);
        const container = byId('book-genres');

        if (!state.genres.length) {
            container.innerHTML = '<span class="muted-text">No hay géneros disponibles.</span>';
            return;
        }

        container.innerHTML = state.genres
            .map(
                (genre) => `
                    <label class="checkbox-label">
                        <input type="checkbox" name="genre_ids" value="${genre.id}"
                            ${selected.includes(String(genre.id)) ? 'checked' : ''}>
                        <span>${escapeHtml(genre.name)}</span>
                    </label>
                `,
            )
            .join('');
    }

    async function loadMembers() {
        try {
            const response = await apiRequest('/api/members');

            state.members = response.data || [];
            renderMemberOptions();
            renderMembers(response);
        } catch (error) {
            setTableMessage('members-table-body', 3, 'No se pudieron cargar los miembros.');
            notify(error.message, 'error');
        }
    }

    function renderMembers(response) {
        const body = byId('members-table-body');

        byId('members-count').textContent = `${state.members.length} registros`;

        if (!state.members.length) {
            setTableMessage('members-table-body', 3, 'No hay miembros registrados.');
            return;
        }

        body.innerHTML = state.members
            .map(
                (member) => `
                    <tr>
                        <td>
                            <div class="primary-cell">${escapeHtml(member.name)}</div>
                            <div class="secondary-cell">ID ${member.id}</div>
                        </td>
                        <td>${escapeHtml(member.phone || 'Sin teléfono')}</td>
                        <td class="actions-column">
                            <div class="action-group">
                                <button type="button" class="table-action" data-action="member-detail"
                                    data-id="${member.id}">Ver</button>
                                <button type="button" class="table-action" data-action="member-edit"
                                    data-id="${member.id}">Editar</button>
                                <button type="button" class="table-action is-danger" data-action="member-delete"
                                    data-id="${member.id}">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                `,
            )
            .join('');
    }

    function renderMemberOptions() {
        const filterSelect = byId('loan-member');
        const formSelect = byId('loan-form-member');
        const selectedFilterId = state.loanFilters.member_id;
        const selectedFormId = formSelect.value;
        const options = state.members
            .map(
                (member) =>
                    `<option value="${member.id}">${escapeHtml(member.name)}</option>`,
            )
            .join('');

        filterSelect.innerHTML = `<option value="">Todos</option>${options}`;
        formSelect.innerHTML = `<option value="">Selecciona un miembro</option>${options}`;
        filterSelect.value = selectedFilterId;
        formSelect.value = selectedFormId || '';
    }

    async function loadAuthors(url = state.authorPageUrl) {
        try {
            state.authorPageUrl = url || '/api/authors';
            const response = await apiRequest(state.authorPageUrl);

            state.authors = response.data || [];
            renderAuthors(response);
        } catch (error) {
            setTableMessage('authors-table-body', 3, 'No se pudieron cargar los autores.');
            notify(error.message, 'error');
        }
    }

    function renderAuthors(response) {
        const body = byId('authors-table-body');

        byId('authors-count').textContent = `${response.meta?.total ?? state.authors.length} registros`;

        if (!state.authors.length) {
            setTableMessage('authors-table-body', 3, 'No hay autores registrados.');
            renderPagination('authors-pagination', response, loadAuthors);
            return;
        }

        body.innerHTML = state.authors
            .map(
                (author) => `
                    <tr>
                        <td>
                            <div class="primary-cell">${escapeHtml(author.name)}</div>
                            <div class="secondary-cell">ID ${author.id}</div>
                        </td>
                        <td>${formatDate(author.created_at)}</td>
                        <td class="actions-column">
                            <div class="action-group">
                                <button type="button" class="table-action" data-action="author-detail"
                                    data-id="${author.id}">Ver</button>
                                <button type="button" class="table-action" data-action="author-edit"
                                    data-id="${author.id}">Editar</button>
                                <button type="button" class="table-action is-danger" data-action="author-delete"
                                    data-id="${author.id}">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                `,
            )
            .join('');

        renderPagination('authors-pagination', response, loadAuthors);
    }

    async function loadBooks(url = state.bookPageUrl || buildBooksUrl()) {
        try {
            state.bookPageUrl = url;
            const response = await apiRequest(url);

            renderBooks(response);
        } catch (error) {
            setTableMessage('books-table-body', 4, 'No se pudieron cargar los libros.');
            notify(error.message, 'error');
        }
    }

    function buildBooksUrl() {
        return `/api/books${buildQuery(state.bookFilters)}`;
    }

    function renderBooks(response) {
        const books = response.data || [];
        const body = byId('books-table-body');

        byId('books-count').textContent = `${response.meta?.total ?? books.length} registros`;

        if (!books.length) {
            setTableMessage('books-table-body', 4, 'No hay libros registrados.');
            renderPagination('books-pagination', response, loadBooks);
            return;
        }

        body.innerHTML = books
            .map((book) => {
                const genres = book.genres?.length
                    ? book.genres
                          .map((genre) => `<span class="tag">${escapeHtml(genre.name)}</span>`)
                          .join('')
                    : '<span class="muted-text">Sin géneros</span>';

                return `
                    <tr>
                        <td>
                            <div class="primary-cell">${escapeHtml(book.title)}</div>
                            <div class="secondary-cell">${escapeHtml(book.isbn || 'Sin ISBN')}</div>
                        </td>
                        <td>${escapeHtml(book.author?.name || 'Sin autor')}</td>
                        <td><div class="tag-list">${genres}</div></td>
                        <td class="actions-column">
                            <div class="action-group">
                                <button type="button" class="table-action" data-action="book-detail"
                                    data-id="${book.id}">Ver</button>
                                <button type="button" class="table-action" data-action="book-edit"
                                    data-id="${book.id}">Editar</button>
                                <button type="button" class="table-action is-danger" data-action="book-delete"
                                    data-id="${book.id}">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                `;
            })
            .join('');

        renderPagination('books-pagination', response, loadBooks);
    }

    async function loadLoans(url = state.loanPageUrl || buildLoansUrl()) {
        try {
            state.loanPageUrl = url;
            const response = await apiRequest(url);

            renderLoans(response);
        } catch (error) {
            setTableMessage('loans-table-body', 5, 'No se pudieron cargar los préstamos.');
            notify(error.message, 'error');
        }
    }

    function buildLoansUrl() {
        return `/api/loans${buildQuery(state.loanFilters)}`;
    }

    function renderLoans(response) {
        const loans = response.data || [];
        const body = byId('loans-table-body');

        byId('loans-count').textContent = `${response.meta?.total ?? loans.length} registros`;

        if (!loans.length) {
            setTableMessage('loans-table-body', 5, 'No hay préstamos que coincidan con los filtros.');
            renderPagination('loans-pagination', response, loadLoans);
            return;
        }

        body.innerHTML = loans
            .map(
                (loan) => `
                    <tr>
                        <td>
                            <div class="primary-cell">${escapeHtml(loan.member?.name || '—')}</div>
                            <div class="secondary-cell">${escapeHtml(loan.member?.phone || 'Sin teléfono')}</div>
                        </td>
                        <td>
                            <div class="primary-cell">${escapeHtml(loan.book?.title || '—')}</div>
                            <div class="secondary-cell">${escapeHtml(loan.book?.author?.name || 'Sin autor')}</div>
                        </td>
                        <td>
                            <div class="date-stack">
                                <span><strong>Préstamo:</strong> ${formatDate(loan.borrowed_at)}</span>
                                <span><strong>Vence:</strong> ${formatDate(loan.due_at)}</span>
                                <span><strong>Devuelto:</strong> ${formatDate(loan.returned_at)}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge ${loan.is_active ? 'badge-active' : 'badge-returned'}">
                                ${loan.is_active ? 'Activo' : 'Devuelto'}
                            </span>
                        </td>
                        <td class="actions-column">
                            <div class="action-group">
                                ${
                                    loan.is_active
                                        ? `<button type="button" class="table-action is-success"
                                            data-action="return-loan" data-id="${loan.id}">
                                            Devolver
                                        </button>`
                                        : ''
                                }
                                <button type="button" class="table-action" data-action="loan-edit"
                                    data-id="${loan.id}">Editar</button>
                                <button type="button" class="table-action is-danger" data-action="loan-delete"
                                    data-id="${loan.id}">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                `,
            )
            .join('');

        renderPagination('loans-pagination', response, loadLoans);
    }

    function toLocalInputValue(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

        return localDate.toISOString().slice(0, 16);
    }

    function toApiDateTime(value) {
        if (!value) {
            return null;
        }

        return value.length === 16 ? `${value.replace('T', ' ')}:00` : value.replace('T', ' ');
    }

    function resetLoanForm() {
        byId('loan-form').reset();
        byId('loan-form-id').value = '';
        byId('loan-form-eyebrow').textContent = 'Nuevo registro';
        byId('loan-form-title').textContent = 'Crear préstamo';
        byId('loan-submit').textContent = 'Crear préstamo';
        byId('loan-borrowed-at').value = toLocalInputValue(new Date());

        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 14);
        byId('loan-due-at').value = toLocalInputValue(dueDate);
        byId('loan-returned-at').value = '';
        renderMemberOptions();
        renderBookOptions();
        clearFormErrors('loan-form-errors');
    }

    async function editLoan(id) {
        try {
            switchView('loans-view');
            const response = await apiRequest(`/api/loans/${id}`);
            const loan = response.data;

            byId('loan-form-id').value = loan.id;
            renderMemberOptions();
            renderBookOptions();
            byId('loan-form-member').value = loan.member_id;
            byId('loan-form-book').value = loan.book_id;
            byId('loan-borrowed-at').value = toLocalInputValue(loan.borrowed_at);
            byId('loan-due-at').value = toLocalInputValue(loan.due_at);
            byId('loan-returned-at').value = toLocalInputValue(loan.returned_at);
            byId('loan-form-eyebrow').textContent = 'Edición';
            byId('loan-form-title').textContent = 'Editar préstamo';
            byId('loan-submit').textContent = 'Guardar cambios';
            clearFormErrors('loan-form-errors');
            byId('loan-form-member').focus();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function deleteLoan(id) {
        if (!window.confirm('¿Quieres eliminar este préstamo?')) {
            return;
        }

        try {
            await apiRequest(`/api/loans/${id}`, { method: 'DELETE' });
            notify('Préstamo eliminado correctamente.');
            await Promise.all([loadLoans(buildLoansUrl()), loadStatistics()]);
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function resetAuthorForm() {
        byId('author-form').reset();
        byId('author-id').value = '';
        byId('author-form-eyebrow').textContent = 'Nuevo registro';
        byId('author-form-title').textContent = 'Crear autor';
        byId('author-submit').textContent = 'Crear autor';
        clearFormErrors('author-form-errors');
    }

    async function editAuthor(id) {
        try {
            switchView('authors-view');
            const response = await apiRequest(`/api/authors/${id}`);
            const author = response.data;

            byId('author-id').value = author.id;
            byId('author-name').value = author.name;
            byId('author-form-eyebrow').textContent = 'Edición';
            byId('author-form-title').textContent = 'Editar autor';
            byId('author-submit').textContent = 'Guardar cambios';
            clearFormErrors('author-form-errors');
            byId('author-name').focus();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function showAuthor(id) {
        try {
            const response = await apiRequest(`/api/authors/${id}`);
            const author = response.data;

            openModal(
                `Autor · ${author.name}`,
                `
                    <dl class="detail-list">
                        <div><dt>ID</dt><dd>${author.id}</dd></div>
                        <div><dt>Nombre</dt><dd>${escapeHtml(author.name)}</dd></div>
                        <div><dt>Creado</dt><dd>${formatDate(author.created_at)}</dd></div>
                        <div><dt>Actualizado</dt><dd>${formatDate(author.updated_at)}</dd></div>
                    </dl>
                `,
            );
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function deleteAuthor(id) {
        if (!window.confirm('¿Quieres eliminar este autor?')) {
            return;
        }

        try {
            await apiRequest(`/api/authors/${id}`, { method: 'DELETE' });
            notify('Autor eliminado correctamente.');
            await Promise.all([loadAuthors('/api/authors'), loadAuthorOptions(), loadStatistics()]);
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function resetMemberForm() {
        byId('member-form').reset();
        byId('member-id').value = '';
        byId('member-form-eyebrow').textContent = 'Nuevo registro';
        byId('member-form-title').textContent = 'Crear miembro';
        byId('member-submit').textContent = 'Crear miembro';
        clearFormErrors('member-form-errors');
    }

    async function editMember(id) {
        try {
            switchView('members-view');
            const response = await apiRequest(`/api/members/${id}`);
            const member = response.data;

            byId('member-id').value = member.id;
            byId('member-name').value = member.name;
            byId('member-phone').value = member.phone || '';
            byId('member-form-eyebrow').textContent = 'Edición';
            byId('member-form-title').textContent = 'Editar miembro';
            byId('member-submit').textContent = 'Guardar cambios';
            clearFormErrors('member-form-errors');
            byId('member-name').focus();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function showMember(id) {
        try {
            const response = await apiRequest(`/api/members/${id}`);
            const member = response.data;

            openModal(
                `Miembro · ${member.name}`,
                `
                    <dl class="detail-list">
                        <div><dt>ID</dt><dd>${member.id}</dd></div>
                        <div><dt>Nombre</dt><dd>${escapeHtml(member.name)}</dd></div>
                        <div><dt>Teléfono</dt><dd>${escapeHtml(member.phone || '—')}</dd></div>
                        <div><dt>Creado</dt><dd>${formatDate(member.created_at)}</dd></div>
                        <div><dt>Actualizado</dt><dd>${formatDate(member.updated_at)}</dd></div>
                    </dl>
                `,
            );
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function deleteMember(id) {
        if (!window.confirm('¿Quieres eliminar este miembro?')) {
            return;
        }

        try {
            await apiRequest(`/api/members/${id}`, { method: 'DELETE' });
            notify('Miembro eliminado correctamente.');
            await Promise.all([loadMembers(), loadStatistics()]);
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function resetBookForm() {
        byId('book-form').reset();
        byId('book-id').value = '';
        byId('book-form-eyebrow').textContent = 'Nuevo registro';
        byId('book-form-title').textContent = 'Crear libro';
        byId('book-submit').textContent = 'Crear libro';
        renderAuthorOptions();
        renderGenreOptions();
        clearFormErrors('book-form-errors');
    }

    async function editBook(id) {
        try {
            switchView('books-view');
            const response = await apiRequest(`/api/books/${id}`);
            const book = response.data;

            byId('book-id').value = book.id;
            byId('book-title').value = book.title;
            byId('book-isbn').value = book.isbn || '';
            byId('book-year').value = book.published_year || '';
            renderAuthorOptions(book.author?.id);
            renderGenreOptions((book.genres || []).map((genre) => genre.id));
            byId('book-form-eyebrow').textContent = 'Edición';
            byId('book-form-title').textContent = 'Editar libro';
            byId('book-submit').textContent = 'Guardar cambios';
            clearFormErrors('book-form-errors');
            byId('book-title').focus();
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function showBook(id) {
        try {
            const response = await apiRequest(`/api/books/${id}`);
            const book = response.data;
            const genres = (book.genres || []).map((genre) => escapeHtml(genre.name)).join(', ');

            openModal(
                `Libro · ${book.title}`,
                `
                    <dl class="detail-list">
                        <div><dt>ID</dt><dd>${book.id}</dd></div>
                        <div><dt>Título</dt><dd>${escapeHtml(book.title)}</dd></div>
                        <div><dt>Autor</dt><dd>${escapeHtml(book.author?.name || '—')}</dd></div>
                        <div><dt>ISBN</dt><dd>${escapeHtml(book.isbn || '—')}</dd></div>
                        <div><dt>Año</dt><dd>${book.published_year || '—'}</dd></div>
                        <div><dt>Géneros</dt><dd>${genres || '—'}</dd></div>
                    </dl>
                `,
            );
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function deleteBook(id) {
        if (!window.confirm('¿Quieres eliminar este libro?')) {
            return;
        }

        try {
            await apiRequest(`/api/books/${id}`, { method: 'DELETE' });
            notify('Libro eliminado correctamente.');
            await Promise.all([loadBooks(buildBooksUrl()), loadStatistics()]);
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    async function returnLoan(id) {
        if (!window.confirm('¿Confirmas que este préstamo ha sido devuelto?')) {
            return;
        }

        try {
            await apiRequest(`/api/loans/${id}/return`, { method: 'POST' });
            notify('Devolución registrada correctamente.');
            await Promise.all([loadLoans(buildLoansUrl()), loadStatistics()]);
        } catch (error) {
            notify(error.message, 'error');
        }
    }

    function openModal(title, body) {
        const modal = byId('detail-modal');

        byId('modal-title').textContent = title;
        byId('modal-body').innerHTML = body;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        const modal = byId('detail-modal');

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    async function submitAuthorForm(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const id = byId('author-id').value;
        const name = byId('author-name').value.trim();

        clearFormErrors('author-form-errors');
        setFormBusy(form, true);

        try {
            await apiRequest(id ? `/api/authors/${id}` : '/api/authors', {
                method: id ? 'PATCH' : 'POST',
                body: { name },
            });

            notify(id ? 'Autor actualizado correctamente.' : 'Autor creado correctamente.');
            resetAuthorForm();
            await Promise.all([loadAuthors('/api/authors'), loadAuthorOptions(), loadStatistics()]);
        } catch (error) {
            renderFormErrors('author-form-errors', error);
        } finally {
            setFormBusy(form, false);
        }
    }

    async function submitMemberForm(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const id = byId('member-id').value;
        const payload = {
            name: byId('member-name').value.trim(),
            phone: byId('member-phone').value.trim() || null,
        };

        clearFormErrors('member-form-errors');
        setFormBusy(form, true);

        try {
            await apiRequest(id ? `/api/members/${id}` : '/api/members', {
                method: id ? 'PATCH' : 'POST',
                body: payload,
            });

            notify(id ? 'Miembro actualizado correctamente.' : 'Miembro creado correctamente.');
            resetMemberForm();
            await Promise.all([loadMembers(), loadStatistics()]);
        } catch (error) {
            renderFormErrors('member-form-errors', error);
        } finally {
            setFormBusy(form, false);
        }
    }

    async function submitBookForm(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const id = byId('book-id').value;
        const year = byId('book-year').value.trim();
        const genreIds = [...document.querySelectorAll('input[name="genre_ids"]:checked')].map(
            (input) => Number(input.value),
        );
        const payload = {
            author_id: Number(byId('book-author').value),
            title: byId('book-title').value.trim(),
            isbn: byId('book-isbn').value.trim() || null,
            published_year: year ? Number(year) : null,
            genre_ids: genreIds,
        };

        clearFormErrors('book-form-errors');
        setFormBusy(form, true);

        try {
            await apiRequest(id ? `/api/books/${id}` : '/api/books', {
                method: id ? 'PATCH' : 'POST',
                body: payload,
            });

            notify(id ? 'Libro actualizado correctamente.' : 'Libro creado correctamente.');
            resetBookForm();
            await Promise.all([loadBooks(buildBooksUrl()), loadStatistics()]);
        } catch (error) {
            renderFormErrors('book-form-errors', error);
        } finally {
            setFormBusy(form, false);
        }
    }

    async function submitLoanForm(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const id = byId('loan-form-id').value;
        const payload = {
            member_id: Number(byId('loan-form-member').value),
            book_id: Number(byId('loan-form-book').value),
            borrowed_at: toApiDateTime(byId('loan-borrowed-at').value),
            due_at: toApiDateTime(byId('loan-due-at').value),
            returned_at: toApiDateTime(byId('loan-returned-at').value),
        };

        clearFormErrors('loan-form-errors');
        setFormBusy(form, true);

        try {
            await apiRequest(id ? `/api/loans/${id}` : '/api/loans', {
                method: id ? 'PATCH' : 'POST',
                body: payload,
            });

            notify(id ? 'Préstamo actualizado correctamente.' : 'Préstamo creado correctamente.');
            resetLoanForm();
            await Promise.all([
                loadLoans(buildLoansUrl()),
                loadBookOptions(),
                loadStatistics(),
            ]);
        } catch (error) {
            renderFormErrors('loan-form-errors', error);
        } finally {
            setFormBusy(form, false);
        }
    }

    function updateBookFilters() {
        state.bookFilters = {
            sort: byId('book-sort').value,
            direction: byId('book-direction').value,
            per_page: byId('book-per-page').value,
        };
    }

    function updateLoanFilters() {
        state.loanFilters = {
            active: byId('loan-active').value,
            member_id: byId('loan-member').value,
            sort: byId('loan-sort').value,
            direction: byId('loan-direction').value,
            per_page: byId('loan-per-page').value,
        };
    }

    async function refreshAll() {
        await Promise.all([
            checkHealth(),
            loadAuthorOptions(),
            loadBookOptions(),
            loadGenres(),
            loadMembers(),
            loadStatistics(),
            loadAuthors('/api/authors'),
            loadBooks(buildBooksUrl()),
            loadLoans(buildLoansUrl()),
        ]);
    }

    document.addEventListener('click', async (event) => {
        const viewTarget = event.target.closest('[data-view-target]');

        if (viewTarget) {
            switchView(viewTarget.dataset.viewTarget);
            return;
        }

        const actionTarget = event.target.closest('[data-action]');

        if (!actionTarget) {
            return;
        }

        const { action, id } = actionTarget.dataset;

        if (action === 'refresh-all') {
            await refreshAll();
        } else if (action === 'reload-authors') {
            await Promise.all([loadAuthors('/api/authors'), loadAuthorOptions(), loadStatistics()]);
        } else if (action === 'reload-members') {
            await Promise.all([loadMembers(), loadStatistics()]);
        } else if (action === 'reload-books') {
            await Promise.all([loadBooks(buildBooksUrl()), loadAuthorOptions(), loadGenres(), loadStatistics()]);
        } else if (action === 'reload-loans') {
            await Promise.all([
                loadLoans(buildLoansUrl()),
                loadMembers(),
                loadBookOptions(),
                loadStatistics(),
            ]);
        } else if (action === 'reset-author-form') {
            resetAuthorForm();
        } else if (action === 'reset-member-form') {
            resetMemberForm();
        } else if (action === 'reset-book-form') {
            resetBookForm();
        } else if (action === 'reset-loan-form') {
            resetLoanForm();
        } else if (action === 'author-detail') {
            await showAuthor(id);
        } else if (action === 'author-edit') {
            await editAuthor(id);
        } else if (action === 'author-delete') {
            await deleteAuthor(id);
        } else if (action === 'member-detail') {
            await showMember(id);
        } else if (action === 'member-edit') {
            await editMember(id);
        } else if (action === 'member-delete') {
            await deleteMember(id);
        } else if (action === 'book-detail') {
            await showBook(id);
        } else if (action === 'book-edit') {
            await editBook(id);
        } else if (action === 'book-delete') {
            await deleteBook(id);
        } else if (action === 'loan-edit') {
            await editLoan(id);
        } else if (action === 'loan-delete') {
            await deleteLoan(id);
        } else if (action === 'return-loan') {
            await returnLoan(id);
        } else if (action === 'close-modal') {
            closeModal();
        }
    });

    byId('author-form').addEventListener('submit', submitAuthorForm);
    byId('member-form').addEventListener('submit', submitMemberForm);
    byId('book-form').addEventListener('submit', submitBookForm);
    byId('loan-form').addEventListener('submit', submitLoanForm);

    byId('book-filters').addEventListener('submit', async (event) => {
        event.preventDefault();
        updateBookFilters();
        await loadBooks(buildBooksUrl());
    });

    byId('loan-filters').addEventListener('submit', async (event) => {
        event.preventDefault();
        updateLoanFilters();
        await loadLoans(buildLoansUrl());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    const initializeLibrary = () => {
        switchView('dashboard-view');
        resetLoanForm();
        refreshAll();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLibrary);
    } else {
        initializeLibrary();
    }
}
