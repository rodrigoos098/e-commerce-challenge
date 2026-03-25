# enhanceTemp.md

Design enhancement session — Shopsugi
Branch: front-impeccable

---

## /onboard

### Arquivos alterados
- `resources/js/Components/Public/ProductGrid.tsx`
- `resources/js/Pages/Products/Index.tsx`
- `resources/js/Pages/Customer/Cart.tsx`
- `resources/js/Pages/Customer/Orders/Index.tsx`

### O que foi feito

**ProductGrid empty state**
- Ícone: `bg-warm-100 text-warm-400` → `bg-kintsugi-50 text-kintsugi-400` (warm, on-brand)
- Heading: adicionado `font-display font-extrabold`
- Subtítulo: mais acionável ("explorar toda a coleção")
- Novo prop `onClearFilters?: () => void` — quando presente, exibe botão "Limpar filtros"
- `Products/Index.tsx` passa `onClearFilters={hasActiveFilters ? clearFilters : undefined}`

**Cart empty state**
- Ícone: `bg-warm-50 text-warm-400` → `bg-kintsugi-50 text-kintsugi-400`
- h1: adicionado `font-display font-extrabold`
- Copy: "Descubra pecas artesanais unicas para adicionar." → "Cada peça tem uma história. Encontre a que vai contar a sua."
- Adicionado `max-w-xs mx-auto leading-relaxed` para leitura confortável

**Orders empty state**
- Ícone: `bg-warm-50 text-warm-400` → `bg-kintsugi-50 text-kintsugi-400`
- h2: adicionado `font-display font-extrabold`, cor: `text-warm-600` → `text-warm-700`
- Copy: "Quando você fizer um pedido, ele aparecerá aqui." → "Cada peça tem uma história. Comece a escrever a sua."
- CTA: "Ir para a loja" → "Explorar a coleção"

---

## /animate

### Arquivos alterados
- `resources/css/app.css`
- `resources/js/Components/Public/HeroBanner.tsx`
- `resources/js/Layouts/PublicLayout.tsx`
- `resources/js/Pages/Products/Index.tsx`
- `resources/js/Components/Public/ProductGrid.tsx`

### O que foi feito

