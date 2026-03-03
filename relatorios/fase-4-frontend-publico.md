# Relatório Técnico — Fase 4: Frontend Público

**Projeto:** Sistema de E-commerce (Desafio Full-Stack)  
**Data de execução:** 2026-03-03  
**Responsável:** Agente 4 (Frontend Público)  
**Status:** ✅ Concluída  

---

## 1. Objetivo

Implementar a camada de frontend voltada ao consumidor final do e-commerce: vitrine de produtos, fluxo completo de compra (carrinho → checkout), autenticação pública (login/registro) e área do cliente (histórico de pedidos e perfil). O desenvolvimento usou React 19 + TypeScript + Inertia.js v2 + Tailwind CSS v4, com dados mockados prontos para substituição na Fase de Integração.

---

## 2. Stack e Decisões Técnicas

### 2.1 Tecnologias Utilizadas

| Tecnologia | Versão | Papel |
|---|---|---|
| React | 19 | Framework de UI |
| TypeScript | 5.9 | Tipagem estática em todos os arquivos `.tsx` |
| Inertia.js | v2 (`@inertiajs/react ^2.3.17`) | Bridge Laravel ↔ React: routing, mutações, props de página |
| Tailwind CSS | v4 | Estilização utility-first com config CSS-first (sem `tailwind.config.js`) |
| react-hot-toast | ^2.6.0 | Toast notifications para flash messages e feedback de ação |
| Vite | 7 | Bundler com alias `@` → `resources/js/` |

### 2.2 Estratégia de Data Fetching

Toda a comunicação segue o modelo **Inertia-first**, sem Axios ou React Query:

- **Leitura de dados:** Props da página via `usePage().props` — os Page Controllers passarão dados via `Inertia::render()` na Fase de Integração.
- **Mutações (carrinho, pedido):** `router.post()`, `router.put()`, `router.delete()` do Inertia.
- **Filtros, busca e paginação:** `router.get()` com query params, `preserveState: true, replace: true`.
- **Formulários:** `useForm()` do Inertia para login, registro, checkout e perfil — gerencia `data`, `errors`, `processing` e `reset()` automaticamente.

### 2.3 Dados Mockados

Cada página declara constantes `MOCK_*` no topo do arquivo (tipadas) simulando as props que o Inertia entregará. O padrão de consumo é:

```tsx
export default function Page({ products }: Partial<ProductsPageProps>) {
    const data = products ?? MOCK_PAGINATED;
    // ...
}
```

Na Fase de Integração: remover o `Partial<>`, os defaults e as constantes `MOCK_*`.

### 2.4 Debounce de Busca

A busca por texto nos filtros de produtos usa debounce de **400 ms** implementado com `useCallback` + `setTimeout`/`clearTimeout`, evitando requisições consecutivas enquanto o usuário digita. O mesmo padrão é aplicado na barra de busca global do `PublicLayout`.

### 2.5 Nomeação: CartItem (type) vs CartItem (componente)

Para evitar colisão de nomes, o componente de linha do carrinho é importado com alias:

```tsx
import CartItem as CartItemComponent from '@/Components/Public/CartItem';
```

---

## 3. Estrutura de Arquivos Criados

