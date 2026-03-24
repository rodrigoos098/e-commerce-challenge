import React from 'react';
import type { Category } from '@/types/public';

interface CategoryFilterProps {
    categories: Category[];
    selected: number | string | null;
    onChange: (categoryId: number | string | null) => void;
}

function CategoryItem({
    category,
    selected,
    onChange,
    depth = 0,
}: {
    category: Category;
    selected: number | string | null;
    onChange: (id: number | string | null) => void;
    depth?: number;
}) {
    const isSelected = String(selected) === String(category.id);

    return (
        <li>
            <button
                type="button"
                onClick={() => onChange(isSelected ? null : category.id)}
                className={`w-full text-left rounded-lg px-3 py-2 text-sm transition-colors duration-150 flex items-center gap-2
                    ${depth > 0 ? 'pl-6' : ''}
                    ${isSelected
                        ? 'bg-kintsugi-50 text-kintsugi-600 font-semibold'
                        : 'text-warm-600 hover:bg-warm-100 hover:text-warm-700'
                    }`}
            >
                {isSelected && (
                    <span className="h-1.5 w-1.5 rounded-full bg-kintsugi-500 shrink-0" aria-hidden="true" />
                )}
                {category.name}
            </button>
            {category.children && category.children.length > 0 && (
                <ul className="ml-2">
                    {category.children.map((child) => (
                        <CategoryItem
                            key={child.id}
                            category={child}
                            selected={selected}
                            onChange={onChange}
                            depth={depth + 1}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function CategoryFilter({ categories, selected, onChange }: CategoryFilterProps) {
    return (
        <div>
            <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-warm-500">Categorias</h3>
            <ul className="space-y-0.5">
                <li>
                    <button
                        type="button"
                        onClick={() => onChange(null)}
                        className={`w-full text-left rounded-lg px-3 py-2 text-sm transition-colors duration-150 ${
                            !selected
                                ? 'bg-kintsugi-50 text-kintsugi-600 font-semibold'
                                : 'text-warm-600 hover:bg-warm-100 hover:text-warm-700'
                        }`}
                    >
                        Todas as categorias
                    </button>
                </li>
                {categories.map((cat) => (
                    <CategoryItem
                        key={cat.id}
                        category={cat}
                        selected={selected}
                        onChange={onChange}
                    />
                ))}
            </ul>
        </div>
    );
}
