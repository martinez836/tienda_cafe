let mesaId = null;
let pedido = { productos: [], total: 0 };
let tokenMesa = null;
let expiracionToken = null;
let intervaloExpira = null;
let refreshPedidosActivosInterval = null;

function iniciarAutoRefreshPedidosActivosMesa() {
    if (refreshPedidosActivosInterval) clearInterval(refreshPedidosActivosInterval);
    refreshPedidosActivosInterval = setInterval(cargarPedidosActivosMesa, 10000); // cada 10 segundos
}

function detenerAutoRefreshPedidosActivosMesa() {
    if (refreshPedidosActivosInterval) clearInterval(refreshPedidosActivosInterval);
    refreshPedidosActivosInterval = null;
}

function validarToken() {
    const token = document.getElementById('tokenInput').value.trim();
    if (!token) {
        Swal.fire('Error', 'Por favor ingrese el token', 'error');
        document.getElementById('pedidoSection').style.display = 'none';
        return;
    }

    fetch('../../controllers/usuario_mesa/validar_token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'token=' + encodeURIComponent(token)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mesaId = data.mesa_id;
                tokenMesa = token;
                expiracionToken = data.expiracion_timestamp;
                document.getElementById('tokenSection').style.display = 'none';
                document.getElementById('pedidoSection').style.display = 'block';
                cargarPedidosActivos();
                cargarCategorias();
                cargarTodosLosProductosDelUsuario();
                cargarPedidosActivosMesa();
                iniciarAutoRefreshPedidosActivosMesa(); // Inicia el refresco automático
                mostrarTiempoExpiracion();
                intervaloExpira = setInterval(mostrarTiempoExpiracion, 1000);
            } else {
                document.getElementById('pedidoSection').style.display = 'none';
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            document.getElementById('pedidoSection').style.display = 'none';
            console.error('Error:', error);
            Swal.fire('Error', 'Error al validar el token', 'error');
        });
}

function cargarPedidosActivos() {
    if (!mesaId) return;

    fetch('../../controllers/usuario_mesa/pedidos_activos.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const pedidosMesa = data.pedidos.filter(p => p.mesa_id === mesaId);
                if (pedidosMesa.length > 0) {
                    const pedido = pedidosMesa[0]; // Tomamos el pedido más reciente
                    pedido.productos.forEach(prod => {
                        agregarAlPedido({
                            id: prod.id,
                            nombre: prod.nombre,
                            precio: prod.precio,
                            cantidad: prod.cantidad,
                            comentario: prod.comentario
                        });
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar pedidos activos:', error);
        });
}

function mostrarTiempoExpiracion() {
    const ahora = new Date().getTime();
    const tiempoRestante = expiracionToken - ahora;
    const div = document.getElementById('expiracionTokenInfo');
    if (!div) return; // Evita error si el elemento no existe

    if (tiempoRestante <= 0) {
        clearInterval(intervaloExpira);
        div.innerHTML = '<span class="text-danger">Token expirado</span>';
        document.getElementById('productosContainer').style.display = 'none';
        document.getElementById('categoriaSelect').style.display = 'none';
        document.getElementById('btnConfirmarPedido').disabled = true;
        return;
    }
    const minutos = Math.floor(tiempoRestante / (1000 * 60));
    const segundos = Math.floor((tiempoRestante % (1000 * 60)) / 1000);
    div.innerHTML = `<h1>Te quedan: </h1><h3>${minutos} minutos y ${segundos.toString().padStart(2, '0')} segundos</h3>`;
}

async function cargarCategorias() {
    try {
        const response = await fetch('../../controllers/mesero/cargarCategorias.php');
        const data = await response.text();
        document.querySelector('#categoriaSelect').innerHTML = data;
    } catch (error) {
        console.error('Error al cargar las categorías:', error);
    }
}

function cargarProductos(idcategoria) {
    console.log('cargarProductos llamado con idcategoria:', idcategoria);
    const contenedor = document.getElementById('productosContainer');
    if (!idcategoria) {
        contenedor.innerHTML = '';
        return;
    }
    contenedor.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';

    const body = JSON.stringify({ idcategorias: idcategoria });
    console.log('Enviando body:', body);

    fetch('../../controllers/usuario_mesa/cargar_productos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body
    })
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Response data:', data);
            contenedor.innerHTML = data.html;
            // Agregar event listeners a los botones de agregar
            document.querySelectorAll('#productosContainer .btn-primary').forEach(btn => {
                btn.addEventListener('click', function () {
                    const card = this.closest('.card');
                    const id = card.getAttribute('data-id');
                    const nombre = card.querySelector('h5').textContent.trim();
                    const precio = parseFloat(this.getAttribute('data-precio'));
                    const input = card.querySelector('input[type=number]');
                    const cantidad = parseInt(input.value);
                    // Validar stock
                    const stockBadge = card.querySelector('.badge.bg-secondary');
                    let stock = null;
                    if (stockBadge) {
                        const match = stockBadge.textContent.match(/Stock:\s*(\d+)/);
                        if (match) stock = parseInt(match[1]);
                    }
                    if (stock !== null && cantidad > stock) {
                        Swal.fire('Cantidad inválida', 'No puedes agregar más que el stock disponible.', 'warning');
                        return;
                    }
                    if (!cantidad || cantidad <= 0) {
                        Swal.fire('Cantidad inválida', 'Ingrese una cantidad válida', 'warning');
                        return;
                    }

                    // Actualizar el modal con los datos del producto
                    document.getElementById('productoNombreSeleccionado').textContent = nombre;
                    document.getElementById('productoSeleccionado').value = id;
                    document.getElementById('precioUnitario').value = precio;
                    document.getElementById('stockDisponible').textContent = stock === 'ilimitado' ? '∞' : stock;
                    document.getElementById('cantidadInput').value = 1;
                    document.getElementById('cantidadInput').max = stock === 'ilimitado' ? 99 : stock;
                    document.getElementById('comentarioInput').value = '';
                    document.getElementById('contadorCaracteres').textContent = '0';
                    document.getElementById('errorObservaciones').classList.add('d-none');
                    actualizarPrecioTotal();

                    // Mostrar el modal
                    const modalElement = document.getElementById('observacionModal');
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                });
            });
        })
        .catch(error => {
            console.error('Error en cargarProductos:', error);
            contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar los productos</div>';
        });
}

