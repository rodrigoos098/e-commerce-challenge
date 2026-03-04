# Desafio: Sistema de E-commerce - Nível Pleno

**Atenção, Dev!**

Este desafio foi projetado para avaliar conhecimentos avançados em Laravel, arquitetura de software e boas práticas. Antes de começar, leia com atenção todas as instruções abaixo.

---

## 📋 Contexto do Projeto

Você deverá desenvolver um sistema de e-commerce completo com as seguintes funcionalidades:
- Gestão de produtos com categorias e tags
- Sistema de carrinho de compras
- Processamento de pedidos
- Gestão de estoque
- Dashboard administrativo

---

## 🎯 Primeira Etapa - API (Backend)

### 1 - Configuração Inicial e Arquitetura

- Crie um **fork** desse repositório.
- Instale todas as dependências do projeto usando o Composer.
- Configure o arquivo `.env` apropriado e gere uma nova chave para a aplicação Laravel.
- **Implemente uma arquitetura em camadas** utilizando:
  - **Service Layer** para lógica de negócio
  - **Repository Pattern** para abstração de acesso a dados
  - **DTOs (Data Transfer Objects)** para transferência de dados entre camadas
  - **Form Requests** para validação de requisições
  - **Resource Classes** para formatação de respostas JSON

### 2 - Modelos e Relacionamentos

Crie os seguintes modelos com seus relacionamentos:

**Product:**
- Campos: id, name, slug, description, price, cost_price, quantity, min_quantity, active, category_id, created_at, updated_at, deleted_at (soft delete)
- Relacionamentos: belongsTo Category, belongsToMany Tags, hasMany OrderItems, hasMany StockMovements

**Category:**
- Campos: id, name, slug, description, parent_id, active, created_at, updated_at
- Relacionamentos: belongsTo Category (parent), hasMany Category (children), hasMany Products

**Tag:**
- Campos: id, name, slug, created_at, updated_at
- Relacionamentos: belongsToMany Products

**Order:**
- Campos: id, user_id, status, total, subtotal, tax, shipping_cost, shipping_address, billing_address, notes, created_at, updated_at
- Relacionamentos: belongsTo User, hasMany OrderItems
- Status: pending, processing, shipped, delivered, cancelled

**OrderItem:**
- Campos: id, order_id, product_id, quantity, unit_price, total_price, created_at, updated_at
- Relacionamentos: belongsTo Order, belongsTo Product


**StockMovement:**
- Campos: id, product_id, type, quantity, reason, reference_type, reference_id, created_at, updated_at
- Relacionamentos: belongsTo Product
- Type: entrada, saida, ajuste, venda, devolucao

**Cart:**
- Campos: id, user_id, session_id, created_at, updated_at
- Relacionamentos: belongsTo User, hasMany CartItems

**CartItem:**
- Campos: id, cart_id, product_id, quantity, created_at, updated_at
- Relacionamentos: belongsTo Cart, belongsTo Product

### 3 - Migrações e Seeders

- Crie todas as migrações necessárias com índices apropriados para otimização.
- Implemente **soft deletes** na tabela products.
- Crie **seeders** e **factories** para popular todas as tabelas com dados realistas.
- Crie um seeder para usuários de teste (admin, cliente, etc).

### 4 - Rotas e Controladores

Implemente uma **API RESTful versionada** (v1) com os seguintes endpoints:

**Produtos:**
- `GET /api/v1/products` - Listar produtos (com filtros, busca, ordenação e paginação)
- `GET /api/v1/products/{id}` - Exibir produto específico
- `POST /api/v1/products` - Criar produto (apenas admin)
- `PUT /api/v1/products/{id}` - Atualizar produto (apenas admin)
- `DELETE /api/v1/products/{id}` - Excluir produto (soft delete, apenas admin)

**Categorias:**
- `GET /api/v1/categories` - Listar categorias (árvore hierárquica)
- `GET /api/v1/categories/{id}/products` - Listar produtos da categoria

**Carrinho:**
- `GET /api/v1/cart` - Obter carrinho do usuário
- `POST /api/v1/cart/items` - Adicionar item ao carrinho
- `PUT /api/v1/cart/items/{id}` - Atualizar item do carrinho
- `DELETE /api/v1/cart/items/{id}` - Remover item do carrinho
- `DELETE /api/v1/cart` - Limpar carrinho

