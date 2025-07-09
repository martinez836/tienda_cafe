let estaEditando = false;
const modalProducto = new bootstrap.Modal(document.getElementById('productModal'));

// Funciones globales
function editarProducto(id) {
    estaEditando = true;
    document.getElementById('productModalLabel').textContent = 'Editar Producto';
    
    fetch(`../../controllers/admin/productos.php?action=getProducto&id=${id}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.success) {
                const producto = datos.data;
                console.log('Producto recibido para edición:', producto);
                document.getElementById('productId').value = producto.idproductos;
                document.getElementById('productName').value = producto.nombre_producto;
                document.getElementById('productPrice').value = producto.precio_producto;
                document.getElementById('productStock').value = producto.stock_producto;
                document.getElementById('productCategory').value = producto.fk_categoria;
                document.getElementById('productEstado').value = producto.estados_idestados;
                document.getElementById('productTipo').value =
                    (producto.tipo_producto_idtipo_producto === undefined || producto.tipo_producto_idtipo_producto === null)
                        ? '1'
                        : String(producto.tipo_producto_idtipo_producto);
                modalProducto.show();
            }
        })
        .catch(error => console.error('Error al cargar producto:', error));
}

function eliminarProducto(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: '¿Está seguro de que desea eliminar este producto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../controllers/admin/productos.php?action=deleteProducto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(respuesta => respuesta.json())
            .then(datos => {
                if (datos.success) {
                    Swal.fire('¡Eliminado!', 'Producto eliminado exitosamente', 'success');
                    cargarProductos();
                    if (typeof notificacionesStock !== 'undefined') {
                        notificacionesStock.verificarStockInicial();
                    }
                } else {
                    Swal.fire('Error', 'Error al eliminar el producto', 'error');
                }
            })
            .catch(error => Swal.fire('Error', 'Error al eliminar el producto', 'error'));
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tablaProductos = $('#tablaProductos').DataTable({
        responsive: {
        details: {
            renderer: function (api, rowIdx, columns) {
                let data = columns
                    .filter(col => col.hidden)
                    .map(col => {
                        const label = `<strong>${col.title}:</strong>`;
                        return `<tr>
                                    <td class="text-end fw-bold">${col.title}</td>
                                    <td>${col.data}</td>
                                </tr>`;
                    })
                    .join('');

                return data
                    ? $('<table class="table table-sm table-bordered mb-0 w-100"/>').append(data)
                    : false;
            }
        }
    },
    columnDefs: [
        { responsivePriority: 1, targets: 1 }, // Nombre
        { responsivePriority: 2, targets: 2 }, // Precio
        { responsivePriority: 3, targets: 3 }, // Stock
        { responsivePriority: 4, targets: 4 }, // Categoría
        { responsivePriority: 5, targets: 5 }, // Estado
        { responsivePriority: 6, targets: 6 }, // Tipo
        { responsivePriority: 7, targets: 7 }  // Acciones
    ],
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
    });

    const formularioProducto = document.getElementById('productForm');
    const botonAgregarProducto = document.getElementById('addProductBtn');
    const botonGuardarProducto = document.getElementById('saveProduct');

    // Cargar categorías
    function cargarCategorias() {
        fetch('../../controllers/admin/categorias.php?action=getAllCategorias')
            .then(respuesta => respuesta.json())
            .then(datos => {
                const selectorCategoria = document.getElementById('productCategory');
                selectorCategoria.innerHTML = '<option value="">Seleccione una categoría</option>';
                
                if (datos.success && datos.data.length > 0) {
                    datos.data.forEach(categoria => {
                        selectorCategoria.innerHTML += `
                            <option value="${categoria.idcategorias}">${categoria.nombre_categoria}</option>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error al cargar categorías:', error));
    }

    // Cargar productos
    window.cargarProductos = function() {
        fetch('../../controllers/admin/productos.php?action=getAllProductos')
            .then(respuesta => respuesta.json())
            .then(datos => {
                tablaProductos.clear();
                if (datos.success && datos.data.length > 0) {
                    datos.data.forEach(producto => {
                        // Formatear el stock - mostrar "Sin stock" si es null o 0
                        const stockDisplay = producto.stock_producto === null || producto.stock_producto === 0 
                            ? '<span class="badge bg-warning">Sin stock</span>' 
                            : producto.stock_producto;
                        // Estado
                        const estadoBadge = producto.estados_idestados == 1
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                        // Tipo de producto
                        const tipoBadge = producto.tipo_producto_idtipo_producto == 1
                            ? '<span class="badge bg-secondary">Sin stock</span>'
                            : '<span class="badge bg-primary">Con stock</span>';
                        tablaProductos.row.add([
                            producto.idproductos,
                            producto.nombre_producto,
                            `$${parseFloat(producto.precio_producto).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                            stockDisplay,
                            producto.nombre_categoria,
                            estadoBadge,
                            tipoBadge,
                            `<button class="btn btn-sm btn-warning me-1" onclick="editarProducto(${producto.idproductos})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${producto.idproductos})">
                                <i class="fas fa-trash"></i>
                            </button>`
                        ]);
                    });
                } else {
                    tablaProductos.row.add([
                        '', '', '', '', '', '', '', '<span class="text-center">No hay productos para mostrar.</span>'
                    ]);
                }
                tablaProductos.draw();
            })
            .catch(error => {
                tablaProductos.clear();
                tablaProductos.row.add([
                    '', '', '', '', '', '', '', `<span class="text-danger">Error al cargar productos: ${error.message}</span>`
                ]);
                tablaProductos.draw();
            });
    };

    // Evento para agregar nuevo producto
    botonAgregarProducto.addEventListener('click', () => {
        estaEditando = false;
        document.getElementById('productModalLabel').textContent = 'Agregar Producto';
        formularioProducto.reset();
        document.getElementById('productId').value = '';
        document.getElementById('productEstado').value = '5';
        document.getElementById('productTipo').value = '2';
        modalProducto.show();
    });

    // Evento para guardar producto
    botonGuardarProducto.addEventListener('click', () => {
        const stockValue = document.getElementById('productStock').value;
        const datosProducto = {
            id: document.getElementById('productId').value,
            nombre: document.getElementById('productName').value,
            precio: document.getElementById('productPrice').value,
            stock: stockValue === '' ? null : parseInt(stockValue),
            categoria: document.getElementById('productCategory').value,
            estado: document.getElementById('productEstado').value,
            tipo_producto_idtipo_producto: document.getElementById('productTipo').value || '2'
        };
        const accion = estaEditando ? 'updateProducto' : 'createProducto';
        fetch(`../../controllers/admin/productos.php?action=${accion}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosProducto)
        })
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.success) {
                Swal.fire('¡Éxito!', estaEditando ? 'Producto actualizado exitosamente' : 'Producto creado exitosamente', 'success');
                modalProducto.hide();
                cargarProductos();
                if (typeof notificacionesStock !== 'undefined') {
                    notificacionesStock.verificarStockInicial();
                }
            } else {
                Swal.fire('Error', 'Error al guardar el producto', 'error');
            }
        })
        .catch(error => Swal.fire('Error', 'Error al guardar el producto', 'error'));
    });

    // Cargar datos iniciales
    cargarCategorias();
    cargarProductos();
});
