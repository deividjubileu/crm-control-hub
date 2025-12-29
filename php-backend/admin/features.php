<?php
/**
 * API: Gerenciar Features
 * GET /admin/features.php?license_id=X - Listar features de uma licença
 * GET /admin/features.php?defaults=true - Listar features padrão
 * PUT /admin/features.php - Atualizar features de uma licença
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/logger.php';
require_once __DIR__ . '/../config.php';

// CORS
Security::handleCors();

// Requer admin
$admin = Auth::requireAdmin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retornar features padrão
        if (isset($_GET['defaults']) && $_GET['defaults'] === 'true') {
            $defaults = [];
            foreach (DEFAULT_FEATURES as $feature) {
                $defaults[] = [
                    'feature_key' => $feature['feature_key'],
                    'enabled' => (bool) $feature['enabled'],
                    'value' => $feature['value']
                ];
            }
            Response::success($defaults);
        }
        
        // Listar features de uma licença
        $licenseId = isset($_GET['license_id']) ? Security::sanitize($_GET['license_id'], 'int') : null;
        
        if (!$licenseId) {
            Response::error('ID da licença é obrigatório');
        }
        
        // Verificar se licença existe
        $stmt = $pdo->prepare("SELECT id FROM licenses WHERE id = ?");
        $stmt->execute([$licenseId]);
        if (!$stmt->fetch()) {
            Response::notFound('Licença não encontrada');
        }
        
        // Buscar features
        $stmt = $pdo->prepare("SELECT * FROM features WHERE license_id = ? ORDER BY feature_key");
        $stmt->execute([$licenseId]);
        $features = $stmt->fetchAll();
        
        // Converter enabled para boolean
        foreach ($features as &$feature) {
            $feature['enabled'] = (bool) $feature['enabled'];
        }
        
        Response::success($features);
        break;
        
    case 'PUT':
        $data = Security::getJsonInput();
        
        // Validar campos obrigatórios
        if (!isset($data['license_id']) || !isset($data['features'])) {
            Response::error('license_id e features são obrigatórios');
        }
        
        $licenseId = Security::sanitize($data['license_id'], 'int');
        $features = $data['features'];
        
        if (!is_array($features)) {
            Response::error('features deve ser um array');
        }
        
        // Verificar se licença existe
        $stmt = $pdo->prepare("SELECT id FROM licenses WHERE id = ?");
        $stmt->execute([$licenseId]);
        if (!$stmt->fetch()) {
            Response::notFound('Licença não encontrada');
        }
        
        // Atualizar cada feature
        $updateStmt = $pdo->prepare("
            INSERT INTO features (license_id, feature_key, enabled, value)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), value = VALUES(value)
        ");
        
        foreach ($features as $feature) {
            if (!isset($feature['feature_key'])) {
                continue;
            }
            
            $featureKey = Security::sanitize($feature['feature_key'], 'string');
            $enabled = isset($feature['enabled']) ? (bool) $feature['enabled'] : true;
            $value = isset($feature['value']) ? Security::sanitize($feature['value'], 'int') : null;
            
            $updateStmt->execute([$licenseId, $featureKey, $enabled ? 1 : 0, $value]);
        }
        
        // Log
        Logger::featuresUpdated($admin['id'], $licenseId);
        
        // Retornar features atualizadas
        $stmt = $pdo->prepare("SELECT * FROM features WHERE license_id = ? ORDER BY feature_key");
        $stmt->execute([$licenseId]);
        $updatedFeatures = $stmt->fetchAll();
        
        // Converter enabled para boolean
        foreach ($updatedFeatures as &$feature) {
            $feature['enabled'] = (bool) $feature['enabled'];
        }
        
        Response::success($updatedFeatures, 'Features atualizadas com sucesso');
        break;
        
    default:
        Response::error('Método não permitido', 405);
}
