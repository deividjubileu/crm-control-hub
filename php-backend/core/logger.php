<?php
/**
 * SISTEMA DE LOGS
 * Registro de ações do sistema
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

class Logger {
    
    /**
     * Registrar ação
     */
    public static function log(string $action, int $userId = null, string $details = null): void {
        if (!LOG_ENABLED) {
            return;
        }
        
        // Verificar se ação deve ser logada
        if (!in_array($action, LOG_ACTIONS)) {
            return;
        }
        
        $pdo = db();
        $ip = Auth::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, details, ip, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $action, $details, $ip, $userAgent]);
    }
    
    /**
     * Log de login bem-sucedido
     */
    public static function loginSuccess(int $userId): void {
        self::log('login', $userId, 'Login bem-sucedido');
    }
    
    /**
     * Log de tentativa de login falha
     */
    public static function loginFailed(string $email): void {
        self::log('login_failed', null, "Tentativa falha para: $email");
    }
    
    /**
     * Log de logout
     */
    public static function logout(int $userId): void {
        self::log('logout', $userId);
    }
    
    /**
     * Log de criação de licença
     */
    public static function licenseCreated(int $userId, string $licenseKey): void {
        self::log('license_created', $userId, "Licença criada: $licenseKey");
    }
    
    /**
     * Log de atualização de licença
     */
    public static function licenseUpdated(int $userId, int $licenseId): void {
        self::log('license_updated', $userId, "Licença atualizada: #$licenseId");
    }
    
    /**
     * Log de exclusão de licença
     */
    public static function licenseDeleted(int $userId, int $licenseId): void {
        self::log('license_deleted', $userId, "Licença excluída: #$licenseId");
    }
    
    /**
     * Log de criação de usuário
     */
    public static function userCreated(int $adminId, string $email): void {
        self::log('user_created', $adminId, "Usuário criado: $email");
    }
    
    /**
     * Log de atualização de usuário
     */
    public static function userUpdated(int $adminId, int $userId): void {
        self::log('user_updated', $adminId, "Usuário atualizado: #$userId");
    }
    
    /**
     * Log de usuário bloqueado
     */
    public static function userBlocked(int $adminId, int $userId): void {
        self::log('user_blocked', $adminId, "Usuário bloqueado: #$userId");
    }
    
    /**
     * Log de atualização de features
     */
    public static function featuresUpdated(int $adminId, int $licenseId): void {
        self::log('features_updated', $adminId, "Features atualizadas para licença: #$licenseId");
    }
    
    /**
     * Log de revogação de token
     */
    public static function tokenRevoked(int $adminId, int $tokenId): void {
        self::log('token_revoked', $adminId, "Token revogado: #$tokenId");
    }
    
    /**
     * Obter logs com filtros
     */
    public static function getLogs(array $filters = []): array {
        $pdo = db();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "l.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "l.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['from'])) {
            $where[] = "l.created_at >= ?";
            $params[] = $filters['from'];
        }
        
        if (!empty($filters['to'])) {
            $where[] = "l.created_at <= ?";
            $params[] = $filters['to'] . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $pdo->prepare("
            SELECT l.*, u.email as user_email
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            $whereClause
            ORDER BY l.created_at DESC
            LIMIT 500
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
