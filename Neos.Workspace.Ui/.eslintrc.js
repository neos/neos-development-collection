module.exports = {
    root: true,
    parser: '@typescript-eslint/parser',
    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended',
        'plugin:react/recommended',
        'plugin:prettier/recommended',
    ],
    plugins: ['prettier', 'react', 'react-hooks'],
    settings: {
        react: {
            version: 'detect',
        },
    },
    env: {
        browser: true,
        es6: true,
        node: true,
    },
    rules: {
        '@typescript-eslint/explicit-function-return-type': 'off',
        '@typescript-eslint/explicit-module-boundary-types': 'off',
        '@typescript-eslint/no-explicit-any': 'off',
        '@typescript-eslint/no-var-requires': 'off',
        '@typescript-eslint/ban-ts-ignore': 'off',
        '@typescript-eslint/ban-ts-comment': 'off',
        'react/prop-types': 'off',
        'prettier/prettier': ['error'],
        'react-hooks/rules-of-hooks': 'error',
    },
};
