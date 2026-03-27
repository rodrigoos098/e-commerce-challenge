# Backlog v2

## #01 Garantir criacao de pedido confiavel com reserva/baixa de estoque consistente

- Tipo: bug
- Prioridade: critico
- Descricao: hoje `app/Services/OrderService.php` cria o pedido, limpa o carrinho e so depois delega a baixa/validacao final de estoque para `app/Jobs/ProcessOrderPipeline.php`. Isso abre janela para inconsistencias caso a fila atrase, falhe ou o job seja reprocessado. O impacto e pedido confirmado sem estoque efetivamente reservado, alem de carrinho limpo antes da garantia operacional.
- Criterios de aceitacao:
  - o fluxo web `/customer/orders` e o endpoint `POST /api/v1/orders` nao podem deixar pedido valido sem estoque reservado/baixado de forma deterministica
  - o carrinho so pode ser limpo apos o ponto de consistencia definido
  - reenvio duplo da submissao nao pode gerar pedido duplicado
  - falha operacional deve manter estado recuperavel e mensagem clara ao usuario
  - testes devem cobrir sucesso, estoque insuficiente, retry e falha assincrona
- Arquivos relevantes:
  - `app/Services/OrderService.php`
  - `app/Jobs/ProcessOrderPipeline.php`
  - `app/Listeners/ProcessOrderListener.php`
  - `app/Http/Controllers/OrderPageController.php`
  - `app/Http/Controllers/Api/V1/OrderController.php`
  - `app/Models/Order.php`
  - `tests/Feature/OrderFlowTest.php`
  - `tests/Feature/OrderProcessingPipelineTest.php`
  - `tests/Feature/OrderProcessingAsyncTest.php`
  - `tests/Feature/Api/V1/OrderApiTest.php`
- Dependencias:
  - alinhar em conjunto com `#02`

## #02 Normalizar a maquina de estados do pedido entre backend, admin, API e frontend

- Tipo: bug
- Prioridade: alto
- Descricao: `app/Services/OrderService.php` cria pedido com status `processing` e o pipeline muda para `pending`, enquanto `app/Models/Order.php` define transicoes partindo de `pending -> processing`. O admin em `resources/js/Pages/Admin/Orders/Show.tsx` e a timeline publica assumem outra semantica. O impacto e operacional: status inconsistente, transicoes confusas e risco de automacoes incorretas.
- Criterios de aceitacao:
  - status inicial do pedido deve ser unico e coerente com o fluxo definido
  - `Order::ALLOWED_TRANSITIONS`, `OrderService`, telas admin e cliente e recursos da API devem refletir a mesma cadeia
  - cancelamento deve respeitar a nova maquina de estados
  - testes de transicao devem cobrir todos os estados validos e invalidos
- Arquivos relevantes:
  - `app/Models/Order.php`
  - `app/Services/OrderService.php`
  - `app/Http/Controllers/Admin/AdminOrderController.php`
  - `app/Http/Controllers/Api/V1/OrderController.php`
  - `resources/js/Pages/Admin/Orders/Show.tsx`
  - `resources/js/Components/Public/OrderStatusTimeline.tsx`
  - `resources/js/Pages/Customer/Orders/Show.tsx`
  - `tests/Unit/Models/OrderTest.php`
  - `tests/Feature/OrderFlowTest.php`
- Dependencias:
  - recomendavel executar junto com `#01`

## #03 Completar a validacao server-side do checkout web com Form Request dedicado

- Tipo: bug
- Prioridade: alto
- Descricao: `app/Http/Controllers/OrderPageController.php` valida apenas os campos de entrega e monta billing com fallback implicito. Isso permite payloads incompletos quando `same_billing = false`, alem de manter validacao inline fora do padrao arquitetural do projeto. O impacto e criacao de pedidos com dados de cobranca inconsistentes.
- Criterios de aceitacao:
  - a criacao web de pedido deve usar Form Request proprio
  - shipping, billing, `same_billing` e `notes` devem ser validados de forma explicita
  - quando `same_billing = false`, todos os campos de cobranca obrigatorios devem ser exigidos
  - erros devem voltar por campo para o Inertia
  - testes devem cobrir cenarios validos e invalidos
