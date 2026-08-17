<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · X Ads Mini Composer</title>
    <link rel="stylesheet" href="assets/app.css?v=20260816-1">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="brand auth-brand">
            <div class="x-logo">𝕏</div>
            <div>
                <h1>Sign in</h1>
                <div class="subtitle">X Ads Mini Composer</div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-error" role="alert"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" maxlength="64" required autocomplete="username" autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>

            <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
