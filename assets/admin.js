(() => {
    'use strict';

    const state = {
        users: [],
        statusTimer: null
    };

    const $ = selector => document.querySelector(selector);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const canEditAccounts = document.body.dataset.canEditAccounts === 'true';
    const els = {
        form: $('#accountForm'),
        formTitle: $('#accountFormTitle'),
        entityId: $('#accountEntityId'),
        accountId: $('#accountIdInput'),
        userId: $('#userIdInput'),
        cookie: $('#accountCookieInput'),
        saveBtn: $('#accountSaveBtn'),
        tableBody: $('#accountTableBody'),
        status: $('#status')
    };

    async function api(url, options = {}) {
        const response = await fetch(url, {
            cache: 'no-store',
            headers: {
                'Content-Type': 'application/json',
                'X-App-CSRF-Token': csrfToken,
                ...(options.headers || {})
            },
            ...options
        });
        const json = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(json.error || json.message || `HTTP ${response.status}`);
        }

        return json;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function toast(message, type = '') {
        clearTimeout(state.statusTimer);
        els.status.className = `status show ${type}`.trim();
        els.status.textContent = message;
        state.statusTimer = setTimeout(() => {
            els.status.className = 'status';
        }, 4200);
    }

    function setLoading(loading) {
        els.saveBtn.disabled = loading;
        els.saveBtn.textContent = loading
            ? 'Saving…'
            : (els.entityId.value ? 'Save account' : 'Add account');
    }

    function resetForm() {
        els.form.reset();
        els.entityId.value = '';
        els.formTitle.textContent = 'Add account';
        els.saveBtn.textContent = 'Add account';
    }

    function render() {
        els.tableBody.innerHTML = state.users.map(user => `
            <tr>
                <td>${user.entity_id}</td>
                <td>${escapeHtml(user.account_id)}</td>
                <td>${escapeHtml(user.user_id)}</td>
                <td><span class="session-status ${user.cookie_configured ? 'ok' : 'missing'}">${user.cookie_configured ? 'Configured' : 'Missing'}</span></td>
                <td>${escapeHtml(user.updated_at || '')}</td>
                <td>
                    ${canEditAccounts ? `<div class="table-actions">
                        <button class="small-btn" type="button" data-edit="${user.entity_id}">Edit</button>
                        <button class="small-btn danger-btn" type="button" data-delete="${user.entity_id}">Delete</button>
                    </div>` : '—'}
                </td>
            </tr>
        `).join('');
    }

    async function loadUsers() {
        const json = await api('api/users');
        state.users = json.data || [];
        render();
    }

    els.form.addEventListener('submit', async event => {
        event.preventDefault();
        const entityId = Number(els.entityId.value || 0);
        setLoading(true);

        try {
            await api(entityId ? `api/users/${entityId}` : 'api/users', {
                method: entityId ? 'PUT' : 'POST',
                body: JSON.stringify({
                    account_id: els.accountId.value.trim(),
                    user_id: els.userId.value.trim(),
                    cookie: els.cookie.value.trim()
                })
            });
            resetForm();
            await loadUsers();
            toast(entityId ? 'Account updated.' : 'Account added.', 'success');
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            setLoading(false);
        }
    });

    els.tableBody.addEventListener('click', async event => {
        const edit = event.target.closest('[data-edit]');
        const remove = event.target.closest('[data-delete]');

        if (edit) {
            const user = state.users.find(item => Number(item.entity_id) === Number(edit.dataset.edit));
            if (!user) return;

            els.entityId.value = user.entity_id;
            els.accountId.value = user.account_id;
            els.userId.value = user.user_id;
            els.cookie.value = '';
            els.formTitle.textContent = 'Edit account';
            els.saveBtn.textContent = 'Save account';
            els.accountId.focus();
            return;
        }

        if (!remove) return;

        const entityId = Number(remove.dataset.delete);
        const user = state.users.find(item => Number(item.entity_id) === entityId);
        if (!user || !window.confirm(`Delete account ${user.account_id}?`)) return;

        try {
            await api(`api/users/${entityId}`, { method: 'DELETE' });
            resetForm();
            await loadUsers();
            toast('Account deleted.', 'success');
        } catch (error) {
            toast(error.message, 'error');
        }
    });

    loadUsers().catch(error => toast(error.message, 'error'));
})();
