<?php
/**
 * API: Validar Token Admin
 * GET /admin/validate-token.php
 */

require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';

// CORS
Security::handleCors();

// Validar autenticação
$user = Auth::requireAdmin();

// Retornar dados do usuário
Response::success([
    'id' => $user['id'],
    'email' => $user['email'],
    'role' => $user['role'],
    'status' => $user['status']
]);
