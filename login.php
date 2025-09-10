<?php
session_start();
include 'connection.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_pass, $role);
    
    if ($stmt->fetch() && password_verify($password, $hashed_pass)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        
        if ($role == 'client') {
            header("Location: client_dashboard.php");
        } elseif ($role == 'technician') {
            header("Location: technician_dashboard.php");
        } elseif ($role == 'admin') {
            header("Location: admin_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid credentials. Please check your email and password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PhantomWork</title>
    <meta name="description" content="Sign in to your PhantomWork account to access professional technician services.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="min-h-screen">
        <div class="hero-background">
            <img src="pic11.png" alt="Login background" class="hero-bg-image" style="opacity: 0.1;">
            <div class="hero-overlay" style="opacity: 0.5;"></div>
        </div>
        
        <div class="login-container">
            <div class="glass-card login-card">
                <div class="login-header">
                    <div class="nav-brand">
                        <img src="pic2.png" alt="PhantomWork" class="logo">
                        <span class="brand-text">PhantomWork</span>
                    </div>
                    <h2 class="login-title">Sign in to your account</h2>
                    <p class="login-subtitle">Access your dashboard and manage your services</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-alert">
                        <span>⚠️</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" onsubmit="return validateForm('loginForm')">
                    <div class="form-group">
                        <label for="email" class="form-label">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            class="form-input"
                            placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="form-input"
                            placeholder="Enter your password"
                        >
                    </div>

                    <div class="form-row">
                        <div class="checkbox-group">
                            <input id="remember" name="remember" type="checkbox" class="form-checkbox">
                            <label for="remember" class="checkbox-label">Remember me</label>
                        </div>
                        <a href="#" class="forgot-link">Forgot your password?</a>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary login-btn">
                        Sign in
                    </button>
                </form>

                <div class="divider">
                    <span class="divider-text">Don't have an account?</span>
                </div>

                <div class="auth-links">
                    <a href="register.php" class="btn btn-secondary auth-link">Register as Client</a>
                    <a href="technician_application.php" class="auth-text-link">Apply as Technician</a>
                </div>
            </div>

            <div class="auth-footer">
                <p>
                    By signing in, you agree to our 
                    <a href="#" class="footer-link">Terms of Service</a> and 
                    <a href="#" class="footer-link">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    
    <style>
    .min-h-screen {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        position: relative;
    }

    .login-container {
        max-width: 28rem;
        width: 100%;
        position: relative;
        z-index: 10;
    }

    .login-card {
        margin-bottom: 2rem;
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .nav-brand {
        margin-bottom: 1rem;
    }

    .login-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .login-subtitle {
        color: var(--muted-foreground);
    }

    .error-alert {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(239, 68, 68, 0.1);
        color: var(--destructive);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--destructive);
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        color: var(--foreground);
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-checkbox {
        width: 1rem;
        height: 1rem;
        color: var(--primary);
        border: 1px solid var(--border);
        border-radius: 0.25rem;
    }

    .checkbox-label {
        color: var(--muted-foreground);
        font-size: 0.875rem;
    }

    .forgot-link {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.875rem;
        transition: var(--transition-smooth);
    }

    .forgot-link:hover {
        color: var(--primary-glow);
    }

    .login-btn {
        width: 100%;
        margin-bottom: 1.5rem;
    }

    .divider {
        position: relative;
        margin: 1.5rem 0;
    }

    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--border);
    }

    .divider-text {
        position: relative;
        background: var(--card);
        padding: 0 1rem;
        color: var(--muted-foreground);
        font-size: 0.875rem;
        display: flex;
        justify-content: center;
    }

    .auth-links {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .auth-link {
        width: 100%;
        text-align: center;
    }

    .auth-text-link {
        text-align: center;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        color: var(--muted-foreground);
        text-decoration: none;
        transition: var(--transition-smooth);
    }

    .auth-text-link:hover {
        color: var(--primary);
        border-color: var(--primary);
    }

    .auth-footer {
        text-align: center;
    }

    .auth-footer p {
        color: var(--muted-foreground);
        font-size: 0.875rem;
    }

    .footer-link {
        color: var(--primary);
        text-decoration: none;
    }

    .footer-link:hover {
        color: var(--primary-glow);
    }
    </style>
</body>
</html>