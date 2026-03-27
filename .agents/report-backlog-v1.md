# Report Backlog v1

## BL-002 - Ajuste manual de estoque com trilha de movimentacoes

- Ajuste manual de `quantity` na edicao de produto passou a usar `StockService` em vez de update direto.
- O motivo do ajuste passou a ser exigido quando a quantidade muda.
- O historico de movimentacoes do produto continua exibindo a movimentacao gerada pelo ajuste.
- Cobertura adicionada para validacao de motivo e criacao da movimentacao no update de produto.

Arquivos principais:

- `app/Http/Controllers/Admin/AdminProductController.php`
- `app/Http/Controllers/Api/V1/ProductController.php`
- `app/Http/Requests/Api/V1/UpdateProductRequest.php`
- `app/Services/StockService.php`
- `resources/js/Pages/Admin/Products/Edit.tsx`
- `tests/Feature/Api/V1/ProductApiTest.php`
- `tests/Feature/Web/Admin/AdminContractsTest.php`

## BL-003 - Recuperacao de senha

- Fluxo web de solicitacao de reset e redefinicao de senha implementado com broker nativo do Laravel.
- Login passou a apontar para um fluxo funcional de recuperacao.
- Paginas Inertia criadas para solicitar link e redefinir senha.
- Testes cobrem envio do link, token valido, token invalido e token expirado.

Arquivos principais:

- `app/Http/Controllers/AuthPageController.php`
- `app/Http/Requests/Web/ForgotPasswordRequest.php`
- `app/Http/Requests/Web/ResetPasswordRequest.php`
- `resources/js/Pages/Auth/ForgotPassword.tsx`
- `resources/js/Pages/Auth/ResetPassword.tsx`
- `resources/js/Pages/Auth/Login.tsx`
- `routes/web.php`
- `tests/Feature/Auth/PasswordResetFlowTest.php`

## BL-004 - Verificacao de e-mail

- Cadastro passou a disparar o fluxo de verificacao nativo via evento `Registered`.
- Usuario `User` ficou formalmente verificavel pelo contrato do Laravel.
- Login de cliente nao verificado redireciona para a tela de verificacao.
- Checkout e criacao de pedido sensiveis ficaram protegidos com middleware `verified`.
- Testes cobrem cadastro, envio de verificacao, link assinado e bloqueio de checkout para conta nao verificada.

Arquivos principais:

- `app/Models/User.php`
- `app/Http/Controllers/AuthPageController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/Pages/Auth/VerifyEmail.tsx`
- `routes/web.php`
- `tests/Feature/Auth/EmailVerificationFlowTest.php`

## BL-005 - Cancelamento pelo cliente

- O fluxo ja estava implementado no worktree e foi validado.
- Policy de cancelamento, rotas web/API, `can_cancel` no resource e tela do cliente estavam coerentes.
- Estoque continua sendo devolvido corretamente quando o cancelamento ocorre apos processamento.

Arquivos validados:

- `app/Policies/OrderPolicy.php`
- `app/Http/Controllers/OrderPageController.php`
- `app/Http/Controllers/Api/V1/OrderController.php`
- `app/Http/Resources/Api/V1/OrderResource.php`
- `resources/js/Pages/Customer/Orders/Show.tsx`
- `tests/Feature/Api/V1/OrderApiTest.php`
- `tests/Feature/PolicyTest.php`
- `tests/Feature/Web/Customer/OrderContractsTest.php`

## BL-006 - CRUD de tags

- CRUD de tags no admin/API consolidado.
- Listagem de tags exposta na API com `products_count`.
- Gestao admin de tags disponivel na interface administrativa.
- Vinculacao com produtos continua funcionando via fluxo ja existente de produto.
- Testes cobrem listagem publica, CRUD admin e exposicao da contagem de produtos.

Arquivos principais:

- `app/Http/Controllers/Admin/AdminTagController.php`
- `app/Http/Controllers/Api/V1/TagController.php`
- `app/Http/Requests/Api/V1/StoreTagRequest.php`
- `app/Http/Requests/Api/V1/UpdateTagRequest.php`
- `app/Http/Resources/Api/V1/TagResource.php`
- `app/Services/TagService.php`
- `resources/js/Pages/Admin/Tags/Index.tsx`
- `routes/api.php`
- `routes/web.php`
- `tests/Feature/Api/V1/TagApiTest.php`
- `tests/Feature/Web/Admin/AdminContractsTest.php`

## BL-007 - Processamento assincrono de pedidos

