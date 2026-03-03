import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import StatusBadge from '@/Components/Admin/StatusBadge';
import { Link } from '@inertiajs/react';
import type { DashboardStats } from '@/types/admin';

// — Mock data (será substituído pelas props reais do Inertia::render() na integração) ——————
const MOCK_STATS: DashboardStats = {
    total_products: 148,
    total_orders: 3_241,
    total_revenue: 189_450.75,
    low_stock_count: 7,
    recent_orders: [
        { id: 1001, user_id: 1, status: 'pending', total: 299.90, subtotal: 270.00, tax: 14.90, shipping_cost: 15.00, items: [], created_at: '2026-03-03T10:15:00Z' },
        { id: 1002, user_id: 2, status: 'processing', total: 549.00, subtotal: 510.00, tax: 24.00, shipping_cost: 15.00, items: [], created_at: '2026-03-03T09:02:00Z' },
        { id: 1003, user_id: 3, status: 'shipped', total: 129.90, subtotal: 115.00, tax: 4.90, shipping_cost: 10.00, items: [], created_at: '2026-03-02T22:30:00Z' },
        { id: 1004, user_id: 4, status: 'delivered', total: 899.00, subtotal: 850.00, tax: 34.00, shipping_cost: 15.00, items: [], created_at: '2026-03-02T18:10:00Z' },
        { id: 1005, user_id: 5, status: 'cancelled', total: 45.00, subtotal: 40.00, tax: 0.00, shipping_cost: 5.00, items: [], created_at: '2026-03-02T15:55:00Z' },
    ],
    low_stock_products: [
        { id: 1, name: 'Fone de Ouvido Bluetooth', slug: 'fone-bluetooth', description: '', price: 299.90, quantity: 2, min_quantity: 10, active: true, category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true }, tags: [], created_at: '', updated_at: '' },
        { id: 2, name: 'Mouse Ergonômico Sem Fio', slug: 'mouse-ergonomico', description: '', price: 189.90, quantity: 1, min_quantity: 5, active: true, category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true }, tags: [], created_at: '', updated_at: '' },
        { id: 3, name: 'Carregador USB-C 65W', slug: 'carregador-usbc', description: '', price: 79.90, quantity: 3, min_quantity: 15, active: true, category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true }, tags: [], created_at: '', updated_at: '' },
        { id: 4, name: 'Teclado Mecânico', slug: 'teclado-mecanico', description: '', price: 459.00, quantity: 0, min_quantity: 5, active: true, category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true }, tags: [], created_at: '', updated_at: '' },
    ],
};

// Dados de gráfico (últimos 7 dias simulados)
const CHART_DATA = [
    { day: 'Seg', orders: 42, revenue: 9_840 },
    { day: 'Ter', orders: 58, revenue: 13_200 },
    { day: 'Qua', orders: 31, revenue: 7_150 },
    { day: 'Qui', orders: 67, revenue: 15_600 },
    { day: 'Sex', orders: 89, revenue: 20_300 },
    { day: 'Sáb', orders: 74, revenue: 17_100 },
    { day: 'Dom', orders: 45, revenue: 10_500 },
];

// — Helpers ——————————————————————————————————————
function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso));
}

// — Icons ——————————————————————————————————————
const IconBox = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
    </svg>
);
const IconShoppingBag = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
    </svg>
);
const IconCurrency = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
);
const IconWarning = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
);

// — Page Props —————————————————————————————————————
interface DashboardProps {
    stats?: DashboardStats;
}

