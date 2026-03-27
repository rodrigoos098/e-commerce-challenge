import React from 'react';
import type { UseFormRegisterReturn } from 'react-hook-form';

type FieldType =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'url'
  | 'textarea'
  | 'select'
  | 'toggle'
  | 'file'
  | 'date';

interface SelectOption {
  value: string | number;
  label: string;
}

interface FormFieldProps {
  label: string;
  name: string;
  type?: FieldType;
  register?: UseFormRegisterReturn;
  error?: string;
  placeholder?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  options?: SelectOption[];
  rows?: number;
  /** For toggle: the current controlled value */
  checked?: boolean;
  /** For toggle: change handler */
  onToggle?: (value: boolean) => void;
  className?: string;
  min?: number | string;
  max?: number | string;
  step?: number | string;
  includeEmptyOption?: boolean;
  emptyOptionLabel?: string;
}

const baseInputClasses =
  'w-full rounded-lg border border-warm-300 bg-white px-3 py-2 text-sm text-warm-700 placeholder-warm-400 ' +
  'focus:border-kintsugi-500 focus:outline-none focus:ring-2 focus:ring-kintsugi-500/20 ' +
  'disabled:bg-warm-50 disabled:text-warm-400 disabled:cursor-not-allowed ' +
  'transition-colors duration-150';

const errorInputClasses = 'border-red-400 focus:border-red-500 focus:ring-red-500/20';

export default function FormField({
  label,
  name,
  type = 'text',
  register,
  error,
  placeholder,
  hint,
  required = false,
  disabled = false,
  options = [],
  rows = 4,
  checked,
  onToggle,
  className = '',
  min,
  max,
  step,
  includeEmptyOption = true,
  emptyOptionLabel = '— Selecione —',
}: FormFieldProps) {
  const fieldClasses = [baseInputClasses, error ? errorInputClasses : ''].join(' ').trim();
  const hintId = hint ? `${name}-hint` : undefined;
  const errorId = error ? `${name}-error` : undefined;
  const describedBy = [errorId, hintId].filter(Boolean).join(' ') || undefined;

  const renderField = () => {
    if (type === 'toggle') {
      return (
        <button
          type="button"
          id={name}
          role="switch"
          aria-checked={checked}
          disabled={disabled}
          onClick={() => onToggle?.(!checked)}
          className={[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-kintsugi-500/20',
            checked ? 'bg-kintsugi-600' : 'bg-warm-300',
            disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
          ].join(' ')}
        >
          <span
            className={[
              'inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-150',
              checked ? 'translate-x-6' : 'translate-x-1',
            ].join(' ')}
          />
        </button>
      );
    }

    if (type === 'textarea') {
      return (
        <textarea
          id={name}
          rows={rows}
          placeholder={placeholder}
          disabled={disabled}
          className={fieldClasses}
          aria-invalid={Boolean(error)}
          aria-describedby={describedBy}
          {...register}
        />
      );
    }

    if (type === 'select') {
      return (
        <select
          id={name}
          disabled={disabled}
          className={fieldClasses}
          aria-invalid={Boolean(error)}
          aria-describedby={describedBy}
          {...register}
        >
          {includeEmptyOption && <option value="">{emptyOptionLabel}</option>}
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      );
    }

    if (type === 'file') {
      return (
        <input
          id={name}
          type="file"
          disabled={disabled}
          className={[
            fieldClasses,
            'file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium',
            'file:bg-kintsugi-50 file:text-kintsugi-700 hover:file:bg-kintsugi-100',
          ].join(' ')}
          aria-invalid={Boolean(error)}
          aria-describedby={describedBy}
          {...register}
        />
      );
    }

    return (
      <input
        id={name}
        type={type}
        placeholder={placeholder}
        disabled={disabled}
        min={min}
        max={max}
        step={step}
        className={fieldClasses}
        aria-invalid={Boolean(error)}
        aria-describedby={describedBy}
        {...register}
      />
    );
  };

  return (
    <div className={['space-y-1.5', className].join(' ')}>
      <div className={type === 'toggle' ? 'flex items-center justify-between' : ''}>
        <label htmlFor={name} className="block text-sm font-medium text-warm-600">
          {label}
          {required && <span className="text-red-500 ml-0.5">*</span>}
        </label>
        {type === 'toggle' && renderField()}
      </div>

      {type !== 'toggle' && renderField()}

      {hint && !error && (
        <p id={hintId} className="text-xs text-warm-500">
          {hint}
        </p>
      )}
      {error && (
        <p id={errorId} className="text-xs text-red-500 flex items-center gap-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-3.5 w-3.5 flex-shrink-0"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fillRule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clipRule="evenodd"
            />
          </svg>
          {error}
        </p>
      )}
    </div>
  );
}
