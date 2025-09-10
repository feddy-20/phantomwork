<?php
include 'connection.php';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    $errors = [];
    
    // Validation
    if (empty($username)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role) VALUES (?, ?, ?, ?, 'client')");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $phone);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now login.";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Client - PhantomWork</title>
    <meta name="description" content="Create your PhantomWork client account to access professional technician services.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="min-h-screen">
        <div class="hero-background">
            <img src="pic3.png" alt="Registration background" class="hero-bg-image" style="opacity: 0.1;">
            <div class="hero-overlay" style="opacity: 0.5;"></div>
        </div>
        
        <div class="register-container">
            <div class="glass-card register-card">
                <div class="register-header">
                    <div class="nav-brand">
                        <img src="pic2.png" alt="PhantomWork" class="logo">
                        <span class="brand-text">PhantomWork</span>
                    </div>
                    <h2 class="register-title">Register as Client</h2>
                    <p class="register-subtitle">Create your account to start requesting services</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-alert">
                        <span>⚠️</span>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="success-alert">
                        <span>✅</span>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm" onsubmit="return validateForm('registerForm')">
                    <div class="form-group">
                        <label for="username" class="form-label">Full Name</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            class="form-input"
                            placeholder="Enter your full name"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                    </div>

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
                        <label for="phone" class="form-label">Phone Number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            required
                            class="form-input"
                            placeholder="Enter your phone number"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
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
                            placeholder="Create a password (min 6 characters)"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input
                            id="confirmPassword"
                            name="confirmPassword"
                            type="password"
                            required
                            class="form-input"
                            placeholder="Confirm your password"
                        >
                    </div>

                    <div class="checkbox-group terms-group">
                        <input id="terms" name="terms" type="checkbox" required class="form-checkbox">
                        <label for="terms" class="checkbox-label">
                            I agree to the <a href="#" class="footer-link">Terms of Service</a> and 
                            <a href="#" class="footer-link">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary register-btn">
                        Create Account
                    </button>
                </form>

                <div class="divider">
                    <span class="divider-text">Already have an account?</span>
                </div>

                <div class="auth-links">
                    <a href="login.php" class="btn btn-secondary auth-link">Sign in instead</a>
                </div>
            </div>

            <div class="auth-footer">
                <p>
                    Want to offer services? 
                    <a href="technician_application.php" class="footer-link">Apply as a Technician</a>
                </p>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    
    <style>
    .register-container {
        max-width: 28rem;
        width: 100%;
        position: relative;
        z-index: 10;
    }

    .register-card {
        margin-bottom: 2rem;
    }

    .register-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .register-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .register-subtitle {
        color: var(--muted-foreground);
    }

    .success-alert {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(34, 197, 94, 0.1);
        color: var(--accent);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--accent);
        margin-bottom: 1.5rem;
    }

    .terms-group {
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }

    .terms-group .checkbox-label {
        line-height: 1.4;
    }

    .register-btn {
        width: 100%;
        margin-bottom: 1.5rem;
    }
    </style>
</body>
</html>