function agregarAlPedido() {
    const id = document.getElementById('productoSeleccionado').value;
    const nombre = document.getElementById('productoNombreSeleccionado').textContent;
    const cantidad = parseInt(document.getElementById('cantidadInput').value) || 1;
    const precio = parseInt(document.getElementById('precioUnitario').value) || 0;
    const comentario = document.getElementById('comentarioInput').value;
    const stock = document.getElementById('stockDisponible').textContent === '∞' ? 999999 : parseInt(document.getElementById('stockDisponible').textContent);

    // Validar observaciones
    const textarea = document.getElementById('comentarioInput');
    if (!validarObservaciones(textarea)) {
        return;
    }

    if (isNaN(precio) || isNaN(cantidad)) {
        Swal.fire('Error', 'Error en los valores del producto', 'error');
        return;
    }

    // Validar stock antes de agregar
    const existente = pedido.productos.find(p => p.id == id && p.comentario == sanitizarObservaciones(comentario));
    if (existente) {
        if (stock !== 999999 && existente.cantidad + cantidad > stock) {
            Swal.fire('Stock insuficiente', 'No puedes agregar más que el stock disponible.', 'warning');
            return;
        }
        existente.cantidad += cantidad;
    } else {
        if (stock !== 999999 && cantidad > stock) {
            Swal.fire('Stock insuficiente', 'No puedes agregar más que el stock disponible.', 'warning');
            return;
        }
        pedido.productos.push({
            id: id,
            nombre: nombre,
            cantidad: cantidad,
            precio: precio,
            comentario: sanitizarObservaciones(comentario),
            stock: stock
        });
    }
    renderPedido();

    // Cerrar el modal
    const modalElement = document.getElementById('observacionModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
}

function renderPedido() {
    const lista = document.getElementById('productosPedido');
    lista.innerHTML = '';
    let total = 0;
    pedido.productos.forEach((producto, index) => {
        const subtotal = producto.precio * producto.cantidad;
        total += subtotal;
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `
            <div class="flex-grow-1">
                <div class="fw-bold">${producto.nombre}</div>
                <div class="text-muted small">${producto.comentario ? producto.comentario : 'sin obs.'}</div>
                <div class="fw-semibold">$${producto.precio.toFixed(2)} x ${producto.cantidad}</div>
            </div>
            <div class="d-flex flex-column align-items-end ms-3">
                <div class="mb-2">$${subtotal.toFixed(2)}</div>
                <div>
                    <button class="btn btn-sm btn-secondary me-1" onclick="cambiarCantidadUsuarioMesa(${index}, -1)">-</button>
                    <button class="btn btn-sm btn-secondary me-1" onclick="cambiarCantidadUsuarioMesa(${index}, 1)">+</button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarProductoUsuarioMesa(${index})">x</button>
                </div>
            </div>
        `;
        lista.appendChild(li);
    });
    pedido.total = total;
    document.getElementById('totalPedido').textContent = '$' + total.toFixed(2);
}

function cambiarCantidadUsuarioMesa(index, delta) {
    if (!pedido.productos[index]) return;
    // Solo permitir incrementar si no supera el stock
    if (delta > 0) {
        const item = pedido.productos[index];
        if (item.stock !== undefined && item.cantidad + 1 > item.stock) {
            Swal.fire('Stock insuficiente', 'No puedes agregar más que el stock disponible.', 'warning');
            return;
        }
    }
    pedido.productos[index].cantidad += delta;
    if (pedido.productos[index].cantidad < 1) {
        pedido.productos[index].cantidad = 1;
    }
    renderPedido();
}

function eliminarProductoUsuarioMesa(index) {
    pedido.productos.splice(index, 1);
    renderPedido();
}

function regresarASeleccionarProductos() {
    document.getElementById('pedidoSection').style.display = 'block';
    document.getElementById('expiracionTokenInfo').style.display = 'block';
    document.getElementById('pedidoActual').innerHTML = '';
    document.getElementById('productosPedido').innerHTML = '';
    document.getElementById('totalPedido').textContent = '$0.00';
    pedido = { productos: [], total: 0 };

    // Reiniciar el contador si no está corriendo
    if (!intervaloExpira) {
        mostrarTiempoExpiracion();
        intervaloExpira = setInterval(mostrarTiempoExpiracion, 1000);
    }

    cargarTodosLosProductosDelUsuario();

    Swal.fire({
        title: '¡Listo!',
        text: 'Puede agregar más productos a su pedido',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}

function confirmarPedido() {
    if (!pedido.productos.length) {
        Swal.fire('Error', 'No hay productos en el pedido', 'error');
        return;
    }
    fetch('../../controllers/usuario_mesa/confirmar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mesa_id: mesaId, productos: pedido.productos, token: tokenMesa })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ocultar completamente la sección de pedido
            document.getElementById('pedidoSection').style.display = 'none';
            // Ocultar completamente el contenedor de token (columna izquierda)
            const tokenContainer = document.getElementById('tokenPanel');
            if (tokenContainer) {
                tokenContainer.style.display = 'none';
            }

            // Centrar el panel de pedidos
            const pedidoPanel = document.getElementById('pedidoPanel');
            if (pedidoPanel) {
                pedidoPanel.className = 'col-lg-12';
                pedidoPanel.style.margin = '0 auto';
                pedidoPanel.style.maxWidth = '800px';
            }

            // Ocultar el tiempo restante del token
            document.getElementById('expiracionTokenInfo').style.display = 'none';

            // Cargar todos los productos del usuario con el mismo token para mostrar el resumen completo
            cargarResumenCompletoDelUsuario();

            // Limpiar el pedido actual
            pedido = { productos: [], total: 0 };
            clearInterval(intervaloExpira);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
        })
    .catch(error => {
       console.error('Error:', error);
        Swal.fire('Error', 'Error al confirmar el pedido', 'error');
    });
}

function cargarResumenCompletoDelUsuario() {
    detenerAutoRefreshPedidosActivosMesa();
    if (!mesaId || !tokenMesa) {
        console.log('No hay mesaId o tokenMesa:', { mesaId, tokenMesa });
        mostrarResumenPedidoActual();
        return;
    }

    console.log('Cargando resumen completo para mesa:', mesaId, 'token:', tokenMesa);

    fetch('../../controllers/usuario_mesa/pedidos_usuario_token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mesa_id: mesaId, token: tokenMesa })
    })
        .then(res => res.json())
        .then(data => {
            console.log('Respuesta completa de pedidos_usuario_token.php:', data);

            if (data.success) {
                const pedidos = data.pedidos;
                console.log('Pedidos encontrados:', pedidos);

                if (pedidos.length > 0) {
                    // Calcular total general de todos los pedidos
                    let totalGeneral = 0;
                    let todosLosProductos = [];

                    pedidos.forEach((pedido, pedidoIndex) => {
                        console.log(`Procesando pedido ${pedidoIndex + 1}:`, pedido);
                        console.log('Productos del pedido:', pedido.productos);

                        pedido.productos.forEach((prod, prodIndex) => {
                            console.log(`Producto ${prodIndex + 1}:`, prod);
                            console.log('Tipo de precio:', typeof prod.precio, 'Valor:', prod.precio);
                            console.log('Tipo de cantidad:', typeof prod.cantidad, 'Valor:', prod.cantidad);

                            // Asegurar que precio y cantidad sean números
                            const precio = parseFloat(prod.precio) || 0;
                            const cantidad = parseInt(prod.cantidad) || 0;

                            console.log('Precio convertido:', precio, 'Cantidad convertida:', cantidad);

                            todosLosProductos.push({
                                ...prod,
                                precio: precio,
                                cantidad: cantidad
                            });
                            totalGeneral += precio * cantidad;
                        });
                    });

                    console.log('Total de productos encontrados:', todosLosProductos.length);
                    console.log('Total general:', totalGeneral);
                    console.log('Productos procesados:', todosLosProductos);

                    const resumenPedidosHTML = `
                    <div class="card shadow-lg border-0 rounded-4 bg-light mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>Resumen Completo de Todos sus Pedidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group mb-3">
                                ${todosLosProductos.map((producto) => {
                        const subtotal = producto.precio * producto.cantidad;
                        return `
                                    <li class='list-group-item d-flex justify-content-between align-items-center'>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">${producto.nombre}</div>
                                            <div class="text-muted small">${producto.comentario ? producto.comentario : 'sin obs.'}</div>
                                            <div class="fw-semibold">$${producto.precio.toFixed(2)} x ${producto.cantidad}</div>
                                        </div>
                                        <div class="d-flex flex-column align-items-end ms-3">
                                            <div class="mb-2">$${subtotal.toFixed(2)}</div>
                                        </div>
                                    </li>
                                    `;
                    }).join('')}
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                    <strong>Total</strong>
                                    <strong>$${totalGeneral.toFixed(2)}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                `;
                    const mensajeConfirmacionHTML = `
                    <div class="alert alert-success text-center mt-4" id="alertPedidoConfirmado">
                        <h4 class="alert-heading">
                            <i class="fas fa-check-circle me-2"></i>¡Pedido Confirmado!</h4>
                        <p class="mb-0">Su pedido está siendo preparado. Pronto lo recibiras en la mesa... Gracias por visitarnos!!!.</p>
                    </div>
                `;
                    const pedidoActualDiv = document.getElementById('pedidoActual');
                    pedidoActualDiv.innerHTML = resumenPedidosHTML;
                    pedidoActualDiv.insertAdjacentHTML('afterend', mensajeConfirmacionHTML);

                    // Reemplazar el historial para evitar volver atrás
                    window.history.replaceState({ pedidoConfirmado: true }, '', window.location.href);
                } else {
                    console.log('No se encontraron pedidos para el token, mostrando resumen del pedido actual');
                    mostrarResumenPedidoActual();
                }
            } else {
                console.log('Error en la respuesta:', data.message);
                mostrarResumenPedidoActual();
            }
        })
        .catch(error => {
            console.error('Error al cargar el resumen completo:', error);
            // Si hay error, mostrar el resumen del pedido actual
            mostrarResumenPedidoActual();
        });
}

function mostrarResumenPedidoActual() {
    let total = pedido.total || 0;
    const resumenPedidosHTML = `
        <div class="card shadow-lg border-0 rounded-4 bg-light mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-receipt me-2"></i>Resumen de su Pedido
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-group mb-3">
                    ${pedido.productos.map((producto) => {
        const subtotal = producto.precio * producto.cantidad;
        return `
                        <li class='list-group-item d-flex justify-content-between align-items-center'>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${producto.nombre}</div>
                                <div class="text-muted small">${producto.comentario ? producto.comentario : 'sin obs.'}</div>
                                <div class="fw-semibold">$${producto.precio.toFixed(2)} x ${producto.cantidad}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end ms-3">
                                <div class="mb-2">$${subtotal.toFixed(2)}</div>
                            </div>
                        </li>
                        `;
    }).join('')}
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                        <strong>Total</strong>
                        <strong>$${total.toFixed(2)}</strong>
                    </li>
                </ul>
            </div>
        </div>
    `;
    const mensajeConfirmacionHTML = `
        <div class="alert alert-success text-center mt-4" id="alertPedidoConfirmado">
            <h4 class="alert-heading">
                <i class="fas fa-check-circle me-2"></i>¡Pedido Confirmado!</h4>
            <p class="mb-0">Su pedido está siendo procesado. Gracias por visitarnos.</p>
        </div>
    `;
    const pedidoActualDiv = document.getElementById('pedidoActual');
    // Elimina mensaje anterior si existe
    const prevAlert = document.getElementById('alertPedidoConfirmado');
    if (prevAlert) prevAlert.remove();
    pedidoActualDiv.innerHTML = resumenPedidosHTML;
    pedidoActualDiv.insertAdjacentHTML('afterend', mensajeConfirmacionHTML);
}

function cargarTodosLosProductosDelUsuario() {
    if (!mesaId || !tokenMesa) return;

    // Limpiar el array de productos antes de agregar los nuevos
    pedido.productos = [];
    pedido.total = 0;

    fetch('../../controllers/usuario_mesa/pedidos_usuario_token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mesa_id: mesaId, token: tokenMesa })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const pedidos = data.pedidos;
                if (pedidos.length > 0) {
                    mostrarHistorialPedidos(pedidos);
                    pedidos.forEach(pedido => {
                        pedido.productos.forEach(prod => {
                            const precio = parseFloat(prod.precio) || 0;
                            const cantidad = parseInt(prod.cantidad) || 0;
                            agregarAlPedido({
                                id: prod.id,
                                nombre: prod.nombre,
                                precio: precio,
                                cantidad: cantidad,
                                comentario: prod.comentario
                            });
                        });
                    });
                } else {
                    document.getElementById('historialPedidos').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar todos los productos del usuario:', error);
        });
}

function mostrarHistorialPedidos(pedidos) {
    const historialDiv = document.getElementById('historialPedidos');
    const contenidoHistorial = document.getElementById('contenidoHistorial');

    if (pedidos.length === 0) {
        historialDiv.style.display = 'none';
        return;
    }

    let html = '';
    pedidos.forEach((pedido, index) => {
        const totalPedido = pedido.productos.reduce((sum, prod) => {
            const precio = parseFloat(prod.precio) || 0;
            const cantidad = parseInt(prod.cantidad) || 0;
            return sum + (precio * cantidad);
        }, 0);

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Pedido #${index + 1} - Total: $${totalPedido.toFixed(2)}
                        <small class="text-muted ms-2">(${new Date(pedido.fecha_hora).toLocaleTimeString()})</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Observaciones</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${pedido.productos.map(prod => {
            const precio = parseFloat(prod.precio) || 0;
            const cantidad = parseInt(prod.cantidad) || 0;
            return `
                                        <tr>
                                            <td>${prod.nombre}</td>
                                            <td>${cantidad}</td>
                                            <td>$${precio.toFixed(2)}</td>
                                            <td>${prod.comentario || '-'}</td>
                                            <td>$${(cantidad * precio).toFixed(2)}</td>
                                        </tr>
                                    `;
        }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    });

    contenidoHistorial.innerHTML = html;
    historialDiv.style.display = 'block';
}

function cargarPedidosActivosMesa() {
    if (!mesaId) return;

    fetch('../../controllers/usuario_mesa/pedidos_activos_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mesa_id: mesaId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarHistorialPedidos(data.pedidos);
            }
        })
        .catch(error => {
            console.error('Error al cargar pedidos activos de la mesa:', error);
        });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Agregar event listener al select de categorías
    document.getElementById('categoriaSelect').addEventListener('change', function () {
        cargarProductos(this.value);
    });

    // Hacer las funciones globales para que puedan ser llamadas desde el HTML
    window.cargarTodosLosProductosDelUsuario = cargarTodosLosProductosDelUsuario;
    window.mostrarHistorialPedidos = mostrarHistorialPedidos;
    window.cargarResumenCompletoDelUsuario = cargarResumenCompletoDelUsuario;
    window.mostrarResumenPedidoActual = mostrarResumenPedidoActual;

    const buscador = document.querySelector("#buscadorProductos");
    if (buscador) {
        buscador.addEventListener("input", function () {
            const filtro = buscador.value.toLowerCase();
            const productos = document.querySelectorAll("#productosContainer .card");
            productos.forEach(card => {
                const nombre = card.querySelector("h5").textContent.toLowerCase();
                card.parentElement.style.display = nombre.includes(filtro) ? "" : "none";
            });
        });
    }
});