**`app.css` — Fundação de animações**
- 5 `@keyframes` adicionados: `fade-up`, `fade-in`, `slide-down`, `slide-in-left`, `float`
- 5 tokens de animação registrados no `@theme` com easing `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out-expo)
- `@media (prefers-reduced-motion: reduce)` adicionado — corrige violação de acessibilidade WCAG

**`HeroBanner.tsx` — Animação de assinatura (entrada escalonada)**
- Badge: `animate-fade-up` (imediato)
- h1: `animate-fade-up` delay 100ms
- Subtítulo: `animate-fade-up` delay 250ms
- CTAs: `animate-fade-up` delay 400ms
- Stats: `animate-fade-up` delay 500ms
- Cria leitura fluida top→bottom, refletindo o fluxo natural do olho

**`PublicLayout.tsx` — Mobile menu**
- `animate-slide-down` ao montar o menu — elimina o aparecimento abrupto

**`Products/Index.tsx` — Mobile sidebar de filtros**
- Backdrop: `animate-fade-in` — entrada suave do overlay escuro
- Painel: `animate-slide-in-left` — slide natural da esquerda (origem do painel)

**`ProductGrid.tsx` — Empty state**
- Ícone com `animate-float` — flutuação suave 3s infinita, adiciona vida sem distrair

---

## /polish

### Arquivos alterados
- `resources/js/Pages/Customer/Cart.tsx`
- `resources/js/Pages/Customer/Orders/Index.tsx`
- `resources/js/Pages/Products/Index.tsx`

### O que foi feito

**`font-display` adicionado nas h1 de página faltantes**
- `Cart.tsx`: "Sua Sacola" e `Orders/Index.tsx`: "Meus Pedidos" estavam sem `font-display` (Playfair Display). Todas as outras h1 de página já usavam — essa inconsistência fazia as páginas do cliente parecerem visualmente diferentes das demais.

**Botões primários padronizados para `rounded-full`**
- Cart: CTA do empty state e botão "Finalizar compra" estavam com `rounded-2xl`.
- Orders: CTA do empty state estava com `rounded-2xl`.
- O sistema de design define `rounded-full` para botões primários — `rounded-2xl` é para cards e containers, não para CTAs de ação.

**Capitalização padronizada — Sentence Case**
- "Finalizar Compra" → "Finalizar compra" (Title Case em botão é inadequado para o tom do brand)
- "Nossa Coleção" → "Nossa coleção" (heading de página, não nome próprio)
- A hero mantém "Explorar Coleção" em Title Case por ser o CTA de assinatura da marca — intencional.

**Toasts do Cart alinhados ao vocabulário do brand**
- "Carrinho limpo!" → "Sacola esvaziada." — usa o mesmo termo "sacola" que o restante da interface, e tom mais sereno em vez de exclamativo
- "Erro ao limpar carrinho." → "Não foi possível esvaziar a sacola." — linguagem mais natural e sem a palavra "erro" que soa técnica

**`active:scale-[.98]` → `active:scale-95`**
- Botão "Finalizar compra": o valor `.98` era um off-token arbitrário. O padrão do sistema é `active:scale-95` para feedback de clique em botões primários.

---

## /optimize

### Arquivos alterados
- `resources/js/Pages/Products/Index.tsx`

### O que foi feito

**`FiltersPanel` extraído para fora do componente pai**
- Antes: `FiltersPanel` era uma arrow function declarada dentro de `ProductsIndex`. Em React, isso cria um novo *tipo de componente* a cada render — o que faz React desmontar e remontar os filtros inteiros toda vez que qualquer estado do pai muda (ex: pesquisa, abertura do sidebar). Isso causava perda de foco em inputs e possível flickering.
- Agora: `FiltersPanel` é um `React.memo` declarado fora do componente pai, recebendo todos os dados como props explícitas (`FiltersPanelProps`). React mantém a instância entre renders e só re-renderiza quando alguma prop realmente muda.

**Debounce de busca desacoplado de `applyFilters`**
- Antes: o `useEffect` de debounce dependia de `applyFilters` (um `useCallback` com deps `[search, categoryId, priceMin, priceMax]`). Isso significava que quando categoria ou preço mudavam, `applyFilters` era recriado → o effect disparava → criava um novo timeout que navegava para `/products` — **além** da navegação já feita pelos handlers `handleCategoryChange` e `handlePriceChange`. Resultado: double-navigation ao trocar filtros.
- Agora: o effect de debounce depende só de `[search]`. Outros filtros já chamam `router.get` diretamente nos seus handlers. O `useCallback` `applyFilters` foi removido por completo — era indireção sem benefício.

---

## /clarify

### Arquivos alterados
- `resources/js/Pages/Customer/Cart.tsx`
- `resources/js/Pages/Products/Index.tsx`
- `resources/js/Layouts/PublicLayout.tsx`
- `resources/js/Components/Public/ProductGrid.tsx`

### O que foi feito

**Bugs tipográficos corrigidos**
- `Cart.tsx`: "Explorar Colecao" → "Explorar Coleção" (cedilha faltando — aparecia errado no botão do empty state)
- `Products/Index.tsx`: `title="Colecao"` → `title="Coleção"` (aparecia na aba do browser sem acento)

**Acessibilidade semântica — menu mobile**
- `PublicLayout.tsx`: `aria-label="Abrir menu"` era estático — agora alterna para `"Fechar menu"` quando o menu está aberto, garantindo que leitores de tela anunciem o estado correto do botão

**Tom do dropdown de usuário**
- `PublicLayout.tsx`: "Conectado como" (frio, técnico) → "Olá, {nome}" (quente, pessoal) — alinha com o tom contemplativo e humano do brand Shopsugi

**Badge de filtros ativos**
- `Products/Index.tsx`: badge mostrava `!` (parecia um aviso de erro) → agora mostra o número exato de filtros ativos, informando o usuário de forma clara e objetiva

**Subtítulo do empty state contextual**
- `ProductGrid.tsx`: subtítulo era genérico ("Tente ajustar os filtros ou explorar toda a coleção") independente do contexto — agora varia: com filtros ativos mostra "Nenhuma peça corresponde a esses filtros. Tente ajustá-los."; sem filtros mostra "Em breve novas peças chegam à coleção." — copy que dá direção real ao usuário

---

## /adapt

### Arquivos alterados
- `resources/js/Components/Public/HeroBanner.tsx`
- `resources/js/Pages/Customer/Cart.tsx`
- `resources/js/Pages/Customer/Orders/Index.tsx`
- `resources/js/Pages/Products/Index.tsx`

### O que foi feito

**HeroBanner — h1 menor em mobile**
- `text-4xl` → `text-3xl` em mobile (base). Em 320px, o heading de duas linhas com `whitespace-pre-line` estava ocupando quase toda a viewport. `sm:text-5xl lg:text-6xl` mantidos para telas maiores — a progressão é agora mais suave e respira melhor.

**HeroBanner — stats grid menos apertado em mobile**
- `gap-x-10` → `gap-x-4 sm:gap-x-10`: o gap horizontal fixo de 10 comprimia os três blocos de estatísticas em telas estreitas. Agora o gap é menor em mobile e expande em sm+.

**Cart — header de sacola não colapsa em mobile estreito**
- Adicionado `flex-wrap gap-2` no container e `shrink-0` no botão "Limpar carrinho". Antes, em telas de 320px, o flex sem wrap comprimia o título e o botão juntos — agora o botão cai para a segunda linha se necessário.

**Orders — indentação do card com `pl` em vez de `ml`**
- `ml-14 sm:ml-0` → `pl-14 sm:pl-0`: `margin-left` empurrava o container para fora do card em alguns contextos, enquanto `padding-left` mantém o alinhamento dentro do flex pai sem afetar o layout externo.

**Products — padding vertical responsivo**
- `py-10` → `py-8 sm:py-10`: pequena redução do padding vertical em mobile para maximizar a área visível do grid de produtos, que é o conteúdo principal da página.

---

## /delight

### Arquivos alterados
- `resources/css/app.css`
- `resources/js/Layouts/PublicLayout.tsx`
- `resources/js/Components/Public/HeroBanner.tsx`

### O que foi feito

Estratégia: **subtle sophistication** — delight que se descobre, não que se anuncia. Todos os momentos são opcionais, instantâneos, e respeitam `prefers-reduced-motion`.

**Logo ツ — easter egg de marca**
- O caractere ツ ganha `inline-block` com `group-hover:scale-125 group-hover:rotate-6 group-hover:text-kintsugi-500 transition-all duration-300`
- Usuários que exploram o logo descobrem a assinatura animada — conecta ao conceito Kintsugi sem anunciar

**Nav links — sublinhado editorial**
- Adicionado sublinhado que cresce da esquerda (`after:w-0 hover:after:w-full after:transition-all after:duration-300`)
- Inspirado em design editorial/magazine — muito on-brand para Shopsugi

**Cart badge — pop ao adicionar item**
- Adicionado `key={count}` no span do badge — força remount no React ao mudar o count
- Novo keyframe `badge-pop` (scale 1 → 1.35 → 1, 350ms) registrado no `@theme`
- Feedback satisfatório e imediato quando produto é adicionado ao carrinho

**Hero CTA arrow — slide on hover**
- O `group` no Link CTA + `group-hover:translate-x-1` na seta →
- Padrão premium recorrente: indica direção/ação sem verbosidade
