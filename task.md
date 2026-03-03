# E-commerce System — Implementation Tracker

## Fase 0 — Setup Base
- [x] Instalar dependências (Composer + NPM)
- [x] Configurar [.env](file:///c:/Users/spyki/OneDrive/Documentos/DEV/e-commerce-challenge/.env) e gerar chave
- [x] Configurar Sanctum e Spatie Permission
- [x] Criar estrutura de pastas (DTOs, Repositories, Services, etc.)
- [x] Criar Models base com migrations
- [x] Configurar bootstrap/app.php (middleware, routing)
- [x] Configurar Vite + React + Inertia
- [x] Criar layout base Inertia

## Fase 1 — Desenvolvimento Paralelo
### Agente 1: Backend Core
- [x] Repository Contracts + Implementations
- [x] DTOs
- [x] Services
- [x] Form Requests
- [x] API Resources
- [x] Controllers API v1
- [x] Rotas API
- [x] Events & Listeners
- [x] Jobs
- [x] Policies
- [x] Scopes e Custom Rules
- [x] Cache
- [x] Seeders & Factories

### Agente 2: Testes
- [x] Testes unitários de Models
- [x] Testes unitários de Services
- [x] Testes unitários de Repositories
- [x] Testes de integração (API endpoints)
- [x] Testes de feature (fluxos completos)
- [x] Testes de validação e autorização

### Agente 3: Frontend Admin
- [x] Layout Admin
- [x] Componentes Admin compartilhados
- [x] Dashboard
- [x] CRUD de Produtos
- [x] CRUD de Categorias
- [x] Listagem de Pedidos
- [x] Relatório de Estoque Baixo

### Agente 4: Frontend Público
- [x] Layout Público
- [x] Componentes compartilhados
- [x] Homepage
- [x] Listagem de Produtos com filtros
- [x] Detalhe do Produto
- [x] Login/Registro
- [x] Carrinho
- [x] Checkout
- [x] Histórico de Pedidos
- [x] Perfil do Usuário

### Agente 5: Documentação & DevOps
- [x] Swagger/OpenAPI
- [x] PROJECT.md
- [x] Logging estruturado
- [x] Pint, ESLint, Prettier, TypeScript config

## Fase 2 — Integração
- [ ] Conectar rotas web Inertia com controllers
- [ ] Conectar admin frontend com backend via Inertia
- [ ] Conectar público frontend com backend via Inertia
- [ ] Ajustar autenticação Sanctum + Inertia
- [ ] Rodar seeders
- [x] Suíte de testes completa
- [x] Verificar cobertura ≥80%

## Fase 3 — Verificação Final
- [ ] Testes automatizados passando
- [ ] Testes manuais (fluxo compra, admin, responsivo)
- [ ] Swagger UI funcionando
- [ ] Build de produção
