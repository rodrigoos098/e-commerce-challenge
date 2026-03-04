# E-commerce System — Documentação do Projeto

> Sistema de e-commerce completo desenvolvido como desafio técnico full-stack, utilizando Laravel 12 + React + TypeScript + Inertia.js.

---

## Pré-requisitos

- PHP 8.2+
- Composer 2+
- Node.js 18+
- MySQL 8.0+
- Redis (opcional, para cache e filas em produção)

---

## Setup Rápido

```bash
# 1. Clone o repositório
git clone <repo-url>
cd e-commerce-challenge

# 2. Setup automático (instala deps, configura .env, migra e builda)
composer run setup
```

Ou passo a passo:

```bash
# Dependências PHP
composer install

# Copiar e configurar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no .env:
# DB_CONNECTION=mysql
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=secret

# Criar banco de dados
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ecommerce;"

# Migrations + Seeders
php artisan migrate --seed

# Dependências NPM e build
npm install
npm run build
```

---

## Rodar em Desenvolvimento

```bash
# Inicia servidor PHP, fila e Vite simultaneamente
composer run dev
```

Isso roda em paralelo:
- `php artisan serve` — servidor Laravel na porta 8000
- `php artisan queue:listen` — processamento de filas
- `npm run dev` — Vite com HMR

---

## Credenciais de Teste

Após `php artisan migrate --seed`:

| Papel | Email | Senha |
|-------|-------|-------|
| Admin | admin@example.com | password |
| Cliente | customer@example.com | password |

---

## Executar Testes

```bash
# Rodar todos os testes
php artisan test --compact

# Rodar com cobertura (mínimo 80%)
php artisan test --coverage --min=80

# Filtrar por arquivo
php artisan test --compact tests/Feature/Api/V1/ProductApiTest.php

# Filtrar por nome de teste
php artisan test --compact --filter=testCreateProduct
```

---

## Qualidade de Código

```bash
# PHP: formatar com Pint (PSR-12)
vendor/bin/pint

# TypeScript/React: verificar tipos
npm run type-check

# ESLint
npm run lint

# ESLint com correção automática
npm run lint:fix

# Prettier
npm run format
```

---

## Documentação da API (Swagger/OpenAPI)

```bash
# Gerar documentação
php artisan l5-swagger:generate
```

Acesse: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

---

## Estrutura de Pastas

```
app/
├── DTOs/                    # Data Transfer Objects (transferência entre camadas)
├── Events/                  # Eventos do domínio (OrderCreated, StockLow, etc.)
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/         # Controllers da API REST versionada
│   ├── Requests/            # Form Requests (validação de entrada)
│   └── Resources/          # API Resources (formatação JSON de saída)
├── Jobs/                   # Jobs para filas (ProcessOrder, SendEmail, etc.)
├── Listeners/              # Listeners de eventos
├── Mail/                   # Mailable classes para emails
├── Models/                 # Eloquent Models com relacionamentos e scopes
├── Policies/               # Autorização por recurso (ProductPolicy, OrderPolicy)
├── Repositories/           # Repository Pattern com Contracts + Implementations
├── Rules/                  # Regras de validação customizadas
├── Services/               # Camada de lógica de negócio
└── Traits/                 # Traits reutilizáveis (LogsActivity)

resources/js/
├── Components/
│   ├── Admin/              # Componentes do painel administrativo
│   ├── Public/             # Componentes da loja pública
│   └── Shared/             # Componentes compartilhados (SkeletonLoader, etc.)
├── Layouts/
│   ├── AdminLayout.tsx     # Layout do painel admin
│   └── PublicLayout.tsx    # Layout da loja pública
├── Pages/
│   ├── Admin/              # Páginas do painel admin (Dashboard, CRUD)
│   ├── Auth/               # Login e Registro
│   ├── Customer/           # Carrinho, Checkout, Pedidos, Perfil
│   └── Products/           # Listagem e detalhe de produtos
└── types/                  # TypeScript types compartilhados
```

---

## Decisões Arquiteturais

### Service Layer + Repository Pattern
A lógica de negócio reside exclusivamente nos **Services** (`app/Services/`). Os **Repositories** (`app/Repositories/`) abstraem o acesso a dados via interfaces (Contracts), facilitando testes com mocks e desacoplando a lógica de negócio do Eloquent.

### DTOs (Data Transfer Objects)
Os DTOs (`app/DTOs/`) garantem tipagem forte na transferência de dados entre camadas (Controller → Service → Repository), evitando dependência de arrays anônimos e documentando explicitamente a forma dos dados.

### Inertia.js + React — Frontend 100% Inertia
O frontend utiliza Inertia.js para toda a comunicação com o servidor:
- **Dados de página:** Chegam via `Inertia::render()` (props do servidor)
- **Mutações:** Enviadas via `router.post/put/delete` do `@inertiajs/react`
- **Filtros e paginação:** Via `router.get()` com query params

Isso elimina duplicação de controllers, simplifica autenticação (cookies nativos do Laravel, sem tokens no frontend) e oferece UX superior (sem flash de conteúdo vazio). A API REST existe como interface independente para clientes externos.