- Arquivos relevantes:
  - `app/Http/Controllers/OrderPageController.php`
  - `app/Http/Requests/Web/StoreOrderRequest.php`
  - `app/DTOs/OrderDTO.php`
  - `tests/Feature/Web/Customer/OrderContractsTest.php`
  - `tests/Feature/ValidationTest.php`
- Dependencias:
  - nenhuma

## #04 Validar o checkout por etapa antes de avancar no frontend

- Tipo: bug
- Prioridade: alto
- Descricao: em `resources/js/Pages/Customer/Checkout.tsx`, `goNext()` apenas incrementa a etapa sem validar os campos da etapa atual. O usuario so percebe falhas no submit final. O impacto e UX ruim e maior taxa de erro/abandono.
- Criterios de aceitacao:
  - a etapa 1 so pode avancar com shipping valido
  - a etapa 2 so pode avancar com billing valido ou `same_billing` ativo
  - mensagens devem aparecer antes do avanco
  - a navegacao entre etapas deve preservar dados preenchidos
  - o submit final nao pode ser o primeiro ponto de feedback de erro do formulario
- Arquivos relevantes:
  - `resources/js/Pages/Customer/Checkout.tsx`
  - `resources/js/types/public.ts`
- Dependencias:
  - nenhuma

## #05 Garantir sincronizacao correta do billing quando "mesmo endereco" estiver ativo

- Tipo: bug
- Prioridade: medio
- Descricao: em `resources/js/Pages/Customer/Checkout.tsx`, `handleSameBillingChange()` copia os valores so no momento do toggle. Se o usuario editar o endereco de entrega depois, o billing fica desatualizado. O impacto e envio de endereco divergente sem percepcao do usuario.
- Criterios de aceitacao:
  - com `same_billing = true`, qualquer alteracao em shipping deve refletir em billing ate o toggle ser desligado
  - ao desligar o toggle, os campos de billing devem permanecer editaveis
  - ao religar, o billing deve ser sobrescrito pelo shipping atual
  - testes manuais ou automatizados devem cobrir toggles repetidos
- Arquivos relevantes:
  - `resources/js/Pages/Customer/Checkout.tsx`
- Dependencias:
  - nenhuma

## #06 Preservar estado completo de filtros no catalogo publico

- Tipo: bug
- Prioridade: medio
- Descricao: em `resources/js/Pages/Products/Index.tsx`, `handleCategoryChange()` e `handlePageChange()` descartam `price_min` e `price_max` da navegacao. Isso quebra o fluxo de descoberta do catalogo e gera comportamento inconsistente na paginacao.
- Criterios de aceitacao:
  - busca, categoria, faixa de preco e paginacao devem sempre preservar entre si
  - limpar filtros deve resetar tudo
  - a URL deve refletir o estado real
  - o backend em `ProductPageController` deve continuar aceitando os filtros corretamente
- Arquivos relevantes:
  - `resources/js/Pages/Products/Index.tsx`
  - `app/Http/Controllers/ProductPageController.php`
  - `resources/js/types/public.ts`
- Dependencias:
  - nenhuma

## #07 Bloquear exposicao publica de produtos vinculados a categorias inativas

- Tipo: bug
- Prioridade: alto
- Descricao: a desativacao em `app/Repositories/CategoryRepository.php` marca `active = false`, mas o catalogo publico continua filtrando basicamente por `products.active` via `app/Repositories/ProductQueryBuilder.php` e `app/Http/Controllers/ProductPageController.php`. O impacto e exposicao indevida de itens que deveriam sair da vitrine.
- Criterios de aceitacao:
  - listagem publica, detalhe de produto, relacionados e endpoints publicos de categoria nao devem expor produto cuja categoria ou ancestral esteja inativo
  - categorias desativadas devem remover a vitrine imediatamente apos invalidacao de cache
  - testes devem cobrir categoria raiz e subcategoria
