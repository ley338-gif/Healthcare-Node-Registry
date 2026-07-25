import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['vendor', 'node_modules', 'public/build'] },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
);
