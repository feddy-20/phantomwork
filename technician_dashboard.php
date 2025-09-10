<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'technician') {
    header("Location: login.php");
    exit();
}
include 'connection.php';
$user_id = $_SESSION['user_id'];

// Fetch details
$stmt = $conn->prepare("SELECT username, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $phone);
$stmt->fetch();
$stmt->close();

// Fetch technician stats
$stats = $conn->query("SELECT rating, num_ratings FROM technician_details WHERE user_id = $user_id")->fetch_assoc();
$completed_jobs = $conn->query("SELECT COUNT(*) as count FROM orders o JOIN order_proposals op ON o.id = op.order_id WHERE op.technician_id = $user_id AND o.status = 'completed'")->fetch_assoc()['count'];
$total_earnings = $conn->query("SELECT SUM(tp.amount) as total FROM technician_payments tp JOIN order_proposals op ON tp.order_id = op.order_id WHERE op.technician_id = $user_id AND tp.paid = 1")->fetch_assoc()['total'] ?? 0;

// Update profile
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_phone = trim($_POST['phone']);
    $new_password = $_POST['password'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_username, $new_phone, $hashed_password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_username, $new_phone, $user_id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: technician_dashboard.php");
}

// Fetch active orders (pending or paid that technician can propose or has accepted)
$active_orders = $conn->query("SELECT o.id, s.name as service, o.description, o.pictures, o.location, o.status FROM orders o JOIN services s ON o.service_id = s.id WHERE status IN ('pending', 'paid') ORDER BY o.created_at DESC");

// Fetch progress: completed orders, ratings, earnings
$progress = $conn->query("SELECT o.id, r.rating, r.comment, tp.amount FROM orders o JOIN ratings r ON o.id = r.order_id JOIN technician_payments tp ON o.id = tp.order_id JOIN order_proposals op ON o.id = op.order_id WHERE op.technician_id = $user_id AND o.status = 'completed' ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - PhantomWork</title>
    <meta name="description" content="Manage your technician profile and browse available service requests on PhantomWork.">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container dashboard-nav">
            <div class="nav-brand">
                <img src="pic2.png" alt="PhantomWork" class="logo">
                <span class="brand-text">PhantomWork</span>
            </div>
            <div class="nav-user">
                <span class="user-greeting">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn btn-outline logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card glass-card">
                <div class="stat-value"><?php echo number_format($stats['rating'] ?? 0, 1); ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card glass-card">
                <div class="stat-value"><?php echo $completed_jobs; ?></div>
                <div class="stat-label">Jobs Completed</div>
            </div>
            <div class="stat-card glass-card">
                <div class="stat-value">$<?php echo number_format($total_earnings, 0); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card glass-card">
                <div class="stat-value"><?php echo $active_orders->num_rows; ?></div>
                <div class="stat-label">Available Orders</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Left Column - Profile -->
            <div class="sidebar">
                <div class="glass-card profile-card">
                    <h3 class="card-title text-gradient">Your Profile</h3>
                    <p class="card-desc">Manage your professional information</p>
                    
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($username, 0, 2)); ?>
                    </div>
                    <div class="profile-info">
                        <h4 class="profile-name"><?php echo htmlspecialchars($username); ?></h4>
                        <div class="profile-rating">
                            <span class="rating-star">★</span>
                            <span class="rating-value"><?php echo number_format($stats['rating'] ?? 0, 1); ?></span>
                            <span class="rating-count">(<?php echo $stats['num_ratings'] ?? 0; ?> reviews)</span>
                        </div>
                    </div>
                    
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input 
                                name="username"
                                value="<?php echo htmlspecialchars($username); ?>" 
                                class="form-input"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input 
                                name="phone"
                                value="<?php echo htmlspecialchars($phone); ?>"
                                class="form-input"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password (Optional)</label>
                            <input 
                                name="password"
                                type="password"
                                placeholder="Leave blank to keep current"
                                class="form-input"
                            >
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Right Column - Orders & History -->
            <div class="main-content">
                <!-- Active Orders -->
                <div class="glass-card orders-card">
                    <h3 class="card-title text-gradient">Available Orders</h3>
                    <p class="card-desc">Browse and bid on new service requests</p>
                    
                    <div class="orders-list">
                        <?php if ($active_orders->num_rows > 0): ?>
                            <?php while ($order = $active_orders->fetch_assoc()): ?>
                                <div class="order-item elevated-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4 class="order-title">Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['service']); ?></h4>
                                            <p class="order-desc"><?php echo htmlspecialchars($order['description']); ?></p>
                                            <div class="order-meta">
                                                <span class="order-location">📍 <?php echo htmlspecialchars($order['location']); ?></span>
                                            </div>
                                        </div>
                                        <span class="status-badge status-pending">Available</span>
                                    </div>
                                    
                                    <!-- Order Images -->
                                    <?php
                                    $pictures = json_decode($order['pictures'], true);
                                    if ($pictures && count($pictures) > 0):
                                    ?>
                                    <div class="order-images">
                                        <h5>Reference Images:</h5>
                                        <div class="images-list">
                                            <?php foreach ($pictures as $pic): ?>
                                                <img src="<?php echo htmlspecialchars($pic); ?>" alt="Order reference" class="order-image">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="order-actions">
                                        <button class="btn btn-primary" onclick="openProposalModal(<?php echo $order['id']; ?>)">View Details & Propose</button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No available orders at the moment. Check back later!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Work History -->
                <div class="glass-card history-card">
                    <h3 class="card-title text-gradient">Work Progress & Earnings</h3>
                    <p class="card-desc">Your completed jobs, ratings, and earnings history</p>
                    
                    <div class="history-list">
                        <?php if ($progress->num_rows > 0): ?>
                            <?php while ($job = $progress->fetch_assoc()): ?>
                                <div class="history-item elevated-card">
                                    <div class="history-header">
                                        <div class="history-info">
                                            <h4 class="history-title">Order #<?php echo $job['id']; ?></h4>
                                            <div class="history-rating">
                                                <span class="rating-star">★</span>
                                                <span class="rating-value"><?php echo $job['rating']; ?></span>
                                            </div>
                                        </div>
                                        <div class="history-amount">$<?php echo number_format($job['amount'], 0); ?></div>
                                    </div>
                                    
                                    <div class="history-comment">
                                        <p>"<?php echo htmlspecialchars($job['comment']); ?>"</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No completed jobs yet. Start bidding on orders!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="modal" style="display: none;">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3 class="modal-title">Submit Proposal</h3>
                <button class="modal-close" onclick="closeModal('proposalModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="propose_order.php" method="POST">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <div class="form-group">
                        <label class="form-label">Your Proposal Amount ($)</label>
                        <input
                            name="proposed_amount"
                            type="number"
                            step="0.01"
                            min="1"
                            required
                            class="form-input"
                            placeholder="Enter your price"
                        >
                    </div>
                    <button type="submit" name="propose" class="btn btn-primary">Submit Proposal</button>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function openProposalModal(orderId) {
        document.getElementById('modalOrderId').value = orderId;
        document.getElementById('proposalModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    </script>
    <link rel="stylesheet" href="dashboard.css">
</body>
</html>