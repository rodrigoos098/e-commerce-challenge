# Shopsugiツ — Frontend Quality Audit

> Audited on 2026-03-25 | 26 files scanned | Stack: React 19, Inertia v2, Tailwind v4, TypeScript

---

## Anti-Patterns Verdict

**Verdict: Mild AI tells — overall genuine, but a few classic patterns leak through.**

| Tell | Found? | Details |
|---|:---:|---|
| AI color palette (purple gradients) | ❌ | Custom Kintsugi Gold palette is original and well-curated |
| Gradient text | ❌ | Not used anywhere |
| Glassmorphism / backdrop-blur | ⚠️ | Used in HeroBanner badge, mobile overlay, out-of-stock overlay, category badge — acceptable in moderation |
| **Hero metrics / vanity stats** | **✅** | `500+ Artesãos`, `2k+ Peças únicas`, `4.9★` — hard-coded, fabricated numbers. Classic AI slop tell |
| Card grids (uniform) | ⚠️ | `ProductGrid` uses a uniform 4-column grid — functional but predictable |
| Generic fonts | ❌ | DM Sans + Playfair Display is a strong, intentional pairing |
| Bounce easing | ❌ | Uses `cubic-bezier(0.22, 1, 0.36, 1)` — smooth, not bouncy |
| Redundant copy | ❌ | Copy is concise and purposeful |

**The hero stats are the biggest tell.** They scream "AI generated" and should be removed or replaced with real dynamic data.

---

## Executive Summary

| Severity | Count |
|---|:---:|
| 🔴 Critical | 2 |
| 🟠 High | 6 |
| 🟡 Medium | 10 |
| 🔵 Low | 7 |
| **Total** | **25** |

### Top 5 Most Critical Issues

1. **Hard-coded hero metrics** — fabricated data erodes trust and is a clear AI slop tell
2. **Missing `aria-label` on admin mobile buttons** — hamburger/close buttons lack accessible names
3. **Sortable table headers use `<th onClick>` instead of `<button>`** — keyboard inaccessible
4. **User dropdown has no `onClickOutside` hook** — only dismisses via invisible overlay div
5. **No `<main>` landmark or skip-nav in AdminLayout** — screen readers can't jump to content

### Overall Quality Score: **7.2 / 10**

Solid foundation with good design token discipline, proper `prefers-reduced-motion` support, and consistent component architecture. Main gaps are in accessibility edge cases and a few AI-generated content patterns.

---

## Detailed Findings by Severity

### 🔴 Critical Issues

