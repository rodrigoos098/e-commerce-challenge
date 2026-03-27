import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import FormField from '@/Components/Admin/FormField';
import Modal from '@/Components/Shared/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import type { Tag } from '@/types/shared';
import { appRoutes } from '@/utils/routes';

const tagSchema = z.object({
  name: z
    .string()
    .trim()
    .min(1, 'Informe o nome da tag')
    .max(255, 'Nome deve ter no maximo 255 caracteres'),
  slug: z.string().trim().max(255, 'Slug deve ter no maximo 255 caracteres').optional(),
});

type TagForm = z.infer<typeof tagSchema>;

interface TagsIndexProps {
  tags: Tag[];
}

const emptyTagForm: TagForm = {
  name: '',
  slug: '',
};

const normalizeTagPayload = (data: TagForm): { name: string; slug: string | null } => ({
  name: data.name.trim(),
  slug: data.slug?.trim() ? data.slug.trim() : null,
});

export default function TagsIndex({ tags }: TagsIndexProps) {
  const [editingTagId, setEditingTagId] = useState<number | null>(null);
  const [deleteModal, setDeleteModal] = useState<{ open: boolean; tag: Tag | null }>({
    open: false,
    tag: null,
  });
  const [submitting, setSubmitting] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<TagForm>({
    resolver: zodResolver(tagSchema) as Resolver<TagForm>,
    defaultValues: emptyTagForm,
  });

  function resetForm(): void {
    setEditingTagId(null);
    reset(emptyTagForm);
  }

  function handleSave(data: TagForm): void {
    setSubmitting(true);

    const payload = normalizeTagPayload(data);

    if (editingTagId) {
      router.put(appRoutes.admin.tags.show(editingTagId), payload, {
        onSuccess: () => resetForm(),
        onFinish: () => setSubmitting(false),
      });

      return;
    }

    router.post(appRoutes.admin.tags.index, payload, {
      onSuccess: () => reset(emptyTagForm),
      onFinish: () => setSubmitting(false),
    });
  }

  function startEditing(tag: Tag): void {
    setEditingTagId(tag.id);
    reset({
      name: tag.name,
      slug: tag.slug ?? '',
    });
  }

  function openDeleteModal(tag: Tag): void {
    setDeleteModal({ open: true, tag });
  }

  function handleDelete(): void {
    if (!deleteModal.tag) {
      return;
    }

    setDeleting(true);

    router.delete(appRoutes.admin.tags.show(deleteModal.tag.id), {
      onFinish: () => {
        setDeleting(false);
        setDeleteModal({ open: false, tag: null });
      },
    });
  }

  return (
    <AdminLayout title="Tags">
      <div className="space-y-6 p-6">
        <div className="flex flex-col gap-2">
          <h1 className="text-2xl font-bold text-warm-700">Tags</h1>
          <p className="text-sm text-warm-500">
            Gerencie tags comerciais para merchandising e curadoria de produtos.
          </p>
        </div>

        <div className="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
          <div className="rounded-2xl border border-warm-200 bg-white p-5 shadow-xs">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-500">
              {editingTagId ? 'Editar tag' : 'Nova tag'}
            </h2>

            <form onSubmit={handleSubmit(handleSave)} className="space-y-4">
              <FormField
                label="Nome"
                name="name"
                required
                register={register('name')}
                error={errors.name?.message}
                placeholder="Ex.: lancamento"
                hint="Use um nome curto e facil de reconhecer no admin."
                disabled={submitting}
              />

              <FormField
                label="Slug"
                name="slug"
                register={register('slug')}
                error={errors.slug?.message}
                placeholder="Opcional"
                hint="Opcional. Se vazio, o backend segue o comportamento padrao."
                disabled={submitting}
              />

              <div className="flex gap-3">
                <button
                  type="submit"
                  disabled={submitting}
                  className="rounded-xl bg-kintsugi-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:bg-kintsugi-300"
                >
                  {editingTagId ? 'Salvar alteracoes' : 'Criar tag'}
                </button>
                {editingTagId && (
                  <button
                    type="button"
                    onClick={resetForm}
                    disabled={submitting}
                    className="rounded-xl border border-warm-200 px-4 py-2.5 text-sm font-semibold text-warm-600 transition-colors hover:bg-warm-50 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    Cancelar
                  </button>
                )}
              </div>
            </form>
          </div>

          <div className="rounded-2xl border border-warm-200 bg-white p-5 shadow-xs">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-sm font-semibold uppercase tracking-wider text-warm-500">
                Lista de tags
              </h2>
              <span className="rounded-full bg-warm-100 px-3 py-1 text-xs font-semibold text-warm-600">
                {tags.length} cadastradas
              </span>
            </div>

            <div className="overflow-hidden rounded-2xl border border-warm-200">
              <table className="min-w-full divide-y divide-warm-200">
                <thead className="bg-warm-50">
                  <tr className="text-left text-xs uppercase tracking-wider text-warm-500">
                    <th className="px-4 py-3">Nome</th>
                    <th className="px-4 py-3">Slug</th>
                    <th className="px-4 py-3">Produtos</th>
                    <th className="px-4 py-3 text-right">Acoes</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-warm-100 bg-white text-sm text-warm-700">
                  {tags.map((tag) => (
                    <tr key={tag.id}>
                      <td className="px-4 py-3 font-medium">{tag.name}</td>
                      <td className="px-4 py-3 text-warm-500">{tag.slug}</td>
                      <td className="px-4 py-3">{tag.products_count ?? 0}</td>
                      <td className="px-4 py-3">
                        <div className="flex justify-end gap-3">
                          <button
                            type="button"
                            onClick={() => startEditing(tag)}
                            className="text-sm font-medium text-kintsugi-600 transition-colors hover:text-kintsugi-700"
                          >
                            Editar
                          </button>
                          <button
                            type="button"
                            onClick={() => openDeleteModal(tag)}
                            className="text-sm font-medium text-red-600 transition-colors hover:text-red-700"
                          >
                            Excluir
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {tags.length === 0 && (
                <div className="px-4 py-10 text-center text-sm text-warm-500">
                  Nenhuma tag cadastrada ainda.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <Modal
        isOpen={deleteModal.open}
        onClose={() => setDeleteModal({ open: false, tag: null })}
        title="Excluir tag"
        onConfirm={handleDelete}
        confirmLabel="Excluir tag"
        confirmDestructive
        loading={deleting}
      >
        <p className="text-sm leading-relaxed text-warm-600">
          A tag <strong className="font-semibold">{deleteModal.tag?.name}</strong> sera removida da
          curadoria.
        </p>
      </Modal>
    </AdminLayout>
  );
}
