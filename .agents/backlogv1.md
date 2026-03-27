# Backlog v1

Backlog inicial gerado a partir da analise do fluxo atual do sistema e das regras do challenge.
Os itens abaixo misturam gaps de produto, operacao e aderencia tecnica ao desafio.

## Escala de prioridade

- P0: quebra operacao, consistencia de dados ou rastreabilidade
- P1: alto impacto em conversao, autosservico, atendimento ou aderencia ao challenge
- P2: melhora importante de experiencia, comercial ou manutencao
- P3: acabamento e entregaveis finais do challenge

## Itens priorizados

### BL-001 - Repor estoque ao cancelar pedido

- Prioridade: P0
- Problema: o pedido pode ir para `cancelled`, mas o fluxo atual nao devolve as unidades ao estoque nem registra uma movimentacao de retorno.
- Valor de negocio: evita estoque incorreto, ruptura artificial e erro de decisao operacional.
- Criterios de aceite:
  - ao cancelar um pedido, cada item devolve a quantidade ao produto
  - o sistema registra movimentacao de estoque do tipo `devolucao` ou equivalente com referencia ao pedido
  - o cache de produtos e a visao de estoque baixo sao atualizados apos o cancelamento
  - existe teste cobrindo cancelamento com devolucao de estoque
- Evidencias:
  - `app/Services/OrderService.php`
  - `app/Services/StockService.php`
  - `resources/js/Pages/Admin/Orders/Show.tsx`

### BL-002 - Fazer ajuste manual de estoque passar pela trilha de movimentacoes

- Prioridade: P0
- Problema: o admin altera `quantity` na edicao do produto, mas isso nao gera historico de movimentacao e quebra auditabilidade.
- Valor de negocio: garante trilha confiavel para operacao, auditoria e analise de perdas/ajustes.
- Criterios de aceite:
  - ajuste manual de estoque usa `StockService` em vez de update direto de quantidade
  - o admin informa motivo do ajuste
  - a tela de detalhes do produto mostra a movimentacao criada pelo ajuste manual
  - existe teste cobrindo ajuste manual com historico
- Evidencias:
  - `app/Http/Controllers/Admin/AdminProductController.php`
  - `app/Services/ProductService.php`
  - `resources/js/Pages/Admin/Products/Show.tsx`

### BL-003 - Implementar recuperacao de senha

- Prioridade: P1
- Problema: a tela de login sugere recuperacao de senha, mas hoje nao existe fluxo funcional para solicitar reset e redefinir senha.
- Valor de negocio: reduz atrito de retorno de clientes e evita perda de compra por bloqueio de acesso.
- Criterios de aceite:
  - usuario pode solicitar link/token de redefinicao de senha
  - usuario pode cadastrar nova senha com validacao adequada
  - a UI de login aponta para um fluxo funcional
  - existe teste do fluxo feliz e do token invalido/expirado
- Evidencias:
  - `resources/js/Pages/Auth/Login.tsx`
  - `routes/web.php`
  - `database/migrations/0001_01_01_000000_create_users_table.php`

### BL-004 - Implementar verificacao de e-mail no cadastro

- Prioridade: P1
- Problema: contas novas sao criadas e autenticadas sem confirmacao do e-mail.
- Valor de negocio: melhora qualidade da base, reduz fraudes simples e aumenta confiabilidade da comunicacao de pedidos.
- Criterios de aceite:
  - novo usuario recebe fluxo de verificacao de e-mail
  - a aplicacao sinaliza claramente quando a conta ainda nao foi verificada
  - operacoes sensiveis definidas pelo produto podem exigir conta verificada
  - existe teste cobrindo cadastro e verificacao
- Evidencias:
  - `app/Services/AuthService.php`
  - `app/Models/User.php`
  - `routes/web.php`

### BL-005 - Permitir cancelamento pelo proprio cliente em status elegiveis

- Prioridade: P1
- Problema: o cliente apenas consulta seus pedidos; cancelamento depende de acao administrativa.
- Valor de negocio: reduz carga do suporte e melhora autosservico no pos-compra.
- Criterios de aceite:
  - cliente pode cancelar pedido em status configurados como cancelaveis, por exemplo `pending` e `processing`
  - cancelamento feito pelo cliente respeita as mesmas regras de devolucao de estoque
  - timeline e historico exibem o novo status corretamente
  - existe teste cobrindo autorizacao e restricao por status
