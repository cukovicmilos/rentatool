<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in?
if (isAdmin()) {
    redirect('admin/');
}

$error = '';

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(post('username'));
    $password = post('password');
    
    if (empty($username) || empty($password)) {
        $error = 'Unesite korisničko ime i lozinku.';
    } else {
        // Check credentials from database
        $admin = db()->fetch("SELECT * FROM admins WHERE username = ?", [$username]);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Login successful
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Update last login
            db()->execute("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$admin['id']]);
            
            flash('success', 'Uspešno ste se prijavili.');
            redirect('admin/');
        } else {
            $error = 'Pogrešno korisničko ime ili lozinka.';
        }
    }
}

$pageTitle = 'Admin Login - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><polygon points=%2250,5 63,38 98,38 69,59 80,95 50,72 20,95 31,59 2,38 37,38%22 fill=%22%23000%22/></svg>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        body {
            background: var(--color-gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--spacing-md);
        }
        
        .login-container {
            background: var(--color-white);
            padding: var(--spacing-xl);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .login-logo h1 {
            font-size: var(--font-size-h2);
            margin-bottom: var(--spacing-xs);
        }
        
        .login-logo p {
            color: var(--color-gray-500);
            margin: 0;
        }
        
        .login-form .form-group {
            margin-bottom: var(--spacing-lg);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>🔧 <?= SITE_NAME ?></h1>
            <p>Admin Panel</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username" class="form-label">Korisničko ime</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       value="<?= e(post('username')) ?>"
                       autocomplete="username"
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Lozinka</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control"
                       autocomplete="current-password"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block btn-large">
                Prijavi se
            </button>
        </form>
        
        <p class="text-center text-muted mt-3" style="font-size: var(--font-size-small);">
            <a href="<?= url('') ?>">← Nazad na sajt</a>
        </p>
    </div>
</body>
</html>
