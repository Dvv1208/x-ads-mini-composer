(() => {
    'use strict';

    const state = {
        config: null,
        media: new Map(),
        selectedMedia: [],
        mediaCursor: null,
        statusTimer: null
    };

    const $ = (selector) => document.querySelector(selector);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const els = {
        accountSelect: $('#accountSelect'),
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
                'X-App-CSRF-Token': csrfToken,
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
            return 'The selected account does not have a valid X Cookie containing ct0. Update it in Admin.';
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

    function mediaPreviewHtml(item) {
        const src = item.media_type === 'VIDEO'
            ? (item.poster_media_url || item.media_url || '')
            : (item.media_url || item.poster_media_url || '');
        const label = escapeHtml(item.name || item.file_name || item.media_key || 'Media');
        const assetSrc = src ? escapeHtml(src) : '';

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
                Account ${escapeHtml(account.account_id)} · User ${escapeHtml(account.user_id)}${account.ct0_configured ? '' : ' · Session missing'}
            </option>
        `).join('');
    }

    function applyAccount(entityId, reloadMedia = false) {
        const account = state.config.accounts.find(item => item.entity_id === Number(entityId));
        if (!account) return false;

        state.config.entity_id = account.entity_id;
        state.config.account_id = account.account_id;
        state.config.user_id = account.user_id;
        state.config.authenticated = Boolean(account.ct0_configured && state.config.auth?.bearer_configured);
        els.accountSelect.value = String(account.entity_id);

        if (reloadMedia) {
            resetForm();

            if (!state.config.authenticated) {
                state.media.clear();
                state.mediaCursor = null;
                renderMediaGrid();
                toast('The selected account session is missing or invalid. Update its X Cookie in Admin.', 'error');
            } else {
                loadMedia(true);
            }
        }

        return true;
    }

    function validateSelection(nextItems) {
        if (nextItems.length > 1) {
            throw new Error('Website card uses one media item. Select only one media.');
        }

        const invalid = nextItems.find(item => item.validation?.selectable === false);
        if (invalid) {
            throw new Error(invalid.validation.reason || 'This media cannot be selected.');
        }
    }

    function toggleMedia(key) {
        const item = state.media.get(key);
        if (!item) return;

        if (item.validation?.selectable === false) {
            toast(item.validation.reason || 'This media cannot be selected.', 'error');
            return;
        }

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

        els.mediaGrid.innerHTML = items.map(item => {
            const validation = item.validation || {};
            const invalid = validation.selectable === false;
            const mediaKey = escapeHtml(item.media_key);
            const title = invalid
                ? `${item.media_key} — ${validation.reason || 'Unavailable'}`
                : item.media_key;

            return `
            <button
                type="button"
                class="media-card ${selectedKeys.has(item.media_key) ? 'selected' : ''} ${invalid ? 'invalid' : ''}"
                data-media-key="${mediaKey}"
                aria-disabled="${invalid ? 'true' : 'false'}"
                title="${escapeHtml(title)}"
            >
                ${mediaPreviewHtml(item)}
                <span class="media-card-gradient"></span>
                <span class="media-key">${mediaKey}</span>
                ${validation.ratio ? `<span class="ratio-pill">${escapeHtml(validation.ratio)}</span>` : ''}
                ${invalid ? `<span class="media-warning">${escapeHtml(validation.reason || 'Unavailable')}</span>` : ''}
                <span class="check-mark">✓</span>
            </button>
        `;
        }).join('');
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
            renderAccountSelector();
            applyAccount(state.config.entity_id);

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

    els.saveBtn.addEventListener('click', savePost);

    init();
})();
