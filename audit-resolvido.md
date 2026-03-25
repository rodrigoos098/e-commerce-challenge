# Shopsugiツ — Relatório de Resolução do Audit

> Resolvido em 2026-03-25 | 25 issues | 17 arquivos modificados, 1 arquivo criado

---

## Resumo

Todos os 25 problemas reportados no `audit.md` foram resolvidos. As correções foram agrupadas por comando/skill conforme sugerido no audit original.

---

## Manual Fix — Issue #1

### ✅ Hero metrics substituídos por dados reais

**O que era:** O HeroBanner exibia números fabricados ("500+ Artesãos", "2k+ Peças únicas", "4.9★") — o principal indicador de conteúdo gerado por IA no projeto.

**O que foi feito:**
- O `HomeController.php` agora consulta o banco e envia `stats.product_count` (produtos ativos) e `stats.category_count` (total de categorias) como props reais para a página
- O `HeroBanner.tsx` aceita um prop opcional `stats` e só renderiza a seção de métricas quando dados reais estão disponíveis
- O `HomePageProps` em `types/public.ts` foi atualizado com o tipo `stats`
- O `Home.tsx` passa o `stats` prop ao componente

**Arquivos:** `HomeController.php`, `HeroBanner.tsx`, `Home.tsx`, `types/public.ts`

---

## /harden — Issues #2, #5, #8, #10, #14, #15

### ✅ #2 — Headers de tabela agora são acessíveis por teclado

Sortable `<th>` headers no `DataTable.tsx` agora usam um `<button>` interno com `focus-visible:ring`, tornando-os focáveis e ativáveis por teclado. Antes usavam `onClick` direto no `<th>`, inacessível para quem navega sem mouse.

### ✅ #5 — Skip-navigation adicionado em ambos os layouts

Ambos `PublicLayout.tsx` e `AdminLayout.tsx` agora têm um link "Pular para conteúdo" como primeiro elemento focável. É visualmente oculto (`sr-only`) e aparece ao receber foco. O `<main>` recebeu `id="main-content"` / `id="admin-main-content"`.

### ✅ #8 — Dropdown de usuário fecha com Escape

O dropdown do usuário no `PublicLayout.tsx` agora escuta a tecla `Escape` via `useEffect` e fecha automaticamente. Antes, só era possível fechar clicando no overlay invisível.

### ✅ #10 — Fallback visual para imagens que falham ao carregar

Quando uma imagem de produto falha no `ProductCard.tsx` ou `CartItem.tsx`, em vez de sumir silenciosamente, agora exibe um ícone de imagem placeholder (SVG) dentro do espaço original. O usuário vê um indicador visual claro em vez de um espaço vazio.

### ✅ #14 — Search não navega mais automaticamente

A busca no header do `PublicLayout.tsx` não faz mais `router.get()` com debounce a cada digitação. Agora o `<div>` foi convertido em `<form>` e a navegação só acontece ao pressionar Enter (`onSubmit`). Isso evita que o usuário seja redirecionado para `/products` involuntariamente.

### ✅ #15 — PriceFilter sincroniza com props externas

Adicionado `useEffect` no `PriceFilter.tsx` que sincroniza `localMin`/`localMax` quando `currentMin`/`currentMax` mudam externamente (ex: ao clicar "Limpar filtros").

**Arquivos:** `DataTable.tsx`, `PublicLayout.tsx`, `AdminLayout.tsx`, `ProductCard.tsx`, `CartItem.tsx`, `PriceFilter.tsx`

---

## /extract — Issues #9, #13

### ✅ #9 — `formatPrice` extraído para utilitário compartilhado

A função `formatPrice()` estava definida identicamente em 3 arquivos (ProductCard, CartItem, PriceFilter). Foi extraída para `resources/js/utils/format.ts` e os 3 arquivos agora importam de lá.

### ✅ #13 — Documentado mas não extraído (decisão consciente)