### API REST Versionada (`/api/v1/`)
A API REST é completamente independente do frontend Inertia. Ambas as camadas consomem os mesmos **Services** e **Repositories**. A API é protegida por Laravel Sanctum (tokens) e documentada via Swagger.

### Autorização com Roles e Policies
- **Roles** gerenciados via `spatie/laravel-permission`: `admin`, `customer`
- **Policies** (`ProductPolicy`, `OrderPolicy`) para autorização de recursos
- Rate limiting: 100 requisições/minuto por IP via middleware do Laravel

### Cache com Tags
- Produtos: TTL 1 hora (`cache()->tags(['products'])`)
- Categorias: TTL 24 horas (`cache()->tags(['categories'])`)
- Invalidação automática nas operações de escrita

### Filas e Jobs
Operações assíncronas via `database` queue driver (configurável para Redis em produção):
- `ProcessOrderJob` — Processamento do pedido em background
- `SendOrderConfirmationEmail` — Email de confirmação
- `UpdateStockAfterOrder` — Atualização de estoque + criação de StockMovement

---

## Bibliotecas Utilizadas

### Backend (PHP/Laravel)

| Biblioteca | Versão | Justificativa |
|-----------|--------|---------------|
| `laravel/framework` | ^12.0 | Base do projeto |
| `laravel/sanctum` | ^4.3 | Autenticação API via tokens |
| `spatie/laravel-permission` | ^6.24 | Roles e permissions (admin, customer) |
| `darkaonline/l5-swagger` | ^10.1 | Geração de documentação OpenAPI/Swagger |
| `inertiajs/inertia-laravel` | ^2.0 | Adaptador Inertia para Laravel (SPA sem API separada) |
| `predis/predis` | ^3.0 | Cliente Redis para cache e filas em produção |
| `opcodesio/log-viewer` | ^3.21 | Visualizador de logs em `/log-viewer` |

### Frontend (TypeScript/React)

| Biblioteca | Versão | Justificativa |
|-----------|--------|---------------|
| `react` | ^19 | UI framework |
| `@inertiajs/react` | ^2.3 | Adaptador Inertia para React |
| `tailwindcss` | ^4.0 | Utility-first CSS (design system consistente) |
| `react-hook-form` | ^7.71 | Formulários performáticos com menor re-render |
| `@hookform/resolvers` | ^5.2 | Integração react-hook-form com Zod |
| `zod` | ^4.3 | Validação de schema TypeScript-first |
| `react-hot-toast` | ^2.6 | Toast notifications leves e customizáveis |
| `typescript` | ^5.9 | Type safety em todo o frontend |

### Dev / Qualidade

| Ferramenta | Justificativa |
|-----------|---------------|
| `laravel/pint` | Formatter PHP (preset PSR-12) |
| `eslint` + `@typescript-eslint` | Linting TypeScript/React |
| `prettier` | Formatação JavaScript/TypeScript |
| `phpunit/phpunit` | Testes unitários e de integração |
| `laravel/telescope` | Debug e profiling em desenvolvimento |
| `laravel/pail` | Tail de logs em tempo real |

---

## Endpoints da API

Documentação completa em `/api/documentation`. Resumo:

### Autenticação
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
```

### Produtos
```
GET    /api/v1/products              # Listagem com filtros, busca, paginação
GET    /api/v1/products/{id}         # Detalhes
POST   /api/v1/products              # Criar (admin)
PUT    /api/v1/products/{id}         # Atualizar (admin)
DELETE /api/v1/products/{id}         # Excluir soft delete (admin)
```

### Categorias
```
GET    /api/v1/categories            # Árvore hierárquica
GET    /api/v1/categories/{id}/products  # Produtos da categoria
```

### Carrinho
```
GET    /api/v1/cart                  # Carrinho do usuário
POST   /api/v1/cart/items            # Adicionar item
PUT    /api/v1/cart/items/{id}       # Atualizar quantidade
DELETE /api/v1/cart/items/{id}       # Remover item
DELETE /api/v1/cart                  # Limpar carrinho
```

### Pedidos
```
GET    /api/v1/orders                # Listar pedidos do usuário
GET    /api/v1/orders/{id}           # Detalhes do pedido
POST   /api/v1/orders                # Criar pedido a partir do carrinho
PUT    /api/v1/orders/{id}/status    # Atualizar status (admin)
```

### Formato de Resposta Padrão
```json
// Sucesso (recurso único)
{ "success": true, "data": { ... } }

// Sucesso (listagem com paginação)
{ "success": true, "data": [...], "meta": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7 }, "links": { ... } }

// Erro
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```

---

## Variáveis de Ambiente Importantes

```dotenv
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database   # Em produção: redis

CACHE_STORE=redis           # Em produção: redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=log             # Em produção: smtp/mailgun/ses
```

---

## Logging

Os logs são segmentados por canal em `storage/logs/`:
- `laravel.log` — log geral da aplicação
- `orders.log` — operações de pedidos
- `stock.log` — movimentações de estoque
- `auth.log` — eventos de autenticação

Visualização visual em: [http://localhost:8000/log-viewer](http://localhost:8000/log-viewer)
