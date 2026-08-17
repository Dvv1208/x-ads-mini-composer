<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>X Ads Mini Composer</title>
    <link rel="stylesheet" href="assets/app.css">
</head>

<body>
    <div class="shell">
        <div class="topbar">
            <div class="brand">
                <div class="x-logo">𝕏</div>
                <div>
                    <h1>X Ads Mini Composer</h1>
                    <div class="subtitle">Schedule Website Card posts using the X Ads API.</div>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-identity">
                    <span class="user-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string)$currentUser['username'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="user-details">
                        <strong><?= htmlspecialchars((string)$currentUser['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars(match ($currentUser['role'] ?? 'user') {
                            'admin' => 'Administrator',
                            'editor' => 'Editor',
                            default => 'User',
                        }, ENT_QUOTES, 'UTF-8') ?></small>
                    </span>
                </div>
                <span class="header-divider" aria-hidden="true"></span>
                <?php if (in_array(($currentUser['role'] ?? ''), ['admin', 'editor'], true)): ?>
                    <a class="btn header-btn" href="admin">Admin</a>
                <?php endif; ?>
                <form method="post" action="logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn header-btn logout-btn" type="submit">Log out</button>
                </form>
            </div>
        </div>

        <div class="account-bar">
            <label for="accountSelect">X Ads account: </label>
            <div class="account-pill">
                <select id="accountSelect" aria-label="X Ads account">
                    <option value="">Loading account…</option>
                </select>
            </div>
        </div>

        <div class="panel">
            <section class="view active" id="view-composer">
                <div class="composer-grid" id="composerGrid">
                    <div class="form-side">
                        <div class="destination-block">
                            <div class="section-label">Destination</div>
                            <button
                                class="destination-option"
                                id="websiteDestination"
                                type="button"
                                aria-pressed="false"
                                aria-controls="websiteFields"
                            >
                                <span class="destination-copy">
                                    <strong>Website</strong>
                                    <small>Include a call to action to your website.</small>
                                </span>
                                <span class="destination-check" aria-hidden="true">✓</span>
                            </button>
                        </div>

                        <div class="website-fields" id="websiteFields" hidden>
                            <div class="field">
                                <label for="websiteUrl">Website URL <span class="required">*</span></label>
                                <div class="url-input-group">
                                    <span class="url-prefix">https://</span>
                                    <input
                                        id="websiteUrl"
                                        type="text"
                                        placeholder="example.com"
                                        inputmode="url"
                                        autocomplete="url"
                                        spellcheck="false"
                                    >
                                </div>
                            </div>

                            <div class="field">
                                <label for="headline">Headline <span class="required">*</span></label>
                                <input id="headline" type="text" maxlength="80" placeholder="Enter a headline" autocomplete="on">
                            </div>

                            <div class="field">
                                <label>
                                    <span>Media <span class="required">*</span> <span class="hint inline-hint">Required for card ads</span></span>
                                    <span class="hint" id="mediaCount">0 selected</span>
                                </label>
                                <div class="selected-strip" id="selectedStrip">
                                    <span class="selected-empty">Choose media from the library.</span>
                                </div>
                            </div>

                            <div class="field">
                                <label for="scheduleAt">
                                    Schedule
                                    <span class="hint">optional · defaults to +1 minute</span>
                                </label>
                                <input id="scheduleAt" type="datetime-local" step="60">
                            </div>

                            <div class="actions">
                                <button class="btn btn-primary" id="saveBtn">Schedule</button>
                            </div>
                        </div>
                    </div>

                    <aside class="media-side" id="mediaLibraryPanel" hidden>
                        <div class="media-head">
                            <h2>Media Library</h2>
                            <button class="btn" id="refreshMediaBtn">Refresh</button>
                        </div>

                        <div class="media-toolbar">
                            <input type="search" id="mediaSearch" placeholder="Search media…" autocomplete="on">
                            <button class="btn" id="mediaSearchBtn">Search</button>
                        </div>

                        <div id="mediaGrid" class="media-grid"></div>
                        <button class="btn load-more" id="loadMoreMediaBtn" style="display:none">Load more</button>
                    </aside>
                </div>
            </section>
        </div>
    </div>

    <div class="status" id="status"></div>
    <script src="assets/app.js" defer></script>
</body>

</html>
