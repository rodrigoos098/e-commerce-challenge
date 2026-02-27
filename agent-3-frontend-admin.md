# 🟡 Agente 3 — Frontend Admin (Dashboard + CRUD)

## Contexto

Você é o agente responsável por todas as **páginas administrativas** do e-commerce: Dashboard com métricas, CRUD de produtos, CRUD de categorias, listagem de pedidos e relatório de estoque baixo. A tecnologia é **React + TypeScript + Inertia.js + Tailwind CSS v4**.

> **Leia o plano completo:** [implementation_plan.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/AGENTS.md)
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

- [frontend-design](https://skills.sh/anthropics/claude-code/frontend-design)
- [ui-ux-pro-max](https://skills.sh/nextlevelbuilder/ui-ux-pro-max-skill/ui-ux-pro-max)
- [tailwind-design-system](https://skills.sh/wshobson/agents/tailwind-design-system)
- [responsive-design](https://skills.sh/wshobson/agents/responsive-design)
- [typescript-advanced-types](https://skills.sh/wshobson/agents/typescript-advanced-types)
- [executing-plans](https://skills.sh/obra/superpowers/executing-plans)

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-agent-3.md` (raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - Componentes/páginas criados
   - Decisões de design e justificativas
   - Screenshots ou descrição visual quando relevante
   ```

2. **Marque o checkbox no [task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)** ao concluir cada item da seção "Agente 3: Frontend Admin".
   [C:\Users\rodrigo.santos\.gemini\antigravity\brain\e3e17065-da6c-472d-b9c6-74d37305cf22\task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)

3. **Use TypeScript** em todos os arquivos (`.tsx`). Defina tipos/interfaces para todos os dados.

4. **Use Tailwind CSS v4** para estilos. NÃO use CSS inline ou styled-components.

5. **Design premium e responsivo (mobile-first).** O admin deve parecer profissional — use um design system consistente com cores harmônicas, sombras, bordas arredondadas, transições suaves.

6. **Crie dados mockados inicialmente.** Como o backend pode não estar pronto, use dados mockados com a mesma estrutura da API. Na integração (Fase 2), eles serão substituídos por chamadas reais via Inertia.

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
│       └── SkeletonLoader.tsx
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
    └── admin.ts  (interfaces TypeScript)
```

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — TypeScript Types (`resources/js/types/admin.ts`)
Defina todas as interfaces:
```typescript
interface Product {
  id: number; name: string; slug: string; description: string;
  price: number; cost_price: number; quantity: number; min_quantity: number;
  active: boolean; category: Category; tags: Tag[];
  created_at: string; updated_at: string;
}
interface Category { id: number; name: string; slug: string; description: string; parent_id: number | null; active: boolean; children?: Category[]; }
interface Tag { id: number; name: string; slug: string; }
interface Order { id: number; user_id: number; status: OrderStatus; total: number; subtotal: number; tax: number; shipping_cost: number; items: OrderItem[]; created_at: string; }
type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
interface OrderItem { id: number; product: Product; quantity: number; unit_price: number; total_price: number; }
interface PaginatedResponse<T> { data: T[]; meta: { current_page: number; per_page: number; total: number; last_page: number; }; links: { first: string; last: string; prev: string | null; next: string | null; }; }
interface DashboardStats { total_products: number; total_orders: number; total_revenue: number; low_stock_count: number; recent_orders: Order[]; }
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
| `SkeletonLoader.tsx` | `type` (table, card, form) | Loading states |

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
4. **Commit:** `feat: complete admin frontend with dashboard, CRUD and reports`
