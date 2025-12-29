<?php
/**
 * HELPER PARA RESPOSTAS JSON
 * Padroniza todas as respostas da API
 */

class Response {
    
    /**
     * Resposta de sucesso
     */
    public static function success($data = null, string $message = null, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = ['status' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta de erro
     */
    public static function error(string $message, int $statusCode = 400, $errors = null): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Erro 401 - Não autorizado
     */
    public static function unauthorized(string $message = 'Não autorizado'): void {
        self::error($message, 401);
    }
    
    /**
     * Erro 403 - Proibido
     */
    public static function forbidden(string $message = 'Acesso negado'): void {
        self::error($message, 403);
    }
    
    /**
     * Erro 404 - Não encontrado
     */
    public static function notFound(string $message = 'Recurso não encontrado'): void {
        self::error($message, 404);
    }
    
    /**
     * Erro 429 - Muitas requisições
     */
    public static function tooManyRequests(string $message = 'Muitas requisições. Tente novamente mais tarde.'): void {
        self::error($message, 429);
    }
    
    /**
     * Erro 500 - Erro interno
     */
    public static function serverError(string $message = 'Erro interno do servidor'): void {
        self::error($message, 500);
    }
    
    /**
     * Resposta para login bem-sucedido
     */
    public static function loginSuccess(string $token, int $expiresIn, array $user = null): void {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => true,
            'token' => $token,
            'expires_in' => $expiresIn
        ];
        
        if ($user !== null) {
            unset($user['password']); // Nunca retornar senha
            $response['user'] = $user;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta para validação de licença (formato para extensão)
     */
    public static function licenseValidation(bool $active, string $expiresAt = null, array $features = null): void {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => true,
            'license_active' => $active
        ];
        
        if ($expiresAt !== null) {
            $response['expires_at'] = $expiresAt;
        }
        
        if ($features !== null) {
            $response['features'] = $features;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta para heartbeat (formato para extensão)
     */
    public static function heartbeat(bool $continue, string $message = null): void {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => true,
            'continue' => $continue
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