// — Component ————————————————————————————————————
export default function Dashboard({ stats = MOCK_STATS }: DashboardProps) {
    const maxOrders = Math.max(...CHART_DATA.map((d) => d.orders));

    return (
        <AdminLayout title="Dashboard">
            <div className="p-6 space-y-8">

                {/* Page header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p className="text-sm text-gray-500 mt-0.5">Visão geral das métricas do e-commerce</p>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <StatCard
                        title="Total de Produtos"
                        value={stats.total_products.toLocaleString('pt-BR')}
                        icon={<IconBox />}
                        color="indigo"
                        trend={{ direction: 'up', value: '+12', label: 'este mês' }}
                    />
                    <StatCard
                        title="Total de Pedidos"
                        value={stats.total_orders.toLocaleString('pt-BR')}
                        icon={<IconShoppingBag />}
                        color="emerald"
                        trend={{ direction: 'up', value: '+8.5%', label: 'vs. mês anterior' }}
                    />
                    <StatCard
                        title="Receita Total"
                        value={formatCurrency(stats.total_revenue)}
                        icon={<IconCurrency />}
                        color="sky"
                        trend={{ direction: 'up', value: '+14.2%', label: 'vs. mês anterior' }}
                    />
                    <StatCard
                        title="Estoque Crítico"
                        value={stats.low_stock_count}
                        icon={<IconWarning />}
                        color="rose"
                        trend={{ direction: 'down', value: `${stats.low_stock_count} itens`, label: 'precisam reposição' }}
                    />
                </div>

                {/* Middle row: Chart + Quick actions */}
                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">

                    {/* Orders chart (últimos 7 dias) */}
                    <div className="xl:col-span-2 bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h2 className="text-base font-semibold text-gray-900">Pedidos — últimos 7 dias</h2>
                                <p className="text-xs text-gray-400 mt-0.5">Quantidade de pedidos por dia</p>
                            </div>
                            <span className="text-xs font-medium text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">Esta semana</span>
                        </div>
                        <div className="flex items-end gap-3 h-36">
                            {CHART_DATA.map((d, idx) => {
                                const heightPct = maxOrders > 0 ? (d.orders / maxOrders) * 100 : 0;
                                return (
                                    <div key={idx} className="flex flex-col items-center gap-2 flex-1 group">
                                        <span className="text-xs font-semibold text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                            {d.orders}
                                        </span>
                                        <div className="w-full flex items-end" style={{ height: '96px' }}>
                                            <div
                                                className="w-full rounded-t-md bg-indigo-500 group-hover:bg-indigo-600 transition-colors duration-150"
                                                style={{ height: `${heightPct}%`, minHeight: '4px' }}
                                                title={`${d.day}: ${d.orders} pedidos — ${formatCurrency(d.revenue)}`}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-500">{d.day}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Quick links */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                        <h2 className="text-base font-semibold text-gray-900 mb-4">Ações rápidas</h2>
                        <div className="space-y-2">
                            {[
                                { label: 'Novo Produto', href: '/admin/products/create', color: 'indigo' },
                                { label: 'Ver Pedidos', href: '/admin/orders', color: 'emerald' },
                                { label: 'Gerenciar Categorias', href: '/admin/categories', color: 'sky' },
                                { label: 'Estoque Baixo', href: '/admin/stock/low', color: 'rose' },
                            ].map((action) => (
                                <Link
                                    key={action.href}
                                    href={action.href}
                                    className="flex items-center justify-between w-full px-4 py-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-150 group"
                                >
                                    <span className="text-sm font-medium text-gray-700 group-hover:text-indigo-700">
                                        {action.label}
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Bottom row: Recent orders + Low stock */}
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    {/* Recent orders */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="text-base font-semibold text-gray-900">Últimos Pedidos</h2>
                            <Link href="/admin/orders" className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Ver todos →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {stats.recent_orders.map((order) => (
                                <div key={order.id} className="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                                    <div>
                                        <p className="text-sm font-medium text-gray-800">#{order.id}</p>
                                        <p className="text-xs text-gray-400 mt-0.5">{formatDate(order.created_at)}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <StatusBadge status={order.status} size="sm" />
                                        <span className="text-sm font-semibold text-gray-900">
                                            {formatCurrency(order.total)}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Low stock alert */}
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                            <h2 className="text-base font-semibold text-gray-900">
                                Estoque Crítico
                                {stats.low_stock_count > 0 && (
                                    <span className="ml-2 text-xs font-bold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                                        {stats.low_stock_count}
                                    </span>
                                )}
                            </h2>
                            <Link href="/admin/stock/low" className="text-xs font-medium text-red-600 hover:text-red-700">
                                Ver relatório →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {stats.low_stock_products.map((product) => {
                                const isCritical = product.quantity === 0;
                                return (
                                    <div key={product.id} className={['flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors', isCritical ? 'bg-red-50/50' : ''].join(' ')}>
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-gray-800 truncate">{product.name}</p>
                                            <p className="text-xs text-gray-400 mt-0.5">{product.category.name}</p>
                                        </div>
                                        <div className="flex items-center gap-3 flex-shrink-0 ml-3">
                                            <div className="text-right">
                                                <p className={['text-sm font-bold', isCritical ? 'text-red-600' : 'text-amber-600'].join(' ')}>
                                                    {product.quantity} un.
                                                </p>
                                                <p className="text-xs text-gray-400">mín: {product.min_quantity}</p>
                                            </div>
                                            {isCritical && (
                                                <span className="text-xs font-semibold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
                                                    Urgente
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
