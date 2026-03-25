# Shopsugiツ — Design Critique

> Avaliação em 2026-03-25 | Design Director review | Stack: React 19, Inertia v2, Tailwind v4

---

## Anti-Patterns Verdict

**Veredito: Passa — com ressalvas.** O design escapou das armadilhas mais comuns de AI-generated interfaces.

| AI Tell | Status | Notas |
|---|:---:|---|
| Purple gradient palette | ✅ Limpo | Kintsugi Gold é original, quente e coerente |
| Gradient text | ✅ Limpo | Nenhum uso |
| Glassmorphism excessivo | ⚠️ Leve | `backdrop-blur-sm` em poucos elementos (hero badge, overlays) — aceitável |
| Hero vanity metrics | ✅ Corrigido | Agora usa dados reais do banco (produto/categorias) |
| Identical card grids | ⚠️ | Grid 4-col uniforme funciona mas é previsível |
| Generic fonts (Inter, Roboto) | ✅ Limpo | DM Sans + Playfair Display é uma combinação forte e intencional |
| Cookie-cutter layout | ⚠️ | Hero → Cards → Features → CTA é um padrão muito comum |
| Bouncy animations | ✅ Limpo | `cubic-bezier(0.22, 1, 0.36, 1)` é sofisticado |
| Dark mode glow effects | ✅ Limpo | Light-only, alinhado com a marca |

**O teste**: Se alguém visse este site sem contexto, acreditaria que foi gerado por AI? **Talvez** — não pelas cores ou tipografia (que são ótimas), mas pela **estrutura de página genérica** e pela **falta de um momento visual memorável**. O site é bonito e competente, mas falta aquela assinatura que faz alguém dizer *"uau, que lindo"*.

---

## Overall Impression

**Gut reaction:** Um projeto sólido e bem construído que se leva a sério. O sistema de design é consistente, a paleta de cores é quente e envolvente, e a tipografia transmite premium sem ser pretensioso. Mas falta *alma*. O layout segue um template mental que já vimos centenas de vezes: hero escuro → cards de categoria → grid de produtos → features → CTA → footer. É competente sem ser memorável.

**O que mais funciona:** A coerência visual. Cada componente fala a mesma linguagem de design.

**O que mais incomoda:** O homepage não tem um momento de *delight* — nada que faça o visitante parar, olhar duas vezes, ou sentir que descobriu algo especial. Para uma marca que celebra imperfeições e arte manual, a interface é paradoxalmente... perfeita demais. Limpa demais. Previsível demais.

**Maior oportunidade:** Introduzir **uma imperfeição intencional** — algo orgânico, inesperado, que quebre a simetria e comunique que este é um lugar de artesãos, não um template de e-commerce.

---

## What's Working

### 1. Sistema de Cores — Genuinamente Único
O sistema Kintsugi Gold (`#D4A017`) + Warm Neutrals é a melhor decisão de design do projeto. É raro ver uma paleta personalizada tão bem calibrada em projetos deste porte. Os 10 tons de `kintsugi-*` e `warm-*` formam uma linguagem completa que transmite exatamente "artesanal sofisticado". As cores nunca brigam entre si — cada tom tem um propósito claro na hierarquia.

### 2. Tipografia — Intenção Clara
DM Sans para corpo + Playfair Display para headings é um par que funciona. O DM Sans é moderno sem ser genérico (diferença sutil mas importante vs Inter/Roboto). O Playfair nos headings adiciona uma elegância editorial sem ser excessivo. Os pesos e tamanhos são bem calibrados: `text-3xl font-extrabold` para títulos de seção, `text-sm font-medium` para auxiliares. A hierarquia é imediatamente legível.

### 3. Empty States — Surpreendentemente Bons
O empty state do `ProductGrid` é um exemplo que muitos projetos maiores não conseguem acertar: ícone flutuante com `animate-float`, mensagem contextual diferenciada entre "nenhum filtro ativo" vs "filtros ativos sem resultado", e um CTA secundário para limpar filtros. É funcional, orientador, e visualmente coerente.

---

## Priority Issues

### 🔴 1. Homepage sem momento memorável — "The Scroll Test"

**O que:** O homepage segue a ordem Hero → Categorias → Produtos → Features → CTA sem nenhum elemento visual que quebre o ritmo. Todas as seções têm espaçamento simétrico, cantos arredondados, e a mesma cadência visual. É uma página que o visitante scrolla em 5 segundos sem se lembrar de nada.

**Por que importa:** Para uma marca de artesanato que celebra *imperfeição* e *unicidade*, a homepage é ironicamente genérica. O visitante não sente a artesanalidade — sente "outro site bonito". A taxa de conversão da primeira visita depende de um momento de encantamento que não existe aqui.

