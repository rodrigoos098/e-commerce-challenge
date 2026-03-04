# Relatório Técnico — Fase 1: Backend Core

**Projeto:** Sistema de E-commerce (Desafio Full-Stack)  
**Data de execução:** 2026-03-01  
**Responsável:** Agente 1 (Backend Core)  
**Status:** ✅ Concluída  

---

## 1. Objetivo

Implementar toda a camada de backend da API REST do sistema de e-commerce, seguindo os princípios de arquitetura limpa com Repository Pattern, Service Layer e DTOs. O objetivo foi entregar uma API completamente funcional, autenticada, cacheada e documentada por testes, pronta para ser consumida pelos agentes de frontend (Agentes 3 e 4).

---

## 2. Arquitetura Implementada

A arquitetura segue o padrão em camadas abaixo:

```
Request HTTP
    ↓
FormRequest (validação + regras customizadas)
    ↓
Controller (transforma request em DTO, chama Service)
    ↓
Service (lógica de negócio, cache, eventos)
    ↓
Repository (abstração de acesso a dados)
    ↓
Eloquent Model / Database
    ↓
API Resource (transforma Model em JSON padronizado)
    ↓
Response HTTP (formato { success, data, meta, links })
```

### Justificativa das Escolhas

| Camada | Padrão | Justificativa |
|--------|--------|---------------|
| Acesso a dados | Repository Pattern | Desacopla a lógica de negócio do ORM; permite troca de fonte de dados sem alterar Services |
| Transferência de dados | DTOs (readonly classes) | Contrato explícito entre Controller e Service; elimina arrays magic e facilita tipagem |
| Lógica de negócio | Service Layer | Centraliza regras; Controllers ficam finos; facilita testes unitários |
| Cache | Redis + `Cache::tags()` | Tags nativas do Redis via `predis/predis`; flush atômico por grupo sem acúmulo de chaves órfãs |
| Eventos | Laravel Event System | Desacoplamento de side effects (logs, jobs, notificações) da lógica principal |
| Filas | Database Queue | Jobs assíncronos para diminuição de stock pós-pedido e envio de e-mail |

---

## 3. Etapas Implementadas

### 3.1 Repository Pattern (Etapa 2)

Criadas 5 interfaces de contrato em `app/Repositories/Contracts/` e suas implementações Eloquent em `app/Repositories/`.

| Interface | Implementação | Métodos principais |
|-----------|--------------|-------------------|
| `ProductRepositoryInterface` | `ProductRepository` | `paginate`, `findById`, `findBySlug`, `create`, `update`, `delete`, `lowStock`, `syncTags`, `slugExists` |
| `CategoryRepositoryInterface` | `CategoryRepository` | `tree`, `all`, `findById`, `findBySlug`, `create`, `update`, `delete`, `slugExists` |
| `OrderRepositoryInterface` | `OrderRepository` | `paginateForUser`, `paginate`, `findById`, `findByIdForUser`, `create`, `updateStatus` |
| `CartRepositoryInterface` | `CartRepository` | `findOrCreateForUser`, `addItem`, `updateItem`, `removeItem`, `clear`, `findItemById`, `findItemByCartAndProduct` |
| `StockMovementRepositoryInterface` | `StockMovementRepository` | `paginateForProduct`, `forProduct`, `create` |

Os bindings interface → implementação foram registrados em `AppServiceProvider`:

```php
$this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
// ... demais bindings
```

---

### 3.2 DTOs — Data Transfer Objects (Etapa 3)

4 classes `readonly` criadas em `app/DTOs/`. Cada uma expõe um método estático `fromRequest()` que recebe um `FormRequest` validado e retorna uma instância tipada.

**Exemplo — `ProductDTO`:**
```php
readonly class ProductDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly ?float  $price,
        public readonly ?float  $costPrice,
        public readonly ?int    $quantity,
        public readonly ?int    $minQuantity,
        public readonly ?bool   $active,
        public readonly ?int    $categoryId,
        public readonly ?array  $tagIds,
    ) {}

    public static function fromRequest(FormRequest $request): self { ... }
    public function toArray(): array { ... }
}
```

