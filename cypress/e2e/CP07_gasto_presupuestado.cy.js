describe('HU08 - CP07: Registro de gasto con boleta', () => {
  beforeEach(() => {
    cy.iniciarSesion('supervisor@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/2');

    cy.get('#nuevo_clase_presupuestado').check();
    cy.get('#nuevo_id_item_presupuesto').select('Instalación eléctrica — Servicios');
    cy.get('#nuevo_nombre_gasto').type('Cableado');
    cy.get('#nuevo_monto_gasto').type('50000');
  });

  it('Positivo: con boleta el gasto queda pendiente', () => {
    cy.get('#nuevo_documento_gasto').selectFile('cypress/fixtures/boleta.pdf');
    cy.contains('#formNuevoGasto button', 'Registrar gasto').click();

    cy.get('.alert.success').should('contain', 'Queda pendiente hasta que un administrador lo apruebe.');
    cy.contains('tr', 'Cableado').should('contain', 'Pendiente');
  });

  it('Negativo: rechaza un monto que se guardaría como $0', () => {
    // Simula a alguien que se salta la validación del navegador
    cy.get('#nuevo_monto_gasto').invoke('attr', 'step', 'any').invoke('removeAttr', 'min').clear().type('0.004');
    cy.get('#nuevo_documento_gasto').selectFile('cypress/fixtures/boleta.pdf');
    cy.contains('#formNuevoGasto button', 'Registrar gasto').click();

    cy.get('.alert.error').should('be.visible');
    cy.contains('tr', 'Cableado').should('not.exist');
  });
});