- Arquivos relevantes:
  - `app/Repositories/ProductQueryBuilder.php`
  - `app/Http/Controllers/ProductPageController.php`
  - `app/Services/CategoryService.php`
  - `app/Http/Controllers/Api/V1/ProductController.php`
  - `app/Http/Controllers/Api/V1/CategoryController.php`
  - `tests/Feature/Api/V1/ProductApiTest.php`
  - `tests/Feature/Api/V1/CategoryApiTest.php`
- Dependencias:
  - nenhuma

## #08 Corrigir inconsistencias de UX publica em busca, menu mobile e fallback de imagens

- Tipo: bug
- Prioridade: medio
- Descricao: `resources/js/Layouts/PublicLayout.tsx` nao permite submeter busca vazia para voltar a `/products`; o menu mobile autenticado nao exibe atalho admin; `CartItem.tsx`, `Checkout.tsx` e `Customer/Orders/Show.tsx` nao usam o mesmo fallback de imagem de `ProductCard.tsx`. O impacto e UX inconsistente e sensacao de produto inacabado.
- Criterios de aceitacao:
  - busca vazia no header deve navegar para `/products` sem filtro
  - usuario admin deve ver atalho para `/admin/dashboard` tambem no menu mobile
  - todas as telas publicas devem usar a mesma estrategia de `image_url ?? /storage/products/{id}.webp` com fallback visual consistente
- Arquivos relevantes:
  - `resources/js/Layouts/PublicLayout.tsx`
  - `resources/js/Components/Public/CartItem.tsx`
  - `resources/js/Pages/Customer/Checkout.tsx`
  - `resources/js/Pages/Customer/Orders/Show.tsx`
  - `resources/js/Components/Public/ProductCard.tsx`
- Dependencias:
  - nenhuma

## #09 Implementar gestao de enderecos do cliente

- Tipo: feature
- Prioridade: alto
- Descricao: hoje o sistema so captura enderecos no checkout e os persiste como snapshot no pedido. Nao existe address book, endereco padrao ou reaproveitamento em `/customer/profile`. Para operacao minimamente realista e melhoria de conversao, o cliente precisa gerenciar multiplos enderecos.
- Criterios de aceitacao:
  - cliente deve conseguir listar, criar, editar, excluir e definir enderecos padrao de entrega e cobranca
  - checkout deve permitir selecionar endereco salvo ou preencher um novo
  - o pedido deve continuar salvando snapshot do endereco escolhido
  - regras de autorizacao devem impedir acesso cruzado entre usuarios
  - UI deve ficar acessivel na area autenticada
- Arquivos relevantes:
  - `app/Models/Address.php`
  - `database/migrations/*_create_addresses_table.php`
  - `app/Models/User.php`
  - `app/Http/Controllers/ProfilePageController.php`
  - `app/Http/Controllers/CheckoutPageController.php`
  - `app/Http/Controllers/OrderPageController.php`
  - `app/Http/Requests/Web/StoreAddressRequest.php`
  - `app/Http/Requests/Web/UpdateAddressRequest.php`
  - `routes/web.php`
  - `resources/js/Pages/Customer/Profile.tsx`
  - `resources/js/Pages/Customer/Addresses/*.tsx`
  - `resources/js/types/public.ts`
- Dependencias:
  - nenhuma

## #10 Implementar calculo mockado de frete no carrinho e checkout

- Tipo: feature
- Prioridade: alto
- Descricao: o frete hoje e uma regra fixa em `app/Services/CartTotalsService.php`, sem qualquer variacao por CEP, faixa ou opcao de entrega. Como o frete real nao sera integrado, o sistema precisa ao menos de um calculo mockado consistente para UX e logica comercial.
- Criterios de aceitacao:
  - carrinho e checkout devem exibir frete mockado calculado por regra explicita
  - o calculo deve considerar ao menos CEP ou faixa de CEP, ou opcao mock de entrega
  - a mesma regra deve ser usada em web, API e criacao do pedido
  - testes devem validar a regra aplicada
  - a regra nao pode ficar duplicada no frontend
- Arquivos relevantes:
  - `app/Services/CartTotalsService.php`
  - `app/Services/CartPricingService.php`
  - `app/Http/Controllers/CheckoutPageController.php`
  - `app/Services/OrderService.php`
  - `app/Http/Resources/Api/V1/CartResource.php`
  - `resources/js/Pages/Customer/Cart.tsx`
  - `resources/js/Pages/Customer/Checkout.tsx`
  - `tests/Unit/CartTotalsServiceTest.php`
  - `tests/Feature/Web/CartCheckoutTotalsTest.php`
