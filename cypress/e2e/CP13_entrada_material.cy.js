describe('HU12 - CP13: Entrada manual de material', () => {
  beforeEach(() => {
    cy.iniciarSesion('supervisor@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/2/inventario');
    cy.contains('tr', 'Arena').contains('button', 'Agregar').click();
  });

  it('Positivo: el stock sube con la cantidad ingresada', () => {
    cy.get('#modalMovimiento input[name="cantidad_movimiento"]').type('20');
    cy.get('#mov_boton').click();

    cy.get('.alert.success').should('contain', 'Movimiento registrado correctamente.');
    cy.contains('tr', 'Arena').contains('strong', /40\s+m3/).should('be.visible');
  });

  it('Negativo: rechaza una fecha de entrada imposible', () => {
    cy.get('#modalMovimiento input[name="cantidad_movimiento"]').type('20');
    cy.get('#modalMovimiento input[name="fecha_movimiento"]').clear().type('2999-01-01');
    cy.get('#mov_boton').click();

    cy.get('.alert.error').should('be.visible');
    cy.contains('tr', 'Arena').contains('strong', /20\s+m3/).should('be.visible');
  });
});
