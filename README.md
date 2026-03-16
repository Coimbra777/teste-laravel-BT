w# API de Gerenciamento de Pagamentos

API REST para gerenciamento de pagamentos com suporte a múltiplos gateways de pagamento, fallback automático e controle de acesso baseado em roles.

## Stack

- **Laravel 10** (PHP 8.1)
- **MySQL 5.7**
- **Docker & Docker Compose**
- **Laravel Sanctum** (autenticação via token)
- **PHPUnit** (testes)

## Como Rodar

```bash
# Clonar o projeto
git clone <repo-url>
cd app-laravel

# Copiar .env (se necessário)
cp .env.example .env

# Subir os containers
docker-compose up -d

# Instalar dependências
docker-compose exec app composer install

# Gerar chave da aplicação
docker-compose exec app php artisan key:generate

# Rodar migrations e seeders
docker-compose exec app php artisan migrate --seed

# Rodar testes
docker-compose exec app php artisan test
```

A aplicação estará disponível em `http://localhost:8989`.

## Usuários de Seed

| Email            | Senha    | Role    |
| ---------------- | -------- | ------- |
| admin@admin.com  | password | admin   |
| manager@test.com | password | manager |
| finance@test.com | password | finance |
| user@test.com    | password | user    |

## Rotas da API

Todas as rotas possuem o prefixo `/api`.

### Autenticação

| Método | Rota    | Descrição     | Auth |
| ------ | ------- | ------------- | ---- |
| POST   | /login  | Gerar token   | Não  |
| POST   | /logout | Revogar token | Sim  |

### Compra

| Método | Rota      | Descrição       | Roles       |
| ------ | --------- | --------------- | ----------- |
| POST   | /purchase | Realizar compra | user, admin |

**Body:**

```json
{
    "name": "Nome do Cliente",
    "email": "cliente@email.com",
    "card_number": "5569000000006063",
    "cvv": "010",
    "products": [
        { "product_id": 1, "quantity": 2 },
        { "product_id": 2, "quantity": 1 }
    ]
}
```

### Produtos (CRUD)

| Método | Rota           | Descrição         | Roles                   |
| ------ | -------------- | ----------------- | ----------------------- |
| GET    | /products      | Listar produtos   | manager, finance, admin |
| POST   | /products      | Criar produto     | manager, finance, admin |
| GET    | /products/{id} | Detalhe produto   | manager, finance, admin |
| PUT    | /products/{id} | Atualizar produto | manager, finance, admin |
| DELETE | /products/{id} | Remover produto   | manager, finance, admin |

### Usuários (CRUD)

| Método | Rota        | Descrição         | Roles          |
| ------ | ----------- | ----------------- | -------------- |
| GET    | /users      | Listar usuários   | manager, admin |
| POST   | /users      | Criar usuário     | manager, admin |
| GET    | /users/{id} | Detalhe usuário   | manager, admin |
| PUT    | /users/{id} | Atualizar usuário | manager, admin |
| DELETE | /users/{id} | Remover usuário   | manager, admin |

### Gateways

| Método | Rota                    | Descrição          | Roles |
| ------ | ----------------------- | ------------------ | ----- |
| GET    | /gateways               | Listar gateways    | admin |
| PATCH  | /gateways/{id}/toggle   | Ativar/desativar   | admin |
| PATCH  | /gateways/{id}/priority | Alterar prioridade | admin |

### Clientes

| Método | Rota          | Descrição                   | Roles          |
| ------ | ------------- | --------------------------- | -------------- |
| GET    | /clients      | Listar clientes             | finance, admin |
| GET    | /clients/{id} | Detalhe cliente com compras | finance, admin |

### Transações

| Método | Rota                      | Descrição         | Roles          |
| ------ | ------------------------- | ----------------- | -------------- |
| GET    | /transactions             | Listar transações | finance, admin |
| GET    | /transactions/{id}        | Detalhe transação | finance, admin |
| POST   | /transactions/{id}/refund | Reembolso         | finance, admin |

## Roles

| Role    | Permissões                              |
| ------- | --------------------------------------- |
| admin   | Acesso total a todos os endpoints       |
| manager | CRUD de produtos e usuários             |
| finance | CRUD de produtos, consultas e reembolso |
| user    | Realizar compras                        |

## Arquitetura

```
app/
├── Gateways/
│   ├── PaymentGatewayInterface.php   # Contrato para gateways
│   ├── GatewayFactory.php            # Instancia o gateway correto
│   ├── GatewayOneService.php         # Implementação Gateway 1
│   └── GatewayTwoService.php         # Implementação Gateway 2
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── PurchaseController.php
│   │   ├── ProductController.php
│   │   ├── UserController.php
│   │   ├── GatewayController.php
│   │   ├── ClientController.php
│   │   └── TransactionController.php
│   ├── Middleware/
│   │   └── RoleMiddleware.php        # Controle de acesso por role
│   └── Requests/                     # Form Requests de validação
├── Models/
│   ├── User.php
│   ├── Gateway.php
│   ├── Client.php
│   ├── Product.php
│   └── Transaction.php
└── Services/
    └── PaymentService.php            # Orquestra o fluxo de pagamento
```

### Fluxo de Pagamento

1. O `PurchaseController` recebe a requisição validada
2. O `PaymentService` busca os gateways ativos, ordenados por prioridade
3. Para cada gateway, o `GatewayFactory` instancia o serviço correto
4. Tenta o pagamento no primeiro gateway
5. Se falhar, tenta no próximo (fallback)
6. Se algum retornar sucesso, salva a transação no banco
7. Se todos falharem, retorna erro com detalhes

## Testes

```bash
docker-compose exec app php artisan test
```

Os testes cobrem o `PaymentService`:

- Pagamento com sucesso no primeiro gateway
- Fallback para segundo gateway quando o primeiro falha
- Falha quando todos os gateways falham
- Falha quando não há gateways ativos
- Reembolso com sucesso
- Reembolso de transação já reembolsada
