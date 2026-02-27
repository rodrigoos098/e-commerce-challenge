# 🟠 Agente 4 — Frontend Público (Loja + Autenticação)

## Contexto

Você é o agente responsável por todas as **páginas públicas e de cliente** do e-commerce: Homepage, catálogo de produtos, detalhe do produto, login/registro, carrinho, checkout, histórico de pedidos e perfil. A tecnologia é **React + TypeScript + Inertia.js + Tailwind CSS v4**.

> **Leia o plano completo:** [implementation_plan.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/AGENTS.md)
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

- [frontend-design](https://skills.sh/anthropics/claude-code/frontend-design)
- [ui-ux-pro-max](https://skills.sh/nextlevelbuilder/ui-ux-pro-max-skill/ui-ux-pro-max)
- [tailwind-design-system](https://skills.sh/wshobson/agents/tailwind-design-system)
- [responsive-design](https://skills.sh/wshobson/agents/responsive-design)
- [interface-design](https://skills.sh/dammyjay93/interface-design/interface-design)
- [executing-plans](https://skills.sh/obra/superpowers/executing-plans)

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-agent-4.md` (raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - Páginas/componentes criados
   - Decisões de design
   - Estado de loading e edge cases tratados
   ```

2. **Marque o checkbox no [task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)** ao concluir cada item da seção "Agente 4: Frontend Público".
   [C:\Users\rodrigo.santos\.gemini\antigravity\brain\e3e17065-da6c-472d-b9c6-74d37305cf22\task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)

3. **Use TypeScript** em todos os arquivos (`.tsx`).

4. **Use Tailwind CSS v4** para estilos.

5. **Design WOW, premium e responsivo (mobile-first).** A loja deve impressionar ao primeiro olhar:
   - Gradientes sutis, sombras, glassmorphism
   - Micro-animações (hover, transições de página)
   - Tipografia moderna (Google Fonts: Inter ou similar)
   - Paleta de cores harmônica e vibrante
   - Skeleton screens durante loading

6. **Acessibilidade WCAG 2.1 AA:** alt texts, aria labels, foco visível, contraste adequado.

7. **Crie dados mockados inicialmente.** Na integração (Fase 2), serão substituídos por dados reais via Inertia.

---

## Estrutura de Pastas

```
resources/js/
├── Components/
│   └── Public/
│       ├── ProductCard.tsx
│       ├── ProductGrid.tsx
│       ├── CategoryFilter.tsx
│       ├── PriceFilter.tsx
│       ├── SearchInput.tsx
│       ├── CartIcon.tsx
│       ├── CartItem.tsx
│       ├── OrderStatusTimeline.tsx
│       ├── QuantitySelector.tsx
│       ├── Pagination.tsx
│       ├── ... (importar SkeletonLoader de ../Shared/)
│       ├── HeroBanner.tsx
│       └── Footer.tsx
├── Layouts/
│   └── PublicLayout.tsx
├── Pages/
│   ├── Home.tsx
│   ├── Products/
│   │   ├── Index.tsx
│   │   └── Show.tsx
│   ├── Auth/
│   │   ├── Login.tsx
│   │   └── Register.tsx
│   └── Customer/
│       ├── Cart.tsx
│       ├── Checkout.tsx
│       ├── Orders/
│       │   ├── Index.tsx
│       │   └── Show.tsx
│       └── Profile.tsx
└── types/
    └── public.ts  (apenas types específicos do público, importar shared de @/types/shared)
```

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — TypeScript Types (`resources/js/types/public.ts`)

> [!IMPORTANT]
> **Importe os types compartilhados de `@/types/shared`** (criado na Fase 0). Defina aqui apenas types específicos do frontend público:

```typescript
import { Product, Category, Tag, Order, OrderStatus, OrderItem, User, PaginatedResponse } from '@/types/shared';

// Re-export para conveniência
export type { Product, Category, Tag, Order, OrderStatus, OrderItem, User, PaginatedResponse };

// Types específicos do público
export interface CartItem { id: number; product: Product; quantity: number; }
export interface Cart { id: number; items: CartItem[]; total: number; }
```

### Etapa 2 — Layout Público (`resources/js/Layouts/PublicLayout.tsx`)
- **Header:**
  - Logo da loja (lado esquerdo)
  - Navegação: Início, Produtos, Categorias
  - SearchInput com debounce
  - CartIcon com badge de quantidade
  - Botão Login/Register ou User dropdown (se logado)
  - Responsivo: hamburger menu em mobile
- **Footer:**
  - Links úteis
  - Copyright
- **Animações:** Transição suave entre páginas

**Marcar:** `[x] Layout Público`

### Etapa 3 — Componentes Compartilhados (`resources/js/Components/Public/`)

| Componente | Props | Funcionalidade |
|------------|-------|----------------|
| `ProductCard.tsx` | `product` | Card com imagem placeholder, nome, preço, botão "Adicionar ao Carrinho". Hover effect com sombra e scale. |
| `ProductGrid.tsx` | `products` | Grid responsivo (1 col mobile, 2 tablet, 3-4 desktop) |
| `CategoryFilter.tsx` | `categories`, `selected`, `onChange` | Sidebar ou dropdown com categorias hierárquicas |
| `PriceFilter.tsx` | `min`, `max`, `onChange` | Slider duplo de faixa de preço |
| `SearchInput.tsx` | `value`, `onChange` | Input com ícone de busca, debounce 300ms |
| `CartIcon.tsx` | `count` | Ícone de carrinho com badge numérico |
| `CartItem.tsx` | `item`, `onUpdate`, `onRemove` | Linha de item: imagem, nome, preço, QuantitySelector, botão remover |
| `QuantitySelector.tsx` | `value`, `onChange`, `max` | Botões +/- com input numérico |
| `OrderStatusTimeline.tsx` | `status` | Timeline visual: pending → processing → shipped → delivered |
| `Pagination.tsx` | `meta`, `onPageChange` | Botões de paginação ou infinite scroll |
| `HeroBanner.tsx` | `title`, `subtitle`, `cta` | Banner hero com gradiente e CTA |
| `Footer.tsx` | — | Footer da loja |

> [!NOTE]
> **SkeletonLoader:** Importe de `@/Components/Shared/SkeletonLoader` (criado na Fase 0). Não crie um SkeletonLoader próprio.

**Marcar:** `[x] Componentes compartilhados`

### Etapa 4 — Homepage (`resources/js/Pages/Home.tsx`)
- **HeroBanner** com titulo, subtítulo e botão "Ver Produtos"
- **Seção "Categorias":** Cards das categorias principais
- **Seção "Produtos em Destaque":** Grid com 8 produtos (mock)
- **Seção "Por que comprar conosco":** 3 cards com ícones (frete, segurança, suporte)
- **Design:** Gradientes, transições suaves, visual premium

**Marcar:** `[x] Homepage`

### Etapa 5 — Listagem de Produtos (`resources/js/Pages/Products/Index.tsx`)
- **Sidebar (desktop) / Top bar (mobile):** CategoryFilter + PriceFilter + SearchInput
- **ProductGrid** com produtos filtrados
- **Paginação** ou infinite scroll
- **Skeleton screens** durante loading
- **URL params** para filtros persistentes (categoria, preço, busca, página)
- **Estado vazio:** Mensagem "Nenhum produto encontrado"

**Marcar:** `[x] Listagem de Produtos com filtros`

### Etapa 6 — Detalhe do Produto (`resources/js/Pages/Products/Show.tsx`)
- Imagem grande do produto (placeholder)
- Nome, preço, descrição
- Tags como badges
- Categoria com link
- QuantitySelector + botão "Adicionar ao Carrinho"
- Informação de estoque ("Em estoque", "Últimas unidades", "Esgotado")
- **Animação:** Transição de entrada suave

**Marcar:** `[x] Detalhe do Produto`

### Etapa 7 — Login e Registro (`resources/js/Pages/Auth/`)
- **`Login.tsx`:** Email + senha, link "Criar conta", validação frontend com zod
- **`Register.tsx`:** Nome + email + senha + confirmação, link "Já tem conta?", validação
- **Design:** Centralizado, card com sombra, fundo com gradiente sutil
- **Toast:** Feedback de sucesso/erro

**Marcar:** `[x] Login/Registro`

### Etapa 8 — Carrinho (`resources/js/Pages/Customer/Cart.tsx`)
- Lista de CartItems com atualizar quantidade e remover
- Resumo: subtotal, taxa, frete, total
- Botão "Finalizar Compra" → navega para Checkout
- Botão "Continuar Comprando" → volta para Produtos
- **Estado vazio:** "Seu carrinho está vazio" com CTA para Produtos

**Marcar:** `[x] Carrinho`

### Etapa 9 — Checkout (`resources/js/Pages/Customer/Checkout.tsx`)
- Resumo do pedido (itens, totais)
- Formulário de endereço de entrega
- Formulário de endereço de cobrança (com checkbox "mesmo que entrega")
- Campo de notas (opcional)
- Botão "Confirmar Pedido"
- **Validação** com react-hook-form + zod
- **Toast** de sucesso → redireciona para detalhes do pedido

**Marcar:** `[x] Checkout`

### Etapa 10 — Histórico de Pedidos (`resources/js/Pages/Customer/Orders/`)
- **`Index.tsx`:** Lista de pedidos com StatusBadge, data, total. Link para detalhes.
- **`Show.tsx`:** OrderStatusTimeline + detalhes do pedido + lista de itens + endereço

**Marcar:** `[x] Histórico de Pedidos`

### Etapa 11 — Perfil do Usuário (`resources/js/Pages/Customer/Profile.tsx`)
- Exibir e editar: nome, email
- Alterar senha
- **Validação** com react-hook-form + zod

**Marcar:** `[x] Perfil do Usuário`

### Etapa 12 — Verificação
1. Verificar build: `npm run build`
2. Verificar TypeScript: `npx tsc --noEmit`
3. Verificar responsividade: mobile, tablet, desktop
4. Verificar acessibilidade: alt texts, aria labels, foco
5. **Solicitar commit ao humano:** Pause e sugira: `feat: complete public frontend with shop, cart, checkout and user pages`
