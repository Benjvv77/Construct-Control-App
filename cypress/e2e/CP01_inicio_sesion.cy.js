describe('HU01 - CP01: Inicio de sesión con credenciales', () => {
  it('Positivo: entra con correo y contraseña correctos', () => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    
    cy.location('pathname').should('eq', '/');
    cy.visit('/proyectos');
    cy.location('pathname').should('eq', '/proyectos');
  });

  it('Negativo: no entra con una contraseña incorrecta', () => {
    cy.iniciarSesion('admin@prueba.test', 'ClaveMala123');

    cy.location('pathname').should('eq', '/login');
    cy.contains('Credenciales incorrectas. Intenta nuevamente.').should('be.visible');
  });
});