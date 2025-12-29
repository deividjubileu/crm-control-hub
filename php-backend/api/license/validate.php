<?php
/**
 * API: Validar Licença
 * POST /api/license/validate.php
 * 
 * Header: Authorization: Bearer <token>
 * Response: { status, license_active, expires_at, features }
 */

require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';

// CORS
Security::handleCors();

// Obter e validar token
$token = Auth::getBearerToken();

if (!$token) {
    Response::error('Token não fornecido', 401);
}

$user = Auth::validateToken($token);

if (!$user) {
    Response::error('Token inválido ou expirado', 401);
}

// Buscar licença ativa
$license = Auth::getUserActiveLicense($user['id']);

if (!$license) {
    Response::licenseValidation(false);
}

// Buscar features
$features = Auth::getLicenseFeatures($license['id']);

// Retornar no formato esperado pela extensão
Response::licenseValidation(
    true,
    $license['expires_at'],
    $features
);
