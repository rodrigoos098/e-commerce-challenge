# 🟢 Agente 2 — Testes (Unitários, Integração, Feature)

## Contexto

Você é o agente responsável por toda a **suíte de testes** do sistema de e-commerce. O projeto usa **PHPUnit** (NÃO Pest). Seu objetivo é atingir **≥80% de cobertura de código** com testes bem organizados e abrangentes.

> **Leia o plano completo:** [implementation_plan.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/AGENTS.md)
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

- [test-driven-development](https://skills.sh/obra/superpowers/test-driven-development)
- [webapp-testing](https://skills.sh/anthropics/skills/webapp-testing)
- [e2e-testing-patterns](https://skills.sh/wshobson/agents/e2e-testing-patterns)
- [verification-before-completion](https://skills.sh/obra/superpowers/verification-before-completion)
- [systematic-debugging](https://skills.sh/obra/superpowers/systematic-debugging)

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-agent-2.md` (raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - Testes criados
   - Total de assertions
   - Problemas encontrados e soluções
   ```

2. **Marque o checkbox no [task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)** ao concluir cada item da seção "Agente 2: Testes".
   [C:\Users\rodrigo.santos\.gemini\antigravity\brain\e3e17065-da6c-472d-b9c6-74d37305cf22\task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)

3. **Use PHPUnit, NÃO Pest.** Se encontrar qualquer teste em Pest, converta para PHPUnit.

4. **Crie testes com:** `php artisan make:test --phpunit {Name}`
   - Para testes unitários: `php artisan make:test --phpunit --unit {Name}`

5. **Use factories** para criar dados de teste. Verifique se factories existem e use states customizados quando possível.

6. **Rode os testes após cada grupo:** `php artisan test --compact tests/Unit/Models/` ou `--filter=testName`

7. **Rode Pint** após modificar PHP: `vendor/bin/pint --dirty --format agent`

---

## Dependências

> [!CAUTION]
> Você tem **dependência parcial** do Agente 1 (Backend Core). **Estratégia de lançamento em 2 fases:**
>
> **Lançamento 1 (imediato, junto com os outros agentes):**
> - Etapa 1: Testes unitários de Models ✅
> - Etapa 2: Testes unitários de Repositories ✅ (se Repositories já existirem da Fase 0)
>
> **Lançamento 2 (após Agente 1 concluir):**
> - Etapa 3: Testes unitários de Services
> - Etapa 4: Testes de integração API
> - Etapa 5: Testes de feature (fluxos completos)
> - Etapa 6: Testes de validação e autorização
> - Etapa 7: Verificação final
>
> **Entre as esperas, revise e refine os testes já escritos.**
>
> **Ownership exclusivo:** Apenas você toca o diretório `tests/`. Nenhum outro agente deve criar ou modificar testes.

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — Testes Unitários de Models (`tests/Unit/Models/`)

Para cada model, teste:
- **Relacionamentos:** Que `$model->relationship` retorna a classe correta
- **Fillable/Guarded:** Que os campos são mass-assignable
- **Casts:** Que os casts estão configurados
- **Scopes** (Product): `active()`, `inStock()`, `lowStock()`
- **Soft Deletes** (Product): que `delete()` não remove do banco

| Arquivo de Teste | Model | Assertions esperadas |
|-------------------|-------|---------------------|
| `tests/Unit/Models/ProductTest.php` | Product | Relacionamentos (category, tags, orderItems, stockMovements), scopes, soft delete, slug |
| `tests/Unit/Models/CategoryTest.php` | Category | parent, children, products, slug |
| `tests/Unit/Models/TagTest.php` | Tag | products (belongsToMany) |
| `tests/Unit/Models/OrderTest.php` | Order | user, items, status values |
| `tests/Unit/Models/OrderItemTest.php` | OrderItem | order, product, total_price |
| `tests/Unit/Models/CartTest.php` | Cart | user, items |
| `tests/Unit/Models/CartItemTest.php` | CartItem | cart, product |
| `tests/Unit/Models/StockMovementTest.php` | StockMovement | product, types |

**Rodar:** `php artisan test --compact tests/Unit/Models/`
**Marcar:** `[x] Testes unitários de Models`

### Etapa 2 — Testes Unitários de Repositories (`tests/Unit/Repositories/`)

Teste as operações CRUD de cada repository:
- Criação, leitura, atualização, exclusão
- Filtros e paginação (ProductRepository)
- Árvore hierárquica (CategoryRepository)

| Arquivo de Teste | Repository |
|-------------------|-----------|
| `tests/Unit/Repositories/ProductRepositoryTest.php` | ProductRepository |
| `tests/Unit/Repositories/CategoryRepositoryTest.php` | CategoryRepository |
| `tests/Unit/Repositories/OrderRepositoryTest.php` | OrderRepository |

**Rodar:** `php artisan test --compact tests/Unit/Repositories/`
**Marcar:** `[x] Testes unitários de Repositories`

### Etapa 3 — Testes Unitários de Services (`tests/Unit/Services/`)

⚠️ **Aguardar Agente 1 completar Services**

Teste a lógica de negócio mockando os repositories:
- Criação de produto com slug auto
- Adição de item ao carrinho com validação de estoque
- Criação de pedido do carrinho com cálculo de totais
- Movimentação de estoque e disparo de evento StockLow

| Arquivo de Teste | Service |
|-------------------|--------|
| `tests/Unit/Services/ProductServiceTest.php` | ProductService |
| `tests/Unit/Services/CartServiceTest.php` | CartService |
| `tests/Unit/Services/OrderServiceTest.php` | OrderService |
| `tests/Unit/Services/StockServiceTest.php` | StockService |

**Rodar:** `php artisan test --compact tests/Unit/Services/`
**Marcar:** `[x] Testes unitários de Services`

### Etapa 4 — Testes de Integração API (`tests/Feature/Api/V1/`)

⚠️ **Aguardar Agente 1 completar Controllers e Rotas**

Teste cada endpoint da API com requests HTTP reais:
- Status codes corretos (200, 201, 401, 403, 404, 422)
- Formato JSON padronizado (`success`, `data`, `meta`, `links`)
- Autenticação (com e sem token Sanctum)
- Autorização (admin vs customer)

| Arquivo de Teste | Endpoints |
|-------------------|-----------|
| `tests/Feature/Api/V1/AuthTest.php` | register, login, logout, user |
| `tests/Feature/Api/V1/ProductApiTest.php` | CRUD completo, filtros, paginação, permissões admin |
| `tests/Feature/Api/V1/CategoryApiTest.php` | Listagem, produtos por categoria |
| `tests/Feature/Api/V1/CartApiTest.php` | Add/update/remove items, clear cart |
| `tests/Feature/Api/V1/OrderApiTest.php` | Criar pedido, listar, show, update status |

Exemplo de teste:
```php
public function test_guest_cannot_create_product(): void
{
    $response = $this->postJson('/api/v1/products', [...]);
    $response->assertStatus(401);
}

public function test_admin_can_create_product(): void
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/products', [...]);
    
    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'data' => ['id', 'name', 'slug']]);
}
```

**Rodar:** `php artisan test --compact tests/Feature/Api/V1/`
**Marcar:** `[x] Testes de integração (API endpoints)`

### Etapa 5 — Testes de Feature (Fluxos Completos) (`tests/Feature/`)

⚠️ **Aguardar Agente 1 completar tudo**

Teste fluxos de ponta a ponta:

| Arquivo de Teste | Fluxo |
|-------------------|-------|
| `tests/Feature/OrderFlowTest.php` | Login → adicionar ao carrinho → checkout → pedido criado → estoque atualizado → stock movement |
| `tests/Feature/CartFlowTest.php` | Adicionar itens → atualizar quantidade → remover → limpar |
| `tests/Feature/StockFlowTest.php` | Criar pedido → estoque diminui → stock movement criado → evento StockLow se baixo |

**Rodar:** `php artisan test --compact tests/Feature/`
**Marcar:** `[x] Testes de feature (fluxos completos)`

### Etapa 6 — Testes de Validação e Autorização

| Arquivo de Teste | O que cobre |
|-------------------|-------------|
| `tests/Feature/ValidationTest.php` | Campos obrigatórios, regras customizadas (estoque, slug, parent_id) |
| `tests/Feature/AuthorizationTest.php` | Policies (admin vs customer), rate limiting |

**Marcar:** `[x] Testes de validação e autorização`

### Etapa 7 — Verificação Final

1. **Rodar toda a suíte:** `php artisan test --compact`
2. **Verificar cobertura:** `php artisan test --coverage --min=80`
3. **Verificar se há testes falhando:** Corrigir todos
4. **Rodar Pint:** `vendor/bin/pint --dirty --format agent`
5. **Solicitar commit ao humano:** Pause e sugira: `test: complete test suite with 80%+ coverage`
