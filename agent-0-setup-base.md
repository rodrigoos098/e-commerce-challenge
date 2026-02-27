# ⚙️ Fase 0 — Setup Base (Pré-requisito)

## Contexto

Esta fase cria a **fundação compartilhada** que todos os 5 agentes paralelos precisam. Deve ser executada **inteiramente antes** de iniciar qualquer agente.

> **Leia o plano completo:** [implementation_plan.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/AGENTS.md)

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-fase-0.md` (raiz do projeto).
2. **Marque o checkbox no [task.md](file:///C:/Users/rodrigo.santos/.gemini/antigravity/brain/e3e17065-da6c-472d-b9c6-74d37305cf22/task.md)** ao concluir cada item da seção "Fase 0 — Setup Base".
3. **Siga as convenções do AGENTS.md** — use `php artisan make:*`, `--no-interaction`, etc.
4. **Rode Pint** após cada grupo de arquivos PHP: `vendor/bin/pint --dirty --format agent`

---

## Passo a Passo

### 1. Instalar dependências do Composer
```bash
composer require laravel/sanctum spatie/laravel-permission darkaonline/l5-swagger
composer require --dev laravel/telescope
```
**Marcar:** `[x] Instalar dependências (Composer + NPM)`

### 2. Instalar dependências do NPM
```bash
npm install react react-dom @inertiajs/react @types/react @types/react-dom
npm install -D typescript @vitejs/plugin-react
npm install react-hot-toast react-hook-form @hookform/resolvers zod
npm install @tanstack/react-query axios
```

### 3. Configurar [.env](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/.env)
Editar [.env](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/.env) com:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
CACHE_STORE=file
```
Gerar chave:
```bash
php artisan key:generate
```
**Marcar:** `[x] Configurar .env e gerar chave`

### 4. Configurar Sanctum
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction
```
**Marcar (parcial):** parte de `[x] Configurar Sanctum e Spatie Permission`

### 5. Configurar Spatie Permission
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
```

### 6. Criar estrutura de pastas
Criar os seguintes diretórios:
```
app/DTOs/
app/Repositories/Contracts/
app/Services/
app/Events/
app/Listeners/
app/Jobs/
app/Rules/
app/Http/Controllers/Api/V1/
app/Http/Requests/
app/Http/Resources/
app/Policies/
```
**Marcar:** `[x] Criar estrutura de pastas`

### 7. Criar Models base com Migrations
Use `php artisan make:model` com flags `-mf`:
```bash
php artisan make:model Product -mf --no-interaction
php artisan make:model Category -mf --no-interaction
php artisan make:model Tag -mf --no-interaction
php artisan make:model Order -mf --no-interaction
php artisan make:model OrderItem -mf --no-interaction
php artisan make:model StockMovement -mf --no-interaction
php artisan make:model Cart -mf --no-interaction
php artisan make:model CartItem -mf --no-interaction
```

Adicionar aos models:
- `$fillable` com todos os campos do desafio
- Relacionamentos conforme especificado
- `SoftDeletes` no Product
- Scopes `active()`, `inStock()`, `lowStock()` no Product

Configurar migrations com todos os campos, tipos e índices. Criar pivot `product_tag`.

> [!IMPORTANT]
> **Os Models devem ser criados COMPLETOS aqui** (com `$fillable`, relacionamentos, scopes, casts). O Agente 1 **NÃO vai recriar os Models** — ele começa diretamente nos Repositories (Etapa 2 do seu plano).

Rodar:
```bash
php artisan migrate --no-interaction
```
**Marcar:** `[x] Criar Models base com migrations`

### 8. Configurar [bootstrap/app.php](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/bootstrap/app.php)
- Adicionar middleware Sanctum
- Configurar rate limiting (100 req/min)
- Registrar arquivo `routes/api.php`

**Marcar:** `[x] Configurar bootstrap/app.php`

### 9. Configurar Vite + React + Inertia

Atualizar [vite.config.js](file:///c:/Users/rodrigo.santos/Documents/personal/e-commerce-challenge/vite.config.js):
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
});
```

**Marcar:** `[x] Configurar Vite + React + Inertia`

### 10. Criar Layout base Inertia

**`resources/js/app.tsx`:**
```tsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

**Marcar:** `[x] Criar layout base Inertia`

### 11. Criar TypeScript Types compartilhados

Criar `resources/js/types/shared.ts` com as interfaces comuns que serão reutilizadas pelos Agentes 3 e 4:

