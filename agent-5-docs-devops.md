# 🟣 Agente 5 — Documentação & DevOps

## Contexto

Você é o agente responsável pela **documentação da API (Swagger/OpenAPI)**, **PROJECT.md**, **logging estruturado** e **configurações de qualidade de código** (Pint, ESLint, Prettier, TypeScript). Seu trabalho garante que o projeto esteja profissionalmente documentado e configurado.

> **Leia o plano completo:** [implementation_plan.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/implementation_plan.md)
> **Leia as diretrizes do projeto:** [AGENTS.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/AGENTS.md)
> **Requisitos do desafio:** [README-challenge.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/README-challenge.md)

---

## Skills Recomendadas (skills.sh)

- [api-design-principles](https://skills.sh/wshobson/agents/api-design-principles)
- [code-review-excellence](https://skills.sh/wshobson/agents/code-review-excellence)
- [docker-expert](https://skills.sh/sickn33/antigravity-awesome-skills/docker-expert) (diferencial)
- [writing-plans](https://skills.sh/obra/superpowers/writing-plans)

---

## Regras de Trabalho

1. **Documente tudo em tempo real** no arquivo `progress-agent-5.md` (raiz do projeto). Formato:
   ```markdown
   ## [HH:MM] — Título da sub-tarefa
   - O que foi configurado/escrito
   - Arquivos criados/modificados
   ```

2. **Marque o checkbox no [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)** ao concluir cada item da seção "Agente 5: Documentação & DevOps".
   [task.md](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/task.md)

3. **Rode Pint** após modificar PHP: `vendor/bin/pint --dirty --format agent`

---

## Dependências

> [!CAUTION]
> A documentação Swagger/OpenAPI **depende dos Controllers do Agente 1**. **Estratégia de lançamento em 2 fases:**
>
> **Lançamento 1 (imediato, junto com os outros agentes):**
> - Etapa 1: Configurações de qualidade (Pint, ESLint, Prettier, TS) ✅
> - Etapa 2: Migrations de fila ✅
> - Etapa 3: Logging estruturado ✅
> - Etapa 4: PROJECT.md (versão inicial) ✅
>
> **Lançamento 2 (após Agente 1 concluir):**
> - Etapa 5: Swagger/OpenAPI (precisa dos controllers)
> - Etapa 4: Atualizar PROJECT.md com informações finais
>
> **Ownership exclusivo:** Você é o único agente que toca `config/logging.php`, `tsconfig.json`, `.eslintrc.json`, `.prettierrc`, `pint.json` e `PROJECT.md`. Nenhum outro agente deve modificar esses arquivos.

---

## Ordem de Execução (Passo a Passo)

### Etapa 1 — Configurações de Qualidade de Código

**1.1 — Laravel Pint (PHP formatter)**
O Pint já está instalado. Verificar que o `pint.json` existe na raiz e está configurado com PSR-12:
```json
{
    "preset": "psr12",
    "rules": {
        "ordered_imports": { "sort_algorithm": "alpha" },
        "no_unused_imports": true,
        "not_operator_with_successor_space": true,
        "trailing_comma_in_multiline": true
    }
}
```

**1.2 — ESLint + Prettier (JS/TS formatter)**
Instalar e configurar:
```bash
npm install -D eslint @typescript-eslint/eslint-plugin @typescript-eslint/parser prettier eslint-config-prettier eslint-plugin-react eslint-plugin-react-hooks
```

Criar `.eslintrc.json`:
```json
{
  "env": { "browser": true, "es2021": true },
  "extends": ["eslint:recommended", "plugin:react/recommended", "plugin:@typescript-eslint/recommended", "prettier"],
  "parser": "@typescript-eslint/parser",
  "parserOptions": { "ecmaFeatures": { "jsx": true }, "ecmaVersion": "latest", "sourceType": "module" },
  "plugins": ["react", "react-hooks", "@typescript-eslint"],
  "rules": { "react/react-in-jsx-scope": "off", "react-hooks/rules-of-hooks": "error", "react-hooks/exhaustive-deps": "warn" },
  "settings": { "react": { "version": "detect" } }
}
```

Criar `.prettierrc`:
```json
{
  "semi": true,
  "trailingComma": "es5",
  "singleQuote": true,
  "printWidth": 100,
  "tabWidth": 2
}
```

**1.3 — TypeScript Config**
Criar `tsconfig.json`:
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "baseUrl": ".",
    "paths": { "@/*": ["resources/js/*"] }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.tsx"],
  "exclude": ["node_modules"]
}
```

Adicionar scripts ao [package.json](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/package.json):
```json
{
  "scripts": {
    "lint": "eslint resources/js/ --ext .ts,.tsx",
    "lint:fix": "eslint resources/js/ --ext .ts,.tsx --fix",
    "format": "prettier --write resources/js/",
    "type-check": "tsc --noEmit"
  }
}
```

**Marcar:** `[x] Pint, ESLint, Prettier, TypeScript config`

### Etapa 2 — Migrations de Fila
```bash
php artisan queue:table --no-interaction
php artisan migrate --no-interaction
```
Verificar que a tabela `jobs` foi criada.

### Etapa 3 — Logging Estruturado

**3.1 — Configurar canais de log** em [config/logging.php](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/config/logging.php):
- Canal `orders` para operações de pedidos
- Canal `stock` para movimentações de estoque
- Canal `auth` para eventos de autenticação

**3.2 — Criar um Trait ou Helper** para logging padronizado:
```php
// app/Traits/LogsActivity.php
trait LogsActivity
{
    protected function logActivity(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->info($message, array_merge($context, [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
```

**Marcar:** `[x] Logging estruturado`

### Etapa 4 — PROJECT.md

Criar `PROJECT.md` na raiz do projeto com:

```markdown
# E-commerce System — Documentação do Projeto

## Como executar o projeto

### Pré-requisitos
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+

### Setup
1. Clone o repositório
2. `composer install`
3. `npm install`
4. Copie `.env.example` para `.env` e configure o banco MySQL
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `npm run build`

### Rodar em desenvolvimento
- `composer run dev` (inicia server, queue e vite simultaneamente)

### Rodar testes
- `php artisan test --compact`
- `php artisan test --coverage --min=80`

## Decisões Arquiteturais
- **Service Layer Pattern:** ...
- **Repository Pattern:** ...
- **DTOs:** ...
- **Inertia.js + React:** ...

## Bibliotecas Utilizadas
| Biblioteca | Justificativa |
|---|---|
| laravel/sanctum | Autenticação API |
| spatie/laravel-permission | Roles e permissions |
| ... | ... |

## Estrutura de Pastas
(descrever a organização)

## Documentação da API
Acesse `/api/documentation` após iniciar o servidor.
```

> **Complete o PROJECT.md com informações reais após todos os agentes finalizarem.**

**Marcar:** `[x] PROJECT.md`

### Etapa 5 — Swagger/OpenAPI

⚠️ **Aguardar Agente 1 completar Controllers**

**5.1 — Configurar L5-Swagger:**
```bash
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --no-interaction
```

Configurar `config/l5-swagger.php` com informações do projeto.

**5.2 — Adicionar anotações nos Controllers:**

Exemplo para ProductController:
```php
/**
 * @OA\Info(title="E-commerce API", version="1.0")
 * @OA\Server(url="/api/v1")
 */

/**
 * @OA\Get(
 *     path="/products",
 *     summary="Listar produtos",
 *     tags={"Produtos"},
 *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Lista de produtos paginada")
 * )
 */
```

Adicionar anotações para TODOS os endpoints:
- Auth: register, login, logout
- Products: list, show, store, update, destroy
- Categories: list, products
- Cart: show, addItem, updateItem, removeItem, clear
- Orders: list, show, store, updateStatus

**5.3 — Gerar documentação:**
```bash
php artisan l5-swagger:generate
```

**5.4 — Verificar** acessando `/api/documentation` no browser.

**Marcar:** `[x] Swagger/OpenAPI`

### Etapa 6 — Verificação Final
1. Rodar Pint: `vendor/bin/pint --dirty --format agent`
2. Rodar ESLint: `npx eslint resources/js/ --ext .ts,.tsx`
3. Verificar PROJECT.md está completo
4. Verificar Swagger UI carrega
5. **Solicitar commit ao humano:** Pause e sugira: `docs: add API documentation, PROJECT.md, and code quality configs`
