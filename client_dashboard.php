<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit();
}
include 'connection.php';
$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $phone);
$stmt->fetch();
$stmt->close();

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
    header("Location: client_dashboard.php");
}

// Fetch order history
$orders = $conn->query("SELECT o.id, s.name as service, o.description, o.status FROM orders o JOIN services s ON o.service_id = s.id WHERE client_id = $user_id ORDER BY o.created_at DESC");

// Fetch technicians
$technicians = $conn->query("SELECT u.id, u.username, u.phone, td.rating FROM users u JOIN technician_details td ON u.id = td.user_id WHERE role = 'technician'");

// Create order
if (isset($_POST['create_order'])) {
    $service_id = $_POST['service_id'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    
    if (!empty($service_id) && !empty($description)) {
        $pictures = [];

        if (!empty($_FILES['pictures']['name'][0])) {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['pictures']['name'] as $key => $name) {
                if ($_FILES['pictures']['error'][$key] === 0) {
                    $path = $uploadDir . basename($name);
                    if (move_uploaded_file($_FILES['pictures']['tmp_name'][$key], $path)) {
                        $pictures[] = $path;
                    }
                }
            }
        }
        $pictures_json = json_encode($pictures);

        $stmt = $conn->prepare("INSERT INTO orders (client_id, service_id, description, pictures, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $service_id, $description, $pictures_json, $location);
        $stmt->execute();
        $stmt->close();
        header("Location: client_dashboard.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - PhantomWork</title>
    <meta name="description" content="Manage your service requests and connect with professional technicians on PhantomWork.">
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
        <div class="dashboard-grid">
            <!-- Left Column - Profile & Create Order -->
            <div class="sidebar">
                <!-- Profile Card -->
                <div class="glass-card profile-card">
                    <h3 class="card-title text-gradient">Your Profile</h3>
                    <p class="card-desc">Manage your account information</p>
                    
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

                <!-- Create Order -->
                <div class="glass-card order-form-card">
                    <h3 class="card-title text-gradient">Create Service Request</h3>
                    <p class="card-desc">Post a new job for technicians to bid on</p>
                    
                    <form method="POST" enctype="multipart/form-data" class="order-form">
                        <div class="form-group">
                            <label class="form-label">Service Type</label>
                            <select name="service_id" required class="form-input">
                                <option value="">Select service</option>
                                <?php
                                $services = $conn->query("SELECT id, name FROM services");
                                while ($s = $services->fetch_assoc()) {
                                    echo "<option value='{$s['id']}'>{$s['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea
                                name="description"
                                placeholder="Describe the work needed..."
                                class="form-input"
                                rows="3"
                                required
                            ></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input
                                name="location"
                                placeholder="Enter your location"
                                class="form-input"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Upload Photos (Optional)</label>
                            <input
                                name="pictures[]"
                                type="file"
                                multiple
                                accept="image/*"
                                class="form-input"
                            >
                        </div>
                        
                        <button type="submit" name="create_order" class="btn btn-primary">Create Request</button>
                    </form>
                </div>
            </div>

            <!-- Right Column - Orders & Technicians -->
            <div class="main-content">
                <!-- Order History -->
                <div class="glass-card orders-card">
                    <h3 class="card-title text-gradient">Your Orders</h3>
                    <p class="card-desc">Track and manage your service requests</p>
                    
                    <div class="orders-list">
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <div class="order-item elevated-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4 class="order-title">Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['service']); ?></h4>
                                            <p class="order-desc"><?php echo htmlspecialchars($order['description']); ?></p>
                                        </div>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="order_proposals.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">View Proposals</a>
                                        <?php if ($order['status'] == 'accepted'): ?>
                                            <a href="upload_payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">Upload Payment</a>
                                        <?php endif; ?>
                                        <?php if ($order['status'] == 'in_progress'): ?>
                                            <a href="rate_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">Rate & Complete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No orders yet. Create your first service request!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Available Technicians -->
                <div class="glass-card technicians-card">
                    <h3 class="card-title text-gradient">Available Technicians</h3>
                    <p class="card-desc">Browse verified professionals in your area</p>
                    
                    <div class="technicians-grid">
                        <?php if ($technicians->num_rows > 0): ?>
                            <?php while ($tech = $technicians->fetch_assoc()): ?>
                                <div class="technician-card elevated-card">
                                    <div class="tech-avatar">
                                        <?php echo strtoupper(substr($tech['username'], 0, 2)); ?>
                                    </div>
                                    <div class="tech-info">
                                        <h4 class="tech-name"><?php echo htmlspecialchars($tech['username']); ?></h4>
                                        <p class="tech-phone"><?php echo htmlspecialchars($tech['phone']); ?></p>
                                        <div class="tech-rating">
                                            <span class="rating-star">★</span>
                                            <span class="rating-value"><?php echo number_format($tech['rating'], 1); ?></span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline btn-sm">View Profile</button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No technicians available at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <link rel="stylesheet" href="dashboard.css">
</body>
</html>