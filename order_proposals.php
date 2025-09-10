<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit();
}
include 'connection.php';
$order_id = $_GET['order_id'];

// Fetch order details
$order_stmt = $conn->prepare("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

// Fetch proposals
$proposals = $conn->query("SELECT op.id, u.username, u.phone, op.proposed_amount, op.status, td.rating FROM order_proposals op JOIN users u ON op.technician_id = u.id LEFT JOIN technician_details td ON u.id = td.user_id WHERE order_id = $order_id ORDER BY op.proposed_amount ASC");

// Accept proposal
if (isset($_POST['accept_proposal'])) {
    $proposal_id = $_POST['proposal_id'];
    $amount = $_POST['amount'];

    // Update proposal to accepted, others to rejected
    $conn->query("UPDATE order_proposals SET status = 'rejected' WHERE order_id = $order_id AND id != $proposal_id");
    $conn->query("UPDATE order_proposals SET status = 'accepted' WHERE id = $proposal_id");
    $conn->query("UPDATE orders SET status = 'accepted' WHERE id = $order_id");

    // Create payment entry
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount) VALUES (?, ?)");
    $stmt->bind_param("id", $order_id, $amount);
    $stmt->execute();
    $stmt->close();
    
    echo "<script>showMessage('Proposal accepted! Please proceed to payment.', 'success'); setTimeout(() => { window.location.href = 'upload_payment.php?order_id=$order_id'; }, 2000);</script>";
}

// Cancel order
if (isset($_POST['cancel_order'])) {
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
    echo "<script>showMessage('Order cancelled successfully!', 'success'); setTimeout(() => { window.location.href = 'client_dashboard.php'; }, 1500);</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Proposals - PhantomWork</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="overlay">
        <!-- Navigation -->
        <nav class="main-nav">
            <div class="nav-container">
                <div class="nav-brand">
                    <h2>PhantomWork</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="client_dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php" class="logout-btn">Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="dashboard-container">
            <div class="page-header">
                <h1>Order Proposals</h1>
                <p>Review and manage proposals for your service request</p>
            </div>

            <!-- Order Details Card -->
            <div class="glass-card">
                <h3>Order Details</h3>
                <div class="order-info">
                    <div class="info-item">
                        <span class="label">Order ID:</span>
                        <span class="value">#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Service:</span>
                        <span class="value"><?php echo $order['service_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Description:</span>
                        <span class="value"><?php echo $order['description']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Location:</span>
                        <span class="value"><?php echo $order['location']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Status:</span>
                        <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Proposals Section -->
            <div class="glass-card">
                <div class="card-header">
                    <h3>Received Proposals</h3>
                    <span class="proposal-count"><?php echo $proposals->num_rows; ?> proposals</span>
                </div>

                <?php if ($proposals->num_rows > 0): ?>
                    <div class="proposals-grid">
                        <?php while ($row = $proposals->fetch_assoc()): ?>
                            <div class="proposal-card <?php echo $row['status'] == 'accepted' ? 'accepted' : ($row['status'] == 'rejected' ? 'rejected' : 'pending'); ?>">
                                <div class="proposal-header">
                                    <div class="technician-info">
                                        <h4><?php echo htmlspecialchars($row['username']); ?></h4>
                                        <div class="rating">
                                            <?php 
                                            $rating = $row['rating'] ?? 0;
                                            for($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '★' : '☆';
                                            }
                                            ?>
                                            <span>(<?php echo number_format($rating, 1); ?>)</span>
                                        </div>
                                        <p class="phone">📞 <?php echo htmlspecialchars($row['phone']); ?></p>
                                    </div>
                                    <div class="proposal-status">
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="proposal-amount">
                                    <span class="amount-label">Proposed Amount</span>
                                    <span class="amount-value">$<?php echo number_format($row['proposed_amount'], 2); ?></span>
                                </div>

                                <?php if ($row['status'] == 'pending'): ?>
                                    <form method="POST" class="proposal-action" onsubmit="return confirmAccept('<?php echo htmlspecialchars($row['username']); ?>', <?php echo $row['proposed_amount']; ?>)">
                                        <input type="hidden" name="proposal_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="amount" value="<?php echo $row['proposed_amount']; ?>">
                                        <button type="submit" name="accept_proposal" class="btn btn-success">
                                            Accept Proposal
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>No Proposals Yet</h3>
                        <p>Technicians haven't submitted proposals for this order yet. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="client_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if ($order['status'] == 'pending' || $order['status'] == 'proposed'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirmCancel()">
                        <button type="submit" name="cancel_order" class="btn btn-danger">Cancel Order</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Message Container -->
        <div id="messageContainer"></div>
    </div>

    <script>
        function confirmAccept(technicianName, amount) {
            return confirm(`Are you sure you want to accept the proposal from ${technicianName} for $${amount}?`);
        }

        function confirmCancel() {
            return confirm('Are you sure you want to cancel this order? This action cannot be undone.');
        }

        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message-${type}`;
            messageDiv.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">×</button>
            `;
            container.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Auto refresh proposals every 30 seconds
        setInterval(() => {
            if (!document.querySelector('.accepted')) {
                location.reload();
            }
        }, 30000);
    </script>

    <style>
        .proposals-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .proposal-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .proposal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
        }

        .proposal-card.accepted {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .proposal-card.rejected {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
            opacity: 0.7;
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .technician-info h4 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .rating {
            color: #ffc107;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }

        .phone {
            color: #888;
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }

        .proposal-amount {
            text-align: center;
            padding: 1rem;
            background: rgba(0, 123, 255, 0.1);
            border-radius: 8px;
            margin: 1rem 0;
        }

        .amount-label {
            display: block;
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .amount-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .proposal-action {
            text-align: center;
            margin-top: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .order-info {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .label {
            font-weight: bold;
            color: #888;
        }

        .value {
            color: white;
        }

        .proposal-count {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .proposal-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .info-item {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
    </style>
</body>
</html>