- Dependencias:
  - pode ser feita antes ou depois de `#09`

## #11 Adicionar etapa mock de pagamento antes da finalizacao do pedido

- Tipo: feature
- Prioridade: alto
- Descricao: o checkout atualmente confirma pedido diretamente no passo final sem qualquer etapa de pagamento, mesmo que mock. Como o projeto nao tera gateway real, a melhor aproximacao e inserir um simular pagamento com estado explicito para fechar o fluxo de compra.
- Criterios de aceitacao:
  - checkout deve exibir uma etapa ou acao clara de simular pagamento
  - o usuario nao deve concluir o pedido sem passar por essa acao
  - o pedido deve registrar metadados minimos do mock, como `payment_status`, `payment_method` mock e `paid_at` ou equivalente
  - admin e cliente devem visualizar que o pagamento foi simulado, sem misturar esse estado ao status logistico
- Arquivos relevantes:
  - `database/migrations/*_add_payment_fields_to_orders_table.php`
  - `app/Models/Order.php`
  - `app/Services/OrderService.php`
  - `app/Http/Controllers/OrderPageController.php`
  - `app/Http/Controllers/Api/V1/OrderController.php`
  - `resources/js/Pages/Customer/Checkout.tsx`
  - `resources/js/Pages/Customer/Orders/Show.tsx`
  - `resources/js/Pages/Admin/Orders/Show.tsx`
  - `app/Http/Resources/Api/V1/OrderResource.php`
- Dependencias:
  - `#01`
  - `#02`

## #12 Expandir notificacoes transacionais de pedido

- Tipo: feature
- Prioridade: alto
- Descricao: so existe confirmacao de pedido via `SendOrderConfirmationEmail`. Faltam notificacoes para cancelamento, envio, entrega e eventual pagamento mockado. O impacto e pouca visibilidade para cliente e operacao.
- Criterios de aceitacao:
  - deve haver notificacao para pedido criado, pagamento mock confirmado, pedido cancelado, enviado e entregue
  - disparos nao podem duplicar em reprocessamentos
  - templates devem refletir os dados reais do pedido
  - jobs devem ser enfileirados
  - alteracoes de status via admin devem disparar os eventos corretos
- Arquivos relevantes:
  - `app/Jobs/SendOrderConfirmationEmail.php`
  - `app/Mail/OrderConfirmationMail.php`
  - `app/Services/OrderService.php`
  - `app/Http/Controllers/Admin/AdminOrderController.php`
  - `app/Events/*`
  - `app/Listeners/*`
  - `resources/views/emails/orders/*.blade.php`
  - `tests/Feature/OrderFlowTest.php`
- Dependencias:
  - `#02`
  - `#11`
  - `#15`

## #13 Corrigir referencia polimorfica de `StockMovement`

- Tipo: bug
- Prioridade: alto
- Descricao: `app/Models/StockMovement.php` define `reference(): MorphTo`, mas `app/Services/StockService.php` grava `reference_type = 'order'`, valor que nao corresponde a uma classe Eloquent sem `morphMap`. O impacto e perda de rastreabilidade correta do estoque e relacao quebrada para auditoria.
- Criterios de aceitacao:
  - `reference_type/reference_id` devem resolver corretamente para o modelo referenciado
  - deve existir `Relation::morphMap()` ou remocao explicita do uso de `morphTo`
  - dados novos e existentes devem permanecer consultaveis
  - testes devem validar a associacao de movimentos de venda e devolucao ao pedido
- Arquivos relevantes:
  - `app/Models/StockMovement.php`
  - `app/Services/StockService.php`
  - `app/Providers/AppServiceProvider.php` ou provider dedicado
  - `database/migrations/*`
  - `tests/Feature/Api/V1/OrderApiTest.php`
  - `tests/Feature/StockFlowTest.php`
  - `tests/Unit/Models/StockMovementTest.php`
