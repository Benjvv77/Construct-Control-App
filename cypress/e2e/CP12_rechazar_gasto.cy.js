describe('HU10 - CP12: Rechazo de un gasto con motivo', () => {
  beforeEach(() => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/gastos/pendientes');
    cy.contains('tr', 'Gasto por revisar').contains('button', 'Rechazar').click();
  });

  it('Positivo: con motivo queda rechazado y el Supervisor lo ve', () => {
    cy.get('#modalRechazo textarea[name="motivo_rechazo"]').type('La boleta no se lee');
    cy.contains('#modalRechazo button', 'Rechazar gasto').click();

    cy.get('.alert.success').should('contain', 'Gasto "Gasto por revisar" rechazado.');

    cy.clearCookies();
    cy.iniciarSesion('supervisor@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/2');
    cy.contains('tr', 'Gasto por revisar').should('contain', 'La boleta no se lee');
  });

  it('Negativo: rechaza un motivo con solo espacios', () => {
    cy.get('#modalRechazo textarea[name="motivo_rechazo"]').type('   ');
    cy.contains('#modalRechazo button', 'Rechazar gasto').click();

    cy.get('.alert.error').should('contain', 'Indica el motivo del rechazo');
    cy.contains('tr', 'Gasto por revisar').should('be.visible');
  });
});
