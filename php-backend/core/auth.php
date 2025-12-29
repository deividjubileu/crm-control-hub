<?php
/**
 * AUTENTICAÇÃO E TOKENS
 * Gerenciamento de tokens e validação de sessões
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config.php';

class Auth {
    
    /**
     * Gerar token único
     */
    public static function generateToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Gerar chave de licença
     */
    public static function generateLicenseKey(): string {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(md5(random_bytes(16)), 0, 5));
        }
        return implode('-', $segments);
    }
    
    /**
     * Hash de senha
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar senha
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Criar token de sessão
     */
    public static function createToken(int $userId, string $deviceHash = null, int $expiryHours = null): array {
        $pdo = db();
        
        $expiryHours = $expiryHours ?? TOKEN_EXPIRY_HOURS;
        $token = self::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
        $ip = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO tokens (user_id, token, device_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $token, $deviceHash, $ip, $userAgent, $expiresAt]);
        
        return [
            'token' => $token,
            'expires_at' => $expiresAt,
            'expires_in' => $expiryHours * 3600
        ];
    }
    
    /**
     * Validar token e retornar usuário
     */
    public static function validateToken(string $token): ?array {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT t.*, u.id as user_id, u.email, u.role, u.status
            FROM tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ? AND t.expires_at > NOW()
        ");
        
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return null;
        }
        
        // Verificar se usuário está ativo
        if ($result['status'] !== 'active') {
            return null;
        }
        
        // Atualizar last_seen
        $updateStmt = $pdo->prepare("UPDATE tokens SET last_seen = NOW() WHERE id = ?");
        $updateStmt->execute([$result['id']]);
        
        return [
            'id' => $result['user_id'],
            'email' => $result['email'],
            'role' => $result['role'],
            'status' => $result['status'],
            'token_id' => $result['id']
        ];
    }
    
    /**
     * Revogar token
     */
    public static function revokeToken(string $token): bool {
        $pdo = db();
        $stmt = $pdo->prepare("DELETE FROM tokens WHERE token = ?");
        return $stmt->execute([$token]);
    }
    
    /**
     * Revogar todos os tokens de um usuário
     */
    public static function revokeAllTokens(int $userId): bool {
        $pdo = db();
        $stmt = $pdo->prepare("DELETE FROM tokens WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Obter token do header Authorization
     */
    public static function getBearerToken(): ?string {
        $headers = self::getAuthorizationHeader();
        
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Obter header de autorização
     */
    private static function getAuthorizationHeader(): ?string {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Obter IP do cliente
     */
    public static function getClientIp(): string {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return trim($ip);
    }
    
    /**
     * Middleware: Requer autenticação
     */
    public static function requireAuth(): array {
        $token = self::getBearerToken();
        
        if (!$token) {
            Response::unauthorized('Token não fornecido');
        }
        
        $user = self::validateToken($token);
        
        if (!$user) {
            Response::unauthorized('Token inválido ou expirado');
        }
        
        return $user;
    }
    
    /**
     * Middleware: Requer admin
     */
    public static function requireAdmin(): array {
        $user = self::requireAuth();
        
        if ($user['role'] !== 'admin') {
            Response::forbidden('Acesso restrito a administradores');
        }
        
        return $user;
    }
    
    /**
     * Verificar se usuário tem licença ativa
     */
    public static function getUserActiveLicense(int $userId): ?array {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT * FROM licenses 
            WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
            ORDER BY expires_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obter features de uma licença
     */
    public static function getLicenseFeatures(int $licenseId): array {
        $pdo = db();
        
        $stmt = $pdo->prepare("SELECT * FROM features WHERE license_id = ?");
        $stmt->execute([$licenseId]);
        
        $features = [];
        while ($row = $stmt->fetch()) {
            $features[$row['feature_key']] = $row['enabled'] ? ($row['value'] ?? true) : false;
        }
        
        return $features;
    }
    
    /**
     * Contar dispositivos ativos de uma licença
     */
    public static function countActiveDevices(int $userId): int {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT device_hash) as count 
            FROM tokens 
            WHERE user_id = ? AND device_hash IS NOT NULL AND expires_at > NOW()
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }
}
