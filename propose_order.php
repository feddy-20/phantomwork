<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'technician') {
    header("Location: login.php");
    exit();
}
include 'connection.php';

if (isset($_POST['propose'])) {
    $order_id = $_POST['order_id'];
    $proposed_amount = $_POST['proposed_amount'];
    $technician_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO order_proposals (order_id, technician_id, proposed_amount) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $order_id, $technician_id, $proposed_amount);
    $stmt->execute();
    $stmt->close();

    $conn->query("UPDATE orders SET status = 'proposed' WHERE id = $order_id");
    header("Location: technician_dashboard.php");
}
?>