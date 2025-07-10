document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const correo = document.getElementById('correo').value.trim();
      const contrasena = document.getElementById('contrasena').value.trim();
      
      // Validación del lado cliente
      if (!correo) {
        Swal.fire('Error', 'El correo electrónico es requerido', 'error');
        return;
      }
      
      if (!contrasena) {
        Swal.fire('Error', 'La contraseña es requerida', 'error');
        return;
      }
      
      // Validar formato de correo
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(correo)) {
        Swal.fire('Error', 'Formato de correo electrónico inválido', 'error');
        return;
      }
      
      // Validar longitud de contraseña
      if (contrasena.length < 5) {
        Swal.fire('Error', 'La contraseña debe tener al menos 5 caracteres', 'error');
        return;
      }
      
      if (contrasena.length > 255) {
        Swal.fire('Error', 'La contraseña es demasiado larga', 'error');
        return;
      }
      
      // Validar caracteres permitidos en contraseña
      const passwordRegex = /^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+$/;
      if (!passwordRegex.test(contrasena)) {
        Swal.fire('Error', 'La contraseña contiene caracteres no permitidos', 'error');
        return;
      }
      
      try {
        const response = await fetch('../controllers/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ correo, contrasena })
        });
        
        const data = await response.json();
        if (data.success) {
          window.location.href = 'mesero.php';
        } else {
          Swal.fire('Error', data.message || 'Credenciales incorrectas', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Error de conexión', 'error');
      }
    });
  }
});

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