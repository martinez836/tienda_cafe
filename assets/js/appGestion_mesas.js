const mesasTableBody = document.querySelector('#mesasTableBody');
const mesasModal = new bootstrap.Modal(document.getElementById('mesasModal'));
const addMesasBtn = document.querySelector('#addMesasBtn');
const saveMesaBtn = document.querySelector('#saveMesa');
const mesaIdInput = document.querySelector('#mesaId');
let mesasData = []; // variable global para almacenar los datos de las mesas
let intervaloActualizacion = null;

document.addEventListener('DOMContentLoaded', () => {
    llenarTablaMesas();
    iniciarActualizacionAutomatica();
    
    // Detener actualización cuando la página pierde el foco
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            detenerActualizacionAutomatica();
        } else {
            iniciarActualizacionAutomatica();
        }
    });
});

// Iniciar actualización automática cada 60 segundos
function iniciarActualizacionAutomatica() {
    intervaloActualizacion = setInterval(() => {
        llenarTablaMesas();
    }, 60000); // 60 segundos
}

// Detener actualización automática
function detenerActualizacionAutomatica() {
    if (intervaloActualizacion) {
        clearInterval(intervaloActualizacion);
        intervaloActualizacion = null;
    }
}

const llenarTablaMesas = () =>{
    fetch('../../controllers/cargar_mesas.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la Peticion: ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Error al cargar las mesas');
        }
        
        mesasData = data.mesas; // Guardamos los datos para usarlos después

        // Limpiamos el cuerpo de la tabla antes de llenarlo
        mesasTableBody.innerHTML = '';

        // Mapa de estados → etiqueta y clase de badge
            const estadoConfig = {
                1: { label: 'Activo', badge: 'bg-success' },
                2: { label: 'Inactivo', badge: 'bg-secondary' }
            };
        
        data.mesas.forEach(mesa => {
            const fila = document.createElement('tr');

            // Elegimos la configuración según el id, o un defecto
            const cfg = estadoConfig[mesa.estados_idestados] || { label: 'Desconocido', badge: 'bg-light' };
            const estado = `<span class="badge ${cfg.badge}">${cfg.label}</span>`;

            fila.innerHTML = `
                <td class="text-center">${mesa.idmesas}</td>
                <td>${mesa.nombre}</td>
                <td class="text-center">${estado}</td>
                <td class="d-none d-md-table-cell text-center">
                    <button class="btn btn-warning btn-sm me-1" onclick="editarMesa(${mesa.idmesas})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="eliminarMesa(${mesa.idmesas})"><i class="fas fa-trash"></i></button>
                </td>
                <td class="d-md-none text-center">
                    <div class="d-flex flex-column gap-1">
                        <button class="btn btn-warning btn-sm w-100" onclick="editarMesa(${mesa.idmesas})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm w-100" onclick="eliminarMesa(${mesa.idmesas})"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            `;
            mesasTableBody.appendChild(fila);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
};

const editarMesa = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Deseas editar el nombre de esta mesa?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, editar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            mesasModal.show();
            const nombreMesaInput = document.querySelector('#nombreMesa');
            nombreMesaInput.value = '';
            const mesa = mesasData.find(m => Number(m.idmesas) === Number(id));
            if (mesa) {
                nombreMesaInput.value = mesa.nombre;
                mesaIdInput.value = mesa.idmesas;
            } else {
                mesaIdInput.value = '';
            }
        }
    });
};

const eliminarMesa = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción inactivará la mesa. ¿Deseas continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../controllers/eliminar_mesa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(id)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Mesa inactivada!', 'La mesa ha sido Eliminada.', 'success');
                    llenarTablaMesas();
                } else {
                    Swal.fire('Error', 'No se pudo inactivar la mesa.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Ocurrió un error en la petición.', 'error');
            });
        }
    });
};

addMesasBtn.addEventListener('click', () => {
    mesasModal.show();
    document.querySelector('#nombreMesa').value = '';
    mesaIdInput.value = '';
});

saveMesaBtn.addEventListener('click', () => {
    const id = mesaIdInput.value;
    const nombre = document.querySelector('#nombreMesa').value.trim();

    if (!nombre) {
        Swal.fire('Campo vacío', 'El nombre de la mesa no puede estar vacío', 'warning');
        return;
    }

    // Si hay id, editar; si no, agregar
    const url = id
        ? '../../controllers/editar_mesa.php'
        : '../../controllers/agregar_mesa.php';

    const body = id
        ? `id=${encodeURIComponent(id)}&nombre=${encodeURIComponent(nombre)}`
        : `nombre=${encodeURIComponent(nombre)}`;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mesasModal.hide();
            llenarTablaMesas();
            Swal.fire('¡Éxito!', id ? 'Mesa editada correctamente.' : 'Mesa agregada correctamente.', 'success');
        } else if (data.error === 'duplicado') {
            Swal.fire('Nombre duplicado', 'Ya existe una mesa activa con ese nombre.', 'warning');
        } else {
            Swal.fire('Error', 'No se pudo guardar la mesa', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Error en la petición', 'error'));
});
