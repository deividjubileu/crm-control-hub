<?php
/**
 * API: Logs do Sistema
 * GET /admin/logs.php - Listar logs
 * GET /admin/logs.php?user_id=X - Filtrar por usuário
 * GET /admin/logs.php?action=X - Filtrar por ação
 * GET /admin/logs.php?from=YYYY-MM-DD&to=YYYY-MM-DD - Filtrar por data
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/logger.php';

// CORS
Security::handleCors();

// Requer admin
Auth::requireAdmin();

// Apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método não permitido', 405);
}

// Obter filtros
$filters = [];

if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $filters['user_id'] = Security::sanitize($_GET['user_id'], 'int');
}

if (isset($_GET['action']) && $_GET['action'] !== '') {
    $filters['action'] = Security::sanitize($_GET['action'], 'string');
}

if (isset($_GET['from']) && $_GET['from'] !== '') {
    $filters['from'] = Security::sanitize($_GET['from'], 'string');
}

if (isset($_GET['to']) && $_GET['to'] !== '') {
    $filters['to'] = Security::sanitize($_GET['to'], 'string');
}

// Buscar logs
$logs = Logger::getLogs($filters);

Response::success($logs);