DTOs criados: `ProductDTO`, `OrderDTO`, `CartItemDTO`, `StockMovementDTO`.

---

### 3.3 Services — Camada de Negócio (Etapa 4)

5 serviços em `app/Services/`, cada um injetado via interface de Repository. Todos os Services delegam **exclusivamente** ao Repository para acesso a dados — sem chamadas diretas ao Eloquent.

#### `ProductService`
- Paginação com filtros: `search`, `category_id`, `active`, `in_stock`, `low_stock`, `min_price`, `max_price`, `sort_by`, `sort_dir`
- Geração de slug único com detecção de colisão via `ProductRepository::slugExists()` (verifica inclusive registros soft-deleted)
- Cache com version-key busting (TTL 1 hora)
- Dispara `ProductCreated` e `StockLow` nos momentos corretos

#### `CategoryService`
- Árvore de categorias com eager loading de filhos e pais
- Cache com version-key busting (TTL 24 horas)
- Geração de slug único via `CategoryRepository::slugExists()`

#### `CartService`
- `findOrCreate` por usuário autenticado
- Validação de estoque disponível ao adicionar/atualizar item
- Verificação de item existente via `CartRepository::findItemByCartAndProduct()`
- Operação `addItem()` encapsulada em `DB::transaction()` para atomicidade
- Cálculo de total do carrinho

#### `OrderService`
- Criação de pedido a partir do carrinho em `DB::transaction()`
- Validação de estoque de cada item antes de criar
- Cálculo: `subtotal` (soma dos itens) + `tax` (10% do subtotal) = `total`
- Limpa o carrinho após criação bem-sucedida
- Dispara `OrderCreated` e `ProcessOrderJob`

#### `StockService`
- Movimentações de estoque tipadas:
  - `entrada` / `devolucao` → incrementa quantidade
  - `saida` / `venda` → decrementa (mínimo 0)
  - `ajuste` → define quantidade absoluta
- Registra `StockMovement` para cada operação
- Operação `recordMovement()` encapsulada em `DB::transaction()` para atomicidade
- Dispara `StockLow` via `ProductRepository::findById()` (sem `$product->fresh()`)

#### `AuthService`
- Registro de usuário com atribuição de role `customer`
- Login com validação de credenciais e geração de token Sanctum
- Logout com revogação do token corrente

---

### 3.4 Form Requests (Etapa 5)

8 classes em `app/Http/Requests/Api/V1/`:

| Classe | Endpoint | Destaques |
|--------|----------|-----------|
| `StoreProductRequest` | POST /products | nome único, price > 0, cost_price < price, usa `UniqueSlug` |
| `UpdateProductRequest` | PUT /products/{id} | campos `sometimes`, ignora próprio ID nos unique checks |
| `StoreOrderRequest` | POST /orders | endereços com campos aninhados obrigatórios |
| `UpdateOrderStatusRequest` | PUT /orders/{id}/status | status deve estar em `Order::STATUSES` |
| `AddCartItemRequest` | POST /cart/items | usa `SufficientStock` para validar estoque disponível |
| `UpdateCartItemRequest` | PUT /cart/items/{id} | quantity mínimo 1, usa `SufficientStock` para validar estoque |
| `RegisterRequest` | POST /auth/register | nome, email único, password confirmada com `Password::defaults()` |
| `LoginRequest` | POST /auth/login | email, password obrigatórios |

---

### 3.5 Custom Rules (Etapa 11 — implementada junto à 5)

3 regras de validação customizadas em `app/Rules/`:

**`SufficientStock`** — verifica se o produto possui quantidade suficiente em estoque:
```php
// Valida: product->quantity >= $requested
```

**`ValidParentCategory`** — garante que a categoria pai:
- Existe no banco
- Não é a própria categoria (auto-referência)
- Não cria referência circular (verificação recursiva)

**`UniqueSlug`** — verifica unicidade de slug entre produtos, com suporte a:
- Soft-deleted (`.withTrashed()`)
- Exceção por ID (para updates)

