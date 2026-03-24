# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Redis (Memurai no Windows)

O projeto usa `CACHE_STORE=redis` por padrão (obrigatório para cache tags). Localmente no Windows, o Redis roda via **Memurai**, instalado como serviço do Windows — ele sobe automaticamente com o sistema, sem precisar de comando manual.

Para verificar se está ativo:
```bash
netstat -an | findstr :6379
```

Se a porta 6379 aparecer como `LISTENING`, o Memurai está rodando. Caso contrário, inicie pelo `services.msc` (procurar "Memurai").

---

## Commands

```bash
# Setup (first time)
composer run setup

# Development (serves Laravel + queue worker + Vite in parallel)
composer run dev

# Tests
php artisan test --compact
php artisan test --compact tests/Feature/Api/V1/ProductApiTest.php
php artisan test --compact --filter=testCreateProduct
composer run test   # clears config cache first

# Code quality
vendor/bin/pint --dirty --format agent   # PHP formatter (Laravel Pint)
npm run type-check
npm run lint

# Regenerate Swagger docs
php artisan l5-swagger:generate
# Docs available at http://localhost:8000/api/documentation
```

## Architecture

**Stack**: Laravel 12, Inertia.js v2, React 19, TypeScript, Sanctum, Spatie Permission, Redis, PHPUnit.

### Layered backend

- `app/Services/` — business logic (ProductService, OrderService, CartService, StockService, etc.)
- `app/Repositories/` — data access; each has a contract in `Contracts/` bound in `AppServiceProvider`
- `app/DTOs/` — data transfer between layers (ProductDTO, OrderDTO, CartItemDTO, StockMovementDTO)
- `app/Http/Requests/Api/V1/` — API form requests; `app/Http/Requests/Web/` — web form requests
- `app/Http/Resources/Api/V1/` — JSON response wrappers
- `app/Traits/ApiResponseTrait.php` — shared `success()`/`error()` response helpers used by all API controllers

### Routes

- `routes/api.php` — all REST endpoints under `/api/v1`; public, `auth:sanctum`, and `role:admin` middleware groups
- `routes/web.php` — Inertia page routes served by dedicated Page controllers (`HomeController`, `ProductPageController`, `Admin/*Controller`, etc.)

### Frontend

- `resources/js/Pages/` — Inertia pages organized as `Admin/`, `Customer/`, `Products/`, `Auth/`
- `resources/js/Layouts/` — `PublicLayout.tsx` and `AdminLayout.tsx`
- `resources/js/Components/Public/` and `Components/Admin/` — domain-split components
- `resources/js/types/shared.ts`, `public.ts`, `admin.ts` — TypeScript contracts shared across pages

### Auth & authorization

- Sanctum API tokens for the REST API
- Spatie Permission roles: `admin` and `customer`
- `app/Policies/` — `ProductPolicy`, `OrderPolicy`; registered automatically via model discovery

### Cache

- Products: `Cache::tags(['products'])`, TTL 1 hour
- Categories: `Cache::tags(['categories'])`, TTL 24 hours
- Redis is required for cache tag support (`CACHE_STORE=redis`)

### Orders & stock

- Order creation and stock decrement run in a single DB transaction — no async step for stock
- `stock_movements` records are written during order creation
- `OrderCreated` event → `ProcessOrderListener` → dispatches `SendOrderConfirmationEmail` job (async, queue required)

### Seeded credentials

| Role     | Email                   | Password |
|----------|-------------------------|----------|
| Admin    | admin@example.com       | password |
| Customer | customer@example.com    | password |

### API response envelope

```json
// Success
{ "success": true, "data": {} }

// Paginated list
{ "success": true, "data": [], "meta": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7 }, "links": {} }

// Error
{ "success": false, "message": "...", "errors": { "field": ["..."] } }
```

## Design Context

Full design guidelines live in `.impeccable.md` at the project root. Key points:

- **Brand**: Shopsugi — inspired by Kintsugi. Personality: sofisticada, curada, poetica.
- **Audience**: Women 28-45, decoration enthusiasts seeking unique handcrafted pieces.
- **Fonts**: Playfair Display (`font-display`) for headings, DM Sans (`font-sans`) for body/UI.
- **Colors**: Kintsugi gold (`kintsugi-500: #D4A017`) as primary accent; warm neutrals for surfaces/text; `warm-50` page bg; `cream`/`parchment` for auth pages.
- **Components**: `rounded-2xl` cards, `rounded-full` buttons, `border-warm-200` borders, `shadow-sm` resting states, inline SVG icons (no icon library).
- **Theme**: Light mode only. No dark mode.
- **Accessibility**: WCAG 2.1 AA. Keyboard navigation, visible focus rings, semantic HTML, meaningful alt text.
- **Anti-patterns**: No neon colors, no aggressive CTAs, no countdown timers, no sterile gray-on-white. The interface should feel like a quiet atelier, not a loud marketplace.
