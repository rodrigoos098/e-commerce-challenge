import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import StatusBadge from '@/Components/Admin/StatusBadge';
import { Link } from '@inertiajs/react';
import type { DashboardStats } from '@/types/admin';

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

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

interface DashboardProps {
    stats: DashboardStats;
}

export default function Dashboard({ stats }: DashboardProps) {
    const chartData = stats.orders_by_day.map((entry) => ({
        ...entry,
        day: new Intl.DateTimeFormat('pt-BR', { weekday: 'short' }).format(new Date(`${entry.date}T00:00:00`)),
    }));
    const maxOrders = Math.max(...chartData.map((entry) => entry.orders), 0);

    return (
        <AdminLayout title="Dashboard">
            <div className="space-y-8 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p className="mt-0.5 text-sm text-gray-500">Visao geral das metricas do e-commerce</p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Total de Produtos"
                        value={stats.total_products.toLocaleString('pt-BR')}
                        icon={<IconBox />}
                        color="indigo"
                        trend={{ direction: 'up', value: '+12', label: 'este mes' }}
                    />
                    <StatCard
                        title="Total de Pedidos"
                        value={stats.total_orders.toLocaleString('pt-BR')}
                        icon={<IconShoppingBag />}
                        color="emerald"
                        trend={{ direction: 'up', value: '+8.5%', label: 'vs. mes anterior' }}
                    />
                    <StatCard
                        title="Receita Total"
                        value={formatCurrency(stats.total_revenue)}
                        icon={<IconCurrency />}
                        color="sky"
                        trend={{ direction: 'up', value: '+14.2%', label: 'vs. mes anterior' }}
                    />
                    <StatCard
                        title="Estoque Critico"
                        value={stats.low_stock_count}
                        icon={<IconWarning />}
                        color="rose"
                        trend={{ direction: 'down', value: `${stats.low_stock_count} itens`, label: 'precisam reposicao' }}
                    />
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-xs xl:col-span-2">
                        <div className="mb-6 flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-gray-900">Pedidos - ultimos 7 dias</h2>
                                <p className="mt-0.5 text-xs text-gray-400">Quantidade de pedidos por dia</p>
                            </div>
                            <span className="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-600">Esta semana</span>
                        </div>
                        <div className="flex h-36 items-end gap-3">
                            {chartData.map((entry) => {
                                const heightPct = maxOrders > 0 ? (entry.orders / maxOrders) * 100 : 0;

                                return (
                                    <div key={entry.date} className="group flex flex-1 flex-col items-center gap-2">
                                        <span className="text-xs font-semibold text-gray-700 opacity-0 transition-opacity group-hover:opacity-100">
                                            {entry.orders}
                                        </span>
                                        <div className="flex w-full items-end" style={{ height: '96px' }}>
                                            <div
                                                className="w-full rounded-t-md bg-indigo-500 transition-colors duration-150 group-hover:bg-indigo-600"
                                                style={{ height: `${heightPct}%`, minHeight: '4px' }}
                                                title={`${entry.day}: ${entry.orders} pedidos - ${formatCurrency(entry.revenue)}`}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-500">{entry.day}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-xs">
                        <h2 className="mb-4 text-base font-semibold text-gray-900">Acoes rapidas</h2>
                        <div className="space-y-2">
                            {[
                                { label: 'Novo Produto', href: '/admin/products/create' },
                                { label: 'Ver Pedidos', href: '/admin/orders' },
                                { label: 'Gerenciar Categorias', href: '/admin/categories' },
                                { label: 'Estoque Baixo', href: '/admin/stock/low' },
                            ].map((action) => (
                                <Link
                                    key={action.href}
                                    href={action.href}
                                    className="group flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-3 transition-all duration-150 hover:border-indigo-300 hover:bg-indigo-50"
                                >
                                    <span className="text-sm font-medium text-gray-700 group-hover:text-indigo-700">{action.label}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                        <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <h2 className="text-base font-semibold text-gray-900">Ultimos Pedidos</h2>
                            <Link href="/admin/orders" className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Ver todos
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {stats.recent_orders.map((order) => (
                                <div key={order.id} className="flex items-center justify-between px-5 py-3 transition-colors hover:bg-gray-50">
                                    <div>
                                        <p className="text-sm font-medium text-gray-800">#{order.id}</p>
                                        <p className="mt-0.5 text-xs text-gray-400">{formatDate(order.created_at)}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <StatusBadge status={order.status} size="sm" />
                                        <span className="text-sm font-semibold text-gray-900">{formatCurrency(order.total)}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                        <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <h2 className="text-base font-semibold text-gray-900">
                                Estoque Critico
                                {stats.low_stock_count > 0 && (
                                    <span className="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-600">
                                        {stats.low_stock_count}
                                    </span>
                                )}
                            </h2>
                            <Link href="/admin/stock/low" className="text-xs font-medium text-red-600 hover:text-red-700">
                                Ver relatorio
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {stats.low_stock_products.map((product) => {
                                const isCritical = product.quantity === 0;

                                return (
                                    <div
                                        key={product.id}
                                        className={['flex items-center justify-between px-5 py-3 transition-colors hover:bg-gray-50', isCritical ? 'bg-red-50/50' : ''].join(' ')}
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-gray-800">{product.name}</p>
                                            <p className="mt-0.5 text-xs text-gray-400">{product.category?.name ?? 'Sem categoria'}</p>
                                        </div>
                                        <div className="ml-3 flex flex-shrink-0 items-center gap-3">
                                            <div className="text-right">
                                                <p className={['text-sm font-bold', isCritical ? 'text-red-600' : 'text-amber-600'].join(' ')}>
                                                    {product.quantity} un.
                                                </p>
                                                <p className="text-xs text-gray-400">min: {product.min_quantity}</p>
                                            </div>
                                            {isCritical && (
                                                <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
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
