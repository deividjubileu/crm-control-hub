<?php
/**
 * API: Validar Token da Extensão
 * POST /api/auth/validate-token.php
 * 
 * Header: Authorization: Bearer <token>
 * Response: { status: true/false }
 */

require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';

// CORS
Security::handleCors();

// Obter token
$token = Auth::getBearerToken();

if (!$token) {
    Response::error('Token não fornecido', 401);
}

// Validar token
$user = Auth::validateToken($token);

if (!$user) {
    Response::error('Token inválido ou expirado', 401);
}

// Verificar se ainda tem licença ativa
$license = Auth::getUserActiveLicense($user['id']);

if (!$license) {
    Response::error('Licença expirada', 403);
}

Response::success(['valid' => true]);
