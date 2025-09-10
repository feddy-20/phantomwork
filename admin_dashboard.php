<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'connection.php';

// Manage applications
$applications = $conn->query("SELECT * FROM technician_applications ORDER BY application_date DESC");

// Approve/reject application
if (isset($_POST['approve'])) {
    $app_id = $_POST['app_id'];
    $app = $conn->query("SELECT * FROM technician_applications WHERE id = $app_id")->fetch_assoc();
    $password = password_hash('defaultpass', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role) VALUES (?, ?, ?, ?, 'technician')");
    $stmt->bind_param("ssss", $app['name'], $app['email'], $password, $app['phone']);
    $stmt->execute();
    $tech_id = $stmt->insert_id;
    $stmt->close();

    $conn->query("INSERT INTO technician_details (user_id) VALUES ($tech_id)");
    $conn->query("UPDATE technician_applications SET status = 'approved' WHERE id = $app_id");

    $success = "Application approved. Credentials: Username: {$app['email']}, Password: defaultpass";
}

if (isset($_POST['reject'])) {
    $app_id = $_POST['app_id'];
    $conn->query("UPDATE technician_applications SET status = 'rejected' WHERE id = $app_id");
    $success = "Application rejected successfully.";
}

// Verify payments
$payments = $conn->query("SELECT p.id, p.order_id, p.amount, p.verification_file, p.verified, u.username as client_name FROM payments p JOIN orders o ON p.order_id = o.id JOIN users u ON o.client_id = u.id WHERE verified = 0");

// Verify payment
if (isset($_POST['verify_payment'])) {
    $payment_id = $_POST['payment_id'];
    $order_id = $_POST['order_id'];
    $conn->query("UPDATE payments SET verified = 1 WHERE id = $payment_id");
    $conn->query("UPDATE orders SET status = 'in_progress' WHERE id = $order_id");
    $success = "Payment verified and order status updated.";
}

// Pay technicians
$tech_payments = $conn->query("SELECT tp.id, tp.order_id, tp.amount, tp.paid, u.username as technician_name FROM technician_payments tp JOIN order_proposals op ON tp.order_id = op.order_id JOIN users u ON op.technician_id = u.id WHERE paid = 0");

// Pay technician
if (isset($_POST['pay_technician'])) {
    $tp_id = $_POST['tp_id'];
    $conn->query("UPDATE technician_payments SET paid = 1, paid_at = NOW() WHERE id = $tp_id");
    $success = "Technician payment processed successfully.";
}

