document.addEventListener('DOMContentLoaded', function() {
    const pedidos_pendientes = document.getElementById('pedidos_pendientes');
    const detalles_pedido = document.getElementById('detalles_pedido');

    let idPedidoSeleccionado = null;
    let intervaloActualizacion = null;

    // Función para obtener y mostrar pedidos pendientes
    function obtenerPedidosPendientes() {
        fetch('../controllers/cocina.php?action=traer_pedidos_pendientes')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            console.log('Obteniendo pedidos pendientes...', response);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderizarPedidosPendientes(data.data);
                console.log('Pedidos pendientes obtenidos:', data.data);
            } else {
                renderizarPedidosPendientes([]);
                console.log('No se encontraron pedidos pendientes o la respuesta no es válida.');
            }
        })
        .catch(error => {
            showSwalError('Error al obtener pedidos pendientes.');
            console.log('Error al obtener pedidos pendientes:', error);
        });

    }

    // Función para renderizar los pedidos pendientes en la lista
    function renderizarPedidosPendientes(pedidos) {
        pedidos_pendientes.innerHTML = ''; // Limpiar lista existente
        if (pedidos.length === 0) {
            pedidos_pendientes.innerHTML = `
                <div class="estado-vacio texto-centrado py-5">
                    <i class="fas fa-check-circle fa-3x texto-exito mb-3"></i>
                    <h5>No hay pedidos pendientes</h5>
                    <p>Todos los pedidos han sido preparados.</p>
                </div>
            `;
            detalles_pedido.innerHTML = `
                <div class="estado-vacio texto-centrado py-5">
                    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                    <h5>Selecciona un pedido</h5>
                    <p>Haz clic en un pedido de la lista para ver los detalles y prepararlo.</p>
                </div>
            `;
            idPedidoSeleccionado = null;
            return;
        }

        const ul = document.createElement('ul');
        ul.classList.add('list-group', 'list-group-flush');

        pedidos.forEach(pedido => {
            const li = document.createElement('li');
            li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center', 'elemento-pedido');
            if (pedido.id === idPedidoSeleccionado) {
                li.classList.add('seleccionado');
            }
            li.dataset.orderId = pedido.id;
            li.innerHTML = `
                <div>
                    <h6 class="mb-1">Pedido #${pedido.id} - ${pedido.table}</h6>
                    <small class="text-muted">${pedido.time}</small>
                </div>
                <span class="badge rounded-pill bg-warning text-dark insignia-estado insignia-pendiente">Pendiente</span>
            `;
            li.addEventListener('click', () => seleccionarPedido(pedido.id));
            ul.appendChild(li);
        });
        pedidos_pendientes.appendChild(ul);

        // Si un pedido fue previamente seleccionado, volver a seleccionarlo para mostrar los detalles
        if (idPedidoSeleccionado) {
            seleccionarPedido(idPedidoSeleccionado);
        } else if (pedidos.length > 0) {
            // Seleccionar automáticamente el primer pedido si no hay ninguno seleccionado y hay pedidos
            seleccionarPedido(pedidos[0].id);
        }
    }

    // Función para seleccionar un pedido y mostrar sus detalles
    function seleccionarPedido(idPedido) {
        idPedidoSeleccionado = idPedido;
        // Actualizar la UI para mostrar el pedido seleccionado
        document.querySelectorAll('.elemento-pedido').forEach(item => {
            if (parseInt(item.dataset.orderId) === idPedido) {
                item.classList.add('seleccionado');
            } else {
                item.classList.remove('seleccionado');
            }
        });

        detalles_pedido.innerHTML = `
            <div class="estado-vacio texto-centrado py-5">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <h5>Cargando detalles del pedido...</h5>
                <p>Por favor, espera.</p>
            </div>
        `;

        fetch(`../controllers/cocina.php?action=traerDetallesDeUnPedido&id=${idPedido}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderizarDetallesPedido(data.pedido);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudieron cargar los detalles del pedido.',
                });
                detalles_pedido.innerHTML = `
                    <div class="estado-vacio texto-centrado py-5">
                        <i class="fas fa-exclamation-circle fa-3x texto-peligro mb-3"></i>
                        <h5>Error al cargar detalles</h5>
                        <p>No se pudo obtener el pedido seleccionado.</p>
                    </div>
                `;
            }
        })
        .catch(error => showSwalError('Error al obtener detalles del pedido.'));
    }

    // Función para renderizar los detalles del pedido en el panel
    function renderizarDetallesPedido(pedido) {
        if (!pedido) {
            detalles_pedido.innerHTML = `
                <div class="estado-vacio texto-centrado py-5">
                    <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                    <h5>Selecciona un pedido</h5>
                    <p>Haz clic en un pedido de la lista para ver los detalles y prepararlo.</p>
                </div>
            `;
            return;
        }

        let htmlItems = pedido.items.map(item => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    ${item.quantity}x ${item.name}
                    ${item.observations ? `<br><small class="text-muted">(${item.observations})</small>` : ''}
                </div>
            </li>
        `).join('');

        detalles_pedido.innerHTML = `
            <h5 class="mb-3">Pedido #${pedido.idpedidos} - ${pedido.nombre_mesa} <span class="badge rounded-pill bg-info insignia-estado insignia-preparacion">En Preparación</span></h5>
            <p class="text-muted">Hora de Pedido: ${pedido.time}</p>
            <h6>Items:</h6>
            <ul class="list-group mb-4">
                ${htmlItems}
            </ul>
            <button class="btn btn-success btn-lg w-100" onclick="marcarPedidoComoListo(${pedido.idpedidos})">
                <i class="fas fa-check-circle me-2"></i>Marcar como Preparado
            </button>
        `;
    }

    // Iniciar actualización automática cada 30 segundos
    function iniciarActualizacionAutomatica() {
        intervaloActualizacion = setInterval(() => {
            obtenerPedidosPendientes();
        }, 7000); // 7 segundos
    }

    // Detener actualización automática
    function detenerActualizacionAutomatica() {
        if (intervaloActualizacion) {
            clearInterval(intervaloActualizacion);
            intervaloActualizacion = null;
        }
    }

    // Iniciar actualización automática al cargar la página
    iniciarActualizacionAutomatica();

    // Detener actualización cuando la página pierde el foco
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            detenerActualizacionAutomatica();
        } else {
            iniciarActualizacionAutomatica();
        }
    });

    // Función para marcar un pedido como listo
    window.marcarPedidoComoListo = function(idPedido) {
        Swal.fire({
            title: `¿Estás seguro de que deseas marcar el Pedido #${idPedido} como preparado?`,
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B5E3C',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, marcar como preparado!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Marcando pedido...',
                    text: 'Por favor espera...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('../controllers/cocina.php?action=marcarPedidoComoListo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ orderId: idPedido })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            '¡Preparado!',
                            'El pedido ha sido marcado como preparado.',
                            'success'
                        );
                        obtenerPedidosPendientes(); // Actualizar la lista
                    } else {
                        Swal.fire(
                            'Error!',
                            data.message || 'Hubo un problema al marcar el pedido como preparado.',
                            'error'
                        );
                    }
                })
                .catch(error => showSwalError('Error al marcar el pedido como listo.'));
            }
        });
    };

    // Carga inicial de pedidos pendientes cuando la página se carga
    obtenerPedidosPendientes();
    setInterval(obtenerPedidosPendientes, 10000)
});

function showSwalError(msg) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: msg || 'Ocurrió un error.',
        confirmButtonText: 'Aceptar'
    });
} 