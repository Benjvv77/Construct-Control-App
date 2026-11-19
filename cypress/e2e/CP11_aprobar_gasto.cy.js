describe('HU10 - CP11: Aprobación de un gasto', () => {
  beforeEach(() => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/gastos/pendientes');
  });

  it('Positivo: al aprobarlo el monto suma a lo gastado', () => {
    cy.contains('tr', 'Gasto por revisar').contains('button', 'Aprobar').click();

    cy.get('.alert.success').should('contain', 'Gasto "Gasto por revisar" aprobado.');
    cy.contains('tr', 'Gasto por revisar').should('not.exist');

    cy.visit('/proyectos/2');
    cy.contains('#presupuesto tr', 'Instalación eléctrica').should('contain', '$30.000').and('not.contain', 'por aprobar');
  });

  it('Negativo: un gasto ya revisado no se puede aprobar de nuevo', () => {
    // Otra pestaña lo aprueba mientras esta página sigue mostrándolo como pendiente
    cy.contains('tr', 'Gasto por revisar').find('form').first().then(($form) => {
      cy.request({
        method: 'POST',
        url: $form.attr('action'),
        form: true,
        body: { _token: $form.find('input[name="_token"]').val() },
      });
    });

    cy.contains('tr', 'Gasto por revisar').contains('button', 'Aprobar').click();

    cy.get('.alert.error').should('contain', 'Este gasto ya fue revisado.');
  });
});
