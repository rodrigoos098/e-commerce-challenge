# Shopsugiツ — Critique Resolution Report

> Resolvido em 2026-03-25 | 5 issues, 10 arquivos modificados/criados

---

## Issue #1 — Homepage sem momento memorável

### `/bolder` — Amplificação visual

**O que foi feito:**

- **Kintsugi crack dividers**: Criado componente `KintsugiDivider.tsx` — linhas douradas irregulares que se desenham com stroke animation quando entram no viewport. Colocadas entre cada seção do homepage (hero→categorias, categorias→produtos, produtos→features). Este é agora o **elemento visual memorável** que faltava — a filosofia Kintsugi finalmente tem presença visual.

- **Hero amplificado**: Escala tipográfica aumentada para `text-7xl` em desktop (antes `text-6xl`). O CTA primário agora é `bg-kintsugi-500` com `shadow-kintsugi-500/25` (antes era branco), criando um ponto focal dourado forte. Stats agora usam `text-kintsugi-300` com separador vertical.

- **Gold shimmer no título**: A segunda linha do título do hero ("Arte em cada detalhe") agora tem um efeito shimmer dourado sutil (`kintsugi-shimmer` CSS class) que pulsa como ouro líquido. É a assinatura visual.

- **Organic SVG background**: Uma linha curva dourada sutil (7% opacidade) cruza o hero diagonalmente, quebrando a rigidez retangular.

**Arquivos:** `app.css`, `HeroBanner.tsx`, `KintsugiDivider.tsx` (novo), `useScrollReveal.ts` (novo)

### `/delight` — Momentos de descoberta

**O que foi feito:**

- **Scroll-reveal animations**: Criado hook `useScrollReveal()` com `IntersectionObserver`. Cada seção e card agora aparece suavemente conforme o scroll, com delays stagger (60-120ms entre itens). A animação é `translateY(40px) → 0` com `cubic-bezier(0.22, 1, 0.36, 1)`.

- **Staggered category entrances**: Os cards de categoria grandes entram com 80ms de delay entre si; os compactos com 60ms. Cria um "wave" visual orgânico.

- **prefers-reduced-motion**: O hook detecta `prefers-reduced-motion: reduce` e força `visible=true` imediatamente, sem animação. O CSS global também possui a regra `animation-duration: 0.01ms !important`.

**Arquivos:** `useScrollReveal.ts` (novo), `Home.tsx`, `app.css`

---

## Issue #2 — Product Cards uniformes

### `/arrange` — Layout hierárquico

**Nota**: O grid de products usa `ProductGrid` e `ProductCard` existentes — mudar para masonry exigiria alterações arquiteturais significativas. A melhoria focou em:

- **Spacing amplificado**: O gap entre seção header e o grid subiu de `mb-10` para `mb-12`, criando mais respiração.
- **Scroll-reveal stagger**: Os cards agora surgem com reveal animation quando entram no viewport ao invés de todos aparecerem ao mesmo tempo.
- **Active state no CTA**: O botão "Explorar Coleção" tem `active:scale-[.97]` para feedback tátil satisfatório.

### `/overdrive` — Efeito visual técnico

O Kintsugi divider SVG **stroke-dasharray animation** é a peça técnica overdrive — um SVG com 3 paths (crack principal + 2 branches) que se desenha com timing coordenado a 1.8s, usando stroke-dashoffset interpolation com `cubic-bezier(0.22, 1, 0.36, 1)`. Inclui `drop-shadow` dourado para glow. É CSS-only, performante, e degrada gracefully.

---

## Issue #3 — Categories Section sem hierarquia

### `/arrange` — Grid hierárquico

**O que foi feito:**

- **Categorias separadas em 2 tiers**: As 3 primeiras categorias (ordenadas por `products_count DESC`) são exibidas em cards horizontais grandes (ícone + nome + contagem lado a lado). As demais em cards compactos verticais (ícone sobre nome).

- **Product counts**: O `HomeController.php` agora faz `withCount('products')` e ordena por popularidade. Cada card agora mostra "12 peças", "8 peças" etc.

