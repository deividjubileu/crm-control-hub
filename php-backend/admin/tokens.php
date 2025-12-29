<?php
/**
 * API: Gerenciar Tokens/Sessões
 * GET /admin/tokens.php?user_id=X - Listar sessões de um usuário
 * DELETE /admin/tokens.php?id=X - Revogar sessão específica
 * DELETE /admin/tokens.php?user_id=X - Revogar todas sessões de um usuário
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/logger.php';

// CORS
Security::handleCors();

// Requer admin
$admin = Auth::requireAdmin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $userId = isset($_GET['user_id']) ? Security::sanitize($_GET['user_id'], 'int') : null;
        
        if (!$userId) {
            Response::error('user_id é obrigatório');
        }
        
        // Buscar sessões ativas do usuário
        $stmt = $pdo->prepare("
            SELECT id, device_hash, ip_address, last_seen, expires_at, created_at
            FROM tokens
            WHERE user_id = ? AND expires_at > NOW()
            ORDER BY last_seen DESC
        ");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll();
        
        Response::success($tokens);
        break;
        
    case 'DELETE':
        $tokenId = isset($_GET['id']) ? Security::sanitize($_GET['id'], 'int') : null;
        $userId = isset($_GET['user_id']) ? Security::sanitize($_GET['user_id'], 'int') : null;
        
        if ($tokenId) {
            // Revogar sessão específica
            $stmt = $pdo->prepare("DELETE FROM tokens WHERE id = ?");
            $stmt->execute([$tokenId]);
            
            // Log
            Logger::tokenRevoked($admin['id'], $tokenId);
            
            Response::success(null, 'Sessão revogada com sucesso');
        } elseif ($userId) {
            // Revogar todas as sessões do usuário
            Auth::revokeAllTokens($userId);
            
            Response::success(null, 'Todas as sessões foram revogadas');
        } else {
            Response::error('id ou user_id é obrigatório');
        }
        break;
        
    default:
        Response::error('Método não permitido', 405);
}
