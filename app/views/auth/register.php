<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/auth.css">
</head>
<body class="auth-body">

<div class="auth-container auth-container--wide">

    <div class="auth-header">
        <span class="auth-icon">📝</span>
        <h1>Créer un compte</h1>
        <p>Rejoignez <?= APP_NAME ?> dès maintenant</p>
    </div>

    <?= Session::getFlash() ?>

    <form method="POST" action="<?= APP_URL ?>/app.php?page=register" novalidate>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Nom + Prénom côte à côte -->
        <div class="form-row">
            <div class="form-group">
                <label for="nom">Nom <span class="required">*</span></label>
                <input
                    type="text" id="nom" name="nom"
                    placeholder="Ex : Dupont"
                    value="<?= htmlspecialchars($old['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required minlength="2" autocomplete="family-name"
                >
            </div>
            <div class="form-group">
                <label for="prenom">Prénom <span class="required">*</span></label>
                <input
                    type="text" id="prenom" name="prenom"
                    placeholder="Ex : Jean"
                    value="<?= htmlspecialchars($old['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required minlength="2" autocomplete="given-name"
                >
            </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Adresse email <span class="required">*</span></label>
            <input
                type="email" id="email" name="email"
                placeholder="exemple@email.com"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required autocomplete="email"
            >
        </div>

        <!-- Mot de passe -->
        <div class="form-group">
            <label for="password">Mot de passe <span class="required">*</span></label>
            <div class="input-wrapper">
                <input
                    type="password" id="password" name="password"
                    placeholder="Minimum 8 caractères"
                    required minlength="8" autocomplete="new-password"
                >
                <button type="button" class="toggle-pwd"
                        onclick="togglePassword('password', this)"
                        aria-label="Afficher/masquer">👁️</button>
            </div>
        </div>

        <!-- Confirmation mot de passe -->
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe <span class="required">*</span></label>
            <div class="input-wrapper">
                <input
                    type="password" id="confirm_password" name="confirm_password"
                    placeholder="Répétez votre mot de passe"
                    required autocomplete="new-password"
                >
                <button type="button" class="toggle-pwd"
                        onclick="togglePassword('confirm_password', this)"
                        aria-label="Afficher/masquer">👁️</button>
            </div>
        </div>

        <!-- Rôle — synchronisé avec ENUM BDD (enseignant, censeur, administrateur) -->
        <div class="form-group">
            <label for="role">Type de compte <span class="required">*</span></label>
            <select id="role" name="role" required>
                <option value="">— Choisir votre profil —</option>
                <?php
                $roles = [
                    'enseignant'     => 'Enseignant',
                    'censeur'        => 'Censeur',
                    'administrateur' => 'Administrateur',
                ];
                foreach ($roles as $val => $label):
                    $selected = (($old['role'] ?? '') === $val) ? 'selected' : '';
                ?>
                    <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-auth">Créer mon compte</button>

    </form>

    <div class="auth-footer">
        <p>Vous avez déjà un compte ? <a href="<?= APP_URL ?>/app.php?page=login">Se connecter</a></p>
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
