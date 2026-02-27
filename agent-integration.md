# 🔗 Fase 2 — Integração (Pós Agentes Paralelos)

## Contexto

Esta fase conecta o trabalho dos 5 agentes paralelos. Deve ser executada **somente após** todos os agentes completarem suas tarefas na Fase 1.

> **Leia o plano completo:** [implementation_plan.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/implementation_plan.md)

---

## Regras de Trabalho

1. **Documente tudo** no arquivo `progress-integration.md` (raiz do projeto).
2. **Marque o checkbox no [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)** ao concluir cada item da seção "Fase 2 — Integração".
3. **Rode Pint** após modificar PHP: `vendor/bin/pint --dirty --format agent`

---

## Passo a Passo

### 1. Criar Rotas Web Inertia ([routes/web.php](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/routes/web.php))

Conectar as páginas React com rotas Laravel. Criar controllers Inertia que passam dados do backend para o frontend:

```php
// Páginas públicas
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductPageController::class, 'show'])->name('products.show');

// Auth
Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::get('/register', [AuthPageController::class, 'register'])->name('register');

// Customer (autenticado)
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartPageController::class, 'index'])->name('cart');
    Route::get('/checkout', [CheckoutPageController::class, 'index'])->name('checkout');
    Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderPageController::class, 'show'])->name('orders.show');
    Route::get('/profile', [ProfilePageController::class, 'index'])->name('profile');
});

// Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('/products', AdminProductController::class)->names('admin.products');
    Route::resource('/categories', AdminCategoryController::class)->names('admin.categories');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/stock/low', [AdminStockController::class, 'lowStock'])->name('admin.stock.low');
});
```

**Marcar:** `[x] Conectar rotas web Inertia com controllers`

### 2. Criar Page Controllers (Inertia)

Estes controllers usam `Inertia::render()` para passar dados do backend para componentes React:

```php
// app/Http/Controllers/HomeController.php
class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home', [
            'featuredProducts' => ProductResource::collection(
                Product::active()->inStock()->latest()->take(8)->get()
            ),
            'categories' => CategoryResource::collection(
                Category::whereNull('parent_id')->with('children')->get()
            ),
        ]);
    }
}
```

Repetir para todos os controllers de página.

**Marcar:** parte de `[x] Conectar admin frontend com API` e `[x] Conectar público frontend com API`

### 3. Ajustar Autenticação Sanctum + Inertia

Para SPAs com Inertia, Sanctum usa autenticação baseada em cookies (não tokens):

1. Verificar `config/sanctum.php` → `stateful` domains incluem localhost
2. Verificar middleware em [bootstrap/app.php](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/bootstrap/app.php)
3. Testar login/logout em ambos os frontends
4. Garantir que CSRF token é enviado

**Marcar:** `[x] Ajustar autenticação Sanctum + Inertia`

### 4. Substituir Mocks por Dados Reais

Nos componentes React dos Agentes 3 e 4, substituir dados mockados pelas props recebidas via Inertia:

```tsx
// Antes (mock)
const products = mockProducts;

// Depois (Inertia)
import { usePage } from '@inertiajs/react';
const { featuredProducts } = usePage().props;
```

### 5. Rodar Seeders
```bash
php artisan migrate:fresh --seed
```
**Marcar:** `[x] Rodar seeders`

### 6. Suíte de Testes Completa
```bash
php artisan test --compact
```
Corrigir quaisquer falhas.
**Marcar:** `[x] Suíte de testes completa`

### 7. Verificar Cobertura
```bash
php artisan test --coverage --min=80
```
**Marcar:** `[x] Verificar cobertura ≥80%`

### 8. Verificação Final
```bash
# PHP formatting
vendor/bin/pint --dirty --format agent

# JS/TS formatting
npx eslint resources/js/ --ext .ts,.tsx --fix
npx prettier --write resources/js/

# TypeScript check
npx tsc --noEmit

# Build
npm run build

# Swagger
php artisan l5-swagger:generate
```

### 9. Solicitar commit ao humano

> [!IMPORTANT]
> **NÃO faça commits.** Pause a execução e solicite ao humano que revise e faça o commit manualmente.
> Mensagem sugerida: `"Integração concluída. Sugestão de commit: feat: integrate all modules, connect frontend to backend"`
