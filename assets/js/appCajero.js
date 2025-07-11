let selectedOrder = null;
let intervaloActualizacion = null;

// Formato de moneda colombiana
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

function loadOrders() {
    const ordersList = document.getElementById('ordersList');
    fetch('../controllers/cajero/obtenerPedidosPendientes.php')
        .then(response => response.json())
        .then(response => {
            const pendingOrders = response.data || [];
            if (pendingOrders.length === 0) {
                ordersList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h5>No hay pedidos pendientes</h5>
                        <p>Todos los pedidos han sido liquidados</p>
                    </div>
                `;
                return;
            }

            ordersList.innerHTML = '';
            console.log(ordersList)
            pendingOrders.forEach(order => {
                const div = document.createElement('div');
                div.className = 'order-item';
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="order-number me-3">${order.numero.replace('P', '')}</div>
                            <div>
                                <h6 class="mb-1">${order.cliente}</h6>
                                <small class="text-muted">${order.hora}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">${formatCurrency(order.total)}</div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">${order.productos.map(p => p.nombre).join(' • ')}</small>
                    </div>
                `;

                // Evento click para seleccionar pedido
                div.addEventListener('click', () => {
                    document.querySelectorAll('.order-item').forEach(item => item.classList.remove('selected'));
                    div.classList.add('selected');
                    selectedOrder = order;
                    showPaymentPanel(order);
                });

                ordersList.appendChild(div);
            });
        })
        .catch(error => showSwalError('Error al cargar pedidos.'));
}

function showPaymentPanel(order) {
    const paymentPanel = document.getElementById('paymentPanel');
    
    paymentPanel.innerHTML = `
        <div class="mb-4">
            <h5 class="text-center mb-3">Pedido ${order.numero}</h5>
            <div class="row">
                <div class="col-sm-6">
                    <strong>Cliente:</strong><br>
                    <span class="text-muted">${order.cliente}</span><br>
                    <strong>Mesero:</strong><br>
                    <span class="text-muted">${order.mesero ?? 'N/A'}</span>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <strong>Hora:</strong><br>
                    <span class="text-muted">${order.hora}</span>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="mb-2">Productos:</h6>
            <ul class="list-unstyled">
                ${order.productos.map(item => `<li class="mb-1">• ${item.nombre}</li>`).join('')}
            </ul>
        </div>

        <div class="payment-summary">
            <div class="row align-items-center mb-3">
                <div class="col">   
                    <span class="fw-bold">Total a Pagar:</span>
                </div>
                <div class="col-auto">
                    <span class="amount-display">${formatCurrency(order.total)}</span>
                </div>
            </div>

            <div class="mb-3">
                <label for="amountReceived" class="form-label fw-bold">Dinero Recibido:</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="amountReceived" 
                           placeholder="Ingrese el monto recibido" min="${order.total}" 
                           oninput="calculateChange(${order.total})">
                </div>
            </div>

            <div id="changeSection" class="mb-3" style="display: none;">
                <div class="row align-items-center">
                    <div class="col">
                        <span class="fw-bold">Cambio a Devolver:</span>
                    </div>
                    <div class="col-auto">
                        <span id="changeAmount" class="change-display">$0</span>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button id="processPaymentBtn" class="btn btn-success-coffee" 
                        onclick="processPayment('${order.numero}')" disabled>
                    <i class="fas fa-check me-2"></i>
                    Procesar Pago
                </button>
                <button class="btn btn-outline-secondary" onclick="clearSelection()">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
            </div>
        </div>
    `;
}

function calculateChange(orderTotal) {
    const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
    const changeSection = document.getElementById('changeSection');
    const changeAmount = document.getElementById('changeAmount');
    const processBtn = document.getElementById('processPaymentBtn');

    if (amountReceived >= orderTotal) {
        const change = amountReceived - orderTotal;
        changeAmount.textContent = formatCurrency(change);
        changeSection.style.display = 'block';
        processBtn.disabled = false;

        if (change > 0) {
            changeAmount.className = 'change-display';
        } else {
            changeAmount.className = 'change-display text-primary';
        }
    } else {
        changeSection.style.display = 'none';
        processBtn.disabled = true;
    }
}

function processPayment(orderNumero) {
    const amountReceived = parseFloat(document.getElementById('amountReceived').value);

    if (selectedOrder && amountReceived >= selectedOrder.total) {
        const change = amountReceived - selectedOrder.total;
        
        Swal.fire({
            title: '¿Confirmar pago?',
            html: `
                <div class="text-start">
                    <p><strong>Total:</strong> ${formatCurrency(selectedOrder.total)}</p>
                    <p><strong>Recibido:</strong> ${formatCurrency(amountReceived)}</p>
                    <p><strong>Cambio:</strong> ${formatCurrency(change)}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Sí, confirmar pago',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../controllers/cajero/procesarPago.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        numero: orderNumero
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: 'Pago procesado correctamente',
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        });
                        window.open('../facturas/factura_pedido_' + orderNumero.replace('P', '') + '.pdf', '_blank');
                        clearSelection();
                        loadOrders();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al procesar el pago: ' + data.message,
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al procesar el pago',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    }
}

function clearSelection() {
    selectedOrder = null;
    document.querySelectorAll('.order-item').forEach(item => item.classList.remove('selected'));
    
    document.getElementById('paymentPanel').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-hand-pointer"></i>
            <h5>Selecciona un pedido</h5>
            <p>Haz clic en un pedido de la lista para proceder con el pago</p>
        </div>
    `;
}

function showSwalError(msg) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: msg || 'Ocurrió un error.',
        confirmButtonText: 'Aceptar'
    });
}

// Iniciar actualización automática cada 30 segundos
function iniciarActualizacionAutomatica() {
    intervaloActualizacion = setInterval(() => {
        loadOrders();
    }, 7000); // 7 segundos
}

// Detener actualización automática
function detenerActualizacionAutomatica() {
    if (intervaloActualizacion) {
        clearInterval(intervaloActualizacion);
        intervaloActualizacion = null;
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
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