- Evidencias:
  - `routes/web.php`
  - `app/Policies/OrderPolicy.php`
  - `resources/js/Pages/Customer/Orders/Show.tsx`

### BL-006 - Criar CRUD de tags para merchandising

- Prioridade: P1
- Problema: tags existem no modelo e no vinculo com produto, mas nao existe fluxo de gestao dedicado no admin/API.
- Valor de negocio: melhora taxonomia, campanhas, curadoria e flexibilidade comercial.
- Criterios de aceite:
  - admin pode criar, editar, listar e excluir tags
  - API expone endpoints coerentes para tags, caso o padrao do projeto permita
  - produto continua podendo associar multiplas tags gerenciadas pelo admin
  - existe teste para CRUD e vinculacao com produto
- Evidencias:
  - `app/Services/TagService.php`
  - `app/Http/Controllers/Admin/AdminProductController.php`
  - `routes/api.php`

### BL-007 - Completar processamento assincrono de pedidos conforme challenge

- Prioridade: P1
- Problema: apenas o envio de e-mail esta em fila; criacao do pedido e baixa de estoque continuam sincronos.
- Valor de negocio: melhora aderencia ao challenge e prepara o fluxo para cenarios de maior carga.
- Criterios de aceite:
  - existe job ou orquestracao assincrona para processamento do pedido conforme estrategia definida
  - baixa de estoque e demais efeitos colaterais seguem fluxo consistente e observavel
  - o usuario recebe feedback claro sobre pedido em processamento quando aplicavel
  - existe teste cobrindo despacho e processamento do job/listener
- Evidencias:
  - `README.md`
  - `app/Services/OrderService.php`
  - `app/Listeners/ProcessOrderListener.php`

### BL-008 - Habilitar carrinho anonimo por sessao e merge no login

- Prioridade: P2
- Problema: o usuario precisa estar autenticado para comecar a montar o carrinho, mesmo existindo `session_id` no modelo.
- Valor de negocio: aumenta conversao no topo do funil e reduz abandono antes do cadastro/login.
- Criterios de aceite:
  - visitante pode adicionar e editar itens em carrinho por sessao
  - ao autenticar, o carrinho anonimo e conciliado com o carrinho do usuario
  - regras de estoque continuam validadas no merge
  - existe teste cobrindo sessao anonima e merge pos-login
- Evidencias:
  - `app/Models/Cart.php`
  - `app/Services/CartService.php`
  - `resources/js/Components/Public/ProductCard.tsx`

### BL-009 - Evoluir logica de frete e totalizacao comercial

- Prioridade: P2
- Problema: frete esta fixo em zero e impostos estao simplificados, o que distancia o checkout de um fluxo comercial real.
- Valor de negocio: melhora previsibilidade do pedido, margem e transparencia para o cliente.
- Criterios de aceite:
  - checkout suporta pelo menos uma regra explicita de frete ou simulacao coerente
  - totalizacao exibe claramente subtotal, impostos e frete
  - carrinho e checkout usam a mesma regra de calculo
  - existe teste cobrindo a totalizacao
- Evidencias:
  - `app/Http/Controllers/CartPageController.php`
  - `app/Http/Controllers/CheckoutPageController.php`
  - `app/Services/OrderService.php`

### BL-010 - Criar `PROJECT.md` para entrega final do challenge

- Prioridade: P3
- Problema: o challenge pede um arquivo de handoff detalhando setup, arquitetura, testes e documentacao da API.
- Valor de negocio: melhora avaliacao final, onboarding e transferencia de contexto.
- Criterios de aceite:
  - arquivo `PROJECT.md` existe na raiz do projeto
  - documento explica setup, decisoes arquiteturais, estrutura de pastas, testes e swagger
  - instrucoes batem com o estado atual do repositorio
- Evidencias:
  - `.agents/README-challenge.md`

## Itens explicitamente fora de escopo deste backlog

- Integracao de pagamento real nao entra aqui, porque o challenge permite simulacao de pagamento.
