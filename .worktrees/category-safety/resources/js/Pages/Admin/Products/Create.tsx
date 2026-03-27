import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FormField from '@/Components/Admin/FormField';
import Button from '@/Components/Shared/Button';
import type { Category, Tag } from '@/types/admin';

// — Zod schema ————————————————————————
const productSchema = z.object({
  name: z.string().min(3, 'Nome deve ter ao menos 3 caracteres').max(255),
  description: z.string().min(10, 'Descrição deve ter ao menos 10 caracteres'),
  price: z.number({ message: 'Preço inválido' }).positive('Preço deve ser maior que zero'),
  cost_price: z
    .number({ message: 'Custo inválido' })
    .nonnegative('Custo não pode ser negativo')
    .nullable()
    .optional(),
  quantity: z
    .number({ message: 'Quantidade inválida' })
    .int('Deve ser inteiro')
    .nonnegative('Não pode ser negativa'),
  min_quantity: z
    .number({ message: 'Quantidade mínima inválida' })
    .int('Deve ser inteiro')
    .nonnegative('Não pode ser negativa'),
  category_id: z.number({ message: 'Selecione uma categoria' }).positive('Selecione uma categoria'),
  active: z.boolean().default(true),
});

type ProductForm = z.infer<typeof productSchema>;

// — Props ——————————————————————
interface ProductsCreateProps {
  categories: Category[];
  tags: Tag[];
}

// — Component ——————————————————————
export default function ProductsCreate({ categories, tags }: ProductsCreateProps) {
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
      prev.includes(tagId) ? prev.filter((id) => id !== tagId) : [...prev, tagId]
    );
  }

  function onSubmit(data: ProductForm) {
    setSubmitting(true);
    router.post(
      '/admin/products',
      { ...data, active: activeToggle, tags: selectedTags },
      {
        onFinish: () => setSubmitting(false),
      }
    );
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
              className="flex items-center gap-1.5 text-sm text-warm-500 hover:text-kintsugi-600 mb-3 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Voltar para Produtos
            </button>
            <h1 className="text-2xl font-bold text-warm-700">Novo Produto</h1>
            <p className="text-sm text-warm-500 mt-0.5">
              Preencha os dados para cadastrar um novo produto
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {/* Basic info card */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
                Informações básicas
              </h2>

              <FormField
                label="Nome do Produto"
                name="name"
                required
                placeholder="Ex.: Vaso Cerâmica Wabi-Sabi"
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
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
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
                  register={register('cost_price', {
                    setValueAs: (value) => (value === '' ? null : Number(value)),
                  })}
                  error={errors.cost_price?.message}
                  hint="Valor pago pelo produto (interno)"
                />
              </div>
            </div>

            {/* Stock card */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
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
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-4">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
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
                          ? 'bg-kintsugi-600 text-white border-kintsugi-600 shadow-sm'
                          : 'bg-white text-warm-600 border-warm-300 hover:border-kintsugi-400 hover:text-kintsugi-600',
                      ].join(' ')}
                    >
                      {selected && <span className="mr-1">✓</span>}
                      {tag.name}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Status card */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6">
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
              <Button
                type="button"
                variant="secondary"
                onClick={() => router.visit('/admin/products')}
              >
                Cancelar
              </Button>
              <Button type="submit" loading={submitting}>
                Cadastrar Produto
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AdminLayout>
  );
}