```
resources/js/
├── types/
│   └── public.ts                              # Types específicos do frontend público
├── Layouts/
│   └── PublicLayout.tsx                       # Layout wrapper de todas as páginas públicas
├── Components/
│   └── Public/
│       ├── HeroBanner.tsx                     # Seção hero da homepage
│       ├── SearchInput.tsx                    # Input de busca com botão clear
│       ├── CartIcon.tsx                       # Ícone de carrinho com badge
│       ├── ProductCard.tsx                    # Card de produto com add-to-cart
│       ├── ProductGrid.tsx                    # Grid responsivo de ProductCards
│       ├── CategoryFilter.tsx                 # Filtro hierárquico de categorias
│       ├── PriceFilter.tsx                    # Filtro de faixa de preço (dual range)
│       ├── QuantitySelector.tsx               # Seletor de quantidade (+/−)
│       ├── CartItem.tsx                       # Linha de item no carrinho
│       ├── OrderStatusTimeline.tsx            # Timeline visual de status do pedido
│       └── Pagination.tsx                     # Paginação com ellipsis inteligente
└── Pages/
    ├── Home.tsx                               # Homepage pública
    ├── Products/
    │   ├── Index.tsx                          # Listagem com filtros sidebar + mobile drawer
    │   └── Show.tsx                           # Detalhe do produto com galeria e add-to-cart
    ├── Auth/
    │   ├── Login.tsx                          # Formulário de login
    │   └── Register.tsx                       # Formulário de registro
    └── Customer/
        ├── Cart.tsx                           # Carrinho de compras
        ├── Checkout.tsx                       # Checkout em 3 etapas
        └── Orders/
            ├── Index.tsx                      # Histórico de pedidos
            └── Show.tsx                       # Detalhe do pedido
        └── Profile.tsx                        # Perfil e alteração de senha
```

**Total:** 1 layout + 11 componentes + 10 páginas = **22 arquivos `.tsx`** + 1 arquivo de tipos

---

## 4. Etapas Implementadas

### 4.1 Etapa 1 — TypeScript Types (`types/public.ts`)

Arquivo re-exporta os tipos compartilhados de `@/types/shared` e adiciona:

| Tipo | Descrição |
|---|---|
| `CartItem` | Item no carrinho: `id`, `product: Product`, `quantity` |
| `Cart` | Carrinho completo com `items`, `total`, `subtotal`, `tax`, `shipping_cost`, `item_count` |
| `CheckoutAddress` | Endereço de entrega/cobrança com 6 campos |
| `CheckoutFormData` | Dados do formulário de checkout (endereços + flag `same_as_shipping` + `notes`) |
| `ProductFilters` | Filtros de listagem: `search`, `category_id`, `price_min`, `price_max`, `page` |
| `HomePageProps` | `featured_products`, `categories` |
| `ProductsPageProps` | `products: PaginatedResponse<Product>`, `categories`, `filters` |
| `ProductShowPageProps` | `product`, `related_products?` |
| `CartPageProps` | `cart: Cart` |
| `CheckoutPageProps` | `cart: Cart` |
| `OrdersPageProps` | `orders: PaginatedResponse<Order>` |
| `OrderShowPageProps` | `order: Order` |
| `ProfilePageProps` | `user: User` |

Também corrigido em `types/shared.ts`: `PaginatedResponse.links.first` e `.last` passaram a ser `string | null` (eram `string`), alinhando com o retorno real do Laravel.

---

### 4.2 Etapa 2 — Layout (`PublicLayout.tsx`)

Layout wrapper responsivo que envolve todas as páginas públicas.

| Aspecto | Implementação |
|---|---|
| **Header sticky** | `position: sticky top-0`, shadow ao rolar via `scroll` event listener |
| **Logo** | Link `/` com texto gradiente `violet → indigo` |
| **Navegação desktop** | Links: Início, Produtos, com destaque de rota ativa |
| **Busca global** | Input com debounce de 400 ms; ao confirmar, navega para `/products?search=...` |
| **Badge do carrinho** | Lê `usePage().props.cart_count`, exibe badge até 99+ |
| **Menu do usuário** | Dropdown com email, link para pedidos, perfil, e link para "Painel Admin" (se `user.roles.includes('admin')`) |
| **Mobile menu** | Hamburger que expande menu com links e estado de autenticação |
| **Flash messages** | `useEffect` watching `usePage().props.flash`; exibe toasts via `react-hot-toast` |
| **Logout** | `router.post('/logout')` |
| **Footer** | 4 colunas (Marca, Loja, Conta, Atendimento) com ícones de redes sociais |
| **Title** | `document.title` atualizado via prop `title` |

**Paleta de design:**
- Header: `bg-white`, border-bottom `gray-100`
- Accent: `violet-600`
- Footer: `bg-gray-900`, texto `gray-400`
- Links ativos: `text-violet-700 font-semibold`

---

