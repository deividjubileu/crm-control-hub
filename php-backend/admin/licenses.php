<?php
/**
 * API: CRUD Licenças
 * GET /admin/licenses.php - Listar todas
 * GET /admin/licenses.php?id=X - Buscar por ID
 * POST /admin/licenses.php - Criar/Gerar licença
 * PUT /admin/licenses.php?id=X - Atualizar
 * DELETE /admin/licenses.php?id=X - Excluir
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
$id = isset($_GET['id']) ? Security::sanitize($_GET['id'], 'int') : null;
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Buscar licença específica com features
            $stmt = $pdo->prepare("
                SELECT l.*, u.email as user_email
                FROM licenses l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $license = $stmt->fetch();
            
            if (!$license) {
                Response::notFound('Licença não encontrada');
            }
            
            // Buscar features
            $stmt = $pdo->prepare("SELECT * FROM features WHERE license_id = ?");
            $stmt->execute([$id]);
            $license['features'] = $stmt->fetchAll();
            
            Response::success($license);
        } else {
            // Listar todas com email do usuário
            $stmt = $pdo->query("
                SELECT l.*, u.email as user_email
                FROM licenses l
                LEFT JOIN users u ON l.user_id = u.id
                ORDER BY l.created_at DESC
            ");
            $licenses = $stmt->fetchAll();
            Response::success($licenses);
        }
        break;
        
    case 'POST':
        $data = Security::getJsonInput();
        
        // Validar campos obrigatórios
        $error = Security::validateRequired($data, ['user_id', 'expires_at']);
        if ($error) {
            Response::error($error);
        }
        
        $userId = Security::sanitize($data['user_id'], 'int');
        $expiresAt = Security::sanitize($data['expires_at'], 'string');
        $maxDevices = Security::sanitize($data['max_devices'] ?? 1, 'int');
        
        // Verificar se usuário existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            Response::error('Usuário não encontrado');
        }
        
        // Validar data de expiração
        if (strtotime($expiresAt) === false) {
            Response::error('Data de expiração inválida');
        }
        
        // Gerar chave de licença
        $licenseKey = Auth::generateLicenseKey();
        
        // Criar licença
        $stmt = $pdo->prepare("
            INSERT INTO licenses (license_key, user_id, status, expires_at, max_devices)
            VALUES (?, ?, 'active', ?, ?)
        ");
        $stmt->execute([$licenseKey, $userId, $expiresAt, max(1, $maxDevices)]);
        
        $licenseId = $pdo->lastInsertId();
        
        // Criar features padrão
        $featureStmt = $pdo->prepare("
            INSERT INTO features (license_id, feature_key, enabled, value)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach (DEFAULT_FEATURES as $feature) {
            $featureStmt->execute([
                $licenseId,
                $feature['feature_key'],
                $feature['enabled'] ? 1 : 0,
                $feature['value']
            ]);
        }
        
        // Log
        Logger::licenseCreated($admin['id'], $licenseKey);
        
        // Retornar licença criada
        $stmt = $pdo->prepare("
            SELECT l.*, u.email as user_email
            FROM licenses l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.id = ?
        ");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch();
        
        // Buscar features
        $stmt = $pdo->prepare("SELECT * FROM features WHERE license_id = ?");
        $stmt->execute([$licenseId]);
        $license['features'] = $stmt->fetchAll();
        
        Response::success($license, 'Licença criada com sucesso', 201);
        break;
        
    case 'PUT':
        if (!$id) {
            Response::error('ID da licença é obrigatório');
        }
        
        // Verificar se licença existe
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        $license = $stmt->fetch();
        
        if (!$license) {
            Response::notFound('Licença não encontrada');
        }
        
        $data = Security::getJsonInput();
        
        $updates = [];
        $params = [];
        
        if (isset($data['status']) && in_array($data['status'], ['active', 'expired', 'blocked'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (isset($data['expires_at'])) {
            if (strtotime($data['expires_at']) === false) {
                Response::error('Data de expiração inválida');
            }
            $updates[] = "expires_at = ?";
            $params[] = $data['expires_at'];
        }
        
        if (isset($data['max_devices'])) {
            $updates[] = "max_devices = ?";
            $params[] = max(1, Security::sanitize($data['max_devices'], 'int'));
        }
        
        if (empty($updates)) {
            Response::error('Nenhum campo para atualizar');
        }
        
        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE licenses SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);
        
        // Log
        Logger::licenseUpdated($admin['id'], $id);
        
        // Retornar licença atualizada
        $stmt = $pdo->prepare("
            SELECT l.*, u.email as user_email
            FROM licenses l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        
        Response::success($stmt->fetch(), 'Licença atualizada com sucesso');
        break;
        
    case 'DELETE':
        if (!$id) {
            Response::error('ID da licença é obrigatório');
        }
        
        // Verificar se licença existe
        $stmt = $pdo->prepare("SELECT id FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::notFound('Licença não encontrada');
        }
        
        // Excluir licença (cascade remove features)
        $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        Logger::licenseDeleted($admin['id'], $id);
        
        Response::success(null, 'Licença excluída com sucesso');
        break;
        
    default:
        Response::error('Método não permitido', 405);
}
