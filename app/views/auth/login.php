<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">

    <!-- En-tête -->
    <div class="auth-header">
        <span class="auth-icon">📘</span>
        <h1>Bon retour !</h1>
        <p>Connectez-vous à votre espace <?= APP_NAME ?></p>
    </div>

    <!-- Messages flash -->
    <?= Session::getFlash() ?>

    <!-- Formulaire -->
    <form method="POST" action="<?= APP_URL ?>/app.php?page=login" novalidate>

        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Email -->
        <div class="form-group">
            <label for="email">Adresse email <span class="required">*</span></label>
            <div class="input-wrapper">
                <span class="input-icon">✉️</span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="exemple@email.com"
                    value="<?= htmlspecialchars($_COOKIE['remember_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    autocomplete="email"
                >
            </div>
        </div>

        <!-- Mot de passe -->
        <div class="form-group">
            <label for="password">Mot de passe <span class="required">*</span></label>
            <div class="input-wrapper">
                <span class="input-icon">🔒</span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="toggle-pwd" onclick="togglePassword('password', this)" aria-label="Afficher/masquer">
                    👁️
                </button>
            </div>
        </div>

        <!-- Se souvenir -->
        <div class="form-options">
            <label class="remember-me">
                <input type="checkbox" name="remember"
                    <?= !empty($_COOKIE['remember_email']) ? 'checked' : '' ?>>
                Se souvenir de moi
            </label>
            <a href="#" class="forgot-link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn-auth">Se connecter</button>

    </form>

    <div class="auth-footer">
        <p>Pas encore de compte ? <a href="<?= APP_URL ?>/app.php?page=register">Créer un compte</a></p>
    </div>

</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.textContent = isText ? '👁️' : '🙈';
}
</script>

</body>
</html>