**Fix:**
- Introduzir um **elemento de textura orgânica** — uma borda irregular, um traço de tinta dourada no hero, uma divisória entre seções que pareça pincelada e não uma linha reta
- Na seção de categorias, quebrar o grid uniforme. Algumas categorias maiores que outras — criar **hierarquia visual entre categorias** (uma featured, as outras menores)
- Adicionar **um detalhe Kintsugi visual** — uma rachadura dourada decorativa como separator entre seções, referenciando diretamente a inspiração da marca

**Command:** `/bolder` seguido de `/delight`

---

### 🟠 2. Product Cards — funcional mas forgettable

**O que:** Os product cards são bem construídos tecnicamente (hover scale, badge de stock, loading state), mas são visualmente idênticos. Todos têm o mesmo ratio de imagem, mesma borda, mesmo padding, mesma shadow. Não há variação, não há surpresa.

**Por que importa:** Em um marketplace artesanal, cada peça deveria sentir-se única. Quando todos os cards parecem iguais, subconscientemente comunicamos "produção em massa" — exatamente o oposto da proposta da marca.

**Fix:**
- Considerar um **masonry layout** (Pinterest-style) onde imagens de diferentes aspectos criam ritmo visual orgânico
- Ou, na abordagem mais conservadora: adicionar **variação sutil** — uma "featured" tag visual diferente para certos produtos, ou uma alternância leve de estilos (ex: a cada 5° card, um estilo ligeiramente maior)
- O badge "Feito à Mão" é bom — mas *todos* os produtos têm ele, o que o torna redundante. Se tudo é feito à mão, nada se destaca

**Command:** `/arrange` seguido de `/overdrive`

---

### 🟠 3. Categories Section — Grid uniforme mata a descoberta

**O que:** As 8 categorias são apresentadas em um grid `grid-cols-6` perfeitamente uniforme, com ícones monocromáticos idênticos em tamanho e tratamento. Cada card tem exatamente o mesmo peso visual.

**Por que importa:** Quando todas as categorias competem igualmente pela atenção, nenhuma ganha. O olho não sabe onde ir. Um marketplace saudável tem categorias "âncora" (as mais populares) e categorias de nicho — essa hierarquia não é comunicada visualmente.

**Fix:**
- **Hierarquizar visualmente**: as 2-3 categorias mais populares maiores (`col-span-2`), as demais menores
- Substituir ícones genéricos SVG por **ilustrações ou fotos representativas** — um ícone de um vaso cerâmico diz menos que uma foto real de cerâmica artesanal
- Adicionar **contagem de produtos** em cada categoria para dar peso numérico (ex: "Cerâmicas · 12 peças")

**Command:** `/arrange`

---

### 🟡 4. Feature cards — texto genérico, disposição previsível

**O que:** A seção "Por que escolher Shopsugiツ?" tem 3 cards centrados com ícone → título → descrição. Copy é genérica: "Embalagem Artesanal", "Direto do Artesão", "Peças Únicas". Além disso, os textos têm acentos faltando: "peca" em vez de "peça", "colecoes" vs "coleções".

**Por que importa:** Esses value props são o momento onde a marca deveria criar conexão emocional. "Cada peça embalada com cuidado e materiais sustentáveis" é informativo mas não inspira. Além disso, acentos faltando em um site em português é um deslize de credibilidade.

**Fix:**
- Reescrever a copy com mais personalidade: em vez de "Direto do Artesão", algo como "Direto das mãos de quem cria" — mais humano, mais caloroso
- Corrigir todos os acentos (peca→peça, colecoes→coleções, edicoes→edições)
- Considerar layout horizontal ao invés de vertical — uma faixa com 3 colunas lado-a-lado é um padrão visual tão batido quanto o hero com 3 features abaixo

**Command:** `/clarify`

---

### 🟡 5. Footer — Separa mal informações dummy de reais

**O que:** O footer exibe `contato@shopsugi.com` e `(11) 99999-9999` — claramente dados placeholder. Existe um "Feito com amor, inspirado pelo Kintsugi" que é uma assinatura bonita, mas fica perdida num rodapé visualmente denso.

**Por que importa:** Dados visivelmente falsos no footer deterioram a perceção de profissionalismo. Se este é um projeto para portfolio/challenge, melhor omitir que inventar.

**Fix:**
- Remover ou marcar claramente dados de contato placeholder
- Elevar a frase "Feito com amor, inspirado pelo Kintsugi" — poderia ser um elemento visual mais proeminente, talvez com o ideograma 金継ぎ decorativo
- Considerar um footer mais compact — 4 colunas para um e-commerce deste porte pode ser over-engineered

**Command:** `/distill`

---

## Minor Observations