- Pipeline assincrono de pedido consolidado:
  - `OrderCreated` continua sendo disparado na criacao do pedido.
  - `ProcessOrderListener` enfileira `ProcessOrderPipeline`.
  - `ProcessOrderPipeline` efetiva o processamento e despacha o e-mail de confirmacao.
- `OrderService::createFromCart` cria o pedido em `processing` e deixa a baixa de estoque para o pipeline.
- `OrderService::processPendingOrder` centraliza a efetivacao do pedido e o cancelamento por indisponibilidade.
- Cancelamento passou a devolver estoque apenas quando a baixa efetivamente ocorreu.
- Testes focados de pipeline, criacao de pedido e estoque foram executados.

Arquivos principais:

- `app/Services/OrderService.php`
- `app/Listeners/ProcessOrderListener.php`
- `app/Jobs/ProcessOrderPipeline.php`
- `tests/Unit/Services/OrderServiceTest.php`
- `tests/Feature/OrderProcessingAsyncTest.php`
- `tests/Feature/OrderProcessingPipelineTest.php`

## BL-008 - Carrinho anonimo por sessao e merge no login

- Carrinho web foi aberto para contexto anonimo por `session_id`.
- `CartService` e `CartRepository` suportam contexto por usuario ou sessao e merge de carrinho.
- Login e cadastro passam a reconciliar o carrinho anonimo com o carrinho do usuario autenticado.
- Header passou a refletir `cart_count` tambem para visitantes.
- CTAs de produto deixaram de exigir login previo para adicionar ao carrinho.
- Teste de merge de carrinho anonimo no login validado.

Arquivos principais:

- `app/Services/CartService.php`
- `app/Repositories/Contracts/CartRepositoryInterface.php`
- `app/Repositories/CartRepository.php`
- `app/Http/Controllers/CartPageController.php`
- `app/Http/Controllers/AuthPageController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/Components/Public/ProductCard.tsx`
- `routes/web.php`
- `tests/Feature/Web/CartGuestMergeTest.php`
- `tests/Unit/Services/CartServiceTest.php`

## BL-009 - Regra de frete e totalizacao comercial

- `CartTotalsService` centraliza subtotal, imposto e frete.
- Regra comercial de frete aplicada:
  - subtotal `>= 200` -> frete gratis
  - subtotal `> 0` e `< 200` -> frete `19.90`
- Carrinho, checkout e criacao de pedido usam a mesma regra.
- Testes de unit e feature validam totalizacao e regra de frete.

Arquivos principais:

- `app/Services/CartTotalsService.php`
- `app/Http/Controllers/CartPageController.php`
- `app/Http/Controllers/CheckoutPageController.php`
- `app/Services/OrderService.php`
- `app/Http/Resources/Api/V1/CartResource.php`
- `tests/Unit/CartTotalsServiceTest.php`
- `tests/Feature/OrderFlowTest.php`
- `tests/Feature/Web/CartCheckoutTotalsTest.php`

## Validacoes executadas

- `php artisan test --compact tests/Unit/Services/StockServiceTest.php`
- `php artisan test --compact tests/Feature/Api/V1/ProductApiTest.php`
- `php artisan test --compact tests/Feature/Auth/PasswordResetFlowTest.php`
- `php artisan test --compact tests/Feature/Auth/EmailVerificationFlowTest.php`
- `php artisan test --compact tests/Feature/Api/V1/OrderApiTest.php`
- `php artisan test --compact tests/Feature/AuthorizationTest.php`
- `php artisan test --compact tests/Feature/PolicyTest.php`
- `php artisan test --compact tests/Feature/Web/Customer/OrderContractsTest.php`
- `php artisan test --compact tests/Feature/Api/V1/TagApiTest.php`
- `php artisan test --compact tests/Feature/OrderProcessingPipelineTest.php`
- `php artisan test --compact tests/Feature/StockFlowTest.php`
- `php artisan test --compact tests/Feature/Web/CartGuestMergeTest.php`
- `php artisan test --compact tests/Feature/Api/V1/CartApiTest.php`
- `php artisan test --compact tests/Unit/CartTotalsServiceTest.php`
- `php artisan test --compact tests/Feature/OrderFlowTest.php`
- `php artisan test --compact tests/Feature/Web/CartCheckoutTotalsTest.php`
- `vendor/bin/pint --dirty --format agent`

## Observacoes finais

- O worktree ja tinha varias implementacoes parciais desses itens; o trabalho aqui foi completar, alinhar e validar o conjunto.
- O item `BL-005` estava funcionalmente pronto no codigo e foi tratado como consolidacao/validacao.