### 4.3 Etapa 3 — Componentes Compartilhados

#### `HeroBanner`

Seção hero da homepage com:
- Background gradiente `violet-600 → indigo-700` com blobs decorativos animados (`animate-pulse`)
- Título principal com destaque em texto branco + span gradiente
- 2 CTAs: "Ver Produtos" (sólido branco) e "Nossas Categorias" (outline transparente)
- Barra de stats: 3 métricas (produtos, clientes, avaliação) com separadores

#### `SearchInput`

Input de busca simples com:
- Ícone de lupa à esquerda
- Botão "×" clear que aparece quando há texto
- `onChange` emite o valor atual (o debounce fica a cargo do consumidor)

#### `CartIcon`

Ícone de carrinho SVG com badge numérico:
- Badge capped em `99+`
- `aria-label` dinâmico para acessibilidade

#### `ProductCard`

Card de produto com:
- Imagem via `picsum.photos/seed/{id}/400/400` (placeholder determinístico)
- Badge de categoria
- Nome, preço formatado em BRL
- Indicador de estoque inline (Disponível / Estoque baixo / Esgotado)
- Botão "Adicionar" dispara `router.post('/cart/items', { product_id, quantity: 1 })`
- Efeito `hover:scale-[1.02] hover:shadow-xl` com `transition-all`
- Link no card → `/products/{slug}`

#### `ProductGrid`

Grid responsivo `1 → 2 → 3 → 4` colunas com:
- `SkeletonLoader type="card"` quando prop `loading` é `true`
- Empty state com ícone e CTA para limpar filtros

#### `CategoryFilter`

Filtro de categorias com suporte a hierarquia:
- Item "Todas as Categorias" zera o filtro
- `CategoryItem` recursivo exibe subcategorias com indentação
- Ativo: `bg-violet-50 text-violet-700 font-semibold border-l-2 border-violet-600`

#### `PriceFilter`

Dois sliders independentes (mín / máx) com:
- Input numérico editável para cada extremo
- Botões "Aplicar" e "Resetar"
- Clamp automático: `min ≤ max`

#### `QuantitySelector`

Seletor +/− com:
- Input numérico central editável
- Clamp entre `props.min` e `props.max`
- Estado `disabled` quando esgotado

#### `CartItem` (componente)

Linha de item no carrinho com:
- Imagem + nome + preço unitário
- `QuantitySelector` em linha — ao alterar, dispara `router.put('/cart/items/{id}', { quantity })`
- Botão "Remover" → `router.delete('/cart/items/{id}')`
- Total da linha (`quantidade × preço`)

#### `OrderStatusTimeline`

Timeline visual de 4 etapas: Pedido Realizado → Processando → Enviado → Entregue.
- Etapa atual em `violet`, concluídas em `emerald`, pendentes em `gray`
- Estado `cancelled` exibe todas as etapas em `red-200` + ícone de cancelamento
- Conectores de linha entre etapas com preenchimento progressivo

#### `Pagination`

Paginação inteligente com:
- Ellipsis (`…`) quando há muitas páginas
- Sempre exibe primeira, última, atual e vizinhas ±1
- Botões Anterior/Próximo com setas SVG
- `aria-label` e `aria-current` para acessibilidade

---

### 4.4 Etapa 4 — Homepage (`Pages/Home.tsx`)

Seções em ordem:
1. **HeroBanner** — gradiente com stats
2. **Categorias** — grid 2×3 de cards com emoji como ícone e `bg-gradient` por categoria
3. **Produtos em Destaque** — `ProductGrid` com 8 itens, cabeçalho "Mais Vendidos"
4. **Por que comprar conosco** — 3 feature cards (Entrega, Segurança, Suporte)
5. **CTA escuro** — banner `bg-gray-900` com link para `/products`

---

### 4.5 Etapa 5 — Listagem de Produtos (`Pages/Products/Index.tsx`)

