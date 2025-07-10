// Funcionalidad para la gestión de pedidos
document.addEventListener('DOMContentLoaded', function() {
    const tablaPedidos = $('#tablaPedidos').DataTable({
        responsive: {
            details: {
                renderer: function (api, rowIdx, columns) {
                    let data = columns
                        .filter(col => col.hidden)
                        .map(col => {
                            return `<tr><td class="text-end fw-bold">${col.title}</td><td>${col.data}</td></tr>`;
                        })
                        .join('');
                    return data ? $('<table class="table table-sm table-bordered mb-0 w-100"/>').append(data) : false;
                }
            }
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 }, // ID Pedido (más importante)
            { responsivePriority: 2, targets: 1 }, // Fecha y Hora
            { responsivePriority: 3, targets: 2 }, // Mesa
            { responsivePriority: 4, targets: 3 }, // Estado
            { responsivePriority: 5, targets: 4 }, // Usuario
            { responsivePriority: 6, targets: 5 }, // Acciones
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
    loadOrders(tablaPedidos); // Cargar pedidos al cargar la página
    setInterval(() => loadOrders(tablaPedidos), 30000);

    const generarReporteBtn = document.getElementById('generarReporteBtn');
    if (generarReporteBtn) {
        generarReporteBtn.addEventListener('click', function() {
            const fechaInicio = document.getElementById('FechaInicio').value;
            const fechaFin = document.getElementById('FechaFin').value;
            if (!fechaInicio || !fechaFin) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas requeridas',
                    text: 'Por favor selecciona la fecha de inicio y fin.'
                });
                return;
            }
            generarBalancePDF(fechaInicio, fechaFin);
        });
    }

    // Evento para ver historial de balances
    const botonVerHistorial = document.getElementById('verHistorialBtn');
    if (botonVerHistorial) {
        botonVerHistorial.addEventListener('click', () => {
            cargarHistorialBalances();
            const modalHistorial = new bootstrap.Modal(document.getElementById('historialModal'));
            modalHistorial.show();
        });
    }
});

function loadOrders(tablaPedidos) {
    fetch('../../controllers/admin/pedidos.php?action=get_all_orders')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            tablaPedidos.clear();
            if (data.success && data.data.length > 0) {
                data.data.forEach(order => {
                    tablaPedidos.row.add([
                        order.idpedidos,
                        formatDateTime(order.fecha_hora_pedido),
                        order.nombre_mesa,
                        `<span class="badge bg-${getEstadoColor(order.estado_pedido)}">${order.estado_pedido}</span>`,
                        order.nombre_usuario,
                        `<button class=\"btn btn-sm btn-info me-1\" onclick=\"verDetallePedido(${order.idpedidos})\"><i class=\"fas fa-eye\"></i> Ver Detalle</button>`
                    ]);
                });
            } else {
                tablaPedidos.row.add([
                    '', '', '', '', '', '<span class="text-center">No hay pedidos para mostrar.</span>'
                ]);
            }
            tablaPedidos.draw();
        })
        .catch(error => {
            tablaPedidos.clear();
            tablaPedidos.row.add([
                '', '', '', '', '', `<span class="text-danger">Error al cargar pedidos: ${error.message}</span>`
            ]);
            tablaPedidos.draw();
        });
}

