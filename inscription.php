<?php
include __DIR__."/config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <link rel="stylesheet" href="./assets/styleinsription.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>inscription</title>
</head>
<body>
    <div class="register-container">

        <!-- Partie gauche -->
        <div class="register-info">

            <div class="logo">
                📘
            </div>

            <h1>Cahier de texte digital</h1>

            <p class="description">
                Créez votre compte et accédez facilement à votre
                espace scolaire numérique.
            </p>

            <div class="features">

                <div class="feature">
                    <span>✓</span>
                    <p>Consultez les cours et devoirs</p>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <p>Suivez les activités scolaires</p>
                </div>

                <div class="feature">
                    <span>✓</span>
                    <p>Communiquez avec votre établissement</p>
                </div>

            </div>

        </div>


        <!-- Formulaire -->
        <div class="register-form">

            <div class="form-header">

                <h2>Créer un compte</h2>

                <p>
                    Remplissez les informations ci-dessous
                </p>

            </div>


            <form action="#" method="POST">

                <!-- Nom et prénom -->
                <div class="form-row">

                    <div class="form-group">

                        <label for="nom">
                            Nom
                        </label>

                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            placeholder="Votre nom"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="prenom">
                            Prénom
                        </label>

                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            placeholder="Votre prénom"
                            required
                        >

                    </div>

                </div>


                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Adresse e-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemple@email.com"
                        required
                    >

                </div>


                <!-- Téléphone -->
                <div class="form-group">

                    <label for="telephone">
                        Téléphone
                    </label>

                    <input
                        type="tel"
                        id="telephone"
                        name="telephone"
                        placeholder="+237 6XX XX XX XX"
                    >

                </div>


                <!-- Profil -->
                <div class="form-group">

                    <label for="profil">
                        Type de compte
                    </label>

                    <select
                        id="profil"
                        name="profil"
                        required
                    >

                        <option value="">
                            -- Choisir votre profil --
                        </option>

                        <option value="enseignant">
                            Enseignant
                        </option>

                        <option value="eleve">
                            Élève
                        </option>

                        <option value="parent">
                            Parent / Tuteur
                        </option>

                        <option value="administrateur">
                            Administrateur
                        </option>

                    </select>

                </div>


                <!-- Etablissement -->
                <div class="form-group">

                    <label for="etablissement">
                        Établissement
                    </label>

                    <input
                        type="text"
                        id="etablissement"
                        name="etablissement"
                        placeholder="Nom de votre établissement"
                        required
                    >

                </div>


                <!-- Identifiant -->
                <div class="form-group">

                    <label for="identifiant">
                        Nom d'utilisateur
                    </label>

                    <input
                        type="text"
                        id="identifiant"
                        name="identifiant"
                        placeholder="Choisissez un identifiant"
                        required
                    >

                </div>


                <!-- Mot de passe -->
                <div class="form-row">

                    <div class="form-group">

                        <label for="password">
                            Mot de passe
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            minlength="8"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="confirm-password">
                            Confirmation
                        </label>

                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="••••••••"
                            minlength="8"
                            required
                        >

                    </div>

                </div>


                <!-- Conditions -->
                <div class="terms">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        required
                    >

                    <label for="terms">

                        J'accepte les
                        <a href="#">
                            conditions d'utilisation
                        </a>
                        et la
                        <a href="#">
                            politique de confidentialité
                        </a>.

                    </label>

                </div>


                <!-- Bouton -->
                <button
                    type="submit"
                    class="btn-register"
                >

                    Créer mon compte

                </button>


                <!-- Connexion -->
                <div class="login-link">

                    <p>
                        Vous avez déjà un compte ?
                        <a href="connexion.html">
                            Se connecter
                        </a>
                    </p>

                </div>

            </form>

        </div>

    </div>

</body>
</html>