| Aspecto | Implementação |
|---|---|
| **Layout** | Sidebar 280px (lg+) + conteúdo principal |
| **Sidebar** | `CategoryFilter` + `PriceFilter` + `SearchInput` com debounce 400ms |
| **Mobile** | Botão "Filtros" abre drawer com overlay e swipe-to-close |
| **Filtros ativos** | Badge contador no botão mobile; botão "Limpar" aparece quando há filtro ativo |
| **Resultados** | "`N produtos encontrados`" com termo de busca destacado |
| **Paginação** | Componente `Pagination` com `router.get(preserveState, replace)` |
| **Aplicação de filtros** | `router.get('/products', filters, { preserveState: true, replace: true })` |

---

### 4.6 Etapa 6 — Detalhe do Produto (`Pages/Products/Show.tsx`)

| Aspecto | Implementação |
|---|---|
| **Breadcrumb** | Início → Produtos → Nome do produto |
| **Galeria** | Imagem principal grande + 3 thumbnails (picsum seeds derivados do `id`) |
| **Badges** | Categoria (link) + tags como chips |
| **Preço** | Formatado em BRL; texto de parcelamento (`12× de R$ X sem juros`) |
| **Estoque** | Componente `StockIndicator` inline com 3 estados |
| **Add to cart** | `QuantitySelector` + botão que dispara `router.post('/cart/items')` |
| **Trust badges** | 3 ícones: Entrega grátis, Devolução, Pagamento seguro |
| **Produtos Relacionados** | `ProductGrid` com até 4 items e heading "Você também pode gostar" |

---

### 4.7 Etapa 7 — Autenticação (`Pages/Auth/Login.tsx` e `Register.tsx`)

Ambas as páginas usam layout próprio (sem `PublicLayout`) com fundo gradiente `violet-50 → indigo-50`.

**Login:**
- `useForm({ email, password, remember })`
- `form.post('/login')`
- Checkbox "Lembrar-me"
- Link para registro e recuperação de senha

**Register:**
- `useForm({ name, email, password, password_confirmation })`
- `form.post('/register')`
- Validação de força da senha (indicador visual)
- Link para login

Ambas exibem erros por campo com `aria-describedby` e `role="alert"`.

---

### 4.8 Etapa 8 — Carrinho (`Pages/Customer/Cart.tsx`)

| Aspecto | Implementação |
|---|---|
| **Lista de itens** | `CartItemComponent` renderizado para cada `cart.items` |
| **Empty state** | Ilustração SVG + botão "Ir para a Loja" |
| **Resumo** | Card lateral com Subtotal, Impostos, Frete (Grátis se 0), Total em negrito |
| **Limpar carrinho** | `router.delete('/cart')` com `processing` state |
| **Ir ao checkout** | `<Link href="/customer/checkout">` — desabilitado se carrinho vazio |
| **Contagem** | Header: "`N itens no carrinho`" |

---

### 4.9 Etapa 9 — Checkout (`Pages/Customer/Checkout.tsx`)

Formulário em 3 etapas sequenciais com barra de progresso:

| Etapa | Conteúdo |
|---|---|
| 1 — Entrega | Nome, rua, cidade, estado, CEP, país |
| 2 — Cobrança | Checkbox "Mesmo endereço de entrega" — se marcado, oculta os campos e copia os dados |
| 3 — Confirmação | Campo de observações + resumo final do pedido |

- **Sidebar** (desktop): Resumo do pedido com imagens, quantidades e total — visível em todas as etapas
- **`useForm`**: Um único objeto gerencia todos os campos das 3 etapas
- **Submissão**: `form.post('/customer/orders')` na etapa 3
- **Navegação entre etapas**: Botões Anterior/Próximo sem submeter o formulário

---

### 4.10 Etapa 10 — Histórico de Pedidos (`Pages/Customer/Orders/`)

**`Index.tsx`:**
- Lista de pedidos como cards linkáveis (`/customer/orders/{id}`)
- Card: ícone de pedido, número do pedido, data, `StatusBadge`, total
- Empty state com CTA para a loja
- Paginação via `Pagination` component

