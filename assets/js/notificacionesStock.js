// Sistema de Notificaciones de Stock
class NotificacionesStock {
    constructor() {
        this.intervaloVerificacion = 30000; // 30 segundos
        this.notificacionesActivas = new Set();
        this.init();
    }

    init() {
        this.crearContenedorNotificaciones();
        this.verificarStockInicial();
        // this.iniciarVerificacionPeriodica(); // Eliminado para quitar notificación periódica
        this.configurarEventos();
    }

    crearContenedorNotificaciones() {
        // Crear contenedor de notificaciones si no existe
        if (!document.getElementById('notificacionesStock')) {
            const contenedor = document.createElement('div');
            contenedor.id = 'notificacionesStock';
            contenedor.className = 'position-fixed top-0 end-0 p-3';
            contenedor.style.zIndex = '9999';
            document.body.appendChild(contenedor);
        }
    }

    async verificarStockInicial() {
        try {
            const [productosBajoStock, productosSinStock] = await Promise.all([
                this.obtenerProductosBajoStock(),
                this.obtenerProductosSinStock()
            ]);

            this.mostrarNotificaciones(productosBajoStock, productosSinStock);
        } catch (error) {
            showSwalError('Error al verificar stock inicial.');
        }
    }

    // iniciarVerificacionPeriodica() {
    //     setInterval(() => {
    //         this.verificarStockInicial();
    //     }, this.intervaloVerificacion);
    // }

    async obtenerProductosBajoStock() {
        const response = await fetch(`../../controllers/admin/productos.php?action=getProductosBajoStock`);
        const data = await response.json();
        return data.success ? data.data : [];
    }

    async obtenerProductosSinStock() {
        const response = await fetch('../../controllers/admin/productos.php?action=getProductosSinStock');
        const data = await response.json();
        return data.success ? data.data : [];
    }

    mostrarNotificaciones(productosBajoStock, productosSinStock) {
        const contenedor = document.getElementById('notificacionesStock');
        
        // Limpiar notificaciones existentes
        contenedor.innerHTML = '';

        // Actualizar badge de notificaciones
        this.actualizarBadgeNotificaciones(productosBajoStock.length + productosSinStock.length);

        // Mostrar notificación de productos sin stock
        if (productosSinStock.length > 0) {
            this.mostrarNotificacion(
                'Productos Sin Stock',
                `${productosSinStock.length} producto(s) sin stock disponible`,
                'danger',
                productosSinStock
            );
        }

        // Mostrar notificación de productos con bajo stock
        if (productosBajoStock.length > 0) {
            this.mostrarNotificacion(
                'Stock Bajo',
                `${productosBajoStock.length} producto(s) con stock bajo`,
                'warning',
                productosBajoStock
            );
        }
    }

    actualizarBadgeNotificaciones(totalNotificaciones) {
        const badge = document.getElementById('badgeNotificaciones');
        if (badge) {
            if (totalNotificaciones > 0) {
                badge.textContent = totalNotificaciones;
                badge.style.display = 'block';
                
                // Agregar efecto de parpadeo si hay muchas notificaciones
                if (totalNotificaciones > 5) {
                    badge.classList.add('animate__animated', 'animate__pulse', 'animate__infinite');
                } else {
                    badge.classList.remove('animate__animated', 'animate__pulse', 'animate__infinite');
                }
            } else {
                badge.style.display = 'none';
                badge.classList.remove('animate__animated', 'animate__pulse', 'animate__infinite');
            }
        }
    }