---

### 3.6 API Resources (Etapa 6)

8 classes em `app/Http/Resources/Api/V1/` com transformações e campos computados:

| Resource | Campos computados |
|----------|------------------|
| `ProductResource` | `in_stock` (qty > 0), `low_stock` (qty ≤ min_qty) |
| `CategoryResource` | `products_count` via `withCount` |
| `CartResource` | `items_count`, `total` (soma dos subtotais) |
| `CartItemResource` | `subtotal` (qty × price) |
| `OrderResource` | todos os valores monetários como `float`; `user` via `UserResource($this->whenLoaded('user'))` |
| `OrderItemResource` | `unit_price` e `total_price` como `float` |
| `ProductCollection` | extends `ResourceCollection`, `collects = ProductResource::class` |
| `UserResource` | `roles` (via Spatie), `permissions` (condicional) |

---

### 3.7 Controllers API v1 (Etapa 7)

`ApiResponseTrait` implementado em `app/Traits/ApiResponseTrait.php`, garantindo o formato padronizado em todas as respostas:

```json
// Sucesso simples
{ "success": true, "data": { ... } }

// Listagem paginada
{ "success": true, "data": [...], "meta": { "current_page": 1, "per_page": 15, "total": 55, "last_page": 4 }, "links": { "first": "...", "last": "...", "prev": null, "next": "..." } }

// Erro
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```

5 controllers em `app/Http/Controllers/Api/V1/`, todos seguindo o padrão Service injection + FormRequest + API Resource:

| Controller | Responsabilidades |
|-----------|------------------|
| `AuthController` | register (via `AuthService` + `RegisterRequest` + `UserResource`), login (`LoginRequest`), logout, me |
| `ProductController` | index (filtros), show, store, update, destroy, lowStock |
| `CategoryController` | index (árvore), show, products (paginados por categoria) |
| `CartController` | index (`CartResource`), addItem/updateItem (`CartItemResource`), removeItem, clear |
| `OrderController` | index (admin vê todos / customer vê os próprios), show, store, updateStatus |

---

### 3.8 Rotas API (Etapa 8)

22 endpoints registrados em `routes/api.php` sob o prefixo `/api/v1`:

#### Rotas Públicas
| Método | Endpoint | Controller |
|--------|----------|-----------|
| GET | `/products` | `ProductController@index` |
| GET | `/products/{id}` | `ProductController@show` (constraint: `id` numérico) |
| GET | `/categories` | `CategoryController@index` |
| GET | `/categories/{category}` | `CategoryController@show` |
| GET | `/categories/{category}/products` | `CategoryController@products` |
| POST | `/auth/register` | `AuthController@register` |
| POST | `/auth/login` | `AuthController@login` |

#### Rotas Autenticadas (Sanctum)
| Método | Endpoint | Controller |
|--------|----------|-----------|
| GET | `/auth/me` | `AuthController@me` |
| POST | `/auth/logout` | `AuthController@logout` |
| GET | `/cart` | `CartController@index` |
| POST | `/cart/items` | `CartController@addItem` |
| PUT | `/cart/items/{itemId}` | `CartController@updateItem` |
| DELETE | `/cart/items/{itemId}` | `CartController@removeItem` |
| DELETE | `/cart` | `CartController@clear` |
| GET | `/orders` | `OrderController@index` |
| POST | `/orders` | `OrderController@store` |
| GET | `/orders/{id}` | `OrderController@show` |

#### Rotas Admin (Sanctum + role:admin)
| Método | Endpoint | Controller |
|--------|----------|-----------|
| POST | `/products` | `ProductController@store` |
| PUT | `/products/{product}` | `ProductController@update` |
| DELETE | `/products/{product}` | `ProductController@destroy` |
| PUT | `/orders/{order}/status` | `OrderController@updateStatus` |
| GET | `/products/low-stock` | `ProductController@lowStock` |

---

### 3.9 Autenticação & Policies (Etapa 9)

