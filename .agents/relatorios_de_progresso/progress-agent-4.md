# Progress — Agente 4: Frontend Público

## [00:00] — Início das implementações

### Etapa 1 — TypeScript Types (`resources/js/types/public.ts`)
- Criado `public.ts` com types específicos do frontend público
- Re-exporta types compartilhados de `@/types/shared`
- Define `CartItem`, `Cart`, `CheckoutAddress`, `CheckoutFormData`

### Etapa 2 — Layout Público (`resources/js/Layouts/PublicLayout.tsx`)
- Header com logo, navegação, busca, CartIcon e menu de usuário
- Footer com links e copyright
- Hamburger menu mobile responsivo
- Toaster para notificações globais

### Etapa 3 — Componentes Públicos (`resources/js/Components/Public/`)
- `HeroBanner.tsx` — Banner hero com gradiente e CTA
- `Footer.tsx` — Footer da loja
- `SearchInput.tsx` — Input de busca com debounce
- `CartIcon.tsx` — Ícone de carrinho com badge
- `ProductCard.tsx` — Card de produto com hover effect
- `ProductGrid.tsx` — Grid responsivo de produtos
- `CategoryFilter.tsx` — Filtro por categorias
- `PriceFilter.tsx` — Filtro de faixa de preço
- `CartItem.tsx` — Linha de item no carrinho  
- `QuantitySelector.tsx` — Input +/- para quantidade
- `OrderStatusTimeline.tsx` — Timeline visual de status do pedido
- `Pagination.tsx` — Componente de paginação

### Etapa 4 — Homepage (`resources/js/Pages/Home.tsx`)
- HeroBanner com gradiente vibrante
- Seção de categorias em destaque
- Grid de produtos em destaque (8 produtos mock)
- Seção "Por que comprar conosco" com 3 cards

### Etapa 5 — Listagem de Produtos (`resources/js/Pages/Products/Index.tsx`)
- Sidebar com CategoryFilter + PriceFilter em desktop
- SearchInput no topo
- ProductGrid com skeleton screens
- Paginação
- Estado vazio

### Etapa 6 — Detalhe do Produto (`resources/js/Pages/Products/Show.tsx`)
- Imagem placeholder grande
- Nome, preço, descrição, tags, categoria
- QuantitySelector + botão "Adicionar ao Carrinho"
- Indicador de estoque

### Etapa 7 — Login e Registro (`resources/js/Pages/Auth/`)
- `Login.tsx` — Email + senha, validação, link de registro
- `Register.tsx` — Nome + email + senha + confirmação, validação

### Etapa 8 — Carrinho (`resources/js/Pages/Customer/Cart.tsx`)
- Lista de CartItems com atualização e remoção
- Resumo de preços
- Botões de ação

### Etapa 9 — Checkout (`resources/js/Pages/Customer/Checkout.tsx`)
- Formulário de endereço de entrega e cobrança
- Notas opcionais
- Resumo do pedido

### Etapa 10 — Histórico de Pedidos
- `Orders/Index.tsx` — Lista com status e totais
- `Orders/Show.tsx` — Detalhes + OrderStatusTimeline

### Etapa 11 — Perfil (`resources/js/Pages/Customer/Profile.tsx`)
- Edição de nome e email
- Alteração de senha

### Etapa 12 — Revisões e Correções

**Revisão 1 (correções menores):**
- `Footer.tsx` extraído de `PublicLayout.tsx` como componente independente
- `<Link>` substituindo `<a>` em `ProductCard.tsx`
- Fonte Inter adicionada via Google Fonts em `app.css`
- Validação Zod adicionada em `Login.tsx` e `Register.tsx`
- Diretório `Pages/Produtos/` removido (vazio/duplicado)

**Revisão 2 (correções críticas e importantes):**
- `Checkout.tsx` reescrito como wizard de 3 etapas real (Entrega → Cobrança → Revisão) com barra de progresso, `react-hook-form + zodResolver`, cópia automática dos campos de entrega para cobrança via `setValue`, e `router.post` no submit final
- `Orders/Index.tsx` — `window.location.href` substituído por `router.get()` com `preserveState`
- `Profile.tsx` migrado para `react-hook-form + zodResolver` com schemas separados para perfil e senha
- `PublicLayout.tsx` — link "Categorias" adicionado à navegação desktop e mobile
- `SearchInput.tsx` — debounce interno de 300ms implementado via `useEffect` + `setTimeout`
- Relatório `fase-4-frontend-publico.md` — seção 9.8 corrigida para descrever envio por campos separados
