<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>X Ads Mini Composer</title>
    <link rel="stylesheet" href="assets/app.css?v=20260816-2">
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
                <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                    <a class="small-btn link-btn" href="admin">Admin</a>
                <?php endif; ?>
                <span class="signed-user"><?= htmlspecialchars((string)$currentUser['username'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="small-btn" type="submit">Log out</button>
                </form>
            </div>
        </div>

        <div class="account-bar">
            <label for="accountSelect">X Ads account</label>
            <div class="account-pill">
                <select id="accountSelect" aria-label="X Ads account">
                    <option value="">Loading account…</option>
                </select>
            </div>
        </div>

        <div class="panel">
            <section class="view active" id="view-composer">
                <div class="composer-grid">
                    <div class="form-side">
                        <div class="field">
                            <label for="scheduleAt">
                                Schedule
                                <span class="hint">optional · defaults to +1 minute</span>
                            </label>
                            <input id="scheduleAt" type="datetime-local" step="60">
                        </div>

                        <div class="field">
                            <label for="websiteUrl">Website URL</label>
                            <input id="websiteUrl" type="text" placeholder="https://example.com" autocomplete="on">
                        </div>

                        <div class="field">
                            <label for="headline">Headline <span class="hint">required with website</span></label>
                            <input id="headline" type="text" maxlength="80" placeholder="Enter a headline" autocomplete="on">
                        </div>

                        <div class="field">
                            <label>
                                Selected media
                                <span class="hint" id="mediaCount">0 selected</span>
                            </label>
                            <div class="selected-strip" id="selectedStrip">
                                <span class="selected-empty">Choose media from the library.</span>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" id="saveBtn">Schedule</button>
                        </div>
                    </div>

                    <aside class="media-side">
                        <div class="media-head">
                            <h2>Media Library</h2>
                            <button class="small-btn" id="refreshMediaBtn">Refresh</button>
                        </div>

                        <div class="media-toolbar">
                            <input type="search" id="mediaSearch" placeholder="Search media…" autocomplete="on">
                            <button class="small-btn" id="mediaSearchBtn">Search</button>
                        </div>

                        <div id="mediaGrid" class="media-grid"></div>
                        <button class="btn load-more" id="loadMoreMediaBtn" style="display:none">Load more</button>
                    </aside>
                </div>
            </section>
        </div>
    </div>

    <div class="status" id="status"></div>
    <script src="assets/app.js?v=20260817-1" defer></script>
</body>
</html>
