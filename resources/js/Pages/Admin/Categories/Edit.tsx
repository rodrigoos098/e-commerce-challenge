import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FormField from '@/Components/Admin/FormField';
import type { Category } from '@/types/admin';

// — Mock data ——————————————————————————————————
const MOCK_CATEGORY: Category = {
    id: 2,
    name: 'Computadores',
    slug: 'computadores',
    parent_id: 1,
    active: true,
    description: 'Todos os tipos de computadores e acessórios.',
};

const MOCK_CATEGORIES: Category[] = [
    { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true },
    { id: 6, name: 'Moda', slug: 'moda', parent_id: null, active: true },
    { id: 9, name: 'Casa & Decoração', slug: 'casa-decoracao', parent_id: null, active: true },
];

// — Schema ——————————————————————
const categorySchema = z.object({
    name: z.string().min(2, 'Nome deve ter ao menos 2 caracteres').max(100),
    description: z.string().max(500, 'Descrição muito longa').optional(),
    parent_id: z.number().nullable().optional(),
    active: z.boolean().default(true),
});

type CategoryForm = z.infer<typeof categorySchema>;

// — Props ——————————————————————
interface CategoriesEditProps {
    category?: Category;
    categories?: Category[];
}

// — Component ——————————————————————
export default function CategoriesEdit({ category = MOCK_CATEGORY, categories = MOCK_CATEGORIES }: CategoriesEditProps) {
    const [activeToggle, setActiveToggle] = useState(category.active);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<CategoryForm>({
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        resolver: zodResolver(categorySchema) as Resolver<CategoryForm>,
        defaultValues: {
            name: category.name,
            description: (category as Category & { description?: string }).description ?? '',
            parent_id: category.parent_id ?? null,
            active: category.active,
        },
    });

    function onSubmit(data: CategoryForm) {
        setSubmitting(true);
        router.put(`/admin/categories/${category.id}`, { ...data, active: activeToggle }, {
            onFinish: () => setSubmitting(false),
        });
    }

    // Exclude the category itself (and its descendants) to prevent circular references
    const parentOptions = [
        { value: '', label: '— Nenhuma (categoria raiz) —' },
        ...categories
            .filter((cat) => cat.id !== category.id)
            .map((cat) => ({ value: cat.id, label: cat.name })),
    ];

    return (
        <AdminLayout title={`Editar Categoria: ${category.name}`}>
            <div className="p-6">
                <div className="max-w-2xl mx-auto">
                    {/* Header */}
                    <div className="mb-8">
                        <button
                            type="button"
                            onClick={() => router.visit('/admin/categories')}
                            className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 mb-3 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Voltar para Categorias
                        </button>
                        <h1 className="text-2xl font-bold text-gray-900">Editar Categoria</h1>
                        <p className="text-sm text-gray-500 mt-0.5">
                            Atualizando <span className="font-medium text-gray-700">{category.name}</span>
                            {' · '}
                            <code className="text-xs bg-gray-100 text-gray-600 px-1 rounded">{category.slug}</code>
                        </p>
                    </div>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                        {/* Main form card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-5">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3">
                                Informações
                            </h2>

                            <FormField
                                label="Nome da Categoria"
                                name="name"
                                required
                                placeholder="Ex.: Eletrônicos"
                                register={register('name')}
                                error={errors.name?.message}
                            />

                            <FormField
                                label="Descrição"
                                name="description"
                                type="textarea"
                                rows={3}
                                placeholder="Descrição opcional da categoria..."
                                register={register('description')}
                                error={errors.description?.message}
                            />

                            <div>
                                <label htmlFor="parent_id" className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Categoria Pai
                                </label>
                                <select
                                    id="parent_id"
                                    {...register('parent_id', { setValueAs: (v) => v === '' ? null : parseInt(String(v), 10) })}
                                    defaultValue={category.parent_id ?? ''}
                                    className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-colors"
                                >
                                    {parentOptions.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                                <p className="text-xs text-gray-500 mt-1">
                                    Deixe em branco para manter como categoria raiz
                                </p>
                                {errors.parent_id && (
                                    <p className="text-xs text-red-500 mt-1">{errors.parent_id.message?.toString()}</p>
                                )}
                            </div>

                            {/* Slug (read-only) */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Slug <span className="text-gray-400 font-normal">(gerado automaticamente)</span>
                                </label>
                                <input
                                    type="text"
                                    readOnly
                                    value={category.slug}
                                    className="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                />
                            </div>
                        </div>

                        {/* Status card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                            <FormField
                                label="Categoria Ativa"
                                name="active"
                                type="toggle"
                                checked={activeToggle}
                                onToggle={setActiveToggle}
                                hint={activeToggle ? 'Categoria visível na loja' : 'Categoria oculta da loja'}
                            />
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/categories')}
                                className="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={submitting}
                                className="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed shadow-sm"
                            >
                                {submitting && (
                                    <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                )}
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