| Observation | Impacto | Sugestão |
|---|---|---|
| Copy "Browse" em inglês na seção de categorias (`Home.tsx:78`) | Inconsistência idiomática | Traduzir para "Explorar" ou remover |
| Textos de features sem acentos (`Embala**gem** Artesan**al**` está ok, mas `Pecas Unicas` → `Peças Únicas`) | Credibilidade | Corrigir todos os acentos em `Home.tsx` FEATURES |
| CTA "Criar conta grátis" aparece **3 vezes** na homepage (hero, CTA bottom, footer) | Repetição excessiva | Manter apenas 2 — hero + CTA bottom |
| O hero CTA primário ("Explorar Coleção") é branco sobre dourado — pode troca de cor funcionar melhor com o inverso? | Contraste do CTA | Testar com kintsugi-600 bg + white text para o botão primário |
| Login page usa `bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100` — o **único** gradiente de página inteira do site | Inconsistência | Não é ruim, mas é notável como exceção ao estilo flat do resto |
| Feature icons (`h-7 w-7`) são pequenos relativos ao card (`p-8`) | Proporção | Subir para `h-9 w-9` ou `h-10 w-10` para melhor peso visual |

---

## Questions to Consider

1. **E se o homepage não tivesse estrutura de seções?** Um scroll fluido com transições orgânicas entre conteúdos — inspirado em como uma galeria de arte guia o visitante sem cortes abruptos.

2. **Este site precisava de um hero banner escuro?** A marca é sobre "warmth" — o contraste warm-800/900 no hero comunica *sofisticação*, mas também *distância*. E se o hero fosse light, com a mesma tipografia bold, mas num tom cream/parchment que fizesse o visitante se sentir imediatamente acolhido?

3. **Onde está o Kintsugi no design?** A inspiração está descrita no `.impeccable.md`, mas visualmente, onde estão as "rachaduras douradas"? Nenhum elemento visual referencia diretamente a estética Kintsugi. E se houvesse uma textura decorativa sutil — linhas douradas irregulares como separadores — que trouxesse a filosofia para a superfície visual?

4. **Os product cards precisam de bordas e sombras?** Em muitos e-commerces artesanais premium, os cards são borderless — a imagem do produto "flutua" sobre o fundo claro, criando uma sensação de galeria. As bordas e sombras atuais (por menores que sejam) adicionam "peso visual" que pode estar competindo com as imagens dos produtos.

5. **E se houvesse som?** Um som sutil e elegante ao adicionar ao carrinho (tipo um "clink" de cerâmica) — não é comum em e-commerce, mas para uma marca de artesanato, poderia ser o detalhe memorável que falta.

---

## Scores by Dimension

| Dimension | Score | Notes |
|---|:---:|---|
| AI Slop Detection | 7/10 | Paleta e tipografia escapam; estrutura de página não |
| Visual Hierarchy | 7/10 | Funcional mas sem surpresas — olho flui mas não para em nada |
| Information Architecture | 8/10 | Filtros bem organizados, navegação clara, breadcrumbs no admin |
| Emotional Resonance | 5/10 | **Ponto fraco.** Bonito mas não emociona. Falta a "alma artesanal" |
| Discoverability & Affordance | 8/10 | Interactive elements são claros; hover states bem implementados |
| Composition & Balance | 6/10 | Tudo simétrico, tudo uniforme — funciona mas é safe |
| Typography as Communication | 8.5/10 | Excelente hierarquia e pair. Um dos pontos fortes |
| Color with Purpose | 9/10 | **Melhor aspecto.** Kintsugi Gold é usado com intenção e precisão |
| States & Edge Cases | 8/10 | Empty, loading, error states são todos thoughtful |
| Microcopy & Voice | 6/10 | Funcional mas genérica; acentos faltando; "Browse" em inglês |

**Score geral: 7.3/10** — Acima da média. Sólido na mecânica, fraco na emoção.

---

## Resumo Executivo

O Shopsugiツ é um projeto **tecnicamente maduro** com um sistema de design coerente e raro em projetos deste porte. A paleta Kintsugi Gold e a tipografia DM Sans/Playfair são escolhas genuinamente boas que diferenciam o projeto da massa de AI-generated e-commerce.

O que impede o projeto de ser **excelente** é a distância entre a promessa da marca ("Beleza nas imperfeições") e a execução visual (perfeição simétrica). O layout é um template mental que todos já vimos. A oportunidade está em **injetar o espírito artesanal na superfície visual** — texturas orgânicas, layouts assimétricos intencionais, e um momento de delight no scroll que faça o visitante sentir que entrou numa galeria, não num site de e-commerce.

**Próximos passos recomendados:**

1. `/bolder` — Amplificar o visual da homepage com elementos Kintsugi
2. `/arrange` — Quebrar a uniformidade do grid de categorias e produtos
3. `/clarify` — Reescrever copy das features e corrigir acentos
4. `/delight` — Adicionar um momento de *joy* memorável ao fluxo
