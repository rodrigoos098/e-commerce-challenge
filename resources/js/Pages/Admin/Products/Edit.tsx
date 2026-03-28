import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FormField from '@/Components/Admin/FormField';
import Button from '@/Components/Shared/Button';
import type { Product, Category, Tag } from '@/types/admin';
import { formatCurrencyInput, parseCurrencyInput } from '@/utils/format';

const toRequiredNumber = (value: unknown): number | undefined => {
  if (value === '' || value === null || value === undefined) {
    return undefined;
  }

  const parsedValue = Number(value);

  return Number.isFinite(parsedValue) ? parsedValue : undefined;
};

const toNullableNumber = (value: unknown): number | null | undefined => {
  if (value === '' || value === null || value === undefined) {
    return null;
  }

  const parsedValue = Number(value);

  return Number.isFinite(parsedValue) ? parsedValue : undefined;
};

const baseProductSchema = z.object({
  name: z
    .string()
    .trim()
    .min(1, 'Informe o nome do produto')
    .max(255, 'Nome deve ter no máximo 255 caracteres'),
  description: z.string().trim().min(1, 'Informe a descrição do produto'),
  price: z
    .number({ message: 'Informe um preço de venda válido' })
    .positive('Preço deve ser maior que zero'),
  cost_price: z
    .number({ message: 'Informe um custo válido' })
    .nonnegative('Custo não pode ser negativo')
    .nullable()
    .optional(),
  quantity: z
    .number({ message: 'Informe uma quantidade válida' })
    .int('Deve ser um número inteiro')
    .nonnegative('Não pode ser negativa'),
  min_quantity: z
    .number({ message: 'Informe uma quantidade mínima válida' })
    .int('Deve ser um número inteiro')
    .nonnegative('Não pode ser negativa')
    .nullable()
    .optional(),
  stock_adjustment_reason: z
    .string()
    .trim()
    .max(255, 'Motivo deve ter no máximo 255 caracteres')
    .optional(),
  category_id: z
    .number({ message: 'Selecione uma categoria válida' })
    .int()
    .positive('Selecione uma categoria válida'),
  active: z.boolean().default(true),
});

type ProductForm = z.infer<typeof baseProductSchema>;

// — Props ——————————————————————
interface ProductsEditProps {
  product: Product;
  categories: Category[];
  tags: Tag[];
}

