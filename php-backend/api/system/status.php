<?php
/**
 * API: Status do Sistema
 * GET /api/system/status.php
 * 
 * Response: { status, version, timestamp }
 * 
 * Endpoint público para verificar se API está online
 */

require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';
require_once __DIR__ . '/../../config.php';

// CORS
Security::handleCors();

Response::success([
    'online' => true,
    'version' => APP_VERSION,
    'timestamp' => date('c'),
    'timezone' => date_default_timezone_get()
]);
