import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FormField from '@/Components/Admin/FormField';
import Button from '@/Components/Shared/Button';
import type { Category } from '@/types/admin';

const toNullableSelectNumber = (value: unknown): number | null => {
  if (value === '' || value === null || value === undefined) {
    return null;
  }

  const parsedValue = Number(value);

  return Number.isInteger(parsedValue) ? parsedValue : null;
};

const categorySchema = z.object({
  name: z
    .string()
    .trim()
    .min(1, 'Informe o nome da categoria')
    .max(255, 'Nome deve ter no máximo 255 caracteres'),
  description: z.string().trim().optional(),
  parent_id: z.number().int().nullable().optional(),
  active: z.boolean().default(true),
});

type CategoryForm = z.infer<typeof categorySchema>;

// — Props ——————————————————————
interface CategoriesCreateProps {
  categories: Category[];
}

// — Component ——————————————————————
export default function CategoriesCreate({ categories }: CategoriesCreateProps) {
  const [activeToggle, setActiveToggle] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CategoryForm>({
    resolver: zodResolver(categorySchema) as Resolver<CategoryForm>,
    defaultValues: {
      active: true,
      parent_id: null,
    },
  });

  function onSubmit(data: CategoryForm) {
    setSubmitting(true);
    router.post(
      '/admin/categories',
      { ...data, active: activeToggle },
      {
        onFinish: () => setSubmitting(false),
      }
    );
  }

  const parentOptions = categories.map((cat) => ({ value: cat.id, label: cat.name }));

  return (
    <AdminLayout title="Nova Categoria">
      <div className="p-6">
        <div className="max-w-2xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <button
              type="button"
              onClick={() => router.visit('/admin/categories')}
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
              Voltar para Categorias
            </button>
            <h1 className="text-2xl font-bold text-warm-700">Nova Categoria</h1>
            <p className="text-sm text-warm-500 mt-0.5">
              Preencha os dados para criar uma nova categoria
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            {/* Main form card */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6 space-y-5">
              <h2 className="text-sm font-semibold text-warm-600 uppercase tracking-wider border-b border-warm-200 pb-3">
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

              <FormField
                label="Categoria Pai"
                name="parent_id"
                type="select"
                options={parentOptions}
                emptyOptionLabel="— Nenhuma (categoria raiz) —"
                register={register('parent_id', {
                  setValueAs: toNullableSelectNumber,
                })}
                hint="Opcional. Deixe em branco para criar uma categoria raiz."
                error={errors.parent_id?.message}
              />
            </div>

            {/* Status card */}
            <div className="bg-white rounded-xl border border-warm-200 shadow-xs p-6">
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
              <Button
                type="button"
                variant="secondary"
                onClick={() => router.visit('/admin/categories')}
              >
                Cancelar
              </Button>
              <Button type="submit" loading={submitting}>
                Criar Categoria
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AdminLayout>
  );
}
