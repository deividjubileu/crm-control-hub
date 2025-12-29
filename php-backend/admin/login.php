<?php
/**
 * API: Login Admin
 * POST /admin/login.php
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/logger.php';

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

// Verificar se é admin
if ($user['role'] !== 'admin') {
    Logger::loginFailed($email);
    Response::error('Acesso restrito a administradores', 403);
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

// Criar token
$tokenData = Auth::createToken($user['id'], null, ADMIN_TOKEN_EXPIRY_HOURS);

// Log de sucesso
Logger::loginSuccess($user['id']);

// Retornar resposta
Response::loginSuccess(
    $tokenData['token'],
    $tokenData['expires_in'],
    [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
        'created_at' => $user['created_at']
    ]
);
