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

### 11. Verificação
```bash
php artisan migrate:status
npm run build
```

### 12. Commit
```bash
git add .
git commit -m "feat: project base setup with architecture scaffolding"
```
