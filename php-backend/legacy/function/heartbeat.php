<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$license = $_POST['license'] ?? '';

$stmt = $conn->prepare("SELECT active FROM licenses WHERE license_key=?");
$stmt->bind_param("s", $license);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => false]);
    exit;
}

$row = $res->fetch_assoc();

if ($row['active'] !== 'true') {
    echo json_encode(['status' => false]);
    exit;
}

echo json_encode(['status' => true]);