function getEstadoColor(estado) {
    // Convertir a minúsculas y eliminar espacios extra
    const estadoNormalizado = estado.toLowerCase().trim();
    
    switch(estadoNormalizado) {
        case 'confirmado':
        case '3':
            return 'warning'; // Amarillo para confirmado
        case 'entregado':
        case '4':
            return 'info'; // Azul para entregado
        case 'procesado':
        case '5':
            return 'success'; // Verde para procesado
        case 'pendiente':
        case '1':
            return 'primary'; // Azul oscuro para pendiente
        case 'cancelado':
        case '2':
            return 'danger'; // Rojo para cancelado
        default:
            return 'secondary'; // Gris para estados desconocidos
    }
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function verDetallePedido(idPedido) {
    const modal = new bootstrap.Modal(document.querySelector('#detallePedidoModal'));
    const content = document.getElementById('detallePedidoContent');
    
    // Mostrar loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles del pedido...</p>
        </div>
    `;
    
    modal.show();

    // Cargar detalles del pedido
    fetch(`../../controllers/admin/pedidos.php?action=get_order_detail&id=${idPedido}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const pedido = data.data;
                let productosHtml = '';
                
                if (pedido.productos && pedido.productos.length > 0) {
                    productosHtml = `
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${pedido.productos.map(producto => `
                                        <tr>
                                            <td>
                                                ${producto.nombre_producto}
                                                ${producto.observaciones ? `<br><small class="text-muted">(${producto.observaciones})</small>` : ''}
                                            </td>
                                            <td>${producto.cantidad_producto}</td>
                                            <td>$${parseFloat(producto.precio_producto).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td>$${parseFloat(producto.subtotal).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    productosHtml = '<p class="text-muted">No hay productos registrados para este pedido.</p>';
                }

                content.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>ID del Pedido:</strong> ${pedido.idpedidos}
                        </div>
                        <div class="col-md-6">
                            <strong>Mesa:</strong> ${pedido.nombre_mesa}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Fecha y Hora:</strong> ${formatDateTime(pedido.fecha_hora_pedido)}
                        </div>
                        <div class="col-md-6">
                            <strong>Estado:</strong> 
                            <span class="badge bg-${getEstadoColor(pedido.estado_pedido)}">
                                ${pedido.estado_pedido}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Usuario:</strong> ${pedido.nombre_usuario}
                        </div>
                        <div class="col-md-6">
                            <strong>Total:</strong> $${parseFloat(pedido.total_pedido || 0).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </div>
                    </div>
                    <hr>
                    <h6>Productos del Pedido:</h6>
                    ${productosHtml}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Error al cargar los detalles del pedido.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles del pedido:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los detalles del pedido: ${error.message}
                </div>
            `;
        });
}

function generarBalancePDF(fechaInicio, fechaFin) {
    Swal.fire({
        title: 'Generando reporte...',
        text: 'Por favor espera unos segundos.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    fetch('../../controllers/admin/reporteBalance.php?fecha_inicio=' + fechaInicio + '&fecha_fin=' + fechaFin)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.filename) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reporte generado',
                    text: 'El balance se generó correctamente. Se descargará el PDF.',
                    showConfirmButton: false,
                    timer: 1800
                });
                // Descargar el PDF
                const link = document.createElement('a');
                link.href = '../../facturas/reportes/' + data.filename;
                link.download = data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo generar el reporte.'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al generar el reporte: ' + error.message
            });
        });
}

function cargarHistorialInventario() {
    fetch('../../controllers/admin/listarReportes.php?tipo=inventario')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('historialTableBody');
            tbody.innerHTML = '';
            if (data.success && data.data.length > 0) {
                data.data.forEach(reporte => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${reporte.filename}</td>
                        <td>${reporte.created}</td>
                        <td>${reporte.size_formatted}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="descargarReporte('${reporte.filename}')">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No hay reportes de inventario generados</td></tr>';
            }
        })
        .catch(error => {
            const tbody = document.getElementById('historialTableBody');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar el historial</td></tr>';
        });
}

function cargarHistorialBalances() {
    fetch('../../controllers/admin/listarReportes.php?tipo=balance')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('historialTableBody');
            tbody.innerHTML = '';
            if (data.success && data.data.length > 0) {
                data.data.forEach(reporte => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${reporte.filename}</td>
                        <td>${reporte.created}</td>
                        <td>${reporte.size_formatted}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="descargarReporte('${reporte.filename}')">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No hay balances generados</td></tr>';
            }
        })
        .catch(error => {
            const tbody = document.getElementById('historialTableBody');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar el historial</td></tr>';
        });
}

// Función global para descargar reporte desde el historial
window.descargarReporte = function(filename) {
    window.open('../../controllers/admin/descargarReporte.php?filename=' + filename, '_blank');
};