```typescript
// Types compartilhados entre Admin e Public
export interface Product {
  id: number; name: string; slug: string; description: string;
  price: number; cost_price?: number; quantity: number; min_quantity: number;
  active: boolean; category: Category; tags: Tag[];
  created_at: string; updated_at: string;
}
export interface Category {
  id: number; name: string; slug: string; description?: string;
  parent_id: number | null; active: boolean; children?: Category[];
}
export interface Tag { id: number; name: string; slug: string; }
export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
export interface Order {
  id: number; user_id: number; status: OrderStatus; total: number;
  subtotal: number; tax: number; shipping_cost: number;
  items: OrderItem[]; shipping_address?: string; billing_address?: string;
  notes?: string; created_at: string;
}
export interface OrderItem {
  id: number; product: Product; quantity: number;
  unit_price: number; total_price: number;
}
export interface User { id: number; name: string; email: string; }
export interface PaginatedResponse<T> {
  data: T[];
  meta: { current_page: number; per_page: number; total: number; last_page: number; };
  links: { first: string; last: string; prev: string | null; next: string | null; };
}
```

> [!IMPORTANT]
> Os Agentes 3 e 4 devem **importar de `@/types/shared`** em vez de redefinir essas interfaces. Cada agente pode estender com types específicos no seu próprio arquivo (`admin.ts` ou `public.ts`).

### 12. Criar componente SkeletonLoader compartilhado

Criar `resources/js/Components/Shared/SkeletonLoader.tsx` — componente reutilizável pelos Agentes 3 e 4:

```tsx
import React from 'react';

interface SkeletonLoaderProps {
  type: 'card' | 'table' | 'form' | 'text' | 'avatar';
  count?: number;
}

export default function SkeletonLoader({ type, count = 1 }: SkeletonLoaderProps) {
  // Implementar skeleton genérico com animação pulse
  // Os agentes 3 e 4 podem importar este componente
}
```

### 13. Criar rotas web stub para desenvolvimento

Criar rotas básicas em `routes/web.php` para que os Agentes 3 e 4 possam testar suas páginas visualmente durante o desenvolvimento:

```php
// Stubs — serão substituídas na Fase de Integração com dados reais
Route::get('/', fn() => Inertia::render('Home'));
Route::get('/products', fn() => Inertia::render('Products/Index'));
Route::get('/products/{slug}', fn() => Inertia::render('Products/Show'));
Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
Route::get('/register', fn() => Inertia::render('Auth/Register'));

Route::middleware('auth')->group(function () {
    Route::get('/cart', fn() => Inertia::render('Customer/Cart'));
    Route::get('/checkout', fn() => Inertia::render('Customer/Checkout'));
    Route::get('/orders', fn() => Inertia::render('Customer/Orders/Index'));
    Route::get('/profile', fn() => Inertia::render('Customer/Profile'));
});

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Admin/Dashboard'));
    Route::get('/products', fn() => Inertia::render('Admin/Products/Index'));
    Route::get('/products/create', fn() => Inertia::render('Admin/Products/Create'));
    Route::get('/categories', fn() => Inertia::render('Admin/Categories/Index'));
    Route::get('/orders', fn() => Inertia::render('Admin/Orders/Index'));
    Route::get('/stock/low', fn() => Inertia::render('Admin/Stock/LowStock'));
});
```

> [!NOTE]
> Essas rotas são stubs sem dados. Na Fase de Integração (`agent-integration.md`), serão substituídas por controllers Inertia que passam dados reais do backend.

---

## Regras de Ownership de Arquivos

> [!CAUTION]
> Para evitar conflitos entre agentes trabalhando em paralelo, respeite estas regras:

| Arquivo/Diretório | Owner exclusivo | Outros agentes |
|---|---|---|
| `app/Providers/AppServiceProvider.php` | **Agente 1** | Não tocar |
| `routes/api.php` | **Agente 1** | Não tocar |
| `routes/web.php` | **Fase 0 / Integração** | Não tocar durante Fase 1 |
| `resources/js/types/shared.ts` | **Fase 0** | Importar, não modificar |
| `resources/js/Components/Admin/` | **Agente 3** | Ninguém mais toca |
| `resources/js/Components/Public/` | **Agente 4** | Ninguém mais toca |
| `resources/js/Components/Shared/` | **Fase 0** | Importar, não modificar |
| `config/logging.php` | **Agente 5** | Ninguém mais toca |
| `tsconfig.json`, `.eslintrc.json`, `.prettierrc` | **Agente 5** | Ninguém mais toca |
| `tests/` | **Agente 2** | Ninguém mais toca |

### 14. Verificação
```bash
php artisan migrate:status
npm run build
```

### 15. Solicitar commit ao humano

> [!IMPORTANT]
> **NÃO faça commits.** Pause a execução e solicite ao humano que revise e faça o commit manualmente.
> Mensagem sugerida: `"Fase 0 concluída. Sugestão de commit: feat: project base setup with architecture scaffolding"`
