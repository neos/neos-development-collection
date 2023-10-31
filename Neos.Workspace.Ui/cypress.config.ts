import { defineConfig } from 'cypress';

export default defineConfig({
    e2e: {
        setupNodeEvents(on, config) {
            return require('./cypress/plugins/index.js')(on, config);
        },
        specPattern: 'Tests/integration/**/*.cy.{js,jsx,ts,tsx}',
        baseUrl: 'http://localhost:4000',
    },
});
