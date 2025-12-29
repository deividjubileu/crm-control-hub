<?php
/**
 * API: CRUD Usuários
 * GET /admin/users.php - Listar todos
 * GET /admin/users.php?id=X - Buscar por ID
 * POST /admin/users.php - Criar
 * PUT /admin/users.php?id=X - Atualizar
 * DELETE /admin/users.php?id=X - Excluir
 */

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/logger.php';

// CORS
Security::handleCors();

// Requer admin
$admin = Auth::requireAdmin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? Security::sanitize($_GET['id'], 'int') : null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Buscar usuário específico
            $stmt = $pdo->prepare("SELECT id, email, role, status, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            Response::success($user);
        } else {
            // Listar todos
            $stmt = $pdo->query("SELECT id, email, role, status, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            Response::success($users);
        }
        break;
        
    case 'POST':
        $data = Security::getJsonInput();
        
        // Validar campos obrigatórios
        $error = Security::validateRequired($data, ['email', 'password']);
        if ($error) {
            Response::error($error);
        }
        
        $email = Security::sanitize($data['email'], 'email');
        $password = $data['password'];
        $role = Security::sanitize($data['role'] ?? 'user', 'string');
        $status = Security::sanitize($data['status'] ?? 'active', 'string');
        
        // Validar email
        if (!Security::isValidEmail($email)) {
            Response::error('Email inválido');
        }
        
        // Validar senha
        if (!Security::isValidPassword($password)) {
            Response::error('Senha deve ter no mínimo 6 caracteres');
        }
        
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('Email já cadastrado');
        }
        
        // Validar role e status
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        if (!in_array($status, ['active', 'blocked'])) {
            $status = 'active';
        }
        
        // Criar usuário
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$email, Auth::hashPassword($password), $role, $status]);
        
        $userId = $pdo->lastInsertId();
        
        // Log
        Logger::userCreated($admin['id'], $email);
        
        // Retornar usuário criado
        $stmt = $pdo->prepare("SELECT id, email, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        Response::success($stmt->fetch(), 'Usuário criado com sucesso', 201);
        break;
        
    case 'PUT':
        if (!$id) {
            Response::error('ID do usuário é obrigatório');
        }
        
        // Verificar se usuário existe
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Response::notFound('Usuário não encontrado');
        }
        
        $data = Security::getJsonInput();
        
        $updates = [];
        $params = [];
        
        if (isset($data['email'])) {
            $email = Security::sanitize($data['email'], 'email');
            if (!Security::isValidEmail($email)) {
                Response::error('Email inválido');
            }
            
            // Verificar se email já existe em outro usuário
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                Response::error('Email já cadastrado');
            }
            
            $updates[] = "email = ?";
            $params[] = $email;
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            if (!Security::isValidPassword($data['password'])) {
                Response::error('Senha deve ter no mínimo 6 caracteres');
            }
            $updates[] = "password = ?";
            $params[] = Auth::hashPassword($data['password']);
        }
        
        if (isset($data['role']) && in_array($data['role'], ['admin', 'user'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
        
        if (isset($data['status']) && in_array($data['status'], ['active', 'blocked'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
            
            // Se bloqueou, registrar log específico
            if ($data['status'] === 'blocked' && $user['status'] !== 'blocked') {
                Logger::userBlocked($admin['id'], $id);
            }
        }
        
        if (empty($updates)) {
            Response::error('Nenhum campo para atualizar');
        }
        
        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);
        
        // Log
        Logger::userUpdated($admin['id'], $id);
        
        // Retornar usuário atualizado
        $stmt = $pdo->prepare("SELECT id, email, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        Response::success($stmt->fetch(), 'Usuário atualizado com sucesso');
        break;
        
    case 'DELETE':
        if (!$id) {
            Response::error('ID do usuário é obrigatório');
        }
        
        // Não permitir excluir a si mesmo
        if ($id === $admin['id']) {
            Response::error('Não é possível excluir seu próprio usuário');
        }
        
        // Verificar se usuário existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::notFound('Usuário não encontrado');
        }
        
        // Excluir usuário (cascade remove tokens, licenses, etc.)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        Response::success(null, 'Usuário excluído com sucesso');
        break;
        
    default:
        Response::error('Método não permitido', 405);
}
