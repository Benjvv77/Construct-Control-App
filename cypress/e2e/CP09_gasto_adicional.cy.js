describe('HU09 - CP09: Registro de gasto adicional con justificación', () => {
  beforeEach(() => {
    cy.iniciarSesion('supervisor@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/2');

    cy.get('#nuevo_clase_adicional').check();
    cy.get('#nuevo_id_tipo_gasto').select('Transporte');
    cy.get('#nuevo_nombre_gasto').type('Flete urgente');
    cy.get('#nuevo_monto_gasto').type('30000');
  });

  it('Positivo: con justificación queda pendiente', () => {
    cy.get('#nuevo_descripcion_gasto').type('Se atrasó el proveedor');
    cy.contains('#formNuevoGasto button', 'Registrar gasto').click();

    cy.get('.alert.success').should('contain', 'Queda pendiente hasta que un administrador lo apruebe.');
    cy.contains('tr', 'Flete urgente').should('contain', 'Pendiente');
  });

  it('Negativo: rechaza una justificación con solo espacios', () => {
    cy.get('#nuevo_descripcion_gasto').type('   ');
    cy.contains('#formNuevoGasto button', 'Registrar gasto').click();

    cy.get('.alert.error').should('contain', 'Explica por qué se necesita este gasto adicional.');
    cy.contains('tr', 'Flete urgente').should('not.exist');
  });
});