    mostrarNotificacion(titulo, mensaje, tipo, productos) {
        const contenedor = document.getElementById('notificacionesStock');
        const notificacionId = `notif-${Date.now()}-${Math.random()}`;
        
        const notificacion = document.createElement('div');
        notificacion.id = notificacionId;
        notificacion.className = `alert alert-${tipo} alert-dismissible fade show mb-2`;
        notificacion.style.minWidth = '350px';
        notificacion.style.maxWidth = '400px';
        
        notificacion.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${tipo === 'danger' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'} me-2"></i>
                <div class="flex-grow-1">
                    <strong>${titulo}</strong><br>
                    <small>${mensaje}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <div class="mt-2">
                <button class="btn btn-sm btn-outline-${tipo}" onclick="notificacionesStock.verDetalles('${notificacionId}', ${JSON.stringify(productos).replace(/"/g, '&quot;')})">
                    Ver Detalles
                </button>
            </div>
        `;

        contenedor.appendChild(notificacion);
        this.notificacionesActivas.add(notificacionId);

        // Auto-ocultar después de 10 segundos
        setTimeout(() => {
            this.ocultarNotificacion(notificacionId);
        }, 10000);
    }

    ocultarNotificacion(notificacionId) {
        const notificacion = document.getElementById(notificacionId);
        if (notificacion) {
            notificacion.remove();
            this.notificacionesActivas.delete(notificacionId);
        }
    }

    verDetalles(notificacionId, productos) {
        this.ocultarNotificacion(notificacionId);
        this.mostrarModalDetalles(productos);
    }

    mostrarModalDetalles(productos) {
        // Crear modal si no existe
        let modal = document.getElementById('modalDetallesStock');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modalDetallesStock';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalles de Stock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="listaProductosStock"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="window.location.href='productos.php'">
                                Ir a Productos
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Poblar la lista de productos
        const lista = document.getElementById('listaProductosStock');
        lista.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${productos.map(producto => `
                            <tr class="${producto.stock_producto === null || producto.stock_producto === 0 ? 'table-danger' : 'table-warning'}">
                                <td>${producto.nombre_producto}</td>
                                <td>${producto.nombre_categoria || 'Sin categoría'}</td>
                                <td>
                                    <span class="badge ${producto.stock_producto === null || producto.stock_producto === 0 ? 'bg-danger' : 'bg-warning'}">
                                        ${producto.stock_producto === null || producto.stock_producto === 0 ? 'Sin stock' : producto.stock_producto}
                                    </span>
                                </td>
                                <td>$${parseFloat(producto.precio_producto).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        // Mostrar modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    // Método para actualizar el umbral de stock bajo
    setUmbralBajoStock(nuevoUmbral) {
        this.umbralBajoStock = nuevoUmbral;
    }

    // Método para obtener estadísticas de stock
    async obtenerEstadisticasStock() {
        try {
            const response = await fetch('../../controllers/admin/productos.php?action=getResumenStock');
            const data = await response.json();
            return data.success ? data.data[0] : null;
        } catch (error) {
            showSwalError('Error al obtener estadísticas de stock.');
            return null;
        }
    }

    configurarEventos() {
        // Evento para el botón de notificaciones
        const btnNotificaciones = document.getElementById('btnNotificaciones');
        if (btnNotificaciones) {
            btnNotificaciones.addEventListener('click', (e) => {
                e.preventDefault();
                this.mostrarTodasLasAlertas();
            });
        }
    }

    async mostrarTodasLasAlertas() {
        try {
            const [productosBajoStock, productosSinStock] = await Promise.all([
                this.obtenerProductosBajoStock(),
                this.obtenerProductosSinStock()
            ]);

            const todosLosProductos = [...productosSinStock, ...productosBajoStock];
            
            if (todosLosProductos.length > 0) {
                this.mostrarModalDetalles(todosLosProductos);
            } else {
                // Mostrar mensaje de que no hay alertas
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Notificaciones de Stock</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>¡Excelente!</strong> No hay alertas de stock pendientes.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                
                // Remover el modal del DOM después de cerrarlo
                modal.addEventListener('hidden.bs.modal', () => {
                    modal.remove();
                });
            }
        } catch (error) {
            showSwalError('Error al mostrar todas las alertas.');
        }
    }
}

// Inicializar el sistema de notificaciones
let notificacionesStock;
document.addEventListener('DOMContentLoaded', function() {
    notificacionesStock = new NotificacionesStock();
});

function showSwalError(msg) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: msg || 'Ocurrió un error.',
        confirmButtonText: 'Aceptar'
    });
} 