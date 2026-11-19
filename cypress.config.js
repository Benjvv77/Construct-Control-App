const { execSync } = require('node:child_process');
const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://127.0.0.1:8001',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.js',
    video: false,
    setupNodeEvents(on) {
      on('task', {
        reiniciarBase() {
          execSync('npm run cypress:bd', { stdio: 'ignore' });
          return null;
        },
      });
    },
  },
});