describe('HU03 - CP04: Permiso para crear proyectos', () => {
  it('Positivo: el Administrador ve el botón para crear', () => {
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/proyectos');

    cy.contains('button', '+ Nuevo proyecto').should('be.visible');
  });

  it('Negativo: el Supervisor no puede crear proyectos', () => {
    cy.iniciarSesion('supervisor@prueba.test', 'Prueba1234');
    cy.visit('/proyectos');

    // No tiene el botón en pantalla
    cy.contains('+ Nuevo proyecto').should('not.exist');

    // Tampoco puede crear enviando la petición directamente al servidor
    cy.get('input[name="_token"]').first().invoke('val').then((token) => {
      cy.request({
        method: 'POST',
        url: '/proyectos',
        form: true,
        failOnStatusCode: false,
        body: {
          _token: token,
          nombre_proyecto: 'Proyecto no autorizado',
          fecha_inicio_proyecto: '2026-11-10',
          estado_proyecto: 1,
        },
      }).its('status').should('eq', 403);
    });

    // El Administrador comprueba que el proyecto no se creó
    cy.clearCookies();
    cy.iniciarSesion('admin@prueba.test', 'Prueba1234');
    cy.visit('/proyectos');
    cy.contains('table', 'Proyecto no autorizado').should('not.exist');
  });
});