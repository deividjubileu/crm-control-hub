<?php
/**
 * API: Logout da Extensão
 * POST /api/auth/logout.php
 * 
 * Header: Authorization: Bearer <token>
 * Response: { status: true }
 */

require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';
require_once __DIR__ . '/../../core/logger.php';

// CORS
Security::handleCors();

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido', 405);
}

// Obter token
$token = Auth::getBearerToken();

if ($token) {
    // Validar token para obter user_id para log
    $user = Auth::validateToken($token);
    
    // Revogar token
    Auth::revokeToken($token);
    
    if ($user) {
        Logger::logout($user['id']);
    }
}

Response::success(null, 'Logout realizado com sucesso');
