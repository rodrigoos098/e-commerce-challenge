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
- [ ] Repository Contracts + Implementations
- [ ] DTOs
- [ ] Services
- [ ] Form Requests
- [ ] API Resources
- [ ] Controllers API v1
- [ ] Rotas API
- [ ] Events & Listeners
- [ ] Jobs
- [ ] Policies
- [ ] Scopes e Custom Rules
- [ ] Cache
- [ ] Seeders & Factories

### Agente 2: Testes
- [ ] Testes unitários de Models
- [ ] Testes unitários de Services
- [ ] Testes unitários de Repositories
- [ ] Testes de integração (API endpoints)
- [ ] Testes de feature (fluxos completos)
- [ ] Testes de validação e autorização

### Agente 3: Frontend Admin
- [ ] Layout Admin
- [ ] Componentes Admin compartilhados
- [ ] Dashboard
- [ ] CRUD de Produtos
- [ ] CRUD de Categorias
- [ ] Listagem de Pedidos
- [ ] Relatório de Estoque Baixo

### Agente 4: Frontend Público
- [ ] Layout Público
- [ ] Componentes compartilhados
- [ ] Homepage
- [ ] Listagem de Produtos com filtros
- [ ] Detalhe do Produto
- [ ] Login/Registro
- [ ] Carrinho
- [ ] Checkout
- [ ] Histórico de Pedidos
- [ ] Perfil do Usuário

### Agente 5: Documentação & DevOps
- [ ] Swagger/OpenAPI
- [ ] PROJECT.md
- [ ] Logging estruturado
- [ ] Pint, ESLint, Prettier, TypeScript config

## Fase 2 — Integração
- [ ] Conectar rotas web Inertia com controllers
- [ ] Conectar admin frontend com backend via Inertia
- [ ] Conectar público frontend com backend via Inertia
- [ ] Ajustar autenticação Sanctum + Inertia
- [ ] Rodar seeders
- [ ] Suíte de testes completa
- [ ] Verificar cobertura ≥80%

## Fase 3 — Verificação Final
- [ ] Testes automatizados passando
- [ ] Testes manuais (fluxo compra, admin, responsivo)
- [ ] Swagger UI funcionando
- [ ] Build de produção
