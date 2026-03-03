import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FormField from '@/Components/Admin/FormField';
import type { Category, Tag } from '@/types/admin';

// — Mock data ——————————————————————————————————
const MOCK_CATEGORIES: Category[] = [
    { id: 1, name: 'Eletrônicos', slug: 'eletronicos', parent_id: null, active: true },
    { id: 2, name: 'Computadores', slug: 'computadores', parent_id: 1, active: true },
    { id: 3, name: 'Acessórios', slug: 'acessorios', parent_id: null, active: true },
    { id: 4, name: 'Periféricos', slug: 'perifericos', parent_id: 2, active: true },
];
const MOCK_TAGS: Tag[] = [
    { id: 1, name: 'Promoção', slug: 'promocao' },
    { id: 2, name: 'Novo', slug: 'novo' },
    { id: 3, name: 'Destaque', slug: 'destaque' },
    { id: 4, name: 'Importado', slug: 'importado' },
    { id: 5, name: 'Garantia 2 anos', slug: 'garantia-2-anos' },
];

// — Zod schema ————————————————————————
const productSchema = z.object({
    name: z.string().min(3, 'Nome deve ter ao menos 3 caracteres').max(255),
    description: z.string().min(10, 'Descrição deve ter ao menos 10 caracteres'),
    price: z.number({ message: 'Preço inválido' }).positive('Preço deve ser maior que zero'),
    cost_price: z.number({ message: 'Custo inválido' }).nonnegative('Custo não pode ser negativo').optional(),
    quantity: z.number({ message: 'Quantidade inválida' }).int('Deve ser inteiro').nonnegative('Não pode ser negativa'),
    min_quantity: z.number({ message: 'Quantidade mínima inválida' }).int('Deve ser inteiro').nonnegative('Não pode ser negativa'),
    category_id: z.number({ message: 'Selecione uma categoria' }).positive('Selecione uma categoria'),
    active: z.boolean().default(true),
});

type ProductForm = z.infer<typeof productSchema>;

// — Props ——————————————————————
interface ProductsCreateProps {
    categories?: Category[];
    tags?: Tag[];
}

// — Component ——————————————————————
export default function ProductsCreate({
    categories = MOCK_CATEGORIES,
    tags = MOCK_TAGS,
}: ProductsCreateProps) {
    const [selectedTags, setSelectedTags] = useState<number[]>([]);
    const [activeToggle, setActiveToggle] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<ProductForm>({
        resolver: zodResolver(productSchema) as Resolver<ProductForm>,
        defaultValues: {
            active: true,
            quantity: 0,
            min_quantity: 5,
        },
    });

    function toggleTag(tagId: number) {
        setSelectedTags((prev) =>
            prev.includes(tagId) ? prev.filter((id) => id !== tagId) : [...prev, tagId],
        );
    }

    function onSubmit(data: ProductForm) {
        setSubmitting(true);
        router.post('/admin/products', { ...data, active: activeToggle, tags: selectedTags }, {
            onFinish: () => setSubmitting(false),
        });
    }

    const categoryOptions = categories.map((cat) => ({
        value: cat.id,
        label: cat.parent_id ? `  └ ${cat.name}` : cat.name,
    }));

    return (
        <AdminLayout title="Novo Produto">
            <div className="p-6">
                <div className="max-w-3xl mx-auto">
                    {/* Header */}
                    <div className="mb-8">
                        <button
                            type="button"
                            onClick={() => router.visit('/admin/products')}
                            className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 mb-3 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Voltar para Produtos
                        </button>
                        <h1 className="text-2xl font-bold text-gray-900">Novo Produto</h1>
                        <p className="text-sm text-gray-500 mt-0.5">Preencha os dados para cadastrar um novo produto</p>
                    </div>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                        {/* Basic info card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-5">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3">
                                Informações básicas
                            </h2>

                            <FormField
                                label="Nome do Produto"
                                name="name"
                                required
                                placeholder="Ex.: Fone de Ouvido Bluetooth"
                                register={register('name')}
                                error={errors.name?.message}
                            />

                            <FormField
                                label="Descrição"
                                name="description"
                                type="textarea"
                                required
                                rows={4}
                                placeholder="Descreva o produto detalhadamente..."
                                register={register('description')}
                                error={errors.description?.message}
                            />

                            <FormField
                                label="Categoria"
                                name="category_id"
                                type="select"
                                required
                                options={categoryOptions}
                                register={register('category_id', { valueAsNumber: true })}
                                error={errors.category_id?.message}
                            />
                        </div>

                        {/* Pricing card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-5">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3">
                                Preços
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <FormField
                                    label="Preço de Venda (R$)"
                                    name="price"
                                    type="number"
                                    required
                                    placeholder="0,00"
                                    min={0.01}
                                    step={0.01}
                                    register={register('price', { valueAsNumber: true })}
                                    error={errors.price?.message}
                                    hint="Preço exibido para o cliente"
                                />
                                <FormField
                                    label="Preço de Custo (R$)"
                                    name="cost_price"
                                    type="number"
                                    placeholder="0,00"
                                    min={0}
                                    step={0.01}
                                    register={register('cost_price', { valueAsNumber: true })}
                                    error={errors.cost_price?.message}
                                    hint="Valor pago pelo produto (interno)"
                                />
                            </div>
                        </div>

                        {/* Stock card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-5">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3">
                                Estoque
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <FormField
                                    label="Quantidade em Estoque"
                                    name="quantity"
                                    type="number"
                                    required
                                    min={0}
                                    step={1}
                                    register={register('quantity', { valueAsNumber: true })}
                                    error={errors.quantity?.message}
                                />
                                <FormField
                                    label="Quantidade Mínima"
                                    name="min_quantity"
                                    type="number"
                                    required
                                    min={0}
                                    step={1}
                                    register={register('min_quantity', { valueAsNumber: true })}
                                    error={errors.min_quantity?.message}
                                    hint="Alertas de estoque baixo abaixo deste valor"
                                />
                            </div>
                        </div>

                        {/* Tags card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-4">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3">
                                Tags
                            </h2>
                            <div className="flex flex-wrap gap-2">
                                {tags.map((tag) => {
                                    const selected = selectedTags.includes(tag.id);
                                    return (
                                        <button
                                            key={tag.id}
                                            type="button"
                                            onClick={() => toggleTag(tag.id)}
                                            className={[
                                                'px-3 py-1.5 rounded-full text-sm font-medium border transition-all duration-150',
                                                selected
                                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                                    : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600',
                                            ].join(' ')}
                                        >
                                            {selected && (
                                                <span className="mr-1">✓</span>
                                            )}
                                            {tag.name}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Status card */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                            <FormField
                                label="Produto Ativo"
                                name="active"
                                type="toggle"
                                checked={activeToggle}
                                onToggle={setActiveToggle}
                                hint={activeToggle ? 'Produto visível na loja' : 'Produto oculto da loja'}
                            />
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/products')}
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
                                Cadastrar Produto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
