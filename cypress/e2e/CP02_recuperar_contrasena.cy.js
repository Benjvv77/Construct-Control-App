describe('HU01 - CP02: Recuperación de contraseña sin revelar cuentas', () => {
  const generico = 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.';

  const pedirEnlace = (correo) => {
    cy.visit('/olvide-contrasena');
    cy.get('input[name="email"]').type(correo);
    cy.contains('button', 'Enviar enlace').click();
  };

  it('Positivo: con un correo registrado muestra el mensaje genérico', () => {
    pedirEnlace('supervisor@prueba.test');

    cy.get('.ok-box').should('contain', generico);
  });

  it('Negativo: al repetir la solicitud no revela que la cuenta existe', () => {
    // Un correo que no existe recibe siempre el mensaje genérico
    pedirEnlace('noexiste@prueba.test');
    pedirEnlace('noexiste@prueba.test');
    cy.get('.ok-box').should('contain', generico);

    // Un correo que sí existe debería recibir exactamente la misma respuesta
    pedirEnlace('supervisor@prueba.test');
    pedirEnlace('supervisor@prueba.test');
    cy.get('.error-box').should('not.exist');
    cy.get('.ok-box').should('contain', generico);
  });
});
