describe('HU03 - CP03: Creación de proyecto con sus datos', () => {
  beforeEach(() => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/proyectos');
    cy.contains('button', '+ Nuevo proyecto').click();
  });

  it('Positivo: crea el proyecto con datos válidos', () => {
    cy.get('#modalNuevoProyecto').within(() => {
      cy.get('input[name="nombre_proyecto"]').type('Edificio Talca');
      cy.get('input[name="fecha_inicio_proyecto"]').type('2026-11-10');
      cy.contains('button', 'Guardar Proyecto').click();
    });

    cy.get('.alert.success').should('contain', 'Proyecto creado');
    cy.contains('table', 'Edificio Talca').should('be.visible');
  });

  it('Negativo: rechaza una fecha de término anterior al inicio', () => {
    cy.get('#modalNuevoProyecto').within(() => {
      cy.get('input[name="nombre_proyecto"]').type('Edificio Curicó');
      cy.get('input[name="fecha_inicio_proyecto"]').type('2026-11-10');
      cy.get('input[name="fecha_termino_proyecto"]').type('2026-11-01');
      cy.contains('button', 'Guardar Proyecto').click();
    });

    cy.get('.alert.error').should('contain', 'La fecha de término debe ser igual o posterior');
    cy.contains('table', 'Edificio Curicó').should('not.exist');
  });
});