**Middlewares Spatie registrados** em `bootstrap/app.php`:
```php
$middleware->alias([
    'role'              => RoleMiddleware::class,
    'permission'        => PermissionMiddleware::class,
    'role_or_permission'=> RoleOrPermissionMiddleware::class,
]);
```

**`ProductPolicy`:**
- `viewAny` / `view` → público (`?User` nullable — sem autenticação requerida)
- `create` / `update` / `delete` → requer role `admin`

**`OrderPolicy`:**
- `view` → `$user->id === $order->user_id` **ou** role `admin`
- `update` → apenas role `admin`

---

### 3.10 Events, Listeners & Jobs (Etapa 10)

#### Events
| Classe | Quando disparado |
|--------|-----------------|
| `ProductCreated` | Após criação de produto |
| `OrderCreated` | Após criação de pedido |
| `StockLow` | Quando `quantity ≤ min_quantity` |

#### Listeners
| Classe | Event | Ação |
|--------|-------|------|
| `LogProductCreated` | `ProductCreated` | Log informacional via `Log::info()` |
| `ProcessOrderListener` | `OrderCreated` | Despacha `SendOrderConfirmationEmail` |
| `NotifyStockLow` | `StockLow` | Log de warning com detalhes do produto |

Listeners descobertos automaticamente pelo Laravel (sem registro manual em `EventServiceProvider`).

#### Jobs (ShouldQueue — fila `database`)
| Classe | Função |
|--------|--------|
| `ProcessOrderJob` | Executa `StockService::decreaseStock()` para cada item do pedido |
| `SendOrderConfirmationEmail` | Placeholder para envio de e-mail de confirmação |

---

### 3.11 Cache (Etapa 12)

**Estratégia: Redis + `Cache::tags()`**

Driver `redis` via `predis/predis` (cliente PHP puro, sem extensão nativa necessária). Tags permitem invalidação atômica de grupos inteiros de chaves sem acúmulo de entradas órfãs.

**Configuração:**
```ini
# .env
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

**Funcionamento:**
```php
// Armazenamento com tag
Cache::tags(['products'])->remember('products.list.{$hash}.{$perPage}', now()->addHour(), fn() => ...);

// Invalidação: flush atômico de todas as chaves do grupo
Cache::tags(['products'])->flush();
```

| Entidade | Tag | TTL | Invalidação |
|----------|-----|-----|-------------|
| Produtos (listagem + individual + slug) | `products` | 1 hora | create / update / delete |
| Categorias (árvore + listagem flat) | `categories` | 24 horas | create / update / delete |

**Ambiente de testes:** `phpunit.xml` define `CACHE_STORE=array`, que suporta tags nativamente — sem necessidade de Redis nos testes.

---

### 3.12 Seeders & Factories (Etapa 13)

#### Factories implementadas

| Factory | Campos gerados |
|---------|---------------|
| `CategoryFactory` | name, slug (Str::slug), description, parent_id (null), active |
| `ProductFactory` | name, slug, description, price (10–500), cost_price (< price), quantity (0–200), min_quantity (5–20), active, category_id. States: `inactive()`, `outOfStock()`, `lowStock()` |
| `TagFactory` | name, slug (auto-gerado pelo model boot) |
| `OrderFactory` | user_id, status (aleatório de `Order::STATUSES`), subtotal, tax, total, shipping_cost, shipping_address, billing_address, notes |
| `OrderItemFactory` | order_id, product_id, quantity, unit_price, total_price |
| `CartFactory` | user_id |
| `CartItemFactory` | cart_id, product_id, quantity |
| `StockMovementFactory` | product_id, type (de `StockMovement::TYPES`), quantity, reason |

#### Seeders e dados gerados

| Seeder | Dados criados |
|--------|--------------|
| `RoleAndPermissionSeeder` | Roles: `admin`, `customer`. Permissions: `manage-products`, `manage-categories`, `manage-orders`, `view-orders`, `view-stock`. Admin recebe todas; customer recebe `view-orders` |
| `UserSeeder` | 1 admin (`admin@example.com` / `password`) + 5 customers com role |
| `CategorySeeder` | 5 categorias raiz + 3 subcategorias cada = 20 total |
| `TagSeeder` | 15 tags |
| `ProductSeeder` | 50 produtos normais + 5 com estoque baixo = 55 total; cada com 1–4 tags |
| `OrderSeeder` | 20 pedidos para clientes aleatórios; cada com 1–4 `OrderItems` |

---

## 4. Problemas Encontrados e Soluções

### 4.1 MySQL: Índice composto excedendo 1000 bytes

**Problema:** A migration do Spatie Permission usava `string('name')` (varchar 255) e `string('guard_name')` (varchar 255) em um índice composto único. Com `utf8mb4`, cada caractere ocupa até 4 bytes, resultando em `(255 + 255) × 4 = 2040 bytes` — acima do limite MySQL de 1000 bytes por índice.

**Solução:** Reduzir os tamanhos:
```php
// Antes
$table->string('name');
$table->string('guard_name');

