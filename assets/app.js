(() => {
    'use strict';

    const state = {
        config: null,
        users: [],
        media: new Map(),
        selectedMedia: [],
        mediaCursor: null,
        statusTimer: null
    };

    const $ = (selector) => document.querySelector(selector);

    const els = {
        accountSelect: $('#accountSelect'),
        accountForm: $('#accountForm'),
        accountEntityId: $('#accountEntityId'),
        accountIdInput: $('#accountIdInput'),
        userIdInput: $('#userIdInput'),
        accountSaveBtn: $('#accountSaveBtn'),
        accountCancelBtn: $('#accountCancelBtn'),
        accountTableBody: $('#accountTableBody'),
        scheduleAt: $('#scheduleAt'),
        websiteUrl: $('#websiteUrl'),
        headline: $('#headline'),
        selectedStrip: $('#selectedStrip'),
        mediaCount: $('#mediaCount'),
        mediaGrid: $('#mediaGrid'),
        mediaSearch: $('#mediaSearch'),
        loadMoreMediaBtn: $('#loadMoreMediaBtn'),
        saveBtn: $('#saveBtn'),
        status: $('#status')
    };

    async function api(url, options = {}) {
        const response = await fetch(url, {
            cache: 'no-store',
            headers: {
                'Content-Type': 'application/json',
                ...(options.headers || {})
            },
            ...options
        });

        let json;
        try {
            json = await response.json();
        } catch {
            throw new Error(`Local API returned HTTP ${response.status} with invalid JSON.`);
        }

        if (!response.ok) {
            const message =
                json?.errors?.[0]?.message ||
                json?.error ||
                json?.message ||
                `HTTP ${response.status}`;

            const detail = json?.errors?.[0]?.details || json?.hint || '';
            const error = new Error(detail ? `${message} — ${detail}` : message);
            throw error;
        }

        return json;
    }

    function toast(message, type = '') {
        clearTimeout(state.statusTimer);
        els.status.className = `status show ${type}`.trim();
        els.status.textContent = message;
        state.statusTimer = setTimeout(() => {
            els.status.className = 'status';
        }, 4200);
    }

    function configMessage(config) {
        const auth = config?.auth || {};

        if (!auth.ct0_configured) {
            return 'The Cookie in config.php does not contain ct0.';
        }

        if (!auth.bearer_configured) {
            return 'Missing bearer token in config.php.';
        }

        return '';
    }

    function setButtonLoading(button, loading, label) {
        if (loading) {
            button.dataset.oldText = button.textContent;
            button.disabled = true;
            button.innerHTML = `<span class="spinner"></span>${label || 'Loading…'}`;
        } else {
            button.disabled = false;
            button.textContent = button.dataset.oldText || label || 'Done';
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function mediaAssetUrl(src) {
        return `api.php?action=asset&url=${encodeURIComponent(src)}`;
    }

    function mediaPreviewHtml(item) {
        const src = item.media_type === 'VIDEO'
            ? (item.poster_media_url || item.media_url || '')
            : (item.media_url || item.poster_media_url || '');
        const label = escapeHtml(item.name || item.file_name || item.media_key || 'Media');
        const assetSrc = src ? escapeHtml(mediaAssetUrl(src)) : '';

        if (item.media_type === 'IMAGE' || item.media_type === 'GIF') {
            return src
                ? `<img src="${assetSrc}" alt="${label}" loading="lazy">`
                : `<div class="media-fallback">${label}</div>`;
        }

        if (item.media_type === 'VIDEO') {
            if (item.poster_media_url) {
                return `<img src="${assetSrc}" alt="${label}" loading="lazy">`;
            }

            return src
                ? `<video src="${assetSrc}" muted preload="metadata"></video>`
                : `<div class="media-fallback">VIDEO<br>${label}</div>`;
        }

        return `<div class="media-fallback">${label}</div>`;
    }

    function renderAccountSelector() {
        els.accountSelect.innerHTML = state.config.accounts.map(account => `
            <option value="${account.entity_id}" ${account.entity_id === state.config.entity_id ? 'selected' : ''}>
                Account ${escapeHtml(account.account_id)} · User ${escapeHtml(account.user_id)}
            </option>
        `).join('');
    }

    function renderUserTable() {
        els.accountTableBody.innerHTML = state.users.map(user => `
            <tr>
                <td>${user.entity_id}</td>
                <td>${escapeHtml(user.account_id)}</td>
                <td>${escapeHtml(user.user_id)}</td>
                <td>
                    <div class="table-actions">
                        <button class="small-btn" type="button" data-account-edit="${user.entity_id}">Edit</button>
                        <button class="small-btn danger-btn" type="button" data-account-delete="${user.entity_id}">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function resetAccountForm() {
        els.accountForm.reset();
        els.accountEntityId.value = '';
        els.accountSaveBtn.textContent = 'Add account';
        els.accountCancelBtn.hidden = true;
    }

    function applyAccount(entityId, reloadMedia = false) {
        const account = state.config.accounts.find(item => item.entity_id === Number(entityId));
        if (!account) return false;

        state.config.entity_id = account.entity_id;
        state.config.account_id = account.account_id;
        state.config.user_id = account.user_id;
        els.accountSelect.value = String(account.entity_id);

        if (reloadMedia) {
            resetForm();
            loadMedia(true);
        }

        return true;
    }

    async function loadUsers(preferredEntityId = null) {
        const json = await api('api/users');
        state.users = json.data || [];
        state.config.accounts = state.users.map(user => ({
            entity_id: Number(user.entity_id),
            account_id: user.account_id,
            user_id: user.user_id
        }));

        const preferred = Number(preferredEntityId || state.config.entity_id);
        const selected = state.config.accounts.some(account => account.entity_id === preferred)
            ? preferred
            : state.config.accounts[0]?.entity_id;

        if (selected) {
            state.config.entity_id = selected;
        }

        renderAccountSelector();
        renderUserTable();

        if (selected) applyAccount(selected);
    }

    function validateSelection(nextItems) {
        if (nextItems.length > 4) {
            throw new Error('X allows at most 4 images.');
        }

        const nonImages = nextItems.filter(item => item.media_type !== 'IMAGE');

        if (nonImages.length > 0 && nextItems.length > 1) {
            throw new Error('GIF/video must be selected alone. X allows 1 GIF or 1 video.');
        }
    }

    function toggleMedia(key) {
        const item = state.media.get(key);
        if (!item) return;

        const index = state.selectedMedia.findIndex(media => media.media_key === key);

        if (index >= 0) {
            state.selectedMedia.splice(index, 1);
        } else {
            const next = [...state.selectedMedia, item];

            try {
                validateSelection(next);
            } catch (error) {
                toast(error.message, 'error');
                return;
            }

            state.selectedMedia = next;
        }

        renderMediaGrid();
        renderSelected();
    }

    function renderSelected() {
        els.mediaCount.textContent = `${state.selectedMedia.length} selected`;

        if (state.selectedMedia.length === 0) {
            els.selectedStrip.innerHTML =
                '<span class="selected-empty">Choose media from the library.</span>';
            return;
        }

        els.selectedStrip.innerHTML = state.selectedMedia.map(item => `
            <div class="selected-thumb" title="${escapeHtml(item.name || item.file_name || item.media_key)}">
                ${mediaPreviewHtml(item)}
                <button type="button" data-remove-media="${escapeHtml(item.media_key)}">×</button>
            </div>
        `).join('');
    }

    function renderMediaGrid() {
        const items = [...state.media.values()];

        if (items.length === 0) {
            els.mediaGrid.innerHTML = '<div class="empty" style="grid-column:1/-1">No media found.</div>';
            return;
        }

        const selectedKeys = new Set(state.selectedMedia.map(item => item.media_key));

        els.mediaGrid.innerHTML = items.map(item => `
            <button
                type="button"
                class="media-card ${selectedKeys.has(item.media_key) ? 'selected' : ''}"
                data-media-key="${escapeHtml(item.media_key)}"
                title="${escapeHtml(item.name || item.file_name || item.media_key)}"
            >
                ${mediaPreviewHtml(item)}
                <span class="type-pill">${escapeHtml(item.media_type || 'MEDIA')}</span>
                <span class="check-mark">✓</span>
            </button>
        `).join('');
    }

    async function loadMedia(reset = false) {
        if (reset) {
            state.media.clear();
            state.mediaCursor = null;
            renderMediaGrid();
        }

        const params = new URLSearchParams({
            action: 'media',
            count: '50',
            entity_id: String(state.config.entity_id)
        });

        const q = els.mediaSearch.value.trim();
        if (q) params.set('q', q);
        if (!reset && state.mediaCursor) params.set('cursor', state.mediaCursor);

        try {
            const json = await api(`api.php?${params}`);
            (json.data || []).forEach(item => state.media.set(item.media_key, item));
            state.mediaCursor = json.next_cursor || null;

            renderMediaGrid();
            renderSelected();
            els.loadMoreMediaBtn.style.display = state.mediaCursor ? '' : 'none';
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    async function ensureMediaLoaded(keys) {
        const missing = keys.filter(key => !state.media.has(key));

        for (const key of missing) {
            try {
                const params = new URLSearchParams({
                    action: 'media',
                    id: key,
                    entity_id: String(state.config.entity_id)
                });
                const json = await api(`api.php?${params}`);
                if (json.data?.media_key) {
                    state.media.set(json.data.media_key, json.data);
                }
            } catch (error) {
                console.warn('Cannot load media', key, error);
            }
        }
    }

    function resetForm() {
        state.selectedMedia = [];

        els.scheduleAt.value = '';
        els.websiteUrl.value = '';
        els.headline.value = '';

        renderSelected();
        renderMediaGrid();
    }

    function getFormPayload() {
        const websiteUrl = els.websiteUrl.value.trim();
        const headline = els.headline.value.trim();

        if ((websiteUrl || headline) && (!websiteUrl || !headline)) {
            throw new Error('Website URL and Headline are both required for a website card.');
        }

        if (websiteUrl && state.selectedMedia.length === 0) {
            throw new Error('Choose at least one media item for the website card.');
        }

        if (websiteUrl && state.selectedMedia.length > 1) {
            throw new Error('Website card uses one media item. Select only one media.');
        }

        validateSelection(state.selectedMedia);

        return {
            scheduled_at: els.scheduleAt.value,
            website_url: websiteUrl,
            headline,
            media_keys: state.selectedMedia.map(item => item.media_key)
        };
    }

    async function savePost() {
        let payload;

        try {
            payload = getFormPayload();
        } catch (error) {
            toast(error.message, 'error');
            return;
        }

        setButtonLoading(els.saveBtn, true, 'Scheduling…');

        try {
            const params = new URLSearchParams({
                action: 'tweet',
                entity_id: String(state.config.entity_id)
            });
            const json = await api(`api.php?${params}`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });

            toast(
                `Post scheduled${json.data?.id ? `: ${json.data.id}` : ''}${json.data?.scheduled_at ? ` at ${json.data.scheduled_at}` : ''}${json.data?.verified ? ' · verified' : ''}.`,
                'success'
            );

            resetForm();
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            setButtonLoading(els.saveBtn, false);
        }
    }

    async function init() {
        renderSelected();

        try {
            const json = await api('api.php?action=config');
            state.config = json.data;
            await loadUsers(state.config.entity_id);

            if (!state.config.authenticated) {
                const message = configMessage(state.config);
                els.mediaGrid.innerHTML = `<div class="empty" style="grid-column:1/-1">${escapeHtml(message)}</div>`;
                toast(message, 'error');
                return;
            }
        } catch (error) {
            toast(error.message, 'error');
            return;
        }

        loadMedia(true);
    }

    els.mediaGrid.addEventListener('click', event => {
        const card = event.target.closest('[data-media-key]');
        if (card) toggleMedia(card.dataset.mediaKey);
    });

    els.selectedStrip.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-media]');
        if (button) toggleMedia(button.dataset.removeMedia);
    });

    $('#refreshMediaBtn').addEventListener('click', () => loadMedia(true));
    $('#mediaSearchBtn').addEventListener('click', () => loadMedia(true));
    els.mediaSearch.addEventListener('keydown', event => {
        if (event.key === 'Enter') loadMedia(true);
    });
    els.loadMoreMediaBtn.addEventListener('click', () => loadMedia(false));

    els.accountSelect.addEventListener('change', () => {
        if (!applyAccount(Number(els.accountSelect.value), true)) {
            toast('Selected account was not found.', 'error');
        }
    });

    els.accountForm.addEventListener('submit', async event => {
        event.preventDefault();

        const entityId = Number(els.accountEntityId.value || 0);
        const url = entityId ? `api/users/${entityId}` : 'api/users';

        setButtonLoading(els.accountSaveBtn, true, entityId ? 'Saving…' : 'Adding…');

        try {
            const json = await api(url, {
                method: entityId ? 'PUT' : 'POST',
                body: JSON.stringify({
                    account_id: els.accountIdInput.value.trim(),
                    user_id: els.userIdInput.value.trim()
                })
            });

            resetAccountForm();
            await loadUsers(json.data.entity_id);
            applyAccount(json.data.entity_id, true);
            toast(entityId ? 'Account updated.' : 'Account added.', 'success');
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            setButtonLoading(els.accountSaveBtn, false, entityId ? 'Save account' : 'Add account');
        }
    });

    els.accountCancelBtn.addEventListener('click', resetAccountForm);

    els.accountTableBody.addEventListener('click', async event => {
        const editButton = event.target.closest('[data-account-edit]');
        const deleteButton = event.target.closest('[data-account-delete]');

        if (editButton) {
            const user = state.users.find(item => Number(item.entity_id) === Number(editButton.dataset.accountEdit));
            if (!user) return;

            els.accountEntityId.value = user.entity_id;
            els.accountIdInput.value = user.account_id;
            els.userIdInput.value = user.user_id;
            els.accountSaveBtn.textContent = 'Save account';
            els.accountCancelBtn.hidden = false;
            els.accountIdInput.focus();
            return;
        }

        if (!deleteButton) return;

        const entityId = Number(deleteButton.dataset.accountDelete);
        const user = state.users.find(item => Number(item.entity_id) === entityId);
        if (!user || !window.confirm(`Delete account ${user.account_id}?`)) return;

        try {
            await api(`api/users/${entityId}`, { method: 'DELETE' });
            resetAccountForm();
            await loadUsers(state.config.entity_id === entityId ? null : state.config.entity_id);
            applyAccount(state.config.entity_id, true);
            toast('Account deleted.', 'success');
        } catch (error) {
            toast(error.message, 'error');
        }
    });

    els.saveBtn.addEventListener('click', savePost);

    init();
})();
