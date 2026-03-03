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

### Status: ✅ Concluído — 35 testes, 83 assertions

- `tests/Feature/ValidationTest.php` — campos obrigatórios, UniqueSlug, SufficientStock, custo < preço, endereço de pedido
- `tests/Feature/AuthorizationTest.php` — guest bloqueado, customer sem acesso admin, admin acessa tudo, isolamento de recursos

---

## [Etapa 7] — Verificação Final

### Status: ✅ Concluído

**Suite completa:** 299 testes passando, 583 assertions
**Cobertura de código:** 86.6% (supera o mínimo de 80%)
**Pint:** Todos os arquivos formatados (`vendor/bin/pint --dirty`)
