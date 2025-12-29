<?php
/**
 * API: Login da Extensão
 * POST /api/auth/login.php
 * 
 * Body: { email, password, device_hash }
 * Response: { status, token, expires_in }
 */

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/security.php';
require_once __DIR__ . '/../../core/logger.php';

// CORS e Rate Limiting
Security::handleCors();
Security::checkRateLimit();

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método não permitido', 405);
}

// Obter dados
$data = Security::getJsonInput();

// Validar campos obrigatórios
$error = Security::validateRequired($data, ['email', 'password']);
if ($error) {
    Response::error($error);
}

$email = Security::sanitize($data['email'], 'email');
$password = $data['password'];
$deviceHash = $data['device_hash'] ?? Security::generateDeviceHash();

// Validar formato do email
if (!Security::isValidEmail($email)) {
    Response::error('Email inválido');
}

// Buscar usuário
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    Logger::loginFailed($email);
    Response::error('Credenciais inválidas', 401);
}

// Verificar se está ativo
if ($user['status'] !== 'active') {
    Logger::loginFailed($email);
    Response::error('Usuário bloqueado', 403);
}

// Verificar senha
if (!Auth::verifyPassword($password, $user['password'])) {
    Logger::loginFailed($email);
    Response::error('Credenciais inválidas', 401);
}

// Verificar se tem licença ativa
$license = Auth::getUserActiveLicense($user['id']);

if (!$license) {
    Response::error('Nenhuma licença ativa encontrada', 403);
}

// Verificar limite de dispositivos
$activeDevices = Auth::countActiveDevices($user['id']);

if ($activeDevices >= $license['max_devices']) {
    // Verificar se já tem token com esse device_hash
    $stmt = $pdo->prepare("
        SELECT id FROM tokens 
        WHERE user_id = ? AND device_hash = ? AND expires_at > NOW()
    ");
    $stmt->execute([$user['id'], $deviceHash]);
    
    if (!$stmt->fetch()) {
        Response::error('Limite de dispositivos atingido. Revogue uma sessão existente.', 403);
    }
}

// Criar token
$tokenData = Auth::createToken($user['id'], $deviceHash);

// Log de sucesso
Logger::loginSuccess($user['id']);

// Retornar resposta no formato esperado pela extensão
Response::loginSuccess($tokenData['token'], $tokenData['expires_in']);
