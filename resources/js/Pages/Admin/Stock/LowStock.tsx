import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { Product } from '@/types/admin';

// — Mock data ——————————————————————————————————
const MOCK_CATEGORY = { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true };

const MOCK_LOW_STOCK: Product[] = [
    {
        id: 4,  name: 'SSD NVMe 1TB',           slug: 'ssd-nvme-1tb',           description: '',
        price: 399.90, cost_price: 280.00, quantity: 0, min_quantity: 5,
        active: true, category: MOCK_CATEGORY, tags: [],
        created_at: '2025-01-01T00:00:00Z', updated_at: '2025-01-10T00:00:00Z',
    },
    {
        id: 7,  name: 'Cabo HDMI 2.1 2m',        slug: 'cabo-hdmi-2-1-2m',        description: '',
        price: 89.90,  cost_price: 40.00,  quantity: 1, min_quantity: 10,
        active: true, category: MOCK_CATEGORY, tags: [],
        created_at: '2025-01-01T00:00:00Z', updated_at: '2025-01-08T00:00:00Z',
    },
    {
        id: 11, name: 'Webcam Full HD',           slug: 'webcam-full-hd',           description: '',
        price: 249.90, cost_price: 140.00, quantity: 2, min_quantity: 8,
        active: true, category: MOCK_CATEGORY, tags: [],
        created_at: '2025-01-01T00:00:00Z', updated_at: '2025-01-09T00:00:00Z',
    },
    {
        id: 15, name: 'Teclado Bluetooth Slim',  slug: 'teclado-bluetooth-slim',  description: '',
        price: 179.90, cost_price: 95.00,  quantity: 3, min_quantity: 5,
        active: true, category: MOCK_CATEGORY, tags: [],
        created_at: '2025-01-01T00:00:00Z', updated_at: '2025-01-07T00:00:00Z',
    },
    {
        id: 22, name: 'Hub USB-C 7 em 1',        slug: 'hub-usb-c-7-em-1',        description: '',
        price: 134.90, cost_price: 70.00,  quantity: 4, min_quantity: 10,
        active: true, category: MOCK_CATEGORY, tags: [],
        created_at: '2025-01-01T00:00:00Z', updated_at: '2025-01-06T00:00:00Z',
    },
];

// — Props ——————————————————————
interface LowStockProps {
    products?: Product[];
}

// — Helpers ——————————————————————
function getDeficit(p: Product): number {
    return p.min_quantity - p.quantity;
}

function fillPercent(p: Product): number {
    return p.min_quantity > 0 ? Math.min((p.quantity / p.min_quantity) * 100, 100) : 0;
}