**Pedidos:**
- `GET /api/v1/orders` - Listar pedidos do usuário
- `GET /api/v1/orders/{id}` - Exibir pedido específico
- `POST /api/v1/orders` - Criar pedido a partir do carrinho
- `PUT /api/v1/orders/{id}/status` - Atualizar status do pedido (apenas admin)

**Requisitos de validação:**
- Produto: name (obrigatório, único), price (obrigatório, > 0), cost_price (opcional, < price), quantity (obrigatório, inteiro, >= 0), min_quantity (opcional, inteiro, >= 0)
- Pedido: validação de estoque antes de criar o pedido

### 5 - Autenticação e Autorização

- Implemente autenticação usando **Laravel Sanctum** (API tokens).
- Crie **Políticas (Policies)** para autorização:
  - Apenas admins podem criar/editar/excluir produtos
  - Usuários só podem visualizar e gerenciar seus próprios pedidos
- Implemente **middleware** para rate limiting (100 requisições/minuto por IP).
- Crie **roles e permissions** usando Spatie Laravel Permission ou implementação própria.

### 6 - Recursos Avançados

**Cache:**
- Implemente cache para listagem de produtos (TTL de 1 hora)
- Cache para categorias (TTL de 24 horas)
- Use cache tags para invalidação inteligente

**Filas e Jobs:**
- Crie um job para processar pedidos em background
- Job para enviar email de confirmação de pedido
- Job para atualizar estoque após criação de pedido
- Configure queue connection (pode ser database ou redis)

**Eventos e Listeners:**
- Evento `ProductCreated` - disparar quando produto é criado
- Evento `OrderCreated` - disparar quando pedido é criado
- Evento `StockLow` - disparar quando estoque está abaixo do mínimo
- Listener para cada evento com ações apropriadas

**Scopes e Query Builders:**
- Scope `active()` para produtos ativos
- Scope `inStock()` para produtos com estoque disponível
- Scope `lowStock()` para produtos com estoque abaixo do mínimo
- Query builder customizado para filtros complexos de produtos

**Validações Customizadas:**
- Regra customizada para validar se produto tem estoque suficiente
- Regra customizada para validar se categoria pai existe
- Regra customizada para validar slug único

### 7 - Testes

Escreva testes abrangentes cobrindo:

**Testes Unitários:**
- Testes para Services (lógica de negócio)
- Testes para Repositories
- Testes para Models (relacionamentos, scopes, mutators/accessors)

**Testes de Integração:**
- Testes para endpoints da API
- Testes para autenticação e autorização
- Testes para validações

**Testes de Feature:**
- Fluxo completo de criação de pedido
- Fluxo de adicionar item ao carrinho
- Fluxo de atualização de estoque

**Cobertura mínima:** 80% do código

### 8 - Documentação e Performance

- Documente a API usando **Laravel Swagger/OpenAPI** (L5-Swagger ou similar).
- Implemente **query optimization** (eager loading, índices, select específico).
- Adicione **logging estruturado** para operações críticas.
- Implemente **API Resource** para formatação consistente de respostas.

### 9 - Estrutura de Resposta JSON

Todas as respostas devem seguir o padrão:

**Sucesso:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Playstation 5",
        "slug": "playstation-5",
        "description": "Console de última geração",
        "price": 3550.00,
        "cost_price": 2800.00,
        "quantity": 100,
        "min_quantity": 10,
        "active": true,
        "category": {
            "id": 1,
            "name": "Eletrônicos"
        },
        "tags": [
            {"id": 1, "name": "gaming"},
            {"id": 2, "name": "console"}
        ],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