**`Show.tsx`:**
- Breadcrumb: Meus Pedidos → Pedido #N
- Header com número, data/hora completos e `StatusBadge`
- `OrderStatusTimeline` com status atual
- Lista de itens: imagem, nome (link para produto), `qty × unit`, total da linha
- Breakdown de preços: subtotal, impostos, frete, total
- Cards de endereço de entrega e cobrança (se presentes)
- Campo de observações (se presente)
- Link "Voltar para meus pedidos"

---

### 4.11 Etapa 11 — Perfil (`Pages/Customer/Profile.tsx`)

Interface com abas (tabs):

| Aba | Conteúdo |
|---|---|
| **Informações** | Campos Nome e E-mail com `useForm`; `form.put('/customer/profile')` |
| **Segurança** | Campos Senha atual, Nova senha, Confirmar senha com `useForm`; `form.put('/customer/profile/password')` |

- Cabeçalho com avatar gerado a partir da inicial do nome (gradient `violet → indigo`)
- Tabs controladas por `useState<'profile' | 'password'>`
- Dois `useForm` independentes para evitar reset cruzado de campos
- Spinners de loading nos botões de submit durante `form.processing`

---

## 5. Design System

### Paleta de Cores

| Uso | Classe Tailwind |
|---|---|
| Accent primário | `violet-600` |
| Accent hover | `violet-700` |
| Texto principal | `gray-900` |
| Texto secundário | `gray-500` |
| Bordas sutis | `gray-100` / `gray-200` |
| Fundo de página | `gray-50` |
| Cards | `bg-white` + `border-gray-100` + `shadow-sm` |
| CTA hero | `bg-white text-violet-700` |
| Footer | `bg-gray-900` |
| Status pending | `amber-*` |
| Status processing | `blue-*` |
| Status shipped | `indigo-*` |
| Status delivered | `green-*` |
| Status cancelled | `red-*` |

### Padrão de Card

```html
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
```

### Padrão de Botão Primário

```html
<button class="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white
               hover:bg-violet-700 active:scale-[.98] transition-all
               shadow-md shadow-violet-200 disabled:opacity-60">
```

### Tipografia

- Títulos de página: `text-2xl sm:text-3xl font-extrabold text-gray-900`
- Subtítulos de seção: `text-base font-bold text-gray-900`
- Labels de campo: `text-sm font-semibold text-gray-700`
- Texto corrido: `text-sm text-gray-600`
- Texto auxiliar: `text-xs text-gray-400`
- Preços: `font-bold text-gray-900`

---

## 6. Rotas Esperadas para Integração

Todas as mutações apontam para as seguintes URLs (sem stubs criados nesta fase — aguardam integração):

| Página | Método | Rota | Ação |
|---|---|---|---|
| Login | POST | `/login` | Autenticar |
| Register | POST | `/register` | Criar conta |
| Logout | POST | `/logout` | Encerrar sessão |
| Produtos | GET | `/products` | Listar com filtros |
| Produto | GET | `/products/{slug}` | Detalhe |
| Adicionar ao carrinho | POST | `/cart/items` | `{ product_id, quantity }` |
| Atualizar item | PUT | `/cart/items/{id}` | `{ quantity }` |
| Remover item | DELETE | `/cart/items/{id}` | — |
| Limpar carrinho | DELETE | `/cart` | — |
| Checkout | POST | `/customer/orders` | Criar pedido |
| Pedidos | GET | `/customer/orders` | Listar pedidos |
| Detalhe pedido | GET | `/customer/orders/{id}` | Detalhe |
| Atualizar perfil | PUT | `/customer/profile` | `{ name, email }` |
| Alterar senha | PUT | `/customer/profile/password` | `{ current_password, password, password_confirmation }` |

---

## 7. Verificações Realizadas

| Verificação | Resultado |
|---|---|
| `npx tsc --noEmit` | ✅ 0 erros |
| `npm run build` | ✅ Sucesso (4.39s, build limpo) |
| TypeScript: `PageProps` index signature | ✅ Corrigido em `PublicLayout.tsx` |
| TypeScript: `PaginatedResponse.links` | ✅ Corrigido para `string \| null` |