// Depois
$table->string('name', 125);
$table->string('guard_name', 25);
```

---

### 4.2 `Cache::tags()` — migração para Redis

**Contexto inicial:** O driver `file` não suporta cache tags, então a implementação inicial usou version-key cache busting. Posteriormente, decidiu-se migrar para Redis + tags para eliminar acúmulo de chaves órfãs e ter invalidação mais expressiva.

**Solução adotada:**
- Instalado `predis/predis ^3.0` (cliente PHP puro, sem extensão phpredis necessária)
- `CACHE_STORE=redis` + `REDIS_CLIENT=predis` no `.env`
- `ProductService` e `CategoryService` migrados integralmente para `Cache::tags([...])->remember()` e `->flush()`
- Testes isolados do Redis via `CACHE_STORE=array` no `phpunit.xml` (driver array suporta tags nativamente)

---

### 4.3 Tabela `telescope_entries` ausente

**Problema:** Após `migrate:fresh`, o Telescope tentava gravar entradas mas a tabela não existia (migration não estava publicada).

**Solução:**
```bash
php artisan telescope:install
php artisan migrate
```

---

### 4.4 Correções pós-revisão (Round 1–3)

Após revisão de código, os seguintes problemas foram identificados e corrigidos:

| Problema | Severidade | Correção |
|----------|-----------|---------|
| Rota `GET products/admin/low-stock` inalcançável (conflito com `products/{id}`) | CRÍTICO | Constraint `where('id', '[0-9]+')` na rota `products/{id}` + path simplificado para `products/low-stock` |
| Chamadas Eloquent diretas em 4 Services (`slugExists`, `$cart->items()`, `$product->load()`, `$product->fresh()`) | ALTO | Métodos `slugExists()` e `findItemByCartAndProduct()` adicionados aos Repositories; Services refatorados para delegar exclusivamente |
| `AuthController` sem Service/FormRequest/Resource | ALTO | Criados `AuthService`, `RegisterRequest`, `LoginRequest`, `UserResource` |
| Job duplicado `UpdateStockAfterOrder` (idêntico a `ProcessOrderJob`) | ALTO | Arquivo removido |
| `CartService::addItem()` e `StockService::recordMovement()` sem transação | MÉDIO | Ambos encapsulados em `DB::transaction()` |
| `UpdateCartItemRequest` sem validação de estoque | MÉDIO | Adicionada regra `SufficientStock` |
| `CartController::addItem()`/`updateItem()` retornando model cru | MENOR | Refatorados para usar `CartItemResource` |
| `StockMovementRepository` sem eager loading | MENOR | Adicionado `->with('product')` |

---

### 4.5 Correções pós-revisão (Round 4)

Segunda rodada de revisão identificou e corrigiu os seguintes problemas:

| Problema | Severidade | Correção |
|----------|-----------|---------|
| `throttle:api` definido no `RateLimiter` mas não aplicado no grupo de middleware da API | ALTO | Adicionado `$middleware->throttleApi()` em `bootstrap/app.php` |
| `withExceptions()` vazio — erros retornavam HTML/stack trace em vez de JSON | ALTO | Handlers adicionados para `ValidationException` (422), `AuthenticationException` (401), `NotFoundHttpException` (404), `MethodNotAllowedHttpException` (405) e `UnauthorizedException` Spatie (403) — todos retornam `{success: false, message: ...}` |
| `RoleAndPermissionSeeder` criava apenas roles, sem permissions | MÉDIO | Adicionadas 5 permissions (`manage-products`, `manage-categories`, `manage-orders`, `view-orders`, `view-stock`) e sincronizadas com os roles correspondentes |
| `OrderResource` retornava `user_id` (scalar) em vez do objeto `user` | MÉDIO | Substituído por `new UserResource($this->whenLoaded('user'))` |
| `OrderRepository::paginateForUser()` e `findByIdForUser()` não eager-loadavam `user` | MÉDIO | Adicionado `'user'` ao array de `with()` em ambos os métodos |

---

## 5. Verificação Final

### 5.1 Pint (Code Style)
```
vendor/bin/pint --dirty → {"result":"pass"}
```
Todos os arquivos modificados estão em conformidade com o estilo do projeto.

### 5.2 Route List
```
php artisan route:list --path=api/v1 → 22 rotas registradas
```

### 5.3 Testes Manuais (Smoke Tests)

**GET `/api/v1/products?per_page=2`**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Et Qui Cumque",
      "slug": "et-qui-cumque",
      "price": 321.65,
      "in_stock": true,
      "low_stock": false,
      "category": { "id": 18, "name": "Sunt Quaerat" },
      "tags": [...]
    }
  ],
  "meta": { "current_page": 1, "per_page": 2, "total": 55 },
  "links": { ... }
}
```