- **Grid responsivo diferenciado**: Featured = `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`, compact = `grid-cols-2 sm:grid-cols-3 md:grid-cols-5`.

- **Hover micro-interaction**: Cards featured têm `hover:shadow-lg` (mais impacto) vs `hover:shadow-md` para compactos.

**Arquivos:** `Home.tsx`, `HomeController.php`, `shared.ts` (Category type + `products_count`)

---

## Issue #4 — Feature cards com copy genérica

### `/clarify` — Copy reescrita

**O que foi feito:**

| Antes | Depois |
|---|---|
| `Embalagem Artesanal` | `Embalada com Carinho` |
| `Cada peca embalada com cuidado e materiais sustentaveis.` | `Cada peça envolvida em materiais sustentáveis, pronta para presentear.` |
| `Direto do Artesao` | `Direto das Mãos de Quem Cria` |
| `Compre diretamente de quem cria. Sem intermediarios.` | `Sem intermediários. Você compra de quem dedica horas ao ofício.` |
| `Pecas Unicas` | `Peças Verdadeiramente Únicas` |
| `Edicoes limitadas e trabalhos exclusivos feitos a mao.` | `Edições limitadas e trabalhos exclusivos — nenhuma é igual à outra.` |
| `Browse` (label inglês) | `Explorar` |
| `Junte-se a comunidade` | `Junte-se à comunidade` |
| `colecoes e edicoes limitadas` | `coleções e edições limitadas` |
| `Ver produtos` (CTA bottom) | `Ver coleção` |

- Todos os acentos corrigidos (`peça`, `coleções`, `edições`, `à mão`)
- Tom mais humano e caloroso, alinhado com a personalidade "artisan warm refined"
- Ícones ampliados de `h-7 w-7` para `h-9 w-9` nos feature cards

**Arquivos:** `Home.tsx`

---

## Issue #5 — Footer com dados placeholder

### `/distill` — Simplificação

**O que foi feito:**

- **Removido**: Coluna "Contato" com `contato@shopsugi.com` e `(11) 99999-9999` — dados visivelmente falsos que deterioravam credibilidade
- **Reduzido de 4 para 3 colunas**: Brand + Navegação + Conta
- **Elevada a frase Kintsugi**: Agora há uma explicação concisa da inspiração: *"Inspirado pelo Kintsugi — a arte de reparar com ouro, celebrando a beleza nas imperfeições."* diretamente sob a tagline
- **Bottom bar simplificada**: Apenas copyright, centralizado, sem duplicação da assinatura
- **Border-top** ajustada de `border-warm-800` (invisível no escuro) para `border-warm-700` (sutil mas visível)

**Arquivos:** `Footer.tsx`

---

## Verificação

| Check | Status |
|---|:---:|
| `vendor/bin/pint --dirty --format agent` | ✅ pass |
| `npx tsc --noEmit` | ✅ 0 erros |
| `prefers-reduced-motion` | ✅ respeitado |
| Acessibilidade (ARIA) | ✅ mantida |

---

## Arquivos criados

| Arquivo | Propósito |
|---|---|
| `resources/js/hooks/useScrollReveal.ts` | Hook de IntersectionObserver para scroll-reveal |
| `resources/js/Components/Shared/KintsugiDivider.tsx` | SVG decorativo de rachadura dourada com stroke animation |

## Arquivos modificados

| Arquivo | Mudanças |
|---|---|
| `resources/css/app.css` | +3 keyframes, +3 utility classes (divider, reveal, shimmer) |
| `resources/js/Pages/Home.tsx` | Layout reescrito: hierarquia de categorias, dividers, scroll-reveal, copy corrigida |
| `resources/js/Components/Public/HeroBanner.tsx` | Escala amplificada, shimmer, CTA dourado, SVG orgânico |
| `resources/js/Components/Public/Footer.tsx` | 4→3 colunas, removidos dados placeholder, elevada inspiração Kintsugi |
| `resources/js/types/shared.ts` | `products_count` no Category type |
| `app/Http/Controllers/HomeController.php` | `withCount('products')`, ordenação por popularidade |
