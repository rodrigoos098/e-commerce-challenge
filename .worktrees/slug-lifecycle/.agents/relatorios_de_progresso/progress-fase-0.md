# Progress — Fase 0: Setup Base

## Status: ✅ Concluída

### Log de Execução

| # | Passo | Status | Notas |
|---|-------|--------|-------|
| 1 | Instalar dependências Composer | ✅ | sanctum, spatie/permission, l5-swagger, telescope |
| 2 | Instalar dependências NPM | ✅ | react, inertia, typescript, react-hook-form, zod, react-hot-toast |
| 3 | Configurar .env | ✅ | SQLite (MySQL pode ser configurado quando disponível), APP_KEY gerada |
| 4 | Configurar Sanctum | ✅ | Provider publicado |
| 5 | Configurar Spatie Permission | ✅ | Provider publicado |
| 6 | Criar estrutura de pastas | ✅ | DTOs, Repositories/Contracts, Services, Events, Listeners, Jobs, Rules, Policies, Controllers/Api/V1, Requests, Resources |
| 7 | Criar Models base com Migrations | ✅ | 8 models completos com $fillable, casts, relacionamentos, scopes. 14 migrações executadas. User atualizado com HasApiTokens + HasRoles |
| 8 | Configurar bootstrap/app.php | ✅ | API routing, Sanctum stateful middleware, rate limiting 100/min |
| 9 | Configurar Vite + React + Inertia | ✅ | Plugin React, entry app.tsx, alias @, inertia-laravel instalado |
| 10 | Criar Layout base Inertia | ✅ | app.tsx (entry), app.blade.php (root template) |
| 11 | Criar TypeScript Types compartilhados | ✅ | resources/js/types/shared.ts com Product, Category, Tag, Order, OrderItem, User, PaginatedResponse |
| 12 | Criar SkeletonLoader compartilhado | ✅ | resources/js/Components/Shared/SkeletonLoader.tsx com 5 variantes |
| 13 | Criar rotas web stub | ✅ | Rotas públicas, autenticadas e admin com Inertia::render |
| 14 | Verificação | ✅ | `migrate:status` — 14 migrações OK. `npm run build` — sucesso |
| 15 | Solicitar commit | ⏳ | Aguardando revisão humana |

### Notas
- DB configurado como SQLite para funcionar sem MySQL. Para usar MySQL: descomentar as linhas no .env e alterar DB_CONNECTION=mysql
- tsconfig.json criado com path alias @/ → resources/js/
- Diretórios de páginas e componentes frontend foram pré-criados (Admin, Public, Auth, Customer, Products, etc.)
- Pint executado e aprovado em todos os arquivos PHP
