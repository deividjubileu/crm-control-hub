<?php
/**
 * SEGURANÇA
 * CORS, Rate Limiting, Validação
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config.php';

class Security {
    
    /**
     * Configurar CORS
     */
    public static function handleCors(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Verificar se origem é permitida
        $allowed = false;
        foreach (CORS_ALLOWED_ORIGINS as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                $allowed = true;
                break;
            }
            
            // Suporte a wildcards para extensões
            if (strpos($allowedOrigin, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowedOrigin, '/'));
                if (preg_match('/^' . $pattern . '$/', $origin)) {
                    $allowed = true;
                    break;
                }
            }
            
            if ($origin === $allowedOrigin) {
                $allowed = true;
                break;
            }
        }
        
        if ($allowed && $origin) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        // Preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * Rate Limiting
     */
    public static function checkRateLimit(string $identifier = null, string $endpoint = null): void {
        $identifier = $identifier ?? self::getClientIdentifier();
        $endpoint = $endpoint ?? $_SERVER['REQUEST_URI'];
        
        $pdo = db();
        
        // Limpar entradas antigas
        $cleanStmt = $pdo->prepare("
            DELETE FROM rate_limits 
            WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $cleanStmt->execute([RATE_LIMIT_WINDOW]);
        
        // Verificar limite atual
        $stmt = $pdo->prepare("
            SELECT requests, window_start 
            FROM rate_limits 
            WHERE identifier = ? AND endpoint = ?
        ");
        $stmt->execute([$identifier, $endpoint]);
        $result = $stmt->fetch();
        
        if ($result) {
            $windowStart = strtotime($result['window_start']);
            $windowEnd = $windowStart + RATE_LIMIT_WINDOW;
            
            if (time() < $windowEnd && $result['requests'] >= RATE_LIMIT_REQUESTS) {
                $retryAfter = $windowEnd - time();
                header("Retry-After: $retryAfter");
                Response::tooManyRequests();
            }
            
            // Incrementar contador
            if (time() < $windowEnd) {
                $updateStmt = $pdo->prepare("
                    UPDATE rate_limits SET requests = requests + 1 
                    WHERE identifier = ? AND endpoint = ?
                ");
                $updateStmt->execute([$identifier, $endpoint]);
            } else {
                // Nova janela
                $updateStmt = $pdo->prepare("
                    UPDATE rate_limits SET requests = 1, window_start = NOW() 
                    WHERE identifier = ? AND endpoint = ?
                ");
                $updateStmt->execute([$identifier, $endpoint]);
            }
        } else {
            // Primeira requisição
            $insertStmt = $pdo->prepare("
                INSERT INTO rate_limits (identifier, endpoint, requests, window_start)
                VALUES (?, ?, 1, NOW())
            ");
            $insertStmt->execute([$identifier, $endpoint]);
        }
    }
    
    /**
     * Obter identificador do cliente
     */
    private static function getClientIdentifier(): string {
        return Auth::getClientIp();
    }
    
    /**
     * Validar e sanitizar input
     */
    public static function sanitize($input, string $type = 'string') {
        if ($input === null) {
            return null;
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
                
            case 'int':
                return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'bool':
                return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validar email
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar senha (mínimo 6 caracteres)
     */
    public static function isValidPassword(string $password): bool {
        return strlen($password) >= 6;
    }
    
    /**
     * Obter dados JSON do body
     */
    public static function getJsonInput(): array {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data ?? [];
    }
    
    /**
     * Validar campos obrigatórios
     */
    public static function validateRequired(array $data, array $fields): ?string {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return "Campo obrigatório: $field";
            }
        }
        return null;
    }
    
    /**
     * Gerar hash de dispositivo
     */
    public static function generateDeviceHash(): string {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            Auth::getClientIp()
        ];
        
        return hash('sha256', implode('|', $data));
    }
}
