# Progress — Agente 2: Testes

## Objetivo
Suíte de testes completa com ≥80% de cobertura. PHPUnit (não Pest).

---

## [Etapa 1] — Testes Unitários de Models

### Status: ✅ Concluído — 91 testes, 125 assertions

- `tests/Unit/Models/ProductTest.php` — Relacionamentos, fillable, casts, scopes (active, inStock, lowStock), soft delete, slug auto
- `tests/Unit/Models/CategoryTest.php` — Relacionamentos (parent, children, products), fillable, casts, slug auto
- `tests/Unit/Models/TagTest.php` — Relacionamento products (belongsToMany), fillable, slug auto
- `tests/Unit/Models/OrderTest.php` — Relacionamentos (user, items), fillable, casts, statuses
- `tests/Unit/Models/OrderItemTest.php` — Relacionamentos (order, product), fillable, casts
- `tests/Unit/Models/CartTest.php` — Relacionamentos (user, items), fillable
- `tests/Unit/Models/CartItemTest.php` — Relacionamentos (cart, product), fillable, casts
- `tests/Unit/Models/StockMovementTest.php` — Relacionamento product, fillable, casts, types

---

## [Etapa 2] — Testes Unitários de Repositories

### Status: ✅ Concluído

- `tests/Unit/Repositories/ProductRepositoryTest.php` — paginate (6 filtros), findById, findBySlug, create, update, delete, lowStock, syncTags, slugExists
- `tests/Unit/Repositories/CategoryRepositoryTest.php` — tree, all, findById, findBySlug, create, update, delete, slugExists
- `tests/Unit/Repositories/OrderRepositoryTest.php` — paginateForUser, paginate (filtros), findById, findByIdForUser, create com items, updateStatus

---

## [Etapa 3] — Testes Unitários de Services

### Status: ✅ Concluído (usa Mockery para mock de repositórios)

- `tests/Unit/Services/ProductServiceTest.php` — eventos, slug único, sincronização de tags, delete
- `tests/Unit/Services/CartServiceTest.php` — validação de estoque, produto inativo, qty existente, clear
- `tests/Unit/Services/OrderServiceTest.php` — cálculos (subtotal/tax/total), eventos, jobs, validações
- `tests/Unit/Services/StockServiceTest.php` — todos os tipos de movimento, evento StockLow, decreaseStock, increaseStock

---

## [Etapa 4] — Testes de Integração (API endpoints)

### Status: ✅ Concluído — 68 testes, 186 assertions

- `tests/Feature/Api/V1/AuthTest.php` — register (201, 422 duplicado, 422 faltando campos), login, logout, me
- `tests/Feature/Api/V1/ProductApiTest.php` — list/show público, CRUD admin (201/200/200), gates (401/403)
- `tests/Feature/Api/V1/CategoryApiTest.php` — list tree, show, products por categoria
- `tests/Feature/Api/V1/CartApiTest.php` — auth (401), add/update/remove/clear, validação estoque (422)
- `tests/Feature/Api/V1/OrderApiTest.php` — isolamento customer/admin, criar pedido, updateStatus admin-only

---

## [Etapa 5] — Testes de Feature (fluxos completos)

### Status: ✅ Concluído — 15 testes, 50 assertions

- `tests/Feature/CartFlowTest.php` — fluxo completo: add → update → remove → clear; isolamento por usuário; acumulação de quantidade
- `tests/Feature/OrderFlowTest.php` — fluxo login → carrinho → pedido → confirmação; cálculo de totais; admin atualiza status
- `tests/Feature/StockFlowTest.php` — estoque diminui após pedido; StockMovement criado (reference_type/reference_id); evento StockLow

---

## [Etapa 6] — Testes de Validação e Autorização

### Status: ✅ Concluído — 37 testes

- `tests/Feature/ValidationTest.php` — campos obrigatórios, UniqueSlug (inclusive slug de produto soft-deletado), SufficientStock, custo < preço, endereço de pedido
- `tests/Feature/AuthorizationTest.php` — guest bloqueado, customer sem acesso admin, admin acessa tudo, isolamento de recursos, rate limiting (exercita config real via pré-preenchimento com chave `md5('api'.$key)`)

---

## [Etapa 7] — Verificação Final

### Status: ✅ Concluído

**Suite completa:** 329 testes passando, 648 assertions
**Cobertura de código:** 90.8% (supera o mínimo de 80%)

---

## [Pós-Review] — Revisões Aplicadas (Code Review #1 e #2)

### Status: ✅ Concluído

**Code Review #1 (7 achados — todos corrigidos):**
- P1/P2: `UpdateStockAfterOrder` e `SendOrderConfirmationEmail` agora testados com comportamento real
- P3: `ProductPolicy` — testes ajustados
- P4: `StockFlowTest` — cobertura de edge case de evento StockLow
- P5: `CartFlowTest` — acumulação de quantidade
- P6: `CategoryApiTest` — filtro de inativas
- P7: `StockServiceTest` — mock verificado

**Code Review #2 (5 achados — todos corrigidos):**
- P1: Testes de rate limiting reescritos para exercitar a config real de produção (sem `RateLimiter::for()` override). Chave correta: `md5('api'.$rawKey)` — descoberta lendo `ThrottleRequests::$shouldHashKeys = true`
- P2: `PolicyTest` cobre código de produção real (policies são usadas indiretamente via API; relatório atualizado)
- P3: Código de produção modificado durante fase de testes documentado (rules `UniqueSlug` e `SufficientStock` tiveram pequenos ajustes)
- P4: Documentação atualizada (`progress-agent-2.md`, `relatorios/fase-2-testes.md`)
- P5: Novo teste `test_unique_slug_rule_rejects_slug_of_soft_deleted_product` adicionado em `ValidationTest`

**Suite final:** 329 testes, 648 assertions, 0 falhas
**Pint:** Todos os arquivos formatados (`vendor/bin/pint --dirty`)
