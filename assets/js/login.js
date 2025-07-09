document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const alertBox = document.getElementById('loginAlert');
    const submitBtn = form.querySelector('button[type="submit"]');
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Submit interceptado');

        // Validación antes del envío
        let isValid = true;
        if (!emailInput.value.trim()) {
            emailInput.classList.add('is-invalid');
            isValid = false;
        }
        if (!passwordInput.value.trim()) {
            passwordInput.classList.add('is-invalid');
            isValid = false;
        }
        if (!isValid) {
            showAlert('Por favor, completa todos los campos.', 'warning');
            return;
        }

        // Limpiar alertas anteriores
        alertBox.innerHTML = '';
        alertBox.style.display = 'none';

        // Deshabilitar botón durante el envío
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';

        const formData = new FormData(form);
        fetch('../controllers/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta cruda fetch:', response);
            if (!response.ok) {
                throw new Error('Error de red');
            }
            return response.text();
        })
        .then(text => {
            console.log('Texto recibido:', text);
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                showAlert('Respuesta inesperada del servidor.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
                console.error('Error al parsear JSON:', e);
                return;
            }
            console.log('JSON parseado:', data);
            if (data.success) {
                console.log('Redirigiendo a:', data.redirect);
                window.location.href = data.redirect;
            } else {
                showAlert(data.message, 'danger');
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión. Por favor, intenta nuevamente.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
        });
        console.log('Fin del submit JS');
    });

    function showAlert(message, type) {
        alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'danger' ? 'exclamation-triangle' : 'check-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertBox.style.display = 'block';

        setTimeout(() => {
            const alert = alertBox.querySelector('.alert');
            if (alert) {
                alert.remove();
                alertBox.style.display = 'none';
            }
        }, 5000);
    }

    emailInput.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });

    passwordInput.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
}); 