#### 1. Hard-coded fabricated hero metrics
- **Location**: [HeroBanner.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/HeroBanner.tsx#L58-L69)
- **Category**: Anti-Pattern
- **Description**: Stats "500+ Artesãos", "2k+ Peças únicas", "4.9★" are hard-coded fabricated numbers
- **Impact**: Destroys credibility. Users who investigate will find these are fake. Classic AI-generated content pattern
- **Recommendation**: Remove the stats section or replace with real data fetched from the backend (actual product count, actual artisan count, etc.)
- **Suggested command**: Manual fix — remove or wire to real data

#### 2. Sortable `<th>` headers use `onClick` instead of `<button>`
- **Location**: [DataTable.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/DataTable.tsx#L107-L127)
- **Category**: Accessibility
- **WCAG**: 2.1.1 (Keyboard), 4.1.2 (Name, Role, Value)
- **Description**: Sortable columns use `<th onClick>` — a `<th>` is not focusable or keyboard-accessible by default
- **Impact**: Keyboard-only users cannot sort table columns at all
- **Recommendation**: Wrap the label inside a `<button>` within the `<th>`, or add `tabIndex={0}` + `onKeyDown` handler with `role="button"`
- **Suggested command**: `/harden`

---

### 🟠 High-Severity Issues

#### 3. Missing `aria-label` on admin hamburger and close buttons
- **Location**: [AdminLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/AdminLayout.tsx#L272-L292)
- **Category**: Accessibility
- **WCAG**: 4.1.2 (Name, Role, Value)
- **Description**: The mobile hamburger button (line 287) and sidebar close button (line 272) have no `aria-label`
- **Impact**: Screen readers announce these as unlabeled buttons
- **Recommendation**: Add `aria-label="Abrir menu"` and `aria-label="Fechar menu"` respectively

#### 4. Admin logout button uses `title` instead of `aria-label`
- **Location**: [AdminLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/AdminLayout.tsx#L239-L245), line 328
- **Category**: Accessibility
- **WCAG**: 4.1.2 (Name, Role, Value)
- **Description**: Icon-only logout buttons use `title="Sair"` but no `aria-label`. `title` is not reliably announced by all screen readers
- **Impact**: Screen reader users may not know the button's purpose
- **Recommendation**: Add `aria-label="Sair"` alongside or instead of `title`

#### 5. No skip-navigation link
- **Location**: [PublicLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/PublicLayout.tsx), [AdminLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/AdminLayout.tsx)
- **Category**: Accessibility
- **WCAG**: 2.4.1 (Bypass Blocks)
- **Description**: Neither layout provides a "skip to main content" link for keyboard users
- **Impact**: Keyboard users must tab through the entire nav on every page load
- **Recommendation**: Add a visually-hidden skip link as the first focusable element: `<a href="#main-content" className="sr-only focus:not-sr-only ...">Pular para conteúdo</a>`
- **Suggested command**: `/harden`

#### 6. `AdminSearchBar` search input missing `aria-label`
- **Location**: [SearchBar.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/SearchBar.tsx#L69-L75)
- **Category**: Accessibility
- **WCAG**: 1.3.1 (Info and Relationships)
- **Description**: The search `<input>` has no `aria-label` or associated `<label>`
- **Impact**: Screen readers announce it as an unnamed input
- **Recommendation**: Add `aria-label={placeholder}` to mirror what `SearchInput` (Public) does correctly

#### 7. Admin sidebar close button missing `aria-label`
- **Location**: [AdminLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/AdminLayout.tsx#L272-L277)
- **Category**: Accessibility
- **WCAG**: 4.1.2
- **Description**: Close button in sidebar has no accessible name
- **Recommendation**: Add `aria-label="Fechar menu lateral"`

#### 8. User dropdown dismissal relies on invisible overlay
- **Location**: [PublicLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/PublicLayout.tsx#L172-L178)
- **Category**: Accessibility / UX
- **Description**: The dropdown is closed by an invisible `<div className="fixed inset-0">` overlay. No `Escape` key handler; no proper focus management
- **Impact**: Keyboard users can't dismiss the dropdown without clicking elsewhere. Tab order breaks because the overlay is in the way
- **Recommendation**: Add an `Escape` key handler and use a proper `useClickOutside` hook or `onBlur` approach
- **Suggested command**: `/harden`

---

### 🟡 Medium-Severity Issues

#### 9. Duplicated `formatPrice` utility
- **Location**: [ProductCard.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/ProductCard.tsx#L10-L12), [CartItem.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/CartItem.tsx#L13-L15), [PriceFilter.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/PriceFilter.tsx#L11-L13)
- **Category**: Code Quality
- **Description**: `formatPrice()` is defined identically in 3+ files
- **Recommendation**: Extract to a shared `utils/format.ts` module
- **Suggested command**: `/extract`

#### 10. Image `onError` hides image without fallback
- **Location**: [ProductCard.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/ProductCard.tsx#L72), [CartItem.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/CartItem.tsx#L70)
- **Category**: UX / Resilience
- **Description**: `onError={(e) => { e.currentTarget.style.display = 'none'; }}` hides the image entirely, leaving a blank space
- **Impact**: Users see empty boxes with no indication of what product they're looking at
- **Recommendation**: Show a placeholder SVG or generic product icon instead
- **Suggested command**: `/harden`

#### 11. `Spinner` doesn't respect `prefers-reduced-motion`
- **Location**: [Spinner.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Shared/Spinner.tsx#L4)
- **Category**: Accessibility
- **WCAG**: 2.3.3 (Animation from Interactions)
- **Description**: Uses `animate-spin` without `motion-safe:` prefix. Other components correctly use `motion-safe:animate-pulse`
- **Recommendation**: Change to `motion-safe:animate-spin` for consistency
- **Suggested command**: `/normalize`

#### 12. `SidebarContent` defined as component inside render
- **Location**: [AdminLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/AdminLayout.tsx#L186)
- **Category**: Performance
- **Description**: `SidebarContent` is declared as a function component inside `AdminLayout`, causing it to be recreated on every render and losing React's reconciliation optimization
- **Impact**: Unnecessary re-renders of the entire sidebar on any state change
- **Recommendation**: Extract `SidebarContent` to a separate component or use `useMemo`
- **Suggested command**: `/optimize`

#### 13. Inline SVG icons duplicated across files
- **Location**: Multiple files (PublicLayout, AdminLayout, ProductCard, etc.)
- **Category**: Code Quality / Bundle Size
- **Description**: SVG icons (cart, search, close, menu, chevron) are inlined as JSX in multiple files rather than imported from a shared icon set
- **Impact**: Increases bundle size and maintenance burden
- **Recommendation**: Extract to a shared `Components/Icons/` module or adopt `lucide-react`
- **Suggested command**: `/extract`

#### 14. Header search fires navigation on every keystroke (debounced but still navigates)
- **Location**: [PublicLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/PublicLayout.tsx#L84-L91)
- **Category**: Performance / UX
- **Description**: The debounced search fires `router.get('/products', ...)` after 400ms — this causes a full Inertia page visit on every search input, even when not on the products page
- **Impact**: Typing in the search bar from the homepage unexpectedly navigates the user away
- **Recommendation**: Only fire the search if already on `/products`, or navigate to `/products?search=...` on Enter/submit
- **Suggested command**: `/harden`

#### 15. `PriceFilter` doesn't sync when props change
- **Location**: [PriceFilter.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/PriceFilter.tsx#L16-L17)
- **Category**: UX Bug
- **Description**: `localMin`/`localMax` are initialized from props via `useState` but never synced if `currentMin`/`currentMax` props change externally
- **Impact**: If filters reset externally (e.g. "clear all"), the sliders show stale values
- **Recommendation**: Add a `useEffect` to sync state with prop changes, or use a key prop to reset

#### 16. `CategoryFilter` buttons missing `aria-pressed` or `aria-selected`
- **Location**: [CategoryFilter.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/CategoryFilter.tsx#L25-L39)
- **Category**: Accessibility
- **WCAG**: 4.1.2
- **Description**: Filter buttons toggle selection but don't communicate selected state to assistive tech
- **Recommendation**: Add `aria-pressed={isSelected}` to each toggle button

#### 17. `DataTable` pagination links missing `aria-label`
- **Location**: [DataTable.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/DataTable.tsx#L184-L240)
- **Category**: Accessibility
- **WCAG**: 2.4.4 (Link Purpose)
- **Description**: Pagination `<Link>` elements have no `aria-label`. Labels decoded from HTML entities (« Previous, Next ») are announced but individual page numbers lack context
- **Recommendation**: Add `aria-label={`Ir para página ${page}`}` and wrap pagination in `<nav aria-label="Paginação">`

#### 18. `QuantitySelector` input too narrow for 3-digit values
- **Location**: [QuantitySelector.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/QuantitySelector.tsx#L51-L60)
- **Category**: Responsive
- **Description**: Input width is `w-10` (40px), which clips when the max is 999 and the user types 3 digits
- **Recommendation**: Use `w-12` or `min-w-[3ch]` for numbers up to 999

---

### 🔵 Low-Severity Issues

#### 19. `StatusBadge` dot lacks `aria-hidden`
- **Location**: [StatusBadge.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/StatusBadge.tsx#L55)
- **Category**: Accessibility
- **Description**: The decorative colored dot `<span>` is missing `aria-hidden="true"`
- **Recommendation**: Add `aria-hidden="true"` to the dot span

#### 20. `SearchBar` clear button missing `aria-label`
- **Location**: [SearchBar.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/SearchBar.tsx#L79-L88)
- **Category**: Accessibility
- **Description**: The clear button has no accessible name (compare with `SearchInput.tsx` which correctly has `aria-label="Limpar busca"`)
- **Recommendation**: Add `aria-label="Limpar pesquisa"`

#### 21. `Button` component missing focus ring
- **Location**: [Button.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Shared/Button.tsx#L37-L45)
- **Category**: Accessibility
- **WCAG**: 2.4.7 (Focus Visible)
- **Description**: No `focus:ring` or `focus-visible:ring` class — keyboard users can't distinguish focus
- **Recommendation**: Add `focus-visible:ring-2 focus-visible:ring-kintsugi-500 focus-visible:ring-offset-2`

#### 22. `FormField` toggle label not associated
- **Location**: [FormField.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/FormField.tsx#L163-L164)
- **Category**: Accessibility
- **Description**: When `type === 'toggle'`, the label has `htmlFor={undefined}`. Clicking the label text doesn't toggle the switch
- **Recommendation**: Add an `id` to the toggle button and wire `htmlFor` to it, or wrap in a `<label>` element

#### 23. `PublicLayout` duplicates search input (desktop + mobile)
- **Location**: [PublicLayout.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Layouts/PublicLayout.tsx#L130-L143), lines 248-261
- **Category**: Code Quality
- **Description**: Two separate search `<input>` elements with identical logic but slightly different styling
- **Recommendation**: Extract to a `HeaderSearch` component used in both contexts

#### 24. `OrderStatusTimeline` uses `<nav>` for non-navigation content
- **Location**: [OrderStatusTimeline.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Public/OrderStatusTimeline.tsx#L49)
- **Category**: Accessibility / Semantics
- **Description**: The timeline is wrapped in `<nav>` but it's a progress indicator, not navigation. This misuses the landmark
- **Recommendation**: Use `<div role="progressbar">` or a custom `role="group"` with `aria-label`

#### 25. `Modal` uses static `id="modal-title"` — breaks with multiple modals
- **Location**: [Modal.tsx](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/resources/js/Components/Admin/Modal.tsx#L88-L108)
- **Category**: Accessibility
- **Description**: If two modals exist in the DOM (e.g. nested or stacked), `aria-labelledby="modal-title"` will reference the wrong one
- **Recommendation**: Generate a unique ID with `useId()` (React 18+)

---

## Patterns & Systemic Issues

| Pattern | Occurrences | Impact |
|---|:---:|---|
| Missing `aria-label` on icon-only buttons | 5+ | Screen reader users can't identify button purposes |
| Duplicated utilities (`formatPrice`, SVG icons) | 6+ files | Maintenance burden, bundle bloat |
| Inconsistent `motion-safe:` prefix usage | 2 components | `Spinner` animates without respecting reduced-motion while others do |
| `onError` hides images without fallback | 2 components | Users see empty white boxes when images fail to load |
| No focus-visible indicators on custom buttons | 3+ components | Keyboard navigation is invisible |

---

## Positive Findings ✅

| Area | Details |
|---|---|
| **Design tokens** | Excellent discipline — all colors use the `kintsugi-*` and `warm-*` token scales. No hard-coded hex values in components |
| **`prefers-reduced-motion`** | Global `@media` rule properly disables all animations. Most components use `motion-safe:` prefix |
| **Typography system** | `font-display` (Playfair) and `font-sans` (DM Sans) are consistently applied — distinctive and well-paired |
| **Semantic HTML** | Good use of `<article>`, `<section>`, `<nav>`, `<header>`, `<footer>`, `<main>` across layouts |
| **ARIA in Public components** | `ProductCard`, `Pagination`, `QuantitySelector`, `SearchInput` all have proper `aria-label` attributes |
| **Modal focus management** | Focus trap, `Escape` key handler, body scroll lock, and previous focus restoration — well-implemented |
| **Animation system** | Custom keyframes with refined cubic-bezier timing and staggered `animationDelay` — feels polished, not generic |
| **Responsive architecture** | Layouts adapt from mobile to desktop with proper breakpoints (`sm`, `md`, `lg`, `xl`) |
| **Empty states** | `ProductGrid` and `DataTable` have thoughtful empty states with icons and helpful messages |
| **Loading states** | Skeleton loaders, spinner integration, and `aria-busy` on the add-to-cart button |

---

## Recommendations by Priority

### 🔴 Immediate (before next deploy)
1. Remove or replace hard-coded hero metrics with real data
2. Add `aria-label` to all icon-only buttons in AdminLayout
3. Make DataTable sort headers keyboard-accessible (wrap in `<button>`)

### 🟠 Short-term (this sprint)
4. Add skip-navigation link to both layouts
5. Add `Escape` key handler to user dropdown
6. Add `aria-label` to admin SearchBar input
7. Add `focus-visible:ring` to the shared `Button` component
8. Replace image `onError` hide-on-fail with placeholder fallback

### 🟡 Medium-term (next sprint)
9. Extract `formatPrice` to shared utility
10. Extract common SVG icons to shared `Icons/` module
11. Fix `Spinner` to use `motion-safe:animate-spin`
12. Extract `SidebarContent` out of `AdminLayout` render
13. Fix header search to not navigate away from non-product pages
14. Sync `PriceFilter` state with prop changes
15. Add `aria-pressed` to `CategoryFilter` buttons

### 🔵 Long-term (backlog)
16. Generate unique modal IDs with `useId()`
17. Replace `<nav>` in `OrderStatusTimeline` with appropriate role
18. Extract duplicate search input in `PublicLayout`
19. Associate toggle label in `FormField`

---

## Suggested Commands for Fixes

| Command | Addresses |
|---|---|
| `/harden` | Issues #2, #5, #8, #10, #14, #15 — accessibility, resilience, edge cases |
| `/extract` | Issues #9, #13, #23 — duplicated code extraction |
| `/normalize` | Issues #3, #4, #6, #7, #11, #20, #21 — consistency with existing patterns |
| `/polish` | Issues #16, #17, #18, #19, #22, #24, #25 — final detail pass |
| Manual fix | Issue #1 — hero metrics require backend data integration |
| `/optimize` | Issue #12 — component extraction for render performance |