**Listagem com Paginação:**
```json
{
    "success": true,
    "data": [...],
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

**Erro:**
```json
{
    "success": false,
    "message": "Mensagem de erro",
    "errors": {
        "field": ["Mensagem de validação"]
    }
}
```

---

## 🎨 Segunda Etapa - Frontend

Para consumir os dados da API, você poderá escolher uma das seguintes abordagens:

- **SPA em ReactJS** (pode usar Inertia.js ou React isolado com Vite)
- **SPA em Vue.js** (pode usar Inertia.js ou Vue isolado com Vite)
- **Blade Templates** (tradicional do Laravel)

### Funcionalidades Obrigatórias:

**Páginas Públicas:**
- Homepage com produtos em destaque
- Listagem de produtos com filtros (categoria, preço, busca)
- Página de detalhes do produto
- Página de login/registro

**Páginas Autenticadas:**
- Carrinho de compras
- Checkout (criação de pedido)
- Histórico de pedidos
- Detalhes do pedido
- Perfil do usuário

**Páginas Admin:**
- Dashboard com métricas (total de produtos, pedidos, receita)
- CRUD completo de produtos
- CRUD de categorias
- Listagem de pedidos com filtros
- Relatório de estoque baixo

### Requisitos Técnicos (SPA - React/Vue):

- Use **TypeScript** para type safety (recomendado)
- Implemente **gerenciamento de estado** adequado à tecnologia escolhida
- Use uma biblioteca de formulários apropriada
- Implemente **tratamento de erros** global
- Adicione **loading states** e **skeleton screens**
- Implemente **infinite scroll** ou paginação na listagem
- Use bibliotecas apropriadas para cache e sincronização de dados (React Query, SWR, Vue Query, etc)
- Implemente **toast notifications** para feedback ao usuário
- Adicione **validação de formulários** no frontend
- Implemente **roteamento protegido** (rotas privadas e admin)

### Requisitos Técnicos (Blade):

- Use **Livewire** ou **Alpine.js** para interatividade (recomendado)
- Implemente **componentes Blade reutilizáveis**
- Use **Blade Components** e **View Composers** quando apropriado
- Implemente **validação de formulários** no frontend e backend
- Adicione **feedback visual** para ações do usuário
- Use **AJAX** ou **Fetch API** para requisições assíncronas quando necessário

### Design (Todas as Abordagens):

- Use um framework CSS moderno (Tailwind CSS, Bootstrap, Material-UI, ou similar)
- Design responsivo (mobile-first)
- Acessibilidade (WCAG 2.1 nível AA)
- Animações suaves para transições

---

## 📊 O que vamos avaliar:

### Backend:
- **Arquitetura e Design Patterns:** Uso adequado de Service Layer, Repository Pattern, DTOs
- **Qualidade de Código:** SOLID, DRY, Clean Code
- **Performance:** Otimização de queries, uso de cache, índices
- **Testes:** Cobertura, qualidade e organização dos testes
- **Segurança:** Validações, sanitização, autorização adequada
- **Documentação:** API documentada, código comentado quando necessário

### Frontend:
- **Arquitetura:** Organização de componentes/views, separação de concerns, reutilização de código
- **Performance:** Code splitting, lazy loading, otimização de renderizações (quando aplicável)
- **UX/UI:** Interface intuitiva, responsiva e acessível
- **Type Safety:** Uso adequado de TypeScript (para SPAs) ou validação robusta (para Blade)
- **Estado:** Gerenciamento de estado eficiente (SPAs) ou uso adequado de sessão/componentes (Blade)

### Geral:
- **Versionamento:** Commits descritivos, branch strategy
- **Manutenibilidade:** Código legível, bem estruturado
- **Boas Práticas:** PSR-12, ESLint, Prettier configurados

---

## 🚀 Para finalizar...

### Informações importantes:

- Crie um **fork** e desenvolva a sua solução nele.
- Crie um arquivo **PROJECT.md** detalhado com:
  - Como executar o projeto (setup, dependências, comandos)
  - Decisões arquiteturais tomadas
  - Bibliotecas utilizadas e justificativas
  - Estrutura de pastas e organização do código
  - Como executar os testes
  - Como acessar a documentação da API
- Após concluir todas as tarefas, faça um **pull request**.
- Envie um E-mail para **alexander@aldesenvolvimento.com.br** com o link do seu **pull request** e com o assunto **"Challenge Pleno Accepted"**.

### Dicas:

- Não precisa implementar pagamento real (pode simular)
- Foque em código limpo e bem testado
- Documente decisões importantes
- Use Git de forma profissional (commits atômicos, mensagens claras)

Caso tenha alguma dúvida, entre em contato através do E-mail **alexander@aldesenvolvimento.com.br**.

---

### **Boa sorte!**

Este desafio foi criado para avaliar suas habilidades como desenvolvedor pleno. Mostre todo o seu conhecimento e experiência!

#### **VAMBORA PRA CIMA! 🚀**

