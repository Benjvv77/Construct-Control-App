describe('HU07 - CP06: Eliminar ítem del presupuesto', () => {
  beforeEach(() => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/2');
  });

  it('Positivo: elimina un ítem sin gastos y baja el presupuesto', () => {
    cy.contains('#presupuesto tr', 'Ítem sin gastos').contains('button', 'Eliminar').click();

    cy.get('.alert.success').should('contain', 'Ítem eliminado del presupuesto.');
    cy.contains('#presupuesto tr', 'Ítem sin gastos').should('not.exist');

    cy.visit('/proyectos');
    cy.contains('tr', 'Obra del supervisor').should('contain', '$1.500.000');
  });

  it('Negativo: no elimina un ítem que tiene gastos', () => {
    cy.contains('#presupuesto tr', 'Cemento 25 kg').contains('button', 'Eliminar').click();

    cy.get('.alert.error').should('contain', 'No se puede eliminar "Cemento 25 kg": tiene 1 gasto asociado');
    cy.contains('#presupuesto tr', 'Cemento 25 kg').should('be.visible');
  });
});