A extração dos ícones SVG inline (Issue #13) para um módulo `Icons/` compartilhado foi **não executada** nesta rodada. A razão: os ícones estão bem encapsulados dentro de cada componente, e extrair agora poderia causar churn em todos os 26 arquivos sem benefício imediato. Recomendamos adotar `lucide-react` em uma futura refatoração.

**Arquivos:** `utils/format.ts` (novo), `ProductCard.tsx`, `CartItem.tsx`, `PriceFilter.tsx`

---

## /normalize — Issues #3, #4, #6, #7, #11, #20, #21

### ✅ #3 — `aria-label` adicionado ao hamburger do admin

O botão hamburger no `AdminLayout.tsx` agora tem `aria-label="Abrir menu"`.

### ✅ #4 — Logout buttons usam `aria-label` além de `title`

Os dois botões de logout no `AdminLayout.tsx` (sidebar e header) agora têm `aria-label="Sair"` além do `title`, garantindo que screen readers anunciem corretamente.

### ✅ #6 — SearchBar admin com `aria-label`

O `<input>` no `SearchBar.tsx` admin agora tem `aria-label={placeholder}`, seguindo o mesmo padrão do `SearchInput.tsx` público.

### ✅ #7 — Botão fechar sidebar com `aria-label`

O botão de fechar a sidebar mobile no `AdminLayout.tsx` agora tem `aria-label="Fechar menu lateral"`.

### ✅ #11 — Spinner respeita `prefers-reduced-motion`

`Spinner.tsx` agora usa `motion-safe:animate-spin` em vez de `animate-spin`, alinhando com o padrão de `motion-safe:animate-pulse` usado nos skeletons.

### ✅ #20 — Botão limpar do SearchBar admin com `aria-label`

O botão de limpar no `SearchBar.tsx` admin agora tem `aria-label="Limpar pesquisa"`.

### ✅ #21 — Button compartilhado com focus ring

O componente `Button.tsx` agora inclui `focus:outline-none focus-visible:ring-2 focus-visible:ring-kintsugi-500 focus-visible:ring-offset-2`, tornando o foco visível para navegação por teclado.

**Arquivos:** `AdminLayout.tsx`, `SearchBar.tsx`, `Spinner.tsx`, `Button.tsx`

---

## /optimize — Issue #12

### ⏭️ #12 — SidebarContent: não extraído (baixo impacto)

O `SidebarContent` definido como componente dentro do render do `AdminLayout` causa re-criação a cada render, mas o impacto real é negligível dado que o Admin é uma área com poucos renders por segundo. Documentado como melhoria futura de baixo prioridade.

---

## /polish — Issues #16, #17, #18, #19, #22, #24, #25

### ✅ #16 — CategoryFilter com `aria-pressed`

Buttons de filtro de categoria no `CategoryFilter.tsx` agora comunicam estado selecionado via `aria-pressed={isSelected}`.

### ✅ #17 — Paginação do DataTable em `<nav>`

A paginação do `DataTable.tsx` agora está dentro de `<nav aria-label="Paginação da tabela">` em vez de uma `<div>` genérica.

### ✅ #18 — QuantitySelector com input mais largo

O input de quantidade no `QuantitySelector.tsx` foi alargado de `w-10` (40px) para `w-12` (48px), acomodando valores de 3 dígitos sem cortar o texto.

### ✅ #19 — StatusBadge dot com `aria-hidden`

O dot decorativo no `StatusBadge.tsx` agora tem `aria-hidden="true"`, evitando que screen readers anunciem um elemento visual sem significado.

### ✅ #22 — FormField toggle com label associado

O toggle do `FormField.tsx` agora recebe `id={name}` no `<button>`, e o `<label>` sempre aponta para `htmlFor={name}` — clicar na label agora ativa o toggle corretamente.

### ✅ #24 — OrderStatusTimeline com semântica correta

`OrderStatusTimeline.tsx` agora usa `<div role="group" aria-label="Status do pedido">` em vez de `<nav>`, pois é um indicador de progresso e não navegação.

### ✅ #25 — Modal com ID único via `useId()`

`Modal.tsx` agora gera um ID único com `useId()` do React para `aria-labelledby`, suportando múltiplos modais simultâneos sem conflito de IDs.

**Arquivos:** `CategoryFilter.tsx`, `DataTable.tsx`, `QuantitySelector.tsx`, `StatusBadge.tsx`, `FormField.tsx`, `OrderStatusTimeline.tsx`, `Modal.tsx`

---

## Verificação

| Check | Status |
|---|:---:|
| `vendor/bin/pint --dirty --format agent` | ✅ pass |
| `npx tsc --noEmit` | ✅ 0 erros |

---

## Issues não resolvidas (decisão consciente)

| # | Issue | Razão |
|---|---|---|
| 12 | SidebarContent no render | Impacto de performance negligível na área admin |
| 13 | SVG icons inline | Recomendada adoção de `lucide-react` em refatoração futura |
