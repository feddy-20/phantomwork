<?php
include 'connection.php';

if (isset($_POST['apply'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $experience = trim($_POST['experience']);
    $bio = trim($_POST['bio']);
    
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($experience)) $errors[] = "Years of experience is required";
    if (empty($bio)) $errors[] = "Professional bio is required";
    
    // File uploads
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $passport = '';
    $certificate = '';
    $fee_proof = '';
    
    if (empty($errors)) {
        // Handle passport photo
        if (isset($_FILES['passport']) && $_FILES['passport']['error'] === 0) {
            $passport = $uploadDir . basename($_FILES['passport']['name']);
            if (!move_uploaded_file($_FILES['passport']['tmp_name'], $passport)) {
                $errors[] = "Failed to upload passport photo";
            }
        } else {
            $errors[] = "Passport photo is required";
        }
        
        // Handle certificate
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === 0) {
            $certificate = $uploadDir . basename($_FILES['certificate']['name']);
            if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $certificate)) {
                $errors[] = "Failed to upload certificate";
            }
        } else {
            $errors[] = "Certificate is required";
        }
        
        // Handle fee proof
        if (isset($_FILES['fee_proof']) && $_FILES['fee_proof']['error'] === 0) {
            $fee_proof = $uploadDir . basename($_FILES['fee_proof']['name']);
            if (!move_uploaded_file($_FILES['fee_proof']['tmp_name'], $fee_proof)) {
                $errors[] = "Failed to upload fee payment proof";
            }
        } else {
            $errors[] = "Fee payment proof is required";
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO technician_applications (name, email, phone, experience, bio, passport_file, certificate_file, fee_proof) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssissss", $name, $email, $phone, $experience, $bio, $passport, $certificate, $fee_proof);
        
        if ($stmt->execute()) {
            $success = "Application submitted successfully! We'll review your application within 2-3 business days.";
        } else {
            $errors[] = "Failed to submit application. Please try again.";
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
    <title>Apply as Technician - PhantomWork</title>
    <meta name="description" content="Join PhantomWork's network of verified professional technicians. Apply today and start earning with your skills.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="application-page">
        <div class="hero-background">
            <img src="pic4.png" alt="Technician application background" class="hero-bg-image" style="opacity: 0.1;">
            <div class="hero-overlay" style="opacity: 0.3;"></div>
        </div>
        
        <div class="container application-container">
            <div class="application-header">
                <div class="nav-brand">
                    <img src="pic2.png" alt="PhantomWork" class="logo">
                    <span class="brand-text">PhantomWork</span>
                </div>
                <h1 class="application-title">
                    Join Our <span class="text-gradient">Expert Network</span>
                </h1>
                <p class="application-subtitle">
                    Apply to become a verified technician and connect with clients who need your skills.
                </p>
            </div>

            <div class="application-grid">
                <!-- Application Process -->
                <div class="process-sidebar">
                    <div class="glass-card process-card">
                        <h3 class="process-title text-gradient">Application Process</h3>
                        <p class="process-desc">Simple steps to join our network</p>
                        
                        <div class="process-steps">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Submit Application</h4>
                                    <p>Fill out the form with your details and upload required documents</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Review Process</h4>
                                    <p>Our team reviews your credentials and verifies your documents</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Get Approved</h4>
                                    <p>Receive your login credentials and start accepting jobs</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-info">
                            <h4 class="payment-title">Required Payment</h4>
                            <p class="payment-desc">
                                Application fee: $50 (Bank Transfer)<br>
                                Account: 123456789, Bank XYZ
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="form-section">
                    <div class="glass-card form-card">
                        <h2 class="form-title">Technician Application Form</h2>
                        <p class="form-desc">Please fill out all required information accurately</p>

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

                        <form method="POST" enctype="multipart/form-data" id="applicationForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        required
                                        class="form-input"
                                        placeholder="Enter your full name"
                                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address *</label>
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
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
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
                                <label for="experience" class="form-label">Years of Experience *</label>
                                <input
                                    id="experience"
                                    name="experience"
                                    type="number"
                                    required
                                    min="1"
                                    class="form-input"
                                    placeholder="Enter years of experience"
                                    value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="bio" class="form-label">Professional Bio *</label>
                                <textarea
                                    id="bio"
                                    name="bio"
                                    required
                                    rows="4"
                                    class="form-input"
                                    placeholder="Describe your skills, specializations, and professional background..."
                                ><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                            </div>

                            <div class="documents-section">
                                <h3 class="documents-title">Required Documents</h3>
                                
                                <div class="documents-grid">
                                    <div class="document-group">
                                        <label for="passport" class="form-label">Passport Photo *</label>
                                        <input id="passport" name="passport" type="file" accept="image/*" required class="form-input">
                                        <p class="file-hint">Professional headshot</p>
                                    </div>
                                    <div class="document-group">
                                        <label for="certificate" class="form-label">Certificate *</label>
                                        <input id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="form-input">
                                        <p class="file-hint">Professional certification</p>
                                    </div>
                                    <div class="document-group">
                                        <label for="fee_proof" class="form-label">Fee Payment Proof *</label>
                                        <input id="fee_proof" name="fee_proof" type="file" accept="image/*" required class="form-input">
                                        <p class="file-hint">Bank transfer screenshot</p>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox-group terms-group">
                                <input id="terms" type="checkbox" required class="form-checkbox">
                                <label for="terms" class="checkbox-label">
                                    I agree to the Terms of Service and confirm all information is accurate *
                                </label>
                            </div>

                            <button type="submit" name="apply" class="btn btn-primary submit-btn">
                                Submit Application
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="auth-footer">
                <p>
                    Already have an account? 
                    <a href="login.php" class="footer-link">Sign in here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    
    <style>
    .application-page {
        min-height: 100vh;
        padding: 2rem 0;
        position: relative;
    }

    .application-container {
        max-width: 80rem;
        position: relative;
        z-index: 10;
    }

    .application-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .application-title {
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 1rem;
    }

    .application-subtitle {
        font-size: 1.25rem;
        color: var(--muted-foreground);
        max-width: 32rem;
        margin: 0 auto;
    }

    .application-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    @media (min-width: 1024px) {
        .application-grid {
            grid-template-columns: 1fr 2fr;
        }
    }

    .process-sidebar {
        position: sticky;
        top: 2rem;
        height: fit-content;
    }

    .process-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .process-desc {
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
    }

    .process-steps {
        margin-bottom: 1.5rem;
    }

    .process-step {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .step-number {
        width: 1.5rem;
        height: 1.5rem;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .step-content h4 {
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.25rem;
    }

    .step-content p {
        color: var(--muted-foreground);
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .payment-info {
        background: rgba(34, 197, 94, 0.1);
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--accent);
    }

    .payment-title {
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 0.5rem;
    }

    .payment-desc {
        color: var(--muted-foreground);
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .form-desc {
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    @media (min-width: 768px) {
        .form-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    .documents-section {
        margin: 2rem 0;
    }

    .documents-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 1rem;
    }

    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .document-group {
        display: flex;
        flex-direction: column;
    }

    .file-hint {
        color: var(--muted-foreground);
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .submit-btn {
        width: 100%;
        margin-top: 1.5rem;
    }

    .auth-footer {
        text-align: center;
        margin-top: 2rem;
    }

    .auth-footer p {
        color: var(--muted-foreground);
        font-size: 0.875rem;
    }

    .footer-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .footer-link:hover {
        color: var(--primary-glow);
    }
    </style>
</body>
</html>