- Dependencias:
  - nenhuma

## #14 Remover duplicidade das rotas web do carrinho

- Tipo: debito tecnico
- Prioridade: medio
- Descricao: `routes/web.php` registra `/cart` e `/cart/items/*` duas vezes, com os mesmos nomes e handlers. Isso aumenta ruido, dificulta manutencao e pode mascarar alteracoes futuras de middleware.
- Criterios de aceitacao:
  - cada rota do carrinho deve existir uma unica vez
  - nomes das rotas devem permanecer estaveis
  - o comportamento web nao deve mudar
  - testes ou validacao de rotas devem garantir ausencia de duplicidade
- Arquivos relevantes:
  - `routes/web.php`
- Dependencias:
  - nenhuma

## #15 Alinhar contrato de resposta da API na criacao de pedidos

- Tipo: bug
- Prioridade: medio
- Descricao: `app/Http/Controllers/Api/V1/OrderController.php` chama `createdResponse()` com mensagem adicional, mas `app/Traits/ApiResponseTrait.php` aceita apenas `data`. Mesmo quando nao explode em runtime, o contrato esta inconsistente e induz erro de manutencao.
- Criterios de aceitacao:
  - `POST /api/v1/orders` deve responder de forma consistente com o trait adotado
  - se houver mensagem, o trait precisa suporta-la explicitamente
  - testes da API devem validar o formato final
  - nao pode existir chamada incompativel com o helper de resposta
- Arquivos relevantes:
  - `app/Http/Controllers/Api/V1/OrderController.php`
  - `app/Traits/ApiResponseTrait.php`
  - `tests/Feature/Api/V1/OrderApiTest.php`
- Dependencias:
  - nenhuma

## #16 Atualizar documentacao tecnica para refletir o comportamento real do pedido e da fila

- Tipo: debito tecnico
- Prioridade: baixo
- Descricao: `README.md` afirma que a baixa de estoque acontece na mesma transacao e que o fluxo nao depende de job, o que diverge da implementacao atual em `OrderService` + `ProcessOrderPipeline`. Isso gera onboarding errado e documentacao enganosa.
- Criterios de aceitacao:
  - `README.md` deve refletir o fluxo final decidido para criacao e processamento de pedidos
  - setup de fila e e-mail deve estar documentado corretamente
  - nao deve haver contradicoes entre docs e implementacao
- Arquivos relevantes:
  - `README.md`
  - `config/queue.php`
  - `app/Services/OrderService.php`
  - `app/Jobs/ProcessOrderPipeline.php`
- Dependencias:
  - `#01`
  - `#02`
  - `#10`
  - `#11`
  - `#12`

## #17 Refatorar ou remover `CartPricingService` morto e substituir teste placeholder por cobertura real

- Tipo: debito tecnico
- Prioridade: medio
- Descricao: `app/Services/CartPricingService.php` esta vazio e `tests/Unit/Services/CartPricingServiceTest.php` e um placeholder sem valor. Como a camada de pricing e frete vai evoluir, esse codigo precisa virar implementacao real ou ser removido.
- Criterios de aceitacao:
  - `CartPricingService` deve ser integrado ao fluxo de calculo ou removido
  - o teste placeholder deve ser substituido por testes reais
  - namespace e cobertura do teste devem estar corretos
  - nao pode restar classe vazia sem uso
- Arquivos relevantes:
  - `app/Services/CartPricingService.php`
  - `tests/Unit/Services/CartPricingServiceTest.php`
  - `app/Services/CartTotalsService.php`
- Dependencias:
  - `#10`

## #18 Permitir limpeza explicita de campos nullable em atualizacao de produto

- Tipo: bug
- Prioridade: medio
- Descricao: `app/DTOs/ProductDTO.php` usa `array_filter(... !== null)` em `toArray()`, impedindo que campos como `cost_price` sejam limpos de proposito. Hoje a UI admin em `AdminProductController` ate tenta enviar `null`, mas o DTO descarta.
- Criterios de aceitacao:
  - update de produto deve permitir setar `cost_price = null` de forma explicita
  - API e admin web devem respeitar a limpeza do campo
  - testes devem cobrir update com valor, sem valor e limpando valor existente
