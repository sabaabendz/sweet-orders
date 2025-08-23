<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Connexion</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
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
        .login-btn {
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
        .login-btn:hover {
            background: #f8a5c2;
        }
        .login-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
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
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #666;
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

    <div class="login-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #f78fb3;">🔐 Connexion</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['login_success']) ?></div>
            <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>

        <form id="loginForm" action="index.php?controller=auth&action=authenticate" method="POST">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="text" id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <div class="error-message" id="email-error"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password">
                <div class="error-message" id="password-error"></div>
            </div>
            
            <button type="submit" class="login-btn">Se connecter</button>
        </form>

        <div class="links">
            <a href="index.php?controller=auth&action=forgot">Mot de passe oublié ?</a>
        </div>

        <div class="divider">───── ou ─────</div>

        <div style="text-align: center;">
            <a href="index.php?controller=auth&action=register" class="btn" style="display: inline-block; background: #786fa6;">
                Créer un compte
            </a>
        </div>

        <div class="links">
            <a href="index.php">← Retour à l'accueil</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 CakeShop - Pâtisserie artisanale</p>
    </footer>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Reset previous errors
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
            
            // Validate email
            const email = document.getElementById('email').value.trim();
            if (!email) {
                showError('email', 'L\'email est requis');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showError('email', 'Format d\'email invalide');
                isValid = false;
            }
            
            // Validate password
            const password = document.getElementById('password').value;
            if (!password) {
                showError('password', 'Le mot de passe est requis');
                isValid = false;
            } else if (password.length < 6) {
                showError('password', 'Le mot de passe doit contenir au moins 6 caractères');
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
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>