// Get all orders with details
$all_orders = $conn->query("
    SELECT o.*, u.username as client_name, s.name as service_name,
           COUNT(op.id) as proposal_count,
           (SELECT COUNT(*) FROM payments p WHERE p.order_id = o.id AND p.verified = 1) as verified_payments
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN order_proposals op ON o.id = op.order_id 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");

// Get all proposals with details  
$all_proposals = $conn->query("
    SELECT op.*, o.description as order_description, o.status as order_status, s.name as service_name,
           uc.username as client_name, ut.username as technician_name
    FROM order_proposals op 
    JOIN orders o ON op.order_id = o.id 
    LEFT JOIN services s ON o.service_id = s.id
    JOIN users uc ON o.client_id = uc.id 
    JOIN users ut ON op.technician_id = ut.id 
    ORDER BY op.proposed_at DESC
");

// Get platform statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'total_proposals' => $conn->query("SELECT COUNT(*) as count FROM order_proposals")->fetch_assoc()['count'],
    'pending_applications' => $conn->query("SELECT COUNT(*) as count FROM technician_applications WHERE status = 'pending'")->fetch_assoc()['count'],
    'unverified_payments' => $conn->query("SELECT COUNT(*) as count FROM payments WHERE verified = 0")->fetch_assoc()['count'],
    'completed_orders' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count']
];

// Get recent activity (last 50 activities)
$recent_activity = $conn->query("
    (SELECT 'order_created' as activity_type, o.created_at, u.username as user_name, 
     CONCAT('Order for ', s.name, ' service created') as description, o.id as related_id
     FROM orders o JOIN users u ON o.client_id = u.id LEFT JOIN services s ON o.service_id = s.id)
    UNION ALL
    (SELECT 'proposal_submitted' as activity_type, op.proposed_at as created_at, u.username as user_name,
     CONCAT('Proposal submitted for order #', op.order_id) as description, op.id as related_id
     FROM order_proposals op JOIN users u ON op.technician_id = u.id)
    UNION ALL
    (SELECT 'payment_made' as activity_type, o.created_at, u.username as user_name,
     CONCAT('Payment made for order #', p.order_id) as description, p.id as related_id
     FROM payments p JOIN orders o ON p.order_id = o.id JOIN users u ON o.client_id = u.id)
    UNION ALL
    (SELECT 'application_submitted' as activity_type, ta.application_date as created_at, ta.name as user_name,
     'Technician application submitted' as description, ta.id as related_id
     FROM technician_applications ta)
    ORDER BY created_at DESC LIMIT 50
");

// Manage users (list and delete)
$users = $conn->query("SELECT id, username, email, role FROM users WHERE role != 'admin' ORDER BY username");

if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $conn->query("DELETE FROM users WHERE id = $user_id");
    $success = "User deleted successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PhantomWork</title>
    <meta name="description" content="Manage PhantomWork platform operations, applications, and payments.">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container dashboard-nav">
            <div class="nav-brand">
                <img src="pic2.png" alt="PhantomWork" class="logo">
                <span class="brand-text">PhantomWork Admin</span>
            </div>
            <div class="nav-user">
                <a href="logout.php" class="btn btn-outline logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="admin-header">
            <h1 class="admin-title text-gradient">Admin Dashboard</h1>
            <p class="admin-desc">Manage applications, payments, and platform operations</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-alert">
                <span>✅</span>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <div class="admin-tabs">
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab('overview')">Overview</button>
                <button class="tab-btn" onclick="switchTab('orders')">All Orders</button>
                <button class="tab-btn" onclick="switchTab('proposals')">Proposals</button>
                <button class="tab-btn" onclick="switchTab('activity')">Activity</button>
                <button class="tab-btn" onclick="switchTab('applications')">Applications</button>
                <button class="tab-btn" onclick="switchTab('payments')">Payments</button>
                <button class="tab-btn" onclick="switchTab('technician-payments')">Tech Payments</button>
                <button class="tab-btn" onclick="switchTab('users')">Users</button>
            </div>

            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['total_users']; ?></h3>
                            <p class="stat-label">Total Users</p>
                        </div>
                    </div>
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['total_orders']; ?></h3>
                            <p class="stat-label">Total Orders</p>
                        </div>
                    </div>
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">💼</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['total_proposals']; ?></h3>
                            <p class="stat-label">Total Proposals</p>
                        </div>
                    </div>
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['pending_applications']; ?></h3>
                            <p class="stat-label">Pending Applications</p>
                        </div>
                    </div>
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['unverified_payments']; ?></h3>
                            <p class="stat-label">Unverified Payments</p>
                        </div>
                    </div>
                    <div class="stat-card elevated-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $stats['completed_orders']; ?></h3>
                            <p class="stat-label">Completed Orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Orders Tab -->
            <div id="orders" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">All Orders</h3>
                    <p class="card-desc">Complete overview of all orders in the system</p>
                    
                    <div class="orders-list">
                        <?php if ($all_orders->num_rows > 0): ?>
                            <?php while ($order = $all_orders->fetch_assoc()): ?>
                                <div class="order-item elevated-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4 class="order-title">Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['service_name']); ?></h4>
                                            <p class="order-client">Client: <?php echo htmlspecialchars($order['client_name']); ?></p>
                                            <p class="order-location">Location: <?php echo htmlspecialchars($order['location']); ?></p>
                                            <p class="order-proposals"><?php echo $order['proposal_count']; ?> Proposals</p>
                                            <?php if ($order['verified_payments'] > 0): ?>
                                                <p class="order-payment">✅ Payment Verified</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="order-meta">
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                            <p class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($order['description'])): ?>
                                        <div class="order-description">
                                            <p><?php echo nl2br(htmlspecialchars(substr($order['description'], 0, 200))); ?><?php echo strlen($order['description']) > 200 ? '...' : ''; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No orders found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- All Proposals Tab -->
            <div id="proposals" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">All Proposals</h3>
                    <p class="card-desc">View all technician proposals and their status</p>
                    
                    <div class="proposals-list">
                        <?php if ($all_proposals->num_rows > 0): ?>
                            <?php while ($proposal = $all_proposals->fetch_assoc()): ?>
                                <div class="proposal-item elevated-card">
                                    <div class="proposal-header">
                                        <div class="proposal-info">
                                            <h4 class="proposal-title">Proposal for Order #<?php echo $proposal['order_id']; ?> - <?php echo htmlspecialchars($proposal['service_name']); ?></h4>
                                            <p class="proposal-tech">Technician: <?php echo htmlspecialchars($proposal['technician_name']); ?></p>
                                            <p class="proposal-client">Client: <?php echo htmlspecialchars($proposal['client_name']); ?></p>
                                            <p class="proposal-amount">Proposed Amount: $<?php echo number_format($proposal['proposed_amount'], 2); ?></p>
                                        </div>
                                        <div class="proposal-meta">
                                            <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                                <?php echo ucfirst($proposal['status']); ?>
                                            </span>
                                            <p class="proposal-date"><?php echo date('M j, Y', strtotime($proposal['proposed_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="proposal-description">
                                        <h5>Order Description:</h5>
                                        <p><?php echo nl2br(htmlspecialchars(substr($proposal['order_description'], 0, 200))); ?><?php echo strlen($proposal['order_description']) > 200 ? '...' : ''; ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No proposals found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Tab -->
            <div id="activity" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">Recent Activity</h3>
                    <p class="card-desc">Track all platform activities in real-time</p>
                    
                    <div class="activity-list">
                        <?php if ($recent_activity->num_rows > 0): ?>
                            <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php 
                                        switch($activity['activity_type']) {
                                            case 'order_created': echo '📋'; break;
                                            case 'proposal_submitted': echo '💼'; break;
                                            case 'payment_made': echo '💰'; break;
                                            case 'application_submitted': echo '👨‍💻'; break;
                                            default: echo '📝'; break;
                                        }
                                        ?>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-description">
                                            <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                            <?php echo htmlspecialchars($activity['description']); ?>
                                        </p>
                                        <p class="activity-time"><?php echo date('M j, Y \a\t g:i A', strtotime($activity['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No recent activity found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Technician Applications Tab -->
            <div id="applications" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">Technician Applications</h3>
                    <p class="card-desc">Review and manage pending technician applications</p>
                    
                    <div class="applications-list">
                        <?php if ($applications->num_rows > 0): ?>
                            <?php while ($app = $applications->fetch_assoc()): ?>
                                <div class="application-item elevated-card">
                                    <div class="app-header">
                                        <div class="app-info">
                                            <h4 class="app-name"><?php echo htmlspecialchars($app['name']); ?></h4>
                                            <p class="app-email"><?php echo htmlspecialchars($app['email']); ?></p>
                                            <p class="app-phone"><?php echo htmlspecialchars($app['phone']); ?></p>
                                            <?php if (!empty($app['experience'])): ?>
                                                <p class="app-experience">Experience: <?php echo htmlspecialchars($app['experience']); ?> years</p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($app['bio'])): ?>
                                        <div class="app-bio">
                                            <h5>Bio:</h5>
                                            <p><?php echo htmlspecialchars($app['bio']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="app-actions">
                                        <button class="btn btn-outline btn-sm" onclick="viewDocuments(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['passport_file']); ?>', '<?php echo htmlspecialchars($app['certificate_file']); ?>', '<?php echo htmlspecialchars($app['fee_proof']); ?>')">
                                            View Documents
                                        </button>
                                        
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                <button type="submit" name="approve" class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                <button type="submit" name="reject" class="btn btn-destructive btn-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No applications found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Client Payments Tab -->
            <div id="payments" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">Client Payment Verification</h3>
                    <p class="card-desc">Verify client payments to proceed with services</p>
                    
                    <div class="payments-list">
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <div class="payment-item elevated-card">
                                    <div class="payment-header">
                                        <div class="payment-info">
                                            <h4 class="payment-title">Order #<?php echo $payment['order_id']; ?></h4>
                                            <p class="payment-client">Client: <?php echo htmlspecialchars($payment['client_name']); ?></p>
                                            <p class="payment-amount">Amount: $<?php echo number_format($payment['amount'], 2); ?></p>
                                        </div>
                                        <span class="status-badge status-pending">Pending Verification</span>
                                    </div>

                                    <div class="payment-actions">
                                        <button class="btn btn-outline btn-sm" onclick="viewPaymentProof('<?php echo htmlspecialchars($payment['verification_file']); ?>')">
                                            View Payment Proof
                                        </button>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $payment['order_id']; ?>">
                                            <button type="submit" name="verify_payment" class="btn btn-primary btn-sm">Verify Payment</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No pending payment verifications.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Technician Payments Tab -->
            <div id="technician-payments" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">Technician Payments</h3>
                    <p class="card-desc">Process payments to technicians for completed work</p>
                    
                    <div class="tech-payments-list">
                        <?php if ($tech_payments->num_rows > 0): ?>
                            <?php while ($payment = $tech_payments->fetch_assoc()): ?>
                                <div class="tech-payment-item elevated-card">
                                    <div class="tech-payment-header">
                                        <div class="tech-payment-info">
                                            <h4 class="tech-payment-title">Order #<?php echo $payment['order_id']; ?></h4>
                                            <p class="tech-payment-name">Technician: <?php echo htmlspecialchars($payment['technician_name']); ?></p>
                                            <p class="tech-payment-amount">Amount: $<?php echo number_format($payment['amount'], 2); ?></p>
                                        </div>
                                        <span class="status-badge status-pending">Pending Payment</span>
                                    </div>

                                    <div class="tech-payment-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tp_id" value="<?php echo $payment['id']; ?>">
                                            <button type="submit" name="pay_technician" class="btn btn-primary btn-sm">Process Payment</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No pending technician payments.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Users Management Tab -->
            <div id="users" class="tab-content">
                <div class="glass-card">
                    <h3 class="card-title text-gradient">User Management</h3>
                    <p class="card-desc">Manage registered users and their accounts</p>
                    
                    <div class="users-list">
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <div class="user-item elevated-card">
                                    <div class="user-header">
                                        <div class="user-info">
                                            <h4 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h4>
                                            <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                        <div class="user-meta">
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="user-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-destructive btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete User
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No users found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Modal -->
    <div id="documentsModal" class="modal" style="display: none;">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3 class="modal-title">Application Documents</h3>
                <button class="modal-close" onclick="closeModal('documentsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="documents-grid">
                    <div class="document-item">
                        <h5>Passport Photo</h5>
                        <img id="passportImg" src="" alt="Passport" class="document-image">
                    </div>
                    <div class="document-item">
                        <h5>Certificate</h5>
                        <img id="certificateImg" src="" alt="Certificate" class="document-image">
                    </div>
                    <div class="document-item">
                        <h5>Fee Payment Proof</h5>
                        <img id="feeProofImg" src="" alt="Fee Proof" class="document-image">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Proof Modal -->
    <div id="paymentProofModal" class="modal" style="display: none;">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3 class="modal-title">Payment Verification</h3>
                <button class="modal-close" onclick="closeModal('paymentProofModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-proof-container">
                    <img id="paymentProofImg" src="" alt="Payment Proof" class="document-image">
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function switchTab(tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Remove active class from all tab buttons
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => btn.classList.remove('active'));
        
        // Show selected tab content
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
    }

    function viewDocuments(appId, passport, certificate, feeProof) {
        document.getElementById('passportImg').src = passport;
        document.getElementById('certificateImg').src = certificate;
        document.getElementById('feeProofImg').src = feeProof;
        document.getElementById('documentsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function viewPaymentProof(proofFile) {
        document.getElementById('paymentProofImg').src = proofFile;
        document.getElementById('paymentProofModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    </script>
    <link rel="stylesheet" href="dashboard.css">
    
    <style>
    .admin-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .admin-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .admin-desc {
        color: var(--muted-foreground);
        font-size: 1.125rem;
    }

    .admin-tabs {
        background: var(--card);
        border-radius: 0.75rem;
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .tab-navigation {
        display: flex;
        background: var(--muted);
        border-bottom: 1px solid var(--border);
        overflow-x: auto;
    }

    .tab-btn {
        flex: 1;
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        color: var(--muted-foreground);
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition-smooth);
        white-space: nowrap;
    }

    .tab-btn:hover {
        background: var(--card);
        color: var(--foreground);
    }

    .tab-btn.active {
        background: var(--primary);
        color: var(--primary-foreground);
    }

    .tab-content {
        display: none;
        padding: 2rem;
    }

    .tab-content.active {
        display: block;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        text-align: left;
    }

    .stat-icon {
        font-size: 2rem;
        opacity: 0.8;
    }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin: 0;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin: 0;
    }

    /* Orders, Proposals, Activity Lists */
    .orders-list,
    .proposals-list,
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .order-item,
    .proposal-item {
        padding: 1.5rem;
    }

    .order-header,
    .proposal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .order-info,
    .proposal-info {
        flex: 1;
    }

    .order-title,
    .proposal-title {
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .order-client,
    .order-budget,
    .order-proposals,
    .order-payment,
    .proposal-tech,
    .proposal-client,
    .proposal-amount {
        color: var(--muted-foreground);
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .order-meta,
    .proposal-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }

    .order-date,
    .proposal-date {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin: 0;
    }

    .order-description,
    .proposal-message {
        background: var(--muted);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .proposal-message h5 {
        color: var(--foreground);
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        font-size: 0.875rem;
    }

    .proposal-message p {
        color: var(--muted-foreground);
        line-height: 1.5;
        margin: 0;
        font-size: 0.875rem;
    }

    /* Activity Items */
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: var(--muted);
        border-radius: 0.5rem;
        border-left: 3px solid var(--primary);
    }

    .activity-icon {
        font-size: 1.5rem;
        opacity: 0.8;
        flex-shrink: 0;
    }

    .activity-content {
        flex: 1;
    }

    .activity-description {
        color: var(--foreground);
        margin: 0 0 0.25rem 0;
        line-height: 1.4;
    }

    .activity-description strong {
        color: var(--primary);
    }

    .activity-time {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin: 0;
    }

    /* Application Items */
    .applications-list,
    .payments-list,
    .tech-payments-list,
    .users-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .application-item,
    .payment-item,
    .tech-payment-item,
    .user-item {
        padding: 1.5rem;
    }

    .app-header,
    .payment-header,
    .tech-payment-header,
    .user-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .app-info,
    .payment-info,
    .tech-payment-info,
    .user-info {
        flex: 1;
    }

    .app-name,
    .payment-title,
    .tech-payment-title,
    .user-name {
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.25rem;
    }

    .app-email,
    .app-phone,
    .app-experience,
    .payment-client,
    .payment-amount,
    .tech-payment-name,
    .tech-payment-amount,
    .user-email {
        color: var(--muted-foreground);
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .app-bio {
        background: var(--muted);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .app-bio h5 {
        color: var(--foreground);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .app-bio p {
        color: var(--muted-foreground);
        line-height: 1.5;
        margin: 0;
    }

    .app-actions,
    .payment-actions,
    .tech-payment-actions,
    .user-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Role Badges */
    .role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .role-client {
        background: rgba(59, 130, 246, 0.2);
        color: var(--primary);
    }

    .role-technician {
        background: rgba(139, 92, 246, 0.2);
        color: var(--secondary);
    }

    /* Button Variants */
    .btn-destructive {
        background: var(--destructive);
        color: var(--destructive-foreground);
    }

    .btn-destructive:hover {
        background: rgba(239, 68, 68, 0.9);
        transform: scale(1.05);
    }

    /* Document Images */
    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .document-item {
        text-align: center;
    }

    .document-item h5 {
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .document-image {
        width: 100%;
        max-width: 300px;
        height: auto;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
    }

    .payment-proof-container {
        text-align: center;
    }

    /* Success Alert */
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

    /* Responsive */
    @media (max-width: 768px) {
        .tab-navigation {
            flex-direction: column;
        }

        .tab-btn {
            flex: none;
        }

        .tab-content {
            padding: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .order-header,
        .proposal-header,
        .app-header,
        .payment-header,
        .tech-payment-header,
        .user-header {
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .order-meta,
        .proposal-meta {
            align-items: flex-start;
        }

        .app-actions,
        .payment-actions,
        .tech-payment-actions,
        .user-actions {
            width: 100%;
        }

        .app-actions .btn,
        .payment-actions .btn,
        .tech-payment-actions .btn,
        .user-actions .btn {
            flex: 1;
        }
    }
    </style>
</body>
</html>