// Bloquear el botón atrás si el pedido fue confirmado
window.addEventListener('popstate', function (event) {
    if (event.state && event.state.pedidoConfirmado) {
        if (document.getElementById('pedidoSection')) {
            document.getElementById('pedidoSection').style.display = 'none';
        }
        if (document.getElementById('expiracionTokenInfo')) {
            document.getElementById('expiracionTokenInfo').style.display = 'none';
        }
        // Opcional: mostrar un mensaje
        Swal.fire({
            icon: 'info',
            title: 'Acción no permitida',
            text: 'No puede regresar a la pantalla de pedido después de confirmar.',
            timer: 2500,
            showConfirmButton: false
        });
    }
});

// Función para mostrar/ocultar contraseña (migrada desde el HTML)
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input && btn) {
        if (input.type === "password") {
            input.type = "text";
            btn.textContent = "🙈";
        } else {
            input.type = "password";
            btn.textContent = "👁";
        }
    }
}

// Función para validar observaciones y prevenir inyección SQL
function validarObservaciones(textarea) {
    const observaciones = textarea.value;
    const contador = document.getElementById('contadorCaracteres');
    const errorDiv = document.getElementById('errorObservaciones');
    
    // Actualizar contador
    contador.textContent = observaciones.length;
    
    if(observaciones === "") {
        errorDiv.classList.add('d-none');
        return true; // Permitir vacío
    }

    const patronSeguro = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,;:()¿?¡!\-_]*$/;
    const caracteresProhibidos = /['"<>{}[\]\\|`~@#$%^&*+=]/;

    if(observaciones.length > 255) {
        errorDiv.textContent = 'Máximo 255 caracteres permitidos';
        errorDiv.classList.remove('d-none');
        return false;
    }
    if(caracteresProhibidos.test(observaciones)) {
        errorDiv.textContent = 'Caracteres no permitidos encontrados';
        errorDiv.classList.remove('d-none');
        return false;
    }
    if(!patronSeguro.test(observaciones)) {
        errorDiv.textContent = 'Solo se permiten letras, números, espacios y signos básicos';
        errorDiv.classList.remove('d-none');
        return false;
    }
    
    errorDiv.classList.add('d-none');
    return true;
}

