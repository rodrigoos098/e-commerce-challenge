# 🔵 Agente 1 — Backend Core (API + Lógica de Negócio)

## Contexto

Você é o agente responsável por toda a **camada backend** do sistema de e-commerce em **Laravel 12**. O projeto já tem um scaffold base. Seu trabalho é implementar a arquitetura em camadas (Service Layer, Repository Pattern, DTOs), API RESTful versionada, autenticação, autorização, cache, filas, eventos e seeders.

> **Leia o plano completo:** [implementation_plan.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/AGENTS.md) — siga TODAS as convenções Laravel Boost.
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

Antes de iniciar, instale/consulte estas skills:
- [api-design-principles](https://skills.sh/wshobson/agents/api-design-principles)
- [architecture-patterns](https://skills.sh/wshobson/agents/architecture-patterns)
- [error-handling-patterns](https://skills.sh/wshobson/agents/error-handling-patterns)
- [sql-optimization-patterns](https://skills.sh/wshobson/agents/sql-optimization-patterns)
- [security-requirement-extraction](https://skills.sh/wshobson/agents/security-requirement-extraction)
- [executing-plans](https://skills.sh/obra/superpowers/executing-plans)

---

## Regras de Trabalho

1. **Documente tudo em tempo real.** Sempre que iniciar ou concluir uma sub-tarefa, escreva um log resumido do que fez no arquivo `progress-agent-1.md` (crie-o na raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - O que foi feito
   - Arquivos criados/modificados
   - Decisões tomadas e justificativas
   ```

2. **Marque o checkbox no [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)** ao concluir cada item da seção "Agente 1: Backend Core". Use `[x]` para concluído. O arquivo está em:
   [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)

3. **Siga as convenções do AGENTS.md:**
   - Use `php artisan make:*` para criar arquivos (model, controller, request, etc.)
   - Passe `--no-interaction` a todos os comandos artisan
   - Rode `vendor/bin/pint --dirty --format agent` após modificar arquivos PHP
   - Use return types, constructor promotion, PHPDoc blocks

4. **NÃO faça commits.** Quando julgar que já fez conteúdo suficiente para um commit, **pause a implementação** e solicite ao humano que revise e faça o commit manualmente. Sugira a mensagem de commit.

5. **Database é MySQL.** Configure corretamente as migrations para MySQL.

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — ~~Models e Migrations~~ (PULAR)

> [!NOTE]
> **Os Models e Migrations já foram criados na Fase 0** com `$fillable`, `$casts`, relacionamentos e scopes completos. **Pule esta etapa** e vá direto para a Etapa 2 (Repositories).
>
> Apenas verifique que os Models existem e estão corretos:
> ```bash
> php artisan migrate:status
> ```

### Etapa 2 — Repository Contracts + Implementations
1. Criar a pasta `app/Repositories/Contracts/`
2. Criar 5 interfaces: `ProductRepositoryInterface`, `CategoryRepositoryInterface`, `OrderRepositoryInterface`, `CartRepositoryInterface`, `StockMovementRepositoryInterface`
3. Criar 5 implementações Eloquent em `app/Repositories/`
4. Registrar bindings no `AppServiceProvider`
5. **Marcar:** `[x] Repository Contracts + Implementations`

### Etapa 3 — DTOs
1. Criar pasta `app/DTOs/`
2. Criar DTOs: `ProductDTO`, `OrderDTO`, `CartItemDTO`, `StockMovementDTO`
3. Cada DTO deve ser uma classe readonly com construtor e método estático `fromRequest()`
4. **Marcar:** `[x] DTOs`

### Etapa 4 — Services
1. Criar pasta `app/Services/`
2. Implementar: `ProductService`, `CategoryService`, `CartService`, `OrderService`, `StockService`
3. Cada service recebe o repository via constructor injection
4. Implementar lógica de negócio: validação de estoque, cálculo de totais, slug auto-generation, cache invalidation
5. **Marcar:** `[x] Services`

### Etapa 5 — Form Requests
1. Criar com `php artisan make:request`:
   - `StoreProductRequest`, `UpdateProductRequest`, `StoreOrderRequest`, `AddCartItemRequest`, `UpdateCartItemRequest`, `UpdateOrderStatusRequest`
2. Implementar regras de validação conforme desafio (name unique, price > 0, cost_price < price, etc.)
3. **Marcar:** `[x] Form Requests`

### Etapa 6 — API Resources
1. Criar com `php artisan make:resource`:
   - `ProductResource`, `ProductCollection`, `CategoryResource`, `OrderResource`, `CartResource`
2. Seguir formato JSON do desafio (success, data, meta, links)
3. **Marcar:** `[x] API Resources`

### Etapa 7 — Controllers API v1
1. Criar pasta `app/Http/Controllers/Api/V1/`
2. Criar controllers: `AuthController`, `ProductController`, `CategoryController`, `CartController`, `OrderController`
3. Cada controller usa Service e Form Request (nunca lógica direta)
4. Respostas sempre no formato padronizado `{ success, data, meta?, links? }`
5. **Marcar:** `[x] Controllers API v1`

### Etapa 8 — Rotas API
1. Criar `routes/api.php` com prefixo `v1`
2. Rotas públicas: products (list/show), categories
3. Rotas autenticadas (middleware `auth:sanctum`): cart, orders, auth
4. Rotas admin (middleware `auth:sanctum` + `role:admin`): product CRUD, order status
5. Rate limiting: 100 req/min
6. **Marcar:** `[x] Rotas API`

### Etapa 9 — Autenticação e Autorização
1. Verificar que Sanctum já está configurado no [bootstrap/app.php](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/bootstrap/app.php) (feito na Fase 0)
2. Criar Policies: `ProductPolicy`, `OrderPolicy`
3. Configurar roles/permissions com Spatie: roles `admin` e `customer`
4. **Marcar:** `[x] Policies`

### Etapa 10 — Events, Listeners e Jobs
1. Criar eventos: `ProductCreated`, `OrderCreated`, `StockLow`
2. Criar listeners: `LogProductCreated`, `ProcessOrderListener`, `NotifyStockLow`
3. Criar jobs: `ProcessOrderJob`, `SendOrderConfirmationEmail`, `UpdateStockAfterOrder`
4. Registrar event-listener mappings no `EventServiceProvider` ou via discovery
5. **Marcar:** `[x] Events & Listeners` e `[x] Jobs`

### Etapa 11 — Custom Rules e Scopes
1. Criar rules em `app/Rules/`: `SufficientStock`, `ValidParentCategory`, `UniqueSlug`
2. Verificar scopes no Model Product (já feito na Etapa 1)
3. **Marcar:** `[x] Scopes e Custom Rules`

### Etapa 12 — Cache
1. Implementar cache no `ProductService` (TTL 1h) e `CategoryService` (TTL 24h)
2. Usar cache tags para invalidação inteligente
3. Invalidar ao criar/editar/excluir
4. **Marcar:** `[x] Cache`

### Etapa 13 — Seeders e Factories
1. Completar factories com dados realistas (Faker)
2. Criar seeders: `RoleAndPermissionSeeder`, `UserSeeder`, `ProductSeeder`, `CategorySeeder`, `TagSeeder`, `OrderSeeder`
3. Criar `DatabaseSeeder` que chama todos na ordem correta
4. **Rodar:** `php artisan migrate:fresh --seed`
5. **Marcar:** `[x] Seeders & Factories`

> [!CAUTION]
> **Ownership exclusivo:** Você é o único agente que deve modificar `app/Providers/AppServiceProvider.php` e `routes/api.php`. Nenhum outro agente deve tocar esses arquivos.

### Etapa 14 — Verificação
1. Rodar `vendor/bin/pint --dirty --format agent`
2. Verificar que todas as rotas existem: `php artisan route:list --path=api`
3. Testar manualmente 2-3 endpoints básicos (GET products, POST login)
4. **Solicitar commit ao humano:** Pause e sugira: `feat: complete backend core implementation`

---

## Formato de Resposta JSON (Obrigatório)

Todas as respostas da API devem seguir este padrão:

```json
// Sucesso
{ "success": true, "data": { ... } }

// Listagem paginada
{ "success": true, "data": [...], "meta": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7 }, "links": { "first": "...", "last": "...", "prev": null, "next": "..." } }

// Erro
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```