**POST `/api/v1/auth/login`** (admin@example.com / password)
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Admin", "email": "admin@example.com", "roles": ["admin"] },
    "token": "2|uLrc1hqWDPlCj8GJa4lCvAl7S67tmEUSOlrC7ZTs51841c59"
  }
}
```

---

## 6. Arquivos Criados / Modificados

### Novos Arquivos (60 arquivos)

```
app/
├── DTOs/
│   ├── ProductDTO.php
│   ├── OrderDTO.php
│   ├── CartItemDTO.php
│   └── StockMovementDTO.php
├── Events/
│   ├── ProductCreated.php
│   ├── OrderCreated.php
│   └── StockLow.php
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── CartController.php
│   │   └── OrderController.php
│   ├── Requests/Api/V1/
│   │   ├── StoreProductRequest.php
│   │   ├── UpdateProductRequest.php
│   │   ├── StoreOrderRequest.php
│   │   ├── UpdateOrderStatusRequest.php
│   │   ├── AddCartItemRequest.php
│   │   ├── UpdateCartItemRequest.php
│   │   ├── RegisterRequest.php
│   │   └── LoginRequest.php
│   └── Resources/Api/V1/
│       ├── ProductResource.php
│       ├── ProductCollection.php
│       ├── CategoryResource.php
│       ├── OrderResource.php
│       ├── OrderItemResource.php
│       ├── CartResource.php
│       ├── CartItemResource.php
│       └── UserResource.php
├── Jobs/
│   ├── ProcessOrderJob.php
│   └── SendOrderConfirmationEmail.php
├── Listeners/
│   ├── LogProductCreated.php
│   ├── ProcessOrderListener.php
│   └── NotifyStockLow.php
├── Policies/
│   ├── ProductPolicy.php
│   └── OrderPolicy.php
├── Repositories/
│   ├── Contracts/
│   │   ├── ProductRepositoryInterface.php
│   │   ├── CategoryRepositoryInterface.php
│   │   ├── OrderRepositoryInterface.php
│   │   ├── CartRepositoryInterface.php
│   │   └── StockMovementRepositoryInterface.php
│   ├── ProductRepository.php
│   ├── CategoryRepository.php
│   ├── OrderRepository.php
│   ├── CartRepository.php
│   └── StockMovementRepository.php
├── Rules/
│   ├── SufficientStock.php
│   ├── ValidParentCategory.php
│   └── UniqueSlug.php
├── Services/
│   ├── AuthService.php
│   ├── ProductService.php
│   ├── CategoryService.php
│   ├── CartService.php
│   ├── OrderService.php
│   └── StockService.php
└── Traits/
    └── ApiResponseTrait.php

