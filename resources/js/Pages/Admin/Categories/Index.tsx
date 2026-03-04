import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Modal from '@/Components/Admin/Modal';
import type { Category } from '@/types/admin';

// — Tree builder ——————————————————————————————
interface CategoryNode extends Category {
    children: CategoryNode[];
}

function buildTree(flat: Category[]): CategoryNode[] {
    const map = new Map<number, CategoryNode>();
    const roots: CategoryNode[] = [];

    flat.forEach((cat) => {
        map.set(cat.id, { ...cat, children: [] });
    });

    flat.forEach((cat) => {
        const node = map.get(cat.id)!;
        if (cat.parent_id === null) {
            roots.push(node);
        } else {
            const parent = map.get(cat.parent_id);
            if (parent) {
                parent.children.push(node);
            } else {
                roots.push(node);
            }
        }
    });

    return roots;
}

// — Tree row component ———————————————————————
interface TreeRowProps {
    node: CategoryNode;
    depth: number;
    expanded: Set<number>;
    onToggle: (id: number) => void;
    onDelete: (cat: Category) => void;
}

function TreeRow({ node, depth, expanded, onToggle, onDelete }: TreeRowProps) {
    const hasChildren = node.children.length > 0;
    const isExpanded = expanded.has(node.id);

    return (
        <>
            <tr className="hover:bg-gray-50 transition-colors">
                <td className="px-4 py-3">
                    <div className="flex items-center gap-2" style={{ paddingLeft: `${depth * 24}px` }}>
                        {/* Expand/collapse toggle */}
                        {hasChildren ? (
                            <button
                                type="button"
                                onClick={() => onToggle(node.id)}
                                className="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition-colors flex-shrink-0"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    className={['h-3.5 w-3.5 transition-transform duration-150', isExpanded ? 'rotate-90' : ''].join(' ')}
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2.5}
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        ) : (
                            <span className="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                                {depth > 0 && (
                                    <span className="w-1 h-1 rounded-full bg-gray-300" />
                                )}
                            </span>
                        )}

                        <div>
                            <p className={['font-medium text-gray-900', depth === 0 ? 'text-sm' : 'text-sm'].join(' ')}>
                                {node.name}
                            </p>
                            {node.description && (
                                <p className="text-xs text-gray-400 mt-0.5">{node.description}</p>
                            )}
                        </div>
                    </div>
                </td>
                <td className="px-4 py-3 text-sm text-gray-500 font-mono">{node.slug}</td>
                <td className="px-4 py-3">
                    <span className={[
                        'inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full',
                        node.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500',
                    ].join(' ')}>
                        <span className={['h-1.5 w-1.5 rounded-full', node.active ? 'bg-emerald-500' : 'bg-gray-400'].join(' ')} />
                        {node.active ? 'Ativa' : 'Inativa'}
                    </span>
                </td>
                <td className="px-4 py-3">
                    <span className="text-sm text-gray-500">{node.children.length}</span>
                </td>
                <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                        <Link
                            href={`/admin/categories/${node.id}/edit`}
                            className="text-xs font-medium text-indigo-600 hover:text-indigo-700 px-2.5 py-1.5 rounded-md hover:bg-indigo-50 transition-colors"
                        >
                            Editar
                        </Link>
                        <button
                            onClick={() => onDelete(node)}
                            className="text-xs font-medium text-red-600 hover:text-red-700 px-2.5 py-1.5 rounded-md hover:bg-red-50 transition-colors"
                        >
                            Excluir
                        </button>
                    </div>
                </td>
            </tr>
            {/* Recursively render children when expanded */}
            {hasChildren && isExpanded && node.children.map((child) => (
                <TreeRow
                    key={child.id}
                    node={child}
                    depth={depth + 1}
                    expanded={expanded}
                    onToggle={onToggle}
                    onDelete={onDelete}
                />
            ))}
        </>
    );
}

// — Props ——————————————————————
interface CategoriesIndexProps {
    categories: Category[];
}

// — Component ——————————————————————
export default function CategoriesIndex({ categories }: CategoriesIndexProps) {
    const tree = buildTree(categories);

    // Start with all root categories expanded
    const [expanded, setExpanded] = useState<Set<number>>(
        new Set(categories.filter((c) => c.parent_id === null).map((c) => c.id)),
    );
    const [deleteModal, setDeleteModal] = useState<{ open: boolean; category: Category | null }>({ open: false, category: null });
    const [deleting, setDeleting] = useState(false);

    function toggleExpand(id: number) {
        setExpanded((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }

    function expandAll() {
        setExpanded(new Set(categories.map((c) => c.id)));
    }

    function collapseAll() {
        setExpanded(new Set());
    }

    function handleDelete() {
        if (!deleteModal.category) { return; }
        setDeleting(true);
        router.delete(`/admin/categories/${deleteModal.category.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteModal({ open: false, category: null });
            },
        });
    }

    const rootCount = categories.filter((c) => c.parent_id === null).length;
    const totalCount = categories.length;

    return (
        <AdminLayout title="Categorias">
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Categorias</h1>
                        <p className="text-sm text-gray-500 mt-0.5">
                            {rootCount} raiz · {totalCount} total
                        </p>
                    </div>
                    <Link
                        href="/admin/categories/create"
                        className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova Categoria
                    </Link>
                </div>

                {/* Tree controls */}
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={expandAll}
                        className="flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-indigo-600 px-3 py-1.5 rounded-md border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Expandir tudo
                    </button>
                    <button
                        type="button"
                        onClick={collapseAll}
                        className="flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded-md border border-gray-200 hover:bg-gray-100 transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                        </svg>
                        Colapsar tudo
                    </button>
                </div>

                {/* Tree table */}
                <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-200 bg-gray-50">
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subcats.</th>
                                    <th scope="col" className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {tree.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-12 text-center text-sm text-gray-400">
                                            Nenhuma categoria cadastrada.
                                        </td>
                                    </tr>
                                ) : (
                                    tree.map((node) => (
                                        <TreeRow
                                            key={node.id}
                                            node={node}
                                            depth={0}
                                            expanded={expanded}
                                            onToggle={toggleExpand}
                                            onDelete={(cat) => setDeleteModal({ open: true, category: cat })}
                                        />
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Delete modal */}
            <Modal
                isOpen={deleteModal.open}
                onClose={() => setDeleteModal({ open: false, category: null })}
                title="Excluir Categoria"
                onConfirm={handleDelete}
                confirmLabel="Excluir"
                cancelLabel="Cancelar"
                confirmDestructive
                loading={deleting}
            >
                <div className="space-y-3">
                    <p className="text-sm text-gray-700">
                        Tem certeza que deseja excluir a categoria{' '}
                        <strong className="font-semibold">{deleteModal.category?.name}</strong>?
                    </p>
                    <div className="p-3 bg-amber-50 text-amber-800 text-xs rounded-lg flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>As subcategorias e produtos associados a esta categoria podem ser afetados.</span>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}
