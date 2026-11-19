describe('HU07 - CP05: Agregar ítem al presupuesto', () => {
  beforeEach(() => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/proyectos/1');
    cy.contains('button', '+ Agregar ítem').click();

    cy.get('#item_descripcion_item').type('Cemento 25 kg');
    cy.get('#item_id_tipo_gasto').select('Materiales');
    cy.get('#item_unidad_item').type('sacos');
    cy.get('#item_precio_unitario').type('5000');
  });

  it('Positivo: el presupuesto pasa a ser cantidad por precio', () => {
    cy.get('#item_cantidad').type('100');
    cy.get('#itemBoton').click();

    cy.get('.alert.success').should('contain', 'Ítem agregado al presupuesto.');
    cy.contains('#presupuesto tr', 'Cemento 25 kg').should('contain', '$500.000');

    cy.visit('/proyectos');
    cy.contains('tr', 'Obra del administrador').should('contain', '$500.000');
  });

  it('Negativo: rechaza con un mensaje una cantidad y un precio fuera de rango', () => {
    cy.get('#item_cantidad').type('9999999999');
    cy.get('#item_precio_unitario').clear().type('9999999999');
    cy.get('#itemBoton').click();

    // Debería avisar con un mensaje de validación, no con un error del servidor
    cy.get('.alert.error').should('be.visible');
    cy.visit('/proyectos/1');
    cy.contains('#presupuesto tr', 'Cemento 25 kg').should('not.exist');
  });
});
