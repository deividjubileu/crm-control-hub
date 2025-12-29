<?php
/**
 * CONFIGURAÇÃO DO SISTEMA
 * Edite as variáveis abaixo conforme seu ambiente
 */

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'licensing_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURAÇÕES DE SEGURANÇA
// =====================================================
define('JWT_SECRET', 'sua-chave-secreta-muito-forte-aqui-mude-isso');
define('TOKEN_EXPIRY_HOURS', 24);
define('ADMIN_TOKEN_EXPIRY_HOURS', 8);

// =====================================================
// CONFIGURAÇÕES DE RATE LIMITING
// =====================================================
define('RATE_LIMIT_REQUESTS', 100);  // Requisições por janela
define('RATE_LIMIT_WINDOW', 60);     // Janela em segundos

// =====================================================
// CONFIGURAÇÕES DE CORS
// =====================================================
define('CORS_ALLOWED_ORIGINS', [
    'https://5d9fb4fa-f8fe-4694-9433-c35eaaca8680.lovableproject.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'chrome-extension://*',  // Para extensões Chrome
]);

// =====================================================
// CONFIGURAÇÕES DE LOG
// =====================================================
define('LOG_ENABLED', true);
define('LOG_ACTIONS', [
    'login',
    'logout', 
    'login_failed',
    'license_created',
    'license_updated',
    'license_deleted',
    'user_created',
    'user_updated',
    'user_deleted',
    'user_blocked',
    'features_updated',
    'token_revoked',
]);

// =====================================================
// FEATURES PADRÃO PARA NOVAS LICENÇAS
// =====================================================
define('DEFAULT_FEATURES', [
    ['feature_key' => 'auto_reply', 'enabled' => true, 'value' => null],
    ['feature_key' => 'bulk_send', 'enabled' => false, 'value' => null],
    ['feature_key' => 'scraping', 'enabled' => false, 'value' => null],
    ['feature_key' => 'daily_limit', 'enabled' => true, 'value' => 200],
    ['feature_key' => 'ai_assistant', 'enabled' => false, 'value' => null],
    ['feature_key' => 'templates', 'enabled' => true, 'value' => null],
    ['feature_key' => 'scheduler', 'enabled' => false, 'value' => null],
    ['feature_key' => 'reports', 'enabled' => true, 'value' => null],
]);

// =====================================================
// CONFIGURAÇÕES DA APLICAÇÃO
// =====================================================
define('APP_NAME', 'Sistema de Licenciamento');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', false);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
