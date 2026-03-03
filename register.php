<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: contribute/');
    exit;
}

require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    
    if (!$email || !$password) {
        $error = 'Email and password required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, display_name, role) VALUES (?, ?, ?, 'creator')");
            $stmt->execute([$email, $hash, $display_name ?: null]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: contribute/');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | OmniGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0f;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: #12121a;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        .logo {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo span { color: #6366f1; }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            color: #aaa;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            color: #e0e0e0;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        .form-group small { color: #666; font-size: 0.8rem; }
        .btn {
            width: 100%;
            background: #6366f1;
            color: #fff;
            border: none;
            padding: 0.9rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .btn:hover { background: #4f46e5; }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .links a { color: #6366f1; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">Omni<span>Grid</span></div>
        
        <?php if ($error): ?>
            <div class="error"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Display Name (optional)</label>
                <input type="text" name="display_name" placeholder="How viewers see you" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
                <small>At least 8 characters</small>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="links">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
