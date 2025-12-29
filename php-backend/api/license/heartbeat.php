<?php
/**
 * API: Heartbeat da Extensão
 * POST /api/license/heartbeat.php
 * 
 * Header: Authorization: Bearer <token>
 * Response: { status, continue }
 * 
 * Usado para:
 * - Manter sessão ativa
 * - Verificar se licença ainda está válida
 * - Permitir controle remoto (bloquear se necessário)
 */

require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';

// CORS
Security::handleCors();

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido', 405);
}

// Obter e validar token
$token = Auth::getBearerToken();

if (!$token) {
    Response::heartbeat(false, 'Token não fornecido');
}

$user = Auth::validateToken($token);

if (!$user) {
    Response::heartbeat(false, 'Token inválido ou expirado');
}

// Verificar se usuário está ativo
if ($user['status'] !== 'active') {
    Response::heartbeat(false, 'Usuário bloqueado');
}

// Verificar licença
$license = Auth::getUserActiveLicense($user['id']);

if (!$license) {
    Response::heartbeat(false, 'Licença expirada');
}

if ($license['status'] === 'blocked') {
    Response::heartbeat(false, 'Licença bloqueada');
}

// Tudo OK - continuar operação
Response::heartbeat(true);
