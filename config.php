<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);

            if ($check_stmt->fetch()) {
                $error = "Username or Email is already taken.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    header("Location: login.php?registration=success");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = "Registration failed, please try again.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Smash Pro Badminton</title>
    <style>
        /* Shared UI Variable Engine Styling Criteria */
        :root {
            --bg-gradient: linear-gradient(135deg, #f3f7fc 0%, #e6effa 100%);
            --text-main: #2d3748;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --card-border: rgba(227, 238, 252, 0.9);
            --header-bg: rgba(255, 255, 255, 0.85);
            --title-color: #1a202c;
            --input-bg: #f8fafc;
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --card-border: rgba(51, 65, 85, 0.5);
            --header-bg: rgba(15, 23, 42, 0.85);
            --title-color: #ffffff;
            --input-bg: #0f172a;
        }

        body {
            background: var(--bg-gradient); color: var(--text-main);
            font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0;
            transition: background 0.3s, color 0.3s; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        header {
            background: var(--header-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 1.2rem 3rem; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(47, 199, 52, 0.2); position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 24px; font-weight: 800; color: #1e8a4b; text-decoration: none; }
        .theme-toggle {
            background: rgba(94, 59, 150, 0.1); color: #5e3b96; border: none;
            padding: 8px 14px; border-radius: 12px; cursor: pointer; font-weight: 700;
        }

        .container { max-width: 450px; width: 100%; margin: auto; padding: 2rem 1.5rem; box-sizing: border-box; }

        .auth-card {
            background: var(--card-bg); border-radius: 24px; border: 1px solid var(--card-border);
            padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .auth-title { font-size: 24px; font-weight: 800; color: var(--title-color); margin: 0 0 8px 0; text-align: center; }
        .auth-subtitle { font-size: 14px; color: var(--text-muted); margin: 0 0 2rem 0; text-align: center; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }

        .form-input {
            width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--card-border);
            background: var(--input-bg); color: var(--text-main); font-size: 15px; box-sizing: border-box;
            transition: all 0.2s;
        }
        .form-input:focus { outline: none; border-color: #1e8a4b; box-shadow: 0 0 0 3px rgba(30, 138, 75, 0.15); }

        .error-banner {
            background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 1.5rem; text-align: center;
        }

        .btn-auth {
            width: 100%; padding: 14px; border: none; background: linear-gradient(135deg, #2fc734 0%, #1e8a4b 100%);
            color: #ffffff; font-size: 15px; font-weight: 700; border-radius: 14px; cursor: pointer; transition: all 0.2s;
        }
        .btn-auth:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(47, 199, 52, 0.3); }

        .auth-footer { text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 1.5rem; }
        .auth-footer a { color: #5e3b96; text-decoration: none; font-weight: 700; }
        [data-theme="dark"] .auth-footer a { color: #a78bfa; }
    </style>
</head>
<body>

    <header>
        <a href="../store/home.php" class="logo">SmashPro Badminton</a>
        <button class="theme-toggle" id="themeBtn">🌓 Mode</button>
    </header>

    <div class="container">
        <div class="auth-card">
            <h2 class="auth-title">Join Smash Pro</h2>
            <p class="auth-subtitle">Create an account to track setups and equipment orders</p>

            <?php if (!empty($error)): ?>
                <div class="error-banner"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn-auth">Register Account</button>
            </form>

            <div class="auth-footer">
                Already a member? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        const themeBtn = document.getElementById('themeBtn');
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        themeBtn.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>