- Arquivos relevantes:
  - `app/DTOs/ProductDTO.php`
  - `app/Http/Controllers/Admin/AdminProductController.php`
  - `app/Http/Controllers/Api/V1/ProductController.php`
  - `tests/Unit/Services/ProductServiceTest.php`
  - `tests/Feature/Api/V1/ProductApiTest.php`
- Dependencias:
  - nenhuma

## #19 Corrigir template de e-mail de confirmacao para o payload real do pedido

- Tipo: bug
- Prioridade: medio
- Descricao: `resources/views/emails/orders/confirmation.blade.php` usa `shipping_address['number']`, campo que nao e validado nem persistido pelo checkout ou API. O template tambem esta em ingles, enquanto o resto da UX e majoritariamente PT-BR. O impacto e e-mail inconsistente ou parcialmente vazio.
- Criterios de aceitacao:
  - template deve usar apenas campos realmente presentes em `shipping_address` e `billing_address`
  - conteudo deve ser coerente com o idioma e padrao do projeto
  - envio de confirmacao deve renderizar corretamente para pedidos web e API
  - testes ou preview devem validar o markup sem campos faltantes
- Arquivos relevantes:
  - `resources/views/emails/orders/confirmation.blade.php`
  - `app/Mail/OrderConfirmationMail.php`
  - `app/Jobs/SendOrderConfirmationEmail.php`
  - `app/Http/Requests/Api/V1/StoreOrderRequest.php`
  - `app/Http/Controllers/OrderPageController.php`
- Dependencias:
  - nenhuma

## #20 Migrar validacoes do admin web para Form Requests e autorizacao consistente

- Tipo: melhoria
- Prioridade: medio
- Descricao: controllers admin como `AdminProductController` e `AdminOrderController` ainda usam validacao inline, enquanto a API ja adota Form Requests. Isso quebra consistencia arquitetural e dificulta reuso e mensagens customizadas.
- Criterios de aceitacao:
  - operacoes admin web de produtos, categorias, tags e pedidos devem usar Form Requests dedicados
  - `authorize()` deve centralizar permissoes
  - controllers devem ficar focados em orquestracao
  - mensagens de erro devem continuar chegando corretamente ao Inertia
- Arquivos relevantes:
  - `app/Http/Controllers/Admin/AdminProductController.php`
  - `app/Http/Controllers/Admin/AdminCategoryController.php`
  - `app/Http/Controllers/Admin/AdminTagController.php`
  - `app/Http/Controllers/Admin/AdminOrderController.php`
  - `app/Http/Requests/Web/Admin/*.php`
- Dependencias:
  - `#18` para o caso de produto

## #21 Padronizar frontend compartilhado: tipos, formatadores, confirmacoes e rotas

- Tipo: debito tecnico
- Prioridade: medio
- Descricao: o frontend mistura helpers locais de moeda, confirmacoes nativas (`window.confirm`), tipos divergentes e paths hardcoded. Exemplos: `resources/js/Pages/Customer/Cart.tsx`, `resources/js/Pages/Customer/Orders/Show.tsx`, `resources/js/Pages/Admin/Tags/Index.tsx`, `resources/js/types/public.ts`. O impacto e manutencao cara e inconsistencias de UX.
- Criterios de aceitacao:
  - formatadores de preco e data devem ser centralizados
  - tipos TS devem refletir os payloads reais usados nas paginas
  - confirmacoes destrutivas devem seguir um unico padrao visual
  - rotas hardcoded recorrentes devem ser encapsuladas em constantes ou helpers onde fizer sentido
  - nao deve haver duplicacao evitavel de utilitarios
- Arquivos relevantes:
  - `resources/js/utils/format.ts`
  - `resources/js/types/public.ts`
  - `resources/js/types/shared.ts`
  - `resources/js/Pages/Customer/Cart.tsx`
  - `resources/js/Pages/Customer/Orders/Show.tsx`
  - `resources/js/Pages/Admin/Tags/Index.tsx`
  - `resources/js/Layouts/PublicLayout.tsx`
- Dependencias:
  - recomendavel apos `#06`, `#08`, `#11`
