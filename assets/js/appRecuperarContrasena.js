RecuperarContrasenaForm = document.querySelector('#RecuperarContrasenaForm');
RecuperarContrasenaForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const correo = document.querySelector("#correo").value;
    
    fetch('../controllers/recuperacion_contrasena.php', {
        method: "POST",
        body: JSON.stringify({ correo }),
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(respuesta => respuesta.json())
    .then(datos => {
        if (datos.success) {
            Swal.fire({
                icon: 'success',
                title: 'Correo enviado',
                text: 'Se ha enviado un correo con las instrucciones para recuperar su contraseña.',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = './login.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: datos.message || 'Error al enviar el correo. Por favor, intente nuevamente.',
                confirmButtonColor: '#3085d6'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#3085d6'
        });
    });
});
