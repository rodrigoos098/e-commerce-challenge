# PROJECT.md — Shopsugi E-commerce
<img width="1920" height="919" alt="Screenshot 2026-03-30 at 12-45-58 Shopsugi" src="https://github.com/user-attachments/assets/ef15e2d5-ce4b-460d-a42c-c8ad28d8f703" />

## Indice

1. [Visao Geral](#1-visao-geral)
2. [Requisitos](#2-requisitos)
3. [Setup e Execucao](#3-setup-e-execucao)
4. [Decisoes Arquiteturais](#4-decisoes-arquiteturais)
5. [Bibliotecas e Justificativas](#5-bibliotecas-e-justificativas)
6. [Estrutura de Pastas](#6-estrutura-de-pastas)
7. [Testes](#7-testes)
8. [Documentacao da API](#8-documentacao-da-api)
9. [Usuarios Padrao](#9-usuarios-padrao)

---

## 1. Visao Geral

Shopsugi e um e-commerce completo construido com **Laravel 12** (backend) e **React 19** (frontend via Inertia.js v2). O design segue a tematica "Kintsugi" — arte japonesa de reparar ceramicas com ouro — refletida na paleta de cores dourada e nos warm neutrals.

**Funcionalidades principais:**

- Catalogo de produtos com filtros (categoria, preco, busca textual, tags)
- Carrinho de compras (funciona para visitantes e usuarios logados, com merge no login)
- Checkout com simulacao de pagamento
- Gerenciamento de enderecos (com autopreenchimento via ViaCEP)
- Acompanhamento de pedidos com timeline de status
- Painel administrativo (dashboard, CRUD de produtos/categorias/tags, gestao de pedidos, alerta de estoque baixo)
- API RESTful versionada (v1) com autenticacao via Sanctum
- Controle de acesso baseado em roles (admin/customer) via Spatie Permission
- Sistema de eventos e jobs assincronos para processamento de pedidos e envio de emails
- Controle de estoque com movimentacoes rastreadas

---

## 2. Requisitos

| Dependencia | Versao Minima                               |
| ----------- | ------------------------------------------- |
| PHP         | 8.2                                         |
| Composer    | 2.x                                         |
| Node.js     | 18+ (recomendado 20)                        |
| npm         | 9+                                          |
| MySQL       | 8.0 (ou SQLite para desenvolvimento rapido) |
| Redis       | 6+ (obrigatorio para cache com tags)        |

### Extensoes PHP necessarias

`pdo`, `pdo_mysql` (ou `pdo_sqlite`), `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `intl`, `opcache`, `redis` (ou usar predis via Composer)

---

## 3. Setup e Execucao

### 3.1 Setup rapido (um comando)

```bash
composer run setup
```

Esse comando executa automaticamente:

1. `composer install` — instala dependencias PHP
2. Copia `.env.example` para `.env` (se nao existir)
3. `php artisan key:generate` — gera a chave de criptografia
4. `php artisan migrate --seed --force` — cria as tabelas e popula com dados demo
5. `npm install` — instala dependencias frontend
6. `npm run build` — compila assets (React + Tailwind)

### 3.2 Setup manual (passo a passo)

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 3. Configurar banco de dados
#    Opcao A — SQLite (padrao, zero config):
touch database/database.sqlite

#    Opcao B — MySQL:
#    Edite .env com as credenciais do seu MySQL:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=e_commerce_challenge
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Configurar Redis (obrigatorio para cache)
#    Certifique-se de que o Redis esta rodando em localhost:6379
#    .env ja vem configurado com:
#    CACHE_STORE=redis
#    REDIS_CLIENT=predis

# 5. Rodar migrations e seed
php artisan migrate
php artisan db:seed

# 6. Criar symlink de storage (para imagens de produto)
php artisan storage:link

# 7. Compilar frontend
npm run build
```

### 3.3 Executar em desenvolvimento

```bash
composer run dev
```

Esse comando inicia simultaneamente:

- **php artisan serve** — servidor PHP em `http://localhost:8000`
- **npm run dev** — Vite dev server com HMR em `http://localhost:5173`
- **php artisan queue:listen** — worker para jobs assincronos

Acesse `http://localhost:8000` no navegador.

### 3.4 Outros comandos uteis

| Comando              | Descricao                                         |
| -------------------- | ------------------------------------------------- |
| `npm run build`      | Compila assets para producao                      |
| `npm run lint`       | Verifica codigo TypeScript com ESLint             |
| `npm run lint:fix`   | Corrige automaticamente problemas de lint         |
| `npm run format`     | Formata codigo com Prettier                       |
| `npm run type-check` | Verifica tipos TypeScript (sem emitir)            |
| `vendor/bin/pint`    | Formata codigo PHP (PSR-12 + regras customizadas) |
| `php artisan test`   | Executa toda a suite de testes                    |

---

## 4. Decisoes Arquiteturais

### 4.1 Monolito com Inertia.js (SPA sem a complexidade de SPA)

A aplicacao e um **monolito Laravel** que serve o frontend React via Inertia.js. Essa abordagem foi escolhida porque:

- **Sem API separada para o frontend**: o Inertia conecta diretamente controllers Laravel a componentes React, eliminando a necessidade de uma API dedicada para a UI.
- **Roteamento server-side**: as rotas vivem em `routes/web.php`, com toda a logica de autorizacao e redirect no backend.
- **API REST separada existe** (`routes/api.php`) para integracao com clientes externos, versionada em `/api/v1/`.

### 4.2 Repository Pattern

Cada entidade de dominio tem uma interface de repositorio (`app/Repositories/Contracts/`) e uma implementacao concreta (`app/Repositories/`). O binding e feito no `AppServiceProvider`.

**Por que**: desacopla a camada de servicos do Eloquent, facilita testes com mocks, e permite trocar a implementacao de persistencia sem alterar a logica de negocio.

### 4.3 Service Layer

Toda logica de negocio vive em `app/Services/`. Controllers sao magros — apenas recebem requests, delegam para services, e retornam responses.

**Servicos existentes**: `AuthService`, `ProductService`, `CategoryService`, `OrderService`, `CartService`, `CartPricingService`, `CartTotalsService`, `StockService`, `TagService`.

### 4.4 DTOs (Data Transfer Objects)

`app/DTOs/` contem objetos imutaveis para transferencia de dados entre camadas (`OrderDTO`, `ProductDTO`, `StockMovementDTO`, `CartItemDTO`). Evita passar arrays associativos pela aplicacao.

### 4.5 Event-Driven Order Processing

O ciclo de vida de um pedido e orientado a eventos:

```
OrderCreated
  ├── ProcessOrderListener → dispatcha ProcessOrderPipeline (job)
  └── QueueOrderCreatedNotification → dispatcha SendOrderConfirmationEmail (job)

OrderPaymentConfirmed → QueueOrderPaymentConfirmedNotification
OrderCancelled → QueueOrderCancelledNotification
OrderShipped → QueueOrderShippedNotification
OrderDelivered → QueueOrderDeliveredNotification
```

**Por que**: desacopla o fluxo do pedido, permite processamento assincrono, e facilita adicionar novos side-effects (ex: notificar estoque, analytics) sem modificar o controller.

### 4.6 State Machine de Pedidos

O model `Order` define transicoes de status permitidas via `ALLOWED_TRANSITIONS`:

```
pending → processing → shipped → delivered
pending → cancelled
processing → cancelled
```

Transicoes invalidas sao rejeitadas pelo service. O metodo `canTransitionTo()` valida antes de aplicar.

### 4.7 Guest Cart com Merge

Visitantes podem adicionar produtos ao carrinho (identificado por `session_id`). Ao fazer login, o carrinho do visitante e mesclado com o carrinho do usuario autenticado.

### 4.8 Logging Estruturado

Canais de log dedicados para dominios criticos:

- `orders` — `storage/logs/orders.log` (retencao 30 dias)
- `stock` — `storage/logs/stock.log` (retencao 30 dias)
- `auth` — `storage/logs/auth.log` (retencao 30 dias)

### 4.9 Frontend: Custom Components Only

Nenhuma biblioteca de componentes UI (shadcn/ui, Radix, Headless UI) foi utilizada. Todos os componentes sao construidos do zero com Tailwind CSS, priorizando controle total sobre o design "Kintsugi".

---

## 5. Bibliotecas e Justificativas

### 5.1 Backend (PHP / Composer)

| Pacote                      | Versao | Justificativa                                                                               |
| --------------------------- | ------ | ------------------------------------------------------------------------------------------- |
| `laravel/framework`         | ^12.0  | Framework principal. Versao mais recente com estrutura simplificada.                        |
| `laravel/sanctum`           | ^4.3   | Autenticacao SPA (cookie-based) + API tokens. Padrao Laravel para SPAs com Inertia.         |
| `inertiajs/inertia-laravel` | ^2.0   | Bridge server-side do Inertia.js. Permite servir React diretamente dos controllers Laravel. |
| `spatie/laravel-permission` | ^6.24  | RBAC (roles e permissions). Solucao madura e bem testada para controle de acesso.           |
| `predis/predis`             | ^3.0   | Cliente Redis em PHP puro. Nao requer extensao C (`phpredis`), simplificando setup.         |
| `darkaonline/l5-swagger`    | ^10.1  | Gera documentacao OpenAPI/Swagger a partir de annotations PHPDoc nos controllers.           |
| `opcodesio/log-viewer`      | ^3.21  | Interface web para visualizar logs Laravel. Util em desenvolvimento e debug.                |

### Dev-only

| Pacote              | Justificativa                                                           |
| ------------------- | ----------------------------------------------------------------------- |
| `laravel/telescope` | Debug de queries, requests, jobs, exceptions. Desabilitado em producao. |
| `laravel/pint`      | Code formatter PHP (PSR-12 + regras customizadas).                      |
| `laravel/sail`      | Disponivel mas nao utilizado (sem Docker configurado).                  |
| `laravel/pail`      | Tail de logs em tempo real no terminal.                                 |
| `laravel/boost`     | MCP server para desenvolvimento assistido por IA.                       |
| `phpunit/phpunit`   | Framework de testes.                                                    |
| `mockery/mockery`   | Mocking para testes unitarios.                                          |

### 5.2 Frontend (Node / npm)

| Pacote                | Versao | Justificativa                                                               |
| --------------------- | ------ | --------------------------------------------------------------------------- |
| `react`               | ^19.2  | Biblioteca UI. Versao 19 com melhorias de performance e novos hooks.        |
| `react-dom`           | ^19.2  | Renderizacao DOM para React.                                                |
| `@inertiajs/react`    | ^2.3   | Adapter React do Inertia.js. Fornece `usePage`, `router`, `Link`, etc.      |
| `react-hook-form`     | ^7.71  | Gerenciamento de formularios com minimo re-render. Performatico e flexivel. |
| `@hookform/resolvers` | ^5.2   | Integra zod com react-hook-form para validacao declarativa.                 |
| `zod`                 | ^4.3   | Validacao de schemas TypeScript-first. Type-safe e composavel.              |
| `react-hot-toast`     | ^2.6   | Notificacoes toast leves e customizaveis. Unica dependencia de UI.          |
| `axios`               | ^1.11  | Cliente HTTP. Usado pelo bootstrap do Laravel para CSRF tokens.             |

### Dev-only

| Pacote                              | Justificativa                                             |
| ----------------------------------- | --------------------------------------------------------- |
| `vite`                              | Bundler ultra-rapido com HMR.                             |
| `laravel-vite-plugin`               | Integra Vite com o build process do Laravel.              |
| `@vitejs/plugin-react`              | Suporte JSX/TSX no Vite.                                  |
| `tailwindcss` + `@tailwindcss/vite` | Framework CSS utility-first. Versao 4 com config via CSS. |
| `typescript`                        | Type-checking estatico. Strict mode habilitado.           |
| `eslint` + plugins                  | Linting de TypeScript/React.                              |
| `prettier`                          | Formatacao de codigo consistente.                         |

---

## 6. Estrutura de Pastas

```
shopsugi/
├── app/
│   ├── Console/                    # (vazio — Laravel 12 nao usa Kernel)
│   ├── DTOs/                       # Data Transfer Objects
│   │   ├── CartItemDTO.php
│   │   ├── OrderDTO.php
│   │   ├── ProductDTO.php
│   │   └── StockMovementDTO.php
│   ├── Events/                     # Eventos de dominio
│   │   ├── OrderCreated.php
│   │   ├── OrderPaymentConfirmed.php
│   │   ├── OrderCancelled.php
│   │   ├── OrderShipped.php
│   │   ├── OrderDelivered.php
│   │   ├── ProductCreated.php
│   │   └── StockLow.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/              # Controllers do painel admin (Inertia)
│   │   │   ├── Api/V1/             # Controllers da API REST
│   │   │   ├── Auth/               # Controllers de autenticacao (Inertia)
│   │   │   └── *.php               # Controllers publicos e de customer
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   ├── Requests/               # Form Requests (validacao)
│   │   └── Resources/Api/V1/       # Eloquent API Resources
│   ├── Jobs/                       # Jobs assincronos (queue)
│   │   ├── ProcessOrderPipeline.php
│   │   └── SendOrderConfirmationEmail.php
│   ├── Listeners/                  # Event listeners
│   ├── Mail/                       # Mailables
│   ├── Models/                     # Eloquent models (10 models)
│   ├── Policies/                   # Authorization policies
│   ├── Providers/
│   │   ├── AppServiceProvider.php  # Bindings de repositorios + eventos
│   │   └── TelescopeServiceProvider.php
│   ├── Repositories/
│   │   ├── Contracts/              # Interfaces dos repositorios
│   │   └── *.php                   # Implementacoes concretas
│   ├── Rules/                      # Custom validation rules
│   ├── Services/                   # Logica de negocio (9 services)
│   └── Traits/                     # Traits reutilizaveis
│
├── bootstrap/
│   ├── app.php                     # Config de middleware, routing, exceptions
│   └── providers.php               # Service providers registrados
│
├── config/                         # Arquivos de configuracao Laravel
│   ├── app.php                     # Nome, timezone (America/Sao_Paulo), locale (pt_BR)
│   ├── database.php                # SQLite/MySQL + Redis
│   ├── logging.php                 # Canais: orders, stock, auth (30 dias)
│   ├── l5-swagger.php              # Config do Swagger/OpenAPI
│   ├── permission.php              # Spatie Permission
│   └── ...
│
├── database/
│   ├── factories/                  # 10 factories (User, Product, Order, etc.)
│   ├── migrations/                 # 19 migrations
│   ├── seeders/                    # 9 seeders (roles, usuarios, 50 produtos, etc.)
│   │   └── images/products/        # 50 imagens seed (1.webp a 50.webp)
│   └── database.sqlite             # Banco SQLite para dev rapido
│
├── resources/
│   ├── css/app.css                 # Tailwind v4 @theme (paleta Kintsugi, fontes, animacoes)
│   ├── js/
│   │   ├── app.tsx                 # Entry point Inertia + React
│   │   ├── Components/
│   │   │   ├── Admin/              # 7 componentes (DataTable, Sidebar, StatCard, etc.)
│   │   │   ├── Public/             # 11 componentes (ProductCard, HeroBanner, etc.)
│   │   │   └── Shared/             # 6 componentes (Button, Modal, Spinner, etc.)
│   │   ├── hooks/                  # useScrollReveal, useCartSound
│   │   ├── Layouts/                # PublicLayout, AdminLayout
│   │   ├── Pages/                  # 26 paginas organizadas por secao
│   │   │   ├── Home.tsx
│   │   │   ├── Auth/               # Login, Register, ForgotPassword, etc.
│   │   │   ├── Products/           # Index (catalogo), Show (detalhe)
│   │   │   ├── Customer/           # Cart, Checkout, Orders, Addresses, Profile
│   │   │   └── Admin/              # Dashboard, Products, Categories, Tags, Orders, Stock
│   │   ├── types/                  # TypeScript types (shared, admin, public)
│   │   └── utils/                  # Helpers (format, routes, cepLookup, productImage)
│   └── views/                      # Blade templates (app shell + emails)
│
├── routes/
│   ├── web.php                     # Rotas Inertia (publicas, auth, customer, admin)
│   ├── api.php                     # API REST v1 (Sanctum auth)
│   └── console.php                 # Comandos Artisan (apenas inspire)
│
├── storage/
│   ├── api-docs/api-docs.json      # Swagger spec gerada
│   ├── app/public/                 # Arquivos publicos (logos, imagens de produto)
│   └── logs/                       # Logs (laravel, orders, stock, auth)
│
├── tests/
│   ├── Feature/                    # 30 testes de integracao
│   │   ├── Api/V1/                 # Testes da API (Auth, Product, Category, Cart, Tag, Order)
│   │   ├── Auth/                   # Password reset, email verification
│   │   ├── Documentation/          # Swagger documentation
│   │   ├── Observability/          # Structured logging
│   │   ├── Security/               # Configuration hardening
│   │   ├── Web/                    # Cart, checkout, catalog, admin, customer
│   │   └── *.php                   # Order flow, stock flow, policies, authorization
│   └── Unit/                       # 20 testes unitarios
│       ├── Models/                 # Testes de models e relationships
│       ├── Repositories/           # Testes de repositorios
│       └── Services/               # Testes de services
│
├── composer.json                   # Dependencias PHP + scripts (setup, dev, test)
├── package.json                    # Dependencias Node + scripts (build, lint, format)
├── vite.config.js                  # Bundler config (React, Tailwind, Laravel plugin)
├── tsconfig.json                   # TypeScript strict mode, path alias @/
├── phpunit.xml                     # Config PHPUnit (SQLite in-memory para testes)
├── pint.json                       # Code style PHP (PSR-12 + customizacoes)
├── eslint.config.js                # ESLint flat config (TypeScript + React)
└── .prettierrc                     # Prettier config
```

---

## 7. Testes

### 7.1 Visao geral

| Tipo        | Arquivos | Diretorio        |
| ----------- | -------- | ---------------- |
| **Feature** | 30       | `tests/Feature/` |
| **Unit**    | 20       | `tests/Unit/`    |
| **Total**   | **50**   |                  |

Os testes usam **PHPUnit 11** com **SQLite in-memory** (`:memory:`). Cache, mail e queue sao substituidos por drivers `array`/`sync` durante os testes (configurado em `phpunit.xml`).

### 7.2 Executar testes

```bash
# Toda a suite
php artisan test --compact

# Apenas testes de feature
php artisan test --compact tests/Feature/

# Apenas testes unitarios
php artisan test --compact tests/Unit/

# Um arquivo especifico
php artisan test --compact tests/Feature/OrderFlowTest.php

# Filtrar por nome de metodo
php artisan test --compact --filter=testUserCanCreateOrder
```

### 7.3 Cobertura dos testes

Os testes cobrem:

- **API REST**: CRUD completo de todos os endpoints (auth, products, categories, tags, cart, orders)
- **Autenticacao**: login, registro, reset de senha, verificacao de email
- **Autorizacao**: policies (OrderPolicy, ProductPolicy, AddressPolicy), role-based access
- **Fluxo de pedidos**: criacao, processamento assincrono (jobs), transicoes de status, cancelamento
- **Carrinho**: CRUD, merge de carrinho guest com usuario, calculo de totais e impostos
- **Estoque**: movimentacoes, alerta de estoque baixo
- **Seguranca**: configuration hardening, validacao de input
- **Observabilidade**: logging estruturado por canal
- **Documentacao**: Swagger spec valida e acessivel

### 7.4 Linting e formatacao

```bash
# PHP — formatar com Pint
vendor/bin/pint                    # formata tudo
vendor/bin/pint --dirty            # formata apenas arquivos modificados (git)
vendor/bin/pint --test             # verifica sem modificar

# TypeScript — lint
npm run lint                       # verifica
npm run lint:fix                   # corrige automaticamente

# TypeScript — type-check
npm run type-check                 # verifica tipos sem emitir arquivos

# TypeScript/JSX — formatar com Prettier
npm run format
```

---

## 8. Documentacao da API

A API REST e documentada com **OpenAPI 3.0** via L5-Swagger.

### 8.1 Acessar a documentacao

1. Inicie o servidor: `composer run dev`
2. Acesse: `http://localhost:8000/api/documentation`

> A rota e protegida por middleware admin-only em producao. Em desenvolvimento, e acessivel diretamente.

### 8.2 Regenerar a documentacao

```bash
php artisan l5-swagger:generate
```

A spec gerada e salva em `storage/api-docs/api-docs.json`.

### 8.3 Estrutura da API

Base URL: `/api/v1`

| Grupo          | Endpoints                                                         | Autenticacao                              |
| -------------- | ----------------------------------------------------------------- | ----------------------------------------- |
| **Auth**       | `POST /register`, `POST /login`, `POST /logout`, `GET /me`        | Publico (register/login) / Sanctum        |
| **Products**   | `GET /products`, `GET /products/{id}`, `POST`, `PUT`, `DELETE`    | Publico (listagem) / Sanctum + Policy     |
| **Categories** | `GET /categories`, `GET /categories/{id}`, CRUD                   | Publico (listagem) / Sanctum + role:admin |
| **Tags**       | `GET /tags`, CRUD                                                 | Publico (listagem) / Sanctum + role:admin |
| **Cart**       | `GET /cart`, `POST /cart/items`, `PUT`, `DELETE`, `DELETE /clear` | Sanctum                                   |
| **Orders**     | `GET /orders`, `GET /orders/{id}`, `POST`, `PATCH /{id}/cancel`   | Sanctum + Policy                          |
| **Stock**      | `GET /stock/low`                                                  | Sanctum + role:admin                      |

---

## 9. Usuarios Padrao

Apos executar `php artisan db:seed`, os seguintes usuarios estao disponiveis:

| Email                   | Senha      | Role     | Descricao                         |
| ----------------------- | ---------- | -------- | --------------------------------- |
| `admin@example.com`     | `password` | admin    | Acesso total (painel admin + API) |
| `customer@example.com`  | `password` | customer | Cliente padrao                    |
| + 5 usuarios aleatorios | `password` | customer | Gerados via factory               |

### Dados seed incluidos

- **50 produtos** com imagens (`.webp`), precos, categorias e tags
- **38 categorias** em hierarquia (8 pais com filhos)
- **30 tags** (Feito a Mao, Edicao Limitada, Sustentavel, etc.)
- **~20 pedidos** com itens e movimentacoes de estoque
- **Carrinhos** para alguns clientes
- **Roles e permissions** (admin: 5 permissions, customer: 1 permission)