database/
├── factories/           (8 factories implementadas)
└── seeders/
    ├── RoleAndPermissionSeeder.php
    ├── UserSeeder.php
    ├── CategorySeeder.php
    ├── TagSeeder.php
    ├── ProductSeeder.php
    └── OrderSeeder.php
```

### Arquivos Modificados

| Arquivo | Mudanças |
|---------|---------|
| `app/Models/Order.php` | Adicionados casts `array` para `shipping_address` e `billing_address` |
| `app/Providers/AppServiceProvider.php` | Bindings de todos os 5 repositories |
| `routes/api.php` | 22 endpoints REST completos |
| `bootstrap/app.php` | Aliases Spatie (role, permission, role_or_permission); `throttleApi()`; handlers de exceção para todos os erros HTTP comuns em rotas API |
| `database/seeders/DatabaseSeeder.php` | Chamada dos 6 seeders em ordem de dependência |
| `database/seeders/RoleAndPermissionSeeder.php` | Adicionadas 5 permissions e sincronização com roles |
| `database/migrations/*_create_permission_tables.php` | Fix no tamanho das colunas para índice composto MySQL |
| `database/factories/ProductFactory.php` | Definition completa com Faker |
| `database/factories/CategoryFactory.php` | Definition completa com Faker |
| `app/Services/ProductService.php` | Migrado de version-key para `Cache::tags(['products'])` |
| `app/Services/CategoryService.php` | Migrado de version-key para `Cache::tags(['categories'])` |
| `app/Http/Resources/Api/V1/OrderResource.php` | `user` retorna `UserResource($this->whenLoaded('user'))` |
| `app/Repositories/OrderRepository.php` | `paginateForUser` e `findByIdForUser` eager-loadam `user` |
| `composer.json` | Adicionado `predis/predis: ^3.0` |
| `.env` | `CACHE_STORE=redis`, `REDIS_CLIENT=predis` |
| `.env.example` | `CACHE_STORE=redis`, `REDIS_CLIENT=predis` |
| `task.md` | Checkboxes do Agente 1 marcados como concluídos |

---

## 7. Dependências entre Agentes

O trabalho do Agente 1 é consumido diretamente pelos seguintes agentes:

| Agente downstream | O que consome |
|------------------|--------------|
| **Agente 2 (Testes)** | Todos os Services, Repositories, Controllers, Policies e Rules — são os targets dos testes |
| **Agente 3 (Frontend Admin)** | Endpoints: POST/PUT/DELETE products, GET/PUT orders, GET low-stock |
| **Agente 4 (Frontend Público)** | Endpoints: GET products (com filtros), GET categories, POST auth, cart CRUD, POST orders |
| **Agente 5 (Docs/DevOps)** | Route list e estrutura dos Resources para gerar Swagger/OpenAPI |

---

## 8. Próximos Passos Recomendados

1. **Agente 2** deve criar testes para todos os endpoints, com atenção especial a:
   - Validação de estoque insuficiente (`SufficientStock`)
   - Acesso negado a rotas admin por customers
   - Criação de pedido com estoque zero
   - Cache invalidation após operações CUD (testes usam driver `array` — sem necessidade de Redis)
   - Formato `{success: false}` dos handlers de exceção

2. **Redis já configurado** — `CACHE_STORE=redis` + `REDIS_CLIENT=predis` + `predis/predis ^3.0` instalado. Em produção, garantir que Redis esteja disponível na porta 6379 (ou configurar `REDIS_HOST`/`REDIS_PASSWORD` no `.env`)

3. **Implementar envio real de e-mail** em `SendOrderConfirmationEmail` (substituir o `Log::info()` por `Mail::send()`)

4. **Configurar queue worker** em produção: `php artisan queue:work --queue=default`