---

## 8. Commits Realizados

| Hash | Etapa | Descrição |
|---|---|---|
| `4c587a0` | 1 | `A4 - Etapa 1 - types publicos e correcao PaginatedResponse` |
| `45c0d81` | 2 | `A4 - Etapa 2 - PublicLayout com header sticky footer e flash messages` |
| `7ba8683` | 3 | `A4 - Etapa 3 - componentes publicos HeroBanner ProductCard ProductGrid CategoryFilter PriceFilter Pagination etc` |
| `66a4534` | 4 | `A4 - Etapa 4 - Homepage com hero categorias produtos em destaque e CTA` |
| `d89b697` | 5 | `A4 - Etapa 5 - listagem de produtos com filtros sidebar e paginacao` |
| `05349c0` | 6 | `A4 - Etapa 6 - detalhe do produto com galeria badges add-to-cart e relacionados` |
| `cd693b5` | 7 | `A4 - Etapa 7 - paginas de autenticacao Login e Register com useForm Inertia` |
| `2d4429a` | 8 | `A4 - Etapa 8 - pagina carrinho com resumo do pedido e estado vazio` |
| `92c36fc` | 9 | `A4 - Etapa 9 - pagina checkout formulario 3 etapas endereco e resumo` |
| `85b9006` | 10 | `A4 - Etapa 10 - historico de pedidos e detalhe com timeline de status` |
| `90493f3` | 11 | `A4 - Etapa 11 - perfil do usuario edicao de dados e alteracao de senha` |
| `d2a707e` | 12 | `A4 - Etapa 12 - build ok TypeScript sem erros checkboxes task.md atualizados` |

---

## 9. Notas para a Fase de Integração

1. **Remover dados mockados:** Cada página possui constantes `MOCK_*` no topo e props tipadas com `Partial<XPageProps>`. Na integração, remover o `Partial<>`, eliminar os `?? MOCK_*` e apagar as constantes mock.

2. **`cart_count` no `PublicLayout`:** O header exibe o badge do carrinho via `usePage().props.cart_count`. O middleware ou `HandleInertiaRequests` deve incluir esse valor em `share()`:
   ```php
   'cart_count' => fn () => auth()->check()
       ? Cart::where('user_id', auth()->id())->withCount('items')->first()?->items_count ?? 0
       : 0,
   ```

3. **Flash messages:** O `PublicLayout` já consome `usePage().props.flash.success` e `.error`. Os controllers devem usar `session()->flash('success', '...')` ou `back()->with('success', '...')`.

4. **Auth guard nas rotas de cliente:** As rotas `/customer/*` devem ser protegidas com `middleware('auth')`. As rotas `/admin/*` já estavam previstas com `middleware(['auth', 'role:admin'])`.

5. **Rotas Inertia vs. API:** As páginas fazem mutações para rotas web (`POST /cart/items`, `POST /customer/orders`, etc.). Essas rotas precisam existir em `routes/web.php` apontando para Page Controllers que retornem `Inertia::render()` ou `redirect()->back()`, **não** os API Controllers de `routes/api.php`.

6. **Paginação:** O componente `Pagination` aceita `meta: { current_page, last_page, per_page, total }` e um callback `onPageChange(page)`. Internamente usa `router.get` com `preserveState: true`. A forma mais limpa de integrar é passar `$products->toArray()` do Resource Controller direto como prop Inertia — o `meta` já vem no formato correto.

7. **Imagens de produtos:** O `ProductCard` e `Products/Show` usam `https://picsum.photos/seed/{product.id}/400/400` como placeholder. Na integração, substituir pela URL real do storage (campo `image_url` no modelo `Product` ou via `Storage::url()`).

8. **Endereços no checkout:** O `Checkout.tsx` serializa os endereços como strings formatadas antes de submeter (ex.: `"Rua X, 123 — Cidade, UF — CEP"`). O backend deve parsear ou aceitar o formato. Uma alternativa melhor é submeter campos separados e o backend montar a string — ajustar o `useForm` conforme a API definida.