// — Component ——————————————————————
export default function ProductsEdit({ product, categories, tags }: ProductsEditProps) {
  const [selectedTags, setSelectedTags] = useState<number[]>(product.tags.map((t) => t.id));
  const [activeToggle, setActiveToggle] = useState(product.active);
  const [submitting, setSubmitting] = useState(false);
  const [selectedImage, setSelectedImage] = useState<File | null>(null);
  const [imagePreviewUrl, setImagePreviewUrl] = useState<string | null>(product.image_url ?? null);
  const productSchema = baseProductSchema.superRefine((data, ctx) => {
    if (data.quantity !== product.quantity && !data.stock_adjustment_reason?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['stock_adjustment_reason'],
        message: 'Informe o motivo do ajuste de estoque',
      });
    }
  });

  const {
    control,
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ProductForm>({
    resolver: zodResolver(productSchema) as Resolver<ProductForm>,
    defaultValues: {
      name: product.name,
      description: product.description,
      price: product.price,
      cost_price: product.cost_price,
      quantity: product.quantity,
      min_quantity: product.min_quantity,
      stock_adjustment_reason: '',
      category_id: product.category?.id,
      active: product.active,
    },
  });

  useEffect(() => {
    if (!selectedImage) {
      setImagePreviewUrl(product.image_url ?? null);

      return;
    }

    const nextPreviewUrl = URL.createObjectURL(selectedImage);
    setImagePreviewUrl(nextPreviewUrl);

    return () => URL.revokeObjectURL(nextPreviewUrl);
  }, [product.image_url, selectedImage]);

  function toggleTag(tagId: number) {
    setSelectedTags((prev) =>
      prev.includes(tagId) ? prev.filter((id) => id !== tagId) : [...prev, tagId]
    );
  }

  function onSubmit(data: ProductForm) {
    setSubmitting(true);
    router.post(
      `/admin/products/${product.id}`,
      {
        _method: 'put',
        ...data,
        active: activeToggle,
        tags: selectedTags,
        image: selectedImage ?? undefined,
      },
      {
        forceFormData: true,
        onFinish: () => setSubmitting(false),
      }
    );
  }

  const categoryOptions = categories.map((cat) => ({
    value: cat.id,
    label: cat.parent_id ? `  └ ${cat.name}` : cat.name,
  }));

  return (
    <AdminLayout title={`Editar: ${product.name}`}>
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
            <div className="flex items-start justify-between">
              <div>
                <h1 className="text-2xl font-bold text-warm-700">Editar Produto</h1>
                <p className="text-sm text-warm-500 mt-0.5">{product.name}</p>
              </div>
              <a
                href={`/admin/products/${product.id}`}
                className="text-sm text-kintsugi-600 hover:text-kintsugi-700 font-medium transition-colors"
              >
                Ver detalhes →
              </a>
            </div>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {/* Basic info */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
                Informações básicas
              </h2>
              <div className="space-y-3">
                <FormField
                  label="Imagem do Produto"
                  name="image"
                  type="file"
                  accept="image/*"
                  hint="Envie JPG, PNG ou WEBP com até 5 MB"
                  onChange={(event) => {
                    const file =
                      event.target instanceof HTMLInputElement ? event.target.files?.[0] : null;
                    setSelectedImage(file ?? null);
                  }}
                />

                {imagePreviewUrl && (
                  <div className="overflow-hidden rounded-xl border border-warm-200 bg-warm-50 p-3">
                    <img
                      src={imagePreviewUrl}
                      alt="Pré-visualização da imagem do produto"
                      className="h-44 w-full rounded-lg object-cover"
                    />
                  </div>
                )}
              </div>
              <FormField
                label="Nome do Produto"
                name="name"
                required
                register={register('name')}
                error={errors.name?.message}
              />
              <FormField
                label="Descrição"
                name="description"
                type="textarea"
                required
                rows={4}
                register={register('description')}
                error={errors.description?.message}
              />
              <FormField
                label="Categoria"
                name="category_id"
                type="select"
                required
                options={categoryOptions}
                emptyOptionLabel="Selecione uma categoria"
                register={register('category_id', { setValueAs: toRequiredNumber })}
                error={errors.category_id?.message}
              />
            </div>

            {/* Pricing */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
                Preços
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Controller
                  name="price"
                  control={control}
                  render={({ field }) => (
                    <FormField
                      label="Preço de Venda"
                      name="price"
                      type="text"
                      required
                      placeholder="R$ 0,00"
                      inputMode="numeric"
                      value={formatCurrencyInput(field.value)}
                      onChange={(event) =>
                        field.onChange(parseCurrencyInput(event.target.value) ?? undefined)
                      }
                      error={errors.price?.message}
                    />
                  )}
                />
                <Controller
                  name="cost_price"
                  control={control}
                  render={({ field }) => (
                    <FormField
                      label="Preço de Custo"
                      name="cost_price"
                      type="text"
                      placeholder="R$ 0,00"
                      inputMode="numeric"
                      value={formatCurrencyInput(field.value ?? null)}
                      onChange={(event) => field.onChange(parseCurrencyInput(event.target.value))}
                      error={errors.cost_price?.message}
                    />
                  )}
                />
              </div>
            </div>

            {/* Stock */}
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
                  register={register('quantity', { setValueAs: toRequiredNumber })}
                  error={errors.quantity?.message}
                />
                <FormField
                  label="Quantidade Mínima"
                  name="min_quantity"
                  type="number"
                  min={0}
                  step={1}
                  register={register('min_quantity', { setValueAs: toNullableNumber })}
                  error={errors.min_quantity?.message}
                  hint="Opcional. Alertas de estoque baixo abaixo deste valor"
                />
              </div>
              <FormField
                label="Motivo do Ajuste"
                name="stock_adjustment_reason"
                register={register('stock_adjustment_reason')}
                error={errors.stock_adjustment_reason?.message}
                hint="Obrigatório quando a quantidade em estoque for alterada"
              />
            </div>

            {/* Tags */}
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

            {/* Status */}
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
                Salvar Alterações
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AdminLayout>
  );
}
