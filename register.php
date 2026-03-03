<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: contribute/');
    exit;
}

require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');

    if ($error) {
        // CSRF failed, skip processing
    } elseif (!$email || !$password) {
        $error = 'Email and password required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
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
    <title>Create Account — OmniGrid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #08080d;
            --surface: #111118;
            --surface-2: #1a1a24;
            --border: #25253a;
            --border-light: #2f2f4a;
            --text: #f0f0f5;
            --text-secondary: #b0b0c8;
            --muted: #6b6b85;
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --success: #10b981;
            --danger: #ef4444;
            --gradient-primary: linear-gradient(135deg, #6366f1, #a855f7);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        .brand-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 80%, rgba(168, 85, 247, 0.06) 0%, transparent 60%);
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(99, 102, 241, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 30%, transparent 80%);
        }

        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 400px;
        }
        .brand-logo {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .brand-logo .icon {
            width: 52px;
            height: 52px;
            background: var(--gradient-primary);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
        }
        .brand-logo span { color: var(--primary-light); }
        .brand-tagline {
            font-size: 1.15rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .perks {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .perk {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        .perk-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .perk-icon.indigo { background: var(--primary-glow); color: var(--primary-light); }
        .perk-icon.green { background: rgba(16, 185, 129, 0.12); color: var(--success); }
        .perk-icon.rose { background: rgba(244, 63, 94, 0.12); color: #f43f5e; }
        .perk h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
        }
        .perk p {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.5;
        }

        .register-panel {
            width: 480px;
            min-width: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: var(--surface);
            border-left: 1px solid var(--border);
        }

        .register-box {
            width: 100%;
            max-width: 360px;
        }

        .register-header {
            margin-bottom: 2rem;
        }
        .register-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        .register-header p {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.15rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .form-group input::placeholder { color: var(--muted); }
        .form-group .hint {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 0.35rem;
        }
        .form-group .optional {
            color: var(--muted);
            font-weight: 400;
            font-size: 0.75rem;
        }

        .btn-submit {
            width: 100%;
            background: var(--gradient-primary);
            color: #fff;
            border: none;
            padding: 0.85rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.25);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(99, 102, 241, 0.35);
        }

        .error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.75rem 0;
            color: var(--muted);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .links {
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .links a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .links a:hover { text-decoration: underline; }

        .password-strength {
            height: 3px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .password-strength .bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: var(--transition);
        }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .brand-panel { display: none; }
            .register-panel {
                width: 100%;
                min-width: auto;
                min-height: 100vh;
                border-left: none;
            }
        }
    </style>
</head>
<body>
    <div class="brand-panel">
        <div class="brand-content">
            <div class="brand-logo">
                <div class="icon"><i class="fa-solid fa-cube"></i></div>
                Omni<span>Grid</span>
            </div>
            <p class="brand-tagline">Start streaming and earning in minutes. No software required.</p>
            <div class="perks">
                <div class="perk">
                    <div class="perk-icon indigo"><i class="fa-solid fa-bolt"></i></div>
                    <div>
                        <h4>smartGrid Earnings</h4>
                        <p>Earn dynamically based on your engagement and viewership metrics.</p>
                    </div>
                </div>
                <div class="perk">
                    <div class="perk-icon green"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <h4>Creator Dashboard</h4>
                        <p>Track your streams, earnings, and audience growth in real time.</p>
                    </div>
                </div>
                <div class="perk">
                    <div class="perk-icon rose"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <h4>Global Audience</h4>
                        <p>Get discovered through the interactive globe by viewers worldwide.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="register-panel">
        <div class="register-box">
            <div class="register-header">
                <h1>Create your account</h1>
                <p>Join OmniGrid and start streaming today</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="display_name">Display Name <span class="optional">(optional)</span></label>
                    <input type="text" id="display_name" name="display_name" placeholder="How viewers will see you" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="At least 8 characters" oninput="updateStrength(this)">
                    <div class="password-strength"><div class="bar" id="strengthBar"></div></div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-rocket"></i> Create Account
                </button>
            </form>

            <div class="divider">or</div>

            <div class="links">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
    function updateStrength(input) {
        const bar = document.getElementById('strengthBar');
        const len = input.value.length;
        let strength = 0;
        if (len >= 4) strength = 1;
        if (len >= 8) strength = 2;
        if (len >= 8 && /[A-Z]/.test(input.value) && /[0-9]/.test(input.value)) strength = 3;
        if (len >= 12 && /[^A-Za-z0-9]/.test(input.value)) strength = 4;

        const colors = ['', '#ef4444', '#f59e0b', '#10b981', '#6366f1'];
        const widths = ['0%', '25%', '50%', '75%', '100%'];
        bar.style.width = widths[strength];
        bar.style.background = colors[strength];
    }
    </script>
</body>
</html>
