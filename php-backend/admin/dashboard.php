<?php
/**
 * API: Dashboard Stats
 * GET /admin/dashboard.php
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';

// CORS
Security::handleCors();

// Requer admin
Auth::requireAdmin();

$pdo = db();

// Total de usuários
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

// Licenças por status
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'active' AND expires_at > NOW() THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'expired' OR (status = 'active' AND expires_at <= NOW()) THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked
    FROM licenses
");
$licenseStats = $stmt->fetch();

// Atividade recente
$stmt = $pdo->query("
    SELECT l.*, u.email as user_email
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 10
");
$recentActivity = $stmt->fetchAll();

// Retornar estatísticas
Response::success([
    'total_users' => (int) $totalUsers,
    'active_licenses' => (int) ($licenseStats['active'] ?? 0),
    'expired_licenses' => (int) ($licenseStats['expired'] ?? 0),
    'blocked_licenses' => (int) ($licenseStats['blocked'] ?? 0),
    'recent_activity' => $recentActivity
]);
