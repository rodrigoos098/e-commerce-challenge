# E-commerce System - Documentacao do Projeto

> Sistema de e-commerce desenvolvido para o desafio tecnico com Laravel 12, Inertia.js, React 19 e TypeScript.

---

## Pre-requisitos

- PHP 8.2+
- Composer 2+
- Node.js 18+
- MySQL 8.0+ ou SQLite para uso local
- Redis para o cache com tags

---

## Setup rapido

```bash
git clone <repo-url>
cd e-commerce-challenge
composer run setup
```

O script `composer run setup` faz:

```bash
composer install
copy .env.example .env   # ou cp .env.example .env
php artisan key:generate
php artisan migrate --seed --force
npm install
npm run build
```

Setup manual:

```bash
composer install
copy .env.example .env
php artisan key:generate

# Ajuste o banco no .env
# DB_CONNECTION=mysql
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=secret

# Redis e obrigatorio para cache tags
# CACHE_STORE=redis
# QUEUE_CONNECTION=database
# REDIS_HOST=127.0.0.1
# REDIS_PORT=6379

# Filas assincronas usam o driver database por padrao
# MAIL_MAILER=log

php artisan migrate --seed
npm install
npm run build
```

---

## Desenvolvimento

```bash
composer run dev
```

Esse comando sobe em paralelo:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `npm run dev`

Importante:

- o worker de fila e necessario para processar jobs assincronos de follow-up do pedido e envio de e-mails
- a criacao do pedido em si continua sincrona e nao depende do worker

---

## E-mail Local (Mailpit)

Por padrao, o `.env.example` usa `MAIL_MAILER=log`, entao os e-mails sao escritos no log.

Para testar os e-mails transacionais localmente com Mailpit, ajuste o `.env` para algo como:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Com o Mailpit rodando:

1. SMTP: `127.0.0.1:1025`
2. Web UI: `http://localhost:8025`

Os e-mails de pedido sao enviados por job em fila, entao o worker tambem precisa estar ativo.

---

## Credenciais seedadas

Depois de `php artisan migrate --seed`:

| Papel   | Email                | Senha    |
| ------- | -------------------- | -------- |
| Admin   | admin@example.com    | password |
| Cliente | customer@example.com | password |

O seeder tambem cria clientes adicionais com dados fake.

---

## Testes e qualidade

```bash
php artisan test --compact
php artisan test --compact tests/Feature/Api/V1/ProductApiTest.php
php artisan test --compact --filter=testCreateProduct

vendor/bin/pint --dirty --format agent
npm run type-check
npm run lint
php artisan l5-swagger:generate
```

Cobertura minima de 80% continua sendo requisito do desafio, mas depende de driver de coverage habilitado no ambiente.

---

## Documentacao da API

```bash
php artisan l5-swagger:generate
```

Acesse:

- `http://localhost:8000/api/documentation`

---

## Arquitetura

### Backend

- `app/Services`: regra de negocio
- `app/Repositories`: acesso a dados via contracts
- `app/DTOs`: transferencia de dados entre camadas
- `app/Http/Requests`: validacao de entrada
- `app/Http/Resources`: padronizacao de respostas JSON

### Frontend

- `resources/js/Pages`: paginas Inertia
- `resources/js/Components`: componentes publicos e administrativos
- `resources/js/types`: contratos compartilhados TypeScript

### Autenticacao e autorizacao

- Sanctum para API tokens
- Spatie Permission para roles `admin` e `customer`
- Policies e middleware de role nas rotas protegidas

### Cache

- Produtos: TTL de 1 hora com `Cache::tags(['products'])`
- Categorias: TTL de 24 horas com `Cache::tags(['categories'])`
- Mutacoes de produto e estoque invalidam o cache de produtos

### Pedidos e estoque

- `OrderService` cria o pedido de forma sincrona e transacional a partir do carrinho atual
- o calculo final do pedido ja inclui frete mockado e exige simulacao de pagamento antes da conclusao
- a baixa de estoque e os registros em `stock_movements` acontecem dentro da mesma transacao da criacao do pedido
- o carrinho e limpo apenas depois que pedido e estoque sao persistidos com sucesso
- apos o commit, os eventos do pedido disparam notificacoes e jobs de follow-up

### Filas

- a conexao padrao de fila e `database` (`QUEUE_CONNECTION=database`)
- `config/queue.php` mantem `after_commit=false` globalmente; jobs que precisam esperar a transacao usam `afterCommit()` explicitamente
- `ProcessOrderPipeline` e disparado em `OrderCreated`, mas hoje faz apenas follow-up/carregamento do estado final e logs
- `SendOrderConfirmationEmail` envia notificacoes transacionais de pedido criado, pagamento confirmado, envio, entrega e cancelamento
- os jobs assincronos nao executam mais a etapa critica de baixa de estoque

---

## Endpoints principais

### Autenticacao

```text
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
```

### Produtos

```text
GET    /api/v1/products
GET    /api/v1/products/{id}
POST   /api/v1/products
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
GET    /api/v1/products/low-stock
```

### Categorias

```text
GET    /api/v1/categories
GET    /api/v1/categories/{id}
GET    /api/v1/categories/{id}/products
POST   /api/v1/categories
PUT    /api/v1/categories/{id}
DELETE /api/v1/categories/{id}
```

### Carrinho

```text
GET    /api/v1/cart
POST   /api/v1/cart/items
PUT    /api/v1/cart/items/{id}
DELETE /api/v1/cart/items/{id}
DELETE /api/v1/cart
```

### Pedidos

```text
GET    /api/v1/orders
GET    /api/v1/orders/{id}
POST   /api/v1/orders
PUT    /api/v1/orders/{id}/status
```

---

## Resposta JSON

Sucesso:

```json
{ "success": true, "data": {} }
```

Listagem paginada:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

Erro:

```json
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": {
    "field": ["Mensagem de validacao"]
  }
}
```
