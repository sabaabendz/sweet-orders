<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CakeShop - Mot de passe oublié</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        .forgot-container {
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
        .forgot-btn {
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
        .forgot-btn:hover {
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
        .info {
            background: #74b9ff;
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

    <div class="forgot-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: #f78fb3;">🔑 Mot de passe oublié</h2>
        
        <div class="info">
            Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['forgot_success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['forgot_success']) ?></div>
            <?php unset($_SESSION['forgot_success']); ?>
        <?php endif; ?>

        <form id="forgotForm" action="index.php?controller=auth&action=forgot_process" method="POST">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="text" id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Entrez votre adresse email">
                <div class="error-message" id="email-error"></div>
            </div>
            
            <button type="submit" class="forgot-btn">Envoyer le lien de réinitialisation</button>
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
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
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