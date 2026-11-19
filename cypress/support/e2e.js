// Inicia sesión desde el formulario de login
Cypress.Commands.add('iniciarSesion', (correo, clave) => {
  cy.visit('/login');
  cy.get('input[name="email"]').type(correo);
  cy.get('input[name="password"]').type(clave, { log: false });
  cy.get('button[type="submit"]').click();
});

// Comprueba que el navegador considera inválido un campo y por eso no envía el formulario
Cypress.Commands.add('campoInvalido', (selector) => {
  cy.get(selector).then(($campo) => {
    expect($campo[0].checkValidity(), `${selector} debe ser inválido`).to.equal(false);
  });
});

// Cada prueba parte con la base limpia y con datos conocidos
beforeEach(() => {
  cy.task('reiniciarBase');
});