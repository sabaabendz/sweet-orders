<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        .reset-container {
            max-width: 450px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2d3436;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #f78fb3;
            outline: none;
        }
        .form-group input.error {
            border-color: #ff7675;
        }
        .error-message {
            color: #ff7675;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        .reset-btn {
            width: 100%;
            padding: 15px;
            background: #f78fb3;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .reset-btn:hover {
            background: #f8a5c2;
        }
        .error {
            background: #ff7675;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #00b894;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #f78fb3;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo-container">
            <img src="public/assets/images/sweetorderlogo.png" alt="CakeShop Logo" class="logo">
            <h1>CakeShop</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="index.php?controller=produits&action=catalogue">Catalogue</a></li>
                <li><a href="index.php?controller=auth&action=login">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <div class="reset-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #f78fb3;">🔐 Nouveau mot de passe</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['reset_success']) ?></div>
            <?php unset($_SESSION['reset_success']); ?>
        <?php endif; ?>

        <form id="resetForm" action="index.php?controller=auth&action=reset_process" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            
            <div class="form-group">
                <label for="password">Nouveau mot de passe :</label>
                <input type="password" id="password" name="password">
                <div class="error-message" id="password-error"></div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe :</label>
                <input type="password" id="password_confirm" name="password_confirm">
                <div class="error-message" id="password_confirm-error"></div>
            </div>
            
            <button type="submit" class="reset-btn">Réinitialiser le mot de passe</button>
        </form>

        <div class="links">
            <a href="index.php?controller=auth&action=login">← Retour à la connexion</a>
            <a href="index.php">Accueil</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 CakeShop - Pâtisserie artisanale</p>
    </footer>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Reset previous errors
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
            
            // Validate password
            const password = document.getElementById('password').value;
            if (!password) {
                showError('password', 'Le mot de passe est requis');
                isValid = false;
            } else if (password.length < 6) {
                showError('password', 'Le mot de passe doit contenir au moins 6 caractères');
                isValid = false;
            }
            
            // Validate password confirmation
            const passwordConfirm = document.getElementById('password_confirm').value;
            if (!passwordConfirm) {
                showError('password_confirm', 'Veuillez confirmer le mot de passe');
                isValid = false;
            } else if (password !== passwordConfirm) {
                showError('password_confirm', 'Les mots de passe ne correspondent pas');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + '-error');
            field.classList.add('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    </script>
</body>
</html>