function getSeverityBadge(p: Product) {
    if (p.quantity === 0) {
        return (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                <span className="h-1.5 w-1.5 rounded-full bg-red-500 inline-block" />
                Esgotado
            </span>
        );
    }
    const pct = p.quantity / p.min_quantity;
    if (pct <= 0.25) {
        return (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">
                <span className="h-1.5 w-1.5 rounded-full bg-orange-500 inline-block" />
                Crítico
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
            <span className="h-1.5 w-1.5 rounded-full bg-amber-500 inline-block" />
            Baixo
        </span>
    );
}

function rowBorderClass(p: Product): string {
    if (p.quantity === 0) { return 'border-l-red-400 bg-red-50/40'; }
    const pct = p.quantity / p.min_quantity;
    if (pct <= 0.25) { return 'border-l-orange-400 bg-orange-50/40'; }
    return 'border-l-amber-400 bg-amber-50/40';
}

function barClass(p: Product): string {
    if (p.quantity === 0) { return 'bg-red-500'; }
    const pct = p.quantity / p.min_quantity;
    if (pct <= 0.25) { return 'bg-orange-500'; }
    return 'bg-amber-400';
}

// — Component ——————————————————————
export default function LowStock({ products = MOCK_LOW_STOCK }: LowStockProps) {
    // Sort: out-of-stock first, then by highest deficit
    const sorted = [...products].sort((a, b) => {
        if (a.quantity === 0 && b.quantity !== 0) { return -1; }
        if (b.quantity === 0 && a.quantity !== 0) { return 1; }
        return getDeficit(b) - getDeficit(a);
    });

    const outOfStockCount = products.filter((p) => p.quantity === 0).length;
    const criticalCount   = products.filter((p) => p.quantity > 0 && p.quantity / p.min_quantity <= 0.25).length;

    return (
        <AdminLayout title="Estoque Baixo">
            <div className="p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Relatório de Estoque Baixo</h1>
                    <p className="text-sm text-gray-500 mt-0.5">
                        Produtos com quantidade abaixo do mínimo configurado
                    </p>
                </div>

                {/* Summary cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total de Alertas</p>
                        <p className="text-3xl font-bold text-gray-900 mt-1">{products.length}</p>
                        <p className="text-xs text-gray-500 mt-1">produtos abaixo do mínimo</p>
                    </div>
                    <div className="bg-red-50 rounded-xl border border-red-200 p-5">
                        <p className="text-xs font-semibold text-red-600 uppercase tracking-wider">Esgotados</p>
                        <p className="text-3xl font-bold text-red-700 mt-1">{outOfStockCount}</p>
                        <p className="text-xs text-red-500 mt-1">quantidade = 0</p>
                    </div>
                    <div className="bg-orange-50 rounded-xl border border-orange-200 p-5">
                        <p className="text-xs font-semibold text-orange-600 uppercase tracking-wider">Críticos</p>
                        <p className="text-3xl font-bold text-orange-700 mt-1">{criticalCount}</p>
                        <p className="text-xs text-orange-500 mt-1">≤ 25% do mínimo</p>
                    </div>
                </div>

                {/* Product list */}
                {sorted.length === 0 ? (
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-emerald-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-base font-semibold text-gray-900">Tudo em dia!</p>
                        <p className="text-sm text-gray-500 mt-1">Nenhum produto com estoque abaixo do mínimo.</p>
                    </div>
                ) : (
                    <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                                Produtos em Alerta
                            </h2>
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/products')}
                                className="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
                            >
                                Ver todos os produtos →
                            </button>
                        </div>

                        <div className="divide-y divide-gray-100">
                            {sorted.map((p) => {
                                const pct = fillPercent(p);
                                return (
                                    <div key={p.id} className={`px-6 py-4 border-l-4 ${rowBorderClass(p)}`}>
                                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            {/* Info */}
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <p className="text-sm font-semibold text-gray-900">{p.name}</p>
                                                    {getSeverityBadge(p)}
                                                </div>
                                                <p className="text-xs text-gray-500 mt-0.5">
                                                    {p.category.name} · <code className="font-mono">{p.slug}</code>
                                                </p>
                                            </div>

                                            {/* Numbers + bar + action */}
                                            <div className="flex items-center gap-5 shrink-0">
                                                <div className="text-center">
                                                    <p className="text-xs text-gray-500">Atual</p>
                                                    <p className={`text-lg font-bold ${p.quantity === 0 ? 'text-red-600' : 'text-gray-900'}`}>{p.quantity}</p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xs text-gray-500">Mínimo</p>
                                                    <p className="text-lg font-bold text-gray-400">{p.min_quantity}</p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xs text-gray-500">Déficit</p>
                                                    <p className="text-lg font-bold text-red-600">−{getDeficit(p)}</p>
                                                </div>

                                                {/* Progress bar */}
                                                <div className="w-20 hidden sm:block">
                                                    <p className="text-xs text-gray-500 mb-1 text-right">{Math.round(pct)}%</p>
                                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <div
                                                            className={`h-2 rounded-full transition-all ${barClass(p)}`}
                                                            style={{ width: `${pct}%` }}
                                                        />
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() => router.visit(`/admin/products/${p.id}/edit`)}
                                                    className="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 hover:bg-indigo-50 rounded-lg transition-colors whitespace-nowrap"
                                                >
                                                    Restock
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
