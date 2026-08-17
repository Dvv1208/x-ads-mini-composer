<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>X Ads Admin</title>
    <link rel="stylesheet" href="assets/app.css?v=20260816-1">
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
                <a class="small-btn link-btn" href="./">Composer</a>
                <span class="signed-user"><?= htmlspecialchars((string)$currentUser['username'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="small-btn" type="submit">Log out</button>
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
                    <div class="account-form-actions">
                        <button class="btn btn-primary" id="accountSaveBtn" type="submit">Add account</button>
                        <button class="small-btn" id="accountCancelBtn" type="button" hidden>Cancel</button>
                    </div>
                </form>

                <div class="account-table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Account ID</th>
                                <th>User ID</th>
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
    <script src="assets/admin.js?v=20260817-1" defer></script>
</body>
</html>
