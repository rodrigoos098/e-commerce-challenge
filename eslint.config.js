import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import reactPlugin from 'eslint-plugin-react';
import reactHooksPlugin from 'eslint-plugin-react-hooks';

export default [
    {
        ignores: ['bootstrap/**', 'node_modules/**', 'public/**', 'storage/**', 'vendor/**'],
    },
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
            globals: {
                Blob: 'readonly',
                File: 'readonly',
                FormData: 'readonly',
                HTMLElement: 'readonly',
                HTMLFormElement: 'readonly',
                HTMLInputElement: 'readonly',
                HTMLTextAreaElement: 'readonly',
                MouseEvent: 'readonly',
                URLSearchParams: 'readonly',
                clearTimeout: 'readonly',
                console: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                setTimeout: 'readonly',
                window: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
            react: reactPlugin,
            'react-hooks': reactHooksPlugin,
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            'react/react-in-jsx-scope': 'off',
            'react-hooks/exhaustive-deps': 'warn',
            'react-hooks/rules-of-hooks': 'error',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
];
