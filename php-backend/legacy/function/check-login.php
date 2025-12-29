<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['status' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM admin WHERE username=? AND status='true'");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => false]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => false]);
    exit;
}

$_SESSION['login'] = true;
$_SESSION['admin_id'] = $user['id'];

echo json_encode(['status' => true]);
