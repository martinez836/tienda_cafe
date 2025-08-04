document.addEventListener('DOMContentLoaded', function() {
            // Función para cargar los datos del dashboard
            function loadDashboardData() {
                fetch('../../controllers/admin/dashboard.php?action=get_dashboard_data')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const dashboardData = data.data;

                            // Actualizar tarjetas de resumen
                            document.querySelector('.card.bg-primary h3').textContent = (dashboardData.totalPedidos ?? 0).toLocaleString();
                            document.querySelector('.card.bg-success h3').textContent = `$${(dashboardData.ingresosMesActual ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            document.querySelector('.card.bg-warning h3').textContent = (dashboardData.nuevosUsuariosMesActual ?? 0).toLocaleString();

                            // Actualizar gráfica de Ventas Diarias
                            const ventasDiarias = dashboardData.ventasDiarias ?? { labels: [], data: [] }; 
                            createOrUpdateVentasDiariasChart(ventasDiariasCtx, ventasDiarias.labels ?? [], ventasDiarias.data ?? []);

                            // Actualizar últimos pedidos
                            const ultimosPedidosList = document.querySelector('#ultimosPedidosList');
                            if (ultimosPedidosList) {
                                ultimosPedidosList.innerHTML = '';
                                if (Array.isArray(dashboardData.ultimosPedidos) && dashboardData.ultimosPedidos.length > 0) {
                                    dashboardData.ultimosPedidos.forEach(pedido => {
                                        const li = document.createElement('li');
                                        li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
                                        let statusClass = '';
                                        if (pedido.status === 'Pendiente') {
                                            statusClass = 'bg-info';
                                        } else if (pedido.status === 'Completado') {
                                            statusClass = 'bg-success';
                                        }
                                        li.innerHTML = `
                                            Pedido #${pedido.id} - ${pedido.table}
                                            <span class="badge ${statusClass} rounded-pill">${pedido.status}</span>
                                        `;
                                        ultimosPedidosList.appendChild(li);
                                    });
                                } else {
                                    ultimosPedidosList.innerHTML = '<li class="list-group-item text-center">No hay pedidos recientes.</li>';
                                }
                            }

                            // Comentarios recientes (si tienes datos para ellos)
                            const comentariosRecientesList = document.querySelector('#comentariosRecientesList');
                            if (comentariosRecientesList) {
                                comentariosRecientesList.innerHTML = '';
                                if (dashboardData.comentariosRecientes.length > 0) {
                                    dashboardData.comentariosRecientes.forEach(comentario => {
                                        const li = document.createElement('li');
                                        li.classList.add('list-group-item');
                                        li.textContent = `"${comentario.texto}" - ${comentario.autor}`;
                                        comentariosRecientesList.appendChild(li);
                                    });
                                } else {
                                    comentariosRecientesList.innerHTML = '<li class="list-group-item text-center">No hay comentarios recientes.</li>';
                                }
                            }

                        } else {
                            console.error('Error al cargar datos del dashboard:', data.message);
                        }
                    })
                    .catch(error => showSwalError('Error al cargar datos del dashboard.'));
            }

            // Chart para Ventas Diarias (inicialización y actualización)
            const ventasDiariasCtx = document.getElementById('ventasDiariasChart').getContext('2d');
            let ventasDiariasChart = null;

            function createOrUpdateVentasDiariasChart(ctx, labels, data) {
                if (ventasDiariasChart) {
                    ventasDiariasChart.destroy();
                }
                
                ventasDiariasChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ventas ($)',
                            data: data,
                            borderColor: '#8B5E3C',
                            backgroundColor: 'rgba(139, 94, 60, 0.2)',
                            fill: true,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: false,
                                text: 'Ventas Diarias'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Cargar datos al iniciar la página
            loadDashboardData();

            // Cargar alertas de stock
            cargarAlertasStock();
            
            // Configurar gráfico de ventas (placeholder)
            // configurarGraficoVentas(); // Eliminado para evitar datos ficticios
        });

        async function cargarAlertasStock() {
            try {
                const [productosBajoStock, productosSinStock, estadisticas] = await Promise.all([
                    fetch('../../controllers/admin/productos.php?action=getProductosBajoStock').then(r => r.json()),
                    fetch('../../controllers/admin/productos.php?action=getProductosSinStock').then(r => r.json()),
                    fetch('../../controllers/admin/productos.php?action=getResumenStock').then(r => r.json())
                ]);
        
                const contenidoAlertas = document.getElementById('contenidoAlertasStock');
                
                if (!productosBajoStock.success && !productosSinStock.success) {
                    contenidoAlertas.innerHTML = '<div class="alert alert-info">No se pudieron cargar las alertas de stock.</div>';
                    return;
                }
        
                const productosSin = productosSinStock.success ? productosSinStock.data : [];
                // Filtrar productos con stock bajo, excluyendo los que tienen stock = 0
                const productosBajo = productosBajoStock.success ? productosBajoStock.data.filter(producto => producto.stock_producto > 0) : [];
                const stats = estadisticas.success ? estadisticas.data[0] : null;
        
                let html = '';
        
                // Mostrar estadísticas generales
                if (stats) {
                    html += `
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary">${stats.total_productos}</h4>
                                    <small class="text-muted">Total Productos</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success">${stats.con_stock}</h4>
                                    <small class="text-muted">Con Stock</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning">${stats.bajo_stock}</h4>
                                    <small class="text-muted">Stock Bajo</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-danger">${stats.sin_stock}</h4>
                                    <small class="text-muted">Sin Stock</small>
                                </div>
                            </div>
                        </div>
                    `;
                }
        
                // Mostrar alertas - PRIMERO sin stock (más crítico)
                if (productosSin.length > 0) {
                    html += `
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div class="flex-grow-1">
                                    <strong>Productos Sin Stock (${productosSin.length})</strong>
                                    <div class="mt-2">
                                        ${productosSin.slice(0, 3).map(p => 
                                            `<span class="badge bg-danger me-1">${p.nombre_producto}</span>`
                                        ).join('')}
                                        ${productosSin.length > 3 ? `<span class="badge bg-secondary">+${productosSin.length - 3} más</span>` : ''}
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" onclick="verTodosProductosSinStock()">
                                    Ver Todos
                                </button>
                            </div>
                        </div>
                    `;
                }
        
                // Mostrar alerta de stock bajo SOLO si hay productos con stock > 0 pero bajo
                if (productosBajo.length > 0) {
                    html += `
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div class="flex-grow-1">
                                    <strong>Productos con Stock Bajo (${productosBajo.length})</strong>
                                    <div class="mt-2">
                                        ${productosBajo.slice(0, 3).map(p => 
                                            `<span class="badge bg-warning text-dark me-1">${p.nombre_producto} (${p.stock_producto})</span>`
                                        ).join('')}
                                        ${productosBajo.length > 3 ? `<span class="badge bg-secondary">+${productosBajo.length - 3} más</span>` : ''}
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-warning" onclick="verTodosProductosBajoStock()">
                                    Ver Todos
                                </button>
                            </div>
                        </div>
                    `;
                }
        
                if (productosSin.length === 0 && productosBajo.length === 0) {
                    html += `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>¡Excelente!</strong> Todos los productos tienen stock suficiente.
                        </div>
                    `;
                }
        
                contenidoAlertas.innerHTML = html;
        
            } catch (error) {
                console.error('Error al cargar alertas de stock:', error);
                document.getElementById('contenidoAlertasStock').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar las alertas de stock.</div>';
            }
        }

function verTodosProductosSinStock() {
    if (notificacionesStock) {
        notificacionesStock.obtenerProductosSinStock().then(productos => {
            notificacionesStock.mostrarModalDetalles(productos);
        });
    }
}

function verTodosProductosBajoStock() {
    if (notificacionesStock) {
        notificacionesStock.obtenerProductosBajoStock().then(productos => {
            notificacionesStock.mostrarModalDetalles(productos);
        });
    }
}

function showSwalError(msg) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: msg || 'Ocurrió un error.',
        confirmButtonText: 'Aceptar'
    });
}