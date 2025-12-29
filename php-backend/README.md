# 🔐 Sistema de Licenciamento - Backend PHP

Backend completo para o painel administrativo e extensão de navegador.

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite habilitado
- Extensões PHP: PDO, pdo_mysql, json, mbstring

## 🚀 Instalação

### 1. Banco de Dados

Execute o script SQL para criar as tabelas:

```bash
mysql -u root -p < database.sql
```

Ou importe via phpMyAdmin.

### 2. Configuração

Edite o arquivo `config.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'licensing_system');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');

// IMPORTANTE: Altere a chave JWT!
define('JWT_SECRET', 'sua-chave-super-secreta-aqui');

// Configure as origens permitidas (CORS)
define('CORS_ALLOWED_ORIGINS', [
    'https://seu-painel.lovableproject.com',
    'chrome-extension://*',
]);
```

### 3. Upload

Faça upload de toda a pasta `php-backend` para seu servidor:

```
/public_html/api/
├── admin/
│   ├── dashboard.php
│   ├── features.php
│   ├── licenses.php
│   ├── login.php
│   ├── logout.php
│   ├── logs.php
│   ├── tokens.php
│   ├── users.php
│   └── validate-token.php
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── validate-token.php
│   ├── license/
│   │   ├── features.php
│   │   ├── heartbeat.php
│   │   └── validate.php
│   └── system/
│       └── status.php
├── core/
│   ├── auth.php
│   ├── db.php
│   ├── logger.php
│   ├── response.php
│   └── security.php
├── .htaccess
├── config.php
└── database.sql
```

### 4. Permissões

```bash
chmod 644 config.php
chmod 644 .htaccess
chmod -R 755 admin/ api/ core/
```

### 5. Habilitar Event Scheduler (Opcional)

Para expiração automática de licenças e limpeza de tokens:

```sql
SET GLOBAL event_scheduler = ON;
```

## 🔗 Endpoints

### Admin (Painel)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/admin/login.php` | Login admin |
| POST | `/admin/logout.php` | Logout |
| GET | `/admin/validate-token.php` | Validar token |
| GET | `/admin/dashboard.php` | Estatísticas |
| GET/POST/PUT/DELETE | `/admin/users.php` | CRUD usuários |
| GET/POST/PUT/DELETE | `/admin/licenses.php` | CRUD licenças |
| GET/PUT | `/admin/features.php` | Gerenciar features |
| GET | `/admin/logs.php` | Listar logs |
| GET/DELETE | `/admin/tokens.php` | Gerenciar sessões |

### Extensão

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/auth/login.php` | Login extensão |
| POST | `/api/auth/logout.php` | Logout |
| POST | `/api/auth/validate-token.php` | Validar token |
| POST | `/api/license/validate.php` | Validar licença |
| POST | `/api/license/heartbeat.php` | Heartbeat |
| GET | `/api/license/features.php` | Obter features |
| GET | `/api/system/status.php` | Status da API |

## 📝 Exemplos de Uso

### Login Admin

```bash
curl -X POST https://seudominio.com/api/admin/login.php \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@sistema.com", "password": "admin123"}'
```

Resposta:
```json
{
  "status": true,
  "token": "abc123...",
  "expires_in": 28800,
  "user": {
    "id": 1,
    "email": "admin@sistema.com",
    "role": "admin"
  }
}
```

### Login Extensão

```bash
curl -X POST https://seudominio.com/api/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email": "user@email.com", "password": "123456", "device_hash": "abc123"}'
```

Resposta:
```json
{
  "status": true,
  "token": "xyz789...",
  "expires_in": 86400
}
```

### Validar Licença

```bash
curl -X POST https://seudominio.com/api/api/license/validate.php \
  -H "Authorization: Bearer xyz789..."
```

Resposta:
```json
{
  "status": true,
  "license_active": true,
  "expires_at": "2026-01-01 00:00:00",
  "features": {
    "auto_reply": true,
    "bulk_send": false,
    "scraping": false,
    "daily_limit": 200,
    "ai_assistant": false,
    "templates": true,
    "scheduler": false,
    "reports": true
  }
}
```

### Heartbeat

```bash
curl -X POST https://seudominio.com/api/api/license/heartbeat.php \
  -H "Authorization: Bearer xyz789..."
```

Resposta (OK):
```json
{
  "status": true,
  "continue": true
}
```

Resposta (Bloqueado):
```json
{
  "status": true,
  "continue": false,
  "message": "Licença bloqueada"
}
```

## 🔒 Segurança

- ✅ Senhas hasheadas com bcrypt
- ✅ Tokens com expiração
- ✅ Rate limiting
- ✅ Prepared statements (previne SQL injection)
- ✅ Sanitização de inputs
- ✅ CORS configurável
- ✅ Headers de segurança

## 👤 Admin Padrão

- **Email:** admin@sistema.com
- **Senha:** admin123

⚠️ **IMPORTANTE:** Altere a senha após o primeiro login!

## 📊 Features Padrão

Quando uma nova licença é criada, estas features são adicionadas:

| Feature | Habilitada | Valor |
|---------|------------|-------|
| auto_reply | ✅ | - |
| bulk_send | ❌ | - |
| scraping | ❌ | - |
| daily_limit | ✅ | 200 |
| ai_assistant | ❌ | - |
| templates | ✅ | - |
| scheduler | ❌ | - |
| reports | ✅ | - |

## 🐛 Troubleshooting

### Erro de CORS
Verifique se a origem do painel está em `CORS_ALLOWED_ORIGINS` no `config.php`.

### Erro de conexão com banco
Verifique as credenciais em `config.php` e se o MySQL está rodando.

### Token inválido
Os tokens expiram após 24h (extensão) ou 8h (admin). Faça login novamente.

### Rate limit
Aguarde 60 segundos se receber erro 429.

## 📄 Licença

Projeto privado - Todos os direitos reservados.
