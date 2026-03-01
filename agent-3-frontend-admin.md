# 🟡 Agente 3 — Frontend Admin (Dashboard + CRUD)

## Contexto

Você é o agente responsável por todas as **páginas administrativas** do e-commerce: Dashboard com métricas, CRUD de produtos, CRUD de categorias, listagem de pedidos e relatório de estoque baixo. A tecnologia é **React + TypeScript + Inertia.js + Tailwind CSS v4**.

> **Leia o plano completo:** [implementation_plan.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/AGENTS.md)
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

- [frontend-design](https://skills.sh/anthropics/claude-code/frontend-design)
- [ui-ux-pro-max](https://skills.sh/nextlevelbuilder/ui-ux-pro-max-skill/ui-ux-pro-max)
- [tailwind-design-system](https://skills.sh/wshobson/agents/tailwind-design-system)
- [responsive-design](https://skills.sh/wshobson/agents/responsive-design)
- [typescript-advanced-types](https://skills.sh/wshobson/agents/typescript-advanced-types)
- [executing-plans](https://skills.sh/obra/superpowers/executing-plans)

> [!CAUTION]
> **Prioridade de instruções:** Em caso de **qualquer conflito** entre o que uma skill recomenda e o que está definido no [implementation_plan.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/implementation_plan.md), no [README-challenge.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/README-challenge.md) ou no [AGENTS.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/AGENTS.md), **sempre siga o plano de implementação e as regras do desafio**. As skills são guias de boas práticas gerais; o plano e o desafio definem as decisões específicas deste projeto.

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-agent-3.md` (raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - Componentes/páginas criados
   - Decisões de design e justificativas
   - Screenshots ou descrição visual quando relevante
   ```

2. **Marque o checkbox no [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)** ao concluir cada item da seção "Agente 3: Frontend Admin".
   [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)

3. **Use TypeScript** em todos os arquivos (`.tsx`). Defina tipos/interfaces para todos os dados.

4. **Use Tailwind CSS v4** para estilos. NÃO use CSS inline ou styled-components.

5. **Design premium e responsivo (mobile-first).** O admin deve parecer profissional — use um design system consistente com cores harmônicas, sombras, bordas arredondadas, transições suaves.

6. **Crie dados mockados inicialmente.** Como o backend pode não estar pronto, crie constantes mock no topo de cada página simulando as props que o Inertia vai entregar. Na integração (Fase 2), serão substituídos pelas props reais do `Inertia::render()`.

7. **Commits por etapa.** Ao concluir cada etapa numerada, **pause a implementação**, solicite aprovação ao humano e sugira o commit. O formato obrigatório é:
   ```
   A3 - Admin - [descrição do que foi feito na etapa]
   ```
   Exemplos:
   - `A3 - Admin - layout AdminLayout com sidebar responsiva`
   - `A3 - Admin - componentes compartilhados (DataTable, StatCard, Modal)`
   - `A3 - Admin - CRUD de produtos (Index, Create, Edit, Show)`
   **Aguarde o humano aprovar** antes de prosseguir para a próxima etapa.

> [!IMPORTANT]
> **Estratégia de data fetching — 100% Inertia:**
> - **Dados de página:** Recebidos via Inertia props (`usePage().props`). Os Page Controllers passam dados via `Inertia::render()` usando a camada de Services.
> - **Mutações (criar/editar/excluir):** `router.post()` / `router.put()` / `router.delete()` do `@inertiajs/react`, que submete para controllers Inertia no server.
> - **Filtros, paginação e busca:** `router.get()` ou `router.visit()` com query params — o Inertia recarrega a página com os novos dados do server.
> - **Não use Axios nem React Query.** O Inertia gerencia tudo: navegação, revalidação, erros de validação (via `usePage().props.errors`), e redirecionamentos.
>
> A API REST (`/api/v1/...`) existe para **clientes externos, testes e documentação Swagger**, não sendo consumida pelo frontend Inertia. Essa decisão será documentada no `PROJECT.md`.

---

## Estrutura de Pastas

```
resources/js/
├── Components/
│   └── Admin/
│       ├── DataTable.tsx
│       ├── StatCard.tsx
│       ├── FormField.tsx
│       ├── Modal.tsx
│       ├── StatusBadge.tsx
│       ├── Sidebar.tsx
│       ├── SearchBar.tsx
│       └── ... (importar SkeletonLoader de ../Shared/)
├── Layouts/
│   └── AdminLayout.tsx
├── Pages/
│   └── Admin/
│       ├── Dashboard.tsx
│       ├── Products/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   ├── Edit.tsx
│       │   └── Show.tsx
│       ├── Categories/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   └── Edit.tsx
│       ├── Orders/
│       │   ├── Index.tsx
│       │   └── Show.tsx
│       └── Stock/
│           └── LowStock.tsx
└── types/
    └── admin.ts  (apenas types específicos do admin, importar shared de @/types/shared)
```

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — TypeScript Types (`resources/js/types/admin.ts`)

> [!IMPORTANT]
> **Importe os types compartilhados de `@/types/shared`** (criado na Fase 0). Defina aqui apenas types específicos do admin:

```typescript
import { Product, Category, Order, OrderStatus, OrderItem, PaginatedResponse } from '@/types/shared';

// Re-export para conveniência
export type { Product, Category, Order, OrderStatus, OrderItem, PaginatedResponse };

// Types específicos do admin
export interface DashboardStats {
  total_products: number;
  total_orders: number;
  total_revenue: number;
  low_stock_count: number;
  recent_orders: Order[];
}
```

### Etapa 2 — Layout Admin (`resources/js/Layouts/AdminLayout.tsx`)
- **Sidebar** com links: Dashboard, Produtos, Categorias, Pedidos, Estoque
- **Header** com nome do admin, avatar, botão logout
- **Breadcrumbs** dinâmicos
- **Responsivo:** Sidebar colapsável em mobile (hamburger menu)
- Cores escuras/profissionais (dark sidebar, light content area)

**Marcar:** `[x] Layout Admin`

### Etapa 3 — Componentes Compartilhados (`resources/js/Components/Admin/`)

| Componente | Props | Funcionalidade |
|------------|-------|----------------|
| `DataTable.tsx` | `columns`, `data`, `onSort`, `onFilter`, `pagination` | Tabela genérica com sorting, filtros, paginação |
| `StatCard.tsx` | `title`, `value`, `icon`, `trend`, `color` | Card de métrica com ícone e tendência |
| `FormField.tsx` | `label`, `name`, `type`, `error`, `register` | Campo de form integrado com react-hook-form |
| `Modal.tsx` | `isOpen`, `onClose`, `title`, `children`, `onConfirm` | Modal de confirmação/ação |
| `StatusBadge.tsx` | `status` | Badge colorido por status (pending=amarelo, shipped=azul, etc) |
| `Sidebar.tsx` | `items`, `activeItem` | Navegação lateral |
| `SearchBar.tsx` | `onSearch`, `placeholder` | Input com debounce |

> [!NOTE]
> **SkeletonLoader:** Importe de `@/Components/Shared/SkeletonLoader` (criado na Fase 0). Não crie um SkeletonLoader próprio.

**Marcar:** `[x] Componentes Admin compartilhados`

### Etapa 4 — Dashboard (`resources/js/Pages/Admin/Dashboard.tsx`)
- 4 StatCards no topo: Total Produtos, Total Pedidos, Receita Total, Estoque Baixo
- Gráfico de pedidos recentes (pode usar dados mock representados como barras simples em CSS)
- Tabela com últimos 5 pedidos
- Tabela com produtos com estoque baixo
- **Design:** Cores vibrantes nos cards, animações de entrada suaves

**Marcar:** `[x] Dashboard`

### Etapa 5 — CRUD de Produtos
- **`Products/Index.tsx`**: DataTable com colunas (nome, preço, quantidade, categoria, status), filtros (categoria, ativo/inativo), busca por nome, paginação. Botões "Editar" e "Excluir" por linha. Botão "Novo Produto".
- **`Products/Create.tsx`**: Formulário com react-hook-form + zod. Campos: name, description, price, cost_price, quantity, min_quantity, category (select), tags (multi-select), active (toggle). Validação frontend.
- **`Products/Edit.tsx`**: Mesmo formulário, pre-preenchido.
- **`Products/Show.tsx`**: Exibição detalhada com movimentações de estoque.

**Marcar:** `[x] CRUD de Produtos`

### Etapa 6 — CRUD de Categorias
- **`Categories/Index.tsx`**: Exibição hierárquica em árvore (indentação visual com children). Botões expandir/colapsar.
- **`Categories/Create.tsx`**: Formulário com parent_id (select da árvore), name, description, active.
- **`Categories/Edit.tsx`**: Mesmo formulário, pre-preenchido.

**Marcar:** `[x] CRUD de Categorias`

### Etapa 7 — Listagem de Pedidos
- **`Orders/Index.tsx`**: DataTable com colunas (ID, cliente, status, total, data). Filtros por status. StatusBadge para cores.
- **`Orders/Show.tsx`**: Detalhes do pedido, lista de itens, endereço, notas. Dropdown para atualizar status (apenas admin).

**Marcar:** `[x] Listagem de Pedidos`

### Etapa 8 — Relatório de Estoque Baixo
- **`Stock/LowStock.tsx`**: Tabela de produtos com `quantity <= min_quantity`. Colunas: nome, quantidade atual, quantidade mínima, diferença. Ordenado por prioridade (menor estoque primeiro). Alerta visual para itens críticos.

**Marcar:** `[x] Relatório de Estoque Baixo`

### Etapa 9 — Verificação
1. Verificar build: `npm run build`
2. Verificar TypeScript: `npx tsc --noEmit`
3. Verificar responsividade em diferentes tamanhos
4. **Solicitar commit ao humano** com a mensagem sugerida: `A3 - Admin - verificacao final build e typescript`
