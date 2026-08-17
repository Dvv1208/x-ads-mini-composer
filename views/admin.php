<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>X Ads Admin</title>
    <link rel="stylesheet" href="assets/app.css?v=20260817-2">
</head>

<body data-can-edit-accounts="<?= $canEditAccounts ? 'true' : 'false' ?>">
    <div class="shell admin-shell">
        <div class="topbar">
            <div class="brand">
                <div class="x-logo">𝕏</div>
                <div>
                    <h1>X Ads Admin</h1>
                    <div class="subtitle">Manage X Ads accounts available to the Composer.</div>
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
                <a class="btn header-btn" href="./">Composer</a>
                <form method="post" action="logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn header-btn logout-btn" type="submit">Log out</button>
                </form>
            </div>
        </div>

        <div class="panel admin-panel">
            <div class="account-manager-body">
                <form id="accountForm" class="account-form" autocomplete="on">
                    <h2 id="accountFormTitle">Add account</h2>
                    <input id="accountEntityId" type="hidden">
                    <div class="field">
                        <label for="accountIdInput">Account ID</label>
                        <input id="accountIdInput" name="account_id" type="text" maxlength="32" required autocomplete="on">
                    </div>
                    <div class="field">
                        <label for="userIdInput">User ID</label>
                        <input id="userIdInput" name="user_id" type="text" inputmode="numeric" maxlength="32" required autocomplete="on">
                    </div>
                    <div class="field">
                        <label for="accountCookieInput">X Cookie</label>
                        <textarea id="accountCookieInput" name="cookie" rows="7" spellcheck="false" autocomplete="off" placeholder="auth_token=...; ct0=...; ..."></textarea>
                    </div>
                    <div class="account-form-actions">
                        <button class="btn btn-primary" id="accountSaveBtn" type="submit">Add account</button>
                    </div>
                </form>

                <div class="account-table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Account ID</th>
                                <th>User ID</th>
                                <th>Session</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accountTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="status" id="status"></div>
    <script src="assets/admin.js" defer></script>
</body>

</html>