// Función para sanitizar observaciones antes de enviar
function sanitizarObservaciones(texto) {
    if (!texto || typeof texto !== 'string') return '';

    // Eliminar espacios extra y caracteres de control
    return texto
        .trim()
        .replace(/\s+/g, ' ') // Reemplazar múltiples espacios por uno solo
        .replace(/[\x00-\x1F\x7F]/g, '') // Eliminar caracteres de control
        .substring(0, 255); // Asegurar máximo 255 caracteres
}

// Función para actualizar el precio total en el modal
function actualizarPrecioTotal() {
    const cantidad = parseInt(document.querySelector('#cantidadInput').value) || 1;
    const precioUnitario = parseInt(document.querySelector('#precioUnitario').value) || 0;
    const precioTotal = cantidad * precioUnitario;

    document.querySelector('#precioTotal').textContent = '$' + precioTotal.toLocaleString();
}

// Configurar eventos del modal al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const cantidadInput = document.querySelector('#cantidadInput');
    const btnMenos = document.querySelector('#btnMenosCantidad');
    const btnMas = document.querySelector('#btnMasCantidad');
    const btnAgregarAlPedido = document.querySelector('#btnAgregarAlPedido');

    if (btnMenos) {
        btnMenos.addEventListener('click', function () {
            const valor = parseInt(cantidadInput.value);
            if (valor > 1) {
                cantidadInput.value = valor - 1;
                actualizarPrecioTotal();
            }
        });
    }

    if (btnMas) {
        btnMas.addEventListener('click', function () {
            const valor = parseInt(cantidadInput.value);
            const maximo = parseInt(cantidadInput.max);
            if (valor < maximo) {
                cantidadInput.value = valor + 1;
                actualizarPrecioTotal();
            }
        });
    }

    if (cantidadInput) {
        cantidadInput.addEventListener('input', function () {
            const valor = parseInt(this.value);
            const minimo = parseInt(this.min);
            const maximo = parseInt(this.max);

            if (valor < minimo) {
                this.value = minimo;
            } else if (valor > maximo) {
                this.value = maximo;
            }
            actualizarPrecioTotal();
        });
    }

    if (btnAgregarAlPedido) {
        btnAgregarAlPedido.addEventListener('click', agregarAlPedido);
    }
}); 