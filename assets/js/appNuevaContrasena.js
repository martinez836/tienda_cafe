/**
 * Funcionalidad para la página de nueva contraseña
 */

/**
 * Función para mostrar/ocultar contraseña
 * @param {string} inputId - ID del input de contraseña
 * @param {HTMLElement} btn - Botón que activa la función
 */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "🙈";
    } else {
        input.type = "password";
        btn.textContent = "👁";
    }
}

/**
 * Validar formato de contraseña
 * @param {string} password - Contraseña a validar
 * @returns {boolean} - True si es válida, false en caso contrario
 */
function validatePassword(password) {
    // Validar longitud mínima
    if (password.length < 5) {
        Swal.fire({
            icon: 'error',
            title: 'Contraseña muy corta',
            text: 'La contraseña debe tener al menos 5 caracteres.',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    
    // Validar longitud máxima
    if (password.length > 255) {
        Swal.fire({
            icon: 'error',
            title: 'Contraseña muy larga',
            text: 'La contraseña no puede exceder 255 caracteres.',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    
    // Validar caracteres permitidos
    const passwordRegex = /^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+$/;
    if (!passwordRegex.test(password)) {
        Swal.fire({
            icon: 'error',
            title: 'Caracteres no permitidos',
            text: 'La contraseña contiene caracteres no permitidos.',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    
    return true;
}

/**
 * Validar que las contraseñas coincidan
 * @param {string} password - Contraseña principal
 * @param {string} confirm - Confirmación de contraseña
 * @returns {boolean} - True si coinciden, false en caso contrario
 */
function validatePasswordMatch(password, confirm) {
    if (password !== confirm) {
        Swal.fire({
            icon: 'error',
            title: 'Las contraseñas no coinciden',
            text: 'Por favor, asegúrate de que ambas contraseñas sean iguales.',
            confirmButtonColor: '#3085d6'
        });
        return false;
    }
    return true;
}

/**
 * Manejar el envío del formulario de nueva contraseña
 * @param {Event} e - Evento del formulario
 */
function handlePasswordUpdate(e) {
    e.preventDefault();
    
    const password = document.getElementById('nueva_contrasena').value.trim();
    const confirm = document.getElementById('confirmar_contrasena').value.trim();
    
    // Validar contraseña
    if (!validatePassword(password)) {
        return;
    }
    
    // Validar que las contraseñas coincidan
    if (!validatePasswordMatch(password, confirm)) {
        return;
    }
    
    // Enviar formulario
    fetch('../controllers/actualizar_contrasena.php', {
        method: 'POST',
        body: new FormData(e.target)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Contraseña Actualizada!',
                text: data.message || 'Tu contraseña ha sido actualizada exitosamente.',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = './login.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Hubo un error al actualizar la contraseña.',
                confirmButtonColor: '#3085d6'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al procesar tu solicitud.',
            confirmButtonColor: '#3085d6'
        });
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formNuevaContrasena');
    if (form) {
        form.addEventListener('submit', handlePasswordUpdate);
    }
}); 